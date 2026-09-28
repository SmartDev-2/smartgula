<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Tampilkan halaman dashboard monitoring.
     *
     * Konfigurasi batas status disertakan di sini agar mudah diubah
     * tanpa perlu menyentuh banyak file.
     *
     * CATATAN: Nilai batas di bawah adalah contoh awal (prototipe).
     * Sesuaikan nilai-nilai ini dengan hasil penelitian / data lapangan
     * sebelum digunakan dalam lingkungan produksi.
     */
    public function index(): View
    {
        $firebaseConfig = [
            'apiKey'            => config('firebase.api_key'),
            'authDomain'        => config('firebase.auth_domain'),
            'databaseURL'       => config('firebase.database_url'),
            'projectId'         => config('firebase.project_id'),
            'storageBucket'     => config('firebase.storage_bucket'),
            'messagingSenderId' => config('firebase.messaging_sender_id'),
            'appId'             => config('firebase.app_id'),
            'measurementId'     => config('firebase.measurement_id'),
        ];

        /**
         * Batas status kondisi lingkungan.
         *
         * PERINGATAN: Nilai di bawah ini bersifat contoh sementara.
         * Sesuaikan berdasarkan hasil penelitian atau standar teknis
         * yang relevan dengan kondisi ruang penyimpanan aktual.
         */
        $thresholds = [
            // Suhu (°C)
            'temperature_warning' => (float) config('monitoring.temperature_warning', 30.0),
            'temperature_high'    => (float) config('monitoring.temperature_high', 33.0),

            // Kelembapan relatif (%)
            'humidity_warning'    => (float) config('monitoring.humidity_warning', 65.0),
            'humidity_high'       => (float) config('monitoring.humidity_high', 75.0),
        ];

        $devicePath = 'sensor/device_01';

        return view('dashboard', compact('firebaseConfig', 'thresholds', 'devicePath'));
    }
}
