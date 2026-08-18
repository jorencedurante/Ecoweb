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

<div style="display:flex;align-items:center;justify-content:space-between;margin-top:36px;margin-bottom:12px;">
    <div>
        <h4 style="font-size:15px;font-weight:600;">🎯 Achievement Quests</h4>
        <span style="font-size:12px;color:var(--text-light);">({{ $quests->count() }} quests)</span>
    </div>
    @if(Auth::user()->isAdminLevel())
    <button type="button" class="btn btn-primary btn-sm" onclick="openAddQuestModal()">+ Add Achievement Quest</button>
    @endif
</div>
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Quest Title</th>
                <th>Requirement Type</th>
                <th>Required Value</th>
                <th>Description</th>
                <th>Status</th>
                <th>Created By</th>
                @if(Auth::user()->isAdminLevel())
                <th>Actions</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($quests as $i => $q)
            @php
                $isBottleQuest = $q->required_bottles > 0;
                $reqType = $isBottleQuest ? 'Bottles' : 'Points';
                $reqValue = $isBottleQuest ? $q->required_bottles : $q->points_required;
            @endphp
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td style="font-weight:600;">{{ $q->title }}</td>
                <td>{{ $reqType }}</td>
                <td>{{ number_format($reqValue) }}</td>
                <td style="font-size:12px;color:var(--text-light);">{{ $q->description ?: '—' }}</td>
                <td>
                    @if($q->status === 'Active')
                        <span style="color:#22C55E;font-weight:600;font-size:12px;">Active</span>
                    @else
                        <span style="color:var(--text-light);font-size:12px;">{{ $q->status }}</span>
                    @endif
                </td>
                <td>{{ $q->creator->name ?? 'System' }}</td>
                @if(Auth::user()->isAdminLevel())
                <td>
                    <div class="action-btns">
                        <button type="button" class="btn btn-xs" style="background:#FFC107;color:#333;border:none;cursor:pointer;" onclick="openQuestEditModal({{ $q->id }}, '{{ addslashes($q->title) }}', '{{ addslashes($q->description ?? '') }}', '{{ $reqType === 'Bottles' ? 'bottles' : 'points' }}', {{ $reqValue }}, '{{ addslashes($q->badge_name ?? '') }}', '{{ $q->status }}')">Edit</button>
                        <form method="POST" action="{{ route('achievement-quests.destroy', $q->id) }}" style="display:inline;">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-xs" style="background:#dc2626;color:#fff;border:none;cursor:pointer;" onclick="return confirm('Delete this achievement quest?')">Delete</button>
                        </form>
                    </div>
                </td>
                @endif
            </tr>
            @empty
            <tr>
                <td colspan="{{ Auth::user()->isAdminLevel() ? 8 : 7 }}" style="text-align:center;color:var(--text-light);padding:30px;">No achievement quests found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if(Auth::user()->isAdminLevel())
{{-- Add Achievement Quest Modal --}}
<div id="questAddModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:10000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:12px;padding:28px;width:560px;max-width:95%;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h4 style="font-size:16px;font-weight:700;">Add Achievement Quest</h4>
            <button type="button" onclick="closeAddQuestModal()" style="background:none;border:none;font-size:22px;cursor:pointer;color:#999;">✕</button>
        </div>
        <form method="POST" action="{{ route('achievement-quests.store') }}">
            @csrf
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group" style="grid-column:span 2;">
                    <label>Quest Title</label>
                    <input type="text" name="title" value="{{ old('title') }}" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                </div>
                <div class="form-group" style="grid-column:span 2;">
                    <label>Description</label>
                    <textarea name="description" rows="3" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">{{ old('description') }}</textarea>
                </div>
                <div class="form-group">
                    <label>Requirement Type</label>
                    <select name="requirement_type" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                        <option value="points" {{ old('requirement_type') === 'points' ? 'selected' : '' }}>Points</option>
                        <option value="bottles" {{ old('requirement_type') === 'bottles' ? 'selected' : '' }}>Bottles</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Required Value</label>
                    <input type="number" name="required_value" value="{{ old('required_value') }}" min="1" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                </div>
                <div class="form-group">
                    <label>Badge / Reward Name <span style="color:var(--text-light);">(optional)</span></label>
                    <input type="text" name="badge_name" value="{{ old('badge_name') }}" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                        <option value="Active" {{ old('status') === 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Inactive" {{ old('status') === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
            </div>
            <div style="display:flex;gap:12px;margin-top:20px;justify-content:flex-end;">
                <button type="button" class="btn btn-outline" onclick="closeAddQuestModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Achievement Quest</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Achievement Quest Modal --}}
<div id="questEditModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:10000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:12px;padding:28px;width:560px;max-width:95%;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h4 style="font-size:16px;font-weight:700;">Edit Achievement Quest</h4>
            <button type="button" onclick="closeQuestEditModal()" style="background:none;border:none;font-size:22px;cursor:pointer;color:#999;">✕</button>
        </div>
        <form method="POST" action="" id="questEditForm">
            @csrf
            @method('PUT')
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group" style="grid-column:span 2;">
                    <label>Quest Title</label>
                    <input type="text" name="title" id="edit_quest_title" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                </div>
                <div class="form-group" style="grid-column:span 2;">
                    <label>Description</label>
                    <textarea name="description" id="edit_quest_description" rows="3" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;"></textarea>
                </div>
                <div class="form-group">
                    <label>Requirement Type</label>
                    <select name="requirement_type" id="edit_requirement_type" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                        <option value="points">Points</option>
                        <option value="bottles">Bottles</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Required Value</label>
                    <input type="number" name="required_value" id="edit_required_value" min="1" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                </div>
                <div class="form-group">
                    <label>Badge / Reward Name <span style="color:var(--text-light);">(optional)</span></label>
                    <input type="text" name="badge_name" id="edit_quest_badge" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="edit_quest_status" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div style="display:flex;gap:12px;margin-top:20px;justify-content:flex-end;">
                <button type="button" class="btn btn-outline" onclick="closeQuestEditModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openAddQuestModal() {
    document.getElementById('questAddModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeAddQuestModal() {
    document.getElementById('questAddModal').style.display = 'none';
    document.body.style.overflow = '';
}
function openQuestEditModal(id, title, description, type, value, badge, status) {
    document.getElementById('edit_quest_title').value = title;
    document.getElementById('edit_quest_description').value = description;
    document.getElementById('edit_requirement_type').value = type;
    document.getElementById('edit_required_value').value = value;
    document.getElementById('edit_quest_badge').value = badge;
    document.getElementById('edit_quest_status').value = status;
    var form = document.getElementById('questEditForm');
    form.action = '{{ route("achievement-quests.update", ["achievement" => "__ID__"]) }}'.replace('__ID__', id);
    document.getElementById('questEditModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeQuestEditModal() {
    document.getElementById('questEditModal').style.display = 'none';
    document.body.style.overflow = '';
}
document.addEventListener('DOMContentLoaded', function () {
    var addModal = document.getElementById('questAddModal');
    if (addModal) {
        addModal.addEventListener('click', function (e) {
            if (e.target === addModal) closeAddQuestModal();
        });
    }
    var editModal = document.getElementById('questEditModal');
    if (editModal) {
        editModal.addEventListener('click', function (e) {
            if (e.target === editModal) closeQuestEditModal();
        });
    }
});
</script>
@endpush
@endif
@endsection
