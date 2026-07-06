@extends('layouts.admin')

@section('title', 'EcoCollect - Student Info')
@section('page-title', 'Student Information')
@section('page-subtitle', 'View complete student details')

@section('content')
    <a href="{{ route('admin.students') }}" class="back-link">← Back to Students</a>

    {{-- Header --}}
    <div class="card" style="margin-bottom:24px;">
        <div class="card-body">
            <div style="display:flex;align-items:center;gap:16px;margin-bottom:8px;">
                <div class="admin-avatar" style="width:56px;height:56px;font-size:20px;">
                    {{ substr($student->first_name, 0, 1) }}{{ substr($student->last_name, 0, 1) }}
                </div>
                <div>
                    <h3 style="font-size:20px;font-weight:700;">{{ $student->full_name }}</h3>
                    <p style="color:var(--text-medium);font-size:13px;">
                        LRN: {{ $student->lrn }} •
                        {{ $student->grade_level }} •
                        {{ $student->gender }} •
                        {{ $student->total_points }} pts
                    </p>
                    @if($currentEnrollment)
                    <p style="color:var(--text-medium);font-size:12px;margin-top:2px;">
                        Section: {{ $currentEnrollment->section ?? '—' }} @if($currentEnrollment->school_year) | School Year: {{ $currentEnrollment->school_year }} @endif
                        @if($currentEnrollment->teacher) | Teacher: {{ $currentEnrollment->teacher->name }} @endif
                    </p>
                    @endif
                    @if($student->enrollments->count() > 1)
                    <p style="color:#6b7280;font-size:12px;margin-top:2px;">
                        Assigned to {{ $student->enrollments->count() }} class(es)
                    </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">
        {{-- Basic Information --}}
        <div class="card">
            <div class="card-body">
                <h4 style="font-size:14px;font-weight:600;margin-bottom:16px;padding-bottom:8px;border-bottom:1px solid var(--border);">Basic Information</h4>
                <div class="details-grid">
                    <div class="detail-item">
                        <div class="detail-label">LRN</div>
                        <div class="detail-value">{{ $student->lrn }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Full Name</div>
                        <div class="detail-value">{{ $student->full_name }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">First Name</div>
                        <div class="detail-value">{{ $student->first_name }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Middle Name</div>
                        <div class="detail-value">{{ $student->middle_name ?: '—' }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Last Name</div>
                        <div class="detail-value">{{ $student->last_name }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Gender</div>
                        <div class="detail-value">{{ $student->gender }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Birth Date</div>
                        <div class="detail-value">{{ $student->birth_date ? $student->birth_date->format('F d, Y') : '—' }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Age</div>
                        <div class="detail-value">{{ $student->age ?? '—' }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Grade Level</div>
                        <div class="detail-value">{{ $student->grade_level }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Section</div>
                        <div class="detail-value">{{ optional($currentEnrollment)->section ?: '—' }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">School Year</div>
                        <div class="detail-value">{{ optional($currentEnrollment)->school_year ?: '—' }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Assigned Teacher</div>
                        <div class="detail-value">{{ optional(optional($currentEnrollment)->teacher)->name ?: '—' }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Status</div>
                        <div class="detail-value" style="color:{{ $student->status === 'Active' ? 'var(--green)' : '#ef4444' }};">{{ $student->status }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Address --}}
        <div class="card">
            <div class="card-body">
                <h4 style="font-size:14px;font-weight:600;margin-bottom:16px;padding-bottom:8px;border-bottom:1px solid var(--border);">Address</h4>
                <div class="details-grid">
                    <div class="detail-item" style="grid-column:1/-1;">
                        <div class="detail-label">House # / Street / Sitio / Purok</div>
                        <div class="detail-value">{{ $student->house_street ?: '—' }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Barangay</div>
                        <div class="detail-value">{{ $student->barangay ?: '—' }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Municipality / City</div>
                        <div class="detail-value">{{ $student->municipality_city ?: '—' }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Province</div>
                        <div class="detail-value">{{ $student->province ?: '—' }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Family Information --}}
        <div class="card">
            <div class="card-body">
                <h4 style="font-size:14px;font-weight:600;margin-bottom:16px;padding-bottom:8px;border-bottom:1px solid var(--border);">Family Information</h4>
                <div class="details-grid">
                    <div class="detail-item">
                        <div class="detail-label">Father's Name</div>
                        <div class="detail-value">{{ $student->father_name ?: '—' }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Mother's Maiden Name</div>
                        <div class="detail-value">{{ $student->mother_maiden_name ?: '—' }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Guardian Name</div>
                        <div class="detail-value">{{ $student->guardian_name ?: '—' }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Guardian Relationship</div>
                        <div class="detail-value">{{ $student->guardian_relationship ?: '—' }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Contact Number</div>
                        <div class="detail-value">{{ $student->contact_number ?: '—' }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Other SF1 Details --}}
        <div class="card">
            <div class="card-body">
                <h4 style="font-size:14px;font-weight:600;margin-bottom:16px;padding-bottom:8px;border-bottom:1px solid var(--border);">Other SF1 Details</h4>
                <div class="details-grid">
                    <div class="detail-item">
                        <div class="detail-label">Mother Tongue</div>
                        <div class="detail-value">{{ $student->mother_tongue ?: '—' }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">IP / Ethnic Group</div>
                        <div class="detail-value">{{ $student->ip_ethnic_group ?: '—' }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Religion</div>
                        <div class="detail-value">{{ $student->religion ?: '—' }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Learning Modality</div>
                        <div class="detail-value">{{ $student->learning_modality ?: '—' }}</div>
                    </div>
                    <div class="detail-item" style="grid-column:1/-1;">
                        <div class="detail-label">Remarks</div>
                        <div class="detail-value">{{ $student->remarks ?: '—' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- QR Information --}}
    <div class="card" style="margin-bottom:24px;">
        <div class="card-body">
            <h4 style="font-size:14px;font-weight:600;margin-bottom:16px;padding-bottom:8px;border-bottom:1px solid var(--border);">QR Information</h4>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
                <div>
                    <div class="details-grid">
                        <div class="detail-item">
                            <div class="detail-label">QR Code</div>
                            <div class="detail-value">{{ $student->qr_code ?? '—' }}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Total Points</div>
                            <div class="detail-value">{{ $student->total_points }}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Total Bottles Collected</div>
                            <div class="detail-value">{{ $student->bottleCollections->sum('bottle_count') }}</div>
                        </div>
                    </div>
                </div>
                <div>
                    @if($student->qrCodes->isNotEmpty())
                        @php $qr = $student->qrCodes->first(); @endphp
                        <div style="text-align:center;">
                            <img src="{{ asset('storage/' . $qr->qr_image_path) }}" alt="QR Code" style="max-width:180px;border:1px solid var(--border);border-radius:8px;padding:8px;">
                            <p style="font-size:11px;color:#6b7280;margin-top:6px;">{!! nl2br(e($qr->qr_value)) !!}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Claim History --}}
    @php $claims = $student->claims()->with(['item', 'admin'])->latest()->get(); @endphp
    <div class="card">
        <div class="card-body">
            <h4 style="font-size:15px;font-weight:600;margin-bottom:16px;">Claim History</h4>
            @if($claims->count() > 0)
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:13px;">
                    <thead>
                        <tr>
                            <th style="text-align:left;font-size:11px;text-transform:uppercase;color:#9CA3AF;font-weight:600;padding:8px 6px;border-bottom:1px solid var(--border);">#</th>
                            <th style="text-align:left;font-size:11px;text-transform:uppercase;color:#9CA3AF;font-weight:600;padding:8px 6px;border-bottom:1px solid var(--border);">Item Claimed</th>
                            <th style="text-align:left;font-size:11px;text-transform:uppercase;color:#9CA3AF;font-weight:600;padding:8px 6px;border-bottom:1px solid var(--border);">Points Deducted</th>
                            <th style="text-align:left;font-size:11px;text-transform:uppercase;color:#9CA3AF;font-weight:600;padding:8px 6px;border-bottom:1px solid var(--border);">Points Before</th>
                            <th style="text-align:left;font-size:11px;text-transform:uppercase;color:#9CA3AF;font-weight:600;padding:8px 6px;border-bottom:1px solid var(--border);">Points After</th>
                            <th style="text-align:left;font-size:11px;text-transform:uppercase;color:#9CA3AF;font-weight:600;padding:8px 6px;border-bottom:1px solid var(--border);">Claim Date</th>
                            <th style="text-align:left;font-size:11px;text-transform:uppercase;color:#9CA3AF;font-weight:600;padding:8px 6px;border-bottom:1px solid var(--border);">Claimed By</th>
                            <th style="text-align:left;font-size:11px;text-transform:uppercase;color:#9CA3AF;font-weight:600;padding:8px 6px;border-bottom:1px solid var(--border);">Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($claims as $idx => $c)
                        <tr>
                            <td style="padding:8px 6px;border-bottom:1px solid #F3F4F6;">{{ $idx + 1 }}</td>
                            <td style="padding:8px 6px;border-bottom:1px solid #F3F4F6;font-weight:600;">{{ $c->item_name }}</td>
                            <td style="padding:8px 6px;border-bottom:1px solid #F3F4F6;color:#EF4444;font-weight:600;">-{{ $c->points_deducted }}</td>
                            <td style="padding:8px 6px;border-bottom:1px solid #F3F4F6;">{{ $c->points_before }}</td>
                            <td style="padding:8px 6px;border-bottom:1px solid #F3F4F6;color:var(--green);font-weight:600;">{{ $c->points_after }}</td>
                            <td style="padding:8px 6px;border-bottom:1px solid #F3F4F6;">{{ $c->claim_date->format('Y-m-d') }}</td>
                            <td style="padding:8px 6px;border-bottom:1px solid #F3F4F6;">{{ $c->admin->name ?? 'System' }}</td>
                            <td style="padding:8px 6px;border-bottom:1px solid #F3F4F6;">{{ $c->remarks ?? '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <p style="font-size:13px;color:#9CA3AF;text-align:center;padding:12px 0;">No claimed items yet.</p>
            @endif
        </div>
    </div>
@endsection
