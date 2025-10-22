@extends('dashboard.layout.sms')

@section('content')
    <div class="wrapper">
        <div class="content-page sms-page">
            <div class="content">
                <div class="container-fluid">

                    <div class="row mb-4">
                        <div class="col-md-8">
                            <h2 class="text-dark">Referrals by {{ $referrer->name }}</h2>
                            <p class="text-muted">User has referred {{ $referrals->count() }} people</p>
                        </div>
                    </div>

                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0"><i class="fas fa-user-friends me-2 text-primary"></i>Referred Users</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped mb-0">
                                    <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Referred User</th>
                                        <th>Status</th>
                                        <th>Referral Date</th>
                                        <th>Device</th>
                                        <th>IP</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($referrals as $index => $referral)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                @if($referral->referred)
                                                    <strong>{{ $referral->referred->name }}</strong><br>
                                                    <small>{{ $referral->referred->email }}</small>
                                                @else
                                                    <em>Deleted user</em>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $referral->status === 'completed' ? 'success' : 'warning' }}">
                                                    {{ ucfirst($referral->status) }}
                                                </span>
                                            </td>
                                            <td>
                                                {{ $referral->referred_at->format('d M Y') }}
                                                <br><small>{{ $referral->referred_at->diffForHumans() }}</small>
                                            </td>

                                            <td>
                                                @php
                                                    $device = json_decode($referral->device_info, true);
                                                @endphp
                                                <span class="text-muted">
                                                    {{ $device['device_type'] ?? 'Unknown' }}
                                                </span>
                                            </td>
                                            <td>{{ $referral->ip_address }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">No referred users yet.</td>
                                        </tr>
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
@endsection
