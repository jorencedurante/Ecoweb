@extends('layouts.admin')

@section('title', 'EcoCollect - Students')
@section('page-title', 'Students')
@section('page-subtitle', 'Manage student information - filtered view')

@section('content')
    <div class="table-container">
        <div class="table-header">
            <div class="table-header-left">
                <div class="search-box">
                    <span class="search-icon">🔍</span>
                    <input type="text" placeholder="Search students..." value="Karen" readonly>
                </div>
                <a href="{{ route('admin.students') }}" class="btn btn-outline btn-sm">← Clear & Show All</a>
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
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $student = \App\Models\Student::find(6);
                @endphp
                @if($student)
                <tr>
                    <td>{{ $student->lrn }}</td>
                    <td><strong>{{ $student->full_name }}</strong></td>
                    <td>{{ $student->grade_level }}</td>
                    <td>{{ $student->qr_code ?? '—' }}</td>
                    <td>{{ $student->total_points }}</td>
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
                @else
                <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--text-light);">Student not found.</td></tr>
                @endif
            </tbody>
        </table>
        <div class="pagination">
            <span class="page-info">Showing 1 to 1 of 1 entries (filtered)</span>
            <div class="page-btns">
                <button class="page-btn active">1</button>
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
                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" name="first_name" placeholder="Enter first name" required>
                        </div>
                        <div class="form-group">
                            <label>Middle Name <span class="optional">(Optional)</span></label>
                            <input type="text" name="middle_name" placeholder="Enter middle name">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" name="last_name" placeholder="Enter last name" required>
                        </div>
                        <div class="form-group">
                            <label>Student's LRN</label>
                            <input type="text" name="lrn" placeholder="Enter LRN" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Grade Level</label>
                            <select name="grade_level">
                                @foreach(['Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6'] as $g)
                                    <option value="{{ $g }}">{{ $g }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Gender</label>
                            <div class="radio-group">
                                <label><input type="radio" name="gender" value="Male"> Male</label>
                                <label><input type="radio" name="gender" value="Female" checked> Female</label>
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
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-modal-target="editStudentModal"]').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('editStudentForm').action = '{{ route('admin.students.update', '') }}/' + btn.dataset.id;
            document.getElementById('editFirstName').value = btn.dataset.first;
            document.getElementById('editMiddleName').value = btn.dataset.middle || '';
            document.getElementById('editLastName').value = btn.dataset.last;
            document.getElementById('editLrn').value = btn.dataset.lrn;
            document.getElementById('editGradeLevel').value = btn.dataset.grade;
            if (btn.dataset.gender === 'Male') document.getElementById('editGenderMale').checked = true;
            else document.getElementById('editGenderFemale').checked = true;
            document.getElementById('editStudentModal').classList.add('show');
        });
    });
});
</script>
@endpush
