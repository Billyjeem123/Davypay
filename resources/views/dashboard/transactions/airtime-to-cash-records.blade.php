@extends('dashboard.layout.sms')

@section('content')
    <div class="wrapper">
        <div class="content-page sms-page">
            <div class="content">
                <div class="container-fluid">

                    <!-- Header -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <div class="page-title-box">
                                <h1 class="page-title text-dark mb-1">Airtime to Cash Management</h1>
                                <p class="text-muted mb-0">Manage and track Airtime to cash</p>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="stats-card bg-primary text-white p-3 rounded">
                                <h5 class="mb-1">Total Pending</h5>
                                <h3 class="mb-0">{{ $transfers->where('status', 'pending')->count() }}</h3>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body p-4">
                                    <h5 class="card-title mb-3">Network Providers</h5>

                                    <table id="sms-datatable" class="table table-hover dt-responsive nowrap w-100 table-centered mb-0">
                                        <thead class="table-light">
                                        <tr>
                                            <th class="fw-bold text-dark border-0 py-3">User</th>
                                            <th class="fw-bold text-dark border-0 py-3">Status</th>
                                            <th class="fw-bold text-dark border-0 py-3">Network</th>
                                            <th class="fw-bold text-dark border-0 py-3">Rate</th>
                                            <th class="fw-bold text-dark border-0 py-3">Amount</th>
                                            <th class="fw-bold text-dark border-0 py-3">Expected Return</th>
                                            <th class="fw-bold text-dark border-0 py-3">File</th>
                                            <th class="fw-bold text-dark border-0 py-3 text-end">Actions</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @if(isset($transfers) && count($transfers) > 0)
                                            @foreach($transfers as $transfer)
                                                @php
                                                    $amount = floatval($transfer['amount']);
                                                    $adminRate = floatval($transfer['network']['admin_rate']);
                                                    $expectedReturn = $amount - ($amount * ($adminRate / 100));
                                                @endphp
                                                <tr class="border-bottom">
                                                    <td class="align-middle py-3">
                                                        <div class="d-flex align-items-center">
                                                            <div class="bg-info bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 36px; height: 36px;">
                                                                <i class="fa fa-user text-info"></i>
                                                            </div>
                                                            <div>
                                                                <span class="fw-bold text-dark">{{ $transfer['user']['first_name'] }} {{ $transfer['user']['last_name'] }}</span><br>
                                                                <small class="text-muted">ID: {{ $transfer['user']['id'] }}</small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="align-middle py-3">
                    <span class="badge fw-bold px-3 py-2
                        {{ $transfer['status'] === 'completed' ? 'bg-success' :
                           ($transfer['status'] === 'pending' ? 'bg-warning text-dark' :
                           ($transfer['status'] === 'failed' ? 'bg-danger' : 'bg-secondary')) }}">
                        {{ ucfirst($transfer['status']) }}
                    </span>
                                                    </td>
                                                    <td class="align-middle py-3">
                                                        <div class="d-flex align-items-center">
                                                            <div class="bg-primary bg-opacity-10 rounded p-2 me-2">
                                                                <i class="fa fa-mobile text-primary"></i>
                                                            </div>
                                                            <span class="fw-bold text-dark">{{ $transfer['network']['network_name'] ?? $transfer['network_provider'] }}</span>
                                                        </div>
                                                    </td>
                                                    <td class="align-middle py-3">
                                                        <span class="badge bg-light text-dark">{{ number_format($adminRate, 2) }}%</span>
                                                    </td>
                                                    <td class="align-middle py-3">
                                                        <span class="fw-bold text-dark font-monospace">₦{{ number_format($amount, 0) }}</span>
                                                    </td>
                                                    <td class="align-middle py-3">
                                                        <span class="fw-bold text-dark font-monospace">₦{{ number_format($expectedReturn, 0) }}</span>
                                                    </td>
                                                    <td class="align-middle py-3">
                                                        @if($transfer['file'])
                                                            <a href="{{ asset($transfer['file']) }}" target="_blank" class="badge bg-info text-white text-decoration-none">
                                                                <i class="fa fa-file-image"></i> View
                                                            </a>
                                                        @else
                                                            <span class="badge bg-light text-muted">No File</span>
                                                        @endif
                                                    </td>
                                                    <td class="align-middle py-3 text-end">
                                                        <div class="btn-group btn-group-sm">
                                                            @if($transfer['status'] === 'pending')
                                                                <form action="{{ route('network-provider.approve-transfer', $transfer['id']) }}" method="POST" style="display:inline;">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <button type="submit" class="btn btn-outline-success btn-sm" onclick="return confirm('Approve this transfer?')">
                                                                        <i class="fa fa-check"></i> Approve
                                                                    </button>
                                                                </form>
                                                                <form action="{{ route('network-provider.reject-transfer', $transfer['id']) }}" method="POST" style="display:inline;">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <button type="submit" class="btn btn-outline-warning btn-sm" onclick="return confirm('Reject this transfer?')">
                                                                        <i class="fa fa-times"></i> Reject
                                                                    </button>
                                                                </form>
                                                            @endif
                                                            <form action="{{ route('network-provider.delete-transfer', $transfer['id']) }}" method="POST" style="display:inline;">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete this transfer?')">
                                                                    <i class="fa fa-trash"></i> Delete
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endif
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


@endsection



