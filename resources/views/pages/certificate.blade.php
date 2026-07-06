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
    @php
        $templateExt = $latestAward->template_file_path ? strtolower(pathinfo($latestAward->template_file_path, PATHINFO_EXTENSION)) : null;
        $isImage = $templateExt && in_array($templateExt, ['jpg','jpeg','png']);
    @endphp
    <div class="certificate-card" style="margin-bottom:24px;">
        <div class="cert-border">
            @if($isImage)
            <div class="uploaded-template-preview certificate-print-area"
                style="background-image: url('{{ asset('storage/'.$latestAward->template_file_path) }}');">
                @if($latestAward->show_logo)
                    <img src="{{ asset('image/ecocollect-logo.jpg') }}" alt="EcoCollect Logo" class="overlay-logo">
                @endif
                @if($latestAward->show_certificate_title)
                    <div class="overlay-title">{{ $latestAward->certificate_title ?? $latestAward->award_title }}</div>
                @endif
                @if($latestAward->show_student_name)
                    <div class="overlay-student-name">{{ $latestAward->student->full_name }}</div>
                @endif
                @if($latestAward->show_award_description)
                    <div class="overlay-description">{{ $latestAward->award_description ?? 'For outstanding achievement.' }}</div>
                @endif
                @if($latestAward->show_principal_name || $latestAward->show_program_coordinator_name)
                    <div class="overlay-signatures">
                        @if($latestAward->show_principal_name)
                        <div>{{ $latestAward->school_principal_name ?? '___________________' }}<br>School Principal</div>
                        @endif
                        @if($latestAward->show_program_coordinator_name)
                        <div>{{ $latestAward->program_coordinator_name ?? '___________________' }}<br>Program Coordinator</div>
                        @endif
                    </div>
                @endif
                @if($latestAward->show_award_date)
                    <div class="overlay-date">
                        Awarded on {{ $latestAward->awarded_date->format('F d, Y') }}
                        @if($latestAward->awarded_by) by {{ $latestAward->awarded_by }} @endif
                    </div>
                @endif
            </div>
            @else
            <div class="certificate-preview default-template">
                <div class="certificate-content">
                    <img src="{{ asset('image/ecocollect-logo.jpg') }}" alt="EcoCollect Logo" class="cert-logo">
                    <h2>{{ $latestAward->certificate_title ?? $latestAward->award_title }}</h2>
                    <p class="cert-subtitle">Presented to</p>
                    <div class="cert-student">{{ $latestAward->student->full_name }}</div>
                    <p class="cert-desc">{{ $latestAward->award_description ?? 'For outstanding achievement.' }}</p>
                    <div class="cert-signatures">
                        <div>{{ $latestAward->school_principal_name ?? '___________________' }}<br>School Principal</div>
                        <div>{{ $latestAward->program_coordinator_name ?? '___________________' }}<br>Program Coordinator</div>
                    </div>
                    <div class="cert-date">
                        Awarded on {{ $latestAward->awarded_date->format('F d, Y') }}
                        @if($latestAward->awarded_by) by {{ $latestAward->awarded_by }} @endif
                    </div>
                </div>
            </div>
            @if($latestAward->template_file_path && !$isImage)
            <div style="margin-top:12px;text-align:center;font-size:13px;color:var(--text-medium);">
                PDF certificate template uploaded.
                <a href="{{ asset('storage/'.$latestAward->template_file_path) }}" target="_blank" style="color:var(--blue);font-weight:600;">View PDF Template</a>
            </div>
            @endif
            @endif
        </div>
    </div>
    @else
    <div class="certificate-card" style="margin-bottom:24px;">
        <div class="cert-border">
            <div class="certificate-preview default-template">
                <div class="certificate-content">
                    <img src="{{ asset('image/ecocollect-logo.jpg') }}" alt="EcoCollect Logo" class="cert-logo">
                    <h2>Certificate of Award</h2>
                    <p class="cert-subtitle">Presented to</p>
                    <div class="cert-student">[Student Name]</div>
                    <p class="cert-desc">No awards yet. Click "Add Award" to create one.</p>
                    <div class="cert-signatures">
                        <div>___________________<br>School Principal</div>
                        <div>___________________<br>Program Coordinator</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if($awards->count() > 0)
    <div class="filter-card" style="margin-top:24px;">
        <div class="filter-header" onclick="this.classList.toggle('collapsed');this.nextElementSibling.classList.toggle('collapsed')">
            <i class="fas fa-filter"></i> Filters
        </div>
        <div class="filter-body">
            <form method="GET" action="{{ route('admin.certificate') }}" class="filter-form">
                <div class="filter-search">
                    <label>Search</label>
                    <input type="text" name="search" placeholder="Search certificate, student..." value="{{ request('search') }}">
                </div>
                <div class="filter-search">
                    <label>Certificate Type</label>
                    <select name="certificate_type">
                        <option value="">All Types</option>
                        @foreach($certificateTypes ?? [] as $ct)
                            <option value="{{ $ct }}" {{ request('certificate_type') == $ct ? 'selected' : '' }}>{{ $ct }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-search">
                    <label>Student</label>
                    <select name="student_id">
                        <option value="">All Students</option>
                        @foreach($students as $s)
                            <option value="{{ $s->id }}" {{ request('student_id') == $s->id ? 'selected' : '' }}>{{ $s->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-search">
                    <label>Award Date</label>
                    <input type="date" name="award_date" value="{{ request('award_date') }}">
                </div>
                <div class="filter-search">
                    <label>Month</label>
                    <select name="month">
                        <option value="">Month</option>
                        @foreach(['January','February','March','April','May','June','July','August','September','October','November','December'] as $m)
                            <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-search">
                    <label>Year</label>
                    <select name="year">
                        <option value="">Year</option>
                        @foreach(['2023','2024','2025','2026'] as $y)
                            <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-controls">
                    <button class="btn btn-filter" type="submit">Filter</button>
                    <a href="{{ route('admin.certificate') }}" class="btn btn-reset">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div class="table-container" style="margin-top:24px;">
        <div class="table-header">
            <div class="table-header-left">
                <h4 style="font-size:14px;font-weight:600;">Certificate Records</h4>
            </div>
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
                    <td>{{ $awards->firstItem() + $loop->index }}</td>
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
        @if($awards->hasPages())
        <div class="pagination">
            <span class="page-info">Showing {{ $awards->firstItem() ?? 0 }} to {{ $awards->lastItem() ?? 0 }} of {{ $awards->total() }} entries</span>
            <div class="page-btns">
                @for ($p = 1; $p <= $awards->lastPage(); $p++)
                    <a href="{{ $awards->url($p) }}" class="page-btn {{ $awards->currentPage() == $p ? 'active' : '' }}">{{ $p }}</a>
                @endfor
            </div>
        </div>
        @endif
    </div>
    @endif

    <!-- Add Award Modal -->
    <div class="modal-overlay" id="addAwardModal">
        <div class="modal-content award-modal">
            <div class="modal-header">
                <h3>Add award</h3>
                <p class="subtitle">Create a new certificate or award for a student</p>
            </div>
            <form method="POST" action="{{ route('admin.certificate.store') }}" enctype="multipart/form-data" class="modal-form">
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
                    <div class="overlay-options-section">
                        <h3 class="overlay-options-heading">Template Overlay Options</h3>
                        <p class="overlay-help-text">
                            If your uploaded certificate template already contains text, keep only "Show Student Name" enabled to avoid overlapping.
                        </p>
                        <div class="overlay-options-grid">
                            <label class="overlay-option">
                                <input type="hidden" name="show_logo" value="0">
                                <input type="checkbox" name="show_logo" value="1">
                                <span>Show EcoCollect Logo</span>
                            </label>
                            <label class="overlay-option">
                                <input type="hidden" name="show_certificate_title" value="0">
                                <input type="checkbox" name="show_certificate_title" value="1">
                                <span>Show Certificate Title</span>
                            </label>
                            <label class="overlay-option">
                                <input type="hidden" name="show_student_name" value="0">
                                <input type="checkbox" name="show_student_name" value="1" checked>
                                <span>Show Student Name</span>
                            </label>
                            <label class="overlay-option">
                                <input type="hidden" name="show_award_description" value="0">
                                <input type="checkbox" name="show_award_description" value="1">
                                <span>Show Award Description</span>
                            </label>
                            <label class="overlay-option">
                                <input type="hidden" name="show_award_date" value="0">
                                <input type="checkbox" name="show_award_date" value="1">
                                <span>Show Award Date</span>
                            </label>
                            <label class="overlay-option">
                                <input type="hidden" name="show_principal_name" value="0">
                                <input type="checkbox" name="show_principal_name" value="1">
                                <span>Show Principal Name</span>
                            </label>
                            <label class="overlay-option">
                                <input type="hidden" name="show_program_coordinator_name" value="0">
                                <input type="checkbox" name="show_program_coordinator_name" value="1">
                                <span>Show Program Coordinator Name</span>
                            </label>
                        </div>
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
