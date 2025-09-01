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
                                <h1 class="page-title text-dark">Wallet Overview</h1>
                            </div>
                        </div>

                    </div>

                    <!-- Stats -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card stats-card border-left-success">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Wallets</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $total_wallet_count }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card border-left-info">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Balance</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">₦{{ number_format($total_amount_wallet, 2) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card border-left-warning">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Locked Amount</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">₦{{ number_format($total_money_locked, 2) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card border-left-danger">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Suspended/Locked Accounts</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $total_locked_amount }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Wallet Overview Table -->
                    <div class="row mb-3 bg-white">
                        <div class="table-responsive">
                            <table id="sms-datatable" class="table table-hover dt-responsive nowrap w-100 table-centered mb-0">
                                <thead class="table-light">
                                <tr>
                                    <th class="fw-bold text-dark border-0 py-3">User</th>
                                    <th class="fw-bold text-dark border-0 py-3">Balance</th>
                                    <th class="fw-bold text-dark border-0 py-3">Locked Amount</th>
                                    <th class="fw-bold text-dark border-0 py-3">Available Balance</th>
                                    <th class="fw-bold text-dark border-0 py-3">Status</th>
                                    <th class="fw-bold text-dark border-0 py-3 text-end">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($usersWithWallets as $user)
                                    @if ($user->wallet)
                                        <tr class="border-bottom">
                                            <td class="align-middle py-3">
                                                <div>
                                                    <span class="fw-bold text-dark">{{ $user->first_name }} {{ $user->last_name }}</span><br>
                                                    <small class="text-muted font-monospace">{{ $user->email }}</small>
                                                </div>
                                            </td>
                                            <td class="align-middle py-3">
                                                <span class="fw-bold text-dark">₦{{ number_format($user->wallet->amount, 2) }}</span>
                                            </td>
                                            <td class="align-middle py-3">
                                                <span class="fw-bold text-dark">₦{{ number_format($user->wallet->locked_amount, 2) }}</span>
                                            </td>
                                            <td class="align-middle py-3">
                                                <span class="fw-bold text-dark">₦{{ number_format($user->wallet->amount - $user->wallet->locked_amount, 2) }}</span>
                                            </td>
                                            <td class="align-middle py-3">
                        <span class="badge
                            px-3 py-2 fw-bold
                            bg-{{
                                $user->wallet->status === 'active' ? 'success' :
                                ($user->wallet->status === 'locked' ? 'warning' : 'danger')
                            }}">
                            <i class="fas fa-wallet me-1"></i>
                            {{ ucfirst($user->wallet->status) }}
                        </span>
                                            </td>
                                            <td class="align-middle py-3 text-end">
                                                <a href="{{ route('wallet-transactions', $user->id) }}"
                                                   class="btn btn-info btn-sm fw-bold px-3"
                                                   title="View Transactions">
                                                    <i class="fas fa-list me-1"></i> View Transactions
                                                </a>
                                            </td>
                                        </tr>
                                    @endif
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
