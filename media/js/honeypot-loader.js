// Automatic inserted by honeypot plugin
document.addEventListener('DOMContentLoaded', () => {
    const DEBUG = Joomla.getOptions('baohoneypotar')?.debug ?? false;
    const base = Joomla.getOptions('system.paths')?.base ?? '';

    if (DEBUG) {
        console.log('[Honeypot] Debug-Modus aktiviert');
        console.log('[Honeypot] Initialisierung läuft...');
    }

    fetch(`${base}/index.php?option=com_ajax&plugin=baohoneypotar&format=json`)
        .then((response) => {
            if (DEBUG) {
                console.log('[Honeypot] Response erhalten:', response);
            }

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            return response.json();
        })
        .then((data) => {
            // Unterstützt mehrere Joomla-com_ajax-Antwortformen:
            // { field, token }
            // { data: { field, token } }
            // [{ success: true, data: { field, token } }]
            // { data: [{ field, token }] }
            const response = Array.isArray(data) ? data[0] : data;
            const payload = response?.data ?? response;
            const item = Array.isArray(payload) ? payload[0] : payload;

            if (DEBUG) {
                console.log('[Honeypot] Rohdaten:', data);
                console.log('[Honeypot] Daten empfangen:', item);
            }

            if (!item?.field || !item?.token) {
                if (DEBUG) {
                    console.warn(
                        '[Honeypot] Ungültige Antwort vom Server – Abbruch.',
                        data
                    );
                }
                return;
            }

            const forms = document.querySelectorAll('form:not(.no-honeypot)');

            if (forms.length === 0) {
                if (DEBUG) {
                    console.warn('[Honeypot] Keine passenden Formulare gefunden.');
                }
                return;
            }

            forms.forEach((form) => {
                // Verhindert doppelte Felder bei erneutem Laden des Scripts.
                if (form.querySelector(`[name="${CSS.escape(item.field)}"]`)) {
                    return;
                }

                if (DEBUG) {
                    console.log('[Honeypot] Feld in Formular einfügen:', form);
                }

                const honeypot = document.createElement('input');
                honeypot.type = 'text';
                honeypot.name = item.field;
                honeypot.autocomplete = 'off';
                honeypot.tabIndex = -1;
                honeypot.setAttribute('aria-hidden', 'true');
                honeypot.style.cssText =
                    'position:absolute!important;left:-10000px!important;' +
                    'width:1px!important;height:1px!important;overflow:hidden!important;';
                form.appendChild(honeypot);

                const token = document.createElement('input');
                token.type = 'hidden';
                token.name = `${item.field}token`;
                token.value = item.token;
                form.appendChild(token);

                const timeField = document.createElement('input');
                timeField.type = 'hidden';
                timeField.name = `${item.field}_token_time`;
                timeField.value = String(Math.floor(Date.now() / 1000));
                form.appendChild(timeField);

                if (DEBUG) {
                    console.log('[Honeypot] Honeypot-Feldname:', honeypot.name);
                    console.log('[Honeypot] Token-Wert:', token.value);
                    console.log('[Honeypot] Zeit-Wert:', timeField.value);
                }
            });
        })
        .catch((error) => {
            console.error('[Honeypot] Fehler beim Laden der Daten:', error);
        });
});
