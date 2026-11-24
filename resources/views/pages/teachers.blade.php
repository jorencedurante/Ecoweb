@extends('layouts.admin')

@section('page-title', 'Admin & Teacher Management')

@section('content')
    <div class="filter-card teachers-filter-card">
        <button type="button" class="filter-header" onclick="this.classList.toggle('collapsed');this.nextElementSibling.classList.toggle('collapsed')" aria-expanded="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" style="vertical-align:-2px"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg> Filters
        </button>
        <div class="filter-body">
            <form method="GET" action="{{ route(portal_prefix() . '.teachers') }}" class="filter-form" id="teacherFilterForm">
                <div class="filter-search">
                    <label>Search</label>
                    <input type="text" name="search" id="teacherSearch" aria-label="Search by name, username, email, role or position" placeholder="Search name, username, email, role..." value="{{ request('search') }}" autocomplete="off">
                </div>
                <div class="filter-item">
                    <label>Role</label>
                    <select name="role" id="teacherRole" aria-label="Filter by role" data-filter="role">
                        <option value="">All Roles</option>
                        <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="teacher" {{ request('role') === 'teacher' ? 'selected' : '' }}>Teacher</option>
                        <option value="sub_teacher" {{ request('role') === 'sub_teacher' ? 'selected' : '' }}>Sub Teacher</option>
                        <option value="super_admin" {{ request('role') === 'super_admin' ? 'selected' : '' }}>Principal</option>
                    </select>
                </div>
                <div class="filter-item">
                    <label>Status</label>
                    <select name="status" id="teacherStatus" aria-label="Filter by status" data-filter="status">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        <option value="restricted" {{ request('status') === 'restricted' ? 'selected' : '' }}>Restricted</option>
                        <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>Archived</option>
                    </select>
                </div>
                <div class="filter-helper-note">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                    <span>Filter accounts by role or status to quickly find specific admin and teacher records.</span>
                </div>
            </form>
        </div>
    </div>
    <div class="table-card teachers-table-card">
        <div class="table-header">
            <h2 class="accounts-section-title"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg> Admin &amp; Teacher Accounts</h2>
            <div class="table-header-actions">
                @if(auth()->user()->isAdminLevel())
                <button type="button" class="btn btn-success create-teacher-btn" id="createTeacherAccountBtn" aria-label="Create account">+ Create Account</button>
                @endif
                @if(auth()->user()->isSuperAdmin())
                <button type="button" class="btn btn-primary archived-accounts-btn" onclick="openArchivedAccountsModal()" aria-label="View archived accounts">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="2" y="3" width="20" height="5" rx="1"></rect><path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8"></path><path d="M10 12h4"></path></svg> Archived Accounts
                </button>
                @endif
            </div>
        </div>
        <div class="table-responsive">
        <table id="teacherAccountsTable">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">NAME</th>
                    <th scope="col">EMAIL</th>
                    <th scope="col">ROLE</th>
                    <th scope="col">POSITION</th>
                    <th scope="col">STATUS</th>
                    <th scope="col" class="teachers-col-actions account-actions-column">ACTIONS</th>
                </tr>
            </thead>
            <tbody id="teacherTableBody">
                @forelse($accounts as $account)
                <tr>
                    <td>{{ 'ADM' . str_pad($account->id, 3, '0', STR_PAD_LEFT) }}</td>
                    <td><strong>{{ $account->name }}</strong></td>
                    <td>{{ $account->email }}</td>
                    <td>
                        @if(auth()->user()->isSuperAdmin())
                            <span style="display:inline-block;padding:3px 10px;border-radius:12px;font-size:12px;font-weight:600;background:var(--accent-light,#e8f1ff);color:var(--accent,#1a73e8);">{{ \App\Models\User::roleLabel($account->role) }}</span>
                        @else
                            <span class="role-badge" style="display:inline-block;padding:3px 10px;border-radius:12px;font-size:12px;font-weight:600;background:#eef2f7;color:#475569;">{{ \App\Models\User::roleLabel($account->role) }}</span>
                        @endif
                    </td>
                    <td>{{ $account->position ?? 'N/A' }}</td>
                    <td><span style="color:{{ $account->status === 'active' ? 'var(--green)' : 'var(--gray)' }};font-weight:600;">{{ ucfirst($account->status) }}</span></td>
                    <td class="teachers-actions-cell account-actions-column">
                        <div class="account-actions-buttons">
                            <button type="button" class="action-btn action-btn-view" title="View Account Details" aria-label="View account details"
                                onclick="openViewAccountModal({
                                    id: '{{ 'ADM' . str_pad($account->id, 3, '0', STR_PAD_LEFT) }}',
                                    name: '{{ $account->name }}',
                                    username: '{{ $account->username }}',
                                    email: '{{ $account->email }}',
                                    role: '{{ \App\Models\User::roleLabel($account->role) }}',
                                    position: '{{ $account->position ?? 'N/A' }}',
                                    status: '{{ ucfirst($account->status) }}',
                                    created_at: '{{ $account->created_at ? $account->created_at->format('M d, Y') : '—' }}',
                                    updated_at: '{{ $account->updated_at ? $account->updated_at->format('M d, Y') : '—' }}',
                                    can_print_certificates: '{{ $account->can_print_certificates ? '1' : '0' }}'
                                })"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg></button>
                            @if($account->status === 'pending_setup')
                            <button type="button" class="action-btn action-btn-resend" data-id="{{ $account->id }}" title="Resend Invitation" aria-label="Resend invitation">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="m22 7-10 6L2 7"></path></svg>
                            </button>
                            @endif
                            @if(auth()->user()->isSuperAdmin())
                            <button type="button" class="action-btn action-btn-edit" title="Edit Account" aria-label="Edit account"
                                data-modal-target="editTeacherModal"
                                data-id="{{ $account->id }}"
                                data-name="{{ $account->name }}"
                                data-username="{{ $account->username }}"
                                data-email="{{ $account->email }}"
                                data-role="{{ $account->role }}"
                                data-position="{{ $account->position ?? '' }}"
                                data-status="{{ $account->status ?? 'active' }}"
                                data-can-print-certificates="{{ $account->can_print_certificates ? '1' : '0' }}"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 20h9"></path><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path></svg></button>

                            @if($account->status === 'active')
                            <button type="button" class="action-btn action-btn-restrict restrict" title="Restrict Account" aria-label="Restrict account"
                                data-id="{{ $account->id }}"
                                data-name="{{ $account->name }}"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg></button>
                            <form method="POST" action="{{ route(portal_prefix() . '.teachers.archive', $account->id) }}" class="inline-action-form archive-form" data-id="{{ $account->id }}" data-name="{{ $account->name }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="action-btn action-btn-archive" title="Archive Account" aria-label="Archive account"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="2" y="3" width="20" height="5" rx="1"></rect><path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8"></path><path d="M10 12h4"></path></svg></button>
                            </form>
                            @elseif($account->status === 'restricted')
                            <button type="button" class="action-btn action-btn-unrestrict unrestrict" title="Unrestrict Account" aria-label="Unrestrict account"
                                data-id="{{ $account->id }}"
                                data-name="{{ $account->name }}"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 9.9-1"></path></svg></button>
                            <form method="POST" action="{{ route(portal_prefix() . '.teachers.archive', $account->id) }}" class="inline-action-form archive-form" data-id="{{ $account->id }}" data-name="{{ $account->name }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="action-btn action-btn-archive" title="Archive Account" aria-label="Archive account"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="2" y="3" width="20" height="5" rx="1"></rect><path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8"></path><path d="M10 12h4"></path></svg></button>
                            </form>
                            @elseif($account->status === 'archived')
                            <button type="button" class="action-btn action-btn-reactivate reactivate" title="Reactivate Account" aria-label="Reactivate account"
                                data-id="{{ $account->id }}"
                                data-name="{{ $account->name }}"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M3 12a9 9 0 1 0 3-6.7"></path><polyline points="3 3 3 9 9 9"></polyline></svg></button>
                            @endif
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr id="teacherNoResults">
                    <td colspan="7" style="text-align:center;padding:30px;color:var(--text-light);">No accounts found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
        <div class="pagination-bar">
            <span id="teacherPageInfo">Showing {{ $accounts->firstItem() ?? 0 }} to {{ $accounts->lastItem() ?? 0 }} of {{ $accounts->total() }} entries</span>
            <span id="teacherLoading" style="display:none;margin-left:8px;font-size:12px;color:var(--text-light);">Loading…</span>
            <div class="pagination" id="teacherPagination">
                @for($i = 1; $i <= $accounts->lastPage(); $i++)
                    <a href="{{ $accounts->url($i) }}" class="page-btn {{ $accounts->currentPage() == $i ? 'active' : '' }}" data-page="{{ $i }}">{{ $i }}</a>
                @endfor
            </div>
        </div>
    </div>
    <!-- Edit Account Modal -->
    <div class="modal-overlay" id="editTeacherModal">
        <div class="modal-content edit-account-modal-content">
            <div class="modal-header edit-account-modal-header">
                <div>
                    <h2>Edit Account</h2>
                    <p>Update account information</p>
                </div>
                <button type="button" class="modal-close" onclick="closeEditTeacherModal()" aria-label="Close">&times;</button>
            </div>
            <form method="POST" action="" id="editTeacherForm" class="modal-form edit-account-modal-form">
                @csrf @method('PUT')
                <input type="hidden" id="editTeacherId" value="">
                <div class="modal-body edit-account-modal-body">
                    <section class="edit-account-section">
                        <h3 class="edit-account-section-title">Personal Information</h3>
                        <div class="edit-account-grid">
                            <div class="form-group">
                                <label for="editTeacherName">Full Name</label>
                                <input type="text" name="name" id="editTeacherName" required aria-label="Full name">
                            </div>
                            <div class="form-group">
                                <label for="editTeacherUsername">Username</label>
                                <input type="text" name="username" id="editTeacherUsername" required aria-label="Username">
                            </div>
                            <div class="form-group edit-account-grid-full">
                                <label for="editTeacherEmailDisplay">Email Address</label>
                                <div class="edit-account-inline-row">
                                    <input type="email" id="editTeacherEmailDisplay" readonly aria-label="Current verified email">
                                    <button type="button" class="btn btn-secondary edit-account-inline-btn" id="openChangeEmailDialogBtn">Change Email</button>
                                </div>
                                <input type="hidden" name="email" id="editTeacherEmail">
                                <p class="edit-account-help">Email is read-only here. Use Change Email to verify and update it.</p>
                            </div>
                        </div>
                    </section>

                    <section class="edit-account-section">
                        <h3 class="edit-account-section-title">Account Security</h3>
                        <div class="edit-account-grid">
                            <div class="form-group edit-account-grid-full">
                                <label for="editTeacherPasswordPlaceholder">Password</label>
                                <div class="edit-account-inline-row">
                                    <input type="password" id="editTeacherPasswordPlaceholder" value="••••••••••••" readonly aria-label="Password placeholder" autocomplete="off">
                                    <button type="button" class="btn btn-secondary edit-account-inline-btn" id="openChangePasswordDialogBtn">Change Password</button>
                                </div>
                                <p class="edit-account-help">Masked placeholder only. Passwords are never displayed or exposed.</p>
                            </div>
                        </div>
                    </section>

                    <section class="edit-account-section">
                        <h3 class="edit-account-section-title">Account Details</h3>
                        <div class="edit-account-grid">
                            <div class="form-group">
                                <label for="editTeacherRole">Role</label>
                                @if(auth()->user()->isSuperAdmin())
                                <select name="role" id="editTeacherRole" required aria-label="Role">
                                    <option value="admin">Admin</option>
                                    <option value="teacher">Teacher</option>
                                    <option value="sub_teacher">Sub Teacher</option>
                                    <option value="super_admin">Principal</option>
                                </select>
                                @else
                                <input type="text" id="editTeacherRoleText" disabled style="background:#f1f5f9;cursor:not-allowed;" aria-label="Role">
                                <input type="hidden" name="role" id="editTeacherRoleHidden" value="" aria-label="Role">
                                @endif
                            </div>
                            <div class="form-group">
                                <label for="editTeacherPosition">Position</label>
                                <input type="text" name="position" id="editTeacherPosition" placeholder="e.g. Teacher 1" aria-label="Position">
                            </div>
                            <div class="form-group edit-account-grid-full">
                                <label for="editTeacherStatus">Status</label>
                                <select name="status" id="editTeacherStatus" required aria-label="Status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="archived">Archived</option>
                                </select>
                            </div>
                        </div>
                    </section>

                    <section class="edit-account-section" id="editCertificatePermissionsSection" hidden>
                        <h3 class="edit-account-section-title">Certificate Permissions</h3>
                        <div class="edit-account-grid">
                            <div class="form-group edit-account-grid-full">
                                <label for="editCertificatePrinting">Certificate Printing Access</label>
                                <select id="editCertificatePrinting" aria-label="Certificate Printing Access">
                                    <option value="0">Not Granted</option>
                                    <option value="1">Granted</option>
                                </select>
                                <p class="edit-account-help">Teachers and Sub Teachers need explicit permission before they can print certificates. Changes are saved after confirmation.</p>
                            </div>
                        </div>
                    </section>
                </div>
                <div class="modal-footer edit-account-modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEditTeacherModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Account</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Change Email Dialog -->
    <div class="modal-overlay" id="teacherChangeEmailModal" role="dialog" aria-modal="true" aria-labelledby="teacherChangeEmailTitle" aria-hidden="true">
        <div class="modal-content edit-account-submodal-content">
            <div class="modal-header edit-account-modal-header">
                <div>
                    <h2 id="teacherChangeEmailTitle">Change Email</h2>
                    <p>Verify the current registered email to update this account email.</p>
                </div>
                <button type="button" class="modal-close" onclick="closeTeacherChangeEmailModal()" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body edit-account-modal-body">
                <form id="teacherChangeEmailForm" onsubmit="return false;">
                    <div id="teacherEmailNotification" class="modal-notice" role="status" aria-live="polite" aria-atomic="true" style="display:none;"></div>
                    <div class="form-group">
                        <label for="teacherCurrentEmailField">Current Verified Email</label>
                        <input type="email" id="teacherCurrentEmailField" readonly aria-label="Current verified email address">
                    </div>
                    <div class="form-group">
                        <label for="teacherNewEmailInput">New Email Address</label>
                        <div class="edit-account-inline-row">
                            <input type="email" id="teacherNewEmailInput" name="new_email" required placeholder="Enter new email address" aria-label="New email address">
                            <button type="button" class="btn btn-outline edit-account-inline-btn" id="teacherSendEmailOtpBtn">Send</button>
                        </div>
                        <p class="edit-account-help" id="teacherEmailFlowHelp">Click Send to request a verification code for the current registered email address.</p>
                    </div>
                    <div class="form-group" id="teacherEmailOtpGroup">
                        <label for="teacherEmailOtpInput">OTP Verification Code</label>
                        <input type="text" id="teacherEmailOtpInput" name="otp" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" placeholder="Enter 6-digit code" aria-label="OTP verification code">
                        <p class="edit-account-help">Verification code sent to the current registered email address.</p>
                    </div>
                    <div class="edit-account-info-box" id="teacherEmailStageInfo">Send a verification code to the current registered email, then enter it to save the new email address immediately.</div>
                </form>
            </div>
            <div class="modal-footer edit-account-modal-footer edit-account-modal-footer-wrap">
                <button type="button" class="btn btn-secondary" onclick="closeTeacherChangeEmailModal()">Cancel</button>
                <button type="button" class="btn btn-primary" id="teacherVerifyEmailBtn">Verify &amp; Update Email</button>
            </div>
        </div>
    </div>

    <!-- Change Password Dialog -->
    <div class="modal-overlay" id="teacherChangePasswordModal" role="dialog" aria-modal="true" aria-labelledby="teacherChangePasswordTitle" aria-hidden="true">
        <div class="modal-content edit-account-submodal-content">
            <div class="modal-header edit-account-modal-header">
                <div>
                    <h2 id="teacherChangePasswordTitle">Change Password</h2>
                    <p>Update this account password securely.</p>
                </div>
                <button type="button" class="modal-close" onclick="closeTeacherChangePasswordModal()" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body edit-account-modal-body">
                <form id="teacherChangePasswordForm" onsubmit="return false;">
                    <div id="teacherPasswordNotification" class="modal-notice" role="status" aria-live="polite" aria-atomic="true" style="display:none;"></div>
                    <div class="form-group" id="teacherCurrentPasswordGroup">
                        <label for="teacherCurrentPasswordInput">Current Password</label>
                        <div class="password-field-wrapper">
                            <input type="password" id="teacherCurrentPasswordInput" name="current_password" required placeholder="Enter current password" aria-label="Current password" autocomplete="current-password">
                            <button type="button" class="password-toggle" data-target="teacherCurrentPasswordInput" aria-label="Show current password"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg></button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="teacherNewPasswordInput">New Password</label>
                        <div class="password-field-wrapper">
                            <input type="password" id="teacherNewPasswordInput" name="password" placeholder="Enter new password" aria-label="New password" autocomplete="new-password">
                            <button type="button" class="password-toggle" data-target="teacherNewPasswordInput" aria-label="Show new password"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg></button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="teacherConfirmPasswordInput">Confirm New Password</label>
                        <div class="password-field-wrapper">
                            <input type="password" id="teacherConfirmPasswordInput" name="password_confirmation" placeholder="Confirm new password" aria-label="Confirm new password" autocomplete="new-password">
                            <button type="button" class="password-toggle" data-target="teacherConfirmPasswordInput" aria-label="Show confirm password"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg></button>
                        </div>
                        <div id="teacherPasswordMatchFeedback" class="edit-account-help" style="margin-top:6px;"></div>
                    </div>
                    <div id="teacherPasswordRequirementsBox" class="edit-account-info-box" style="display:none;">
                        <div style="font-size:12px;font-weight:600;color:#0369a1;margin-bottom:4px;">Password Requirements</div>
                        <ul style="font-size:12px;color:#0369a1;margin:0;padding-left:16px;line-height:1.6;">
                            <li id="teacherReqLength">At least 8 characters</li>
                            <li id="teacherReqCase">Uppercase and lowercase letters</li>
                            <li id="teacherReqNumber">At least one number</li>
                            <li id="teacherReqMatch">Confirmation must match</li>
                        </ul>
                        <p style="font-size:11px;color:#0369a1;margin:6px 0 0;">Checklist reflects the current UI policy (min 8, mixed case, number, match). Final validation follows the existing backend password policy.</p>
                    </div>
                </form>
            </div>
            <div class="modal-footer edit-account-modal-footer edit-account-modal-footer-wrap">
                <button type="button" class="btn btn-secondary" onclick="closeTeacherChangePasswordModal()">Cancel</button>
                <button type="button" class="btn btn-primary" id="teacherSavePasswordBtn">Save Password</button>
            </div>
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
                    <div class="detail-item"><span>Account ID</span><strong id="viewAccountId">—</strong></div>
                    <div class="detail-item"><span>Username</span><strong id="viewAccountUsername">—</strong></div>
                    <div class="detail-item"><span>Email</span><strong id="viewAccountEmail">—</strong></div>
                    <div class="detail-item"><span>Role</span><strong id="viewAccountRoleText">—</strong></div>
                    <div class="detail-item"><span>Position</span><strong id="viewAccountPosition">—</strong></div>
                    <div class="detail-item"><span>Status</span><strong id="viewAccountStatus">—</strong></div>
                    <div class="detail-item"><span>Created At</span><strong id="viewAccountCreatedAt">—</strong></div>
                    <div class="detail-item"><span>Updated At</span><strong id="viewAccountUpdatedAt">—</strong></div>
                    <div class="detail-item"><span>Certificate Printing Access</span><strong id="viewAccountCertificatePrinting">—</strong></div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeViewAccountModal()">Close</button></div>
        </div>
    </div>

    <!-- Reactivate/Archive/Restrict/Unrestrict/Archived/Unarchive modals preserved -->
    <div id="reactivateConfirmModal" class="modal-overlay"><div class="modal-content"><div class="modal-header"><div class="modal-icon warn"><span class="icon">⚠</span></div><div><h2>Reactivate Account?</h2><p id="reactivateConfirmMessage">Are you sure you want to reactivate "<span id="reactivateAccountName"></span>"?</p></div><button type="button" class="modal-close" onclick="closeReactivateConfirmModal()" aria-label="Close">×</button></div><div class="modal-body"><p>This account will regain access to EcoCollect.</p><input type="hidden" id="reactivateAccountId"></div><div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeReactivateConfirmModal()">Cancel</button><button type="button" class="btn btn-success" onclick="confirmReactivateAccount()" id="btnConfirmReactivate">Reactivate Account</button></div></div></div>
    <div id="archiveConfirmModal" class="modal-overlay"><div class="modal-content"><div class="modal-header"><div class="modal-icon danger"><span class="icon">🗑</span></div><div><h2>Archive Account?</h2><p id="archiveConfirmMessage">Are you sure you want to archive "<span id="archiveAccountName"></span>"?</p></div><button type="button" class="modal-close" onclick="closeArchiveConfirmModal()" aria-label="Close">×</button></div><div class="modal-body"><p>This account will lose access to EcoCollect. Existing student records and activity history will remain unchanged.</p><input type="hidden" id="archiveAccountId"><input type="hidden" id="archiveAccountUrl"></div><div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeArchiveConfirmModal()">Cancel</button><button type="button" class="btn btn-danger" onclick="confirmArchiveAccount()" id="btnConfirmArchive">Archive Account</button></div></div></div>
    <div id="restrictConfirmModal" class="modal-overlay"><div class="modal-content"><div class="modal-header"><div class="modal-icon warn"><span class="icon">🔒</span></div><div><h2>Restrict Account?</h2><p id="restrictConfirmMessage">Are you sure you want to restrict "<span id="restrictAccountName"></span>"?</p></div><button type="button" class="modal-close" onclick="closeRestrictConfirmModal()" aria-label="Close">×</button></div><div class="modal-body"><p>This account will be temporarily suspended and will lose access to EcoCollect. Existing student records and activity history will remain unchanged.</p><input type="hidden" id="restrictAccountId"></div><div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeRestrictConfirmModal()">Cancel</button><button type="button" class="btn btn-warning" onclick="confirmRestrictAccount()" id="btnConfirmRestrict">Restrict Account</button></div></div></div>
    <div id="unrestrictConfirmModal" class="modal-overlay"><div class="modal-content"><div class="modal-header"><div class="modal-icon info"><span class="icon">🔓</span></div><div><h2>Unrestrict Account?</h2><p id="unrestrictConfirmMessage">Are you sure you want to unrestrict "<span id="unrestrictAccountName"></span>"?</p></div><button type="button" class="modal-close" onclick="closeUnrestrictConfirmModal()" aria-label="Close">×</button></div><div class="modal-body"><p>This account will be restored to Active status and will regain access to EcoCollect.</p><input type="hidden" id="unrestrictAccountId"></div><div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeUnrestrictConfirmModal()">Cancel</button><button type="button" class="btn btn-success" onclick="confirmUnrestrictAccount()" id="btnConfirmUnrestrict">Unrestrict Account</button></div></div></div>
    <div id="certificatePrintConfirmModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="certificatePrintConfirmTitle">
        <div class="modal-content" style="max-width:460px;">
            <div class="modal-header">
                <div class="modal-icon warn"><span class="icon">🖨️</span></div>
                <div>
                    <h2 id="certificatePrintConfirmTitle">Grant Certificate Printing Access?</h2>
                    <p id="certificatePrintConfirmMessage">Allow this teacher to print certificates in EcoCollect?</p>
                </div>
                <button type="button" class="modal-close" onclick="closeCertificatePrintConfirmModal()" aria-label="Close">×</button>
            </div>
            <div class="modal-body">
                <p id="certificatePrintConfirmAccount" style="font-size:13px;color:var(--text-medium);"></p>
                <input type="hidden" id="certificatePrintConfirmId">
                <input type="hidden" id="certificatePrintConfirmValue">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeCertificatePrintConfirmModal()">Cancel</button>
                <button type="button" class="btn btn-success" id="btnConfirmCertificatePrint" onclick="confirmCertificatePrintPermission()">Grant Access</button>
            </div>
        </div>
    </div>
    <div id="createTeacherModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="createTeacherModalTitle" style="display:none;">
        <div class="modal-content create-teacher-modal">
            <div class="modal-header">
                <div>
                    <h2 id="createTeacherModalTitle">Create Account</h2>
                    <p class="subtitle" style="margin-top:4px;">Add a new staff account and send an invitation to their email.</p>
                </div>
                <button type="button" class="modal-close" id="createTeacherModalClose" aria-label="Close">&times;</button>
            </div>
            <form id="createTeacherForm" novalidate>
                <div class="modal-body">
                    <div class="create-teacher-grid">
                        <div class="create-teacher-field">
                            <label for="ctName">Full Name <span aria-hidden="true">*</span></label>
                            <input type="text" id="ctName" name="name" required autocomplete="name" placeholder="e.g. Juan Dela Cruz" aria-describedby="ctNameError">
                            <small class="ct-field-error" id="ctNameError" hidden></small>
                        </div>
                        <div class="create-teacher-field">
                            <label for="ctUsername">Username <span aria-hidden="true">*</span></label>
                            <input type="text" id="ctUsername" name="username" required autocomplete="username" placeholder="e.g. juan.delacruz" aria-describedby="ctUsernameError">
                            <small class="ct-field-error" id="ctUsernameError" hidden></small>
                        </div>
                        <div class="create-teacher-field">
                            <label for="ctEmail">Email Address <span aria-hidden="true">*</span></label>
                            <input type="email" id="ctEmail" name="email" required autocomplete="email" placeholder="teacher@example.com" aria-describedby="ctEmailError">
                            <small class="ct-field-error" id="ctEmailError" hidden></small>
                        </div>
                        <div class="create-teacher-field">
                            <label for="ctPosition">Position <span aria-hidden="true">*</span></label>
                            <input type="text" id="ctPosition" name="position" required placeholder="e.g. Teacher" aria-describedby="ctPositionError">
                            <small class="ct-field-error" id="ctPositionError" hidden></small>
                        </div>
                        <div class="create-teacher-field">
                            <label for="ctRole">Role <span aria-hidden="true">*</span></label>
                            <select id="ctRole" name="role" required aria-label="Role" aria-describedby="ctRoleError">
                                <option value="teacher" selected>Teacher</option>
                                <option value="sub_teacher">Sub Teacher</option>
                                <option value="admin">Admin</option>
                                <option value="super_admin">Principal</option>
                            </select>
                            <small class="ct-field-error" id="ctRoleError" hidden></small>
                        </div>
                    </div>
                    <div id="ctSubTeacherFields" class="ct-sub-fields" hidden>
                        <div class="ct-sub-title">Sub Teacher Assignment</div>
                        <div class="create-teacher-grid">
                            <div class="create-teacher-field">
                                <label for="ctTeacherId">Assign to Teacher</label>
                                <select id="ctTeacherId" name="teacher_id" aria-label="Assign to Teacher" aria-describedby="ctTeacherIdError">
                                    <option value="">Loading teachers…</option>
                                </select>
                                <small class="ct-field-error" id="ctTeacherIdError" hidden></small>
                            </div>
                            <div class="create-teacher-field">
                                <label for="ctAccessLevel">Access Level</label>
                                <select id="ctAccessLevel" name="access_level" aria-label="Access Level" aria-describedby="ctAccessLevelError">
                                    <option value="collaborator" selected>Collaborator</option>
                                    <option value="view_only">View Only</option>
                                    <option value="custom">Custom</option>
                                </select>
                                <small class="ct-field-error" id="ctAccessLevelError" hidden></small>
                            </div>
                        </div>
                        <div id="ctCustomPermissions" class="ct-custom-permissions" hidden>
                            <div class="ct-sub-title">Custom Permissions</div>
                            <div class="ct-permission-grid">
                                @foreach(\App\Services\CollaborationService::configurablePermissions() as $permissionKey => $permissionLabel)
                                    <label class="ct-permission-item">
                                        <input type="checkbox" name="custom_permissions[]" value="{{ $permissionKey }}"> {{ $permissionLabel }}
                                    </label>
                                @endforeach
                            </div>
                            <small class="ct-field-error" id="ctCustomPermissionsError" hidden></small>
                        </div>
                    </div>
                    <div class="create-teacher-info">
                        <strong>What happens next?</strong>
                        <p>An invitation link will be sent to the new account's email. The invited user will use the link to verify their email and create their own password to activate the account.</p>
                    </div>
                    <p id="createTeacherError" class="create-teacher-error" role="alert" hidden></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="createTeacherCancel">Cancel</button>
                    <button type="submit" class="btn btn-success" id="createTeacherSubmit">Create Account</button>
                </div>
            </form>
        </div>
    </div>

    <div id="archivedAccountsModal" class="modal-overlay"><div class="modal-content archived-accounts-content"><div class="modal-header"><div class="modal-icon archived"><span class="icon">📦</span></div><div><h2>Archived Accounts</h2><p>View and manage archived teacher and admin accounts.</p></div><button type="button" class="modal-close" onclick="closeArchivedAccountsModal()" aria-label="Close">×</button></div><div class="modal-body"><div class="search-box"><input type="text" id="archivedSearch" placeholder="Search archived accounts..." aria-label="Search archived accounts"></div><div class="archived-table-container"><table class="archived-table" width="100%"><thead><tr><th>Account ID</th><th>Name</th><th>Email</th><th>Role</th><th>Position</th><th>Date Archived</th><th class="archived-actions-col">Actions</th></tr></thead><tbody id="archivedAccountsTableBody"></tbody></table><div id="archivedNoResults" style="display:none;padding:30px;text-align:center;color:var(--text-light);">No archived accounts found.</div></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeArchivedAccountsModal()">Close</button></div></div></div>
    <div id="unarchiveConfirmModal" class="modal-overlay"><div class="modal-content"><div class="modal-header"><div class="modal-icon info"><span class="icon">🔄</span></div><div><h2>Unarchive Account?</h2><p id="unarchiveConfirmMessage">Restore access for "<span id="unarchiveAccountName"></span>"?</p></div><button type="button" class="modal-close" onclick="closeUnarchiveConfirmModal()" aria-label="Close">×</button></div><div class="modal-body"><p>The account will be restored to Active status and will regain access to EcoCollect. Previous student records and activity history will remain unchanged.</p><input type="hidden" id="unarchiveAccountId"></div><div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeUnarchiveConfirmModal()">Cancel</button><button type="button" class="btn btn-success" onclick="confirmUnarchiveAccount()" id="btnConfirmUnarchive">Unarchive Account</button></div></div></div>
    <div id="deleteArchivedConfirmModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="deleteArchivedConfirmTitle"><div class="modal-content"><div class="modal-header"><div class="modal-icon danger"><span class="icon">🗑</span></div><div><h2 id="deleteArchivedConfirmTitle">Permanently Delete Account?</h2><p id="deleteArchivedConfirmMessage">Are you sure you want to permanently delete this archived account? This action cannot be undone.</p></div><button type="button" class="modal-close" onclick="closeDeleteArchivedConfirmModal()" aria-label="Close">×</button></div><div class="modal-body"><p id="deleteArchivedAccountLabel"></p><p>Student records, bottle collections, and historical activity will not be deleted. Deletion is blocked if related records still reference this account.</p><input type="hidden" id="deleteArchivedAccountId"></div><div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeDeleteArchivedConfirmModal()">Cancel</button><button type="button" class="btn btn-danger" onclick="confirmDeleteArchivedAccount()" id="btnConfirmDeleteArchived">Delete Account</button></div></div></div>
    <style>
    #certificatePrintConfirmModal{z-index:10000;}
    .edit-account-section[hidden]{display:none!important;}
    .edit-account-modal-content{max-width:760px;width:min(760px,100%);max-height:90vh;display:flex;flex-direction:column;overflow:hidden;}
    .edit-account-submodal-content{max-width:560px;width:min(560px,100%);max-height:90vh;display:flex;flex-direction:column;overflow:hidden;}
    .edit-account-modal-header{flex:0 0 auto;}
    .edit-account-modal-form{display:flex;flex-direction:column;flex:1 1 auto;min-height:0;}
    .edit-account-modal-body{flex:1 1 auto;min-height:0;overflow-y:auto;padding:20px 24px;display:flex;flex-direction:column;gap:18px;}
    .edit-account-modal-footer{flex:0 0 auto;position:sticky;bottom:0;background:#fff;border-top:1px solid rgba(0,0,0,.08);padding:16px 24px;display:flex;gap:10px;justify-content:flex-end;align-items:center;}
    .edit-account-modal-footer-wrap{flex-wrap:wrap;}
    .edit-account-section{border:1px solid rgba(0,0,0,.08);border-radius:12px;padding:16px;background:#fff;}
    .edit-account-section-title{font-size:14px;font-weight:700;color:#111827;margin:0 0 12px;letter-spacing:-.01em;}
    .edit-account-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px 16px;}
    .edit-account-grid-full{grid-column:1 / -1;}
    .edit-account-inline-row{display:flex;gap:10px;align-items:stretch;}
    .edit-account-inline-row input{flex:1 1 auto;min-width:0;}
    .edit-account-inline-btn{white-space:nowrap;height:44px;padding:0 16px;}
    .edit-account-help{margin:6px 0 0;font-size:12px;color:#6b7280;line-height:1.45;}
    .edit-account-info-box{background:#e8f4fd;border:1px solid #bae6fd;border-radius:8px;padding:12px 14px;font-size:13px;color:#0369a1;line-height:1.5;}
    .edit-account-error-box{background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:10px 14px;color:#991b1b;font-size:13px;}
    .edit-account-success-box{background:rgba(0,200,83,.08);border:1px solid #22c55e;border-radius:8px;padding:10px 14px;color:#166534;font-size:13px;}
    .modal-notice{display:none;align-items:flex-start;gap:8px;border-radius:8px;padding:10px 14px;font-size:13px;line-height:1.45;border:1px solid transparent;opacity:0;transition:opacity .25s ease;overflow:hidden;}
    .modal-notice.is-visible{display:flex;opacity:1;}
    .modal-notice.is-hiding{opacity:0;}
    .modal-notice-success{background:rgba(0,200,83,.08);border-color:#22c55e;color:#166534;}
    .modal-notice-error{background:#fef2f2;border-color:#fecaca;color:#991b1b;}
    .modal-notice-icon{font-size:14px;line-height:1.2;flex:0 0 auto;margin-top:1px;}
    .modal-notice-text{flex:1 1 auto;min-width:0;}
    #editTeacherModal .form-group label,#teacherChangeEmailModal .form-group label,#teacherChangePasswordModal .form-group label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;}
    #editTeacherModal .form-group input,#editTeacherModal .form-group select,#teacherChangeEmailModal .form-group input,#teacherChangePasswordModal .form-group input{width:100%;height:44px;padding:0 14px;border:1px solid var(--border,#d1d5db);border-radius:8px;font-size:14px;outline:none;background:#fff;color:#111827;}
    #editTeacherModal .form-group input:focus,#editTeacherModal .form-group select:focus,#teacherChangeEmailModal .form-group input:focus,#teacherChangePasswordModal .form-group input:focus{border-color:#00C853;box-shadow:0 0 0 3px rgba(0,200,83,.12);}
    #editTeacherModal .form-group input[readonly],#teacherChangeEmailModal .form-group input[readonly]{background:#f8fafc;color:#475569;}
    #teacherChangePasswordModal .password-field-wrapper{position:relative;}
    #teacherChangePasswordModal .password-field-wrapper input{padding-right:44px;}
    #teacherChangePasswordModal .password-toggle{position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;color:#6b7280;cursor:pointer;padding:6px;width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;border-radius:6px;font-size:16px;line-height:1;}
    #teacherChangePasswordModal .password-toggle:hover,#teacherChangePasswordModal .password-toggle:focus{color:#374151;background:rgba(107,114,128,.08);outline:none;}
    #teacherChangePasswordModal .password-toggle:focus-visible{box-shadow:0 0 0 2px rgba(74,144,217,.25);}
    #teacherChangePasswordModal .password-toggle svg{display:block;pointer-events:none;}
    @media (max-width: 720px){.edit-account-grid{grid-template-columns:1fr;}.edit-account-grid-full{grid-column:auto;}.edit-account-inline-row{flex-direction:column;}.edit-account-inline-btn{width:100%;}.edit-account-modal-footer{flex-direction:column;align-items:stretch;}.edit-account-modal-footer .btn{width:100%;}.edit-account-modal-content,.edit-account-submodal-content{max-height:94vh;}}

    /* Teachers page layout (scoped) */
    .teachers-filter-card .filter-body{padding:16px 20px;}
    .teachers-filter-card .filter-form{display:grid;grid-template-columns:minmax(260px,2fr) minmax(160px,1fr) minmax(160px,1fr) minmax(220px,1.2fr);gap:12px 14px;align-items:end;}
    .teachers-filter-card .filter-item,.teachers-filter-card .filter-search{min-width:0;}
    .teachers-filter-card .filter-item label,.teachers-filter-card .filter-search label{display:block;font-size:12px;font-weight:600;color:#5a6a7a;margin-bottom:4px;text-transform:uppercase;letter-spacing:.04em;}
    .teachers-filter-card .filter-item select,.teachers-filter-card .filter-search input{width:100%;height:38px;padding:8px 12px;border:1px solid #cdd5df;border-radius:6px;font-size:13px;color:#2c3e50;background:#fff;}
    .teachers-filter-card .filter-item select:focus,.teachers-filter-card .filter-search input:focus{outline:none;border-color:#4a90d9;box-shadow:0 0 0 3px rgba(74,144,217,.12);}
    .teachers-filter-card .filter-helper-note{grid-column:1 / -1;display:flex;align-items:center;gap:8px;font-size:12px;color:#6b7280;margin-top:2px;}
    .teachers-filter-card .filter-helper-note i{color:#4a90d9;}
    .teachers-table-card .table-header{align-items:center;}
    .teachers-table-card .table-header h2.accounts-section-title{font-size:20px;font-weight:600;line-height:1.4;margin:0;display:flex;align-items:center;gap:8px;color:inherit;}
    #archivedAccountsModal .archived-accounts-content{max-width:min(100%,980px);width:min(100%,980px);}
    #archivedAccountsModal .archived-table-container{max-height:400px;overflow:auto;margin-top:12px;border:1px solid rgba(0,0,0,0.08);border-radius:8px;}
    #archivedAccountsModal .archived-table{width:100%;border-collapse:collapse;}
    #archivedAccountsModal .archived-table th,#archivedAccountsModal .archived-table td{padding:10px 14px;font-size:13px;vertical-align:middle;box-sizing:border-box;}
    #archivedAccountsModal .archived-table th{font-size:11px;letter-spacing:.05em;text-transform:uppercase;white-space:nowrap;}
    #archivedAccountsModal .archived-table th.archived-actions-col,#archivedAccountsModal .archived-table td.archived-actions-cell{width:132px;min-width:132px;padding-left:8px;padding-right:8px;text-align:center;white-space:nowrap;}
    #archivedAccountsModal .archived-action-buttons{display:flex;flex-direction:row;align-items:center;justify-content:center;gap:10px;flex-wrap:nowrap;width:100%;margin:0 auto;min-height:40px;}
    #archivedAccountsModal .action-btn{display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;min-width:40px;flex:0 0 40px;padding:0;border-radius:10px;border:1px solid transparent;cursor:pointer;transition:background .15s ease,border-color .15s ease,color .15s ease,opacity .15s ease,box-shadow .15s ease;line-height:1;appearance:none;-webkit-appearance:none;background:none;vertical-align:middle;}
    #archivedAccountsModal .action-btn svg{width:18px;height:18px;flex:0 0 18px;pointer-events:none;display:block;}
    #archivedAccountsModal .action-btn:hover{opacity:.92;}
    #archivedAccountsModal .action-btn:focus-visible{outline:none;box-shadow:0 0 0 3px rgba(34,197,94,.22);}
    #archivedAccountsModal .action-btn-restore{background:#22c55e;color:#fff;border-color:#22c55e;}
    #archivedAccountsModal .action-btn-restore:hover{background:#16a34a;border-color:#16a34a;}
    #archivedAccountsModal .action-btn-delete{background:#dc2626;color:#fff;border-color:#dc2626;}
    #archivedAccountsModal .action-btn-delete:hover{background:#b91c1c;border-color:#b91c1c;}
    .teachers-table-card .table-header.teachers-table-header{padding:14px 20px;gap:12px;align-items:flex-start;}
    .teachers-table-card .teachers-table-title{display:flex;flex-direction:column;gap:4px;min-width:0;}
    .teachers-table-card .teachers-table-title h2{margin:0;font-size:18px;line-height:1.25;}
    .teachers-table-card .teachers-table-subtitle{margin:0;font-size:12.5px;color:#6b7280;line-height:1.45;}
    .teachers-table-card .table-header-actions{display:flex;align-items:center;gap:8px;flex-shrink:0;max-width:100%;}
    .teachers-table-card .archived-accounts-btn{white-space:nowrap;}
    .teachers-table-card .create-teacher-btn{white-space:nowrap;background:#16a34a;border-color:#16a34a;color:#fff;}
    .teachers-table-card .create-teacher-btn:hover{background:#15803d;}
    .create-teacher-modal{max-width:640px;width:min(640px,92vw);max-height:90vh;max-height:90dvh;display:flex;flex-direction:column;overflow:hidden;}
    .create-teacher-modal .modal-header{flex:0 0 auto;}
    #createTeacherForm{display:flex;flex-direction:column;flex:1 1 auto;min-height:0;}
    .create-teacher-modal .modal-body{flex:1 1 auto;min-height:0;overflow-y:auto;overscroll-behavior:contain;}
    .create-teacher-modal .modal-footer{flex:0 0 auto;position:sticky;bottom:0;background:#fff;border-top:1px solid rgba(0,0,0,.08);}
    .create-teacher-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px 16px;}
    .create-teacher-field label{display:block;font-size:12px;font-weight:700;color:#475569;margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em;}
    .create-teacher-field label span{color:#dc2626;}
    .create-teacher-field input,.create-teacher-field select{width:100%;height:42px;padding:8px 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;box-sizing:border-box;background:#fff;color:#111827;}
    .create-teacher-field input:focus,.create-teacher-field select:focus{outline:none;border-color:#16a34a;box-shadow:0 0 0 3px rgba(22,163,74,.14);}
    .create-teacher-info{margin-top:18px;padding:12px 14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;font-size:13px;color:#166534;}
    .create-teacher-info p{margin:6px 0 0;line-height:1.5;}
    .create-teacher-error{margin-top:12px;padding:10px 12px;background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;font-size:13px;color:#991b1b;}
    .ct-field-error{display:block;margin-top:5px;font-size:12px;line-height:1.35;color:#b91c1c;}
    .ct-field-error[hidden]{display:none!important;}
    .create-teacher-field.has-error input,.create-teacher-field.has-error select{border-color:#dc2626;box-shadow:0 0 0 3px rgba(220,38,38,.12);}
    .ct-sub-fields{margin-top:16px;padding:14px 16px;border:1px solid #cbd5e1;border-radius:10px;background:#f8fafc;}
    .ct-sub-fields[hidden],.ct-custom-permissions[hidden]{display:none!important;}
    .ct-sub-title{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#475569;margin-bottom:10px;}
    .ct-custom-permissions{margin-top:14px;padding-top:12px;border-top:1px dashed #cbd5e1;}
    .ct-permission-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px 12px;}
    .ct-permission-item{display:flex;align-items:center;gap:8px;font-size:13px;color:#374151;cursor:pointer;}
    .ct-permission-item input{width:16px;height:16px;accent-color:#16a34a;}
    @media (max-width:640px){.create-teacher-grid,.ct-permission-grid{grid-template-columns:1fr;}}
    .teachers-table-card table{width:100%;border-collapse:collapse;}
    .teachers-table-card th,.teachers-table-card td{padding:10px 14px;font-size:13px;vertical-align:middle;box-sizing:border-box;}
    .teachers-table-card th{font-size:11px;letter-spacing:.05em;text-transform:uppercase;white-space:nowrap;}
    .teachers-table-card td{line-height:1.35;}
    .teachers-table-card #teacherAccountsTable th.account-actions-column,
    .teachers-table-card #teacherAccountsTable td.account-actions-column{width:1%;min-width:196px;text-align:center;padding:10px 14px;box-sizing:border-box;white-space:nowrap;}
    .teachers-table-card .account-actions-buttons{display:flex;justify-content:center;align-items:center;gap:8px;flex-wrap:nowrap;width:max-content;margin:0 auto;}
    .teachers-table-card .inline-action-form{margin:0;display:inline-flex;}
    .teachers-table-card .action-btn{display:inline-flex;align-items:center;justify-content:center;width:42px;height:42px;min-width:42px;padding:0;border-radius:10px;border:1px solid transparent;font-size:16px;font-weight:600;line-height:1;cursor:pointer;transition:background .15s ease,border-color .15s ease,color .15s ease,opacity .15s ease,box-shadow .15s ease;white-space:nowrap;}
    .teachers-table-card .action-btn i,.teachers-table-card .action-btn svg{pointer-events:none;display:block;}
    .teachers-table-card .action-btn:hover{opacity:.92;}
    .teachers-table-card .action-btn:focus-visible{outline:none;box-shadow:0 0 0 3px rgba(74,144,217,.22);}
    .teachers-table-card .action-btn-view{background:#0ea5e9;color:#fff;border-color:#0ea5e9;}
    .teachers-table-card .action-btn-edit{background:#facc15;color:#111827;border-color:#facc15;}
    .teachers-table-card .action-btn-restrict{background:#f97316;color:#fff;border-color:#f97316;}
    .teachers-table-card .action-btn-unrestrict,.teachers-table-card .action-btn-reactivate{background:#22c55e;color:#fff;border-color:#22c55e;}
    .teachers-table-card .action-btn-archive{background:#64748b;color:#fff;border-color:#64748b;}
    .teachers-table-card .action-btn-resend{background:#0ea5e9;color:#fff;border-color:#0ea5e9;}
    .teachers-table-card .pagination-bar{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 20px;border-top:1px solid #e5e7eb;font-size:13px;color:#6b7280;flex-wrap:wrap;}
    .teachers-table-card .pagination{display:flex;align-items:center;gap:6px;flex-wrap:wrap;}
    .teachers-table-card .pagination .page-btn{text-decoration:none;display:inline-flex;align-items:center;justify-content:center;min-width:32px;height:32px;padding:0 10px;}
    @media (max-width: 1100px){.teachers-filter-card .filter-form{grid-template-columns:1fr 1fr;}.teachers-filter-card .filter-helper-note{grid-column:1 / -1;}}
    @media (max-width: 640px){.teachers-filter-card .filter-form{grid-template-columns:1fr;}.teachers-table-card .table-header.teachers-table-header{flex-direction:column;align-items:stretch;}.teachers-table-card .table-header-actions{width:100%;flex-wrap:wrap;}.teachers-table-card .archived-accounts-btn{flex:1 1 140px;justify-content:center;}.teachers-table-card .create-teacher-btn{flex:1 1 140px;justify-content:center;}.teachers-table-card .pagination-bar{flex-direction:column;align-items:flex-start;}}
    </style>
@endsection

@push('scripts')
<script>
var CURRENT_AUTH_USER_ID = {{ (int) auth()->id() }};
var TEACHERS_BASE_URL = "{{ url('/' . portal_prefix() . '/teachers') }}";
function openEditTeacherModal(){ document.getElementById('editTeacherModal').classList.add('show'); document.body.classList.add('modal-open'); }
function closeEditTeacherModal(){ document.getElementById('editTeacherModal').classList.remove('show'); var certConfirm=document.getElementById('certificatePrintConfirmModal'); if(certConfirm && certConfirm.classList.contains('show')) closeCertificatePrintConfirmModal(); document.body.classList.remove('modal-open'); }
function openViewAccountModal(account){ const modal=document.getElementById('viewAccountModal'); document.getElementById('viewAccountId').textContent=account.id||'—'; document.getElementById('viewAccountName').textContent=account.name||'—'; document.getElementById('viewAccountUsername').textContent=account.username||'—'; document.getElementById('viewAccountEmail').textContent=account.email||'—'; document.getElementById('viewAccountRole').textContent=account.role||'—'; document.getElementById('viewAccountRoleText').textContent=account.role||'—'; document.getElementById('viewAccountPosition').textContent=account.position||'—'; document.getElementById('viewAccountStatus').textContent=account.status||'—'; document.getElementById('viewAccountCreatedAt').textContent=account.created_at||'—'; document.getElementById('viewAccountUpdatedAt').textContent=account.updated_at||'—'; document.getElementById('viewAccountCertificatePrinting').textContent=(account.role==='Teacher'||account.role==='Sub Teacher')?(account.can_print_certificates?'Granted':'Not Granted'):'—'; const initials=account.name?account.name.split(' ').map(function(w){return w.charAt(0);}).join('').substring(0,2).toUpperCase():'A'; document.getElementById('viewAccountInitials').textContent=initials; modal.style.display='flex'; document.body.classList.add('modal-open'); }
function closeViewAccountModal(){ document.getElementById('viewAccountModal').style.display='none'; document.body.classList.remove('modal-open'); }
function openReactivateConfirmModal(id){ document.getElementById('reactivateAccountId').value=id; document.getElementById('reactivateConfirmModal').classList.add('show'); document.body.classList.add('modal-open'); }
function closeReactivateConfirmModal(){ document.getElementById('reactivateConfirmModal').classList.remove('show'); document.body.classList.remove('modal-open'); }
function openArchiveConfirmModal(id,name,url){ document.getElementById('archiveAccountId').value=id; document.getElementById('archiveAccountUrl').value=url; document.getElementById('archiveAccountName').textContent=escapeHtml(name); document.getElementById('archiveConfirmModal').classList.add('show'); document.body.classList.add('modal-open'); }
function closeArchiveConfirmModal(){ document.getElementById('archiveConfirmModal').classList.remove('show'); document.body.classList.remove('modal-open'); }
function openRestrictConfirmModal(id,name){ document.getElementById('restrictAccountId').value=id; document.getElementById('restrictAccountName').textContent=escapeHtml(name); document.getElementById('restrictConfirmModal').classList.add('show'); document.body.classList.add('modal-open'); }
function closeRestrictConfirmModal(){ document.getElementById('restrictConfirmModal').classList.remove('show'); document.body.classList.remove('modal-open'); }
function openUnrestrictConfirmModal(id,name){ document.getElementById('unrestrictAccountId').value=id; document.getElementById('unrestrictAccountName').textContent=escapeHtml(name); document.getElementById('unrestrictConfirmModal').classList.add('show'); document.body.classList.add('modal-open'); }
function closeUnrestrictConfirmModal(){ document.getElementById('unrestrictConfirmModal').classList.remove('show'); document.body.classList.remove('modal-open'); }
function escapeHtml(text){ if(text===null||text===undefined) return ''; var div=document.createElement('div'); div.textContent=String(text); return div.innerHTML; }
function escapeJs(text){ if(text===null||text===undefined) return ''; return String(text).replace(/\\/g,'\\\\').replace(/'/g,"\\'").replace(/\n/g,'\\n').replace(/\r/g,'\\r'); }
function ucfirst(str){ if(!str) return ''; return str.charAt(0).toUpperCase()+str.slice(1); }
function showTeacherToast(message,isError){
  if(window.EcoToast) window.EcoToast.show(isError?'error':'success',message,{key:'teacher-account-action'});
}
function roleLabel(role){
  return {super_admin:'Principal',sub_teacher:'Sub Teacher',teacher:'Teacher',admin:'Admin'}[role] || ucfirst(role || '');
}

var teacherRequestId=0, teacherDebounceTimer=null, archivedDebounceTimer=null;
function renderAccountRow(account){
  var idDisplay='ADM'+String(account.id).padStart(3,'0');
  var roleHtml='{{ auth()->user()->isSuperAdmin() ? '' : '' }}'; roleHtml='<span style="display:inline-block;padding:3px 10px;border-radius:12px;font-size:12px;font-weight:600;background:'+( {{ auth()->user()->isSuperAdmin() ? 'true' : 'false' }} ? 'var(--accent-light,#e8f1ff)' : '#eef2f7')+';color:'+( {{ auth()->user()->isSuperAdmin() ? 'true' : 'false' }} ? 'var(--accent,#1a73e8)' : '#475569')+';">'+roleLabel(account.role)+'</span>';
  var statusColor=account.status==='active'?'var(--green)':(account.status==='restricted'?'var(--orange)':'var(--gray)');
  var position=account.position||'N/A'; var createdAt=account.created_at?new Date(account.created_at).toLocaleDateString('en-US',{year:'numeric',month:'short',day:'numeric'}):'—'; var updatedAt=account.updated_at?new Date(account.updated_at).toLocaleDateString('en-US',{year:'numeric',month:'short',day:'numeric'}):'—';
  var editBtn='',restrictBtn='',unrestrictBtn='',archiveBtn='',reactivateBtn='';
  var resendBtn = account.status==='pending_setup' ? '<button type="button" class="action-btn action-btn-resend" data-id="'+account.id+'" title="Resend Invitation" aria-label="Resend invitation"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="m22 7-10 6L2 7"></path></svg></button>' : '';
  if({{ auth()->user()->isSuperAdmin() ? 'true' : 'false' }}){
    editBtn='<button type="button" class="action-btn action-btn-edit" title="Edit Account" aria-label="Edit account" data-modal-target="editTeacherModal" data-id="'+account.id+'" data-name="'+escapeHtml(account.name)+'" data-username="'+escapeHtml(account.username)+'" data-email="'+escapeHtml(account.email)+'" data-role="'+account.role+'" data-position="'+escapeHtml(account.position ?? '')+'" data-status="'+(account.status ?? 'active')+'" data-can-print-certificates="'+(account.can_print_certificates?'1':'0')+'"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 20h9"></path><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path></svg></button>';
    if(account.status==='active'){ restrictBtn='<button type="button" class="action-btn action-btn-restrict restrict" title="Restrict Account" aria-label="Restrict account" data-id="'+account.id+'" data-name="'+escapeHtml(account.name)+'"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg></button>'; archiveBtn='<form method="POST" action="'+TEACHERS_BASE_URL+'/'+account.id+'/archive" class="inline-action-form archive-form" data-id="'+account.id+'" data-name="'+escapeHtml(account.name)+'">'+'{{ csrf_field() }}'+'<input name="_method" value="PATCH" type="hidden"><button type="submit" class="action-btn action-btn-archive" title="Archive Account" aria-label="Archive account"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="2" y="3" width="20" height="5" rx="1"></rect><path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8"></path><path d="M10 12h4"></path></svg></button></form>'; }
    else if(account.status==='restricted'){ unrestrictBtn='<button type="button" class="action-btn action-btn-unrestrict unrestrict" title="Unrestrict Account" aria-label="Unrestrict account" data-id="'+account.id+'" data-name="'+escapeHtml(account.name)+'"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 9.9-1"></path></svg></button>'; archiveBtn='<form method="POST" action="'+TEACHERS_BASE_URL+'/'+account.id+'/archive" class="inline-action-form archive-form" data-id="'+account.id+'" data-name="'+escapeHtml(account.name)+'">'+'{{ csrf_field() }}'+'<input name="_method" value="PATCH" type="hidden"><button type="submit" class="action-btn action-btn-archive" title="Archive Account" aria-label="Archive account"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="2" y="3" width="20" height="5" rx="1"></rect><path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8"></path><path d="M10 12h4"></path></svg></button></form>'; }
    else if(account.status==='archived'){ reactivateBtn='<button type="button" class="action-btn action-btn-reactivate reactivate" title="Reactivate Account" aria-label="Reactivate account" data-id="'+account.id+'" data-name="'+escapeHtml(account.name)+'"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M3 12a9 9 0 1 0 3-6.7"></path><polyline points="3 3 3 9 9 9"></polyline></svg></button>'; }
  }
  return '<tr><td>'+idDisplay+'</td><td><strong>'+escapeHtml(account.name)+'</strong></td><td>'+escapeHtml(account.email)+'</td><td>'+roleHtml+'</td><td>'+escapeHtml(position)+'</td><td><span style="color:'+statusColor+';font-weight:600;">'+ucfirst(account.status)+'</span></td><td class="teachers-actions-cell"><div class="account-actions-buttons"><button type="button" class="action-btn action-btn-view" title="View Account Details" aria-label="View account details" onclick="openViewAccountModal({\'id\':\''+idDisplay+'\',\'name\':\''+escapeJs(account.name)+'\',\'username\':\''+escapeJs(account.username)+'\',\'email\':\''+escapeJs(account.email)+'\',\'role\':\''+escapeJs(roleLabel(account.role))+'\',\'position\':\''+escapeJs(position)+'\',\'status\':\''+escapeJs(ucfirst(account.status))+'\',\'created_at\':\''+escapeJs(createdAt)+'\',\'updated_at\':\''+escapeJs(updatedAt)+'\',\'can_print_certificates\':\''+(account.can_print_certificates?'1':'0')+'\'})"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg></button>'+editBtn+restrictBtn+unrestrictBtn+archiveBtn+reactivateBtn+resendBtn+'</div></td></tr>';
}
function showTeacherLoading(show){ var el=document.getElementById('teacherLoading'); if(el) el.style.display=show?'inline-block':'none'; }
function updateTeacherUrl(params){ var url=new URL(window.location); Object.keys(params).forEach(function(k){ if(params[k]===''||params[k]===null){url.searchParams.delete(k);} else {url.searchParams.set(k,params[k]);} }); url.searchParams.delete('page'); window.history.pushState({params:params},'',url); }
function fetchTeacherAccounts(page){
  var requestId=++teacherRequestId; var search=document.getElementById('teacherSearch').value; var role=document.getElementById('teacherRole').value; var status=document.getElementById('teacherStatus').value;
  var params={page:page}; if(search) params.search=search; if(role) params.role=role; if(status) params.status=status; updateTeacherUrl(params); showTeacherLoading(true);
  fetch('{{ route(portal_prefix() . '.teachers.fetch') }}'+'?'+new URLSearchParams(params).toString(),{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content}})
  .then(function(r){ if(!r.ok) throw new Error('Network response was not ok'); return r.json(); })
  .then(function(data){
    if(requestId!==teacherRequestId) return; var tbody=document.getElementById('teacherTableBody'); var noResults=document.getElementById('teacherNoResults'); if(noResults) noResults.remove();
    if(!data.accounts||data.accounts.length===0){ tbody.innerHTML='<tr id="teacherNoResults"><td colspan="7" style="text-align:center;padding:30px;color:var(--text-light);">No matching accounts found.</td></tr>'; }
    else { tbody.innerHTML=data.accounts.map(function(a){return renderAccountRow(a);}).join(''); }
    document.getElementById('teacherPageInfo').textContent='Showing '+(data.from||0)+' to '+(data.to||0)+' of '+(data.total||0)+' entries';
    var pagination=document.getElementById('teacherPagination'); var html=''; for(var i=1;i<=data.last_page;i++){ var active=i===data.current_page?' active':''; html+='<a href="#" class="page-btn'+active+'" data-page="'+i+'">'+i+'</a>'; } pagination.innerHTML=html;
    showTeacherLoading(false); bindEditButtons();
  }).catch(function(err){ if(requestId!==teacherRequestId) return; showTeacherLoading(false); console.error('Failed to fetch accounts:',err); });
}
function confirmArchiveAccount(){
  var archiveUrl=document.getElementById('archiveAccountUrl').value; var btn=document.getElementById('btnConfirmArchive');
  closeArchiveConfirmModal(); btn.disabled=true; btn.textContent='Archiving…';
  var formData=new FormData(); formData.append('_method','PATCH');
  fetch(archiveUrl,{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body:formData})
  .then(function(r){ if(r.status===403) throw new Error('You are not authorized to archive accounts.'); if(r.status===422) return r.json().then(function(e){throw new Error(e.error||'Validation failed.');}); if(!r.ok){var ct=r.headers.get('content-type')||''; if(ct.indexOf('application/json')===-1) throw new Error('Server returned HTML instead of JSON. Request may have been redirected.'); throw new Error('Archive failed (HTTP '+r.status+')');} var ct2=r.headers.get('content-type')||''; if(ct2.indexOf('application/json')===-1) throw new Error('Server returned HTML instead of JSON. Request may have been redirected.'); return r.json(); })
  .then(function(data){ var page=(new URL(window.location)).searchParams.get('page')||1; fetchTeacherAccounts(parseInt(page,10)); showTeacherToast(data.success||'Account archived successfully.'); })
  .catch(function(err){ console.error('Archive failed:',err); showTeacherToast(err.message||'Failed to archive account.',true); })
  .finally(function(){ btn.disabled=false; btn.textContent='Archive Account'; });
}
function confirmRestrictAccount(){
  var id=document.getElementById('restrictAccountId').value; var btn=document.getElementById('btnConfirmRestrict');
  if(btn.disabled) return;
  closeRestrictConfirmModal(); btn.disabled=true; btn.textContent='Restricting…';
  fetch(TEACHERS_BASE_URL+'/'+id+'/restrict',{method:'PATCH',headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content}})
  .then(function(r){ if(r.status===403) throw new Error('You are not authorized to restrict accounts.'); if(r.status===422) return r.json().then(function(e){throw new Error(e.error||'Validation failed.');}); if(!r.ok) throw new Error('Restrict failed (HTTP '+r.status+')'); return r.json(); })
  .then(function(data){ var page=(new URL(window.location)).searchParams.get('page')||1; fetchTeacherAccounts(parseInt(page,10)); showTeacherToast(data.success||'Account restricted successfully.'); })
  .catch(function(err){ console.error('Restrict failed:',err); showTeacherToast(err.message||'Failed to restrict account.',true); })
  .finally(function(){ btn.disabled=false; btn.textContent='Restrict Account'; });
}
function confirmUnrestrictAccount(){
  var id=document.getElementById('unrestrictAccountId').value; var btn=document.getElementById('btnConfirmUnrestrict');
  if(btn.disabled) return;
  closeUnrestrictConfirmModal(); btn.disabled=true; btn.textContent='Unrestricting…';
  fetch(TEACHERS_BASE_URL+'/'+id+'/unrestrict',{method:'PATCH',headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content}})
  .then(function(r){ if(r.status===403) throw new Error('You are not authorized to unrestrict accounts.'); if(r.status===422) return r.json().then(function(e){throw new Error(e.error||'Validation failed.');}); if(!r.ok) throw new Error('Unrestrict failed (HTTP '+r.status+')'); return r.json(); })
  .then(function(data){ var page=(new URL(window.location)).searchParams.get('page')||1; fetchTeacherAccounts(parseInt(page,10)); showTeacherToast(data.success||'Account unrestricted successfully.'); })
  .catch(function(err){ console.error('Unrestrict failed:',err); showTeacherToast(err.message||'Failed to unrestrict account.',true); })
  .finally(function(){ btn.disabled=false; btn.textContent='Unrestrict Account'; });
}
var CREATE_TEACHER_URL = @json(route('admin.teachers.create-account'));
var ELIGIBLE_TEACHERS_URL = @json(route('admin.teachers.eligible-for-assignment'));
var createTeacherSubmitting = false;

function loadEligibleTeachers(){
  var select = document.getElementById('ctTeacherId');
  if (!select) return;
  select.innerHTML = '<option value="">Loading teachers…</option>';
  fetch(ELIGIBLE_TEACHERS_URL, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
    .then(function(r){ return r.json(); })
    .then(function(data){
      var html = '<option value="">Unassigned</option>';
      (data.teachers || []).forEach(function(t){
        html += '<option value="' + t.id + '">' + escapeHtml(t.name) + '</option>';
      });
      select.innerHTML = html;
    })
    .catch(function(){ select.innerHTML = '<option value="">Unassigned</option>'; });
}

function syncSubTeacherFields(){
  var form = document.getElementById('createTeacherForm');
  if (!form) return;
  var isSub = form.elements.role.value === 'sub_teacher';
  var block = document.getElementById('ctSubTeacherFields');
  if (block) block.hidden = !isSub;
  if (!isSub) {
    var teacherSelect = document.getElementById('ctTeacherId');
    if (teacherSelect) teacherSelect.value = '';
    var accessSelect = document.getElementById('ctAccessLevel');
    if (accessSelect) accessSelect.value = 'collaborator';
    form.querySelectorAll('input[name="custom_permissions[]"]').forEach(function(cb){ cb.checked = false; });
  }
  var customBlock = document.getElementById('ctCustomPermissions');
  if (customBlock) customBlock.hidden = !(isSub && form.elements.access_level.value === 'custom');
}
function openCreateTeacherModal(){
  var modal = document.getElementById('createTeacherModal');
  if (!modal) return;
  document.getElementById('createTeacherForm').reset();
  syncSubTeacherFields();
  loadEligibleTeachers();
  document.querySelectorAll('#createTeacherForm .ct-field-error').forEach(function(el){ el.textContent = ''; el.hidden = true; });
  document.querySelectorAll('#createTeacherForm .create-teacher-field.has-error').forEach(function(el){ el.classList.remove('has-error'); });
  document.querySelectorAll('#createTeacherForm [aria-invalid="true"]').forEach(function(el){ el.removeAttribute('aria-invalid'); });
  document.getElementById('createTeacherError').hidden = true;
  document.getElementById('createTeacherError').textContent = '';
  modal.style.display = 'flex';
  modal.classList.add('show');
  document.body.classList.add('modal-open');
  document.getElementById('ctName').focus();
}
function closeCreateTeacherModal(){
  var modal = document.getElementById('createTeacherModal');
  if (!modal) return;
  modal.style.display = 'none';
  modal.classList.remove('show');
  document.body.classList.remove('modal-open');
}
(function initCreateTeacher(){
  var btn = document.getElementById('createTeacherAccountBtn');
  if (!btn) return;
  var form = document.getElementById('createTeacherForm');
  var submit = document.getElementById('createTeacherSubmit');
  var errorEl = document.getElementById('createTeacherError');
  var FIELD_IDS = {
    name: 'ctName', username: 'ctUsername', email: 'ctEmail',
    position: 'ctPosition', role: 'ctRole', teacher_id: 'ctTeacherId',
    access_level: 'ctAccessLevel', custom_permissions: 'ctCustomPermissions'
  };

  function clearFieldError(key){
    var input = document.getElementById(FIELD_IDS[key]);
    var errorId = input ? input.id + 'Error' : null;
    var message = errorId ? document.getElementById(errorId) : null;
    if (message) { message.textContent = ''; message.hidden = true; }
    if (input && (input.tagName === 'INPUT' || input.tagName === 'SELECT')) {
      input.removeAttribute('aria-invalid');
      var wrapper = input.closest('.create-teacher-field');
      if (wrapper) wrapper.classList.remove('has-error');
    }
  }
  function clearAllFieldErrors(){
    Object.keys(FIELD_IDS).forEach(clearFieldError);
    errorEl.textContent = '';
    errorEl.hidden = true;
  }
  function setFieldError(key, message){
    var input = document.getElementById(FIELD_IDS[key]);
    var errorId = input ? input.id + 'Error' : null;
    var target = errorId ? document.getElementById(errorId) : null;
    if (!target || !message) return false;
    // Server errors on delegated fields belong to a hidden block until revealed.
    if (key === 'teacher_id' || key === 'access_level' || key === 'custom_permissions') {
      var subBlock = document.getElementById('ctSubTeacherFields');
      if (subBlock) subBlock.hidden = false;
    }
    if (key === 'custom_permissions') {
      var customBlock = document.getElementById('ctCustomPermissions');
      if (customBlock) customBlock.hidden = false;
    }
    target.textContent = message;
    target.hidden = false;
    if (input && (input.tagName === 'INPUT' || input.tagName === 'SELECT')) {
      input.setAttribute('aria-invalid', 'true');
      var wrapper = input.closest('.create-teacher-field');
      if (wrapper) wrapper.classList.add('has-error');
    }
    return true;
  }
  function applyServerErrors(errors){
    var firstField = null;
    Object.keys(errors).forEach(function(key){
      var fieldKey = key.split('.')[0]; // custom_permissions.0 -> custom_permissions
      var message = Array.isArray(errors[key]) ? errors[key].join(' ') : String(errors[key]);
      if (setFieldError(fieldKey, message) && !firstField) firstField = fieldKey;
    });
    return firstField;
  }

  btn.addEventListener('click', openCreateTeacherModal);
  document.getElementById('createTeacherModalClose').addEventListener('click', closeCreateTeacherModal);
  document.getElementById('createTeacherCancel').addEventListener('click', closeCreateTeacherModal);
  document.getElementById('createTeacherModal').addEventListener('click', function(e){ if(e.target===this) closeCreateTeacherModal(); });
  document.addEventListener('keydown', function(e){ if(e.key==='Escape' && document.getElementById('createTeacherModal').style.display==='flex') closeCreateTeacherModal(); });

  var roleSelect = document.getElementById('ctRole');
  var accessSelect = document.getElementById('ctAccessLevel');
  if (roleSelect) roleSelect.addEventListener('change', syncSubTeacherFields);
  if (accessSelect) accessSelect.addEventListener('change', syncSubTeacherFields);
  syncSubTeacherFields();

  form.addEventListener('submit', function(e){
    e.preventDefault();
    // One guard prevents duplicate requests from fast/repeated submissions.
    if (createTeacherSubmitting) return;
    clearAllFieldErrors();

    var required = ['name','username','email','position','role'];
    var firstMissing = null;
    for (var i=0;i<required.length;i++){
      var field = form.elements[required[i]];
      if (!field || !String(field.value).trim()) {
        setFieldError(required[i], 'This field is required.');
        if (!firstMissing) firstMissing = required[i];
      }
    }
    if (firstMissing) {
      errorEl.textContent = 'Please complete all required fields.';
      errorEl.hidden = false;
      var missingInput = document.getElementById(FIELD_IDS[firstMissing]);
      if (missingInput) missingInput.focus();
      return;
    }

    createTeacherSubmitting = true;
    submit.disabled = true;
    submit.textContent = 'Creating Account...';

    var payload = new FormData(form);
    // Never send irrelevant permission data for other roles.
    if (form.elements.role.value !== 'sub_teacher') {
      payload.delete('teacher_id');
      payload.delete('access_level');
      payload.delete('custom_permissions[]');
    } else {
      // Empty selects add nothing to server-side validation.
      if (!String(form.elements.teacher_id.value).trim()) payload.delete('teacher_id');
      if (form.elements.access_level.value !== 'custom') payload.delete('custom_permissions[]');
    }
    payload.append('_token', document.querySelector('meta[name="csrf-token"]').content);

    fetch(CREATE_TEACHER_URL, {
      method: 'POST',
      body: payload,
      credentials: 'same-origin',
      headers: {'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}
    })
    .then(function(r){ return r.json().catch(function(){ return null; }).then(function(data){ return {ok:r.ok,status:r.status,data:data||{}}; }); })
    .then(function(result){
      var data = result.data;
      if (result.ok && data.success) {
        clearAllFieldErrors();
        closeCreateTeacherModal();
        if (window.EcoToast) window.EcoToast.show(data.invitation_sent ? 'success' : 'warning', data.message, {key:'create-teacher'});
        form.reset();
        syncSubTeacherFields();
        fetchTeacherAccounts(1);
        return;
      }
      // HTTP 422: Laravel returns { message, errors: {field: [messages]} }.
      // Keep the modal open, leave every entered value untouched and show the
      // exact server message next to the field that failed validation.
      var firstField = data.errors ? applyServerErrors(data.errors) : null;
      errorEl.textContent = data.message || 'Unable to create the teacher account. Please try again.';
      errorEl.hidden = false;
      if (firstField) {
        var invalidInput = document.getElementById(FIELD_IDS[firstField]);
        if (invalidInput && (invalidInput.tagName === 'INPUT' || invalidInput.tagName === 'SELECT')) {
          invalidInput.focus();
        }
      }
    })
    .catch(function(){
      errorEl.textContent = 'Unable to create the teacher account. Please try again.';
      errorEl.hidden = false;
    })
    .finally(function(){
      createTeacherSubmitting = false;
      submit.disabled = false;
      submit.textContent = 'Create Account';
    });
  });
})();

document.addEventListener('click', function(e){
  var button = e.target.closest('.action-btn-resend');
  if (!button) return;
  e.preventDefault();
  var id = button.getAttribute('data-id');
  if (!id || button.disabled) return;
  button.disabled = true;
  var originalTitle = button.getAttribute('title');
  button.setAttribute('title','Sending invitation...');
  var payload = new FormData();
  payload.append('_token', document.querySelector('meta[name="csrf-token"]').content);
  fetch('{{ url('/' . portal_prefix() . '/teachers') }}/' + id + '/resend-invitation', {
    method:'POST', body:payload, credentials:'same-origin',
    headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}
  })
  .then(function(r){ return r.json().catch(function(){ return null; }).then(function(data){ return {ok:r.ok,data:data}; }); })
  .then(function(result){
    var data = result.data || {};
    if (window.EcoToast) window.EcoToast.show(result.ok && data.success ? 'success' : 'error', data.message || 'Unable to resend the invitation.', {key:'resend-invitation-'+id});
  })
  .catch(function(){ if (window.EcoToast) window.EcoToast.show('error','Unable to resend the invitation. Please try again.',{key:'resend-invitation-'+id}); })
  .finally(function(){ button.disabled=false; button.setAttribute('title',originalTitle); });
});

function openArchivedAccountsModal(){ document.getElementById('archivedAccountsModal').classList.add('show'); document.body.classList.add('modal-open'); document.getElementById('archivedSearch').value=''; fetchArchivedAccounts(1); }
function closeArchivedAccountsModal(){ document.getElementById('archivedAccountsModal').classList.remove('show'); document.body.classList.remove('modal-open'); clearTimeout(archivedDebounceTimer); }
function openUnarchiveConfirmModal(id,name){ var btn=document.getElementById('btnConfirmUnarchive'); if(btn && btn.disabled) return; document.getElementById('unarchiveAccountId').value=id; document.getElementById('unarchiveAccountName').textContent=escapeHtml(name); document.getElementById('unarchiveConfirmModal').classList.add('show'); document.body.classList.add('modal-open'); }
function closeUnarchiveConfirmModal(){ document.getElementById('unarchiveConfirmModal').classList.remove('show'); document.body.classList.remove('modal-open'); }
function openDeleteArchivedConfirmModal(id,name){ var btn=document.getElementById('btnConfirmDeleteArchived'); if(btn && btn.disabled) return; document.getElementById('deleteArchivedAccountId').value=id; var label=document.getElementById('deleteArchivedAccountLabel'); if(label) label.textContent='Account: '+(name||'—'); document.getElementById('deleteArchivedConfirmModal').classList.add('show'); document.body.classList.add('modal-open'); }
function closeDeleteArchivedConfirmModal(){ document.getElementById('deleteArchivedConfirmModal').classList.remove('show'); document.body.classList.remove('modal-open'); }
function renderArchivedAccountRow(account){
  var idDisplay='ADM'+String(account.id).padStart(3,'0');
  var archivedAt=account.updated_at?new Date(account.updated_at).toLocaleDateString('en-US',{year:'numeric',month:'short',day:'numeric'}):'—';
  var roleHtml='<span style="display:inline-block;padding:3px 10px;border-radius:12px;font-size:12px;font-weight:600;background:#eef2f7;color:#475569;">'+roleLabel(account.role)+'</span>';
  return '<tr><td>'+idDisplay+'</td><td><strong>'+escapeHtml(account.name)+'</strong></td><td>'+escapeHtml(account.email)+'</td><td>'+roleHtml+'</td><td>'+escapeHtml(account.position ?? 'N/A')+'</td><td><span style="font-size:12px;color:var(--text-light);">'+archivedAt+'</span></td><td class="teachers-actions-cell archived-actions-cell"><div class="account-actions-buttons archived-action-buttons"><button type="button" class="action-btn action-btn-restore unarchive" title="Restore Account" aria-label="Restore Account" data-id="'+account.id+'" data-name="'+escapeHtml(account.name)+'"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M3 12a9 9 0 1 0 3-6.7"></path><polyline points="3 3 3 9 9 9"></polyline><path d="M21 12a9 9 0 0 0-15-6.7L3 8"></path></svg></button><button type="button" class="action-btn action-btn-delete delete-archived" title="Delete Account" aria-label="Delete Account" data-id="'+account.id+'" data-name="'+escapeHtml(account.name)+'"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg></button></div></td></tr>';
}
function fetchArchivedAccounts(page){
  if(page===undefined) page=1; var search=document.getElementById('archivedSearch').value; var params={page:page}; if(search) params.search=search;
  fetch('{{ route(portal_prefix() . '.teachers.archived') }}'+'?'+new URLSearchParams(params).toString(),{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content}})
  .then(function(r){ if(r.status===403) throw new Error('You are not authorized to view archived accounts.'); if(!r.ok) throw new Error('Failed to load archived accounts (HTTP '+r.status+')'); return r.json(); })
  .then(function(data){ var tbody=document.getElementById('archivedAccountsTableBody'); var noResults=document.getElementById('archivedNoResults'); if(!data.accounts||data.accounts.length===0){tbody.innerHTML=''; noResults.style.display='block';} else {noResults.style.display='none'; tbody.innerHTML=data.accounts.map(function(a){return renderArchivedAccountRow(a);}).join('');} })
  .catch(function(err){ console.error('Failed to load archived accounts:',err); document.getElementById('archivedAccountsTableBody').innerHTML=''; var nr=document.getElementById('archivedNoResults'); nr.textContent='Error loading archived accounts.'; nr.style.display='block'; });
}
function confirmUnarchiveAccount(){
  var id=document.getElementById('unarchiveAccountId').value; var btn=document.getElementById('btnConfirmUnarchive');
  if(btn.disabled) return;
  closeUnarchiveConfirmModal(); btn.disabled=true; btn.textContent='Restoring…';
  fetch(TEACHERS_BASE_URL+'/'+id+'/reactivate',{method:'PATCH',headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content}})
  .then(function(r){ if(r.status===403) throw new Error('You are not authorized to restore accounts.'); if(r.status===422) return r.json().then(function(e){throw new Error(e.error||'Validation failed.');}); if(!r.ok) throw new Error('Restore failed (HTTP '+r.status+')'); return r.json(); })
  .then(function(data){ fetchArchivedAccounts(1); var page=(new URL(window.location)).searchParams.get('page')||1; fetchTeacherAccounts(parseInt(page,10)); showTeacherToast('Account restored successfully.'); })
  .catch(function(err){ console.error('Restore failed:',err); showTeacherToast(err.message||'Failed to restore account.',true); })
  .finally(function(){ btn.disabled=false; btn.textContent='Unarchive Account'; });
}
function confirmDeleteArchivedAccount(){
  var id=document.getElementById('deleteArchivedAccountId').value; var btn=document.getElementById('btnConfirmDeleteArchived');
  if(!id || btn.disabled) return;
  closeDeleteArchivedConfirmModal(); btn.disabled=true; btn.textContent='Deleting…';
  fetch(TEACHERS_BASE_URL+'/'+id+'/delete',{method:'DELETE',headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content}})
  .then(function(r){ if(r.status===403) throw new Error('You are not authorized to delete accounts.'); if(r.status===422) return r.json().then(function(e){throw new Error(e.error||'This account cannot be deleted safely.');}); if(!r.ok) throw new Error('Delete failed (HTTP '+r.status+')'); return r.json(); })
  .then(function(data){ fetchArchivedAccounts(1); var page=(new URL(window.location)).searchParams.get('page')||1; fetchTeacherAccounts(parseInt(page,10)); showTeacherToast(data.message||'Account permanently deleted.'); })
  .catch(function(err){ console.error('Delete failed:',err); showTeacherToast(err.message||'Failed to delete account.',true); })
  .finally(function(){ btn.disabled=false; btn.textContent='Delete Account'; });
}
function confirmReactivateAccount(){
  var id=document.getElementById('reactivateAccountId').value; var btn=document.getElementById('btnConfirmReactivate');
  closeReactivateConfirmModal(); btn.disabled=true; btn.textContent='Reactivating…';
  fetch(TEACHERS_BASE_URL+'/'+id+'/reactivate',{method:'PATCH',headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content}})
  .then(function(r){ if(r.status===403) throw new Error('You are not authorized to reactivate accounts.'); if(r.status===422) return r.json().then(function(e){throw new Error(e.error||'Validation failed.');}); if(!r.ok) throw new Error('Reactivate failed (HTTP '+r.status+')'); return r.json(); })
  .then(function(data){ var page=(new URL(window.location)).searchParams.get('page')||1; fetchTeacherAccounts(parseInt(page,10)); showTeacherToast(data.success||'Account reactivated successfully.'); })
  .catch(function(err){ console.error('Reactivate failed:',err); showTeacherToast(err.message||'Failed to reactivate account.',true); })
  .finally(function(){ btn.disabled=false; btn.textContent='Reactivate Account'; });
}
function initTeacherFilters(){
  var searchInput=document.getElementById('teacherSearch'); var roleSelect=document.getElementById('teacherRole'); var statusSelect=document.getElementById('teacherStatus');
  if(searchInput){ searchInput.addEventListener('input',function(){ clearTimeout(teacherDebounceTimer); teacherDebounceTimer=setTimeout(function(){ fetchTeacherAccounts(1); },300); }); }
  [roleSelect,statusSelect].forEach(function(el){ if(el){ el.addEventListener('change',function(){ fetchTeacherAccounts(1); }); } });
  var pagination=document.getElementById('teacherPagination'); if(pagination){ pagination.addEventListener('click',function(e){ var btn=e.target.closest('.page-btn'); if(!btn) return; e.preventDefault(); var page=parseInt(btn.getAttribute('data-page'),10); if(!isNaN(page)) fetchTeacherAccounts(page); }); }
  var tbody=document.getElementById('teacherTableBody');
  if(tbody){ tbody.addEventListener('click',function(e){
    var archiveForm=e.target.closest('form.archive-form'); if(archiveForm){ e.preventDefault(); openArchiveConfirmModal(archiveForm.getAttribute('data-id'),archiveForm.getAttribute('data-name'),archiveForm.action); return; }
    var restrictBtn=e.target.closest('button.restrict'); if(restrictBtn){ e.preventDefault(); openRestrictConfirmModal(restrictBtn.getAttribute('data-id'),restrictBtn.getAttribute('data-name')); return; }
    var unrestrictBtn=e.target.closest('button.unrestrict'); if(unrestrictBtn){ e.preventDefault(); openUnrestrictConfirmModal(unrestrictBtn.getAttribute('data-id'),unrestrictBtn.getAttribute('data-name')); return; }
    var reactivateBtn=e.target.closest('button.reactivate'); if(reactivateBtn){ e.preventDefault(); openReactivateConfirmModal(reactivateBtn.getAttribute('data-id'),reactivateBtn.getAttribute('data-name')); }
  }); }
}
function bindEditButtons(){
  var editForm=document.getElementById('editTeacherForm'); var editBtns=document.querySelectorAll('[data-modal-target="editTeacherModal"]');
  editBtns.forEach(function(btn){
    if(btn.dataset.boundEdit==='1') return; btn.dataset.boundEdit='1';
    btn.addEventListener('click',function(){
      if(editForm) editForm.action=TEACHERS_BASE_URL+'/'+btn.dataset.id;
      document.getElementById('editTeacherId').value=btn.dataset.id||'';
      document.getElementById('editTeacherName').value=btn.dataset.name||'';
      document.getElementById('editTeacherUsername').value=btn.dataset.username||'';
      document.getElementById('editTeacherEmail').value=btn.dataset.email||'';
      document.getElementById('editTeacherEmailDisplay').value=btn.dataset.email||'';
      var roleTextEl=document.getElementById('editTeacherRoleText');
      if(roleTextEl){ roleTextEl.value=btn.dataset.role||''; var hidden=document.getElementById('editTeacherRoleHidden'); if(hidden) hidden.value=btn.dataset.role||''; }
      else { var roleSel=document.getElementById('editTeacherRole'); if(roleSel) roleSel.value=btn.dataset.role||''; }
      document.getElementById('editTeacherPosition').value=btn.dataset.position||'';
      document.getElementById('editTeacherStatus').value=btn.dataset.status||'active';
      var certSection=document.getElementById('editCertificatePermissionsSection');
      if(certSection) certSection.hidden=!(btn.dataset.role==='teacher'||btn.dataset.role==='sub_teacher');
      var certSelect=document.getElementById('editCertificatePrinting');
      if(certSelect){
        certSelect.value=btn.dataset.canPrintCertificates==='1'?'1':'0';
        certSelect.dataset.previous=certSelect.value;
        certSelect.dataset.accountId=btn.dataset.id||'';
        certSelect.dataset.accountName=btn.dataset.name||'';
      }
      openEditTeacherModal();
    });
  });
}
/* --- Certificate printing permission management (Admin / Principal only) --- */
var certificatePrintSubmitting=false;
function openCertificatePrintConfirmModal(id,name,granted){
  if(certificatePrintSubmitting) return;
  document.getElementById('certificatePrintConfirmId').value=id;
  document.getElementById('certificatePrintConfirmValue').value=granted?'1':'0';
  document.getElementById('certificatePrintConfirmTitle').textContent=granted?'Grant Certificate Printing Access?':'Revoke Certificate Printing Access?';
  document.getElementById('certificatePrintConfirmMessage').textContent=granted?'Allow this teacher to print certificates in EcoCollect?':'This teacher will no longer be authorized to print certificates.';
  document.getElementById('certificatePrintConfirmAccount').textContent=name||'';
  var btn=document.getElementById('btnConfirmCertificatePrint');
  btn.textContent=granted?'Grant Access':'Revoke Access';
  btn.className='btn '+(granted?'btn-success':'btn-danger');
  btn.disabled=false;
  var modal=document.getElementById('certificatePrintConfirmModal');
  modal.classList.add('show');
  document.body.classList.add('modal-open');
  btn.focus();
}
function closeCertificatePrintConfirmModal(revert){
  if(revert!==false){
    var select=document.getElementById('editCertificatePrinting');
    if(select && select.dataset.previous!==undefined) select.value=select.dataset.previous;
  }
  var modal=document.getElementById('certificatePrintConfirmModal');
  if(modal) modal.classList.remove('show');
  var editModal=document.getElementById('editTeacherModal');
  if(!editModal || !editModal.classList.contains('show')) document.body.classList.remove('modal-open');
  var btn=document.getElementById('btnConfirmCertificatePrint');
  if(btn) btn.disabled=false;
}
function confirmCertificatePrintPermission(){
  if(certificatePrintSubmitting) return;
  var id=document.getElementById('certificatePrintConfirmId').value;
  var value=document.getElementById('certificatePrintConfirmValue').value;
  if(!id) return;
  certificatePrintSubmitting=true;
  var btn=document.getElementById('btnConfirmCertificatePrint');
  btn.disabled=true; btn.textContent='Saving…';
  closeCertificatePrintConfirmModal(false);
  fetch(TEACHERS_BASE_URL+'/'+id+'/certificate-permissions',{
    method:'PATCH',
    headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},
    body:new URLSearchParams({can_print_certificates:value})
  })
  .then(function(r){ return r.json().catch(function(){ return null; }).then(function(data){ return {ok:r.ok,data:data||{}}; }); })
  .then(function(result){
    if(!result.ok || !result.data.success) throw new Error(result.data.error||result.data.message||'Unable to update certificate printing access.');
    var select=document.getElementById('editCertificatePrinting');
    if(select){ select.value=value; select.dataset.previous=value; }
    var viewLabel=document.getElementById('viewAccountCertificatePrinting');
    if(viewLabel && viewLabel.textContent!=='—') viewLabel.textContent=value==='1'?'Granted':'Not Granted';
    showTeacherToast(result.data.message);
  })
  .catch(function(err){
    var select=document.getElementById('editCertificatePrinting');
    if(select && select.dataset.previous!==undefined) select.value=select.dataset.previous;
    showTeacherToast(err.message||'Unable to update certificate printing access.',true);
  })
  .finally(function(){
    certificatePrintSubmitting=false;
    if(btn){ btn.disabled=false; btn.textContent=value==='1'?'Grant Access':'Revoke Access'; }
  });
}
(function initCertificatePrintPermission(){
  var select=document.getElementById('editCertificatePrinting');
  if(!select) return;
  select.addEventListener('change',function(){
    if(!select.dataset.accountId) return;
    openCertificatePrintConfirmModal(select.dataset.accountId,select.dataset.accountName||'',select.value==='1');
  });
  var modal=document.getElementById('certificatePrintConfirmModal');
  if(modal) modal.addEventListener('click',function(e){ if(e.target===this) closeCertificatePrintConfirmModal(); });
  document.addEventListener('keydown',function(e){
    if(e.key==='Escape' && document.getElementById('certificatePrintConfirmModal').classList.contains('show')) closeCertificatePrintConfirmModal();
  });
})();
function openTeacherChangeEmailModal(){
  var email=document.getElementById('editTeacherEmailDisplay').value||'';
  document.getElementById('teacherCurrentEmailField').value=email;
  document.getElementById('teacherNewEmailInput').value='';
  document.getElementById('teacherEmailOtpInput').value='';
  document.getElementById('teacherEmailFlowHelp').textContent='Click Send to request a verification code for the current registered email address.';
  document.getElementById('teacherEmailStageInfo').textContent='Send a verification code to the current registered email, then enter it to save the new email address immediately.';
  document.getElementById('teacherSendEmailOtpBtn').disabled=false;
  document.getElementById('teacherSendEmailOtpBtn').textContent='Send';
  document.getElementById('teacherVerifyEmailBtn').textContent='Verify & Update Email';
  document.getElementById('teacherVerifyEmailBtn').dataset.approvedEmail='';
  clearTeacherModalNotice('teacherEmailNotification');
  var overlay=document.getElementById('teacherChangeEmailModal'); overlay.classList.add('show'); overlay.setAttribute('aria-hidden','false'); document.body.classList.add('modal-open');
  var input=document.getElementById('teacherNewEmailInput'); if(input){ setTimeout(function(){ input.focus(); },0); }
}
function closeTeacherChangeEmailModal(){
  var overlay=document.getElementById('teacherChangeEmailModal');
  if(overlay && overlay.contains(document.activeElement)){ try{ document.activeElement.blur(); }catch(e){} }
  clearTeacherModalNotice('teacherEmailNotification');
  overlay.classList.remove('show'); overlay.setAttribute('aria-hidden','true');
  if(!document.getElementById('editTeacherModal').classList.contains('show') && !document.getElementById('teacherChangePasswordModal').classList.contains('show')) document.body.classList.remove('modal-open'); else document.body.classList.add('modal-open');
  var trigger=document.getElementById('openChangeEmailDialogBtn'); if(trigger){ setTimeout(function(){ trigger.focus(); },0); }
}
function openTeacherChangePasswordModal(){
  var id=document.getElementById('editTeacherId').value; var currentGroup=document.getElementById('teacherCurrentPasswordGroup'); var currentInput=document.getElementById('teacherCurrentPasswordInput');
  if(currentGroup){ currentGroup.style.display=''; }
  if(currentInput){ currentInput.required=true; currentInput.value=''; }
  document.getElementById('teacherNewPasswordInput').value=''; document.getElementById('teacherConfirmPasswordInput').value=''; resetTeacherPasswordToggles(); updateTeacherPasswordRequirements(); clearTeacherModalNotice('teacherPasswordNotification');
  document.getElementById('teacherPasswordRequirementsBox').style.display='none';
  var overlay=document.getElementById('teacherChangePasswordModal'); overlay.classList.add('show'); overlay.setAttribute('aria-hidden','false'); document.body.classList.add('modal-open');
  var focusEl=document.getElementById('teacherCurrentPasswordInput'); if(focusEl){ setTimeout(function(){ focusEl.focus(); },0); }
}
function closeTeacherChangePasswordModal(){
  var overlay=document.getElementById('teacherChangePasswordModal');
  if(overlay && overlay.contains(document.activeElement)){ try{ document.activeElement.blur(); }catch(e){} }
  clearTeacherModalNotice('teacherPasswordNotification');
  overlay.classList.remove('show'); overlay.setAttribute('aria-hidden','true');
  if(!document.getElementById('editTeacherModal').classList.contains('show') && !document.getElementById('teacherChangeEmailModal').classList.contains('show')) document.body.classList.remove('modal-open'); else document.body.classList.add('modal-open');
  var trigger=document.getElementById('openChangePasswordDialogBtn'); if(trigger){ setTimeout(function(){ trigger.focus(); },0); }
}
var teacherModalNoticeTimers={};
function clearTeacherModalNotice(id){
  var el=document.getElementById(id); if(!el) return;
  if(teacherModalNoticeTimers[id]){ clearTimeout(teacherModalNoticeTimers[id].hide); clearTimeout(teacherModalNoticeTimers[id].remove); delete teacherModalNoticeTimers[id]; }
  el.classList.remove('is-visible','is-hiding','modal-notice-success','modal-notice-error');
  el.setAttribute('aria-live','polite');
  el.textContent='';
  el.style.display='none';
}
function showTeacherModalNotice(id,type,message){
  var el=document.getElementById(id); if(!el) return;
  if(teacherModalNoticeTimers[id]){ clearTimeout(teacherModalNoticeTimers[id].hide); clearTimeout(teacherModalNoticeTimers[id].remove); }
  var isError = type === 'error';
  el.className='modal-notice ' + (isError ? 'modal-notice-error' : 'modal-notice-success');
  el.setAttribute('role', isError ? 'alert' : 'status');
  el.setAttribute('aria-live', isError ? 'assertive' : 'polite');
  el.innerHTML = '<span class="modal-notice-icon" aria-hidden="true">' + (isError ? '!' : '&#10003;') + '</span><span class="modal-notice-text"></span>';
  el.querySelector('.modal-notice-text').textContent = message || (isError ? 'An error occurred.' : 'Success.');
  el.style.display='flex';
  el.classList.remove('is-hiding');
  void el.offsetWidth;
  el.classList.add('is-visible');
  teacherModalNoticeTimers[id]={
    hide:setTimeout(function(){ if(!el.isConnected) return; el.classList.add('is-hiding'); el.classList.remove('is-visible'); teacherModalNoticeTimers[id] && (teacherModalNoticeTimers[id].remove=setTimeout(function(){ if(!el.isConnected) return; el.style.display='none'; el.classList.remove('is-hiding'); el.textContent=''; },260)); },5000)
  };
}
function showTeacherEmailError(msg){ showTeacherModalNotice('teacherEmailNotification','error',msg); }
function showTeacherEmailSuccess(msg){ showTeacherModalNotice('teacherEmailNotification','success',msg); }
function hideTeacherEmailFeedback(){ clearTeacherModalNotice('teacherEmailNotification'); }
function showTeacherPasswordError(msg){ showTeacherModalNotice('teacherPasswordNotification','error',msg); }
function hideTeacherPasswordError(){ clearTeacherModalNotice('teacherPasswordNotification'); }
function resetTeacherPasswordToggles(){
  var labels={teacherCurrentPasswordInput:'Show current password',teacherNewPasswordInput:'Show new password',teacherConfirmPasswordInput:'Show confirm password'};
  ['teacherCurrentPasswordInput','teacherNewPasswordInput','teacherConfirmPasswordInput'].forEach(function(id){
    var i=document.getElementById(id); if(i) i.type='password';
    var btn=document.querySelector('.password-toggle[data-target="'+id+'"]');
    if(btn){ btn.setAttribute('aria-label', labels[id]||'Show password'); btn.innerHTML='<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>'; }
  });
}
function updateTeacherPasswordRequirements(){
  var val=document.getElementById('teacherNewPasswordInput').value||''; var confirmVal=document.getElementById('teacherConfirmPasswordInput').value||'';
  var len=val.length>=8, caseOk=/[A-Z]/.test(val)&&/[a-z]/.test(val), numOk=/[0-9]/.test(val), matchOk=confirmVal!==''&&val===confirmVal;
  setTeacherReqState('teacherReqLength',len); setTeacherReqState('teacherReqCase',caseOk); setTeacherReqState('teacherReqNumber',numOk); setTeacherReqState('teacherReqMatch',matchOk);
  var feedback=document.getElementById('teacherPasswordMatchFeedback'); if(!confirmVal){feedback.textContent=''; feedback.style.color='#6b7280';} else if(val===confirmVal){feedback.textContent='Passwords match.'; feedback.style.color='#16a34a';} else {feedback.textContent="Password didn't match. Try again."; feedback.style.color='#dc2626';}
}
function setTeacherReqState(id,ok){ var el=document.getElementById(id); if(!el) return; el.style.color=ok?'#00C853':'#6b7280'; el.style.fontWeight=ok?'600':'400'; }
function bindPasswordToggles(scope){
  function teacherPasswordToggleLabel(target, show){ var labels={teacherCurrentPasswordInput:['Show current password','Hide current password'],teacherNewPasswordInput:['Show new password','Hide new password'],teacherConfirmPasswordInput:['Show confirm password','Hide confirm password']}; var pair=labels[target]||['Show password','Hide password']; return show?pair[0]:pair[1]; }
  (scope||document).querySelectorAll('.password-toggle').forEach(function(btn){
    if(btn.dataset.boundToggle==='1') return; btn.dataset.boundToggle='1';
    btn.addEventListener('click',function(){
      var target=this.getAttribute('data-target'); var input=document.getElementById(target); if(!input) return;
      var show = input.type === 'password'; input.type = show ? 'text' : 'password';
      this.setAttribute('aria-label', teacherPasswordToggleLabel(target, !show));
      this.innerHTML = show ? '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a21.77 21.77 0 0 1 5.06-6.94"></path><path d="M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a21.8 21.8 0 0 1-3.17 4.51"></path><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>' : '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
    });
  });
}

window.addEventListener('popstate',function(){ var params=new URLSearchParams(window.location.search); document.getElementById('teacherSearch').value=params.get('search')||''; document.getElementById('teacherRole').value=params.get('role')||''; document.getElementById('teacherStatus').value=params.get('status')||''; fetchTeacherAccounts(parseInt(params.get('page'))||1); });

document.addEventListener('DOMContentLoaded',function(){
  initTeacherFilters(); bindEditButtons(); bindPasswordToggles(document);
  document.getElementById('openChangeEmailDialogBtn')?.addEventListener('click',function(){ if(document.getElementById('editTeacherId').value) openTeacherChangeEmailModal(); });
  document.getElementById('openChangePasswordDialogBtn')?.addEventListener('click',function(){ if(document.getElementById('editTeacherId').value) openTeacherChangePasswordModal(); });
  var newPw=document.getElementById('teacherNewPasswordInput'); var confirmPw=document.getElementById('teacherConfirmPasswordInput');
  if(newPw){ newPw.addEventListener('focus',function(){ document.getElementById('teacherPasswordRequirementsBox').style.display='block'; updateTeacherPasswordRequirements(); }); newPw.addEventListener('input',updateTeacherPasswordRequirements); }
  if(confirmPw){ confirmPw.addEventListener('input',updateTeacherPasswordRequirements); }
  document.getElementById('teacherSendEmailOtpBtn')?.addEventListener('click',function(){
    var id=document.getElementById('editTeacherId').value; if(!id) return;
    var btn=this; var verifyBtn=document.getElementById('teacherVerifyEmailBtn');
    hideTeacherEmailFeedback();
    var newEmail=(document.getElementById('teacherNewEmailInput').value||'').trim();
    if(!newEmail){ showTeacherEmailError('Please enter a new email address.'); return; }
    btn.disabled=true; btn.textContent='Sending...';
    var fd=new FormData(); fd.append('new_email', newEmail);
    fetch(TEACHERS_BASE_URL+'/'+id+'/email-change/initiate',{method:'POST',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},body:fd})
    .then(function(r){ return r.json().catch(function(){return {};}).then(function(d){ return {ok:r.ok,status:r.status,data:d}; }); })
    .then(function(res){
      if(res.ok&&res.data.success){
        verifyBtn.dataset.approvedEmail=newEmail;
        document.getElementById('teacherEmailOtpInput').value='';
        document.getElementById('teacherEmailFlowHelp').textContent='Verification code sent to the current registered email. Enter it below to save the new email immediately.';
        document.getElementById('teacherEmailStageInfo').textContent='After the code is verified, the new email will be saved immediately.';
        showTeacherEmailSuccess(res.data.message||'Verification code sent to the current registered email.');
        var otpInput=document.getElementById('teacherEmailOtpInput'); if(otpInput){ setTimeout(function(){ otpInput.focus(); },0); }
      } else {
        var msg=res.data.message||(res.data.errors&&(res.data.errors.new_email?.[0]||res.data.errors.otp?.[0]||Object.values(res.data.errors)[0]?.[0]))||'Failed to send verification code.';
        showTeacherEmailError(msg);
      }
    })
    .catch(function(){ showTeacherEmailError('An error occurred. Please try again.'); })
    .finally(function(){ btn.disabled=false; btn.textContent='Send'; });
  });

  document.getElementById('teacherVerifyEmailBtn')?.addEventListener('click',function(){
    var id=document.getElementById('editTeacherId').value; if(!id) return;
    var btn=this; hideTeacherEmailFeedback();
    var proposed=(document.getElementById('teacherNewEmailInput').value||'').trim();
    var approved=btn.dataset.approvedEmail||'';
    if(!proposed){ showTeacherEmailError('Please enter the new email address.'); return; }
    if(approved && proposed.toLowerCase()!==approved.toLowerCase()){
      btn.dataset.approvedEmail='';
      document.getElementById('teacherEmailOtpInput').value='';
      showTeacherEmailError('The new email address changed. Please request a new verification code.');
      document.getElementById('teacherEmailFlowHelp').textContent='New email changed. Click Send to request a new verification code for the current registered email address.';
      document.getElementById('teacherEmailStageInfo').textContent='Previous verification progress was invalidated because the new email address changed.';
      return;
    }
    var otp=(document.getElementById('teacherEmailOtpInput').value||'').trim();
    if(!/^\d{6}$/.test(otp)){ showTeacherEmailError('Please enter a valid 6-digit verification code.'); return; }
    btn.disabled=true; btn.textContent='Verifying...';
    var fd=new FormData(); fd.append('new_email', proposed); fd.append('otp', otp);
    fetch(TEACHERS_BASE_URL+'/'+id+'/email-change/confirm',{method:'POST',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},body:fd})
    .then(function(r){ return r.json().catch(function(){return {};}).then(function(d){ return {ok:r.ok,status:r.status,data:d}; }); })
    .then(function(res){
      if(res.ok && res.data.success && res.data.email){
        var updatedEmail=res.data.email;
        document.getElementById('editTeacherEmail').value=updatedEmail; document.getElementById('editTeacherEmailDisplay').value=updatedEmail; document.getElementById('teacherCurrentEmailField').value=updatedEmail;
        document.querySelectorAll('[data-modal-target="editTeacherModal"][data-id="'+id+'"]').forEach(function(b){ b.dataset.email=updatedEmail; });
        document.getElementById('teacherNewEmailInput').value=''; document.getElementById('teacherEmailOtpInput').value='';
        btn.dataset.approvedEmail='';
        document.getElementById('teacherEmailFlowHelp').textContent='Click Send to request a verification code for the current registered email address.';
        document.getElementById('teacherEmailStageInfo').textContent='Send a verification code to the current registered email, then enter it to save the new email address immediately.';
        showTeacherEmailSuccess(res.data.message||'Email updated successfully.');
        var page=(new URL(window.location)).searchParams.get('page')||1; fetchTeacherAccounts(parseInt(page,10));
      } else {
        var msg=res.data.message||(res.data.errors&&(res.data.errors.otp?.[0]||res.data.errors.new_email?.[0]||Object.values(res.data.errors)[0]?.[0]))||'Verification failed. Please try again.';
        showTeacherEmailError(msg);
        if(res.data && res.data.errors && res.data.errors.new_email){ btn.dataset.approvedEmail=''; }
      }
    })
    .catch(function(){ showTeacherEmailError('An error occurred. Please try again.'); })
    .finally(function(){ btn.disabled=false; btn.textContent='Verify & Update Email'; });
  });

  document.getElementById('teacherNewEmailInput')?.addEventListener('input',function(){
    var verifyBtn=document.getElementById('teacherVerifyEmailBtn'); if(!verifyBtn) return;
    var approved=verifyBtn.dataset.approvedEmail||''; var current=(this.value||'').trim();
    if(approved && current.toLowerCase()!==approved.toLowerCase()){
      verifyBtn.dataset.approvedEmail='';
      document.getElementById('teacherEmailOtpInput').value='';
      document.getElementById('teacherEmailFlowHelp').textContent='New email changed. Click Send to request a new verification code for the current registered email address.';
      document.getElementById('teacherEmailStageInfo').textContent='Previous verification progress was invalidated because the new email address changed.';
    }
  });
  document.getElementById('teacherSavePasswordBtn')?.addEventListener('click',function(){
    var id=document.getElementById('editTeacherId').value; if(!id) return; var btn=this; hideTeacherPasswordError(); btn.disabled=true; btn.textContent='Saving...';
    var fd=new FormData(); fd.append('current_password', document.getElementById('teacherCurrentPasswordInput').value||''); fd.append('password', document.getElementById('teacherNewPasswordInput').value||''); fd.append('password_confirmation', document.getElementById('teacherConfirmPasswordInput').value||'');
    fetch(TEACHERS_BASE_URL+'/'+id+'/change-password',{method:'POST',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},body:fd})
    .then(function(r){ return r.json().catch(function(){return {};}).then(function(d){ return {ok:r.ok,status:r.status,data:d}; }); })
    .then(function(res){
      if(res.ok&&res.data.success){
        document.getElementById('teacherCurrentPasswordInput').value=''; document.getElementById('teacherNewPasswordInput').value=''; document.getElementById('teacherConfirmPasswordInput').value='';
        resetTeacherPasswordToggles(); updateTeacherPasswordRequirements();
        showTeacherModalNotice('teacherPasswordNotification','success',res.data.message||'Password updated successfully.');
      }
      else { var msg=res.data.message||(res.data.errors&&(res.data.errors.current_password?.[0]||res.data.errors.password?.[0]||Object.values(res.data.errors)[0]?.[0]))||'Password update failed.'; showTeacherPasswordError(msg); }
    }).catch(function(){ showTeacherPasswordError('An error occurred. Please try again.'); }).finally(function(){ btn.disabled=false; btn.textContent='Save Password'; });
  });
  ['viewAccountModal','reactivateConfirmModal','archiveConfirmModal','restrictConfirmModal','unrestrictConfirmModal','archivedAccountsModal','unarchiveConfirmModal','deleteArchivedConfirmModal','editTeacherModal','teacherChangeEmailModal','teacherChangePasswordModal'].forEach(function(id){
    var el=document.getElementById(id); if(!el) return; el.addEventListener('click',function(ev){ if(ev.target!==this) return; if(id==='viewAccountModal') closeViewAccountModal(); else if(id==='reactivateConfirmModal') closeReactivateConfirmModal(); else if(id==='archiveConfirmModal') closeArchiveConfirmModal(); else if(id==='restrictConfirmModal') closeRestrictConfirmModal(); else if(id==='unrestrictConfirmModal') closeUnrestrictConfirmModal(); else if(id==='archivedAccountsModal') closeArchivedAccountsModal(); else if(id==='unarchiveConfirmModal') closeUnarchiveConfirmModal(); else if(id==='deleteArchivedConfirmModal') closeDeleteArchivedConfirmModal(); else if(id==='editTeacherModal') closeEditTeacherModal(); else if(id==='teacherChangeEmailModal') closeTeacherChangeEmailModal(); else if(id==='teacherChangePasswordModal') closeTeacherChangePasswordModal(); });
  });
  var archivedSearchInput=document.getElementById('archivedSearch'); if(archivedSearchInput){ archivedSearchInput.addEventListener('input',function(){ clearTimeout(archivedDebounceTimer); archivedDebounceTimer=setTimeout(function(){ fetchArchivedAccounts(1); },300); }); }
  document.addEventListener('keydown',function(event){ if(event.key!=='Escape') return; if(document.getElementById('teacherChangePasswordModal')?.classList.contains('show')){closeTeacherChangePasswordModal(); return;} if(document.getElementById('teacherChangeEmailModal')?.classList.contains('show')){closeTeacherChangeEmailModal(); return;} if(document.getElementById('archiveConfirmModal')?.classList.contains('show')) closeArchiveConfirmModal(); if(document.getElementById('reactivateConfirmModal')?.classList.contains('show')) closeReactivateConfirmModal(); if(document.getElementById('restrictConfirmModal')?.classList.contains('show')) closeRestrictConfirmModal(); if(document.getElementById('unrestrictConfirmModal')?.classList.contains('show')) closeUnrestrictConfirmModal(); if(document.getElementById('archivedAccountsModal')?.classList.contains('show')) closeArchivedAccountsModal(); if(document.getElementById('unarchiveConfirmModal')?.classList.contains('show')) closeUnarchiveConfirmModal(); if(document.getElementById('deleteArchivedConfirmModal')?.classList.contains('show')) closeDeleteArchivedConfirmModal(); if(document.getElementById('editTeacherModal')?.classList.contains('show')) closeEditTeacherModal(); });
  var archivedTbody=document.getElementById('archivedAccountsTableBody'); if(archivedTbody){ archivedTbody.addEventListener('click',function(e){ var b=e.target.closest('button.unarchive'); if(b){ e.preventDefault(); openUnarchiveConfirmModal(b.getAttribute('data-id'), b.getAttribute('data-name')); return; } var d=e.target.closest('button.delete-archived'); if(d){ e.preventDefault(); openDeleteArchivedConfirmModal(d.getAttribute('data-id'), d.getAttribute('data-name')); } }); }
});
</script>
@endpush
