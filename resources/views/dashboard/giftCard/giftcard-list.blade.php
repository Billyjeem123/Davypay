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
                                <h1 class="page-title text-dark">Gift Card Types Management</h1>
                                <p class="text-muted">Configure available gift card types and their details</p>
                            </div>
                        </div>
                        <div class="col-md-6 py-2 text-end">
                            <button type="button" class="btn btn-sm btn-primary mb-2" data-bs-toggle="modal" data-bs-target="#addGiftCardModal">
                                <i class="uil-plus"></i> Add New Gift Card Type
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
                                <th>Logo</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Total Cards</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($listing as $giftCardList)
                                <tr>
                                    <td>{{ $giftCardList->id }}</td>
                                    <td>
                                        @if($giftCardList->logo_path)
                                            <img src="{{ asset($giftCardList->logo_path) }}" alt="{{ $giftCardList->name }}" style="width: 50px; height: 50px; object-fit: contain;">
                                        @else
                                            <span class="badge bg-secondary">No Logo</span>
                                        @endif
                                    </td>
                                    <td>{{ $giftCardList->name }}</td>
                                    <td>{{ Str::limit($giftCardList->description, 50) }}</td>
                                    <td>
                                        @if($giftCardList->status == 'active')
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td>{{ $giftCardList->giftCards->count() }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-success edit-giftcard-btn"
                                                data-id="{{ $giftCardList->id }}"
                                                data-name="{{ $giftCardList->name }}"
                                                data-description="{{ $giftCardList->description }}"
                                                data-status="{{ $giftCardList->status }}"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editGiftCardModal">
                                            Edit
                                        </button>

                                        <form action="{{ route('gift-cards.listing.destroy', $giftCardList->id) }}" method="POST" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this gift card type? This will also delete all associated gift card records.')">
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

                <!-- Add Gift Card Type Modal -->
                <div class="modal fade" id="addGiftCardModal" tabindex="-1" aria-labelledby="addGiftCardModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <form method="POST" action="{{route('gift-cards.listing.store')}}" enctype="multipart/form-data">
                            @csrf
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="addGiftCardModalLabel">Add New Gift Card Type</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label for="add-name" class="form-label">Gift Card Name</label>
                                        <input type="text" class="form-control" id="add-name" name="name" placeholder="e.g., Amazon, iTunes, Google Play" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="add-description" class="form-label">Description</label>
                                        <textarea class="form-control" id="add-description" name="description" rows="3" placeholder="Brief description of the gift card"></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label for="add-logo" class="form-label">Logo</label>
                                        <input type="file" class="form-control" id="add-logo" name="logo" accept="image/*">
                                        <div class="form-text">Upload a logo image for this gift card type (PNG, JPG, JPEG)</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="add-status" class="form-label">Status</label>
                                        <select class="form-control" id="add-status" name="status" required>
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                        <div class="form-text">Only active gift cards will be visible to users</div>
                                    </div>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Add Gift Card Type</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Edit Gift Card Type Modal -->
                <div class="modal fade" id="editGiftCardModal" tabindex="-1" aria-labelledby="editGiftCardModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <form method="POST" action="{{route('gift-cards.listing.update')}}" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="id" id="giftcard-id">

                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Gift Card Type</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label for="edit-name" class="form-label">Gift Card Name</label>
                                        <input type="text" class="form-control" id="edit-name" name="name" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="edit-description" class="form-label">Description</label>
                                        <textarea class="form-control" id="edit-description" name="description" rows="3"></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label for="edit-logo" class="form-label">Logo</label>
                                        <input type="file" class="form-control" id="edit-logo" name="logo" accept="image/*">
                                        <div class="form-text">Leave empty to keep current logo. Upload new image to replace.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="edit-status" class="form-label">Status</label>
                                        <select class="form-control" id="edit-status" name="status" required>
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                        <div class="form-text">Only active gift cards will be visible to users</div>
                                    </div>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-success">Update Gift Card Type</button>
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
            const editButtons = document.querySelectorAll('.edit-giftcard-btn');

            editButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const id = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');
                    const description = this.getAttribute('data-description');
                    const status = this.getAttribute('data-status');

                    document.getElementById('giftcard-id').value = id;
                    document.getElementById('edit-name').value = name;
                    document.getElementById('edit-description').value = description;
                    document.getElementById('edit-status').value = status;
                });
            });
        });
    </script>

@endsection
