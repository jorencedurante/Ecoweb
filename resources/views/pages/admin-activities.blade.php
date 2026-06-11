@extends('layouts.admin')

@section('title', 'EcoCollect - Admin Activities')
@section('page-title', 'Admin Activities')
@section('page-subtitle', 'Monitor admin and teacher account activities')

@section('content')
    <a href="{{ route('admin.reports') }}" class="back-link">← Back to Reports</a>

    <div class="table-container">
        <div class="table-header">
            <div class="table-header-left">
                <div class="search-box">
                    <span class="search-icon">🔍</span>
                    <input type="text" placeholder="Search activities...">
                </div>
                <button class="filter-btn">🔽 Filter</button>
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Admin ID</th>
                    <th>Name</th>
                    <th>Action</th>
                    <th>Date</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                <!-- TODO: Fetch activity logs from database -->
                <tr><td>1</td><td>ADM001</td><td>Juan Dela Cruz</td><td>Added student record</td><td>2025-01-06</td><td>08:30 AM</td></tr>
                <tr><td>2</td><td>ADM002</td><td>Maria Santos</td><td>Updated bottle collection</td><td>2025-01-06</td><td>09:00 AM</td></tr>
                <tr><td>3</td><td>ADM001</td><td>Juan Dela Cruz</td><td>Generated report</td><td>2025-01-07</td><td>10:15 AM</td></tr>
                <tr><td>4</td><td>ADM003</td><td>Pedro Reyes</td><td>Archived student record</td><td>2025-01-07</td><td>11:00 AM</td></tr>
                <tr><td>5</td><td>ADM004</td><td>Ana Gonzales</td><td>Edited teacher account</td><td>2025-01-08</td><td>08:45 AM</td></tr>
                <tr><td>6</td><td>ADM002</td><td>Maria Santos</td><td>Downloaded certificate</td><td>2025-01-08</td><td>09:30 AM</td></tr>
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
