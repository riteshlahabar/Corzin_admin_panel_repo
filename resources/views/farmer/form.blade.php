@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">{{ $formTitle }}</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ $formAction }}">
                        @csrf
                        @if($isEdit)
                            @method('PUT')
                        @endif

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Mobile</label>
                                <input type="text" name="mobile" class="form-control" value="{{ old('mobile', $farmer->mobile) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">First Name</label>
                                <input type="text" name="first_name" class="form-control" value="{{ old('first_name', $farmer->first_name) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Middle Name</label>
                                <input type="text" name="middle_name" class="form-control" value="{{ old('middle_name', $farmer->middle_name) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Last Name</label>
                                <input type="text" name="last_name" class="form-control" value="{{ old('last_name', $farmer->last_name) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Village</label>
                                <input type="text" name="village" class="form-control" value="{{ old('village', $farmer->village) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">City</label>
                                <input type="text" name="city" class="form-control" value="{{ old('city', $farmer->city) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Taluka</label>
                                <input type="text" name="taluka" class="form-control" value="{{ old('taluka', $farmer->taluka) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">District</label>
                                <input type="text" name="district" class="form-control" value="{{ old('district', $farmer->district) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">State</label>
                                <input type="text" name="state" class="form-control" value="{{ old('state', $farmer->state) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Pincode</label>
                                <input type="text" name="pincode" class="form-control" value="{{ old('pincode', $farmer->pincode) }}">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="farmerActive" {{ old('is_active', $farmer->is_active ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="farmerActive">Active</label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('farmer.list') }}" class="btn btn-light">Cancel</a>
                            <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Update Farmer' : 'Save Farmer' }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
