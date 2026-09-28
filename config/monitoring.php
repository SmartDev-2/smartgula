<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Konfigurasi Batas Status Monitoring
    |--------------------------------------------------------------------------
    |
    | Nilai di bawah ini adalah contoh sementara untuk prototipe.
    |
    | PENTING: Sesuaikan nilai-nilai ini berdasarkan hasil penelitian
    | atau data lapangan sebelum digunakan dalam lingkungan produksi.
    | Nilai ini BUKAN standar resmi manapun.
    |
    | Nilai dikirim ke frontend melalui DashboardController sehingga
    | logika status konsisten antara backend dan frontend.
    |
    */

    // --- Suhu (°C) ---
    // Jika suhu >= temperature_high  → status TINGGI
    // Jika suhu >= temperature_warning → status PERINGATAN
    // Selain itu → NORMAL
    'temperature_warning' => env('MONITORING_TEMP_WARNING', 30.0),
    'temperature_high'    => env('MONITORING_TEMP_HIGH',    33.0),

    // --- Kelembapan Relatif (%) ---
    // Jika RH >= humidity_high    → status TINGGI
    // Jika RH >= humidity_warning → status PERINGATAN
    // Selain itu → NORMAL
    'humidity_warning' => env('MONITORING_HUM_WARNING', 65.0),
    'humidity_high'    => env('MONITORING_HUM_HIGH',    75.0),

];
