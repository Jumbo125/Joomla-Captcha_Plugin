// Automatic inserted by honeypot plugin
document.addEventListener('DOMContentLoaded', () => {
    const DEBUG = Joomla.getOptions('baohoneypotar')?.debug ?? false;
    const base = Joomla.getOptions('system.paths')?.base ?? '';

    if (DEBUG) {
        console.log('[Honeypot] Debug-Modus aktiviert');
        console.log('[Honeypot] Initialisierung läuft...');
    }

    const unwrapHoneypotResponse = (value) => {
        let current = value;

        // Unterstützt unterschiedliche Joomla-/com_ajax-Antwortformen.
        for (let depth = 0; depth < 5; depth += 1) {
            if (Array.isArray(current)) {
                current = current[0];
                continue;
            }

            if (
                current &&
                typeof current === 'object' &&
                !current.field &&
                current.data !== undefined
            ) {
                current = current.data;
                continue;
            }

            break;
        }

        return current;
    };

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
            const item = unwrapHoneypotResponse(data);

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
                // Keine doppelten Honeypot-Felder einfügen.
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
                    'position:absolute!important;' +
                    'left:-10000px!important;' +
                    'width:1px!important;' +
                    'height:1px!important;' +
                    'overflow:hidden!important;';
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
