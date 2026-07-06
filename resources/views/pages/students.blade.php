@extends('layouts.admin')

@section('title', 'EcoCollect - Students')
@section('page-title', 'Students')
@section('page-subtitle', 'Manage student information')

@section('content')
    <div class="students-page-wrapper">

    <div class="filter-card">
        <div class="filter-header" onclick="this.classList.toggle('collapsed');this.nextElementSibling.classList.toggle('collapsed')">
            <i class="fas fa-filter"></i> Filters
        </div>
        <div class="filter-body">
            <form method="GET" action="{{ route('admin.students') }}" id="filterForm" class="filter-form">
                <div class="filter-search">
                    <label>Search</label>
                    <input type="text" name="search" placeholder="Search students..." value="{{ request('search') }}">
                </div>
                <div class="filter-search">
                    <label>Grade Level</label>
                    <select name="grade_level">
                        <option value="">All Grades</option>
                        @foreach($gradeLevels ?? [] as $gl)
                            <option value="{{ $gl }}" {{ request('grade_level') == $gl ? 'selected' : '' }}>{{ $gl }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-search">
                    <label>Gender</label>
                    <select name="gender">
                        <option value="">All Genders</option>
                        <option value="Male" {{ request('gender') == 'Male' ? 'selected' : '' }}>Male</option>
                        <option value="Female" {{ request('gender') == 'Female' ? 'selected' : '' }}>Female</option>
                    </select>
                </div>
                <div class="filter-controls">
                    <button class="btn btn-filter" type="submit">Filter</button>
                    <a href="{{ route('admin.students') }}" class="btn btn-reset">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div class="table-container">
        <div class="table-header">
            <div class="table-header-left"></div>
            <div class="table-toolbar-actions">
                <a href="{{ route('admin.students.archived') }}" class="btn btn-outline btn-sm archived-btn">📦 Archived</a>
                <button class="btn btn-primary btn-sm" data-modal-target="importStudentModal">📥 Import</button>
                <button class="btn btn-primary" data-modal-target="addStudentModal">+ Add Student</button>
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>LRN</th>
                    <th>Name</th>
                    <th>Grade Level</th>
                    <th>QR Code</th>
                    <th>Total Points</th>
                    <th>Status</th>
                    <th class="actions-th">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $student)
                <tr>
                    <td class="cell-lrn">{{ $student->lrn }}</td>
                    <td class="cell-name"><strong>{{ $student->full_name }}</strong></td>
                    <td>{{ $student->grade_level }}</td>
                    <td class="cell-qr">{{ $student->qr_code ?? '—' }}</td>
                    <td class="cell-points">{{ $student->total_points }}</td>
                    <td>
                        <span class="status-badge status-{{ $student->status }}">{{ ucfirst($student->status) }}</span>
                    </td>
                    <td>
                        <div class="student-actions">
                            <a href="{{ route('students.info', $student->id) }}" class="action-icon-btn view" title="View Info">👁</a>
                            <a href="{{ route('students.achievements', $student->id) }}" class="action-icon-btn achievements" title="Achievements">🏆</a>
                            <a href="{{ route('students.awards', $student->id) }}" class="action-icon-btn awards" title="Awards">🎖</a>
                            <button class="action-icon-btn edit" title="Edit Student" data-modal-target="editStudentModal"
                                data-id="{{ $student->id }}"
                                data-lrn="{{ $student->lrn }}"
                                data-first="{{ $student->first_name }}"
                                data-middle="{{ $student->middle_name }}"
                                data-last="{{ $student->last_name }}"
                                data-grade="{{ $student->grade_level }}"
                                data-gender="{{ $student->gender }}"
                                data-teacher-id="{{ $student->teacher_id }}">✏️</button>
                            @if($student->status !== 'Archived')
                            <form method="POST" action="{{ route('admin.students.archive', $student->id) }}" class="inline-action-form" onsubmit="return confirm('Archive this student?')">
                                @csrf @method('PATCH')
                                <button type="submit" class="action-icon-btn archive" title="Archive Student">📦</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="empty-row">No students found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($students->hasPages())
        <div class="pagination">
            <span class="page-info">Showing {{ $students->firstItem() ?? 0 }} to {{ $students->lastItem() ?? 0 }} of {{ $students->total() }} entries</span>
            <div class="page-btns">
                @for ($i = 1; $i <= $students->lastPage(); $i++)
                    <a href="{{ $students->url($i) }}" class="page-btn {{ $students->currentPage() == $i ? 'active' : '' }}">{{ $i }}</a>
                @endfor
            </div>
        </div>
        @endif
    </div>

    </div>

    @if(session('import_result'))
    @php $result = session('import_result'); $type = $result['type'] ?? 'success'; @endphp
    <div class="import-result-card import-result-{{ $type }}" id="importResult">
        <div class="import-result-header">
            <span>📋 Import Result</span>
            <button onclick="document.getElementById('importResult').remove()" style="background:none;border:none;font-size:18px;cursor:pointer;color:#6b7280;">×</button>
        </div>
        <div class="import-result-summary">
            @foreach(explode(' | ', $result['message']) as $line)
            <div>{{ $line }}</div>
            @endforeach
        </div>
        @if(count($result['errors']))
        <div class="import-result-errors">
            <strong>Skipped rows:</strong>
            <ul>
                @foreach($result['errors'] as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>
    @endif

    <!-- Import Students Modal -->
    <div class="modal-overlay" id="importStudentModal">
        <div class="modal-content" style="max-width:520px;">
            <div class="modal-header">
                <h3>Import Students</h3>
                <p class="subtitle">Upload an Excel file (.xlsx) with student data.</p>
            </div>
            <form method="POST" action="{{ route('admin.students.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Excel File <span style="color:var(--red);">*</span></label>
                        <input type="file" name="file" accept=".xlsx,.xls" required>
                    </div>
                    <div class="form-group">
                        <label>Grade Level (fallback if not detected in file)</label>
                        <select name="grade_level">
                            <option value="">-- Auto-detect or select --</option>
                            <option value="Kindergarten">Kindergarten</option>
                            <option value="Grade 1">Grade 1</option>
                            <option value="Grade 2">Grade 2</option>
                            <option value="Grade 3">Grade 3</option>
                            <option value="Grade 4">Grade 4</option>
                            <option value="Grade 5">Grade 5</option>
                            <option value="Grade 6">Grade 6</option>
                        </select>
                    </div>
                    <p class="import-note">
                        Supports <strong>SF1 (School Form 1)</strong> files and simple Excel templates.
                        For SF1 files, the system auto-detects headers, parses student names (Last,First,Middle),
                        and reads the grade level from the file. Select a grade level above as fallback.
                        <br><br>
                        For simple templates, supported columns: <strong>LRN</strong>, <strong>Student Name</strong>,
                        <strong>Grade Level</strong>, <strong>Gender</strong>, <strong>Student ID</strong>.
                        Gender accepts M/F/Male/Female. Duplicate LRN rows are skipped.
                    </p>
                </div>
                <div class="modal-footer">
                    <a href="{{ route('admin.students.import.template') }}" class="btn btn-outline btn-template">📄 Download Template</a>
                    <button type="button" class="btn btn-danger" data-modal-close>Cancel</button>
                    <button type="submit" class="btn btn-success">Import</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Student Modal -->
    <div class="modal-overlay" id="addStudentModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add student</h3>
                <p class="subtitle">Fill in the following information</p>
            </div>
            <form method="POST" action="{{ route('admin.students.store') }}">
                @csrf
                <div class="modal-body">
                    @if($errors->any())
                    <div style="background:rgba(239,83,80,0.1);border:1px solid var(--red);color:var(--red-dark);padding:10px 14px;border-radius:var(--radius-sm);font-size:12px;margin-bottom:12px;">
                        @foreach($errors->all() as $error)<div>• {{ $error }}</div>@endforeach
                    </div>
                    @endif
                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" name="first_name" placeholder="Enter first name" value="{{ old('first_name') }}" required>
                        </div>
                        <div class="form-group">
                            <label>Middle Name <span class="optional">(Optional)</span></label>
                            <input type="text" name="middle_name" placeholder="Enter middle name" value="{{ old('middle_name') }}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" name="last_name" placeholder="Enter last name" value="{{ old('last_name') }}" required>
                        </div>
                        <div class="form-group">
                            <label>Student's LRN</label>
                            <input type="text" name="lrn" placeholder="Enter LRN" value="{{ old('lrn') }}" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Grade Level</label>
                            <select name="grade_level">
                                <option value="">Select grade</option>
                                @foreach(['Kindergarten','Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6'] as $g)
                                    <option value="{{ $g }}" {{ old('grade_level') == $g ? 'selected' : '' }}>{{ $g }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Gender</label>
                            <div class="radio-group">
                                <label><input type="radio" name="gender" value="Male" {{ old('gender') == 'Male' ? 'checked' : '' }}> Male</label>
                                <label><input type="radio" name="gender" value="Female" {{ old('gender') == 'Female' ? 'checked' : '' }}> Female</label>
                            </div>
                        </div>
                    </div>
                    @if(in_array(Auth::user()->role, ['admin', 'super_admin']))
                    <div class="form-group">
                        <label>Assign Teacher</label>
                        <select name="teacher_id">
                            <option value="">No teacher</option>
                            @foreach($teachers ?? [] as $teacher)
                                <option value="{{ $teacher->id }}" {{ old('teacher_id') == $teacher->id ? 'selected' : '' }}>{{ $teacher->name }} ({{ $teacher->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-modal-close>Cancel</button>
                    <button type="submit" class="btn btn-success">Add Student</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Student Modal -->
    <div class="modal-overlay" id="editStudentModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit student</h3>
                <p class="subtitle">Fill in the following information</p>
            </div>
            <form method="POST" action="" id="editStudentForm">
                @csrf @method('PUT')
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" name="first_name" id="editFirstName" required>
                        </div>
                        <div class="form-group">
                            <label>Middle Name <span class="optional">(Optional)</span></label>
                            <input type="text" name="middle_name" id="editMiddleName">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" name="last_name" id="editLastName" required>
                        </div>
                        <div class="form-group">
                            <label>Student's LRN</label>
                            <input type="text" name="lrn" id="editLrn" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Grade Level</label>
                            <select name="grade_level" id="editGradeLevel">
                                @foreach(['Kindergarten','Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6'] as $g)
                                    <option value="{{ $g }}">{{ $g }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Gender</label>
                            <div class="radio-group">
                                <label><input type="radio" name="gender" value="Male" id="editGenderMale"> Male</label>
                                <label><input type="radio" name="gender" value="Female" id="editGenderFemale"> Female</label>
                            </div>
                        </div>
                    </div>
                    @if(in_array(Auth::user()->role, ['admin', 'super_admin']))
                    <div class="form-group">
                        <label>Assign Teacher</label>
                        <select name="teacher_id" id="editTeacherId">
                            <option value="">No teacher</option>
                            @foreach($teachers ?? [] as $teacher)
                                <option value="{{ $teacher->id }}">{{ $teacher->name }} ({{ $teacher->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-modal-close>Cancel</button>
                    <button type="submit" class="btn btn-success">Save</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const editModal = document.getElementById('editStudentModal');
    document.querySelectorAll('[data-modal-target="editStudentModal"]').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            const lrn = btn.dataset.lrn;
            const first = btn.dataset.first;
            const middle = btn.dataset.middle;
            const last = btn.dataset.last;
            const grade = btn.dataset.grade;
            const gender = btn.dataset.gender;

            const teacherId = btn.dataset.teacherId;
            document.getElementById('editStudentForm').action = '{{ url('/admin/students') }}/' + id;
            document.getElementById('editFirstName').value = first;
            document.getElementById('editMiddleName').value = middle || '';
            document.getElementById('editLastName').value = last;
            document.getElementById('editLrn').value = lrn;
            document.getElementById('editGradeLevel').value = grade;
            if (gender === 'Male') document.getElementById('editGenderMale').checked = true;
            else document.getElementById('editGenderFemale').checked = true;
            const editTeacher = document.getElementById('editTeacherId');
            if (editTeacher) editTeacher.value = teacherId || '';

            editModal.classList.add('show');
        });
    });
});
</script>
@endpush
