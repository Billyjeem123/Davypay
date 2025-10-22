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
                                <h1 class="page-title text-dark">Set Payment Service Rates</h1>
                            </div>
                        </div>
                    </div>

                    <!-- Services Rate Form -->
                    <div class="row justify-content-center">
                        <div class="col-md-8 bg-white p-4 shadow rounded">
                            <h4 class="text-center text-dark mb-4">Configure Rates for Services</h4>

                            {{-- Success/Error Messages --}}
                            @if(session('success'))
                                <div class="alert alert-success text-center">{{ session('success') }}</div>
                            @elseif(session('error'))
                                <div class="alert alert-danger text-center">{{ session('error') }}</div>
                            @endif

                            <form action="{{ route('store.service.rates') }}" method="POST">
                                @csrf

                                <div id="service-rate-wrapper">
                                    <div class="service-rate-row row mb-3">
                                        <div class="col-md-5">
                                            <label class="form-label">Service Type</label>
                                            <select name="services[0][type]" class="form-select" required>
                                                <option value="">-- Select Service --</option>
                                                <option value="giftcard">Gift Card</option>
                                                <option value="electricity">Electricity</option>
                                                <option value="tv">TV Subscription</option>
                                                <option value="virtual_cards">Virtual Card</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Rate / Price (₦)</label>
                                            <input type="number" name="services[0][rate]" class="form-control" required placeholder="e.g. 5000">
                                        </div>
                                        <div class="col-md-3 d-flex align-items-end">
                                            <button type="button" class="btn btn-danger btn-sm remove-service-rate d-none">Remove</button>
                                        </div>
                                    </div>
                                </div>

{{--                                <div class="text-end mb-3">--}}
{{--                                    <button type="button" class="btn btn-outline-primary btn-sm" id="add-service-rate">--}}
{{--                                        + Add Another Service--}}
{{--                                    </button>--}}
{{--                                </div>--}}

                                <div class="text-center">
                                    <button type="submit" class="btn btn-success w-100 py-2">Save Rates</button>
                                </div>
                            </form>
                        </div>
                    </div> <!-- end row -->


                    <!-- Existing Services Table -->
                    @if($charges->count())
                        <div class="row justify-content-center mt-5">
                            <div class="col-md-8 bg-white p-4 shadow rounded">
                                <h4 class="text-center mb-3">Existing Payment Service Rates</h4>
                                <table class="table table-bordered table-striped">
                                    <thead>
                                    <tr>
                                        <th>Service Type</th>
                                        <th>Rate (₦)</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($charges as $charge)
                                        <tr id="service-row-{{ $charge->id }}">
                                            <td>{{ ucfirst($charge->services) }}</td>
                                            <td>{{ number_format($charge->amount, 2) }}</td>
                                            <td>{{ $charge->created_at->format('Y-m-d H:i') }}</td>
                                            <td>
                                                <form action="{{ route('delete.service.charge', $charge->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this service?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif


                </div>
            </div>
        </div>
    </div>

@endsection
