@extends('dashboard.layout.sms')

@section('content')

    <div class="wrapper">
        <div class="content-page sms-page">
            <div class="content">
                <div class="container-fluid">

                    <!-- Page Title -->
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <div class="page-title-box">
                                <h1 class="page-title text-dark">User Activity Logs</h1>
                                <p class="text-muted">Monitor and track user activities across the platform</p>
                            </div>
                        </div>

                    </div>





                    <!-- Activity Logs Table -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">Recent Activity Logs</h5>
                                    <div class="d-flex align-items-center">
                                        <span class="text-muted me-2">Show:</span>
                                        <select class="form-select form-select-sm me-2" style="width: auto;">
                                            <option>25</option>
                                            <option>50</option>
                                            <option>100</option>
                                        </select>
                                        <span class="text-muted">entries</span>
                                    </div>
                                </div>

                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table id="sms-datatable" class="table table-hover dt-responsive nowrap w-100 table-centered mb-0">
                                            <thead class="table-light">
                                            <tr>
                                                <th>ID</th>
                                                <th>User</th>
                                                <th>Activity</th>
                                                <th>Description</th>
                                                <th>IP Address</th>
                                                <th>Timestamp</th>
                                                <th>Action</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @forelse($activityLogs as $log)
                                                <tr>
                                                    <td>#LOG{{ str_pad($log->id, 3, '0', STR_PAD_LEFT) }}</td>

                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar-sm me-2">
                                                                <div class="avatar-title bg-info rounded-circle">
                                                                    {{ strtoupper(substr($log->user->first_name ?? 'U', 0, 1)) }}{{ strtoupper(substr($log->user->last_name ?? '', 0, 1)) }}
                                                                </div>
                                                            </div>
                                                            <div>
                                                                <strong>{{ $log->user->first_name ?? 'Unknown' }} {{ $log->user->last_name ?? '' }}</strong><br>
                                                                <small class="text-muted">{{ $log->user->email ?? 'No Email' }}</small>
                                                            </div>
                                                        </div>
                                                    </td>

                                                    <td>
                                        <span class="badge bg-primary-subtle text-primary">
                                            <i class="uil-setting"></i> {{ ucfirst(str_replace('_', ' ', $log->activity)) }}
                                        </span>
                                                    </td>

                                                    <td>{{ $log->description }}</td>

                                                    <td>
                                                        <span class="font-monospace">{{ $log->ip_address }}</span><br>
                                                        <small class="text-muted">Unknown Location</small> {{-- Replace with geolocation if needed --}}
                                                    </td>

                                                    <td>
                                                        <span>{{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y') }}</span><br>
                                                        <small class="text-muted">{{ \Carbon\Carbon::parse($log->created_at)->format('H:i:s') }}</small>
                                                    </td>

                                                    <td>
                                                        <button class="btn btn-sm btn-primary px-2"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#activityDetailModal"
                                                                onclick='loadActivityDetail(@json($log))'>
                                                            <i class="uil-eye"></i>
                                                        </button>


                                                    </td>
                                                </tr>
                                            @empty

                                            @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>


                </div>
            </div>

            <!-- Activity Detail Modal -->
            <div class="modal fade" id="activityDetailModal" tabindex="-1" role="dialog"
                 aria-labelledby="activityDetailModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title text-dark" id="activityDetailModalLabel">Activity Log Details</h4>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-primary">User Information</h6>
                                    <p><strong>User ID:</strong> <span id="modal-user-id"></span></p>
                                    <p><strong>Name:</strong> <span id="modal-user-name"></span></p>
                                    <p><strong>Email:</strong> <span id="modal-user-email"></span></p>
                                    <p><strong>Role:</strong> <span id="modal-user-role"></span></p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-success">Activity Details</h6>
                                    <p><strong>Activity:</strong> <span id="modal-activity"></span></p>
                                    <p><strong>Description:</strong> <span id="modal-description"></span></p>
                                    <p><strong>Timestamp:</strong> <span id="modal-timestamp"></span></p>
                                    <p><strong>Page URL:</strong> <span id="modal-page-url"></span></p>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <h6 class="text-warning">Technical Details</h6>
                                    <p><strong>IP Address:</strong> <span id="modal-ip-address"></span></p>
                                    <p><strong>Location:</strong> <span id="modal-location"></span></p>
                                    <p><strong>User Agent:</strong> <span id="modal-user-agent" class="text-break"></span></p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-info"></h6>
                                    <div id="modal-properties">
                                        <p><strong>Device:</strong> </p>
                                        <p><strong>Browser:</strong></p>
                                        <p><strong>OS:</strong> </p>
                                        <p><strong>:</strong> </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <!-- End Modal -->

        </div>

        <style>
            .stats-card {
                transition: transform 0.3s ease, box-shadow 0.3s ease;
                border: none;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .stats-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            }
            .border-left-primary {
                border-left: 4px solid #007bff !important;
            }
            .border-left-success {
                border-left: 4px solid #28a745 !important;
            }
            .border-left-warning {
                border-left: 4px solid #ffc107 !important;
            }
            .border-left-danger {
                border-left: 4px solid #dc3545 !important;
            }
            .text-xs {
                font-size: 0.75rem;
            }
            .text-gray-800 {
                color: #5a5c69 !important;
            }
            .avatar-sm {
                width: 40px;
                height: 40px;
            }
            .avatar-title {
                width: 100%;
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 600;
                font-size: 0.875rem;
            }
            .activity-item {
                padding: 8px;
                border-radius: 6px;
                background-color: #f8f9fa;
                transition: background-color 0.2s ease;
            }
            .activity-item:hover {
                background-color: #e9ecef;
            }
            .table-responsive {
                border-radius: 8px;
                overflow: hidden;
            }
            .table th {
                background-color: #f8f9fa;
                border-bottom: 2px solid #dee2e6;
                font-weight: 600;
                text-transform: uppercase;
                font-size: 0.8rem;
                letter-spacing: 0.5px;
            }
            .table td {
                vertical-align: middle;
                padding: 1rem 0.75rem;
            }
            .table-hover tbody tr:hover {
                background-color: #f8f9fa;
            }
            .badge {
                font-size: 0.75rem;
                padding: 0.375rem 0.75rem;
            }
            .btn-sm {
                padding: 0.25rem 0.5rem;
                font-size: 0.875rem;
            }
            .font-monospace {
                font-family: 'Courier New', monospace !important;
                background-color: #f8f9fa;
                padding: 2px 6px;
                border-radius: 4px;
                font-size: 0.8rem;
            }
            .card {
                border: none;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                transition: box-shadow 0.3s ease;
            }
            .card:hover {
                box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            }
            .card-header {
                background-color: #f8f9fa;
                border-bottom: 1px solid #dee2e6;
                padding: 1rem 1.25rem;
            }
            .bg-success-subtle {
                background-color: #d1eddb !important;
            }
            .bg-info-subtle {
                background-color: #d1ecf1 !important;
            }
            .bg-warning-subtle {
                background-color: #fff3cd !important;
            }
            .bg-secondary-subtle {
                background-color: #e2e3e5 !important;
            }
            .bg-primary-subtle {
                background-color: #cfe2ff !important;
            }
            .text-success {
                color: #28a745 !important;
            }
            .text-info {
                color: #17a2b8 !important;
            }
            .text-warning {
                color: #ffc107 !important;
            }
            .text-secondary {
                color: #6c757d !important;
            }
            .text-primary {
                color: #007bff !important;
            }
        </style>

        <script>
            function loadActivityDetail(log) {
                // User Info
                document.getElementById('modal-user-id').textContent = log.user?.id ?? 'N/A';
                document.getElementById('modal-user-name').textContent = `${log.user?.first_name ?? 'N/A'} ${log.user?.last_name ?? ''}`;
                document.getElementById('modal-user-email').textContent = log.user?.email ?? 'N/A';
                document.getElementById('modal-user-role').textContent = log.user?.role?.name ?? 'N/A'; // If roles are related

                // Activity Info
                document.getElementById('modal-activity').textContent = log.activity ?? 'N/A';
                document.getElementById('modal-description').textContent = log.description ?? 'N/A';
                document.getElementById('modal-timestamp').textContent = new Date(log.created_at).toLocaleString();
                document.getElementById('modal-page-url').textContent = log.page_url ?? 'N/A';

                // Technical Info
                document.getElementById('modal-ip-address').textContent = log.ip_address ?? 'N/A';
                document.getElementById('modal-location').textContent = 'Unknown'; // You can replace with geo lookup if needed
                document.getElementById('modal-user-agent').textContent = log.user_agent ?? 'N/A';

                // Additional Properties
                const propertiesDiv = document.getElementById('modal-properties');
                propertiesDiv.innerHTML = ''; // Clear old content

                if (log.properties && typeof log.properties === 'object') {
                    Object.entries(log.properties).forEach(([key, value]) => {
                        propertiesDiv.innerHTML += `<p><strong>${key.replace(/_/g, ' ')}:</strong> ${value}</p>`;
                    });
                } else {
                    propertiesDiv.innerHTML = '<p class="text-muted">No additional properties</p>';
                }
            }



        </script>





@endsection
