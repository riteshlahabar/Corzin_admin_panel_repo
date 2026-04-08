<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farmer\AnimalType;
use Illuminate\Http\Request;

class AnimalTypeController extends Controller
{
    public function index()
    {
        try {
            $animalTypes = AnimalType::select('id', 'name')
                ->orderBy('name', 'asc')
                ->get();

            return response()->json([
                'status' => true,
                'message' => 'Animal types fetched successfully',
                'data' => $animalTypes,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch animal types',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}