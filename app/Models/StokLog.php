<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StokLog extends Model
{
    protected $table = 'stoklogs';

    public $timestamps = false;

    protected $fillable = [
        'barang_id',
        'user_id',
        'tipe',
        'qty',
        'ref_id',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tipe'       => 'string',
            'qty'        => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
