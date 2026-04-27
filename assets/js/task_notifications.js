(function () {
    'use strict';

    var POLL_MS = 5000;
    var STORAGE_KEY = 'taskAssignmentNotifLastSeenId';
    var dropdownOpen = false;
    var mountMode = 'floating';
    var pollTimer = null;
    var audioPrimed = false;
    var audioCtx = null;
    var state = {
        latestId: 0,
        unreadCount: 0,
        notifications: []
    };

    function isMobileViewport() {
        return window.matchMedia('(max-width: 768px)').matches;
    }

    function isSidebarOpenOnMobile() {
        var sidebar = document.getElementById('sidebar');
        if (!sidebar || !isMobileViewport()) return false;
        return !sidebar.classList.contains('-translate-x-full');
    }

    function syncBellVisibilityWithSidebar() {
        var wrap = document.getElementById('tan-floating-wrap');
        if (!wrap) return;

        if (!isMobileViewport()) {
            wrap.style.display = '';
            return;
        }

        // Mobile bell mounted in top header should remain visible.
        if (mountMode === 'header-mobile') {
            wrap.style.display = '';
            return;
        }

        if (!isSidebarOpenOnMobile()) {
            wrap.style.display = 'none';
            if (dropdownOpen) setDropdownOpen(false);
        } else {
            wrap.style.display = '';
        }
    }

    function setDropdownOpen(nextOpen) {
        var dropdown = document.getElementById('tan-dropdown');
        var mobileOverlay = document.getElementById('tan-mobile-overlay');
        if (!dropdown) return;

        dropdownOpen = !!nextOpen;
        dropdown.style.display = dropdownOpen ? 'block' : 'none';

        if (mobileOverlay) {
            if (dropdownOpen && isMobileViewport()) {
                // Avoid double-blur on mobile when sidebar overlay is already active.
                if (isSidebarOpenOnMobile() || mountMode === 'header-mobile') {
                    mobileOverlay.style.opacity = '0';
                    mobileOverlay.style.display = 'none';
                } else {
                    mobileOverlay.style.display = 'block';
                    requestAnimationFrame(function () {
                        mobileOverlay.style.opacity = '0';
                    });
                }
            } else {
                mobileOverlay.style.opacity = '0';
                mobileOverlay.style.display = 'none';
            }
        }

        if (dropdownOpen) {
            renderNotifications();
        }
    }

    function bindTapAndClick(node, handler) {
        if (!node || typeof handler !== 'function') return;
        var lastPointerHandledAt = 0;

        node.addEventListener('click', function (e) {
            // Ignore synthetic click right after touch/pen pointerup.
            if (lastPointerHandledAt && (Date.now() - lastPointerHandledAt) < 500) return;
            handler(e);
        });
        node.addEventListener('pointerup', function (e) {
            if (e.pointerType === 'mouse') return;
            lastPointerHandledAt = Date.now();
            e.preventDefault();
            handler(e);
        });
    }

    function createUiShell() {
        if (document.getElementById('tan-bell-btn')) return;

        var anchor = document.getElementById('tan-sidebar-notification-anchor');
        var mobileToggle = document.getElementById('mobile-menu-toggle');
        var mobileHeaderAnchor = document.getElementById('tan-header-notification-anchor');
        var mobileHeaderContainer = mobileHeaderAnchor || (mobileToggle ? mobileToggle.parentElement : null);
        var canUseSidebarMount = !!anchor && !isMobileViewport();
        var canUseMobileHeaderMount = !!mobileHeaderContainer && isMobileViewport();
        mountMode = canUseSidebarMount ? 'sidebar' : (canUseMobileHeaderMount ? 'header-mobile' : 'floating');

        var style = document.createElement('style');
        style.textContent = ''
            + '#tan-floating-wrap{font-family:Inter,sans-serif;}'
            + '#tan-floating-wrap[data-mode="floating"]{position:fixed;right:16px;top:84px;z-index:9999;}'
            + '#tan-floating-wrap[data-mode="sidebar"]{position:relative;z-index:60;}'
            + '#tan-floating-wrap[data-mode="header-mobile"]{position:relative;z-index:60;display:inline-flex;margin-left:auto;}'
            + '#tan-bell-btn{border-radius:9999px;border:1px solid rgba(96,165,250,.35);background:linear-gradient(135deg,rgba(30,41,59,.95),rgba(15,23,42,.95));color:#dbeafe;box-shadow:0 10px 24px rgba(2,6,23,.45);cursor:pointer;position:relative;display:flex;align-items:center;justify-content:center;padding:0;line-height:1;}'
            + '#tan-floating-wrap[data-mode="floating"] #tan-bell-btn{width:52px;height:52px;}'
            + '#tan-floating-wrap[data-mode="sidebar"] #tan-bell-btn{width:38px;height:38px;}'
            + '#tan-floating-wrap[data-mode="header-mobile"] #tan-bell-btn{width:40px;height:40px;}'
            + '#tan-bell-btn svg{display:block;flex:0 0 auto;}'
            + '#tan-badge{position:absolute;top:-5px;right:-5px;min-width:20px;height:20px;padding:0 6px;border-radius:9999px;background:#ef4444;color:#fff;font-size:11px;font-weight:700;line-height:20px;text-align:center;display:none;}'
            + '#tan-dropdown{position:absolute;right:0;top:62px;width:360px;max-height:440px;border-radius:12px;overflow:hidden;background:#0f172a;border:1px solid rgba(148,163,184,.25);box-shadow:0 20px 45px rgba(2,6,23,.65);display:none;z-index:10002;}'
            + '#tan-floating-wrap[data-mode="sidebar"] #tan-dropdown{position:fixed;top:76px;left:16px;right:auto;width:min(92vw,320px);max-width:calc(100vw - 32px);max-height:min(70vh,440px);}'
            + '#tan-dropdown header{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:10px 12px;border-bottom:1px solid rgba(51,65,85,.8);font-size:12px;color:#cbd5e1;}'
            + '#tan-mark-all{background:transparent;border:0;color:#93c5fd;cursor:pointer;font-size:11px;}'
            + '#tan-list{max-height:388px;overflow-y:auto;}'
            + '.tan-item{padding:10px 12px;border-bottom:1px solid rgba(51,65,85,.5);cursor:pointer;}'
            + '.tan-item:hover{background:rgba(30,41,59,.65);}'
            + '.tan-item.unread{background:rgba(30,58,138,.2);}'
            + '.tan-item-title{color:#f1f5f9;font-size:12px;font-weight:600;line-height:1.3;}'
            + '.tan-item-meta{margin-top:4px;color:#94a3b8;font-size:11px;}'
            + '#tan-toast-wrap{position:fixed;right:16px;top:16px;z-index:10000;display:flex;flex-direction:column;gap:10px;}'
            + '.tan-toast{min-width:280px;max-width:360px;padding:11px 12px;border-radius:10px;border:1px solid rgba(56,189,248,.35);background:rgba(15,23,42,.95);box-shadow:0 10px 24px rgba(2,6,23,.45);color:#e2e8f0;}'
            + '.tan-toast-title{font-size:12px;font-weight:700;color:#bae6fd;}'
            + '.tan-toast-msg{margin-top:4px;font-size:12px;line-height:1.3;}'
            + '.tan-empty{padding:20px 12px;color:#94a3b8;font-size:12px;text-align:center;}'
            + '#tan-mobile-overlay{position:fixed;inset:0;background:rgba(2,6,23,.55);z-index:10001;display:none;opacity:0;transition:opacity .15s ease;}'
            + '@media (max-width: 768px){#tan-floating-wrap{z-index:10020;}#tan-floating-wrap[data-mode="floating"]{top:12px;right:12px;}#tan-floating-wrap[data-mode="floating"] #tan-bell-btn{width:46px;height:46px;}#tan-floating-wrap[data-mode="header-mobile"]{position:relative;top:auto;right:auto;z-index:60;margin-left:auto;}#tan-floating-wrap[data-mode="header-mobile"] #tan-bell-btn{width:44px;height:44px;}#tan-dropdown{position:absolute;top:54px;right:0;left:auto;width:min(92vw,320px);max-width:calc(100vw - 24px);max-height:min(70vh,480px);z-index:10021;}#tan-floating-wrap[data-mode="header-mobile"] #tan-dropdown{position:fixed;top:64px;right:12px;left:auto;bottom:auto;width:min(92vw,320px);max-width:calc(100vw - 24px);}#tan-floating-wrap[data-mode="sidebar"]{position:fixed;top:12px;right:12px;left:auto;z-index:10020;}#tan-floating-wrap[data-mode="sidebar"] #tan-bell-btn{width:46px;height:46px;}#tan-floating-wrap[data-mode="sidebar"] #tan-dropdown{top:54px;right:0;left:auto;bottom:auto;width:min(92vw,320px);max-width:calc(100vw - 24px);z-index:10021;}#tan-mobile-overlay{z-index:10010;}}'
            + '@media (max-width: 360px){#tan-dropdown{left:8px;right:8px;bottom:8px;}}';
        document.head.appendChild(style);

        var wrap = document.createElement('div');
        wrap.id = 'tan-floating-wrap';
        wrap.setAttribute('data-mode', mountMode);
        wrap.innerHTML = ''
            + '<button id="tan-bell-btn" type="button" aria-label="Task Notifications">'
            + '  <span id="tan-badge">0</span>'
            + '  <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">'
            + '    <path d="M15 17h5l-1.4-1.4a2 2 0 0 1-.6-1.4V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5"/>'
            + '    <path d="M10 17a2 2 0 0 0 4 0"/>'
            + '  </svg>'
            + '</button>'
            + '<div id="tan-dropdown">'
            + '  <header><span>Task Notifications</span><button id="tan-mark-all" type="button">Mark all read</button></header>'
            + '  <div id="tan-list"><div class="tan-empty">No notifications yet.</div></div>'
            + '</div>';
        if (canUseSidebarMount) {
            anchor.appendChild(wrap);
        } else if (canUseMobileHeaderMount) {
            mobileHeaderContainer.appendChild(wrap);
        } else {
            document.body.appendChild(wrap);
        }

        var toastWrap = document.createElement('div');
        toastWrap.id = 'tan-toast-wrap';
        document.body.appendChild(toastWrap);

        var mobileOverlay = document.createElement('div');
        mobileOverlay.id = 'tan-mobile-overlay';
        mobileOverlay.setAttribute('aria-hidden', 'true');
        document.body.appendChild(mobileOverlay);

        bindTapAndClick(document.getElementById('tan-bell-btn'), function (e) {
            e.stopPropagation();
            setDropdownOpen(!dropdownOpen);
        });

        document.addEventListener('click', function (e) {
            var dropdown = document.getElementById('tan-dropdown');
            var button = document.getElementById('tan-bell-btn');
            if (!dropdown || !button) return;
            if (dropdownOpen && !dropdown.contains(e.target) && !button.contains(e.target)) {
                setDropdownOpen(false);
            }
        });

        bindTapAndClick(mobileOverlay, function () {
            setDropdownOpen(false);
        });

        window.addEventListener('resize', function () {
            if (!dropdownOpen) return;
            setDropdownOpen(true);
        });
        window.addEventListener('resize', function () {
            syncBellVisibilityWithSidebar();
        });

        document.getElementById('tan-mark-all').addEventListener('click', function () {
            markRead(null, true);
        });

        // Sync visibility for the active mount mode.
        syncBellVisibilityWithSidebar();

        // Sidebar toggle/overlay close are click-driven in main.js.
        document.addEventListener('click', function () {
            setTimeout(syncBellVisibilityWithSidebar, 0);
        });
    }

    function primeAudio() {
        if (audioPrimed) return;
        try {
            var Ctx = window.AudioContext || window.webkitAudioContext;
            if (!Ctx) return;
            audioCtx = new Ctx();
            audioPrimed = true;
        } catch (_) {
            audioPrimed = false;
        }
    }

    function playSound() {
        if (!audioPrimed || !audioCtx) return;
        try {
            var osc = audioCtx.createOscillator();
            var gain = audioCtx.createGain();
            osc.type = 'sine';
            osc.frequency.value = 880;
            gain.gain.value = 0.0001;
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            var now = audioCtx.currentTime;
            gain.gain.exponentialRampToValueAtTime(0.07, now + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.28);
            osc.start(now);
            osc.stop(now + 0.3);
        } catch (_) {}
    }

    function formatTime(ts) {
        if (!ts) return '';
        var d = new Date(ts.replace(' ', 'T'));
        if (isNaN(d.getTime())) return ts;
        return d.toLocaleString();
    }

    function updateBadge(count) {
        var badge = document.getElementById('tan-badge');
        if (!badge) return;
        if (count > 0) {
            badge.style.display = 'inline-block';
            badge.textContent = count > 99 ? '99+' : String(count);
        } else {
            badge.style.display = 'none';
            badge.textContent = '0';
        }
    }

    function toast(item) {
        var wrap = document.getElementById('tan-toast-wrap');
        if (!wrap) return;
        var type = String(item.notification_type || '');
        var isDirect = type.indexOf('direct_task') === 0;
        var isReview = type.indexOf('_review') > -1;
        var el = document.createElement('div');
        el.className = 'tan-toast';
        el.innerHTML = ''
            + '<div class="tan-toast-title">' + (isReview ? 'Task Marked For Review' : (isDirect ? 'New Direct Task' : 'New Project Task')) + '</div>'
            + '<div class="tan-toast-msg">' + escapeHtml(item.message || 'New task assignment received.') + '</div>';
        wrap.appendChild(el);
        setTimeout(function () {
            if (el.parentNode) el.parentNode.removeChild(el);
        }, 5000);
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function renderNotifications() {
        var list = document.getElementById('tan-list');
        if (!list) return;
        if (!state.notifications.length) {
            list.innerHTML = '<div class="tan-empty">No notifications yet.</div>';
            return;
        }
        list.innerHTML = state.notifications.map(function (n) {
            var isUnread = Number(n.is_read) === 0;
            var notifType = String(n.notification_type || '');
            var isDirectType = notifType.indexOf('direct_task') === 0;
            var targetHref = (isDirectType || n.project_id === null || Number(n.project_id) === 0)
                ? 'direct_tasks.php?task_id=' + encodeURIComponent(n.task_id)
                : 'tasks.php?project_id=' + encodeURIComponent(n.project_id) + '&task_id=' + encodeURIComponent(n.task_id);
            return ''
                + '<div class="tan-item ' + (isUnread ? 'unread' : '') + '" data-id="' + n.id + '" data-href="' + targetHref + '">'
                + '  <div class="tan-item-title">' + escapeHtml(n.title_snapshot || 'Task Assignment') + '</div>'
                + '  <div class="tan-item-meta">' + escapeHtml(n.message || '') + '</div>'
                + '  <div class="tan-item-meta">' + escapeHtml(n.sender_name || 'System') + ' • ' + escapeHtml(formatTime(n.created_at)) + '</div>'
                + '</div>';
        }).join('');

        Array.prototype.forEach.call(list.querySelectorAll('.tan-item'), function (node) {
            bindTapAndClick(node, function () {
                var id = parseInt(node.getAttribute('data-id') || '0', 10);
                var href = node.getAttribute('data-href') || '';
                if (href) {
                    // Do not block navigation on mark-read latency/failure.
                    if (id > 0) markRead(id, false);
                    window.location.href = href;
                    return;
                }
                if (id > 0) markRead(id, false);
            });
        });
    }

    function markRead(notificationId, markAll, cb) {
        var body = new URLSearchParams();
        if (markAll) {
            body.append('mark_all', '1');
        } else if (notificationId) {
            body.append('notification_id', String(notificationId));
        }
        fetch('api/mark_task_assignment_notification_read.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: body.toString()
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data || !data.success) return;
                state.unreadCount = Number(data.unread_count || 0);
                if (markAll) {
                    state.notifications = state.notifications.map(function (n) {
                        n.is_read = 1;
                        return n;
                    });
                } else if (notificationId) {
                    state.notifications = state.notifications.map(function (n) {
                        if (Number(n.id) === Number(notificationId)) n.is_read = 1;
                        return n;
                    });
                }
                updateBadge(state.unreadCount);
                renderNotifications();
                if (typeof cb === 'function') cb();
            })
            .catch(function () {});
    }

    function refreshNow() {
        fetch('api/get_task_assignment_notifications.php?limit=20')
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data || !data.success) return;
                state.notifications = Array.isArray(data.notifications) ? data.notifications : [];
                state.unreadCount = Number(data.unread_count || 0);
                state.latestId = Number(data.latest_id || 0);
                updateBadge(state.unreadCount);
                renderNotifications();

                var lastSeen = parseInt(localStorage.getItem(STORAGE_KEY) || '0', 10);
                var fresh = state.notifications.filter(function (n) {
                    return Number(n.id) > lastSeen;
                });
                if (fresh.length > 0) {
                    fresh
                        .sort(function (a, b) { return Number(a.id) - Number(b.id); })
                        .forEach(function (n) { toast(n); });
                    if (audioPrimed) {
                        playSound();
                    }
                    var maxFreshId = Math.max.apply(null, fresh.map(function (n) { return Number(n.id); }));
                    localStorage.setItem(STORAGE_KEY, String(maxFreshId));
                } else if (state.latestId > lastSeen) {
                    localStorage.setItem(STORAGE_KEY, String(state.latestId));
                }
            })
            .catch(function () {});
    }

    function startPolling() {
        if (pollTimer) clearInterval(pollTimer);
        refreshNow();
        pollTimer = setInterval(refreshNow, POLL_MS);
    }

    function init() {
        createUiShell();
        document.addEventListener('click', primeAudio, { once: true });
        document.addEventListener('keydown', primeAudio, { once: true });
        startPolling();
        window.TaskAssignmentNotifications = {
            refreshNow: refreshNow,
            markAllRead: function () { markRead(null, true); }
        };
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
