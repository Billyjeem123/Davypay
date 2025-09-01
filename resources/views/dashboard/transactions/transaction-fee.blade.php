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
                            <a href="{{route('configure-payment')}}" class="btn btn-sm btn-info mb-2">
                                <i class="uil-wallet"></i> Configure Gateways
                            </a>
                            <a href="{{ route('admin.home') }}" class="btn btn-sm btn-secondary mb-2">
                                <i class="uil-dashboard"></i> Dashboard
                            </a>
                        </div>
                    </div>

                    @php
                        $preferredProvider = \App\Models\Settings::get('preferred_provider', 'paystack');
                    @endphp

                        <!-- Payment Gateway Status Cards -->
                    <div class="row mb-4">
                        <!-- Paystack Card -->
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="card-title text-primary mb-1">Paystack Gateway</h5>
                                            <p class="text-muted mb-0">Online payments & transfers</p>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="paystackStatus" {{ $preferredProvider === 'paystack' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="paystackStatus">
                            <span class="badge {{ $preferredProvider === 'paystack' ? 'bg-success' : 'bg-secondary' }}">
                                {{ $preferredProvider === 'paystack' ? 'Active' : 'Inactive' }}
                            </span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Nomba Card -->
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="card-title text-info mb-1">Nomba Gateway</h5>
                                            <p class="text-muted mb-0">POS & mobile payments</p>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="nombaStatus" {{ $preferredProvider === 'nomba' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="nombaStatus">
                            <span class="badge {{ $preferredProvider === 'nomba' ? 'bg-success' : 'bg-secondary' }}">
                                {{ $preferredProvider === 'nomba' ? 'Active' : 'Inactive' }}
                            </span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- Transaction Fee Configuration -->
                    <div class="row">
                        <!-- Transfer Settings -->
                        <div class="col-lg-6 mb-4">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-light border-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h4 class="card-title mb-1">
                                                <i class="uil-exchange text-primary me-2"></i>Transfer Fees
                                            </h4>
                                            <p class="text-muted mb-0">Configure fees for money transfers</p>
                                        </div>
{{--                                        <button type="button" class="btn btn-outline-success btn-sm" onclick="addTransferRange()">--}}
{{--                                            <i class="uil-plus"></i> Add Range--}}
{{--                                        </button>--}}
                                    </div>
                                </div>
                                <div class="card-body">
                                    <form   action="{{route('save.transfer.fee')}}" method="POST">
                                        @csrf
                                        <div class="alert alert-info border-0 bg-light-info">
                                            <i class="uil-info-circle me-2"></i>
                                            <strong>Note:</strong> Fee takes effect when transaction amount is within the specified range.
                                        </div>

                                        <div id="transferRanges">
                                            <!-- Default Range -->
                                            <div class="fee-range-item border rounded p-3 mb-3 bg-light">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <h6 class="mb-0">Range 1</h6>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" >
                                                        <i class="uil-times"></i>
                                                    </button>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Minimum Amount (₦)</label>
                                                        <input type="number" class="form-control" name="transfer_min[]" placeholder="0" min="0" step="0.01">
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Maximum Amount (₦)</label>
                                                        <input type="number" class="form-control" name="transfer_max[]" placeholder="10000" min="0" step="0.01">
                                                    </div>
                                                </div>

                                                <div class="row">

                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Percentage Fee (%)</label>
                                                        <input type="number" class="form-control" name="transfer_percent[]" placeholder="1.5" min="0" max="100" step="0.01">
                                                    </div>

                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Provider </label>
                                                      <input type="text" class="form-control" name="provider" value="{{ \App\Models\Settings::get('preferred_provider') }}" readonly>
                                                    </div>

                                                    <input type="hidden" value="transfer" name="type">



                                                </div>


                                            </div>
                                        </div>

                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-success">
                                                <i class="uil-save"></i> Save Transfer Settings
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Deposit Settings -->
                        <div class="col-lg-6 mb-4">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-light border-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h4 class="card-title mb-1">
                                                <i class="uil-credit-card text-success me-2"></i>Deposit Fees
                                            </h4>
                                            <p class="text-muted mb-0">Configure fees for wallet deposits</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <form   action="{{route('save.deposit.fee')}}" method="POST">
                                        @csrf
                                        <div class="alert alert-warning border-0 bg-light-warning">
                                            <i class="uil-info-circle me-2"></i>
                                            <strong>Note:</strong> Fee and addon takes effect when transaction amount is within range. Fee is calculated in percentage.
                                        </div>

                                        <div id="depositRanges">
                                            <!-- Default Range -->
                                            <div class="fee-range-item border rounded p-3 mb-3 bg-light">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <h6 class="mb-0">Range 1</h6>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRange(this)">
                                                        <i class="uil-times"></i>
                                                    </button>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-12 mb-3">
                                                        <label class="form-label">Amount Range (₦)</label>
                                                        <div class="input-group">
                                                            <input type="number" class="form-control" name="deposit_min[]" placeholder="Min" min="0" step="0.01">
                                                            <span class="input-group-text">to</span>
                                                            <input type="number" class="form-control" name="deposit_max[]" placeholder="Max" min="0" step="0.01">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">

                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Platform Fee (%)</label>
                                                        <input type="number" class="form-control" name="deposit_platform_fee[]" placeholder="0.5" min="0" max="100" step="0.01">
                                                        <div class="form-text">Additional platform charge</div>
                                                    </div>

                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Provider </label>
                                                        <input type="text" class="form-control" name="provider" value="{{ \App\Models\Settings::get('preferred_provider') }}" readonly>
                                                    </div>

                                                </div>


                                            </div>
                                        </div>

                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-success">
                                                <i class="uil-save"></i> Save Deposit Settings
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3 bg-white">
                        <div class="table-responsive">
                            <table id="sms-datatable" class="table table-bordered table-hover w-100 table-centered mb-0">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Provider</th>
                                    <th>Type</th>
                                    <th>Min (₦)</th>
                                    <th>Max (₦)</th>
                                    <th>Fee (%)</th>
                                    <th>Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($fees as $fee)
                                    <tr>
                                        <td>{{ $fee->id }}</td>
                                        <td>{{ ucfirst($fee->provider) }}</td>
                                        <td>{{ ucfirst($fee->type) }}</td>
                                        <td>₦{{ number_format($fee->min, 2) }}</td>
                                        <td>₦{{ number_format($fee->max, 2) }}</td>
                                        <td>{{ $fee->fee }}%</td>
                                        <td>
                                            <button class="btn btn-sm btn-success edit-fee-btn"
                                                    data-id="{{ $fee->id }}"
                                                    data-provider="{{ $fee->provider }}"
                                                    data-type="{{ $fee->type }}"
                                                    data-min="{{ $fee->min }}"
                                                    data-max="{{ $fee->max }}"
                                                    data-fee="{{ $fee->fee }}"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editFeeModal">
                                                Edit
                                            </button>

                                            <form action="{{ route('transaction-fee.destroy', $fee->id) }}" method="POST" style="display: inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this fee?')">
                                                    Delete
                                                </button>
                                            </form>

                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>

                        </div>
                    </div>


                    <!-- Edit Fee Modal -->
                    <div class="modal fade" id="editFeeModal" tabindex="-1" aria-labelledby="editFeeModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <form method="POST" action="{{route('transaction-fee.update')}}">
                                @csrf
                                <input type="hidden" name="id" id="fee-id">

                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Transaction Fee</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>

                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label for="provider" class="form-label">Provider</label>
                                            <input type="text" class="form-control" id="provider" name="provider" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="type" class="form-label">Type</label>
                                            <input type="text" class="form-control" id="type" name="type" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="min" class="form-label">Minimum (₦)</label>
                                            <input type="number" step="0.01" class="form-control" id="min" name="min" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="max" class="form-label">Maximum (₦)</label>
                                            <input type="number" step="0.01" class="form-control" id="max" name="max" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="fee" class="form-label">Fee (%)</label>
                                            <input type="number" step="0.01" class="form-control" id="fee" name="fee" required>
                                        </div>
                                    </div>

                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-success">Update Fee</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>


                </div>
            </div>
        </div>
    </div>



    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const editButtons = document.querySelectorAll('.edit-fee-btn');

            editButtons.forEach(button => {
                button.addEventListener('click', function () {
                    document.getElementById('fee-id').value = this.dataset.id;
                    document.getElementById('provider').value = this.dataset.provider;
                    document.getElementById('type').value = this.dataset.type;
                    document.getElementById('min').value = this.dataset.min;
                    document.getElementById('max').value = this.dataset.max;
                    document.getElementById('fee').value = this.dataset.fee;
                });
            });
        });
    </script>



@endsection
