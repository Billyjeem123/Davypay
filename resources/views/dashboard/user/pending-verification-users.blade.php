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
                                <h1 class="page-title text-dark">Users Pending Email Verification</h1>
                            </div>
                        </div>
                    </div>

                    <!-- Summary -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card stats-card border-left-warning">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Total Unverified Users
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">18</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Unverified Users Table -->
                    <div class="row mb-3 bg-white">
                        <div class="table-responsive">
                            <table id="sms-datatable" class="table table-hover dt-responsive nowrap w-100 table-centered mb-0">
                                <thead>
                                <tr>
                                    <th>User ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Registered On</th>
                                    <th>Email Verified</th>
                                    <th>Action</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr>
                                    <td>#USR104</td>
                                    <td>Taiwo Adams</td>
                                    <td>taiwo@example.com</td>
                                    <td>08023456789</td>
                                    <td>10/07/2025</td>
                                    <td><span class="badge bg-warning">Pending</span></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#userDetailModal"><i class="uil-eye"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>#USR105</td>
                                    <td>Joy Okon</td>
                                    <td>joyo@example.com</td>
                                    <td>08111122233</td>
                                    <td>09/07/2025</td>
                                    <td><span class="badge bg-warning">Pending</span></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#userDetailModal"><i class="uil-eye"></i></button>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Modal -->
                    <div class="modal fade" id="userDetailModal" tabindex="-1" role="dialog" aria-labelledby="userDetailModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h4 class="modal-title text-dark" id="userDetailModalLabel">User Details</h4>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>User ID:</strong> #USR104</p>
                                            <p><strong>Name:</strong> Taiwo Adams</p>
                                            <p><strong>Email:</strong> taiwo@example.com</p>
                                            <p><strong>Phone:</strong> 08023456789</p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Status:</strong> <span class="badge bg-warning">Email Not Verified</span></p>
                                            <p><strong>Registered On:</strong> 10/07/2025</p>
                                            <p><strong>Verification Link Sent:</strong> Yes</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="button" class="btn btn-info">Resend Verification Email</button>
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

        .border-left-warning {
            border-left: 4px solid #ffc107 !important;
        }

        .text-xs {
            font-size: 0.75rem;
        }

        .text-gray-800 {
            color: #5a5c69 !important;
        }
    </style>

@endsection
