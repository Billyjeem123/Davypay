@extends('dashboard.layout.sms')

@section('content')
    <div class="wrapper">
        <div class="content-page sms-page">
            <div class="content">
                <div class="container-fluid">

                    <!-- Page Header -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <h1 class="page-title text-dark mb-1">Referral Management Dashboard</h1>
                            <p class="text-muted">Track and manage user referrals and commissions</p>
                        </div>
                    </div>

                    <div class="row g-3 mb-5">
                        <!-- Total Referrals -->
                        <div class="col-md-3">
                            <div class="card shadow-sm border-0 h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <p class="text-muted text-uppercase small mb-0">Total Referrals</p>
                                        <i class="fas fa-info-circle text-muted" data-bs-toggle="tooltip" title="Total number of users referred since the beginning."></i>
                                    </div>
                                    <h4 class="fw-bold mb-1">{{ number_format($totalReferrals) }}</h4>
                                    <small class="text-muted">Since launch</small>
                                </div>
                            </div>
                        </div>

                        <!-- Successful Referrals (7d) -->
                        <div class="col-md-3">
                            <div class="card shadow-sm border-0 h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <p class="text-muted text-uppercase small mb-0">Successful Referrals (7d)</p>
                                        <i class="fas fa-info-circle text-muted" data-bs-toggle="tooltip" title="Referrals completed in the last 7 days."></i>
                                    </div>
                                    <h4 class="fw-bold mb-1">{{ number_format($successfulReferralsLast7Days) }}</h4>
                                    <small class="text-success"><i class="fas fa-arrow-up me-1"></i>Trending up this week</small>
                                </div>
                            </div>
                        </div>

                        <!-- Top Referrer -->
                        <div class="col-md-3">
                            <div class="card shadow-sm border-0 h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <p class="text-muted text-uppercase small mb-0">Top Referrer (7d)</p>
                                        <i class="fas fa-info-circle text-muted" data-bs-toggle="tooltip" title="User with the most successful referrals in the last 7 days."></i>
                                    </div>
                                    <h4 class="fw-bold mb-1">
                                        {{ $topReferrer ? $topReferrer->name : 'N/A' }}
                                    </h4>
                                    @if($topReferrer)
                                        <small class="text-muted">{{ $topReferrer->email }}</small>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Active Referrers -->
                        <div class="col-md-3">
                            <div class="card shadow-sm border-0 h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <p class="text-muted text-uppercase small mb-0">Active Referrers</p>
                                        <i class="fas fa-info-circle text-muted" data-bs-toggle="tooltip" title="Total users who have referred at least one person."></i>
                                    </div>
                                    <h4 class="fw-bold mb-1">{{ number_format($activeReferrers) }}</h4>
                                    <small class="text-muted">Includes new and returning referrers</small>
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- Referral Fee Form -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-coins me-2"></i>Referral Reward Settings
                            </h5>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('referral.saveFee') }}" method="POST">
                                @csrf

                                {{-- Referral Fee Input --}}
                                <div class="mb-3">
                                    <label for="setting_value" class="form-label">Referral Fee (₦)</label>
                                    <input
                                        type="number"
                                        name="setting_value"
                                        id="setting_value"
                                        class="form-control"
                                        value="{{ old('setting_value', \App\Models\Settings::get('referral_fee')) }}"
                                        placeholder="Enter referral fee amount"
                                        required>
                                    <div class="form-text">
                                        Set the amount users will earn for successful referrals.
                                    </div>
                                </div>

                                {{-- Activation Toggle --}}
                                <div class="mb-3">
                                    <label for="referral_status" class="form-label">Referral Reward Status</label>
                                    <select name="referral_status" id="referral_status" class="form-select">
                                        <option value="active" {{ \App\Models\Settings::get('referral_status') === 'active' ? 'selected' : '' }}>
                                            Active
                                        </option>
                                        <option value="inactive" {{ \App\Models\Settings::get('referral_status') === 'inactive' ? 'selected' : '' }}>
                                            Inactive
                                        </option>
                                    </select>
                                    <div class="form-text">
                                        Choose whether to enable or disable the referral reward system.
                                    </div>
                                </div>

                                <div class="d-grid gap-2 d-md-block">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>



                    <!-- Referral Table -->
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0"><i class="fas fa-users me-2 text-primary"></i>Referrer Overview</h5>
                        </div>
                        <div class="card-body p-0">
                            <table id="sms-datatable" class="table table-hover dt-responsive nowrap w-100 table-centered mb-0">
                                <thead class="table-light">
                                <tr>
                                    <th>Referrer</th>
                                    <th>Referral Code</th>
                                    <th>Status</th>
                                    <th>Date Referred</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($users as $user)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm bg-success bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-3">
                                                    <i class="fas fa-user text-success"></i>
                                                </div>
                                                <div>
                                                    <strong class="text-dark">{{ $user->name }}</strong><br>
                                                    <small class="text-muted">{{ $user->email }}</small><br>
                                                    <small class="text-muted">Registered: {{ $user->created_at->format('d M Y') }}</small>
                                                </div>
                                            </div>
                                        </td>

                                        <td>
                                            @if($user->referral)
                                                <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2">
                    {{ $user->referral_code }}
                </span><br>
                                                <small class="text-muted">From  {{ $user->referral->referrer->first_name ?? "kk" }}</small>
                                            @else
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary px-3 py-2">
                    N/A
                </span>
                                            @endif
                                        </td>

                                        <td>
                                            @if($user->referral)
                                                <span class="badge bg-success fw-bold px-3 py-2">
                    <i class="fas fa-check-double me-1"></i> Referred
                </span>
                                            @else
                                                <span class="badge bg-warning fw-bold px-3 py-2">
                    <i class="fas fa-question-circle me-1"></i> Organic
                </span>
                                            @endif
                                        </td>

                                        <td>
                                            @if($user->referral)
                                                <strong class="text-dark">{{ $user->referral->referred_at->format('d/m/Y') }}</strong><br>
                                                <small class="text-muted">{{ $user->referral->referred_at->diffForHumans() }}</small>
                                            @else
                                                <em class="text-muted">—</em>
                                            @endif
                                        </td>

                                        <td class="text-end">
                                            <a href="{{ route('rewards.details', ['id' => $user->id]) }}" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye me-1"></i> View Details
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>

                            </table>
                        </div>
                    </div>

                </div> <!-- container -->
            </div> <!-- content -->
        </div>
    </div>
@endsection
