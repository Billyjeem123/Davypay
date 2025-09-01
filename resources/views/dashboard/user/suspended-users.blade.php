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
                                <h1 class="page-title text-dark">Suspended Users</h1>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Card -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card stats-card border-left-danger">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Total Suspended Users</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $count }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Suspended Users Table -->
                    <div class="row mb-3 bg-white">
                        <div class="table-responsive">
                            <table id="sms-datatable" class="table table-hover dt-responsive nowrap w-100 table-centered mb-0">
                                <thead class="table-light">
                                <tr>
                                    <th class="fw-bold text-dark border-0 py-3">User ID</th>
                                    <th class="fw-bold text-dark border-0 py-3">Name</th>
                                    <th class="fw-bold text-dark border-0 py-3">Email</th>
                                    <th class="fw-bold text-dark border-0 py-3">Phone</th>
                                    <th class="fw-bold text-dark border-0 py-3">Suspension Date</th>
                                    <th class="fw-bold text-dark border-0 py-3">Reason</th>
                                    <th class="fw-bold text-dark border-0 py-3">Status</th>
                                    <th class="fw-bold text-dark border-0 py-3 text-end">Action</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($users as $user)
                                    <tr class="border-bottom">
                                        <td class="align-middle py-3">
                                            <span class="fw-bold text-primary font-monospace">#USR{{ str_pad($user->id, 3, '0', STR_PAD_LEFT) }}</span>
                                        </td>
                                        <td class="align-middle py-3">
                                            <span class="fw-bold text-dark">{{ $user->first_name }} {{ $user->last_name }}</span>
                                        </td>
                                        <td class="align-middle py-3">
                                            <span class="text-dark fw-medium">{{ $user->email }}</span>
                                        </td>
                                        <td class="align-middle py-3">
                                            @if($user->phone)
                                                <span class="fw-medium text-dark font-monospace">{{ $user->phone }}</span>
                                            @else
                                                <span class="badge bg-light text-muted px-2 py-1">
                            <i class="fas fa-phone-slash me-1"></i> N/A
                        </span>
                                            @endif
                                        </td>
                                        <td class="align-middle py-3">
                                            <span class="text-dark">{{ $user->restriction_date ?? 'N/A' }}</span>
                                        </td>
                                        <td class="align-middle py-3">
                                            <span class="text-muted">{{ $user->reason_restriction ?? 'N/A' }}</span>
                                        </td>
                                        <td class="align-middle py-3">
                                            @if($user->is_ban)
                                                <span class="badge bg-danger px-3 py-2 fw-bold">
                            <i class="fas fa-ban me-1"></i> Banned
                        </span>
                                            @elseif($user->is_account_restricted)
                                                <span class="badge bg-warning px-3 py-2 fw-bold">
                            <i class="fas fa-exclamation-triangle me-1"></i> Restricted
                        </span>
                                            @else
                                                <span class="badge bg-success px-3 py-2 fw-bold">
                            <i class="fas fa-check-circle me-1"></i> Active
                        </span>
                                            @endif
                                        </td>
                                        <td class="align-middle py-3 text-end">
                                            <a href="{{ route('user.info', $user->id) }}" class="btn btn-primary btn-sm fw-bold px-3">
                                                <i class="fas fa-eye me-1"></i> View More
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
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
