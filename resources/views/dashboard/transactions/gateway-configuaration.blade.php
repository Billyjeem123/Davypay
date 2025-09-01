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
                                <h1 class="page-title text-dark">Transaction Settings</h1>
                                <p class="text-muted">Configure payment gateways, fees, and transaction rules</p>
                            </div>
                        </div>
                        <div class="col-md-6 py-2 text-end">
                            <a href="{{route('transaction.fee')}}" class="btn btn-sm btn-info mb-2">
                                <i class="uil-wallet"></i> Back
                            </a>
                            <a href="{{ route('admin.home') }}" class="btn btn-sm btn-secondary mb-2">
                                <i class="uil-dashboard"></i> Back to home
                            </a>
                        </div>
                    </div>


                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-light border-0">
                                    <h4 class="card-title mb-1">
                                        <i class="uil-setting text-warning me-2"></i>Gateway Configuration
                                    </h4>
                                    <p class="text-muted mb-0">Configure payment gateway settings and API keys</p>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <!-- Paystack Config -->
                                        <div class="col-lg-6 mb-4">
                                            <div class="border rounded p-4">
                                                <h5 class="text-primary mb-3">
                                                    <i class="uil-credit-card me-2"></i>Paystack Configuration
                                                </h5>
                                                <form id="paystackConfigForm">
                                                    @csrf
                                                    <div class="mb-3">
                                                        <label class="form-label">Public Key</label>
                                                        <input type="text" class="form-control" name="paystack_public_key"
                                                               placeholder="pk_test_..." value="{{ config('paystack.public_key') }}">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Secret Key</label>
                                                        <div class="input-group">
                                                            <input type="password" class="form-control" name="paystack_secret_key"
                                                                   placeholder="sk_test_..." value="{{ config('paystack.secret_key') }}">
                                                            <button type="button" class="btn btn-outline-secondary" onclick="togglePassword(this)">
                                                                <i class="uil-eye"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Environment</label>
                                                        <select class="form-select" name="paystack_environment">
                                                            <option value="test">Test Mode</option>
                                                            <option value="live">Live Mode</option>
                                                        </select>
                                                    </div>
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="uil-save"></i> Update Paystack
                                                    </button>
                                                </form>
                                            </div>
                                        </div>

                                        <!-- Nomba Config -->
                                        <div class="col-lg-6 mb-4">
                                            <div class="border rounded p-4">
                                                <h5 class="text-info mb-3">
                                                    <i class="uil-mobile-android me-2"></i>Nomba Configuration
                                                </h5>
                                                <form id="nombaConfigForm">
                                                    @csrf
                                                    <div class="mb-3">
                                                        <label class="form-label">API Key</label>
                                                        <input type="text" class="form-control" name="nomba_api_key"
                                                               placeholder="nmb_..." value="{{ config('nomba.api_key') }}">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Secret Key</label>
                                                        <div class="input-group">
                                                            <input type="password" class="form-control" name="nomba_secret_key"
                                                                   placeholder="sk_..." value="{{ config('nomba.secret_key') }}">
                                                            <button type="button" class="btn btn-outline-secondary" onclick="togglePassword(this)">
                                                                <i class="uil-eye"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Environment</label>
                                                        <select class="form-select" name="nomba_environment">
                                                            <option value="sandbox">Sandbox</option>
                                                            <option value="production">Production</option>
                                                        </select>
                                                    </div>
                                                    <button type="submit" class="btn btn-info">
                                                        <i class="uil-save"></i> Update Nomba
                                                    </button>
                                                </form>
                                            </div>
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



        function togglePassword(button) {
            const input = button.previousElementSibling;
            const icon = button.querySelector('i');

            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'uil-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'uil-eye';
            }
        }



    </script>

    <style>
        .sms-page {
        }

        .card {
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
        }

        .card-header {
            border-radius: 12px 12px 0 0 !important;
        }

        .fee-range-item {
            transition: all 0.3s ease;
            background: #f8f9fa !important;
        }

        .fee-range-item:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .form-control, .form-select {
            border-radius: 8px;
        }

        .btn {
            border-radius: 8px;
        }

        .alert {
            border-radius: 8px;
            border: none;
        }

        .bg-light-info {
            color: #1976d2;
        }

        .bg-light-warning {
            color: #f57c00;
        }


    </style>

@endsection
