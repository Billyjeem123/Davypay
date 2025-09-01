@extends('dashboard.layout.report')

@section('content')

    <div class="wrapper">

        <div class="content-page report-page">
            <div class="content">

                <!-- Start Content-->
                <div class="container-fluid">

                    <div class="row mb-2">
                        <div class="col-md-6">
                            <div class="page-title-box">
                                <h1 class="page-title text-dark">Reports</h1>
                            </div>
                        </div>

                    </div>

                    <div class="row mb-3 bg-white">
                        <div class="col-md-12">
                            <ul class="nav nav-tabs nav-bordered mb-3">
                                <li class="nav-item">
                                    <a href="#campaignReport" data-bs-toggle="tab" aria-expanded="false"
                                       class="nav-link active">
                                        <span class="">Campaign Report</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#deliveryReport" data-bs-toggle="tab" aria-expanded="true"
                                       class="nav-link">
                                        <span class="">Delivery Report</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#scheduleReport" data-bs-toggle="tab" aria-expanded="false"
                                       class="nav-link">
                                        <span class="">Schedule Report</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#archivedReport" data-bs-toggle="tab" aria-expanded="false"
                                       class="nav-link">
                                        <span class="">Archived Report</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#creditHistory" data-bs-toggle="tab" aria-expanded="false"
                                       class="nav-link">
                                        <span class="">Credit History</span>
                                    </a>
                                </li>
                            </ul>
                            <div class="tab-content">
                                <div class="tab-pane show active" id="campaignReport">
                                    <div class="table-responsive">
                                        <table id="notification-datatable" class="table table-hover dt-responsive nowrap w-100 table-centered mb-0">
                                            <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Name</th>
                                                <th>Sender ID</th>
                                                <th>Message</th>
                                                <th>Interface</th>
                                                <th>Channel</th>
                                                <th>Credit Used</th>
                                                <th>Action</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <tr>
                                                <td>27/04/2025</td>
                                                <td>MRSVLT</td>
                                                <td>By Admin</td>
                                                <td class="text-start">Lorem ipsum dolor, sit amet consectetur
                                                    adipisicing elit. Itaque,
                                                    nemo.</td>
                                                <td>Https</td>
                                                <td>Trans</td>
                                                <td>10</td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-dark px-1"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#viewCampaignReportModal"><i
                                                            class="uil-eye"></i></button>
                                                    &nbsp;
                                                    <button class="btn btn-sm btn-success px-1"><i
                                                            class="uil-download-alt"></i></button>
                                                </td>

                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div><!-- end tab-pane -->
                                <div class="tab-pane" id="deliveryReport">
                                    <P>......</P>
                                </div><!-- end tab-pane -->
                                <div class="tab-pane" id="scheduleReport">
                                    <P>......</P>
                                </div><!-- end tab-pane -->
                                <div class="tab-pane" id="archivedReport">
                                    <P>......</P>
                                </div><!-- end tab-pane -->
                                <div class="tab-pane" id="creditHistory">
                                    <P>......</P>
                                </div><!-- end tab-pane -->
                            </div>
                        </div><!-- end col -->
                    </div> <!-- end row -->
                </div>
                <!-- container -->

            </div>
            <!-- content -->

            <!-- Footer Start -->
            <footer class="footer">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-6">
                            <script>document.write(new Date().getFullYear())</script> © Sendtruly
                        </div>
                        <div class="col-md-6">
                            <div class="text-md-end footer-links d-none d-md-block">
                                <a href="javascript: void(0);">About</a>
                                <a href="javascript: void(0);">Support</a>
                                <a href="javascript: void(0);">Contact Us</a>
                            </div>
                        </div>
                    </div>
                </div>
            </footer>
            <!-- end Footer -->
            <!-- ============================================================== -->
            <!-- Modals -->
            <!-- Add contact modal -->
            <div class="modal fade" id="viewCampaignReportModal" tabindex="-1" role="dialog"
                 aria-labelledby="viewCampaignReportModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title text-dark" id="viewCampaignReportModalLabel">Campaign Report</h4>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <small>Date</small>
                                    <h4 class="h4 text-dark">27/04/2025</h4>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <small>Name</small>
                                    <h4 class="h4 text-dark">MRSVLT</h4>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <small>Sender ID</small>
                                    <h4 class="h4 text-dark">By Admin</h4>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <small>Interface</small>
                                    <h4 class="h4 text-dark">https</h4>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <small>Message</small>
                                    <p class="text-dark">Lorem ipsum dolor, sit amet consectetur adipisicing elit.
                                        Itaque, nemo.</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <small>Channel</small>
                                    <h4 class="h4 text-dark">Trans</h4>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <small>Credit Used</small>
                                    <h4 class="h4 text-dark">10</h4>
                                </div>
                                <div class="col-md-12 text-center mb-3">
                                    <button type="button" class="btn btn-primary py-2 px-4">Download</button>
                                    <button type="button" data-bs-dismiss="modal"
                                            class="btn btn-dark py-2 px-4 rounded-3">Close</button>
                                </div>
                            </div>
                        </div>
                    </div><!-- /.modal-content -->
                </div><!-- /.modal-dialog -->
            </div><!-- /.modal -->

        </div>

    </div>


@endsection

