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
            @forelse($claimItems as $i)
            <tr>
                <td>{{ $claimItems->firstItem() + $loop->index }}</td>
                <td><strong>{{ $i->item_name }}</strong></td>
                <td style="max-width:200px;white-space:normal;">{{ $i->description ?? '—' }}</td>
                <td>{{ $i->points_required }}</td>
                <td>{{ $i->quantity }}</td>
                @php $displayStatus = $i->quantity <= 0 ? 'Unavailable' : $i->status; @endphp
                <td><span style="color:{{ $displayStatus === 'Available' ? 'var(--green)' : 'var(--gray)' }};">{{ $displayStatus }}</span></td>
                <td>{{ $i->creator->name ?? 'System' }}</td>
                <td class="action-buttons">
                    <button type="button" class="btn-edit-item" title="Edit Item" aria-label="Edit"
                        data-id="{{ $i->id }}"
                        data-name="{{ $i->item_name }}"
                        data-description="{{ $i->description }}"
                        data-points="{{ $i->points_required }}"
                        data-quantity="{{ $i->quantity }}"
                        data-status="{{ $i->status }}">
                        ✏️ Edit
                    </button>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" style="text-align:center;padding:30px;color:var(--text-light);">No claim items added yet.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
    @if($claimItems->hasPages())
    <div class="pagination">
        <span class="page-info">Showing {{ $claimItems->firstItem() }} to {{ $claimItems->lastItem() }} of {{ $claimItems->total() }} entries</span>
        <div class="page-btns">
            @for ($i = 1; $i <= $claimItems->lastPage(); $i++)
                <a href="{{ $claimItems->url($i) }}" class="page-btn {{ $claimItems->currentPage() == $i ? 'active' : '' }}">{{ $i }}</a>
            @endfor
        </div>
    </div>
    @endif
</div>
