@extends('dashboard.layout.contact')

@section('content')

    <div class="wrapper">

        <div class="content-page">
            <div class="content">

                <!-- Start Content-->
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <div class="page-title-box">
                                <h1 class="page-title text-dark">Contact</h1>
                            </div>
                        </div>
                        <div class="col-md-6 py-2 text-end">

                            <button type="button" class="btn btn-sm btn-primary mb-2" data-bs-toggle="modal"
                                    data-bs-target="#addContactModal"><i class="uil-plus"></i> Add Contact</button>
                        </div>
                    </div>

                    <div class="row mb-3 bg-white">
                        <div class="col-md-12">
                            <ul class="nav nav-tabs nav-bordered mb-3">
                                <li class="nav-item">
                                    <a href="#contacts" data-bs-toggle="tab" aria-expanded="false"
                                       class="nav-link active">
                                        <span class="">Contacts</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#contactGroups" data-bs-toggle="tab" aria-expanded="false"
                                       class="nav-link">
                                        <span class="">Contact Group</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#importContact" data-bs-toggle="tab" aria-expanded="true" class="nav-link">
                                        <span class="">Import Contacts</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#exportContact" data-bs-toggle="tab" aria-expanded="false"
                                       class="nav-link">
                                        <span class="">Export Contacts</span>
                                    </a>
                                </li>
                            </ul>
                            <div class="tab-content">
                                <div class="tab-pane show active" id="contacts">
                                    <table id="allContacts-datatable"
                                           class="table table-hover dt-responsive nowrap w-100 table-centered mb-0">
                                        <thead>
                                        <tr>
                                            <th><input type="checkbox" name="" id=""></th>
                                            <th>S/N</th>
                                            <th>Name</th>
                                            <th>Phone</th>
                                            <th>Email</th>
                                            <th>Action</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr>
                                            <td><input type="checkbox" name="" id=""></td>
                                            <td>1</td>
                                            <td>John Doe</td>
                                            <td>+234 000 00 000</td>
                                            <td>example@gmail.com</td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-secondary px-1"
                                                        data-bs-toggle="modal" data-bs-target="#editContactModal"><i
                                                        class="uil-edit-alt"></i></button>
                                                &nbsp;
                                                <button type="button" class="btn btn-sm btn-dark px-1"
                                                        data-bs-toggle="modal" data-bs-target="#contactDetailsModal"><i
                                                        class="uil-eye"></i></button>
                                                &nbsp;
                                                <button type="button" class="btn btn-sm btn-danger px-1"><i
                                                        class="uil-trash-alt"></i></button>
                                            </td>

                                        </tr>
                                        </tbody>
                                    </table>
                                </div><!-- end tab-pane -->
                                <div class="tab-pane" id="contactGroups">
                                    <table id="contactGroup-datatable"
                                           class="table table-hover dt-responsive nowrap w-100 table-centered mb-0">
                                        <thead>
                                        <tr>
                                            <th><input type="checkbox" name="" id=""></th>
                                            <th>Group ID</th>
                                            <th>Group Name </th>
                                            <th>Count</th>
                                            <th>Action</th>
                                        </tr>
                                        </thead>
                                        <tbody class="text-center">
                                        <tr>
                                            <td><input type="checkbox" name="" id=""></td>
                                            <td>242333</td>
                                            <td>Admin Group</td>
                                            <td>10</td>
                                            <td>
                                                <button class="btn btn-sm btn-secondary px-1"><i
                                                        class="uil-edit-alt"></i></button>
                                                &nbsp;
                                                <button class="btn btn-sm btn-danger px-1"><i
                                                        class="uil-trash-alt"></i></button>
                                            </td>

                                        </tr>
                                        </tbody>
                                    </table>
                                </div><!-- end tab-pane -->
                                <div class="tab-pane" id="importContact">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <form>
                                                <div class="mb-3">
                                                    <label for="messageChannel" class="form-label">Select Group</label>
                                                    <select class="form-select py-2 ps-3 text-dark" id="messageChannel">
                                                        <option>Group1</option>
                                                        <option> #</option>
                                                        <option> #</option>
                                                        <option> #</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <button class="btn btn-primary px-4">Import</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div><!-- end tab-pane -->
                                <div class="tab-pane" id="exportContact">
                                    <div class="table-responsive">
                                        <table class="table table-hover nowrap w-100 table-centered mb-0">
                                            <thead>
                                            <tr>
                                                <th><input type="checkbox" name="" id=""></th>
                                                <th>Group ID</th>
                                                <th>Group Name </th>
                                                <th>Count</th>
                                            </tr>
                                            </thead>
                                            <tbody class="text-center">
                                            <tr>
                                                <td><input type="checkbox" name="" id=""></td>
                                                <td>242333</td>
                                                <td>Admin Group</td>
                                                <td>10</td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <form class="row mt-4">
                                        <div class="form-check col-sm-3 mb-3">
                                            <input type="checkbox" class="form-check-input" id="exportExcel">
                                            <label class="form-check-label" for="exportExcel">Export to Excel</label>
                                        </div>
                                        <div class="form-check col-sm-3 mb-3">
                                            <input type="checkbox" class="form-check-input" id="exportCsv">
                                            <label class="form-check-label" for="exportCsv">Export to CSV</label>
                                        </div>
                                        <div class="form-check col-sm-3 mb-3">
                                            <input type="checkbox" class="form-check-input" id="exportTxt">
                                            <label class="form-check-label" for="exportTxt">Export to TXT</label>
                                        </div>
                                        <div class="col-12 mb-3">
                                            <button class="btn btn-primary">
                                                <i class="uil-plus"></i>
                                                Export Contacts
                                            </button>
                                        </div>
                                    </form>
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

        </div>

        <!-- ============================================================== -->
        <!-- Modals -->
        <!-- Add contact modal -->
        <div class="modal fade" id="addContactModal" tabindex="-1" role="dialog" aria-labelledby="addContactModalLabel"
             aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title text-dark" id="addContactModalLabel">Add Contact</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                    </div>
                    <div class="modal-body">
                        <form class="row">
                            <div class="mb-3 col-md-6">
                                <label for="contactName" class="form-label">Campaign Name</label>
                                <input class="form-control py-2 ps-3 text-dark" id="contactName" type="text"
                                       name="contactName" placeholder="e.g John Doe">
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="contactPhone" class="form-label">Phone</label>
                                <input class="form-control py-2 ps-3 text-dark" id="contactPhone" type="tel"
                                       name="contactPhone" placeholder="e.g +234 000 00 000">
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="contactEmail" class="form-label">Email</label>
                                <input class="form-control py-2 ps-3 text-dark" id="contactEmail" type="email"
                                       name="contactEmail" placeholder="e.g example@gmail.com">
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="contactDob" class="form-label">Date of Birth</label>
                                <input class="form-control py-2 ps-3 text-dark" id="contactDob" type="date"
                                       name="contactDob" placeholder="Enter Date">
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="contactGender" class="form-label">Gender</label>
                                <select class="form-select py-2 ps-3 text-dark" id="contactGender">
                                    <option selected disabled> Select Sex</option>
                                    <option> Male</option>
                                    <option> Female</option>

                                </select>
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="contactAnniversary" class="form-label">Anniversary</label>
                                <input class="form-control py-2 ps-3 text-dark" id="contactAnniversary" type="date"
                                       name="contactAnniversary" placeholder="Enter Date">
                            </div>
                            <div class="col-md-6 m-auto mb-3">
                                <button class="btn btn-primary w-100 py-2">Add Contact</button>
                            </div>
                        </form>
                    </div>
                </div><!-- /.modal-content -->
            </div><!-- /.modal-dialog -->
        </div><!-- /.modal -->

        <!-- Add contact modal -->
        <div class="modal fade" id="editContactModal" tabindex="-1" role="dialog"
             aria-labelledby="editContactModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title text-dark" id="editContactModalLabel">Edit Contact</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                    </div>
                    <div class="modal-body">
                        <form class="row">
                            <div class="mb-3 col-md-6">
                                <label for="contactName" class="form-label">Campaign Name</label>
                                <input class="form-control py-2 ps-3 text-dark" id="contactName" type="text"
                                       name="contactName" placeholder="e.g John Doe" value="John Doe">
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="contactPhone" class="form-label">Phone</label>
                                <input class="form-control py-2 ps-3 text-dark" id="contactPhone" type="tel"
                                       name="contactPhone" placeholder="e.g +234 000 00 000" value="+234 000 0 000">
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="contactEmail" class="form-label">Email</label>
                                <input class="form-control py-2 ps-3 text-dark" id="contactEmail" type="email"
                                       name="contactEmail" placeholder="e.g example@gmail.com" value="example@gmail.com">
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="contactDob" class="form-label">Date of Birth</label>
                                <input class="form-control py-2 ps-3 text-dark" id="contactDob" type="date"
                                       name="contactDob" placeholder="Enter Date">
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="contactGender" class="form-label">Gender</label>
                                <select class="form-select py-2 ps-3 text-dark" id="contactGender">
                                    <option disabled> Select Sex</option>
                                    <option selected> Male</option>
                                    <option> Female</option>

                                </select>
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="contactAnniversary" class="form-label">Anniversary</label>
                                <input class="form-control py-2 ps-3 text-dark" id="contactAnniversary" type="date"
                                       name="contactAnniversary" placeholder="Enter Date">
                            </div>
                            <div class="col-md-6 m-auto mb-3">
                                <button class="btn btn-primary w-100 py-2 mb-3">Save</button><br>
                                <button class="btn border-danger text-danger w-100 py-2">Delete Contact</button>
                            </div>
                        </form>
                    </div>
                </div><!-- /.modal-content -->
            </div><!-- /.modal-dialog -->
        </div><!-- /.modal -->

        <!--  Contact details modal -->
        <div class="modal fade" id="contactDetailsModal" tabindex="-1" role="dialog"
             aria-labelledby="contactDetailsModalLabel" aria-hidden="true">
            <div class="modal-dialog standard-modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title text-dark" id="contactDetailsModalLabel">Add Contact</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                    </div>
                    <div class="modal-body">
                        <div class="d-flex justify-content-start align-items-start gap-2 mb-3">
                            <h3 class="text-primary m-0 p-0"><i class="uil-user"></i></h3>
                            <p class="text-dark">
                                Name<br>
                                <strong class="text-dark">John Doe</strong>
                            </p>
                        </div>
                        <div class="d-flex justify-content-start align-items-start gap-2 mb-3">
                            <h3 class="text-primary m-0 p-0"><i class="uil-phone-alt"></i></h3>
                            <p class="text-dark">
                                Phone<br>
                                <strong class="text-dark">+234 000 00 000</strong>
                            </p>
                        </div>
                        <div class="d-flex justify-content-start align-items-start gap-2 mb-3">
                            <h3 class="text-primary m-0 p-0"><i class="uil-envelope"></i></h3>
                            <p class="text-dark">
                                Email<br>
                                <strong class="text-dark">example@gmail.com</strong>
                            </p>
                        </div>
                        <div class="d-flex justify-content-start align-items-start gap-2 mb-3">
                            <h3 class="text-primary m-0 p-0"><i class="uil-calender"></i></h3>
                            <p class="text-dark">
                                Date of Birth<br>
                                <strong class="text-dark">00/00/0000</strong>
                            </p>
                        </div>
                        <div class="d-flex justify-content-start align-items-start gap-2 mb-3">
                            <h3 class="text-primary m-0 p-0"><i class="uil-sperms"></i></h3>
                            <p class="text-dark">
                                Sex<br>
                                <strong class="text-dark">Male</strong>
                            </p>
                        </div>
                        <div class="d-flex justify-content-start align-items-start gap-2 mb-3">
                            <h3 class="text-primary m-0 p-0"><i class="uil-calender"></i></h3>
                            <p class="text-dark">
                                Anniversary<br>
                                <strong class="text-dark">00/00/0000</strong>
                            </p>
                        </div>
                        <div class="mb-3">
                            <button class="btn btn-secondary w-100 py-2 mb-3" data-bs-toggle="modal"
                                    data-bs-target="#editContactModal">Edit Contact</button><br>
                            <button class="btn border-danger text-danger w-100 py-2">Delete Contact</button>
                        </div>
                    </div>
                </div><!-- /.modal-content -->
            </div><!-- /.modal-dialog -->
        </div><!-- /.modal -->
        <!-- ============================================================== -->

    </div>


@endsection

