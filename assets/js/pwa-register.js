/**
 * PWA Service Worker Registration
 * Registers the service worker for offline support and Add to Home Screen.
 * Does not block or interfere with existing page functionality.
 */
(function () {
    'use strict';
    var hasSW = ('serviceWorker' in navigator);
    var isSecure = (window.location.protocol === 'https:' || window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1');
    var deferredPrompt = null;
    var installButton = null;

    var SW_VERSION = '1.0.0'; // Bump to force SW update when needed

    function isStandaloneMode() {
        return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    }

    function createInstallButton() {
        if (installButton || isStandaloneMode()) return;
        installButton = document.createElement('button');
        installButton.id = 'pwa-install-btn';
        installButton.type = 'button';
        installButton.textContent = 'Install App';
        installButton.style.cssText = [
            'position:fixed',
            'right:16px',
            'bottom:16px',
            'z-index:9999',
            'padding:10px 14px',
            'border-radius:10px',
            'border:1px solid rgba(96,165,250,0.45)',
            'background:rgba(37,99,235,0.95)',
            'color:#fff',
            'font-size:14px',
            'font-family:Inter,sans-serif',
            'font-weight:600',
            'box-shadow:0 10px 24px rgba(29,78,216,0.35)',
            'display:none'
        ].join(';');
        document.body.appendChild(installButton);

        installButton.addEventListener('click', function () {
            if (!deferredPrompt) return;
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then(function () {
                deferredPrompt = null;
                hideInstallButton();
            });
        });
    }

    function showInstallButton() {
        if (!installButton || isStandaloneMode()) return;
        installButton.style.display = 'block';
    }

    function hideInstallButton() {
        if (!installButton) return;
        installButton.style.display = 'none';
    }

    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        deferredPrompt = e;
        showInstallButton();
    });

    window.addEventListener('appinstalled', function () {
        deferredPrompt = null;
        hideInstallButton();
    });

    window.addEventListener('load', function () {
        createInstallButton();

        if (!hasSW || !isSecure) {
            return;
        }

        navigator.serviceWorker.register('sw.js?v=' + SW_VERSION, { scope: './' })
            .then(function (reg) {
                reg.addEventListener('updatefound', function () {
                    var newWorker = reg.installing;
                    if (!newWorker) return;
                    newWorker.addEventListener('statechange', function () {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            if (typeof console !== 'undefined' && console.log) {
                                console.log('[PWA] New version available. Refresh to update.');
                            }
                        }
                    });
                });
            })
            .catch(function (err) {
                if (typeof console !== 'undefined' && console.warn) {
                    console.warn('[PWA] Service worker registration failed:', err);
                }
            });
    });
})();
