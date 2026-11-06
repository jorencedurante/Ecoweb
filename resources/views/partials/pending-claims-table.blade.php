@php
    // --- Initialize Pending Claims search parameters ---
    // Works for both normal page loads and AJAX requests.
    // Does not assume the controller always passes these variables.
    $pendingStudentSearch = isset($pendingStudentSearch)
        ? trim((string) $pendingStudentSearch)
        : trim((string) request('pending_student_search', ''));
    $pendingItemSearch = isset($pendingItemSearch)
        ? trim((string) $pendingItemSearch)
        : trim((string) request('pending_item_search', ''));

    $hasActiveSearch = $pendingStudentSearch !== '' || $pendingItemSearch !== '';

    // --- Initialize $pendingPaginatedGroups (PHP array) ---
    // On normal page loads, $groupedByStudent (Collection) or $pendingClaims (Paginator) is passed.
    // On AJAX requests, $pendingPaginatedGroups (PHP array) is passed by filterPending().
    if (!isset($pendingPaginatedGroups)) {
        if (isset($groupedByStudent) && $groupedByStudent instanceof \Illuminate\Support\Collection) {
            $pendingGroups = $groupedByStudent;
        } elseif (isset($pendingClaims)) {
            $pendingGroups = $pendingClaims->groupBy('student_id');
        } else {
            $pendingGroups = collect([]);
        }

        $pendingStudentIds = $pendingGroups->keys()->toArray();
        $pendingPage = isset($pendingPage) ? (int) $pendingPage : (int) request('pending_page', 1);
        $pendingPerPage = 10;
        $pendingOffset = max(0, ($pendingPage - 1) * $pendingPerPage);
        $pendingPageStudentIds = array_slice($pendingStudentIds, $pendingOffset, $pendingPerPage);
        $pendingPaginatedGroups = [];
        foreach ($pendingPageStudentIds as $sid) {
            if (isset($pendingGroups[$sid])) {
                $pendingPaginatedGroups[$sid] = $pendingGroups[$sid];
            }
        }
        $pendingHasMorePages = count($pendingStudentIds) > $pendingPage * $pendingPerPage;
    } elseif (is_array($pendingPaginatedGroups)) {
        // AJAX request: pendingPaginatedGroups is already a PHP array
        $pendingStudentIds = isset($pendingStudentIds) && is_array($pendingStudentIds)
            ? $pendingStudentIds
            : array_keys($pendingPaginatedGroups);
        $pendingPage = isset($pendingPage) ? (int) $pendingPage : (int) request('pending_page', 1);
        $pendingHasMorePages = isset($pendingHasMorePages) ? (bool) $pendingHasMorePages : false;
    }

    $hasAnyMatches = isset($hasAnyMatches) ? (bool) $hasAnyMatches : !empty($pendingPaginatedGroups);
@endphp

@if(!request()->ajax())
<div class="table-container" id="pendingClaimsTableContainer">
    {{-- Live Filters for Pending Claims (NOT replaced by AJAX) --}}
    <form id="pendingClaimsFilterForm" class="table-filter-form pending-claims-filter" method="GET" action="{{ route('claims.index') }}#pending-claims-section" onsubmit="return false;">
        <div class="filter-field">
            <input
                type="text"
                id="pendingStudentSearch"
                name="pending_student_search"
                value="{{ e($pendingStudentSearch) }}"
                placeholder="Student name or LRN..."
                autocomplete="off"
                aria-label="Search pending claims by student"
            >
        </div>
        <div class="filter-field">
            <input
                type="text"
                id="pendingItemSearch"
                name="pending_item_search"
                value="{{ e($pendingItemSearch) }}"
                placeholder="Requested item name..."
                autocomplete="off"
                aria-label="Search pending claims by item"
            >
        </div>
    </form>
    <div id="pendingClaimsResults" style="padding:16px;">
@endif
    {{-- Pending Claims Results (replaced by AJAX) --}}
    @if(empty($pendingPaginatedGroups) && $hasActiveSearch)
        <div style="text-align:center;padding:40px 20px;color:#9ca3af;">
            <div style="font-size:32px;margin-bottom:8px;">🔍</div>
            @if(!empty($pendingStudentSearch) && !empty($pendingItemSearch))
                <p style="font-size:14px;font-weight:500;">No matching pending requests found.</p>
            @elseif(!empty($pendingStudentSearch))
                <p style="font-size:14px;font-weight:500;">No students found.</p>
            @else
                <p style="font-size:14px;font-weight:500;">No matching pending requests found.</p>
            @endif
            <p style="font-size:13px;">Clear your search or adjust your filters.</p>
        </div>
    @elseif(empty($pendingPaginatedGroups) && !$hasActiveSearch)
        <div style="text-align:center;padding:40px 20px;color:#9ca3af;">
            <div style="font-size:32px;margin-bottom:8px;">📋</div>
            <p style="font-size:14px;font-weight:500;">No pending claims.</p>
            <p style="font-size:13px;">Pending item requests will appear here grouped by student.</p>
        </div>
    @else
        @foreach($pendingPaginatedGroups as $studentId => $claimsGroup)
            @php
                $firstClaim = $claimsGroup->first();
                $student = $firstClaim->student;
                $studentName = $student->full_name ?? 'Unknown Student';
                $studentLRN = $student->lrn ?? 'N/A';
                $studentPoints = $student->total_points ?? 0;
                $pendingCount = $claimsGroup->count();
                $collapseId = 'pending-student-' . $studentId;
            @endphp
            <div class="approved-student-card" style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;margin-bottom:12px;overflow:hidden;">
                <div class="approved-student-summary" style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px;cursor:pointer;flex-wrap:wrap;gap:8px;background:#f9fafb;border-bottom:1px solid #e5e7eb;" onclick="togglePendingStudent('{{ $collapseId }}')">
                    <div style="flex:1;min-width:0;">
                        <div style="font-weight:700;font-size:14px;color:#111827;">{{ $studentName }}</div>
                        <div style="font-size:12px;color:#6b7280;margin-top:2px;">
                            LRN: {{ $studentLRN }} &middot; Available Points: {{ number_format($studentPoints) }}
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
                        <div style="text-align:center;">
                            <div style="font-size:18px;font-weight:700;color:#111827;">{{ $pendingCount }}</div>
                            <div style="font-size:11px;color:#6b7280;">Pending</div>
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
                                    <th scope="col" style="padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;color:#6b7280;font-weight:600;">Points Required</th>
                                    <th scope="col" style="padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;color:#6b7280;font-weight:600;">Date Requested</th>
                                    <th scope="col" style="padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;color:#6b7280;font-weight:600;">Status</th>
                                    <th scope="col" style="padding:10px 14px;text-align:center;font-size:11px;text-transform:uppercase;color:#6b7280;font-weight:600;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($claimsGroup->sortByDesc('created_at') as $claim)
                                <tr style="border-bottom:1px solid #f3f4f6;">
                                    <td style="padding:10px 14px;font-weight:600;color:#111827;">{{ $claim->item_name }}</td>
                                    <td style="padding:10px 14px;">{{ $claim->quantity ?? 1 }}</td>
                                    <td style="padding:10px 14px;color:#ef4444;font-weight:600;">{{ number_format($claim->points_deducted) }}</td>
                                    <td style="padding:10px 14px;">{{ $claim->created_at->format('M d, Y h:i A') }}</td>
                                    <td style="padding:10px 14px;">
                                        <span class="status-badge pending">Pending</span>
                                    </td>
                                    <td style="padding:10px 14px;text-align:center;">
                                        <div class="action-button-group">
                                            <form method="POST" action="{{ route('claims.approve', $claim->id) }}" style="display:inline;" onsubmit="return confirm('Approve this item claim?');">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="btn-approve-icon" aria-label="Approve Request" title="Approve Request"
                                                    @if(($claim->student->total_points ?? 0) < $claim->points_deducted) disabled title="Insufficient points" @endif>
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="vertical-align: middle;">
                                                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                                                    </svg>
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('claims.reject', $claim->id) }}" style="display:inline;" onsubmit="return confirm('Reject this claim?');">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="btn-reject-icon" aria-label="Reject Request" title="Reject Request">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="vertical-align: middle;">
                                                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach

        {{-- Pending Claims Pagination --}}
        <div class="pagination" style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;margin-top:8px;border-top:1px solid #e2e8f0;">
            <span class="page-info" style="font-size:13px;color:#6b7280;">
                Student groups {{ (($pendingPage - 1) * 10) + 1 }} to {{ min($pendingPage * 10, count($pendingStudentIds)) }} of {{ count($pendingStudentIds) }}
            </span>
            <div class="page-btns" style="display:flex;gap:4px;">
                @if ($pendingPage > 1)
                    <a href="{{ route('claims.index', array_merge(request()->query(), ['pending_page' => $pendingPage - 1])) }}#pending-claims-section" class="page-btn" style="padding:6px 10px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;color:#10b981;background:#d1fae5;text-decoration:none;">Prev</a>
                @endif
                @for ($i = max(1, $pendingPage - 2); $i <= min($pendingPage + 2, ceil(count($pendingStudentIds) / 10)); $i++)
                    <a href="{{ route('claims.index', array_merge(request()->query(), ['pending_page' => $i])) }}#pending-claims-section" class="page-btn {{ $i == $pendingPage ? 'active' : '' }}" style="padding:6px 10px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;color:{{ $i == $pendingPage ? '#10b981' : '#374151' }};background:{{ $i == $pendingPage ? '#d1fae5' : '#fff' }};text-decoration:none;">{{ $i }}</a>
                @endfor
                @if ($pendingHasMorePages)
                    <a href="{{ route('claims.index', array_merge(request()->query(), ['pending_page' => $pendingPage + 1])) }}#pending-claims-section" class="page-btn" style="padding:6px 10px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;color:#10b981;background:#d1fae5;text-decoration:none;">Next</a>
                @endif
            </div>
        </div>
    @endif
@if(!request()->ajax())
    </div>
</div>
@endif

<style>
/* Compact action buttons for pending item claims */
.action-button-group {
    display: flex;
    gap: 6px;
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

.btn-approve-icon:hover:not(:disabled) {
    background-color: #059669;
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(0,0,0,0.15);
}

.btn-approve-icon:disabled {
    background-color: #9ca3af;
    cursor: not-allowed;
    opacity: 0.6;
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

.btn-reject-icon:hover:not(:disabled) {
    background-color: #dc2626;
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(0,0,0,0.15);
}

.action-buttons .btn-approve-icon,
.action-buttons .btn-reject-icon,
.action-button-group .btn-approve-icon,
.action-button-group .btn-reject-icon {
    color: white;
    font-weight: 600;
}

.action-buttons .btn-approve-icon svg,
.action-buttons .btn-reject-icon svg,
.action-button-group .btn-approve-icon svg,
.action-button-group .btn-reject-icon svg {
    width: 16px;
    height: 16px;
    display: block;
}

/* Compact filter layout for Pending Item Claims */
.pending-claims-filter {
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
    padding: 16px 16px 0 16px;
    margin-bottom: 16px;
    border: none;
    background: transparent;
}

.pending-claims-filter .filter-field {
    min-width: 0;
}

@media (max-width: 768px) {
    .pending-claims-filter {
        grid-template-columns: 1fr;
        gap: 12px;
        padding: 16px 16px 0 16px;
        margin-bottom: 12px;
    }
}

.loading {
    opacity: 0.6;
    pointer-events: none;
    position: relative;
}
</style>
