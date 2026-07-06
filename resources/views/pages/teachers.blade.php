@extends('layouts.admin')

@section('title', 'EcoCollect - Admin & Teacher Accounts')
@section('page-title', 'Admin & Teacher Accounts')
@section('page-subtitle', 'Manage system user accounts')

@section('content')
    <div class="filter-card">
        <div class="filter-header" onclick="this.classList.toggle('collapsed');this.nextElementSibling.classList.toggle('collapsed')">
            <i class="fas fa-filter"></i> Filters
        </div>
        <div class="filter-body">
            <form method="GET" action="{{ route('admin.teachers') }}" class="filter-form">
                <div class="filter-search">
                    <label>Search</label>
                    <input type="text" name="search" placeholder="Search accounts..." value="{{ request('search') }}">
                </div>
                <div class="filter-search">
                    <label>Role</label>
                    <select name="role">
                        <option value="">All Roles</option>
                        <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="teacher" {{ request('role') === 'teacher' ? 'selected' : '' }}>Teacher</option>
                        <option value="super_admin" {{ request('role') === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                    </select>
                </div>
                <div class="filter-search">
                    <label>Status</label>
                    <select name="status">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>Archived</option>
                    </select>
                </div>
                <div class="filter-controls">
                    <button class="btn btn-filter" type="submit">Filter</button>
                    <a href="{{ route('admin.teachers') }}" class="btn btn-reset">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div class="table-container">
        <div class="table-header">
            <div class="table-header-left"></div>
            <span style="font-size:13px;color:var(--text-light);">New accounts should be created through the Sign Up page.</span>
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
                        <div class="account-actions">
                            <button type="button" class="action-icon-btn view" title="View Account Details"
                                onclick="openViewAccountModal({
                                    id: 'ADM{{ str_pad($account->id, 3, '0', STR_PAD_LEFT) }}',
                                    name: '{{ $account->name }}',
                                    username: '{{ $account->username }}',
                                    email: '{{ $account->email }}',
                                    role: '{{ ucfirst($account->role) }}',
                                    position: '{{ $account->position ?? 'N/A' }}',
                                    status: '{{ ucfirst($account->status) }}',
                                    created_at: '{{ optional($account->created_at)->format('F d, Y h:i A') }}',
                                    updated_at: '{{ optional($account->updated_at)->format('F d, Y h:i A') }}'
                                })">👁</button>
                            <button type="button" class="action-icon-btn edit" title="Edit Account"
                                data-modal-target="editTeacherModal"
                                data-id="{{ $account->id }}"
                                data-name="{{ $account->name }}"
                                data-username="{{ $account->username }}"
                                data-email="{{ $account->email }}"
                                data-role="{{ $account->role }}"
                                data-position="{{ $account->position ?? '' }}"
                                data-status="{{ $account->status ?? 'active' }}">✏️</button>
                            <form method="POST" action="{{ route('admin.teachers.destroy', $account->id) }}" class="inline-action-form" onsubmit="return confirm('Archive this account?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="action-icon-btn delete" title="Delete / Deactivate Account">🗑</button>
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

    <!-- Edit Account Modal -->
    <div class="modal-overlay" id="editTeacherModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Account</h2>
                <p>Update account information</p>
                <button type="button" class="modal-close" onclick="closeEditTeacherModal()">&times;</button>
            </div>
            <form method="POST" action="" id="editTeacherForm" class="modal-form">
                @csrf @method('PUT')
                <div class="modal-body">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" id="editTeacherName" required>
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" id="editTeacherUsername" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" id="editTeacherEmail" required>
                    </div>
                    <div class="password-note">Leave password fields blank if you do not want to change the password.</div>
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="current_password" id="editTeacherCurrentPassword" placeholder="Enter current password">
                        @error('current_password')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label>New Password (leave blank to keep current)</label>
                        <input type="password" name="password" id="editTeacherPassword" placeholder="Leave blank to keep current">
                        @error('password')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="password_confirmation" id="editTeacherPasswordConfirm" placeholder="Confirm new password">
                        @error('password_confirmation')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <select name="role" id="editTeacherRole" required>
                            <option value="admin">Admin</option>
                            <option value="teacher">Teacher</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Position</label>
                        <input type="text" name="position" id="editTeacherPosition" placeholder="e.g. Teacher 1">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" id="editTeacherStatus" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEditTeacherModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Account</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Account Details Modal -->
    <div id="viewAccountModal" class="modal-overlay">
        <div class="modal-content account-view-modal">
            <div class="modal-header">
                <div>
                    <h2>Account Details</h2>
                    <p>View admin or teacher account information</p>
                </div>
                <button type="button" class="modal-close" onclick="closeViewAccountModal()">×</button>
            </div>
            <div class="modal-body">
                <div class="account-detail-card">
                    <div class="account-avatar" id="viewAccountInitials">A</div>
                    <div>
                        <h3 id="viewAccountName">Account Name</h3>
                        <p id="viewAccountRole">Role</p>
                    </div>
                </div>
                <div class="account-detail-grid">
                    <div class="detail-item">
                        <span>Account ID</span>
                        <strong id="viewAccountId">—</strong>
                    </div>
                    <div class="detail-item">
                        <span>Username</span>
                        <strong id="viewAccountUsername">—</strong>
                    </div>
                    <div class="detail-item">
                        <span>Email</span>
                        <strong id="viewAccountEmail">—</strong>
                    </div>
                    <div class="detail-item">
                        <span>Role</span>
                        <strong id="viewAccountRoleText">—</strong>
                    </div>
                    <div class="detail-item">
                        <span>Position</span>
                        <strong id="viewAccountPosition">—</strong>
                    </div>
                    <div class="detail-item">
                        <span>Status</span>
                        <strong id="viewAccountStatus">—</strong>
                    </div>
                    <div class="detail-item">
                        <span>Created At</span>
                        <strong id="viewAccountCreatedAt">—</strong>
                    </div>
                    <div class="detail-item">
                        <span>Updated At</span>
                        <strong id="viewAccountUpdatedAt">—</strong>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeViewAccountModal()">Close</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function openEditTeacherModal() {
    document.getElementById('editTeacherModal').classList.add('show');
    document.body.classList.add('modal-open');
}

function closeEditTeacherModal() {
    document.getElementById('editTeacherModal').classList.remove('show');
    document.body.classList.remove('modal-open');
}

function openViewAccountModal(account) {
    const modal = document.getElementById('viewAccountModal');
    document.getElementById('viewAccountId').textContent = account.id || '—';
    document.getElementById('viewAccountName').textContent = account.name || '—';
    document.getElementById('viewAccountUsername').textContent = account.username || '—';
    document.getElementById('viewAccountEmail').textContent = account.email || '—';
    document.getElementById('viewAccountRole').textContent = account.role || '—';
    document.getElementById('viewAccountRoleText').textContent = account.role || '—';
    document.getElementById('viewAccountPosition').textContent = account.position || '—';
    document.getElementById('viewAccountStatus').textContent = account.status || '—';
    document.getElementById('viewAccountCreatedAt').textContent = account.created_at || '—';
    document.getElementById('viewAccountUpdatedAt').textContent = account.updated_at || '—';
    const initials = account.name
        ? account.name.split(' ').map(function (w) { return w.charAt(0); }).join('').substring(0, 2).toUpperCase()
        : 'A';
    document.getElementById('viewAccountInitials').textContent = initials;
    modal.style.display = 'flex';
    document.body.classList.add('modal-open');
}

function closeViewAccountModal() {
    document.getElementById('viewAccountModal').style.display = 'none';
    document.body.classList.remove('modal-open');
}

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
            document.getElementById('editTeacherCurrentPassword').value = '';
            document.getElementById('editTeacherPassword').value = '';
            document.getElementById('editTeacherPasswordConfirm').value = '';
            openEditTeacherModal();
        });
    });

    document.getElementById('viewAccountModal')?.addEventListener('click', function (event) {
        if (event.target === this) {
            closeViewAccountModal();
        }
    });
});
</script>
@endpush
