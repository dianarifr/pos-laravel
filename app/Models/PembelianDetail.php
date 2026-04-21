<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PembelianDetail extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'pembelian_id',
        'barang_id',
        'qty',
        'harga_beli',
        'diskon',
        'subtotal',
        'update_harga_jual_master',
        'harga_jual_baru',
    ];

    protected function casts(): array
    {
        return [
            'qty'       => 'integer',
            'harga_beli' => 'decimal:2',
            'diskon'    => 'decimal:2',
            'subtotal'  => 'decimal:2',
            'update_harga_jual_master' => 'boolean',
            'harga_jual_baru' => 'decimal:2',
        ];
    }

    public function pembelian(): BelongsTo
    {
        return $this->belongsTo(Pembelian::class, 'pembelian_id');
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }
}
