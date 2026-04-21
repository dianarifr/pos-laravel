<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PenjualanDetail extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'penjualan_id',
        'barang_id',
        'qty',
        'harga_jual',
        'diskon',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'qty'       => 'integer',
            'harga_jual' => 'decimal:2',
            'diskon'    => 'decimal:2',
            'subtotal'  => 'decimal:2',
        ];
    }

    public function penjualan(): BelongsTo
    {
        return $this->belongsTo(Penjualan::class, 'penjualan_id');
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }
}
