<div class="table-wrapper">
    <div class="table-responsive">
    <table>
        <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">Item Name</th>
                <th scope="col">Description</th>
                <th scope="col">Points Required</th>
                <th scope="col">Quantity</th>
                <th scope="col">Status</th>
                <th scope="col">Created By</th>
                <th scope="col">Actions</th>
            </tr>
        </thead>
        <tbody>
            @if ($claimItems->count())
                @foreach ($claimItems as $index => $item)
                <tr>
                    <td>{{ (($claimItems->currentPage() - 1) * $claimItems->perPage()) + $index + 1 }}</td>
                    <td><strong>{{ $item->item_name }}</strong></td>
                    <td style="max-width:200px;white-space:normal;">{{ $item->description ?? '—' }}</td>
                    <td>{{ $item->points_required }}</td>
                    <td>{{ $item->quantity }}</td>
                    @php $displayStatus = $item->quantity <= 0 ? 'Unavailable' : $item->status; @endphp
                    <td><span style="color:{{ $displayStatus === 'Available' ? 'var(--green)' : 'var(--gray)' }};">{{ $displayStatus }}</span></td>
                    <td>{{ $item->creator->name ?? 'System' }}</td>
                    <td class="action-buttons">
                        <button type="button" class="btn-edit-item" title="Edit Item" aria-label="Edit"
                            data-id="{{ $item->id }}"
                            data-name="{{ $item->item_name }}"
                            data-description="{{ $item->description }}"
                            data-points="{{ $item->points_required }}"
                            data-quantity="{{ $item->quantity }}"
                            data-status="{{ $item->status }}">
                            ✏️ Edit
                        </button>
                    </td>
                </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="8" style="text-align:center;padding:30px;color:var(--text-light);">No claim items found.</td>
                </tr>
            @endif
        </tbody>
    </table>
    </div>
</div>

{{-- Pagination for Claim Items --}}
@if ($claimItems instanceof \Illuminate\LengthAwarePaginator && $claimItems->total() > 0)
<div class="pagination" style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;margin-top:8px;border-top:1px solid #e2e8f0;">
    <span class="page-info" style="font-size:13px;color:#6b7280;">
        Showing {{ $claimItems->firstItem() }} to {{ $claimItems->lastItem() }} of {{ $claimItems->total() }} items
    </span>
    <div class="page-btns" style="display:flex;gap:4px;">
        @for ($i = 1; $i <= $claimItems->lastPage(); $i++)
            <a href="{{ route('claims.index', array_merge(request()->query(), ['page' => $i])) }}#claim-items-section" class="page-btn {{ $claimItems->currentPage() == $i ? 'active' : '' }}" style="padding:6px 10px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;color:{{ $claimItems->currentPage() == $i ? '#10b981' : '#374151' }};background:{{ $claimItems->currentPage() == $i ? '#d1fae5' : '#fff' }};text-decoration:none;">{{ $i }}</a>
        @endfor
    </div>
</div>
@endif
