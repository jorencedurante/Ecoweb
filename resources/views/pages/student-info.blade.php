@extends('layouts.admin')

@section('title', 'EcoCollect - Student Info')
@section('page-title', 'Student Information')
@section('page-subtitle', 'View complete student details')

@section('content')
    <a href="{{ route('admin.students') }}" class="back-link">← Back to Students</a>

    <div class="card" style="margin-bottom:24px;">
        <div class="card-body">
            <div style="display:flex;align-items:center;gap:16px;margin-bottom:24px;">
                <div class="admin-avatar" style="width:56px;height:56px;font-size:20px;">
                    {{ substr($student->first_name, 0, 1) }}{{ substr($student->last_name, 0, 1) }}
                </div>
                <div>
                    <h3 style="font-size:20px;font-weight:700;">{{ $student->full_name }}</h3>
                    <p style="color:var(--text-medium);font-size:13px;">{{ $student->grade_level }} • {{ $student->gender }} • ID: {{ $student->id }}</p>
                </div>
            </div>

            <div class="details-grid">
                <div class="detail-item">
                    <div class="detail-label">Student ID</div>
                    <div class="detail-value">{{ $student->id }}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">LRN</div>
                    <div class="detail-value">{{ $student->lrn }}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Full Name</div>
                    <div class="detail-value">{{ $student->full_name }}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Grade Level</div>
                    <div class="detail-value">{{ $student->grade_level }}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Gender</div>
                    <div class="detail-value">{{ $student->gender }}</div>
                </div>
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
                <div class="detail-item">
                    <div class="detail-label">Status</div>
                    <div class="detail-value" style="color:{{ $student->status === 'active' ? 'var(--green)' : 'var(--gray)' }};">{{ ucfirst($student->status) }}</div>
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
