@extends('layouts.app')
@section('title', 'Edit Templates')

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
    @endif

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <h4 class="mb-0 text-dark">Edit Templates</h4>
        <small class="text-muted">Manage notification title/body templates for appointment and shop flow.</small>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="min-width: 200px;">Template</th>
                            <th style="min-width: 260px;">Title</th>
                            <th style="min-width: 320px;">Body</th>
                            <th style="min-width: 100px;">Status</th>
                            <th style="min-width: 90px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($templates as $template)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $template->template_name }}</div>
                                </td>
                                <td>{{ $template->title_template }}</td>
                                <td>{{ $template->body_template }}</td>
                                <td>
                                    <span class="badge {{ $template->is_active ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $template->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editTemplate{{ $template->id }}">
                                        Edit
                                    </button>
                                </td>
                            </tr>

                            <div class="modal fade" id="editTemplate{{ $template->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <form method="POST" action="{{ route('settings.templates.update', $template) }}">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Template</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label">Template Name</label>
                                                    <input type="text" name="template_name" class="form-control" value="{{ $template->template_name }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Title Template</label>
                                                    <input type="text" name="title_template" class="form-control" value="{{ $template->title_template }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Body Template</label>
                                                    <textarea name="body_template" rows="4" class="form-control" required>{{ $template->body_template }}</textarea>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" value="1" id="activeTemplate{{ $template->id }}" name="is_active" {{ $template->is_active ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="activeTemplate{{ $template->id }}">Active</label>
                                                </div>
                                                <div class="alert alert-light border mt-3 mb-0">
                                                    <div class="fw-semibold mb-1">Supported placeholders</div>
                                                    <small class="text-muted">
                                                        <code>{{ '{' }}{appointment_id}{{ '}' }}</code>,
                                                        <code>{{ '{' }}{farmer_name}{{ '}' }}</code>,
                                                        <code>{{ '{' }}{animal_name}{{ '}' }}</code>,
                                                        <code>{{ '{' }}{doctor_name}{{ '}' }}</code>,
                                                        <code>{{ '{' }}{otp}{{ '}' }}</code>,
                                                        <code>{{ '{' }}{order_code}{{ '}' }}</code>
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-success">Save Template</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No templates available.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
