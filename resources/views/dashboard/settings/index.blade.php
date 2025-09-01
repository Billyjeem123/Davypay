@extends('dashboard.layout.sms')

@section('content')

    <div class="wrapper">

        <div class="content-page">
            <div class="content">

                <!-- Start Content-->
                <div class="container-fluid">

                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <h1 class="page-title text-dark">Settings</h1>
                            </div>
                        </div>
                    </div>


                    <div class="row mb-3">
                        <div class="col-md-8 bg-white p-3 mb-3">
                            <h3 class="text-center text-dark mb-3">Update Details</h3>

                            <!-- Profile Update Form -->
                            <form class="row" action="{{ route('admin.profile.update') }}" method="POST">
                                @csrf

                                <div class="mb-3 col-md-6">
                                    <label for="fullName" class="form-label">Full Name</label>
                                    <input class="form-control py-2 ps-3 text-dark @error('fullName') is-invalid @enderror"
                                           id="fullName" type="text" name="fullName"
                                           value="{{ old('fullName', $admin->first_name ?? '') }}"
                                           placeholder="John Doe">
                                    @error('fullName')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3 col-md-6">
                                    <label for="email" class="form-label">Email</label>
                                    <input class="form-control py-2 ps-3 text-dark @error('email') is-invalid @enderror"
                                           id="email" type="email" name="email"
                                           value="{{ old('email', $admin->email ?? '') }}"
                                           placeholder="example@gmail.com">
                                    @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3 col-md-6">
                                    <label for="companyName" class="form-label">Software Name</label>
                                    <input class="form-control py-2 ps-3 text-dark @error('companyName') is-invalid @enderror"
                                           id="companyName" type="text" name="companyName"
                                           value="{{ old('companyName', $admin->company_name ?? 'Billia-App') }}"
                                           placeholder="ABC">
                                    @error('companyName')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3 col-md-6">
                                    <label for="phoneNo" class="form-label">Phone Number</label>
                                    <input class="form-control py-2 ps-3 text-dark @error('phoneNo') is-invalid @enderror"
                                           id="phoneNo" type="tel" name="phoneNo"
                                           value="{{ old('phoneNo', $admin->phone ?? '') }}"
                                           placeholder="+234 000 00 000">
                                    @error('phoneNo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3 col-md-12 text-center">
                                    <button type="submit" class="btn btn-primary px-4">Save Fields</button>
                                </div>
                            </form><!--Form end-->
                        </div><!--end col-->

                        <div class="col-md-4">
                            <div class="card">
                                <h3 class="card-header text-center text-dark">Change Password</h3>
                                <div class="card-body">

                                    <!-- Password Change Form -->
                                    <form action="{{ route('admin.profile.change-password') }}" method="POST">
                                        @csrf

                                        <div class="mb-3">
                                            <label for="newPass" class="form-label">New Password</label>
                                            <input class="form-control py-2 ps-3 text-dark @error('newPass') is-invalid @enderror"
                                                   id="newPass" type="password" name="newPass"
                                                   placeholder="..........">
                                            @error('newPass')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="confirmPass" class="form-label">Confirm Password</label>
                                            <input class="form-control py-2 ps-3 text-dark @error('confirmPass') is-invalid @enderror"
                                                   id="confirmPass" type="password" name="confirmPass"
                                                   placeholder="..........">
                                            @error('confirmPass')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3 text-center">
                                            <button type="submit" class="btn btn-primary px-4 w-100 py-2">Save Password</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div><!--end col-->




                        <div class="col-md-12">
                            <div class="card">
                                <h3 class="card-header text-center text-dark">Select Preferred Provider</h3>
                                <div class="card-body">

                                    {{-- Show current default provider --}}
                                    <div class="alert alert-info text-center">
                                        Current Default Provider:
                                        <strong class="text-uppercase">
                                            {{ \App\Models\Settings::get('preferred_provider', 'Not Set') }}
                                        </strong>
                                    </div>

                                    <!-- Provider Selection Form -->
                                    <form action="{{ route('user.save-provider') }}" method="POST">
                                        @csrf

                                        <div class="mb-3">
                                            <label for="preferred_provider" class="form-label">Choose Default Provider</label>
                                            <select name="preferred_provider" class="form-control">
                                                <option value="paystack" {{ \App\Models\Settings::get('preferred_provider') === 'paystack' ? 'selected' : '' }}>Paystack</option>
                                                <option value="nomba" {{ \App\Models\Settings::get('preferred_provider') === 'nomba' ? 'selected' : '' }}>Nomba</option>
                                            </select>
                                        </div>

                                        <button class="btn btn-primary">Save Default Provider</button>
                                    </form>

                                </div>
                            </div>
                        </div><!--end col-->

                    </div> <!-- end row -->
                </div>
                <!-- container -->

            </div>


        </div>

    </div>


@endsection

