# CRM & Attendance Management System

<p align="center">
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="300" alt="Laravel Logo">
</p>

## 📌 Tentang Project
Proyek ini adalah sistem **CRM (Customer Relationship Management)** yang dilengkapi dengan modul **Manajemen Kehadiran (Attendance)** tingkat lanjut. Sistem ini dirancang untuk memantau performa karyawan, aktivitas harian, serta absensi berbasis lokasi (Geofencing).

## 🚀 Fitur Utama
Sistem ini mencakup berbagai modul inti, di antaranya:

* **Attendance Tracking:** Absensi dengan koordinat GPS (Latitude & Longitude).
* **Geofencing Validation:** Memastikan absensi dilakukan dalam radius kantor yang diizinkan (`allowed_radius`).
* **Security & Validation:** Validasi alamat IP, tipe perangkat, dan bukti foto untuk mencegah kecurangan.
* **Employee Management:** Manajemen data karyawan dan integrasi role user.
* **Policy Monitoring:** Pelacakan status kebijakan (policy status) dan alasan keterlambatan/ketidakhadiran.

## 🛠️ Tech Stack
* **Framework:** [Laravel 11](https://laravel.com)
* **Database:** MySQL / PostgreSQL
* **ORM:** Eloquent
* **Language:** PHP 8.2+

## ⚙️ Instalasi

1. **Clone repository:**
   ```bash
   git clone [https://github.com/username/project-crm-kamu.git](https://github.com/username/project-crm-kamu.git)
   cd project-crm-kamu