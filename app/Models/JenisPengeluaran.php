<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JenisPengeluaran extends Model
{
    protected $fillable = ['nama', 'deskripsi'];

    public function pengeluarans(): HasMany
    {
        return $this->hasMany(Pengeluaran::class, 'jenis_pengeluaran_id');
    }
}
