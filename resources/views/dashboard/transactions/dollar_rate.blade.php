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
                                <h1 class="page-title text-dark">Dollar Conversion Rate</h1>
                            </div>
                        </div>
                    </div>

                    <!-- Dollar Rate Form -->
                    <div class="row justify-content-center">
                        <div class="col-md-6 bg-white p-4 shadow rounded">
                            <h3 class="text-center text-dark mb-4">Set Dollar Rate (₦ per $1)</h3>

                            {{-- Success/Error Messages --}}
                            @if(session('success'))
                                <div class="alert alert-success text-center">{{ session('success') }}</div>
                            @elseif(session('error'))
                                <div class="alert alert-danger text-center">{{ session('error') }}</div>
                            @endif

                            {{-- Show current rate --}}
                            <div class="alert alert-info text-center">
                                Current Rate: <strong>₦{{ number_format($currentDollarRate ?? 0, 2) }}</strong> per $1
                            </div>

                            <form action="{{ route('save_dollar_rate') }}" method="POST">
                                @csrf

                                <div class="mb-3">
                                    <label for="dollar_rate" class="form-label">New Dollar Rate</label>
                                    <input type="number" name="dollar_rate" id="dollar_rate" step="0.01"
                                           class="form-control @error('dollar_rate') is-invalid @enderror"
                                           value="{{ old('dollar_rate', $currentDollarRate) }}"
                                           placeholder="Enter amount in naira (e.g. 1500)">
                                    @error('dollar_rate')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="text-center mt-4">
                                    <button type="submit" class="btn btn-primary w-100 py-2">Save Rate</button>
                                </div>
                            </form>
                        </div>
                    </div> <!-- end row -->

                </div>
            </div>
        </div>
    </div>
@endsection
