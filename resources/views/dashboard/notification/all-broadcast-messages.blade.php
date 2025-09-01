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
                                <h1 class="page-title text-dark">Announcement History</h1>
                                <p class="text-muted">View and manage all announcement broadcasts and their statistics</p>
                            </div>
                        </div>
                        <div class="col-md-6 py-2 text-end">
                            <a href="#" class="btn btn-sm btn-success mb-2"><i class="uil-plus"></i> New Announcement</a>
                            <a href="#" class="btn btn-sm btn-info mb-2"><i class="uil-export"></i> Export Report</a>
                            <a href="#" class="btn btn-sm btn-secondary mb-2"><i class="uil-arrow-left"></i> Back to Dashboard</a>
                        </div>
                    </div>

                    <!-- Overall Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-2">
                            <div class="card stats-card border-left-primary">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Announcements</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">1,245</div>
                                    <div class="text-xs text-muted">+12% from last month</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card stats-card border-left-success">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Delivered</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">1,198</div>
                                    <div class="text-xs text-success">96.2% success rate</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card stats-card border-left-warning">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">32</div>
                                    <div class="text-xs text-warning">2.6% in queue</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card stats-card border-left-danger">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Failed</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">15</div>
                                    <div class="text-xs text-danger">1.2% failure rate</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card stats-card border-left-info">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Recipients</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">2.4M</div>
                                    <div class="text-xs text-info">Messages sent</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card stats-card border-left-secondary">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Total Cost</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">₦12.5M</div>
                                    <div class="text-xs text-muted">This month</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters and Search -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row align-items-end">
                                        <div class="col-md-3">
                                            <label for="dateFrom" class="form-label">From Date</label>
                                            <input type="date" class="form-control" id="dateFrom" value="2025-07-01">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="dateTo" class="form-label">To Date</label>
                                            <input type="date" class="form-control" id="dateTo" value="2025-07-13">
                                        </div>
                                        <div class="col-md-2">
                                            <label for="statusFilter" class="form-label">Status</label>
                                            <select class="form-select" id="statusFilter">
                                                <option value="">All Status</option>
                                                <option value="sent">Sent</option>
                                                <option value="pending">Pending</option>
                                                <option value="failed">Failed</option>
                                                <option value="draft">Draft</option>
                                                <option value="scheduled">Scheduled</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label for="categoryFilter" class="form-label">Category</label>
                                            <select class="form-select" id="categoryFilter">
                                                <option value="">All Categories</option>
                                                <option value="general">General</option>
                                                <option value="system">System</option>
                                                <option value="promotion">Promotion</option>
                                                <option value="maintenance">Maintenance</option>
                                                <option value="security">Security</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-primary" onclick="filterAnnouncements()">
                                                <i class="uil-filter"></i> Filter
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Announcement History Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="row align-items-center">
                                        <div class="col-md-6">
                                            <h4 class="card-title">Announcement History</h4>
                                        </div>
                                        <div class="col-md-6 text-end">
                                            <div class="btn-group" role="group">
                                                <input type="radio" class="btn-check" name="view" id="tableView" checked>
                                                <label class="btn btn-outline-primary btn-sm" for="tableView">
                                                    <i class="uil-list-ul"></i> Table
                                                </label>
                                                <input type="radio" class="btn-check" name="view" id="cardView">
                                                <label class="btn btn-outline-primary btn-sm" for="cardView">
                                                    <i class="uil-apps"></i> Cards
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <!-- Table View -->
                                    <div id="tableViewContent">
                                        <div class="table-responsive">
                                            <table id="notification-datatable" class="table table-hover dt-responsive nowrap w-100 table-centered mb-0">
                                                <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Title</th>
                                                    <th>Category</th>
                                                    <th>Recipients</th>
                                                    <th>Delivery Methods</th>
                                                    <th>Status</th>
                                                    <th>Sent Date</th>
                                                    <th>Success Rate</th>
                                                    <th>Cost</th>
                                                    <th>Actions</th>
                                                </tr>
                                                </thead>
                                                <tbody id="announcementTableBody">
                                                <!-- Table content will be populated by JavaScript -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- Card View -->
                                    <div id="cardViewContent" style="display: none;">
                                        <div class="row" id="announcementCards">
                                            <!-- Cards will be populated by JavaScript -->
                                        </div>
                                    </div>


                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Announcement Details Modal -->
    <div class="modal fade" id="announcementModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Announcement Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Title:</strong> <span id="modalTitle"></span></p>
                            <p><strong>Category:</strong> <span id="modalCategory"></span></p>
                            <p><strong>Priority:</strong> <span id="modalPriority"></span></p>
                            <p><strong>Created By:</strong> <span id="modalCreatedBy"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Recipients:</strong> <span id="modalRecipients"></span></p>
                            <p><strong>Delivery Methods:</strong> <span id="modalDeliveryMethods"></span></p>
                            <p><strong>Status:</strong> <span id="modalStatus"></span></p>
                            <p><strong>Sent Date:</strong> <span id="modalSentDate"></span></p>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <p><strong>Message:</strong></p>
                            <div class="border p-3 bg-light rounded" id="modalMessage"></div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="h4 text-primary" id="modalTotalSent">0</div>
                                <div class="text-muted">Total Sent</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="h4 text-success" id="modalDelivered">0</div>
                                <div class="text-muted">Delivered</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="h4 text-warning" id="modalPending">0</div>
                                <div class="text-muted">Pending</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="h4 text-danger" id="modalFailed">0</div>
                                <div class="text-muted">Failed</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="resendAnnouncement()">Resend</button>
                    <button type="button" class="btn btn-success" onclick="duplicateAnnouncement()">Duplicate</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Sample announcement data
        const announcements = [
            {
                id: 'ANN001',
                title: 'System Maintenance Notice',
                category: 'maintenance',
                recipients: 12450,
                deliveryMethods: ['Email', 'SMS'],
                status: 'sent',
                sentDate: '2025-07-13 09:30:00',
                successRate: 98.5,
                cost: 75000,
                message: 'We will be performing scheduled maintenance on our systems from 2:00 AM to 4:00 AM tomorrow. Services may be temporarily unavailable during this time.',
                priority: 'high',
                createdBy: 'Admin User',
                totalSent: 12450,
                delivered: 12263,
                pending: 0,
                failed: 187
            },
            {
                id: 'ANN002',
                title: 'Special Promotion Alert',
                category: 'promotion',
                recipients: 8500,
                deliveryMethods: ['Email', 'SMS', 'Push'],
                status: 'sent',
                sentDate: '2025-07-12 14:15:00',
                successRate: 96.2,
                cost: 52000,
                message: 'Great news! We are offering a special promotion with up to 50% discount on all services. This limited-time offer is valid until the end of this month.',
                priority: 'normal',
                createdBy: 'Marketing Team',
                totalSent: 8500,
                delivered: 8177,
                pending: 0,
                failed: 323
            },
            {
                id: 'ANN003',
                title: 'Security Update Required',
                category: 'security',
                recipients: 15000,
                deliveryMethods: ['Email', 'SMS'],
                status: 'pending',
                sentDate: '2025-07-13 16:00:00',
                successRate: 0,
                cost: 90000,
                message: 'For your security, we recommend updating your password and enabling two-factor authentication. If you notice any suspicious activity, please contact our support team immediately.',
                priority: 'urgent',
                createdBy: 'Security Team',
                totalSent: 0,
                delivered: 0,
                pending: 15000,
                failed: 0
            },
            {
                id: 'ANN004',
                title: 'New Feature Announcement',
                category: 'general',
                recipients: 9800,
                deliveryMethods: ['Email'],
                status: 'sent',
                sentDate: '2025-07-11 10:00:00',
                successRate: 99.1,
                cost: 9800,
                message: 'We are excited to announce the release of our new dashboard feature with enhanced analytics and reporting capabilities.',
                priority: 'normal',
                createdBy: 'Product Team',
                totalSent: 9800,
                delivered: 9712,
                pending: 0,
                failed: 88
            },
            {
                id: 'ANN005',
                title: 'Monthly Newsletter',
                category: 'general',
                recipients: 20000,
                deliveryMethods: ['Email'],
                status: 'failed',
                sentDate: '2025-07-10 08:00:00',
                successRate: 0,
                cost: 20000,
                message: 'Check out our monthly newsletter with the latest updates, tips, and success stories from our community.',
                priority: 'normal',
                createdBy: 'Content Team',
                totalSent: 0,
                delivered: 0,
                pending: 0,
                failed: 20000
            }
        ];

        // Populate table
        function populateTable() {
            const tbody = document.getElementById('announcementTableBody');
            tbody.innerHTML = '';

            announcements.forEach(announcement => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${announcement.id}</td>
                    <td>
                        <div class="fw-bold">${announcement.title}</div>
                        <div class="text-muted small">${announcement.message.substring(0, 50)}...</div>
                    </td>
                    <td>
                        <span class="badge bg-${getCategoryColor(announcement.category)}">${announcement.category}</span>
                    </td>
                    <td>${announcement.recipients.toLocaleString()}</td>
                    <td>
                        ${announcement.deliveryMethods.map(method =>
                    `<span class="badge bg-${getMethodColor(method)} me-1">${method}</span>`
                ).join('')}
                    </td>
                    <td>
                        <span class="badge bg-${getStatusColor(announcement.status)}">${announcement.status}</span>
                    </td>
                    <td>${formatDate(announcement.sentDate)}</td>
                    <td>
                        <div class="progress" style="width: 60px;">
                            <div class="progress-bar bg-success" style="width: ${announcement.successRate}%"></div>
                        </div>
                        <small>${announcement.successRate}%</small>
                    </td>
                    <td>₦${announcement.cost.toLocaleString()}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="viewAnnouncement('${announcement.id}')">
                            <i class="uil-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-success" onclick="duplicateAnnouncement('${announcement.id}')">
                            <i class="uil-copy"></i>
                        </button>
                        ${announcement.status === 'failed' ? `<button class="btn btn-sm btn-outline-warning" onclick="resendAnnouncement('${announcement.id}')"><i class="uil-redo"></i></button>` : ''}
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        // Populate cards
        function populateCards() {
            const container = document.getElementById('announcementCards');
            container.innerHTML = '';

            announcements.forEach(announcement => {
                const card = document.createElement('div');
                card.className = 'col-md-6 col-lg-4 mb-3';
                card.innerHTML = `
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="card-title">${announcement.title}</h6>
                                <span class="badge bg-${getStatusColor(announcement.status)}">${announcement.status}</span>
                            </div>
                            <p class="card-text text-muted small">${announcement.message.substring(0, 100)}...</p>
                            <div class="row text-center mb-2">
                                <div class="col-4">
                                    <div class="h6 text-primary">${announcement.recipients.toLocaleString()}</div>
                                    <div class="text-muted small">Recipients</div>
                                </div>
                                <div class="col-4">
                                    <div class="h6 text-success">${announcement.successRate}%</div>
                                    <div class="text-muted small">Success</div>
                                </div>
                                <div class="col-4">
                                    <div class="h6 text-info">₦${announcement.cost.toLocaleString()}</div>
                                    <div class="text-muted small">Cost</div>
                                </div>
                            </div>
                            <div class="mb-2">
                                ${announcement.deliveryMethods.map(method =>
                    `<span class="badge bg-${getMethodColor(method)} me-1">${method}</span>`
                ).join('')}
                            </div>
                            <div class="text-muted small mb-2">${formatDate(announcement.sentDate)}</div>
                            <div class="d-flex justify-content-between">
                                <button class="btn btn-sm btn-outline-primary" onclick="viewAnnouncement('${announcement.id}')">
                                    <i class="uil-eye"></i> View
                                </button>
                                <button class="btn btn-sm btn-outline-success" onclick="duplicateAnnouncement('${announcement.id}')">
                                    <i class="uil-copy"></i> Duplicate
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                container.appendChild(card);
            });
        }

        // Helper functions
        function getCategoryColor(category) {
            const colors = {
                general: 'primary',
                system: 'info',
                promotion: 'success',
                maintenance: 'warning',
                security: 'danger'
            };
            return colors[category] || 'secondary';
        }

        function getStatusColor(status) {
            const colors = {
                sent: 'success',
                pending: 'warning',
                failed: 'danger',
                draft: 'secondary',
                scheduled: 'info'
            };
            return colors[status] || 'secondary';
        }

        function getMethodColor(method) {
            const colors = {
                Email: 'info',
                SMS: 'success',
                Push: 'warning'
            };
            return colors[method] || 'secondary';
        }

        function formatDate(dateString) {
            return new Date(dateString).toLocaleString();
        }

        // View announcement details
        function viewAnnouncement(id) {
            const announcement = announcements.find(a => a.id === id);
            if (announcement) {
                document.getElementById('modalTitle').textContent = announcement.title;
                document.getElementById('modalCategory').textContent = announcement.category;
                document.getElementById('modalPriority').textContent = announcement.priority;
                document.getElementById('modalCreatedBy').textContent = announcement.createdBy;
                document.getElementById('modalRecipients').textContent = announcement.recipients.toLocaleString();
                document.getElementById('modalDeliveryMethods').innerHTML = announcement.deliveryMethods.map(method =>
                    `<span class="badge bg-${getMethodColor(method)} me-1">${method}</span>`
                ).join('');
                document.getElementById('modalStatus').innerHTML = `<span class="badge bg-${getStatusColor(announcement.status)}">${announcement.status}</span>`;
                document.getElementById('modalSentDate').textContent = formatDate(announcement.sentDate);
                document.getElementById('modalMessage').textContent = announcement.message;
                document.getElementById('modalTotalSent').textContent = announcement.totalSent.toLocaleString();
                document.getElementById('modalDelivered').textContent = announcement.delivered.toLocaleString();
                document.getElementById('modalPending').textContent = announcement.pending.toLocaleString();
                document.getElementById('modalFailed').textContent = announcement.failed.toLocaleString();

                new bootstrap.Modal(document.getElementById('announcementModal')).show();
            }
        }

        // Toggle between table and card view
        document.getElementById('tableView').addEventListener('change', function() {
            if (this.checked) {
                document.getElementById('tableViewContent').style.display = 'block';
                document.getElementById('cardViewContent').style.display = 'none';
            }
        });

        document.getElementById('cardView').addEventListener('change', function() {
            if (this.checked) {
                document.getElementById('tableViewContent').style.display = 'none';
                document.getElementById('cardViewContent').style.display = 'block';
            }
        });

        // Filter announcements
        function filterAnnouncements() {
            // In a real application, this would make an API call with the filter parameters
            alert('Filtering announcements...');
        }

        // Resend announcement
        function resendAnnouncement(id) {
            if (confirm('Are you sure you want to resend this announcement?')) {
                alert('Announcement resent successfully!');
                // Refresh the table/cards
                populateTable();
                populateCards();
            }
        }

        // Duplicate announcement
        function duplicateAnnouncement(id) {
            if (confirm('This will create a copy of this announcement. Continue?')) {
                alert('Announcement duplicated successfully!');
                // In a real application, this would redirect to the create page with pre-filled data
            }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            populateTable();
            populateCards();
        });
    </script>

    <style>
        .stats-card {
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-2px);
        }
        .border-left-primary {
            border-left: 4px solid #007bff !important;
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
        .border-left-secondary {
            border-left: 4px solid #6c757d !important;
        }
        .text-xs {
            font-size: 0.75rem;
        }
        .text-gray-800 {
            color: #5a5c69 !important;
        }
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border: 1px solid #dee2e6;
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        .progress {
            height: 8px;
        }
        .table th {
            border-top: none;
            font-weight: 600;
            background-color: #f8f9fa;
        }
        .badge {
            font-size: 0.7em;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
        .modal-body .row {
            margin-bottom: 1rem;
        }
        .btn-check:checked + .btn {
            background-color: #007bff;
            border-color: #007bff;
            color: white;
        }
    </style>

@endsection
