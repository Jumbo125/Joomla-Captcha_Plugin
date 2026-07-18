$ErrorActionPreference = 'Stop'

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location -LiteralPath $scriptDir

$outputDir  = Join-Path -Path $scriptDir -ChildPath 'dist'
$outputPath = Join-Path -Path $outputDir -ChildPath 'baohoney.zip'

# Diese Ordner werden vollständig ausgeschlossen.
$excludeDirectories = @(
    'dist',
    'idst',
    '.git',
    '.idea',
    '.vscode'
)

# Dateien mit diesen Namen werden überall ausgeschlossen.
$excludeFiles = @(
    'zip-project.ps1',
    'honeypot-debug.txt',
    'CLAUDE.md'
)

Write-Host ''
Write-Host 'Suche gültige Joomla-Manifestdatei ...' -ForegroundColor Cyan

$manifestCandidates = @()

# Nur XML-Dateien direkt im Projektstamm prüfen.
Get-ChildItem -LiteralPath $scriptDir -File -Filter '*.xml' |
    ForEach-Object {
        try {
            [xml]$xml = Get-Content -LiteralPath $_.FullName -Raw

            if (
                $null -ne $xml.DocumentElement -and
                $xml.DocumentElement.LocalName -eq 'extension'
            ) {
                $manifestCandidates += [PSCustomObject]@{
                    File = $_
                    Xml  = $xml
                }
            }
        }
        catch {
            Write-Host "Ungültige XML-Datei: $($_.Name)" -ForegroundColor Red
            Write-Host $_.Exception.Message -ForegroundColor DarkRed
        }
    }

if ($manifestCandidates.Count -eq 0) {
    Write-Host ''
    Write-Host 'FEHLER: Keine gültige Joomla-Manifestdatei gefunden.' -ForegroundColor Red
    Write-Host 'Die XML-Datei muss direkt im Projektordner liegen.' -ForegroundColor Yellow
    Write-Host 'Das Wurzelelement muss <extension> sein.' -ForegroundColor Yellow
    exit 1
}

if ($manifestCandidates.Count -gt 1) {
    Write-Host ''
    Write-Host 'FEHLER: Mehrere Joomla-Manifestdateien gefunden:' -ForegroundColor Red

    foreach ($candidate in $manifestCandidates) {
        Write-Host " - $($candidate.File.Name)" -ForegroundColor Yellow
    }

    Write-Host ''
    Write-Host 'Im Projektstamm darf nur ein Joomla-Installationsmanifest vorhanden sein.' -ForegroundColor Yellow
    exit 1
}

$manifestFile = $manifestCandidates[0].File
$manifestXml  = $manifestCandidates[0].Xml
$root         = $manifestXml.DocumentElement

Write-Host "Manifest gefunden: $($manifestFile.Name)" -ForegroundColor Green

$extensionType = $root.GetAttribute('type')
$method        = $root.GetAttribute('method')
$joomlaVersion = $root.GetAttribute('version')
$pluginGroup   = $root.GetAttribute('group')

if ([string]::IsNullOrWhiteSpace($extensionType)) {
    Write-Host ''
    Write-Host 'FEHLER: Im <extension>-Element fehlt das Attribut type.' -ForegroundColor Red
    exit 1
}

Write-Host "Erweiterungstyp: $extensionType" -ForegroundColor DarkGray

if ($extensionType -eq 'plugin') {
    if ([string]::IsNullOrWhiteSpace($pluginGroup)) {
        Write-Host ''
        Write-Host 'FEHLER: Bei einem Plugin fehlt group="..." im <extension>-Element.' -ForegroundColor Red
        exit 1
    }

    Write-Host "Plugin-Gruppe: $pluginGroup" -ForegroundColor DarkGray
}

if ([string]::IsNullOrWhiteSpace($method)) {
    Write-Host 'WARNUNG: method="upgrade" fehlt im Manifest.' -ForegroundColor Yellow
}

if ([string]::IsNullOrWhiteSpace($joomlaVersion)) {
    Write-Host 'WARNUNG: Das version-Attribut fehlt im <extension>-Element.' -ForegroundColor Yellow
}

$filesNode = $manifestXml.SelectSingleNode('//files')

if ($null -eq $filesNode) {
    Write-Host ''
    Write-Host 'FEHLER: Im Manifest fehlt der <files>-Bereich.' -ForegroundColor Red
    exit 1
}

# Ausgabeverzeichnis erstellen.
if (-not (Test-Path -LiteralPath $outputDir)) {
    New-Item `
        -Path $outputDir `
        -ItemType Directory `
        -Force |
        Out-Null
}

# Bereits vorhandene ZIP löschen.
if (Test-Path -LiteralPath $outputPath) {
    Remove-Item -LiteralPath $outputPath -Force
}

# Dateien für das ZIP-Paket bestimmen.
$filesToPack = @(
    Get-ChildItem `
        -LiteralPath $scriptDir `
        -File `
        -Recurse `
        -Force |
        Where-Object {
            $relativePath = $_.FullName.Substring($scriptDir.Length)
            $relativePath = $relativePath.TrimStart('\', '/')

            $pathParts   = $relativePath -split '[\\/]'
            $topFolder  = $pathParts[0]
            $fileName   = $_.Name

            $directoryAllowed = $true
            $fileAllowed      = $true

            foreach ($excludedDirectory in $excludeDirectories) {
                if ($topFolder.Equals(
                    $excludedDirectory,
                    [System.StringComparison]::OrdinalIgnoreCase
                )) {
                    $directoryAllowed = $false
                    break
                }
            }

            foreach ($excludedFile in $excludeFiles) {
                if ($fileName.Equals(
                    $excludedFile,
                    [System.StringComparison]::OrdinalIgnoreCase
                )) {
                    $fileAllowed = $false
                    break
                }
            }

            $directoryAllowed -and $fileAllowed
        }
)

if ($filesToPack.Count -eq 0) {
    Write-Host ''
    Write-Host 'FEHLER: Keine Dateien zum Packen gefunden.' -ForegroundColor Red
    exit 1
}

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$zip = $null

try {
    $zip = [System.IO.Compression.ZipFile]::Open(
        $outputPath,
        [System.IO.Compression.ZipArchiveMode]::Create
    )

    Write-Host ''
    Write-Host 'Packe Dateien ...' -ForegroundColor Cyan

    foreach ($file in $filesToPack) {
        $relativePath = $file.FullName.Substring($scriptDir.Length)
        $relativePath = $relativePath.TrimStart('\', '/')

        # ZIP-Pfade müssen normale Schrägstriche enthalten.
        $zipEntryPath = $relativePath -replace '\\', '/'

        [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
            $zip,
            $file.FullName,
            $zipEntryPath,
            [System.IO.Compression.CompressionLevel]::Optimal
        ) | Out-Null

        Write-Host "  + $zipEntryPath" -ForegroundColor DarkGray
    }
}
catch {
    Write-Host ''
    Write-Host 'FEHLER beim Erstellen der ZIP-Datei:' -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red

    if (Test-Path -LiteralPath $outputPath) {
        Remove-Item -LiteralPath $outputPath -Force -ErrorAction SilentlyContinue
    }

    exit 1
}
finally {
    if ($null -ne $zip) {
        $zip.Dispose()
    }
}

Write-Host ''
Write-Host 'Kontrolliere fertige ZIP-Datei ...' -ForegroundColor Cyan

$zipCheck = $null

try {
    $zipCheck = [System.IO.Compression.ZipFile]::OpenRead($outputPath)

    $entryNames = @(
        $zipCheck.Entries |
            ForEach-Object {
                $_.FullName
            }
    )

    # Manifest muss direkt im ZIP-Stamm liegen.
    if ($manifestFile.Name -notin $entryNames) {
        Write-Host ''
        Write-Host 'FEHLER: Das Manifest liegt nicht direkt im ZIP-Stamm.' -ForegroundColor Red
        Write-Host "Erwartet: $($manifestFile.Name)" -ForegroundColor Yellow

        Write-Host ''
        Write-Host 'Gefundene XML-Dateien:' -ForegroundColor Yellow

        $xmlEntries = @(
            $entryNames |
                Where-Object {
                    $_ -like '*.xml'
                }
        )

        if ($xmlEntries.Count -eq 0) {
            Write-Host ' - Keine XML-Dateien gefunden.' -ForegroundColor Red
        }
        else {
            foreach ($xmlEntry in $xmlEntries) {
                Write-Host " - $xmlEntry"
            }
        }

        exit 1
    }

    # Prüfen, dass CLAUDE.md wirklich nicht enthalten ist.
    $claudeEntries = @(
        $entryNames |
            Where-Object {
                [System.IO.Path]::GetFileName($_).Equals(
                    'CLAUDE.md',
                    [System.StringComparison]::OrdinalIgnoreCase
                )
            }
    )

    if ($claudeEntries.Count -gt 0) {
        Write-Host ''
        Write-Host 'FEHLER: CLAUDE.md wurde trotz Ausschluss gepackt:' -ForegroundColor Red

        foreach ($claudeEntry in $claudeEntries) {
            Write-Host " - $claudeEntry" -ForegroundColor Red
        }

        exit 1
    }

    Write-Host "Manifest im ZIP-Stamm: $($manifestFile.Name)" -ForegroundColor Green
    Write-Host 'CLAUDE.md ist nicht im ZIP enthalten.' -ForegroundColor Green
    Write-Host "Anzahl gepackter Dateien: $($zipCheck.Entries.Count)" -ForegroundColor Green
}
catch {
    Write-Host ''
    Write-Host 'FEHLER bei der ZIP-Kontrolle:' -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
    exit 1
}
finally {
    if ($null -ne $zipCheck) {
        $zipCheck.Dispose()
    }
}

Write-Host ''
Write-Host 'ZIP erfolgreich erstellt:' -ForegroundColor Green
Write-Host $outputPath -ForegroundColor Cyan
Write-Host ''