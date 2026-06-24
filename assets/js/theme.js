(function () {
    const storageKey = 'megastats-theme';
    const root = document.documentElement;
    const toggle = document.getElementById('themeToggle');

    function systemTheme() {
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    function applyTheme(theme) {
        const resolved = theme === 'auto' ? systemTheme() : theme;
        root.setAttribute('data-bs-theme', resolved);
        document.querySelectorAll('.ms-whm-wrap').forEach(function (el) {
            el.setAttribute('data-bs-theme', resolved);
        });
        if (toggle) {
            toggle.innerHTML = resolved === 'dark'
                ? '<i class="bi bi-sun"></i>'
                : '<i class="bi bi-moon-stars"></i>';
        }
    }

    let saved = localStorage.getItem(storageKey);
    if (!saved) {
        saved = 'auto';
    }

    applyTheme(saved);

    if (toggle) {
        toggle.addEventListener('click', function () {
            const current = root.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
            localStorage.setItem(storageKey, current);
            applyTheme(current);
        });
    }

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function () {
        if (localStorage.getItem(storageKey) === 'auto' || !localStorage.getItem(storageKey)) {
            applyTheme('auto');
        }
    });
})();
