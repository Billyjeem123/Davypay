@extends('dashboard.layout.sms')

@section('content')
    <div class="wrapper">
        <div class="content-page">
            <div class="content">
                <div class="container-fluid">

                    <!-- Page Header -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h2 class="text-dark mb-0">Virtual Card Settings</h2>
                            <p class="text-muted">Manage virtual card fees and rates</p>
                        </div>
                    </div>

                    <!-- Add/Update Setting Card -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body p-4">
                                    <h5 class="card-title mb-3">Update Virtual Card Configuration</h5>

                                    <form action="{{ route('admin.virtual-card.settings.store') }}" method="POST">
                                        @csrf
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label">Setting Type</label>
                                                <select name="setting_type" id="setting_type" class="form-select @error('setting_type') is-invalid @enderror" required>
                                                    <option value="">Choose setting type</option>
                                                    <option value="virtual_card_topup_fee">Virtual Card Top Up Fee</option>
                                                    <option value="virtual_card_creation_fee">Virtual Card Creation Fee</option>
                                                    <option value="virtual_card_account_fee">Virtual Card Account Fee</option>
                                                    <option value="dollar_conversion_rate">Dollar Conversion Rate</option>
                                                </select>
                                                @error('setting_type')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-4">
                                                <label class="form-label">Value</label>
                                                <div class="input-group">
                                                    <span class="input-group-text" id="currency-symbol">₦</span>
                                                    <input type="number" name="setting_value" id="setting_value"
                                                           class="form-control @error('setting_value') is-invalid @enderror"
                                                           placeholder="0.00" min="0" step="0.01" required>
                                                </div>
                                                @error('setting_value')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <small class="text-muted" id="value-hint">Enter the amount</small>
                                            </div>

                                            <div class="col-md-4 d-flex align-items-end">
                                                <button type="submit" class="btn btn-primary w-100">
                                                    <i class="fa fa-save me-1"></i> Save Setting
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Settings List -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body p-4">
                                    <h5 class="card-title mb-3">Current Settings</h5>

                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-light">
                                            <tr>
                                                <th class="border-0">Setting</th>
                                                <th class="border-0">Current Value</th>
                                                <th class="border-0">Last Updated</th>
                                                <th class="border-0 text-end">Actions</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <tr>
                                                <td class="align-middle">
                                                    <div class="d-flex align-items-center">
                                                        <div class="bg-success bg-opacity-10 rounded p-2 me-2">
                                                            <i class="fa fa-credit-card text-success"></i>
                                                        </div>
                                                        <span class="fw-medium">Virtual Card Top Up Fee</span>
                                                    </div>
                                                </td>
                                                <td class="align-middle">
                                                        <span class="badge bg-light text-dark fs-6">
                                                            ₦{{ number_format(\App\Models\Settings::get('virtual_card_topup_fee', 0), 2) }}
                                                        </span>
                                                </td>
                                                <td class="align-middle text-muted">
                                                    {{ now()->format('M d, Y') }}
                                                </td>
                                                <td class="align-middle text-end">
                                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                                            onclick="editSetting('virtual_card_topup_fee', '{{ \App\Models\Settings::get('virtual_card_topup_fee', 0) }}', '₦')"
                                                            title="Edit">
                                                        <i class="fa fa-edit me-1"></i> Edit
                                                    </button>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td class="align-middle">
                                                    <div class="d-flex align-items-center">
                                                        <div class="bg-primary bg-opacity-10 rounded p-2 me-2">
                                                            <i class="fa fa-plus-circle text-primary"></i>
                                                        </div>
                                                        <span class="fw-medium">Virtual Card Creation Fee</span>
                                                    </div>
                                                </td>
                                                <td class="align-middle">
                                                        <span class="badge bg-light text-dark fs-6">
                                                            ₦{{ number_format(\App\Models\Settings::get('virtual_card_creation_fee', 0), 2) }}
                                                        </span>
                                                </td>
                                                <td class="align-middle text-muted">
                                                    {{ now()->format('M d, Y') }}
                                                </td>
                                                <td class="align-middle text-end">
                                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                                            onclick="editSetting('virtual_card_creation_fee', '{{ \App\Models\Settings::get('virtual_card_creation_fee', 0) }}', '₦')"
                                                            title="Edit">
                                                        <i class="fa fa-edit me-1"></i> Edit
                                                    </button>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td class="align-middle">
                                                    <div class="d-flex align-items-center">
                                                        <div class="bg-info bg-opacity-10 rounded p-2 me-2">
                                                            <i class="fa fa-user text-info"></i>
                                                        </div>
                                                        <span class="fw-medium">Virtual Card Account Fee</span>
                                                    </div>
                                                </td>
                                                <td class="align-middle">
                                                        <span class="badge bg-light text-dark fs-6">
                                                            ₦{{ number_format(\App\Models\Settings::get('virtual_card_account_fee', 0), 2) }}
                                                        </span>
                                                </td>
                                                <td class="align-middle text-muted">
                                                    {{ now()->format('M d, Y') }}
                                                </td>
                                                <td class="align-middle text-end">
                                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                                            onclick="editSetting('virtual_card_account_fee', '{{\App\Models\Settings::get('virtual_card_account_fee', 0) }}', '₦')"
                                                            title="Edit">
                                                        <i class="fa fa-edit me-1"></i> Edit
                                                    </button>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td class="align-middle">
                                                    <div class="d-flex align-items-center">
                                                        <div class="bg-warning bg-opacity-10 rounded p-2 me-2">
                                                            <i class="fa fa-dollar text-warning"></i>
                                                        </div>
                                                        <span class="fw-medium">Dollar Conversion Rate</span>
                                                    </div>
                                                </td>
                                                <td class="align-middle">
                                                        <span class="badge bg-light text-dark fs-6">
                                                            ₦{{ number_format(\App\Models\Settings::get('dollar_conversion_rate', 0), 2) }} / $1
                                                        </span>
                                                </td>
                                                <td class="align-middle text-muted">
                                                    {{ now()->format('M d, Y') }}
                                                </td>
                                                <td class="align-middle text-end">
                                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                                            onclick="editSetting('dollar_conversion_rate', '{{ \App\Models\Settings::get('dollar_conversion_rate', 0) }}', '₦/$1')"
                                                            title="Edit">
                                                        <i class="fa fa-edit me-1"></i> Edit
                                                    </button>
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Edit Setting Modal -->
    <div class="modal fade" id="editSettingModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title">Edit Setting</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editSettingForm" action="{{ route('admin.virtual-card.settings.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="setting_type" id="edit_setting_type">

                        <div class="mb-3">
                            <label class="form-label fw-medium" id="edit_setting_label">Setting Value</label>
                            <div class="input-group">
                                <span class="input-group-text" id="edit_currency_symbol">₦</span>
                                <input type="number" name="setting_value" id="edit_setting_value"
                                       class="form-control" min="0" step="0.01" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Setting</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Update currency symbol based on selection
        document.getElementById('setting_type').addEventListener('change', function() {
            const currencySymbol = document.getElementById('currency-symbol');
            const valueHint = document.getElementById('value-hint');

            if (this.value === 'dollar_conversion_rate') {
                currencySymbol.textContent = '₦/$1';
                valueHint.textContent = 'Enter the naira equivalent of $1';
            } else {
                currencySymbol.textContent = '₦';
                valueHint.textContent = 'Enter the fee amount';
            }
        });

        function editSetting(settingType, currentValue, currencySymbol) {
            document.getElementById('edit_setting_type').value = settingType;
            document.getElementById('edit_setting_value').value = currentValue;
            document.getElementById('edit_currency_symbol').textContent = currencySymbol;

            // Set appropriate label
            const labels = {
                'virtual_card_topup_fee': 'Virtual Card Top Up Fee',
                'virtual_card_creation_fee': 'Virtual Card Creation Fee',
                'virtual_card_account_fee': 'Virtual Card Account Fee',
                'dollar_conversion_rate': 'Dollar Conversion Rate'
            };
            document.getElementById('edit_setting_label').textContent = labels[settingType];

            new bootstrap.Modal(document.getElementById('editSettingModal')).show();
        }
    </script>

@endsection
