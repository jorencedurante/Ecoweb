@extends('layouts.admin')

@section('title', 'EcoCollect - Students')
@section('page-title', 'Students')
@section('page-subtitle', 'Manage student information')

@section('content')
    <div class="table-container">
        <div class="table-header">
            <div class="table-header-left">
                <div class="search-box">
                    <span class="search-icon">🔍</span>
                    <input type="text" placeholder="Search students..." id="studentSearch" name="search" value="{{ request('search') }}" onkeyup="filterStudents()">
                </div>
                <form method="GET" action="{{ route('admin.students') }}" id="filterForm" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                    <select name="grade_level" onchange="this.form.submit()" style="padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#fff;">
                        <option value="">All Grades</option>
                        @foreach($gradeLevels ?? [] as $gl)
                            <option value="{{ $gl }}" {{ request('grade_level') == $gl ? 'selected' : '' }}>{{ $gl }}</option>
                        @endforeach
                    </select>
                    <select name="gender" onchange="this.form.submit()" style="padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#fff;">
                        <option value="">All Genders</option>
                        <option value="Male" {{ request('gender') == 'Male' ? 'selected' : '' }}>Male</option>
                        <option value="Female" {{ request('gender') == 'Female' ? 'selected' : '' }}>Female</option>
                    </select>
                    <button class="filter-btn" type="button" onclick="document.getElementById('filterForm').reset(); window.location='{{ route('admin.students') }}';">Clear</button>
                </form>
            </div>
            <button class="btn btn-primary" data-modal-target="addStudentModal">+ Add Student</button>
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
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $student)
                <tr>
                    <td>{{ $student->lrn }}</td>
                    <td><strong>{{ $student->full_name }}</strong></td>
                    <td>{{ $student->grade_level }}</td>
                    <td>{{ $student->qr_code ?? '—' }}</td>
                    <td>{{ $student->total_points }}</td>
                    <td><span style="color:{{ $student->status === 'active' ? 'var(--green)' : 'var(--gray)' }};font-weight:600;">{{ ucfirst($student->status) }}</span></td>
                    <td>
                        <div class="action-btns">
                            <a href="{{ route('students.info', $student->id) }}" class="btn btn-view btn-xs">View Info</a>
                            <a href="{{ route('students.achievements', $student->id) }}" class="btn btn-achievement btn-xs">Achievements</a>
                            <a href="{{ route('students.awards', $student->id) }}" class="btn btn-award btn-xs">Awards</a>
                            <button class="btn btn-edit btn-xs" data-modal-target="editStudentModal"
                                data-id="{{ $student->id }}"
                                data-lrn="{{ $student->lrn }}"
                                data-first="{{ $student->first_name }}"
                               data-middle="{{ $student->middle_name }}"
                                data-last="{{ $student->last_name }}"
                                data-grade="{{ $student->grade_level }}"
                                data-gender="{{ $student->gender }}">Edit</button>
                            <form method="POST" action="{{ route('admin.students.archive', $student->id) }}" style="display:inline;" onsubmit="return confirm('Archive this student?')">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-archive btn-xs">Archive</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--text-light);">No students found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="pagination">
            <span class="page-info">Showing {{ $students->firstItem() ?? 0 }} to {{ $students->lastItem() ?? 0 }} of {{ $students->total() }} entries</span>
            <div class="page-btns">
                @for ($i = 1; $i <= $students->lastPage(); $i++)
                    <a href="{{ $students->url($i) }}" class="page-btn {{ $students->currentPage() == $i ? 'active' : '' }}">{{ $i }}</a>
                @endfor
            </div>
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
                                @foreach(['Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6'] as $g)
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
                                @foreach(['Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6'] as $g)
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
function filterStudents() {
    const val = document.getElementById('studentSearch').value;
    const url = new URL(window.location);
    if (val) url.searchParams.set('search', val);
    else url.searchParams.delete('search');
    window.location = url.toString();
}

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

            document.getElementById('editStudentForm').action = '{{ url('/admin/students') }}/' + id;
            document.getElementById('editFirstName').value = first;
            document.getElementById('editMiddleName').value = middle || '';
            document.getElementById('editLastName').value = last;
            document.getElementById('editLrn').value = lrn;
            document.getElementById('editGradeLevel').value = grade;
            if (gender === 'Male') document.getElementById('editGenderMale').checked = true;
            else document.getElementById('editGenderFemale').checked = true;

            editModal.classList.add('show');
        });
    });
});
</script>
@endpush
