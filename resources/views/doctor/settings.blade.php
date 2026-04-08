@extends('layouts.app')
@section('title', 'Doctor Settings')

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
    @endif

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3 mb-3">
        <h4 class="mb-0 text-dark">Doctor Settings</h4>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent">
            <ul class="nav nav-tabs card-header-tabs" id="doctorSettingsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="terms-tab" data-bs-toggle="tab" data-bs-target="#terms-pane" type="button" role="tab" aria-controls="terms-pane" aria-selected="true">
                        Terms &amp; Condition
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="privacy-tab" data-bs-toggle="tab" data-bs-target="#privacy-pane" type="button" role="tab" aria-controls="privacy-pane" aria-selected="false">
                        Privacy Policy
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="banner-tab" data-bs-toggle="tab" data-bs-target="#banner-pane" type="button" role="tab" aria-controls="banner-pane" aria-selected="false">
                        Banner
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="doctorSettingsTabsContent">
                <div class="tab-pane fade show active" id="terms-pane" role="tabpanel" aria-labelledby="terms-tab" tabindex="0">
                    <form method="POST" action="{{ route('doctor.settings.update') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Terms And Conditions</label>
                            <textarea
                                name="terms_and_conditions"
                                rows="10"
                                class="form-control @error('terms_and_conditions') is-invalid @enderror"
                                required
                            >{{ old('terms_and_conditions', $setting->terms_and_conditions) }}</textarea>
                            @error('terms_and_conditions')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn doctor-settings-btn px-4">Save Terms</button>
                    </form>
                </div>

                <div class="tab-pane fade" id="privacy-pane" role="tabpanel" aria-labelledby="privacy-tab" tabindex="0">
                    <form method="POST" action="{{ route('doctor.settings.update') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Privacy Policy</label>
                            <textarea
                                name="privacy_policy"
                                rows="10"
                                class="form-control @error('privacy_policy') is-invalid @enderror"
                                required
                            >{{ old('privacy_policy', $setting->privacy_policy) }}</textarea>
                            @error('privacy_policy')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn doctor-settings-btn px-4">Save Privacy Policy</button>
                    </form>
                </div>

                <div class="tab-pane fade" id="banner-pane" role="tabpanel" aria-labelledby="banner-tab" tabindex="0">
                    <form method="POST" action="{{ route('doctor.settings.banner.upload') }}" enctype="multipart/form-data" class="row g-3 mb-4">
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
                            @error('banner_image')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn doctor-settings-btn w-100">Upload Banner</button>
                        </div>
                    </form>

                    <div class="row g-3">
                        @forelse($banners as $banner)
                            <div class="col-md-4 col-xl-3">
                                <div class="card h-100 border">
                                    <img src="{{ asset($banner->image_path) }}" class="card-img-top" alt="doctor banner" style="height: 140px; object-fit: cover;">
                                    <div class="card-body py-2">
                                        <p class="mb-0 fw-semibold text-truncate">{{ $banner->title ?: 'Doctor Banner' }}</p>
                                    </div>
                                    <div class="card-footer bg-transparent border-0 pt-0">
                                        <form method="POST" action="{{ route('doctor.settings.banner.destroy', $banner) }}" onsubmit="return confirm('Remove this banner?')">
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
    .doctor-settings-btn {
        background-color: #198754;
        border-color: #198754;
        color: #fff;
    }
    .doctor-settings-btn:hover,
    .doctor-settings-btn:focus {
        background-color: #157347;
        border-color: #157347;
        color: #fff;
    }
</style>
@endpush
