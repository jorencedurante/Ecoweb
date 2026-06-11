@extends('layouts.admin')

@section('title', 'EcoCollect - Bottle Collection')
@section('page-title', 'Bottle Collection')
@section('page-subtitle', 'Manage bottle collection records')

@section('content')
    <div class="table-container">
        <div class="table-header">
            <div class="table-header-left">
                <div class="dropdown-group">
                    <select>
                        <option>Day</option>
                        @for($i = 1; $i <= 31; $i++)
                            <option>{{ $i }}</option>
                        @endfor
                    </select>
                    <select>
                        <option>Month</option>
                        <option>January</option>
                        <option>February</option>
                        <option>March</option>
                        <option>April</option>
                        <option>May</option>
                        <option>June</option>
                        <option>July</option>
                        <option>August</option>
                        <option>September</option>
                        <option>October</option>
                        <option>November</option>
                        <option>December</option>
                    </select>
                    <select>
                        <option>Year</option>
                        <option>2023</option>
                        <option>2024</option>
                        <option selected>2025</option>
                        <option>2026</option>
                    </select>
                </div>
                <div class="search-box">
                    <span class="search-icon">🔍</span>
                    <input type="text" placeholder="Search by LRN...">
                </div>
                <button class="filter-btn">🔽 Filter</button>
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>LRN</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Bottle Count</th>
                </tr>
            </thead>
            <tbody>
                <!-- TODO: Fetch collection records from database -->
                <tr><td>123456789012</td><td>2025-01-06</td><td>08:30 AM</td><td>5</td></tr>
                <tr><td>123456789013</td><td>2025-01-06</td><td>09:00 AM</td><td>3</td></tr>
                <tr><td>123456789014</td><td>2025-01-06</td><td>09:15 AM</td><td>7</td></tr>
                <tr><td>123456789015</td><td>2025-01-07</td><td>08:45 AM</td><td>4</td></tr>
                <tr><td>123456789016</td><td>2025-01-07</td><td>10:00 AM</td><td>6</td></tr>
                <tr><td>123456789017</td><td>2025-01-08</td><td>08:20 AM</td><td>8</td></tr>
                <tr><td>123456789012</td><td>2025-01-08</td><td>09:30 AM</td><td>2</td></tr>
                <tr><td>123456789013</td><td>2025-01-08</td><td>10:15 AM</td><td>5</td></tr>
                <tr><td>123456789014</td><td>2025-01-09</td><td>08:50 AM</td><td>3</td></tr>
                <tr><td>123456789015</td><td>2025-01-09</td><td>09:10 AM</td><td>6</td></tr>
            </tbody>
        </table>
        <div class="pagination">
            <span class="page-info">Showing 1 to 10 of 10 entries</span>
            <div class="page-btns">
                <button class="page-btn active">1</button>
            </div>
        </div>
    </div>
@endsection
