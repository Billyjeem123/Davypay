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
                                <h1 class="page-title text-dark">User Wallet Transactions</h1>
                            </div>
                        </div>

                    </div>

                    <!-- Stats -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card stats-card border-left-success">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Transactions</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $total_transactions }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card border-left-info">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Amount</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">₦{{ number_format($total_amount, 2) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card border-left-warning">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $pending_count }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card border-left-danger">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Failed</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $failed_count }}</div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- Bank Transfer Transactions Table -->
                    <div class="row mb-3 bg-white">
                        <div class="table-responsive">
                            <table id="sms-datatable" class="table table-hover dt-responsive nowrap w-100 table-centered mb-0">
                                <thead>
                                <tr>
                                    <th>Transaction ID</th>
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
                                @foreach ($transactions as $txn)
                                    <tr>
                                        <td>#TXN{{ str_pad($txn->id, 3, '0', STR_PAD_LEFT) }}</td>
                                        <td>
                                            <div>
                                                <strong>{{ $txn->user->first_name ?? 'N/A' }}</strong><br>
                                                <small class="text-muted">{{ $txn->user->email ?? 'N/A' }}</small>
                                            </div>
                                        </td>
                                        <td>
                                           <span class="text-success text-dark fw-bold">
    {{ ucfirst($txn->provider ?? 'N/A') }}
</span>

                                        </td>
                                        <td><strong>₦{{ number_format($txn->amount, 2) }}</strong></td>

                                        <td>{{ $txn->type ?? 'N/A' }}</td>
                                        <td>
                        <span class="{{ $txn->status === 'failed' ? 'text-danger' : ($txn->status === 'pending' ? 'text-warning' : 'text-success') }} text-dark fw-bold">
                                    {{ ucfirst($txn->status) }}
                                </span>

                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($txn->created_at)->format('d/m/Y h:i A') }}</td>
                                        <td>
                                            <button class="btn btn-sm btn-primary view-transaction"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#transactionDetailModal"
                                                    data-id="{{ $txn->id }}"
                                                    data-reference="{{ $txn->transaction_reference }}"
                                                    data-user="{{ $txn->user->first_name ?? 'N/A' }}"
                                                    data-email="{{ $txn->user->email ?? 'N/A' }}"
                                                    data-provider="{{ ucfirst($txn->provider ?? 'N/A') }}"
                                                    data-channel="{{ $txn->channel ?? 'N/A' }}"
                                                    data-amount="{{ number_format($txn->amount, 2) }}"
                                                    data-before="{{ number_format($txn->amount_before, 2) }}"
                                                    data-after="{{ number_format($txn->amount_after, 2) }}"
                                                    data-currency="{{ $txn->currency }}"
                                                    data-status="{{ $txn->status }}"
                                                    data-service="{{ $txn->service_type ?? 'N/A' }}"
                                                    data-description="{{ $txn->description ?? 'N/A' }}"
                                                    data-created="{{ \Carbon\Carbon::parse($txn->created_at)->format('d/m/Y h:i A') }}"
                                                    data-updated="{{ \Carbon\Carbon::parse($txn->updated_at)->format('d/m/Y h:i A') }}"
                                                    data-provider-response="{{$txn->provider_response }}"
                                            >
                                                <i class="uil-eye"></i>
                                            </button>

                                        </td>
                                    </tr>
                                @endforeach
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
                                            <p><strong>Transaction ID:</strong> <span id="txn-id"></span></p>
                                            <p><strong>Transaction Reference:</strong> <span id="txn-ref"></span></p>
                                            <p><strong>User:</strong> <span id="txn-user"></span></p>
                                            <p><strong>Email:</strong> <span id="txn-email"></span></p>
                                            <p><strong>Provider:</strong> <span id="txn-provider"></span></p>
                                            <p><strong>Channel:</strong> <span id="txn-channel"></span></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Amount:</strong> ₦<span id="txn-amount"></span></p>
                                            <p><strong>Amount Before:</strong> ₦<span id="txn-before"></span></p>
                                            <p><strong>Amount After:</strong> ₦<span id="txn-after"></span></p>
                                            <p><strong>Currency:</strong> <span id="txn-currency"></span></p>
                                            <p><strong>Status:</strong> <span class="badge" id="txn-status-badge"></span></p>
                                            <p><strong>Service Type:</strong> <span id="txn-service"></span></p>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <p><strong>Description:</strong> <span id="txn-description"></span></p>
                                            <p><strong>Date Created:</strong> <span id="txn-created"></span></p>
                                            <p><strong>Date Updated:</strong> <span id="txn-updated"></span></p>

                                            <!-- Provider response -->

                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <h6><strong>Provider Response:</strong></h6>
                                            <div class="bg-light p-3 rounded">
                                                <pre class="bg-light p-3 rounded" style="white-space: pre-wrap;">
                                                    <code id="txn-response"></code>
                                                </pre>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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



    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const buttons = document.querySelectorAll('.view-transaction');

            buttons.forEach(button => {
                button.addEventListener('click', function () {
                    document.getElementById('txn-id').textContent = '#TXN' + this.dataset.id;
                    document.getElementById('txn-ref').textContent = this.dataset.reference;
                    document.getElementById('txn-user').textContent = this.dataset.user;
                    document.getElementById('txn-email').textContent = this.dataset.email;
                    document.getElementById('txn-provider').textContent = this.dataset.provider;
                    document.getElementById('txn-channel').textContent = this.dataset.channel;

                    document.getElementById('txn-amount').textContent = this.dataset.amount;
                    document.getElementById('txn-before').textContent = this.dataset.before;
                    document.getElementById('txn-after').textContent = this.dataset.after;
                    document.getElementById('txn-currency').textContent = this.dataset.currency;
                    document.getElementById('txn-service').textContent = this.dataset.service;
                    document.getElementById('txn-description').textContent = this.dataset.description;
                    document.getElementById('txn-created').textContent = this.dataset.created;
                    document.getElementById('txn-updated').textContent = this.dataset.updated;

                    // Set status badge color
                    const statusBadge = document.getElementById('txn-status-badge');
                    const status = this.dataset.status.toLowerCase();
                    statusBadge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
                    statusBadge.className = 'badge ' + (status === 'completed' ? 'bg-success' : (status === 'pending' ? 'bg-warning' : 'bg-danger'));

                    // Provider response


                    const response = this.dataset.providerResponse; // ✅ Correct key
                    try {
                        const parsed = JSON.parse(response);
                        const formatted = JSON.stringify(parsed, null, 4); // pretty-print with 4 spaces
                        document.getElementById('txn-response').textContent = formatted;
                    } catch (e) {
                        document.getElementById('txn-response').textContent = response || 'N/A';
                    }

                });
            });
        });
    </script>


@endsection
