<div class="table-container">
    @if($pendingClaims->isNotEmpty())
    <div class="table-responsive">
    <table>
        <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">Student</th>
                <th scope="col">LRN</th>
                <th scope="col">Item</th>
                <th scope="col">Qty</th>
                <th scope="col">Points Required</th>
                <th scope="col">Student Points</th>
                <th scope="col">Date Requested</th>
                <th scope="col">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pendingClaims as $index => $claim)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td><strong>{{ $claim->student->full_name ?? 'N/A' }}</strong></td>
                <td>{{ $claim->student->lrn ?? 'N/A' }}</td>
                <td>{{ $claim->item_name }}</td>
                <td>{{ $claim->quantity ?? 1 }}</td>
                <td>{{ number_format($claim->points_deducted) }}</td>
                <td>{{ number_format($claim->student->total_points ?? 0) }}</td>
                <td>{{ $claim->created_at->format('M d, Y h:i A') }}</td>
                <td>
                    <div style="display:flex;gap:6px;">
                        <form method="POST" action="{{ route('claims.approve', $claim->id) }}" style="display:inline;">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-success btn-sm" aria-label="Approve claim"
                                @if(($claim->student->total_points ?? 0) < $claim->points_deducted) disabled title="Insufficient points" style="opacity:0.5;" @endif>
                                Approve
                            </button>
                        </form>
                        <form method="POST" action="{{ route('claims.reject', $claim->id) }}" style="display:inline;" onsubmit="return confirm('Reject this claim?')">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-danger btn-sm" aria-label="Reject claim">Reject</button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>
    @else
    <div style="text-align:center;padding:30px;color:#9CA3AF;">
        <p>No pending claims.</p>
    </div>
    @endif
</div>
