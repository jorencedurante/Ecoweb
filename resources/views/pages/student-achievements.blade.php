@extends('layouts.admin')

@section('title', 'EcoCollect - Student Achievements')
@section('page-title', 'Student Achievements')
@section('page-subtitle', 'Waste collection achievements and milestones')

@section('content')
    <a href="{{ route('admin.students') }}" class="back-link">← Back to Students</a>

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

    <h4 style="font-size:15px;font-weight:600;margin-bottom:12px;">📋 Available Achievement Quests</h4>
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
@endsection
