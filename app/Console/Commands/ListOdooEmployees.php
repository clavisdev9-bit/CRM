<?php

namespace App\Console\Commands;

use App\Services\OdooService;
use Illuminate\Console\Command;

/**
 * Command bantuan buat ambil daftar employee di Odoo (hr.employee) --
 * dipakai buat cocokkan nama SALES di CRM (ms_users.fullname) dengan
 * nama EMPLOYEE di Odoo, karena resolveOdooEmployeeId() di
 * ExpenseController mencocokkan pakai EXACT match nama (bukan ILIKE
 * kayak kategori->product), jadi harus sama persis biar auto-match
 * jalan.
 *
 * Kalau nama di CRM & Odoo memang beda (misal CRM cuma nama panggilan
 * "Nana", Odoo pakai nama lengkap), auto-match TIDAK akan pernah
 * ketemu -- solusinya isi manual kolom ms_users.odoo_employee_id +
 * odoo_employee_name pakai ID yang didapat dari command ini (lihat
 * contoh query UPDATE di akhir output).
 *
 * Cara pakai:
 *   php artisan expense:list-odoo-employees
 *   php artisan expense:list-odoo-employees --search=nana   (filter nama)
 */
class ListOdooEmployees extends Command
{
    protected $signature = 'expense:list-odoo-employees {--search= : Filter nama employee (ILIKE)}';

    protected $description = 'Tampilkan daftar employee di Odoo (hr.employee) -- buat dicocokkan dengan fullname sales di ms_users';

    public function handle(OdooService $odooService)
    {
        $search = $this->option('search');

        $domain = [];
        if ($search) {
            $domain[] = ['name', 'ilike', $search];
        }

        $this->info('Menghubungi Odoo...');

        try {
            $employees = $odooService->searchRead(
                'hr.employee',
                $domain,
                ['id', 'name', 'work_email'],
                0,
                'name asc'
            );
        } catch (\Throwable $e) {
            $this->error('Gagal ambil data dari Odoo: ' . $e->getMessage());

            return self::FAILURE;
        }

        if (empty($employees)) {
            $this->warn('Tidak ada employee ditemukan' . ($search ? " untuk pencarian \"{$search}\"." : '.'));

            return self::SUCCESS;
        }

        $this->info('Ditemukan ' . count($employees) . ' employee di Odoo:');
        $this->newLine();

        $rows = [];
        foreach ($employees as $e) {
            $rows[] = [$e['id'], $e['name'], $e['work_email'] ?? '-'];
        }
        $this->table(['Odoo Employee ID', 'Nama', 'Email'], $rows);

        $this->newLine();
        $this->info('Kalau nama di atas TIDAK PERSIS SAMA dengan fullname sales di ms_users, auto-match tidak akan pernah ketemu.');
        $this->info('Solusi cepat: isi manual mapping-nya lewat SQL, contoh (ganti ID_USER_SALES & ID_EMPLOYEE_ODOO & NAMA_DI_ODOO):');
        $this->newLine();
        $this->line("UPDATE ms_users SET odoo_employee_id = ID_EMPLOYEE_ODOO, odoo_employee_name = 'NAMA_DI_ODOO' WHERE id_user = ID_USER_SALES;");

        return self::SUCCESS;
    }
}