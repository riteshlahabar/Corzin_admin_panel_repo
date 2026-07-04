<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppTranslation;
use Illuminate\Support\Facades\Schema;

class AppTranslationController extends Controller
{
    public function index()
    {
        if (! Schema::hasTable('app_translations')) {
            return response()->json([
                'status' => true,
                'message' => 'Translations table not ready.',
                'data' => [
                    'hi' => [],
                    'mr' => [],
                ],
            ]);
        }

        $translations = AppTranslation::query()
            ->where('is_active', true)
            ->orderBy('group_name')
            ->orderBy('translation_key')
            ->get();

        $mapped = [
            'hi' => [],
            'mr' => [],
        ];

        foreach ($translations as $translation) {
            $key = trim((string) $translation->translation_key);
            if ($key === '') {
                continue;
            }

            $hiValue = trim((string) ($translation->hi_value ?? ''));
            $mrValue = trim((string) ($translation->mr_value ?? ''));

            if ($hiValue !== '') {
                $mapped['hi'][$key] = $hiValue;
            }

            if ($mrValue !== '') {
                $mapped['mr'][$key] = $mrValue;
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Translations fetched successfully.',
            'data' => $mapped,
        ]);
    }
}
