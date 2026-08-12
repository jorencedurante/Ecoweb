@extends('layouts.admin')

@section('title', 'EcoCollect - Certificate Award')
@section('page-title', 'Certificate Award')
@section('page-subtitle', 'Manage certificate designs and history')

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
    <div>
        <h1 style="font-size:22px;font-weight:700;margin:0;">Certificate Award</h1>
        <p style="margin:4px 0 0;color:var(--text-light);font-size:13px;">Manage certificate designs and history</p>
    </div>
    <a href="{{ route('admin.certificate.create') }}" class="btn btn-primary" style="padding:10px 24px;">+ Design Certificate</a>
</div>

<div class="filter-card" style="margin-top:24px;">
    <div class="filter-header" onclick="this.classList.toggle('collapsed');this.nextElementSibling.classList.toggle('collapsed')">
        <i class="fas fa-filter"></i> Filters
    </div>
    <div class="filter-body">
        <form method="GET" action="{{ route('admin.certificate') }}" class="filter-form">
            <div class="filter-search">
                <label>Search</label>
                <input type="text" name="search" placeholder="Search certificate title..." value="{{ request('search') }}">
            </div>
            <div class="filter-search">
                <label>Award Date</label>
                <input type="date" name="award_date" value="{{ request('award_date') }}">
            </div>
            <div class="filter-search">
                <label>Month</label>
                <select name="month">
                    <option value="">Month</option>
                    @foreach(['January','February','March','April','May','June','July','August','September','October','November','December'] as $m)
                        <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>{{ $m }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-search">
                <label>Year</label>
                <select name="year">
                    <option value="">Year</option>
                    @foreach(['2023','2024','2025','2026'] as $y)
                        <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-controls">
                <button class="btn btn-filter" type="submit">Filter</button>
                <a href="{{ route('admin.certificate') }}" class="btn btn-reset">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="table-container" style="margin-top:24px;">
    <div class="table-header">
        <div class="table-header-left">
            <h4 style="font-size:14px;font-weight:600;">Certificate / Award History</h4>
            <span style="font-size:12px;color:var(--text-light);margin-left:8px;">({{ $awards->total() }} records)</span>
        </div>
    </div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Certificate Title</th>
                <th>Description</th>
                <th>Date Created</th>
                <th>Created By</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($awards as $i => $award)
            <tr>
                <td>{{ $awards->firstItem() + $loop->index }}</td>
                <td>{{ $award->award_title ?: 'Certificate' }}</td>
                <td style="font-size:12px;color:var(--text-light);">{{ $award->award_description ?: 'No description' }}</td>
                <td>{{ $award->awarded_date ? $award->awarded_date->format('Y-m-d') : 'N/A' }}</td>
                <td>{{ $award->issuer->name ?? 'System' }}</td>
                <td>
                    @if($award->status === 'Active')
                        <span style="color:#22C55E;font-weight:600;font-size:12px;">Active</span>
                    @else
                        <span style="color:var(--text-light);font-size:12px;">{{ $award->status }}</span>
                    @endif
                </td>
                <td>
                    <div class="action-btns">
                        @php
                            $filePath = $award->template_file ?? $award->certificate_file ?? $award->template_file_path;
                            $hasCanvas = !empty($award->canvas_data);
                        @endphp
                        @if($hasCanvas)
                        <a href="{{ route('admin.certificate.print', $award->id) }}" class="btn btn-xs" style="background:#6B7280;color:#fff;" target="_blank">Print</a>
                        @if(Auth::user()->isAdminLevel() || $award->teacher_id === Auth::id())
                        <a href="{{ route('admin.certificate.edit', $award->id) }}" class="btn btn-xs" style="background:#FFC107;color:#333;">Edit</a>
                        @endif
                        @elseif($filePath)
                        <a href="{{ route('admin.certificate.download', $award->id) }}" class="btn btn-achievement btn-xs" download>Download</a>
                        <a href="{{ route('admin.certificate.print', $award->id) }}" class="btn btn-xs" style="background:#6B7280;color:#fff;" target="_blank">Print</a>
                        @else
                        <a href="{{ route('admin.certificate.print', $award->id) }}" class="btn btn-xs" style="background:#6B7280;color:#fff;" target="_blank">Print</a>
                        @endif
                        @if(Auth::user()->isAdminLevel() || $award->teacher_id === Auth::id())
                        <form method="POST" action="{{ route('admin.certificate.archive', $award->id) }}" style="display:inline;">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-xs" style="background:var(--red);color:#fff;" onclick="return confirm('Archive this certificate?')">Archive</button>
                        </form>
                        @endif
                        @if(Auth::user()->isAdminLevel())
                        <form method="POST" action="{{ route('admin.certificate.destroy', $award->id) }}" style="display:inline;">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-xs" style="background:#dc2626;color:#fff;" onclick="return confirm('Delete this certificate permanently?')">Delete</button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align:center;color:var(--text-light);padding:30px;">No certificate records found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @if($awards->hasPages())
    <div class="pagination">
        <span class="page-info">Showing {{ $awards->firstItem() ?? 0 }} to {{ $awards->lastItem() ?? 0 }} of {{ $awards->total() }} entries</span>
        <div class="page-btns">
            @for ($p = 1; $p <= $awards->lastPage(); $p++)
                <a href="{{ $awards->url($p) }}" class="page-btn {{ $awards->currentPage() == $p ? 'active' : '' }}">{{ $p }}</a>
            @endfor
        </div>
    </div>
    @endif
</div>
@endsection
