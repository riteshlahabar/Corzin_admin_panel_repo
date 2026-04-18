<?php

namespace App\Http\Controllers\Api\DoctorApp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LocationController extends Controller
{
    public function states()
    {
        $states = [];

        if (Schema::hasTable('location_hierarchies')) {
            $states = DB::table('location_hierarchies')
                ->whereNotNull('state')
                ->where('state', '!=', '')
                ->distinct()
                ->orderBy('state')
                ->pluck('state')
                ->map(fn ($value) => trim((string) $value))
                ->filter()
                ->values()
                ->all();
        }

        if (empty($states)) {
            $states = DB::table('doctors')
                ->whereNotNull('state')
                ->where('state', '!=', '')
                ->distinct()
                ->orderBy('state')
                ->pluck('state')
                ->map(fn ($value) => trim((string) $value))
                ->filter()
                ->values()
                ->all();
        }

        if (! in_array('Maharashtra', $states, true)) {
            array_unshift($states, 'Maharashtra');
        }

        $states = array_values(array_unique($states));

        return response()->json([
            'status' => true,
            'message' => 'States fetched successfully.',
            'data' => $states,
        ]);
    }

    public function districts(Request $request)
    {
        $state = trim((string) $request->query('state', ''));
        if ($state === '') {
            return response()->json([
                'status' => false,
                'message' => 'State is required.',
                'data' => [],
            ], 422);
        }

        $districts = [];

        if (Schema::hasTable('location_hierarchies')) {
            $districts = DB::table('location_hierarchies')
                ->where('state', $state)
                ->whereNotNull('district')
                ->where('district', '!=', '')
                ->distinct()
                ->orderBy('district')
                ->pluck('district')
                ->map(fn ($value) => trim((string) $value))
                ->filter()
                ->values()
                ->all();
        }

        if (empty($districts)) {
            $districts = DB::table('doctors')
                ->where('state', $state)
                ->whereNotNull('district')
                ->where('district', '!=', '')
                ->distinct()
                ->orderBy('district')
                ->pluck('district')
                ->map(fn ($value) => trim((string) $value))
                ->filter()
                ->values()
                ->all();
        }

        return response()->json([
            'status' => true,
            'message' => 'Districts fetched successfully.',
            'data' => $districts,
        ]);
    }

    public function talukas(Request $request)
    {
        $state = trim((string) $request->query('state', ''));
        $district = trim((string) $request->query('district', ''));
        if ($state === '' || $district === '') {
            return response()->json([
                'status' => false,
                'message' => 'State and district are required.',
                'data' => [],
            ], 422);
        }

        $talukas = [];

        if (Schema::hasTable('location_hierarchies')) {
            $talukas = DB::table('location_hierarchies')
                ->where('state', $state)
                ->where('district', $district)
                ->whereNotNull('taluka')
                ->where('taluka', '!=', '')
                ->distinct()
                ->orderBy('taluka')
                ->pluck('taluka')
                ->map(fn ($value) => trim((string) $value))
                ->filter()
                ->values()
                ->all();
        }

        if (empty($talukas)) {
            $talukas = DB::table('doctors')
                ->where('state', $state)
                ->where('district', $district)
                ->whereNotNull('taluka')
                ->where('taluka', '!=', '')
                ->distinct()
                ->orderBy('taluka')
                ->pluck('taluka')
                ->map(fn ($value) => trim((string) $value))
                ->filter()
                ->values()
                ->all();
        }

        return response()->json([
            'status' => true,
            'message' => 'Talukas fetched successfully.',
            'data' => $talukas,
        ]);
    }

    public function cities(Request $request)
    {
        $state = trim((string) $request->query('state', ''));
        $district = trim((string) $request->query('district', ''));
        $taluka = trim((string) $request->query('taluka', ''));
        if ($state === '' || $district === '' || $taluka === '') {
            return response()->json([
                'status' => false,
                'message' => 'State, district and taluka are required.',
                'data' => [],
            ], 422);
        }

        $cities = [];

        if (Schema::hasTable('location_hierarchies')) {
            $cities = DB::table('location_hierarchies')
                ->where('state', $state)
                ->where('district', $district)
                ->where('taluka', $taluka)
                ->whereNotNull('city')
                ->where('city', '!=', '')
                ->distinct()
                ->orderBy('city')
                ->pluck('city')
                ->map(fn ($value) => trim((string) $value))
                ->filter()
                ->values()
                ->all();
        }

        if (empty($cities)) {
            $cities = DB::table('doctors')
                ->where('state', $state)
                ->where('district', $district)
                ->where('taluka', $taluka)
                ->whereNotNull('city')
                ->where('city', '!=', '')
                ->distinct()
                ->orderBy('city')
                ->pluck('city')
                ->map(fn ($value) => trim((string) $value))
                ->filter()
                ->values()
                ->all();
        }

        return response()->json([
            'status' => true,
            'message' => 'Cities fetched successfully.',
            'data' => $cities,
        ]);
    }
}

