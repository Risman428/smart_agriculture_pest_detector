<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SensorData;

class SensorController extends Controller
{
    public function store(Request $request)
    {
        // Simpan semua data untuk status terakhir
        $data = SensorData::create([
            'temperature' => $request->temperature,
            'humidity'    => $request->humidity,
            'distance'    => $request->distance,
            'hama'        => $request->hama,
            'servo'       => $request->servo,
            'buzzer'      => $request->buzzer,
        ]);

        // Hanya simpan ke history jika hama terdeteksi
        if ($request->hama) {
            // Bisa pakai tabel terpisah jika mau, tapi di sini kita tetap pakai sensor_data
            // nanti di API history kita filter hama = 1
        }

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }
}
