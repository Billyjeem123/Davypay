@extends('dashboard.layout.sms')

@section('content')

    <div class="wrapper">
        <div class="content-page sms-page">
            <div class="content">
                <div class="container-fluid">
                    <div class="row mb-3 bg-white">
                        <!-- Add Fee Button -->
                        <div class="col-12 mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h4>Card Delivery Fees</h4>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFeeModal">
                                    <i class="uil-plus"></i> Add New Fee
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="sms-datatable" class="table table-bordered table-hover w-100 table-centered mb-0">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>State</th>
                                    <th>Amount (₦)</th>
                                    <th>Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($fees as $fee)
                                    <tr>
                                        <td>{{ $fee->id }}</td>
                                        <td>{{ ucfirst($fee->state) }}</td>
                                        <td>{{ number_format($fee->amount, 2) }}</td>
                                        <td>
                                            <button class="btn btn-sm btn-success edit-fee-btn"
                                                    data-id="{{ $fee->id }}"
                                                    data-state="{{ $fee->state }}"
                                                    data-amount="{{ $fee->amount }}"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editFeeModal">
                                                Edit
                                            </button>

                                            <form action="{{ route('delivery-fee.destroy', $fee->id) }}" method="POST" style="display: inline;">
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

                    <!-- Add Fee Modal -->
                    <div class="modal fade" id="addFeeModal" tabindex="-1" aria-labelledby="addFeeModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <form method="POST" action="{{route('deliveries-fee.store')}}">
                                @csrf
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="addFeeModalLabel">Add New Transaction Fee</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>

                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label for="add-state" class="form-label">State</label>
                                            <input type="text" class="form-control" id="add-state" name="state" placeholder="Lagos" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="add-amount" class="form-label">Amount (₦)</label>
                                            <input type="number" step="0.01" class="form-control" id="add-amount" name="amount" placeholder="2000.00" required>
                                        </div>

                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Add Fee</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Edit Fee Modal -->
                    <div class="modal fade" id="editFeeModal" tabindex="-1" aria-labelledby="editFeeModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <form method="POST" action="{{route('delivery-fee.update')}}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="id" id="edit-fee-id">

                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Transaction Fee</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>

                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label for="edit-state" class="form-label">State</label>
                                            <input type="text" class="form-control" id="edit-state" name="state" placeholder="Lagos" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="edit-amount" class="form-label">Amount (₦)</label>
                                            <input type="number" step="0.01" class="form-control" id="edit-amount" name="amount" placeholder="2000.00" required>
                                        </div>

                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
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
                    document.getElementById('edit-fee-id').value = this.dataset.id;
                    document.getElementById('edit-state').value = this.dataset.state;
                    document.getElementById('edit-amount').value = this.dataset.amount;
                });
            });
        });
    </script>

@endsection
