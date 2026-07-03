<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\AppTranslation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class LanguageController extends Controller
{
    public function index(Request $request): View
    {
        $tableReady = Schema::hasTable('app_translations');
        $search = trim((string) $request->query('search', ''));

        $translations = collect();
        if ($tableReady) {
            $translations = AppTranslation::query()
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($inner) use ($search) {
                        $inner->where('group_name', 'like', "%{$search}%")
                            ->orWhere('translation_key', 'like', "%{$search}%")
                            ->orWhere('en_value', 'like', "%{$search}%")
                            ->orWhere('hi_value', 'like', "%{$search}%")
                            ->orWhere('mr_value', 'like', "%{$search}%");
                    });
                })
                ->orderBy('group_name')
                ->orderBy('translation_key')
                ->paginate($this->tablePerPage($request))
                ->withQueryString();
        }

        return view('settings.language', [
            'tableReady' => $tableReady,
            'translations' => $translations,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! Schema::hasTable('app_translations')) {
            return back()->with('error', 'Please run the language migration first.');
        }

        $data = $request->validate([
            'group_name' => ['required', 'string', 'max:100'],
            'translation_key' => ['required', 'string', 'max:190', 'unique:app_translations,translation_key'],
            'en_value' => ['nullable', 'string'],
            'hi_value' => ['nullable', 'string'],
            'mr_value' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        AppTranslation::create([
            'group_name' => trim($data['group_name']),
            'translation_key' => trim($data['translation_key']),
            'en_value' => $data['en_value'] ?? null,
            'hi_value' => $data['hi_value'] ?? null,
            'mr_value' => $data['mr_value'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return redirect()->route('settings.language.index')->with('success', 'Translation added successfully.');
    }

    public function update(Request $request, AppTranslation $translation): RedirectResponse
    {
        $data = $request->validate([
            'group_name' => ['required', 'string', 'max:100'],
            'translation_key' => ['required', 'string', 'max:190', 'unique:app_translations,translation_key,' . $translation->id],
            'en_value' => ['nullable', 'string'],
            'hi_value' => ['nullable', 'string'],
            'mr_value' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $translation->update([
            'group_name' => trim($data['group_name']),
            'translation_key' => trim($data['translation_key']),
            'en_value' => $data['en_value'] ?? null,
            'hi_value' => $data['hi_value'] ?? null,
            'mr_value' => $data['mr_value'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        return redirect()->route('settings.language.index')->with('success', 'Translation updated successfully.');
    }

    public function toggle(AppTranslation $translation): RedirectResponse
    {
        $translation->update([
            'is_active' => ! $translation->is_active,
        ]);

        return redirect()->route('settings.language.index')->with('success', 'Translation status updated successfully.');
    }
}