<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Barang extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'kategori_id',
        'unit_id',
        'sku',
        'nama_barang',
        'harga_beli',
        'harga_jual',
        'stok_minimal',
        'stok',
    ];

    protected function casts(): array
    {
        return [
            'harga_beli'   => 'decimal:2',
            'harga_jual'   => 'decimal:2',
            'stok_minimal' => 'integer',
            'stok'         => 'integer',
        ];
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function penjualanDetails(): HasMany
    {
        return $this->hasMany(PenjualanDetail::class, 'barang_id');
    }
}
