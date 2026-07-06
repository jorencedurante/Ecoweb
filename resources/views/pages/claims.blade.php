@extends('layouts.admin')

@section('title', 'EcoCollect - Claim Items')
@section('page-title', 'Claim Items')
@section('page-subtitle', 'Manage reward items and student claims')

@section('content')
    {{-- Pending Claims Card --}}
    <div class="data-card pending-claims-card" style="margin-bottom:24px;border-color:#FBBF24;">
        <div class="data-card-header">
            <h3>Pending Item Claims</h3>
            <p>Review student item requests before approval.</p>
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>LRN</th>
                        <th>Item Requested</th>
                        <th>Points Required</th>
                        <th>Student Points</th>
                        <th>Request Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($pendingClaims as $index => $claim)
                        <tr>
                            <td>{{ $pendingClaims->firstItem() + $index }}</td>
                            <td><strong>{{ $claim->student->full_name ?? 'Student not found' }}</strong></td>
                            <td>{{ $claim->student->lrn ?? 'N/A' }}</td>
                            <td>{{ $claim->item_name }}</td>
                            <td>{{ $claim->points_deducted }}</td>
                            <td>{{ $claim->student->total_points ?? 0 }}</td>
                            <td>{{ $claim->created_at ? $claim->created_at->format('M d, Y') : 'N/A' }}</td>
                            <td>
                                <span class="status-badge pending">Pending</span>
                            </td>
                            <td class="action-buttons">
                                <form method="POST" action="{{ route('claims.approve', $claim->id) }}" onsubmit="return confirm('Approve this item claim?');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn-approve">Approve</button>
                                </form>

                                <form method="POST" action="{{ route('claims.reject', $claim->id) }}" onsubmit="return confirm('Reject this item claim?');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn-reject">Reject</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align:center;padding:30px;color:var(--text-light);">No pending item claims.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @if($pendingClaims->hasPages())
            <div class="pagination" style="padding:12px 16px;">
                <span class="page-info">Showing {{ $pendingClaims->firstItem() }} to {{ $pendingClaims->lastItem() }} of {{ $pendingClaims->total() }} entries</span>
                <div class="page-btns">
                    @for ($i = 1; $i <= $pendingClaims->lastPage(); $i++)
                        <a href="{{ $pendingClaims->url($i) }}" class="page-btn {{ $pendingClaims->currentPage() == $i ? 'active' : '' }}">{{ $i }}</a>
                    @endfor
                </div>
            </div>
            @endif
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px;">
        {{-- Claim Item Form --}}
        <div class="card">
            <div class="card-body">
                <h4 style="font-size:15px;font-weight:600;margin-bottom:16px;">+ Add Claim Item</h4>
                <form method="POST" action="{{ route('claim-items.store') }}">
                    @csrf
                    @if($errors->has('item_name') || $errors->has('points_required') || $errors->has('quantity') || $errors->has('status'))
                        <div class="form-error-message">Please check the claim item fields.</div>
                    @endif
                    <div class="form-group">
                        <label>Item Name</label>
                        <input type="text" name="item_name" value="{{ old('item_name') }}" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="2" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">{{ old('description') }}</textarea>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                        <div class="form-group">
                            <label>Points Required</label>
                            <input type="number" name="points_required" value="{{ old('points_required') }}" min="1" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                        </div>
                        <div class="form-group">
                            <label>Quantity</label>
                            <input type="number" name="quantity" value="{{ old('quantity', 0) }}" min="0" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                                <option value="Available" {{ old('status') === 'Available' ? 'selected' : '' }}>Available</option>
                                <option value="Unavailable" {{ old('status') === 'Unavailable' ? 'selected' : '' }}>Unavailable</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success" style="margin-top:12px;">Add Item</button>
                </form>
            </div>
        </div>

        {{-- Claim Item Form --}}
        <div class="card">
            <div class="card-body">
                <h4 style="font-size:15px;font-weight:600;margin-bottom:16px;">Claim Item for Student</h4>
                <form method="POST" action="{{ route('claims.store') }}">
                    @csrf
                    @if($errors->has('claim_error'))
                        <div class="claim-error-message">{{ $errors->first('claim_error') }}</div>
                    @endif
                    <div class="student-search-wrapper">
                        <label>Select Student</label>
                        <input type="text" id="studentSearchInput" name="student_display" class="student-search-input" placeholder="Search student by name, LRN, or Student ID..." autocomplete="off" value="{{ old('student_display') }}">
                        <input type="hidden" name="student_id" id="selectedStudentId" value="{{ old('student_id') }}">
                        <div id="studentSearchResults" class="student-search-results"></div>
                        @error('student_id')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label>Select Item</label>
                        <select name="claim_item_id" id="claim_item_id" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                            <option value="">Select item</option>
                            @foreach($availableItems as $i)
                                <option value="{{ $i->id }}" data-points="{{ $i->points_required }}">{{ $i->item_name }} — {{ $i->points_required }} pts ({{ $i->quantity }} left)</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                        <div class="points-box" style="background:#F3F4F6;border-radius:8px;padding:12px;text-align:center;">
                            <span style="font-size:11px;color:#9CA3AF;">Student Points</span>
                            <strong id="studentPointsBox" style="font-size:20px;font-weight:700;color:#111827;display:block;">—</strong>
                        </div>
                        <div style="background:#F3F4F6;border-radius:8px;padding:12px;text-align:center;">
                            <div style="font-size:11px;color:#9CA3AF;">Item Cost</div>
                            <div id="item_cost_display" style="font-size:20px;font-weight:700;color:#EF4444;">—</div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Remarks (optional)</label>
                        <input type="text" name="remarks" value="{{ old('remarks') }}" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                    </div>
                    <button type="submit" class="btn btn-primary" id="claimSubmitBtn" style="width:100%;">Claim Item</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Claim Items Card --}}
    <div id="claim-items-section" class="data-card">
        <div class="data-card-header with-filters">
            <div class="card-title-area">
                <h3>Claim Items</h3>
            </div>
            <form id="claimItemsFilterForm" class="table-filter-form claim-items-filter">
                <input type="text" name="item_search" value="{{ request('item_search') }}" placeholder="Search items...">

                <select name="status">
                    <option value="">All Status</option>
                    <option value="Available" {{ request('status') == 'Available' ? 'selected' : '' }}>Available</option>
                    <option value="Unavailable" {{ request('status') == 'Unavailable' ? 'selected' : '' }}>Unavailable</option>
                </select>

                <input type="number" name="min_points" value="{{ request('min_points') }}" placeholder="Min points">

                <input type="number" name="max_points" value="{{ request('max_points') }}" placeholder="Max points">

                <button type="submit" class="btn-filter">Filter</button>
                <button type="button" id="clearClaimItemsFilter" class="btn-clear">Clear</button>
            </form>
        </div>
        <div id="claimItemsTableContainer">
            @include('partials.claim-items-table', ['claimItems' => $items])
        </div>
    </div>

    {{-- Claim History Card --}}
    <div id="claim-history-section" class="data-card">
        <div class="data-card-header with-filters">
            <div class="card-title-area">
                <h3>Claim History</h3>
            </div>
            <form id="claimHistoryFilterForm" class="table-filter-form claim-history-filter">
                <input type="text" name="claim_search" value="{{ request('claim_search') }}" placeholder="Search student, item, claimed by...">

                <select name="claim_item_id">
                    <option value="">All Items</option>
                    @foreach($allClaimItems as $item)
                        <option value="{{ $item->id }}" {{ request('claim_item_id') == $item->id ? 'selected' : '' }}>
                            {{ $item->item_name }}
                        </option>
                    @endforeach
                </select>

                <div class="filter-field">
                    <label>Date From</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}">
                </div>

                <div class="filter-field">
                    <label>Date To</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}">
                </div>

                <button type="submit" class="btn-filter">Filter</button>
                <button type="button" id="clearClaimHistoryFilter" class="btn-clear">Clear</button>
            </form>
        </div>
        <div id="claimHistoryTableContainer">
            @include('partials.claim-history-table', ['claims' => $claims])
        </div>
    </div>
@endsection

<!-- Edit Item Modal -->
<div class="modal-overlay" id="editItemModal">
    <div class="modal-content" style="max-width:520px;">
        <div class="modal-header">
            <h2>Edit Claim Item</h2>
            <p>Update item information</p>
            <button type="button" class="modal-close" id="closeEditItemModal">&times;</button>
        </div>
        <form method="POST" action="" id="editItemForm" class="modal-form">
            @csrf @method('PUT')
            <div class="modal-body">
                <div class="form-group">
                    <label>Item Name</label>
                    <input type="text" name="item_name" id="editItemName" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="editItemDescription" rows="2" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;"></textarea>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                    <div class="form-group">
                        <label>Points Required</label>
                        <input type="number" name="points_required" id="editItemPoints" min="1" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                    </div>
                    <div class="form-group">
                        <label>Quantity</label>
                        <input type="number" name="quantity" id="editItemQuantity" min="0" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" id="editItemStatus" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                            <option value="Available">Available</option>
                            <option value="Unavailable">Unavailable</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancelEditItem">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Item</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // --- Student Search ---
    const studentSearchInput = document.getElementById('studentSearchInput');
    const selectedStudentId = document.getElementById('selectedStudentId');
    const studentSearchResults = document.getElementById('studentSearchResults');
    const studentPointsBox = document.getElementById('studentPointsBox');
    const itemSelect = document.getElementById('claim_item_id');
    const itemCost = document.getElementById('item_cost_display');
    const submitBtn = document.getElementById('claimSubmitBtn');

    let searchTimeout = null;

    if (studentSearchInput && selectedStudentId && studentSearchResults) {
        studentSearchInput.addEventListener('input', function () {
            const query = this.value.trim();
            selectedStudentId.value = '';
            if (studentPointsBox) studentPointsBox.textContent = '—';
            checkSufficient();
            clearTimeout(searchTimeout);
            if (query.length < 2) {
                studentSearchResults.innerHTML = '';
                studentSearchResults.style.display = 'none';
                return;
            }
            searchTimeout = setTimeout(function () {
                fetch('{{ route("admin.students.search") }}?q=' + encodeURIComponent(query))
                    .then(response => response.json())
                    .then(students => {
                        studentSearchResults.innerHTML = '';
                        if (!students.length) {
                            studentSearchResults.innerHTML = '<div class="student-search-empty">No student found.</div>';
                            studentSearchResults.style.display = 'block';
                            return;
                        }
                        students.forEach(student => {
                            const resultButton = document.createElement('button');
                            resultButton.type = 'button';
                            resultButton.className = 'student-search-result-item';
                            resultButton.innerHTML = '<strong>' + student.name + '</strong><span>LRN: ' + (student.lrn ?? 'N/A') + ' | Student ID: ' + (student.student_id ?? 'N/A') + ' | ' + (student.grade_level ?? 'No grade') + ' | ' + (student.total_points ?? 0) + ' pts</span>';
                            resultButton.addEventListener('click', function () {
                                studentSearchInput.value = student.name + ' - LRN: ' + (student.lrn ?? 'N/A') + ' - ' + (student.total_points ?? 0) + ' pts';
                                selectedStudentId.value = student.id;
                                if (studentPointsBox) studentPointsBox.textContent = student.total_points ?? 0;
                                studentSearchResults.innerHTML = '';
                                studentSearchResults.style.display = 'none';
                                const errorMessage = document.querySelector('.student-search-wrapper .field-error');
                                if (errorMessage) errorMessage.style.display = 'none';
                                checkSufficient();
                            });
                            studentSearchResults.appendChild(resultButton);
                        });
                        studentSearchResults.style.display = 'block';
                    })
                    .catch(function () {
                        studentSearchResults.innerHTML = '<div class="student-search-empty">Unable to search students.</div>';
                        studentSearchResults.style.display = 'block';
                    });
            }, 300);
        });

        document.addEventListener('click', function (event) {
            if (!event.target.closest('.student-search-wrapper')) {
                studentSearchResults.style.display = 'none';
            }
        });
    }

    if (itemSelect) {
        itemSelect.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            itemCost.textContent = opt && opt.value ? (opt.dataset.points || '0') : '—';
            checkSufficient();
        });
    }

    function checkSufficient() {
        const pts = parseInt(studentPointsBox ? studentPointsBox.textContent : '0') || 0;
        const cost = parseInt(itemCost ? itemCost.textContent : '0') || 0;
        if (submitBtn && studentPointsBox && studentPointsBox.textContent !== '—' && itemCost && itemCost.textContent !== '—') {
            submitBtn.textContent = pts >= cost ? 'Claim Item' : 'Insufficient Points';
            submitBtn.style.background = pts >= cost ? '#0ea5e9' : '#ef4444';
            submitBtn.style.opacity = '1';
        }
    }

    // --- AJAX Filtering ---
    const claimItemsForm = document.getElementById('claimItemsFilterForm');
    const claimHistoryForm = document.getElementById('claimHistoryFilterForm');
    const claimItemsContainer = document.getElementById('claimItemsTableContainer');
    const claimHistoryContainer = document.getElementById('claimHistoryTableContainer');

    function submitFilter(form, container, url) {
        const formData = new FormData(form);
        const queryString = new URLSearchParams(formData).toString();
        container.classList.add('loading');
        fetch(url + '?' + queryString, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.text())
        .then(html => {
            container.innerHTML = html;
            container.classList.remove('loading');
        })
        .catch(function () {
            container.classList.remove('loading');
        });
    }

    if (claimItemsForm && claimItemsContainer) {
        claimItemsForm.addEventListener('submit', function (event) {
            event.preventDefault();
            submitFilter(claimItemsForm, claimItemsContainer, '{{ route("claims.items.filter") }}');
        });
    }

    if (claimHistoryForm && claimHistoryContainer) {
        claimHistoryForm.addEventListener('submit', function (event) {
            event.preventDefault();
            submitFilter(claimHistoryForm, claimHistoryContainer, '{{ route("claims.history.filter") }}');
        });
    }

    // --- Edit Item Modal (event delegation for AJAX-refreshed rows) ---
    const editModal = document.getElementById('editItemModal');
    const editForm = document.getElementById('editItemForm');
    const closeEditBtn = document.getElementById('closeEditItemModal');
    const cancelEditBtn = document.getElementById('cancelEditItem');

    document.getElementById('claimItemsTableContainer').addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-edit-item');
        if (!btn) return;
        const id = btn.dataset.id;
        editForm.action = '{{ url("admin/claim-items") }}/' + id;
        document.getElementById('editItemName').value = btn.dataset.name;
        document.getElementById('editItemDescription').value = btn.dataset.description || '';
        document.getElementById('editItemPoints').value = btn.dataset.points;
        document.getElementById('editItemQuantity').value = btn.dataset.quantity;
        document.getElementById('editItemStatus').value = btn.dataset.status;
        editModal.style.display = 'flex';
    });

    function closeEditItem() {
        editModal.style.display = 'none';
    }

    if (closeEditBtn) closeEditBtn.addEventListener('click', closeEditItem);
    if (cancelEditBtn) cancelEditBtn.addEventListener('click', closeEditItem);
    if (editModal) editModal.addEventListener('click', function (e) {
        if (e.target === this) closeEditItem();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeEditItem();
    });

    const clearClaimItemsFilter = document.getElementById('clearClaimItemsFilter');
    const clearClaimHistoryFilter = document.getElementById('clearClaimHistoryFilter');

    if (clearClaimItemsFilter && claimItemsForm) {
        clearClaimItemsFilter.addEventListener('click', function () {
            claimItemsForm.querySelectorAll('input, select').forEach(function (el) {
                if (el.type === 'hidden') return;
                if (el.tagName === 'SELECT') { el.selectedIndex = 0; return; }
                el.value = '';
            });
            claimItemsForm.dispatchEvent(new Event('submit'));
        });
    }

    if (clearClaimHistoryFilter && claimHistoryForm) {
        clearClaimHistoryFilter.addEventListener('click', function () {
            claimHistoryForm.querySelectorAll('input, select').forEach(function (el) {
                if (el.type === 'hidden') return;
                if (el.tagName === 'SELECT') { el.selectedIndex = 0; return; }
                el.value = '';
            });
            claimHistoryForm.dispatchEvent(new Event('submit'));
        });
    }
});
</script>
@endpush
