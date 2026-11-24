// ============================================
// ECOCOLLECT - Admin Dashboard JavaScript
// TODO: Replace mock data with database/API data
// ============================================

// ---------- Mock Data ----------
const mockStudents = [
    { id: 'STU001', lrn: '123456789012', firstName: 'Kathleen', middleName: 'E.', lastName: 'Tabadero', fullName: 'Kathleen E. Tabadero', gradeLevel: 'Grade 6', gender: 'Female', qrCode: 'Q001', totalPoints: 43, bottlesCollected: 40, status: 'Active' },
    { id: 'STU002', lrn: '123456789013', firstName: 'Joy', middleName: 'O.', lastName: 'Tabadero', fullName: 'Joy O. Tabadero', gradeLevel: 'Grade 5', gender: 'Female', qrCode: 'Q002', totalPoints: 38, bottlesCollected: 35, status: 'Active' },
    { id: 'STU003', lrn: '123456789014', firstName: 'Jerence', middleName: 'C.', lastName: 'Tabadero', fullName: 'Jerence C. Tabadero', gradeLevel: 'Grade 4', gender: 'Male', qrCode: 'Q003', totalPoints: 50, bottlesCollected: 48, status: 'Active' },
    { id: 'STU004', lrn: '123456789015', firstName: 'Patricia', middleName: 'R.', lastName: 'Tabadero', fullName: 'Patricia R. Tabadero', gradeLevel: 'Grade 3', gender: 'Female', qrCode: 'Q004', totalPoints: 32, bottlesCollected: 30, status: 'Active' },
    { id: 'STU005', lrn: '123456789016', firstName: 'Denver', middleName: 'P.', lastName: 'Tabadero', fullName: 'Denver P. Tabadero', gradeLevel: 'Grade 2', gender: 'Male', qrCode: 'Q005', totalPoints: 45, bottlesCollected: 42, status: 'Active' },
    { id: 'STU006', lrn: '123456789017', firstName: 'Karen', middleName: 'N.', lastName: 'Tabadero', fullName: 'Karen N. Tabadero', gradeLevel: 'Grade 6', gender: 'Female', qrCode: 'Q006', totalPoints: 40, bottlesCollected: 38, status: 'Active' },
    { id: 'STU007', lrn: '123456789018', firstName: 'Sophia', middleName: 'M.', lastName: 'Cruz', fullName: 'Sophia M. Cruz', gradeLevel: 'Kindergarten', gender: 'Female', qrCode: 'Q007', totalPoints: 15, bottlesCollected: 12, status: 'Active' },
];

const mockTeachers = [
    { id: 'ADM001', name: 'Juan Dela Cruz', email: 'juan@ecocollect.edu', position: 'Admin' },
    { id: 'ADM002', name: 'Maria Santos', email: 'maria@ecocollect.edu', position: 'Teacher' },
    { id: 'ADM003', name: 'Pedro Reyes', email: 'pedro@ecocollect.edu', position: 'Teacher' },
    { id: 'ADM004', name: 'Ana Gonzales', email: 'ana@ecocollect.edu', position: 'Admin' },
];

const mockCollections = [
    { lrn: '123456789012', date: '2025-01-06', time: '08:30 AM', bottles: 5 },
    { lrn: '123456789013', date: '2025-01-06', time: '09:00 AM', bottles: 3 },
    { lrn: '123456789014', date: '2025-01-06', time: '09:15 AM', bottles: 7 },
    { lrn: '123456789015', date: '2025-01-07', time: '08:45 AM', bottles: 4 },
    { lrn: '123456789016', date: '2025-01-07', time: '10:00 AM', bottles: 6 },
    { lrn: '123456789017', date: '2025-01-08', time: '08:20 AM', bottles: 8 },
    { lrn: '123456789012', date: '2025-01-08', time: '09:30 AM', bottles: 2 },
    { lrn: '123456789013', date: '2025-01-08', time: '10:15 AM', bottles: 5 },
    { lrn: '123456789014', date: '2025-01-09', time: '08:50 AM', bottles: 3 },
    { lrn: '123456789015', date: '2025-01-09', time: '09:10 AM', bottles: 6 },
];

// ---------- DOM Ready ----------
document.addEventListener('DOMContentLoaded', () => {
    initModals();
    initPasswordToggle();
    initPasswordSubmitCheck();
    initSettingsTabs();
    initQrGeneration();
    initSidebarActive();
    initTableSearch();
    // initFilters() removed — filtering is handled by GET forms
    initPagination();
});

// ---------- Permission denied popup ----------
function showPermissionDenied(action, message) {
    const modal = document.getElementById('permissionDeniedModal');
    if (!modal) return;
    const contact = 'Please contact your Teacher or Principal to request access.';
    const defaults = {
        add: "You don't have permission to add students. " + contact,
        edit: "You don't have permission to edit students. " + contact,
        delete: "You don't have permission to delete students. " + contact,
    };
    const text = modal.querySelector('#permissionDeniedMessage');
    if (text) text.textContent = message || defaults[action] || defaults.add;
    modal.classList.add('show');
    document.body.classList.add('modal-open');
}

window.EcoPermission = {
    show: showPermissionDenied,
    /**
     * Call before opening ANY permission-gated modal. Returns false when the
     * action must be blocked; in that case the warning popup is shown and the
     * target form modal is explicitly closed.
     */
    guard: function (btn) {
        const action = btn.dataset.permissionAction;
        if (action && btn.dataset.permissionAllowed === 'false') {
            const target = document.getElementById(btn.dataset.modalTarget);
            if (target) {
                target.classList.remove('show');
                target.setAttribute('aria-hidden', 'true');
                target.setAttribute('inert', '');
            }
            showPermissionDenied(action);
            return false;
        }
        return true;
    },
    showFromResponse: function (payload) {
        if (payload && payload.permission_denied) {
            showPermissionDenied(payload.action, payload.message || payload.error);
            return true;
        }
        return false;
    },
};

// ---------- Modals ----------
function initModals() {
    const openBtns = document.querySelectorAll('[data-modal-target]');
    const closeBtns = document.querySelectorAll('[data-modal-close]');

    openBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            if (!window.EcoPermission.guard(btn)) return;
            const target = btn.dataset.modalTarget;
            const modal = document.getElementById(target);
            if (modal) {
                modal.classList.add('show');
                document.body.classList.add('modal-open');
                // If edit modal, populate with data
                if (target === 'editStudentModal') {
                    populateEditModal(btn.dataset.studentId);
                }
            }
        });
    });

    closeBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = btn.closest('.modal-overlay');
            if (modal) {
                modal.classList.remove('show');
                document.body.classList.remove('modal-open');
            }
        });
    });

    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                overlay.classList.remove('show');
                document.body.classList.remove('modal-open');
            }
        });
    });

    if (window.ecPermissionDenied && window.ecPermissionDenied.permission_denied) {
        showPermissionDenied(
            window.ecPermissionDenied.action,
            window.ecPermissionDenied.message || window.ecPermissionDenied.error
        );
    }
}

function populateEditModal(studentId) {
    // Legacy mock filler — only runs on legacy camelCase forms. The real
    // Student Management edit modal is populated by the students page script.
    const modal = document.getElementById('editStudentModal');
    if (!modal) return;
    if (!modal.querySelector('[name="firstName"]')) return;

    const student = mockStudents.find(s => s.id === studentId) || mockStudents[0];

    modal.querySelector('[name="firstName"]').value = student.firstName;
    modal.querySelector('[name="middleName"]').value = student.middleName || '';
    modal.querySelector('[name="lastName"]').value = student.lastName;
    modal.querySelector('[name="lrn"]').value = student.lrn;
    modal.querySelector('[name="gradeLevel"]').value = student.gradeLevel;
    const genderRadio = modal.querySelector(`input[name="gender"][value="${student.gender}"]`);
    if (genderRadio) genderRadio.checked = true;
}

// ---------- Password Toggle (shared by every password field) ----------
// One Lucide icon set (eye / eye-off) and one behaviour for the whole app.
(function () {
    const SVG_ATTRS = 'class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" '
        + 'stroke="currentColor" stroke-width="2" stroke-linecap="round" '
        + 'stroke-linejoin="round" aria-hidden="true" focusable="false"';
    const ICONS = {
        hidden: '<svg ' + SVG_ATTRS + '><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>',
        visible: '<svg ' + SVG_ATTRS + '><path d="M17.94 11.34a10 10 0 0 1-4.11 0 10.75 10.75 0 0 1-5.88-1.86M17.94 3c-.51 0-1 .45-1 1s.45 1 1 1"/><circle cx="12" cy="12" r="3"/><path d="M1 1l15 15M4.93 4.93 19.07 19.07"/></svg>',
    };

    function findInput(btn) {
        const targetId = btn.getAttribute('data-target');
        if (targetId) return document.getElementById(targetId);
        const wrapper = btn.closest('.input-wrapper, .password-field-wrapper');
        return wrapper ? wrapper.querySelector('input') : null;
    }

    function render(btn, isVisible) {
        btn.innerHTML = isVisible ? ICONS.visible : ICONS.hidden;
        const showLabel = btn.getAttribute('data-show-label') || 'Show password';
        const hideLabel = btn.getAttribute('data-hide-label') || 'Hide password';
        const label = isVisible ? hideLabel : showLabel;
        btn.setAttribute('aria-label', label);
        btn.setAttribute('title', label);
        btn.setAttribute('aria-pressed', isVisible ? 'true' : 'false');
    }

    function toggle(btn) {
        const input = findInput(btn);
        if (!input) return;
        const isVisible = input.type === 'password';
        input.type = isVisible ? 'text' : 'password';
        render(btn, isVisible);
    }

    function reset(root) {
        (root || document).querySelectorAll('.password-toggle').forEach(function (btn) {
            const input = findInput(btn);
            if (input) input.type = 'password';
            render(btn, false);
        });
    }

    window.EcoPasswordToggle = { render: render, toggle: toggle, reset: reset };
})();

function initPasswordToggle() {
    // Delegation keeps dynamically opened modals working without rebinding.
    document.addEventListener('click', function (event) {
        const btn = event.target.closest('.password-toggle, .toggle-pw');
        if (!btn) return;
        event.preventDefault();
        window.EcoPasswordToggle.toggle(btn);
    });
    document.querySelectorAll('.password-toggle, .toggle-pw').forEach(function (btn) {
        window.EcoPasswordToggle.render(btn, false);
    });
}

// ---------- Password Match Check on Submit ----------
function initPasswordSubmitCheck() {
    document.querySelectorAll('form').forEach(form => {
        const password = form.querySelector('input[name="password"]');
        const confirm = form.querySelector('input[name="password_confirmation"]');
        if (!password || !confirm) return;

        const showPasswordAlert = () => {
            let alertBox = form.querySelector('.password-confirm-alert');
            if (!alertBox) {
                alertBox = document.createElement('div');
                alertBox.className = 'password-confirm-alert';
                alertBox.style.display = 'none';
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn && submitBtn.parentNode) {
                    submitBtn.parentNode.insertBefore(alertBox, submitBtn);
                } else {
                    form.appendChild(alertBox);
                }
            }

            alertBox.textContent = "Password didn't match. Try again.";
            alertBox.style.transition = 'none';
            alertBox.style.display = 'block';
            alertBox.style.opacity = '1';
            alertBox.style.transform = 'translateY(0)';

            setTimeout(() => {
                alertBox.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                alertBox.style.opacity = '0';
                alertBox.style.transform = 'translateY(-8px)';

                setTimeout(() => {
                    alertBox.style.display = 'none';
                }, 500);
            }, 4000);
        };

        form.addEventListener('submit', event => {
            if (password.value !== confirm.value) {
                event.preventDefault();
                confirm.classList.add('password-invalid');
                showPasswordAlert();
                confirm.focus();
            }
        });

        confirm.addEventListener('input', () => {
            confirm.classList.remove('password-invalid');

            const alertBox = form.querySelector('.password-confirm-alert');
            if (alertBox) {
                alertBox.style.display = 'none';
            }
        });
    });
}

// ---------- Settings Tabs ----------
function initSettingsTabs() {
    const tabs = document.querySelectorAll('.settings-tab');
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            document.querySelectorAll('.settings-section').forEach(s => s.classList.remove('active'));
            const target = document.getElementById(tab.dataset.section);
            if (target) target.classList.add('active');
        });
    });

    // Settings forms submit normally to the backend. Success and error
    // feedback are rendered by the shared EcoCollect notification system
    // through partials/alerts.blade.php (no native alerts, no placeholder).
}

// ---------- QR Generation ----------
function initQrGeneration() {
    const generateBtn = document.querySelector('#generateQrBtn');
    if (generateBtn) {
        generateBtn.addEventListener('click', () => {
            // TODO: Generate QR code from real student LRN
            const alert = document.querySelector('.alert-success');
            if (alert) {
                alert.classList.add('show');
                setTimeout(() => alert.classList.remove('show'), 3000);
            }
        });
    }
}

// ---------- Sidebar Active State ----------
function initSidebarActive() {
    const currentPath = window.location.pathname;
    const items = Array.from(document.querySelectorAll('#sidebar-nav .nav-item'));

    // Pick a single best match (longest matching href) so only one item is highlighted.
    let best = null;
    let bestLength = -1;

    items.forEach(item => {
        const href = item.getAttribute('href');
        if (!href || href === '#' || href.charAt(0) !== '/') return;
        if (currentPath.includes(href) && href.length > bestLength) {
            best = item;
            bestLength = href.length;
        }
    });

    if (best && !best.classList.contains('active')) {
        best.classList.add('active');
        best.setAttribute('aria-current', 'page');
    }
}

// ---------- Table Search (Students) ----------
function initTableSearch() {
    const searchInputs = document.querySelectorAll('.search-box input');
    searchInputs.forEach(input => {
        input.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase().trim();
            const table = input.closest('.table-container') || input.closest('body').querySelector('.table-container');
            if (!table) return;

            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });

            // Show/hide no results
            const visibleRows = table.querySelectorAll('tbody tr[style*="display: none"]');
            const totalRows = rows.length;
            const hiddenCount = visibleRows.length;
            // Update pagination info
            const pageInfo = table.querySelector('.pagination .page-info');
            if (pageInfo) {
                const showing = totalRows - hiddenCount;
                pageInfo.textContent = `Showing 1 to ${showing} of ${showing} entries`;
            }
        });
    });
}

// ---------- Pagination ----------
function initPagination() {
    document.querySelectorAll('.page-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const container = btn.closest('.pagination');
            if (!container) return;
            container.querySelectorAll('.page-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            // TODO: Load actual page data from database
        });
    });
}
