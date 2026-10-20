# ⚡ Monitoring Server by kikopola

[![Latest Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com/kikopola/monitoring-server)
[![Software License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Laravel Compatibility](https://img.shields.io/badge/laravel-10.x%20|%2011.x%20|%2012.x%20|%2013.x-red.svg)](https://laravel.com)
[![PHP Version](https://img.shields.io/badge/php-%5E8.1%20|%20%5E8.2%20|%20%5E8.3%20|%20%5E8.4-purple.svg)](https://php.net)

Package pemantau server (*Server Health & Resource Monitor*) untuk aplikasi Laravel yang bekerja secara **Zero-Configuration (Plug and Play)**. Cukup install via Composer, dan langsung aktif tanpa perlu mengubah kode aplikasi Anda.

```text
  ███╗   ███╗ ██████╗ ███╗   ██╗██╗████████╗ ██████╗ ██████╗ ██╗███╗   ██╗ ██████╗ 
  ████╗ ████║██╔═══██╗████╗  ██║██║╚══██╔══╝██╔═══██╗██╔══██╗██║████╗  ██║██╔════╝ 
  ██╔████╔██║██║   ██║██╔██╗ ██║██║   ██║   ██║   ██║██████╔╝██║██╔██╗ ██║██║  ███╗
  ██║╚██╔╝██║██║   ██║██║╚██╗██║██║   ██║   ██║   ██║██╔══██╗██║██║╚██╗██║██║   ██║
  ██║ ╚═╝ ██║╚██████╔╝██║ ╚████║██║   ██║   ╚██████╔╝██║  ██║██║██║ ╚████║╚██████╔╝
  ╚═╝     ╚═╝ ╚═════╝ ╚═╝  ╚═══╝╚═╝   ╚═╝    ╚═════╝ ╚═╝  ╚═╝╚═╝╚═╝  ╚═══╝ ╚═════╝ 

                 ███████╗███████╗██████╗ ██╗   ██╗███████╗██████╗ 
                 ██╔════╝██╔════╝██╔══██╗██║   ██║██╔════╝██╔══██╗
                 ███████╗█████╗  ██████╔╝██║   ██║█████╗  ██╔══██╗
                 ╚════██║██╔══╝  ██╔══██╗╚██╗ ██╔╝██╔══╝  ██╔══██╗
                 ███████║███████╗██║  ██║ ╚████╔╝ ███████╗██║  ██║
                 ╚══════╝╚══════╝╚═╝  ╚═╝  ╚═══╝  ╚══════╝╚═╝  ╚═╝
               ⚡ MONITORING SERVER • by kikopola v1.0 ⚡
```

---

## 🚀 Fitur Utama

- **100% Data Hardware Asli**: Membaca CPU, RAM, Disk Space, dan Server Uptime riil langsung dari sistem operasi (Mendukung **Linux / Ubuntu Server** dan **Windows / Laragon**).
- **Cek Kesehatan Database**: Memantau koneksi database aktif, driver engine, dan latensi query (*ping response time* dalam milidetik).
- **CLI Interaktif & Real-Time Log Stream**: Perintah `php artisan monitoring:check` menampilkan banner ANSI Shadow dan tabel hardware satu kali, lalu langsung streaming aktivitas request HTTP server (seperti `php artisan serve`).
- **Dedicated Port Otomatis (8989)**: Otomatis menjalankan web server monitoring mandiri di port 8989 tanpa mengganggu web server utama.
- **Graceful Shutdown**: Tekan `Ctrl+C` di terminal untuk mematikan monitoring sekaligus server port 8989 secara bersih.
- **Web Dashboard Real-Time**: Halaman web modern bertema *Dark Mode* di `/monitoring` dengan *live polling* otomatis setiap 3 detik.
- **REST API Endpoint**: Menyediakan endpoint JSON di `/api/monitoring/metrics` untuk integrasi dashboard luar.
- **Proteksi API Token (Opsional)**: Pengamanan endpoint menggunakan token rahasia untuk server produksi.

---

## 📦 Panduan Instalasi

### Metode 1: Instalasi dari Folder Lokal (Path Repository)
Jika package berada di folder lokal komputer Anda (misalnya `../monitoring-server`):

1. Buka file `composer.json` di proyek Laravel target Anda, lalu tambahkan bagian `repositories`:
```json
"repositories": [
    {
        "type": "path",
        "url": "../monitoring-server"
    }
]
```

2. Jalankan perintah instalasi di terminal proyek Laravel:
```bash
composer require kikopola/monitoring-server:@dev
```

---

### Metode 2: Instalasi dari Repository GitHub (VCS)
Jika package di-upload ke GitHub Anda:

1. Buka file `composer.json` di proyek Laravel target:
```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/USERNAME/monitoring-server.git"
    }
]
```

2. Jalankan require:
```bash
composer require kikopola/monitoring-server:dev-main
```

---

### Metode 3: Instalasi dari Packagist (Composer Publik)
Jika package sudah didaftarkan di Packagist:

```bash
composer require kikopola/monitoring-server
```

> **Catatan:** Berkat fitur *Laravel Package Auto-Discovery*, package ini otomatis terdaftar tanpa perlu mengubah `config/app.php` atau `routes/web.php`.

---

## 💻 Cara Menggunakan

### 1. Pengecekan via Terminal & Live Request Stream (Artisan CLI)
Jalankan perintah berikut di terminal proyek Laravel Anda:

```bash
php artisan monitoring:check
```

Perintah ini akan:
- Menampilkan Banner ANSI Shadow **MONITORING SERVER**
- Menampilkan tabel kondisi hardware (CPU, RAM, Harddisk, Database, Uptime)
- Otomatis mengaktifkan live server port 8989
- Menampilkan link dashboard: `http://localhost:8989/monitoring`
- **Streaming log request aktivitas server secara real-time** (seperti `php artisan serve`)
- Tekan `Ctrl+C` untuk mematikan server dan keluar program.

Opsi tambahan:
- `php artisan monitoring:check --open` : Langsung membuka web browser menuju dashboard.
- `php artisan monitoring:check --once` : Menampilkan laporan metrik satu kali saja tanpa streaming loop.

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
