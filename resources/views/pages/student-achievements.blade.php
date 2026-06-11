@extends('layouts.admin')

@section('title', 'EcoCollect - Student Achievements')
@section('page-title', 'Student Achievements')
@section('page-subtitle', 'Waste collection achievements and milestones')

@section('content')
    <a href="{{ route('admin.students') }}" class="back-link">← Back to Students</a>

    @if(session('success'))
    <div class="alert-success show">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div style="background:rgba(239,83,80,0.1);border:1px solid var(--red);color:var(--red-dark);padding:12px 16px;border-radius:var(--radius-sm);font-size:14px;font-weight:500;margin-bottom:16px;">Please check the form fields and try again.</div>
    @endif

    <div class="card" style="margin-bottom:24px;">
        <div class="card-body">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                <div style="display:flex;align-items:center;gap:16px;">
                    <div class="admin-avatar" style="width:48px;height:48px;font-size:18px;">
                        {{ substr($student->first_name, 0, 1) }}{{ substr($student->last_name, 0, 1) }}
                    </div>
                    <div>
                        <h3 style="font-size:18px;font-weight:700;">{{ $student->full_name }}</h3>
                        <p style="color:var(--text-medium);font-size:13px;">{{ $student->grade_level }} • {{ $student->id }}</p>
                    </div>
                </div>
            </div>
            <div class="summary-cards">
                <div class="stat-card">
                    <div class="stat-icon blue">🧴</div>
                    <div class="stat-info">
                        <div class="stat-value">{{ $totalBottles }}</div>
                        <div class="stat-label">Total Bottles</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">⭐</div>
                    <div class="stat-info">
                        <div class="stat-value">{{ $earnedPoints }}</div>
                        <div class="stat-label">Earned Points</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon yellow">🏅</div>
                    <div class="stat-info">
                        <div class="stat-value">{{ $earnedAchievements->count() }}</div>
                        <div class="stat-label">Achievements</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
        <h4 style="font-size:15px;font-weight:600;">📋 Available Achievement Quests</h4>
        @if(in_array(auth()->user()->role ?? 'admin', ['admin', 'teacher', 'super_admin']))
        <button type="button" class="btn btn-primary btn-sm" onclick="openQuestModal()">+ Add Achievement Quest</button>
        @endif
    </div>
    <div class="achievement-grid" style="margin-bottom:24px;">
        @forelse($quests as $quest)
            @php
                $progressBottles = $quest->required_bottles > 0 ? min(100, ($totalBottles / $quest->required_bottles) * 100) : 0;
                $progressPoints = $quest->points_required > 0 ? min(100, ($earnedPoints / $quest->points_required) * 100) : 0;
                $questProgress = max($progressBottles, $progressPoints);
                $completedByBottles = $quest->required_bottles > 0 && $totalBottles >= $quest->required_bottles;
                $completedByPoints = $quest->points_required > 0 && $earnedPoints >= $quest->points_required;
                $isCompleted = $completedByBottles || $completedByPoints;
                $displayProgress = $quest->required_bottles > 0 ? $totalBottles . ' / ' . $quest->required_bottles . ' bottles' : ($quest->points_required > 0 ? $earnedPoints . ' / ' . $quest->points_required . ' pts' : '—');
            @endphp
            <div class="achievement-card" style="position:relative;">
                @if($isCompleted)
                <div style="position:absolute;top:8px;right:8px;background:var(--green);color:#fff;font-size:10px;font-weight:700;padding:2px 8px;border-radius:6px;">✓ Completed</div>
                @endif
                <div class="card-icon">📋</div>
                <h4>{{ $quest->title }}</h4>
                <div class="achievement-desc">{{ $quest->description ?? '—' }}</div>
                <div class="achievement-meta">
                    @if($quest->badge_name)<span>Badge: <strong>{{ $quest->badge_name }}</strong></span> • @endif
                    @if($quest->required_bottles > 0)<span>{{ $quest->required_bottles }} bottles required</span>@endif
                    @if($quest->points_required > 0)<span>{{ $quest->points_required }} pts required</span>@endif
                </div>
                @if(in_array(auth()->user()->role ?? 'admin', ['admin', 'teacher', 'super_admin']))
                <div style="margin-top:8px;">
                    <button type="button" class="btn btn-sm btn-outline" onclick="openEditProgressModal({{ $quest->id }}, '{{ addslashes($quest->title) }}', {{ $quest->required_bottles }}, {{ $quest->points_required }})">Edit Progress</button>
                </div>
                @endif
                <div style="margin-top:10px;padding-top:10px;border-top:1px solid var(--border);">
                    <div style="font-size:12px;color:var(--text-medium);margin-bottom:4px;">
                        <span>Status: <strong style="color:{{ $isCompleted ? 'var(--green)' : '#FBBF24' }};">{{ $isCompleted ? 'Completed' : 'In Progress' }}</strong></span>
                        <span style="float:right;">{{ $displayProgress }}</span>
                    </div>
                    <div style="background:#E5E7EB;border-radius:6px;height:8px;overflow:hidden;">
                        <div style="background:{{ $isCompleted ? 'var(--green)' : 'var(--blue)' }};width:{{ $questProgress }}%;height:100%;border-radius:6px;transition:width 0.3s;"></div>
                    </div>
                </div>
            </div>
        @empty
        <div class="card" style="padding:30px;text-align:center;color:var(--text-light);grid-column:1/-1;">
            No achievement quests available yet.
        </div>
        @endforelse
    </div>

    <div style="margin-bottom:12px;">
        <h4 style="font-size:15px;font-weight:600;">🏅 Earned Achievements</h4>
    </div>
    <div class="achievement-grid" style="margin-bottom:24px;">
        @forelse($earnedAchievements as $ea)
            @php $q = $ea->quest; @endphp
            <div class="achievement-card eco" style="position:relative;">
                <div style="position:absolute;top:8px;right:8px;background:var(--green);color:#fff;font-size:10px;font-weight:700;padding:2px 8px;border-radius:6px;">✓ Earned</div>
                <div class="card-icon">🏅</div>
                <h4>{{ $q->title ?? 'Achievement' }}</h4>
                <div class="achievement-desc">{{ $q->description ?? '—' }}</div>
                <div class="achievement-meta">
                    @if($q->badge_name)<span>Badge: <strong>{{ $q->badge_name }}</strong></span> • @endif
                    <span>Awarded: {{ $ea->awarded_date->format('M d, Y') }}</span>
                </div>
            </div>
        @empty
        <div class="card" style="padding:30px;text-align:center;color:var(--text-light);grid-column:1/-1;">
            No earned achievements yet.
        </div>
        @endforelse
    </div>

    {{-- Edit Progress Modal --}}
    <div id="editProgressModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:10000;align-items:center;justify-content:center;">
        <div style="background:#fff;border-radius:12px;padding:28px;width:500px;max-width:95%;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                <h4 style="font-size:16px;font-weight:700;">Edit Quest Progress</h4>
                <button type="button" onclick="closeEditProgressModal()" style="background:none;border:none;font-size:22px;cursor:pointer;color:#999;">✕</button>
            </div>
            <form method="POST" action="{{ route('admin.achievements.update', ['achievement' => '__ID__']) }}" id="editProgressForm">
                @csrf
                @method('PUT')
                <p style="margin-bottom:16px;color:var(--text-medium);font-size:14px;" id="editProgressTitle"></p>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group">
                        <label>Required Bottles</label>
                        <input type="number" name="required_bottles" id="edit_required_bottles" min="0" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                    </div>
                    <div class="form-group">
                        <label>Required Points</label>
                        <input type="number" name="points_required" id="edit_points_required" min="0" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                    </div>
                </div>
                <div style="display:flex;gap:12px;margin-top:20px;justify-content:flex-end;">
                    <button type="button" class="btn btn-outline" onclick="closeEditProgressModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Add Achievement Quest Modal --}}
    <div id="questModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:10000;align-items:center;justify-content:center;">
        <div style="background:#fff;border-radius:12px;padding:28px;width:560px;max-width:95%;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                <h4 style="font-size:16px;font-weight:700;">Add Achievement Quest</h4>
                <button type="button" onclick="closeQuestModal()" style="background:none;border:none;font-size:22px;cursor:pointer;color:#999;">✕</button>
            </div>
            <form method="POST" action="{{ route('admin.achievements.store') }}">
                @csrf
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group">
                        <label>Achievement Title</label>
                        <input type="text" name="title" value="{{ old('title') }}" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                    </div>
                    <div class="form-group">
                        <label>Badge Name</label>
                        <input type="text" name="badge_name" value="{{ old('badge_name') }}" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                    </div>
                    <div class="form-group" style="grid-column:span 2;">
                        <label>Description</label>
                        <textarea name="description" rows="3" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">{{ old('description') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label>Milestone Type</label>
                        <select name="milestone" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                            <option value="Bottle Collection" {{ old('milestone') === 'Bottle Collection' ? 'selected' : '' }}>Bottle Collection</option>
                            <option value="Points" {{ old('milestone') === 'Points' ? 'selected' : '' }}>Points</option>
                            <option value="Consistency" {{ old('milestone') === 'Consistency' ? 'selected' : '' }}>Consistency</option>
                            <option value="Special Award" {{ old('milestone') === 'Special Award' ? 'selected' : '' }}>Special Award</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                            <option value="Active" {{ old('status') === 'Active' ? 'selected' : '' }}>Active</option>
                            <option value="Inactive" {{ old('status') === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Required Bottles</label>
                        <input type="number" name="required_bottles" value="{{ old('required_bottles', 0) }}" min="0" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                    </div>
                    <div class="form-group">
                        <label>Required Points</label>
                        <input type="number" name="points_required" value="{{ old('points_required', 0) }}" min="0" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                    </div>
                </div>
                <div style="display:flex;gap:12px;margin-top:20px;justify-content:flex-end;">
                    <button type="button" class="btn btn-outline" onclick="closeQuestModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Achievement Quest</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function openQuestModal() {
    document.getElementById('questModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeQuestModal() {
    document.getElementById('questModal').style.display = 'none';
    document.body.style.overflow = '';
}
document.addEventListener('DOMContentLoaded', function () {
    var qmodal = document.getElementById('questModal');
    if (qmodal) {
        qmodal.addEventListener('click', function (e) {
            if (e.target === qmodal) {
                closeQuestModal();
            }
        });
    }
});

function openEditProgressModal(id, title, requiredBottles, requiredPoints) {
    document.getElementById('editProgressTitle').textContent = 'Quest: ' + title;
    document.getElementById('edit_required_bottles').value = requiredBottles;
    document.getElementById('edit_points_required').value = requiredPoints;
    var form = document.getElementById('editProgressForm');
    form.action = '{{ route("admin.achievements.update", ["achievement" => "__ID__"]) }}'.replace('__ID__', id);
    document.getElementById('editProgressModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeEditProgressModal() {
    document.getElementById('editProgressModal').style.display = 'none';
    document.body.style.overflow = '';
}
document.addEventListener('DOMContentLoaded', function () {
    var epmodal = document.getElementById('editProgressModal');
    if (epmodal) {
        epmodal.addEventListener('click', function (e) {
            if (e.target === epmodal) {
                closeEditProgressModal();
            }
        });
    }
});
</script>
@endpush
