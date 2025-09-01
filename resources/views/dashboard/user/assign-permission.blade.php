@extends('dashboard.layout.sms')

@section('content')

    <div class="wrapper">
        <div class="content-page sms-page">
            <div class="content">
                <div class="container-fluid">

                    <!-- Header -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="page-title-box">
                                <h1 class="page-title text-dark">User Permission Management</h1>
                                <p class="text-muted">Manage admin users and their permissions</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-end">
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                    <i class="fas fa-plus me-2"></i>Add User
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Cards -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-primary text-white mb-4 stats-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <div class="text-xs font-weight-bold text-uppercase mb-1">Total Users</div>
                                            <div class="h5 mb-0">{{ $totalUsers ?? 0 }}</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-users fa-2x text-white-50"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-success text-white mb-4 stats-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <div class="text-xs font-weight-bold text-uppercase mb-1">Active Users</div>
                                            <div class="h5 mb-0">{{ $activeUsers ?? 0 }}</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-user-check fa-2x text-white-50"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-warning text-white mb-4 stats-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <div class="text-xs font-weight-bold text-uppercase mb-1">Pending Users</div>
                                            <div class="h5 mb-0">{{ $pendingUsers ?? 0 }}</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-user-clock fa-2x text-white-50"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-info text-white mb-4 stats-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <div class="text-xs font-weight-bold text-uppercase mb-1">Total Roles</div>
                                            <div class="h5 mb-0">{{ $totalRoles ?? 0 }}</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-shield-alt fa-2x text-white-50"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Users Table -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Admin Users</h6>
                            <div class="d-flex align-items-center">
                                <input type="text" class="form-control form-control-sm me-2" placeholder="Search users..." id="searchUsers" style="width: 200px;">
                                <select class="form-select form-select-sm" id="roleFilter" style="width: 150px;">
                                    <option value="">All Roles</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="sms-responsive">
                                <table id="sms-datatable" class="table table-hover dt-responsive nowrap w-100 table-centered mb-0">
                                    <thead class="table-light">
                                    <tr>
                                        <th class="text-start">User</th>
                                        <th class="text-start">Role</th>
                                        <th class="text-start">Status</th>
                                        <th class="text-start">Permissions</th>
                                        <th class="text-start">Last Login</th>
                                        <th class="text-start">Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($admins as $admin)
                                        <tr>
                                            <td class="text-start">
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm me-3">
                                                        <div class="avatar-title bg-primary rounded-circle">
                                                            {{ strtoupper(substr($admin->name, 0, 1)) }}
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold">{{ $admin->name }}</div>
                                                        <div class="text-muted small">{{ $admin->email }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-start">
                                                <span class="badge bg-primary">{{ ucfirst($admin->role ?? 'Admin') }}</span>
                                            </td>
                                            <td class="text-start">
                                                <span class="badge bg-{{ $admin->status === 'active' ? 'success' : 'warning' }}">
                                                    {{ ucfirst($admin->status ?? 'active') }}
                                                </span>
                                            </td>
                                            <td class="text-start">
                                                <div class="d-flex flex-wrap gap-1">
                                                    @if($admin->permissions)
                                                        @foreach(json_decode($admin->permissions, true) as $permission)
                                                            <span class="badge bg-light text-dark">{{ $permission }}</span>
                                                        @endforeach
                                                    @else
                                                        <span class="text-muted">No permissions</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="text-start">
                                                <span class="text-muted">{{ $admin->last_login_at ? $admin->last_login_at->format('M d, Y H:i') : 'Never' }}</span>
                                            </td>
                                            <td class="text-start">
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-outline-primary"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#assignPermissionModal"
                                                            onclick="editUser({{ $admin->id }}, '{{ $admin->name }}', '{{ $admin->email }}', '{{ $admin->role }}', '{{ $admin->permissions }}')">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger"
                                                            onclick="deleteUser({{ $admin->id }}, '{{ $admin->name }}')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Add User Modal -->
                    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header bg-primary text-white">
                                    <h5 class="modal-title" id="addUserModalLabel">
                                        <i class="fas fa-user-plus me-2"></i>Add New User
                                    </h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form action="{{ route('admin.users.store') }}" method="POST">
                                        @csrf
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Name <span class="text-danger">*</span></label>
                                            <input type="text" name="first_name" class="form-control" placeholder="Enter full name" required>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Email <span class="text-danger">*</span></label>
                                            <input type="email" name="email" class="form-control" placeholder="Enter email address" required>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Role <span class="text-danger">*</span></label>
                                            <select name="role" class="form-select" required>
                                                <option value="">Select Role</option>
                                                @foreach($roles as $role)
                                                    <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
                                                @endforeach
                                            </select>
                                        </div>


                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-primary flex-fill">
                                                <i class="fas fa-save me-2"></i>Create User
                                            </button>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Assign Permission Modal -->
                    <div class="modal fade" id="assignPermissionModal" tabindex="-1" aria-labelledby="assignPermissionModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header bg-success text-white">
                                    <h5 class="modal-title" id="assignPermissionModalLabel">
                                        <i class="fas fa-shield-alt me-2"></i>Manage User Permissions
                                    </h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form id="permissionForm" action="{{ route('admin.users.permissions.update') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="user_id" id="editUserId">

                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold">User</label>
                                                <input type="text" id="editUserName" class="form-control" readonly>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold">Email</label>
                                                <input type="email" id="editUserEmail" class="form-control" readonly>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Role</label>
                                            <select name="role" id="editUserRole" class="form-select">
                                                @foreach($roles as $role)
                                                    <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Permissions</label>

                                            <div class="row">
                                                <!-- Dashboard & Settings Column -->
                                                <div class="col-md-6">
                                                    <div class="border rounded p-3 mb-3">
                                                        <h6 class="text-primary mb-2"><i class="uil-home-alt me-1"></i>Dashboard & Settings</h6>
                                                        <div class="form-check mb-1">
                                                            <input class="form-check-input" type="checkbox" id="perm_view_dashboard" name="permissions[]" value="view dashboard">
                                                            <label class="form-check-label" for="perm_view_dashboard">View Dashboard</label>
                                                        </div>
                                                        <div class="form-check mb-1">
                                                            <input class="form-check-input" type="checkbox" id="perm_manage_settings" name="permissions[]" value="manage settings">
                                                            <label class="form-check-label" for="perm_manage_settings">Manage Settings</label>
                                                        </div>
                                                        <div class="form-check mb-1">
                                                            <input class="form-check-input" type="checkbox" id="perm_view_notifications" name="permissions[]" value="view notifications">
                                                            <label class="form-check-label" for="perm_view_notifications">View Notifications</label>
                                                        </div>
                                                    </div>

                                                    <div class="border rounded p-3 mb-3">
                                                        <h6 class="text-success mb-2"><i class="uil-users-alt me-1"></i>User Management</h6>
                                                        <div class="form-check mb-1">
                                                            <input class="form-check-input" type="checkbox" id="perm_view_all_users" name="permissions[]" value="view all users">
                                                            <label class="form-check-label" for="perm_view_all_users">View All Users</label>
                                                        </div>
                                                        <div class="form-check mb-1">
                                                            <input class="form-check-input" type="checkbox" id="perm_view_active_users" name="permissions[]" value="view active users">
                                                            <label class="form-check-label" for="perm_view_active_users">View Active Users</label>
                                                        </div>
                                                        <div class="form-check mb-1">
                                                            <input class="form-check-input" type="checkbox" id="perm_view_suspended_users" name="permissions[]" value="view suspended users">
                                                            <label class="form-check-label" for="perm_view_suspended_users">View Suspended Users</label>
                                                        </div>
                                                        <div class="form-check mb-1">
                                                            <input class="form-check-input" type="checkbox" id="perm_manage_kyc" name="permissions[]" value="manage kyc">
                                                            <label class="form-check-label" for="perm_manage_kyc">Manage KYC</label>
                                                        </div>
                                                        <div class="form-check mb-1">
                                                            <input class="form-check-input" type="checkbox" id="perm_manage_user_roles" name="permissions[]" value="manage user roles">
                                                            <label class="form-check-label" for="perm_manage_user_roles">Manage User Roles</label>
                                                        </div>
                                                        <div class="form-check mb-1">
                                                            <input class="form-check-input" type="checkbox" id="perm_manage_user_permissions" name="permissions[]" value="manage user permissions">
                                                            <label class="form-check-label" for="perm_manage_user_permissions">Manage User Permissions</label>
                                                        </div>
                                                        <div class="form-check mb-1">
                                                            <input class="form-check-input" type="checkbox" id="perm_view_user_activity_logs" name="permissions[]" value="view user activity logs">
                                                            <label class="form-check-label" for="perm_view_user_activity_logs">View User Activity Logs</label>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Transactions & Financial Column -->
                                                <div class="col-md-6">
                                                    <div class="border rounded p-3 mb-3">
                                                        <h6 class="text-info mb-2"><i class="uil-exchange-alt me-1"></i>Transaction Management</h6>
                                                        <div class="form-check mb-1">
                                                            <input class="form-check-input" type="checkbox" id="perm_view_user_transactions" name="permissions[]" value="view user transactions">
                                                            <label class="form-check-label" for="perm_view_user_transactions">View User Transactions</label>
                                                        </div>
                                                        <div class="form-check mb-1">
                                                            <input class="form-check-input" type="checkbox" id="perm_view_all_transactions" name="permissions[]" value="view all transactions">
                                                            <label class="form-check-label" for="perm_view_all_transactions">View All Transactions</label>
                                                        </div>
                                                        <div class="form-check mb-1">
                                                            <input class="form-check-input" type="checkbox" id="perm_view_pending_transactions" name="permissions[]" value="view pending transactions">
                                                            <label class="form-check-label" for="perm_view_pending_transactions">View Pending Transactions</label>
                                                        </div>
                                                        <div class="form-check mb-1">
                                                            <input class="form-check-input" type="checkbox" id="perm_view_failed_transactions" name="permissions[]" value="view failed transactions">
                                                            <label class="form-check-label" for="perm_view_failed_transactions">View Failed Transactions</label>
                                                        </div>
                                                        <div class="form-check mb-1">
                                                            <input class="form-check-input" type="checkbox" id="perm_view_successful_transactions" name="permissions[]" value="view successful transactions">
                                                            <label class="form-check-label" for="perm_view_successful_transactions">View Successful Transactions</label>
                                                        </div>
                                                        <div class="form-check mb-1">
                                                            <input class="form-check-input" type="checkbox" id="perm_view_transaction_reports" name="permissions[]" value="view transaction reports">
                                                            <label class="form-check-label" for="perm_view_transaction_reports">View Transaction Reports</label>
                                                        </div>
                                                    </div>

                                                    <div class="border rounded p-3 mb-3">
                                                        <h6 class="text-warning mb-2"><i class="uil-wallet me-1"></i>Wallet & Security</h6>
                                                        <div class="form-check mb-1">
                                                            <input class="form-check-input" type="checkbox" id="perm_view_wallet_overview" name="permissions[]" value="view wallet overview">
                                                            <label class="form-check-label" for="perm_view_wallet_overview">View Wallet Overview</label>
                                                        </div>
                                                        <div class="form-check mb-1">
                                                            <input class="form-check-input" type="checkbox" id="perm_view_wallet_funding" name="permissions[]" value="view wallet funding">
                                                            <label class="form-check-label" for="perm_view_wallet_funding">View Wallet Funding</label>
                                                        </div>
                                                        <div class="form-check mb-1">
                                                            <input class="form-check-input" type="checkbox" id="perm_view_fraudulent_transaction_reports" name="permissions[]" value="view fraudulent transaction reports">
                                                            <label class="form-check-label" for="perm_view_fraudulent_transaction_reports">View Fraudulent Reports</label>
                                                        </div>
                                                    </div>

                                                    <div class="border rounded p-3 mb-3">
                                                        <h6 class="text-secondary mb-2"><i class="uil-chart-line me-1"></i>Reports & Communication</h6>
                                                        <div class="form-check mb-1">
                                                            <input class="form-check-input" type="checkbox" id="perm_view_transaction_analytics" name="permissions[]" value="view transaction analytics">
                                                            <label class="form-check-label" for="perm_view_transaction_analytics">View Transaction Analytics</label>
                                                        </div>
                                                        <div class="form-check mb-1">
                                                            <input class="form-check-input" type="checkbox" id="perm_send_announcements" name="permissions[]" value="send announcements">
                                                            <label class="form-check-label" for="perm_send_announcements">Send Announcements</label>
                                                        </div>
                                                        <div class="form-check mb-1">
                                                            <input class="form-check-input" type="checkbox" id="perm_view_all_announcements" name="permissions[]" value="view all announcements">
                                                            <label class="form-check-label" for="perm_view_all_announcements">View All Announcements</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>

                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-success flex-fill">
                                                <i class="fas fa-save me-2"></i>Update Permissions
                                            </button>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script>
        function editUser(id, name, email, role, permissions) {
            document.getElementById('editUserId').value = id;
            document.getElementById('editUserName').value = name;
            document.getElementById('editUserEmail').value = email;
            document.getElementById('editUserRole').value = role;

            // Clear all checkboxes first
            const checkboxes = document.querySelectorAll('input[name="permissions[]"]');
            checkboxes.forEach(checkbox => checkbox.checked = false);

            // Parse and check relevant permissions
            if (permissions && permissions !== 'null') {
                try {
                    const userPermissions = JSON.parse(permissions);
                    userPermissions.forEach(permission => {
                        const checkbox = document.querySelector(`input[value="${permission}"]`);
                        if (checkbox) checkbox.checked = true;
                    });
                } catch (e) {
                    console.error('Error parsing permissions:', e);
                }
            }
        }

        function deleteUser(id, name) {
            if (confirm(`Are you sure you want to delete user "${name}"?`)) {
                // Create form and submit
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/admin/users/${id}`;

                const csrfToken = document.querySelector('meta[name="csrf-token"]');
                if (csrfToken) {
                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = '_token';
                    csrfInput.value = csrfToken.getAttribute('content');
                    form.appendChild(csrfInput);
                }

                const methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = 'DELETE';
                form.appendChild(methodInput);

                document.body.appendChild(form);
                form.submit();
            }
        }

        // Search functionality
        document.getElementById('searchUsers').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#usersTable tbody tr');

            rows.forEach(row => {
                const name = row.cells[0].textContent.toLowerCase();
                const email = row.cells[0].textContent.toLowerCase();

                if (name.includes(searchTerm) || email.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Role filter functionality
        document.getElementById('roleFilter').addEventListener('change', function() {
            const selectedRole = this.value.toLowerCase();
            const rows = document.querySelectorAll('#usersTable tbody tr');

            rows.forEach(row => {
                const role = row.cells[1].textContent.toLowerCase();

                if (selectedRole === '' || role.includes(selectedRole)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    </script>

    <style>
        .stats-card {
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-2px);
        }
        .avatar-sm {
            width: 2.5rem;
            height: 2.5rem;
        }
        .avatar-title {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            font-weight: 600;
        }
        .text-xs {
            font-size: 0.75rem;
        }
        .card {
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        .table th {
            border-top: none;
        }
        .btn-group .btn {
            border-radius: 0.375rem;
            margin-right: 0.25rem;
        }
        .badge {
            font-size: 0.75rem;
        }
    </style>

@endsection
