<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quotation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'quotations';

    public const ODOO_PUSH_PUSHED = 'pushed';
    public const ODOO_PUSH_FAILED = 'failed';

    protected $fillable = [
        'sales_id',
        'customer_id',
        'customer_company_name',
        'customer_address',
        'customer_pic_name',
        'quotation_no',
        'customer_ref',
        'payment_terms',
        'quotation_date',
        'pages',
        'validity',
        'delivery_time',
        'term',
        'sub_total',
        'ppn',
        'net_amount',
        'signature',
        'odoo_sale_order_id',
        'odoo_push_status',
        'odoo_push_error',
        'odoo_pushed_at',
        'created_by',
    ];

    protected $casts = [
        'quotation_date'  => 'date',
        'sub_total'       => 'decimal:2',
        'ppn'             => 'decimal:2',
        'net_amount'      => 'decimal:2',
        'odoo_pushed_at'  => 'datetime',
    ];

    public function sales()
    {
        return $this->belongsTo(MsUsers::class, 'sales_id', 'id_user');
    }

    public function customer()
    {
        return $this->belongsTo(MsCustomers::class, 'customer_id', 'id');
    }

    public function creator()
    {
        return $this->belongsTo(MsUsers::class, 'created_by', 'id_user');
    }

    public function items()
    {
        return $this->hasMany(QuotationItem::class, 'quotation_id')->orderBy('sort_order');
    }

    /**
     * Hitung ulang sub_total & net_amount dari items yang ada, lalu
     * simpan ke kolom-nya sendiri (dipanggil setelah items disimpan --
     * lihat QuotationController::store()/update()). PPN TIDAK dihitung
     * di sini (nominal manual diisi sales, bukan hasil kalkulasi
     * persentase).
     */
    public function recalculateTotals(): void
    {
        $subTotal = $this->items()->sum('total');

        $this->update([
            'sub_total'  => $subTotal,
            'net_amount' => $subTotal + $this->ppn,
        ]);
    }
}