<?php

namespace App\Http\Controllers;

use App\Models\SensorData;

class DashboardController extends Controller
{
    public function index()
    {
        $latest = SensorData::latest()->first();
        $history = SensorData::latest()->take(20)->get();

        return view('dashboard', compact('latest', 'history'));
    }
}
