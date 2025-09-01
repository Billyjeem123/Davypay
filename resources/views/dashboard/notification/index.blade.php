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
                                <h1 class="page-title text-dark">Notifications</h1>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3 ">
                        <div class="col-md-12 bg-light py-3">
                            <div class="card">
                                <div class="card-body">
                                    <table id="sms-datatable"
                                           class="table table-hover dt-responsive nowrap w-100 table-centered mb-0">
                                        <thead>
                                        <tr>
                                            <th class="text-start">Notification</th>
                                            <th class="text-end">Date</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @forelse($notifications as $notification)
                                            <tr>
                                                <td class="text-start">
                                                    <strong>{{ $notification->data['title'] ?? 'No Title' }}</strong>
                                                    <p>{{ $notification->data['message'] ?? 'No message provided.' }}</p>
                                                </td>
                                                <td class="text-end">
                                                    {{ \Carbon\Carbon::parse($notification->created_at)->format('d M, Y h:i A') }}
                                                </td>

                                            </tr>
                                        @empty

                                        @endforelse
                                        </tbody>
                                    </table>

                                </div>
                            </div>
                        </div>
                    </div> <!-- end row -->
                </div>
                <!-- container -->

            </div>
            <!-- content -->

        </div>

    </div>


@endsection

