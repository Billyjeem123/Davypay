@extends('dashboard.layout.sms')

@section('content')

    <div class="wrapper">
        <div class="content-page sms-page">
            <div class="content">
                <div class="container-fluid">

                    <!-- Header -->
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <div class="page-title-box">
                                <h1 class="page-title text-dark">User Management Dashboard</h1>
                            </div>
                        </div>
                        <div class="col-md-6 py-2 text-end">
                            <a href="#" class="btn btn-sm btn-primary mb-2"><i class="uil-plus"></i> Add New User</a>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card stats-card border-left-primary">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Users</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalUsers) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card border-left-success">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active Users</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($activeUsers) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card border-left-danger">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Blocked Users</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($blockedUsers) }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="card stats-card border-left-danger">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Restricted Users</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($restrictedUsers) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card border-left-warning">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">New Signups (7 days)</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($recentSignups) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- Users Table -->
                    <div class="row mb-3 bg-white">
                        <div class="table-responsive">
                            <table id="sms-datatable" class="table table-hover dt-responsive nowrap w-100 table-centered mb-0">
                                <thead class="table-light">
                                <tr>
                                    <th class="fw-bold text-dark border-0 py-3">User ID</th>
                                    <th class="fw-bold text-dark border-0 py-3">Name</th>
                                    <th class="fw-bold text-dark border-0 py-3">Email</th>
                                    <th class="fw-bold text-dark border-0 py-3">Phone</th>
                                    <th class="fw-bold text-dark border-0 py-3">Status</th>
                                    <th class="fw-bold text-dark border-0 py-3">Registered</th>
                                    <th class="fw-bold text-dark border-0 py-3 text-end">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($users as $user)
                                    <tr class="border-bottom">
                                        <td class="align-middle py-3">
                                            <span class="fw-bold text-primary font-monospace">#USR{{ str_pad($user->id, 3, '0', STR_PAD_LEFT) }}</span>
                                        </td>
                                        <td class="align-middle py-3">
                                            <div class="d-flex align-items-center">
                                                <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                    <i class="fas fa-user text-primary"></i>
                                                </div>
                                                <div>
                                                    <span class="fw-bold text-dark">{{ $user->first_name }} {{ $user->last_name }}</span>
                                                    <br>
                                                    <small class="text-muted">ID: {{ $user->id }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="align-middle py-3">
                                            <span class="text-dark">{{ $user->email }}</span>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-envelope me-1"></i>
                                                Email verified
                                            </small>
                                        </td>
                                        <td class="align-middle py-3">
                                            <span class="fw-medium text-dark font-monospace">{{ $user->phone ?? 'N/A' }}</span>
                                            @if($user->phone)
                                                <br>
                                                <small class="text-muted">
                                                    <i class="fas fa-phone me-1"></i>
                                                    Mobile
                                                </small>
                                            @endif
                                        </td>
                                        <td class="align-middle py-3">
                                            @if($user->is_ban)
                                                <span class="badge bg-danger px-3 py-2 fw-bold">
                                        <i class="fas fa-ban me-1"></i>
                                        Banned
                                    </span>
                                            @elseif($user->is_account_restricted)
                                                <span class="badge bg-warning text-dark px-3 py-2 fw-bold">
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        Restricted
                                    </span>
                                            @else
                                                <span class="badge bg-success px-3 py-2 fw-bold">
                                        <i class="fas fa-check-circle me-1"></i>
                                        Active
                                    </span>
                                            @endif
                                        </td>
                                        <td class="align-middle py-3">
                                            <span class="fw-bold text-dark">{{ $user->created_at->format('d/m/Y') }}</span>
                                            <br>
                                            <small class="text-muted">{{ $user->created_at->diffForHumans() }}</small>
                                        </td>
                                        <td class="align-middle py-3 text-end">
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('user.info', $user->id) }}"
                                                   class="btn btn-primary btn-sm fw-bold px-3">
                                                    <i class="fas fa-eye me-1"></i>
                                                    View Details
                                                </a>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle dropdown-toggle-split"
                                                            data-bs-toggle="dropdown" aria-expanded="false">
                                                        <span class="visually-hidden">Toggle Dropdown</span>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <a class="dropdown-item" href="#">
                                                                <i class="fas fa-edit me-2 text-warning"></i>
                                                                Edit User
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="#">
                                                                <i class="fas fa-key me-2 text-info"></i>
                                                                Reset Password
                                                            </a>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        @if($user->is_ban)
                                                            <li>
                                                                <a class="dropdown-item text-success" href="#">
                                                                    <i class="fas fa-unlock me-2"></i>
                                                                    Unban User
                                                                </a>
                                                            </li>
                                                        @else
                                                            <li>
                                                                <a class="dropdown-item text-danger" href="#">
                                                                    <i class="fas fa-ban me-2"></i>
                                                                    Ban User
                                                                </a>
                                                            </li>
                                                        @endif
                                                    </ul>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="fas fa-users fa-3x mb-3 text-muted opacity-50"></i>
                                                <h5 class="fw-bold text-muted">No Users Found</h5>
                                                <p class="mb-0">There are no users registered yet.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>


                </div>
            </div>
        </div>
    </div>

    <style>
        .stats-card {
            transition: transform 0.2s;
        }

        .stats-card:hover {
            transform: translateY(-2px);
        }

        .border-left-primary {
            border-left: 4px solid #007bff !important;
        }

        .border-left-success {
            border-left: 4px solid #28a745 !important;
        }

        .border-left-warning {
            border-left: 4px solid #ffc107 !important;
        }

        .border-left-danger {
            border-left: 4px solid #dc3545 !important;
        }

        .text-xs {
            font-size: 0.75rem;
        }

        .text-gray-800 {
            color: #5a5c69 !important;
        }
    </style>

@endsection
