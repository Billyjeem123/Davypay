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
                                <h1 class="page-title text-dark">Transaction Fraud Checks</h1>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="page-title-box text-end">
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#logViewerModal">
                                    <i class="uil-file-alt"></i> View Log Files
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card stats-card border-left-success">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Checks</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">1,247</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card border-left-info">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Passed</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">1,089</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card border-left-warning">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Failed</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">158</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card border-left-danger">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Blocked</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">43</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <form class="row g-3">
                                        <div class="col-md-2">
                                            <select class="form-select">
                                                <option selected>All Status</option>
                                                <option value="passed">Passed</option>
                                                <option value="failed">Failed</option>
                                                <option value="blocked">Blocked</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <select class="form-select">
                                                <option selected>All Types</option>
                                                <option value="transfer">Transfer</option>
                                                <option value="deposit">Deposit</option>
                                                <option value="withdrawal">Withdrawal</option>
                                                <option value="bill_payment">Bill Payment</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <select class="form-select">
                                                <option selected>Risk Level</option>
                                                <option value="low">Low (0-30)</option>
                                                <option value="medium">Medium (31-70)</option>
                                                <option value="high">High (71-100)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <input type="date" class="form-control" placeholder="From Date">
                                        </div>
                                        <div class="col-md-2">
                                            <input type="date" class="form-control" placeholder="To Date">
                                        </div>
                                        <div class="col-md-2">
                                            <button type="submit" class="btn btn-primary">Filter</button>
                                            <button type="button" class="btn btn-secondary">Clear</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Fraud Checks Table -->
                    <div class="row mb-3 bg-white">
                        <div class="table-responsive">
                            <table id="notification-datatable" class="table table-hover dt-responsive nowrap w-100 table-centered mb-0">
                                <thead>
                                <tr>
                                    <th>Check ID</th>
                                    <th>User</th>
                                    <th>Amount</th>
                                    <th>Transaction Type</th>
                                    <th>Status</th>
                                    <th>Risk Score</th>
                                    <th>Action Taken</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                                </thead>
                                <tbody>

                                <tr>
                                    <td><strong>#FC005</strong></td>
                                    <td>
                                        <div>
                                            <strong>David Brown</strong><br>
                                            <small class="text-muted">david@example.com</small>
                                        </div>
                                    </td>
                                    <td>
                                        <strong class="text-success">₦10,000.00</strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">Bill Payment</span>
                                    </td>
                                    <td><span class="badge bg-success">passed</span></td>
                                    <td>
                                        <span class="badge bg-success">8</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">none</span>
                                    </td>
                                    <td>12/07/2025 14:15</td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#fraudDetailModal"><i class="uil-eye"></i></button>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Fraud Detail Modal -->
                    <div class="modal fade" id="fraudDetailModal" tabindex="-1" role="dialog"
                         aria-labelledby="fraudDetailModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-xl">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h4 class="modal-title text-dark">Fraud Check Details</h4>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6><strong>Basic Information</strong></h6>
                                            <p><strong>Check ID:</strong> #FC001</p>
                                            <p><strong>User:</strong> John Doe</p>
                                            <p><strong>Email:</strong> john@example.com</p>
                                            <p><strong>Amount:</strong> <span class="text-danger">₦500,000.00</span></p>
                                            <p><strong>Transaction Type:</strong> <span class="badge bg-info">Transfer</span></p>
                                            <p><strong>Status:</strong> <span class="badge bg-danger">blocked</span></p>
                                            <p><strong>Risk Score:</strong> <span class="badge bg-danger">95</span></p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6><strong>System Information</strong></h6>
                                            <p><strong>IP Address:</strong> 192.168.1.100</p>
                                            <p><strong>User Agent:</strong> Mozilla/5.0 (Windows NT 10.0; Win64; x64)...</p>
                                            <p><strong>Action Taken:</strong> <span class="badge bg-warning">block_transaction</span></p>
                                            <p><strong>Message:</strong> High risk transaction detected - unusual amount</p>
                                            <p><strong>Created:</strong> 13/07/2025 10:30:45</p>
                                            <p><strong>Updated:</strong> 13/07/2025 10:30:45</p>
                                        </div>
                                    </div>
                                    <div class="row mt-4">
                                        <div class="col-md-6">
                                            <h6><strong>Risk Factors</strong></h6>
                                            <div class="alert alert-danger">
                                                <ul class="mb-0">
                                                    <li>Unusual large amount (Score: 40)</li>
                                                    <li>New IP address (Score: 25)</li>
                                                    <li>Off-hours transaction (Score: 20)</li>
                                                    <li>Velocity check failed (Score: 10)</li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <h6><strong>Check Details</strong></h6>
                                            <div class="alert alert-info">
                                                <ul class="mb-0">
                                                    <li><strong>Amount Check:</strong> Failed (>₦100,000)</li>
                                                    <li><strong>IP Verification:</strong> Failed (New IP)</li>
                                                    <li><strong>Time Check:</strong> Failed (2:30 AM)</li>
                                                    <li><strong>Velocity Check:</strong> Failed (>3 transactions/hour)</li>
                                                    <li><strong>Device Check:</strong> Passed</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-4">
                                        <div class="col-12">
                                            <h6><strong>Additional Context</strong></h6>
                                            <div class="alert alert-secondary">
                                                <p><strong>Previous similar transactions:</strong> 0 in last 30 days</p>
                                                <p><strong>Account age:</strong> 45 days</p>
                                                <p><strong>Previous fraud checks:</strong> 2 passed, 0 failed</p>
                                                <p><strong>Geographic location:</strong> Lagos, Nigeria</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="button" class="btn btn-success">Approve Transaction</button>
                                    <button type="button" class="btn btn-warning">Review Later</button>
                                    <button type="button" class="btn btn-danger">Block User</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Log Viewer Modal -->
                    <div class="modal fade" id="logViewerModal" tabindex="-1" role="dialog"
                         aria-labelledby="logViewerModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-xl">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h4 class="modal-title text-dark">Fraud Check Log Viewer</h4>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <select class="form-select" id="logTypeSelect">
                                                <option value="">Select log type</option>
                                                <option value="fraud">fraud.logs</option>
                                                <option value="bills">bills.logs</option>
                                                <option value="transactions">transaction.logs</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <input type="file" class="form-control" id="logFileInput" accept=".log,.txt">
                                        </div>
                                        <div class="col-md-4">
                                            <button class="btn btn-primary" onclick="loadLogFile()">Load Log File</button>
                                            <button class="btn btn-success" onclick="refreshLogs()">Refresh</button>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="log-viewer-container">
                                                <pre id="logContent" class="log-content">Select a log file to view its contents...</pre>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="button" class="btn btn-primary" onclick="downloadLog()">Download Log</button>
                                    <button type="button" class="btn btn-warning" onclick="clearLogs()">Clear Display</button>
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
        .border-left-info {
            border-left: 4px solid #17a2b8 !important;
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
        .log-viewer-container {
            background: #1a1a1a;
            border-radius: 8px;
            padding: 20px;
            max-height: 500px;
            overflow-y: auto;
        }
        .log-content {
            color: #00ff00;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.4;
            white-space: pre-wrap;
            word-wrap: break-word;
            margin: 0;
            background: transparent;
            border: none;
        }
        .log-content .error {
            color: #ff4444;
        }
        .log-content .warning {
            color: #ffaa00;
        }
        .log-content .info {
            color: #44aaff;
        }
        .log-content .success {
            color: #44ff44;
        }
    </style>

    <script>
        function loadLogFile() {
            const fileInput = document.getElementById('logFileInput');
            const logContent = document.getElementById('logContent');

            if (fileInput.files.length === 0) {
                alert('Please select a log file first.');
                return;
            }

            const file = fileInput.files[0];
            const reader = new FileReader();

            reader.onload = function(e) {
                const content = e.target.result;
                logContent.textContent = content;
                highlightLogContent();
            };

            reader.readAsText(file);
        }

        function highlightLogContent() {
            const logContent = document.getElementById('logContent');
            let content = logContent.textContent;

            // Highlight different log levels
            content = content.replace(/\[ERROR\].*$/gm, '<span class="error">$&</span>');
            content = content.replace(/\[WARNING\].*$/gm, '<span class="warning">$&</span>');
            content = content.replace(/\[INFO\].*$/gm, '<span class="info">$&</span>');
            content = content.replace(/\[SUCCESS\].*$/gm, '<span class="success">$&</span>');

            logContent.innerHTML = content;
        }

        function refreshLogs() {
            const logTypeSelect = document.getElementById('logTypeSelect');
            const logContent = document.getElementById('logContent');

            if (logTypeSelect.value === '') {
                alert('Please select a log type first.');
                return;
            }

            // Simulate loading logs from server
            logContent.textContent = 'Loading logs...';

            setTimeout(() => {
                const sampleLog = `[2025-07-13 10:30:45] [INFO] Fraud check initiated for user ID: 123
[2025-07-13 10:30:45] [WARNING] High risk transaction detected - Amount: ₦500,000.00
[2025-07-13 10:30:45] [ERROR] Transaction blocked due to risk score: 95
[2025-07-13 10:30:46] [INFO] User notification sent
[2025-07-13 10:30:46] [SUCCESS] Fraud check completed - Status: blocked`;

                logContent.textContent = sampleLog;
                highlightLogContent();
            }, 1000);
        }

        function downloadLog() {
            const logContent = document.getElementById('logContent');
            const content = logContent.textContent;

            if (content === 'Select a log file to view its contents...' || content === 'Loading logs...') {
                alert('No log content to download.');
                return;
            }

            const blob = new Blob([content], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'fraud_logs_' + new Date().toISOString().split('T')[0] + '.log';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }

        function clearLogs() {
            document.getElementById('logContent').textContent = 'Select a log file to view its contents...';
        }
    </script>

@endsection
