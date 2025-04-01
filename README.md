# 🛡️ BAOhoneypotAR – Bot-Schutz für Joomla 4/5

Ein leichtgewichtiges, datenschutzfreundliches Honeypot-Plugin für Joomla 4 und Joomla 5. Speziell entwickelt für Formulare wie **BAOforms** – schützt zuverlässig vor Bot-Spam ohne Google reCAPTCHA.

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


