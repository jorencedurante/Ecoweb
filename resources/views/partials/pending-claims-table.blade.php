<div class="table-container">
    @if($pendingClaims->isNotEmpty())
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Student</th>
                <th>LRN</th>
                <th>Item</th>
                <th>Points Required</th>
                <th>Student Points</th>
                <th>Date Requested</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pendingClaims as $index => $claim)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td><strong>{{ $claim->student->full_name ?? 'N/A' }}</strong></td>
                <td>{{ $claim->student->lrn ?? 'N/A' }}</td>
                <td>{{ $claim->item_name }}</td>
                <td>{{ number_format($claim->points_deducted) }}</td>
                <td>{{ number_format($claim->student->total_points ?? 0) }}</td>
                <td>{{ $claim->created_at->format('M d, Y h:i A') }}</td>
                <td>
                    <div style="display:flex;gap:6px;">
                        <form method="POST" action="{{ route('claims.approve', $claim->id) }}" style="display:inline;">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-success btn-sm"
                                @if(($claim->student->total_points ?? 0) < $claim->points_deducted) disabled title="Insufficient points" style="opacity:0.5;" @endif>
                                Approve
                            </button>
                        </form>
                        <form method="POST" action="{{ route('claims.reject', $claim->id) }}" style="display:inline;" onsubmit="return confirm('Reject this claim?')">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div style="text-align:center;padding:30px;color:#9CA3AF;">
        <p>No pending claims.</p>
    </div>
    @endif
</div>
