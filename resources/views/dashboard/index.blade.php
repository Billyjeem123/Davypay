@extends('dashboard.layout.main')

@section('content')
    <div id="noticeModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="noticeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <span style="font-size: 80px; color: #28a745;">
                        <i class="uil-info-circle"></i>
                    </span>
                    <br>
                    <h3 class="text-dark">System Notice</h3>
                    <br>
                    <p class="text-black">
                        Welcome to your fintech admin dashboard. Monitor all transactions, manage users, and oversee system operations from here.
                    </p>
                    <br>
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Got it</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Begin page -->
    <div class="wrapper">
        <div class="content-page">
            <div class="content">

                <!-- Start Content-->
                <div class="container-fluid">

                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <h1 class="page-title text-dark">Admin Dashboard</h1>
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item">Last updated: {{ now()->format('M d, Y H:i') }}</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Overview -->
                    <div class="row mb-3">
                        <div class="col-12 mb-3">
                            <h4 class="h4 text-dark">Financial Overview</h4>
                        </div>

                        <div class="col-sm-6 col-md-3 col-lg-3">
                            <div class="card border-left-primary shadow-sm">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Net Platform Balance</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">₦{{ number_format($netBalance, 2) }}</div>
                                    <p class="text-muted small">Net revenue (cash inflow - outflow)</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-md-3 col-lg-3">
                            <div class="card border-left-success shadow-sm">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Users</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalUsers }}</div>
                                    <p class="text-muted small">Registered users on the platform</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-md-3 col-lg-3">
                            <div class="card border-left-warning shadow-sm">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Transactions</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $pendingTransactions }}</div>
                                    <p class="text-muted small">Transactions awaiting completion</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-md-3 col-lg-3">
                            <div class="card border-left-danger shadow-sm">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Users Registered Today
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        {{ $usersRegisteredToday }}
                                    </div>
                                    <p class="text-muted small">
                                        New registrations today
                                    </p>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- Platform Financial Summary -->
                    <div class="row mb-3">
                        <div class="col-12 mb-3">
                            <h4 class="h4 text-dark">Platform Financial Summary</h4>
                        </div>

                        <div class="col-sm-6 col-md-4 col-lg-4">
                            <div class="card border-left-success shadow-sm">
                                <div class="card-body">
                                    <h5 class="text-success mt-0">Total Cash Inflow</h5>
                                    <h3 class="mt-2 text-dark">₦{{ number_format($cashInFlow, 2) }}</h3>
                                    <p class="text-muted mb-0">All deposits and bank funding</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-md-4 col-lg-4">
                            <div class="card border-left-danger shadow-sm">
                                <div class="card-body">
                                    <h5 class="text-danger mt-0">Total Cash Outflow</h5>
                                    <h3 class="mt-2 text-dark">₦{{ number_format($totalDebit, 2) }}</h3>
                                    <p class="text-muted mb-0">All withdrawals or debits from the system</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-md-4 col-lg-4">
                            <div class="card border-left-info shadow-sm">
                                <div class="card-body">
                                    <h5 class="text-info mt-0">Internal Platform Credits</h5>
                                    <h3 class="mt-2 text-dark">₦{{ number_format($internalCredits, 2) }}</h3>
                                    <p class="text-muted mb-0">Includes referrals, refunds, bonuses</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Service Statistics -->
                    <div class="row mb-3">
                        <div class="col-12 mb-3">
                            <h4 class="h4 text-dark">Service Activity (Today)</h4>
                        </div>

                        <div class="col-sm-6 col-md-3 col-lg-3">
                            <div class="card border-left-primary shadow-sm">
                                <div class="card-body">
                                    <h6 class="text-dark mt-0">Successful Bank Transfers</h6>
                                    <h3 class="mt-2 text-dark">{{ $successfulTransfers }}</h3>
                                    <p class="text-muted small">Transfers processed successfully today</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-md-3 col-lg-3">
                            <div class="card border-left-danger shadow-sm">
                                <div class="card-body">
                                    <h6 class="text-dark mt-0">Failed Bank Transfers</h6>
                                    <h3 class="mt-2 text-dark">{{ $failedTransfers }}</h3>
                                    <p class="text-muted small">Bank transfers that failed today</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-md-3 col-lg-3">
                            <div class="card border-left-warning shadow-sm">
                                <div class="card-body">
                                    <h6 class="text-dark mt-0">Transfer Refunds</h6>
                                    <h3 class="mt-2 text-dark">{{ $failedTransferRefunds }}</h3>
                                    <p class="text-muted small">Refunds issued due to failed transfers</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-md-3 col-lg-3">
                            <div class="card border-left-success shadow-sm">
                                <div class="card-body">
                                    <h6 class="text-dark mt-0">Successful Bill Transactions</h6>
                                    <h3 class="mt-2 text-dark">{{ $todaySuccessfulBillTransaction }}</h3>
                                    <p class="text-muted small">Bills paid successfully today</p>
                                </div>
                            </div>
                        </div>
                    </div>





                    <!-- Fraud Check Summary -->
                    <div class="row mb-3">
                        <div class="col-12 mb-3">
                            <h4 class="h4 text-dark">Fraud Check Summary <span class="text-muted">({{ $fraudStats['date_scope'] }})</span></h4>
                        </div>

                        <div class="col-sm-6 col-md-4 col-lg-4">
                            <div class="card border-left-danger shadow-sm">
                                <div class="card-body">
                                    <h5 class="text-danger mt-0">Fraud Checks Run</h5>
                                    <h3 class="mt-2 text-dark">{{ number_format($fraudStats['total_checks']) }}</h3>
                                    <p class="text-muted mb-0">Number of fraud checks processed</p>
                                </div>
                            </div>
                        </div>

                        @foreach($fraudStats['action_stats'] as $action => $total)
                            @php
                                $isNone = $action === 'none';
                                $title = $isNone ? 'Legit Transactions' : ucwords(str_replace('_', ' ', $action));
                                $description = $isNone
                                    ? 'Passed fraud checks without any action'
                                    : 'Times this action was taken';
                                $iconClass = $isNone ? 'border-left-success' : 'border-left-warning';
                            @endphp

                            <div class="col-sm-6 col-md-4 col-lg-4">
                                <div class="card {{ $iconClass }} shadow-sm">
                                    <div class="card-body">
                                        <h5 class="{{ $isNone ? 'text-success' : 'text-warning' }} mt-0">
                                            {{ $title }}
                                        </h5>
                                        <h3 class="mt-2 text-dark">{{ number_format($total) }}</h3>
                                        <p class="text-muted mb-0">{{ $description }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                    </div>



                </div>
                <!-- container -->

            </div>
            <!-- content -->

        </div>

        <!-- ============================================================== -->
        <!-- End Page content -->
        <!-- ============================================================== -->

    </div>
    <!-- END wrapper -->

@endsection

@push('scripts')
    <script>
        // Auto-refresh dashboard every 5 minutes
        setInterval(function() {
            location.reload();
        }, 300000);
    </script>
@endpush
