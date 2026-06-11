@extends('layouts.admin')

@section('title', 'EcoCollect - Teachers')
@section('page-title', 'Teachers')
@section('page-subtitle', 'Manage Teacher Accounts')

@section('content')
    <div class="table-container">
        <div class="table-header">
            <div class="table-header-left">
                <div class="search-box">
                    <span class="search-icon">🔍</span>
                    <input type="text" placeholder="Search teachers...">
                </div>
                <button class="filter-btn">🔽 Filter</button>
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Admin ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Position</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- TODO: Fetch teacher records from database -->
                <tr>
                    <td>ADM001</td>
                    <td><strong>Juan Dela Cruz</strong></td>
                    <td>juan@ecocollect.edu</td>
                    <td>Admin</td>
                    <td>
                        <div class="action-btns">
                            <button class="btn btn-primary btn-xs">👁</button>
                            <button class="btn btn-warning btn-xs">✏</button>
                            <button class="btn btn-outline btn-xs" onclick="archiveTeacher('ADM001')">🗑</button>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>ADM002</td>
                    <td><strong>Maria Santos</strong></td>
                    <td>maria@ecocollect.edu</td>
                    <td>Teacher</td>
                    <td>
                        <div class="action-btns">
                            <button class="btn btn-primary btn-xs">👁</button>
                            <button class="btn btn-warning btn-xs">✏</button>
                            <button class="btn btn-outline btn-xs" onclick="archiveTeacher('ADM002')">🗑</button>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>ADM003</td>
                    <td><strong>Pedro Reyes</strong></td>
                    <td>pedro@ecocollect.edu</td>
                    <td>Teacher</td>
                    <td>
                        <div class="action-btns">
                            <button class="btn btn-primary btn-xs">👁</button>
                            <button class="btn btn-warning btn-xs">✏</button>
                            <button class="btn btn-outline btn-xs" onclick="archiveTeacher('ADM003')">🗑</button>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>ADM004</td>
                    <td><strong>Ana Gonzales</strong></td>
                    <td>ana@ecocollect.edu</td>
                    <td>Admin</td>
                    <td>
                        <div class="action-btns">
                            <button class="btn btn-primary btn-xs">👁</button>
                            <button class="btn btn-warning btn-xs">✏</button>
                            <button class="btn btn-outline btn-xs" onclick="archiveTeacher('ADM004')">🗑</button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
        <div class="pagination">
            <span class="page-info">Showing 1 to 4 of 4 entries</span>
            <div class="page-btns">
                <button class="page-btn active">1</button>
            </div>
        </div>
    </div>
@endsection
