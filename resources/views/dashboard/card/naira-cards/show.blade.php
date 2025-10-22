@extends('dashboard.layout.sms')

@section('content')
    <div class="wrapper">
        <div class="content-page">
            <div class="content">
                <div class="container-fluid">
                    <!-- Page Title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <div class="page-title-right">
                                    <a href="{{ route('naira-cards.index') }}" class="btn btn-secondary">
                                        <i class="ri-arrow-left-line"></i> Back to Cards
                                    </a>
                                </div>
                                <h4 class="page-title">Card Details - {{ $card->mask ?? 'N/A' }}</h4>
                            </div>
                        </div>
                    </div>

                    <!-- Alert Messages -->
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-lg-8">
                            <!-- Card Information -->
                            <div class="card">
                                <div class="card-header bg-light">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="card-title mb-0">
                                            <i class="ri-bank-card-line me-2"></i>Card Information
                                        </h5>
                                        <div>
                                <span class="badge bg-{{ $card->type == 'physical' ? 'primary' : 'info' }} me-2">
                                    {{ ucfirst($card->type ?? 'N/A') }}
                                </span>
                                            <span
                                                class="badge bg-{{ $card->status == 'active' ? 'success' : ($card->status == 'inactive' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($card->status ?? 'N/A') }}
                                </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <table class="table table-borderless table-sm">
                                                <tr>
                                                    <th width="140">Card ID:</th>
                                                    <td><code>{{ $card->card_id ?? 'N/A' }}</code></td>
                                                </tr>
                                                <tr>
                                                    <th>Customer ID:</th>
                                                    <td><code>{{ $card->customer_id ?? 'N/A' }}</code></td>
                                                </tr>
                                                <tr>
                                                    <th>Masked PAN:</th>
                                                    <td><strong>{{ $card->mask ?? 'N/A' }}</strong></td>
                                                </tr>
                                                <tr>
                                                    <th>Brand:</th>
                                                    <td>{{ $card->brand ?? 'N/A' }}</td>
                                                </tr>
                                            </table>
                                        </div>
                                        <div class="col-md-6">
                                            <table class="table table-borderless table-sm">
                                                <tr>
                                                    <th width="140">Expiration:</th>
                                                    <td>{{ $card->expiration ?? 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Balance:</th>
                                                    <td>₦{{ number_format($card->balance ?? 0, 2) }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Created:</th>
                                                    <td>{{ $card->created_at->format('M d, Y H:i A') }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Last Updated:</th>
                                                    <td>{{ $card->updated_at->format('M d, Y H:i A') }}</td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>

                                    <div class="mt-3 pt-3 border-top">
                                        <strong class="text-muted">Quick Actions:</strong>
                                        <div class="mt-2">
                                            @if($card->card_id)
                                                <form action="{{ route('naira-cards.card.update-status', $card->id) }}" method="POST" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to {{ $card->status == 'active' ? 'deactivate' : 'activate' }} this card?')">
                                                    @csrf
                                                    @if($card->status == 'active')
                                                        <input type="hidden" name="status" value="inactive">
                                                        <button type="submit" class="btn btn-warning btn-sm">
                                                            <i class="ri-pause-circle-line"></i> Deactivate Card
                                                        </button>
                                                    @elseif($card->status == 'inactive')
                                                        <input type="hidden" name="status" value="active">
                                                        <button type="submit" class="btn btn-success btn-sm">
                                                            <i class="ri-play-circle-line"></i> Activate Card
                                                        </button>
                                                    @endif
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Recent Transactions -->
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h5 class="card-title mb-0">
                                        <i class="ri-exchange-line me-2"></i>Recent Transactions
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover table-sm">
                                            <thead class="table-light">
                                            <tr>
                                                <th>Transaction ID</th>
                                                <th>Merchant</th>
                                                <th>Amount</th>
                                                <th>Fee</th>
                                                <th>Type</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @forelse($transactions as $txn)
                                                <tr>
                                                    <td><code class="small">{{ $txn->transaction_id }}</code></td>
                                                    <td>{{ $txn->merchant_name ?? 'N/A' }}</td>
                                                    <td><strong>₦{{ number_format($txn->amount, 2) }}</strong></td>
                                                    <td>₦{{ number_format($txn->fee, 2) }}</td>
                                                    <td>
                                            <span class="badge bg-{{ $txn->type == 'refund' ? 'info' : 'primary' }}">
                                                {{ ucfirst($txn->type) }}
                                            </span>
                                                    </td>
                                                    <td>
                                            <span
                                                class="badge bg-{{ $txn->status == 'completed' ? 'success' : 'warning' }}">
                                                {{ ucfirst($txn->status) }}
                                            </span>
                                                    </td>
                                                    <td>{{ $txn->created_at->format('M d, Y H:i') }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="7" class="text-center py-4">
                                                        <i class="ri-inbox-line text-muted"
                                                           style="font-size: 2rem;"></i>
                                                        <p class="text-muted mb-0 mt-2">No transactions found</p>
                                                    </td>
                                                </tr>
                                            @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                    @if($transactions->hasPages())
                                        <div class="mt-3">
                                            {{ $transactions->links() }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-lg-4">
                            <!-- User Information -->
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h5 class="card-title mb-0">
                                        <i class="ri-user-line me-2"></i>User Information
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="text-center mb-3 pb-3 border-bottom">
                                        <div class="avatar-lg mx-auto mb-2">
                                <span class="avatar-title rounded-circle bg-soft-primary text-primary font-20">
                                    {{ strtoupper(substr($card->firstname, 0, 1) . substr($card->lastname, 0, 1)) }}
                                </span>
                                        </div>
                                        <h5 class="mb-1">{{ $card->firstname }} {{ $card->lastname }}</h5>
                                        <p class="text-muted mb-0"><small>{{ $card->email }}</small></p>
                                    </div>

                                    <table class="table table-borderless table-sm">
                                        <tr>
                                            <th width="100"><i class="ri-phone-line me-1"></i>Phone:</th>
                                            <td>{{ $card->phone }}</td>
                                        </tr>
                                        <tr>
                                            <th><i class="ri-user-line me-1"></i>User ID:</th>
                                            <td><code>{{ $card->user_id ?? 'N/A' }}</code></td>
                                        </tr>
                                        @if($card->provider_user_id)
                                            <tr>
                                                <th><i class="ri-shield-user-line me-1"></i>Provider ID:</th>
                                                <td><code>{{ $card->provider_user_id }}</code></td>
                                            </tr>
                                        @endif
                                    </table>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header bg-light">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="card-title mb-0">
                                            <i class="ri-truck-line me-2"></i>Delivery Details
                                        </h5>
                                        @php
                                            $deliveryColors = [
                                                'pending' => 'warning',
                                                'processing' => 'info',
                                                'delivered' => 'success',
                                                'failed' => 'danger',
                                            ];
                                            $color = $deliveryColors[$card->card_status] ?? 'secondary';
                                        @endphp
                                        <span class="badge bg-{{ $color }}">
                                {{ ucfirst($card->card_status ?? 'Pending') }}
                            </span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless table-sm">
                                        <tr>
                                            <th width="100"><i class="ri-home-line me-1"></i>Address:</th>
                                            <td>{{ $card->line ?? 'N/A' }}</td>
                                        </tr>
                                        @if($card->house_no)
                                            <tr>
                                                <th>House No:</th>
                                                <td>{{ $card->house_no }}</td>
                                            </tr>
                                        @endif
                                        @if($card->nearest_bus_stop)
                                            <tr>
                                                <th><i class="ri-map-pin-line me-1"></i>Bus Stop:</th>
                                                <td>{{ $card->nearest_bus_stop }}</td>
                                            </tr>
                                        @endif
                                        <tr>
                                            <th><i class="ri-building-line me-1"></i>City:</th>
                                            <td>{{ $card->city ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th><i class="ri-map-line me-1"></i>State:</th>
                                            <td>{{ $card->state ?? 'N/A' }}</td>
                                        </tr>
                                        @if($card->postcode)
                                            <tr>
                                                <th>Postcode:</th>
                                                <td>{{ $card->postcode }}</td>
                                            </tr>
                                        @endif
                                    </table>

                                    @if($card->type == 'physical')
                                        <div class="mt-3 pt-3 border-top">
                                            <label class="form-label fw-bold">Update Delivery Status</label>
                                            <form action="{{ route('naira-cards.delivery.update-status', $card->id) }}"
                                                  method="POST">
                                                @csrf
                                                <div class="input-group">
                                                    <select name="status" class="form-select" required>
                                                        <option value="">Select Status</option>
                                                        <option
                                                            value="processing" {{ $card->card_status == 'processing' ? 'selected' : '' }}>
                                                            Processing
                                                        </option>
                                                        <option
                                                            value="delivered" {{ $card->card_status == 'delivered' ? 'selected' : '' }}>
                                                            Delivered
                                                        </option>
                                                        <option
                                                            value="failed" {{ $card->card_status == 'failed' ? 'selected' : '' }}>
                                                            Failed
                                                        </option>
                                                    </select>
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="ri-refresh-line"></i> Update
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    @else
                                        <div class="alert alert-info mt-3 mb-0">
                                            <small><i class="ri-information-line me-1"></i>Delivery tracking is not
                                                applicable for virtual cards</small>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            @if($card->api_response && is_array($card->api_response))
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h5 class="card-title mb-0">
                                            <i class="ri-server-line me-2"></i>Provider Information
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="accordion accordion-flush" id="providerAccordion">
                                            @foreach($card->api_response as $key => $value)
                                                <div class="accordion-item border-bottom">
                                                    <h2 class="accordion-header">
                                                        <button class="accordion-button collapsed p-2" type="button"
                                                                data-bs-toggle="collapse"
                                                                data-bs-target="#collapse{{ $loop->index }}">
                                                            <small><strong>{{ ucwords(str_replace('_', ' ', $key)) }}</strong></small>
                                                        </button>
                                                    </h2>
                                                    <div id="collapse{{ $loop->index }}"
                                                         class="accordion-collapse collapse"
                                                         data-bs-parent="#providerAccordion">
                                                        <div class="accordion-body p-2">
                                                            @if(is_array($value))
                                                                <pre
                                                                    class="mb-0 small bg-light p-2 rounded">{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>
                                                            @else
                                                                <code class="small">{{ $value }}</code>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            @push('scripts')
                <script>
                    setTimeout(function () {
                        const alerts = document.querySelectorAll('.alert');
                        alerts.forEach(alert => {
                            const bsAlert = new bootstrap.Alert(alert);
                            bsAlert.close();
                        });
                    }, 5000);
                </script>
    @endpush
@endsection
