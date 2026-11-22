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
    <style>
        /* QR scan control — additive to the existing manual request form. */
        .reward-lrn-group { display: flex; align-items: center; gap: 8px; }
        .reward-lrn-group input { flex: 1; min-width: 0; }
        .reward-claim-form .qr-scan-btn {
            width: 48px;
            height: 48px;
            margin-top: 0;
            flex: 0 0 48px;
            border: 1px solid #d1d5db;
            border-radius: 14px;
            background: #fff;
            color: #0ea5e9;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: none;
        }
        .reward-claim-form .qr-scan-btn:hover {
            background: #f0f9ff;
            transform: none;
            filter: none;
        }
        .qr-scanner-content { max-width: 520px; width: min(520px, 92vw); }
        .qr-video-wrap {
            position: relative;
            width: 100%;
            aspect-ratio: 4 / 3;
            background: #0f172a;
            border-radius: 12px;
            overflow: hidden;
        }
        .qr-video-wrap video { width: 100%; height: 100%; object-fit: cover; display: block; }
        .qr-scanner-status { margin-top: 12px; font-size: 13px; color: #475569; }
        .qr-scanner-error { margin-top: 8px; font-size: 13px; color: #b91c1c; font-weight: 700; }
        @media (max-width: 480px) {
            .reward-claim-form .qr-scan-btn { flex-basis: 44px; width: 44px; height: 44px; border-radius: 12px; }
        }

        .public-alert.auto-dismiss {
            transition: opacity 0.4s ease, transform 0.4s ease, margin 0.4s ease;
        }
        .public-alert.auto-dismiss.dismissing {
            opacity: 0;
            transform: translateY(-10px);
            margin: 0 !important;
            padding: 0 !important;
            border: none !important;
            height: 0;
            overflow: hidden;
        }
    </style>
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
            <script type="application/json" id="ecFlashMessages">@json([['type' => 'success', 'message' => (string) session('success')]])</script>
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

                    <form method="POST" action="{{ route('public.items.request') }}" class="reward-claim-form" data-item-name="{{ $item->item_name }}">
                        @csrf
                        <input type="hidden" name="claim_item_id" value="{{ $item->id }}">
                        <input type="hidden" name="request_token" value="{{ \Illuminate\Support\Str::uuid() }}">
                        <input type="hidden" name="submitted_card_id" value="{{ $item->id }}">
                        <label for="claim-lrn-{{ $item->id }}">Student LRN</label>
                        <div class="reward-input-group reward-lrn-group">
                            <input type="text" id="claim-lrn-{{ $item->id }}" name="lrn" 
                                value="{{ request('submitted_card_id') == $item->id ? old('lrn') : '' }}" 
                                placeholder="Enter your LRN" required aria-label="Student LRN" data-card-id="{{ $item->id }}">
                            <button type="button" class="qr-scan-btn" data-qr-scan
                                title="Scan Student QR Code" aria-label="Scan Student QR Code">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M14.5 4h-5L7.7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3.7L14.5 4z"/>
                                    <circle cx="12" cy="13.5" r="3.5"/>
                                </svg>
                            </button>
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
                            <input type="number" id="claim-qty-{{ $item->id }}" name="quantity" min="1" max="{{ $stockAvailable ? $item->quantity : 1 }}" value="{{ request('submitted_card_id') == $item->id ? old('quantity', 1) : 1 }}" required aria-label="Quantity" data-points="{{ $pointsPerPiece }}" data-available="{{ (int) $item->quantity }}">
                        </div>
                        <div class="reward-total-box" aria-live="polite">
                            <span>Total Required:</span>
                            <strong class="js-total-points">{{ number_format($pointsPerPiece * $initialQuantity) }}</strong>
                            <span>Points</span>
                        </div>
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

<script src="{{ asset('js/toast.js') }}?v={{ filemtime(public_path('js/toast.js')) }}"></script>
<script src="{{ asset('js/jsQR.js') }}"></script>

<div id="qrScannerModal" class="modal-overlay qr-scanner-modal" role="dialog" aria-modal="true" aria-labelledby="qrScannerTitle" style="display:none;">
    <div class="modal-content qr-scanner-content">
        <div class="modal-header">
            <div>
                <h2 id="qrScannerTitle">Scan Student QR Code</h2>
                <p id="qrScannerMeta" class="subtitle" style="margin-top:4px;"></p>
            </div>
            <button type="button" class="modal-close" id="qrScannerClose" aria-label="Close">&times;</button>
        </div>
        <div class="modal-body">
            <div class="qr-video-wrap">
                <video id="qrVideo" playsinline autoplay muted></video>
                <canvas id="qrCanvas" hidden></canvas>
            </div>
            <p id="qrScannerStatus" class="qr-scanner-status" role="status" aria-live="polite">Starting camera&hellip;</p>
            <p id="qrScannerError" class="qr-scanner-error" role="alert" hidden></p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="qrScannerCancel">Cancel</button>
        </div>
    </div>
</div>

<script>
(function () {
    var POINTS_LOOKUP_URL = @json(route('public.items.points'));
    var LOOKUP_DEBOUNCE_MS = 350;

    document.querySelectorAll('.reward-claim-form').forEach(function (form) {
        var qtyInput = form.querySelector('input[name="quantity"]');
        var lrnInput = form.querySelector('input[name="lrn"]');
        var totalEl = form.querySelector('.js-total-points');
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
        // Only an LRN resolved by the lookup may be submitted. Cleared whenever
        // the LRN changes so a previously selected student cannot be reused.
        var resolvedLrn = null;
        
        // Store LRN state per card to maintain independence
        var cardId = lrnInput.getAttribute('data-card-id') || 'default';
        var cardLrnState = {};
        cardLrnState[cardId] = lrnInput.value;

        function renderLookup(state, points) {
            lookupState = state;
            knownPoints = (state === 'ok') ? points : null;
            if (state !== 'ok') {
                resolvedLrn = null;
            }

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

            if (available <= 0) {
                qtyInput.disabled = true;
                blockSubmit = true;
            } else {
                qtyInput.disabled = false;
                if (isNaN(qty) || qty < 1 || qty > available) {
                    blockSubmit = true;
                }
            }

            // Never submit while a points lookup is still running, and never
            // submit an LRN that has not been resolved for this form yet.
            if (lookupState !== 'ok' || resolvedLrn !== lrnInput.value.trim()) {
                blockSubmit = true;
            }

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
                    if (lrnInput.value.trim() === lrn) {
                        resolvedLrn = lrn;
                        refresh();
                    }
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

            // Drop the previous student's resolved selection before resolving
            // the newly entered LRN. Clear any hidden student id if present.
            resolvedLrn = null;
            var hiddenStudentId = form.querySelector('input[name="student_id"]');
            if (hiddenStudentId) {
                hiddenStudentId.value = '';
            }

            var lrn = lrnInput.value.trim();
            var currentCardId = lrnInput.getAttribute('data-card-id') || 'default';
            
            // Update the stored LRN state for this card
            if (!cardLrnState) cardLrnState = {};
            cardLrnState[currentCardId] = lrn;

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
            var currentCardId = lrnInput.getAttribute('data-card-id') || 'default';
            renderLookup('checking');
            lookupPoints(lrnInput.value.trim());
        }
    });

    // --- Auto-dismiss success notifications ---
    var dismissTimer = null;
    var alertEl = document.getElementById('publicSuccessAlert');

    function dismissSuccessAlert() {
        if (!alertEl) return;
        alertEl.classList.add('dismissing');
        setTimeout(function () {
            if (alertEl && alertEl.parentNode) {
                alertEl.parentNode.removeChild(alertEl);
            }
            alertEl = null;
        }, 400); // match CSS transition duration
    }

    if (alertEl) {
        dismissTimer = setTimeout(dismissSuccessAlert, 4000);
    }
})();
</script>

<script>
// --- QR-based automatic item request (additional to the manual form) ---
(function () {
    var QR_REQUEST_URL = @json(route('public.items.request-qr'));
    var modal = document.getElementById('qrScannerModal');
    if (!modal) return;

    var video = document.getElementById('qrVideo');
    var canvas = document.getElementById('qrCanvas');
    var statusEl = document.getElementById('qrScannerStatus');
    var errorEl = document.getElementById('qrScannerError');
    var metaEl = document.getElementById('qrScannerMeta');

    var stream = null;
    var rafId = null;
    var scanning = false;
    var submitting = false;
    var activeForm = null;

    function setStatus(message) {
        if (statusEl) {
            statusEl.textContent = message;
            statusEl.hidden = false;
        }
    }

    function setError(message) {
        if (!errorEl) return;
        if (!message) {
            errorEl.textContent = '';
            errorEl.hidden = true;
            return;
        }
        errorEl.textContent = message;
        errorEl.hidden = false;
    }

    function stopCamera() {
        scanning = false;
        if (rafId) {
            cancelAnimationFrame(rafId);
            rafId = null;
        }
        if (stream) {
            stream.getTracks().forEach(function (track) { track.stop(); });
            stream = null;
        }
        if (video) video.srcObject = null;
    }

    function closeScanner() {
        stopCamera();
        modal.style.display = 'none';
        modal.classList.remove('show');
        activeForm = null;
        submitting = false;
        setError('');
    }

    function newToken() {
        if (window.crypto && typeof window.crypto.randomUUID === 'function') {
            return window.crypto.randomUUID();
        }
        return 'qr-' + Date.now() + '-' + Math.random().toString(36).slice(2);
    }

    function showSuccess(data) {
        if (!window.EcoToast) return;
        var message = (data && data.message) ? data.message : 'Request submitted successfully!';
        if (data && data.claim) {
            message += ' ' + data.claim.item_name + ' · Quantity ' + data.claim.quantity + ' · Status: Pending Approval';
        }
        window.EcoToast.show('success', message, { key: 'qr-item-request' });
    }

    function submitQrClaim(rawValue) {
        if (submitting || !activeForm) return;
        submitting = true;
        scanning = false;
        setStatus('Submitting request\u2026');
        setError('');
        stopCamera();

        var csrfField = activeForm.querySelector('input[name="_token"]');
        var itemField = activeForm.querySelector('input[name="claim_item_id"]');
        var qtyField = activeForm.querySelector('input[name="quantity"]');
        if (!csrfField || !itemField || !qtyField) {
            setError('Unable to submit the request. Please try again.');
            submitting = false;
            return;
        }

        var payload = new FormData();
        payload.append('_token', csrfField.value);
        payload.append('claim_item_id', itemField.value);
        payload.append('quantity', qtyField.value);
        payload.append('qr_value', rawValue);
        payload.append('request_token', newToken());
        payload.append('submitted_card_id', itemField.value);

        fetch(QR_REQUEST_URL, {
            method: 'POST',
            body: payload,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
        .then(function (response) {
            return response.json().catch(function () { return null; }).then(function (data) {
                return { ok: response.ok, data: data };
            });
        })
        .then(function (result) {
            if (result.ok && result.data && result.data.success) {
                showSuccess(result.data);
                closeScanner();
                return;
            }
            setError((result.data && result.data.message) || 'Unable to submit the request. Please try again.');
            setStatus('Scan again when ready.');
            submitting = false;
            startCamera();
        })
        .catch(function () {
            setError('Unable to submit the request. Please try again.');
            setStatus('Scan again when ready.');
            submitting = false;
            startCamera();
        });
    }

    function scanLoop() {
        if (!scanning) return;
        if (video && canvas && typeof window.jsQR === 'function' && video.readyState === video.HAVE_ENOUGH_DATA) {
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            var context = canvas.getContext('2d', { willReadFrequently: true });
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            var imageData = context.getImageData(0, 0, canvas.width, canvas.height);
            var code = window.jsQR(imageData.data, imageData.width, imageData.height, {
                inversionAttempts: 'dontInvert'
            });
            if (code && code.data) {
                submitQrClaim(code.data);
                return;
            }
        }
        rafId = requestAnimationFrame(scanLoop);
    }

    function startCamera() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            setError('Camera access is required to scan your student QR code.');
            setStatus('');
            return;
        }
        if (typeof window.jsQR !== 'function') {
            setError('QR scanning is not supported in this browser.');
            setStatus('');
            return;
        }

        navigator.mediaDevices.getUserMedia({
            video: { facingMode: { ideal: 'environment' } },
            audio: false
        })
        .then(function (mediaStream) {
            stream = mediaStream;
            video.srcObject = mediaStream;
            return video.play();
        })
        .then(function () {
            scanning = true;
            setStatus('Position the student QR code inside the frame.');
            scanLoop();
        })
        .catch(function () {
            setError('Camera access is required to scan your student QR code.');
            setStatus('');
        });
    }

    function openScanner(form) {
        if (submitting) return;
        activeForm = form;
        setError('');

        var itemName = form.getAttribute('data-item-name') || 'this item';
        var qtyField = form.querySelector('input[name="quantity"]');
        var quantity = qtyField ? qtyField.value : '';
        var totalEl = form.querySelector('.js-total-points');
        var total = totalEl ? totalEl.textContent.trim() : '';
        if (metaEl) {
            metaEl.textContent = 'Selected Item: ' + itemName + ' · Quantity: ' + quantity + ' · Total Required: ' + total + ' Points';
        }

        modal.style.display = 'flex';
        modal.classList.add('show');
        setStatus('Starting camera\u2026');
        startCamera();
    }

    document.querySelectorAll('[data-qr-scan]').forEach(function (button) {
        button.addEventListener('click', function () {
            var form = button.closest('.reward-claim-form');
            if (form) openScanner(form);
        });
    });

    document.getElementById('qrScannerCancel').addEventListener('click', closeScanner);
    document.getElementById('qrScannerClose').addEventListener('click', closeScanner);
    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeScanner();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.style.display === 'flex') closeScanner();
    });
})();
</script>

</body>
</html>