<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Dashboard Monitoring Suhu dan Kelembapan Ruang Penyimpanan Gula">
    <title>Monitoring Ruang Penyimpanan Gula</title>

    {{-- Google Fonts: Inter --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Dashboard CSS --}}
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

    {{-- ================================================================
         Konfigurasi Firebase & Threshold disuntikkan dari server.
         Tidak ada credential rahasia di sini — hanya Web SDK config
         dan nilai batas monitoring.
         ================================================================ --}}
    <script>
        window.__FIREBASE_CONFIG__ = @json($firebaseConfig);
        window.__THRESHOLDS__      = @json($thresholds);
        window.__DEVICE_PATH__     = @json($devicePath);
    </script>
</head>
<body>

<div class="app-wrapper">

    {{-- ============================================================
         HEADER
         ============================================================ --}}
    <header class="app-header">
        <div>
            <h1 class="app-header__title">
                {{-- Ikon gudang --}}
                <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
                Monitoring Ruang Penyimpanan Gula
            </h1>
            <p class="app-header__subtitle">Monitoring Suhu dan Kelembapan</p>
        </div>

        <div class="header-meta">
            {{-- Indikator status koneksi sensor --}}
            <span class="connection-badge disconnected" id="conn-badge" role="status" aria-live="polite">
                <span class="connection-badge__dot"></span>
                <span id="conn-text">Menghubungkan…</span>
            </span>

            {{-- Waktu pembaruan terakhir --}}
            <span class="last-update" id="last-update" aria-live="polite">Menunggu data…</span>
        </div>
    </header>

    {{-- ============================================================
         CARDS: SUHU  |  KELEMBAPAN  |  STATUS
         ============================================================ --}}
    <div class="cards-grid">

        {{-- Card Suhu --}}
        <div class="metric-card" role="region" aria-label="Suhu">
            <div class="metric-card__label">
                {{-- Ikon termometer --}}
                <svg class="metric-card__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                     fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 14.76V3.5a2.5 2.5 0 0 0-5 0v11.26a4.5 4.5 0 1 0 5 0z"/>
                </svg>
                Suhu
            </div>
            <div class="metric-card__value">
                <span id="val-suhu">—</span>
                <span class="metric-card__unit">°C</span>
            </div>
        </div>

        {{-- Card Kelembapan --}}
        <div class="metric-card" role="region" aria-label="Kelembapan">
            <div class="metric-card__label">
                {{-- Ikon tetes air --}}
                <svg class="metric-card__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                     fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>
                </svg>
                Kelembapan
            </div>
            <div class="metric-card__value">
                <span id="val-kelembapan">—</span>
                <span class="metric-card__unit">%</span>
            </div>
        </div>

        {{-- Card Status --}}
        <div class="metric-card metric-card--status" role="region" aria-label="Status Kondisi">
            <div class="metric-card__label">
                {{-- Ikon status/shield --}}
                <svg class="metric-card__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                     fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
                Status Kondisi
            </div>
            <div class="metric-card__value">
                <span id="val-status" class="status-badge">—</span>
            </div>
            <p class="status-desc" id="val-status-desc">Menunggu data sensor…</p>
        </div>

    </div>

    {{-- ============================================================
         GRAFIK RIWAYAT
         ============================================================ --}}
    <div class="section-card" role="region" aria-label="Grafik Riwayat Sensor">
        <div class="section-card__header">
            <h2 class="section-card__title">
                {{-- Ikon grafik --}}
                <svg width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                </svg>
                Riwayat Suhu dan Kelembapan
            </h2>
            <span class="section-card__badge">{{ $historyLimit ?? 50 }} data terakhir</span>
        </div>
        <div class="chart-container">
            <canvas id="sensor-chart" aria-label="Grafik riwayat suhu dan kelembapan"></canvas>
        </div>
    </div>

    {{-- ============================================================
         TABEL RIWAYAT
         ============================================================ --}}
    <div class="section-card" role="region" aria-label="Tabel Riwayat Data">
        <div class="section-card__header">
            <h2 class="section-card__title">
                {{-- Ikon tabel --}}
                <svg width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                    <line x1="3" y1="9" x2="21" y2="9"/>
                    <line x1="3" y1="15" x2="21" y2="15"/>
                    <line x1="9" y1="3" x2="9" y2="21"/>
                </svg>
                Riwayat Data
            </h2>
            <span class="section-card__badge">Terbaru di atas</span>
        </div>

        <div class="data-table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th class="text-right">Suhu</th>
                        <th class="text-right">Kelembapan</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="history-tbody">
                    <tr>
                        <td colspan="4" class="table-empty">Memuat data…</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>{{-- .app-wrapper --}}

{{-- ============================================================
     Dashboard JS — ESM module mengimpor Firebase SDK dari CDN
     ============================================================ --}}
<script type="module" src="{{ asset('js/dashboard.js') }}"></script>

</body>
</html>
