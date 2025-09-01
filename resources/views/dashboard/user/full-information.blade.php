@extends('dashboard.layout.sms')
@section('content')

    <div class="wrapper">



        <div class="content-page sms-page">
            <div class="content ">
                <div class="container-fluid">
                    <!-- Profile Header -->
                    <div class="row align-items-center profile-header">
                        <div class="col-auto">
                            <img src="https://via.placeholder.com/80x80/667eea/ffffff?text={{ strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1)) }}" alt="Profile" class="avatar-lg">
                        </div>
                        <div class="col">
                            <h3 class="mb-1">{{ $user->first_name }} {{ $user->last_name }}</h3>
                            <p class="mb-2 opacity-75">{{$user->username }}</p>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <h4 class="mb-1">₦{{ number_format($user->wallet->amount, 2) }}</h4>
                                        <small class="opacity-75">Wallet Balance</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <h4 class="mb-1">{{ $user->virtual_cards ? '1' : '0' }}</h4>
                                        <small class="opacity-75">Virtual Cards</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <h4 class="mb-1">{{ ucfirst(str_replace('_', ' ', $user->account_level)) }}</h4>
                                        <small class="opacity-75">Account Level</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <h4 class="mb-1">{{ $user->is_ban ? 'Banned' : ($user->is_account_restricted ? 'Restricted' : 'Active') }}</h4>
                                        <small class="opacity-75">Status</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            {{-- Restrict/Unrestrict --}}
                            <form action="{{ route('admin.users.toggle-restrict', $user->id) }}" method="POST" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-action">
                                    <i class="fas fa-ban"></i>
                                    {{ $user->is_account_restricted ? 'Unrestrict Account' : 'Restrict Account' }}
                                </button>
                            </form>

                            {{-- Ban/Unban --}}
                            <form action="{{ route('admin.users.toggle-ban', $user->id) }}" method="POST" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn btn-danger btn-action">
                                    <i class="fas fa-user-slash"></i>
                                    {{ $user->is_ban ? 'Unban User' : 'Ban User' }}
                                </button>
                            </form>
                        </div>

                    </div>

                    <!-- Navigation Tabs -->
                    <ul class="nav nav-tabs mb-4" id="profileTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab">
                                <i class="fas fa-user"></i> Profile Info
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="kyc-tab" data-bs-toggle="tab" data-bs-target="#kyc" type="button" role="tab">
                                <i class="fas fa-id-card"></i> KYC Details
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="activity-tab" data-bs-toggle="tab" data-bs-target="#activity" type="button" role="tab">
                                <i class="fas fa-history"></i> Activity Log
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab">
                                <i class="fas fa-shield-alt"></i> Security
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="wallet-tab" data-bs-toggle="tab" data-bs-target="#wallet" type="button" role="tab">
                                <i class="fas fa-wallet"></i> Wallet & Cards
                            </button>
                        </li>
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content" id="profileTabsContent">
                        <!-- Profile Info Tab -->
                        <div class="tab-pane fade show active" id="profile" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0">Personal Information</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="info-item">
                                                        <div class="info-label">First Name</div>
                                                        <div class="info-value">{{ $user->first_name }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="info-item">
                                                        <div class="info-label">Last Name</div>
                                                        <div class="info-value">{{ $user->last_name }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="info-item">
                                                        <div class="info-label">Email</div>
                                                        <div class="info-value">{{ $user->email }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="info-item">
                                                        <div class="info-label">Phone</div>
                                                        <div class="info-value">{{ $user->phone }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="info-item">
                                                        <div class="info-label">Username</div>
                                                        <div class="info-value">{{ $user->username }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="info-item">
                                                        <div class="info-label">Maiden Name</div>
                                                        <div class="info-value">{{ $user->maiden ?? 'N/A' }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-12">
                                                    <div class="info-item">
                                                        <div class="info-label">Role</div>
                                                        <div class="info-value">
                                                            <span class="badge bg-primary">{{ ucfirst($user->role) }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0">Account Status</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="info-item">
                                                        <div class="info-label">Account Level</div>
                                                        <div class="info-value">
                                                            <span class="tier-badge tier-{{ str_replace('tier_', '', $user->account_level) }}">{{ ucfirst(str_replace('_', ' ', $user->account_level)) }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="info-item">
                                                        <div class="info-label">KYC Status</div>
                                                        <div class="info-value">
                                                            <span class="status-badge status-{{ $user->kyc_status }}">{{ ucfirst($user->kyc_status) }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="info-item">
                                                        <div class="info-label">Account Restricted</div>
                                                        <div class="info-value">
                                                            <span class="badge bg-{{ $user->is_account_restricted ? 'danger' : 'success' }}">{{ $user->is_account_restricted ? 'Yes' : 'No' }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="info-item">
                                                        <div class="info-label">Account Banned</div>
                                                        <div class="info-value">
                                                            <span class="badge bg-{{ $user->is_ban ? 'danger' : 'success' }}">{{ $user->is_ban ? 'Yes' : 'No' }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="info-item">
                                                        <div class="info-label">Email Verified</div>
                                                        <div class="info-value">
                                                            <span class="badge bg-{{ $user->email_verified_at ? 'success' : 'warning' }}">{{ $user->email_verified_at ? 'Yes' : 'No' }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="info-item">
                                                        <div class="info-label">Referral Code</div>
                                                        <div class="info-value">{{ $user->referral_code ?? 'N/A' }}</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0">Device Information</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="device-info">
                                                <i class="fas fa-mobile-alt device-icon"></i>
                                                <div>
                                                    <div class="info-value">{{ $user->device_type ?? 'Mobile Device' }}</div>
                                                    <div class="info-label">Primary Device</div>
                                                </div>
                                            </div>
                                            <div class="info-item">
                                                <div class="info-label">Device Token</div>
                                                <div class="info-value">
                                                    <small class="text-muted">{{ substr($user->device_token, 0, 30) }}...</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- KYC Details Tab -->
                        <div class="tab-pane fade" id="kyc" role="tabpanel">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0">KYC Information</h5>
                                        </div>
                                        <div class="card-body">
                                            @if($user->kyc)
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="info-item">
                                                            <div class="info-label">KYC Type</div>
                                                            <div class="info-value">
                                                                <span class="badge bg-info">{{ strtoupper($user->kyc_type) }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="info-item">
                                                            <div class="info-label">Status</div>
                                                            <div class="info-value">
                                                                <span class="status-badge status-{{ $user->kyc->status }}">{{ ucfirst($user->kyc->status) }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="info-item">
                                                            <div class="info-label">BVN</div>
                                                            <div class="info-value">{{ $user->kyc->bvn ? '***********' . substr($user->kyc->bvn, -2) : 'N/A' }}</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="info-item">
                                                            <div class="info-label">NIN</div>
                                                            <div class="info-value">{{ $user->kyc->nin ? '***********' . substr($user->kyc->nin, -2) : 'N/A' }}</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="info-item">
                                                            <div class="info-label">Date of Birth</div>
                                                            <div class="info-value">{{ $user->kyc->dob ? \Carbon\Carbon::parse($user->kyc->dob)->format('F j, Y') : 'N/A' }}</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="info-item">
                                                            <div class="info-label">Nationality</div>
                                                            <div class="info-value">{{ $user->kyc->nationality ?? 'N/A' }}</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-12">
                                                        <div class="info-item">
                                                            <div class="info-label">Address</div>
                                                            <div class="info-value">{{ $user->kyc->address ?? 'N/A' }}</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="info-item">
                                                            <div class="info-label">Selfie Confidence</div>
                                                            <div class="info-value">{{ $user->kyc->selfie_confidence ? number_format($user->kyc->selfie_confidence, 2) . '%' : 'N/A' }}</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="info-item">
                                                            <div class="info-label">Selfie Match</div>
                                                            <div class="info-value">{{ $user->kyc->selfie_match ? number_format($user->kyc->selfie_match, 2) . '%' : 'N/A' }}</div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mt-4">
                                                    <h6>Documents</h6>
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <div class="kyc-document">
                                                                <i class="fas fa-camera fa-2x text-primary mb-2"></i>
                                                                <div class="info-label">Selfie</div>
                                                                <div class="info-value">
                                                                    @if($user->kyc->selfie)
                                                                        <button class="btn btn-sm btn-outline-primary" onclick="viewDocument('{{ asset('storage/' . $user->kyc->selfie) }}')">View</button>
                                                                    @else
                                                                        <span class="text-muted">Not uploaded</span>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="kyc-document">
                                                                <i class="fas fa-id-card fa-2x text-info mb-2"></i>
                                                                <div class="info-label">ID Document</div>
                                                                <div class="info-value">
                                                                    @if($user->kyc->id_image)
                                                                        <button class="btn btn-sm btn-outline-info" onclick="viewDocument('{{ asset('storage/' . $user->kyc->id_image) }}')">View</button>
                                                                    @else
                                                                        <span class="text-muted">Not uploaded</span>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="kyc-document">
                                                                <i class="fas fa-file-alt fa-2x text-success mb-2"></i>
                                                                <div class="info-label">Utility Bill</div>
                                                                <div class="info-value">
                                                                    @if($user->kyc->utility_bill)
                                                                        <button class="btn btn-sm btn-outline-success" onclick="viewDocument('{{ asset('storage/' . $user->kyc->utility_bill) }}')">View</button>
                                                                    @else
                                                                        <span class="text-muted">Not uploaded</span>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="text-center py-4">
                                                    <i class="fas fa-id-card fa-3x text-muted mb-3"></i>
                                                    <p class="text-muted">No KYC information available</p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    @if($user->kyc)
                                        <div class="card">
                                            <div class="card-header">
                                                <h5 class="mb-0">Driver's License</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="info-item">
                                                    <div class="info-label">License Number</div>
                                                    <div class="info-value">{{ $user->kyc->dl_licenseNo ?? 'N/A' }}</div>
                                                </div>
                                                <div class="info-item">
                                                    <div class="info-label">Issue Date</div>
                                                    <div class="info-value">{{ $user->kyc->dl_issuedDate ? \Carbon\Carbon::parse($user->kyc->dl_issuedDate)->format('M j, Y') : 'N/A' }}</div>
                                                </div>
                                                <div class="info-item">
                                                    <div class="info-label">Expiry Date</div>
                                                    <div class="info-value">{{ $user->kyc->dl_expiryDate ? \Carbon\Carbon::parse($user->kyc->dl_expiryDate)->format('M j, Y') : 'N/A' }}</div>
                                                </div>
                                                <div class="info-item">
                                                    <div class="info-label">State of Issue</div>
                                                    <div class="info-value">{{ $user->kyc->dl_stateOfIssue ?? 'N/A' }}</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card">
                                            <div class="card-header">
                                                <h5 class="mb-0">Admin Remarks</h5>
                                            </div>
                                            <div class="card-body">
                                                <p class="text-muted">{{ $user->kyc->admin_remark ?? 'No remarks available' }}</p>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Activity Log Tab -->
                        <div class="tab-pane fade" id="activity" role="tabpanel">
                            <div class="card shadow-sm border-0">
                                <div class="card-header bg-white border-bottom">
                                    <h5 class="mb-0 text-primary">Recent Activity</h5>
                                </div>

                                <div class="card-body p-5">
                                    @if($user->activity_logs && count($user->activity_logs) > 0)
                                        <div class="table-responsive" style="max-height: 450px; overflow-y: auto;">
                                            <table id="sms-datatable" class="table table-hover table-striped table-bordered mb-0">
                                                <thead class="table-light text-center">
                                                <tr>
                                                    <th>Action</th>
                                                    <th>Description</th>
                                                    <th>Time</th>
                                                    <th>Type</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @foreach($user->activity_logs as $activity)
                                                    <tr>
                                                        <td><strong>{{ $activity->action ?? 'Activity' }}</strong></td>
                                                        <td>{{ $activity->description ?? 'No description' }}</td>
                                                        <td>{{ $activity->created_at ? $activity->created_at->diffForHumans() : 'Unknown time' }}</td>
                                                        <td class="text-center">
                                        <span class="badge bg-{{ $activity->type == 'success' ? 'success' : ($activity->type == 'error' ? 'danger' : 'info') }}">
                                            {{ ucfirst($activity->type ?? 'Info') }}
                                        </span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="text-center py-5">
                                            <i class="fas fa-history fa-3x text-muted mb-3"></i>
                                            <p class="text-muted mb-0">No activity logs available</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="security" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0">Account Security</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <strong>Last Login</strong>
                                                        <p class="mb-0 text-muted">{{ $user->updated_at ? $user->updated_at->diffForHumans() : 'Unknown' }}</p>
                                                        <small>Account Created: {{ $user->created_at ? $user->created_at->format('M j, Y') : 'Unknown' }}</small>
                                                    </div>
                                                    <span class="badge bg-{{ $user->is_ban ? 'danger' : 'success' }}">
                                                {{ $user->is_ban ? 'Banned' : 'Active' }}
                                            </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0">Security Actions</h5>
                                        </div>
                                        <div class="card-body">
                                            <button class="btn btn-outline-warning btn-sm mb-2 w-100">
                                                <i class="fas fa-key"></i> Reset Password
                                            </button>
                                            <button class="btn btn-outline-info btn-sm mb-2 w-100">
                                                <i class="fas fa-lock"></i> Reset PIN
                                            </button>
                                            <button class="btn btn-outline-danger btn-sm mb-2 w-100">
                                                <i class="fas fa-sign-out-alt"></i> Force Logout All Sessions
                                            </button>
                                            <button class="btn btn-outline-secondary btn-sm mb-2 w-100">
                                                <i class="fas fa-envelope"></i> Send Verification Email
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>




                        <!-- Wallet & Cards Tab -->
                        <div class="tab-pane fade" id="wallet" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0">Wallet Information</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="info-item">
                                                <div class="info-label">Available Balance</div>
                                                <div class="info-value">₦{{ number_format($user->wallet->amount, 2) }}</div>
                                            </div>
                                            <div class="info-item">
                                                <div class="info-label">Locked Amount</div>
                                                <div class="info-value">₦{{ number_format($user->wallet->locked_amount, 2) }}</div>
                                            </div>
                                            <div class="info-item">
                                                <div class="info-label">Wallet Status</div>
                                                <div class="info-value">
                                            <span class="badge bg-{{ $user->wallet->status == 'active' ? 'success' : 'warning' }}">
                                                {{ ucfirst($user->wallet->status) }}
                                            </span>
                                                </div>
                                            </div>
                                            <div class="info-item">
                                                <div class="info-label">Total Balance</div>
                                                <div class="info-value">₦{{ number_format($user->wallet->amount + $user->wallet->locked_amount, 2) }}</div>
                                            </div>
                                        </div>
                                    </div>

                                    @if($user->virtual_accounts && count($user->virtual_accounts) > 0)
                                        <div class="card">
                                            <div class="card-header">
                                                <h5 class="mb-0">Virtual Accounts</h5>
                                            </div>
                                            <div class="card-body">
                                                @foreach($user->virtual_accounts as $account)
                                                    <div class="mb-3 p-3 border rounded">
                                                        <div class="info-item">
                                                            <div class="info-label">Account Name</div>
                                                            <div class="info-value">{{ $account->account_name }}</div>
                                                        </div>
                                                        <div class="info-item">
                                                            <div class="info-label">Account Number</div>
                                                            <div class="info-value">{{ $account->account_number }}</div>
                                                        </div>
                                                        <div class="info-item">
                                                            <div class="info-label">Bank Name</div>
                                                            <div class="info-value">{{ $account->bank_name }}</div>
                                                        </div>
                                                        <div class="info-item">
                                                            <div class="info-label">Provider</div>
                                                            <div class="info-value">
                                                                <span class="badge bg-primary">{{ ucfirst($account->provider) }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>


                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>


        <style>
            body {
                    background-color: #f8f9fa;
            }

            .profile-header {
                background-color: #1a1a1a;
                border-radius: 10px;
                color: #ffffff;
                padding: 2rem;
                margin-bottom: 2rem;
                border: 1px solid #333333;
            }

            .avatar-lg {
                width: 80px;
                height: 80px;
                border-radius: 50%;
                border: 3px solid rgba(255,255,255,0.3);
                object-fit: cover;
            }

            .card {
                border: none;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                margin-bottom: 2rem;
            }

            .card-header {
                background: white;
                border-bottom: 1px solid #e9ecef;
                padding: 1.5rem;
                border-radius: 10px 10px 0 0 !important;
            }

            .status-badge {
                display: inline-block;
                padding: 0.25rem 0.75rem;
                border-radius: 20px;
                font-size: 0.875rem;
                font-weight: 500;
            }

            .status-verified {
                background-color: #d4edda;
                color: #155724;
            }

            .status-pending {
                background-color: #fff3cd;
                color: #856404;
            }

            .status-failed {
                background-color: #f8d7da;
                color: #721c24;
            }

            .info-item {
                margin-bottom: 1.5rem;
            }

            .info-label {
                font-size: 0.875rem;
                color: #6c757d;
                margin-bottom: 0.25rem;
                font-weight: 500;
            }

            .info-value {
                font-size: 1rem;
                color: #212529;
                font-weight: 600;
            }

            .nav-tabs .nav-link {
                border: none;
                border-radius: 20px;
                margin-right: 0.5rem;
                color: #6c757d;
                font-weight: 500;
            }

            .nav-tabs .nav-link.active {
                background-color: #1a1a1a;
                color: white;
            }

            .activity-item {
                border-left: 3px solid #667eea;
                padding-left: 1rem;
                margin-bottom: 1rem;
            }

            .activity-time {
                font-size: 0.875rem;
                color: #6c757d;
            }

            .btn-action {
                margin-right: 0.5rem;
                margin-bottom: 0.5rem;
            }

            .tier-badge {
                display: inline-block;
                padding: 0.5rem 1rem;
                border-radius: 25px;
                font-weight: 600;
                text-transform: uppercase;
                font-size: 0.75rem;
                letter-spacing: 0.5px;
            }

            .tier-1 { background-color: #e3f2fd; color: #1976d2; }
            .tier-2 { background-color: #fff3e0; color: #f57c00; }
            .tier-3 { background-color: #f3e5f5; color: #7b1fa2; }

            .kyc-document {
                border: 1px solid #e9ecef;
                border-radius: 8px;
                padding: 1rem;
                margin-bottom: 1rem;
                background-color: #f8f9fa;
            }

            .device-info {
                display: flex;
                align-items: center;
                margin-bottom: 0.5rem;
            }

            .device-icon {
                margin-right: 0.5rem;
                color: #667eea;
            }
        </style>

@endsection


