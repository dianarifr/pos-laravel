<?php

namespace App\Models;

use App\Enums\StatusPenjualan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Penjualan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_id',
        'user_id',
        'no_faktur',
        'total_harga',
        'nominal_bayar',
        'sisa_bayar',
        'status',
        'tanggal',
        'void_reason',
        'void_by',
    ];

    protected function casts(): array
    {
        return [
            'total_harga'   => 'decimal:2',
            'nominal_bayar' => 'decimal:2',
            'sisa_bayar'    => 'decimal:2',
            'status'        => StatusPenjualan::class,
            'tanggal'       => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'void_by');
    }

    public function details(): HasMany
    {
        // withTrashed agar detail tetap tampil saat penjualan di-void
        return $this->hasMany(PenjualanDetail::class, 'penjualan_id')->withTrashed();
    }
}
