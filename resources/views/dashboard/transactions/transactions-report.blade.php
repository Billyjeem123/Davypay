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
                                <h1 class="page-title text-dark">Transaction Reports</h1>
                                <p class="text-muted">Comprehensive transaction analytics and insights</p>
                            </div>
                        </div>

                    </div>

                    <!-- Key Performance Indicators -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card stats-card border-left-success">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Total Revenue
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">₦12,450,000</div>
                                            <div class="text-xs text-success">+12% from last month</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="uil-money-bill text-success" style="font-size: 2rem;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card border-left-primary">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Success Rate
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">94.2%</div>
                                            <div class="text-xs text-success">+2.1% from last month</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="uil-chart-pie text-primary" style="font-size: 2rem;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card border-left-info">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Avg Transaction
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">₦24,823</div>
                                            <div class="text-xs text-info">+5% from last month</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="uil-calculator text-info" style="font-size: 2rem;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card border-left-warning">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Processing Time
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">2.3s</div>
                                            <div class="text-xs text-success">-0.5s from last month</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="uil-clock text-warning" style="font-size: 2rem;"></i>
                                        </div>
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
                transition: transform 0.2s;
            }
            .stats-card:hover {
                transform: translateY(-2px);
            }
            .border-left-success {
                border-left: 4px solid #28a745 !important;
            }
            .border-left-primary {
                border-left: 4px solid #007bff !important;
            }
            .border-left-info {
                border-left: 4px solid #17a2b8 !important;
            }
            .border-left-warning {
                border-left: 4px solid #ffc107 !important;
            }
            .border-left-danger {
                border-left: 4px solid #dc3545 !important;
            }
            .border-left-secondary {
                border-left: 4px solid #6c757d !important;
            }
            .text-xs {
                font-size: 0.75rem;
            }
            .text-gray-800 {
                color: #5a5c69 !important;
            }
            .text-purple {
                color: #6f42c1 !important;
            }
            .report-item {
                padding: 15px;
                border: 1px solid #e3e6f0;
                border-radius: 8px;
                height: 100%;
                transition: all 0.2s;
            }
            .report-item:hover {
                border-color: #d1ecf1;
                background-color: #f8f9fa;
            }
            .card-header {
                background-color: #f8f9fa;
                border-bottom: 1px solid #e3e6f0;
            }
        </style>

        <script>
            // Date range functionality
            document.getElementById('dateRange').addEventListener('change', function() {
                const value = this.value;
                if (value === 'custom') {
                    // Show custom date picker
                    console.log('Show custom date range picker');
                } else {
                    // Update reports based on selected range
                    console.log('Update reports for: ' + value);
                }
            });

            // Report generation functions
            function generateReport(reportType) {
                console.log('Generating report: ' + reportType);
                // Add your report generation logic here
            }

            // Add click handlers to all generate buttons
            document.querySelectorAll('.btn[class*="btn-outline"]').forEach(button => {
                button.addEventListener('click', function() {
                    const reportName = this.parentElement.querySelector('strong').textContent;
                    console.log('Generating report: ' + reportName);
                    // Add loading state
                    this.innerHTML = '<i class="uil-spinner uil-spin me-1"></i>Generating...';
                    this.disabled = true;

                    // Simulate report generation
                    setTimeout(() => {
                        this.innerHTML = 'Download Report';
                        this.disabled = false;
                        this.classList.remove('btn-outline-' + this.classList[2].split('-')[2]);
                        this.classList.add('btn-success');
                    }, 2000);
                });
            });
        </script>

@endsection
