<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Contact extends Model
{

    use HasFactory;
    use SoftDeletes;

    protected $table = 'contacts';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        'contact_type_id',
        'contact_code',
        'source_type',
        'source_id',
        'company_name',
        'contact_name',
        'email',
        'phone',
        'address',
        'notes',
        'status',
        'created_by',
    ];

    /**
     * ── email & phone disimpan sebagai JSON array di kolom jsonb yang
     * sama (lihat migration change_contacts_email_phone_to_jsonb) supaya
     * satu contact STANDALONE bisa punya lebih dari 1 email/nomor telepon.
     * Cast 'array' bikin Eloquent otomatis encode saat disimpan & decode
     * jadi array PHP saat dibaca -- HANYA berlaku untuk baris contacts
     * milik model ini sendiri. Untuk contact LINKED, resolveSource() di
     * bawah TETAP mengembalikan email/phone sebagai string tunggal biasa
     * (diambil langsung dari kolom varchar milik customers/leads/dst,
     * yang TIDAK diubah jadi jsonb -- sesuai keputusan scope: multi-value
     * hanya untuk standalone). ──
     */
    protected $casts = [
        'email' => 'array',
        'phone' => 'array',
    ];

    /**
     * ── Daftar source_type yang valid untuk fitur "Link dari Data
     * Existing" (lihat AskUserQuestion waktu desain: customers, leads,
     * customer_contacts, branch_contacts). Dipakai di FormRequest &
     * Controller supaya whitelist-nya satu tempat saja -- kalau nanti
     * mau nambah source baru, cukup ubah di sini + resolveSource()
     * di bawah. ──
     */
    public const SOURCE_TYPES = [
        'customer',
        'lead',
        'customer_contact',
        'branch_contact',
    ];

    //opsional
    public function scopeOnlyDeleted(Builder $query, bool $only = false): Builder
    {
        return $only ? $query->onlyTrashed() : $query;
    }

    /**
     * ── Search sederhana, hanya menyentuh kolom milik baris contacts
     * sendiri (company_name/contact_name/email/phone/contact_code).
     * Ini otomatis mencakup semua contact STANDALONE.
     * Untuk contact LINKED, field-field itu sengaja NULL di baris ini
     * (datanya di tabel sumber) -- search yang mencakup linked contact
     * dilakukan lewat JOIN+COALESCE langsung di ContactController::index()
     * (bukan lewat scope ini), supaya bisa tetap 1 query & di-paginate
     * dengan benar tanpa N+1. ──
     */
    public function scopeSearch($query, $search)
    {
        if ($search) {
            return $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                    ->orWhere('contact_name', 'like', "%{$search}%")
                    // ── email sekarang jsonb (array), operator 'like' biasa
                    // tidak jalan di tipe jsonb -- di-cast dulu ke text (hasilnya
                    // representasi JSON-nya, mis. ["a@x.com","b@x.com"]) supaya
                    // search substring tetap bisa kena salah satu emailnya. ──
                    ->orWhereRaw('email::text ILIKE ?', ["%{$search}%"])
                    ->orWhere('contact_code', 'like', "%{$search}%");
            });
        }
        return $query;
    }

    // Scope untuk sorting dinamis
    public function scopeSort($query, $sortBy, $sortDir)
    {
        return $query->orderBy($sortBy ?? 'created_at', $sortDir ?? 'asc');
    }

    /**
     * Cek duplikat company_name, HANYA relevan untuk contact standalone
     * (source_type null) -- linked contact tidak divalidasi di sini
     * karena company_name-nya memang kosong (lihat unique index
     * contacts_company_name_unique_idx di migration, yang juga cuma
     * berlaku WHERE source_type IS NULL).
     */
    public static function isDuplicate(array $data, $id = null): array
    {
        $errors = [];

        $query = static::whereNull('source_type')
            ->where('contact_type_id', $data['contact_type_id'])
            ->whereRaw('LOWER(TRIM(company_name)) = ?', [strtolower(trim($data['company_name']))]);

        if ($id) {
            $query->where('id', '!=', $id);
        }

        if ($query->exists()) {
            $errors['company_name'] = ['Company name sudah terdaftar untuk jenis contact ini.'];
        }

        return $errors;
    }

    /**
     * ── Generate contact_code, mengikuti pola PERSIS
     * Costumers::generateCustomerCode() (hitung baris hari ini + 1,
     * tanpa lock/transaction khusus -- disamakan supaya konsisten,
     * meski secara teori masih punya race condition yang sama). Hanya
     * dipakai untuk contact STANDALONE -- contact hasil link dibiarkan
     * contact_code-nya null (lihat migration). ──
     */
    public static function generateContactCode(): string
    {
        $date = now()->format('Ymd');

        $countToday = DB::table('contacts')
            ->whereDate('created_at', now()->toDateString())
            ->count();

        $number = str_pad($countToday + 1, 3, '0', STR_PAD_LEFT);

        return "CT-{$date}-{$number}";
    }

    /**
     * ── Ambil data LIVE dari tabel sumber (customers/leads/
     * customer_contacts/branch_contacts) untuk contact yang LINKED.
     * Return null kalau contact ini standalone (source_type null) atau
     * baris sumbernya sudah tidak ada (mis. customer-nya dihapus).
     *
     * Sengaja pakai DB::table() manual (bukan Eloquent morphTo/relasi)
     * karena customer_contacts & branch_contacts di codebase ini belum
     * punya Eloquent Model -- konsisten dengan Costumers::class yang
     * juga murni pakai DB::table() untuk domain yang sama.
     *
     * Return array dengan key yang SUDAH DINORMALISASI supaya
     * ContactResources tidak perlu tahu bedanya field per source_type:
     * - display_name   : nama perusahaan/orang yang representatif
     * - contact_name   : nama PIC
     * - email, phone
     * - source_code    : kode di tabel asal (customer_code, dst), kalau ada
     * - parent_label   : konteks tambahan (mis. nama perusahaan induk
     *                    untuk source_type customer_contact/branch_contact)
     * ──
     */
    public function resolveSource(): ?array
    {
        if (!$this->source_type || !$this->source_id) {
            return null;
        }

        switch ($this->source_type) {

            case 'customer':
                $row = DB::table('customers')
                    ->where('id', $this->source_id)
                    ->whereNull('deleted_at')
                    ->first();

                if (!$row) {
                    return null;
                }

                return [
                    'display_name' => $row->company_name,
                    'contact_name' => $row->contact_name,
                    'email'        => $row->email,
                    'phone'        => $row->phone,
                    'address'      => $row->address,
                    'source_code'  => $row->customer_code,
                    'parent_label' => null,
                ];

            case 'lead':
                $row = DB::table('leads')
                    ->where('id', $this->source_id)
                    ->whereNull('deleted_at')
                    ->first();

                if (!$row) {
                    return null;
                }

                return [
                    'display_name' => $row->company_name,
                    'contact_name' => $row->contact_name,
                    'email'        => $row->email,
                    'phone'        => $row->phone,
                    'address'      => $row->address,
                    'source_code'  => null, // leads tidak punya kode unik
                    'parent_label' => null,
                ];

            case 'customer_contact':
                $row = DB::table('customer_contacts as cc')
                    ->leftJoin('customers as c', 'c.id', '=', 'cc.customer_id')
                    ->select('cc.*', 'c.company_name as parent_company_name', 'c.customer_code as parent_customer_code')
                    ->where('cc.id', $this->source_id)
                    ->whereNull('cc.deleted_at')
                    ->first();

                if (!$row) {
                    return null;
                }

                return [
                    // PIC-nya sendiri yang jadi "nama utama" contact ini
                    'display_name' => $row->name,
                    'contact_name' => $row->name,
                    'email'        => $row->email,
                    'phone'        => $row->phone,
                    'address'      => null,
                    'source_code'  => $row->parent_customer_code,
                    'parent_label' => $row->parent_company_name,
                ];

            case 'branch_contact':
                $row = DB::table('branch_contacts as bc')
                    ->leftJoin('customer_branches as cb', 'cb.id', '=', 'bc.branch_id')
                    ->select('bc.*', 'cb.branch_name as parent_branch_name', 'cb.branch_code as parent_branch_code')
                    ->where('bc.id', $this->source_id)
                    ->whereNull('bc.deleted_at')
                    ->first();

                if (!$row) {
                    return null;
                }

                return [
                    'display_name' => $row->name,
                    'contact_name' => $row->name,
                    'email'        => $row->email,
                    'phone'        => $row->phone,
                    'address'      => null,
                    'source_code'  => $row->parent_branch_code,
                    'parent_label' => $row->parent_branch_name,
                ];

            default:
                return null;
        }
    }

    public function contactType()
    {
        return $this->belongsTo(ContactType::class, 'contact_type_id');
    }
}