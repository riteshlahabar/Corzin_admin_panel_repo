@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
@php
    $kpiCards = [
        [
            'title' => 'Total Farmer',
            'value' => number_format((int) $totalFarmers),
            'sub' => 'Registered farmers',
            'change' => $farmersTrend,
            'bg' => 'linear-gradient(135deg, #16a34a 0%, #22c55e 100%)',
            'iconBg' => 'rgba(255, 255, 255, 0.16)',
            'textClass' => 'text-white',
            'subClass' => 'text-white-50',
            'icon' => 'iconoir-user',
            'image' => 'assets/images/extra/line-chart.png',
        ],
        [
            'title' => 'Total Doctor',
            'value' => number_format((int) $totalDoctors),
            'sub' => 'Registered doctors',
            'change' => $doctorsTrend,
            'bg' => 'linear-gradient(135deg, #0284c7 0%, #38bdf8 100%)',
            'iconBg' => 'rgba(255, 255, 255, 0.16)',
            'textClass' => 'text-white',
            'subClass' => 'text-white-50',
            'icon' => 'iconoir-stethoscope',
            'image' => 'assets/images/extra/bar.png',
        ],
        [
            'title' => 'Farmer Active Subscription',
            'value' => number_format((int) $farmerActiveSubscriptions),
            'sub' => 'Only active farmer plans',
            'change' => $farmerActiveSubscriptionTrend,
            'bg' => 'linear-gradient(135deg, #d97706 0%, #f59e0b 100%)',
            'iconBg' => 'rgba(255, 255, 255, 0.16)',
            'textClass' => 'text-white',
            'subClass' => 'text-white-50',
            'icon' => 'iconoir-percentage-circle',
            'image' => 'assets/images/extra/donut.png',
        ],
        [
            'title' => 'Total Revenue',
            'value' => 'Rs '.number_format((float) $farmerSubscriptionRevenue, 2),
            'sub' => 'Farmer subscription amount',
            'change' => $farmerSubscriptionRevenueTrend,
            'bg' => 'linear-gradient(135deg, #7c3aed 0%, #a855f7 100%)',
            'iconBg' => 'rgba(255, 255, 255, 0.16)',
            'textClass' => 'text-white',
            'subClass' => 'text-white-50',
            'icon' => 'iconoir-wallet',
            'image' => 'assets/images/extra/tree.png',
        ],
    ];
@endphp

<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box d-md-flex justify-content-md-between align-items-center">
            <h4 class="page-title">Dashboard</h4>
            <form method="GET" action="{{ route('dashboard') }}" class="d-flex align-items-center gap-2 flex-wrap">
                <div>
                    <label class="form-label mb-1 small text-muted">From Date</label>
                    <input type="date" name="from_date" class="form-control" value="{{ optional($selectedFromDate)->toDateString() }}">
                </div>
                <div>
                    <label class="form-label mb-1 small text-muted">To Date</label>
                    <input type="date" name="to_date" class="form-control" value="{{ optional($selectedToDate)->toDateString() }}">
                </div>
                <div class="d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-success">Filter</button>
                    <a href="{{ route('dashboard') }}" class="btn btn-light border">Reset</a>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="row justify-content-center">
    @foreach($kpiCards as $card)
        @php
            $isPositive = $card['change'] >= 0;
        @endphp
        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm" style="overflow: hidden; background: {{ $card['bg'] }};">
                <div class="card-body d-flex flex-column justify-content-between p-3">
                    <div class="d-flex align-items-start gap-2 mb-1">
                        <div class="flex-shrink-0 rounded-circle d-flex align-items-center justify-content-center text-white" style="height: 34px; width: 34px; background: {{ $card['iconBg'] }};">
                            <i class="{{ $card['icon'] }}" style="font-size: 17px;"></i>
                        </div>
                        <div class="flex-grow-1" style="min-width: 0;">
                            <p class="mb-1 fw-semibold text-truncate {{ $card['textClass'] }}" style="font-size: 12px; line-height: 1.2;">{{ $card['title'] }}</p>
                            <p class="mb-0 text-truncate {{ $card['subClass'] }}" style="font-size: 11px; line-height: 1.2;">
                                <span class="fw-semibold {{ $isPositive ? 'text-warning' : 'text-light' }}">
                                    {{ $isPositive ? '+' : '' }}{{ number_format((float) $card['change'], 1) }}%
                                </span>
                                {{ $trendLabel }}
                            </p>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between gap-2">
                        <div style="min-width: 0;">
                            <h5 class="mb-0 fw-bold text-truncate {{ $card['textClass'] }}" style="font-size: 17px; line-height: 1.2;">{{ $card['value'] }}</h5>
                            <small class="d-block text-truncate {{ $card['subClass'] }}" style="font-size: 10.5px;">{{ $card['sub'] }}</small>
                        </div>
                        <img src="{{ asset($card['image']) }}" alt="" class="flex-shrink-0" style="max-width: 38px; opacity: .28; filter: brightness(0) invert(1);">
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-8">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h4 class="card-title">Monthly Avg. Income</h4>
                    </div>
                    <div class="col-auto">
                        <span class="badge bg-primary-subtle text-primary">Last 12 months</span>
                    </div>
                </div>
            </div>
            <div class="card-body pt-0">
                <div id="monthly_income" class="apex-charts"></div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-4">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h4 class="card-title">System Distribution</h4>
                    </div>

                </div>
            </div>
            <div class="card-body pt-0">
                <div id="customers" class="apex-charts"></div>
                <div class="bg-light py-3 px-2 mb-0 mt-3 text-center rounded">
                    <h6 class="mb-0">
                        <i class="icofont-calendar fs-5 me-1"></i>
                        {{ now()->startOfYear()->format('d F Y') }} to {{ now()->endOfYear()->format('d F Y') }}
                    </h6>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h4 class="card-title">Top Farmer States</h4>
                    </div>
                    <div class="col-auto">
                        <span class="badge bg-success-subtle text-success">Live</span>
                    </div>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <tbody>
                            @forelse($topStates as $index => $state)
                                <tr>
                                    <td class="px-0">
                                        <div class="d-flex align-items-center">
                                            
                                            <div class="flex-grow-1 text-truncate">
                                                <h6 class="m-0 text-truncate">{{ $state['name'] }}</h6>
                                                <div class="d-flex align-items-center">
                                                    <div class="progress bg-primary-subtle w-100" style="height:4px;" role="progressbar" aria-valuenow="{{ (int) $state['percent'] }}" aria-valuemin="0" aria-valuemax="100">
                                                        <div class="progress-bar bg-primary" style="width: {{ $state['percent'] }}%"></div>
                                                    </div>
                                                    <small class="flex-shrink-1 ms-1">{{ $state['percent'] }}%</small>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-0 text-end">
                                        <span class="text-body ps-2 align-self-center text-end fw-medium">{{ $state['total'] }} farmers</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="text-muted text-center py-3">No state data available.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <hr class="my-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted" style="font-size: 12px;">Top Farmer By Milk Production</div>
                        <div class="fw-semibold">
                            {{ $topMilkProducer['name'] ?? 'No data available' }}
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="text-muted" style="font-size: 12px;">Total Milk</div>
                        <div class="fw-bold text-success">
                            {{ isset($topMilkProducer['milk']) ? number_format((float) $topMilkProducer['milk'], 2).' L' : '-' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-8">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h4 class="card-title">Popular Products</h4>
                    </div>
                    <div class="col-auto">
                        <span class="badge bg-info-subtle text-info">Shop Module</span>
                    </div>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="border-top-0">Product</th>
                                <th class="border-top-0">Price</th>
                                <th class="border-top-0">Unit</th>
                                <th class="border-top-0">Status</th>
                                <th class="border-top-0">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($popularProducts as $product)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="{{ asset('assets/images/products/01.png') }}" height="40" class="me-3 align-self-center rounded" alt="">
                                            <div class="flex-grow-1 text-truncate">
                                                <h6 class="m-0">{{ $product->name }}</h6>
                                                <a href="#" class="fs-12 text-primary">{{ strtoupper($product->category ?: 'general') }}</a>
                                            </div>
                                        </div>
                                    </td>
                                    <td>Rs {{ number_format((float) $product->price, 2) }}</td>
                                    <td>{{ $product->unit ?: '-' }}</td>
                                    <td>
                                        <span class="badge {{ $product->is_active ? 'bg-primary-subtle text-primary' : 'bg-danger-subtle text-danger' }} px-2">
                                            {{ $product->is_active ? 'Live' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="#"><i class="las la-pen text-secondary fs-18"></i></a>
                                        <a href="#"><i class="las la-trash-alt text-secondary fs-18"></i></a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">No products found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h4 class="card-title">Subdistrict Wise Animal Types</h4>
                        <p class="text-muted mb-0" style="font-size: 12px;">Taluka-wise count of the four animal types.</p>
                    </div>
                    <div class="col-auto">
                        <span class="badge bg-success-subtle text-success">
                            {{ $selectedFromDate && $selectedToDate ? optional($selectedFromDate)->format('d M Y').' to '.optional($selectedToDate)->format('d M Y') : 'All records' }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="card-body pt-0">
                @if(empty($animalTypeChart['labels']))
                    <div class="text-center text-muted py-5">No animal data found for the selected range.</div>
                @else
                    <div id="animal_type_subdistrict_chart" class="apex-charts" style="min-height: {{ $animalTypeChart['height'] }}px;"></div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('assets/libs/apexcharts/apexcharts.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const monthlyOptions = {
            chart: {
                type: 'area',
                height: 340,
                toolbar: { show: false }
            },
            stroke: {
                curve: 'smooth',
                width: 2
            },
            series: [
                { name: 'Milk Revenue', data: @json($monthlyMilkRevenue) },
                { name: 'Visit Revenue', data: @json($monthlyVisitRevenue) },
                { name: 'Total Revenue', data: @json($monthlyTotalRevenue) }
            ],
            xaxis: {
                categories: @json($monthlyLabels)
            },
            colors: ['#16a34a', '#0284c7', '#7c3aed'],
            dataLabels: { enabled: false },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.25,
                    opacityTo: 0.05
                }
            },
            yaxis: {
                labels: {
                    formatter: function(value) { return 'Rs ' + Number(value).toFixed(0); }
                }
            },
            tooltip: {
                y: {
                    formatter: function(value) { return 'Rs ' + Number(value).toFixed(2); }
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'right'
            }
        };

        const customerOptions = {
            chart: {
                type: 'donut',
                height: 305
            },
            labels: @json($distributionLabels),
            series: @json($distributionSeries),
            colors: ['#22c55e', '#0ea5e9', '#6366f1', '#f59e0b'],
            dataLabels: { enabled: true },
            legend: { position: 'bottom' },
            plotOptions: {
                pie: {
                    donut: {
                        size: '70%'
                    }
                }
            }
        };

        const animalTypeTalukaOptions = {
            chart: {
                type: 'bar',
                height: {{ (int) ($animalTypeChart['height'] ?? 320) }},
                stacked: true,
                toolbar: { show: false }
            },
            series: @json($animalTypeChart['series'] ?? []),
            xaxis: {
                categories: @json($animalTypeChart['labels'] ?? []),
                title: { text: 'Taluka / Subdistrict' }
            },
            yaxis: {
                title: { text: 'Animals' }
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 4,
                    barHeight: '58%'
                }
            },
            dataLabels: { enabled: false },
            legend: {
                position: 'top',
                horizontalAlign: 'left'
            },
            colors: ['#16a34a', '#0284c7', '#f59e0b', '#7c3aed'],
            grid: {
                borderColor: '#edf2f7'
            },
            tooltip: {
                y: {
                    formatter: function(value) { return Number(value).toFixed(0) + ' animals'; }
                }
            }
        };

        const incomeEl = document.querySelector('#monthly_income');
        if (incomeEl) {
            new ApexCharts(incomeEl, monthlyOptions).render();
        }

        const customersEl = document.querySelector('#customers');
        if (customersEl) {
            new ApexCharts(customersEl, customerOptions).render();
        }

        const animalTypeTalukaEl = document.querySelector('#animal_type_subdistrict_chart');
        if (animalTypeTalukaEl) {
            new ApexCharts(animalTypeTalukaEl, animalTypeTalukaOptions).render();
        }
    });
</script>
@endpush






