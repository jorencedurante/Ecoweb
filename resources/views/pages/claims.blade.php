@extends('layouts.admin')

@section('title', 'EcoCollect - Claim Items')
@section('page-title', 'Claim Items')
@section('page-subtitle', 'Manage reward items and student claims')

@section('content')
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px;">
        {{-- Claim Item Form --}}
        <div class="card">
            <div class="card-body">
                <h4 style="font-size:15px;font-weight:600;margin-bottom:16px;">+ Add Claim Item</h4>
                <form method="POST" action="{{ route('claim-items.store') }}">
                    @csrf
                    <div class="form-group">
                        <label>Item Name</label>
                        <input type="text" name="item_name" value="{{ old('item_name') }}" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="2" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">{{ old('description') }}</textarea>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                        <div class="form-group">
                            <label>Points Required</label>
                            <input type="number" name="points_required" value="{{ old('points_required') }}" min="1" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                        </div>
                        <div class="form-group">
                            <label>Quantity</label>
                            <input type="number" name="quantity" value="{{ old('quantity', 0) }}" min="0" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
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
                    <div class="form-group">
                        <label>Select Student</label>
                        <select name="student_id" id="claim_student_id" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                            <option value="">Select student</option>
                            @foreach($students as $s)
                                <option value="{{ $s->id }}" data-points="{{ $s->total_points ?? 0 }}">{{ $s->full_name }} ({{ $s->lrn }}) — {{ $s->total_points ?? 0 }} pts</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Select Item</label>
                        <select name="claim_item_id" id="claim_item_id" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                            <option value="">Select item</option>
                            @foreach(\App\Models\ClaimItem::where('status', 'Available')->where('quantity', '>', 0)->get() as $i)
                                <option value="{{ $i->id }}" data-points="{{ $i->points_required }}">{{ $i->item_name }} — {{ $i->points_required }} pts ({{ $i->quantity }} left)</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                        <div style="background:#F3F4F6;border-radius:8px;padding:12px;text-align:center;">
                            <div style="font-size:11px;color:#9CA3AF;">Student Points</div>
                            <div id="student_pts_display" style="font-size:20px;font-weight:700;color:#111827;">—</div>
                        </div>
                        <div style="background:#F3F4F6;border-radius:8px;padding:12px;text-align:center;">
                            <div style="font-size:11px;color:#9CA3AF;">Item Cost</div>
                            <div id="item_cost_display" style="font-size:20px;font-weight:700;color:#EF4444;">—</div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Remarks (optional)</label>
                        <input type="text" name="remarks" value="{{ old('remarks') }}" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;">Claim Item</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Claim Items List --}}
    <div class="table-container" style="margin-bottom:24px;">
        <div class="table-header">
            <h4 style="font-size:14px;font-weight:600;">Claim Items</h4>
        </div>
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
                </tr>
            </thead>
            <tbody>
                @forelse($items as $i)
                <tr>
                    <td>{{ $items->firstItem() + $loop->index }}</td>
                    <td><strong>{{ $i->item_name }}</strong></td>
                    <td style="max-width:200px;white-space:normal;">{{ $i->description ?? '—' }}</td>
                    <td>{{ $i->points_required }}</td>
                    <td>{{ $i->quantity }}</td>
                    <td><span style="color:{{ $i->status === 'Available' ? 'var(--green)' : 'var(--gray)' }};">{{ $i->status }}</span></td>
                    <td>{{ $i->creator->name ?? 'System' }}</td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--text-light);">No claim items added yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($items->hasPages())
        <div class="pagination">
            <span class="page-info">Showing {{ $items->firstItem() }} to {{ $items->lastItem() }} of {{ $items->total() }} entries</span>
            <div class="page-btns">
                @for ($i = 1; $i <= $items->lastPage(); $i++)
                    <a href="{{ $items->url($i) }}" class="page-btn {{ $items->currentPage() == $i ? 'active' : '' }}">{{ $i }}</a>
                @endfor
            </div>
        </div>
        @endif
    </div>

    {{-- Claim History --}}
    <div class="table-container">
        <div class="table-header">
            <h4 style="font-size:14px;font-weight:600;">Claim History</h4>
        </div>
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
                    <td>{{ $c->claim_date->format('Y-m-d') }}</td>
                    <td>{{ $c->admin->name ?? 'System' }}</td>
                    <td>{{ $c->remarks ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="10" style="text-align:center;padding:30px;color:var(--text-light);">No claims recorded yet.</td></tr>
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
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var studentSelect = document.getElementById('claim_student_id');
    var itemSelect = document.getElementById('claim_item_id');
    var studentPts = document.getElementById('student_pts_display');
    var itemCost = document.getElementById('item_cost_display');

    if (studentSelect) {
        studentSelect.addEventListener('change', function () {
            var opt = this.options[this.selectedIndex];
            studentPts.textContent = opt && opt.value ? (opt.dataset.points || '0') : '—';
            checkSufficient();
        });
    }
    if (itemSelect) {
        itemSelect.addEventListener('change', function () {
            var opt = this.options[this.selectedIndex];
            itemCost.textContent = opt && opt.value ? (opt.dataset.points || '0') : '—';
            checkSufficient();
        });
    }
    function checkSufficient() {
        var pts = parseInt(studentPts.textContent) || 0;
        var cost = parseInt(itemCost.textContent) || 0;
        var btn = document.querySelector('button[type="submit"]');
        if (btn && studentPts.textContent !== '—' && itemCost.textContent !== '—') {
            btn.textContent = pts >= cost ? 'Claim Item' : 'Insufficient Points';
            btn.style.opacity = pts >= cost ? '1' : '0.6';
        }
    }
});
</script>
@endpush
