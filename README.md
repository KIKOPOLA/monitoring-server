# ⚡ Monitoring Server by kikopola

[![Latest Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com/kikopola/monitoring-server)
[![Software License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Laravel Compatibility](https://img.shields.io/badge/laravel-10.x%20|%2011.x%20|%2012.x%20|%2013.x-red.svg)](https://laravel.com)
[![PHP Version](https://img.shields.io/badge/php-%5E8.1%20|%20%5E8.2%20|%20%5E8.3%20|%20%5E8.4-purple.svg)](https://php.net)

Package pemantau server (*Server Health & Resource Monitor*) untuk aplikasi Laravel yang bekerja secara **Zero-Configuration (Plug and Play)**. Cukup install via Composer, dan langsung aktif tanpa perlu mengubah kode aplikasi Anda.

```text
  __  __             _ _             _                ____                                
 |  \/  | ___  _ __ (_) |_ ___  _ __(_)_ __   __ _   / ___|  ___ _ ____   _____ _ __ 
 | |\/| |/ _ \| '_ \| | __/ _ \| '__| | '_ \ / _` |  \___ \ / _ \ '__\ \ / / _ \ '__|
 | |  | | (_) | | | | | || (_) | |  | | | | | (_| |   ___) |  __/ |   \ V /  __/ |   
 |_|  |_|\___/|_| |_|_|\__\___/|_|  |_|_| |_|\__, |  |____/ \___|_|    \_/ \___|_|   
                                              |___/                                   
                        ⚡ MONITORING SERVER • by kikopola ⚡
```

---

## 🚀 Fitur Utama

- **100% Data Hardware Asli**: Membaca CPU, RAM, Disk Space, dan Server Uptime riil langsung dari sistem operasi (Mendukung **Linux / Ubuntu Server** dan **Windows / Laragon**).
- **Cek Kesehatan Database**: Memantau koneksi database aktif, driver engine, dan latensi query (*ping response time* dalam milidetik).
- **Perintah Terminal Interaktif**: Cukup ketik `php artisan monitoring:check` untuk melihat banner keren dan tabel kondisi server, lengkap dengan link langsung menuju dashboard.
- **Web Dashboard Real-Time**: Halaman web modern bertema *Dark Mode* di `/monitoring` dengan *live polling* otomatis setiap 3 detik tanpa reload halaman.
- **REST API Endpoint**: Menyediakan endpoint JSON di `/api/monitoring/metrics` untuk integrasi dashboard luar.
- **Proteksi API Token (Opsional)**: Dukungan pengamanan endpoint menggunakan API Token rahasia.

---

## 📋 Kebutuhan Sistem

- PHP: `^8.1`, `^8.2`, `^8.3`, atau `^8.4`
- Laravel: `10.x`, `11.x`, `12.x`, atau `13.x`

---

## 📦 Panduan Instalasi

Buka terminal di proyek Laravel target Anda, lalu jalankan:

```bash
composer require kikopola/monitoring-server
```

> **Catatan:** Berkat fitur *Laravel Package Auto-Discovery*, package ini akan terdaftar otomatis. Anda **tidak perlu** mengedit `config/app.php` atau `routes/web.php`.

---

## 💻 Cara Menggunakan

### 1. Pengecekan Cepat via Terminal (Artisan CLI)
Jalankan perintah berikut di terminal proyek Laravel Anda:

```bash
php artisan monitoring:check
```

Terminal akan langsung menampilkan:
- Banner ASCII Art **by kikopola**
- Tabel status CPU, RAM, Harddisk, Uptime, dan Database
- Deteksi otomatis link dashboard (Laragon / Artisan)

Opsi tambahan:
- `php artisan monitoring:check --open` : Langsung membuka browser ke dashboard web.

### 2. Menjalankan Dashboard Mandiri & Membuka Browser Otomatis
Jika web server utama belum dinyalakan, Anda bisa langsung menjalankan server monitoring mandiri:

```bash
php artisan monitoring:serve
```

Perintah ini akan:
- Menjalankan server lokal di `http://127.0.0.1:8989/monitoring`
- Otomatis membuka web browser Anda
- Menampilkan log akses real-time (tekan `Ctrl+C` untuk berhenti)

### 3. Membuka Dashboard Melalui Web Server yang Sedang Aktif
Jika Laragon sudah aktif (**Start All**):
```text
http://template_web_umkm.test/monitoring
```
Atau jika Anda menjalankan `php artisan serve`:
```text
http://localhost:8000/monitoring
```

### 3. Mengambil Data via API (JSON)
Endpoint untuk integrasi bot, dashboard luar, atau mobile app:

```http
GET /api/monitoring/metrics
Accept: application/json
```

---

## ⚙️ Kustomisasi Konfigurasi (Opsional)

Jika Anda ingin mengubah URL dashboard, prefix API, atau memasang token rahasia, publish file konfigurasinya dengan perintah:

```bash
php artisan vendor:publish --tag=monitoring-server-config
```

File konfigurasi akan tersedia di `config/monitoring-server.php`:

```php
return [
    'enabled' => env('MONITORING_SERVER_ENABLED', true),
    'dashboard_path' => env('MONITORING_SERVER_DASHBOARD_PATH', 'monitoring'),
    'api_prefix' => env('MONITORING_SERVER_API_PREFIX', 'api/monitoring'),
    'api_token' => env('MONITORING_SERVER_API_TOKEN', null),
    'middleware' => ['web'],
];
```

### Mengamankan Dashboard di Server Production
Di file `.env` server Anda, cukup tambahkan token rahasia:

```env
MONITORING_SERVER_API_TOKEN=kuncirahasia123
```
Buka dashboard dengan parameter: `http://domain-anda.com/monitoring?token=kuncirahasia123`

---

## 📄 Lisensi

Package ini dirilis di bawah lisensi [MIT License](LICENSE).

Dibuat dengan ❤️ oleh **bintang (kikopola)**.
