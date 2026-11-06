@extends('layouts.admin')

@section('title', 'EcoCollect - Claim Items')
@section('page-title', 'Claim Items')
@section('page-subtitle', 'Manage reward items and student claims')

@section('content')
    {{-- Pending Claims Card --}}
    <div class="data-card pending-claims-card" style="margin-bottom:24px;">
        <div class="data-card-header">
            <h3>Pending Item Claims</h3>
            <p>Review student item requests before approval.</p>
        </div>
        <div id="pendingClaimsSectionContainer">
            @include('partials.pending-claims-table', ['pendingClaims' => $pendingClaims, 'groupedByStudent' => $pendingClaims->groupBy('student_id')])
        </div>
    </div>

    <div class="grid-2col" style="margin-bottom:24px;">
        {{-- Claim Item Form --}}
        <div class="card">
            <div class="card-body">
                <h4 style="font-size:15px;font-weight:600;margin-bottom:16px;">+ Add Claim Item</h4>
                <form method="POST" action="{{ route('claim-items.store') }}">
                    @csrf
                    @if($errors->has('item_name') || $errors->has('points_required') || $errors->has('quantity') || $errors->has('status'))
                        <div class="form-error-message" role="alert">Please check the claim item fields.</div>
                    @endif
                    <div class="form-group">
                        <label>Item Name</label>
                        <input type="text" name="item_name" value="{{ old('item_name') }}" required aria-label="Item name" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="2" aria-label="Description" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">{{ old('description') }}</textarea>
                    </div>
                    <div class="grid-3col">
                        <div class="form-group">
                            <label>Points Required</label>
                            <input type="number" name="points_required" value="{{ old('points_required') }}" min="1" required aria-label="Points required" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                        </div>
                        <div class="form-group">
                            <label>Quantity</label>
                            <input type="number" name="quantity" value="{{ old('quantity', 0) }}" min="0" required aria-label="Quantity" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" required aria-label="Item status" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
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
        <div class="card" style="overflow: visible;">
            <div class="card-body">
                <h4 style="font-size:15px;font-weight:600;margin-bottom:16px;">Claim Item for Student</h4>
                <form method="POST" action="{{ route('claims.store') }}">
                    @csrf
                    @if($errors->has('claim_error'))
                        <div class="claim-error-message" role="alert">{{ $errors->first('claim_error') }}</div>
                    @endif
                    <div class="form-group">
                        <label for="claimStudentSearchInput">Select Student</label>
                        <div class="student-search-wrapper">
                            <input
                                type="text"
                                id="claimStudentSearchInput"
                                name="student_display"
                                placeholder="Search student by name, LRN, or Student ID..."
                                autocomplete="off"
                                aria-label="Search student"
                                style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;"
                            >
                            <input
                                type="hidden"
                                id="claimSelectedStudentId"
                                name="student_id"
                                required
                            >
                            <div id="claimStudentSearchResults" class="student-search-results"></div>
                        </div>
                        <div id="claimStudentError" style="color:#ef4444;font-size:12px;margin-top:4px;display:none;">Please select a student from the search results.</div>
                        @error('student_id')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label>Select Item</label>
                        <select name="claim_item_id" id="claim_item_id" required aria-label="Select item" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                            <option value="">Select item</option>
                            @foreach($availableItems as $i)
                                <option value="{{ $i->id }}" data-points="{{ $i->points_required }}">{{ $i->item_name }} — {{ $i->points_required }} pts ({{ $i->quantity }} left)</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid-2col" style="margin-bottom:12px;">
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
                        <input type="text" name="remarks" value="{{ old('remarks') }}" aria-label="Remarks" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
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
            <form id="claimItemsFilterForm" class="table-filter-form claim-items-filter" method="GET" action="{{ route('claims.index') }}#claim-items-section">
                <div class="item-search-wrapper">
                    <input
                        type="text"
                        id="claimItemSearchInput"
                        name="claim_item_search"
                        value="{{ request('claim_item_search') }}"
                        placeholder="Search item name..."
                        autocomplete="off"
                        aria-label="Search claim item"
                    >
                </div>

                <select name="claim_item_status" aria-label="Filter by status">
                    <option value="">All Status</option>
                    <option value="Available" {{ request('claim_item_status') == 'Available' ? 'selected' : '' }}>Available</option>
                    <option value="Unavailable" {{ request('claim_item_status') == 'Unavailable' ? 'selected' : '' }}>Unavailable</option>
                </select>
            </form>
        </div>
        <div id="claimItemsTableContainer">
            @include('partials.claim-items-table', ['claimItems' => $claimItems])
        </div>
    </div>

    {{-- Claim History Card --}}
    <div id="claim-history-section" class="data-card">
        <div class="data-card-header with-filters">
            <div class="card-title-area">
                <h3>Claim History</h3>
            </div>
            <form id="claimHistoryFilterForm" class="table-filter-form claim-history-filter" method="GET" action="{{ route('claims.index') }}#claim-history-section">
                <div class="history-search-wrapper">
                    <input
                        type="text"
                        id="claimHistorySearchInput"
                        name="history_search"
                        value="{{ request('history_search') }}"
                        placeholder="Search student, LRN, or item..."
                        autocomplete="off"
                        aria-label="Search claim history"
                    >
                </div>

                <select name="history_item" aria-label="Filter by item">
                    <option value="">All Items</option>
                    @foreach($allClaimItems as $item)
                        <option value="{{ $item->id }}" {{ request('history_item') == $item->id ? 'selected' : '' }}>
                            {{ $item->item_name }}
                        </option>
                    @endforeach
                </select>

                {{-- Date Range Control --}}
                <div class="date-range-control" style="display:inline-flex;align-items:center;gap:8px;">
                    <label style="font-size:12px;font-weight:600;color:#374151;white-space:nowrap;">Date Range</label>
                    <input type="date" name="history_date_from" value="{{ request('history_date_from') }}" aria-label="Start date" style="padding:6px 8px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;">
                    <span style="font-size:12px;color:#6b7280;">to</span>
                    <input type="date" name="history_date_to" value="{{ request('history_date_to') }}" aria-label="End date" style="padding:6px 8px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;">
                </div>
            </form>
        </div>
        <div id="claimHistoryTableContainer">
            @include('partials.claim-history-table', ['claims' => $claims, 'claimHistoryByStudent' => $historyPaginatedByStudent ?? $claimHistoryByStudent])
        </div>
    </div>

    {{-- Claim History Pagination --}}
    @if(isset($historyHasMorePages) && $historyHasMorePages)
    <div class="pagination" style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;margin-top:8px;border-top:1px solid #e2e8f0;">
        <span class="page-info" style="font-size:13px;color:#6b7280;">
            Student groups {{ (($historyPage - 1) * 10) + 1 }} to {{ min($historyPage * 10, $claimHistoryByStudent->count()) }} of {{ $claimHistoryByStudent->count() }}
        </span>
        <div class="page-btns" style="display:flex;gap:4px;">
            @if ($historyPage > 1)
                <a href="{{ route('claims.index', array_merge(request()->query(), ['history_page' => $historyPage - 1])) }}#claim-history-section" class="page-btn" style="padding:6px 10px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;color:#10b981;background:#d1fae5;text-decoration:none;">Prev</a>
            @endif
            @for ($i = max(1, $historyPage - 2); $i <= min($historyPage + 2, ceil(($claimHistoryByStudent->count() ?? 0) / 10)); $i++)
                <a href="{{ route('claims.index', array_merge(request()->query(), ['history_page' => $i])) }}#claim-history-section" class="page-btn {{ $i == $historyPage ? 'active' : '' }}" style="padding:6px 10px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;color:{{ $i == $historyPage ? '#10b981' : '#374151' }};background:{{ $i == $historyPage ? '#d1fae5' : '#fff' }};text-decoration:none;">{{ $i }}</a>
            @endfor
            @if ($historyHasMorePages)
                <a href="{{ route('claims.index', array_merge(request()->query(), ['history_page' => $historyPage + 1])) }}#claim-history-section" class="page-btn" style="padding:6px 10px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;color:#10b981;background:#d1fae5;text-decoration:none;">Next</a>
            @endif
        </div>
    </div>
    @endif

    {{-- All Item Requests by Student (Admin/Super Admin only) --}}
    @if(in_array(Auth::user()->role, ['admin', 'super_admin', 'Admin', 'Super Admin']))
    <div id="approved-by-student-section" class="data-card" style="margin-top:24px;">
        <div class="data-card-header with-filters">
            <div class="card-title-area">
                <h3>All Item Requests by Student</h3>
                <p style="font-size:13px;color:#6b7280;margin:4px 0 0;">Compiled record of approved item requests for quarterly releasing.</p>
            </div>
            <form id="approvedByStudentFilterForm" class="table-filter-form approved-by-student-filter" style="margin-top:12px;" method="GET" action="{{ route('claims.index') }}#approved-by-student-section" onsubmit="return false;">
                <input type="text" name="approved_search" value="{{ request('approved_search') }}" placeholder="Search student name or LRN..." aria-label="Search approved claims">

                <select name="approved_quarter" aria-label="Filter by quarter">
                    <option value="">All Quarters</option>
                    <option value="Q1" {{ request('approved_quarter') == 'Q1' ? 'selected' : '' }}>Q1 (Jan-Mar)</option>
                    <option value="Q2" {{ request('approved_quarter') == 'Q2' ? 'selected' : '' }}>Q2 (Apr-Jun)</option>
                    <option value="Q3" {{ request('approved_quarter') == 'Q3' ? 'selected' : '' }}>Q3 (Jul-Sep)</option>
                    <option value="Q4" {{ request('approved_quarter') == 'Q4' ? 'selected' : '' }}>Q4 (Oct-Dec)</option>
                </select>

                <select name="approved_year" aria-label="Filter by year">
                    <option value="">All Years</option>
                    @for($y = date('Y'); $y >= date('Y') - 3; $y--)
                        <option value="{{ $y }}" {{ request('approved_year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </form>
        </div>

        <div id="approvedByStudentContainer">
            @include('partials.approved-claims-table', ['approvedPaginatedByStudent' => $approvedPaginatedByStudent ?? $approvedClaimsByStudent, 'approvedClaimsByStudent' => $approvedClaimsByStudent, 'approvedHasMorePages' => $approvedHasMorePages ?? null, 'approvedPage' => $approvedPage ?? 1])
        </div>
    </div>
    @endif

    {{-- Archived Released Item Requests (Admin/Super Admin only) --}}
    @if(in_array(Auth::user()->role, ['admin', 'super_admin', 'Admin', 'Super Admin']) && $archivedClaimsByStudent->isNotEmpty())
    <div id="archived-by-student-section" class="data-card" style="margin-top:24px;">
        <div class="data-card-header" style="cursor:pointer;" onclick="toggleArchivedSection()" aria-expanded="false">
            <div class="card-title-area" style="display:flex;align-items:center;justify-content:space-between;width:100%;">
                <div>
                    <h3 style="display:flex;align-items:center;gap:8px;">
                        Archived Released Item Requests
                        <span id="archived-toggle-icon" style="font-size:12px;color:#6b7280;">▼</span>
                    </h3>
                    <p style="font-size:13px;color:#6b7280;margin:4px 0 0;">Previously released item requests for reference.</p>
                </div>
            </div>
        </div>
        <div id="archived-by-student-container" style="display:none;padding:16px;">
            @foreach($archivedClaimsByStudent as $studentId => $claimsGroup)
                @php
                    $firstClaim = $claimsGroup->first();
                    $student = $firstClaim->student;
                    $totalItems = $claimsGroup->sum('quantity') ?: $claimsGroup->count();
                    $totalPoints = $claimsGroup->sum('points_deducted');
                    $latestRelease = $claimsGroup->max('released_at');
                    $studentName = $student->full_name ?? 'Unknown Student';
                    $studentLRN = $student->lrn ?? 'N/A';
                    $studentGrade = $student->grade_level ?? '';
                    $collapseId = 'archived-student-' . $studentId;
                @endphp
                <div class="approved-student-card" style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;margin-bottom:12px;overflow:hidden;">
                    <div class="approved-student-summary" style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px;cursor:pointer;flex-wrap:wrap;gap:8px;background:#f9fafb;border-bottom:1px solid #e5e7eb;" onclick="toggleApprovedStudent('{{ $collapseId }}')">
                        <div style="flex:1;min-width:0;">
                            <div style="font-weight:700;font-size:14px;color:#111827;">{{ $studentName }}</div>
                            <div style="font-size:12px;color:#6b7280;margin-top:2px;">
                                LRN: {{ $studentLRN }}
                                @if($studentGrade) &middot; {{ $studentGrade }} @endif
                            </div>
                        </div>
                        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
                            <div style="text-align:center;">
                                <div style="font-size:18px;font-weight:700;color:#6b7280;">{{ $totalItems }}</div>
                                <div style="font-size:11px;color:#6b7280;">Released</div>
                            </div>
                            <div style="text-align:center;">
                                <div style="font-size:18px;font-weight:700;color:#6b7280;">{{ number_format($totalPoints) }}</div>
                                <div style="font-size:11px;color:#6b7280;">Points</div>
                            </div>
                            <div style="text-align:center;">
                                <div style="font-size:12px;color:#6b7280;">{{ $latestRelease ? \Carbon\Carbon::parse($latestRelease)->format('M d, Y') : 'N/A' }}</div>
                                <div style="font-size:11px;color:#9ca3af;">Last Released</div>
                            </div>
                            <button type="button" id="btn-{{ $collapseId }}" class="btn btn-outline btn-sm" style="white-space:nowrap;font-size:12px;padding:6px 12px;" aria-expanded="false" aria-controls="{{ $collapseId }}">
                                View Requests ▼
                            </button>
                        </div>
                    </div>
                    <div id="{{ $collapseId }}" class="approved-student-details" style="display:none;padding:0;">
                        <div class="table-responsive">
                            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                                <thead>
                                    <tr style="background:#f3f4f6;">
                                        <th scope="col" style="padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;color:#6b7280;font-weight:600;">Item</th>
                                        <th scope="col" style="padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;color:#6b7280;font-weight:600;">Qty</th>
                                        <th scope="col" style="padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;color:#6b7280;font-weight:600;">Points Deducted</th>
                                        <th scope="col" style="padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;color:#6b7280;font-weight:600;">Claim Date</th>
                                        <th scope="col" style="padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;color:#6b7280;font-weight:600;">Released Date</th>
                                        <th scope="col" style="padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;color:#6b7280;font-weight:600;">Released By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($claimsGroup as $claim)
                                    <tr style="border-bottom:1px solid #f3f4f6;">
                                        <td style="padding:10px 14px;font-weight:600;color:#111827;">{{ $claim->item_name }}</td>
                                        <td style="padding:10px 14px;">{{ $claim->quantity ?? 1 }}</td>
                                        <td style="padding:10px 14px;color:#ef4444;font-weight:600;">-{{ number_format($claim->points_deducted) }}</td>
                                        <td style="padding:10px 14px;">{{ $claim->claim_date ? \Carbon\Carbon::parse($claim->claim_date)->format('M d, Y') : 'N/A' }}</td>
                                        <td style="padding:10px 14px;color:#6b7280;">{{ $claim->released_at ? \Carbon\Carbon::parse($claim->released_at)->format('M d, Y') : 'N/A' }}</td>
                                        <td style="padding:10px 14px;color:#6b7280;">{{ $claim->releaser->name ?? 'System' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif
@endsection

<!-- Edit Item Modal -->
<div class="modal-overlay" id="editItemModal">
    <div class="modal-content" style="max-width:520px;">
        <div class="modal-header">
            <h2>Edit Claim Item</h2>
            <p>Update item information</p>
            <button type="button" class="modal-close" id="closeEditItemModal" aria-label="Close">&times;</button>
        </div>
        <form method="POST" action="" id="editItemForm" class="modal-form">
            @csrf @method('PUT')
            <div class="modal-body">
                <div class="form-group">
                    <label>Item Name</label>
                    <input type="text" name="item_name" id="editItemName" required aria-label="Item name" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="editItemDescription" rows="2" aria-label="Description" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;"></textarea>
                </div>
                <div class="grid-3col">
                    <div class="form-group">
                        <label>Points Required</label>
                        <input type="number" name="points_required" id="editItemPoints" min="1" required aria-label="Points required" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                    </div>
                    <div class="form-group">
                        <label>Quantity</label>
                        <input type="number" name="quantity" id="editItemQuantity" min="0" required aria-label="Quantity" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" id="editItemStatus" required aria-label="Item status" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
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
@php
$claimStudentsData = $studentsForClaim->map(function ($student) {
    $name = $student->full_name
        ?? trim(($student->first_name ?? '') . ' ' . ($student->middle_name ?? '') . ' ' . ($student->last_name ?? ''));
    return [
        'id' => $student->id,
        'name' => $name ?: 'Unnamed Student',
        'lrn' => $student->lrn ?? $student->student_id ?? '',
        'student_id' => $student->student_id ?? '',
        'grade_level' => $student->grade_level ?? $student->grade ?? '',
        'points' => $student->total_points ?? $student->points ?? $student->current_points ?? 0,
    ];
})->values()->toArray();
@endphp
<script>
const claimStudents = @json($claimStudentsData);
</script>
@php
$claimItemsSearchData = $claimItemsForSearch->map(function ($item) {
    $itemName = $item->item_name ?? $item->name ?? 'Unnamed Item';

    return [
        'id' => $item->id,
        'name' => $itemName,
        'description' => $item->description ?? '',
        'points_required' => $item->points_required ?? $item->points ?? 0,
        'quantity' => $item->quantity ?? 0,
        'status' => $item->status ?? '',
    ];
})->values()->toArray();
@endphp
<script>
const claimItemsSearchData = @json($claimItemsSearchData);
</script>
@php
$claimHistorySearchData = collect()
    ->merge($studentsForHistorySearch->map(function ($student) {
        $studentName = $student->full_name
            ?? trim(($student->first_name ?? '') . ' ' . ($student->middle_name ?? '') . ' ' . ($student->last_name ?? ''));

        return [
            'type' => 'student',
            'label' => $studentName ?: 'Unnamed Student',
            'value' => $studentName ?: '',
            'subtitle' => 'Student · LRN: ' . ($student->lrn ?? $student->student_id ?? 'N/A') . ' · ' . ($student->grade_level ?? $student->grade ?? ''),
            'searchable' => trim(
                ($studentName ?? '') . ' ' .
                ($student->lrn ?? '') . ' ' .
                ($student->student_id ?? '') . ' ' .
                ($student->grade_level ?? $student->grade ?? '')
            ),
        ];
    }))
    ->merge($itemsForHistorySearch->map(function ($item) {
        $itemName = $item->item_name ?? $item->name ?? 'Unnamed Item';

        return [
            'type' => 'item',
            'label' => $itemName,
            'value' => $itemName,
            'subtitle' => 'Item · ' . (($item->points_required ?? $item->points ?? 0) . ' pts') . ' · ' . ($item->status ?? ''),
            'searchable' => trim(
                ($itemName ?? '') . ' ' .
                ($item->description ?? '') . ' ' .
                ($item->status ?? '')
            ),
        ];
    }))
    ->values()
    ->toArray();
@endphp
<script>
const claimHistorySearchData = @json($claimHistorySearchData);
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var pointsBox = document.getElementById('studentPointsBox');
    var itemSelect = document.getElementById('claim_item_id');
    var itemCost = document.getElementById('item_cost_display');
    var submitBtn = document.getElementById('claimSubmitBtn');

    // --- Student Search ---
    var searchInput = document.getElementById('claimStudentSearchInput');
    var resultsBox = document.getElementById('claimStudentSearchResults');
    var hiddenInput = document.getElementById('claimSelectedStudentId');
    var studentError = document.getElementById('claimStudentError');

    function closeResults() {
        resultsBox.innerHTML = '';
        resultsBox.classList.remove('show');
    }

    function renderResults(students) {
        resultsBox.innerHTML = '';

        if (!students.length) {
            resultsBox.innerHTML = '<div class="student-search-empty">No students found.</div>';
            resultsBox.classList.add('show');
            return;
        }

        students.slice(0, 12).forEach(function (student) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'student-search-result-item';

            button.innerHTML =
                '<strong>' + student.name + '</strong>' +
                '<small>LRN: ' + (student.lrn || student.student_id || 'N/A') + (student.grade_level ? ' &middot; ' + student.grade_level : '') + '</small>';

            button.addEventListener('click', function () {
                searchInput.value = student.name;
                hiddenInput.value = student.id;
                if (pointsBox) pointsBox.textContent = student.points ?? '\u2014';
                if (studentError) studentError.style.display = 'none';
                closeResults();
                checkSufficient();
            });

            resultsBox.appendChild(button);
        });

        resultsBox.classList.add('show');
    }

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            var search = searchInput.value.trim().toLowerCase();
            hiddenInput.value = '';
            if (pointsBox) pointsBox.textContent = '\u2014';
            if (studentError) studentError.style.display = 'none';

            if (search.length < 1) {
                closeResults();
                return;
            }

            var filtered = claimStudents.filter(function (student) {
                return (
                    String(student.name || '').toLowerCase().includes(search) ||
                    String(student.lrn || '').toLowerCase().includes(search) ||
                    String(student.student_id || '').toLowerCase().includes(search) ||
                    String(student.grade_level || '').toLowerCase().includes(search)
                );
            });

            renderResults(filtered);
        });

        searchInput.addEventListener('focus', function () {
            if (searchInput.value.trim().length > 0) {
                searchInput.dispatchEvent(new Event('input'));
            }
        });
    }

    document.addEventListener('click', function (event) {
        if (!event.target.closest('.student-search-wrapper')) {
            closeResults();
        }
    });

    // --- Item Select ---
    if (itemSelect) {
        itemSelect.addEventListener('change', function () {
            var opt = this.options[this.selectedIndex];
            itemCost.textContent = opt && opt.value ? (opt.dataset.points || '0') : '\u2014';
            checkSufficient();
        });
    }

    function checkSufficient() {
        var pts = parseInt(pointsBox ? pointsBox.textContent : '0') || 0;
        var cost = parseInt(itemCost ? itemCost.textContent : '0') || 0;
        if (submitBtn && pointsBox && pointsBox.textContent !== '\u2014' && itemCost && itemCost.textContent !== '\u2014') {
            submitBtn.textContent = pts >= cost ? 'Claim Item' : 'Insufficient Points';
            submitBtn.style.background = pts >= cost ? '#0ea5e9' : '#ef4444';
            submitBtn.style.opacity = '1';
        }
    }

    // --- Form validation: require hidden student_id ---
    var claimForm = submitBtn ? submitBtn.closest('form') : null;
    if (claimForm) {
        claimForm.addEventListener('submit', function (e) {
            if (!hiddenInput.value) {
                e.preventDefault();
                if (studentError) studentError.style.display = 'block';
                searchInput.focus();
                return false;
            }
        });
    }

    // --- Claim Items Search ---
    var claimItemInput = document.getElementById('claimItemSearchInput');
    var claimItemResults = document.getElementById('claimItemSearchResults');
    var claimSelectedItemName = document.getElementById('claimSelectedItemName');

    function closeItemResults() {
        if (!claimItemResults) return;
        claimItemResults.innerHTML = '';
        claimItemResults.classList.remove('show');
    }

    function renderItemResults(items) {
        if (!claimItemResults) return;
        claimItemResults.innerHTML = '';

        if (!items.length) {
            claimItemResults.innerHTML = '<div class="item-search-empty">No items found.</div>';
            claimItemResults.classList.add('show');
            return;
        }

        items.slice(0, 10).forEach(function (item) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'item-search-result-item';

            button.innerHTML =
                '<strong>' + item.name + '</strong>' +
                '<small>' + item.points_required + ' pts &middot; Qty: ' + item.quantity + ' &middot; ' + item.status + '</small>';

            button.addEventListener('click', function () {
                claimItemInput.value = item.name;
                if (claimSelectedItemName) {
                    claimSelectedItemName.value = item.name;
                }
                closeItemResults();
            });

            claimItemResults.appendChild(button);
        });

        claimItemResults.classList.add('show');
    }

    if (claimItemInput) {
        claimItemInput.addEventListener('input', function () {
            var search = claimItemInput.value.trim().toLowerCase();

            if (claimSelectedItemName) {
                claimSelectedItemName.value = '';
            }

            if (search.length < 1) {
                closeItemResults();
                return;
            }

            var filtered = claimItemsSearchData.filter(function (item) {
                return (
                    String(item.name || '').toLowerCase().includes(search) ||
                    String(item.description || '').toLowerCase().includes(search) ||
                    String(item.status || '').toLowerCase().includes(search)
                );
            });

            renderItemResults(filtered);
        });

        claimItemInput.addEventListener('focus', function () {
            if (claimItemInput.value.trim().length > 0) {
                claimItemInput.dispatchEvent(new Event('input'));
            }
        });
    }

    document.addEventListener('click', function (event) {
        if (!event.target.closest('.item-search-wrapper')) {
            closeItemResults();
        }
    });

    // --- Claim History Search ---
    var historyInput = document.getElementById('claimHistorySearchInput');
    var historyResults = document.getElementById('claimHistorySearchResults');
    var historyHiddenInput = document.getElementById('claimHistorySelectedValue');

    function closeHistoryResults() {
        if (!historyResults) return;
        historyResults.innerHTML = '';
        historyResults.classList.remove('show');
    }

    function renderHistoryResults(results) {
        if (!historyResults) return;
        historyResults.innerHTML = '';

        if (!results.length) {
            historyResults.innerHTML = '<div class="history-search-empty">No matching student or item found.</div>';
            historyResults.classList.add('show');
            return;
        }

        results.slice(0, 12).forEach(function (result) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'history-search-result-item';

            button.innerHTML =
                '<strong>' + result.label + '</strong>' +
                '<small>' + (result.subtitle || '') + '</small>';

            button.addEventListener('click', function () {
                historyInput.value = result.value || result.label;
                if (historyHiddenInput) {
                    historyHiddenInput.value = result.value || result.label;
                }
                closeHistoryResults();
            });

            historyResults.appendChild(button);
        });

        historyResults.classList.add('show');
    }

    if (historyInput) {
        historyInput.addEventListener('input', function () {
            var search = historyInput.value.trim().toLowerCase();

            if (historyHiddenInput) {
                historyHiddenInput.value = '';
            }

            if (search.length < 1) {
                closeHistoryResults();
                return;
            }

            var filtered = claimHistorySearchData.filter(function (result) {
                return (
                    String(result.label || '').toLowerCase().includes(search) ||
                    String(result.subtitle || '').toLowerCase().includes(search) ||
                    String(result.searchable || '').toLowerCase().includes(search)
                );
            });

            renderHistoryResults(filtered);
        });

        historyInput.addEventListener('focus', function () {
            if (historyInput.value.trim().length > 0) {
                historyInput.dispatchEvent(new Event('input'));
            }
        });
    }

    document.addEventListener('click', function (event) {
        if (!event.target.closest('.history-search-wrapper')) {
            closeHistoryResults();
        }
    });

    // --- AJAX Filtering with AbortController and request sequence tracking ---
    var claimItemsForm = document.getElementById('claimItemsFilterForm');
    var claimHistoryForm = document.getElementById('claimHistoryFilterForm');
    var claimItemsContainer = document.getElementById('claimItemsTableContainer');
    var claimHistoryContainer = document.getElementById('claimHistoryTableContainer');

    // Global request tracking to prevent stale responses from overwriting newer ones
    var claimAjaxState = {
        pendingController: null,
        currentSequence: 0
    };

    function abortPendingRequest() {
        if (claimAjaxState.pendingController) {
            claimAjaxState.pendingController.abort();
            claimAjaxState.pendingController = null;
        }
    }

    function fetchPartial(url, container) {
        abortPendingRequest();
        claimAjaxState.currentSequence++;
        var seq = claimAjaxState.currentSequence;
        var controller = new AbortController();
        claimAjaxState.pendingController = controller;

        if (container) container.classList.add('loading');

        fetch(url, {
            headers: {
                'Accept': 'text/html',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            signal: controller.signal
        })
        .then(function (response) {
            if (!response.ok) throw new Error('Filter failed');
            return response.text();
        })
        .then(function (html) {
            if (seq !== claimAjaxState.currentSequence) return; // stale response
            if (container) container.innerHTML = html;
            if (container) container.classList.remove('loading');
        })
        .catch(function (err) {
            if (seq !== claimAjaxState.currentSequence) return; // stale response
            if (container) container.classList.remove('loading');
            if (err.name !== 'AbortError') console.error('Fetch error:', err);
        })
        .finally(function () {
            if (seq === claimAjaxState.currentSequence) {
                claimAjaxState.pendingController = null;
            }
        });
    }

    // --- Claim Items Live Filter (AJAX, debounced) ---
    var claimItemsFilterForm = document.getElementById('claimItemsFilterForm');
    var claimItemSearchInput = document.getElementById('claimItemSearchInput');
    var claimItemsContainer = document.getElementById('claimItemsTableContainer');
    var claimItemsTimer = null;

    function submitClaimItemsFilter() {
        if (!claimItemsFilterForm || !claimItemsContainer) return;
        var formData = new FormData(claimItemsFilterForm);
        var queryString = new URLSearchParams(formData).toString();
        var url = '{{ route('claims.items.filter') }}' + '?' + queryString;
        fetchPartial(url, claimItemsContainer);
    }

    if (claimItemSearchInput) {
        claimItemSearchInput.addEventListener('input', function () {
            clearTimeout(claimItemsTimer);
            claimItemsTimer = setTimeout(submitClaimItemsFilter, 300);
        });
    }

    // Status select - auto filter on change
    var claimItemStatusSelect = claimItemsFilterForm ? claimItemsFilterForm.querySelector('select[name="claim_item_status"]') : null;
    if (claimItemStatusSelect) {
        claimItemStatusSelect.addEventListener('change', function () {
            clearTimeout(claimItemsTimer);
            claimItemsTimer = setTimeout(submitClaimItemsFilter, 300);
        });
    }

    // --- Pending Item Claims Live Filter (AJAX, debounced) ---
    var pendingClaimsFilterForm = document.getElementById('pendingClaimsFilterForm');
    var pendingResultsContainer = document.getElementById('pendingClaimsResults');
    var pendingTimer = null;

    function submitPendingFilter() {
        if (!pendingClaimsFilterForm || !pendingResultsContainer) return;
        var formData = new FormData(pendingClaimsFilterForm);
        var queryString = new URLSearchParams(formData).toString();
        var url = '{{ route('claims.pending.filter') }}' + '?' + queryString;
        fetchPartial(url, pendingResultsContainer);
    }

    if (pendingClaimsFilterForm) {
        pendingClaimsFilterForm.querySelectorAll('input[name="pending_student_search"], input[name="pending_item_search"]').forEach(function (input) {
            input.addEventListener('input', function () {
                clearTimeout(pendingTimer);
                pendingTimer = setTimeout(submitPendingFilter, 300);
            });
        });
    }

    // --- Claim History Live Filter (AJAX, debounced) ---
    var claimHistoryFilterForm = document.getElementById('claimHistoryFilterForm');
    var claimHistorySearchInput = document.getElementById('claimHistorySearchInput');
    var claimHistoryTimer = null;

    function submitClaimHistoryFilter() {
        if (!claimHistoryFilterForm || !claimHistoryContainer) return;
        var formData = new FormData(claimHistoryFilterForm);
        var queryString = new URLSearchParams(formData).toString();
        var url = '{{ route('claims.history.filter') }}' + '?' + queryString;
        fetchPartial(url, claimHistoryContainer);
    }

    if (claimHistoryFilterForm && claimHistorySearchInput) {
        claimHistorySearchInput.addEventListener('input', function () {
            clearTimeout(claimHistoryTimer);
            claimHistoryTimer = setTimeout(submitClaimHistoryFilter, 300);
        });

        // Item select - auto filter on change
        var claimHistoryItemSelect = claimHistoryFilterForm.querySelector('select[name="history_item"]');
        if (claimHistoryItemSelect) {
            claimHistoryItemSelect.addEventListener('change', function () {
                clearTimeout(claimHistoryTimer);
                claimHistoryTimer = setTimeout(submitClaimHistoryFilter, 300);
            });
        }

        // Date inputs - submit only on Enter keypress (not while typing)
        var claimHistoryDateFrom = claimHistoryFilterForm.querySelector('input[name="history_date_from"]');
        var claimHistoryDateTo = claimHistoryFilterForm.querySelector('input[name="history_date_to"]');
        [claimHistoryDateFrom, claimHistoryDateTo].forEach(function (dateInput) {
            if (dateInput) {
                dateInput.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        clearTimeout(claimHistoryTimer);
                        claimHistoryTimer = setTimeout(submitClaimHistoryFilter, 300);
                    }
                });
            }
        });
    }

    // --- Edit Item Modal (event delegation for AJAX-refreshed rows) ---
    var editModal = document.getElementById('editItemModal');
    var editForm = document.getElementById('editItemForm');
    var closeEditBtn = document.getElementById('closeEditItemModal');
    var cancelEditBtn = document.getElementById('cancelEditItem');

    document.getElementById('claimItemsTableContainer').addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-edit-item');
        if (!btn) return;
        var id = btn.dataset.id;
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

    var clearClaimItemsFilter = document.getElementById('clearClaimItemsFilter');
    var clearClaimHistoryFilter = document.getElementById('clearClaimHistoryFilter');

    if (clearClaimItemsFilter && claimItemsFilterForm) {
        clearClaimItemsFilter.addEventListener('click', function () {
            claimItemsFilterForm.querySelectorAll('input, select').forEach(function (el) {
                if (el.type === 'hidden') return;
                if (el.tagName === 'SELECT') { el.selectedIndex = 0; return; }
                el.value = '';
            });
            if (claimItemsTimer) clearTimeout(claimItemsTimer);
            claimItemsTimer = setTimeout(submitClaimItemsFilter, 300);
        });
    }

    if (clearClaimHistoryFilter && claimHistoryFilterForm) {
        clearClaimHistoryFilter.addEventListener('click', function () {
            claimHistoryFilterForm.querySelectorAll('input, select').forEach(function (el) {
                if (el.type === 'hidden') return;
                if (el.tagName === 'SELECT') { el.selectedIndex = 0; return; }
                el.value = '';
            });
            if (claimHistoryTimer) clearTimeout(claimHistoryTimer);
            claimHistoryTimer = setTimeout(submitClaimHistoryFilter, 300);
        });
    }

    // --- Approved by Student Filters (AJAX) ---
    var approvedByStudentForm = document.getElementById('approvedByStudentFilterForm');
    var approvedByStudentContainer = document.getElementById('approvedByStudentContainer');
    var approvedByStudentTimer = null;

    function submitApprovedFilter() {
        if (!approvedByStudentForm || !approvedByStudentContainer) return;
        var formData = new FormData(approvedByStudentForm);
        var queryString = new URLSearchParams(formData).toString();
        var url = '{{ route('claims.approved.filter') }}' + '?' + queryString;
        fetchPartial(url, approvedByStudentContainer);
    }

    if (approvedByStudentForm && approvedByStudentContainer) {
        var approvedSearchInput = approvedByStudentForm.querySelector('input[name="approved_search"]');
        var approvedQuarterSelect = approvedByStudentForm.querySelector('select[name="approved_quarter"]');
        var approvedYearSelect = approvedByStudentForm.querySelector('select[name="approved_year"]');

        if (approvedSearchInput) {
            approvedSearchInput.addEventListener('input', function () {
                clearTimeout(approvedByStudentTimer);
                approvedByStudentTimer = setTimeout(submitApprovedFilter, 300);
            });
        }
        if (approvedQuarterSelect) {
            approvedQuarterSelect.addEventListener('change', submitApprovedFilter);
        }
        if (approvedYearSelect) {
            approvedYearSelect.addEventListener('change', submitApprovedFilter);
        }

        // Prevent form submission on Enter key (full-page refresh)
        approvedByStudentForm.addEventListener('submit', function (e) {
            e.preventDefault();
        });
    }

    var clearApprovedByStudentFilter = document.getElementById('clearApprovedByStudentFilter');
    if (clearApprovedByStudentFilter && approvedByStudentForm) {
        clearApprovedByStudentFilter.addEventListener('click', function () {
            approvedByStudentForm.querySelectorAll('input, select').forEach(function (el) {
                if (el.type === 'hidden') return;
                if (el.tagName === 'SELECT') { el.selectedIndex = 0; return; }
                el.value = '';
            });
            clearTimeout(approvedByStudentTimer);
            approvedByStudentTimer = setTimeout(submitApprovedFilter, 300);
        });
    }

    if (window.location.hash === '#approved-by-student-section') {
        setTimeout(function () {
            var section = document.getElementById('approved-by-student-section');
            if (section) section.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 300);
    }
});
</script>
<script>
function togglePendingStudent(id) {
    var el = document.getElementById(id);
    var btn = document.getElementById('btn-' + id);
    if (!el) return;
    if (el.style.display === 'none' || el.style.display === '') {
        el.style.display = 'block';
        if (btn) btn.textContent = 'Hide Requests ▲';
    } else {
        el.style.display = 'none';
        if (btn) btn.textContent = 'View Requests ▼';
    }
}

function toggleApprovedStudent(id) {
    var el = document.getElementById(id);
    var btn = document.getElementById('btn-' + id);
    if (!el) return;
    if (el.style.display === 'none' || el.style.display === '') {
        el.style.display = 'block';
        if (btn) btn.textContent = 'Hide Requests ▲';
    } else {
        el.style.display = 'none';
        if (btn) btn.textContent = 'View Requests ▼';
    }
}

function toggleArchivedSection() {
    var container = document.getElementById('archived-by-student-container');
    var icon = document.getElementById('archived-toggle-icon');
    if (!container) return;
    if (container.style.display === 'none' || container.style.display === '') {
        container.style.display = 'block';
        if (icon) icon.textContent = '▲';
    } else {
        container.style.display = 'none';
        if (icon) icon.textContent = '▼';
    }
}

function toggleClaimHistoryStudent(id) {
    var el = document.getElementById(id);
    var btn = document.getElementById('btn-' + id);
    if (!el) return;
    if (el.style.display === 'none' || el.style.display === '') {
        el.style.display = 'block';
        if (btn) btn.textContent = 'Hide History ▲';
    } else {
        el.style.display = 'none';
        if (btn) btn.textContent = 'View History ▼';
    }
}

</script>
<style>
/* Compact action buttons for pending item claims */
.action-button-group {
    display: flex;
    gap: 8px;
    align-items: center;
    justify-content: center;
}

.btn-approve-icon {
    background-color: #10b981;
    border: none;
    border-radius: 6px;
    width: 32px;
    height: 32px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.btn-approve-icon:hover {
    background-color: #059669;
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(0,0,0,0.15);
}

.btn-reject-icon {
    background-color: #ef4444;
    border: none;
    border-radius: 6px;
    width: 32px;
    height: 32px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.btn-reject-icon:hover {
    background-color: #dc2626;
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(0,0,0,0.15);
}

.action-buttons .btn-approve-icon,
.action-buttons .btn-reject-icon {
    color: white;
    font-weight: 600;
}

.action-buttons .btn-approve-icon svg,
.action-buttons .btn-reject-icon svg {
    width: 16px;
    height: 16px;
    display: block;
}
</style>
@endpush
