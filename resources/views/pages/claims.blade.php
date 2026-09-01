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

        <div class="table-wrapper">
            <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Student</th>
                        <th scope="col">LRN</th>
                        <th scope="col">Item Requested</th>
                        <th scope="col">Points Required</th>
                        <th scope="col">Student Points</th>
                        <th scope="col">Request Date</th>
                        <th scope="col">Status</th>
                        <th scope="col">Actions</th>
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
                                    <button type="submit" class="btn-approve" aria-label="Approve claim">Approve</button>
                                </form>

                                <form method="POST" action="{{ route('claims.reject', $claim->id) }}" onsubmit="return confirm('Reject this item claim?');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn-reject" aria-label="Reject claim">Reject</button>
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
            </div>
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
        <div class="card">
            <div class="card-body">
                <h4 style="font-size:15px;font-weight:600;margin-bottom:16px;">Claim Item for Student</h4>
                <form method="POST" action="{{ route('claims.store') }}">
                    @csrf
                    @if($errors->has('claim_error'))
                        <div class="claim-error-message" role="alert">{{ $errors->first('claim_error') }}</div>
                    @endif
                    <div class="student-search-wrapper">
                        <label>Select Student</label>
                        <input type="text" id="studentSearchInput" name="student_display" class="student-search-input" placeholder="Search student by name, LRN, or Student ID..." autocomplete="off" value="{{ old('student_display') }}" aria-label="Select student">
                        <input type="hidden" name="student_id" id="selectedStudentId" value="{{ old('student_id') }}" aria-label="Select student">
                        <div id="studentSearchResults" class="student-search-results"></div>
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
            <form id="claimItemsFilterForm" class="table-filter-form claim-items-filter">
                <input type="text" name="item_search" value="{{ request('item_search') }}" placeholder="Search items..." aria-label="Search items">

                <select name="status" aria-label="Filter by status">
                    <option value="">All Status</option>
                    <option value="Available" {{ request('status') == 'Available' ? 'selected' : '' }}>Available</option>
                    <option value="Unavailable" {{ request('status') == 'Unavailable' ? 'selected' : '' }}>Unavailable</option>
                </select>

                <input type="number" name="min_points" value="{{ request('min_points') }}" placeholder="Min points" aria-label="Minimum points">

                <input type="number" name="max_points" value="{{ request('max_points') }}" placeholder="Max points" aria-label="Maximum points">

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
                <input type="text" name="claim_search" value="{{ request('claim_search') }}" placeholder="Search student, item, claimed by..." aria-label="Search claims">

                <select name="claim_item_id" aria-label="Filter by item">
                    <option value="">All Items</option>
                    @foreach($allClaimItems as $item)
                        <option value="{{ $item->id }}" {{ request('claim_item_id') == $item->id ? 'selected' : '' }}>
                            {{ $item->item_name }}
                        </option>
                    @endforeach
                </select>

                <div class="filter-field">
                    <label>Date From</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" aria-label="Date from">
                </div>

                <div class="filter-field">
                    <label>Date To</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" aria-label="Date to">
                </div>

                <button type="submit" class="btn-filter">Filter</button>
                <button type="button" id="clearClaimHistoryFilter" class="btn-clear">Clear</button>
            </form>
        </div>
        <div id="claimHistoryTableContainer">
            @include('partials.claim-history-table', ['claims' => $claims])
        </div>
    </div>

    {{-- All Item Requests by Student (Admin/Super Admin only) --}}
    @if(in_array(Auth::user()->role, ['admin', 'super_admin', 'Admin', 'Super Admin']))
    <div id="approved-by-student-section" class="data-card" style="margin-top:24px;">
        <div class="data-card-header with-filters">
            <div class="card-title-area">
                <h3>All Item Requests by Student</h3>
                <p style="font-size:13px;color:#6b7280;margin:4px 0 0;">Compiled record of approved item requests for quarterly releasing.</p>
            </div>
            <form id="approvedByStudentFilterForm" class="table-filter-form approved-by-student-filter" style="margin-top:12px;">
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

                <button type="submit" class="btn-filter">Filter</button>
                <button type="button" id="clearApprovedByStudentFilter" class="btn-clear">Clear</button>
            </form>
        </div>

        <div id="approvedByStudentContainer" style="padding:16px;">
            @forelse($approvedClaimsByStudent as $studentId => $claimsGroup)
                @php
                    $firstClaim = $claimsGroup->first();
                    $student = $firstClaim->student;
                    $totalItems = $claimsGroup->sum('quantity') ?: $claimsGroup->count();
                    $totalPoints = $claimsGroup->sum('points_deducted');
                    $latestDate = $claimsGroup->max('claim_date');
                    $studentName = $student->full_name ?? 'Unknown Student';
                    $studentLRN = $student->lrn ?? 'N/A';
                    $studentGrade = $student->grade_level ?? '';
                    $collapseId = 'approved-student-' . $studentId;
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
                                <div style="font-size:18px;font-weight:700;color:#111827;">{{ $totalItems }}</div>
                                <div style="font-size:11px;color:#6b7280;">Items</div>
                            </div>
                            <div style="text-align:center;">
                                <div style="font-size:18px;font-weight:700;color:#ef4444;">{{ number_format($totalPoints) }}</div>
                                <div style="font-size:11px;color:#6b7280;">Points</div>
                            </div>
                            <div style="text-align:center;">
                                <div style="font-size:12px;color:#6b7280;">{{ $latestDate ? \Carbon\Carbon::parse($latestDate)->format('M d, Y') : 'N/A' }}</div>
                                <div style="font-size:11px;color:#9ca3af;">Latest</div>
                            </div>
                            <button type="button" id="btn-{{ $collapseId }}" class="btn btn-outline btn-sm" style="white-space:nowrap;font-size:12px;padding:6px 12px;" aria-expanded="false" aria-controls="{{ $collapseId }}">
                                View Requests ▼
                            </button>
                            <form method="POST" action="{{ route('claims.archiveAllByStudent', $studentId) }}" style="display:inline;" onclick="event.stopPropagation();">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm" style="background:#f59e0b;color:#fff;border:none;padding:6px 12px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap;" aria-label="Archive" onclick="return confirm('Mark all approved item requests of {{ addslashes($studentName) }} as released?')">
                                    Mark All Released
                                </button>
                            </form>
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
                                        <th scope="col" style="padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;color:#6b7280;font-weight:600;">Points Before</th>
                                        <th scope="col" style="padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;color:#6b7280;font-weight:600;">Points After</th>
                                        <th scope="col" style="padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;color:#6b7280;font-weight:600;">Claim Date</th>
                                        <th scope="col" style="padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;color:#6b7280;font-weight:600;">Approved By</th>
                                        <th scope="col" style="padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;color:#6b7280;font-weight:600;">Remarks</th>
                                        <th scope="col" style="padding:10px 14px;text-align:center;font-size:11px;text-transform:uppercase;color:#6b7280;font-weight:600;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($claimsGroup as $claim)
                                    <tr style="border-bottom:1px solid #f3f4f6;">
                                        <td style="padding:10px 14px;font-weight:600;color:#111827;">{{ $claim->item_name }}</td>
                                        <td style="padding:10px 14px;">{{ $claim->quantity ?? 1 }}</td>
                                        <td style="padding:10px 14px;color:#ef4444;font-weight:600;">-{{ number_format($claim->points_deducted) }}</td>
                                        <td style="padding:10px 14px;">{{ number_format($claim->points_before) }}</td>
                                        <td style="padding:10px 14px;color:#22c55e;font-weight:600;">{{ number_format($claim->points_after) }}</td>
                                        <td style="padding:10px 14px;">{{ $claim->claim_date ? \Carbon\Carbon::parse($claim->claim_date)->format('M d, Y') : 'N/A' }}</td>
                                        <td style="padding:10px 14px;">{{ $claim->approver->name ?? $claim->admin->name ?? 'System' }}</td>
                                        <td style="padding:10px 14px;color:#6b7280;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $claim->remarks ?? '—' }}</td>
                                        <td style="padding:10px 14px;text-align:center;">
                                            <form method="POST" action="{{ route('claims.archive', $claim->id) }}" style="display:inline;">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm" style="background:#f59e0b;color:#fff;border:none;padding:5px 10px;border-radius:6px;font-size:11px;font-weight:600;cursor:pointer;white-space:nowrap;" aria-label="Archive" onclick="return confirm('Mark this item request as released and archive it?')">
                                                    Mark Released
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @empty
                <div style="text-align:center;padding:40px 20px;color:#9ca3af;">
                    <div style="font-size:32px;margin-bottom:8px;">📋</div>
                    <p style="font-size:14px;font-weight:500;">No approved item requests found.</p>
                    <p style="font-size:13px;">Approved claims will appear here grouped by student.</p>
                </div>
            @endforelse
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

    // --- Approved by Student Filters ---
    const approvedByStudentForm = document.getElementById('approvedByStudentFilterForm');
    const approvedByStudentContainer = document.getElementById('approvedByStudentContainer');

    if (approvedByStudentForm && approvedByStudentContainer) {
        approvedByStudentForm.addEventListener('submit', function (event) {
            event.preventDefault();
            const formData = new FormData(approvedByStudentForm);
            const queryString = new URLSearchParams(formData).toString();
            window.location.href = '{{ route("claims.index") }}?' + queryString + '#approved-by-student-section';
        });
    }

    const clearApprovedByStudentFilter = document.getElementById('clearApprovedByStudentFilter');
    if (clearApprovedByStudentFilter && approvedByStudentForm) {
        clearApprovedByStudentFilter.addEventListener('click', function () {
            window.location.href = '{{ route("claims.index") }}#approved-by-student-section';
        });
    }

    // Scroll to section if hash present
    if (window.location.hash === '#approved-by-student-section') {
        setTimeout(function () {
            var section = document.getElementById('approved-by-student-section');
            if (section) section.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 300);
    }
});
</script>
<script>
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
@endpush
