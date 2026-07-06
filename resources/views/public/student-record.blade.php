<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $student->full_name }} - EcoCollect Record</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        .record-nav {
            background: var(--sidebar-bg);
            padding: 0 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 64px;
        }
        .record-nav .nav-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #fff;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 1px;
            text-decoration: none;
        }
        .record-nav .nav-logo {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--green), var(--blue));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 800;
            color: #fff;
        }
        .record-nav .nav-links {
            display: flex;
            align-items: center;
            gap: 16px;
            list-style: none;
        }
        .record-nav .nav-links a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.2s;
        }
        .record-nav .nav-links a:hover { color: #fff; }
        .record-hero {
            background: linear-gradient(135deg, var(--green), var(--blue));
            padding: 32px 40px;
            color: #fff;
        }
        .record-hero h1 { font-size: 26px; font-weight: 700; }
        .record-hero p { opacity: 0.85; font-size: 14px; margin-top: 4px; }
        .record-section { padding: 32px 40px; max-width: 1000px; margin: 0 auto; }
        .record-card {
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-bottom: 24px;
        }
        .record-card .rc-header {
            padding: 16px 24px;
            font-weight: 600;
            font-size: 15px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .record-card .rc-body { padding: 20px 24px; }
        .summary-row {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }
        .summary-item {
            flex: 1;
            min-width: 130px;
            text-align: center;
            padding: 16px;
            background: #F9FAFB;
            border-radius: 8px;
        }
        .summary-item .si-value { font-size: 24px; font-weight: 700; color: var(--sidebar-bg); }
        .summary-item .si-label { font-size: 12px; color: var(--text-medium); margin-top: 2px; }
        .detail-table { width: 100%; font-size: 13px; border-collapse: collapse; }
        .detail-table th { text-align: left; font-size: 11px; text-transform: uppercase; color: var(--text-light); font-weight: 600; padding: 8px 8px; border-bottom: 1px solid var(--border); }
        .detail-table td { padding: 8px 8px; border-bottom: 1px solid var(--border); }
        .detail-table tr:last-child td { border-bottom: none; }
        .achievement-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            background: #F0F0F0;
            border-radius: 20px;
            font-size: 13px;
            margin: 0 6px 6px 0;
        }
        .award-card-mini {
            padding: 14px;
            border: 1px solid var(--border);
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 4px solid var(--blue);
        }
        .award-card-mini h5 { font-size: 14px; font-weight: 600; }
        .award-card-mini .award-meta { font-size: 12px; color: var(--text-light); margin-top: 2px; }
        .award-card-mini .award-desc { font-size: 13px; color: var(--text-medium); margin-top: 6px; }
        .record-footer {
            background: var(--sidebar-bg);
            color: rgba(255,255,255,0.6);
            text-align: center;
            padding: 20px 40px;
            font-size: 13px;
        }
        .record-footer strong { color: rgba(255,255,255,0.8); }
        .empty-msg { font-size: 13px; color: var(--text-light); font-style: italic; }
        .pagination-info { font-size: 13px; color: var(--text-medium); margin-top: 12px; }
        .btn-print {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            background: var(--blue);
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s;
        }
        .btn-print:hover { background: var(--blue-dark); }
        @media (max-width: 640px) {
            .record-nav { padding: 0 16px; }
            .record-hero { padding: 24px 16px; }
            .record-section { padding: 24px 16px; }
            .record-card .rc-body { padding: 16px; }
        }
    </style>
    <link rel="icon" type="image/jpeg" href="{{ asset('image/Page-logo.jpg') }}">
    <link rel="shortcut icon" type="image/jpeg" href="{{ asset('image/Page-logo.jpg') }}">
    <link rel="apple-touch-icon" href="{{ asset('image/Page-logo.jpg') }}">
</head>
<body>
    <nav class="record-nav">
        <a href="{{ route('landing') }}" class="nav-brand">
            <img src="{{ asset('image/ecocollect-logo.jpg') }}" alt="EcoCollect Logo" style="height:32px;width:auto;object-fit:contain;">
            ECOCOLLECT
        </a>
        <ul class="nav-links">
            <li><a href="{{ route('landing') }}">← Back to Home</a></li>
            <li><a href="{{ route('landing') }}#search">Search Another LRN</a></li>
        </ul>
    </nav>

    <div class="record-hero">
        <h1>{{ $student->full_name }}</h1>
        <p>LRN: {{ $student->lrn }} &bull; {{ $student->grade_level }} &bull; {{ $student->gender }} &bull; Status: {{ ucfirst($student->status) }}</p>
    </div>

    <div class="record-section">
        <div class="record-card">
            <div class="rc-header">📊 Points Summary</div>
            <div class="rc-body">
                <div class="summary-row">
                    <div class="summary-item">
                        <div class="si-value">{{ $student->bottleCollections->sum('bottle_count') }}</div>
                        <div class="si-label">Total Bottles</div>
                    </div>
                    <div class="summary-item">
                        <div class="si-value">{{ $student->bottleCollections->sum('points_earned') }}</div>
                        <div class="si-label">Total Points</div>
                    </div>
                    @if($student->bottleCollections->count() > 0)
                        <div class="summary-item">
                            <div class="si-value">{{ $student->bottleCollections->first()->collection_date }}</div>
                            <div class="si-label">Latest Collection</div>
                        </div>
                        <div class="summary-item">
                            <div class="si-value">{{ $student->bottleCollections->first()->bottle_count }}</div>
                            <div class="si-label">Latest Bottles</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="record-card">
            <div class="rc-header">🧴 Bottle Collection Records</div>
            <div class="rc-body">
                @if($bottleCollections->count() > 0)
                    <table class="detail-table">
                        <thead>
                            <tr><th>Date</th><th>Time</th><th>Bottle Count</th><th>Points Earned</th></tr>
                        </thead>
                        <tbody>
                            @foreach($bottleCollections as $c)
                                <tr>
                                    <td>{{ $c->collection_date }}</td>
                                    <td>{{ $c->collection_time ? \Carbon\Carbon::parse($c->collection_time)->format('h:i A') : '—' }}</td>
                                    <td>{{ $c->bottle_count }}</td>
                                    <td>{{ $c->points_earned }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="pagination-info">
                        Showing {{ $bottleCollections->firstItem() ?? 0 }} to {{ $bottleCollections->lastItem() ?? 0 }} of {{ $bottleCollections->total() }} records
                    </div>
                @else
                    <div class="empty-msg">No bottle collection records yet.</div>
                @endif
            </div>
        </div>

        <div class="record-card">
            <div class="rc-header">🏆 Achievements</div>
            <div class="rc-body">
                @if($achievements->count() > 0)
                    @foreach($achievements as $a)
                        <span class="achievement-badge">
                            {{ $a->badge_icon ?? '⭐' }} {{ $a->title }}
                            @if($a->milestone) ({{ $a->milestone }} milestone) @endif
                            @if($a->achieved_date) &bull; {{ $a->achieved_date }} @endif
                        </span>
                    @endforeach
                @else
                    <div class="empty-msg">No achievements yet.</div>
                @endif
            </div>
        </div>

        <div class="record-card">
            <div class="rc-header">🎖️ Awards & Certificates</div>
            <div class="rc-body">
                @if($awards->count() > 0)
                    @foreach($awards as $aw)
                        <div class="award-card-mini">
                            <h5>{{ $aw->award_title }}</h5>
                            <div class="award-meta">{{ $aw->awarded_date }}</div>
                            @if($aw->award_description)
                                <div class="award-desc">{{ $aw->award_description }}</div>
                            @endif
                        </div>
                    @endforeach
                @else
                    <div class="empty-msg">No awards yet.</div>
                @endif
            </div>
        </div>
    </div>

    <footer class="record-footer">
        &copy; {{ date('Y') }} <strong>ECOCOLLECT</strong> &mdash; Smart Waste Management for a Cleaner Tomorrow.
    </footer>
</body>
</html>
