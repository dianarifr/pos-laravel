<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pengeluaran extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'jenis_pengeluaran_id',
        'user_id',
        'nama_pengeluaran',
        'nominal',
        'tanggal',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'nominal' => 'decimal:2',
            'tanggal' => 'date',
        ];
    }

    public function jenisPengeluaran(): BelongsTo
    {
        return $this->belongsTo(JenisPengeluaran::class, 'jenis_pengeluaran_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
