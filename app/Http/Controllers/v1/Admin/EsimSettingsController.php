<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


class EsimSettingsController extends Controller
{
    public function index()
    {
        $esim_markup_percentage = Settings::get('esim_markup_percentage');
        $last_updated = Settings::where('key', 'esim_markup_percentage')->value('updated_at');

        return view('dashboard.esim.settings', compact('esim_markup_percentage', 'last_updated'));
    }

    public function updateEsimSettings(Request $request)
    {
        $request->validate([
            'esim_markup_percentage' => 'required|numeric|min:0|max:100',
        ]);

        Settings::updateOrCreate(
            ['key' => 'esim_markup_percentage'],
            ['value' => $request->esim_markup_percentage]
        );

        return back()->with('success', 'eSIM markup percentage updated successfully!');
    }


}
