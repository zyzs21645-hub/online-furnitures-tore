(function () {
    'use strict';

    var storageKey = 'admin-theme';
    var modalState = {
        active: false,
        lastFocused: null
    };

    var modalLocale = {
        en: {
            close: 'Close modal',
            ok: 'OK',
            cancel: 'Cancel',
            confirm: 'Confirm',
            confirmTitle: 'Confirm action',
            confirmMessage: 'Are you sure you want to continue?',
            alertTitle: 'Notice',
            deleteTitle: 'Delete item',
            searchError: 'Search request failed',
            imagePreviewAlt: 'Image preview'
        },
        ar: {
            close: 'إغلاق النافذة',
            ok: 'حسنًا',
            cancel: 'إلغاء',
            confirm: 'تأكيد',
            confirmTitle: 'تأكيد الإجراء',
            confirmMessage: 'هل أنت متأكد أنك تريد المتابعة؟',
            alertTitle: 'تنبيه',
            deleteTitle: 'حذف العنصر',
            searchError: 'فشل طلب البحث',
            imagePreviewAlt: 'معاينة الصورة'
        }
    };

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

    function getCurrentLanguage() {
        var html = document.documentElement;
        var rawLang = html ? String(html.getAttribute('lang') || '').toLowerCase() : '';

        return rawLang.indexOf('ar') === 0 ? 'ar' : 'en';
    }

    function t(key) {
        var language = getCurrentLanguage();
        var dictionary = modalLocale[language] || modalLocale.en;

        if (Object.prototype.hasOwnProperty.call(dictionary, key)) {
            return dictionary[key];
        }

        return modalLocale.en[key] || '';
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
                var hideLabel = toggleButton.getAttribute('data-label-hide') || 'Hide password';
                var showLabel = toggleButton.getAttribute('data-label-show') || 'Show password';

                input.setAttribute('type', shouldShowPassword ? 'text' : 'password');
                toggleButton.setAttribute('aria-label', shouldShowPassword ? hideLabel : showLabel);

                if (icon) {
                    icon.classList.toggle('fa-eye', !shouldShowPassword);
                    icon.classList.toggle('fa-eye-slash', shouldShowPassword);
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

    function ensureModalMarkup() {
        var existingModal = document.getElementById('adminActionModal');

        if (existingModal) {
            return existingModal;
        }

        var modal = document.createElement('div');
        modal.id = 'adminActionModal';
        modal.className = 'admin-modal';
        modal.setAttribute('aria-hidden', 'true');
        modal.innerHTML = '' +
            '<div class="admin-modal-backdrop" data-modal-close></div>' +
            '<div class="admin-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="adminModalTitle" aria-describedby="adminModalMessage">' +
                '<button class="admin-modal-close" type="button" data-modal-close>' +
                    '<i class="fa-solid fa-xmark"></i>' +
                '</button>' +
                '<div class="admin-modal-icon">' +
                    '<i class="fa-solid fa-triangle-exclamation"></i>' +
                '</div>' +
                '<h3 id="adminModalTitle" class="admin-modal-title"></h3>' +
                '<p id="adminModalMessage" class="admin-modal-message"></p>' +
                '<div class="admin-modal-actions">' +
                    '<button class="btn btn-secondary" type="button" data-modal-cancel></button>' +
                    '<button class="btn btn-danger" type="button" data-modal-confirm></button>' +
                '</div>' +
            '</div>';

        document.body.appendChild(modal);
        return modal;
    }

    function getModalElements() {
        var modal = ensureModalMarkup();

        return {
            modal: modal,
            title: modal.querySelector('.admin-modal-title'),
            message: modal.querySelector('.admin-modal-message'),
            icon: modal.querySelector('.admin-modal-icon i'),
            confirmButton: modal.querySelector('[data-modal-confirm]'),
            cancelButton: modal.querySelector('[data-modal-cancel]'),
            closeButton: modal.querySelector('.admin-modal-close'),
            closeTriggers: modal.querySelectorAll('[data-modal-close]')
        };
    }

    function normalizeModalOptions(options, type) {
        var normalizedType = type === 'alert' ? 'alert' : 'confirm';
        var source = options || {};

        return {
            type: normalizedType,
            title: source.title || (normalizedType === 'alert' ? t('alertTitle') : t('confirmTitle')),
            message: source.message || t('confirmMessage'),
            confirmText: source.confirmText || (normalizedType === 'alert' ? t('ok') : t('confirm')),
            cancelText: source.cancelText || t('cancel'),
            closeLabel: source.closeLabel || t('close'),
            confirmClass: source.confirmClass || (normalizedType === 'alert' ? 'btn btn-primary' : 'btn btn-danger'),
            confirmIcon: source.confirmIcon || (normalizedType === 'alert' ? 'fa-solid fa-circle-info' : 'fa-solid fa-triangle-exclamation'),
            showCancel: normalizedType === 'confirm'
        };
    }

    function showModal(options, type) {
        return new Promise(function (resolve) {
            var config = normalizeModalOptions(options, type);
            var parts = getModalElements();
            var cleanupHandlers = [];
            var resolved = false;
            var focusableSelectors = 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])';

            function closeModal(result) {
                if (resolved) {
                    return;
                }

                resolved = true;
                parts.modal.classList.remove('is-visible');
                parts.modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('modal-open');
                modalState.active = false;

                cleanupHandlers.forEach(function (cleanup) {
                    cleanup();
                });

                if (modalState.lastFocused && typeof modalState.lastFocused.focus === 'function') {
                    modalState.lastFocused.focus();
                }

                resolve(result);
            }

            function bindTemporaryListener(element, eventName, handler) {
                if (!element) {
                    return;
                }

                element.addEventListener(eventName, handler);
                cleanupHandlers.push(function () {
                    element.removeEventListener(eventName, handler);
                });
            }

            modalState.active = true;
            modalState.lastFocused = document.activeElement;

            parts.title.textContent = config.title;
            parts.message.textContent = config.message;
            parts.confirmButton.textContent = config.confirmText;
            parts.cancelButton.textContent = config.cancelText;
            parts.closeButton.setAttribute('aria-label', config.closeLabel);
            parts.confirmButton.className = config.confirmClass;
            parts.confirmButton.setAttribute('type', 'button');
            parts.cancelButton.setAttribute('type', 'button');
            parts.cancelButton.hidden = !config.showCancel;
            parts.icon.className = config.confirmIcon;

            bindTemporaryListener(parts.confirmButton, 'click', function () {
                closeModal(true);
            });

            bindTemporaryListener(parts.cancelButton, 'click', function () {
                closeModal(false);
            });

            parts.closeTriggers.forEach(function (trigger) {
                bindTemporaryListener(trigger, 'click', function () {
                    closeModal(false);
                });
            });

            bindTemporaryListener(document, 'keydown', function (event) {
                if (!modalState.active) {
                    return;
                }

                if (event.key === 'Escape') {
                    event.preventDefault();
                    closeModal(false);
                    return;
                }

                if (event.key === 'Tab') {
                    var focusable = Array.prototype.slice.call(parts.modal.querySelectorAll(focusableSelectors))
                        .filter(function (element) {
                            return !element.disabled && !element.hidden && element.offsetParent !== null;
                        });

                    if (focusable.length === 0) {
                        return;
                    }

                    var first = focusable[0];
                    var last = focusable[focusable.length - 1];

                    if (event.shiftKey && document.activeElement === first) {
                        event.preventDefault();
                        last.focus();
                    } else if (!event.shiftKey && document.activeElement === last) {
                        event.preventDefault();
                        first.focus();
                    }
                }
            });

            parts.modal.classList.add('is-visible');
            parts.modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
            parts.confirmButton.focus();
        });
    }

    function showConfirmModal(options) {
        return showModal(options, 'confirm');
    }

    function showAlertModal(options) {
        return showModal(options, 'alert');
    }

    function initializeDialogOverrides() {
        window.adminConfirm = function (message, options) {
            var modalOptions = options || {};
            modalOptions.message = message || modalOptions.message || t('confirmMessage');
            return showConfirmModal(modalOptions);
        };

        window.adminAlert = function (message, options) {
            var modalOptions = options || {};
            modalOptions.message = message || modalOptions.message || '';
            return showAlertModal(modalOptions);
        };

        window.confirm = window.adminConfirm;
        window.alert = window.adminAlert;
    }

    function initializeConfirmActions() {
        document.querySelectorAll('[data-confirm]').forEach(function (element) {
            if (element.getAttribute('data-confirm-bound') === 'true') {
                return;
            }

            element.setAttribute('data-confirm-bound', 'true');

            element.addEventListener('click', function (event) {
                event.preventDefault();

                var href = element.getAttribute('href');
                var message = element.getAttribute('data-confirm') || t('confirmMessage');
                var title = element.getAttribute('data-confirm-title') || t('confirmTitle');
                var confirmText = element.getAttribute('data-confirm-ok') || t('confirm');
                var cancelText = element.getAttribute('data-confirm-cancel') || t('cancel');

                showConfirmModal({
                    title: title,
                    message: message,
                    confirmText: confirmText,
                    cancelText: cancelText
                }).then(function (confirmed) {
                    if (!confirmed) {
                        return;
                    }

                    if (element.tagName === 'A' && href) {
                        window.location.href = href;
                        return;
                    }

                    if (element.tagName === 'BUTTON') {
                        var form = element.closest('form');

                        if (form) {
                            form.submit();
                        }
                    }
                });
            });
        });
    }

    function animateCounter(element) {
        var rawValue = element.getAttribute('data-count-value');

        if (!rawValue) {
            return;
        }

        var target = Number(rawValue);

        if (!Number.isFinite(target)) {
            return;
        }

        var prefix = element.getAttribute('data-count-prefix') || '';
        var suffix = element.getAttribute('data-count-suffix') || '';
        var decimals = Number(element.getAttribute('data-count-decimals') || 0);
        var duration = Number(element.getAttribute('data-count-duration') || 900);
        var startTime = null;

        function render(value) {
            var formatted = value.toLocaleString(undefined, {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            });

            element.textContent = prefix + formatted + suffix;
        }

        function step(timestamp) {
            if (!startTime) {
                startTime = timestamp;
            }

            var progress = Math.min((timestamp - startTime) / duration, 1);
            var currentValue = target * progress;

            render(progress === 1 ? target : currentValue);

            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        }

        render(0);
        window.requestAnimationFrame(step);
    }

    function initializeCounterAnimations() {
        document.querySelectorAll('[data-count-value]').forEach(function (element) {
            animateCounter(element);
        });
    }

    function buildTableRow(item) {
        var stockClass = 'success';
        var deleteTitle = item.delete_confirm_title || t('deleteTitle');
        var deleteConfirm = item.delete_confirm || t('confirmMessage');
        var deleteConfirmOk = item.delete_confirm_ok || t('confirm');
        var deleteConfirmCancel = item.delete_confirm_cancel || t('cancel');

        if (Number(item.stock_quantity) <= 5) {
            stockClass = 'danger';
        } else if (Number(item.stock_quantity) <= 15) {
            stockClass = 'warning';
        }

        return '' +
            '<tr>' +
                '<td>' +
                    '<div class="table-item">' +
                        '<img class="table-item-image" src="' + item.image + '" alt="' + item.item_name + '">' +
                        '<div class="table-item-copy">' +
                            '<strong>' + item.item_name + '</strong>' +
                            '<span>' + item.description + '</span>' +
                        '</div>' +
                    '</div>' +
                '</td>' +
                '<td>' +
                    '<span class="badge">' +
                        '<i class="fa-solid fa-tags"></i>' +
                        item.category_name +
                    '</span>' +
                '</td>' +
                '<td class="price-text">' + item.price_html + '</td>' +
                '<td>' +
                    '<span class="status-badge ' + stockClass + '">' +
                        '<i class="fa-solid fa-cubes-stacked"></i>' +
                        item.stock_text +
                    '</span>' +
                '</td>' +
                '<td>' + item.created_at + '</td>' +
                '<td>' +
                    '<div class="table-actions">' +
                        '<a class="btn btn-secondary" href="' + item.edit_url + '">' +
                            '<i class="fa-solid fa-pen-to-square"></i>' +
                            item.edit_label +
                        '</a>' +
                        '<a class="btn btn-danger" href="' + item.delete_url + '" data-confirm="' + deleteConfirm + '" data-confirm-title="' + deleteTitle + '" data-confirm-ok="' + deleteConfirmOk + '" data-confirm-cancel="' + deleteConfirmCancel + '">' +
                            '<i class="fa-solid fa-trash-can"></i>' +
                            item.delete_label +
                        '</a>' +
                    '</div>' +
                '</td>' +
            '</tr>';
    }

    function renderLiveSearchResults(payload, targetBody, emptyState, tableWrap) {
        if (!payload || !Array.isArray(payload.items)) {
            return;
        }

        if (payload.items.length === 0) {
            targetBody.innerHTML = '';

            if (tableWrap) {
                tableWrap.setAttribute('hidden', 'hidden');
            }

            if (emptyState) {
                emptyState.removeAttribute('hidden');
            }

            return;
        }

        targetBody.innerHTML = payload.items.map(buildTableRow).join('');

        if (tableWrap) {
            tableWrap.removeAttribute('hidden');
        }

        if (emptyState) {
            emptyState.setAttribute('hidden', 'hidden');
        }

        initializeConfirmActions();
    }

    function initializeLiveSearch() {
        var form = document.querySelector('[data-live-search-form]');

        if (!form) {
            return;
        }

        var input = form.querySelector('input[name="search"]');
        var endpoint = form.getAttribute('data-live-search-endpoint');
        var targetSelector = form.getAttribute('data-live-search-target');
        var emptySelector = form.getAttribute('data-live-search-empty');
        var tableWrapSelector = form.getAttribute('data-live-search-wrap');
        var targetBody = targetSelector ? document.querySelector(targetSelector) : null;
        var emptyState = emptySelector ? document.querySelector(emptySelector) : null;
        var tableWrap = tableWrapSelector ? document.querySelector(tableWrapSelector) : null;
        var debounceTimer = null;

        if (!input || !endpoint || !targetBody) {
            return;
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();
        });

        input.addEventListener('input', function () {
            var searchValue = input.value.trim();

            window.clearTimeout(debounceTimer);

            debounceTimer = window.setTimeout(function () {
                var requestUrl = endpoint + (endpoint.indexOf('?') === -1 ? '?' : '&') + 'search=' + encodeURIComponent(searchValue);

                window.fetch(requestUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error(t('searchError'));
                        }

                        return response.json();
                    })
                    .then(function (payload) {
                        renderLiveSearchResults(payload, targetBody, emptyState, tableWrap);
                    })
                    .catch(function () {
                        return;
                    });
            }, 220);
        });
    }

    function updatePreview(preview, file) {
        if (!preview) {
            return;
        }

        var reader = new FileReader();

        reader.onload = function (event) {
            preview.innerHTML = '<img src="' + String(event.target.result) + '" alt="' + t('imagePreviewAlt') + '">';
        };

        reader.readAsDataURL(file);
    }

    function initializeDragAndDropUploads() {
        document.querySelectorAll('[data-upload-zone]').forEach(function (zone) {
            var inputSelector = zone.getAttribute('data-upload-input');
            var previewSelector = zone.getAttribute('data-upload-preview');
            var input = inputSelector ? document.querySelector(inputSelector) : null;
            var preview = previewSelector ? document.querySelector(previewSelector) : null;
            var trigger = zone.querySelector('[data-upload-trigger]');

            if (!input) {
                return;
            }

            function assignFiles(files) {
                if (!files || !files.length) {
                    return;
                }

                var file = files[0];

                if (!file.type || file.type.indexOf('image/') !== 0) {
                    return;
                }

                try {
                    var dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    input.files = dataTransfer.files;
                } catch (error) {
                    return;
                }

                updatePreview(preview, file);
                zone.classList.add('has-file');
            }

            zone.addEventListener('click', function (event) {
                if (event.target.closest('[data-upload-trigger]') || event.target === zone) {
                    input.click();
                }
            });

            if (trigger) {
                trigger.addEventListener('click', function (event) {
                    event.preventDefault();
                    input.click();
                });
            }

            zone.addEventListener('dragover', function (event) {
                event.preventDefault();
                zone.classList.add('is-dragover');
            });

            zone.addEventListener('dragleave', function () {
                zone.classList.remove('is-dragover');
            });

            zone.addEventListener('drop', function (event) {
                event.preventDefault();
                zone.classList.remove('is-dragover');
                assignFiles(event.dataTransfer.files);
            });

            input.addEventListener('change', function () {
                assignFiles(input.files);
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initializeThemeToggle();
        initializePasswordToggles();
        initializeDialogOverrides();
        initializeConfirmActions();
        initializeAutoDismissAlerts();
        initializeActiveLinks();
        initializeLiveSearch();
        initializeCounterAnimations();
        initializeDragAndDropUploads();
    });
}());
