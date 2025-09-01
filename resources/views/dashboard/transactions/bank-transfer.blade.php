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
                                <h1 class="page-title text-dark">Bank Transfer Report</h1>
                            </div>
                        </div>
                        <div class="col-md-6 py-2 text-end">
                            <a href="#" class="btn btn-sm btn-success mb-2"><i class="uil-export"></i> Export Report</a>
                            <a href="#" class="btn btn-sm btn-primary mb-2"><i class="uil-filter"></i> Filter</a>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card stats-card border-left-success">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Transactions</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">2,847</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card border-left-info">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Amount</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">₦12,450,000</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card border-left-warning">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">45</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card border-left-danger">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Failed</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">12</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bank Transfer Transactions Table -->
                    <div class="row mb-3 bg-white">
                        <div class="table-responsive">
                            <table id="notification-datatable" class="table table-hover dt-responsive nowrap w-100 table-centered mb-0">
                                <thead>
                                <tr>
{{--                                    <th>Transaction ID</th>--}}
                                    <th>User</th>
                                    <th>Provider</th>
                                    <th>Amount</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr>
                                    <td>#TXN001</td>
                                    <td>
                                        <div>
                                            <strong>John Doe</strong><br>
                                            <small class="text-muted">john@example.com</small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">Flutterwave</span>
                                    </td>
                                    <td>
                                        <strong>₦25,000.00</strong><br>
                                        <small class="text-muted">Before: ₦50,000.00</small>
                                    </td>
                                    <td>bank_transfer</td>
                                    <td><span class="badge bg-success">completed</span></td>
                                    <td>13/07/2025 09:45 AM</td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#transactionDetailModal"><i class="uil-eye"></i></button>
                                        <button class="btn btn-sm btn-info"><i class="uil-download-alt"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>#TXN002</td>
                                    <td>
                                        <div>
                                            <strong>Jane Smith</strong><br>
                                            <small class="text-muted">jane@example.com</small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">Paystack</span>
                                    </td>
                                    <td>
                                        <strong>₦15,500.00</strong><br>
                                        <small class="text-muted">Before: ₦30,000.00</small>
                                    </td>
                                    <td>bank_transfer</td>
                                    <td><span class="badge bg-warning">pending</span></td>
                                    <td>13/07/2025 08:30 AM</td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#transactionDetailModal"><i class="uil-eye"></i></button>
                                        <button class="btn btn-sm btn-info"><i class="uil-download-alt"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>#TXN003</td>
                                    <td>
                                        <div>
                                            <strong>Mike Johnson</strong><br>
                                            <small class="text-muted">mike@example.com</small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-dark">Monnify</span>
                                    </td>
                                    <td>
                                        <strong>₦75,000.00</strong><br>
                                        <small class="text-muted">Before: ₦100,000.00</small>
                                    </td>
                                    <td>bank_transfer</td>
                                    <td><span class="badge bg-danger">failed</span></td>
                                    <td>12/07/2025 06:15 PM</td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#transactionDetailModal"><i class="uil-eye"></i></button>
                                        <button class="btn btn-sm btn-info"><i class="uil-download-alt"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>#TXN004</td>
                                    <td>
                                        <div>
                                            <strong>Sarah Williams</strong><br>
                                            <small class="text-muted">sarah@example.com</small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">Flutterwave</span>
                                    </td>
                                    <td>
                                        <strong>₦10,000.00</strong><br>
                                        <small class="text-muted">Before: ₦25,000.00</small>
                                    </td>
                                    <td>bank_transfer</td>
                                    <td><span class="badge bg-success">completed</span></td>
                                    <td>12/07/2025 02:20 PM</td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#transactionDetailModal"><i class="uil-eye"></i></button>
                                        <button class="btn btn-sm btn-info"><i class="uil-download-alt"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>#TXN005</td>
                                    <td>
                                        <div>
                                            <strong>David Brown</strong><br>
                                            <small class="text-muted">david@example.com</small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">Paystack</span>
                                    </td>
                                    <td>
                                        <strong>₦50,000.00</strong><br>
                                        <small class="text-muted">Before: ₦80,000.00</small>
                                    </td>
                                    <td>bank_transfer</td>
                                    <td><span class="badge bg-warning">pending</span></td>
                                    <td>12/07/2025 11:45 AM</td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#transactionDetailModal"><i class="uil-eye"></i></button>
                                        <button class="btn btn-sm btn-info"><i class="uil-download-alt"></i></button>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Transaction Detail Modal -->
                    <div class="modal fade" id="transactionDetailModal" tabindex="-1" role="dialog"
                         aria-labelledby="transactionDetailModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h4 class="modal-title text-dark">Transaction Details</h4>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Transaction ID:</strong> #TXN001</p>
                                            <p><strong>Transaction Reference:</strong> FLW-TXN-2025071309451234</p>
                                            <p><strong>User:</strong> John Doe</p>
                                            <p><strong>Email:</strong> john@example.com</p>
                                            <p><strong>Provider:</strong> Flutterwave</p>
                                            <p><strong>Channel:</strong> bank_transfer</p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Amount:</strong> ₦25,000.00</p>
                                            <p><strong>Amount Before:</strong> ₦50,000.00</p>
                                            <p><strong>Amount After:</strong> ₦25,000.00</p>
                                            <p><strong>Currency:</strong> NGN</p>
                                            <p><strong>Status:</strong> <span class="badge bg-success">completed</span></p>
                                            <p><strong>Service Type:</strong> bank_transfer</p>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <p><strong>Description:</strong> Bank transfer to Access Bank - 0123456789</p>
                                            <p><strong>Date Created:</strong> 13/07/2025 09:45 AM</p>
                                            <p><strong>Date Updated:</strong> 13/07/2025 09:47 AM</p>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <h6><strong>Provider Response:</strong></h6>
                                            <div class="bg-light p-3 rounded">
                                                <code>
                                                    {
                                                    "status": "success",
                                                    "message": "Transfer successful",
                                                    "data": {
                                                    "reference": "FLW-TXN-2025071309451234",
                                                    "account_number": "0123456789",
                                                    "bank_name": "Access Bank"
                                                    }
                                                    }
                                                </code>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="button" class="btn btn-success">Retry Transaction</button>
                                    <button type="button" class="btn btn-info">Download Receipt</button>
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
        .border-left-info {
            border-left: 4px solid #17a2b8 !important;
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
