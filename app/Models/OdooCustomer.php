<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class OdooCustomer extends Model
{
    protected $fillable = [
        'odoo_partner_id',
        'name',
        'email',
        'phone',
        'mobile',
        'address',
        'is_company',
        'company_id',
        'has_purchased',
        'total_transaksi',
    ];

    protected $casts = [
        'is_company'    => 'boolean',
        'has_purchased' => 'boolean',
    ];

    public function purchaseItems()
    {
        return $this->hasMany(OdooCustomerPurchaseItem::class, 'odoo_customer_id', 'odoo_partner_id');
    }

    public function assignment()
    {
        return $this->hasOne(CustomerSalesAssignmentOdoo::class, 'odoo_customer_id', 'odoo_partner_id');
    }

    public function sales()
{
    return $this->hasOneThrough(
        MsUsers::class,
        CustomerSalesAssignmentOdoo::class,
        'odoo_customer_id',
        'id_user',
        'odoo_partner_id',
        'sales_id'
    );
}

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (!$search) {
            return $query;
        }

        $words = preg_split('/\s+/', trim($search));

        return $query->where(function (Builder $q) use ($words) {
            foreach ($words as $word) {
                $word = mb_strtolower($word);
                $q->where(function (Builder $sub) use ($word) {
                    $sub->whereRaw('LOWER(name) LIKE ?', ["%{$word}%"])
                        ->orWhereRaw('LOWER(email) LIKE ?', ["%{$word}%"])
                        ->orWhereRaw('LOWER(phone) LIKE ?', ["%{$word}%"])
                        ->orWhereRaw('LOWER(mobile) LIKE ?', ["%{$word}%"])
                        ->orWhereRaw('LOWER(address) LIKE ?', ["%{$word}%"]);
                });
            }
        });
    }

    public function scopeFilterPurchased(Builder $query, ?string $filter): Builder
    {
        if ($filter === 'has_purchased') {
            return $query->where('has_purchased', true);
        }

        return $query;
    }

    public function scopeSort(Builder $query, ?string $sortBy, ?string $sortDir): Builder
    {
        $sortBy  = $sortBy ?? 'name';
        $sortDir = $sortDir ?? 'asc';

        return $query
            ->orderByDesc('has_purchased')
            ->orderBy($sortBy, $sortDir);
    }

    // Tambahan tahap ini: filter customer berdasarkan sales yang pegang
    public function scopeFilterBySales(Builder $query, ?int $salesId): Builder
    {
        if (!$salesId) {
            return $query;
        }

        return $query->whereHas('assignment', function (Builder $q) use ($salesId) {
            $q->where('sales_id', $salesId);
        });
    }
}