@extends('layouts.admin')

@section('page-title', 'Sub Teacher Assignments')

@section('content')
    <div class="filter-card sta-filter-card">
        <div class="filter-card-header"><i class="fas fa-filter"></i> Search Assignments</div>
        <div class="filter-card-body">
            <div class="sta-filter-form">
                <div class="filter-search">
                    <label for="staSearch">Search</label>
                    <input type="text" id="staSearch" value="{{ request('search') }}" placeholder="Sub teacher, username or assigned teacher..." aria-label="Search sub teacher or assigned teacher" autocomplete="off">
                </div>
                <div class="filter-item">
                    <label for="staStatus">Status</label>
                    <select id="staStatus" aria-label="Filter by assignment status">
                        <option value="">All Statuses</option>
                        <option value="assigned" {{ request('status') === 'assigned' ? 'selected' : '' }}>Assigned</option>
                        <option value="unassigned" {{ request('status') === 'unassigned' ? 'selected' : '' }}>Unassigned</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="sta-loading" id="staLoading" aria-live="polite" hidden>Updating results…</div>
            </div>
        </div>
    </div>

    <div class="table-card sta-table-card">
        <div class="table-header">
            <h2 class="accounts-section-title">Sub Teacher Assignments</h2>
            <span class="sta-count" id="staCount">{{ $subTeachers->total() }} account(s)</span>
        </div>
        <div class="table-responsive" id="staResultsContainer">
            @include('partials.sub-teacher-assignment-results', ['subTeachers' => $subTeachers])
        </div>
    </div>

    <!-- View Assignment Modal -->
    <div id="staViewModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="staViewTitle">
        <div class="modal-content">
            <div class="modal-header">
                <div><h2 id="staViewTitle">View Assignment</h2><p>Assignment details and delegated access.</p></div>
                <button type="button" class="modal-close" data-modal-close aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="sta-detail"><span>Sub Teacher</span><strong id="staViewSubTeacher">—</strong></div>
                <div class="sta-detail"><span>Assigned Teacher</span><strong id="staViewTeacher">—</strong></div>
                <div class="sta-detail"><span>Access Level</span><strong id="staViewAccess">—</strong></div>
                <div class="sta-detail"><span>Status</span><strong id="staViewStatus">—</strong></div>
                <div class="sta-detail"><span>Permissions</span><strong id="staViewPermissions">—</strong></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-modal-close>Close</button></div>
        </div>
    </div>

    <!-- Edit / Assign Modal -->
    <div id="staEditModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="staEditTitle">
        <div class="modal-content">
            <div class="modal-header">
                <div><h2 id="staEditTitle">Edit Assignment</h2><p>Only Admin and Principal can manage assignments.</p></div>
                <button type="button" class="modal-close" data-modal-close aria-label="Close">&times;</button>
            </div>
            <form id="staEditForm">
                <div class="modal-body">
                    <input type="hidden" id="staFormId">
                    <input type="hidden" id="staFormMode" value="edit">
                    <p class="sta-sub-name" id="staFormSubName">—</p>
                    <div class="sta-field">
                        <label for="staFormTeacher">Assigned Teacher</label>
                        <select id="staFormTeacher" required>
                            <option value="">Select a Teacher</option>
                            @foreach($teachers as $teacher)
                                <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sta-field">
                        <label for="staFormAccess">Access Level</label>
                        <select id="staFormAccess" required>
                            @foreach(\App\Services\CollaborationService::accessLevels() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="staCustomBlock" class="sta-custom" hidden>
                        <div class="sta-sub-title">Custom Permissions</div>
                        <div class="sta-permission-grid">
                            @foreach(\App\Services\CollaborationService::configurablePermissions() as $key => $label)
                                <label class="sta-permission-item"><input type="checkbox" name="sta_permissions" value="{{ $key }}"> {{ $label }}</label>
                            @endforeach
                        </div>
                    </div>
                    <p id="staFormError" class="sta-error" role="alert" hidden></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                    <button type="submit" class="btn btn-success" id="staFormSubmit">Save Assignment</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Unassign Confirmation Modal -->
    <div id="staUnassignModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="staUnassignTitle">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-icon danger"><span class="icon">⚠</span></div>
                <div><h2 id="staUnassignTitle">Unassign Sub Teacher?</h2><p id="staUnassignMessage"></p></div>
                <button type="button" class="modal-close" data-modal-close aria-label="Close">&times;</button>
            </div>
            <div class="modal-body"><p>Access to the assigned Teacher's records will be revoked immediately. Historical activity records are preserved.</p></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-modal-close>Cancel</button><button type="button" class="btn btn-danger" id="staConfirmUnassign">Unassign</button></div>
        </div>
    </div>

    <style>
    .sta-filter-card .sta-filter-form{display:grid;grid-template-columns:minmax(240px,2fr) minmax(160px,1fr) auto;gap:12px;align-items:end;}
    .sta-filter-card label{display:block;font-size:12px;font-weight:600;color:#5a6a7a;margin-bottom:4px;text-transform:uppercase;letter-spacing:.04em;}
    .sta-filter-card input,.sta-filter-card select{width:100%;height:38px;padding:8px 12px;border:1px solid #cdd5df;border-radius:6px;font-size:13px;background:#fff;}
    .sta-loading{font-size:12px;color:#6b7280;align-self:center;white-space:nowrap;}
    .sta-loading[hidden]{display:none!important;}
    .sta-table-card .table-header{display:flex;align-items:center;justify-content:space-between;padding:14px 20px;}
    .sta-table-card table{width:100%;border-collapse:collapse;}
    .sta-table-card th,.sta-table-card td{padding:10px 14px;font-size:13px;vertical-align:middle;border-bottom:1px solid #e5e7eb;}
    .sta-table-card th{font-size:11px;letter-spacing:.05em;text-transform:uppercase;color:#6b7280;white-space:nowrap;}
    .sta-count{font-size:13px;color:#6b7280;}
    .sta-status{display:inline-block;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:600;}
    .sta-status-active{background:#dcfce7;color:#166534;}
    .sta-status-unassigned{background:#fef3c7;color:#92400e;}
    .sta-status-inactive{background:#fee2e2;color:#991b1b;}
    .sta-table-card .table-responsive{overflow-x:auto;}
    .sta-table-card th.sta-actions-header,.sta-table-card tbody td:last-child{text-align:center;white-space:nowrap;min-width:132px;}
    .sta-actions{display:flex;justify-content:center;align-items:center;gap:8px;flex-wrap:nowrap;}
    .sta-icon-btn{display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;min-width:34px;padding:0;border-radius:9px;border:1px solid transparent;cursor:pointer;line-height:1;appearance:none;-webkit-appearance:none;background:none;transition:background .15s ease,border-color .15s ease,opacity .15s ease,box-shadow .15s ease;}
    .sta-icon-btn svg{width:17px;height:17px;flex:0 0 17px;display:block;pointer-events:none;}
    .sta-icon-btn:hover{opacity:.9;}
    .sta-icon-btn:focus-visible{outline:none;box-shadow:0 0 0 3px rgba(74,144,217,.28);}
    .sta-icon-view{background:#0ea5e9;color:#fff;border-color:#0ea5e9;}
    .sta-icon-edit{background:#f59e0b;color:#111827;border-color:#f59e0b;}
    .sta-icon-unassign{background:#dc2626;color:#fff;border-color:#dc2626;}
    .sta-detail{display:flex;justify-content:space-between;gap:16px;padding:10px 0;border-bottom:1px solid #f3f4f6;font-size:13px;}
    .sta-detail span{color:#6b7280;}
    .sta-sub-name{font-weight:600;color:#111827;margin-bottom:14px;}
    .sta-field label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;}
    .sta-field select{width:100%;height:42px;padding:8px 12px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;font-size:14px;}
    .sta-sub-title{font-size:12px;font-weight:700;text-transform:uppercase;color:#475569;margin:14px 0 8px;}
    .sta-custom{border:1px solid #cbd5e1;border-radius:8px;padding:12px;background:#f8fafc;}
    .sta-custom[hidden]{display:none!important;}
    .sta-permission-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px 12px;}
    .sta-permission-item{display:flex;align-items:center;gap:8px;font-size:13px;color:#374151;cursor:pointer;}
    .sta-permission-item input{width:16px;height:16px;accent-color:#16a34a;}
    .sta-error{margin-top:12px;padding:10px 12px;background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;color:#991b1b;font-size:13px;}
    .modal-icon.danger{width:44px;height:44px;border-radius:50%;background:#fee2e2;display:flex;align-items:center;justify-content:center;flex:0 0 auto;}
    .sta-pagination{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;}
    .page-ellipsis{color:#6b7280;padding:0 4px;}
    .sta-loading{opacity:.75;}
    @media(max-width:720px){.sta-filter-card .sta-filter-form{grid-template-columns:1fr;}.sta-permission-grid{grid-template-columns:1fr;}}
    </style>
@endsection

@push('scripts')
<script>
(function () {
    var DATA_URL = @json(route('admin.sub-teacher-assignments.data'));
    var BASE_URL = @json(route('admin.sub-teacher-assignments'));
    var token = document.querySelector('meta[name="csrf-token"]').content;
    var searchInput = document.getElementById('staSearch');
    var statusSelect = document.getElementById('staStatus');
    var container = document.getElementById('staResultsContainer');
    var countEl = document.getElementById('staCount');
    var loadingEl = document.getElementById('staLoading');
    var viewModal = document.getElementById('staViewModal');
    var editModal = document.getElementById('staEditModal');
    var unassignModal = document.getElementById('staUnassignModal');
    var form = document.getElementById('staEditForm');
    var accessSelect = document.getElementById('staFormAccess');
    var customBlock = document.getElementById('staCustomBlock');
    var errorEl = document.getElementById('staFormError');
    var submitBtn = document.getElementById('staFormSubmit');

    var debounceTimer = null;
    var controller = null;
    var requestId = 0;
    var currentPage = 1;

    function toast(type, message) {
        if (window.EcoToast) window.EcoToast.show(type, message, { key: 'sub-teacher-assignment' });
    }
    function open(el) { el.classList.add('show'); document.body.classList.add('modal-open'); }
    function close(el) { el.classList.remove('show'); document.body.classList.remove('modal-open'); }
    function setLoading(on) { loadingEl.hidden = !on; container.style.opacity = on ? '0.65' : '1'; }

    function loadResults(page) {
        var id = ++requestId;
        if (controller) controller.abort();
        controller = new AbortController();
        setLoading(true);

        var params = new URLSearchParams({
            search: searchInput.value,
            status: statusSelect.value,
            page: page || 1
        });

        fetch(DATA_URL + '?' + params.toString(), {
            signal: controller.signal,
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (id !== requestId || !data.success) return;
            container.innerHTML = data.html;
            currentPage = data.pagination.current_page;
            if (countEl) countEl.textContent = data.pagination.total + ' account(s)';
        })
        .catch(function (err) {
            if (err && err.name === 'AbortError') return;
            toast('error', 'Unable to load assignments.');
        })
        .finally(function () { if (id === requestId) setLoading(false); });
    }

    searchInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        var value = this.value;
        debounceTimer = setTimeout(function () {
            loadResults(1);
            searchInput.focus();
        }, 350);
    });

    statusSelect.addEventListener('change', function () { loadResults(1); });

    container.addEventListener('click', function (e) {
        var pageBtn = e.target.closest('.sta-page');
        if (pageBtn) {
            if (pageBtn.disabled) return;
            loadResults(parseInt(pageBtn.dataset.page, 10));
            return;
        }
        var viewBtn = e.target.closest('.sta-view');
        if (viewBtn) {
            fetch(BASE_URL + '/' + viewBtn.dataset.id, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    var a = data.assignment || {};
                    document.getElementById('staViewSubTeacher').textContent = a.sub_teacher_name || '—';
                    document.getElementById('staViewTeacher').textContent = a.teacher_name || '—';
                    document.getElementById('staViewAccess').textContent = a.access_level || '—';
                    document.getElementById('staViewStatus').textContent = a.status || '—';
                    document.getElementById('staViewPermissions').textContent =
                        (a.custom_permissions || []).join(', ') || 'Default for access level';
                    open(viewModal);
                })
                .catch(function () { toast('error', 'Unable to load assignment details.'); });
            return;
        }
        var editBtn = e.target.closest('.sta-edit');
        if (editBtn) {
            var isCreate = editBtn.dataset.mode === 'create';
            document.getElementById('staFormId').value = editBtn.dataset.id || '';
            document.getElementById('staFormMode').value = isCreate ? 'create' : 'edit';
            document.getElementById('staFormSubName').textContent = editBtn.dataset.name || '';
            document.getElementById('staFormTeacher').value = editBtn.dataset.teacherId || '';
            accessSelect.value = editBtn.dataset.accessLevel || 'collaborator';
            var checks = [];
            try { checks = JSON.parse(editBtn.dataset.custom || '[]'); } catch (err) { checks = []; }
            form.querySelectorAll('input[name="sta_permissions"]').forEach(function (cb) {
                cb.checked = checks.indexOf(cb.value) !== -1;
            });
            document.getElementById('staEditTitle').textContent = isCreate ? 'Assign Sub Teacher' : 'Edit Assignment';
            submitBtn.textContent = isCreate ? 'Assign' : 'Save Assignment';
            errorEl.hidden = true;
            syncCustom();
            open(editModal);
            return;
        }
        var unassignBtn = e.target.closest('.sta-unassign');
        if (unassignBtn) {
            document.getElementById('staUnassignMessage').textContent = 'Unassign ' + unassignBtn.dataset.name + '?';
            unassignModal.dataset.id = unassignBtn.dataset.id;
            open(unassignModal);
        }
    });

    document.addEventListener('click', function (e) {
        var closeBtn = e.target.closest('[data-modal-close]');
        if (closeBtn) close(closeBtn.closest('.modal-overlay'));
    });

    function syncCustom() { customBlock.hidden = accessSelect.value !== 'custom'; }
    accessSelect.addEventListener('change', syncCustom);
    syncCustom();

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        errorEl.hidden = true;
        var id = document.getElementById('staFormId').value;
        var isCreate = document.getElementById('staFormMode').value === 'create';
        var checks = Array.prototype.slice.call(form.querySelectorAll('input[name="sta_permissions"]:checked'))
            .map(function (cb) { return cb.value; });
        var payload = {
            _token: token,
            teacher_id: document.getElementById('staFormTeacher').value,
            access_level: accessSelect.value,
            custom_permissions: accessSelect.value === 'custom' ? checks : []
        };
        if (isCreate) payload.sub_teacher_id = id;

        submitBtn.disabled = true;
        var url = isCreate ? BASE_URL : BASE_URL + '/' + id;
        fetch(url, {
            method: isCreate ? 'POST' : 'PUT',
            body: JSON.stringify(payload),
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token }
        })
        .then(function (r) { return r.json().catch(function () { return null; }).then(function (data) { return { ok: r.ok, data: data || {} }; }); })
        .then(function (result) {
            if (result.ok && result.data.success) {
                close(editModal);
                toast('success', result.data.message);
                loadResults(currentPage);
                return;
            }
            errorEl.textContent = result.data.message || result.data.error || 'Unable to save the assignment.';
            errorEl.hidden = false;
        })
        .catch(function () { toast('error', 'Unable to save the assignment.'); })
        .finally(function () { submitBtn.disabled = false; });
    });

    document.getElementById('staConfirmUnassign').addEventListener('click', function () {
        var id = unassignModal.dataset.id;
        if (!id) return;
        this.disabled = true;
        fetch(BASE_URL + '/' + id, {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': token }
        })
        .then(function (r) { return r.json().catch(function () { return null; }).then(function (data) { return { ok: r.ok, data: data || {} }; }); })
        .then(function (result) {
            if (result.ok && result.data.success) {
                close(unassignModal);
                toast('success', result.data.message);
                loadResults(currentPage);
            } else {
                toast('error', result.data.message || result.data.error || 'Unable to unassign.');
            }
        })
        .catch(function () { toast('error', 'Unable to unassign.'); })
        .finally(function () { this.disabled = false; }.bind(this));
    });
})();
</script>
@endpush
