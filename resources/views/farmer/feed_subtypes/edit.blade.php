@extends('layouts.app')
@section('title', 'Edit Feed Sub Type')

@section('content')
<div class="container-fluid">
    <div class="row mt-4 mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h4 class="mb-0 text-dark">Edit Feed Sub Type</h4>
            <a href="{{ route('farmer.feed-subtypes.index') }}" class="btn btn-light border">Back</a>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('farmer.feed-subtypes.update', $feedSubtype) }}" class="row g-3">
                @csrf
                @method('PUT')
                <div class="col-md-6">
                    <label class="form-label">Farmer <span class="text-danger">*</span></label>
                    <select name="farmer_id" class="form-select @error('farmer_id') is-invalid @enderror" required>
                        <option value="">Select farmer</option>
                        @foreach($farmers as $farmer)
                            <option value="{{ $farmer->id }}" {{ old('farmer_id', $feedSubtype->farmer_id) == $farmer->id ? 'selected' : '' }}>
                                {{ trim(($farmer->first_name ?? '').' '.($farmer->last_name ?? '')) ?: 'Farmer #'.$farmer->id }} - {{ $farmer->mobile }}
                            </option>
                        @endforeach
                    </select>
                    @error('farmer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Feed Type <span class="text-danger">*</span></label>
                    <select name="feed_type_id" class="form-select @error('feed_type_id') is-invalid @enderror" required>
                        <option value="">Select feed type</option>
                        @foreach($feedTypes as $feedType)
                            <option value="{{ $feedType->id }}" {{ old('feed_type_id', $feedSubtype->feed_type_id) == $feedType->id ? 'selected' : '' }}>
                                {{ $feedType->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('feed_type_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Feed Sub Type Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $feedSubtype->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="is_active" name="is_active" {{ old('is_active', $feedSubtype->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
                <div class="col-12 d-flex justify-content-end gap-2">
                    <a href="{{ route('farmer.feed-subtypes.index') }}" class="btn btn-light border">Cancel</a>
                    <button type="submit" class="btn btn-success">Update Feed Sub Type</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
