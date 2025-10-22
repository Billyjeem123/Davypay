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
                                <h1 class="page-title text-dark">Gift Card Details #{{ $giftCard->id }}</h1>
                                <p class="text-muted">Complete information about this gift card transaction</p>
                            </div>
                        </div>
                        <div class="col-md-6 py-2 text-end">
                            <a href="{{ route('gift-cards') }}" class="btn btn-sm btn-secondary mb-2">
                                <i class="uil-arrow-left"></i> Back to List
                            </a>
                            <a href="{{ route('admin.home') }}" class="btn btn-sm btn-secondary mb-2">
                                <i class="uil-dashboard"></i> Dashboard
                            </a>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Left Column - Main Details -->
                        <div class="col-lg-8">
                            <!-- Status Card -->
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h4 class="mb-1">Transaction Status</h4>
                                            @switch($giftCard->status)
                                                @case('pending')
                                                    <span class="badge bg-warning fs-5">Pending Review</span>
                                                    @break
                                                @case('approved')
                                                    <span class="badge bg-success fs-5">Approved</span>
                                                    @break
                                                @case('rejected')
                                                    <span class="badge bg-danger fs-5">Rejected</span>
                                                    @break
                                                @default
                                                    <span class="badge bg-secondary fs-5">{{ ucfirst($giftCard->status) }}</span>
                                            @endswitch
                                        </div>

                                        @if($giftCard->status == 'pending')
                                            <div>
                                                <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#evaluateModal">
                                                    <i class="uil-calculator"></i> Evaluate
                                                </button>
                                                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal">
                                                    <i class="uil-check"></i> Approve
                                                </button>
                                                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                                    <i class="uil-times"></i> Reject
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Gift Card Information -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h4 class="card-title mb-0">Gift Card Information</h4>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="text-muted">Gift Card Type</label>
                                            @if($giftCard->type)
                                                <div class="d-flex align-items-center mt-1">
                                                    @if($giftCard->type->logo_path)
                                                        <img src="{{ asset($giftCard->type->logo_path) }}" alt="{{ $giftCard->type->name }}" style="width: 40px; height: 40px; object-fit: contain; margin-right: 10px;">
                                                    @endif
                                                    <h5 class="mb-0">{{ $giftCard->type->name }}</h5>
                                                </div>
                                            @else
                                                <p class="text-muted">Unknown Type</p>
                                            @endif
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted">Country</label>
                                            <p class="fw-bold">{{ $giftCard->country ?? 'N/A' }}</p>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="text-muted">Currency</label>
                                            <p class="fw-bold">{{ $giftCard->currency ?? 'USD' }}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted">Package Type</label>
                                            <p class="fw-bold">{{ $giftCard->package ?? 'N/A' }}</p>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="text-muted">Gift Card Code</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="card-code" value="{{ $giftCard->code ?? 'N/A' }}" readonly>
                                                @if($giftCard->code)
                                                    <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('card-code')">
                                                        <i class="uil-copy"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted">Issued By</label>
                                            <p class="fw-bold">{{ $giftCard->issue_by ?? 'N/A' }}</p>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="text-muted">Issue Date</label>
                                            <p class="fw-bold">{{ $giftCard->issue_date ? \Carbon\Carbon::parse($giftCard->issue_date)->format('M d, Y') : 'N/A' }}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted">Expiration Date</label>
                                            <p class="fw-bold">
                                                @if($giftCard->expiration_date)
                                                    {{ \Carbon\Carbon::parse($giftCard->expiration_date)->format('M d, Y') }}
                                                    @if(\Carbon\Carbon::parse($giftCard->expiration_date)->isPast())
                                                        <span class="badge bg-danger">Expired</span>
                                                    @endif
                                                @else
                                                    N/A
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Value Information -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h4 class="card-title mb-0">Value Information</h4>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-md-4">
                                            <div class="p-3 border rounded">
                                                <label class="text-muted">Initial Value</label>
                                                <h3 class="text-primary mb-0">{{ $giftCard->currency ?? '$' }}{{ number_format($giftCard->initial_value, 2) }}</h3>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="p-3 border rounded">
                                                <label class="text-muted">Current Value</label>
                                                <h3 class="text-info mb-0">{{ $giftCard->currency ?? '$' }}{{ number_format($giftCard->current_value, 2) }}</h3>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="p-3 border rounded">
                                                <label class="text-muted">Evaluated Value</label>
                                                <h3 class="text-success mb-0">
                                                    @if($giftCard->evaluated_value)
                                                        ₦{{ number_format($giftCard->evaluated_value, 2) }}
                                                    @else
                                                        <span class="text-muted">Pending</span>
                                                    @endif
                                                </h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Gift Card Image -->
                            @if($giftCard->image_path)
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h4 class="card-title mb-0">Uploaded Gift Card Image</h4>
                                    </div>
                                    <div class="card-body text-center">
                                        <img src="{{ asset($giftCard->image_path) }}" alt="Gift Card" class="img-fluid rounded" style="max-height: 400px;">
                                        <div class="mt-2">
                                            <a href="{{ asset($giftCard->image_path) }}" target="_blank" class="btn btn-sm btn-primary">
                                                <i class="uil-external-link-alt"></i> View Full Size
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Notes -->
                            @if($giftCard->notes)
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h4 class="card-title mb-0">Notes</h4>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-0">{{ $giftCard->notes }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Right Column - User & Activity -->
                        <div class="col-lg-4">
                            <!-- User Information -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h4 class="card-title mb-0">User Information</h4>
                                </div>
                                <div class="card-body">
                                    @if($giftCard->user)
                                        <div class="text-center mb-3">
                                            <div class="avatar-lg mx-auto mb-2">
                                                <span class="avatar-title rounded-circle bg-soft-primary text-primary font-20">
                                                    {{ strtoupper(substr($giftCard->user->first_name, 0, 1) . substr($giftCard->user->last_name, 0, 1)) }}
                                                </span>
                                            </div>
                                            <h5 class="mb-1">{{ $giftCard->user->first_name }} {{ $giftCard->user->last_name }}</h5>
                                            <p class="text-muted mb-0">{{ $giftCard->user->email }}</p>
                                        </div>

                                        <div class="border-top pt-3">
                                            <div class="mb-2">
                                                <span class="text-muted">User ID:</span>
                                                <span class="float-end fw-bold">{{ $giftCard->user_id }}</span>
                                            </div>
                                            <div class="mb-2">
                                                <span class="text-muted">Phone:</span>
                                                <span class="float-end fw-bold">{{ $giftCard->user->phone ?? 'N/A' }}</span>
                                            </div>
                                        </div>
                                    @else
                                        <p class="text-muted text-center">No user information available</p>
                                    @endif
                                </div>
                            </div>

                            <!-- Timeline/Activity -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h4 class="card-title mb-0">Activity Timeline</h4>
                                </div>
                                <div class="card-body">
                                    <div class="timeline-alt">
                                        <div class="timeline-item">
                                            <i class="uil-circle bg-info timeline-icon"></i>
                                            <div class="timeline-item-info">
                                                <h5 class="mb-1">Submitted</h5>
                                                <p class="text-muted mb-0">{{ $giftCard->created_at->format('M d, Y h:i A') }}</p>
                                                <small class="text-muted">{{ $giftCard->created_at->diffForHumans() }}</small>
                                            </div>
                                        </div>

                                        @if($giftCard->status == 'approved' || $giftCard->status == 'rejected')
                                            <div class="timeline-item">
                                                <i class="uil-circle {{ $giftCard->status == 'approved' ? 'bg-success' : 'bg-danger' }} timeline-icon"></i>
                                                <div class="timeline-item-info">
                                                    <h5 class="mb-1">{{ ucfirst($giftCard->status) }}</h5>
                                                    <p class="text-muted mb-0">{{ $giftCard->updated_at->format('M d, Y h:i A') }}</p>
                                                    <small class="text-muted">{{ $giftCard->updated_at->diffForHumans() }}</small>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Evaluate Modal -->
                <div class="modal fade" id="evaluateModal" tabindex="-1">
                    <div class="modal-dialog">
                        <form method="POST" action="/admin/gift-cards/{{ $giftCard->id }}/evaluate">
                            @csrf
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Evaluate Gift Card</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="alert alert-info">
                                        <strong>Initial Value:</strong> {{ $giftCard->currency ?? '$' }}{{ number_format($giftCard->initial_value, 2) }}
                                    </div>
                                    <div class="mb-3">
                                        <label for="rate" class="form-label">Exchange Rate (to Naira)</label>
                                        <input type="number" step="0.01" class="form-control" id="rate" name="rate" min="0" value="0.8" required>
                                        <div class="form-text">Enter the conversion rate (e.g., 0.8 means ₦0.8 per unit)</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="amount" class="form-label">Gift Card Amount</label>
                                        <div class="input-group">
                                            <span class="input-group-text">{{ $giftCard->currency ?? '$' }}</span>
                                            <input type="number" step="0.01" class="form-control" id="amount" name="amount" value="{{ $giftCard->initial_value }}" required readonly>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Calculated Evaluated Value</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₦</span>
                                            <input type="text" class="form-control fw-bold fs-5 text-success" id="calculated-value" value="0.00" readonly>
                                        </div>
                                        <div class="form-text">Amount × Rate = Evaluated Value in Naira</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="evaluate_notes" class="form-label">Notes (Optional)</label>
                                        <textarea class="form-control" id="evaluate_notes" name="notes" rows="3">{{ $giftCard->notes }}</textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-warning">Save Evaluation</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Approve Modal -->
                <div class="modal fade" id="approveModal" tabindex="-1">
                    <div class="modal-dialog">
                        <form method="POST" action="{{ route('gift-cards.approve') }}">
                            @csrf
                            <input type="hidden" name="id" value="{{ $giftCard->id }}">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Approve Gift Card</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="alert alert-info">
                                        <strong>Initial Value:</strong> {{ $giftCard->currency ?? '$' }}{{ number_format($giftCard->initial_value, 2) }}
                                    </div>
                                    <div class="mb-3">
                                        <label for="evaluated_value" class="form-label">Evaluated Value (₦)</label>
                                        <input type="number" step="0.01" class="form-control" id="evaluated_value" name="evaluated_value" value="{{ $giftCard->evaluated_value }}" required>
                                        <div class="form-text">Enter the amount to credit the user in Naira</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="approve_notes" class="form-label">Notes (Optional)</label>
                                        <textarea class="form-control" id="approve_notes" name="notes" rows="3">{{ $giftCard->notes }}</textarea>
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

                <!-- Reject Modal -->
                <div class="modal fade" id="rejectModal" tabindex="-1">
                    <div class="modal-dialog">
                        <form method="POST" action="{{ route('gift-cards.reject') }}">
                            @csrf
                            <input type="hidden" name="id" value="{{ $giftCard->id }}">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Reject Gift Card</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="alert alert-warning">
                                        <i class="uil-exclamation-triangle"></i> Are you sure you want to reject this gift card transaction?
                                    </div>
                                    <div class="mb-3">
                                        <label for="reject_notes" class="form-label">Reason for Rejection</label>
                                        <textarea class="form-control" id="reject_notes" name="notes" rows="3" required placeholder="Explain why this gift card is being rejected...">{{ $giftCard->notes }}</textarea>
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
        function copyToClipboard(elementId) {
            const copyText = document.getElementById(elementId);
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(copyText.value);
            alert('Code copied to clipboard!');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const rateInput = document.getElementById('rate');
            const amountInput = document.getElementById('amount');
            const calculatedValueInput = document.getElementById('calculated-value');

            function calculateEvaluatedValue() {
                const rate = parseFloat(rateInput.value) || 0;
                const amount = parseFloat(amountInput.value) || 0;
                const evaluatedValue = amount * rate;
                calculatedValueInput.value = evaluatedValue.toLocaleString('en-NG', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            calculateEvaluatedValue();
            rateInput.addEventListener('input', calculateEvaluatedValue);
            amountInput.addEventListener('input', calculateEvaluatedValue);
        });
    </script>

    <style>
        .timeline-alt {
            position: relative;
            padding-left: 30px;
        }
        .timeline-item {
            position: relative;
            padding-bottom: 20px;
        }
        .timeline-item:last-child {
            padding-bottom: 0;
        }
        .timeline-icon {
            position: absolute;
            left: -30px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }
        .timeline-item:not(:last-child)::before {
            content: '';
            position: absolute;
            left: -24px;
            top: 12px;
            bottom: -20px;
            width: 2px;
            background: #e3e6f0;
        }
    </style>

@endsection
