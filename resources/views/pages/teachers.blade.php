@extends('layouts.admin')

@section('title', 'EcoCollect - Admin & Teacher Accounts')
@section('page-title', 'Admin & Teacher Accounts')
@section('page-subtitle', 'Manage system user accounts')

@section('content')
    <div class="table-container">
        <div class="table-header">
            <div class="table-header-left">
                <form method="GET" action="{{ route('admin.teachers') }}" style="display:flex;gap:8px;align-items:center;">
                    <div class="search-box">
                        <span class="search-icon">🔍</span>
                        <input type="text" name="search" placeholder="Search accounts..." value="{{ request('search') }}">
                    </div>
                    <select name="role" style="padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;background:#FAFAFA;outline:none;">
                        <option value="">All Roles</option>
                        <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="teacher" {{ request('role') === 'teacher' ? 'selected' : '' }}>Teacher</option>
                        <option value="super_admin" {{ request('role') === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                    </select>
                    <button class="filter-btn" type="submit">🔽 Filter</button>
                    <button class="filter-btn" type="button" onclick="window.location='{{ route('admin.teachers') }}'">Clear</button>
                </form>
            </div>
            <button class="btn btn-primary" data-modal-target="addTeacherModal">+ Add Account</button>
        </div>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Position</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($accounts as $account)
                <tr>
                    <td>ADM{{ str_pad($account->id, 3, '0', STR_PAD_LEFT) }}</td>
                    <td><strong>{{ $account->name }}</strong></td>
                    <td>{{ $account->email }}</td>
                    <td><span style="font-weight:600;">{{ ucfirst($account->role) }}</span></td>
                    <td>{{ $account->position ?? 'N/A' }}</td>
                    <td><span style="color:{{ $account->status === 'active' ? 'var(--green)' : 'var(--gray)' }};font-weight:600;">{{ ucfirst($account->status) }}</span></td>
                    <td>
                        <div class="action-btns">
                            <button class="btn btn-view btn-xs" onclick="alert('View account: {{ $account->name }}')">👁</button>
                            <button class="btn btn-edit btn-xs" data-modal-target="editTeacherModal"
                                data-id="{{ $account->id }}"
                                data-name="{{ $account->name }}"
                                data-username="{{ $account->username }}"
                                data-email="{{ $account->email }}"
                                data-role="{{ $account->role }}"
                                data-position="{{ $account->position ?? '' }}"
                                data-status="{{ $account->status ?? 'active' }}">Edit</button>
                            <form method="POST" action="{{ route('admin.teachers.destroy', $account->id) }}" style="display:inline;" onsubmit="return confirm('Archive this account?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-archive btn-xs">🗑</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--text-light);">No admin or teacher accounts found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="pagination">
            <span class="page-info">Showing {{ $accounts->firstItem() ?? 0 }} to {{ $accounts->lastItem() ?? 0 }} of {{ $accounts->total() }} entries</span>
            <div class="page-btns">
                @for ($i = 1; $i <= $accounts->lastPage(); $i++)
                    <a href="{{ $accounts->url($i) }}" class="page-btn {{ $accounts->currentPage() == $i ? 'active' : '' }}">{{ $i }}</a>
                @endfor
            </div>
        </div>
    </div>

    <!-- Add Account Modal -->
    <div class="modal-overlay" id="addTeacherModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add Account</h3>
                <p class="subtitle">Fill in the following information</p>
            </div>
            <form method="POST" action="{{ route('admin.teachers.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" placeholder="Enter full name" required style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;">
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" placeholder="Enter username" required style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" placeholder="Enter email" required style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;">
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="Min 8 characters" required style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;">
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="password_confirmation" placeholder="Confirm password" required style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;">
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <select name="role" required style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;">
                            <option value="">Select role</option>
                            <option value="admin">Admin</option>
                            <option value="teacher">Teacher</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Position</label>
                        <input type="text" name="position" placeholder="e.g. Teacher 1" style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" required style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-modal-close>Cancel</button>
                    <button type="submit" class="btn btn-success">Add Account</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Account Modal -->
    <div class="modal-overlay" id="editTeacherModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Account</h3>
                <p class="subtitle">Update account information</p>
            </div>
            <form method="POST" action="" id="editTeacherForm">
                @csrf @method('PUT')
                <div class="modal-body">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" id="editTeacherName" required style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;">
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" id="editTeacherUsername" required style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" id="editTeacherEmail" required style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;">
                    </div>
                    <div class="form-group">
                        <label>New Password (leave blank to keep current)</label>
                        <input type="password" name="password" id="editTeacherPassword" placeholder="Leave blank to keep current" style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;">
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="password_confirmation" id="editTeacherPasswordConfirm" placeholder="Confirm new password" style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;">
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <select name="role" id="editTeacherRole" required style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;">
                            <option value="admin">Admin</option>
                            <option value="teacher">Teacher</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Position</label>
                        <input type="text" name="position" id="editTeacherPosition" placeholder="e.g. Teacher 1" style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" id="editTeacherStatus" required style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="archived">Archived</option>
                        </select>
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
    document.querySelectorAll('[data-modal-target="editTeacherModal"]').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('editTeacherForm').action = '{{ url('/admin/teachers') }}/' + btn.dataset.id;
            document.getElementById('editTeacherName').value = btn.dataset.name;
            document.getElementById('editTeacherUsername').value = btn.dataset.username;
            document.getElementById('editTeacherEmail').value = btn.dataset.email;
            document.getElementById('editTeacherRole').value = btn.dataset.role;
            document.getElementById('editTeacherPosition').value = btn.dataset.position;
            document.getElementById('editTeacherStatus').value = btn.dataset.status;
            document.getElementById('editTeacherPassword').value = '';
            document.getElementById('editTeacherPasswordConfirm').value = '';
            document.getElementById('editTeacherModal').classList.add('show');
        });
    });
});
</script>
@endpush
