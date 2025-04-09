//automatic inserted by honeypot plugin
document.addEventListener('DOMContentLoaded', function () {
    fetch('/media/plg_baohoneypotar/honeypot-token.php')
        .then(response => response.json())
        .then(data => {
            if (!data.field || !data.token) return;

            const forms = document.querySelectorAll('form:not(.no-honeypot)');

            forms.forEach(form => {
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
});
