<div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Student</th>
                <th>LRN</th>
                <th>Item Claimed</th>
                <th>Points Deducted</th>
                <th>Points Before</th>
                <th>Points After</th>
                <th>Claim Date</th>
                <th>Claimed By</th>
                <th>Status</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            @forelse($claims as $c)
            <tr>
                <td>{{ $claims->firstItem() + $loop->index }}</td>
                <td><strong>{{ $c->student->full_name ?? '—' }}</strong></td>
                <td>{{ $c->student->lrn ?? '—' }}</td>
                <td>{{ $c->item_name }}</td>
                <td style="color:#EF4444;font-weight:600;">-{{ $c->points_deducted }}</td>
                <td>{{ $c->points_before }}</td>
                <td style="color:var(--green);font-weight:600;">{{ $c->points_after }}</td>
                <td>{{ $c->claim_date ? $c->claim_date->format('Y-m-d') : 'N/A' }}</td>
                <td>{{ $c->admin->name ?? 'System' }}</td>
                <td>
                    <span class="status-badge {{ strtolower($c->status ?? 'approved') }}">
                        {{ $c->status ?? 'Approved' }}
                    </span>
                </td>
                <td>{{ $c->remarks ?? '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="11" style="text-align:center;padding:30px;color:var(--text-light);">No claims recorded yet.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($claims->hasPages())
    <div class="pagination">
        <span class="page-info">Showing {{ $claims->firstItem() }} to {{ $claims->lastItem() }} of {{ $claims->total() }} entries</span>
        <div class="page-btns">
            @for ($i = 1; $i <= $claims->lastPage(); $i++)
                <a href="{{ $claims->url($i) }}" class="page-btn {{ $claims->currentPage() == $i ? 'active' : '' }}">{{ $i }}</a>
            @endfor
        </div>
    </div>
    @endif
</div>
