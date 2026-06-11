@extends('layouts.admin')

@section('title', 'EcoCollect - Certificate Award')
@section('page-title', 'Certificate Award')
@section('page-subtitle', 'Manage certificates and awards')

@section('content')
    @if(session('success'))
    <div class="alert-success show">✅ {{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div style="background:rgba(239,83,80,0.1);border:1px solid var(--red);color:var(--red-dark);padding:12px 16px;border-radius:var(--radius-sm);font-size:14px;font-weight:500;margin-bottom:16px;">❌ {{ session('error') }}</div>
    @endif

    <div style="display:flex;justify-content:flex-end;margin-bottom:20px;">
        <button class="btn btn-primary" data-modal-target="addAwardModal">+ Add Award</button>
    </div>

    @if($latestAward)
    <div class="certificate-card" style="margin-bottom:24px;">
        <div class="cert-border" style="position:relative;overflow:hidden;
            @if($latestAward->template_file_path && in_array(pathinfo($latestAward->template_file_path, PATHINFO_EXTENSION), ['jpg','jpeg','png']))
                background-image:url('{{ asset('storage/'.$latestAward->template_file_path) }}');background-size:cover;background-position:center;
            @endif">
            @if($latestAward->template_file_path && in_array(pathinfo($latestAward->template_file_path, PATHINFO_EXTENSION), ['jpg','jpeg','png']))
            <div style="position:absolute;inset:0;background:rgba(255,255,255,0.85);z-index:0;"></div>
            @endif
            <div style="position:relative;z-index:1;">
                <div class="cert-logo">EC</div>
                <h2>{{ $latestAward->certificate_title ?? $latestAward->award_title }}</h2>
                <p class="cert-subtitle">Presented to</p>
                <div class="cert-student">{{ $latestAward->student->full_name }}</div>
                <p class="cert-desc">{{ $latestAward->award_description ?? 'For outstanding achievement.' }}</p>
                <div style="display:flex;justify-content:space-between;margin-top:30px;padding:0 20px;font-size:12px;color:var(--text-medium);">
                    <div>{{ $latestAward->school_principal_name ?? '___________________' }}<br>School Principal</div>
                    <div>{{ $latestAward->program_coordinator_name ?? '___________________' }}<br>Program Coordinator</div>
                </div>
                <div style="margin-top:16px;font-size:12px;color:var(--text-light);">
                    Awarded on {{ $latestAward->awarded_date->format('F d, Y') }}
                    @if($latestAward->awarded_by) by {{ $latestAward->awarded_by }} @endif
                </div>
            </div>
        </div>
        <a href="{{ route('admin.certificate.print', $latestAward->id) }}" class="btn btn-outline cert-print-btn" target="_blank">🖨 Print</a>
    </div>
    @else
    <div class="certificate-card" style="margin-bottom:24px;">
        <div class="cert-border">
            <div class="cert-logo">EC</div>
            <h2>Certificate of Award</h2>
            <p class="cert-subtitle">Presented to</p>
            <div class="cert-student">[Student Name]</div>
            <p class="cert-desc">No awards yet. Click "Add Award" to create one.</p>
            <div style="display:flex;justify-content:space-between;margin-top:30px;padding:0 20px;font-size:12px;color:var(--text-medium);">
                <div>___________________<br>School Principal</div>
                <div>___________________<br>Program Coordinator</div>
            </div>
        </div>
    </div>
    @endif

    @if($awards->count() > 0)
    <div class="table-container" style="margin-top:24px;">
        <div class="table-header">
            <h4 style="font-size:14px;font-weight:600;">Certificate Records</h4>
        </div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student</th>
                    <th>Certificate Title</th>
                    <th>Award Title</th>
                    <th>Award Date</th>
                    <th>Created By</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($awards as $i => $award)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $award->student->full_name ?? 'N/A' }}</td>
                    <td>{{ $award->certificate_title ?? $award->award_title }}</td>
                    <td>{{ $award->award_title }}</td>
                    <td>{{ $award->awarded_date->format('Y-m-d') }}</td>
                    <td>{{ $award->issuer->name ?? 'System' }}</td>
                    <td>
                        <div class="action-btns">
                            <a href="{{ route('admin.certificate.print', $award->id) }}" class="btn btn-view btn-xs" target="_blank">👁 View</a>
                            <a href="{{ route('admin.certificate.print', $award->id) }}" class="btn btn-achievement btn-xs" target="_blank">🖨 Print</a>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Add Award Modal -->
    <div class="modal-overlay" id="addAwardModal">
        <div class="modal-content" style="max-width:560px;">
            <div class="modal-header">
                <h3>Add award</h3>
                <p class="subtitle">Create a new certificate or award for a student</p>
            </div>
            <form method="POST" action="{{ route('admin.certificate.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Student</label>
                        <select name="student_id" required style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;">
                            <option value="">Select student</option>
                            @foreach($students as $s)
                                <option value="{{ $s->id }}">{{ $s->full_name }} ({{ $s->lrn }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Certificate Type</label>
                        <input type="text" name="certificate_type" placeholder="e.g. Academic, Environmental" style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;">
                    </div>
                    <div class="form-group">
                        <label>Certificate Title</label>
                        <input type="text" name="certificate_title" placeholder="e.g. Certificate of Excellence" required style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;">
                    </div>
                    <div class="form-group">
                        <label>Award Title</label>
                        <input type="text" name="award_title" placeholder="e.g. Excellence in Waste Collection Award" required style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;">
                    </div>
                    <div class="form-group">
                        <label>Award Description</label>
                        <textarea name="award_description" rows="2" placeholder="Describe the award..." style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;resize:vertical;"></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>School Principal Name</label>
                            <input type="text" name="school_principal_name" placeholder="Enter school principal name" style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;">
                        </div>
                        <div class="form-group">
                            <label>Program Coordinator Name</label>
                            <input type="text" name="program_coordinator_name" placeholder="Enter program coordinator name" style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Awarded By</label>
                            <input type="text" name="awarded_by" placeholder="Enter awarded by name" style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;">
                        </div>
                        <div class="form-group">
                            <label>Award Date</label>
                            <input type="date" name="awarded_date" value="{{ date('Y-m-d') }}" required style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Template File (optional)</label>
                        <input type="file" name="template_file" accept=".jpg,.jpeg,.png,.pdf" style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;">
                        <div style="font-size:11px;color:var(--text-light);margin-top:4px;">Accepted: JPG, PNG, PDF (max 5MB)</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-modal-close>Cancel</button>
                    <button type="submit" class="btn btn-success">Save Certificate</button>
                </div>
            </form>
        </div>
    </div>
@endsection
