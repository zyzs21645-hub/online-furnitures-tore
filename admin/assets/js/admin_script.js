(function () {
    'use strict';

    var storageKey = 'admin-theme';

    function getStoredTheme() {
        try {
            return window.localStorage.getItem(storageKey);
        } catch (error) {
            return null;
        }
    }

    function setStoredTheme(theme) {
        try {
            window.localStorage.setItem(storageKey, theme);
        } catch (error) {
            return;
        }
    }

    function getPreferredTheme() {
        var storedTheme = getStoredTheme();

        if (storedTheme === 'light' || storedTheme === 'dark') {
            return storedTheme;
        }

        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    function applyTheme(theme) {
        var body = document.body;

        if (!body) {
            return;
        }

        var normalizedTheme = theme === 'dark' ? 'dark' : 'light';
        var isDark = normalizedTheme === 'dark';

        body.setAttribute('data-theme', normalizedTheme);
        body.classList.toggle('theme-dark', isDark);

        document.querySelectorAll('[data-theme-toggle]').forEach(function (toggleButton) {
            toggleButton.setAttribute('aria-pressed', String(isDark));
            toggleButton.setAttribute(
                'title',
                isDark
                    ? (toggleButton.getAttribute('data-title-light') || 'Switch to light mode')
                    : (toggleButton.getAttribute('data-title-dark') || 'Switch to dark mode')
            );

            var icon = toggleButton.querySelector('[data-theme-icon]');

            if (icon) {
                icon.classList.toggle('fa-moon', !isDark);
                icon.classList.toggle('fa-sun', isDark);
            }
        });
    }

    function initializeThemeToggle() {
        applyTheme(getPreferredTheme());

        document.querySelectorAll('[data-theme-toggle]').forEach(function (toggleButton) {
            toggleButton.addEventListener('click', function () {
                var nextTheme = document.body.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                applyTheme(nextTheme);
                setStoredTheme(nextTheme);
            });
        });
    }

    function initializePasswordToggles() {
        document.querySelectorAll('[data-password-toggle]').forEach(function (toggleButton) {
            toggleButton.addEventListener('click', function () {
                var targetSelector = toggleButton.getAttribute('data-target');
                var input = targetSelector ? document.querySelector(targetSelector) : null;

                if (!input) {
                    return;
                }

                var shouldShowPassword = input.getAttribute('type') === 'password';
                var icon = toggleButton.querySelector('i');

                input.setAttribute('type', shouldShowPassword ? 'text' : 'password');
                var hideLabel = toggleButton.getAttribute('data-label-hide') || 'Hide password';
                var showLabel = toggleButton.getAttribute('data-label-show') || 'Show password';

                toggleButton.setAttribute('aria-label', shouldShowPassword ? hideLabel : showLabel);

                if (icon) {
                    icon.classList.toggle('fa-eye', !shouldShowPassword);
                    icon.classList.toggle('fa-eye-slash', shouldShowPassword);
                }
            });
        });
    }

    function initializeConfirmActions() {
        document.querySelectorAll('[data-confirm]').forEach(function (element) {
            element.addEventListener('click', function (event) {
                var message = element.getAttribute('data-confirm') || 'Are you sure you want to continue?';

                if (!window.confirm(message)) {
                    event.preventDefault();
                }
            });
        });
    }

    function initializeAutoDismissAlerts() {
        document.querySelectorAll('.alert[data-auto-dismiss]').forEach(function (alertBox) {
            var timeoutValue = Number(alertBox.getAttribute('data-auto-dismiss')) || 4500;

            window.setTimeout(function () {
                alertBox.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                alertBox.style.opacity = '0';
                alertBox.style.transform = 'translateY(-6px)';

                window.setTimeout(function () {
                    if (alertBox.parentNode) {
                        alertBox.parentNode.removeChild(alertBox);
                    }
                }, 300);
            }, timeoutValue);
        });
    }

    function initializeActiveLinks() {
        var currentPath = window.location.pathname.replace(/\\/g, '/');

        document.querySelectorAll('.sidebar-link').forEach(function (link) {
            var linkPath = link.getAttribute('href');

            if (!linkPath) {
                return;
            }

            var normalizedLinkPath = linkPath.replace(/\\/g, '/');

            if (currentPath.indexOf(normalizedLinkPath.replace('..', '/admin')) !== -1) {
                link.classList.add('active');
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initializeThemeToggle();
        initializePasswordToggles();
        initializeConfirmActions();
        initializeAutoDismissAlerts();
        initializeActiveLinks();
    });
}());
