<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penjualans', function (Blueprint $table) {
            $table->decimal('sisa_bayar', 15, 2)->default(0)->after('nominal_bayar');
        });

        // Isi nilai sisa_bayar untuk data existing berdasarkan status
        \Illuminate\Support\Facades\DB::statement("
            UPDATE penjualans
            SET sisa_bayar = GREATEST(0, total_harga - nominal_bayar)
            WHERE deleted_at IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('penjualans', function (Blueprint $table) {
            $table->dropColumn('sisa_bayar');
        });
    }
};
