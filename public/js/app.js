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
    initSettingsTabs();
    initQrGeneration();
    initSidebarActive();
    initTableSearch();
    initFilters();
    initPagination();
});

// ---------- Modals ----------
function initModals() {
    const openBtns = document.querySelectorAll('[data-modal-target]');
    const closeBtns = document.querySelectorAll('[data-modal-close]');

    openBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const target = btn.dataset.modalTarget;
            const modal = document.getElementById(target);
            if (modal) {
                modal.classList.add('show');
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
            if (modal) modal.classList.remove('show');
        });
    });

    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) overlay.classList.remove('show');
        });
    });


}

function populateEditModal(studentId) {
    // TODO: Fetch student records from database
    const student = mockStudents.find(s => s.id === studentId) || mockStudents[0];
    const modal = document.getElementById('editStudentModal');
    if (!modal) return;

    modal.querySelector('[name="firstName"]').value = student.firstName;
    modal.querySelector('[name="middleName"]').value = student.middleName || '';
    modal.querySelector('[name="lastName"]').value = student.lastName;
    modal.querySelector('[name="lrn"]').value = student.lrn;
    modal.querySelector('[name="gradeLevel"]').value = student.gradeLevel;
    const genderRadio = modal.querySelector(`input[name="gender"][value="${student.gender}"]`);
    if (genderRadio) genderRadio.checked = true;
}

// ---------- Password Toggle ----------
function initPasswordToggle() {
    document.querySelectorAll('.toggle-pw').forEach(btn => {
        btn.addEventListener('click', () => {
            const input = btn.closest('.input-wrapper').querySelector('input');
            if (!input) return;
            const type = input.type === 'password' ? 'text' : 'password';
            input.type = type;
            btn.textContent = type === 'password' ? '👁' : '👁‍🗨';
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

    // Save buttons (placeholder)
    document.querySelectorAll('.btn-success').forEach(btn => {
        if (btn.closest('.settings-section')) {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                // TODO: Connect form submission to backend
                alert('Settings saved! (Placeholder - no database connected)');
            });
        }
    });
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
    document.querySelectorAll('.nav-item').forEach(item => {
        const href = item.getAttribute('href');
        if (href && currentPath.includes(href)) {
            item.classList.add('active');
        }
    });
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

// ---------- Filter Buttons ----------
function initFilters() {
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            // TODO: Implement advanced filtering
            alert('Filter functionality placeholder - connect to database later');
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

// ---------- View Report / Back Navigation ----------
function viewReport(url) {
    // Placeholder navigation - in real app this would navigate to route
    window.location.href = url;
}

// ---------- Archive / Delete (Placeholder) ----------
function archiveStudent(studentId) {
    if (confirm('Are you sure you want to archive this student?')) {
        // TODO: Send archive request to backend
        alert(`Student ${studentId} archived! (Placeholder - no database connected)`);
    }
}

function archiveTeacher(teacherId) {
    if (confirm('Are you sure you want to remove this teacher?')) {
        // TODO: Send delete request to backend
        alert(`Teacher ${teacherId} removed! (Placeholder - no database connected)`);
    }
}
