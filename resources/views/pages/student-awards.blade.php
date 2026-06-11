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
                    <p style="color:var(--text-medium);font-size:13px;">{{ $student->grade_level }} • {{ $student->id }}</p>
                </div>
            </div>
        </div>
    </div>

    <h4 style="font-size:15px;font-weight:600;margin-bottom:12px;">🎖️ Awards & Certificates</h4>
    <div class="award-grid">
        @forelse($awards as $award)
        <div class="award-card">
            <div class="card-icon">🏅</div>
            <h4>{{ $award->award_title }}</h4>
            <div class="award-meta">{{ $award->certificate_type ?? 'Certificate' }} • {{ $award->awarded_date }}</div>
            <div class="award-desc" style="margin-top:6px;">{{ $award->award_description }}</div>
        </div>
        @empty
        <div class="card" style="padding:30px;text-align:center;color:var(--text-light);">
            No awards or certificates yet.
        </div>
        @endforelse
    </div>
@endsection
