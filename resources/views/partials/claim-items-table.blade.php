<div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Item Name</th>
                <th>Description</th>
                <th>Points Required</th>
                <th>Quantity</th>
                <th>Status</th>
                <th>Created By</th>
                <th>Actions</th>
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
                    <button type="button" class="btn-edit-item" title="Edit Item"
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
