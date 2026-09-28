<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Dashboard Monitoring Suhu dan Kelembapan Ruang Penyimpanan Gula">
    <title>SmartGula — Monitoring</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

    {{-- Firebase config & thresholds disuntikkan dari server (tidak ada secret di sini) --}}
    <script>
        window.__FIREBASE_CONFIG__ = @json($firebaseConfig);
        window.__THRESHOLDS__      = @json($thresholds);
        window.__DEVICE_PATH__     = @json($devicePath);
    </script>
</head>
<body>

{{-- ================================================================
     SIDEBAR
     ================================================================ --}}
<aside class="sidebar" id="sidebar">

    {{-- Brand --}}
    <a class="sidebar__brand" href="#">
        <div class="sidebar__brand-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
        </div>
        <div class="sidebar__brand-text">
            <span class="sidebar__brand-name">SmartGula</span>
            <span class="sidebar__brand-sub">Ruang Penyimpanan</span>
        </div>
    </a>

    {{-- Navigation --}}
    <nav class="sidebar__nav">
        <div class="sidebar__nav-label">Menu</div>

        <a class="sidebar__nav-item active" href="#">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
            </svg>
            Dashboard
        </a>

        <div class="sidebar__nav-label">Sensor</div>

        <span class="sidebar__nav-item">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 14.76V3.5a2.5 2.5 0 0 0-5 0v11.26a4.5 4.5 0 1 0 5 0z"/>
            </svg>
            Suhu
        </span>

        <span class="sidebar__nav-item">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>
            </svg>
            Kelembapan
        </span>

        <div class="sidebar__nav-label">Perangkat</div>

        <span class="sidebar__nav-item">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="3"/><circle cx="12" cy="12" r="8" stroke-dasharray="2 2"/>
            </svg>
            Device 01
        </span>
    </nav>

    {{-- Connection footer --}}
    <div class="sidebar__footer">
        <div class="sidebar__conn disconnected" id="conn-badge" role="status" aria-live="polite">
            <div class="sidebar__conn-dot"></div>
            <div class="sidebar__conn-info">
                <span class="sidebar__conn-label" id="conn-text">Menghubungkan…</span>
                <span class="sidebar__conn-sublabel">Firebase RT DB</span>
            </div>
        </div>
    </div>

</aside>

{{-- ================================================================
     MAIN CONTENT
     ================================================================ --}}
<main class="main">

    {{-- TOPBAR --}}
    <header class="topbar">
        <div class="topbar__left">
            <div class="topbar__title">Monitoring Ruang Penyimpanan Gula</div>
            <div class="topbar__subtitle">Monitoring Suhu &amp; Kelembapan secara Real-time</div>
        </div>
        <div class="topbar__right">
            <div class="topbar__device-badge">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="3"/>
                </svg>
                DHT22 · device_01
            </div>
            <div class="topbar__time" id="last-update" aria-live="polite">Menunggu data…</div>
        </div>
    </header>

    {{-- PAGE CONTENT --}}
    <div class="page-content">

        {{-- ==================================================
             ROW 1 — METRIC CARDS
             ================================================== --}}
        <div class="cards-grid">

            {{-- Card Suhu --}}
            <div class="metric-card metric-card--temp fade-in" role="region" aria-label="Suhu">
                <div class="metric-card__header">
                    <span class="metric-card__label">Suhu</span>
                    <div class="metric-card__icon-wrap">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
                             stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 14.76V3.5a2.5 2.5 0 0 0-5 0v11.26a4.5 4.5 0 1 0 5 0z"/>
                        </svg>
                    </div>
                </div>

                <div class="metric-card__value">
                    <span class="metric-card__num" id="val-suhu">—</span>
                    <span class="metric-card__unit">°C</span>
                </div>

                <div class="metric-card__footer">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                        <polyline points="17 6 23 6 23 12"/>
                    </svg>
                    Sensor DHT22 · Ruang Penyimpanan
                </div>
            </div>

            {{-- Card Kelembapan --}}
            <div class="metric-card metric-card--hum fade-in" role="region" aria-label="Kelembapan" style="animation-delay:.06s">
                <div class="metric-card__header">
                    <span class="metric-card__label">Kelembapan</span>
                    <div class="metric-card__icon-wrap">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
                             stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>
                        </svg>
                    </div>
                </div>

                <div class="metric-card__value">
                    <span class="metric-card__num" id="val-kelembapan">—</span>
                    <span class="metric-card__unit">%</span>
                </div>

                <div class="metric-card__footer">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                        <polyline points="17 6 23 6 23 12"/>
                    </svg>
                    Kelembapan Relatif (RH)
                </div>
            </div>

            {{-- Card Status --}}
            <div class="metric-card metric-card--status fade-in" role="region" aria-label="Status Kondisi" style="animation-delay:.12s">
                <div class="metric-card__header">
                    <span class="metric-card__label">Status Kondisi</span>
                    <div class="metric-card__icon-wrap">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
                             stroke="#7c3aed" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                    </div>
                </div>

                <div class="status-value-wrap">
                    {{-- Gauge arcs for temp + humidity --}}
                    <div class="gauge-wrap">
                        <div style="flex:1; min-width:160px;">
                            <div class="status-pill" id="val-status">—</div>
                            <p class="status-desc" id="val-status-desc" style="margin-top:8px;">
                                Menunggu data sensor…
                            </p>
                        </div>

                        <div style="display:flex; gap:12px; flex-shrink:0;">
                            {{-- Mini gauge Suhu --}}
                            <div style="text-align:center;">
                                <svg class="gauge-svg" viewBox="0 0 80 80" width="72" height="72">
                                    <path class="gauge-track"
                                          d="M12,64 A34,34 0 1,1 68,64"
                                          stroke-linecap="round"/>
                                    <path class="gauge-fill" id="gauge-temp"
                                          d="M12,64 A34,34 0 1,1 68,64"
                                          stroke="#ef4444"
                                          stroke-dasharray="110 110"
                                          stroke-dashoffset="110"/>
                                    <text x="40" y="46" class="gauge-label" style="font-size:14px;font-weight:800;fill:#111827;" id="gauge-temp-label">—</text>
                                    <text x="40" y="58" class="gauge-label" style="fill:#6b7280;">°C</text>
                                </svg>
                                <div style="font-size:.65rem;color:#6b7280;font-weight:600;margin-top:2px;">SUHU</div>
                            </div>
                            {{-- Mini gauge Kelembapan --}}
                            <div style="text-align:center;">
                                <svg class="gauge-svg" viewBox="0 0 80 80" width="72" height="72">
                                    <path class="gauge-track"
                                          d="M12,64 A34,34 0 1,1 68,64"
                                          stroke-linecap="round"/>
                                    <path class="gauge-fill" id="gauge-hum"
                                          d="M12,64 A34,34 0 1,1 68,64"
                                          stroke="#3b82f6"
                                          stroke-dasharray="110 110"
                                          stroke-dashoffset="110"/>
                                    <text x="40" y="46" class="gauge-label" style="font-size:14px;font-weight:800;fill:#111827;" id="gauge-hum-label">—</text>
                                    <text x="40" y="58" class="gauge-label" style="fill:#6b7280;">%</text>
                                </svg>
                                <div style="font-size:.65rem;color:#6b7280;font-weight:600;margin-top:2px;">RH</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="metric-card__footer">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    Berdasarkan batas suhu &amp; kelembapan yang dikonfigurasi
                </div>
            </div>

        </div>{{-- .cards-grid --}}

        {{-- ==================================================
             ROW 2 — CHART
             ================================================== --}}
        <div class="section-card fade-in" role="region" aria-label="Grafik Riwayat Sensor" style="animation-delay:.18s">
            <div class="section-card__header">
                <h2 class="section-card__title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                    </svg>
                    Riwayat Suhu &amp; Kelembapan
                </h2>
                <div class="section-card__meta">
                    <span class="badge badge--temp">Suhu °C</span>
                    <span class="badge badge--hum">Kelembapan %</span>
                    <span class="badge">50 data terakhir</span>
                </div>
            </div>

            <div class="chart-body">
                <div class="chart-container">
                    <canvas id="sensor-chart" aria-label="Grafik riwayat suhu dan kelembapan"></canvas>
                </div>
            </div>
        </div>

        {{-- ==================================================
             ROW 3 — TABLE
             ================================================== --}}
        <div class="section-card fade-in" role="region" aria-label="Tabel Riwayat Data" style="animation-delay:.24s">
            <div class="section-card__header" style="padding-bottom:16px;">
                <h2 class="section-card__title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                        <line x1="3" y1="9" x2="21" y2="9"/>
                        <line x1="3" y1="15" x2="21" y2="15"/>
                        <line x1="9" y1="3" x2="9" y2="21"/>
                    </svg>
                    Riwayat Data Sensor
                </h2>
                <span class="badge">Terbaru di atas</span>
            </div>

            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th class="r">Suhu</th>
                            <th class="r">Kelembapan</th>
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

    </div>{{-- .page-content --}}
</main>

{{-- Dashboard JS — ESM module --}}
<script type="module" src="{{ asset('js/dashboard.js') }}"></script>

{{-- Real-time clock in topbar --}}
<script>
    (function () {
        const el = document.getElementById('last-update');
        function tick() {
            const now = new Date();
            const pad = n => String(n).padStart(2, '0');
            // Only show clock if no Firebase data yet
            if (el.dataset.hasData !== '1') {
                // do nothing, firebase will set it
            }
        }
    })();
</script>

</body>
</html>
