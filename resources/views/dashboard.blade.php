@extends('layouts.app')

@section('content')

{{-- PAGE TITLE --}}
<div class="row mb-3">
    <div class="col-12">
        <h4 class="fw-bold">🐄 Smart Dairy Dashboard</h4>
    </div>
</div>

{{-- TOP STATS --}}
<div class="row">

    {{-- TOTAL FARMERS --}}
    <div class="col-md-6 col-lg-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <p class="mb-1">Total Farmers</p>
                        <h3 class="fw-bold">250</h3>
                    </div>
                    <i class="iconoir-user fs-1"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- TOTAL ANIMALS --}}
    <div class="col-md-6 col-lg-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <p class="mb-1">Total Animals</p>
                        <h3 class="fw-bold">120</h3>
                    </div>
                    <i class="iconoir-paw fs-1"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- MILK --}}
    <div class="col-md-6 col-lg-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <p class="mb-1">Milk Today</p>
                        <h3 class="fw-bold">320 L</h3>
                    </div>
                    <i class="iconoir-droplet fs-1"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- INCOME --}}
    <div class="col-md-6 col-lg-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <p class="mb-1">Income</p>
                        <h3 class="fw-bold">₹ 12,500</h3>
                    </div>
                    <i class="iconoir-dollar-circle fs-1"></i>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- PAN SYSTEM --}}
<div class="row mt-4">

    <div class="col-md-6 col-lg-3">
        <div class="card border-success">
            <div class="card-body text-center">
                <h6 class="text-muted">Milking Cows</h6>
                <h2 class="fw-bold text-success">45</h2>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="card border-warning">
            <div class="card-body text-center">
                <h6 class="text-muted">Dry Cows</h6>
                <h2 class="fw-bold text-warning">20</h2>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="card border-info">
            <div class="card-body text-center">
                <h6 class="text-muted">Calves</h6>
                <h2 class="fw-bold text-info">30</h2>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="card border-primary">
            <div class="card-body text-center">
                <h6 class="text-muted">Heifers</h6>
                <h2 class="fw-bold text-primary">25</h2>
            </div>
        </div>
    </div>

</div>

{{-- MILK + PROFIT --}}
<div class="row mt-4">

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">🥛 Milk Production</h5>
            </div>
            <div class="card-body">
                <p>Morning: <strong>150 L</strong></p>
                <p>Evening: <strong>170 L</strong></p>
                <h5>Total: <span class="text-success">320 L</span></h5>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">💰 Profit Analysis</h5>
            </div>
            <div class="card-body">
                <p>Milk Sales: ₹ 12,500</p>
                <p>Expenses: ₹ 4,200</p>
                <h4 class="text-success">Profit: ₹ 8,300</h4>
            </div>
        </div>
    </div>

</div>

{{-- ACTIVITY --}}
<div class="row mt-4">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header bg-light">
                <h5>📋 Recent Activities</h5>
            </div>
            <div class="card-body">

                <ul class="list-group">
                    <li class="list-group-item">🐄 New cow added</li>
                    <li class="list-group-item">🥛 Milk entry updated</li>
                    <li class="list-group-item">🌾 Feed record added</li>
                    <li class="list-group-item">💉 Animal vaccinated</li>
                </ul>

            </div>
        </div>
    </div>
</div>

@endsection