<div class="card diet-app-card">
    <div class="card-body p-3 p-lg-4">
        <form method="POST" action="{{ route('farmer.diet-plan.store') }}" class="diet-plan-form" id="dietPlanCreateForm">
            @csrf

            <div class="diet-summary-grid">
                <div class="diet-summary-box diet-summary-weight">
                    <div class="diet-summary-label">Body Weight</div>
                    <div class="diet-summary-value"><span data-summary="body-weight">0.00</span> <small>Kg</small></div>
                </div>
                <div class="diet-summary-box diet-summary-milk">
                    <div class="diet-summary-label">Milk Production</div>
                    <div class="diet-summary-value"><span data-summary="milk-production">0.00</span> <small>L</small></div>
                </div>
                <div class="diet-summary-box diet-summary-dmi">
                    <div class="diet-summary-label">Required DMI</div>
                    <div class="diet-summary-value"><span data-summary="target-dmi">0.00</span> <small>Kg</small></div>
                </div>
                <div class="diet-summary-box diet-summary-gap">
                    <div class="diet-summary-label">Gap</div>
                    <div class="diet-summary-value"><span data-summary="dmi-gap">0.00</span> <small>Kg</small></div>
                </div>
                <div class="diet-summary-box diet-summary-dry">
                    <div class="diet-summary-label">Dry Matter</div>
                    <div class="diet-summary-value"><span data-summary="planned-dry-matter">0.00</span> <small>Kg</small></div>
                </div>
                <div class="diet-summary-box diet-summary-feed">
                    <div class="diet-summary-label">Total Feeding</div>
                    <div class="diet-summary-value"><span data-summary="package-quantity">0.00</span> <small>Kg</small></div>
                </div>
            </div>

            <div class="diet-package-box mt-3">
                <div class="fw-bold mb-3" style="font-size:14px;">Daily Feeding Package</div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Farmer</label>
                        <select name="farmer_id" class="form-select diet-plan-farmer" required>
                            <option value="">Select farmer</option>
                            @foreach($farmers as $farmer)
                                <option value="{{ $farmer->id }}" {{ old('farmer_id') == $farmer->id ? 'selected' : '' }}>
                                    {{ trim(($farmer->first_name ?? '').' '.($farmer->last_name ?? '')) }} - {{ $farmer->mobile }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Choose Animal</label>
                        <select name="animal_id" class="form-select diet-plan-animal">
                            <option value="">Select animal</option>
                            @foreach($animals as $animal)
                                <option
                                    value="{{ $animal->id }}"
                                    data-farmer-id="{{ $animal->farmer_id }}"
                                    data-body-weight="{{ number_format((float) data_get($animalMetrics, $animal->id.'.body_weight', 0), 2, '.', '') }}"
                                    data-milk-production="{{ number_format((float) data_get($animalMetrics, $animal->id.'.milk_production', 0), 2, '.', '') }}"
                                    data-target-dmi="{{ number_format((float) data_get($animalMetrics, $animal->id.'.target_dmi', 0), 2, '.', '') }}"
                                    data-is-non-milking="{{ data_get($animalMetrics, $animal->id.'.is_non_milking', false) ? '1' : '0' }}"
                                    {{ old('animal_id') == $animal->id ? 'selected' : '' }}
                                >
                                    {{ $animal->animal_name }}{{ $animal->tag_number ? ' - '.$animal->tag_number : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Select Pen</label>
                        <select name="pan_id" class="form-select diet-plan-pan">
                            <option value="">No pen</option>
                            @foreach($pans as $pan)
                                <option
                                    value="{{ $pan->id }}"
                                    data-farmer-id="{{ $pan->farmer_id }}"
                                    data-body-weight="{{ number_format((float) data_get($panMetrics, $pan->id.'.body_weight', 0), 2, '.', '') }}"
                                    data-milk-production="{{ number_format((float) data_get($panMetrics, $pan->id.'.milk_production', 0), 2, '.', '') }}"
                                    data-target-dmi="{{ number_format((float) data_get($panMetrics, $pan->id.'.target_dmi', 0), 2, '.', '') }}"
                                    data-primary-animal-id="{{ (int) data_get($panMetrics, $pan->id.'.primary_animal_id', 0) }}"
                                    data-is-non-milking="{{ data_get($panMetrics, $pan->id.'.is_non_milking', false) ? '1' : '0' }}"
                                    {{ old('pan_id') == $pan->id ? 'selected' : '' }}
                                >
                                    {{ $pan->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Diet Plan Name</label>
                        <input type="text" name="diet_plan_name" class="form-control" placeholder="Enter diet plan name" value="{{ old('diet_plan_name') }}" required>
                    </div>
                    <div class="col-md-8 d-flex align-items-end">
                        <div class="small text-muted">
                            Select either one animal or one pen. Plan metrics will auto-calculate from today's milk entries.
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <div id="dietFeedBlocks"></div>
                <button type="button" class="btn btn-link text-success fw-bold px-1 mt-2" id="addFeedBlockBtn">
                    <i class="fa-solid fa-plus-circle me-1"></i> Add More Feed
                </button>
            </div>

            <input type="hidden" name="unit" id="dietPlanUnit" value="{{ old('unit', 'Kg') }}">
            <input type="hidden" name="feed_type_id" id="dietPlanPrimaryFeedType" value="{{ old('feed_type_id', '') }}">
            <input type="hidden" name="subtype_details_text" value="">
            <input type="hidden" name="reference_date" value="{{ now()->toDateString() }}">
            <input type="hidden" name="days_count" value="">
            <input type="hidden" name="body_weight" class="diet-input-body-weight" value="{{ old('body_weight', '') }}">
            <input type="hidden" name="milk_production" class="diet-input-milk-production" value="{{ old('milk_production', '') }}">
            <input type="hidden" name="target_dmi" class="diet-input-target-dmi" value="{{ old('target_dmi', '') }}">

            <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                <a href="{{ route('farmer.diet-plan') }}" class="btn btn-light border">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back to List
                </a>
                <button type="submit" class="btn btn-success px-4">
                    <i class="fa-solid fa-save me-1"></i> Save Diet Plan
                </button>
            </div>
        </form>
    </div>
</div>
