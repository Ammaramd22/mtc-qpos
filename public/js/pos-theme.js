(function () {
    function storageKey() {
        var id = document.documentElement.getAttribute('data-user-id') || '0';
        return 'vspos_theme_u_' + id;
    }

    function getPreferredTheme() {
        try {
            var saved = localStorage.getItem(storageKey());
            if (saved === 'light' || saved === 'dark') {
                return saved;
            }
        } catch (e) {}
        return 'dark';
    }

    function paintButtons(theme) {
        var buttons = document.querySelectorAll('.qpos-theme-toggle');
        for (var i = 0; i < buttons.length; i++) {
            var btn = buttons[i];
            var icon = btn.querySelector('i');
            var label = btn.querySelector('.qpos-theme-toggle-label');
            if (theme === 'dark') {
                if (icon) {
                    icon.className = 'fi fi-rr-sun qpos-theme-toggle-icon';
                }
                if (label) {
                    label.textContent = 'Light';
                }
                btn.setAttribute('title', 'Switch to light theme');
                btn.setAttribute('aria-label', 'Switch to light theme');
            } else {
                if (icon) {
                    icon.className = 'fi fi-rr-moon qpos-theme-toggle-icon';
                }
                if (label) {
                    label.textContent = 'Dark';
                }
                btn.setAttribute('title', 'Switch to dark theme');
                btn.setAttribute('aria-label', 'Switch to dark theme');
            }
        }
    }

    function applyTheme(theme, persist) {
        if (theme !== 'light' && theme !== 'dark') {
            theme = 'dark';
        }
        document.documentElement.setAttribute('data-vspos-theme', theme);
        if (persist !== false) {
            try {
                localStorage.setItem(storageKey(), theme);
            } catch (e) {}
        }
        paintButtons(theme);
    }

    window.vsposSetTheme = function (theme) {
        applyTheme(theme, true);
    };

    window.vsposToggleTheme = function () {
        var current = document.documentElement.getAttribute('data-vspos-theme') || 'dark';
        applyTheme(current === 'dark' ? 'light' : 'dark', true);
    };

    applyTheme(getPreferredTheme(), false);

    function bind() {
        applyTheme(getPreferredTheme(), false);
        document.addEventListener('click', function (event) {
            var btn = event.target.closest ? event.target.closest('.qpos-theme-toggle') : null;
            if (!btn) {
                return;
            }
            event.preventDefault();
            window.vsposToggleTheme();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bind);
    } else {
        bind();
    }
})();
