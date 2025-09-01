@extends('dashboard.layout.sms')

@section('content')
    <div class="wrapper">
        <div class="content-page">
            <div class="content">
                <div class="container-fluid">

                    <!-- Page Title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <h1 class="page-title text-dark">Banner Management</h1>
                            </div>
                        </div>
                    </div>

                    <!-- Banner Upload Form -->
                    <div class="row justify-content-center mb-4">
                        <div class="col-md-8 bg-white p-4 shadow rounded">
                            <h3 class="text-center text-dark mb-4">Upload New Banner</h3>


                            <form action="{{ route('banners.upload') }}" method="POST" enctype="multipart/form-data">
                                @csrf

                                <div class="mb-3">
                                    <label for="banner_image" class="form-label">Select Banner Image</label>
                                    <input type="file" name="banner_image" id="banner_image"
                                           class="form-control @error('banner_image') is-invalid @enderror"
                                           accept="image/*" required>
                                    @error('banner_image')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Supported formats: JPG, PNG, GIF. Max size: 2MB</small>
                                </div>

                                <div class="text-center mt-4">
                                    <button type="submit" class="btn btn-primary px-5 py-2">
                                        <i class="fas fa-upload me-2"></i>Upload Banner
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Banners List -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Uploaded Banners</h4>
                                </div>
                                <div class="card-body">
                                    <div class="">
                                        <table id="sms-datatable" class="table table-hover dt-responsive nowrap w-100 table-centered mb-0">
                                            <thead class="">
                                            <tr>
                                                <th>ID</th>
                                                <th>Image</th>
                                                <th>File URL</th>
                                                <th>Status</th>
                                                <th>Upload Date</th>
                                                <th>Actions</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @forelse($banners as $banner)
                                                <tr>
                                                    <td>{{ $banner->id }}</td>
                                                    <td>
                                                        <img src="{{ $banner->image_url }}"
                                                             alt="Banner"
                                                             style="width: 80px; height: 50px; object-fit: cover;"
                                                             class="rounded">
                                                    </td>
                                                    <td>
                                                        <a href="{{ $banner->image_url }}" target="_blank" class="text-truncate d-block" style="max-width: 200px;">
                                                            {{ $banner->image_url }}
                                                        </a>
                                                    </td>
                                                    <td>
                                                            <span class="badge bg-{{ $banner->status === 'active' ? 'success' : 'secondary' }}">
                                                                {{ ucfirst($banner->status) }}
                                                            </span>
                                                    </td>
                                                    <td>{{ $banner->created_at->format('M d, Y H:i') }}</td>
                                                    <td>
                                                        @if($banner->status === 'active')
                                                            <form action="{{ route('banners.deactivate', $banner->id) }}" method="POST" class="d-inline">
                                                                @csrf
                                                                @method('PATCH')
                                                                <button type="submit" class="btn btn-sm btn-warning"
                                                                        onclick="return confirm('Are you sure you want to deactivate this banner?')">
                                                                    <i class="fas fa-eye-slash"></i> Deactivate
                                                                </button>
                                                            </form>
                                                        @else
                                                            <form action="{{ route('banners.activate', $banner->id) }}" method="POST" class="d-inline">
                                                                @csrf
                                                                @method('PATCH')
                                                                <button type="submit" class="btn btn-sm btn-success"
                                                                        onclick="return confirm('Are you sure you want to activate this banner?')">
                                                                    <i class="fas fa-eye"></i> Activate
                                                                </button>
                                                            </form>
                                                        @endif

                                                        <form action="{{ route('banners.delete', $banner->id) }}" method="POST" class="d-inline ms-1">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-danger"
                                                                    onclick="return confirm('Are you sure you want to delete this banner? This action cannot be undone.')">
                                                                <i class="fas fa-trash"></i> Delete
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @empty

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
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Preview uploaded image
        document.getElementById('banner_image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Create preview if it doesn't exist
                    let preview = document.getElementById('image-preview');
                    if (!preview) {
                        preview = document.createElement('div');
                        preview.id = 'image-preview';
                        preview.className = 'mt-3 text-center';
                        e.target.parentNode.appendChild(preview);
                    }
                    preview.innerHTML = `
                    <img src="${e.target.result}" style="max-width: 300px; max-height: 200px; object-fit: cover;" class="rounded border">
                    <p class="small text-muted mt-2">Preview</p>
                `;
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
@endpush
