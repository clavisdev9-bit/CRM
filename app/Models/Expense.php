<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'expenses';

    // Kategori expense SUDAH PINDAH ke tabel master expense_categories
    // (lihat model ExpenseCategory) supaya bisa dikelola tanpa deploy
    // ulang kode. Konstanta CATEGORIES yang dulu di sini sudah tidak
    // dipakai lagi -- kalau butuh daftar kategori aktif, query
    // ExpenseCategory::where('is_active', true)->pluck('name').

    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
    ];

    public const ODOO_PUSH_PUSHED = 'pushed';
    public const ODOO_PUSH_FAILED = 'failed';

    protected $fillable = [
        'sales_id',
        'customer_id',
        'location_name',
        'expense_date',
        'amount',
        'category',
        'description',
        'attachment',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'odoo_expense_id',
        'odoo_push_status',
        'odoo_push_error',
        'odoo_pushed_at',
        'created_by',
    ];

    protected $casts = [
        'expense_date'  => 'date',
        'amount'        => 'decimal:2',
        'approved_at'   => 'datetime',
        'odoo_pushed_at' => 'datetime',
    ];

    public function sales()
    {
        return $this->belongsTo(MsUsers::class, 'sales_id', 'id_user');
    }

    public function customer()
    {
        return $this->belongsTo(MsCustomers::class, 'customer_id', 'id');
    }

    public function approver()
    {
        return $this->belongsTo(MsUsers::class, 'approved_by', 'id_user');
    }

    public function creator()
    {
        return $this->belongsTo(MsUsers::class, 'created_by', 'id_user');
    }
}