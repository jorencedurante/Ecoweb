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
                    <td>{{ $index + 1 }}</td>
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
