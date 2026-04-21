<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pembelian extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'supplier_id',
        'user_id',
        'no_nota',
        'total_harga',
        'tanggal',
        'status_pembayaran',
        'tanggal_jatuh_tempo',
        'void_reason',
        'void_by',
    ];

    protected function casts(): array
    {
        return [
            'total_harga' => 'decimal:2',
            'tanggal'     => 'datetime',
            'status_pembayaran' => 'string',
            'tanggal_jatuh_tempo' => 'date',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(PembelianDetail::class, 'pembelian_id')->withTrashed();
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'void_by');
    }
}
