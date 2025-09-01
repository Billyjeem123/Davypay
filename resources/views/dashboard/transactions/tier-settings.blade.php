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
                                <h1 class="page-title text-dark">Tier Settings Management</h1>
                                <p class="text-muted">Manage user tier levels and their limits</p>
                            </div>
                        </div>

                    </div>

                    <!-- Stats Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card stats-card border-left-primary">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Tiers</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total_tiers'] }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="card stats-card border-left-info">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Highest Daily Limit</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">₦{{ ($stats['highest_daily_limit']) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card border-left-warning">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Max Wallet Balance</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">₦{{ ($stats['max_wallet_balance']) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tiers Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Tier Management</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="tiers-datatable" class="table table-hover dt-responsive nowrap w-100 table-centered mb-0">
                                            <thead class="table-light">
                                            <tr>
                                                <th>ID</th>
                                                <th>Tier Name</th>
                                                <th>Daily Limit</th>
                                                <th>Wallet Balance Limit</th>
                                                <th>Status</th>
                                                <th>Created Date</th>
                                                <th>Last Updated</th>
                                                <th>Actions</th>
                                            </tr>
                                            </thead>
                                            <tbody>

                                            @foreach($tiers as $tier)
                                                <tr>
                                                    <td><span class="badge bg-light text-dark">#{{ str_pad($tier->id, 3, '0', STR_PAD_LEFT) }}</span></td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="tier-icon bg-success bg-soft text-success rounded-circle me-2" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                                                <i class="uil-star font-size-16"></i>
                                                            </div>
                                                            <div>
                                                                <h6 class="mb-0">{{ ucfirst(str_replace('_', ' ', $tier->name)) }}</h6>

                                                                @php
                                                                    $levels = [
                                                                        'tier_1' => 'Basic level',
                                                                        'tier_2' => 'Standard level',
                                                                        'tier_3' => 'Premium level',
                                                                    ];

                                                                    $level = $levels[$tier->name] ?? 'Premium package';
                                                                @endphp

                                                                <small class="text-muted">{{ $level }}</small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="h6 text-success">₦{{ ($tier->daily_limit) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="h6 text-info">{{ $tier->wallet_balance }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-success">Active</span>
                                                    </td>
                                                    <td>{{ \Carbon\Carbon::parse($tier->created_at)->format('d/m/Y g:i A') }}</td>
                                                    <td>{{ \Carbon\Carbon::parse($tier->updated_at)->format('d/m/Y g:i A') }}</td>
                                                    <td>
                                                        <div class="btn-group" role="group">

                                                            <button type="button" class="btn btn-sm btn-outline-info view-tier"
                                                                    data-bs-toggle="modal" data-bs-target="#viewTierModal"
                                                                    data-id="{{ $tier->id }}"
                                                                    data-name="{{ $tier->name }}"
                                                                    data-daily="{{ $tier->daily_limit }}"
                                                                    data-wallet="{{ $tier->wallet_balance }}"
                                                                    data-created="{{ \Carbon\Carbon::parse($tier->created_at)->format('d/m/Y g:i A') }}">
                                                                <i class="uil-eye"></i>
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
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Edit Tier Modal -->
    <div class="modal fade" id="tierModal" tabindex="-1" role="dialog" aria-labelledby="tierModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title text-dark" id="tierModalLabel">Add New Tier</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                </div>
                <form id="" action="{{route('tiers.update')}}" method="POST">
                    @csrf
                    <input id="mytierId" type="text"  name="id">

                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="tierName" class="form-label">Tier Name</label>
                                    <!-- Make name field readonly/disabled -->
                                    <input type="text" class="form-control" id="tierName" value="{{$tier->name}}" readonly style="background-color: #f8f9fa;">
                                    <div class="form-text text-muted">Tier name cannot be changed after creation</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="tierStatus" class="form-label">Status</label>
                                    <select class="form-select" id="tierStatus" name="status">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="dailyLimit" class="form-label">Daily Limit (₦) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">₦</span>
                                        <input type="number" class="form-control" id="dailyLimit" name="daily_limit" >
                                    </div>
                                    <div class="form-text">Maximum daily transaction limit for this tier</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="walletBalance" class="form-label">Wallet Balance Limit (₦) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">₦</span>
                                        <input type="number" class="form-control" id="walletBalance" name="wallet_balance" placeholder="100000" >
                                    </div>
                                    <div class="form-text">Maximum wallet balance allowed for this tier</div>
                                </div>
                            </div>
                        </div>



                        <!-- Tier Preview -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">Tier Preview</h6>
                                        <div class="d-flex align-items-center">
                                            <div class="tier-icon bg-primary bg-soft text-primary rounded-circle me-3" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                                <i class="uil-star font-size-18"></i>
                                            </div>
                                            <div>
                                                <h5 class="mb-1" id="previewName">New Tier</h5>
                                                <p class="mb-0 text-muted">Daily: <span id="previewDaily">₦0</span> | Wallet: <span id="previewWallet">₦0</span></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="uil-check me-1"></i> Save Tier
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Tier Details Modal -->
    <div class="modal fade" id="viewTierModal" tabindex="-1" role="dialog" aria-labelledby="viewTierModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title text-dark">Tier Details</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Tier ID:</strong> <span id="view-tier-id"></span></p>
                            <p><strong>Tier Name:</strong> <span id="view-tier-name"></span></p>
                            <p><strong>Status:</strong> <span id="view-tier-status" class="badge bg-success">Active</span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Daily Limit:</strong> ₦<span id="view-daily-limit"></span></p>
                            <p><strong>Wallet Balance Limit:</strong> ₦<span id="view-wallet-balance"></span></p>
                            <p><strong>Created:</strong> <span id="view-created-date"></span></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary edit-from-view">
                        <i class="uil-edit-alt me-1"></i> Edit Tier
                    </button>
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
            border-left: 4px solid #5b73e8 !important;
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
        .bg-soft {
            background-color: rgba(var(--bs-primary-rgb), 0.1) !important;
        }
        .bg-primary.bg-soft {
            background-color: rgba(91, 115, 232, 0.1) !important;
        }
        .bg-success.bg-soft {
            background-color: rgba(40, 167, 69, 0.1) !important;
        }
        .bg-warning.bg-soft {
            background-color: rgba(255, 193, 7, 0.1) !important;
        }
        .bg-danger.bg-soft {
            background-color: rgba(220, 53, 69, 0.1) !important;
        }
        .bg-info.bg-soft {
            background-color: rgba(23, 162, 184, 0.1) !important;
        }
        .tier-icon {
            font-size: 14px;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tierForm = document.getElementById('tierForm');
            const tierModal = document.getElementById('tierModal');
            const tierModalLabel = document.getElementById('tierModalLabel');

            // Form inputs
            const tierNameInput = document.getElementById('tierName');
            const dailyLimitInput = document.getElementById('dailyLimit');
            const walletBalanceInput = document.getElementById('walletBalance');

            // Preview elements
            const previewName = document.getElementById('previewName');
            const previewDaily = document.getElementById('previewDaily');
            const previewWallet = document.getElementById('previewWallet');

            // Update preview as user types
            function updatePreview() {
                const name = tierNameInput.value || 'New Tier';
                const daily = dailyLimitInput.value ? '₦' + Number(dailyLimitInput.value).toLocaleString() : '₦0';
                const wallet = walletBalanceInput.value ? '₦' + Number(walletBalanceInput.value).toLocaleString() : '₦0';

                previewName.textContent = name.replace(/_/g, ' ').toUpperCase();
                previewDaily.textContent = daily;
                previewWallet.textContent = wallet;
            }

            // Add event listeners for real-time preview
            tierNameInput.addEventListener('input', updatePreview);
            dailyLimitInput.addEventListener('input', updatePreview);
            walletBalanceInput.addEventListener('input', updatePreview);

            // Reset modal for new tier
            tierModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;

                if (!button.classList.contains('edit-tier')) {
                    // New tier mode
                    tierModalLabel.textContent = 'Add New Tier';
                    tierForm.reset();
                    updatePreview();
                }
            });

            // Handle edit tier buttons
            document.querySelectorAll('.edit-tier').forEach(button => {
                button.addEventListener('click', function () {
                    tierModalLabel.textContent = 'Edit Tier';

                    // Populate form with existing data
                    document.getElementById('tierName').value = this.dataset.name;
                    document.getElementById('dailyLimit').value = this.dataset.daily;
                    document.getElementById('walletBalance').value = this.dataset.wallet;
                    document.getElementById('tierStatus').value = 'active';

                    updatePreview();
                });
            });

            // Handle view tier buttons - FIXED
            document.querySelectorAll('.view-tier').forEach(button => {
                button.addEventListener('click', function () {
                    document.getElementById('view-tier-id').textContent = '#' + String(this.dataset.id).padStart(3, '0');
                    document.getElementById('view-tier-name').textContent = this.dataset.name.replace(/_/g, ' ').toUpperCase();
                    document.getElementById('view-daily-limit').textContent = Number(this.dataset.daily).toLocaleString();

                    // Fix for wallet balance display - clean formatted currency
                    const walletBalance = this.dataset.wallet.toString().replace(/[₦,]/g, '').trim();
                    document.getElementById('view-wallet-balance').textContent = Number(walletBalance).toLocaleString();

                    // Set created date
                    document.getElementById('view-created-date').textContent = this.dataset.created || '24/06/2025 7:53 AM';
                });
            });


            // Handle edit from view modal
            document.querySelector('.edit-from-view').addEventListener('click', function () {
                // Get current view modal data
                const viewModal = document.getElementById('viewTierModal');
                const tierId = document.getElementById('view-tier-id').textContent.replace('#', '').replace(/^0+/, '');
                const tierName = document.getElementById('view-tier-name').textContent.toLowerCase().replace(/ /g, '_');
                const dailyLimit = document.getElementById('view-daily-limit').textContent.replace(/,/g, '');

                // Clean wallet balance value from formatted currency
                const walletBalanceText = document.getElementById('view-wallet-balance').textContent;
                const walletBalance = walletBalanceText.replace(/[₦,]/g, '').trim();

                // Close view modal
                const viewModalInstance = bootstrap.Modal.getInstance(viewModal);
                viewModalInstance.hide();

                // Wait for view modal to close, then open edit modal
                setTimeout(() => {
                    // Set edit modal title
                    tierModalLabel.textContent = 'Edit Tier';

                    // Populate edit form
                    document.getElementById('tierName').value = tierName;
                    document.getElementById('dailyLimit').value = dailyLimit;
                    document.getElementById('walletBalance').value = walletBalance;
                    document.getElementById('tierStatus').value = 'active';
                     document.getElementById('mytierId').value = tierId;

                    // Update preview
                    updatePreview();

                    // Open edit modal
                    const editModal = new bootstrap.Modal(tierModal);
                    editModal.show();
                }, 300);
            });
        });
    </script>

@endsection
