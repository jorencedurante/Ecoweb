@extends('layouts.admin')

@section('title', 'EcoCollect - Student\'s Report')
@section('page-title', 'Student\'s Report')
@section('page-subtitle', 'Generate and manage reports')

@section('content')
    <a href="{{ route('admin.reports') }}" class="back-link">← Back to Reports</a>

    <div class="table-header" style="background:var(--card-bg);border-radius:var(--radius);box-shadow:var(--shadow);margin-bottom:20px;padding:16px 20px;">
        <div class="table-header-left">
            <select style="padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;">
                <option>Grade Level</option>
                <option>Grade 1</option>
                <option>Grade 2</option>
                <option>Grade 3</option>
                <option>Grade 4</option>
                <option>Grade 5</option>
                <option>Grade 6</option>
            </select>
            <select style="padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;">
                <option>Gender</option>
                <option>Male</option>
                <option>Female</option>
            </select>
            <div class="search-box">
                <span class="search-icon">🔍</span>
                <input type="text" placeholder="Search...">
            </div>
            <button class="filter-btn">🔽 Filter</button>
        </div>
    </div>

    <div class="summary-cards">
        <div class="stat-card">
            <div class="stat-icon green">👥</div>
            <div class="stat-info">
                <div class="stat-value">156</div>
                <div class="stat-label">Total Students</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue">♀</div>
            <div class="stat-info">
                <div class="stat-value">39</div>
                <div class="stat-label">Female</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon yellow">♂</div>
            <div class="stat-info">
                <div class="stat-value">117</div>
                <div class="stat-label">Male</div>
            </div>
        </div>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Gender</th>
                    <th>Class</th>
                    <th>Bottles Collected</th>
                </tr>
            </thead>
            <tbody>
                <!-- TODO: Fetch student records from database -->
                <tr><td>1</td><td>STU001</td><td>Kathleen E. Tabadero</td><td>Female</td><td>Grade 6</td><td>40</td></tr>
                <tr><td>2</td><td>STU002</td><td>Joy O. Tabadero</td><td>Female</td><td>Grade 5</td><td>35</td></tr>
                <tr><td>3</td><td>STU003</td><td>Jerence C. Tabadero</td><td>Male</td><td>Grade 4</td><td>48</td></tr>
                <tr><td>4</td><td>STU004</td><td>Patricia R. Tabadero</td><td>Female</td><td>Grade 3</td><td>30</td></tr>
                <tr><td>5</td><td>STU005</td><td>Denver P. Tabadero</td><td>Male</td><td>Grade 2</td><td>42</td></tr>
                <tr><td>6</td><td>STU006</td><td>Karen N. Tabadero</td><td>Female</td><td>Grade 6</td><td>38</td></tr>
            </tbody>
        </table>
        <div class="pagination">
            <span class="page-info">Showing 1 to 6 of 6 entries</span>
            <div class="page-btns">
                <button class="page-btn active">1</button>
            </div>
        </div>
    </div>
@endsection
