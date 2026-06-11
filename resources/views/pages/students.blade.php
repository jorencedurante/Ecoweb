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
                    <input type="text" placeholder="Search students..." id="studentSearch">
                </div>
                <button class="filter-btn">🔽 Filter</button>
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
                <!-- TODO: Fetch student records from database -->
                <tr>
                    <td>123456789012</td>
                    <td><strong>Kathleen E. Tabadero</strong></td>
                    <td>Grade 6</td>
                    <td>Q001</td>
                    <td>43</td>
                    <td>
                        <div class="action-btns">
                            <button class="btn btn-primary btn-xs">👁</button>
                            <button class="btn btn-warning btn-xs" data-modal-target="editStudentModal" data-student-id="STU001">✏</button>
                            <button class="btn btn-outline btn-xs" onclick="archiveStudent('STU001')">📦</button>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>123456789013</td>
                    <td><strong>Joy O. Tabadero</strong></td>
                    <td>Grade 5</td>
                    <td>Q002</td>
                    <td>38</td>
                    <td>
                        <div class="action-btns">
                            <button class="btn btn-primary btn-xs">👁</button>
                            <button class="btn btn-warning btn-xs" data-modal-target="editStudentModal" data-student-id="STU002">✏</button>
                            <button class="btn btn-outline btn-xs" onclick="archiveStudent('STU002')">📦</button>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>123456789014</td>
                    <td><strong>Jerence C. Tabadero</strong></td>
                    <td>Grade 4</td>
                    <td>Q003</td>
                    <td>50</td>
                    <td>
                        <div class="action-btns">
                            <button class="btn btn-primary btn-xs">👁</button>
                            <button class="btn btn-warning btn-xs" data-modal-target="editStudentModal" data-student-id="STU003">✏</button>
                            <button class="btn btn-outline btn-xs" onclick="archiveStudent('STU003')">📦</button>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>123456789015</td>
                    <td><strong>Patricia R. Tabadero</strong></td>
                    <td>Grade 3</td>
                    <td>Q004</td>
                    <td>32</td>
                    <td>
                        <div class="action-btns">
                            <button class="btn btn-primary btn-xs">👁</button>
                            <button class="btn btn-warning btn-xs" data-modal-target="editStudentModal" data-student-id="STU004">✏</button>
                            <button class="btn btn-outline btn-xs" onclick="archiveStudent('STU004')">📦</button>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>123456789016</td>
                    <td><strong>Denver P. Tabadero</strong></td>
                    <td>Grade 2</td>
                    <td>Q005</td>
                    <td>45</td>
                    <td>
                        <div class="action-btns">
                            <button class="btn btn-primary btn-xs">👁</button>
                            <button class="btn btn-warning btn-xs" data-modal-target="editStudentModal" data-student-id="STU005">✏</button>
                            <button class="btn btn-outline btn-xs" onclick="archiveStudent('STU005')">📦</button>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>123456789017</td>
                    <td><strong>Karen N. Tabadero</strong></td>
                    <td>Grade 6</td>
                    <td>Q006</td>
                    <td>40</td>
                    <td>
                        <div class="action-btns">
                            <button class="btn btn-primary btn-xs">👁</button>
                            <button class="btn btn-warning btn-xs" data-modal-target="editStudentModal" data-student-id="STU006">✏</button>
                            <button class="btn btn-outline btn-xs" onclick="archiveStudent('STU006')">📦</button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
        <div class="pagination">
            <span class="page-info">Showing 1 to 6 of 6 entries</span>
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
            <form>
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" name="firstName" placeholder="Enter first name" required>
                        </div>
                        <div class="form-group">
                            <label>Middle Name <span class="optional">(Optional)</span></label>
                            <input type="text" name="middleName" placeholder="Enter middle name">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" name="lastName" placeholder="Enter last name" required>
                        </div>
                        <div class="form-group">
                            <label>Student's LRN</label>
                            <input type="text" name="lrn" placeholder="Enter LRN" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Grade Level</label>
                            <select name="gradeLevel">
                                <option>Grade 1</option>
                                <option>Grade 2</option>
                                <option>Grade 3</option>
                                <option>Grade 4</option>
                                <option>Grade 5</option>
                                <option selected>Grade 6</option>
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
            <form>
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" name="firstName" required>
                        </div>
                        <div class="form-group">
                            <label>Middle Name <span class="optional">(Optional)</span></label>
                            <input type="text" name="middleName">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" name="lastName" required>
                        </div>
                        <div class="form-group">
                            <label>Student's LRN</label>
                            <input type="text" name="lrn" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Grade Level</label>
                            <select name="gradeLevel">
                                <option>Grade 1</option>
                                <option>Grade 2</option>
                                <option>Grade 3</option>
                                <option>Grade 4</option>
                                <option>Grade 5</option>
                                <option>Grade 6</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Gender</label>
                            <div class="radio-group">
                                <label><input type="radio" name="gender" value="Male"> Male</label>
                                <label><input type="radio" name="gender" value="Female"> Female</label>
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
