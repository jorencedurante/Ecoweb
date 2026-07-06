<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Items - EcoCollect</title>
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
            <li><a href="{{ route('landing') }}" class="{{ request()->is('/') ? 'active' : '' }}">Home</a></li>
            <li><a href="{{ route('public.items') }}" class="{{ request()->is('items') ? 'active' : '' }}">Items</a></li>
            <li><a href="{{ route('landing') }}#top-students">Leaderboard</a></li>
        </ul>
    </div>
</nav>

<section class="items-page">
    <div class="items-hero">
        <div class="items-hero-content">
            <span class="items-kicker">EcoCollect Rewards</span>
            <h1>Available Items</h1>
            <p>Use your EcoCollect points to request available reward items. Enter your LRN and submit a claim request for admin approval.</p>
        </div>
    </div>

    <div class="items-container">
        @if(session('success'))
            <div class="public-alert success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="public-alert error">{{ $errors->first() }}</div>
        @endif

        <div class="items-grid">
            @forelse($availableItems as $item)
                <article class="reward-card">
                    <div class="reward-card-header">
                        <div class="reward-icon">🎁</div>
                        <span class="reward-stock">{{ $item->quantity }} left</span>
                    </div>

                    <div class="reward-card-body">
                        <h3>{{ $item->item_name }}</h3>
                        <p>{{ $item->description ?? 'No description available.' }}</p>
                        <div class="reward-points-box">
                            <span>Required Points</span>
                            <strong>{{ $item->points_required }} pts</strong>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('public.items.request') }}" class="reward-claim-form">
                        @csrf
                        <input type="hidden" name="claim_item_id" value="{{ $item->id }}">
                        <label>Student LRN</label>
                        <div class="reward-input-group">
                            <input type="text" name="lrn" placeholder="Enter your LRN" required value="{{ old('lrn') }}">
                        </div>
                        <button type="submit">Request Claim</button>
                    </form>
                </article>
            @empty
                <div class="items-empty-state">
                    <div class="empty-icon">📦</div>
                    <h3>No available items right now</h3>
                    <p>Please check again later for new EcoCollect rewards.</p>
                </div>
            @endforelse
        </div>
    </div>
</section>

<footer class="public-footer">
    <div class="footer-container">
        <div class="footer-brand">
            <img src="{{ asset('image/ecocollect-logo.jpg') }}" alt="EcoCollect Logo" class="footer-logo-img">
            <div>
                <h3><span>ECO</span>COLLECT</h3>
                <p>Smart Waste Management for a Cleaner Tomorrow</p>
            </div>
        </div>
        <p class="footer-copy">&copy; {{ date('Y') }} EcoCollect. All rights reserved.</p>
    </div>
</footer>

</div>

</body>
</html>