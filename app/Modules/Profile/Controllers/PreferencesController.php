<?php

namespace App\Modules\Profile\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class PreferencesController
{
    /**
     * Show the user preferences page
     * 
     * GET /preferences
     */
    public function index(Request $request): View
    {
        return view('preferences.index', [
            'user' => $request->user(),
        ]);
    }
}
