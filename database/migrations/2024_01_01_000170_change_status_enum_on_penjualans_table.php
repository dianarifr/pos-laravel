<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: tambah 'belum_lunas' ke enum dulu agar UPDATE tidak error
        DB::statement("ALTER TABLE penjualans MODIFY COLUMN status ENUM('lunas','hutang','belum_lunas') NOT NULL DEFAULT 'lunas'");

        // Step 2: migrate data lama 'hutang' → 'belum_lunas'
        DB::statement("UPDATE penjualans SET status = 'belum_lunas' WHERE status = 'hutang'");

        // Step 3: hapus 'hutang' dari enum
        DB::statement("ALTER TABLE penjualans MODIFY COLUMN status ENUM('lunas','belum_lunas') NOT NULL DEFAULT 'lunas'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE penjualans MODIFY COLUMN status ENUM('lunas','belum_lunas','hutang') NOT NULL DEFAULT 'lunas'");

        DB::statement("UPDATE penjualans SET status = 'hutang' WHERE status = 'belum_lunas'");

        DB::statement("ALTER TABLE penjualans MODIFY COLUMN status ENUM('lunas','hutang') NOT NULL DEFAULT 'lunas'");
    }
};
