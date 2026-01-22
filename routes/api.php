<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SensorController;
use App\Models\SensorData;

Route::post('/sensor', [SensorController::class, 'store']);

// Route::get('/test', function () {
//     return 'API OK';
// });

Route::get('/latest', function() {
    $latest = \App\Models\SensorData::latest()->first();

    $history = \App\Models\SensorData::where('hama',1)
                         ->latest()
                         ->take(20)
                         ->get();

    return response()->json([
        'temperature' => $latest->temperature,
        'humidity' => $latest->humidity,
        'distance'    => $latest->distance,
        'servo' => $latest->servo,
        'buzzer' => $latest->buzzer,
        'history' => $history->map(function($row){
            return ['created_at' => $row->created_at];
        })
    ]);
});
