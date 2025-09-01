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
                                <h1 class="page-title text-dark">Payment Provider Settings</h1>
                                <p class="text-muted mb-0">Configure your default payment gateway</p>
                            </div>
                        </div>
                    </div>

                    <!-- Provider Settings -->
                    <div class="row justify-content-center">
                        <div class="col-lg-8 col-md-10">
                            <div class="card shadow-sm border-0">
                                <div class="card-header bg-primary text-white">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-credit-card fa-lg me-3"></i>
                                        <div>
                                            <h5 class="card-title mb-1">Default Payment Provider</h5>
                                            <p class="card-text mb-0 opacity-90">Choose your preferred payment gateway</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-body p-4">
                                    {{-- Success/Error Messages --}}
                                    @if(session('success'))
                                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                                            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                                    @elseif(session('error'))
                                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                                    @endif

                                    {{-- Current Provider Display --}}
                                    <div class="current-provider mb-4">
                                        <div class="bg-light rounded p-4 text-center">
                                            @php
                                                $currentProvider = \App\Models\Settings::get('preferred_provider', 'Not Set');
                                            @endphp
                                            <div class="mb-3">
                                                <span class="badge bg-info px-3 py-2 fs-6">Currently Active</span>
                                            </div>
                                            <div class="provider-display">
                                                @if($currentProvider === 'paystack')
                                                    <i class="fab fa-paypal text-primary fa-4x mb-3"></i>
                                                    <h4 class="text-primary fw-bold">Paystack</h4>
                                                    <p class="text-muted mb-0">Nigerian Payment Gateway</p>
                                                @elseif($currentProvider === 'nomba')
                                                    <i class="fas fa-university text-success fa-4x mb-3"></i>
                                                    <h4 class="text-success fw-bold">Nomba</h4>
                                                    <p class="text-muted mb-0">Banking & Payment Solutions</p>
                                                @else
                                                    <i class="fas fa-question-circle text-muted fa-4x mb-3"></i>
                                                    <h4 class="text-muted fw-bold">No Provider Set</h4>
                                                    <p class="text-muted mb-0">Please select a payment provider</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Provider Selection Form --}}
                                    <form action="{{ route('user.save-provider') }}" method="POST">
                                        @csrf

                                        <div class="mb-4">
                                            <label for="preferred_provider" class="form-label fw-semibold mb-3">
                                                <i class="fas fa-cog me-2"></i>Select Payment Provider
                                            </label>

                                            <div class="provider-options">
                                                <!-- Paystack Option -->
                                                <div class="form-check provider-option mb-3">
                                                    <input class="form-check-input" type="radio" name="preferred_provider"
                                                           id="paystack" value="paystack"
                                                        {{ \App\Models\Settings::get('preferred_provider') === 'paystack' ? 'checked' : '' }}>
                                                    <label class="form-check-label w-100" for="paystack">
                                                        <div class="d-flex align-items-center p-3 border rounded provider-card">
                                                            <div class="flex-shrink-0 me-3">
                                                                <i class="fab fa-paypal fa-2x text-primary"></i>
                                                            </div>
                                                            <div class="flex-grow-1">
                                                                <h6 class="mb-1 fw-bold">Paystack</h6>
                                                                <p class="text-muted mb-0 small">
                                                                    Leading payment gateway in Nigeria with support for cards, bank transfers, and mobile money
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </label>
                                                </div>

                                                <!-- Nomba Option -->
                                                <div class="form-check provider-option mb-3">
                                                    <input class="form-check-input" type="radio" name="preferred_provider"
                                                           id="nomba" value="nomba"
                                                        {{ \App\Models\Settings::get('preferred_provider') === 'nomba' ? 'checked' : '' }}>
                                                    <label class="form-check-label w-100" for="nomba">
                                                        <div class="d-flex align-items-center p-3 border rounded provider-card">
                                                            <div class="flex-shrink-0 me-3">
                                                                <i class="fas fa-university fa-2x text-success"></i>
                                                            </div>
                                                            <div class="flex-grow-1">
                                                                <h6 class="mb-1 fw-bold">Nomba</h6>
                                                                <p class="text-muted mb-0 small">
                                                                    Comprehensive banking and payment solutions with advanced financial services
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary btn-lg py-3">
                                                <i class="fas fa-save me-2"></i>Save Payment Provider
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <style>
        .provider-card {
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .provider-card:hover {
            background-color: #f8f9fa;
            border-color: #007bff !important;
        }

        .form-check-input:checked + .form-check-label .provider-card {
            background-color: #e3f2fd;
            border-color: #007bff !important;
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.2);
        }

        .provider-option {
            position: relative;
        }

        .provider-option .form-check-input {
            position: absolute;
            opacity: 0;
        }

        .card {
            border-radius: 10px;
        }

        .current-provider {
            position: relative;
        }

        .current-provider::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, #007bff, #28a745);
            border-radius: 8px;
            z-index: -1;
            opacity: 0.1;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add click handler for provider cards
            const providerCards = document.querySelectorAll('.provider-card');
            providerCards.forEach(card => {
                card.addEventListener('click', function() {
                    const radio = this.parentElement.querySelector('input[type="radio"]');
                    radio.checked = true;
                });
            });
        });
    </script>
@endsection
