@extends('dashboard.layout.sms')

@section('content')
    <div class="wrapper">
        <div class="content-page">
            <div class="content">
                <div class="container-fluid">

                    <!-- Page Header -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h2 class="text-dark mb-0">Airtime To Cash</h2>
                            <p class="text-muted">Manage network providers and rates</p>
                        </div>
                    </div>

                    <!-- Add New Provider Card -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body p-4">
                                    <h5 class="card-title mb-3">Add Network Provider</h5>

                                    <form action="{{ route('admin.network-provider.store') }}" method="POST">
                                        @csrf
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <label class="form-label">Network</label>
                                                <select name="network_name" class="form-select @error('network_name') is-invalid @enderror" required>
                                                    <option value="">Choose network</option>
                                                    <option value="MTN">MTN</option>
                                                    <option value="GLO">GLO</option>
                                                    <option value="AIRTEL">AIRTEL</option>
                                                    <option value="9MOBILE">9MOBILE</option>
                                                </select>
                                                @error('network_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-2">
                                                <label class="form-label">Rate (%)</label>
                                                <input type="number" name="admin_rate" class="form-control @error('admin_rate') is-invalid @enderror"
                                                       placeholder="5" min="0" max="100" step="0.01" required>
                                                @error('admin_rate')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-4">
                                                <label class="form-label">Transfer Number</label>
                                                <input type="tel" name="transfer_number" class="form-control @error('transfer_number') is-invalid @enderror"
                                                       placeholder="0801234567" required>
                                                @error('transfer_number')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-3 d-flex align-items-end">
                                                <button type="submit" class="btn btn-primary w-100">Add Provider</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Providers List -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body p-4">
                                    <h5 class="card-title mb-3">Network Providers</h5>

                                    @if(isset($networkProviders) && $networkProviders->count() > 0)
                                            <table  id="sms-datatable" class="table table-hover">
                                                <thead class="table-light">
                                                <tr>
                                                    <th class="border-0">Network</th>
                                                    <th class="border-0">Rate</th>
                                                    <th class="border-0">Transfer Number</th>
                                                    <th class="border-0">Status</th>
                                                    <th class="border-0 text-end">Actions</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @foreach($networkProviders as $provider)
                                                    <tr>
                                                        <td class="align-middle">
                                                            <div class="d-flex align-items-center">
                                                                <div class="bg-primary bg-opacity-10 rounded p-2 me-2">
                                                                    <i class="fa fa-mobile text-primary"></i>
                                                                </div>
                                                                <span class="fw-medium">{{ $provider->network_name }}</span>
                                                            </div>
                                                        </td>
                                                        <td class="align-middle">
                                                            <span class="badge bg-light text-dark">{{ $provider->admin_rate }}%</span>
                                                        </td>
                                                        <td class="align-middle">
                                                            <span class="font-monospace">{{ $provider->transfer_number }}</span>
                                                        </td>
                                                        <td class="align-middle">
                                                            @if($provider->is_active)
                                                                <span class="badge bg-success-subtle text-success">Active</span>
                                                            @else
                                                                <span class="badge bg-danger-subtle text-danger">Inactive.</span>
                                                            @endif
                                                        </td>
                                                        <td class="align-middle text-end">
                                                            <div class="btn-group btn-group-sm">
                                                                <button type="button" class="btn btn-outline-primary"
                                                                        onclick="editProvider({{ $provider->id }}, '{{ $provider->network_name }}', {{ $provider->admin_rate }}, '{{ $provider->transfer_number }}')"
                                                                        title="Edit">
                                                                    Edit
                                                                </button>

                                                                <form action="{{ route('admin.network-provider.toggle', $provider->id) }}" method="POST" class="d-inline">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <button type="submit" class="btn btn-outline-{{ $provider->is_active ? 'warning' : 'success' }}"
                                                                            title="{{ $provider->is_active ? 'Deactivate' : 'Activate' }}">
                                                                        {{ $provider->is_active ? 'Disable' : 'Enable' }}
                                                                    </button>
                                                                </form>

                                                                <form action="{{ route('admin.network-provider.destroy', $provider->id) }}" method="POST" class="d-inline"
                                                                      onsubmit="return confirm('Delete this provider?')">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="btn btn-outline-danger" title="Delete">
                                                                        Delete
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                    @else
                                        <div class="text-center py-5">
                                            <div class="mb-3">
                                                <i class="fa fa-mobile fa-3x text-muted"></i>
                                            </div>
                                            <h6 class="text-muted">No network providers yet</h6>
                                            <p class="text-muted mb-0">Add your first provider using the form above</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Edit Provider Modal -->
    <div class="modal fade" id="editProviderModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title">Edit Network Provider</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editProviderForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Network</label>
                            <select name="network_name" id="edit_network_name" class="form-select" required>
                                <option value="MTN">MTN</option>
                                <option value="GLO">GLO</option>
                                <option value="AIRTEL">AIRTEL</option>
                                <option value="9MOBILE">9MOBILE</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Rate (%)</label>
                            <input type="number" name="admin_rate" id="edit_admin_rate"
                                   class="form-control" min="0" max="100" step="0.01" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Transfer Number</label>
                            <input type="tel" name="transfer_number" id="edit_transfer_number"
                                   class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Provider</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function editProvider(id, networkName, adminRate, transferNumber) {
            document.getElementById('editProviderForm').action = `/admin/network-provider/${id}`;
            document.getElementById('edit_network_name').value = networkName;
            document.getElementById('edit_admin_rate').value = adminRate;
            document.getElementById('edit_transfer_number').value = transferNumber;

            new bootstrap.Modal(document.getElementById('editProviderModal')).show();
        }
    </script>

@endsection
