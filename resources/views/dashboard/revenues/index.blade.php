@extends('dashboard.layout.sms')

@section('content')
    <div class="wrapper">
        <div class="content-page sms-page">
            <div class="content">
                <div class="container-fluid">

                    <!-- Header -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h1 class="page-title text-dark">Platform Revenue Dashboard</h1>
                            <p class="text-muted">Monitor profit, revenue, and transactions in real time</p>
                        </div>
                    </div>

                    <!-- Stats Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card stats-card border-left-success shadow-sm">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Profit</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">₦{{ number_format($stats['total_profit'], 2) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card border-left-primary shadow-sm">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Revenue</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">₦{{ number_format($stats['total_revenue'], 2) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card border-left-info shadow-sm">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Transactions</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total_transactions'] }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card border-left-warning shadow-sm">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Successful</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['successful_transactions'] }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Revenue Bar Chart -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card shadow-sm">
                                <div class="card-header bg-light">
                                    <h4 class="card-title mb-0">Monthly Profit & Revenue Trend</h4>
                                </div>
                                <div class="card-body">
                                    <canvas id="revenueChart" height="120"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Revenue Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card shadow-sm">
                                <div class="card-header bg-light">
                                    <h4 class="card-title mb-0">Revenue Transactions</h4>
                                </div>
                                <div class="">
                                    <div class="">
                                            <table id="sms-datatable" class="table table-hover dt-responsive nowrap w-100 table-centered mb-0">
                                            <thead class="table-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Transaction ID</th>
                                                <th>Product</th>
                                                <th>Platform</th>
                                                <th>Amount</th>
                                                <th>Profit</th>
                                                <th>Status</th>
                                                <th>Channel</th>
                                                <th>Date</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($transactions as $rev)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td><span class="badge bg-dark">{{ $rev->transaction_id }}</span></td>
                                                    <td>{{ $rev->product_name }}</td>
                                                    <td>{{ ucfirst($rev->platform) }}</td>
                                                    <td>₦{{ number_format($rev->amount, 2) }}</td>
                                                    <td class="text-success">₦{{ number_format($rev->profit, 2) }}</td>
                                                    <td>
                                                        @php
                                                            $status = strtolower($rev->status);
                                                        @endphp
                                                        <span class="badge bg-{{ in_array($status, ['delivered', 'completed']) ? 'success' : 'danger' }}">
    {{ ucfirst($status) }}
</span>

                                                    </td>
                                                    <td>{{ ucfirst($rev->channel ?? 'Web') }}</td>
                                                    <td>{{ \Carbon\Carbon::parse($rev->transaction_date)->format('d M, Y h:i A') }}</td>
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
            </div>
        </div>
    </div>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($chartMonths) !!},
                datasets: [
                    {
                        label: 'Profit (₦)',
                        data: {!! json_encode($chartProfits) !!},
                        backgroundColor: 'rgba(40, 167, 69, 0.6)',
                        borderColor: 'rgba(40, 167, 69, 1)',
                        borderWidth: 1,
                    },
                    {
                        label: 'Revenue (₦)',
                        data: {!! json_encode($chartRevenues) !!},
                        backgroundColor: 'rgba(91, 115, 232, 0.6)',
                        borderColor: 'rgba(91, 115, 232, 1)',
                        borderWidth: 1,
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true, ticks: { callback: value => '₦' + value.toLocaleString() } },
                },
                plugins: {
                    legend: { position: 'top' },
                    tooltip: { mode: 'index', intersect: false },
                },
            },
        });
    </script>

    <style>
        .stats-card { transition: transform .2s; border-radius: 10px; }
        .stats-card:hover { transform: translateY(-3px); }
        .border-left-primary { border-left: 4px solid #5b73e8 !important; }
        .border-left-success { border-left: 4px solid #28a745 !important; }
        .border-left-info { border-left: 4px solid #17a2b8 !important; }
        .border-left-warning { border-left: 4px solid #ffc107 !important; }
    </style>
@endsection
