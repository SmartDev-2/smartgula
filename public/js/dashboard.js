/**
 * dashboard.js
 * Monitoring Ruang Penyimpanan Gula
 *
 * Menggunakan Firebase Modular SDK (CDN ESM build) untuk mendengarkan
 * perubahan data sensor secara real-time tanpa perlu refresh halaman.
 *
 * Alur:
 *   Firebase RT DB → onValue(latestRef)  → updateMetricCards()
 *   Firebase RT DB → onValue(historyRef) → updateChart() + updateTable()
 */

import { initializeApp }                   from "https://www.gstatic.com/firebasejs/10.12.2/firebase-app.js";
import { getDatabase, ref, onValue, query, orderByChild, limitToLast }
                                            from "https://www.gstatic.com/firebasejs/10.12.2/firebase-database.js";

/* ============================================================
   1. Inisialisasi Firebase
   ============================================================ */

/** @type {object} Konfigurasi disuntikkan dari Blade via window.__FIREBASE_CONFIG__ */
const firebaseConfig = window.__FIREBASE_CONFIG__;

/** @type {object} Batas status disuntikkan dari Blade via window.__THRESHOLDS__ */
const THRESHOLDS = window.__THRESHOLDS__;

const app = initializeApp(firebaseConfig);
const db  = getDatabase(app);

/* ============================================================
   2. Konstanta & referensi DOM
   ============================================================ */

const DEVICE_PATH   = window.__DEVICE_PATH__ || "sensor/device_01";
const HISTORY_LIMIT = 50; // tampilkan maks N data terakhir di grafik & tabel

const elSuhu       = document.getElementById("val-suhu");
const elKelembapan = document.getElementById("val-kelembapan");
const elStatus     = document.getElementById("val-status");
const elStatusDesc = document.getElementById("val-status-desc");
const elLastUpdate = document.getElementById("last-update");
const elConnBadge  = document.getElementById("conn-badge");
const elConnText   = document.getElementById("conn-text");
const elTableBody  = document.getElementById("history-tbody");

/* ============================================================
   3. Logika status
   Semua batas bersumber dari THRESHOLDS — tidak ada nilai
   hardcode di dalam fungsi ini.
   ============================================================ */

/**
 * Tentukan status berdasarkan nilai suhu dan kelembapan.
 * @param {number} suhu
 * @param {number} kelembapan
 * @returns {{ label: string, desc: string }}
 */
function computeStatus(suhu, kelembapan) {
    if (suhu >= THRESHOLDS.temperature_high || kelembapan >= THRESHOLDS.humidity_high) {
        return { label: "TINGGI", desc: "Suhu atau kelembapan melebihi batas atas" };
    }
    if (suhu >= THRESHOLDS.temperature_warning || kelembapan >= THRESHOLDS.humidity_warning) {
        return { label: "PERINGATAN", desc: "Suhu atau kelembapan mendekati batas atas" };
    }
    return { label: "NORMAL", desc: "Kondisi lingkungan dalam batas normal" };
}

/* ============================================================
   4. Utilitas format
   ============================================================ */

/**
 * Format epoch milliseconds → string waktu lokal "HH:MM:SS DD/MM/YYYY"
 * @param {number} ts  epoch ms
 * @returns {string}
 */
function formatTimestamp(ts) {
    if (!ts) return "—";
    const d = new Date(ts);
    const pad = n => String(n).padStart(2, "0");
    return `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())} `
         + `${pad(d.getDate())}/${pad(d.getMonth()+1)}/${d.getFullYear()}`;
}

/**
 * Format epoch ms → label sumbu waktu ringkas "HH:MM:SS"
 * @param {number} ts
 * @returns {string}
 */
function formatTimeShort(ts) {
    if (!ts) return "";
    const d = new Date(ts);
    const pad = n => String(n).padStart(2, "0");
    return `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
}

/* ============================================================
   5. Update metric cards (data terkini)
   ============================================================ */

/**
 * Perbarui tampilan card suhu, kelembapan, dan status.
 * @param {{ suhu: number, kelembapan: number, timestamp: number, status?: string }} data
 */
function updateMetricCards(data) {
    const { suhu, kelembapan, timestamp } = data;
    const status = computeStatus(suhu, kelembapan);

    // Suhu
    elSuhu.textContent = Number(suhu).toFixed(1);

    // Kelembapan
    elKelembapan.textContent = Number(kelembapan).toFixed(1);

    // Status
    elStatus.textContent      = status.label;
    elStatus.className        = `status-badge ${status.label}`;
    elStatusDesc.textContent  = status.desc;

    // Last update
    elLastUpdate.textContent = "Diperbarui: " + formatTimestamp(timestamp);

    // Koneksi aktif
    setConnectionState(true);
}

/* ============================================================
   6. Indikator koneksi
   ============================================================ */

function setConnectionState(connected) {
    elConnBadge.className = "connection-badge " + (connected ? "connected" : "disconnected");
    elConnText.textContent = connected ? "Terhubung" : "Tidak Terhubung";
}

/* ============================================================
   7. Chart.js — Grafik riwayat suhu & kelembapan
   ============================================================ */

let chart = null;

function initChart() {
    const ctx = document.getElementById("sensor-chart").getContext("2d");

    Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
    Chart.defaults.font.size   = 12;

    chart = new Chart(ctx, {
        type: "line",
        data: {
            labels: [],
            datasets: [
                {
                    label: "Suhu (°C)",
                    data: [],
                    borderColor: "#ef4444",
                    backgroundColor: "rgba(239,68,68,.08)",
                    borderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    tension: 0.3,
                    yAxisID: "yTemp",
                },
                {
                    label: "Kelembapan (%)",
                    data: [],
                    borderColor: "#3b82f6",
                    backgroundColor: "rgba(59,130,246,.08)",
                    borderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    tension: 0.3,
                    yAxisID: "yHum",
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 400 },
            interaction: { mode: "index", intersect: false },
            plugins: {
                legend: {
                    position: "top",
                    labels: { boxWidth: 12, padding: 16 },
                },
                tooltip: {
                    callbacks: {
                        label: ctx => {
                            const v = ctx.parsed.y.toFixed(1);
                            return ctx.datasetIndex === 0
                                ? ` Suhu: ${v} °C`
                                : ` Kelembapan: ${v} %`;
                        },
                    },
                },
            },
            scales: {
                x: {
                    grid: { color: "rgba(0,0,0,.04)" },
                    ticks: { maxRotation: 45, color: "#64748b" },
                },
                yTemp: {
                    type: "linear",
                    position: "left",
                    title: { display: true, text: "Suhu (°C)", color: "#ef4444" },
                    grid: { color: "rgba(0,0,0,.04)" },
                    ticks: { color: "#64748b" },
                },
                yHum: {
                    type: "linear",
                    position: "right",
                    title: { display: true, text: "Kelembapan (%)", color: "#3b82f6" },
                    grid: { drawOnChartArea: false },
                    ticks: { color: "#64748b" },
                },
            },
        },
    });
}

/**
 * Perbarui data grafik dari array history.
 * @param {Array<{suhu:number, kelembapan:number, timestamp:number}>} rows
 */
function updateChart(rows) {
    if (!chart) return;

    chart.data.labels               = rows.map(r => formatTimeShort(r.timestamp));
    chart.data.datasets[0].data     = rows.map(r => r.suhu);
    chart.data.datasets[1].data     = rows.map(r => r.kelembapan);

    chart.update();
}

/* ============================================================
   8. Tabel riwayat
   ============================================================ */

/**
 * Render tabel riwayat, baris terbaru di atas.
 * @param {Array<{suhu:number, kelembapan:number, timestamp:number}>} rows
 */
function updateTable(rows) {
    if (!elTableBody) return;

    // Urutan terbaru di atas
    const sorted = [...rows].reverse();

    elTableBody.innerHTML = sorted.length === 0
        ? `<tr><td colspan="4" class="table-empty">Belum ada data riwayat.</td></tr>`
        : sorted.map(row => {
            const st = computeStatus(row.suhu, row.kelembapan);
            return `
            <tr>
                <td>${formatTimestamp(row.timestamp)}</td>
                <td class="text-right">${Number(row.suhu).toFixed(1)} °C</td>
                <td class="text-right">${Number(row.kelembapan).toFixed(1)} %</td>
                <td><span class="table-status ${st.label}">${st.label}</span></td>
            </tr>`.trim();
        }).join("");
}

/* ============================================================
   9. Firebase listeners
   ============================================================ */

/**
 * Dengarkan node `latest` untuk data real-time terkini.
 */
function listenLatest() {
    const latestRef = ref(db, `${DEVICE_PATH}/latest`);

    onValue(
        latestRef,
        snapshot => {
            const data = snapshot.val();
            if (!data) {
                console.warn("[Dashboard] Node latest kosong atau tidak ada.");
                setConnectionState(false);
                return;
            }
            updateMetricCards(data);
        },
        error => {
            console.error("[Dashboard] Error membaca latest:", error.message);
            setConnectionState(false);
        }
    );
}

/**
 * Dengarkan node `history` — ambil N data terakhir saja.
 * Menggunakan query orderByChild + limitToLast agar snapshot tidak besar.
 */
function listenHistory() {
    const historyRef = query(
        ref(db, `${DEVICE_PATH}/history`),
        orderByChild("timestamp"),
        limitToLast(HISTORY_LIMIT)
    );

    onValue(
        historyRef,
        snapshot => {
            const val = snapshot.val();
            if (!val) {
                updateChart([]);
                updateTable([]);
                return;
            }

            // Konversi object Firebase → array, urutkan ASC by timestamp
            const rows = Object.values(val).sort((a, b) => a.timestamp - b.timestamp);
            updateChart(rows);
            updateTable(rows);
        },
        error => {
            console.error("[Dashboard] Error membaca history:", error.message);
        }
    );
}

/* ============================================================
   10. Boot
   ============================================================ */

document.addEventListener("DOMContentLoaded", () => {
    // Tampilkan skeleton awal di value cards
    [elSuhu, elKelembapan].forEach(el => el.classList.add("skeleton"));

    initChart();
    listenLatest();
    listenHistory();
});
