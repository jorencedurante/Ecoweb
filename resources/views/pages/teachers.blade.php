@extends('layouts.admin')

@section('title', 'EcoCollect - Admin & Teacher Accounts')
@section('page-title', 'Admin & Teacher Accounts')
@section('page-subtitle', 'Manage system user accounts')

@section('content')
    <div class="filter-card">
        <button type="button" class="filter-header" onclick="this.classList.toggle('collapsed');this.nextElementSibling.classList.toggle('collapsed')" aria-expanded="true">
            <i class="fas fa-filter"></i> Filters
        </button>
        <div class="filter-body">
            <form method="GET" action="{{ route('admin.teachers') }}" class="filter-form" id="teacherFilterForm">
                <div class="filter-search">
                    <label>Search</label>
                    <input type="text" name="search" id="teacherSearch" placeholder="Search accounts..." value="{{ request('search') }}" aria-label="Search accounts" data-filter="search">
                </div>
                <div class="filter-search">
                    <label>Role</label>
                    <select name="role" id="teacherRole" aria-label="Filter by role" data-filter="role">
                        <option value="">All Roles</option>
                        <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="teacher" {{ request('role') === 'teacher' ? 'selected' : '' }}>Teacher</option>
                        <option value="super_admin" {{ request('role') === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                    </select>
                </div>
                <div class="filter-search">
                    <label>Status</label>
                    <select name="status" id="teacherStatus" aria-label="Filter by status" data-filter="status">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        <option value="restricted" {{ request('status') === 'restricted' ? 'selected' : '' }}>Restricted</option>
                        <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>Archived</option>
                    </select>
                </div>
                <div class="filter-controls">
                    <span style="font-size:12px;color:var(--text-light);">Search and filters update automatically.</span>
                </div>
            </form>
        </div>
    </div>

    <div class="table-container">
        <div class="table-header">
            <div class="table-header-left">
                <button type="button" class="btn btn-primary archived-accounts-btn" onclick="openArchivedAccountsModal()" aria-label="View archived accounts">
                    📦 Archived Accounts
                </button>
            </div>
            <span style="font-size:13px;color:var(--text-light);">New accounts should be created through the Sign Up page.</span>
        </div>
        <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Name</th>
                    <th scope="col">Email</th>
                    <th scope="col">Role</th>
                    <th scope="col">Position</th>
                    <th scope="col">Status</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody id="teacherTableBody">
                @forelse($accounts as $account)
                <tr>
                    <td>ADM{{ str_pad($account->id, 3, '0', STR_PAD_LEFT) }}</td>
                    <td><strong>{{ $account->name }}</strong></td>
                    <td>{{ $account->email }}</td>
                    <td>
                        @if(auth()->user()->isSuperAdmin())
                            <span style="display:inline-block;padding:3px 10px;border-radius:12px;font-size:12px;font-weight:600;background:var(--accent-light,#e8f1ff);color:var(--accent,#1a73e8);">{{ ucfirst($account->role) }}</span>
                        @else
                            <span class="role-badge" style="display:inline-block;padding:3px 10px;border-radius:12px;font-size:12px;font-weight:600;background:#eef2f7;color:#475569;">{{ ucfirst($account->role) }}</span>
                        @endif
                    </td>
                    <td>{{ $account->position ?? 'N/A' }}</td>
                    <td><span style="color:{{ $account->status === 'active' ? 'var(--green)' : 'var(--gray)' }};font-weight:600;">{{ ucfirst($account->status) }}</span></td>
                    <td>
                        <div class="account-actions">
                            <button type="button" class="action-icon-btn view" title="View Account Details" aria-label="View account details"
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
                            @if(auth()->user()->isSuperAdmin())
                            <button type="button" class="action-icon-btn edit" title="Edit Account" aria-label="Edit account"
                                data-modal-target="editTeacherModal"
                                data-id="{{ $account->id }}"
                                data-name="{{ $account->name }}"
                                data-username="{{ $account->username }}"
                                data-email="{{ $account->email }}"
                                data-role="{{ $account->role }}"
                                data-position="{{ $account->position ?? '' }}"
                                data-status="{{ $account->status ?? 'active' }}">✏️</button>

                            @if($account->status === 'active')
                            <button type="button" class="action-icon-btn restrict" title="Restrict Account" aria-label="Restrict account"
                                data-id="{{ $account->id }}"
                                data-name="{{ $account->name }}">🔒</button>
                            <form method="POST" action="{{ route('admin.teachers.archive', $account->id) }}" class="inline-action-form archive-form" data-action="archive" data-id="{{ $account->id }}" data-name="{{ $account->name }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="action-icon-btn archive" title="Archive Account" aria-label="Archive account">📦</button>
                            </form>
                            @elseif($account->status === 'restricted')
                            <button type="button" class="action-icon-btn unrestrict" title="Unrestrict Account" aria-label="Unrestrict account"
                                data-id="{{ $account->id }}"
                                data-name="{{ $account->name }}">🔓</button>
                            <form method="POST" action="{{ route('admin.teachers.archive', $account->id) }}" class="inline-action-form archive-form" data-action="archive" data-id="{{ $account->id }}" data-name="{{ $account->name }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="action-icon-btn archive" title="Archive Account" aria-label="Archive account">📦</button>
                            </form>
                            @elseif($account->status === 'archived')
                            <button type="button" class="action-icon-btn reactivate" title="Reactivate Account" aria-label="Reactivate account"
                                data-id="{{ $account->id }}"
                                data-name="{{ $account->name }}">🔄</button>
                            @endif
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr id="teacherNoResults"><td colspan="7" style="text-align:center;padding:30px;color:var(--text-light);">No matching accounts found.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
        <div class="pagination">
            <span class="page-info" id="teacherPageInfo">Showing {{ $accounts->firstItem() ?? 0 }} to {{ $accounts->lastItem() ?? 0 }} of {{ $accounts->total() }} entries</span>
            <div class="page-btns" id="teacherPagination">
                @for ($i = 1; $i <= $accounts->lastPage(); $i++)
                    <a href="{{ $accounts->url($i) }}" class="page-btn {{ $accounts->currentPage() == $i ? 'active' : '' }}" data-page="{{ $i }}">{{ $i }}</a>
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
                <button type="button" class="modal-close" onclick="closeEditTeacherModal()" aria-label="Close">&times;</button>
            </div>
            <form method="POST" action="" id="editTeacherForm" class="modal-form">
                @csrf @method('PUT')
                <div class="modal-body">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" id="editTeacherName" required aria-label="Full name">
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" id="editTeacherUsername" required aria-label="Username">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" id="editTeacherEmail" required aria-label="Email">
                    </div>
                    <div class="password-note">Leave password fields blank if you do not want to change the password.</div>
                    <div class="form-group">
                        <label>Current Password</label>
                        <div class="password-field-wrapper">
                            <input type="password" name="current_password" id="editTeacherCurrentPassword" placeholder="Enter current password" aria-label="Current password" aria-describedby="current_password-error">
                            <button type="button" class="password-toggle" data-target="editTeacherCurrentPassword" aria-label="Show or hide current password">👁</button>
                        </div>
                        @error('current_password')
                            <div class="field-error" id="current_password-error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label>New Password (leave blank to keep current)</label>
                        <div class="password-field-wrapper">
                            <input type="password" name="password" id="editTeacherPassword" placeholder="Leave blank to keep current" aria-label="New password" aria-describedby="password-error">
                            <button type="button" class="password-toggle" data-target="editTeacherPassword" aria-label="Show or hide new password">👁</button>
                        </div>
                        @error('password')
                            <div class="field-error" id="password-error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <div class="password-field-wrapper">
                            <input type="password" name="password_confirmation" id="editTeacherPasswordConfirm" placeholder="Confirm new password" aria-label="Confirm new password" aria-describedby="password_confirmation-error">
                            <button type="button" class="password-toggle" data-target="editTeacherPasswordConfirm" aria-label="Show or hide confirm password">👁</button>
                        </div>
                        @error('password_confirmation')
                            <div class="field-error" id="password_confirmation-error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        @if(auth()->user()->isSuperAdmin())
                        <select name="role" id="editTeacherRole" required aria-label="Role">
                            <option value="admin">Admin</option>
                            <option value="teacher">Teacher</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                        @else
                        <input type="text" id="editTeacherRoleText" disabled style="background:#f1f5f9;cursor:not-allowed;" aria-label="Role">
                        <input type="hidden" name="role" value="" aria-label="Role">
                        @endif
                    </div>
                    <div class="form-group">
                        <label>Position</label>
                        <input type="text" name="position" id="editTeacherPosition" placeholder="e.g. Teacher 1" aria-label="Position">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" id="editTeacherStatus" required aria-label="Status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEditTeacherModal()">Cancel</button>
                    <div class="password-confirm-alert" style="display: none;">
                        Password didn't match. Try again.
                    </div>
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
                <button type="button" class="modal-close" onclick="closeViewAccountModal()" aria-label="Close">×</button>
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

    <!-- Reactivate Account Confirmation Modal -->
    <div id="reactivateConfirmModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-icon warn">
                    <span class="icon">⚠</span>
                </div>
                <div>
                    <h2>Reactivate Account?</h2>
                    <p id="reactivateConfirmMessage">Are you sure you want to reactivate "<span id="reactivateAccountName"></span>"?</p>
                </div>
                <button type="button" class="modal-close" onclick="closeReactivateConfirmModal()" aria-label="Close">×</button>
            </div>
            <div class="modal-body">
                <p>This account will regain access to EcoCollect.</p>
                <input type="hidden" id="reactivateAccountId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeReactivateConfirmModal()">Cancel</button>
                <button type="button" class="btn btn-success" onclick="confirmReactivateAccount()" id="btnConfirmReactivate">Reactivate Account</button>
            </div>
        </div>
    </div>

    <!-- Archive Account Confirmation Modal -->
    <div id="archiveConfirmModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-icon danger">
                    <span class="icon">🗑</span>
                </div>
                <div>
                    <h2>Archive Account?</h2>
                    <p id="archiveConfirmMessage">Are you sure you want to archive "<span id="archiveAccountName"></span>"?</p>
                </div>
                <button type="button" class="modal-close" onclick="closeArchiveConfirmModal()" aria-label="Close">×</button>
            </div>
            <div class="modal-body">
                <p>This account will lose access to EcoCollect. Existing student records and activity history will remain unchanged.</p>
                <input type="hidden" id="archiveAccountId">
                <input type="hidden" id="archiveAccountUrl">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeArchiveConfirmModal()">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="confirmArchiveAccount()" id="btnConfirmArchive">Archive Account</button>
            </div>
    </div>
</div>

    <!-- Restrict Account Confirmation Modal -->
    <div id="restrictConfirmModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-icon warn">
                    <span class="icon">🔒</span>
                </div>
                <div>
                    <h2>Restrict Account?</h2>
                    <p id="restrictConfirmMessage">Are you sure you want to restrict "<span id="restrictAccountName"></span>"?</p>
                </div>
                <button type="button" class="modal-close" onclick="closeRestrictConfirmModal()" aria-label="Close">×</button>
            </div>
            <div class="modal-body">
                <p>This account will be temporarily suspended and will lose access to EcoCollect. Existing student records and activity history will remain unchanged.</p>
                <input type="hidden" id="restrictAccountId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeRestrictConfirmModal()">Cancel</button>
                <button type="button" class="btn btn-warning" onclick="confirmRestrictAccount()" id="btnConfirmRestrict">Restrict Account</button>
            </div>
        </div>
    </div>

    <!-- Unrestrict Account Confirmation Modal -->
    <div id="unrestrictConfirmModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-icon info">
                    <span class="icon">🔓</span>
                </div>
                <div>
                    <h2>Unrestrict Account?</h2>
                    <p id="unrestrictConfirmMessage">Are you sure you want to unrestrict "<span id="unrestrictAccountName"></span>"?</p>
                </div>
                <button type="button" class="modal-close" onclick="closeUnrestrictConfirmModal()" aria-label="Close">×</button>
            </div>
            <div class="modal-body">
                <p>This account will be restored to Active status and will regain access to EcoCollect.</p>
                <input type="hidden" id="unrestrictAccountId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeUnrestrictConfirmModal()">Cancel</button>
                <button type="button" class="btn btn-success" onclick="confirmUnrestrictAccount()" id="btnConfirmUnrestrict">Unrestrict Account</button>
            </div>
        </div>
    </div>

    <style>
    #archiveConfirmModal .modal-overlay,
    #reactivateConfirmModal .modal-overlay,
    #restrictConfirmModal .modal-overlay,
    #unrestrictConfirmModal .modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.45);
        backdrop-filter: blur(2px);
        z-index: 2000;
        align-items: center;
        justify-content: center;
        padding: 24px;
    }
    #archiveConfirmModal .modal-overlay.show,
    #reactivateConfirmModal .modal-overlay.show,
    #restrictConfirmModal .modal-overlay.show,
    #unrestrictConfirmModal .modal-overlay.show {
        display: flex;
    }
    #archiveConfirmModal .modal-content,
    #reactivateConfirmModal .modal-content,
    #restrictConfirmModal .modal-content,
    #unrestrictConfirmModal .modal-content {
        background: #ffffff;
        border-radius: 14px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        width: 100%;
        max-width: 420px;
        padding: 0;
        border: 1px solid rgba(0, 0, 0, 0.08);
        animation: modalFadeIn 0.2s ease;
    }
    @keyframes modalFadeIn {
        from { opacity: 0; transform: translateY(-8px); }
        to { opacity: 1; transform: translateY(0); }
    }
    #archiveConfirmModal .modal-header,
    #reactivateConfirmModal .modal-header,
    #restrictConfirmModal .modal-header,
    #unrestrictConfirmModal .modal-header {
        display: flex;
        gap: 16px;
        align-items: flex-start;
        padding: 24px 24px 16px 24px;
    }
    #archiveConfirmModal .modal-icon,
    #reactivateConfirmModal .modal-icon,
    #restrictConfirmModal .modal-icon,
    #unrestrictConfirmModal .modal-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 10px;
        flex-shrink: 0;
    }
    #archiveConfirmModal .modal-icon {
        background: rgba(239, 68, 68, 0.12);
        color: #dc2626;
    }
    #reactivateConfirmModal .modal-icon {
        background: rgba(34, 197, 94, 0.12);
        color: #16a34a;
    }
    #restrictConfirmModal .modal-icon {
        background: rgba(245, 158, 11, 0.12);
        color: #d97706;
    }
    #unrestrictConfirmModal .modal-icon {
        background: rgba(59, 130, 246, 0.12);
        color: #2563eb;
    }
    #archiveConfirmModal .modal-icon .icon,
    #reactivateConfirmModal .modal-icon .icon {
        font-size: 20px;
        line-height: 1;
    }
    #archiveConfirmModal .modal-header h2,
    #reactivateConfirmModal .modal-header h2,
    #restrictConfirmModal .modal-header h2,
    #unrestrictConfirmModal .modal-header h2 {
        margin: 0 0 6px 0;
        font-size: 18px;
        font-weight: 700;
        color: #111827;
        letter-spacing: -0.01em;
    }
    #archiveConfirmModal .modal-header p,
    #reactivateConfirmModal .modal-header p,
    #restrictConfirmModal .modal-header p,
    #unrestrictConfirmModal .modal-header p {
        margin: 0;
        font-size: 14px;
        color: #4b5563;
        line-height: 1.5;
    }
    #archiveConfirmModal .modal-body,
    #reactivateConfirmModal .modal-body,
    #restrictConfirmModal .modal-body,
    #unrestrictConfirmModal .modal-body {
        padding: 0 24px 20px 24px;
    }
    #archiveConfirmModal .modal-body p,
    #reactivateConfirmModal .modal-body p,
    #restrictConfirmModal .modal-body p,
    #unrestrictConfirmModal .modal-body p {
        margin: 0 0 16px 0;
        font-size: 14px;
        color: #374151;
        line-height: 1.55;
    }
    #archiveConfirmModal .modal-body p strong,
    #reactivateConfirmModal .modal-body p strong,
    #restrictConfirmModal .modal-body p strong,
    #unrestrictConfirmModal .modal-body p strong {
        color: #111827;
    }
    #archiveConfirmModal .modal-footer,
    #reactivateConfirmModal .modal-footer,
    #restrictConfirmModal .modal-footer,
    #unrestrictConfirmModal .modal-footer {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        padding: 16px 24px 24px 24px;
        border-top: 1px solid rgba(0, 0, 0, 0.06);
    }
    #archiveConfirmModal .modal-footer .btn,
    #reactivateConfirmModal .modal-footer .btn,
    #restrictConfirmModal .modal-footer .btn,
    #unrestrictConfirmModal .modal-footer .btn {
        padding: 10px 20px;
        font-size: 14px;
        font-weight: 600;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        min-width: 100px;
    }
    #archiveConfirmModal .modal-footer .btn-secondary,
    #reactivateConfirmModal .modal-footer .btn-secondary,
    #restrictConfirmModal .modal-footer .btn-secondary,
    #unrestrictConfirmModal .modal-footer .btn-secondary {
        background: #f3f4f6;
        color: #374151;
    }
    #archiveConfirmModal .modal-footer .btn-secondary:hover,
    #reactivateConfirmModal .modal-footer .btn-secondary:hover,
    #restrictConfirmModal .modal-footer .btn-secondary:hover,
    #unrestrictConfirmModal .modal-footer .btn-secondary:hover {
        background: #e5e7eb;
    }
    #archiveConfirmModal .modal-footer .btn-danger,
    #reactivateConfirmModal .modal-footer .btn-success,
    #restrictConfirmModal .modal-footer .btn-warning,
    #unrestrictConfirmModal .modal-footer .btn-success {
        color: #ffffff;
    }
    #archiveConfirmModal .modal-footer .btn-danger {
        background: #dc2626;
    }
    #archiveConfirmModal .modal-footer .btn-danger:hover:not(:disabled) {
        background: #b91c1c;
    }
    #reactivateConfirmModal .modal-footer .btn-success {
        background: #16a34a;
    }
    #reactivateConfirmModal .modal-footer .btn-success:hover:not(:disabled) {
        background: #15803d;
    }
    #restrictConfirmModal .modal-footer .btn-warning {
        background: #d97706;
    }
    #restrictConfirmModal .modal-footer .btn-warning:hover:not(:disabled) {
        background: #b45309;
    }
    #unrestrictConfirmModal .modal-footer .btn-success {
        background: #16a34a;
    }
    #unrestrictConfirmModal .modal-footer .btn-success:hover:not(:disabled) {
        background: #15803d;
    }
    #archiveConfirmModal .modal-footer .btn:disabled,
    #reactivateConfirmModal .modal-footer .btn:disabled,
    #restrictConfirmModal .modal-footer .btn:disabled,
    #unrestrictConfirmModal .modal-footer .btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    #archiveConfirmModal .modal-close,
    #reactivateConfirmModal .modal-close,
    #restrictConfirmModal .modal-close,
    #unrestrictConfirmModal .modal-close {
        background: none;
        border: none;
        font-size: 24px;
        line-height: 1;
        color: #9ca3af;
        cursor: pointer;
        padding: 0;
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
    }
    #archiveConfirmModal .modal-close:hover,
    #reactivateConfirmModal .modal-close:hover,
    #restrictConfirmModal .modal-close:hover,
    #unrestrictConfirmModal .modal-close:hover,
    #archivedAccountsModal .modal-close:hover,
    #unarchiveConfirmModal .modal-close:hover {
        color: #111827;
        background: rgba(0, 0, 0, 0.05);
    }
    #archivedAccountsModal .modal-overlay,
    #unarchiveConfirmModal .modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.45);
        backdrop-filter: blur(2px);
        z-index: 2000;
        align-items: center;
        justify-content: center;
        padding: 24px;
    }
    #archivedAccountsModal .modal-overlay.show,
    #unarchiveConfirmModal .modal-overlay.show {
        display: flex;
    }
    #archivedAccountsModal .modal-content,
    #unarchiveConfirmModal .modal-content {
        background: #ffffff;
        border-radius: 14px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        width: 100%;
        max-width: 700px;
        padding: 0;
        border: 1px solid rgba(0, 0, 0, 0.08);
        animation: modalFadeIn 0.2s ease;
    }
    #archivedAccountsModal .modal-header,
    #unarchiveConfirmModal .modal-header {
        display: flex;
        gap: 16px;
        align-items: flex-start;
        padding: 24px 24px 16px 24px;
    }
    #archivedAccountsModal .modal-icon,
    #unarchiveConfirmModal .modal-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 10px;
        flex-shrink: 0;
    }
    #archivedAccountsModal .modal-icon {
        background: rgba(239, 68, 68, 0.12);
        color: #dc2626;
    }
    #unarchiveConfirmModal .modal-icon {
        background: rgba(34, 197, 94, 0.12);
        color: #16a34a;
    }
    #archivedAccountsModal .modal-header h2,
    #unarchiveConfirmModal .modal-header h2 {
        margin: 0 0 6px 0;
        font-size: 18px;
        font-weight: 700;
        color: #111827;
        letter-spacing: -0.01em;
    }
    #archivedAccountsModal .modal-header p,
    #unarchiveConfirmModal .modal-header p {
        margin: 0;
        font-size: 14px;
        color: #4b5563;
        line-height: 1.5;
    }
    #archivedAccountsModal .modal-body,
    #unarchiveConfirmModal .modal-body {
        padding: 0 24px 20px 24px;
    }
    #archivedAccountsModal .modal-body p,
    #unarchiveConfirmModal .modal-body p {
        margin: 0 0 16px 0;
        font-size: 14px;
        color: #374151;
        line-height: 1.55;
    }
    #archivedAccountsModal .modal-footer,
    #unarchiveConfirmModal .modal-footer {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        padding: 16px 24px 24px 24px;
        border-top: 1px solid rgba(0, 0, 0, 0.06);
    }
    #archivedAccountsModal .modal-footer .btn,
    #unarchiveConfirmModal .modal-footer .btn {
        padding: 10px 20px;
        font-size: 14px;
        font-weight: 600;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        min-width: 100px;
    }
    #archivedAccountsModal .modal-footer .btn-secondary,
    #unarchiveConfirmModal .modal-footer .btn-secondary {
        background: #f3f4f6;
        color: #374151;
    }
    #archivedAccountsModal .modal-footer .btn-secondary:hover,
    #unarchiveConfirmModal .modal-footer .btn-secondary:hover {
        background: #e5e7eb;
    }
    #unarchiveConfirmModal .modal-footer .btn-success {
        background: #16a34a;
        color: #ffffff;
    }
    #unarchiveConfirmModal .modal-footer .btn-success:hover:not(:disabled) {
        background: #15803d;
    }
    #archivedAccountsModal .modal-footer .btn:disabled,
    #unarchiveConfirmModal .modal-footer .btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    #archivedAccountsModal .modal-close,
    #unarchiveConfirmModal .modal-close {
        background: none;
        border: none;
        font-size: 24px;
        line-height: 1;
        color: #9ca3af;
        cursor: pointer;
        padding: 0;
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
    }
    </style>

    <!-- Archived Accounts Modal -->
    <div id="archivedAccountsModal" class="modal-overlay">
        <div class="modal-content archived-accounts-content">
            <div class="modal-header">
                <div class="modal-icon archived">
                    <span class="icon">📦</span>
                </div>
                <div>
                    <h2>Archived Accounts</h2>
                    <p>View and manage archived teacher and admin accounts.</p>
                </div>
                <button type="button" class="modal-close" onclick="closeArchivedAccountsModal()" aria-label="Close">×</button>
            </div>
            <div class="modal-body">
                <div class="search-box">
                    <input type="text" id="archivedSearch" placeholder="Search archived accounts..." aria-label="Search archived accounts">
                </div>
                <div class="archived-table-container" style="max-height:400px;overflow-y:auto;margin-top:12px;border:1px solid rgba(0,0,0,0.08);border-radius:8px;">
                    <table class="archived-table" width="100%">
                        <thead>
                            <tr>
                                <th>Account ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Position</th>
                                <th>Date Archived</th>
                                <th style="width:110px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="archivedAccountsTableBody"></tbody>
                    </table>
                    <div id="archivedNoResults" style="display:none;padding:30px;text-align:center;color:var(--text-light);">No archived accounts found.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeArchivedAccountsModal()">Close</button>
            </div>
        </div>
    </div>

    <!-- Unarchive Account Confirmation Modal -->
    <div id="unarchiveConfirmModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-icon info">
                    <span class="icon">🔄</span>
                </div>
                <div>
                    <h2>Unarchive Account?</h2>
                    <p id="unarchiveConfirmMessage">Restore access for "<span id="unarchiveAccountName"></span>"?</p>
                </div>
                <button type="button" class="modal-close" onclick="closeUnarchiveConfirmModal()" aria-label="Close">×</button>
            </div>
            <div class="modal-body">
                <p>The account will be restored to Active status and will regain access to EcoCollect. Previous student records and activity history will remain unchanged.</p>
                <input type="hidden" id="unarchiveAccountId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeUnarchiveConfirmModal()">Cancel</button>
                <button type="button" class="btn btn-success" onclick="confirmUnarchiveAccount()" id="btnConfirmUnarchive">Unarchive Account</button>
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

function openReactivateConfirmModal(accountId, accountName) {
    document.getElementById('reactivateAccountId').value = accountId;
    document.getElementById('reactivateConfirmModal').classList.add('show');
    document.body.classList.add('modal-open');
}

function closeReactivateConfirmModal() {
    document.getElementById('reactivateConfirmModal').classList.remove('show');
    document.body.classList.remove('modal-open');
}

function openArchiveConfirmModal(accountId, accountName, archiveUrl) {
    document.getElementById('archiveAccountId').value = accountId;
    document.getElementById('archiveAccountUrl').value = archiveUrl;
    document.getElementById('archiveAccountName').textContent = escapeHtml(accountName);
    document.getElementById('archiveConfirmModal').classList.add('show');
    document.body.classList.add('modal-open');
}

function closeArchiveConfirmModal() {
    document.getElementById('archiveConfirmModal').classList.remove('show');
    document.body.classList.remove('modal-open');
}

function openRestrictConfirmModal(accountId, accountName) {
    document.getElementById('restrictAccountId').value = accountId;
    document.getElementById('restrictAccountName').textContent = escapeHtml(accountName);
    document.getElementById('restrictConfirmModal').classList.add('show');
    document.body.classList.add('modal-open');
}

function closeRestrictConfirmModal() {
    document.getElementById('restrictConfirmModal').classList.remove('show');
    document.body.classList.remove('modal-open');
}

function openUnrestrictConfirmModal(accountId, accountName) {
    document.getElementById('unrestrictAccountId').value = accountId;
    document.getElementById('unrestrictAccountName').textContent = escapeHtml(accountName);
    document.getElementById('unrestrictConfirmModal').classList.add('show');
    document.body.classList.add('modal-open');
}

function closeUnrestrictConfirmModal() {
    document.getElementById('unrestrictConfirmModal').classList.remove('show');
    document.body.classList.remove('modal-open');
}

function confirmArchiveAccount() {
    var accountId = document.getElementById('archiveAccountId').value;
    var archiveUrl = document.getElementById('archiveAccountUrl').value;
    var btn = document.getElementById('btnConfirmArchive');

    closeArchiveConfirmModal();

    btn.disabled = true;
    btn.textContent = 'Archiving…';

    var formData = new FormData();
    formData.append('_method', 'PATCH');

    fetch(archiveUrl, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: formData
    })
    .then(function (response) {
        if (response.status === 403) {
            throw new Error('You are not authorized to archive accounts.');
        }
        if (response.status === 422) {
            return response.json().then(function (err) {
                throw new Error(err.error || 'Validation failed.');
            });
        }
        if (!response.ok) {
            var ct = response.headers.get('content-type') || '';
            if (ct.indexOf('application/json') === -1) {
                throw new Error('Server returned HTML instead of JSON. Request may have been redirected.');
            }
            throw new Error('Archive failed (HTTP ' + response.status + ')');
        }
        var ct = response.headers.get('content-type') || '';
        if (ct.indexOf('application/json') === -1) {
            throw new Error('Server returned HTML instead of JSON. Request may have been redirected.');
        }
        return response.json();
    })
    .then(function (data) {
        var currentUrl = new URL(window.location);
        var page = currentUrl.searchParams.get('page') || 1;
        fetchTeacherAccounts(parseInt(page, 10));
        showTeacherToast(data.success || 'Account archived successfully.');
    })
    .catch(function (error) {
        console.error('Archive failed:', error);
        showTeacherToast(error.message || 'Failed to archive account.', true);
    })
    .finally(function () {
        btn.disabled = false;
        btn.textContent = 'Archive Account';
    });
}

function confirmRestrictAccount() {
    var accountId = document.getElementById('restrictAccountId').value;
    var url = '{{ url('/admin/teachers') }}' + '/' + accountId + '/restrict';
    var btn = document.getElementById('btnConfirmRestrict');

    closeRestrictConfirmModal();

    btn.disabled = true;
    btn.textContent = 'Restricting…';

    fetch(url, {
        method: 'PATCH',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(function (response) {
        if (response.status === 403) {
            throw new Error('You are not authorized to restrict accounts.');
        }
        if (response.status === 422) {
            return response.json().then(function (err) {
                throw new Error(err.error || 'Validation failed.');
            });
        }
        if (!response.ok) throw new Error('Restrict failed (HTTP ' + response.status + ')');
        return response.json();
    })
    .then(function (data) {
        var currentUrl = new URL(window.location);
        var page = currentUrl.searchParams.get('page') || 1;
        fetchTeacherAccounts(parseInt(page, 10));
        showTeacherToast(data.success || 'Account restricted successfully.');
    })
    .catch(function (error) {
        console.error('Restrict failed:', error);
        showTeacherToast(error.message || 'Failed to restrict account.', true);
    })
    .finally(function () {
        btn.disabled = false;
        btn.textContent = 'Restrict Account';
    });
}

function confirmUnrestrictAccount() {
    var accountId = document.getElementById('unrestrictAccountId').value;
    var url = '{{ url('/admin/teachers') }}' + '/' + accountId + '/unrestrict';
    var btn = document.getElementById('btnConfirmUnrestrict');

    closeUnrestrictConfirmModal();

    btn.disabled = true;
    btn.textContent = 'Unrestricting…';

    fetch(url, {
        method: 'PATCH',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(function (response) {
        if (response.status === 403) {
            throw new Error('You are not authorized to unrestrict accounts.');
        }
        if (response.status === 422) {
            return response.json().then(function (err) {
                throw new Error(err.error || 'Validation failed.');
            });
        }
        if (!response.ok) throw new Error('Unrestrict failed (HTTP ' + response.status + ')');
        return response.json();
    })
    .then(function (data) {
        var currentUrl = new URL(window.location);
        var page = currentUrl.searchParams.get('page') || 1;
        fetchTeacherAccounts(parseInt(page, 10));
        showTeacherToast(data.success || 'Account unrestricted successfully.');
    })
    .catch(function (error) {
        console.error('Unrestrict failed:', error);
        showTeacherToast(error.message || 'Failed to unrestrict account.', true);
    })
    .finally(function () {
        btn.disabled = false;
        btn.textContent = 'Archive Account';
    });
}

var archivedDebounceTimer = null;

function openArchivedAccountsModal() {
    document.getElementById('archivedAccountsModal').classList.add('show');
    document.body.classList.add('modal-open');
    document.getElementById('archivedSearch').value = '';
    fetchArchivedAccounts(1);
}

function closeArchivedAccountsModal() {
    document.getElementById('archivedAccountsModal').classList.remove('show');
    document.body.classList.remove('modal-open');
    clearTimeout(archivedDebounceTimer);
}

function openUnarchiveConfirmModal(accountId, accountName) {
    document.getElementById('unarchiveAccountId').value = accountId;
    document.getElementById('unarchiveAccountName').textContent = escapeHtml(accountName);
    document.getElementById('unarchiveConfirmModal').classList.add('show');
    document.body.classList.add('modal-open');
}

function closeUnarchiveConfirmModal() {
    document.getElementById('unarchiveConfirmModal').classList.remove('show');
    document.body.classList.remove('modal-open');
}

function renderArchivedAccountRow(account) {
    var idDisplay = 'ADM' + String(account.id).padStart(3, '0');
    var archivedAt = account.updated_at ? new Date(account.updated_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : '—';
    var roleHtml = '<span style="display:inline-block;padding:3px 10px;border-radius:12px;font-size:12px;font-weight:600;background:#eef2f7;color:#475569;">' + ucfirst(account.role) + '</span>';

    return '<tr>' +
        '<td>' + idDisplay + '</td>' +
        '<td><strong>' + escapeHtml(account.name) + '</strong></td>' +
        '<td>' + escapeHtml(account.email) + '</td>' +
        '<td>' + roleHtml + '</td>' +
        '<td>' + escapeHtml(account.position ?? 'N/A') + '</td>' +
        '<td><span style="font-size:12px;color:var(--text-light);">' + archivedAt + '</span></td>' +
        '<td>' +
        '<button type="button" class="action-icon-btn unarchive" title="Unarchive Account" aria-label="Unarchive account" data-id="' + account.id + '" data-name="' + escapeHtml(account.name) + '">🔄</button>' +
        '</td>' +
        '</tr>';
}

function fetchArchivedAccounts(page) {
    if (page === undefined) page = 1;
    var search = document.getElementById('archivedSearch').value;

    var params = { page: page };
    if (search) params.search = search;

    fetch('{{ route('admin.teachers.archived') }}' + '?' + new URLSearchParams(params).toString(), {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(function (response) {
        if (response.status === 403) {
            throw new Error('You are not authorized to view archived accounts.');
        }
        if (!response.ok) throw new Error('Failed to load archived accounts (HTTP ' + response.status + ')');
        return response.json();
    })
    .then(function (data) {
        var tbody = document.getElementById('archivedAccountsTableBody');
        var noResults = document.getElementById('archivedNoResults');

        if (!data.accounts || data.accounts.length === 0) {
            tbody.innerHTML = '';
            noResults.style.display = 'block';
        } else {
            noResults.style.display = 'none';
            tbody.innerHTML = data.accounts.map(function (acct) { return renderArchivedAccountRow(acct); }).join('');
        }
    })
    .catch(function (error) {
        console.error('Failed to load archived accounts:', error);
        var tbody = document.getElementById('archivedAccountsTableBody');
        var noResults = document.getElementById('archivedNoResults');
        tbody.innerHTML = '';
        noResults.textContent = 'Error loading archived accounts.';
        noResults.style.display = 'block';
    });
}

function confirmUnarchiveAccount() {
    var accountId = document.getElementById('unarchiveAccountId').value;
    var url = '{{ url('/admin/teachers') }}' + '/' + accountId + '/reactivate';
    var btn = document.getElementById('btnConfirmUnarchive');

    closeUnarchiveConfirmModal();

    btn.disabled = true;
    btn.textContent = 'Unarchiving…';

    fetch(url, {
        method: 'PATCH',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(function (response) {
        if (response.status === 403) {
            throw new Error('You are not authorized to unarchive accounts.');
        }
        if (response.status === 422) {
            return response.json().then(function (err) {
                throw new Error(err.error || 'Validation failed.');
            });
        }
        if (!response.ok) throw new Error('Unarchive failed (HTTP ' + response.status + ')');
        return response.json();
    })
    .then(function (data) {
        fetchArchivedAccounts(1);
        var currentUrl = new URL(window.location);
        var page = currentUrl.searchParams.get('page') || 1;
        fetchTeacherAccounts(parseInt(page, 10));
        showTeacherToast(data.success || 'Account unarchived successfully.');
    })
    .catch(function (error) {
        console.error('Unarchive failed:', error);
        showTeacherToast(error.message || 'Failed to unarchive account.', true);
    })
    .finally(function () {
        btn.disabled = false;
        btn.textContent = 'Unarchive Account';
    });
}

function confirmReactivateAccount() {
    var accountId = document.getElementById('reactivateAccountId').value;
    var url = '{{ url('/admin/teachers') }}' + '/' + accountId + '/reactivate';
    var btn = document.getElementById('btnConfirmReactivate');

    closeReactivateConfirmModal();

    btn.disabled = true;
    btn.textContent = 'Reactivating…';

    fetch(url, {
        method: 'PATCH',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(function (response) {
        if (response.status === 403) {
            throw new Error('You are not authorized to reactivate accounts.');
        }
        if (response.status === 422) {
            return response.json().then(function (err) {
                throw new Error(err.error || 'Validation failed.');
            });
        }
        if (!response.ok) throw new Error('Reactivate failed (HTTP ' + response.status + ')');
        return response.json();
    })
    .then(function (data) {
        var currentUrl = new URL(window.location);
        var page = currentUrl.searchParams.get('page') || 1;
        fetchTeacherAccounts(parseInt(page, 10));
        showTeacherToast(data.success || 'Account reactivated successfully.');
    })
    .catch(function (error) {
        console.error('Reactivate failed:', error);
        showTeacherToast(error.message || 'Failed to reactivate account.', true);
    })
    .finally(function () {
        btn.disabled = false;
        btn.textContent = 'Reactivate Account';
    });
}

function showTeacherToast(message, isError) {
    var existing = document.getElementById('teacherToast');
    if (existing) existing.remove();

    var toast = document.createElement('div');
    toast.id = 'teacherToast';
    toast.role = 'alert';
    toast.textContent = message;
    toast.style = 'position:fixed;top:20px;right:20px;padding:12px 16px;border-radius:8px;font-size:14px;font-weight:500;z-index:10000;display:flex;align-items:center;gap:8px;' +
        (isError
            ? 'background:rgba(239,83,80,0.08);border:1px solid var(--red);color:var(--red-dark);'
            : 'background:rgba(0,200,83,0.08);border:1px solid #22c55e;color:#166534;');
    document.body.appendChild(toast);

    setTimeout(function () {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.5s ease';
        setTimeout(function () { toast.remove(); }, 500);
    }, 4000);
}

var teacherRequestId = 0;
var teacherDebounceTimer = null;

function renderAccountRow(account) {
    var idDisplay = 'ADM' + String(account.id).padStart(3, '0');
    var roleBadge = '{{ auth()->user()->isSuperAdmin() ? "" : "" }}';
    var roleHtml = '<span style="display:inline-block;padding:3px 10px;border-radius:12px;font-size:12px;font-weight:600;background:#eef2f7;color:#475569;">' + ucfirst(account.role) + '</span>';
    if ({{ auth()->user()->isSuperAdmin() ? 'true' : 'false' }}) {
        roleHtml = '<span style="display:inline-block;padding:3px 10px;border-radius:12px;font-size:12px;font-weight:600;background:var(--accent-light,#e8f1ff);color:var(--accent,#1a73e8);">' + ucfirst(account.role) + '</span>';
    }
    var statusColor = account.status === 'active' ? 'var(--green)' : (account.status === 'restricted' ? 'var(--orange)' : 'var(--gray)');
    var position = account.position || 'N/A';
    var createdAt = account.created_at ? new Date(account.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : '—';
    var updatedAt = account.updated_at ? new Date(account.updated_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : '—';

    var editBtn = '', restrictBtn = '', unrestrictBtn = '', archiveBtn = '', reactivateBtn = '';
    if ({{ auth()->user()->isSuperAdmin() ? 'true' : 'false' }}) {
        editBtn = '<button type="button" class="action-icon-btn edit" title="Edit Account" aria-label="Edit account" data-modal-target="editTeacherModal" data-id="' + account.id + '" data-name="' + escapeHtml(account.name) + '" data-username="' + escapeHtml(account.username) + '" data-email="' + escapeHtml(account.email) + '" data-role="' + account.role + '" data-position="' + escapeHtml(account.position ?? '') + '" data-status="' + (account.status ?? 'active') + '">✏️</button>';
        if (account.status === 'active') {
            restrictBtn = '<button type="button" class="action-icon-btn restrict" title="Restrict Account" aria-label="Restrict account" data-id="' + account.id + '" data-name="' + escapeHtml(account.name) + '">🔒</button>';
            archiveBtn = '<form method="POST" action="' + '{{ url('/admin/teachers') }}' + '/' + account.id + '/archive' + '" class="inline-action-form archive-form" data-id="' + account.id + '" data-name="' + escapeHtml(account.name) + '">' + '{{ csrf_field() }}' + '<input name="_method" value="PATCH" type="hidden">' + '<button type="submit" class="action-icon-btn archive" title="Archive Account" aria-label="Archive account">📦</button></form>';
        } else if (account.status === 'restricted') {
            unrestrictBtn = '<button type="button" class="action-icon-btn unrestrict" title="Unrestrict Account" aria-label="Unrestrict account" data-id="' + account.id + '" data-name="' + escapeHtml(account.name) + '">🔓</button>';
            archiveBtn = '<form method="POST" action="' + '{{ url('/admin/teachers') }}' + '/' + account.id + '/archive' + '" class="inline-action-form archive-form" data-id="' + account.id + '" data-name="' + escapeHtml(account.name) + '">' + '{{ csrf_field() }}' + '<input name="_method" value="PATCH" type="hidden">' + '<button type="submit" class="action-icon-btn archive" title="Archive Account" aria-label="Archive account">📦</button></form>';
        } else if (account.status === 'archived') {
            reactivateBtn = '<button type="button" class="action-icon-btn reactivate" title="Reactivate Account" aria-label="Reactivate account" data-id="' + account.id + '" data-name="' + escapeHtml(account.name) + '">🔄</button>';
        }
    }

    return '<tr>' +
        '<td>' + idDisplay + '</td>' +
        '<td><strong>' + escapeHtml(account.name) + '</strong></td>' +
        '<td>' + escapeHtml(account.email) + '</td>' +
        '<td>' + roleHtml + '</td>' +
        '<td>' + escapeHtml(position) + '</td>' +
        '<td><span style="color:' + statusColor + ';font-weight:600;">' + ucfirst(account.status) + '</span></td>' +
        '<td><div class="account-actions">' +
        '<button type="button" class="action-icon-btn view" title="View Account Details" aria-label="View account details" onclick="openViewAccountModal({\'id\':\'' + idDisplay + '\',\'name\':\'' + escapeJs(account.name) + '\',\'username\':\'' + escapeJs(account.username) + '\',\'email\':\'' + escapeJs(account.email) + '\',\'role\':\'' + escapeJs(ucfirst(account.role)) + '\',\'position\':\'' + escapeJs(position) + '\',\'status\':\'' + escapeJs(ucfirst(account.status)) + '\',\'created_at\':\'' + escapeJs(createdAt) + '\',\'updated_at\':\'' + escapeJs(updatedAt) + '\'})">👁</button>' +
        editBtn + restrictBtn + unrestrictBtn + archiveBtn + reactivateBtn +
        '</div></td>' +
        '</tr>';
}

function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function escapeJs(text) {
    if (!text) return '';
    return String(text).replace(/\\/g, '\\\\').replace(/'/g, '\\\'').replace(/\n/g, '\\n').replace(/\r/g, '\\r');
}

function ucfirst(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function showTeacherLoading(show) {
    var el = document.getElementById('teacherLoading');
    if (el) el.style.display = show ? 'inline-block' : 'none';
}

function updateTeacherUrl(params) {
    var url = new URL(window.location);
    Object.keys(params).forEach(function (key) {
        if (params[key] === '' || params[key] === null) {
            url.searchParams.delete(key);
        } else {
            url.searchParams.set(key, params[key]);
        }
    });
    url.searchParams.delete('page');
    window.history.pushState({ params: params }, '', url);
}

function fetchTeacherAccounts(page, append) {
    if (append === undefined) append = false;
    var requestId = ++teacherRequestId;
    var form = document.getElementById('teacherFilterForm');
    var search = document.getElementById('teacherSearch').value;
    var role = document.getElementById('teacherRole').value;
    var status = document.getElementById('teacherStatus').value;

    var params = { page: page };
    if (search) params.search = search;
    if (role) params.role = role;
    if (status) params.status = status;

    updateTeacherUrl(params);

    showTeacherLoading(true);

    var fetchUrl = '{{ route('admin.teachers.fetch') }}' + '?' + new URLSearchParams(params).toString();

    fetch(fetchUrl, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(function (response) {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.json();
    })
    .then(function (data) {
        if (requestId !== teacherRequestId) return;

        var tbody = document.getElementById('teacherTableBody');
        var noResults = document.getElementById('teacherNoResults');

        if (noResults) noResults.remove();

        if (!data.accounts || data.accounts.length === 0) {
            tbody.innerHTML = '<tr id="teacherNoResults"><td colspan="7" style="text-align:center;padding:30px;color:var(--text-light);">No matching accounts found.</td></tr>';
        } else {
            tbody.innerHTML = data.accounts.map(function (acct) { return renderAccountRow(acct); }).join('');
        }

        document.getElementById('teacherPageInfo').textContent = 'Showing ' + data.from + ' to ' + data.to + ' of ' + data.total + ' entries';

        var pagination = document.getElementById('teacherPagination');
        var pageBtnsHtml = '';
        for (var i = 1; i <= data.last_page; i++) {
            var active = i === data.current_page ? ' active' : '';
            pageBtnsHtml += '<a href="#" class="page-btn' + active + '" data-page="' + i + '">' + i + '</a>';
        }
        pagination.innerHTML = pageBtnsHtml;

        showTeacherLoading(false);
    })
    .catch(function (error) {
        if (requestId !== teacherRequestId) return;
        showTeacherLoading(false);
        console.error('Failed to fetch accounts:', error);
    });
}

function initTeacherFilters() {
    var searchInput = document.getElementById('teacherSearch');
    var roleSelect = document.getElementById('teacherRole');
    var statusSelect = document.getElementById('teacherStatus');

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(teacherDebounceTimer);
            teacherDebounceTimer = setTimeout(function () {
                fetchTeacherAccounts(1);
            }, 300);
        });
    }

    [roleSelect, statusSelect].forEach(function (el) {
        if (el) {
            el.addEventListener('change', function () {
                fetchTeacherAccounts(1);
            });
        }
    });

    var pagination = document.getElementById('teacherPagination');
    if (pagination) {
        pagination.addEventListener('click', function (e) {
            var btn = e.target.closest('.page-btn');
            if (!btn) return;
            e.preventDefault();
            var page = parseInt(btn.getAttribute('data-page'), 10);
            if (!isNaN(page)) {
                fetchTeacherAccounts(page);
            }
        });
    }

    var pendingArchiveForm = null;

    var tbody = document.getElementById('teacherTableBody');
    if (tbody) {
        tbody.addEventListener('click', function (e) {
            var archiveForm = e.target.closest('form.archive-form');
            if (archiveForm) {
                e.preventDefault();
                var accountId = archiveForm.getAttribute('data-id');
                var accountName = archiveForm.getAttribute('data-name');
                openArchiveConfirmModal(accountId, accountName, archiveForm.action);
                return;
            }

            var restrictBtn = e.target.closest('button.restrict');
            if (restrictBtn) {
                e.preventDefault();
                var accountId = restrictBtn.getAttribute('data-id');
                var accountName = restrictBtn.getAttribute('data-name');
                openRestrictConfirmModal(accountId, accountName);
                return;
            }

            var unrestrictBtn = e.target.closest('button.unrestrict');
            if (unrestrictBtn) {
                e.preventDefault();
                var accountId = unrestrictBtn.getAttribute('data-id');
                var accountName = unrestrictBtn.getAttribute('data-name');
                openUnrestrictConfirmModal(accountId, accountName);
                return;
            }

            var reactivateBtn = e.target.closest('button.reactivate');
            if (reactivateBtn) {
                e.preventDefault();
                var accountId = reactivateBtn.getAttribute('data-id');
                var accountName = reactivateBtn.getAttribute('data-name');
                openReactivateConfirmModal(accountId, accountName);
            }
        });
    }
}

window.addEventListener('popstate', function () {
    var params = new URLSearchParams(window.location.search);
    var page = parseInt(params.get('page')) || 1;
    var search = params.get('search') || '';
    var role = params.get('role') || '';
    var status = params.get('status') || '';

    document.getElementById('teacherSearch').value = search;
    document.getElementById('teacherRole').value = role;
    document.getElementById('teacherStatus').value = status;

    fetchTeacherAccounts(page);
});

document.addEventListener('DOMContentLoaded', function () {
    var loadingSpan = document.createElement('span');
    loadingSpan.id = 'teacherLoading';
    loadingSpan.style = 'display:none;margin-left:8px;font-size:12px;color:var(--text-light);';
    loadingSpan.textContent = 'Loading…';
    var pagination = document.getElementById('teacherPagination');
    if (pagination && pagination.previousElementSibling) {
        pagination.previousElementSibling.appendChild(loadingSpan);
    }

    initTeacherFilters();

    var editForm = document.getElementById('editTeacherForm');
    var editBtns = document.querySelectorAll('[data-modal-target="editTeacherModal"]');
    editBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (editForm) {
                editForm.action = '{{ url('/admin/teachers') }}/' + btn.dataset.id;
            }
            document.getElementById('editTeacherName').value = btn.dataset.name;
            document.getElementById('editTeacherUsername').value = btn.dataset.username;
            document.getElementById('editTeacherEmail').value = btn.dataset.email;
            var roleTextEl = document.getElementById('editTeacherRoleText');
            if (roleTextEl) {
                roleTextEl.value = btn.dataset.role;
            } else {
                document.getElementById('editTeacherRole').value = btn.dataset.role;
            }
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

    document.getElementById('reactivateConfirmModal')?.addEventListener('click', function (event) {
        if (event.target === this) {
            closeReactivateConfirmModal();
        }
    });

    document.getElementById('archiveConfirmModal')?.addEventListener('click', function (event) {
        if (event.target === this) {
            closeArchiveConfirmModal();
        }
    });

    document.getElementById('restrictConfirmModal')?.addEventListener('click', function (event) {
        if (event.target === this) {
            closeRestrictConfirmModal();
        }
    });

    document.getElementById('unrestrictConfirmModal')?.addEventListener('click', function (event) {
        if (event.target === this) {
            closeUnrestrictConfirmModal();
        }
    });

    document.getElementById('archivedAccountsModal')?.addEventListener('click', function (event) {
        if (event.target === this) {
            closeArchivedAccountsModal();
        }
    });

    document.getElementById('unarchiveConfirmModal')?.addEventListener('click', function (event) {
        if (event.target === this) {
            closeUnarchiveConfirmModal();
        }
    });

    var archivedSearchInput = document.getElementById('archivedSearch');
    if (archivedSearchInput) {
        archivedSearchInput.addEventListener('input', function () {
            clearTimeout(archivedDebounceTimer);
            archivedDebounceTimer = setTimeout(function () {
                fetchArchivedAccounts(1);
            }, 300);
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            var archiveModal = document.getElementById('archiveConfirmModal');
            var reactivateModal = document.getElementById('reactivateConfirmModal');
            var restrictModal = document.getElementById('restrictConfirmModal');
            var unrestrictModal = document.getElementById('unrestrictConfirmModal');
            var archivedModal = document.getElementById('archivedAccountsModal');
            var unarchiveModal = document.getElementById('unarchiveConfirmModal');
            if (archiveModal && archiveModal.classList.contains('show')) {
                closeArchiveConfirmModal();
            }
            if (reactivateModal && reactivateModal.classList.contains('show')) {
                closeReactivateConfirmModal();
            }
            if (restrictModal && restrictModal.classList.contains('show')) {
                closeRestrictConfirmModal();
            }
            if (unrestrictModal && unrestrictModal.classList.contains('show')) {
                closeUnrestrictConfirmModal();
            }
            if (archivedModal && archivedModal.classList.contains('show')) {
                closeArchivedAccountsModal();
            }
            if (unarchiveModal && unarchiveModal.classList.contains('show')) {
                closeUnarchiveConfirmModal();
            }
        }
    });

    var archivedTbody = document.getElementById('archivedAccountsTableBody');
    if (archivedTbody) {
        archivedTbody.addEventListener('click', function (e) {
            var unarchiveBtn = e.target.closest('button.unarchive');
            if (unarchiveBtn) {
                e.preventDefault();
                var accountId = unarchiveBtn.getAttribute('data-id');
                var accountName = unarchiveBtn.getAttribute('data-name');
                openUnarchiveConfirmModal(accountId, accountName);
            }
        });
    }
});
</script>
@endpush
