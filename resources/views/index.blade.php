<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Monitoring Gudang Gula</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600&family=Barlow+Condensed:wght@500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --ink: #14252e;
            --ink-soft: #4d626b;
            --paper: #f3f6f5;
            --panel: #ffffff;
            --line: #d8e0de;
            --ok: #1f8a5b;
            --ok-bg: #e6f3ec;
            --warn: #c98200;
            --warn-bg: #fcf1d6;
            --crit: #c8372d;
            --crit-bg: #fae4e1;
            --off: #8a9793;
            --off-bg: #eceeed;
            --air: #1e6fa8;
            --radius: 6px;
            --font-body: "Barlow", system-ui, sans-serif;
            --font-num: "Barlow Condensed", "Barlow", system-ui, sans-serif;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: var(--paper);
            color: var(--ink);
            font-family: var(--font-body);
            font-size: 15px;
            line-height: 1.45;
        }

        button, input { font: inherit; color: inherit; }
        :focus-visible { outline: 3px solid var(--air); outline-offset: 2px; }

        .wrap { max-width: 1280px; margin: 0 auto; padding: 0 20px; }

        /* ---------- Header ---------- */
        .topbar { background: var(--ink); color: #fff; }
        .topbar .wrap {
            display: flex; align-items: center; justify-content: space-between;
            gap: 16px; padding-top: 14px; padding-bottom: 14px; flex-wrap: wrap;
        }
        .brand h1 {
            margin: 0; font-family: var(--font-num); font-weight: 700;
            font-size: 26px; letter-spacing: .01em;
        }
        .brand p { margin: 0; font-size: 13px; color: #a9bcc4; }
        .top-actions { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
        .clock { font-family: var(--font-num); font-size: 22px; font-weight: 600; text-align: right; line-height: 1.1; }
        .clock small { display: block; font-family: var(--font-body); font-size: 12px; font-weight: 400; color: #a9bcc4; }
        .pill {
            display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px;
            border-radius: 999px; background: rgba(255,255,255,.1); font-size: 13px;
        }
        .dot { width: 9px; height: 9px; border-radius: 50%; background: var(--ok); }
        .dot.bad { background: var(--crit); }
        .btn-ghost {
            background: transparent; border: 1px solid rgba(255,255,255,.35); color: #fff;
            padding: 6px 12px; border-radius: var(--radius); cursor: pointer; font-size: 13px;
            transition: all 0.2s ease;
        }
        .btn-ghost[aria-pressed="true"] { background: var(--crit); border-color: var(--crit); color: #fff; font-weight: 600; animation: blink 1s infinite alternate; }

        @keyframes blink { from { opacity: 0.7; } to { opacity: 1; } }

        /* ---------- Ringkasan ---------- */
        .summary {
            display: grid; grid-template-columns: repeat(4, 1fr);
            background: var(--panel); border: 1px solid var(--line); border-radius: var(--radius);
            margin: 20px 0;
        }
        .summary > div { padding: 14px 20px; border-right: 1px solid var(--line); }
        .summary > div:last-child { border-right: 0; }
        .summary .label { font-size: 13px; color: var(--ink-soft); }
        .summary .value { font-family: var(--font-num); font-size: 38px; font-weight: 600; line-height: 1.1; }
        .summary .value small { font-size: 18px; font-weight: 500; color: var(--ink-soft); margin-left: 3px; }
        .summary .value.is-crit { color: var(--crit); }

        /* ---------- Layout utama ---------- */
        .grid { display: grid; grid-template-columns: minmax(0, 1.15fr) minmax(0, 1fr); gap: 20px; align-items: start; }
        .panel { background: var(--panel); border: 1px solid var(--line); border-radius: var(--radius); }
        .panel-head {
            display: flex; justify-content: space-between; align-items: baseline;
            gap: 12px; padding: 14px 18px; border-bottom: 1px solid var(--line); flex-wrap: wrap;
        }
        .panel-head h2 { margin: 0; font-family: var(--font-num); font-size: 21px; font-weight: 600; }
        .panel-head span { font-size: 13px; color: var(--ink-soft); }
        .panel-body { padding: 18px; }
        .grid > * { min-width: 0; }
        .stack { display: grid; gap: 20px; grid-template-columns: minmax(0, 1fr); min-width: 0; }
        .panel { min-width: 0; overflow: hidden; }

        /* ---------- Denah gudang ---------- */
        .map-legend { display: flex; gap: 16px; font-size: 13px; color: var(--ink-soft); flex-wrap: wrap; }
        .map-legend i { display: inline-block; width: 11px; height: 11px; border-radius: 2px; margin-right: 6px; vertical-align: -1px; }
        #map { width: 100%; height: auto; display: block; min-height: 250px; }
        #map .room { cursor: pointer; }
        #map .room rect.body { stroke-width: 2; transition: fill .3s, stroke .3s; }
        #map .room.ok rect.body { fill: var(--ok-bg); stroke: var(--ok); }
        #map .room.warn rect.body { fill: var(--warn-bg); stroke: var(--warn); }
        #map .room.crit rect.body { fill: var(--crit-bg); stroke: var(--crit); }
        #map .room.off rect.body { fill: var(--off-bg); stroke: var(--off); stroke-dasharray: 5 4; }
        #map .room.selected rect.body { stroke-width: 5; }
        #map .room:focus-visible rect.body { stroke: var(--air); stroke-width: 5; }
        #map text { font-family: var(--font-num); fill: var(--ink); }
        #map .t-name { font-size: 19px; font-weight: 700; }
        #map .t-temp { font-size: 30px; font-weight: 600; }
        #map .t-rh { font-size: 30px; font-weight: 600; }
        #map .t-unit { font-size: 15px; font-weight: 500; fill: var(--ink-soft); }
        #map .t-sub { font-family: var(--font-body); font-size: 12px; fill: var(--ink-soft); }
        #map .fan { transform-box: fill-box; transform-origin: center; }
        #map .fan.on { animation: spin 0.9s linear infinite; }
        #map .fan path { fill: var(--off); }
        #map .fan.on path { fill: var(--air); }
        .floor-lane { fill: #e5eae9; }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ---------- Detail zona ---------- */
        .readouts { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        .readout .label { font-size: 13px; color: var(--ink-soft); }
        .readout .big { font-family: var(--font-num); font-size: 56px; font-weight: 600; line-height: 1; }
        .readout .big small { font-size: 24px; color: var(--ink-soft); margin-left: 4px; }
        .bar { position: relative; height: 10px; border-radius: 5px; margin-top: 10px; background: #e0e0e0; }
        .bar .marker {
            position: absolute; top: -5px; width: 4px; height: 20px; background: var(--ink);
            border-radius: 2px; transform: translateX(-50%); transition: left .5s; left: 50%;
        }
        .bar-scale { display: flex; justify-content: space-between; font-size: 11px; color: var(--ink-soft); margin-top: 3px; }

        .facts { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0; margin-top: 20px; border: 1px solid var(--line); border-radius: var(--radius); }
        .facts > div { padding: 10px 12px; border-right: 1px solid var(--line); }
        .facts > div:last-child { border-right: 0; }
        .facts .label { font-size: 12px; color: var(--ink-soft); }
        .facts .v { font-family: var(--font-num); font-size: 22px; font-weight: 600; }

        .advice { margin-top: 14px; padding: 10px 12px; border-radius: var(--radius); font-size: 14px; }
        .advice.ok { background: var(--ok-bg); }
        .advice.warn { background: var(--warn-bg); }
        .advice.crit { background: var(--crit-bg); }
        .advice.off { background: var(--off-bg); }

        /* ---------- Kontrol blower ---------- */
        .control { display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
        .switch {
            position: relative; width: 116px; height: 52px; border-radius: 26px; border: 0;
            background: var(--off); cursor: pointer; transition: background .2s; flex-shrink: 0;
        }
        .switch::after {
            content: ""; position: absolute; top: 5px; left: 5px; width: 42px; height: 42px;
            border-radius: 50%; background: #fff; transition: transform .2s;
        }
        .switch span {
            position: absolute; top: 50%; transform: translateY(-50%); right: 14px;
            font-family: var(--font-num); font-weight: 700; font-size: 18px; color: #fff;
        }
        .switch[aria-checked="true"] { background: var(--air); }
        .switch[aria-checked="true"]::after { transform: translateX(64px); }
        .switch[aria-checked="true"] span { right: auto; left: 16px; }
        .switch:disabled { opacity: .5; cursor: not-allowed; }

        .segmented { display: inline-flex; border: 1px solid var(--line); border-radius: var(--radius); overflow: hidden; }
        .segmented button { border: 0; background: #fff; padding: 9px 18px; cursor: pointer; }
        .segmented button + button { border-left: 1px solid var(--line); }
        .segmented button[aria-pressed="true"] { background: var(--ink); color: #fff; }

        .rule { font-size: 13px; color: var(--ink-soft); margin: 12px 0 0; }

        /* ---------- Batas alarm ---------- */
        .limits { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
        .limits label { display: block; font-size: 12px; color: var(--ink-soft); margin-bottom: 4px; }
        .limits input {
            width: 100%; padding: 8px 10px; border: 1px solid var(--line); border-radius: var(--radius);
            font-family: var(--font-num); font-size: 20px; background: #fff;
        }
        .btn {
            margin-top: 14px; padding: 9px 18px; background: var(--ink); color: #fff; border: 0;
            border-radius: var(--radius); cursor: pointer; font-weight: 500;
        }
        .btn.secondary { background: #fff; color: var(--ink); border: 1px solid var(--line); }

        /* ---------- Tabel & alarm ---------- */
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { text-align: left; padding: 10px 14px; border-bottom: 1px solid var(--line); white-space: nowrap; }
        th { font-weight: 500; color: var(--ink-soft); font-size: 13px; }
        tbody tr { cursor: pointer; }
        tbody tr:hover { background: var(--paper); }
        tbody tr.selected { background: #e9f1f6; }
        td.num { font-family: var(--font-num); font-size: 18px; font-weight: 600; }
        .badge { display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: 12px; font-weight: 500; }
        .badge.ok { background: var(--ok-bg); color: var(--ok); }
        .badge.warn { background: var(--warn-bg); color: #8a5a00; }
        .badge.crit { background: var(--crit-bg); color: var(--crit); }
        .badge.off { background: var(--off-bg); color: var(--ink-soft); }
        .badge.on { background: #dcebf6; color: var(--air); }

        .chart-box { position: relative; height: 280px; }

        .log { list-style: none; margin: 0; padding: 0; max-height: 300px; overflow-y: auto; }
        .log li { display: grid; grid-template-columns: 62px 10px 1fr; gap: 10px; align-items: baseline; padding: 9px 18px; border-bottom: 1px solid var(--line); font-size: 14px; }
        .log time { font-family: var(--font-num); font-size: 16px; color: var(--ink-soft); }
        .log .lv { width: 10px; height: 10px; border-radius: 50%; background: var(--off); align-self: center; }
        .log .lv.crit { background: var(--crit); }
        .log .lv.warn { background: var(--warn); }
        .log .lv.ok { background: var(--ok); }
        .log .lv.info { background: var(--air); }
        .empty { padding: 24px 18px; color: var(--ink-soft); }

        footer { padding: 24px 0 40px; font-size: 13px; color: var(--ink-soft); }

        .toast {
            position: fixed; left: 50%; bottom: 24px; transform: translate(-50%, 20px);
            background: var(--ink); color: #fff; padding: 10px 18px; border-radius: var(--radius);
            opacity: 0; pointer-events: none; transition: .25s; z-index: 10;
        }
        .toast.show { opacity: 1; transform: translate(-50%, 0); }

        @media (max-width: 980px) {
            .grid { grid-template-columns: 1fr; }
            .summary { grid-template-columns: 1fr 1fr; }
            .summary > div:nth-child(2) { border-right: 0; }
            .summary > div:nth-child(-n+2) { border-bottom: 1px solid var(--line); }
        }
        @media (max-width: 560px) {
            .readouts { grid-template-columns: 1fr; }
            .limits { grid-template-columns: 1fr 1fr; }
            .facts { grid-template-columns: 1fr; }
            .facts > div { border-right: 0; border-bottom: 1px solid var(--line); }
            .facts > div:last-child { border-bottom: 0; }
            .clock { text-align: left; }
        }
        @media (prefers-reduced-motion: reduce) {
            #map .fan.on { animation-duration: 3s; }
            * { transition: none !important; }
        }
    </style>
</head>
<body>

<header class="topbar">
    <div class="wrap">
        <div class="brand">
            <h1>Monitoring Gudang Gula</h1>
            <p>Suhu, kelembaban, dan blower penyimpanan</p>
        </div>
        <div class="top-actions">
            <span class="pill" title="Status koneksi sensor Firebase"><i class="dot" id="netDot"></i><span id="netText">Menghubungkan…</span></span>
            <span class="pill" id="demoBadge">Firebase Realtime</span>
            <button class="btn-ghost" id="soundBtn" aria-pressed="false">Suara sirine web: mati</button>
            <div class="clock"><span id="clockTime">--:--:--</span><small id="clockDate">&nbsp;</small></div>
        </div>
    </div>
</header>

<main class="wrap">

    <section class="summary" aria-label="Ringkasan seluruh gudang">
        <div><div class="label">Rata-rata suhu</div><div class="value"><span id="sumTemp">--</span><small>°C</small></div></div>
        <div><div class="label">Rata-rata kelembaban</div><div class="value"><span id="sumRh">--</span><small>%</small></div></div>
        <div><div class="label">Blower menyala</div><div class="value"><span id="sumBlower">0</span><small id="sumBlowerOf">/ 2</small></div></div>
        <div><div class="label">Gudang perlu perhatian</div><div class="value" id="sumAlarmWrap"><span id="sumAlarm">0</span><small id="sumAlarmOf">/ 2</small></div></div>
    </section>

    <div class="grid">
        <!-- Kolom kiri -->
        <div class="stack">
            <section class="panel">
                <div class="panel-head">
                    <h2>Denah gudang</h2>
                    <div class="map-legend">
                        <span><i style="background:var(--ok)"></i>Aman</span>
                        <span><i style="background:var(--warn)"></i>Waspada</span>
                        <span><i style="background:var(--crit)"></i>Bahaya</span>
                        <span><i style="background:var(--off)"></i>Sensor mati</span>
                    </div>
                </div>
                <div class="panel-body">
                    <svg id="map" viewBox="0 0 780 300" role="group" aria-label="Denah dua gudang gula"></svg>
                    <p class="rule" style="margin-top:8px">Klik gudang untuk melihat detail dan mengatur blower. Baling-baling berputar saat blower menyala.</p>
                </div>
            </section>

            <section class="panel">
                <div class="panel-head">
                    <h2>Tren <span id="chartZone" style="font-family:var(--font-num);font-size:21px;color:var(--ink);font-weight:600"></span></h2>
                    <button class="btn secondary" id="csvBtn" style="margin:0;padding:6px 14px">Unduh CSV</button>
                </div>
                <div class="panel-body">
                    <div class="chart-box"><canvas id="chart" aria-label="Grafik tren suhu dan kelembaban" role="img"></canvas></div>
                </div>
            </section>

            <section class="panel">
                <div class="panel-head"><h2>Semua gudang</h2><span>Klik baris untuk memilih</span></div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Gudang</th><th>Suhu</th><th>Kelembaban</th><th>Titik embun</th><th>Stok</th><th>Blower</th><th>Status</th><th>Data terakhir</th></tr>
                        </thead>
                        <tbody id="tableBody"></tbody>
                    </table>
                </div>
            </section>
        </div>

        <!-- Kolom kanan -->
        <div class="stack">
            <section class="panel">
                <div class="panel-head">
                    <h2 id="dName">Gudang A</h2>
                    <span id="dProduct">Gula kristal putih (GKP)</span>
                </div>
                <div class="panel-body">
                    <div class="readouts">
                        <div class="readout">
                            <div class="label">Suhu</div>
                            <div class="big"><span id="dTemp">--</span><small>°C</small></div>
                            <div class="bar" id="tBar"><div class="marker" id="tMarker"></div></div>
                            <div class="bar-scale"><span>20</span><span>30</span><span>40</span></div>
                        </div>
                        <div class="readout">
                            <div class="label">Kelembaban relatif</div>
                            <div class="big"><span id="dRh">--</span><small>%</small></div>
                            <div class="bar" id="hBar"><div class="marker" id="hMarker"></div></div>
                            <div class="bar-scale"><span>40</span><span>70</span><span>95</span></div>
                        </div>
                    </div>

                    <div class="facts">
                        <div><div class="label">Titik embun</div><div class="v"><span id="dDew">--</span> °C</div></div>
                        <div><div class="label">Selisih suhu &amp; titik embun</div><div class="v"><span id="dMargin">--</span> °C</div></div>
                        <div><div class="label">Terisi</div><div class="v"><span id="dStock">--</span> ton</div></div>
                    </div>

                    <div class="advice" id="dAdvice">Memuat data sensor…</div>
                </div>
            </section>

            <section class="panel">
                <div class="panel-head"><h2>Blower</h2><span id="bState">--</span></div>
                <div class="panel-body">
                    <div class="control">
                        <button class="switch" id="blowerSwitch" role="switch" aria-checked="false" aria-label="Blower"><span id="switchText">MATI</span></button>
                        <div>
                            <div class="label" style="font-size:13px;color:var(--ink-soft);margin-bottom:6px">Mode kendali</div>
                            <div class="segmented" role="group" aria-label="Mode kendali blower">
                                <button id="modeAuto" aria-pressed="true">Otomatis</button>
                                <button id="modeManual" aria-pressed="false">Manual</button>
                            </div>
                        </div>
                    </div>
                    <div class="facts">
                        <div><div class="label">Nyala hari ini</div><div class="v" id="bRuntime">0 j 00 m</div></div>
                        <div><div class="label">Terakhir berubah</div><div class="v" id="bLast">--:--</div></div>
                        <div><div class="label">Siklus hari ini</div><div class="v" id="bCycles">0 kali</div></div>
                    </div>
                    <p class="rule" id="ruleText"></p>
                </div>
            </section>

            <section class="panel">
                <div class="panel-head"><h2>Batas alarm</h2><span id="alarmDeviceIndicator">Untuk gudang terpilih</span></div>
                <div class="panel-body">
                    <div class="limits">
                        <div><label for="inTMax">Suhu maks. (°C)</label><input id="inTMax" type="number" step="0.5" value="30"></div>
                        <div><label for="inRhOn">Blower nyala (% RH)</label><input id="inRhOn" type="number" step="1" value="70"></div>
                        <div><label for="inRhOff">Blower mati (% RH)</label><input id="inRhOff" type="number" step="1" value="65"></div>
                        <div><label for="inRhMax">Alarm bahaya (% RH)</label><input id="inRhMax" type="number" step="1" value="75"></div>
                    </div>
                    <button class="btn" id="saveLimits">Simpan batas</button>
                </div>
            </section>

            <section class="panel">
                <div class="panel-head"><h2>Riwayat kejadian</h2><button class="btn secondary" id="clearLog" style="margin:0;padding:6px 14px">Bersihkan</button></div>
                <ul class="log" id="log"></ul>
            </section>
        </div>
    </div>
</main>

<footer class="wrap">
    Acuan awal: gula kristal paling aman disimpan di bawah 30 °C dan kelembaban di bawah 70%. Atur batas sesuai SOP pabrik Anda.
</footer>

<div class="toast" id="toast" role="status" aria-live="polite"></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

@verbatim
<script>
(() => {
    // Pengaturan awal default (Jika di Firebase kosong)
    const DEFAULT_LIMITS = { tMax: 30, rhOn: 70, rhOff: 65, rhMax: 75 };
    const STATUS_LABEL = { ok: 'Aman', warn: 'Waspada', crit: 'Bahaya', off: 'Sensor mati' };

    const FIRE_CONFIG = {
        rateThresholdC: 2.0,
        rateTimeWindowSec: 60,
        extremeTempC: 45.0
    };

    /* ---------- Konfigurasi Firebase ---------- */
    const FIREBASE = {
        dbUrl: 'https://smartteam-f5e2f-default-rtdb.asia-southeast1.firebasedatabase.app',
        pollMs: 2000
    };

    async function fbGet(path) {
        try {
            const res = await fetch(`${FIREBASE.dbUrl}/${path}.json`);
            if (!res.ok) return null;
            return await res.json();
        } catch (e) {
            return null;
        }
    }

    const $ = (id) => document.getElementById(id);
    const pad = (n) => String(n).padStart(2, '0');
    const hhmm = (ts) => { const d = new Date(ts); return isNaN(d.getTime()) ? '--:--' : pad(d.getHours()) + ':' + pad(d.getMinutes()); };
    const clamp = (v, a, b) => Math.min(b, Math.max(a, v));
    const fmt = (v) => (v == null || isNaN(v)) ? '--' : String(Number(v));

    const state = {
        zones: [
            {
                id: 'A', name: 'Gudang A', sensorKeys: ['sensor_1', 'sensor1'], ctrlKey: 'device_01', product: 'Gula kristal putih (GKP)',
                stock: 820, capacity: 1200, temp: null, rh: null,
                blower: false, alarm: false, mode: 'auto', online: false,
                limits: { ...DEFAULT_LIMITS },
                runtimeMin: 0, cycles: 0, lastSwitch: Date.now(),
                updatedAt: Date.now(), history: [], status: 'off', prevStatus: 'off',
                localWriteAt: 0, alarmLocalWriteAt: 0, fireAlertTriggered: false
            },
            {
                id: 'B', name: 'Gudang B', sensorKeys: ['sensor_2', 'sensor2'], ctrlKey: 'device_02', product: 'Gula kristal putih (GKP)',
                stock: 1010, capacity: 1200, temp: null, rh: null,
                blower: false, alarm: false, mode: 'auto', online: false,
                limits: { ...DEFAULT_LIMITS },
                runtimeMin: 0, cycles: 0, lastSwitch: Date.now(),
                updatedAt: Date.now(), history: [], status: 'off', prevStatus: 'off',
                localWriteAt: 0, alarmLocalWriteAt: 0, fireAlertTriggered: false
            }
        ],
        selected: 'A',
        alarms: [],
        chart: null,
        sound: false // Master switch buzzer web
    };

    function tickClock() {
        try {
            const d = new Date();
            const timeEl = $('clockTime');
            const dateEl = $('clockDate');
            if (timeEl) timeEl.textContent = pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
            if (dateEl) dateEl.textContent = d.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        } catch (e) {}
    }

    function dewPoint(t, rh) {
        if (t == null || rh == null || isNaN(t) || isNaN(rh)) return NaN;
        const a = 17.62, b = 243.12;
        const g = Math.log(rh / 100) + (a * t) / (b + t);
        return (b * g) / (a - g);
    }

    function computeStatus(z) {
        if (!z.online || z.temp == null || z.rh == null || isNaN(z.temp) || isNaN(z.rh)) return 'off';
        const L = z.limits;
        const margin = z.temp - dewPoint(z.temp, z.rh);
        
        // Kondisi Bahaya: Api terdeteksi ATAU Suhu > Maks ATAU RH > Maks
        if (z.fireAlertTriggered || z.rh >= L.rhMax || z.temp >= L.tMax) return 'crit';
        // Kondisi Waspada: Suhu hampir menyentuh batas (selisih 1 derajat) atau margin embun tipis
        if (z.rh >= L.rhOn || z.temp >= (L.tMax - 1) || margin < 3) return 'warn';
        
        return 'ok';
    }

    function adviceFor(z) {
        const L = z.limits, s = z.status;
        const margin = z.temp - dewPoint(z.temp, z.rh);
        if (z.fireAlertTriggered) return '🔥 PERINGATAN DARURAT: Terdeteksi lonjakan suhu drastis indikasi kebakaran!';
        if (s === 'off') return 'Sensor tidak mengirim data. Menunggu kiriman data dari modul WiFi sensor.';
        if (s === 'crit') {
            if (z.rh >= L.rhMax || z.temp >= L.tMax) return z.blower
                ? `Kondisi kritis (Suhu: ${fmt(z.temp)}°C, RH: ${fmt(z.rh)}%). Blower aktif menstabilkan ruangan.`
                : `Batas aman terlewati (Suhu ≥ ${L.tMax}°C atau RH ≥ ${L.rhMax}%). Blower akan diaktifkan otomatis.`;
            return 'Suhu gudang melebihi batas toleransi aman. Periksa ventilasi udara.';
        }
        if (s === 'warn') {
            if (margin < 3) return 'Suhu mendekati titik embun (selisih ' + (isNaN(margin) ? '--' : margin.toFixed(1)) + ' °C). Waspada kondensasi air pada karung.';
            if (z.rh >= L.rhOn || z.temp >= (L.tMax - 1)) return 'Parameter mulai naik mendekati batas. ' + (z.blower ? 'Blower sedang beroperasi.' : 'Blower siap menyala otomatis.');
            return 'Suhu mendekati batas maksimum. Harap pantau berkala.';
        }
        return 'Kondisi penyimpanan aman. Suhu dan kelembaban dalam batas normal.';
    }

    /* ---------- API REQUEST KE FIREBASE ---------- */
    async function sendBlowerToFirebase(z, stateBoolean) {
        z.localWriteAt = Date.now();
        z.blower = stateBoolean;
        z.lastSwitch = Date.now();
        if (stateBoolean) z.cycles += 1;

        try {
            await fetch(`${FIREBASE.dbUrl}/kontrol/${z.ctrlKey}/blower.json`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(Boolean(stateBoolean))
            });
        } catch (e) {}
    }

    async function triggerAlarmAndBlower(z, alarmState, isAutoFire = false) {
        z.alarmLocalWriteAt = Date.now();
        z.alarm = alarmState;

        // Jika alarm nyala otomatis/manual, paksa blower nyala juga demi safety.
        if (alarmState) {
            z.localWriteAt = Date.now();
            z.blower = true;
            z.lastSwitch = Date.now();
            z.cycles += 1;
        }

        try {
            await fetch(`${FIREBASE.dbUrl}/kontrol/${z.ctrlKey}/alarm.json`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(Boolean(alarmState))
            });

            if (alarmState) {
                await fetch(`${FIREBASE.dbUrl}/kontrol/${z.ctrlKey}/blower.json`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(true)
                });
            } else {
                z.fireAlertTriggered = false; // Reset status api saat alarm di matikan
            }
        } catch (e) {}
    }

    /* ---------- LOGIKA DETEKSI KEBAKARAN ---------- */
    function checkFireAlgorithm(z, currentTemp, currentTs) {
        if (!z.history || z.history.length < 2) return;

        // Suhu menembus batas mutlak kebakaran (Extreme)
        if (currentTemp >= FIRE_CONFIG.extremeTempC) {
            if (!z.alarm) {
                z.fireAlertTriggered = true;
                triggerAlarmAndBlower(z, true, true);
                addAlarm('crit', `🔥 DARURAT KEBAKARAN: Suhu ${z.name} menembus ${currentTemp} °C!`);
            }
            return;
        }

        // Kenaikan suhu mendadak (Rate of Rise)
        const windowMs = FIRE_CONFIG.rateTimeWindowSec * 1000;
        const prevPoints = z.history.filter(p => (currentTs - p.t) <= windowMs && (currentTs - p.t) >= 10000);

        if (prevPoints.length > 0) {
            const oldestPoint = prevPoints[0];
            const tempDiff = currentTemp - oldestPoint.temp;
            const timeDiffSec = Math.max(1, Math.round((currentTs - oldestPoint.t) / 1000));

            if (tempDiff >= FIRE_CONFIG.rateThresholdC) {
                if (!z.alarm) {
                    z.fireAlertTriggered = true;
                    triggerAlarmAndBlower(z, true, true);
                    addAlarm('crit', `🔥 PERINGATAN API ${z.name}: Suhu melonjak +${tempDiff.toFixed(1)} °C dalam ${timeDiffSec} detik!`);
                }
            }
        }
    }

    /* ---------- LOGIKA OTOMATIS: EVALUASI BLOWER & ALARM DARI BATAS (LIMITS) ---------- */
    function applyAutoRule(z, quiet) {
        const L = z.limits;
        if (z.temp == null || z.rh == null || isNaN(z.temp) || isNaN(z.rh)) return;

        // 1. KONTROL ALARM BAHAYA (Triggered by Temperature or Humidity)
        const isCritical = (z.temp >= L.tMax) || (z.rh >= L.rhMax);
        
        if (isCritical && !z.alarm) {
            // Suhu atau RH melebihi batas -> Nyalakan alarm dan blower otomatis
            triggerAlarmAndBlower(z, true, false);
            if (!quiet) addAlarm('crit', `🚨 BAHAYA! Kondisi ${z.name} kritis. Alarm otomatis diaktifkan.`);
        } 
        else if (!isCritical && z.alarm && !z.fireAlertTriggered) {
            // Jika keadaan sudah berada di bawah angka batas, matikan Alarm otomatis
            triggerAlarmAndBlower(z, false, false);
            if (!quiet) addAlarm('ok', `✅ ${z.name} kembali aman di bawah batas kritis. Alarm dimatikan.`);
        }

        // 2. KONTROL BLOWER (Normal operations / Pendinginan)
        const shouldTurnOn = (z.temp >= L.tMax) || (z.rh >= L.rhOn);
        const shouldTurnOff = (z.temp < (L.tMax - 1)) && (z.rh <= L.rhOff);

        if (!z.blower && shouldTurnOn) {
            sendBlowerToFirebase(z, true);
            const triggerReason = z.temp >= L.tMax ? `suhu ${z.temp}°C` : `RH ${z.rh}%`;
            if (!quiet) addAlarm('info', `${z.name}: blower dinyalakan otomatis (${triggerReason})`);
        } 
        // Blower hanya boleh mati otomatis jika Alarm Bahaya sedang MATI
        else if (z.blower && shouldTurnOff && !z.alarm && !z.fireAlertTriggered) {
            sendBlowerToFirebase(z, false);
            if (!quiet) addAlarm('info', `${z.name}: blower dimatikan otomatis (kondisi normal)`);
        }
    }

    /* ---------- EVENT LOG DAN AUDIO NOTIFIKASI WEB ---------- */
    function addAlarm(level, msg) {
        state.alarms.unshift({ t: Date.now(), level, msg });
        if (state.alarms.length > 40) state.alarms.pop();
        renderLog();
        
        // Peringatan audio web hanya dibunyikan jika switch Suara Web = "nyala"
        if (level === 'crit' && state.sound) {
            beepFire();
        }
    }

    function renderLog() {
        const ul = $('log');
        if (!ul) return;
        if (!state.alarms.length) { ul.innerHTML = '<li class="empty" style="display:block">Belum ada kejadian tercatat.</li>'; return; }
        ul.innerHTML = state.alarms.map(a =>
            '<li><time>' + hhmm(a.t) + '</time><span class="lv ' + a.level + '"></span><span>' + a.msg + '</span></li>'
        ).join('');
    }

    function beep() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const o = ctx.createOscillator(), g = ctx.createGain();
            o.type = 'square'; o.frequency.value = 880;
            g.gain.value = 0.05; o.connect(g); g.connect(ctx.destination);
            o.start(); o.stop(ctx.currentTime + 0.25);
        } catch (e) {}
    }

    function beepFire() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const o = ctx.createOscillator(), g = ctx.createGain();
            o.type = 'sawtooth';
            o.frequency.setValueAtTime(900, ctx.currentTime);
            o.frequency.exponentialRampToValueAtTime(1400, ctx.currentTime + 0.4);
            g.gain.value = 0.15;
            o.connect(g);
            g.connect(ctx.destination);
            o.start();
            o.stop(ctx.currentTime + 0.5);
        } catch (e) {}
    }

    /* ---------- DOM RENDERERS ---------- */
    const FAN_PATH = 'M0 0 C 6 -6, 8 -20, 0 -24 C -8 -20, -6 -6, 0 0 Z';
    const ROOM = { w: 367, h: 210, gap: 22, x0: 12, y0: 12 };

    function buildMap() {
        const svg = $('map');
        if (!svg) return;
        let html = '<rect class="floor-lane" x="0" y="238" width="780" height="50" rx="4"/>' +
                   '<text class="t-sub" x="390" y="268" text-anchor="middle">Jalur forklift dan pintu muat</text>';
        state.zones.forEach((z, i) => {
            const x = ROOM.x0 + i * (ROOM.w + ROOM.gap), y = ROOM.y0, cx = x + ROOM.w / 2;
            html += `
            <g class="room off" id="room-${z.id}" data-id="${z.id}" tabindex="0" role="button" aria-label="${z.name}">
                <rect class="body" x="${x}" y="${y}" width="${ROOM.w}" height="${ROOM.h}" rx="6"/>
                <text class="t-name" x="${x + 14}" y="${y + 28}">${z.name}</text>
                <text class="t-sub" x="${x + ROOM.w - 14}" y="${y + 27}" text-anchor="end" id="rs-${z.id}">Sensor mati</text>
                <g transform="translate(${cx} ${y + 78})">
                    <circle r="34" fill="#fff" fill-opacity=".7"/>
                    <g class="fan" id="fan-${z.id}">
                        <path d="${FAN_PATH}"/>
                        <path d="${FAN_PATH}" transform="rotate(120)"/>
                        <path d="${FAN_PATH}" transform="rotate(240)"/>
                    </g>
                    <circle r="4" fill="#14252e"/>
                </g>
                <text class="t-sub" x="${cx}" y="${y + 132}" text-anchor="middle" id="rb-${z.id}">Blower mati · otomatis</text>
                <text class="t-temp" x="${x + 14}" y="${y + 165}" id="rt-${z.id}">--</text>
                <text class="t-unit" x="${x + 14}" y="${y + 181}">suhu °C</text>
                <text class="t-rh" x="${x + ROOM.w - 14}" y="${y + 165}" text-anchor="end" id="rh-${z.id}">--</text>
                <text class="t-unit" x="${x + ROOM.w - 14}" y="${y + 181}" text-anchor="end">RH %</text>
                <rect x="${x + 14}" y="${y + 191}" width="${ROOM.w - 28}" height="8" rx="4" fill="#fff" fill-opacity=".8"/>
                <rect x="${x + 14}" y="${y + 191}" width="0" height="8" rx="4" fill="#14252e" id="rstock-${z.id}" data-max="${ROOM.w - 28}"/>
            </g>`;
        });
        svg.innerHTML = html;
        svg.querySelectorAll('.room').forEach(el => {
            el.addEventListener('click', () => select(el.dataset.id));
            el.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); select(el.dataset.id); } });
        });
    }

    function updateMap() {
        state.zones.forEach(z => {
            const g = $('room-' + z.id);
            if (!g) return;
            g.setAttribute('class', 'room ' + z.status + (z.id === state.selected ? ' selected' : ''));
            const rt = $('rt-' + z.id); if (rt) rt.textContent = z.online ? fmt(z.temp) : '--';
            const rh = $('rh-' + z.id); if (rh) rh.textContent = z.online ? fmt(z.rh) : '--';
            const rs = $('rs-' + z.id); if (rs) rs.textContent = z.fireAlertTriggered ? '🔥 KEBAKARAN' : STATUS_LABEL[z.status];
            const rb = $('rb-' + z.id); if (rb) rb.textContent = 'Blower ' + (z.blower ? 'menyala' : 'mati') + ' · ' + (z.mode === 'auto' ? 'otomatis' : 'manual');
            const fan = $('fan-' + z.id); if (fan) fan.setAttribute('class', 'fan' + (z.blower && z.online ? ' on' : ''));
            const bar = $('rstock-' + z.id);
            if (bar) bar.setAttribute('width', (bar.dataset.max * clamp(z.stock / z.capacity, 0, 1)).toFixed(1));
        });
    }

    function updateSummary() {
        const live = state.zones.filter(z => z.online && z.temp != null);
        const avg = (k) => live.length ? live.reduce((s, z) => s + z[k], 0) / live.length : null;
        if ($('sumTemp')) $('sumTemp').textContent = live.length ? Number(avg('temp')).toFixed(1) : '--';
        if ($('sumRh')) $('sumRh').textContent = live.length ? Number(avg('rh')).toFixed(1) : '--';
        const on = state.zones.filter(z => z.blower).length;
        if ($('sumBlower')) $('sumBlower').textContent = on;
        const bad = state.zones.filter(z => z.status === 'warn' || z.status === 'crit' || z.status === 'off' || z.fireAlertTriggered).length;
        if ($('sumAlarm')) $('sumAlarm').textContent = bad;
        if ($('sumAlarmWrap')) $('sumAlarmWrap').classList.toggle('is-crit', state.zones.some(z => z.status === 'crit' || z.fireAlertTriggered));
        const online = live.length;
        if ($('netText')) $('netText').textContent = 'Sensor aktif ' + online + '/' + state.zones.length;
        if ($('netDot')) $('netDot').classList.toggle('bad', online === 0);
    }

    function updateTable() {
        const tb = $('tableBody');
        if (!tb) return;
        tb.innerHTML = state.zones.map(z => `
            <tr data-id="${z.id}" class="${z.id === state.selected ? 'selected' : ''}">
                <td><strong>${z.name}</strong></td>
                <td class="num">${z.online ? fmt(z.temp) + ' °C' : '--'}</td>
                <td class="num">${z.online ? fmt(z.rh) + ' %' : '--'}</td>
                <td class="num">${z.online ? (isNaN(dewPoint(z.temp, z.rh)) ? '--' : dewPoint(z.temp, z.rh).toFixed(1)) + ' °C' : '--'}</td>
                <td>${fmt(z.stock)} / ${fmt(z.capacity)} ton</td>
                <td><span class="badge ${z.blower ? 'on' : 'off'}">${z.blower ? 'Menyala' : 'Mati'}</span> <small>${z.mode === 'auto' ? 'otomatis' : 'manual'}</small></td>
                <td><span class="badge ${z.fireAlertTriggered || z.alarm ? 'crit' : z.status}">${z.fireAlertTriggered ? 'BAHAYA API' : (z.alarm ? 'ALARM AKTIF' : STATUS_LABEL[z.status])}</span></td>
                <td>${z.online ? hhmm(z.updatedAt) : '--:--'}</td>
            </tr>`).join('');
        tb.querySelectorAll('tr').forEach(tr => tr.addEventListener('click', () => select(tr.dataset.id)));
    }

    function zoneBar(elId, min, max, okTo, warnTo) {
        const p = (v) => ((clamp(v, min, max) - min) / (max - min) * 100).toFixed(1);
        const el = $(elId);
        if (el) {
            el.style.background = `linear-gradient(to right,
                #9fd3b8 0%, #9fd3b8 ${p(okTo)}%,
                #f3cf7a ${p(okTo)}%, #f3cf7a ${p(warnTo)}%,
                #eba299 ${p(warnTo)}%, #eba299 100%)`;
        }
        return p;
    }

    function updateDetail() {
        const z = state.zones.find(x => x.id === state.selected);
        if (!z) return;
        const L = z.limits;
        if ($('dName'))$('dName').textContent = z.name;
        if ($('dProduct'))$('dProduct').textContent = z.product;
        if ($('dTemp'))$('dTemp').textContent = z.online ? fmt(z.temp) : '--';
        if ($('dRh'))$('dRh').textContent = z.online ? fmt(z.rh) : '--';
        if ($('alarmDeviceIndicator'))$('alarmDeviceIndicator').textContent = 'Batas untuk ' + z.name;

        const pT = zoneBar('tBar', 20, 40, L.tMax - 1, L.tMax);
        const pH = zoneBar('hBar', 40, 95, L.rhOff, L.rhOn);
        if ($('tMarker'))$('tMarker').style.left = (z.temp == null || isNaN(z.temp) ? 0 : pT(z.temp)) + '%';
        if ($('hMarker'))$('hMarker').style.left = (z.rh == null || isNaN(z.rh) ? 0 : pH(z.rh)) + '%';

        const dew = dewPoint(z.temp, z.rh);
        if ($('dDew'))$('dDew').textContent = z.online && !isNaN(dew) ? dew.toFixed(1) : '--';
        if ($('dMargin'))$('dMargin').textContent = z.online && !isNaN(dew) ? (z.temp - dew).toFixed(1) : '--';
        if ($('dStock'))$('dStock').textContent = fmt(z.stock) + ' / ' + fmt(z.capacity);

        const adv = $('dAdvice');
        if (adv) {
            adv.className = 'advice ' + (z.fireAlertTriggered || z.alarm ? 'crit' : z.status);
            adv.textContent = z.alarm ? '🚨 ALARM SEDANG BERBUNYI! Evaluasi kondisi gudang segera!' : adviceFor(z);
        }

        const sw = $('blowerSwitch');
        if (sw) {
            sw.setAttribute('aria-checked', String(z.blower));
            if ($('switchText'))$('switchText').textContent = z.blower ? 'NYALA' : 'MATI';
        }
        if ($('bState'))$('bState').textContent = z.blower ? 'Menyala' : 'Mati';
        if ($('modeAuto'))$('modeAuto').setAttribute('aria-pressed', String(z.mode === 'auto'));
        if ($('modeManual'))$('modeManual').setAttribute('aria-pressed', String(z.mode === 'manual'));
        if ($('bRuntime'))$('bRuntime').textContent = Math.floor(z.runtimeMin / 60) + ' j ' + pad(z.runtimeMin % 60) + ' m';
        if ($('bLast'))$('bLast').textContent = hhmm(z.lastSwitch);
        if ($('bCycles'))$('bCycles').textContent = z.cycles + ' kali';
        if ($('ruleText')) {$('ruleText').textContent = z.mode === 'auto'
                ? `Mode otomatis: blower menyala jika suhu ≥ ${L.tMax}°C ATAU RH ≥ ${L.rhOn}%.`
                : 'Mode manual: blower hanya diatur lewat tombol sakelar di atas.';
        }
    }

    /* Mengisi nilai di form sesuai batasan gudang yg aktif (dan mengabaikan jika sedang diketik) */
    function fillLimitInputs() {
        const z = state.zones.find(x => x.id === state.selected);
        if (!z) return;
        if ($('inTMax') && document.activeElement !== $('inTMax'))$('inTMax').value = z.limits.tMax;
        if ($('inRhOn') && document.activeElement !== $('inRhOn'))$('inRhOn').value = z.limits.rhOn;
        if ($('inRhOff') && document.activeElement !== $('inRhOff'))$('inRhOff').value = z.limits.rhOff;
        if ($('inRhMax') && document.activeElement !== $('inRhMax'))$('inRhMax').value = z.limits.rhMax;
    }

    function buildChart() {
        const chartEl = $('chart');
        if (!chartEl || typeof Chart === 'undefined') return;
        try {
            const ctx = chartEl.getContext('2d');
            state.chart = new Chart(ctx, {
                type: 'line',
                data: { labels: [], datasets: [
                    { label: 'Suhu (°C)', data: [], borderColor: '#14252e', backgroundColor: '#14252e', yAxisID: 'yT', pointRadius: 2, borderWidth: 2, tension: .3 },
                    { label: 'Kelembaban (%)', data: [], borderColor: '#1e6fa8', backgroundColor: '#1e6fa8', yAxisID: 'yH', pointRadius: 2, borderWidth: 2, tension: .3 },
                    { label: 'RH blower nyala', data: [], borderColor: '#c98200', yAxisID: 'yH', pointRadius: 0, borderWidth: 1.5, borderDash: [6, 5] },
                    { label: 'RH bahaya', data: [], borderColor: '#c8372d', yAxisID: 'yH', pointRadius: 0, borderWidth: 1.5, borderDash: [6, 5] }
                ]},
                options: {
                    responsive: true, maintainAspectRatio: false, animation: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, usePointStyle: true } } },
                    scales: {
                        x: { ticks: { maxTicksLimit: 8, maxRotation: 0 }, grid: { display: false } },
                        yT: { position: 'left', min: 20, max: 45, title: { display: true, text: '°C' } },
                        yH: { position: 'right', min: 40, max: 100, title: { display: true, text: '% RH' }, grid: { drawOnChartArea: false } }
                    }
                }
            });
        } catch (e) {}
    }

    function updateChart() {
        const z = state.zones.find(x => x.id === state.selected);
        if (!z || !state.chart) return;
        const h = z.history;
        const c = state.chart;
        c.data.labels = h.map(p => hhmm(p.t));
        c.data.datasets[0].data = h.map(p => p.temp);
        c.data.datasets[1].data = h.map(p => p.rh);
        c.data.datasets[2].data = h.map(() => z.limits.rhOn);
        c.data.datasets[3].data = h.map(() => z.limits.rhMax);
        c.update();
        if ($('chartZone'))$('chartZone').textContent = '· ' + z.name;
    }

    function renderAll() {
        state.zones.forEach(z => {
            z.prevStatus = z.status;
            z.status = computeStatus(z);
            if (z.status !== z.prevStatus) {
                if (z.status === 'crit' && !z.fireAlertTriggered) {
                    addAlarm('crit', z.name + ': BAHAYA! RH ' + fmt(z.rh) + '%, suhu ' + fmt(z.temp) + ' °C');
                } else if (z.status === 'warn' && z.prevStatus === 'ok') {
                    addAlarm('warn', z.name + ': Waspada. Batas aman akan segera terlewati.');
                }
            }
        });
        updateSummary();
        updateMap();
        updateTable();
        updateDetail();
        updateChart();
    }

    function select(id) {
        state.selected = id;
        fillLimitInputs();
        renderAll();
    }

    function toast(msg) {
        const t = $('toast');
        if (!t) return;
        t.textContent = msg; t.classList.add('show');
        clearTimeout(toast._t); toast._t = setTimeout(() => t.classList.remove('show'), 2500);
    }

    /* ---------- POLLING DATA FIREBASE ---------- */
    async function pollFirebase() {
        const latest = await fbGet('sensor/device_01/latest');

        if (latest) {
            let ts = Number(latest.timestamp || Date.now());
            if (ts < 1e11) ts = Date.now();

            state.zones.forEach(z => {
                let sData = null;
                for (const key of z.sensorKeys) {
                    if (latest[key]) {
                        sData = latest[key];
                        break;
                    }
                }
                if (!sData) sData = latest;

                if (sData && sData.suhu != null) {
                    const newTemp = Number(sData.suhu);
                    const newRh = Number(sData.kelembapan ?? sData.kelembaban);

                    z.temp = newTemp;
                    z.rh = newRh;
                    z.updatedAt = ts;
                    z.online = true;

                    const lastPoint = z.history[z.history.length - 1];
                    if (!lastPoint || lastPoint.t !== ts) {
                        z.history.push({ t: ts, temp: z.temp, rh: z.rh });
                        if (z.history.length > 96) z.history.shift();
                    }

                    checkFireAlgorithm(z, newTemp, ts);

                    if (z.mode === 'auto') {
                        applyAutoRule(z, true);
                    }
                }
            });
        } else {
            state.zones.forEach(z => { z.online = false; });
        }

        // Cek Sinkronisasi Blower, Alarm & Limits
        const ctrl01 = await fbGet('kontrol/device_01');
        const ctrl02 = await fbGet('kontrol/device_02');
        const ctrlMap = { 'device_01': ctrl01 || {}, 'device_02': ctrl02 || {} };

        state.zones.forEach(z => {
            const devCtrl = ctrlMap[z.ctrlKey];
            if (devCtrl) {
                // Jangan reset switch UI jika ada klik dalam 4 detik
                if (typeof devCtrl.blower === 'boolean' && Date.now() - (z.localWriteAt || 0) > 4000) {
                    z.blower = devCtrl.blower;
                }
                if (typeof devCtrl.alarm === 'boolean' && Date.now() - (z.alarmLocalWriteAt || 0) > 4000) {
                    z.alarm = devCtrl.alarm;
                }
                // Sinkron Batasan Form (Limits) dari database
                if (devCtrl.limits && typeof devCtrl.limits === 'object') {
                    const prevRhMax = z.limits.rhMax;
                    z.limits = Object.assign({}, z.limits, devCtrl.limits);
                    // Update field di form jika ada perubahan dan tidak sedang di ketik user
                    if (z.id === state.selected && z.limits.rhMax !== prevRhMax) {
                        fillLimitInputs();
                    }
                }
            }
        });
    }

    async function loadHistoryFromFirebase() {
        try {
            const histData = await fbGet('sensor/device_01/history');
            if (!histData) return;

            const items = Object.values(histData);
            state.zones.forEach(z => {
                const zoneHist = [];
                items.forEach(item => {
                    let sData = null;
                    for (const key of z.sensorKeys) {
                        if (item[key]) {
                            sData = item[key];
                            break;
                        }
                    }
                    if (!sData) sData = item;

                    if (sData && sData.suhu != null) {
                        let t = Number(item.timestamp || Date.now());
                        if (t < 1e11) t = Date.now();
                        zoneHist.push({
                            t: t,
                            temp: Number(sData.suhu),
                            rh: Number(sData.kelembapan ?? sData.kelembaban)
                        });
                    }
                });
                z.history = zoneHist.slice(-96);
            });
        } catch (e) {}
    }

    /* ---------- INTERAKSI UI / KLIK TOMBOL ---------- */
    if ($('blowerSwitch')) {$('blowerSwitch').addEventListener('click', () => {
            const z = state.zones.find(x => x.id === state.selected);
            if (!z) return;
            z.mode = 'manual';
            const nextState = !z.blower;
            sendBlowerToFirebase(z, nextState);
            renderAll();
        });
    }

    function setMode(mode) {
        const z = state.zones.find(x => x.id === state.selected);
        if (!z) return;
        z.mode = mode;
        if (mode === 'auto') applyAutoRule(z, false);
        toast(z.name + ': mode ' + (mode === 'auto' ? 'otomatis' : 'manual'));
        renderAll();
    }
    if ($('modeAuto'))$('modeAuto').addEventListener('click', () => setMode('auto'));
    if ($('modeManual'))$('modeManual').addEventListener('click', () => setMode('manual'));

    // SIMPAN BATAS DINAMIS KE FIREBASE & APPLY
    if ($('saveLimits')) {$('saveLimits').addEventListener('click', async () => {
            const z = state.zones.find(x => x.id === state.selected);
            if (!z) return;

            const L = {
                tMax: parseFloat($('inTMax').value),
                rhOn: parseFloat($('inRhOn').value),
                rhOff: parseFloat($('inRhOff').value),
                rhMax: parseFloat($('inRhMax').value)
            };

            if (Object.values(L).some(isNaN)) return toast('Semua batas harus berupa angka.');
            // Validasinya diubah <= agar lebih fleksibel
            if (!(L.rhOff < L.rhOn && L.rhOn <= L.rhMax)) return toast('Aturan: Blower Mati < Blower Nyala <= Alarm Bahaya.');

            z.limits = L;

            const btn = $('saveLimits');
            btn.disabled = true;
            btn.textContent = 'Menyimpan…';

            try {
                // Simpan ke node masing-masing gudang
                await fetch(`${FIREBASE.dbUrl}/kontrol/${z.ctrlKey}/limits.json`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(L)
                });
                
                toast(`Batas alarm ${z.name} berhasil disimpan`);
                addAlarm('info', `${z.name}: Aturan diubah (Suhu Maks: ${L.tMax}°C, Alarm: RH ${L.rhMax}%)`);
                
                // PENTING: Evaluasi dan terapkan batas baru saat ini juga!
                z.prevStatus = z.status;
                z.status = computeStatus(z);
                if (z.mode === 'auto') {
                    applyAutoRule(z, false);
                }

            } catch (e) {
                toast('Gagal menyimpan batas ke database');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Simpan batas';
                renderAll();
            }
        });
    }

    if ($('clearLog'))$('clearLog').addEventListener('click', () => { state.alarms = []; renderLog(); });

    if ($('soundBtn')) {$('soundBtn').addEventListener('click', (e) => {
            state.sound = !state.sound;
            e.currentTarget.setAttribute('aria-pressed', String(state.sound));
            e.currentTarget.textContent = 'Suara sirine web: ' + (state.sound ? 'nyala' : 'mati');
            if (state.sound) beep();
        });
    }

    if ($('csvBtn')) {$('csvBtn').addEventListener('click', async () => {
            const z = state.zones.find(x => x.id === state.selected);
            if (!z) return;

            const btn = $('csvBtn');
            const originalText = btn.textContent;
            btn.textContent = 'Mengunduh…';
            btn.disabled = true;

            try {
                const histData = await fbGet('sensor/device_01/history');
                let records = [];

                if (histData) {
                    Object.values(histData).forEach(item => {
                        let sData = null;
                        for (const key of z.sensorKeys) {
                            if (item[key]) {
                                sData = item[key];
                                break;
                            }
                        }
                        if (!sData) sData = item;

                        if (sData && sData.suhu != null) {
                            const tVal = Number(sData.suhu);
                            const rhVal = Number(sData.kelembapan ?? sData.kelembaban);
                            let ts = Number(item.timestamp || Date.now());
                            if (ts < 1e11) ts = Date.now();
                            const dp = dewPoint(tVal, rhVal);

                            records.push({
                                waktu: new Date(ts).toLocaleString('id-ID'),
                                gudang: z.name,
                                suhu: tVal,
                                kelembaban: rhVal,
                                titik_embun: isNaN(dp) ? '--' : dp.toFixed(2),
                                blower: (sData.blower != null ? sData.blower : (item.blower != null ? item.blower : z.blower)) ? 'MENYALA' : 'MATI'
                            });
                        }
                    });
                }

                if (!records.length && z.history.length) {
                    records = z.history.map(p => {
                        const dp = dewPoint(p.temp, p.rh);
                        return {
                            waktu: new Date(p.t).toLocaleString('id-ID'),
                            gudang: z.name,
                            suhu: p.temp,
                            kelembaban: p.rh,
                            titik_embun: isNaN(dp) ? '--' : dp.toFixed(2),
                            blower: z.blower ? 'MENYALA' : 'MATI'
                        };
                    });
                }

                if (!records.length) {
                    toast('Belum ada data riwayat yang dapat diunduh.');
                    return;
                }

                const header = ['Waktu', 'Gudang', 'Suhu (°C)', 'Kelembaban (%)', 'Titik Embun (°C)', 'Status Blower'];
                const rows = records.map(r => [
                    `"${r.waktu}"`,
                    `"${r.gudang}"`,
                    r.suhu,
                    r.kelembaban,
                    r.titik_embun,
                    `"${r.blower}"`
                ]);

                const csvContent = '\uFEFF' + [header.join(','), ...rows.map(e => e.join(','))].join('\r\n');
                const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                const url = URL.createObjectURL(blob);

                const dateStr = new Date().toISOString().slice(0, 10);
                const a = document.createElement('a');
                a.href = url;
                a.download = `laporan-${z.name.replace(/\s+/g, '-').toLowerCase()}-${dateStr}.csv`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);

                toast(`Laporan CSV ${z.name} berhasil diunduh`);
            } catch (err) {
                toast('Gagal mengunduh file CSV.');
            } finally {
                btn.textContent = originalText;
                btn.disabled = false;
            }
        });
    }

    async function init() {
        tickClock();
        setInterval(tickClock, 1000);

        buildMap();
        buildChart();
        
        await loadHistoryFromFirebase();
        await pollFirebase();
        
        // Panggil setelah poll pertama selesai untuk mengamankan data Limits dari DB
        fillLimitInputs();
        renderAll();

        setInterval(async () => {
            await pollFirebase();
            renderAll();
        }, FIREBASE.pollMs);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
@endverbatim
</body>
</html>