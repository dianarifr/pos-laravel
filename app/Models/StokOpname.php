<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StokOpname extends Model
{
    const STATUS_DRAFT     = 'draft';
    const STATUS_VALIDATED = 'validated';

    protected $fillable = [
        'user_id',
        'tanggal',
        'keterangan',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'status'  => 'string',
        ];
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isValidated(): bool
    {
        return $this->status === self::STATUS_VALIDATED;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(StokOpnameDetail::class, 'stok_opname_id');
    }
}
