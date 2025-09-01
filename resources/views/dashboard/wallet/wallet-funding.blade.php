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
                                <h1 class="page-title text-dark">Wallet Funding</h1>
                                <p class="text-muted">Fund user wallets instantly and securely</p>
                            </div>
                        </div>
                        <div class="col-md-6 py-2 text-end">
                            <a href="#" class="btn btn-sm btn-info mb-2"><i class="uil-history"></i> Funding History</a>
                            <a href="#" class="btn btn-sm btn-secondary mb-2"><i class="uil-arrow-left"></i> Back to Wallets</a>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="row mb-4">
                        <!-- Today's Internal Deposit -->
                        <div class="col-md-3">
                            <div class="card stats-card border-left-success">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Today's Internal Deposit
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        ₦{{ number_format($stats['wallet_funding_today'], 2) }}
                                    </div>
                                    <small class="text-muted">Funds added by users today within the platform (via wallet)</small>
                                </div>
                            </div>
                        </div>

                        <!-- Total Internal Deposit -->
                        <div class="col-md-3">
                            <div class="card stats-card border-left-info">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Total Internal Funding
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        ₦{{ number_format($stats['wallet_funding_total'], 2) }}
                                    </div>
                                    <small class="text-muted">Cumulative deposit made by users using the in-app wallet system</small>
                                </div>
                            </div>
                        </div>

                        <!-- Total External Funding -->
                        <div class="col-md-3">
                            <div class="card stats-card border-left-warning">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        External Funding (All Time)
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        ₦{{ number_format($stats['external_funding_total'], 2) }}
                                    </div>
                                    <small class="text-muted">Funds transferred externally (e.g., direct bank transfers into platform)</small>
                                </div>
                            </div>
                        </div>

                        <!-- Today's External Deposit -->
                        <div class="col-md-3">
                            <div class="card stats-card border-left-danger">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                        Today's External Funding
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        ₦{{ number_format($stats['external_funding_today'], 2) }}
                                    </div>
                                    <small class="text-muted">External payments received into the platform today</small>
                                </div>
                            </div>
                        </div>
                    </div>



                    <!-- Main Funding Form -->
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Fund Wallet</h4>
                                </div>
                                <div class="card-body">
                                    <form id="walletFundingForm" method="POST" action="{{ route('admin.wallet.fund') }}">
                                        @csrf
                                        <input type="hidden" name="user_id" id="user_id_hidden">
                                    <!-- User Selection -->
                                        <div class="row mb-3">
                                            <div class="col-md-12">
                                                <label for="userSelect" class="form-label">Or Select User</label>
                                                <select class="form-select" id="userSelect">
                                                    <option value="">Choose user...</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Selected User Info -->
                                        <div class="row mb-3" id="selectedUserInfo" style="display: none;">
                                            <div class="col-12">
                                                <div class="alert alert-info">
                                                    <h6 class="alert-heading">Selected User</h6>
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <strong>Name:</strong> <span id="selectedUserName"></span>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <strong>Email:</strong> <span id="selectedUserEmail"></span>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <strong>Phone:</strong> <span id="selectedUserPhone">N/A</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Funding Details -->
                                        <div class="row mb-3">
                                            <!-- Amount -->
                                            <div class="col-md-4">
                                                <label for="fundingAmount" class="form-label">Funding Amount (₦)</label>
                                                <input type="number" class="form-control" id="fundingAmount" name="amount" placeholder="0.00" step="0.01" min="1">
                                            </div>

                                            <!-- Type: Credit or Debit -->
                                            <div class="col-md-4">
                                                <label for="transactionType" class="form-label">Type</label>
                                                <select class="form-select" id="transactionType" name="transaction_type">
                                                <option value="">Select Type</option>
                                                    <option value="credit">Credit</option>
                                                    <option value="debit">Debit</option>
                                                </select>
                                            </div>

                                            <!-- Funding/Action Type -->
                                            <div class="col-md-4">
                                                <label for="fundingType" class="form-label">Action</label>
                                                <select class="form-select" id="fundingType" name="funding_type" disabled>
                                                    <option value="">Select Type First</option>
                                                </select>
                                            </div>
                                        </div>



                                        <!-- Admin Notes -->
                                        <div class="mb-3">
                                            <label for="adminNotes" class="form-label">Description </label>
                                            <textarea class="form-control"  name="description" id="adminNotes" rows="3" placeholder="Enter funding description..."></textarea>
                                        </div>

                                        <!-- Funding Options -->
                                        <div class="row mb-4">
                                            <div class="col-12">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="send_notification" id="sendNotification" checked>
                                                    <label class="form-check-label" for="sendNotification">
                                                        Send email notification to user
                                                    </label>
                                                </div>

                                            </div>
                                        </div>

                                        <!-- Action Buttons -->
                                        <div class="row">
                                            <div class="col-12">
                                                <button type="submit" class="btn btn-success btn-lg me-2">
                                                    <i class="uil-money-insert"></i> Fund Wallet
                                                </button>
                                                <button type="button" class="btn btn-secondary btn-lg me-2" onclick="clearForm()">
                                                    <i class="uil-refresh"></i> Clear Form
                                                </button>

                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Wallet Information Sidebar -->
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Current Wallet Info</h4>
                                </div>
                                <div class="card-body">

                                    <!-- Wallet Data (hidden by default) -->
                                    <div id="walletInfo" style="display: none;">
                                        <div class="mb-3">
                                            <label class="form-label">Wallet ID</label>
                                            <p class="form-control-plaintext" id="walletId">#—</p>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Current Balance</label>
                                            <p class="form-control-plaintext text-success" id="currentBalance">₦0.00</p>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Locked Amount</label>
                                            <p class="form-control-plaintext text-warning" id="lockedAmount">₦0.00</p>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Available Balance</label>
                                            <p class="form-control-plaintext text-primary" id="availableBalance">₦0.00</p>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Status</label>
                                            <p class="form-control-plaintext">
                                                <span class="badge bg-secondary" id="walletStatus">N/A</span>
                                            </p>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Last Updated</label>
                                            <p class="form-control-plaintext text-muted" id="lastUpdated">—</p>
                                        </div>
                                    </div>

                                    <!-- Fallback for no selection -->
                                    <div id="noWalletInfo">
                                        <p class="text-muted">Select a user to view wallet information</p>
                                    </div>
                                </div>
                            </div>
                        </div>


                    </div>

                </div>
            </div>
        </div>
    </div>
    <!-- JavaScript Section -->
    <script>
        const users = @json($stats['users']);

        // Populate dropdown
        users.forEach(user => {
            const option = document.createElement('option');
            option.value = user.id;
            option.text = `${user.first_name} - ${user.email}`;
            document.getElementById('userSelect').appendChild(option);
        });

        // Show user info and wallet on select
        document.getElementById('userSelect').addEventListener('change', function () {
            const selectedValue = this.value;

            if (selectedValue) {
                document.getElementById('selectedUserInfo').style.display = 'block';
                document.getElementById('walletInfo').style.display = 'block';

                updateWalletInfo(selectedValue);
            } else {
                document.getElementById('selectedUserInfo').style.display = 'none';
                document.getElementById('walletInfo').style.display = 'none';
            }
        });

        function updateWalletInfo(userId) {
            const selectedUser = users.find(u => u.id == userId);

            if (selectedUser) {
                const wallet = selectedUser.wallet || {};

                document.getElementById('selectedUserName').textContent = selectedUser.first_name;
                document.getElementById('selectedUserEmail').textContent = selectedUser.email;


                document.getElementById('walletId').textContent = wallet.id ? '#' + wallet.id : '—';
                document.getElementById('currentBalance').textContent = '₦' + Number(wallet.amount || 0).toLocaleString();
                document.getElementById('lockedAmount').textContent = '₦' + Number(wallet.locked_amount || 0).toLocaleString();

                const available = (Number(wallet.amount || 0) - Number(wallet.locked_amount || 0)).toFixed(2);
                document.getElementById('availableBalance').textContent = '₦' + Number(available).toLocaleString();

                document.getElementById('walletStatus').textContent = wallet.status || 'N/A';
                document.getElementById('walletStatus').className = 'badge ' + getStatusBadgeClass(wallet.status);
                document.getElementById('lastUpdated').textContent = wallet.updated_at
                    ? new Date(wallet.updated_at).toLocaleString()
                    : '—';
            }
        }

        function getStatusBadgeClass(status) {
            switch ((status || '').toLowerCase()) {
                case 'active': return 'bg-success';
                case 'locked': return 'bg-warning';
                case 'suspended': return 'bg-danger';
                default: return 'bg-secondary';
            }
        }

    </script>


    <script>
        const fundingType = document.getElementById('fundingType');
        const transactionType = document.getElementById('transactionType');

        const creditOptions = [
            { value: 'top_up', text: 'Top-up' },
        ];

        const debitOptions = [
            { value: 'admin_reversal', text: 'Remove Transaction (Error Reversal)' },
        ];

        transactionType.addEventListener('change', function () {
            fundingType.innerHTML = ''; // Clear old options
            const selected = this.value;

            if (selected === 'credit') {
                fundingType.disabled = false;
                creditOptions.forEach(opt => {
                    fundingType.appendChild(new Option(opt.text, opt.value));
                });
            } else if (selected === 'debit') {
                fundingType.disabled = false;
                debitOptions.forEach(opt => {
                    fundingType.appendChild(new Option(opt.text, opt.value));
                });
            } else {
                fundingType.disabled = true;
                fundingType.appendChild(new Option('Select Type First', ''));
            }
        });

        document.getElementById('userSelect').addEventListener('change', function () {
            const selectedValue = this.value;
            document.getElementById('user_id_hidden').value = selectedValue;
        });

    </script>




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
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border: 1px solid #dee2e6;
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
    </style>

@endsection
