@extends('layouts.admin')

@section('title', 'EcoCollect - Admin Activities')
@section('page-title', 'Admin Activities')
@section('page-subtitle', 'Monitor admin and teacher account activities')

@section('content')
    <a href="{{ route('admin.reports') }}" class="back-link">← Back to Reports</a>

    <div class="filter-card">
        <div class="filter-header" onclick="this.classList.toggle('collapsed');this.nextElementSibling.classList.toggle('collapsed')">
            <i class="fas fa-filter"></i> Filters
        </div>
        <div class="filter-body">
            <form method="GET" action="{{ route('admin.admin-activities') }}" class="filter-form">
                <div class="filter-search">
                    <label>Search</label>
                    <input type="text" name="search" placeholder="Search activities..." value="{{ request('search') }}">
                </div>
                <div class="filter-search">
                    <label>Action</label>
                    <select name="action">
                        <option value="">All Actions</option>
                        @foreach($actions ?? [] as $a)
                            <option value="{{ $a }}" {{ request('action') == $a ? 'selected' : '' }}>{{ $a }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-search">
                    <label>User</label>
                    <select name="user_id">
                        <option value="">All Users</option>
                        @foreach($users ?? [] as $u)
                            <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-search">
                    <label>Date From</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}">
                </div>
                <div class="filter-search">
                    <label>Date To</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}">
                </div>
                <div class="filter-controls">
                    <button class="btn btn-filter" type="submit">Filter</button>
                    <a href="{{ route('admin.admin-activities') }}" class="btn btn-reset">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div class="table-container">
        <div class="table-header">
            <div class="table-header-left">
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Description</th>
                    <th>Module</th>
                    <th>Date & Time</th>
                </tr>
            </thead>
            <tbody>
                @forelse($activities as $i => $a)
                <tr>
                    <td>{{ $activities->firstItem() + $i }}</td>
                    <td>{{ $a->user->name ?? 'System' }}</td>
                    <td><strong>{{ $a->action }}</strong></td>
                    <td>{{ $a->description }}</td>
                    <td>{{ $a->module ?? '—' }}</td>
                    <td>{{ $a->created_at->format('M d, Y h:i A') }}</td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--text-light);">No activities recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="pagination">
            <span class="page-info">Showing {{ $activities->firstItem() ?? 0 }} to {{ $activities->lastItem() ?? 0 }} of {{ $activities->total() }} entries</span>
            <div class="page-btns">
                @for ($p = 1; $p <= $activities->lastPage(); $p++)
                    <a href="{{ $activities->url($p) }}" class="page-btn {{ $activities->currentPage() == $p ? 'active' : '' }}">{{ $p }}</a>
                @endfor
            </div>
        </div>
    </div>
@endsection
