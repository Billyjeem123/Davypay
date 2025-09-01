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
                                <h1 class="page-title text-dark">Announcement Broadcast</h1>
                                <p class="text-muted">Send announcements to users instantly via SMS and Email</p>
                            </div>
                        </div>

                    </div>

                    <!-- Stats -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card stats-card border-left-success">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Today's Broadcasts</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">45</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card border-left-info">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Sent</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">12,450</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card border-left-warning">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">8</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card border-left-danger">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Failed</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">2</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Announcement Form -->
                    <div class="row">
                        <!-- Display Success/Error Messages -->
                        @if(session('success'))
                            <div class="col-12">
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    {{ session('success') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            </div>
                        @endif

                        @if(session('error'))
                            <div class="col-12">
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    {{ session('error') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            </div>
                        @endif

                        @if(session('warning'))
                            <div class="col-12">
                                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                    {{ session('warning') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            </div>
                        @endif

                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Create Announcement</h4>
                                </div>
                                <div class="card-body">
                                    <form id="announcementForm" action="{{ route('dashboard.broadcast.send') }}" method="POST">
                                        @csrf

                                        <!-- Announcement Title -->
                                        <div class="row mb-3">
                                            <div class="col-md-12">
                                                <label for="announcementTitle" class="form-label">Announcement Title</label>
                                                <input type="text" class="form-control @error('announcement_title') is-invalid @enderror"
                                                       id="announcementTitle" name="announcement_title"
                                                       placeholder="Enter announcement title"
                                                       value="{{ old('announcement_title') }}" required>
                                                @error('announcement_title')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <!-- Recipient Selection -->
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="recipientType" class="form-label">Send To</label>
                                                <select class="form-select @error('recipient_type') is-invalid @enderror"
                                                        id="recipientType" name="recipient_type" required>
                                                    <option value="">Select recipients...</option>
                                                    <option value="all" {{ old('recipient_type') == 'all' ? 'selected' : '' }}>All Users</option>
                                                    <option value="specific" {{ old('recipient_type') == 'specific' ? 'selected' : '' }}>Specific Users</option>
                                                </select>
                                                @error('recipient_type')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label for="priority" class="form-label">Priority Level</label>
                                                <select class="form-select @error('priority') is-invalid @enderror"
                                                        id="priority" name="priority" required>
                                                    <option value="normal" {{ old('priority') == 'normal' ? 'selected' : '' }}>Normal</option>
                                                    <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>High</option>
                                                    <option value="urgent" {{ old('priority') == 'urgent' ? 'selected' : '' }}>Urgent</option>
                                                </select>
                                                @error('priority')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <!-- Specific Users Selection (Hidden by default) -->
                                        <div class="row mb-3" id="specificUsersSection"
                                             style="display: {{ old('recipient_type') == 'specific' ? 'block' : 'none' }};">
                                            <div class="col-md-12">
                                                <label for="specificUsers" class="form-label">Select Specific Users</label>
                                                <select class="form-select @error('specific_users') is-invalid @enderror"
                                                        id="specificUsers" name="specific_users[]" >
                                                    <!-- Users will be loaded dynamically -->
                                                </select>
                                                <div class="form-text">Hold Ctrl/Cmd to select multiple users</div>
                                                @error('specific_users')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <!-- Delivery Options -->
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="deliveryMethod" class="form-label">Delivery Method</label>
                                                <div class="form-check">
                                                    <input class="form-check-input @error('push_notification') is-invalid @enderror"
                                                           type="checkbox" id="pushNotification" name="push_notification" value="1" checked>
                                                    <label class="form-check-label" for="pushNotification">
                                                        Push Notification
                                                    </label>
                                                    @error('push_notification')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Announcement Message -->
                                        <div class="row mb-3">
                                            <div class="col-md-12">
                                                <label for="announcementMessage" class="form-label">Message</label>
                                                <textarea class="form-control @error('announcement_message') is-invalid @enderror"
                                                          id="announcementMessage" name="announcement_message" rows="6"
                                                          placeholder="Enter your announcement message here..." required>{{ old('announcement_message') }}</textarea>
                                                <div class="form-text">
                                                    <span id="charCount">{{ strlen(old('announcement_message', '')) }}</span>/500 characters
                                                </div>
                                                @error('announcement_message')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <!-- Action Buttons -->
                                        <div class="row">
                                            <div class="col-12">
                                                <button type="submit" class="btn btn-success btn-lg me-2">
                                                    <i class="uil-megaphone"></i> Send Announcement
                                                </button>
                                                <button type="button" class="btn btn-secondary btn-lg" onclick="clearForm()">
                                                    <i class="uil-refresh"></i> Clear Form
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Broadcast Information Sidebar -->
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Broadcast Summary</h4>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Estimated Recipients</label>
                                        <p class="form-control-plaintext text-primary" id="estimatedRecipients">0 users</p>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Delivery Methods</label>
                                        <div id="deliveryMethodsSelected">
                                            <span class="badge bg-info me-1">Push Notification</span>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- Quick Templates -->
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h4 class="card-title">Quick Templates</h4>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <button class="btn btn-outline-primary btn-sm" onclick="loadTemplate('maintenance')">
                                            <i class="uil-wrench"></i> Maintenance Notice
                                        </button>

                                        <button class="btn btn-outline-info btn-sm" onclick="loadTemplate('update')">
                                            <i class="uil-sync"></i> System Update
                                        </button>
                                        <button class="btn btn-outline-warning btn-sm" onclick="loadTemplate('security')">
                                            <i class="uil-shield-check"></i> Security Notice
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script>
        // Character count for message
        document.getElementById('announcementMessage').addEventListener('input', function() {
            const charCount = this.value.length;
            document.getElementById('charCount').textContent = charCount;

            if (charCount > 500) {
                this.style.borderColor = '#dc3545';
                document.getElementById('charCount').style.color = '#dc3545';
            } else {
                this.style.borderColor = '#ced4da';
                document.getElementById('charCount').style.color = '#6c757d';
            }
        });

        // Show/hide specific users section and load users dynamically
        document.getElementById('recipientType').addEventListener('change', function() {
            const specificSection = document.getElementById('specificUsersSection');
            const recipientType = this.value;

            if (recipientType === 'specific') {
                specificSection.style.display = 'block';
                loadSpecificUsers();
            } else {
                specificSection.style.display = 'none';
                clearSpecificUsers();
            }
            updateEstimatedRecipients();
        });

        // Load specific users via AJAX
        function loadSpecificUsers() {
            const selectElement = document.getElementById('specificUsers');

            // Show loading state
            selectElement.innerHTML = '<option value="">Loading users...</option>';
            selectElement.disabled = true;

            fetch('/admin/api/users/dropdown?type=specific')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Clear loading option
                        selectElement.innerHTML = '';

                        // Add users to dropdown
                        data.users.forEach(user => {
                            const option = document.createElement('option');
                            option.value = user.id;
                            option.textContent = `${user.first_name} - ${user.email}`;
                            selectElement.appendChild(option);
                        });

                        selectElement.disabled = false;
                    } else {
                        selectElement.innerHTML = '<option value="">Failed to load users</option>';
                        console.error('Failed to load users:', data.message);
                    }
                })
                .catch(error => {
                    selectElement.innerHTML = '<option value="">Error loading users</option>';
                    console.error('Error loading users:', error);
                    selectElement.disabled = false;
                });
        }

        // Clear specific users selection
        function clearSpecificUsers() {
            const selectElement = document.getElementById('specificUsers');
            selectElement.innerHTML = '';
            selectElement.disabled = false;
        }

        // Update delivery methods display
        function updateDeliveryMethods() {
            const deliveryDiv = document.getElementById('deliveryMethodsSelected');
            deliveryDiv.innerHTML = '';

            if (document.getElementById('pushNotification').checked) {
                deliveryDiv.innerHTML += '<span class="badge bg-info me-1">Push Notification</span>';
            }

            // If no delivery method is selected, show default
            if (deliveryDiv.innerHTML === '') {
                deliveryDiv.innerHTML = '<span class="badge bg-secondary me-1">None Selected</span>';
            }
        }

        // Add event listener for push notification checkbox
        document.getElementById('pushNotification').addEventListener('change', updateDeliveryMethods);

        // Update estimated recipients and cost dynamically
        function updateEstimatedRecipients() {
            const recipientType = document.getElementById('recipientType').value;
            let count = 0;

            if (recipientType === 'all') {
                // Use the total count passed from the controller
                count = window.totalUsers || 0;
            } else if (recipientType === 'specific') {
                const specificUsers = document.getElementById('specificUsers');
                count = specificUsers ? specificUsers.selectedOptions.length : 0;
            }

            document.getElementById('estimatedRecipients').textContent = count + ' users';

            // Update estimated cost (₦2 per push notification)
            let cost = 0;
            if (document.getElementById('pushNotification').checked) {
                cost = count * 2;
            }
            document.getElementById('estimatedCost').textContent = '₦' + cost.toLocaleString() + '.00';
        }

        // Update specific users selection listener
        document.addEventListener('change', function(e) {
            if (e.target.id === 'specificUsers') {
                if (document.getElementById('recipientType').value === 'specific') {
                    updateEstimatedRecipients();
                }
            }
        });



        // Template loading function
        function loadTemplate(type) {
            const templates = {
                'maintenance': {
                    title: 'Scheduled Maintenance Notice',
                    message: 'We will be performing scheduled maintenance on our systems from 2:00 AM to 4:00 AM tomorrow. Services may be temporarily unavailable during this time. We apologize for any inconvenience.'
                },
                'update': {
                    title: 'System Update Notification',
                    message: 'We have released a new system update with improved features and bug fixes. Please update your app to enjoy the latest enhancements and better performance.'
                },
                'security': {
                    title: 'Security Notice',
                    message: 'For your security, we recommend updating your password and enabling two-factor authentication. If you notice any suspicious activity, please contact our support team immediately.'
                }
            };

            const template = templates[type];
            if (template) {
                document.getElementById('announcementTitle').value = template.title;
                document.getElementById('announcementMessage').value = template.message;

                // Trigger character count update
                document.getElementById('announcementMessage').dispatchEvent(new Event('input'));
            }
        }

        // Clear form function
        function clearForm() {
            document.getElementById('announcementForm').reset();
            document.getElementById('specificUsersSection').style.display = 'none';
            clearSpecificUsers();
            document.getElementById('charCount').textContent = '0';
            document.getElementById('estimatedRecipients').textContent = '0 users';
            document.getElementById('estimatedCost').textContent = '₦0.00';
            document.getElementById('broadcastStatus').textContent = 'Draft';
            document.getElementById('broadcastStatus').className = 'badge bg-secondary';
            updateDeliveryMethods();
        }

        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Set default push notification to checked
            document.getElementById('pushNotification').checked = true;

            // Set total users from PHP variable
            window.totalUsers = {{ $totalUsers ?? 0 }};

            updateDeliveryMethods();
            updateEstimatedRecipients();
        });
    </script>

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
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border: 1px solid #dee2e6;
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        #announcementMessage {
            resize: vertical;
            min-height: 120px;
        }
        .form-text {
            margin-top: 0.25rem;
        }
        .badge {
            font-size: 0.75em;
        }
        .btn-outline-primary:hover, .btn-outline-success:hover,
        .btn-outline-info:hover, .btn-outline-warning:hover {
            transform: translateY(-1px);
        }
    </style>

@endsection
