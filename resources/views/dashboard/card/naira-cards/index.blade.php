@extends('dashboard.layout.sms')

@section('content')

    <div class="wrapper">
        <div class="content-page sms-page">
            <div class="content">
                <div class="container-fluid">
                    <!-- Page Title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <h4 class="page-title">Naira Cards Management</h4>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row">
                        <!-- Total Cards -->
                        <div class="col-md-6 col-xl-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h5 class="text-muted fw-normal mt-0 mb-0">Total Cards</h5>
                                        <i class="ri-bank-card-line text-primary" style="font-size: 1.5rem;"></i>
                                    </div>
                                    <h2 class="mt-2 mb-3">{{ number_format($stats['total']) }}</h2>
                                </div>
                            </div>
                        </div>

                        <!-- Card Status (Active/Inactive) -->
                        <div class="col-md-6 col-xl-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h5 class="text-muted fw-normal mt-0 mb-0">Card Status</h5>
                                        <i class="ri-toggle-line text-info" style="font-size: 1.5rem;"></i>
                                    </div>
                                    <div class="mt-2">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div class="d-flex align-items-center">
                                                <i class="ri-checkbox-circle-fill text-success me-2"></i>
                                                <span class="text-muted">Active</span>
                                            </div>
                                            <h4 class="mb-0">{{ number_format($stats['active']) }}</h4>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center">
                                                <i class="ri-close-circle-fill text-danger me-2"></i>
                                                <span class="text-muted">Inactive</span>
                                            </div>
                                            <h4 class="mb-0">{{ number_format($stats['inactive']) }}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card Type (Physical/Virtual) -->
                        <div class="col-md-6 col-xl-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h5 class="text-muted fw-normal mt-0 mb-0">Card Type</h5>
                                        <i class="ri-bank-card-2-line text-warning" style="font-size: 1.5rem;"></i>
                                    </div>
                                    <div class="mt-2">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div class="d-flex align-items-center">
                                                <i class="ri-bank-card-2-fill text-primary me-2"></i>
                                                <span class="text-muted">Physical</span>
                                            </div>
                                            <h4 class="mb-0">{{ number_format($stats['physical']) }}</h4>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center">
                                                <i class="ri-smartphone-line text-info me-2"></i>
                                                <span class="text-muted">Virtual</span>
                                            </div>
                                            <h4 class="mb-0">{{ number_format($stats['virtual']) }}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Delivery Status -->
                        <div class="col-md-6 col-xl-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h5 class="text-muted fw-normal mt-0 mb-0">Delivery Status</h5>
                                        <i class="ri-truck-line text-success" style="font-size: 1.5rem;"></i>
                                    </div>
                                    <div class="mt-2">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-warning me-2" style="width: 8px; height: 8px; padding: 0;"></span>
                                                <small class="text-muted">Pending</small>
                                            </div>
                                            <span class="fw-semibold">{{ number_format($stats['pending']) }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-info me-2" style="width: 8px; height: 8px; padding: 0;"></span>
                                                <small class="text-muted">Processing</small>
                                            </div>
                                            <span class="fw-semibold">{{ number_format($stats['processing']) }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-success me-2" style="width: 8px; height: 8px; padding: 0;"></span>
                                                <small class="text-muted">Delivered</small>
                                            </div>
                                            <span class="fw-semibold">{{ number_format($stats['delivered']) }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-danger me-2" style="width: 8px; height: 8px; padding: 0;"></span>
                                                <small class="text-muted">Failed</small>
                                            </div>
                                            <span class="fw-semibold">{{ number_format($stats['failed']) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters and Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <!-- Filters -->
                                    <form method="GET" action="{{ route('naira-cards.index') }}" class="row g-3 mb-3">
                                        <div class="col-md-3">
                                            <input type="text" name="search" class="form-control"
                                                   placeholder="Search by card ID, user..."
                                                   value="{{ request('search') }}">
                                        </div>
                                        <div class="col-md-2">
                                            <select name="status" class="form-select">
                                                <option value="">All Status</option>
                                                <option
                                                    value="active" {{ request('status') == 'active' ? 'selected' : '' }}>
                                                    Active
                                                </option>
                                                <option
                                                    value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>
                                                    Inactive
                                                </option>
                                                <option
                                                    value="created_user" {{ request('status') == 'created_user' ? 'selected' : '' }}>
                                                    Created User
                                                </option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <select name="type" class="form-select">
                                                <option value="">All Types</option>
                                                <option
                                                    value="physical" {{ request('type') == 'physical' ? 'selected' : '' }}>
                                                    Physical
                                                </option>
                                                <option
                                                    value="virtual" {{ request('type') == 'virtual' ? 'selected' : '' }}>
                                                    Virtual
                                                </option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <select name="brand" class="form-select">
                                                <option value="">All Brands</option>
                                                <option
                                                    value="Verve" {{ request('brand') == 'Verve' ? 'selected' : '' }}>
                                                    Verve
                                                </option>
                                                <option
                                                    value="AfriGo" {{ request('brand') == 'AfriGo' ? 'selected' : '' }}>
                                                    AfriGo
                                                </option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <select name="card_status" class="form-select">
                                                <option value="">All Delivery Status</option>
                                                <option
                                                    value="pending" {{ request('card_status') == 'pending' ? 'selected' : '' }}>
                                                    Pending
                                                </option>
                                                <option
                                                    value="processing" {{ request('card_status') == 'processing' ? 'selected' : '' }}>
                                                    Processing
                                                </option>
                                                <option
                                                    value="delivered" {{ request('card_status') == 'delivered' ? 'selected' : '' }}>
                                                    Delivered
                                                </option>
                                                <option
                                                    value="failed" {{ request('card_status') == 'failed' ? 'selected' : '' }}>
                                                    Failed
                                                </option>
                                            </select>
                                        </div>

                                        <div class="col-md-3">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="ri-search-line"></i> Filter
                                            </button>
                                            <a href="{{ route('naira-cards.index') }}" class="btn btn-secondary">
                                                <i class="ri-refresh-line"></i> Reset
                                            </a>
                                        </div>
                                    </form>

                                    <!-- Table -->
                                    <div class="table-responsive">
                                        <table class="table table-hover table-centered mb-0">
                                            <thead class="table-light">
                                            <tr>
                                                <th>User</th>
                                                <th>Card ID</th>
                                                <th>Masked Pan</th>
                                                <th>Type</th>
                                                <th>Brand</th>
                                                <th>Status</th>
                                                <th>Delivery Status</th>
                                                <th>Expiration</th>
                                                <th>Created</th>
                                                <th>Actions</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @forelse($cards as $card)
                                                <tr>
                                                    <td>
                                                        <div>
                                                            <strong>{{ $card->firstname }} {{ $card->lastname }}</strong><br>
                                                            <small class="text-muted">{{ $card->email }}</small>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <code>{{ $card->card_id ?? 'N/A' }}</code>
                                                    </td>
                                                    <td>{{ $card->mask ?? 'N/A' }}</td>
                                                    <td>
                                        <span class="badge bg-{{ $card->type == 'physical' ? 'primary' : 'info' }}">
                                            {{ ucfirst($card->type ?? 'N/A') }}
                                        </span>
                                                    </td>
                                                    <td>{{ $card->brand ?? 'N/A' }}</td>
                                                    <td>
                                        <span
                                            class="badge bg-{{ $card->status == 'active' ? 'success' : ($card->status == 'inactive' ? 'danger' : 'warning') }}">
                                            {{ ucfirst($card->status) }}
                                        </span>
                                                    </td>
                                                    <td>
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
        {{ ucfirst($card->card_status ?? 'N/A') }}
    </span>
                                                    </td>

                                                    <td>{{ $card->expiration ?? 'N/A' }}</td>
                                                    <td>{{ $card->created_at->format('M d, Y') }}</td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <a href="{{ route('naira-cards.show', $card->id) }}"
                                                               class="btn btn-sm btn-info" title="View Details">
                                                                <i class="ri-eye-line"></i>
                                                            </a>

                                                            @if($card->card_id)
                                                                @if($card->status == 'active')
                                                                    <button
                                                                        onclick="updateCardStatus({{ $card->id }}, 'inactive')"
                                                                        class="btn btn-sm btn-warning"
                                                                        title="Deactivate">
                                                                        <i class="ri-pause-circle-line"></i>
                                                                    </button>
                                                                @else
                                                                    <button
                                                                        onclick="updateCardStatus({{ $card->id }}, 'active')"
                                                                        class="btn btn-sm btn-success" title="Activate">
                                                                        <i class="ri-play-circle-line"></i>
                                                                    </button>
                                                                @endif
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="9" class="text-center py-4">
                                                        <i class="ri-inbox-line"
                                                           style="font-size: 3rem; color: #ccc;"></i>
                                                        <p class="text-muted mt-2">No cards found</p>
                                                    </td>
                                                </tr>
                                            @endforelse
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Pagination -->
                                    <div class="mt-3">
                                        {{ $cards->links() }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @push('scripts')
                    <script>
                        function updateCardStatus(cardId, status) {
                            if (!confirm(`Are you sure you want to ${status === 'active' ? 'activate' : 'deactivate'} this card?`)) {
                                return;
                            }

                            fetch(`/admin/naira-cards/${cardId}/update-status`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({status: status})
                            })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        alert(data.message);
                                        location.reload();
                                    } else {
                                        alert('Error: ' + data.message);
                                    }
                                })
                                .catch(error => {
                                    alert('Error updating card status');
                                    console.error(error);
                                });
                        }
                    </script>
    @endpush
@endsection
