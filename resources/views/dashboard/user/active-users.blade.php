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
                                <h1 class="page-title text-dark">Active Users Dashboard</h1>
                            </div>
                        </div>

                    </div>

                    <!-- Stats -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card stats-card border-left-success">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Active Users</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $count }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Active Users Table -->
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
                                    <th class="fw-bold text-dark border-0 py-3 text-end">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($users as $user)
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
                                                    <small class="text-muted">User ID: {{ $user->id }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="align-middle py-3">
                                            <div>
                                                <span class="text-dark fw-medium">{{ $user->email }}</span>
                                                <br>
                                                <small class="text-muted">
                                                    <i class="fas fa-envelope me-1"></i>
                                                    Primary contact
                                                </small>
                                            </div>
                                        </td>
                                        <td class="align-middle py-3">
                                            @if($user->phone)
                                                <div>
                                                    <span class="fw-medium text-dark font-monospace">{{ $user->phone }}</span>
                                                    <br>
                                                    <small class="text-muted">
                                                        <i class="fas fa-phone me-1"></i>
                                                        Mobile
                                                    </small>
                                                </div>
                                            @else
                                                <div class="text-center">
                                        <span class="badge bg-light text-muted px-2 py-1">
                                            <i class="fas fa-phone-slash me-1"></i>
                                            N/A
                                        </span>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="align-middle py-3">
                                <span class="badge bg-success px-3 py-2 fw-bold">
                                    <i class="fas fa-check-circle me-1"></i>
                                    Active
                                </span>
                                        </td>
                                        <td class="align-middle py-3 text-end">
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('user.info', $user->id) }}"
                                                   class="btn btn-primary btn-sm fw-bold px-3">
                                                    <i class="fas fa-eye me-1"></i>
                                                    View Details
                                                </a>

                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- User Detail Modal -->
                    <div class="modal fade" id="userDetailModal" tabindex="-1" role="dialog"
                         aria-labelledby="userDetailModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h4 class="modal-title text-dark">User Details</h4>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>User ID:</strong> #USR001</p>
                                            <p><strong>Name:</strong> John Doe</p>
                                            <p><strong>Email:</strong> john@example.com</p>
                                            <p><strong>Phone:</strong> 08123456789</p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Status:</strong> <span class="badge bg-success">Active</span></p>
                                            <p><strong>Role:</strong> Customer</p>
                                            <p><strong>Date Registered:</strong> 10/07/2025</p>
                                            <p><strong>Last Login:</strong> 13/07/2025 09:45 AM</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="button" class="btn btn-danger">Suspend User</button>
                                </div>
                            </div>
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
        .border-left-success {
            border-left: 4px solid #28a745 !important;
        }
        .text-xs {
            font-size: 0.75rem;
        }
        .text-gray-800 {
            color: #5a5c69 !important;
        }
    </style>

@endsection
