/**
 * dashboard.js — Monitoring Ruang Penyimpanan Gula (v2)
 *
 * Firebase Modular SDK (ESM CDN) + Chart.js + SVG Gauge
 *
 * Alur data:
 *   Firebase RT DB → onValue(latestRef)  → updateMetricCards() + updateGauges()
 *   Firebase RT DB → onValue(historyRef) → updateChart() + updateTable()
 */

import { initializeApp }                                              from "https://www.gstatic.com/firebasejs/10.12.2/firebase-app.js";
import { getDatabase, ref, onValue, query, orderByChild, limitToLast } from "https://www.gstatic.com/firebasejs/10.12.2/firebase-database.js";

/* ============================================================
   1. Init Firebase
   ============================================================ */
const app = initializeApp(window.__FIREBASE_CONFIG__);
const db  = getDatabase(app);

const THRESHOLDS  = window.__THRESHOLDS__;
const DEVICE_PATH = window.__DEVICE_PATH__ || "sensor/device_01";
const HISTORY_LIMIT = 50;

/* ============================================================
   2. DOM refs
   ============================================================ */
const elSuhu        = document.getElementById("val-suhu");
const elKelembapan  = document.getElementById("val-kelembapan");
const elStatus      = document.getElementById("val-status");
const elStatusDesc  = document.getElementById("val-status-desc");
const elLastUpdate  = document.getElementById("last-update");
const elConnBadge   = document.getElementById("conn-badge");
const elConnText    = document.getElementById("conn-text");
const elTableBody   = document.getElementById("history-tbody");

// Gauge SVG elements
const elGaugeTemp      = document.getElementById("gauge-temp");
const elGaugeTempLabel = document.getElementById("gauge-temp-label");
const elGaugeHum       = document.getElementById("gauge-hum");
const elGaugeHumLabel  = document.getElementById("gauge-hum-label");

/* ============================================================
   3. Status logic
   ============================================================ */
/**
 * @param {number} suhu
 * @param {number} kelembapan
 * @returns {{ label: string, desc: string }}
 */
function computeStatus(suhu, kelembapan) {
    if (suhu >= THRESHOLDS.temperature_high || kelembapan >= THRESHOLDS.humidity_high) {
        return { label: "TINGGI",     desc: "Suhu atau kelembapan melebihi batas atas — perlu tindakan segera." };
    }
    if (suhu >= THRESHOLDS.temperature_warning || kelembapan >= THRESHOLDS.humidity_warning) {
        return { label: "PERINGATAN", desc: "Suhu atau kelembapan mendekati batas atas — pantau lebih ketat." };
    }
    return     { label: "NORMAL",    desc: "Kondisi lingkungan dalam batas normal." };
}

/* ============================================================
   4. Format helpers
   ============================================================ */
function formatTimestamp(ts) {
    if (!ts) return "—";
    const d = new Date(ts);
    const p = n => String(n).padStart(2, "0");
    return `${p(d.getDate())}/${p(d.getMonth()+1)}/${d.getFullYear()} `
         + `${p(d.getHours())}:${p(d.getMinutes())}:${p(d.getSeconds())}`;
}

function formatTimeShort(ts) {
    if (!ts) return "";
    const d = new Date(ts);
    const p = n => String(n).padStart(2, "0");
    return `${p(d.getHours())}:${p(d.getMinutes())}:${p(d.getSeconds())}`;
}

/* ============================================================
   5. Mini SVG gauge
   Arc path: M12,64 A34,34 0 1,1 68,64  total arc ≈ 220°
   stroke-dasharray=110 (full arc circumference approximation)
   We map value [0..max] → offset [110..0]
   ============================================================ */
const GAUGE_FULL = 110; // stroke-dasharray total

/**
 * @param {SVGElement} pathEl
 * @param {SVGTextElement} labelEl
 * @param {number} value
 * @param {number} max     — 100% of gauge
 * @param {string} color   — stroke color
 */
function updateGauge(pathEl, labelEl, value, max, color) {
    if (!pathEl || !labelEl) return;
    const ratio  = Math.min(Math.max(value / max, 0), 1);
    const offset = GAUGE_FULL - ratio * GAUGE_FULL;
    pathEl.style.strokeDashoffset = offset;
    pathEl.style.stroke           = color;
    labelEl.textContent           = isNaN(value) ? "—" : value.toFixed(1);
}

/** Pick gauge color based on thresholds */
function gaugeColorTemp(v) {
    if (v >= THRESHOLDS.temperature_high)    return "#dc2626";
    if (v >= THRESHOLDS.temperature_warning) return "#d97706";
    return "#16a34a";
}

function gaugeColorHum(v) {
    if (v >= THRESHOLDS.humidity_high)    return "#dc2626";
    if (v >= THRESHOLDS.humidity_warning) return "#d97706";
    return "#3b82f6";
}

/* ============================================================
   6. Update metric cards + gauges
   ============================================================ */
function updateMetricCards(data) {
    const { suhu, kelembapan, timestamp } = data;
    const status = computeStatus(suhu, kelembapan);

    // Remove skeleton
    elSuhu.classList.remove("skeleton");
    elKelembapan.classList.remove("skeleton");

    elSuhu.textContent       = Number(suhu).toFixed(1);
    elKelembapan.textContent = Number(kelembapan).toFixed(1);

    // Status pill
    elStatus.textContent = status.label;
    // Remove old status classes
    elStatus.classList.remove("NORMAL", "PERINGATAN", "TINGGI");
    elStatus.classList.add(status.label);
    // Ensure dot exists inside pill
    if (!elStatus.querySelector(".status-pill__dot")) {
        const dot = document.createElement("span");
        dot.className = "status-pill__dot";
        elStatus.prepend(dot);
    }

    elStatusDesc.textContent = status.desc;

    // Last update
    elLastUpdate.dataset.hasData = "1";
    elLastUpdate.textContent = "Diperbarui: " + formatTimestamp(timestamp);

    // Gauges (max: 50°C suhu, 100% kelembapan)
    updateGauge(elGaugeTemp, elGaugeTempLabel, Number(suhu),       50,  gaugeColorTemp(suhu));
    updateGauge(elGaugeHum,  elGaugeHumLabel,  Number(kelembapan), 100, gaugeColorHum(kelembapan));

    setConnectionState(true);
}

/* ============================================================
   7. Connection badge
   ============================================================ */
function setConnectionState(connected) {
    elConnBadge.className = "sidebar__conn " + (connected ? "connected" : "disconnected");
    elConnText.textContent = connected ? "Terhubung" : "Tidak Terhubung";
}

/* ============================================================
   8. Chart.js
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
                    backgroundColor: (ctx) => {
                        const g = ctx.chart.ctx.createLinearGradient(0, 0, 0, 280);
                        g.addColorStop(0,   "rgba(239,68,68,.18)");
                        g.addColorStop(1,   "rgba(239,68,68,0)");
                        return g;
                    },
                    borderWidth: 2.5,
                    pointRadius: 3,
                    pointBackgroundColor: "#ef4444",
                    pointHoverRadius: 6,
                    pointHoverBackgroundColor: "#fff",
                    pointHoverBorderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    yAxisID: "yTemp",
                },
                {
                    label: "Kelembapan (%)",
                    data: [],
                    borderColor: "#3b82f6",
                    backgroundColor: (ctx) => {
                        const g = ctx.chart.ctx.createLinearGradient(0, 0, 0, 280);
                        g.addColorStop(0,   "rgba(59,130,246,.14)");
                        g.addColorStop(1,   "rgba(59,130,246,0)");
                        return g;
                    },
                    borderWidth: 2.5,
                    pointRadius: 3,
                    pointBackgroundColor: "#3b82f6",
                    pointHoverRadius: 6,
                    pointHoverBackgroundColor: "#fff",
                    pointHoverBorderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    yAxisID: "yHum",
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 500, easing: "easeInOutQuart" },
            interaction: { mode: "index", intersect: false },
            plugins: {
                legend: { display: false }, // Custom legend via HTML
                tooltip: {
                    backgroundColor: "#0f172a",
                    titleColor: "#94a3b8",
                    bodyColor: "#f1f5f9",
                    borderColor: "rgba(255,255,255,.08)",
                    borderWidth: 1,
                    padding: 12,
                    cornerRadius: 10,
                    callbacks: {
                        label: ctx => {
                            const v = ctx.parsed.y.toFixed(1);
                            return ctx.datasetIndex === 0
                                ? `  Suhu: ${v} °C`
                                : `  Kelembapan: ${v} %`;
                        },
                    },
                },
            },
            scales: {
                x: {
                    grid: { color: "rgba(0,0,0,.04)", drawBorder: false },
                    ticks: { color: "#9ca3af", maxRotation: 40, maxTicksLimit: 10 },
                    border: { display: false },
                },
                yTemp: {
                    type: "linear",
                    position: "left",
                    title: { display: true, text: "Suhu (°C)", color: "#ef4444", font: { size: 11, weight: "600" } },
                    grid: { color: "rgba(0,0,0,.04)", drawBorder: false },
                    ticks: { color: "#9ca3af" },
                    border: { display: false },
                },
                yHum: {
                    type: "linear",
                    position: "right",
                    title: { display: true, text: "Kelembapan (%)", color: "#3b82f6", font: { size: 11, weight: "600" } },
                    grid: { drawOnChartArea: false, drawBorder: false },
                    ticks: { color: "#9ca3af" },
                    border: { display: false },
                },
            },
        },
    });
}

function updateChart(rows) {
    if (!chart) return;
    chart.data.labels           = rows.map(r => formatTimeShort(r.timestamp));
    chart.data.datasets[0].data = rows.map(r => r.suhu);
    chart.data.datasets[1].data = rows.map(r => r.kelembapan);
    chart.update();
}

/* ============================================================
   9. Table
   ============================================================ */
function updateTable(rows) {
    if (!elTableBody) return;
    const sorted = [...rows].reverse();

    elTableBody.innerHTML = sorted.length === 0
        ? `<tr><td colspan="4" class="table-empty">Belum ada data riwayat.</td></tr>`
        : sorted.map((row, i) => {
            const st = computeStatus(row.suhu, row.kelembapan);
            const isLatest = i === 0 ? ' class="latest-row"' : '';
            return `<tr${isLatest}>
                <td class="muted">${formatTimestamp(row.timestamp)}</td>
                <td class="r val-temp">${Number(row.suhu).toFixed(1)} °C</td>
                <td class="r val-hum">${Number(row.kelembapan).toFixed(1)} %</td>
                <td><span class="chip ${st.label}"><span class="chip__dot"></span>${st.label}</span></td>
            </tr>`;
        }).join("");
}

/* ============================================================
   10. Firebase listeners
   ============================================================ */
function listenLatest() {
    const latestRef = ref(db, `${DEVICE_PATH}/latest`);
    onValue(
        latestRef,
        snapshot => {
            const data = snapshot.val();
            if (!data) { setConnectionState(false); return; }
            updateMetricCards(data);
        },
        err => { console.error("[Dashboard] latest:", err.message); setConnectionState(false); }
    );
}

function listenHistory() {
    const historyQ = query(
        ref(db, `${DEVICE_PATH}/history`),
        orderByChild("timestamp"),
        limitToLast(HISTORY_LIMIT)
    );
    onValue(
        historyQ,
        snapshot => {
            const val = snapshot.val();
            if (!val) { updateChart([]); updateTable([]); return; }
            const rows = Object.values(val).sort((a, b) => a.timestamp - b.timestamp);
            updateChart(rows);
            updateTable(rows);
        },
        err => console.error("[Dashboard] history:", err.message)
    );
}

/* ============================================================
   11. Boot
   ============================================================ */
document.addEventListener("DOMContentLoaded", () => {
    // Skeleton state
    elSuhu.classList.add("skeleton");
    elKelembapan.classList.add("skeleton");

    initChart();
    listenLatest();
    listenHistory();
});
