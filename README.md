# Monitoring Ruang Penyimpanan Gula

Dashboard real-time untuk memonitor suhu dan kelembapan ruang penyimpanan.

## Stack

- **Laravel 13** — Backend / routing / server halaman
- **Firebase Realtime Database** — Penyimpanan data sensor real-time
- **Firebase Web SDK (Modular, CDN ESM)** — Listener real-time di browser
- **Chart.js 4** — Grafik riwayat sensor
- **DHT22 → ESP8266/ESP32** — Sumber data fisik

## Struktur Data Firebase

```
sensor/
  device_01/
    latest/
      suhu: 31.5
      kelembapan: 68
      timestamp: 1727500000000
      status: "NORMAL"
    history/
      <auto_id>/
        suhu: 31.5
        kelembapan: 68
        timestamp: 1727500000000
        status: "NORMAL"
```

## Struktur File

```
app/Http/Controllers/DashboardController.php  ← Konfigurasi & routing
config/firebase.php                           ← Firebase Web SDK config
config/monitoring.php                         ← Batas status (NORMAL/PERINGATAN/TINGGI)
resources/views/dashboard.blade.php           ← Tampilan utama
public/css/dashboard.css                      ← Stylesheet
public/js/dashboard.js                        ← Firebase listener + Chart.js
routes/web.php                                ← GET /dashboard
.env                                          ← Semua environment variable
```

## Setup

### 1. Clone & install

```bash
composer install
cp .env.example .env
php artisan key:generate
```

### 2. Isi `.env`

```env
FIREBASE_API_KEY=            # ← isi dari Firebase Console
FIREBASE_DATABASE_URL=https://smartteam-f5e2f-default-rtdb.firebaseio.com

# Batas status (sesuaikan dengan kondisi lapangan)
MONITORING_TEMP_WARNING=30.0
MONITORING_TEMP_HIGH=33.0
MONITORING_HUM_WARNING=65.0
MONITORING_HUM_HIGH=75.0
```

### 3. Jalankan server

```bash
php artisan serve
```

Buka: **http://localhost:8000/dashboard**

---

## Data Dummy Firebase (untuk pengujian)

Masuk ke [Firebase Console → Realtime Database](https://console.firebase.google.com/project/smartteam-f5e2f/database),
lalu import JSON berikut ke path **`sensor/device_01/latest`**:

```json
{
  "suhu": 31.5,
  "kelembapan": 68,
  "timestamp": 1727500000000,
  "status": "NORMAL"
}
```

Dan ke path **`sensor/device_01/history`**, tambahkan beberapa entry:

```json
{
  "entry_001": { "suhu": 29.5, "kelembapan": 63, "timestamp": 1727499700000, "status": "NORMAL" },
  "entry_002": { "suhu": 30.1, "kelembapan": 65, "timestamp": 1727499760000, "status": "PERINGATAN" },
  "entry_003": { "suhu": 31.5, "kelembapan": 68, "timestamp": 1727499820000, "status": "PERINGATAN" },
  "entry_004": { "suhu": 32.0, "kelembapan": 70, "timestamp": 1727499880000, "status": "PERINGATAN" },
  "entry_005": { "suhu": 33.5, "kelembapan": 76, "timestamp": 1727499940000, "status": "TINGGI" }
}
```

---

## Firebase Security Rules (Realtime Database)

Set rules di Firebase Console agar hanya bisa dibaca (read-only dari browser):

```json
{
  "rules": {
    "sensor": {
      ".read": true,
      ".write": false
    }
  }
}
```

> Untuk produksi, batasi `.read` lebih ketat (misal dengan autentikasi).

---

## Konfigurasi Batas Status

Edit `config/monitoring.php` atau ubah nilai di `.env`:

| Variable                  | Default | Keterangan                   |
|---------------------------|---------|------------------------------|
| `MONITORING_TEMP_WARNING` | 30.0 °C | Suhu → PERINGATAN            |
| `MONITORING_TEMP_HIGH`    | 33.0 °C | Suhu → TINGGI                |
| `MONITORING_HUM_WARNING`  | 65.0 %  | Kelembapan → PERINGATAN      |
| `MONITORING_HUM_HIGH`     | 75.0 %  | Kelembapan → TINGGI          |

> **Penting:** Nilai di atas adalah contoh prototipe. Sesuaikan berdasarkan hasil penelitian atau data lapangan aktual.

---

## Kode ESP8266/ESP32 (referensi)

```cpp
#include <ESP8266WiFi.h>         // atau #include <WiFi.h> untuk ESP32
#include <FirebaseESP8266.h>     // library Firebase untuk ESP
#include <DHT.h>

#define DHTPIN    D4
#define DHTTYPE   DHT22

DHT dht(DHTPIN, DHTTYPE);

void sendToFirebase(float suhu, float kelembapan) {
    // Tulis ke /sensor/device_01/latest
    Firebase.setFloat(fbdo, "/sensor/device_01/latest/suhu",       suhu);
    Firebase.setFloat(fbdo, "/sensor/device_01/latest/kelembapan", kelembapan);
    Firebase.setInt  (fbdo, "/sensor/device_01/latest/timestamp",  millis());

    // Push ke /sensor/device_01/history
    FirebaseJson json;
    json.set("suhu",       suhu);
    json.set("kelembapan", kelembapan);
    json.set("timestamp",  (int)millis());
    Firebase.pushJSON(fbdo, "/sensor/device_01/history", json);
}
```
