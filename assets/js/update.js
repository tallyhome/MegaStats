(function () {
    'use strict';

    var banner = document.getElementById('msUpdateBanner');
    var statusEl = document.getElementById('msUpdateStatus');
    var checkButtons = document.querySelectorAll('.ms-update-check');
    var runButtons = document.querySelectorAll('.ms-update-run');
    var runForms = document.querySelectorAll('.ms-update-run-form');

    var cfg = window.MegaStatsUpdate || {};
    var fallbackApiUrl = cfg.checkUrl || (banner ? banner.getAttribute('data-api-url') : null);
    var csrf = banner ? (banner.getAttribute('data-csrf') || '') : '';

    function isDarkTheme() {
        return document.documentElement.getAttribute('data-bs-theme') === 'dark';
    }

    function swalTheme() {
        if (!isDarkTheme()) {
            return {};
        }

        return {
            background: '#1a1d21',
            color: '#dee2e6',
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#6c757d',
        };
    }

    function cleanUpdateQueryFromUrl() {
        if (!window.history.replaceState || !window.location.search) {
            return;
        }

        var params = new URLSearchParams(window.location.search);
        if (!params.has('update') && !params.has('update_msg')) {
            return;
        }

        params.delete('update');
        params.delete('update_msg');
        var query = params.toString();
        var next = window.location.pathname + (query ? '?' + query : '');
        window.history.replaceState({}, document.title, next);
    }

    function showFlashModal(payload) {
        if (!payload || typeof Swal === 'undefined') {
            return;
        }

        var opts = Object.assign({
            title: payload.title || '',
            text: payload.message || '',
            icon: payload.type || 'info',
            confirmButtonText: 'OK',
        }, swalTheme());

        if (payload.autohide && payload.autohide > 0) {
            opts.timer = payload.autohide;
            opts.timerProgressBar = true;
            opts.showConfirmButton = false;
        }

        Swal.fire(opts).finally(cleanUpdateQueryFromUrl);
    }

    function confirmRunUpdate(form) {
        var version = form.getAttribute('data-version') || '?';

        if (typeof Swal === 'undefined') {
            if (window.confirm('Installer MegaStats v' + version + ' ? (1 à 2 minutes)')) {
                form.submit();
            }
            return;
        }

        Swal.fire(Object.assign({
            title: 'Installer la mise à jour ?',
            html: 'Version <strong>v' + version + '</strong><br><span class="text-secondary small">Durée estimée : 1 à 2 minutes. Ne fermez pas WHM.</span>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '<i class="bi bi-cloud-download"></i> Installer',
            cancelButtonText: 'Annuler',
            reverseButtons: true,
            focusCancel: true,
        }, swalTheme())).then(function (result) {
            if (result.isConfirmed) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire(Object.assign({
                        title: 'Mise à jour en cours…',
                        html: 'Patientez 1 à 2 minutes.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: function () {
                            Swal.showLoading();
                        },
                    }, swalTheme()));
                }
                form.submit();
            }
        });
    }

    runForms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            confirmRunUpdate(form);
        });
    });

    if (window.MegaStatsUpdateFlash) {
        showFlashModal(window.MegaStatsUpdateFlash);
        window.MegaStatsUpdateFlash = null;
    }

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
            setStatus('MegaStats <strong>v' + current + '</strong> — à jour sur GitHub.');
            runButtons.forEach(function (btn) {
                btn.classList.add('d-none');
            });
            return;
        }

        setBannerStyle(true);
        setStatus('Mise à jour <strong>v' + (data.latest || '?') + '</strong> disponible (installée : v' + current + ').');
        runButtons.forEach(function (btn) {
            btn.classList.remove('d-none');
        });
    }

    function fetchStatus(refresh) {
        if (!apiCheckUrl) {
            return;
        }

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
                if (refresh && typeof Swal !== 'undefined') {
                    Swal.fire(Object.assign({
                        toast: true,
                        position: 'top-end',
                        icon: data.update_available ? 'info' : 'success',
                        title: data.update_available
                            ? 'Mise à jour v' + (data.latest || '?') + ' disponible'
                            : 'MegaStats v' + (data.current || '?') + ' — à jour',
                        showConfirmButton: false,
                        timer: 3500,
                        timerProgressBar: true,
                    }, swalTheme()));
                }
            })
            .catch(function () {
                if (typeof Swal !== 'undefined') {
                    Swal.fire(Object.assign({
                        toast: true,
                        position: 'top-end',
                        icon: 'warning',
                        title: 'Revérification impossible',
                        text: 'Utilisez le lien Revérifier du bandeau.',
                        showConfirmButton: false,
                        timer: 4000,
                    }, swalTheme()));
                }
            })
            .finally(function () {
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

        if (typeof Swal !== 'undefined') {
            Swal.fire(Object.assign({
                title: 'Installer la mise à jour ?',
                text: 'Durée estimée : 1 à 2 minutes.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Installer',
                cancelButtonText: 'Annuler',
            }, swalTheme())).then(function (result) {
                if (!result.isConfirmed) {
                    return;
                }
                executeAjaxUpdate(runUrl, btn);
            });
            return;
        }

        if (window.confirm('Installer la mise à jour ?')) {
            executeAjaxUpdate(runUrl, btn);
        }
    }

    function executeAjaxUpdate(runUrl, btn) {
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
            headers: { Accept: 'application/json' },
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
                    showFlashModal({
                        type: 'success',
                        title: 'Mise à jour réussie',
                        message: 'Rechargez la page (Ctrl+F5).',
                        autohide: 4500,
                    });
                    if (res.status) {
                        renderStatus(res.status);
                    }
                } else if (typeof Swal !== 'undefined') {
                    Swal.fire(Object.assign({
                        icon: 'error',
                        title: 'Échec de la mise à jour',
                        html: '<pre class="small text-start mb-0" style="max-height:240px;overflow:auto">' + (res.output || res.error || '?') + '</pre>',
                    }, swalTheme()));
                }
            })
            .catch(function (err) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire(Object.assign({
                        icon: 'error',
                        title: 'Erreur',
                        text: err.message || 'réseau',
                    }, swalTheme()));
                }
            })
            .finally(function () {
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
