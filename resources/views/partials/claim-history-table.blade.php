<div style="padding:16px;">
    @forelse($claimHistoryByStudent as $studentId => $claimsGroup)
        @php
            $firstClaim = $claimsGroup->first();
            $student = $firstClaim->student;
            $totalClaims = $claimsGroup->count();
            $totalQty = $claimsGroup->sum('quantity') ?: $totalClaims;
            $totalPoints = $claimsGroup->sum('points_deducted');
            $latestDate = $claimsGroup->max('claim_date');
            $studentName = $student->full_name ?? 'Unknown Student';
            $studentLRN = $student->lrn ?? 'N/A';
            $studentGrade = $student->grade_level ?? '';
            $collapseId = 'history-student-' . $studentId;
        @endphp
        <div class="approved-student-card" style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;margin-bottom:12px;overflow:hidden;">
            <div class="approved-student-summary" style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px;cursor:pointer;flex-wrap:wrap;gap:8px;background:#f9fafb;border-bottom:1px solid #e5e7eb;" onclick="toggleClaimHistoryStudent('{{ $collapseId }}')">
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:700;font-size:14px;color:#111827;">{{ $studentName }}</div>
                    <div style="font-size:12px;color:#6b7280;margin-top:2px;">
                        LRN: {{ $studentLRN }}
                        @if($studentGrade) &middot; {{ $studentGrade }} @endif
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
                    <div style="text-align:center;">
                        <div style="font-size:18px;font-weight:700;color:#111827;">{{ $totalClaims }}</div>
                        <div style="font-size:11px;color:#6b7280;">Claims</div>
                    </div>
                    <div style="text-align:center;">
                        <div style="font-size:18px;font-weight:700;color:#111827;">{{ $totalQty }}</div>
                        <div style="font-size:11px;color:#6b7280;">Qty</div>
                    </div>
                    <div style="text-align:center;">
                        <div style="font-size:18px;font-weight:700;color:#ef4444;">{{ number_format($totalPoints) }}</div>
                        <div style="font-size:11px;color:#6b7280;">Points</div>
                    </div>
                    <div style="text-align:center;">
                        <div style="font-size:12px;color:#6b7280;">{{ $latestDate ? \Carbon\Carbon::parse($latestDate)->format('M d, Y') : 'N/A' }}</div>
                        <div style="font-size:11px;color:#9ca3af;">Latest</div>
                    </div>
                    <button type="button" id="btn-{{ $collapseId }}" class="btn btn-outline btn-sm" style="white-space:nowrap;font-size:12px;padding:6px 12px;">
                        View History ▼
                    </button>
                </div>
            </div>
            <div id="{{ $collapseId }}" class="approved-student-details" style="display:none;padding:0;">
                <div class="table-responsive">
                    <table style="width:100%;border-collapse:collapse;font-size:13px;">
                        <thead>
                            <tr style="background:#f3f4f6;">
                                <th style="padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;color:#6b7280;font-weight:600;">Item</th>
                                <th style="padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;color:#6b7280;font-weight:600;">Qty</th>
                                <th style="padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;color:#6b7280;font-weight:600;">Points Deducted</th>
                                <th style="padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;color:#6b7280;font-weight:600;">Points Before</th>
                                <th style="padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;color:#6b7280;font-weight:600;">Points After</th>
                                <th style="padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;color:#6b7280;font-weight:600;">Claim Date</th>
                                <th style="padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;color:#6b7280;font-weight:600;">Claimed By</th>
                                <th style="padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;color:#6b7280;font-weight:600;">Status</th>
                                <th style="padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;color:#6b7280;font-weight:600;">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($claimsGroup->sortByDesc('claim_date') as $claim)
                            <tr style="border-bottom:1px solid #f3f4f6;">
                                <td style="padding:10px 14px;font-weight:600;color:#111827;">{{ $claim->item_name }}</td>
                                <td style="padding:10px 14px;">{{ $claim->quantity ?? 1 }}</td>
                                <td style="padding:10px 14px;color:#ef4444;font-weight:600;">-{{ number_format($claim->points_deducted) }}</td>
                                <td style="padding:10px 14px;">{{ number_format($claim->points_before) }}</td>
                                <td style="padding:10px 14px;color:#22c55e;font-weight:600;">{{ number_format($claim->points_after) }}</td>
                                <td style="padding:10px 14px;">{{ $claim->claim_date ? \Carbon\Carbon::parse($claim->claim_date)->format('M d, Y') : 'N/A' }}</td>
                                <td style="padding:10px 14px;">{{ $claim->admin->name ?? 'System' }}</td>
                                <td style="padding:10px 14px;">
                                    <span class="status-badge {{ strtolower($claim->status ?? 'approved') }}">
                                        {{ $claim->status ?? 'Approved' }}
                                    </span>
                                </td>
                                <td style="padding:10px 14px;color:#6b7280;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $claim->remarks ?? '—' }}</td>
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
            <p style="font-size:14px;font-weight:500;">No claim history records found.</p>
            <p style="font-size:13px;">Approved and processed claims will appear here grouped by student.</p>
        </div>
    @endforelse
</div>
