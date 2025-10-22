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
                                <h1 class="page-title text-dark"> Announcement</h1>
                                <p class="text-muted">Send notifications to all users instantly</p>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="emergency-status">
                                <span class="badge bg-success fs-6">System Online</span>
                            </div>
                        </div>
                    </div>


                    <div class="row">
                        <!-- Emergency Notification Form -->
                        <div class="col-lg-5">
                            <div class="card emergency-form-card">
                                <div class="card-header bg-primary text-white">
                                    <h4 class="card-title mb-0 text-white">
                                        <i class="uil-exclamation-triangle me-2"></i>Send Announcement
                                    </h4>
                                </div>
                                <div class="card-body">
                                    <form id="emergencyForm" action="{{ route('send_announcement') }}" method="POST">
                                        @csrf

                                        <!-- Emergency Message -->
                                        <div class="mb-4">
                                            <label for="emergencyMessage" class="form-label fw-bold">Announcement Message</label>
                                            <textarea class="form-control emergency-textarea @error('message') is-invalid @enderror"
                                                      id="emergencyMessage" name="message" rows="8"
                                                      placeholder="Type your announcement notification here..."
                                                      required>{{ old('message') }}</textarea>
                                            <div class="form-text d-flex justify-content-between">
                                                <span><i class="uil-info-circle"></i> This will be sent to all users immediately</span>
                                                <span class="text-muted"><span id="charCount">{{ strlen(old('message', '')) }}</span>/500</span>
                                            </div>
                                            @error('message')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- Send Button -->
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary btn-lg primary-send-btn">
                                                <i class="uil-bolt me-2"></i>Send
                                            </button>
                                        </div>


                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Emergency Notifications History -->
                        <div class="col-lg-7">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title mb-0">Records</h4>
                                </div>
                                <div class="card-body p-0">
                                    @if($announcement->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <thead class="table-light">
                                                <tr>
                                                    <th width="60%">Message</th>
                                                    <th width="20%">Sent At</th>
                                                    <th width="15%">Status</th>
                                                    <th width="5%">Action</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @foreach($announcement as $notification)
                                                    <tr>
                                                        <td>
                                                            <div class="message-preview">
                                                                {{ Str::limit($notification->message, 100) }}
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <small class="text-muted">
                                                                {{ $notification->created_at->format('M d, Y') }}<br>
                                                                {{ $notification->created_at->format('h:i A') }}
                                                            </small>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-success">Sent</span>
                                                        </td>
                                                        <td>
                                                            <a href="{{ route('delete_announcement', $notification->id) }}" class="btn btn-outline-danger btn-sm" title="Delete">
                                                                <i class="uil-trash-alt"></i>
                                                            </a>
                                                        </td>

                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- Pagination -->

                                    @else

                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>


@endsection
