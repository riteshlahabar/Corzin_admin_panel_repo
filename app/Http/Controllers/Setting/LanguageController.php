<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;

class LanguageController extends Controller
{
    public function index()
    {
        return view('settings.language');
    }
}
