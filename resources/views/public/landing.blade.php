<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoCollect - Smart Waste Management for Schools</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="public-landing-page">

<div class="public-landing">

<nav class="public-nav">
    <div class="public-nav-inner">
        <a href="{{ route('landing') }}" class="public-nav-brand">
            <span class="public-nav-logo">EC</span>
            <span style="font-size:18px;font-weight:700;letter-spacing:1px;"><span style="color:#22C55E;">ECO</span><span style="color:#fff;">COLLECT</span></span>
        </a>
        <ul class="public-nav-links">
            <li><a href="{{ route('landing') }}">Home</a></li>
            <li><a href="{{ route('student.lookup') }}">Student Record</a></li>
            <li><a href="#top-students">Leaderboard</a></li>
        </ul>
    </div>
</nav>

<section class="hero-section">
    <div class="hero-inner">
        <div class="hero-left">
            <div class="hero-logo-circle">
                <span style="font-size:32px;font-weight:800;">EC</span>
            </div>
            <div class="hero-brand-text">
                <span style="color:#22C55E;font-size:28px;font-weight:800;letter-spacing:2px;">ECO</span><span style="color:#fff;font-size:28px;font-weight:700;letter-spacing:2px;">COLLECT</span>
            </div>
            <div class="hero-subtitle">Smart Waste Management for Schools</div>
            <h1 class="hero-heading">Welcome to <span style="color:#22C55E;">EcoCollect</span></h1>
            <p class="hero-desc">Empowering students. Reducing waste. Building a cleaner tomorrow.</p>
        </div>
        <div class="hero-right">
            <div class="hero-illustration">
                <div class="hill-shape"></div>
                <div class="school-building">
                    <div class="school-roof"></div>
                    <div class="school-body">
                        <div class="school-window"></div>
                        <div class="school-window"></div>
                        <div class="school-door"></div>
                    </div>
                </div>
                <div class="tree tree-1"></div>
                <div class="tree tree-2"></div>
                <div class="bush bush-1"></div>
                <div class="bush bush-2"></div>
                <div class="recycle-icon">♻️</div>
                <div class="trash-bin bin-plastic"><span>PLASTIC</span></div>
                <div class="trash-bin bin-paper"><span>PAPER</span></div>
                <div class="trash-bin bin-others"><span>OTHERS</span></div>
            </div>
        </div>
    </div>
</section>

<div class="public-main">
    <section class="search-card-section">
        <div class="search-card">
            <div class="search-card-left">
                <div class="search-icon-circle">🔍</div>
                <h3>Search Student Record</h3>
                <p>Enter your LRN to view your points, bottle collections, achievements, and awards.</p>
            </div>
            <div class="search-card-right">
                <form method="GET" action="{{ route('student.lookup') }}" class="student-search-form">
                    <input type="text" name="lrn" placeholder="Enter your LRN" value="{{ request('lrn') }}" required>
                    <button type="submit">🔍 Search</button>
                </form>
            </div>
        </div>
    </section>

    @if(isset($student))
        <section class="preview-section">
            <div class="preview-card">
                <div class="preview-avatar">{{ substr($student->first_name, 0, 1) }}{{ substr($student->last_name, 0, 1) }}</div>
                <div class="preview-info">
                    <div class="preview-name">{{ $student->full_name }}</div>
                    <span class="preview-badge">Student Record</span>
                    <div class="preview-details">
                        <span><strong>LRN:</strong> {{ $student->lrn }}</span>
                        <span><strong>Grade Level:</strong> {{ $student->grade_level }}</span>
                    </div>
                    <a href="{{ route('landing.student.details', $student->lrn) }}" class="preview-details-btn">View Details</a>
                </div>
                <div class="preview-stats">
                    <div class="preview-stat"><div class="ps-value">{{ number_format($student->bottleCollections->sum('points_earned')) }}</div><div class="ps-label">Total Points</div></div>
                    <div class="ps-divider"></div>
                    <div class="preview-stat"><div class="ps-value">{{ $student->bottleCollections->sum('bottle_count') }}</div><div class="ps-label">Bottle Collections</div></div>
                    <div class="ps-divider"></div>
                    <div class="preview-stat"><div class="ps-value">{{ $student->earnedAchievements->count() }}</div><div class="ps-label">Achievements</div></div>
                    <div class="ps-divider"></div>
                    <div class="preview-stat"><div class="ps-value">{{ $student->certificateAwards->count() }}</div><div class="ps-label">Awards / Certificates</div></div>
                </div>
            </div>
        </section>
    @elseif(session('error'))
        <section class="preview-section">
            <div class="preview-card" style="text-align:center;padding:30px;color:#EF4444;">
                <p style="font-size:15px;font-weight:500;">{{ session('error') }}</p>
            </div>
        </section>
    @elseif($errors->any())
        <section class="preview-section">
            <div class="preview-card" style="text-align:center;padding:30px;color:#EF4444;">
                <p style="font-size:15px;font-weight:500;">{{ $errors->first('lrn') }}</p>
            </div>
        </section>
    @else
        <section class="preview-section">
            <div class="preview-card">
                <div class="preview-avatar">JD</div>
                <div class="preview-info">
                    <div class="preview-name">Juan Dela Cruz</div>
                    <span class="preview-badge">Sample Record</span>
                    <div class="preview-details">
                        <span><strong>LRN:</strong> 123456789012</span>
                        <span><strong>Grade Level:</strong> Grade 7</span>
                    </div>
                </div>
                <div class="preview-stats">
                    <div class="preview-stat"><div class="ps-value">1,250</div><div class="ps-label">Total Points</div></div>
                    <div class="ps-divider"></div>
                    <div class="preview-stat"><div class="ps-value">86</div><div class="ps-label">Bottle Collections</div></div>
                    <div class="ps-divider"></div>
                    <div class="preview-stat"><div class="ps-value">12</div><div class="ps-label">Achievements</div></div>
                    <div class="ps-divider"></div>
                    <div class="preview-stat"><div class="ps-value">5</div><div class="ps-label">Awards / Certificates</div></div>
                </div>
            </div>
        </section>
    @endif

    <section class="top-students-section" id="top-students">
        <div class="ts-header">
            <div class="ts-trophy">🏆</div>
            <h2>Top 10 Overall Students</h2>
        </div>
        <div class="ts-grid">
            <div class="ts-card">
                <div class="ts-card-title">📅 Current Quarter</div>
                <table class="ts-table">
                    <thead><tr><th>Rank</th><th>Student Name</th><th>Points</th><th>Bottle Collections</th></tr></thead>
                    <tbody>
                        @forelse($currentQuarterRankings as $r)
                            <tr>
                                <td><span class="rank-badge {{ $r['rank'] <= 3 ? 'rank-top' : '' }}">{{ $r['rank'] }}</span></td>
                                <td class="ts-name">{{ $r['name'] }}</td>
                                <td>{{ number_format($r['points']) }}</td>
                                <td>{{ $r['bottles'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" style="text-align:center;color:#9CA3AF;padding:20px;">No ranking data available yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="ts-card">
                <div class="ts-card-title">📅 Previous Quarter</div>
                <table class="ts-table">
                    <thead><tr><th>Rank</th><th>Student Name</th><th>Points</th><th>Bottle Collections</th></tr></thead>
                    <tbody>
                        @forelse($previousQuarterRankings as $r)
                            <tr>
                                <td><span class="rank-badge {{ $r['rank'] <= 3 ? 'rank-top' : '' }}">{{ $r['rank'] }}</span></td>
                                <td class="ts-name">{{ $r['name'] }}</td>
                                <td>{{ number_format($r['points']) }}</td>
                                <td>{{ $r['bottles'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" style="text-align:center;color:#9CA3AF;padding:20px;">No ranking data available yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="features-section">
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">🧴</div>
                <h4>Bottle Collection Monitoring</h4>
                <p>Track and monitor plastic bottle collections in real-time to promote sustainable habits.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">⭐</div>
                <h4>Student Points Tracking</h4>
                <p>Earn points for every contribution and see your progress on the leaderboard.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🏆</div>
                <h4>Achievements & Awards</h4>
                <p>Unlock achievements and earn awards as you make a positive impact on the environment.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📱</div>
                <h4>QR Code Identification</h4>
                <p>Secure and fast student identification using QR code scanning.</p>
            </div>
        </div>
    </section>
</div>

<footer class="public-footer">
    <div class="footer-inner">
        <div class="footer-left">
            <div style="display:flex;align-items:center;gap:10px;">
                <span style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#22C55E,#00AEEF);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;color:#fff;flex-shrink:0;">EC</span>
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
            <a href="#top-students">Leaderboard</a>
        </div>
    </div>
</footer>

</div>

</body>
</html>
