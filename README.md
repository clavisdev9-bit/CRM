# CRM Enterprise & Advanced Attendance System

<p align="center">
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="300" alt="Laravel Logo">
  <img src="https://vuejs.org/images/logo.png" width="80" alt="Vue Logo" style="margin-left: 20px;">
</p>

## 📌 Project Overview
Sistem CRM ini dirancang dengan arsitektur modern yang menggabungkan stabilitas **Monolith** untuk core bisnis dan fleksibilitas **Microservices** untuk modul-modul spesifik yang membutuhkan skalabilitas tinggi (seperti log kehadiran massal atau pemrosesan data GPS).

## 🛠 Tech Stack
| Layer | Technology |
| :--- | :--- |
| **Backend** | Laravel 12 (PHP 8.3+) |
| **Frontend** | Vue 3 (Composition API) |
| **Database** | PostgreSQL (Optimized for JSONB & GIS) |
| **ORM** | Hybrid: Eloquent (Business Logic) & Query Builder (High-Perf Analytics) |
| **Architecture** | Hybrid Monolith & Microservices |

## 🏗 Architecture Concept
Project ini menggunakan pendekatan **Hybrid Architecture**:
* **Monolith Core:** Menangani manajemen User, Role, dan Core Business Logic untuk konsistensi data.
* **Microservices Ready:** Modul seperti *Attendance Tracking* dan *Real-time Analytics* dirancang agar dapat dideploy secara terpisah jika beban traffic meningkat.
* **PostgreSQL Power:** Memanfaatkan fitur *indexing* lanjut dan *geospatial data* untuk menghitung `distance_from_office` secara akurat pada level database.

## 🚀 Key Features
* **Vue 3 SPA Interface:** User experience yang cepat dan reaktif.
* **Advanced Attendance:** Geofencing, validasi IP, dan tracking perangkat menggunakan PostgreSQL GIS.
* **Hybrid Data Processing:** Menggunakan **Eloquent** untuk kemudahan maintain kode, dan **Query Builder** untuk *heavy-duty reporting* agar tetap ringan.
* **API-First Design:** Siap diintegrasikan dengan aplikasi mobile atau sistem pihak ketiga.

## ⚙️ Development Setup

1. **Prerequisites:**
   - PHP 8.3+
   - Composer 2.x
   - Node.js & NPM
   - PostgreSQL 15+

2. **Clone & Install:**
   ```bash
   git clone [https://github.com/username/crm-enterprise.git](https://github.com/username/crm-enterprise.git)
   cd crm-enterprise
   composer install
   npm install

2. **Environment Setup:**
   ```bash
   cp .env.example .env
   php artisan key:generate

2. **Database & Build:**

     ```bash
     php artisan migrate
     npm run dev