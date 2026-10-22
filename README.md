# ⚡ Monitoring Server by kikopola

[![Latest Version on Packagist](https://img.shields.io/packagist/v/kikopola/monitoring-server.svg?style=flat-square)](https://packagist.org/packages/kikopola/monitoring-server)
[![Total Downloads](https://img.shields.io/packagist/dt/kikopola/monitoring-server.svg?style=flat-square)](https://packagist.org/packages/kikopola/monitoring-server)
[![Software License](https://img.shields.io/badge/license-MIT-green.svg?style=flat-square)](LICENSE)
[![Laravel Compatibility](https://img.shields.io/badge/laravel-10.x%20|%2011.x%20|%2012.x%20|%2013.x-red.svg?style=flat-square)](https://laravel.com)
[![PHP Version](https://img.shields.io/badge/php-%5E8.1%20|%20%5E8.2%20|%20%5E8.3%20|%20%5E8.4-purple.svg?style=flat-square)](https://php.net)

**Monitoring Server by kikopola** adalah package pemantau kesehatan server (*Server Health & Resource Monitor*) untuk Laravel yang bekerja secara **Zero-Configuration (Plug and Play)**. Membaca data hardware asli (CPU, RAM, Harddisk, Database Latency, dan Uptime) secara real-time langsung dari sistem operasi tanpa perlu koding tambahan.

```diff
 ███╗  ███╗ ██████╗███╗ ██╗██╗█████╗ ██████╗██████╗ ██╗███╗ ██╗ ██████╗
 ████╗████║██╔═══██╗████╗██║██║╚═██╔╝██╔═══██╗██╔══██╗██║████╗██║██╔════╝
 ██╔███╔██║██║   ██║██╔████║██║  ██║ ██║   ██║██████╔╝██║██╔████║██║  ███╗
 ██║╚█╔╝██║██║   ██║██║╚███║██║  ██║ ██║   ██║██╔══██╗██║██║╚███║██║   ██║
 ██║ ╚═╝██║╚██████╔╝██║ ╚██║██║  ██║ ╚██████╔╝██║  ██║██║██║ ╚██║╚██████╔╝
 ╚═╝    ╚═╝ ╚═════╝ ╚═╝  ╚═╝╚═╝  ╚═╝  ╚═════╝ ╚═╝  ╚═╝╚═╝╚═╝  ╚═╝ ╚═════╝

-           ███████╗███████╗██████╗ ██╗   ██╗███████╗██████╗ 
-           ██╔════╝██╔════╝██╔══██╗██║   ██║██╔════╝██╔══██╗
-           ███████╗█████╗  ██████╔╝██║   ██║█████╗  ██╔══██╗
-           ╚════██║██╔══╝  ██╔══██╗╚██╗ ██╔╝██╔══╝  ██╔══██╗
-           ███████║███████╗██║  ██║ ╚████╔╝ ███████╗██║  ██║
-           ╚══════╝╚══════╝╚═╝  ╚═╝  ╚═══╝  ╚══════╝╚═╝  ╚═╝

            ⚡ MONITORING SERVER • by kikopola v1.0 ⚡
```

---

## 📦 Cara Install

Jalankan perintah ini di terminal proyek Laravel Anda:

```bash
composer require kikopola/monitoring-server
```

> **Catatan:** Package ini otomatis terdaftar (*Auto-Discovery*), langsung aktif dan siap digunakan seketika tanpa perlu konfigurasi manual apa pun.

---

## 🚀 Cara Penggunaan

Jalankan perintah monitoring di terminal proyek Laravel Anda:

```bash
php artisan monitoring:check
```

**Fitur:**
- Menampilkan tabel kondisi hardware riil (CPU load, RAM terpakai, Harddisk, Uptime, & status latensi Database).
- Otomatis mengaktifkan dedicated web server mandiri di **port 8989**.
- Menampilkan link dashboard web: `http://localhost:8989/monitoring`
- **Streaming log request aktivitas server secara real-time** (seperti `php artisan serve`).
- Tekan `Ctrl+C` untuk mematikan monitoring dan server dengan aman (*graceful shutdown*).

Opsi tambahan:
- `php artisan monitoring:check --open` : Langsung membuka web browser menuju dashboard.
- `php artisan monitoring:check --once` : Menampilkan ringkasan metrik 1 kali tanpa streaming server.

---

## 🔒 Keamanan & Proteksi Dashboard

Secara default, dashboard `/monitoring` dapat diakses langsung. Anda dapat mengamankannya dengan menambahkan opsi berikut pada file `.env` aplikasi Anda:

### 1. Form Login Mandiri (Password Khusus di `.env`) 🔑
Langsung menampilkan layar login dark-mode elegan saat `/monitoring` dibuka tanpa perlu registrasi atau database:
```env
MONITORING_PASSWORD=password-rahasia-anda
```
*(Opsional: Jika ingin kombinasi Username + Password:)*
```env
MONITORING_SERVER_USERNAME=admin
MONITORING_SERVER_PASSWORD=password-rahasia-anda
```

### 2. Wajib Login Akun Aplikasi (Redirect ke `/login` bawaan) 👤
```env
MONITORING_SERVER_REQUIRE_AUTH=true
```
*Siapapun yang belum login ke aplikasi Anda dan mencoba mengakses `/monitoring` akan otomatis dialihkan ke halaman `/login` aplikasi.*

### 3. Kunci dengan Token Rahasia / API Key 🛡️
```env
MONITORING_SERVER_API_TOKEN=rahasia-token-anda
```
*Akses dashboard dan API memerlukan token:* `https://domain-anda.com/monitoring?token=rahasia-token-anda`

---

## 📄 Lisensi

Package ini dirilis di bawah lisensi [MIT License](LICENSE).

Dibuat dengan ❤️ oleh **bintang (kikopola)**.
