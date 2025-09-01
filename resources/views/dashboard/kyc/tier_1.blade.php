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
                                <h1 class="page-title text-dark">KYC Management(Tier 1)</h1>
                            </div>
                        </div>
                    </div>


                    <!-- KYC Table -->
                    <div class="row mb-3 bg-white">
                        <div class="table-responsive">
                            <table id="sms-datatable" class="table table-hover dt-responsive nowrap w-100 table-centered mb-0">
                                <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>KYC Tier</th>
                                    <th>Verification Type</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($usersWithKyc as $user)
                                    <tr>
                                        <td>{{ $user->first_name }} {{ $user->last_name }}</td>
                                        <td>{{ ucfirst($user->account_level ?? 'N/A') }}</td>
                                        <td>Email</td>

                                    </tr>
                                @endforeach
                                </tbody>

                            </table>
                        </div>
                    </div>


                </div>
            </div>
        </div>
    </div>



@endsection
