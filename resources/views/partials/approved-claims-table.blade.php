<div id="approvedByStudentContainer" style="padding:16px;">
    @forelse($approvedPaginatedByStudent ?? $approvedClaimsByStudent as $studentId => $claimsGroup)
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
        <div class="approved-student-card" style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;margin-bottom:7px;overflow:hidden;">
            <div class="approved-student-summary" style="display:flex;align-items:center;justify-content:space-between;padding:10px 16px;cursor:pointer;flex-wrap:wrap;gap:6px;background:#f9fafb;border-bottom:1px solid #e5e7eb;" onclick="toggleApprovedStudent('{{ $collapseId }}')">
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:700;font-size:14px;color:#111827;">{{ $studentName }}</div>
                    <div style="font-size:12px;color:#6b7280;margin-top:2px;">
                        LRN: {{ $studentLRN }}
                        @if($studentGrade) &middot; {{ $studentGrade }} @endif
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
                    <div style="text-align:center;">
                        <div style="font-size:16px;font-weight:700;color:#111827;">{{ $totalItems }}</div>
                        <div style="font-size:11px;color:#6b7280;">Items</div>
                    </div>
                    <div style="text-align:center;">
                        <div style="font-size:16px;font-weight:700;color:#ef4444;">{{ number_format($totalPoints) }}</div>
                        <div style="font-size:11px;color:#6b7280;">Points</div>
                    </div>
                    <div style="text-align:center;">
                        <div style="font-size:12px;color:#6b7280;">{{ $latestDate ? \Carbon\Carbon::parse($latestDate)->format('M d, Y') : 'N/A' }}</div>
                        <div style="font-size:11px;color:#9ca3af;">Latest</div>
                    </div>
                    <button type="button" id="btn-{{ $collapseId }}" class="btn btn-outline btn-sm" style="white-space:nowrap;font-size:12px;padding:6px 12px;" aria-controls="{{ $collapseId }}">
                        View Requests
                    </button>
                    <form method="POST" action="{{ route('admin.claims.archiveAllByStudent', $studentId) }}" style="display:inline;" onclick="event.stopPropagation();"
                          data-student-name="{{ $studentName }}"
                          onsubmit="event.preventDefault(); event.stopPropagation(); openClaimConfirmModal(this, 'Mark All Items as Released?', 'Are you sure you want to mark all eligible approved item requests for this student as released?', 'release_all'); return false;">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-sm" style="background:#f59e0b;color:#fff;border:none;padding:6px 12px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap;" aria-label="Mark all released">
                            Mark All Released
                        </button>
                    </form>
                </div>
            </div>
            <template id="{{ $collapseId }}" class="approved-student-details">
                <div class="table-responsive">
                    <table style="width:100%;border-collapse:collapse;font-size:13px;">
                        <thead>
                            <tr style="background:#f3f4f6;">
                                <th scope="col" style="padding:8px 10px;text-align:left;font-size:12px;text-transform:uppercase;color:#6b7280;font-weight:600;">Item</th>
                                <th scope="col" style="padding:8px 10px;text-align:left;font-size:12px;text-transform:uppercase;color:#6b7280;font-weight:600;">Qty</th>
                                <th scope="col" style="padding:8px 10px;text-align:left;font-size:12px;text-transform:uppercase;color:#6b7280;font-weight:600;">Points Deducted</th>
                                <th scope="col" style="padding:8px 10px;text-align:left;font-size:12px;text-transform:uppercase;color:#6b7280;font-weight:600;">Points Before</th>
                                <th scope="col" style="padding:8px 10px;text-align:left;font-size:12px;text-transform:uppercase;color:#6b7280;font-weight:600;">Points After</th>
                                <th scope="col" style="padding:8px 10px;text-align:left;font-size:12px;text-transform:uppercase;color:#6b7280;font-weight:600;">Claim Date</th>
                                <th scope="col" style="padding:8px 10px;text-align:left;font-size:12px;text-transform:uppercase;color:#6b7280;font-weight:600;">Approved By</th>
                                <th scope="col" style="padding:8px 10px;text-align:left;font-size:12px;text-transform:uppercase;color:#6b7280;font-weight:600;">Remarks</th>
                                <th scope="col" style="padding:8px 10px;text-align:center;font-size:12px;text-transform:uppercase;color:#6b7280;font-weight:600;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($claimsGroup as $claim)
                            <tr style="border-bottom:1px solid #f3f4f6;">
                                <td style="padding:8px 10px;font-weight:600;color:#111827;">{{ $claim->item_name }}</td>
                                <td style="padding:8px 10px;">{{ $claim->quantity ?? 1 }}</td>
                                <td style="padding:8px 10px;color:#ef4444;font-weight:600;">-{{ number_format($claim->points_deducted) }}</td>
                                <td style="padding:8px 10px;">{{ number_format($claim->points_before) }}</td>
                                <td style="padding:8px 10px;color:#22c55e;font-weight:600;">{{ number_format($claim->points_after) }}</td>
                                <td style="padding:8px 10px;">{{ $claim->claim_date ? \Carbon\Carbon::parse($claim->claim_date)->format('M d, Y') : 'N/A' }}</td>
                                <td style="padding:8px 10px;">{{ $claim->approver->name ?? $claim->admin->name ?? 'System' }}</td>
                                <td style="padding:8px 10px;color:#6b7280;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $claim->remarks ?? '—' }}</td>
                                <td style="padding:8px 10px;text-align:center;">
                                    <form method="POST" action="{{ route('admin.claims.archive', $claim->id) }}" style="display:inline;"
                                          data-student-name="{{ $studentName }}" data-item-name="{{ $claim->item_name }}" data-quantity="{{ $claim->quantity ?? 1 }}"
                                          onsubmit="event.preventDefault(); event.stopPropagation(); openClaimConfirmModal(this, 'Mark Item as Released?', 'Are you sure you want to mark this item request as released?\n\nThis action will record the item as released and move it to the released item requests history.', 'release'); return false;">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm" style="background:#f59e0b;color:#fff;border:none;padding:5px 10px;border-radius:6px;font-size:11px;font-weight:600;cursor:pointer;white-space:nowrap;" aria-label="Mark released">
                                            Mark Released
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </template>
        </div>
    @empty
        <div style="text-align:center;padding:40px 20px;color:#9ca3af;">
            <div style="font-size:32px;margin-bottom:8px;">📋</div>
            <p style="font-size:14px;font-weight:500;">No approved item requests found.</p>
            <p style="font-size:13px;">Approved claims will appear here grouped by student.</p>
        </div>
    @endforelse

    {{-- Approved by Student Pagination --}}
    @include('partials.claims-pagination', [
        'page' => $approvedPage ?? 1,
        'totalGroups' => $approvedClaimsByStudent->count(),
        'pageParam' => 'approved_page',
        'anchor' => 'approved-by-student-section',
    ])
</div>
