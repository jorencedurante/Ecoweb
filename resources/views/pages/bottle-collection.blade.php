@extends('layouts.admin')

@section('title', 'EcoCollect - Bottle Collection')
@section('page-title', 'Bottle Collection')
@section('page-subtitle', 'Manage bottle collection records')

@section('content')
    <div class="table-container">
        <div class="table-header">
            <div class="table-header-left">
                <form method="GET" action="{{ route('admin.bottle-collection') }}" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                    <div class="dropdown-group">
                        <select name="day">
                            <option value="">Day</option>
                            @for($i = 1; $i <= 31; $i++)
                                <option value="{{ $i }}" {{ request('day') == $i ? 'selected' : '' }}>{{ $i }}</option>
                            @endfor
                        </select>
                        <select name="month">
                            <option value="">Month</option>
                            @foreach(['January','February','March','April','May','June','July','August','September','October','November','December'] as $m)
                                <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>{{ $m }}</option>
                            @endforeach
                        </select>
                        <select name="year">
                            <option value="">Year</option>
                            @foreach(['2023','2024','2025','2026'] as $y)
                                <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="search-box">
                        <span class="search-icon">🔍</span>
                        <input type="text" name="search" placeholder="Search by LRN..." value="{{ request('search') }}">
                    </div>
                    <button class="filter-btn" type="submit">🔽 Filter</button>
                    <button class="filter-btn" type="button" onclick="window.location='{{ route('admin.bottle-collection') }}'">Clear</button>
                </form>
            </div>
            <button class="btn btn-primary" data-modal-target="addCollectionModal">+ Add Collection</button>
        </div>
        <table>
            <thead>
                <tr>
                    <th>LRN</th>
                    <th>Student</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Bottle Count</th>
                    <th>Points</th>
                </tr>
            </thead>
            <tbody>
                @forelse($collections as $c)
                <tr>
                    <td>{{ $c->lrn }}</td>
                    <td><strong>{{ $c->student->full_name ?? 'Unknown' }}</strong></td>
                    <td>{{ $c->collection_date }}</td>
                    <td>{{ \Carbon\Carbon::parse($c->collection_time)->format('h:i A') }}</td>
                    <td>{{ $c->bottle_count }}</td>
                    <td>{{ $c->points_earned }}</td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--text-light);">No collection records found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="pagination">
            <span class="page-info">Showing {{ $collections->firstItem() ?? 0 }} to {{ $collections->lastItem() ?? 0 }} of {{ $collections->total() }} entries</span>
            <div class="page-btns">
                @for ($i = 1; $i <= $collections->lastPage(); $i++)
                    <a href="{{ $collections->url($i) }}" class="page-btn {{ $collections->currentPage() == $i ? 'active' : '' }}">{{ $i }}</a>
                @endfor
            </div>
        </div>
    </div>

    <!-- Add Collection Modal -->
    <div class="modal-overlay" id="addCollectionModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add bottle collection</h3>
                <p class="subtitle">Record a new bottle collection</p>
            </div>
            <form method="POST" action="{{ route('admin.bottle-collection.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Student</label>
                        <select name="student_id" required style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;">
                            <option value="">Select student</option>
                            @foreach(\App\Models\Student::where('status', '!=', 'Archived')->get() as $s)
                                <option value="{{ $s->id }}">{{ $s->full_name }} ({{ $s->lrn }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Date</label>
                            <input type="date" name="collection_date" value="{{ date('Y-m-d') }}" required style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;">
                        </div>
                        <div class="form-group">
                            <label>Time</label>
                            <input type="time" name="collection_time" value="{{ date('H:i') }}" required style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Bottle Count</label>
                        <input type="number" name="bottle_count" min="1" placeholder="Enter number of bottles" required style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-modal-close>Cancel</button>
                    <button type="submit" class="btn btn-success">Save Collection</button>
                </div>
            </form>
        </div>
    </div>
@endsection
