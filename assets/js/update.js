(function () {
    var banner = document.getElementById('msUpdateBanner');
    var statusEl = document.getElementById('msUpdateStatus');
    var checkButtons = document.querySelectorAll('.ms-update-check');
    var runButtons = document.querySelectorAll('.ms-update-run');

    var apiUrl = null;
    if (banner) {
        apiUrl = banner.getAttribute('data-api-url');
    }
    if (!apiUrl && checkButtons.length) {
        apiUrl = checkButtons[0].getAttribute('data-api-url');
    }
    if (!apiUrl && runButtons.length) {
        apiUrl = runButtons[0].getAttribute('data-api-url');
    }

    var csrf = banner ? (banner.getAttribute('data-csrf') || '') : '';

    if (!apiUrl) {
        return;
    }

    function setStatus(html) {
        if (statusEl) {
            statusEl.innerHTML = html;
        }
    }

    function setBannerStyle(available) {
        if (!banner) {
            return;
        }
        banner.classList.remove('alert-info', 'alert-secondary', 'alert-success');
        if (available) {
            banner.classList.add('alert-info');
        } else {
            banner.classList.add('alert-secondary');
        }
    }

    function renderStatus(data) {
        if (!data) {
            return;
        }

        var current = data.current || '?';

        if (!data.update_available) {
            setBannerStyle(false);
            setStatus('MegaStats <strong>v' + current + '</strong> — à jour.');
            runButtons.forEach(function (btn) {
                btn.classList.add('d-none');
            });
            return;
        }

        setBannerStyle(true);
        if (banner) {
            banner.classList.remove('d-none');
        }
        setStatus('Version <strong>' + (data.latest || '?') + '</strong> disponible (actuelle : v' + current + ').');
        runButtons.forEach(function (btn) {
            btn.classList.remove('d-none');
        });
    }

    function fetchStatus(refresh) {
        var url = apiUrl + (refresh ? '&refresh=1' : '');
        checkButtons.forEach(function (btn) {
            btn.disabled = true;
        });
        fetch(url, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                renderStatus(data);
                checkButtons.forEach(function (btn) {
                    btn.disabled = false;
                });
            })
            .catch(function () {
                if (statusEl) {
                    setStatus('<span class="text-warning">Impossible de vérifier les mises à jour.</span>');
                }
                checkButtons.forEach(function (btn) {
                    btn.disabled = false;
                });
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

    checkButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            fetchStatus(true);
        });
    });

    runButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            runUpdate(btn);
        });
    });
})();
