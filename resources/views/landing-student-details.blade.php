<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoCollect - Student Details</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="icon" type="image/jpeg" href="{{ asset('image/Page-logo.jpg') }}">
    <link rel="shortcut icon" type="image/jpeg" href="{{ asset('image/Page-logo.jpg') }}">
    <link rel="apple-touch-icon" href="{{ asset('image/Page-logo.jpg') }}">
</head>
<body class="public-landing-page">

<div class="public-landing">

<nav class="public-nav">
    <div class="public-nav-inner">
        <a href="{{ route('landing') }}" class="public-nav-brand">
            <img src="{{ asset('image/ecocollect-logo.jpg') }}" alt="EcoCollect Logo" class="public-nav-logo-img">
            <span style="font-size:18px;font-weight:700;letter-spacing:1px;"><span style="color:#22C55E;">ECO</span><span style="color:#fff;">COLLECT</span></span>
        </a>
        <ul class="public-nav-links">
            <li><a href="{{ route('landing') }}">Home</a></li>
            <li><a href="{{ route('student.lookup') }}">Student Record</a></li>
            <li><a href="{{ route('landing') }}#top-students">Leaderboard</a></li>
        </ul>
    </div>
</nav>

<div class="public-main" style="padding-top:32px;">

    <a href="{{ route('landing') }}" style="display:inline-flex;align-items:center;gap:6px;color:#22C55E;text-decoration:none;font-size:14px;font-weight:600;margin-bottom:24px;">← Back to Home</a>

    {{-- Student Information --}}
    <div style="background:#fff;border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,0.06);padding:28px 32px;margin-bottom:24px;">
        <div style="display:flex;align-items:center;gap:16px;margin-bottom:20px;">
            <div style="width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,#22C55E,#00AEEF);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:20px;flex-shrink:0;">
                {{ substr($student->first_name, 0, 1) }}{{ substr($student->last_name, 0, 1) }}
            </div>
            <div>
                <h3 style="font-size:22px;font-weight:700;color:#111827;">{{ $student->full_name }}</h3>
                <span style="font-size:12px;font-weight:600;padding:2px 10px;border-radius:10px;background:rgba(34,197,94,0.1);color:#22C55E;">Student Record</span>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;">
            <div><span style="font-size:12px;color:#9CA3AF;display:block;">LRN</span><span style="font-size:15px;font-weight:600;color:#111827;">{{ $student->lrn }}</span></div>
            <div><span style="font-size:12px;color:#9CA3AF;display:block;">Grade Level</span><span style="font-size:15px;font-weight:600;color:#111827;">{{ $student->grade_level }}</span></div>
            <div><span style="font-size:12px;color:#9CA3AF;display:block;">Gender</span><span style="font-size:15px;font-weight:600;color:#111827;">{{ $student->gender ?? '—' }}</span></div>
            <div><span style="font-size:12px;color:#9CA3AF;display:block;">Total Points</span><span style="font-size:15px;font-weight:600;color:#22C55E;">{{ number_format($student->total_points ?? $student->bottleCollections->sum('points_earned')) }}</span></div>
            <div><span style="font-size:12px;color:#9CA3AF;display:block;">Bottle Collections</span><span style="font-size:15px;font-weight:600;color:#111827;">{{ $student->bottleCollections->sum('bottle_count') }}</span></div>
            <div><span style="font-size:12px;color:#9CA3AF;display:block;">Achievements</span><span style="font-size:15px;font-weight:600;color:#111827;">{{ $student->earnedAchievements->count() }}</span></div>
            <div><span style="font-size:12px;color:#9CA3AF;display:block;">Awards / Certificates</span><span style="font-size:15px;font-weight:600;color:#111827;">{{ $student->certificateAwards->count() }}</span></div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px;">

        {{-- Achievement Progress --}}
        <div style="background:#fff;border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,0.06);padding:24px 28px;">
            <h4 style="font-size:15px;font-weight:700;color:#111827;margin-bottom:16px;">Achievement Progress</h4>
            @php
                $totalPoints = $student->total_points ?? $student->bottleCollections->sum('points_earned');
                $goal = 100;
                $progress = min(($totalPoints / $goal) * 100, 100);
            @endphp
            <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                <span style="font-size:13px;color:#6B7280;">Current Points: <strong style="color:#111827;">{{ number_format($totalPoints) }}</strong></span>
                <span style="font-size:13px;color:#6B7280;">Next Goal: <strong style="color:#22C55E;">{{ number_format($goal) }} Points</strong></span>
            </div>
            <div style="background:#E5E7EB;border-radius:8px;height:14px;overflow:hidden;">
                <div style="background:linear-gradient(90deg,#22C55E,#00AEEF);width:{{ $progress }}%;height:100%;border-radius:8px;transition:width 0.5s;"></div>
            </div>
            <div style="text-align:right;margin-top:6px;font-size:12px;color:#9CA3AF;">{{ round($progress) }}% complete</div>
        </div>

        {{-- Bottle Collection Summary --}}
        <div style="background:#fff;border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,0.06);padding:24px 28px;">
            <h4 style="font-size:15px;font-weight:700;color:#111827;margin-bottom:16px;">Bottle Collection Summary</h4>
            @php
                $totalBottles = $student->bottleCollections->sum('bottle_count');
                $totalPointsEarned = $student->bottleCollections->sum('points_earned');
                $collectionCount = $student->bottleCollections->count();
            @endphp
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;text-align:center;">
                <div style="background:rgba(34,197,94,0.08);border-radius:10px;padding:16px 8px;">
                    <div style="font-size:24px;font-weight:700;color:#22C55E;">{{ $totalBottles }}</div>
                    <div style="font-size:12px;color:#6B7280;">Total Bottles</div>
                </div>
                <div style="background:rgba(0,174,239,0.08);border-radius:10px;padding:16px 8px;">
                    <div style="font-size:24px;font-weight:700;color:#00AEEF;">{{ number_format($totalPointsEarned) }}</div>
                    <div style="font-size:12px;color:#6B7280;">Points Earned</div>
                </div>
                <div style="background:rgba(251,191,36,0.08);border-radius:10px;padding:16px 8px;">
                    <div style="font-size:24px;font-weight:700;color:#FBBF24;">{{ $collectionCount }}</div>
                    <div style="font-size:12px;color:#6B7280;">Collections</div>
                </div>
            </div>
        </div>

    </div>

    {{-- Achievements --}}
    <div style="background:#fff;border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,0.06);padding:24px 28px;margin-bottom:24px;">
        <h4 style="font-size:15px;font-weight:700;color:#111827;margin-bottom:16px;">Achievements Earned</h4>
        @if($student->earnedAchievements->count() > 0)
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px;">
            @foreach($student->earnedAchievements as $earned)
            @php $q = $earned->quest; @endphp
            <div style="border:1px solid #E5E7EB;border-radius:12px;padding:16px;">
                <div style="font-size:24px;margin-bottom:8px;">🏅</div>
                <h5 style="font-size:14px;font-weight:600;color:#111827;margin-bottom:4px;">{{ $q->title ?? 'Achievement' }}</h5>
                @if($q->description)<p style="font-size:12px;color:#6B7280;margin-bottom:6px;">{{ $q->description }}</p>@endif
                <div style="font-size:11px;color:#9CA3AF;">
                    @if($q->badge_name)<span>Badge: <strong style="color:#111827;">{{ $q->badge_name }}</strong></span> • @endif
                    <span>Awarded: {{ $earned->awarded_date ? \Carbon\Carbon::parse($earned->awarded_date)->format('M d, Y') : $earned->created_at->format('M d, Y') }}</span>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <p style="font-size:13px;color:#9CA3AF;text-align:center;padding:20px 0;">No achievements earned yet.</p>
        @endif
    </div>

    {{-- Awards / Certificates --}}
    <div style="background:#fff;border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,0.06);padding:24px 28px;margin-bottom:24px;">
        <h4 style="font-size:15px;font-weight:700;color:#111827;margin-bottom:16px;">Awards / Certificates</h4>
        @if($student->certificateAwards->count() > 0)
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;">
            @foreach($student->certificateAwards as $award)
            <div style="border:1px solid #E5E7EB;border-radius:12px;padding:16px;">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                    <span style="font-size:20px;">{{ $award->certificate_type === 'Certificate' ? '📜' : '🏅' }}</span>
                    <h5 style="font-size:14px;font-weight:600;color:#111827;">{{ $award->award_title ?? $award->certificate_title ?? $award->title }}</h5>
                </div>
                @if($award->award_description ?? $award->description)
                <p style="font-size:12px;color:#6B7280;margin-bottom:6px;">{{ $award->award_description ?? $award->description }}</p>
                @endif
                <div style="font-size:11px;color:#9CA3AF;display:flex;justify-content:space-between;align-items:center;">
                    <span>Issued: {{ $award->awarded_date ? $award->awarded_date->format('M d, Y') : ($award->created_at->format('M d, Y')) }}</span>
                    @if($award->certificate_type)<span style="background:rgba(0,174,239,0.1);color:#00AEEF;padding:2px 8px;border-radius:6px;font-weight:600;">{{ $award->certificate_type }}</span>@endif
                </div>
                @if($award->template_file_path)
                <div style="margin-top:10px;padding-top:10px;border-top:1px solid #E5E7EB;">
                    <a href="{{ asset('storage/' . $award->template_file_path) }}" target="_blank" style="font-size:12px;font-weight:600;color:#22C55E;text-decoration:none;">View Certificate →</a>
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @else
        <p style="font-size:13px;color:#9CA3AF;text-align:center;padding:20px 0;">No awards or certificates received yet.</p>
        @endif
    </div>

    {{-- Bottle Collection History --}}
    <div style="background:#fff;border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,0.06);padding:24px 28px;margin-bottom:24px;">
        <h4 style="font-size:15px;font-weight:700;color:#111827;margin-bottom:16px;">Bottle Collection History</h4>
        @if($student->bottleCollections->count() > 0)
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead>
                    <tr>
                        <th style="text-align:left;font-size:11px;text-transform:uppercase;color:#9CA3AF;font-weight:600;padding:8px 6px;border-bottom:1px solid #E5E7EB;">Date</th>
                        <th style="text-align:left;font-size:11px;text-transform:uppercase;color:#9CA3AF;font-weight:600;padding:8px 6px;border-bottom:1px solid #E5E7EB;">Time</th>
                        <th style="text-align:left;font-size:11px;text-transform:uppercase;color:#9CA3AF;font-weight:600;padding:8px 6px;border-bottom:1px solid #E5E7EB;">Bottle Count</th>
                        <th style="text-align:left;font-size:11px;text-transform:uppercase;color:#9CA3AF;font-weight:600;padding:8px 6px;border-bottom:1px solid #E5E7EB;">Points Earned</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($student->bottleCollections->sortByDesc('created_at') as $col)
                    <tr>
                        <td style="padding:8px 6px;border-bottom:1px solid #F3F4F6;color:#111827;">{{ $col->created_at->format('M d, Y') }}</td>
                        <td style="padding:8px 6px;border-bottom:1px solid #F3F4F6;color:#6B7280;">{{ $col->created_at->format('h:i A') }}</td>
                        <td style="padding:8px 6px;border-bottom:1px solid #F3F4F6;color:#111827;font-weight:600;">{{ $col->bottle_count }}</td>
                        <td style="padding:8px 6px;border-bottom:1px solid #F3F4F6;color:#22C55E;font-weight:600;">+{{ $col->points_earned ?? $col->bottle_count }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <p style="font-size:13px;color:#9CA3AF;text-align:center;padding:20px 0;">No bottle collections recorded yet.</p>
        @endif
    </div>

    <div style="text-align:center;padding:12px 0 32px;">
        <a href="{{ route('landing') }}" style="display:inline-flex;align-items:center;gap:6px;padding:10px 28px;background:linear-gradient(135deg,#111827,#1E293B);color:#fff;border-radius:10px;font-size:14px;font-weight:600;text-decoration:none;transition:all 0.3s;">← Back to Home</a>
    </div>

</div>

<footer class="public-footer">
    <div class="footer-inner">
        <div class="footer-left">
            <div style="display:flex;align-items:center;gap:10px;">
                <img src="{{ asset('image/ecocollect-logo.jpg') }}" alt="EcoCollect Logo" class="footer-logo-img">
                <div><span style="font-size:16px;font-weight:700;letter-spacing:1px;"><span style="color:#22C55E;">ECO</span><span style="color:#fff;">COLLECT</span></span><br><span style="font-size:11px;color:rgba(255,255,255,0.5);">Smart Waste Management for a Cleaner Tomorrow</span></div>
            </div>
        </div>
        <div class="footer-center">
            &copy; {{ date('Y') }} EcoCollect. All rights reserved.
        </div>
        <div class="footer-right">
            <div style="font-size:13px;font-weight:600;color:rgba(255,255,255,0.8);margin-bottom:8px;">Quick Links</div>
            <a href="{{ route('landing') }}">Home</a>
            <a href="{{ route('student.lookup') }}">Student Record</a>
            <a href="{{ route('landing') }}#top-students">Leaderboard</a>
        </div>
    </div>
</footer>

</div>

</body>
</html>
