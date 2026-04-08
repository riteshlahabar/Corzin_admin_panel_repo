@extends('layouts.app')
@section('title', 'Register Doctor')

@section('content')
<div class="container-fluid">
    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm">
            <div class="fw-semibold mb-1">Please fix the highlighted fields.</div>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="doctor-page-heading">
        <h3 class="text-dark mb-0">Register Doctor</h3>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4 p-lg-5">
            <form method="POST" action="{{ route('doctor.store') }}" enctype="multipart/form-data" autocomplete="off">
                @csrf

                <div class="doctor-form-section">
                    <div class="doctor-form-section-title">
                        <div>
                            <h5 class="mb-1">Personal Details</h5>
                            <p class="text-muted mb-0">Add the doctor's identity and contact information.</p>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Dr First Name</label>
                            <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Clinic Name</label>
                            <input type="text" name="clinic_name" class="form-control @error('clinic_name') is-invalid @enderror" value="{{ old('clinic_name') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Degree</label>
                            <input type="text" name="degree" class="form-control @error('degree') is-invalid @enderror" value="{{ old('degree') }}" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Contact Number</label>
                            <input type="text" name="contact_number" class="form-control @error('contact_number') is-invalid @enderror" value="{{ old('contact_number') }}" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="" autocomplete="off" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Aadhar Number</label>
                            <input type="text" name="adhar_number" class="form-control @error('adhar_number') is-invalid @enderror" value="{{ old('adhar_number') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">PAN Number</label>
                            <input type="text" name="pan_number" class="form-control @error('pan_number') is-invalid @enderror" value="{{ old('pan_number') }}" required>
                        </div>
                    </div>
                </div>

                <div class="doctor-form-section">
                    <div class="doctor-form-section-title">
                        <div>
                            <h5 class="mb-1">Professional And Clinic Details</h5>
                            <p class="text-muted mb-0">Capture registration numbers and clinic address details.</p>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">MMC Reg No</label>
                            <input type="text" name="mmc_registration_number" class="form-control @error('mmc_registration_number') is-invalid @enderror" value="{{ old('mmc_registration_number') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Clinic Reg No</label>
                            <input type="text" name="clinic_registration_number" class="form-control @error('clinic_registration_number') is-invalid @enderror" value="{{ old('clinic_registration_number') }}" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Clinic Address</label>
                            <textarea name="clinic_address" class="form-control @error('clinic_address') is-invalid @enderror" rows="3" required>{{ old('clinic_address') }}</textarea>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Village</label>
                            <input type="text" name="village" class="form-control @error('village') is-invalid @enderror" value="{{ old('village') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-control @error('city') is-invalid @enderror" value="{{ old('city') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Taluka</label>
                            <input type="text" name="taluka" class="form-control @error('taluka') is-invalid @enderror" value="{{ old('taluka') }}" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">District</label>
                            <input type="text" name="district" class="form-control @error('district') is-invalid @enderror" value="{{ old('district') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">State</label>
                            <input type="text" name="state" class="form-control @error('state') is-invalid @enderror" value="{{ old('state') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Pincode</label>
                            <input type="text" name="pincode" class="form-control @error('pincode') is-invalid @enderror" value="{{ old('pincode') }}" required>
                        </div>
                    </div>
                </div>

                <div class="doctor-form-section">
                    <div class="doctor-form-section-title">
                        <div>
                            <h5 class="mb-1">Security</h5>
                            <p class="text-muted mb-0">Set the doctor login credentials for the mobile app.</p>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" value="" autocomplete="new-password" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Repeat Password</label>
                            <input type="password" name="password_confirmation" class="form-control" value="" autocomplete="new-password" required>
                        </div>
                    </div>
                </div>

                <div class="doctor-form-section mb-0">
                    <div class="doctor-form-section-title">
                        <div>
                            <h5 class="mb-1">Required Documents</h5>
                            <p class="text-muted mb-0">Upload all mandatory attachments for verification and approval.</p>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6 col-xl-4">
                            <label class="doctor-upload-card">
                                <span class="doctor-upload-icon"><i class="iconoir-page"></i></span>
                                <span class="doctor-upload-title">Aadhar Attachment</span>
                                <span class="doctor-upload-subtitle">PDF, JPG, JPEG, PNG</span>
                                <input type="file" name="adhar_document" class="form-control @error('adhar_document') is-invalid @enderror mt-3" required>
                            </label>
                        </div>
                        <div class="col-md-6 col-xl-4">
                            <label class="doctor-upload-card">
                                <span class="doctor-upload-icon"><i class="iconoir-page"></i></span>
                                <span class="doctor-upload-title">PAN Attachment</span>
                                <span class="doctor-upload-subtitle">PDF, JPG, JPEG, PNG</span>
                                <input type="file" name="pan_document" class="form-control @error('pan_document') is-invalid @enderror mt-3" required>
                            </label>
                        </div>
                        <div class="col-md-6 col-xl-4">
                            <label class="doctor-upload-card">
                                <span class="doctor-upload-icon"><i class="iconoir-page"></i></span>
                                <span class="doctor-upload-title">MMC Attachment</span>
                                <span class="doctor-upload-subtitle">PDF, JPG, JPEG, PNG</span>
                                <input type="file" name="mmc_document" class="form-control @error('mmc_document') is-invalid @enderror mt-3" required>
                            </label>
                        </div>
                        <div class="col-md-6 col-xl-6">
                            <label class="doctor-upload-card">
                                <span class="doctor-upload-icon"><i class="iconoir-page-search"></i></span>
                                <span class="doctor-upload-title">Clinic Reg Attachment</span>
                                <span class="doctor-upload-subtitle">PDF, JPG, JPEG, PNG</span>
                                <input type="file" name="clinic_registration_document" class="form-control @error('clinic_registration_document') is-invalid @enderror mt-3" required>
                            </label>
                        </div>
                        <div class="col-md-6 col-xl-6">
                            <label class="doctor-upload-card">
                                <span class="doctor-upload-icon"><i class="iconoir-user"></i></span>
                                <span class="doctor-upload-title">Doctor Photo</span>
                                <span class="doctor-upload-subtitle">Image only</span>
                                <input type="file" name="doctor_photo" class="form-control @error('doctor_photo') is-invalid @enderror mt-3" required>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="doctor-form-footer mt-4">
                    <div>
                        <div class="fw-semibold">Ready to submit?</div>
                        <small class="text-muted">Doctor account will remain unapproved until admin verification is completed.</small>
                    </div>
                    <button type="submit" class="btn btn-primary px-4">Register Doctor</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .doctor-form-section {
        padding-bottom: 28px;
        margin-bottom: 28px;
        border-bottom: 1px solid #edf1f7;
    }
    .doctor-form-section-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: -12px;
        margin-bottom: 12px;
    }
    .doctor-form-section-title h5,
    .doctor-form-section-title p {
        margin-top: 0 !important;
    }
    .doctor-page-heading {
        margin-top: 18px;
        margin-bottom: 18px;
    }
    .doctor-upload-card {
        display: block;
        padding: 18px;
        border: 1px solid #e8edf5;
        border-radius: 16px;
        background: #f8fafc;
        height: 100%;
    }
    .doctor-upload-icon {
        height: 42px;
        width: 42px;
        border-radius: 14px;
        background: #eaf4ee;
        color: #198754;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        margin-bottom: 12px;
    }
    .doctor-upload-title {
        display: block;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 4px;
    }
    .doctor-upload-subtitle {
        display: block;
        color: #7b8794;
        font-size: 12px;
    }
    .doctor-form-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }
</style>
@endpush
