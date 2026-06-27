(function () {
    var banner = document.getElementById('msUpdateBanner');
    var apiUrl = banner ? banner.getAttribute('data-api-url') : null;
    var csrf = banner ? (banner.getAttribute('data-csrf') || '') : '';
    var statusEl = document.getElementById('msUpdateStatus');
    var btnCheck = document.getElementById('msUpdateCheck');
    var runButtons = document.querySelectorAll('#msUpdateRun');

    if (!apiUrl && runButtons.length) {
        apiUrl = runButtons[0].getAttribute('data-api-url');
    }

    if (!apiUrl) {
        return;
    }

    function setStatus(html) {
        if (statusEl) {
            statusEl.innerHTML = html;
        }
    }

    function renderStatus(data) {
        if (!data || !data.update_available) {
            if (banner) {
                banner.classList.add('d-none');
            }
            runButtons.forEach(function (btn) {
                btn.classList.add('d-none');
            });
            return;
        }

        if (banner) {
            banner.classList.remove('d-none');
        }
        setStatus('Version <strong>' + (data.latest || '?') + '</strong> disponible (actuelle : ' + (data.current || '?') + ').');
    }

    function fetchStatus(refresh) {
        var url = apiUrl + (refresh ? '&refresh=1' : '');
        fetch(url, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(renderStatus)
            .catch(function () {
                if (statusEl) {
                    setStatus('<span class="text-warning">Impossible de vérifier les mises à jour.</span>');
                }
            });
    }

    function runUpdate(btn) {
        if (btn) {
            btn.disabled = true;
        }
        setStatus('Mise à jour en cours… (1–2 min)');

        var body = new FormData();
        if (csrf) {
            body.append('csrf_token', csrf);
        }

        fetch(apiUrl.replace('action=check', 'action=run'), {
            method: 'POST',
            body: body,
            credentials: 'same-origin'
        })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.ok) {
                    setStatus('<span class="text-success">Mise à jour terminée. Rechargez la page (Ctrl+F5).</span>');
                    if (res.status) {
                        renderStatus(res.status);
                    }
                } else {
                    setStatus('<span class="text-danger">Échec :</span><pre class="small mb-0 mt-1 text-start">' + (res.output || res.error || '?') + '</pre>');
                }
                if (btn) {
                    btn.disabled = false;
                }
            })
            .catch(function () {
                setStatus('<span class="text-danger">Erreur réseau.</span>');
                if (btn) {
                    btn.disabled = false;
                }
            });
    }

    if (btnCheck) {
        btnCheck.addEventListener('click', function () {
            fetchStatus(true);
        });
    }

    runButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            runUpdate(btn);
        });
    });

    if (banner && banner.classList.contains('d-none')) {
        fetchStatus(false);
    }
})();
