@extends('layouts.admin')

@section('title', 'EcoCollect - Admin Activities')
@section('page-title', 'Admin Activities')
@section('page-subtitle', 'Monitor admin and teacher account activities')

@section('content')
    <a href="{{ route('admin.reports') }}" class="back-link">← Back to Reports</a>

    <div class="table-container">
        <div class="table-header">
            <div class="table-header-left">
                <form method="GET" action="{{ route('admin.admin-activities') }}" style="display:flex;gap:8px;align-items:center;">
                    <div class="search-box">
                        <span class="search-icon">🔍</span>
                        <input type="text" name="search" placeholder="Search activities..." value="{{ request('search') }}">
                    </div>
                    <button class="filter-btn" type="submit">🔽 Filter</button>
                    <button class="filter-btn" type="button" onclick="window.location='{{ route('admin.admin-activities') }}'">Clear</button>
                </form>
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
