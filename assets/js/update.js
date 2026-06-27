(function () {
    var banner = document.getElementById('msUpdateBanner');
    var statusEl = document.getElementById('msUpdateStatus');
    var checkButtons = document.querySelectorAll('.ms-update-check');
    var runButtons = document.querySelectorAll('.ms-update-run');

    var cfg = window.MegaStatsUpdate || {};
    var fallbackApiUrl = cfg.checkUrl || null;

    if (!fallbackApiUrl && banner) {
        fallbackApiUrl = banner.getAttribute('data-api-url');
    }
    if (!fallbackApiUrl && checkButtons.length) {
        fallbackApiUrl = checkButtons[0].getAttribute('data-api-url');
    }

    var csrf = banner ? (banner.getAttribute('data-csrf') || '') : '';

    function buildApiUrl(action) {
        if (action === 'check' && cfg.checkUrl) {
            return cfg.checkUrl;
        }
        if (action === 'run' && cfg.runUrl) {
            return cfg.runUrl;
        }

        var pathname = window.location.pathname || '';
        if (pathname.indexOf('megastats') !== -1) {
            return pathname + '?api=update&action=' + action;
        }
        if (fallbackApiUrl) {
            return fallbackApiUrl.replace(/action=[^&]+/, 'action=' + action);
        }
        return null;
    }

    var apiCheckUrl = buildApiUrl('check');
    if (!apiCheckUrl || checkButtons.length === 0) {
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
        banner.classList.add(available ? 'alert-info' : 'alert-secondary');
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
        setStatus('Version <strong>' + (data.latest || '?') + '</strong> disponible (actuelle : v' + current + ').');
        runButtons.forEach(function (btn) {
            btn.classList.remove('d-none');
        });
    }

    function fetchStatus(refresh) {
        var url = apiCheckUrl + (refresh ? (apiCheckUrl.indexOf('?') >= 0 ? '&' : '?') + 'refresh=1' : '');
        checkButtons.forEach(function (btn) {
            btn.disabled = true;
        });
        fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
            .then(function (r) {
                if (!r.ok) {
                    throw new Error('HTTP ' + r.status);
                }
                return r.json();
            })
            .then(function (data) {
                renderStatus(data);
                checkButtons.forEach(function (btn) {
                    btn.disabled = false;
                });
            })
            .catch(function (err) {
                if (statusEl) {
                    setStatus('<span class="text-warning">AJAX indisponible (' + (err.message || 'réseau') + '). Utilisez le lien Vérifier MAJ.</span>');
                }
                checkButtons.forEach(function (btn) {
                    btn.disabled = false;
                });
            });
    }

    function runUpdate(btn) {
        var runUrl = buildApiUrl('run');
        if (!runUrl) {
            return;
        }

        if (btn) {
            btn.disabled = true;
        }
        setStatus('Mise à jour en cours… (1–2 min)');

        var body = new FormData();
        if (csrf) {
            body.append('csrf_token', csrf);
        }

        fetch(runUrl, {
            method: 'POST',
            body: body,
            credentials: 'same-origin',
            headers: { Accept: 'application/json' }
        })
            .then(function (r) {
                return r.json().then(function (data) {
                    if (!r.ok && !data.output) {
                        throw new Error('HTTP ' + r.status);
                    }
                    return data;
                });
            })
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
            .catch(function (err) {
                setStatus('<span class="text-danger">AJAX échoué (' + (err.message || 'réseau') + '). Utilisez le bouton Mettre à jour du bandeau.</span>');
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
