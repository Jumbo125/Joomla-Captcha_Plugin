# Projektübersicht: BAOhoneypotAR

## Zweck
Dieses Projekt ist ein Joomla-System-Plugin namens `baohoneypotar`. Es implementiert einen datenschutzfreundlichen Honeypot-Bot-Schutz für Formular-POSTs in Joomla 4 und Joomla 5.

## Hauptfunktionalität
- Fügt unsichtbare Honeypot-Felder in Formulare ein.
- Validiert ein SHA-256-basiertes Token, das mit einem geheimen Plugin-Parameter generiert wird.
- Prüft die Formular-Sendezeit: Absenden innerhalb von weniger als 3 Sekunden gilt als Bot.
- Unterstützt automatische JavaScript-Injektion in Formulare via `auto_insert`.
- Kann AJAX-basiert Feldname und Token bereitstellen.
- Schreibt Debug-Informationen in `honeypot-debug.txt`, falls Debug-Modus aktiv.

## Wichtige Dateien
- `README.md`: Projektbeschreibung, Installationshinweise und Beispiel-JS.
- `baohoneypotar.xml`: Joomla-Plugin-Metadaten, Konfiguration, Sprachdateien, Webassets und Update-Server.
- `src/Plugin.php`: Kernlogik des Plugins.
- `services/provider.php`: Service-Provider für Joomla DI/Plugin-Instanziierung.
- `media/js/honeypot-loader.js`: clientseitiges JavaScript, das das AJAX-Endpunkt-Ergebnis in alle Formulare einfügt.
- `media/joomla.asset.json`: Joomla Web Asset Registry für das Script.
- `language/en-GB/plg_system_baohoneypotar.ini`: Englische Sprachstrings.
- `language/de-DE/plg_system_baohoneypotar.ini`: Deutsche Sprachstrings.

## Architektur und Ablauf
1. `onBeforeCompileHead()` fügt bei aktiviertem `auto_insert` das Asset `honeypot-loader` ein und übergibt Debug-Optionen an das Frontend.
2. `honeypot-loader.js` ruft den AJAX-Endpunkt auf: `index.php?option=com_ajax&plugin=baohoneypotar&format=json`.
3. Der AJAX-Endpunkt `onAjaxBaohoneypotar()` erzeugt ein zufälliges Feld mit Präfix und gibt Feldname + Token als JSON zurück.
4. Das JavaScript fügt in jedes Formular ohne Klasse `no-honeypot`:
   - ein unsichtbares Textfeld für den Honeypot,
   - ein verstecktes Token-Feld,
   - ein verstecktes Zeitfeld.
5. Bei jedem POST-Request prüft `onAfterInitialise()`:
   - Ausnahmen per `honeypot_exceptions`
   - übersprungene POST-Keys per `honeypot_skip_keys`
   - Vorhandensein eines Honeypot-Felds
   - leeren Honeypot-Wert
   - korrekten Token
   - ausreichende Zeitspanne seit Token-Erstellung
6. Bei einem Fehler wird die Anfrage mit `403 Forbidden` blockiert und JSON-Antwort zurückgegeben.

## Plugin-Optionen
- `secret`: Geheimer Schlüssel für Token-Hash.
- `secret_praefix`: Präfix zur Erzeugung dynamischer Feldnamen.
- `honeypot_exceptions`: URI-Teile, bei denen die Prüfung übersprungen wird.
- `honeypot_skip_keys`: POST-Felder, die die Prüfung auslassen.
- `auto_insert`: automatische JS-Injektion in alle Formulare.
- `debug`: aktiviert Log-Ausgabe nach `honeypot-debug.txt`.

## Besondere Hinweise
- `baohoneypotar.xml` definiert das Plugin und enthält ein Beispiel-Skript für den manuellen Einbau, falls `auto_insert` deaktiviert ist.
- Das JavaScript nimmt an, dass die AJAX-Antwort im Joomla-Standardformat vorliegt. In `honeypot-loader.js` wird `data.data[0]` verwendet, daher ist zu prüfen, dass die Antwortstruktur konsistent bleibt.
- Die Funktion `blockRequest()` beendet die Ausführung mit `exit`, was bei Joomla-Umgebungen beabsichtigt ist, aber das Verhalten bei komplexen AJAX-Workflows berücksichtigen muss.

## Mögliche Verbesserungen
- Einheitliche Sprachstring-Namen und doppelte `debug`-Felder in `baohoneypotar.xml` bereinigen.
- Frontend-Formularauswahl weiter beschränken, falls nicht alle Formular-POSTs geschützt werden sollen.
- Bessere Fehlerbehandlung bei ungültiger AJAX-Antwort im Loader-Skript.
- Dokumentation zur Nutzung des `no-honeypot`-Ausschlusses und zu bekannten Ausnahme-Pfaden erweitern.

## Fazit
Das Projekt ist ein funktionales Joomla-System-Plugin zur Bot-Erkennung über einen dynamischen Honeypot. Es kombiniert serverseitige POST-Prüfung mit clientseitiger JS-Injektion und ist für Joomla 4/5 ausgelegt.
