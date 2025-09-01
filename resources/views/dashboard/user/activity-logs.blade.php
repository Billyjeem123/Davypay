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



                    <!-- Stats -->
                    <div class="row mb-4">
                        @foreach($stats as $stat)
                            <div class="col-md-3">
                                <div class="card stats-card border-left-{{ $stat['color'] }}">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-{{ $stat['color'] }} text-uppercase mb-1">
                                                    {{ $stat['label'] }}
                                                </div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                    {{ $stat['value'] }}
                                                </div>
                                                <div class="text-xs text-{{ $stat['trend'] === 'down' ? 'danger' : ($stat['trend'] === 'up' ? 'success' : 'muted') }}">
                                                    <i class="uil-arrow-{{ $stat['trend'] }}"></i>
                                                    {{ $stat['change'] }}
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="{{ $stat['icon'] }} text-{{ $stat['color'] }}" style="font-size: 2rem;"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
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
                                                <th>User</th>
                                                <th>Phone</th>
                                                <th>Registered at</th>
                                                <th>Action</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @forelse($users as $user)
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar-sm me-2">
                                                                <div class="avatar-title bg-info rounded-circle">
                                                                    {{ strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1)) }}
                                                                </div>
                                                            </div>
                                                            <div>
                                                                <strong>{{ $user->first_name }} {{ $user->last_name }}</strong><br>
                                                                <small class="text-muted">{{ $user->email }}</small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>{{$user->phone}}</td>
                                                    <td>
                                                        <span>{{ $user->created_at->format('d/m/Y') }}</span><br>
                                                        <small class="text-muted">{{ $user->created_at->format('H:i:s') }}</small>
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('users.log-details', $user->id) }}" class="btn btn-sm btn-primary px-2">
                                                            <i class="uil-eye"></i>
                                                        </a>
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



@endsection
