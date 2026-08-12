@extends('layouts.admin')

@section('title', 'EcoCollect - Student Awards')
@section('page-title', 'Student Awards')
@section('page-subtitle', 'Awards and certificates earned')

@section('content')
    <a href="{{ route('admin.students') }}" class="back-link">← Back to Students</a>

    <div class="card" style="margin-bottom:24px;">
        <div class="card-body">
            <div style="display:flex;align-items:center;gap:16px;">
                <div class="admin-avatar" style="width:48px;height:48px;font-size:18px;">
                    {{ substr($student->first_name, 0, 1) }}{{ substr($student->last_name, 0, 1) }}
                </div>
                <div>
                    <h3 style="font-size:18px;font-weight:700;">{{ $student->full_name }}</h3>
                    <p style="color:var(--text-medium);font-size:13px;">{{ $student->grade_level }} · LRN: {{ $student->lrn ?? 'N/A' }}</p>
                </div>
            </div>
        </div>
    </div>

    <h4 style="font-size:15px;font-weight:600;margin-bottom:12px;">Awards & Certificates</h4>
    <div class="award-grid">
        @forelse($awards as $award)
        <div class="award-card">
            <div class="card-icon">🏅</div>
            <h4>{{ $award->certificate_title ?? $award->award_title }}</h4>
            <div class="award-meta">
                {{ $award->certificate_type ?? 'Certificate' }}
                @if($award->school_year) · S.Y. {{ $award->school_year }} @endif
                · {{ $award->awarded_date->format('M d, Y') }}
            </div>
            @if($award->award_description)
            <div class="award-desc" style="margin-top:6px;">{{ $award->award_description }}</div>
            @endif
            @if($award->signed_by)
            <div style="margin-top:8px;font-size:12px;color:var(--text-medium);">
                Signed by: <strong>{{ $award->signed_by }}</strong>
                @if($award->signatory_position) ({{ $award->signatory_position }}) @endif
            </div>
            @endif
            @if($award->issuer)
            <div style="margin-top:4px;font-size:12px;color:var(--text-light);">
                Awarded by: {{ $award->issuer->name }}
            </div>
            @endif
            @if($award->remarks)
            <div style="margin-top:6px;font-size:12px;color:var(--text-light);font-style:italic;">{{ $award->remarks }}</div>
            @endif
            <div style="margin-top:10px;display:flex;gap:6px;flex-wrap:wrap;">
                @php $filePath = $award->template_file ?? $award->certificate_file ?? $award->template_file_path; @endphp
                @if($filePath)
                <a href="{{ route('admin.certificate.download', $award->id) }}" class="btn btn-view btn-xs" target="_blank">View</a>
                <a href="{{ route('admin.certificate.download', $award->id) }}" class="btn btn-achievement btn-xs" download>Download</a>
                @endif
                <a href="{{ route('admin.certificate.print', $award->id) }}" class="btn btn-xs" style="background:#6B7280;color:#fff;" target="_blank">Print</a>
            </div>
        </div>
        @empty
        <div class="card" style="padding:30px;text-align:center;color:var(--text-light);">
            No awards or certificates yet.
        </div>
        @endforelse
    </div>
@endsection
