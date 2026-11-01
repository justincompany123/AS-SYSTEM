
/*
|--------------------------------------------------------------------------
| ALTAR SERVERS WEB SYSTEM
| Main JavaScript
| St. Francis Xavier Cathedral
|--------------------------------------------------------------------------
*/

'use strict';

/* RUN AFTER THE PAGE LOADS */
document.addEventListener('DOMContentLoaded', function () {

    /* --------------------------------------------------
       1. MOBILE SIDEBAR
    -------------------------------------------------- */

    const sidebar = document.querySelector('.sidebar');
    const menuButton = document.querySelector('.mobile-menu-button');
    let overlay = document.querySelector('.sidebar-overlay');

    if (sidebar && menuButton) {

        // Create an overlay if the page does not already contain one.
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            document.body.appendChild(overlay);
        }

        function closeSidebar() {
            sidebar.classList.remove('open');
            overlay.classList.remove('show');
            menuButton.setAttribute('aria-expanded', 'false');
        }

        menuButton.addEventListener('click', function () {
            const isOpen = sidebar.classList.toggle('open');

            overlay.classList.toggle('show', isOpen);
            menuButton.setAttribute('aria-expanded', String(isOpen));
        });

        overlay.addEventListener('click', closeSidebar);

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeSidebar();
            }
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth > 768) {
                closeSidebar();
            }
        });
    }


    /* --------------------------------------------------
       2. DELETE CONFIRMATION
    -------------------------------------------------- */

    document.querySelectorAll('[data-confirm]').forEach(function (element) {

        element.addEventListener('click', function (event) {

            const message = element.dataset.confirm ||
                'Are you sure you want to continue?';

            if (!window.confirm(message)) {
                event.preventDefault();
                event.stopPropagation();
            }

        });

    });


    /* --------------------------------------------------
       3. PASSWORD VISIBILITY
    -------------------------------------------------- */

    document.querySelectorAll('[data-toggle-password]').forEach(function (button) {

        button.addEventListener('click', function () {

            const targetId = button.dataset.togglePassword;
            const passwordInput = document.getElementById(targetId);

            if (!passwordInput) {
                return;
            }

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                button.textContent = 'Hide';
                button.setAttribute('aria-label', 'Hide password');
            } else {
                passwordInput.type = 'password';
                button.textContent = 'Show';
                button.setAttribute('aria-label', 'Show password');
            }

        });

    });


    /* --------------------------------------------------
       4. AUTOMATIC ALERT DISMISSAL
    -------------------------------------------------- */

    document.querySelectorAll('[data-auto-dismiss]').forEach(function (alert) {

        const delay = Number(alert.dataset.autoDismiss) || 5000;

        window.setTimeout(function () {

            alert.style.transition = 'opacity 0.3s ease';
            alert.style.opacity = '0';

            window.setTimeout(function () {
                alert.remove();
            }, 350);

        }, delay);

    });


    /* --------------------------------------------------
       5. PRINT BUTTONS
    -------------------------------------------------- */

    document.querySelectorAll('[data-print]').forEach(function (button) {

        button.addEventListener('click', function () {
            window.print();
        });

    });


    /* --------------------------------------------------
       6. SEARCH TABLES
    -------------------------------------------------- */

    document.querySelectorAll('[data-table-search]').forEach(function (input) {

        input.addEventListener('input', function () {

            const tableId = input.dataset.tableSearch;
            const table = document.getElementById(tableId);

            if (!table) {
                return;
            }

            const searchValue = input.value.toLowerCase().trim();

            table.querySelectorAll('tbody tr').forEach(function (row) {

                const rowText = row.textContent.toLowerCase();

                row.style.display = rowText.includes(searchValue)
                    ? ''
                    : 'none';

            });

        });

    });


    /* --------------------------------------------------
       7. CHARACTER COUNTER
    -------------------------------------------------- */

    document.querySelectorAll('[data-character-counter]').forEach(function (input) {

        const counterId = input.dataset.characterCounter;
        const counter = document.getElementById(counterId);

        if (!counter) {
            return;
        }

        function updateCounter() {
            counter.textContent = input.value.length;
        }

        input.addEventListener('input', updateCounter);

        updateCounter();

    });


    /* --------------------------------------------------
       8. PREVENT DOUBLE FORM SUBMISSION
    -------------------------------------------------- */

    document.querySelectorAll('form[data-prevent-double-submit]').forEach(function (form) {

        form.addEventListener('submit', function () {

            const submitButton = form.querySelector(
                'button[type="submit"], input[type="submit"]'
            );

            if (!submitButton) {
                return;
            }

            if (form.dataset.submitted === 'true') {
                return;
            }

            form.dataset.submitted = 'true';
            submitButton.disabled = true;

            if (submitButton.tagName === 'BUTTON') {
                submitButton.dataset.originalText = submitButton.textContent;
                submitButton.textContent = 'PLEASE WAIT...';
            }

        });

    });


    /* --------------------------------------------------
       9. CURRENT YEAR
    -------------------------------------------------- */

    document.querySelectorAll('[data-current-year]').forEach(function (element) {
        element.textContent = new Date().getFullYear();
    });


    /* --------------------------------------------------
       10. ACTIVE SIDEBAR LINK
    -------------------------------------------------- */

    const currentPath = window.location.pathname;

    document.querySelectorAll('.sidebar a[href]').forEach(function (link) {

        const linkUrl = new URL(link.href, window.location.origin);

        if (linkUrl.pathname === currentPath) {
            link.classList.add('active');
        }

    });

});
