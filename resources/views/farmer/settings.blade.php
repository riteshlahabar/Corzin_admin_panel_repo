@extends('layouts.app')
@section('title', 'Farmer Settings')

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
    @endif

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3 mb-3">
        <h4 class="mb-0 text-dark">Farmer Settings</h4>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent">
            <ul class="nav nav-tabs card-header-tabs" id="farmerSettingsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="banner-tab" data-bs-toggle="tab" data-bs-target="#banner-pane" type="button" role="tab" aria-controls="banner-pane" aria-selected="true">
                        Banner
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="farmerSettingsTabsContent">
                <div class="tab-pane fade show active" id="banner-pane" role="tabpanel" aria-labelledby="banner-tab" tabindex="0">
                    <form method="POST" action="{{ route('farmer.settings.banner.upload') }}" enctype="multipart/form-data" class="row g-3 mb-4">
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Banner Title (Optional)</label>
                            <input
                                type="text"
                                name="title"
                                class="form-control @error('title') is-invalid @enderror"
                                value="{{ old('title') }}"
                                placeholder="Enter banner title"
                            >
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Banner Image</label>
                            <input
                                type="file"
                                name="banner_image"
                                class="form-control @error('banner_image') is-invalid @enderror"
                                accept="image/*"
                                required
                            >
                            <div class="text-muted small mt-1">Recommended size: 1200 x 450 px</div>
                            @error('banner_image')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn farmer-settings-btn w-100">Upload Banner</button>
                        </div>
                    </form>

                    <div class="row g-3">
                        @forelse($banners as $banner)
                            <div class="col-md-4 col-xl-3">
                                <div class="card h-100 border">
                                    <img src="{{ asset($banner->image_path) }}" class="card-img-top" alt="farmer banner" style="height: 140px; object-fit: cover;">
                                    <div class="card-body py-2">
                                        <p class="mb-0 fw-semibold text-truncate">{{ $banner->title ?: 'Farmer Banner' }}</p>
                                    </div>
                                    <div class="card-footer bg-transparent border-0 pt-0">
                                        <form method="POST" action="{{ route('farmer.settings.banner.destroy', $banner) }}" onsubmit="return confirm('Remove this banner?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger w-100">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="alert alert-light border mb-0">No banner uploaded yet.</div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .farmer-settings-btn {
        background-color: #198754;
        border-color: #198754;
        color: #fff;
    }
    .farmer-settings-btn:hover,
    .farmer-settings-btn:focus {
        background-color: #157347;
        border-color: #157347;
        color: #fff;
    }
</style>
@endpush
