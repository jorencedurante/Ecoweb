@extends('layouts.admin')

@section('title', 'EcoCollect - Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Overview of waste collection statistics')

@section('content')
    @if($subTeacherInfo)
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:18px 22px;margin-bottom:20px;box-shadow:0 2px 12px rgba(0,0,0,.04);">
            <div style="display:flex;flex-wrap:wrap;align-items:center;gap:12px;justify-content:space-between;">
                <div>
                    <div style="font-size:17px;font-weight:700;color:#111827;">{{ $subTeacherInfo['name'] }}</div>
                    <div style="font-size:13px;color:#6b7280;margin-top:2px;">Role: {{ $subTeacherInfo['role'] }}</div>
                </div>
                @if($subTeacherInfo['assigned'])
                    <span style="display:inline-flex;align-items:center;gap:6px;background:#dcfce7;color:#166534;border-radius:999px;padding:6px 14px;font-size:13px;font-weight:600;">Working with {{ $subTeacherInfo['teacher_name'] }}</span>
                @else
                    <span style="display:inline-flex;align-items:center;background:#fef3c7;color:#92400e;border-radius:999px;padding:6px 14px;font-size:13px;font-weight:600;">No Teacher Assignment Yet</span>
                @endif
            </div>
            @if($subTeacherInfo['assigned'])
                <div style="display:flex;flex-wrap:wrap;gap:24px;margin-top:14px;padding-top:14px;border-top:1px solid #f3f4f6;font-size:13px;">
                    <div><span style="color:#6b7280;">Assigned Teacher:</span> <strong style="color:#111827;">{{ $subTeacherInfo['teacher_name'] }}</strong></div>
                    <div><span style="color:#6b7280;">Access Level:</span> <strong style="color:#111827;">{{ $subTeacherInfo['access_level'] }}</strong></div>
                </div>
                <div style="margin-top:10px;font-size:12px;color:#6b7280;">Your actions are recorded under your own account while records stay owned by the assigned Teacher.</div>
            @else
                <div style="margin-top:14px;padding:12px 14px;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;font-size:13px;color:#92400e;">
                    You have not been assigned to a teacher. Please contact your administrator.
                </div>
            @endif
        </div>
    @endif

    @if(Auth::user()->isTeacher() && !empty($collaborators))
        <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:12px 16px;margin-bottom:20px;font-size:13px;color:#1e40af;">
            <strong>With Collaborator</strong>
            <span style="margin-left:8px;">{{ count($collaborators) }} Sub Teacher{{ count($collaborators) === 1 ? '' : 's' }} assigned to your records.</span>
            <div style="margin-top:8px;display:flex;flex-wrap:wrap;gap:8px;">
                @foreach($collaborators as $collaborator)
                    <span style="background:#fff;border:1px solid #bfdbfe;border-radius:999px;padding:4px 10px;">{{ $collaborator['name'] }} — {{ $collaborator['access_level'] }}</span>
                @endforeach
            </div>
        </div>
    @endif

    @if(!$subTeacherInfo || $subTeacherInfo['assigned'])
    <div class="stat-cards">
        <div class="stat-card">
            <div class="stat-icon green">👥</div>
            <div class="stat-info">
                <div class="stat-value">{{ $totalStudents }}</div>
                <div class="stat-label">Total Students</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue">🧴</div>
            <div class="stat-info">
                <div class="stat-value">{{ number_format($totalBottles) }}</div>
                <div class="stat-label">Total Bottles Collected</div>
            </div>
        </div>
        @if(in_array(Auth::user()->role, ['admin', 'super_admin']))
        <div class="stat-card">
            <div class="stat-icon yellow">👨‍🏫</div>
            <div class="stat-info">
                <div class="stat-value">{{ $totalTeachers }}</div>
                <div class="stat-label">Total Teachers</div>
            </div>
        </div>
        @endif

        @if(Auth::user()->isSuperAdmin())
        <div class="stat-card">
            <div class="stat-icon blue">🛡️</div>
            <div class="stat-info">
                <div class="stat-value">{{ $totalAdmins }}</div>
                <div class="stat-label">Total Admins</div>
            </div>
        </div>
        @endif
        <div class="stat-card">
            <div class="stat-icon purple">🏆</div>
            <div class="stat-info">
                <div class="stat-value">{{ $totalAwards }}</div>
                <div class="stat-label">Certificates</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple">🎁</div>
            <div class="stat-info">
                <div class="stat-value">{{ $totalClaimedItems }}</div>
                <div class="stat-label">Total Claimed Items</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue">💳</div>
            <div class="stat-info">
                <div class="stat-value">{{ number_format($totalPointsRedeemed) }}</div>
                <div class="stat-label">Total Points Redeemed</div>
            </div>
        </div>
    </div>

    <div class="charts-row">
        <div class="chart-card">
            <h4>Gender Distribution</h4>
            @php
                $total = $femaleCount + $maleCount;
                $femalePct = $total > 0 ? round($femaleCount / $total * 100) : 0;
                $malePct = $total > 0 ? round($maleCount / $total * 100) : 0;
            @endphp
            <div class="donut-placeholder" style="background: conic-gradient(var(--green) 0% {{ $malePct }}%, #E0E0E0 {{ $malePct }}% 100%);">
                <div class="donut-inner">
                    <span>{{ $malePct }}%</span>
                    <span style="font-size:10px;color:#6b7280;">Male</span>
                </div>
            </div>
            <div class="donut-legend">
                <div class="legend-item"><span class="legend-dot" style="background:var(--green)"></span> Male ({{ $malePct }}%)</div>
                <div class="legend-item"><span class="legend-dot" style="background:#E0E0E0"></span> Female ({{ $femalePct }}%)</div>
            </div>
        </div>
        <div class="chart-card">
            <h4>Bottle Collection Overview</h4>
            <div class="chart-placeholder">
                @foreach(['Monday' => 'Mon', 'Tuesday' => 'Tue', 'Wednesday' => 'Wed', 'Thursday' => 'Thu', 'Friday' => 'Fri'] as $day => $label)
                    @php $height = max(20, ($collectionData[$day] ?? 0) * 2); @endphp
                    <div class="bar-group">
                        <div class="bar" style="height:{{ min($height, 160) }}px"></div>
                        <span class="bar-label">{{ $label }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    @if(!$subTeacherInfo || in_array('view_reports', $subTeacherInfo['permissions'], true))
        <a href="{{ route(portal_prefix() . '.reports') }}" class="btn btn-primary">View Reports</a>
    @endif
    @endif
@endsection
