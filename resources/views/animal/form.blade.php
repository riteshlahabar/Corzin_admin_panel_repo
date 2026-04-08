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
                    <form method="POST" action="{{ $formAction }}" enctype="multipart/form-data">
                        @csrf
                        @if($isEdit)
                            @method('PUT')
                        @endif

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Farmer</label>
                                <select name="farmer_id" class="form-select" required>
                                    <option value="">Select farmer</option>
                                    @foreach($farmers as $farmer)
                                        <option value="{{ $farmer->id }}" {{ (string) old('farmer_id', $animal->farmer_id) === (string) $farmer->id ? 'selected' : '' }}>
                                            {{ trim(($farmer->first_name ?? '').' '.($farmer->last_name ?? '')) }} - {{ $farmer->mobile }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Animal Name</label>
                                <input type="text" name="animal_name" class="form-control" value="{{ old('animal_name', $animal->animal_name) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tag Number</label>
                                <input type="text" name="tag_number" class="form-control" value="{{ old('tag_number', $animal->tag_number) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Animal Type</label>
                                <select name="animal_type_id" class="form-select" required>
                                    <option value="">Select type</option>
                                    @foreach($animalTypes as $type)
                                        <option value="{{ $type->id }}" {{ (string) old('animal_type_id', $animal->animal_type_id) === (string) $type->id ? 'selected' : '' }}>
                                            {{ $type->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Birth Date</label>
                                <input type="date" name="birth_date" class="form-control" value="{{ old('birth_date', $animal->birth_date ? \Carbon\Carbon::parse($animal->birth_date)->format('Y-m-d') : '') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Gender</label>
                                <select name="gender" class="form-select" required>
                                    <option value="">Select gender</option>
                                    <option value="Male" {{ old('gender', $animal->gender) === 'Male' ? 'selected' : '' }}>Male</option>
                                    <option value="Female" {{ old('gender', $animal->gender) === 'Female' ? 'selected' : '' }}>Female</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Weight (kg)</label>
                                <input type="number" step="0.01" name="weight" class="form-control" value="{{ old('weight', $animal->weight) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Image</label>
                                <input type="file" name="image" class="form-control" accept="image/png,image/jpeg">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="animalActive" {{ old('is_active', $animal->is_active ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="animalActive">Active</label>
                                </div>
                            </div>
                            @if($isEdit && $animal->image)
                            <div class="col-md-4">
                                <label class="form-label d-block">Current Image</label>
                                <img src="{{ asset($animal->image) }}" alt="Animal Image" class="img-fluid rounded border" style="max-height:110px;">
                            </div>
                            @endif
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('farmer.animals') }}" class="btn btn-light">Cancel</a>
                            <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Update Animal' : 'Save Animal' }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
