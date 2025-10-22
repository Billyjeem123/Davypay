@extends('dashboard.layout.sms')

@section('content')
    <div class="wrapper">
        <div class="content-page">
            <div class="content">
                <div class="container-fluid">

                    <!-- Header -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h2 class="text-dark mb-0">eSIM Settings</h2>
                            <p class="text-muted">Manage eSIM markup configuration</p>
                        </div>
                    </div>

                    <!-- Form -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card shadow-sm border-0">
                                <div class="card-body p-4">
                                    <h5 class="card-title mb-3">Update eSIM Markup Percentage</h5>

                                    <form action="{{ route('esim.settings.update') }}" method="POST">
                                        @csrf
                                        <div class="row g-3 align-items-end">
                                            <div class="col-md-6">
                                                <label class="form-label">eSIM Markup (%)</label>
                                                <div class="input-group">
                                                    <input
                                                        type="number"
                                                        name="esim_markup_percentage"
                                                        class="form-control @error('esim_markup_percentage') is-invalid @enderror"
                                                        placeholder="Enter markup percentage"
                                                        value="{{ old('esim_markup_percentage', $esim_markup_percentage ?? 0) }}"
                                                        min="0"
                                                        max="100"
                                                        step="0.01"
                                                        required>
                                                    <span class="input-group-text">%</span>
                                                </div>
                                                @error('esim_markup_percentage')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-3">
                                                <button type="submit" class="btn btn-primary w-100">
                                                    <i class="ri-save-3-line me-1"></i> Save Setting
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Settings Display -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card shadow-sm border-0">
                                <div class="card-body p-4">
                                    <h5 class="card-title mb-3">Current Setting</h5>
                                    <table class="table table-hover align-middle">
                                        <thead class="table-light">
                                        <tr>
                                            <th>Setting</th>
                                            <th>Value</th>
                                            <th>Last Updated</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr>
                                            <td><i class="ri-price-tag-3-line text-primary me-2"></i> eSIM Markup</td>
                                            <td>{{ number_format($esim_markup_percentage ?? 0, 2) }}%</td>
                                            <td>{{ $last_updated ?? now()->format('M d, Y') }}</td>
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
@endsection
