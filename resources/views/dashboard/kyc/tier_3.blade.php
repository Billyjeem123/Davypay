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
                                <h1 class="page-title text-dark">KYC Management(Tier 3)</h1>
                            </div>
                        </div>
                    </div>


                    <!-- KYC Table -->
                    <div class="row mb-3 bg-white">
                        <div class="table-responsive">
                            <table id="sms-datatable" class="table table-hover dt-responsive nowrap w-100 table-centered mb-0">
                                <thead class="table-light">
                                <tr>
                                    <th class="fw-bold text-dark border-0 py-3">Name</th>
                                    <th class="fw-bold text-dark border-0 py-3">KYC Tier</th>
                                    <th class="fw-bold text-dark border-0 py-3">Verification Type</th>
                                    <th class="fw-bold text-dark border-0 py-3">Status</th>
                                    <th class="fw-bold text-dark border-0 py-3">Submitted</th>
                                    <th class="fw-bold text-dark border-0 py-3 text-end">Action</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($usersWithKyc as $user)
                                    <tr class="border-bottom">
                                        <td class="align-middle py-3">
                                            <span class="fw-bold text-dark">{{ $user->first_name }} {{ $user->last_name }}</span>
                                        </td>
                                        <td class="align-middle py-3">
                                            <span class="fw-medium text-dark">{{ ucfirst($user->kyc->tier ?? 'N/A') }}</span>
                                        </td>
                                        <td class="align-middle py-3">
                                            <span class="fw-medium text-dark">{{ strtoupper($user->kyc_type ?? 'none') }}</span>
                                        </td>
                                        <td class="align-middle py-3">
                    <span class="badge px-3 py-2 fw-bold
                        {{ $user->kyc->status === 'approved' ? 'bg-success' :
                           ($user->kyc->status === 'rejected' ? 'bg-danger' : 'bg-warning') }}">
                        {{ ucfirst($user->kyc->status) }}
                    </span>
                                        </td>
                                        <td class="align-middle py-3">
                                            <span class="text-muted">{{ \Carbon\Carbon::parse($user->kyc->created_at)->format('d/m/Y') }}</span>
                                        </td>
                                        <td class="align-middle py-3 text-end">
                                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#kycDetailModal{{ $user->id }}">
                                                <i class="uil-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @foreach($usersWithKyc as $user)
                        <div class="modal fade" id="kycDetailModal{{ $user->id }}" tabindex="-1" role="dialog">
                            <div class="modal-dialog modal-xl">
                                <div class="modal-content shadow-lg">
                                    <div class="modal-header bg-primary text-white">
                                        <h4 class="modal-title mb-0">
                                            <i class="fas fa-id-card me-2"></i>
                                            KYC Details for {{ $user->first_name }} {{ $user->last_name }}
                                        </h4>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body p-4">
                                        <!-- Status Badge -->
                                        <div class="d-flex justify-content-between align-items-center mb-4">
                                            <div>
                            <span class="badge bg-{{ $user->kyc->status === 'approved' ? 'success' : ($user->kyc->status === 'rejected' ? 'danger' : 'warning') }} fs-6">
                                {{ ucfirst($user->kyc->status ?? 'N/A') }}
                            </span>
                                                <span class="badge bg-info fs-6 ms-2">{{ ucfirst(str_replace('_', ' ', $user->kyc->tier ?? 'N/A')) }}</span>
                                                <span class="badge bg-secondary fs-6 ms-2">{{ $user->kyc->verification_type ?? 'N/A' }}</span>
                                            </div>
                                            <small class="text-muted">
                                                Last Updated: {{ $user->kyc->updated_at ? $user->kyc->updated_at->format('M d, Y H:i') : 'N/A' }}
                                            </small>
                                        </div>

                                        <div class="row g-4">
                                            <!-- Personal Information Section -->
                                            <div class="col-md-6">
                                                <div class="card border-0 bg-light">
                                                    <div class="card-header bg-secondary text-white">
                                                        <h6 class="mb-0"><i class="fas fa-user me-2"></i>Personal Information</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="row g-3">
                                                            <div class="col-sm-6">
                                                                <label class="form-label text-muted small">Phone Number</label>
                                                                <p class="fw-semibold">{{ $user->kyc->phone_number ?? 'N/A' }}</p>
                                                            </div>
                                                            <div class="col-sm-6">
                                                                <label class="form-label text-muted small">Date of Birth</label>
                                                                <p class="fw-semibold">{{ $user->kyc->dob ?? 'N/A' }}</p>
                                                            </div>
                                                            <div class="col-sm-6">
                                                                <label class="form-label text-muted small">BVN</label>
                                                                <p class="fw-semibold">{{ $user->kyc->bvn ?? 'N/A' }}</p>
                                                            </div>
                                                            <div class="col-sm-6">
                                                                <label class="form-label text-muted small">NIN</label>
                                                                <p class="fw-semibold">{{ $user->kyc->nin ?? 'N/A' }}</p>
                                                            </div>
                                                            <div class="col-12">
                                                                <label class="form-label text-muted small">Address</label>
                                                                <p class="fw-semibold">{{ $user->kyc->address ?? 'N/A' }}</p>
                                                            </div>
                                                            <div class="col-sm-6">
                                                                <label class="form-label text-muted small">Zip Code</label>
                                                                <p class="fw-semibold">{{ $user->kyc->zipcode ?? 'N/A' }}</p>
                                                            </div>
                                                            <div class="col-sm-6">
                                                                <label class="form-label text-muted small">Nationality</label>
                                                                <p class="fw-semibold">{{ $user->kyc->nationality ?? 'N/A' }}</p>
                                                            </div>
                                                            <div class="col-sm-6">
                                                                <label class="form-label text-muted small">Middle Name</label>
                                                                <p class="fw-semibold">{{ $user->kyc->middle_name ?? 'N/A' }}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Document Information Section -->
                                            <div class="col-md-6">
                                                <div class="card border-0 bg-light">
                                                    <div class="card-header bg-info text-white">
                                                        <h6 class="mb-0"><i class="fas fa-id-badge me-2"></i>Document Information</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="row g-3">
                                                            <div class="col-sm-6">
                                                                <label class="form-label text-muted small">Document Type</label>
                                                                <p class="fw-semibold">{{ $user->kyc->document_type ?? 'N/A' }}</p>
                                                            </div>
                                                            <div class="col-sm-6">
                                                                <label class="form-label text-muted small">Document Number</label>
                                                                <p class="fw-semibold">{{ $user->kyc->document_number ?? 'N/A' }}</p>
                                                            </div>
                                                            <div class="col-sm-6">
                                                                <label class="form-label text-muted small">Date Issued</label>
                                                                <p class="fw-semibold">{{ $user->kyc->date_issued ?? 'N/A' }}</p>
                                                            </div>
                                                            <div class="col-sm-6">
                                                                <label class="form-label text-muted small">Expiry Date</label>
                                                                <p class="fw-semibold">{{ $user->kyc->expiry_date ?? 'N/A' }}</p>
                                                            </div>
                                                            <div class="col-12">
                                                                <label class="form-label text-muted small">Reference ID</label>
                                                                <p class="fw-semibold small">{{ $user->kyc->reference_id ?? 'N/A' }}</p>
                                                            </div>
                                                            <div class="col-12">
                                                                <label class="form-label text-muted small">Widget ID</label>
                                                                <p class="fw-semibold small">{{ $user->kyc->widget_id ?? 'N/A' }}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Verification Status Section -->
                                            <div class="col-12">
                                                <div class="card border-0 bg-light">
                                                    <div class="card-header bg-warning text-dark">
                                                        <h6 class="mb-0"><i class="fas fa-check-circle me-2"></i>Verification Status</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="row g-3">
                                                            <div class="col-md-3">
                                                                <label class="form-label text-muted small">Verification Mode</label>
                                                                <p class="fw-semibold">{{ $user->kyc->verification_mode ?? 'N/A' }}</p>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <label class="form-label text-muted small">Verification Type</label>
                                                                <p class="fw-semibold">{{ $user->kyc->verification_type ?? 'N/A' }}</p>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <label class="form-label text-muted small">AML Status</label>
                                                                <span class="badge bg-{{ $user->kyc->aml_status ? 'success' : 'danger' }}">
                                                {{ $user->kyc->aml_status ? 'Passed' : 'Failed' }}
                                            </span>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <label class="form-label text-muted small">Address Verification</label>
                                                                <span class="badge bg-{{ $user->kyc->address_verification_status ? 'success' : 'danger' }}">
                                                {{ $user->kyc->address_verification_status ? 'Verified' : 'Failed' }}
                                            </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Location Information Section -->
                                            <div class="col-md-6">
                                                <div class="card border-0 bg-light">
                                                    <div class="card-header bg-success text-white">
                                                        <h6 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Location Information</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="row g-3">
                                                            <div class="col-12">
                                                                <label class="form-label text-muted small">User Location</label>
                                                                <p class="fw-semibold">{{ $user->kyc->user_location_name ?? 'N/A' }}</p>
                                                            </div>
                                                            <div class="col-sm-6">
                                                                <label class="form-label text-muted small">User Latitude</label>
                                                                <p class="fw-semibold">{{ $user->kyc->user_latitude ?? 'N/A' }}</p>
                                                            </div>
                                                            <div class="col-sm-6">
                                                                <label class="form-label text-muted small">User Longitude</label>
                                                                <p class="fw-semibold">{{ $user->kyc->user_longitude ?? 'N/A' }}</p>
                                                            </div>
                                                            <div class="col-sm-6">
                                                                <label class="form-label text-muted small">Address Latitude</label>
                                                                <p class="fw-semibold">{{ $user->kyc->address_latitude ?? 'N/A' }}</p>
                                                            </div>
                                                            <div class="col-sm-6">
                                                                <label class="form-label text-muted small">Address Longitude</label>
                                                                <p class="fw-semibold">{{ $user->kyc->address_longitude ?? 'N/A' }}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Images & Links Section -->
                                            <div class="col-md-6">
                                                <div class="card border-0 bg-light">
                                                    <div class="card-header bg-dark text-white">
                                                        <h6 class="mb-0"><i class="fas fa-images me-2"></i>Images & Verification Links</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="row g-3">
                                                            @if($user->kyc->id_image_url)
                                                                <div class="col-12">
                                                                    <label class="form-label text-muted small">ID Image</label>
                                                                    <div>
                                                                        <a href="{{ $user->kyc->id_image_url }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                            <i class="fas fa-external-link-alt me-1"></i> View ID Image
                                                                        </a>
                                                                    </div>
                                                                </div>
                                                            @endif

                                                            @if($user->kyc->selfie)
                                                                <div class="col-12">
                                                                    <label class="form-label text-muted small">Selfie Image</label>
                                                                    <div>
                                                                        <a href="{{ $user->kyc->selfie }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                            <i class="fas fa-external-link-alt me-1"></i> View Selfie
                                                                        </a>
                                                                    </div>
                                                                </div>
                                                            @endif

                                                            @if($user->kyc->verification_image)
                                                                <div class="col-12">
                                                                    <label class="form-label text-muted small">Verification Link</label>
                                                                    <div>
                                                                        <a href="{{ $user->kyc->verification_image }}" target="_blank" class="btn btn-sm btn-outline-success">
                                                                            <i class="fas fa-external-link-alt me-1"></i> View Verification
                                                                        </a>
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Webhook Data Section -->
                                            @if($user->kyc->webhook)
                                                <div class="col-12">
                                                    <div class="card border-0 bg-light">
                                                        <div class="card-header bg-primary text-white">
                                                            <h6 class="mb-0"><i class="fas fa-code me-2"></i>Webhook Verification Details</h6>
                                                        </div>
                                                        <div class="card-body">
                                                            @php
                                                                $webhook = json_decode(json_encode($user->kyc->webhook), true);
                                                            @endphp

                                                                <!-- ID Verification -->
                                                            @if(isset($webhook['data']['id']))
                                                                <div class="mb-4">
                                                                    <h6 class="text-primary">ID Verification</h6>
                                                                    <div class="row g-3">
                                                                        <div class="col-md-3">
                                                                            <label class="form-label text-muted small">Status</label>
                                                                            <span class="badge bg-{{ $webhook['data']['id']['status'] ? 'success' : 'danger' }}">
                                                            {{ $webhook['data']['id']['status'] ? 'Verified' : 'Failed' }}
                                                        </span>
                                                                        </div>
                                                                        <div class="col-md-9">
                                                                            <label class="form-label text-muted small">Message</label>
                                                                            <p class="fw-semibold">{{ $webhook['data']['id']['message'] ?? 'N/A' }}</p>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endif

                                                            <!-- Email Verification -->
                                                            @if(isset($webhook['data']['email']))
                                                                <div class="mb-4">
                                                                    <h6 class="text-info">Email Verification</h6>
                                                                    <div class="row g-3">
                                                                        <div class="col-md-3">
                                                                            <label class="form-label text-muted small">Status</label>
                                                                            <span class="badge bg-{{ $webhook['data']['email']['status'] ? 'success' : 'danger' }}">
                                                            {{ $webhook['data']['email']['status'] ? 'Verified' : 'Failed' }}
                                                        </span>
                                                                        </div>
                                                                        <div class="col-md-5">
                                                                            <label class="form-label text-muted small">Email</label>
                                                                            <p class="fw-semibold">{{ $webhook['data']['email']['data']['email'] ?? 'N/A' }}</p>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label class="form-label text-muted small">Message</label>
                                                                            <p class="fw-semibold small">{{ $webhook['data']['email']['message'] ?? 'N/A' }}</p>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endif

                                                            <!-- Address Verification -->
                                                            @if(isset($webhook['data']['address']))
                                                                <div class="mb-4">
                                                                    <h6 class="text-warning">Address Verification</h6>
                                                                    <div class="row g-3">
                                                                        <div class="col-md-3">
                                                                            <label class="form-label text-muted small">Status</label>
                                                                            <span class="badge bg-{{ $webhook['data']['address']['status'] ? 'success' : 'danger' }}">
                                                            {{ $webhook['data']['address']['status'] ? 'Verified' : 'Failed' }}
                                                        </span>
                                                                        </div>
                                                                        <div class="col-md-9">
                                                                            <label class="form-label text-muted small">Message</label>
                                                                            <p class="fw-semibold">{{ $webhook['data']['address']['message'] ?? 'N/A' }}</p>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endif

                                                            <!-- AML Check -->
                                                            @if(isset($webhook['aml']))
                                                                <div class="mb-4">
                                                                    <h6 class="text-danger">AML Check</h6>
                                                                    <div class="row g-3">
                                                                        <div class="col-md-3">
                                                                            <label class="form-label text-muted small">Status</label>
                                                                            <span class="badge bg-{{ $webhook['aml']['status'] ? 'success' : 'danger' }}">
                                                            {{ $webhook['aml']['status'] ? 'Passed' : 'Failed' }}
                                                        </span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endif

                                                            <!-- Metadata -->
                                                            @if(isset($webhook['metadata']))
                                                                <div class="mb-3">
                                                                    <h6 class="text-secondary">Device & Location Info</h6>
                                                                    <div class="row g-3">
                                                                        @if(isset($webhook['metadata']['ipinfo']))
                                                                            <div class="col-md-6">
                                                                                <label class="form-label text-muted small">IP Location</label>
                                                                                <p class="fw-semibold small">
                                                                                    {{ $webhook['metadata']['ipinfo']['city'] ?? '' }},
                                                                                    {{ $webhook['metadata']['ipinfo']['region_name'] ?? '' }},
                                                                                    {{ $webhook['metadata']['ipinfo']['country'] ?? '' }}
                                                                                </p>
                                                                            </div>
                                                                            <div class="col-md-6">
                                                                                <label class="form-label text-muted small">ISP</label>
                                                                                <p class="fw-semibold small">{{ $webhook['metadata']['ipinfo']['isp'] ?? 'N/A' }}</p>
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            @endif


                                                            {{-- Webhook JSON Column --}}
                                                            <div class="col-md-6">
                                                                <div class="card border-0 bg-light">
                                                                    <div class="card-header bg-info text-white">
                                                                        <h6 class="mb-0"><i class="fas fa-code me-2"></i>Webhook JSON</h6>
                                                                    </div>
                                                                    <div class="card-body">
                                                                        @php
                                                                            $webhookData = is_array($user->kyc->webhook)
                                                                                ? $user->kyc->webhook
                                                                                : json_decode($user->kyc->webhook, true);
                                                                        @endphp

                                                                        @if($webhookData)
                                                                            <pre class="bg-white p-3 rounded small" style="max-height: 500px; overflow-y: auto;">
{{ json_encode($webhookData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}
                    </pre>
                                                                        @else
                                                                            <p class="text-danger mb-0">No webhook data available.</p>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="modal-footer bg-light">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        @if($user->kyc->status === 'pending')
                                            <button type="button" class="btn btn-success me-2">Approve</button>
                                            <button type="button" class="btn btn-danger">Reject</button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
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

        .border-left-primary { border-left: 4px solid #007bff !important; }
        .border-left-warning { border-left: 4px solid #ffc107 !important; }
        .border-left-success { border-left: 4px solid #28a745 !important; }
        .border-left-danger { border-left: 4px solid #dc3545 !important; }
        .text-xs { font-size: 0.75rem; }
        .text-gray-800 { color: #5a5c69 !important; }
    </style>

@endsection
