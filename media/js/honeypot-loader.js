//automatic inserted by honeypot plugin
document.addEventListener('DOMContentLoaded', function () {
    const DEBUG = true; // Setze auf false, um Logging zu deaktivieren

    if (DEBUG) console.log('[Honeypot] Initialisierung läuft...');

    fetch('/index.php?option=com_ajax&plugin=baohoneypotar&format=json')
        .then(response => {
            if (DEBUG) console.log('[Honeypot] Response erhalten:', response);
            return response.json();
        })
        .then(data => {
            console.log(data.data);
            const item = data.data[0]; // 👈 Ersten Eintrag aus dem Array holen
            if (DEBUG) console.log('[Honeypot] Daten empfangen:', item);

            if (!item.field || !item.token) {
                if (DEBUG) console.warn('[Honeypot] Ungültige Antwort vom Server – Abbruch.');
                return;
            }

            const forms = document.querySelectorAll('form:not(.no-honeypot)');

            if (forms.length === 0 && DEBUG) {
                console.warn('[Honeypot] Keine passenden Formulare gefunden.');
            }

            forms.forEach(form => {
                if (DEBUG) console.log('[Honeypot] Feld in Formular einfügen:', form);

                const honeypot = document.createElement('input');
                honeypot.type = 'text';
                honeypot.name = item.field;
                honeypot.style.position = 'absolute';
                honeypot.style.left = '-10000px';
                form.appendChild(honeypot);

                const token = document.createElement('input');
                token.type = 'hidden';
                token.name = item.field + 'token';
                token.value = item.token;
                form.appendChild(token);

                const timeField = document.createElement('input');
                timeField.type = 'hidden';
                timeField.name = item.field + '_token_time';
                timeField.value = Math.floor(Date.now() / 1000);
                form.appendChild(timeField);

                if (DEBUG) {
                    console.log('[Honeypot] Honeypot-Feldname:', honeypot.name);
                    console.log('[Honeypot] Token-Wert:', token.value);
                    console.log('[Honeypot] Zeit-Wert:', timeField.value);
                }
            });
        })
        .catch(error => {
            if (DEBUG) console.error('[Honeypot] Fehler beim Laden der Daten:', error);
        });
});
