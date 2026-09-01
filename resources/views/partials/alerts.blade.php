@if(session('success'))
    <div class="auto-dismiss-alert" role="alert" style="background:rgba(0,200,83,0.08);border:1px solid #22c55e;color:#166534;padding:12px 16px;border-radius:8px;font-size:14px;font-weight:500;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
        <span aria-hidden="true">✅</span>
        <span>{{ session('success') }}</span>
    </div>
@endif

@if(session('error'))
    <div class="auto-dismiss-alert" role="alert" style="background:rgba(239,83,80,0.08);border:1px solid var(--red);color:var(--red-dark);padding:12px 16px;border-radius:8px;font-size:14px;font-weight:500;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
        <span aria-hidden="true">❌</span>
        <span>{{ session('error') }}</span>
    </div>
@endif

@if(session('info'))
    <div class="auto-dismiss-alert" role="status" style="background:rgba(0,174,239,0.08);border:1px solid var(--blue);color:var(--blue-dark);padding:12px 16px;border-radius:8px;font-size:14px;font-weight:500;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
        <span aria-hidden="true">ℹ️</span>
        <span>{{ session('info') }}</span>
    </div>
@endif

@if($errors->any())
    <div class="auto-dismiss-alert" role="alert" style="background:rgba(239,83,80,0.08);border:1px solid var(--red);color:var(--red-dark);padding:12px 16px;border-radius:8px;font-size:13px;font-weight:500;margin-bottom:16px;">
        <ul style="margin:0;padding-left:16px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
