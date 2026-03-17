<p align="center">
  <img src="https://github.com/Jumbo125/Joomla-Captcha_Plugin/blob/main/icon.png" alt="Joomla-Captcha_Plugin" width="300">
</p>
# 🛡️ BAhoneypotAR – Bot-Schutz für Joomla 4/5

Ein leichtgewichtiges, datenschutzfreundliches Honeypot-Plugin für Joomla 4 und Joomla 5. – schützt zuverlässig vor Bot-Spam ohne Google reCAPTCHA.

---

## 🇩🇪 Deutsch

### 🔍 Beschreibung

Dieses Plugin fügt unsichtbare, dynamisch generierte Felder in ein Formular ein. Ein Bot füllt diese automatisch aus – ein echter Besucher jedoch nicht. Wird das Feld ausgefüllt oder ein Sicherheitsmerkmal verletzt, wird das Formular blockiert.

### ✅ Features

- Dynamisch generiertes Honeypot-Feld
- SHA-256 Token-Überprüfung
- Zeitprüfung: Absenden < 3 Sekunden = Bot
- Funktioniert mit AJAX-Formularen
- Keine externen Dienste (Google etc.)
- DSGVO-konform & Open Source

---

### ⚙️ Installation & Konfiguration

1. Plugin über den Joomla Erweiterungsmanager installieren.
2. Plugin **System - BAOhoneypotAR** aktivieren.
3. Zwei Werte im Plugin setzen:

| Feld | Bedeutung |
|------|-----------|
| `Geheimer Schlüssel` (`secret`) | Wird zur Token-Generierung genutzt. |
| `Geheimer Präfix` (`secret_praefix`) | Dient zur Generierung des Feldnamens (z. B. `hp`) |

> ⚠️ Der Präfix sollte mind. 3–6 zufällige Zeichen enthalten, z. B. `abc123`.

---

---

## 🛡️ Automatische Integration per JavaScript

Das Plugin kann die Schutz-Felder automatisch in alle Formulare einfügen – ganz ohne manuelles Einfügen von Skriptcode.

### Kontrollkästchen im Plugin:
| Feld | Bedeutung |
|------|-----------|
| `Automatisch in alle Formulare einfügen` (`auto_insert`) |  
Wenn aktiviert, wird der JavaScript-Code automatisch auf jeder Seite geladen. Er fügt den Honeypot-Schutz in alle Formulare **ein, die _nicht_ die Klasse `no-honeypot` besitzen**. |

💡 Das bedeutet:
- Kein Einfügen von HTML- oder JS-Code mehr nötig
- Jedes Formular ist geschützt – außer, du schließt es explizit aus

### 🚫 Formular vom Honeypot ausschließen

Wenn du ein Formular **nicht mit dem Honeypot schützen möchtest**, gib ihm einfach die folgende CSS-Klasse:

```html
<form class="no-honeypot">
```

### 🧩 JS-Snippet für das Formular

In ein HTML-Feld innerhalb deines Formulars einfügen:

```html
<script>
document.addEventListener('DOMContentLoaded', function () {
    fetch('/media/plg_baohoneypotar/honeypot-token.php')
        .then(response => response.json())
        .then(data => {
            const form = document.querySelector('form');
            if (!form || !data.field || !data.token) return;

            const honeypot = document.createElement('input');
            honeypot.type = 'text';
            honeypot.name = data.field;
            honeypot.style.position = 'absolute';
            honeypot.style.left = '-10000px';
            form.appendChild(honeypot);

            const token = document.createElement('input');
            token.type = 'hidden';
            token.name = data.field + 'token';
            token.value = data.token;
            form.appendChild(token);

            const timeField = document.createElement('input');
            timeField.type = 'hidden';
            timeField.name = data.field + '_token_time';
            timeField.value = Math.floor(Date.now() / 1000);
            form.appendChild(timeField);
        });
});
</script>
```
### 🐞 Debug-Modus

Wenn der Debug-Modus im Plugin aktiviert ist (`Debug-Modus = Ja`), erstellt das Plugin zwei Logdateien im Joomla-Root-Verzeichnis:

- `/honeypot-debug.txt` – enthält Prüfungen zum Secret, Token und Zeitfaktor
- `/js-check.txt` – enthält zusätzliche Informationen über die vom JavaScript gesetzten Felder

> Nützlich zur Fehlersuche oder zum Testen der Funktionsweise bei Formularübermittlungen.

## Support

Donate with PayPal ☕
Wenn dir das Projekt hilft und du mir einen Kaffee ausgeben willst:

[![Donate with PayPal ☕](https://img.shields.io/badge/Donate-PayPal-00457C?logo=paypal&logoColor=white)](https://www.paypal.me/andreasrottmann92)


## 🇬🇧 English

### 🔍 Description

This plugin adds invisible, dynamically generated fields to a form. Bots tend to fill them out automatically — real visitors do not. If the honeypot field is filled or a security check fails, the form submission will be blocked.

### ✅ Features

- Dynamically generated honeypot field
- SHA-256 token validation
- Time check: submissions under 3 seconds = bot
- Works with AJAX forms
- No external services (e.g., Google)
- GDPR-compliant & open source

---

### ⚙️ Installation & Configuration

1. Install the plugin via Joomla's Extension Manager.
2. Activate the plugin **System - BAOhoneypotAR**.
3. Set two values in the plugin settings:

| Field | Meaning |
|-------|---------|
| `Secret` (`secret`) | Used to generate a hash token. |
| `Secret Prefix` (`secret_praefix`) | Used to generate the field name (e.g., `hp`). |

> ⚠️ The prefix should be 3–6 random characters, e.g., `abc123`.

---

### Checkbox in the Plugin:
| Field | Description |
|-------|-------------|
| `Automatically insert into all forms` (`auto_insert`) |  
When enabled, the JavaScript code is automatically loaded on every page. It adds the honeypot protection to all forms **except those with the `no-honeypot` class**. |

💡 This means:
- No need to manually insert any HTML or JS code
- Every form is protected – unless you explicitly exclude it

### 🚫 Excluding a form from the honeypot

If you want to **exclude a specific form from honeypot protection**, simply add the following CSS class to the form:

```html
<form class="no-honeypot">
```

### 🧩 JS Snippet for Your Form

Insert into an HTML field inside your form:

```html
<script>
document.addEventListener('DOMContentLoaded', function () {
    fetch('/media/plg_baohoneypotar/honeypot-token.php')
        .then(response => response.json())
        .then(data => {
            const form = document.querySelector('form');
            if (!form || !data.field || !data.token) return;

            const honeypot = document.createElement('input');
            honeypot.type = 'text';
            honeypot.name = data.field;
            honeypot.style.position = 'absolute';
            honeypot.style.left = '-10000px';
            form.appendChild(honeypot);

            const token = document.createElement('input');
            token.type = 'hidden';
            token.name = data.field + 'token';
            token.value = data.token;
            form.appendChild(token);

            const timeField = document.createElement('input');
            timeField.type = 'hidden';
            timeField.name = data.field + '_token_time';
            timeField.value = Math.floor(Date.now() / 1000);
            form.appendChild(timeField);
        });
});
</script>
```

### 🐞 Debug Mode

If the **debug mode** is enabled in the plugin (`Debug Mode = Yes`), it will generate two log files in your Joomla root directory:

- `/honeypot-debug.txt` – contains checks for the **secret**, **token**, and **time factor**
- `/js-check.txt` – contains additional information about the JavaScript-injected fields

## Support

Donate with PayPal ☕ 
If this project helps you, feel free to buy me a coffee:

[![Donate with PayPal ☕](https://img.shields.io/badge/Donate-PayPal-00457C?logo=paypal&logoColor=white)](https://www.paypal.me/andreasrottmann92)

> 🛠️ Useful for troubleshooting or verifying the plugin behavior on form submissions.

