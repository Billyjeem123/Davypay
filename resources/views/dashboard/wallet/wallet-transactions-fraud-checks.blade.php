@extends('dashboard.layout.sms')

@section('content')

    <div class="wrapper">
        <div class="content-page sms-page">
            <div class="content">
                <div class="container-fluid">


                    <!-- Fraud Check Summary -->
                    <div class="row mb-4">
                        <div class="col-12 mb-3">
                            <h4 class="h4 text-dark">
                                Fraud Check Summary
                                <span class="text-muted">({{ $date_scope }})</span>
                            </h4>
                            <p class="text-muted">An overview of fraud analysis activities performed {{ strtolower($date_scope) }}.</p>
                        </div>

                        <!-- Total Checks -->
                        <div class="col-sm-6 col-md-4 mb-3">
                            <div class="card border-start border-4 border-danger shadow-sm h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="text-danger text-uppercase fw-bold">Total Checks Run</h6>
                                            <h3 class="text-dark">{{ number_format($total_checks) }}</h3>
                                            <p class="text-muted mb-0">Fraud checks performed</p>
                                        </div>
                                        <div>
                                            <i class="uil-shield-exclamation text-danger fs-1"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Stats -->
                        @foreach($action_stats as $action => $count)
                            @php
                                $isNone = $action === 'none';
                                $title = $isNone ? 'Legit Transactions' : ucwords(str_replace('_', ' ', $action));
                                $description = $isNone
                                    ? 'Transactions that passed fraud check cleanly'
                                    : 'Times this action was flagged and triggered';
                                $color = $isNone ? 'success' : 'warning';
                            @endphp

                            <div class="col-sm-6 col-md-4 mb-3">
                                <div class="card border-start border-4 border-{{ $color }} shadow-sm h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="text-{{ $color }} text-uppercase fw-bold">{{ $title }}</h6>
                                                <h3 class="text-dark">{{ number_format($count) }}</h3>
                                                <p class="text-muted mb-0">{{ $description }}</p>
                                            </div>
                                            <div>
                                                <i class="uil-search text-{{ $color }} fs-1"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>


                    <!-- Enhanced Fraud Checks Table -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card shadow-sm">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                    <h5 class="text-dark mb-0">User Fraud Checks</h5>
                                    <span class="badge bg-danger">{{ $fraudChecks->where('status', 'failed')->count() }} Suspicious Entries</span>
                                </div>
                                <div class="card-body p-0">
                                    <div class="">
                                        <table id="sms-datatable" class="table table-hover dt-responsive nowrap w-100 table-centered mb-0">
                                            <thead class="table-light">
                                            <tr>
                                                <th class="fw-bold text-dark border-0 py-3">#</th>
                                                <th class="fw-bold text-dark border-0 py-3">User</th>
                                                <th class="fw-bold text-dark border-0 py-3">Status</th>
                                                <th class="fw-bold text-dark border-0 py-3">Score</th>
                                                <th class="fw-bold text-dark border-0 py-3">Action</th>
                                                <th class="fw-bold text-dark border-0 py-3 text-end">Actions</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach ($fraudChecks as $check)
                                                <tr class="border-bottom">
                                                    <td class="align-middle py-3">
                                                        <span class="fw-bold text-dark">#FC{{ str_pad($check->id, 3, '0', STR_PAD_LEFT) }}</span>
                                                    </td>
                                                    <td class="align-middle py-3">
                                                        <div class="d-flex align-items-center">
                                                            <img src="https://ui-avatars.com/api/?name={{ urlencode($check->user->first_name . ' ' . $check->user->last_name) }}" alt="Avatar" class="rounded-circle me-2" width="36" height="36">
                                                            <div>
                                                                <span class="fw-bold text-dark">{{ $check->user->first_name }} {{ $check->user->last_name }}</span><br>
                                                                <small class="text-muted">{{ $check->user->email }}</small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="align-middle py-3">
                    <span class="badge px-3 py-2 fw-bold
                        {{ $check->status === 'passed' ? 'bg-success' :
                           ($check->status === 'failed' ? 'bg-danger' : 'bg-warning') }}">
                        {{ ucfirst($check->status) }}
                    </span>
                                                    </td>
                                                    <td class="align-middle py-3">
                                                        <span class="fw-bold text-dark">{{ $check->risk_score }}</span>
                                                    </td>
                                                    <td class="align-middle py-3">
                                                        <span class="text-dark">{{ ucwords(str_replace('_', ' ', $check->action_taken)) }}</span>
                                                    </td>
                                                    <td class="align-middle py-3 text-end">
                                                        <button class="btn btn-sm btn-info view-check"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#fraudDetailModal"
                                                                data-id="{{ $check->id }}"
                                                                data-score="{{ $check->risk_score }}"
                                                                data-status="{{ $check->status }}"
                                                                data-action="{{ $check->action_taken }}"
                                                                data-factors='@json($check->risk_factors)'
                                                                data-details='@json($check->check_details)'
                                                                data-context='@json($check->context)'
                                                                data-message="{{ $check->message }}"
                                                                data-user="{{ $check->user->first_name . ' ' . $check->user->last_name }}"
                                                                data-email="{{ $check->user->email }}">
                                                            <i class="uil-eye"></i> View
                                                        </button>
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

            <!-- Fraud Detail Modal -->
            <div class="modal fade" id="fraudDetailModal" tabindex="-1" role="dialog" aria-labelledby="fraudDetailModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title text-dark">Fraud Check Details</h4>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>User:</strong> <span id="fc-user"></span></p>
                                    <p><strong>Email:</strong> <span id="fc-email"></span></p>
                                    <p><strong>Status:</strong> <span id="fc-status"></span></p>
                                    <p><strong>Action Taken:</strong> <span id="fc-action"></span></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Score:</strong> <span id="fc-score"></span></p>
                                    <p><strong>Message:</strong> <span id="fc-message"></span></p>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <h6>Risk Factors:</h6>
                                    <pre id="fc-factors" class="bg-light p-3 rounded"></pre>
                                    <h6>Check Details:</h6>
                                    <pre id="fc-details" class="bg-light p-3 rounded"></pre>
                                    <h6>Context:</h6>
                                    <pre id="fc-context" class="bg-light p-3 rounded"></pre>
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
        .log-viewer-container {
            background: #1a1a1a;
            border-radius: 8px;
            padding: 20px;
            max-height: 500px;
            overflow-y: auto;
        }
        .log-content {
            color: #00ff00;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.4;
            white-space: pre-wrap;
            word-wrap: break-word;
            margin: 0;
            background: transparent;
            border: none;
        }
        .log-content .error {
            color: #ff4444;
        }
        .log-content .warning {
            color: #ffaa00;
        }
        .log-content .info {
            color: #44aaff;
        }
        .log-content .success {
            color: #44ff44;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const viewButtons = document.querySelectorAll('.view-check');

            viewButtons.forEach(button => {
                button.addEventListener('click', function () {
                    document.getElementById('fc-user').textContent = this.dataset.user;
                    document.getElementById('fc-email').textContent = this.dataset.email;
                    document.getElementById('fc-status').textContent = this.dataset.status;
                    document.getElementById('fc-action').textContent = this.dataset.action;
                    document.getElementById('fc-score').textContent = this.dataset.score;
                    document.getElementById('fc-message').textContent = this.dataset.message;

                    document.getElementById('fc-factors').textContent = JSON.stringify(JSON.parse(this.dataset.factors || '{}'), null, 4);
                    document.getElementById('fc-details').textContent = JSON.stringify(JSON.parse(this.dataset.details || '{}'), null, 4);
                    document.getElementById('fc-context').textContent = JSON.stringify(JSON.parse(this.dataset.context || '{}'), null, 4);
                });
            });
        });
    </script>
@endsection
