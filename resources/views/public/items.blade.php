<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Items - EcoCollect</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
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
            <div class="public-alert success" role="status">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="public-alert error" role="alert">{{ $errors->first() }}</div>
        @endif

        <div class="items-grid">
            @forelse($availableItems as $item)
                @php
                    $stockAvailable = (int) $item->quantity > 0;
                    $pointsPerPiece = (int) $item->points_required;
                    $initialQuantity = (int) old('quantity', 1);
                    if ($initialQuantity < 1) {
                        $initialQuantity = 1;
                    }
                @endphp
                <article class="reward-card">
                    <div class="reward-card-header">
                        <div class="reward-icon">🎁</div>
                        @if($stockAvailable)
                            <span class="reward-stock">{{ number_format($item->quantity) }} Pieces Available</span>
                        @else
                            <span class="reward-stock out-of-stock">Out of Stock</span>
                        @endif
                    </div>

                    <div class="reward-card-body">
                        <h3>{{ $item->item_name }}</h3>
                        <p>{{ $item->description ?? 'No description available.' }}</p>
                        <div class="reward-points-box">
                            <span>Required Points</span>
                            <strong>{{ number_format($pointsPerPiece) }} Points</strong>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('public.items.request') }}" class="reward-claim-form">
                        @csrf
                        <input type="hidden" name="claim_item_id" value="{{ $item->id }}">
                        <input type="hidden" name="request_token" value="{{ \Illuminate\Support\Str::uuid() }}">
                        <label for="claim-lrn-{{ $item->id }}">Student LRN</label>
                        <div class="reward-input-group">
                            <input type="text" id="claim-lrn-{{ $item->id }}" name="lrn" placeholder="Enter your LRN" required value="{{ old('lrn') }}" aria-label="Student LRN">
                        </div>
                        <div class="points-balance-panel js-points-panel" hidden>
                            <div class="points-balance-row">
                                <span class="points-balance-icon" aria-hidden="true">🪙</span>
                                <span class="points-balance-label">Available Points:</span>
                                <strong class="points-balance-value js-points-value">0</strong>
                            </div>
                            <p class="points-shortfall js-points-shortfall" hidden>Insufficient points for this request.</p>
                        </div>
                        <p class="points-status js-points-status" role="status" aria-live="polite" hidden></p>
                        <label for="claim-qty-{{ $item->id }}">Quantity</label>
                        <div class="reward-input-group">
                            <input type="number" id="claim-qty-{{ $item->id }}" name="quantity" min="1" max="{{ $stockAvailable ? $item->quantity : 1 }}" value="{{ old('quantity', 1) }}" required aria-label="Quantity" data-points="{{ $pointsPerPiece }}" data-available="{{ (int) $item->quantity }}">
                        </div>
                        <div class="reward-total-box" aria-live="polite">
                            <span>Total Required:</span>
                            <strong class="js-total-points">{{ number_format($pointsPerPiece * $initialQuantity) }}</strong>
                            <span>Points</span>
                        </div>
                        <p class="reward-form-hint js-qty-hint" role="alert"></p>
                        <button type="submit" aria-label="Request Claim for {{ $item->item_name }}" @unless($stockAvailable) disabled @endunless>Request Claim</button>
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

<script>
(function () {
    var POINTS_LOOKUP_URL = @json(route('public.items.points'));
    var LOOKUP_DEBOUNCE_MS = 350;

    document.querySelectorAll('.reward-claim-form').forEach(function (form) {
        var qtyInput = form.querySelector('input[name="quantity"]');
        var lrnInput = form.querySelector('input[name="lrn"]');
        var totalEl = form.querySelector('.js-total-points');
        var hintEl = form.querySelector('.js-qty-hint');
        var submitBtn = form.querySelector('button[type="submit"]');
        var pointsPanel = form.querySelector('.js-points-panel');
        var pointsValue = form.querySelector('.js-points-value');
        var pointsShortfall = form.querySelector('.js-points-shortfall');
        var pointsStatus = form.querySelector('.js-points-status');

        if (!qtyInput || !totalEl || !submitBtn || !lrnInput) {
            return;
        }

        var perPiece = parseInt(qtyInput.getAttribute('data-points'), 10);
        var available = parseInt(qtyInput.getAttribute('data-available'), 10);

        if (isNaN(perPiece)) { perPiece = 0; }
        if (isNaN(available)) { available = 0; }

        // Lookup state for this card only.
        var lookupState = 'idle'; // idle | checking | ok | notfound | error
        var knownPoints = null;
        var lookupTimer = null;
        var lookupToken = 0;

        function renderLookup(state, points) {
            lookupState = state;
            knownPoints = (state === 'ok') ? points : null;

            if (state === 'ok') {
                if (pointsPanel) {
                    pointsValue.textContent = Number(points).toLocaleString('en-US');
                    pointsPanel.hidden = false;
                }
                if (pointsStatus) {
                    pointsStatus.hidden = true;
                    pointsStatus.textContent = '';
                }
            } else if (state === 'checking') {
                if (pointsPanel) { pointsPanel.hidden = true; }
                if (pointsStatus) {
                    pointsStatus.hidden = false;
                    pointsStatus.className = 'points-status js-points-status';
                    pointsStatus.textContent = 'Checking points...';
                }
            } else if (state === 'notfound') {
                if (pointsPanel) { pointsPanel.hidden = true; }
                if (pointsStatus) {
                    pointsStatus.hidden = false;
                    pointsStatus.className = 'points-status js-points-status';
                    pointsStatus.textContent = 'Student LRN not found.';
                }
            } else if (state === 'error') {
                if (pointsPanel) { pointsPanel.hidden = true; }
                if (pointsStatus) {
                    pointsStatus.hidden = false;
                    pointsStatus.className = 'points-status js-points-status points-status-error';
                    pointsStatus.textContent = 'Unable to check points. Please try again.';
                }
            } else {
                if (pointsPanel) { pointsPanel.hidden = true; }
                if (pointsStatus) {
                    pointsStatus.hidden = true;
                    pointsStatus.textContent = '';
                }
            }

            refresh();
        }

        function refresh() {
            var raw = qtyInput.value.trim();
            var qty = /^[0-9]+$/.test(raw) ? parseInt(raw, 10) : NaN;
            var total = (!isNaN(qty) && qty >= 1) ? qty * perPiece : 0;

            totalEl.textContent = Number(total).toLocaleString('en-US');

            // Informational shortfall warning (server validation stays authoritative).
            if (pointsShortfall) {
                pointsShortfall.hidden = !(lookupState === 'ok' && knownPoints !== null && total > knownPoints);
            }

            var blockSubmit = false;
            var message = '';

            if (available <= 0) {
                qtyInput.disabled = true;
                message = 'Out of Stock.';
                blockSubmit = true;
            } else {
                qtyInput.disabled = false;

                if (isNaN(qty) || qty < 1) {
                    message = 'Enter a quantity of at least 1.';
                    blockSubmit = true;
                } else if (qty > available) {
                    message = 'Only ' + available + ' piece(s) available.';
                    blockSubmit = true;
                }
            }

            // Never submit while a points lookup is still running.
            if (lookupState === 'checking') {
                blockSubmit = true;
            }

            hintEl.textContent = message;
            submitBtn.disabled = blockSubmit;
        }

        function lookupPoints(lrn) {
            var token = ++lookupToken;

            fetch(POINTS_LOOKUP_URL + '?lrn=' + encodeURIComponent(lrn), {
                headers: { 'Accept': 'application/json' }
            })
            .then(function (response) {
                return response.json()
                    .catch(function () {
                        return null; // non-JSON body (e.g. HTML error page)
                    })
                    .then(function (data) {
                        if (token !== lookupToken) {
                            return null; // outdated response
                        }

                        if (response.ok && data && data.success && typeof data.available_points !== 'undefined') {
                            return { state: 'ok', points: Number(data.available_points) || 0 };
                        }

                        if (response.status === 404 || (data && data.success === false && data.message === 'Student LRN not found.')) {
                            return { state: 'notfound' };
                        }

                        return { state: 'error' };
                    });
            })
            .then(function (result) {
                if (result === null) {
                    return; // outdated response
                }

                if (result.state === 'ok') {
                    renderLookup('ok', result.points);
                } else if (result.state === 'notfound') {
                    renderLookup('notfound');
                } else {
                    renderLookup('error');
                }
            })
            .catch(function () {
                if (token !== lookupToken) {
                    return;
                }
                renderLookup('error');
            });
        }

        lrnInput.addEventListener('input', function () {
            lookupToken++; // invalidate any in-flight response
            clearTimeout(lookupTimer);

            var lrn = lrnInput.value.trim();

            if (!lrn) {
                renderLookup('idle');
                return;
            }

            // Immediately clear the previous student's balance while searching.
            renderLookup('checking');

            lookupTimer = setTimeout(function () {
                lookupPoints(lrn);
            }, LOOKUP_DEBOUNCE_MS);
        });

        qtyInput.addEventListener('input', refresh);
        qtyInput.addEventListener('change', refresh);
        refresh();

        // When an LRN is already prefilled (e.g. right after a claim request
        // redirect), fetch the student's current balance right away so the
        // panel never shows a stale points value.
        if (lrnInput.value.trim()) {
            renderLookup('checking');
            lookupPoints(lrnInput.value.trim());
        }
    });
})();
</script>

</body>
</html>