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
        }
        .btn-ghost[aria-pressed="true"] { background: #fff; color: var(--ink); }

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
        #map { width: 100%; height: auto; display: block; }
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
        .bar { position: relative; height: 10px; border-radius: 5px; margin-top: 10px; }
        .bar .marker {
            position: absolute; top: -5px; width: 4px; height: 20px; background: var(--ink);
            border-radius: 2px; transform: translateX(-50%); transition: left .5s;
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

        /* ---------- Responsif ---------- */
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
            <span class="pill" title="Jumlah sensor yang mengirim data"><i class="dot" id="netDot"></i><span id="netText">Menghubungkan…</span></span>
            <span class="pill" id="demoBadge" hidden>Mode demo</span>
            <button class="btn-ghost" id="soundBtn" aria-pressed="false">Suara alarm: mati</button>
            <div class="clock"><span id="clockTime">--:--:--</span><small id="clockDate">&nbsp;</small></div>
        </div>
    </div>
</header>

<main class="wrap">

    <section class="summary" aria-label="Ringkasan seluruh gudang">
        <div><div class="label">Rata-rata suhu</div><div class="value"><span id="sumTemp">--</span><small>°C</small></div></div>
        <div><div class="label">Rata-rata kelembaban</div><div class="value"><span id="sumRh">--</span><small>%</small></div></div>
        <div><div class="label">Blower menyala</div><div class="value"><span id="sumBlower">--</span><small id="sumBlowerOf"></small></div></div>
        <div><div class="label">Gudang perlu perhatian</div><div class="value" id="sumAlarmWrap"><span id="sumAlarm">--</span><small id="sumAlarmOf"></small></div></div>
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
                    <svg id="map" viewBox="0 0 780 300" role="group" aria-label="Denah empat gudang gula"></svg>
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
                    <h2 id="dName">Gudang</h2>
                    <span id="dProduct"></span>
                </div>
                <div class="panel-body">
                    <div class="readouts">
                        <div class="readout">
                            <div class="label">Suhu</div>
                            <div class="big"><span id="dTemp">--</span><small>°C</small></div>
                            <div class="bar" id="tBar"><div class="marker" id="tMarker"></div></div>
                            <div class="bar-scale"><span>25</span><span>35</span><span>45</span></div>
                        </div>
                        <div class="readout">
                            <div class="label">Kelembaban relatif</div>
                            <div class="big"><span id="dRh">--</span><small>%</small></div>
                            <div class="bar" id="hBar"><div class="marker" id="hMarker"></div></div>
                            <div class="bar-scale"><span>40</span><span>65</span><span>90</span></div>
                        </div>
                    </div>

                    <div class="facts">
                        <div><div class="label">Titik embun</div><div class="v"><span id="dDew">--</span> °C</div></div>
                        <div><div class="label">Selisih suhu &amp; titik embun</div><div class="v"><span id="dMargin">--</span> °C</div></div>
                        <div><div class="label">Terisi</div><div class="v"><span id="dStock">--</span> ton</div></div>
                    </div>

                    <div class="advice" id="dAdvice"></div>
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
                        <div><div class="label">Nyala hari ini</div><div class="v" id="bRuntime">--</div></div>
                        <div><div class="label">Terakhir berubah</div><div class="v" id="bLast">--</div></div>
                        <div><div class="label">Siklus hari ini</div><div class="v" id="bCycles">--</div></div>
                    </div>
                    <p class="rule" id="ruleText"></p>
                </div>
            </section>

            <section class="panel">
                <div class="panel-head"><h2>Batas alarm</h2><span>Untuk gudang terpilih</span></div>
                <div class="panel-body">
                    <div class="limits">
                        <div><label for="inTMax">Suhu maks. (°C)</label><input id="inTMax" type="number" step="0.5"></div>
                        <div><label for="inRhOn">Blower nyala (% RH)</label><input id="inRhOn" type="number" step="1"></div>
                        <div><label for="inRhOff">Blower mati (% RH)</label><input id="inRhOff" type="number" step="1"></div>
                        <div><label for="inRhMax">Alarm bahaya (% RH)</label><input id="inRhMax" type="number" step="1"></div>
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
    Acuan awal: gula kristal paling aman disimpan di bawah 35 °C dan kelembaban di bawah 65%. Atur batas sesuai SOP pabrik Anda.
</footer>

<div class="toast" id="toast" role="status" aria-live="polite"></div>

<script>
    window.MONITORING = {
        demo: true,
        pollMs: 5000,
        endpoints: { data: '', history: '', blower: '', thresholds: '' }
    };
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

@verbatim
<script>
(() => {
    const CFG = window.MONITORING;
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    const DEFAULT_LIMITS = { tMax: 35, rhOn: 65, rhOff: 58, rhMax: 70 };
    const STATUS_LABEL = { ok: 'Aman', warn: 'Waspada', crit: 'Bahaya', off: 'Sensor mati' };

    const state = { zones: [], selected: null, alarms: [], sound: false, chart: null, simClock: Date.now() };
    const $ = (id) => document.getElementById(id);
    const pad = (n) => String(n).padStart(2, '0');
    const hhmm = (ts) => { const d = new Date(ts); return pad(d.getHours()) + ':' + pad(d.getMinutes()); };
    const clamp = (v, a, b) => Math.min(b, Math.max(a, v));
    const fmt = (v, d = 1) => (v == null || isNaN(v)) ? '--' : Number(v).toFixed(d);

    /* ---------- Perhitungan ---------- */
    function dewPoint(t, rh) {
        const a = 17.62, b = 243.12;
        const g = Math.log(rh / 100) + (a * t) / (b + t);
        return (b * g) / (a - g);
    }

    function computeStatus(z) {
        if (!z.online) return 'off';
        const L = z.limits;
        const margin = z.temp - dewPoint(z.temp, z.rh);
        if (z.rh >= L.rhMax || z.temp >= L.tMax) return 'crit';
        if (z.rh >= L.rhOn || z.temp >= L.tMax - 2 || margin < 3) return 'warn';
        return 'ok';
    }

    function adviceFor(z) {
        const L = z.limits, s = z.status;
        const margin = z.temp - dewPoint(z.temp, z.rh);
        if (s === 'off') return 'Sensor tidak mengirim data. Periksa kabel dan catu daya sensor sebelum mengandalkan angka terakhir.';
        if (s === 'crit') {
            if (z.rh >= L.rhMax) return z.blower
                ? 'Kelembaban melewati batas bahaya. Blower sudah menyala; pastikan pintu tertutup dan cek risiko gula menggumpal.'
                : 'Kelembaban melewati batas bahaya dan blower mati. Nyalakan blower sekarang.';
            return 'Suhu melewati batas maksimum. Cek ventilasi dan sumber panas di sekitar gudang.';
        }
        if (s === 'warn') {
            if (margin < 3) return 'Suhu mendekati titik embun (selisih ' + fmt(margin) + ' °C). Ada risiko uap air mengembun pada karung.';
            if (z.rh >= L.rhOn) return 'Kelembaban mulai naik. ' + (z.blower ? 'Blower sedang menurunkannya.' : 'Blower belum menyala; pertimbangkan menyalakannya.');
            return 'Suhu mendekati batas maksimum. Pantau terus.';
        }
        return 'Kondisi penyimpanan baik. Suhu dan kelembaban di dalam batas aman.';
    }

    /* ---------- Data demo ---------- */
    function seedZone(id, name, product, stock, cap, baseT, baseRh) {
        const z = {
            id, name, product, stock, capacity: cap, temp: baseT, rh: baseRh,
            blower: false, mode: 'auto', online: true,
            limits: { ...DEFAULT_LIMITS },
            runtimeMin: 0, cycles: 0, lastSwitch: state.simClock - 3600000,
            updatedAt: Date.now(), history: [], status: 'ok', prevStatus: 'ok'
        };
        const STEP = 5 * 60000, N = 72;
        for (let i = N; i > 0; i--) {
            const ts = state.simClock - i * STEP;
            const wave = Math.sin(i / 9);
            z.history.push({
                t: ts,
                temp: +(baseT + wave * 0.9 + (Math.random() - .5) * .3).toFixed(2),
                rh: +(baseRh + wave * 3 + (Math.random() - .5) * 1).toFixed(2)
            });
        }
        z.history.push({ t: state.simClock, temp: baseT, rh: baseRh });
        return z;
    }

    function seedDemo() {
        return [
            seedZone('A', 'Gudang A', 'Gula kristal putih (GKP)', 820, 1200, 31.5, 57),
            seedZone('B', 'Gudang B', 'Gula kristal putih (GKP)', 1010, 1200, 32.4, 61),
            seedZone('C', 'Gudang C', 'Gula kristal rafinasi', 460, 900, 33.6, 66),
            seedZone('D', 'Gudang D', 'Gula curah', 300, 900, 30.8, 54)
        ];
    }

    function demoTick() {
        state.simClock += 5 * 60000;
        state.zones.forEach((z, i) => {
            z.online = !(z.id === 'D' && z.forceOffline);
            if (!z.online) return;
            z.updatedAt = Date.now();
            if (z.mode === 'auto') applyAutoRule(z, true);

            const drift = (Math.random() - .42) * 0.9 + (z.blower ? -1.1 : 0.35);
            z.rh = clamp(z.rh + drift, 42, 88);
            z.temp = clamp(z.temp + (Math.random() - .5) * .3 + (z.blower ? -.08 : .05), 27, 40);
            if (z.blower) z.runtimeMin += 5;
            z.history.push({ t: state.simClock, temp: +z.temp.toFixed(2), rh: +z.rh.toFixed(2) });
            if (z.history.length > 96) z.history.shift();
        });
        // Simulasi sensor D sesekali mati
        const d = state.zones.find(z => z.id === 'D');
        if (d && Math.random() < 0.01) d.forceOffline = !d.forceOffline;
    }

    /* ---------- Aturan otomatis (histeresis) ---------- */
    function applyAutoRule(z, quiet) {
        const L = z.limits;
        if (!z.blower && z.rh >= L.rhOn) setBlower(z, true, 'otomatis', quiet);
        else if (z.blower && z.rh <= L.rhOff) setBlower(z, false, 'otomatis', quiet);
    }

    function setBlower(z, on, by, quiet) {
        if (z.blower === on) return;
        z.blower = on;
        z.lastSwitch = CFG.demo ? state.simClock : Date.now();
        if (on) z.cycles += 1;
        if (!quiet || true) addAlarm('info', z.name + ': blower ' + (on ? 'dinyalakan' : 'dimatikan') + ' (' + by + ')');
    }

    /* ---------- Log kejadian ---------- */
    function addAlarm(level, msg) {
        state.alarms.unshift({ t: Date.now(), level, msg });
        if (state.alarms.length > 40) state.alarms.pop();
        renderLog();
        if (level === 'crit' && state.sound) beep();
    }

    function renderLog() {
        const ul = $('log');
        if (!state.alarms.length) { ul.innerHTML = '<li class="empty" style="display:block">Belum ada kejadian.</li>'; return; }
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
        } catch (e) { /* browser memblokir audio sebelum ada klik */ }
    }

    /* ---------- Denah ---------- */
    const FAN_PATH = 'M0 0 C 6 -6, 8 -20, 0 -24 C -8 -20, -6 -6, 0 0 Z';
    const ROOM = { w: 172, h: 210, gap: 22, x0: 12, y0: 12 };

    function buildMap() {
        const svg = $('map');
        let html = '<rect class="floor-lane" x="0" y="238" width="780" height="50" rx="4"/>' +
                   '<text class="t-sub" x="390" y="268" text-anchor="middle">Jalur forklift dan pintu muat</text>';
        state.zones.forEach((z, i) => {
            const x = ROOM.x0 + i * (ROOM.w + ROOM.gap), y = ROOM.y0, cx = x + ROOM.w / 2;
            html += `
            <g class="room ok" id="room-${z.id}" data-id="${z.id}" tabindex="0" role="button" aria-label="${z.name}">
                <rect class="body" x="${x}" y="${y}" width="${ROOM.w}" height="${ROOM.h}" rx="6"/>
                <text class="t-name" x="${x + 14}" y="${y + 28}">${z.name}</text>
                <text class="t-sub" x="${x + ROOM.w - 14}" y="${y + 27}" text-anchor="end" id="rs-${z.id}"></text>
                <g transform="translate(${cx} ${y + 78})">
                    <circle r="34" fill="#fff" fill-opacity=".7"/>
                    <g class="fan" id="fan-${z.id}">
                        <path d="${FAN_PATH}"/>
                        <path d="${FAN_PATH}" transform="rotate(120)"/>
                        <path d="${FAN_PATH}" transform="rotate(240)"/>
                    </g>
                    <circle r="4" fill="#14252e"/>
                </g>
                <text class="t-sub" x="${cx}" y="${y + 132}" text-anchor="middle" id="rb-${z.id}"></text>
                <text class="t-temp" x="${x + 14}" y="${y + 165}" id="rt-${z.id}"></text>
                <text class="t-unit" x="${x + 14}" y="${y + 181}">suhu °C</text>
                <text class="t-rh" x="${x + ROOM.w - 14}" y="${y + 165}" text-anchor="end" id="rh-${z.id}"></text>
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
            g.setAttribute('class', 'room ' + z.status + (z.id === state.selected ? ' selected' : ''));
            $('rt-' + z.id).textContent = z.online ? fmt(z.temp) : '--';
            $('rh-' + z.id).textContent = z.online ? fmt(z.rh, 0) : '--';
            $('rs-' + z.id).textContent = STATUS_LABEL[z.status];
            $('rb-' + z.id).textContent = 'Blower ' + (z.blower ? 'menyala' : 'mati') + ' · ' + (z.mode === 'auto' ? 'otomatis' : 'manual');
            $('fan-' + z.id).setAttribute('class', 'fan' + (z.blower && z.online ? ' on' : ''));
            const bar = $('rstock-' + z.id);
            bar.setAttribute('width', (bar.dataset.max * clamp(z.stock / z.capacity, 0, 1)).toFixed(1));
        });
    }

    /* ---------- Ringkasan & tabel ---------- */
    function updateSummary() {
        const live = state.zones.filter(z => z.online);
        const avg = (k) => live.length ? live.reduce((s, z) => s + z[k], 0) / live.length : null;
        $('sumTemp').textContent = fmt(avg('temp'));
        $('sumRh').textContent = fmt(avg('rh'), 0);
        const on = state.zones.filter(z => z.blower).length;
        $('sumBlower').textContent = on;
        $('sumBlowerOf').textContent = '/ ' + state.zones.length;
        const bad = state.zones.filter(z => z.status === 'warn' || z.status === 'crit' || z.status === 'off').length;
        $('sumAlarm').textContent = bad;
        $('sumAlarmOf').textContent = '/ ' + state.zones.length;
        $('sumAlarmWrap').classList.toggle('is-crit', state.zones.some(z => z.status === 'crit'));
        const online = live.length;
        $('netText').textContent = 'Sensor online ' + online + '/' + state.zones.length;
        $('netDot').classList.toggle('bad', online < state.zones.length);
    }

    function updateTable() {
        $('tableBody').innerHTML = state.zones.map(z => `
            <tr data-id="${z.id}" class="${z.id === state.selected ? 'selected' : ''}">
                <td><strong>${z.name}</strong></td>
                <td class="num">${z.online ? fmt(z.temp) + ' °C' : '--'}</td>
                <td class="num">${z.online ? fmt(z.rh, 0) + ' %' : '--'}</td>
                <td class="num">${z.online ? fmt(dewPoint(z.temp, z.rh)) + ' °C' : '--'}</td>
                <td>${fmt(z.stock, 0)} / ${fmt(z.capacity, 0)} ton</td>
                <td><span class="badge ${z.blower ? 'on' : 'off'}">${z.blower ? 'Menyala' : 'Mati'}</span> <small>${z.mode === 'auto' ? 'otomatis' : 'manual'}</small></td>
                <td><span class="badge ${z.status}">${STATUS_LABEL[z.status]}</span></td>
                <td>${hhmm(z.updatedAt)}</td>
            </tr>`).join('');
        $('tableBody').querySelectorAll('tr').forEach(tr => tr.addEventListener('click', () => select(tr.dataset.id)));
    }

    /* ---------- Detail zona terpilih ---------- */
    function zoneBar(elId, min, max, okTo, warnTo) {
        const p = (v) => ((clamp(v, min, max) - min) / (max - min) * 100).toFixed(1);
        $(elId).style.background = `linear-gradient(to right,
            #9fd3b8 0%, #9fd3b8 ${p(okTo)}%,
            #f3cf7a ${p(okTo)}%, #f3cf7a ${p(warnTo)}%,
            #eba299 ${p(warnTo)}%, #eba299 100%)`;
        return p;
    }

    function updateDetail() {
        const z = state.zones.find(x => x.id === state.selected);
        if (!z) return;
        const L = z.limits;
        $('dName').textContent = z.name;
        $('dProduct').textContent = z.product;
        $('dTemp').textContent = z.online ? fmt(z.temp) : '--';
        $('dRh').textContent = z.online ? fmt(z.rh, 1) : '--';

        const pT = zoneBar('tBar', 25, 45, L.tMax - 2, L.tMax);
        const pH = zoneBar('hBar', 40, 90, L.rhOn, L.rhMax);
        $('tMarker').style.left = pT(z.temp) + '%';
        $('hMarker').style.left = pH(z.rh) + '%';

        const dew = dewPoint(z.temp, z.rh);
        $('dDew').textContent = z.online ? fmt(dew) : '--';
        $('dMargin').textContent = z.online ? fmt(z.temp - dew) : '--';
        $('dStock').textContent = fmt(z.stock, 0) + ' / ' + fmt(z.capacity, 0);

        const adv = $('dAdvice');
        adv.className = 'advice ' + z.status;
        adv.textContent = adviceFor(z);

        // Blower
        const sw = $('blowerSwitch');
        sw.setAttribute('aria-checked', String(z.blower));
        $('switchText').textContent = z.blower ? 'NYALA' : 'MATI';
        $('bState').textContent = z.blower ? 'Menyala' : 'Mati';
        $('modeAuto').setAttribute('aria-pressed', String(z.mode === 'auto'));
        $('modeManual').setAttribute('aria-pressed', String(z.mode === 'manual'));
        $('bRuntime').textContent = Math.floor(z.runtimeMin / 60) + ' j ' + pad(z.runtimeMin % 60) + ' m';
        $('bLast').textContent = hhmm(z.lastSwitch);
        $('bCycles').textContent = z.cycles + ' kali';
        $('ruleText').textContent = z.mode === 'auto'
            ? 'Mode otomatis: blower menyala saat RH mencapai ' + L.rhOn + '% dan mati saat turun ke ' + L.rhOff + '%.'
            : 'Mode manual: blower hanya berubah lewat tombol di atas. Alarm tetap aktif.';
    }

    function fillLimitInputs() {
        const z = state.zones.find(x => x.id === state.selected);
        if (!z) return;
        $('inTMax').value = z.limits.tMax;
        $('inRhOn').value = z.limits.rhOn;
        $('inRhOff').value = z.limits.rhOff;
        $('inRhMax').value = z.limits.rhMax;
    }

    /* ---------- Grafik ---------- */
    function buildChart() {
        const ctx = $('chart').getContext('2d');
        state.chart = new Chart(ctx, {
            type: 'line',
            data: { labels: [], datasets: [
                { label: 'Suhu (°C)', data: [], borderColor: '#14252e', backgroundColor: '#14252e', yAxisID: 'yT', pointRadius: 0, borderWidth: 2, tension: .3 },
                { label: 'Kelembaban (%)', data: [], borderColor: '#1e6fa8', backgroundColor: '#1e6fa8', yAxisID: 'yH', pointRadius: 0, borderWidth: 2, tension: .3 },
                { label: 'RH blower nyala', data: [], borderColor: '#c98200', yAxisID: 'yH', pointRadius: 0, borderWidth: 1.5, borderDash: [6, 5] },
                { label: 'RH bahaya', data: [], borderColor: '#c8372d', yAxisID: 'yH', pointRadius: 0, borderWidth: 1.5, borderDash: [6, 5] }
            ]},
            options: {
                responsive: true, maintainAspectRatio: false, animation: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, usePointStyle: true } } },
                scales: {
                    x: { ticks: { maxTicksLimit: 8, maxRotation: 0 }, grid: { display: false } },
                    yT: { position: 'left', min: 25, max: 42, title: { display: true, text: '°C' } },
                    yH: { position: 'right', min: 40, max: 90, title: { display: true, text: '% RH' }, grid: { drawOnChartArea: false } }
                }
            }
        });
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
        $('chartZone').textContent = '· ' + z.name;
    }

    /* ---------- Render gabungan ---------- */
    function refreshStatuses() {
        state.zones.forEach(z => {
            z.prevStatus = z.status;
            z.status = computeStatus(z);
            if (z.status !== z.prevStatus) {
                if (z.status === 'crit') addAlarm('crit', z.name + ': BAHAYA. RH ' + fmt(z.rh, 0) + '%, suhu ' + fmt(z.temp) + ' °C');
                else if (z.status === 'warn' && z.prevStatus === 'ok') addAlarm('warn', z.name + ': waspada. RH ' + fmt(z.rh, 0) + '%, suhu ' + fmt(z.temp) + ' °C');
                else if (z.status === 'off') addAlarm('warn', z.name + ': sensor tidak mengirim data');
                else if (z.status === 'ok') addAlarm('ok', z.name + ': kembali normal');
            }
        });
    }

    function renderAll() {
        refreshStatuses();
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

    /* ---------- Aksi pengguna ---------- */
    function toast(msg) {
        const t = $('toast'); t.textContent = msg; t.classList.add('show');
        clearTimeout(toast._t); toast._t = setTimeout(() => t.classList.remove('show'), 2200);
    }

    async function post(url, body) {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify(body)
        });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
    }

    async function sendBlower(z, payload) {
        if (CFG.demo) return;
        try { await post(CFG.endpoints.blower + '/' + z.id, payload); }
        catch (e) { toast('Perintah blower gagal dikirim. Periksa koneksi ke server.'); }
    }

    $('blowerSwitch').addEventListener('click', () => {
        const z = state.zones.find(x => x.id === state.selected);
        if (!z || !z.online) return;
        z.mode = 'manual';
        setBlower(z, !z.blower, 'manual', false);
        sendBlower(z, { state: z.blower ? 'on' : 'off', mode: 'manual' });
        renderAll();
    });

    function setMode(mode) {
        const z = state.zones.find(x => x.id === state.selected);
        if (!z) return;
        z.mode = mode;
        if (mode === 'auto') applyAutoRule(z, false);
        sendBlower(z, { state: z.blower ? 'on' : 'off', mode });
        toast(z.name + ': mode ' + (mode === 'auto' ? 'otomatis' : 'manual'));
        renderAll();
    }
    $('modeAuto').addEventListener('click', () => setMode('auto'));
    $('modeManual').addEventListener('click', () => setMode('manual'));

    $('saveLimits').addEventListener('click', async () => {
        const z = state.zones.find(x => x.id === state.selected);
        if (!z) return;
        const L = {
            tMax: parseFloat($('inTMax').value), rhOn: parseFloat($('inRhOn').value),
            rhOff: parseFloat($('inRhOff').value), rhMax: parseFloat($('inRhMax').value)
        };
        if (Object.values(L).some(isNaN)) return toast('Semua batas harus berupa angka.');
        if (!(L.rhOff < L.rhOn && L.rhOn < L.rhMax)) return toast('Urutan RH harus: mati < nyala < bahaya.');
        z.limits = L;
        if (!CFG.demo) {
            try { await post(CFG.endpoints.thresholds + '/' + z.id, L); }
            catch (e) { return toast('Batas gagal disimpan ke server.'); }
        }
        toast('Batas ' + z.name + ' disimpan');
        addAlarm('info', z.name + ': batas alarm diperbarui');
        renderAll();
    });

    $('clearLog').addEventListener('click', () => { state.alarms = []; renderLog(); });

    $('soundBtn').addEventListener('click', (e) => {
        state.sound = !state.sound;
        e.currentTarget.setAttribute('aria-pressed', String(state.sound));
        e.currentTarget.textContent = 'Suara alarm: ' + (state.sound ? 'nyala' : 'mati');
        if (state.sound) beep();
    });

    $('csvBtn').addEventListener('click', () => {
        const z = state.zones.find(x => x.id === state.selected);
        if (!z) return;
        const rows = ['waktu,suhu_c,kelembaban_persen,titik_embun_c'].concat(
            z.history.map(p => [new Date(p.t).toISOString(), p.temp, p.rh, dewPoint(p.temp, p.rh).toFixed(2)].join(','))
        );
        const a = document.createElement('a');
        a.href = URL.createObjectURL(new Blob([rows.join('\n')], { type: 'text/csv' }));
        a.download = 'riwayat-' + z.name.replace(/\s+/g, '-').toLowerCase() + '.csv';
        a.click();
        URL.revokeObjectURL(a.href);
    });

    /* ---------- Jam ---------- */
    function tickClock() {
        const d = new Date();
        $('clockTime').textContent = pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
        $('clockDate').textContent = d.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    }

    /* ---------- Mode live (API Laravel) ---------- */
    function normalizeZone(raw, old) {
        const z = old || { history: [], runtimeMin: 0, cycles: 0, status: 'ok', prevStatus: 'ok' };
        z.id = String(raw.id); z.name = raw.name; z.product = raw.product || '';
        z.stock = raw.stock ?? 0; z.capacity = raw.capacity ?? 1;
        z.temp = raw.temp; z.rh = raw.rh;
        z.blower = !!raw.blower; z.mode = raw.mode || 'auto';
        z.online = raw.online !== false;
        z.limits = Object.assign({}, DEFAULT_LIMITS, raw.limits || {});
        z.runtimeMin = raw.runtime_min ?? z.runtimeMin;
        z.cycles = raw.cycles ?? z.cycles;
        z.lastSwitch = raw.last_switch ? new Date(raw.last_switch).getTime() : (z.lastSwitch || Date.now());
        z.updatedAt = raw.updated_at ? new Date(raw.updated_at).getTime() : Date.now();
        return z;
    }

    async function pollLive() {
        try {
            const res = await fetch(CFG.endpoints.data, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const json = await res.json();
            const first = state.zones.length === 0;
            state.zones = json.zones.map(r => normalizeZone(r, state.zones.find(z => z.id === String(r.id))));
            if (first) {
                buildMap();
                state.selected = state.zones[0].id;
                fillLimitInputs();
                await Promise.all(state.zones.map(loadHistory));
            } else {
                state.zones.forEach(z => { if (z.online) z.history.push({ t: z.updatedAt, temp: z.temp, rh: z.rh }); if (z.history.length > 96) z.history.shift(); });
            }
            renderAll();
        } catch (e) {
            $('netText').textContent = 'Server tidak terhubung';
            $('netDot').classList.add('bad');
        }
    }

    async function loadHistory(z) {
        try {
            const res = await fetch(CFG.endpoints.history + '/' + z.id, { headers: { 'Accept': 'application/json' } });
            const json = await res.json();
            z.history = json.points.map(p => ({ t: new Date(p.time).getTime(), temp: p.temp, rh: p.rh }));
        } catch (e) { z.history = []; }
    }

    /* ---------- Mulai ---------- */
    function init() {
        tickClock(); setInterval(tickClock, 1000);
        buildChart();
        renderLog();

        if (CFG.demo) {
            $('demoBadge').hidden = false;
            state.zones = seedDemo();
            buildMap();
            state.selected = state.zones[0].id;
            fillLimitInputs();
            state.zones.forEach(z => { z.status = computeStatus(z); z.prevStatus = z.status; });
            renderAll();
            setInterval(() => { demoTick(); renderAll(); }, 3000);
        } else {
            pollLive();
            setInterval(pollLive, CFG.pollMs);
        }
    }
    init();
})();
</script>
@endverbatim
</body>
</html>