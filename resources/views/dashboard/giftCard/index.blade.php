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
                                <h1 class="page-title text-dark">Gift Card Records</h1>
                                <p class="text-muted">View and manage all gift card transactions</p>
                            </div>
                        </div>
                        <div class="col-md-6 py-2 text-end">
                            <a href="{{ route('gift-cards.listing') }}" class="btn btn-sm btn-info mb-2">
                                <i class="uil-gift"></i> Manage Gift Card Types
                            </a>
                            <a href="{{ route('admin.home') }}" class="btn btn-sm btn-secondary mb-2">
                                <i class="uil-dashboard"></i> Dashboard
                            </a>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="card mb-3">
                        <div class="card-body">
                            <form method="GET" action="{{ route('gift-cards') }}">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Status</label>
                                        <select name="status" class="form-control">
                                            <option value="">All Status</option>
                                            <option value="pending">Pending</option>
                                            <option value="approved">Approved</option>
                                            <option value="rejected">Rejected</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Gift Card Type</label>
                                        <select name="type_id" class="form-control">
                                            <option value="">All Types</option>
                                            <!-- Populate from GiftCardList -->
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Country</label>
                                        <input type="text" name="country" class="form-control" placeholder="e.g., USA, UK">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">&nbsp;</label>
                                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="sms-datatable" class="table table-bordered table-hover w-100 table-centered mb-0">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Gift Card Type</th>
                                <th>Country</th>
                                <th>Initial Value</th>
                                <th>Evaluated Value</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($giftCards as $giftCard)
                                <tr>
                                    <td>{{ $giftCard->id }}</td>
                                    <td>
                                        @if($giftCard->user)
                                            {{ $giftCard->user->first_name }} {{ $giftCard->user->last_name }}
                                            <br>
                                            <small class="text-muted">ID: {{ $giftCard->user_id }}</small>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($giftCard->type)
                                            <div class="d-flex align-items-center">
                                                @if($giftCard->type->logo_path)
                                                    <img src="{{ asset($giftCard->type->logo_path) }}" alt="{{ $giftCard->type->name }}" style="width: 30px; height: 30px; object-fit: contain; margin-right: 8px;">
                                                @endif
                                                <span>{{ $giftCard->type->name }}</span>
                                            </div>
                                        @else
                                            <span class="text-muted">Unknown</span>
                                        @endif
                                    </td>
                                    <td>{{ $giftCard->country ?? 'N/A' }}</td>
                                    <td>
                                        {{ $giftCard->currency ?? '$' }}{{ number_format($giftCard->initial_value, 2) }}
                                    </td>
                                    <td>
                                        @if($giftCard->evaluated_value)
                                            ₦{{ number_format($giftCard->evaluated_value, 2) }}
                                        @else
                                            <span class="text-muted">Pending</span>
                                        @endif
                                    </td>
                                    <td>
                                        @switch($giftCard->status)
                                            @case('pending')
                                                <span class="badge bg-warning">Pending</span>
                                                @break
                                            @case('approved')
                                                <span class="badge bg-success">Approved</span>
                                                @break
                                            @case('rejected')
                                                <span class="badge bg-danger">Rejected</span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary">{{ ucfirst($giftCard->status) }}</span>
                                        @endswitch
                                    </td>
                                    <td>{{ $giftCard->created_at->format('M d, Y') }}</td>
                                    <td>
                                        <a href="{{ route('gift-cards.show', $giftCard->id) }}" class="btn btn-sm btn-info">
                                            <i class="uil-eye"></i> View
                                        </a>

                                        @if($giftCard->status == 'pending')
                                            <button class="btn btn-sm btn-success approve-btn"
                                                    data-id="{{ $giftCard->id }}"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#approveModal">
                                                <i class="uil-check"></i>
                                            </button>

                                            <button class="btn btn-sm btn-danger reject-btn"
                                                    data-id="{{ $giftCard->id }}"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#rejectModal">
                                                <i class="uil-times"></i>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Approve Modal -->
                <div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <form method="POST" action="{{ route('gift-cards.approve') }}">
                            @csrf
                            <input type="hidden" name="id" id="approve-id">

                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="approveModalLabel">Approve Gift Card</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label for="evaluated_value" class="form-label">Evaluated Value (₦)</label>
                                        <input type="number" step="0.01" class="form-control" id="evaluated_value" name="evaluated_value" required>
                                        <div class="form-text">Enter the amount to credit the user in Naira</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="approve_notes" class="form-label">Notes (Optional)</label>
                                        <textarea class="form-control" id="approve_notes" name="notes" rows="3"></textarea>
                                    </div>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-success">Approve & Credit User</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Evaluate Modal -->
                <div class="modal fade" id="evaluateModal" tabindex="-1" aria-labelledby="evaluateModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <form method="POST" id="evaluateForm">
                            @csrf

                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="evaluateModalLabel">Evaluate Gift Card</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                    <div class="alert alert-info">
                                        <strong>Initial Value:</strong> <span id="eval-currency">$</span><span id="eval-initial-value">0.00</span>
                                    </div>

                                    <div class="alert alert-warning">
                                        <i class="uil-info-circle"></i> This will automatically calculate the evaluated value based on the current exchange rate.
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Estimated Evaluated Value</label>
                                        <p class="fw-bold fs-4 text-primary" id="eval-estimated-value">₦0.00</p>
                                        <small class="text-muted">Based on current rate (80%)</small>
                                    </div>

                                    <div class="mb-3">
                                        <label for="evaluate_notes" class="form-label">Evaluation Notes (Optional)</label>
                                        <textarea class="form-control" id="evaluate_notes" name="notes" rows="3" placeholder="Add any notes about this evaluation..."></textarea>
                                    </div>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-warning">Calculate & Save Evaluation</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Reject Modal -->
                <div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <form method="POST" action="{{ route('gift-cards.reject') }}">
                            @csrf
                            <input type="hidden" name="id" id="reject-id">

                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="rejectModalLabel">Reject Gift Card</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                    <div class="alert alert-warning">
                                        <i class="uil-exclamation-triangle"></i> Are you sure you want to reject this gift card transaction?
                                    </div>

                                    <div class="mb-3">
                                        <label for="reject_notes" class="form-label">Reason for Rejection</label>
                                        <textarea class="form-control" id="reject_notes" name="notes" rows="3" required placeholder="Explain why this gift card is being rejected..."></textarea>
                                    </div>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-danger">Reject Transaction</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const approveButtons = document.querySelectorAll('.approve-btn');
            approveButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const id = this.getAttribute('data-id');
                    document.getElementById('approve-id').value = id;
                });
            });

            const evaluateButtons = document.querySelectorAll('.evaluate-btn');
            evaluateButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const id = this.getAttribute('data-id');
                    const initialValue = parseFloat(this.getAttribute('data-initial-value'));
                    const currency = this.getAttribute('data-currency');

                    const form = document.getElementById('evaluateForm');
                    form.action = `/admin/gift-cards/${id}/evaluate`;

                    document.getElementById('eval-currency').textContent = currency;
                    document.getElementById('eval-initial-value').textContent = initialValue.toFixed(2);

                    const estimatedValue = initialValue * 0.8;
                    document.getElementById('eval-estimated-value').textContent = '₦' + estimatedValue.toLocaleString('en-NG', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                });
            });

            const rejectButtons = document.querySelectorAll('.reject-btn');
            rejectButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const id = this.getAttribute('data-id');
                    document.getElementById('reject-id').value = id;
                });
            });
        });
    </script>

@endsection
