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
                                <h1 class="page-title text-dark">Epin Rates Settings</h1>
                                <p class="text-muted">Configure Recharge Card rates and quantity limits</p>
                            </div>
                        </div>
                        <div class="col-md-6 py-2 text-end">
                            <button type="button" class="btn btn-sm btn-primary mb-2" data-bs-toggle="modal" data-bs-target="#addEpinRateModal">
                                <i class="uil-plus"></i> Add New Rate
                            </button>
                            <a href="{{ route('admin.home') }}" class="btn btn-sm btn-secondary mb-2">
                                <i class="uil-dashboard"></i> Dashboard
                            </a>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="sms-datatable" class="table table-bordered table-hover w-100 table-centered mb-0">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Card Network</th>
                                <th>Value (₦)</th>
                                <th>Min Quantity</th>
                                <th>Max Quantity</th>
                                <th>Rate (%)</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($epinRates as $rate)
                                <tr>
                                    <td>{{ $rate->id }}</td>
                                    <td>{{ ucfirst($rate->card_network) }}</td>
                                    <td>₦{{ number_format($rate->value, 2) }}</td>
                                    <td>{{ number_format($rate->min_quantity) }}</td>
                                    <td>{{ $rate->max_quantity ? number_format($rate->max_quantity) : 'Unlimited' }}</td>
                                    <td>{{ $rate->rate }}%</td>
                                    <td>
                                        <button class="btn btn-sm btn-success edit-rate-btn"
                                                data-id="{{ $rate->id }}"
                                                data-card-network="{{ $rate->card_network }}"
                                                data-value="{{ $rate->value }}"
                                                data-min-quantity="{{ $rate->min_quantity }}"
                                                data-max-quantity="{{ $rate->max_quantity }}"
                                                data-rate="{{ $rate->rate }}"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editEpinRateModal">
                                            Edit
                                        </button>

                                        <form action="{{ route('epin-rate.destroy', $rate->id) }}" method="POST" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this rate?')">
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

                <!-- Add Epin Rate Modal -->
                <div class="modal fade" id="addEpinRateModal" tabindex="-1" aria-labelledby="addEpinRateModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <form method="POST" action="{{route('store.epin.rate')}}">
                            @csrf
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="addEpinRateModalLabel">Add New Epin Rate</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label for="add-card-network" class="form-label">Card Network</label>
                                        <select class="form-control" id="add-card-network" name="card_network" required>
                                            <option value="">Select Card Network</option>
                                            <option value="MTN">MTN</option>
                                            <option value="AIRTEL">Airtel</option>
                                            <option value="GLO">Glo</option>
                                            <option value="9MOBILE">9Mobile</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="add-value" class="form-label">Card Value (₦)</label>
                                        <select class="form-control" id="add-value" name="value" required>
                                            <option value="">Select Value</option>
                                            <option value="100">₦100</option>
                                            <option value="200">₦200</option>
                                            <option value="500">₦500</option>
                                            <option value="1000">₦1,000</option>
                                            <option value="1500">₦1,500</option>
                                            <option value="2000">₦2,000</option>
                                            <option value="5000">₦5,000</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="add-min-quantity" class="form-label">Minimum Quantity</label>
                                        <input type="number" class="form-control" id="add-min-quantity" name="min_quantity" placeholder="1" min="1" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="add-max-quantity" class="form-label">Maximum Quantity</label>
                                        <input type="number" class="form-control" id="add-max-quantity" name="max_quantity" placeholder="Leave empty for unlimited" min="1">
                                        <div class="form-text">Leave empty for unlimited quantity</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="add-rate" class="form-label">Rate</label>
                                        <input type="number" step="0.01" class="form-control" id="add-rate" name="rate" placeholder="2.50" min="0" max="100" required>
                                        <div class="form-text">Discount/commission rate percentage</div>
                                    </div>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Add Rate</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Edit Epin Rate Modal -->
                <div class="modal fade" id="editEpinRateModal" tabindex="-1" aria-labelledby="editEpinRateModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <form method="POST" action="{{route('store.epin.rate')}}">
                            @csrf
                            <input type="hidden" name="id" id="rate-id">

                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Epin Rate</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label for="card-network" class="form-label">Card Network</label>
                                        <select class="form-control" id="card-network" name="card_network" required>
                                            <option value="MTN">MTN</option>
                                            <option value="AIRTEL">Airtel</option>
                                            <option value="GLO">Glo</option>
                                            <option value="9MOBILE">9Mobile</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="value" class="form-label">Card Value (₦)</label>
                                        <select class="form-control" id="value" name="value" required>
                                            <option value="100">₦100</option>
                                            <option value="200">₦200</option>
                                            <option value="500">₦500</option>
                                            <option value="1000">₦1,000</option>
                                            <option value="1500">₦1,500</option>
                                            <option value="2000">₦2,000</option>
                                            <option value="5000">₦5,000</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="min-quantity" class="form-label">Minimum Quantity</label>
                                        <input type="number" class="form-control" id="min-quantity" name="min_quantity" min="1" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="max-quantity" class="form-label">Maximum Quantity</label>
                                        <input type="number" class="form-control" id="max-quantity" name="max_quantity" min="1">
                                        <div class="form-text">Leave empty for unlimited quantity</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="rate" class="form-label">Rate</label>
                                        <input type="number" step="0.01" class="form-control" id="rate" name="rate" min="0" max="100" required>
                                        <div class="form-text">Discount/commission rate percentage</div>
                                    </div>
                                </div>

                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-success">Update Rate</button>
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
            const editButtons = document.querySelectorAll('.edit-rate-btn');

            editButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const id = this.getAttribute('data-id');
                    const cardNetwork = this.getAttribute('data-card-network');
                    const value = this.getAttribute('data-value');
                    const minQuantity = this.getAttribute('data-min-quantity');
                    const maxQuantity = this.getAttribute('data-max-quantity');
                    const rate = this.getAttribute('data-rate');

                    document.getElementById('rate-id').value = id;
                    document.getElementById('card-network').value = cardNetwork;
                    document.getElementById('value').value = value;
                    document.getElementById('min-quantity').value = minQuantity;
                    document.getElementById('max-quantity').value = maxQuantity || '';
                    document.getElementById('rate').value = rate;
                });
            });
        });
    </script>

@endsection
