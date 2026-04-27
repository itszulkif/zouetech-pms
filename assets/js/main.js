// ============================================================
// Global Sidebar Toggle — works on ALL pages and ALL roles
// Pure vanilla JS: no jQuery dependency for the toggle itself
// ============================================================
document.addEventListener('DOMContentLoaded', function () {

    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebar-overlay');
    var body = document.body;

    // ── Open / Close helpers ─────────────────────────────────
    function openSidebar() {
        if (!sidebar || !overlay) return;
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden', 'opacity-0');
        overlay.classList.add('opacity-100');
        body.classList.add('overflow-hidden');
    }

    function closeSidebar() {
        if (!sidebar || !overlay) return;
        sidebar.classList.add('-translate-x-full');
        overlay.classList.remove('opacity-100');
        overlay.classList.add('hidden', 'opacity-0');
        body.classList.remove('overflow-hidden');
    }

    function toggleSidebar() {
        if (!sidebar) return;
        if (sidebar.classList.contains('-translate-x-full')) {
            openSidebar();
        } else {
            closeSidebar();
        }
    }

    // ── Wire up the toggle button & overlay (event delegation) ─
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('#mobile-menu-toggle');
        var ovl = e.target.closest('#sidebar-overlay');
        if (btn) { e.preventDefault(); toggleSidebar(); }
        if (ovl) { closeSidebar(); }
    });

    // ── On desktop: always show sidebar, reset overlay ────────
    window.addEventListener('resize', function () {
        if (window.innerWidth >= 768) {
            if (sidebar) sidebar.classList.remove('-translate-x-full');
            if (overlay) {
                overlay.classList.add('hidden', 'opacity-0');
                overlay.classList.remove('opacity-100');
            }
            body.classList.remove('overflow-hidden');
        }
    });

    // ── Legacy jQuery helpers (non-toggle) ───────────────────
    // These still use jQuery but are safe because jQuery is loaded in <head>
    if (typeof $ !== 'undefined') {
        // Handle Create Team Form Submission
        $('#createTeamForm').on('submit', function (e) {
            e.preventDefault();
            var form = $(this);
            var submitBtn = form.find('button[type="submit"]');
            var messageDiv = $('#formMessage');
            submitBtn.prop('disabled', true).addClass('opacity-50 cursor-not-allowed').text('Creating...');
            messageDiv.addClass('hidden').removeClass('text-green-400 text-red-400');
            $.ajax({
                url: 'api/create_team.php',
                type: 'POST',
                data: form.serialize(),
                dataType: 'json',
                success: function (response) {
                    messageDiv.removeClass('hidden');
                    if (response.status === 'success') {
                        messageDiv.addClass('text-green-400').text(response.message);
                        form[0].reset();
                    } else {
                        messageDiv.addClass('text-red-400').text(response.message);
                    }
                },
                error: function () {
                    messageDiv.removeClass('hidden').addClass('text-red-400').text('An error occurred. Please try again.');
                },
                complete: function () {
                    submitBtn.prop('disabled', false).removeClass('opacity-50 cursor-not-allowed').text('Create Team');
                }
            });
        });

        // Count Up Animation
        document.querySelectorAll('.counter-up').forEach(function (counter) {
            var target = +counter.getAttribute('data-target');
            var duration = 2000;
            var increment = target / (duration / 16);
            var current = 0;
            var update = function () {
                current += increment;
                if (current < target) {
                    counter.innerText = Math.ceil(current);
                    requestAnimationFrame(update);
                } else {
                    counter.innerText = target;
                }
            };
            update();
        });
    }
});
