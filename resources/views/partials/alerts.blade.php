@php
    $ecFlash = [];
    foreach (['success', 'info', 'warning', 'error'] as $ecType) {
        if ($message = session($ecType)) {
            $ecFlash[] = ['type' => $ecType, 'message' => (string) $message];
        }
    }
@endphp

@if(!empty($ecFlash))
    <script type="application/json" id="ecFlashMessages">@json($ecFlash)</script>
@endif

{{-- Field-specific validation errors stay inline near the submitted form. --}}
@if($errors->any())
    <div class="auto-dismiss-alert" role="alert" style="background:rgba(239,83,80,0.08);border:1px solid var(--red);color:var(--red-dark);padding:12px 16px;border-radius:8px;font-size:13px;font-weight:500;margin-bottom:16px;">
        <ul style="margin:0;padding-left:16px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
