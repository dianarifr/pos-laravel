<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barangs', function (Blueprint $table) {
            $table->integer('stok')->default(0)->after('stok_minimal');
        });

        // Recalculate stok from existing stoklogs
        DB::table('barangs')->get()->each(function (object $barang) {
            $stokIn  = DB::table('stoklogs')->where('barang_id', $barang->id)->where('tipe', 'in')->sum('qty');
            $stokOut = DB::table('stoklogs')->where('barang_id', $barang->id)->where('tipe', 'out')->sum('qty');
            $opname  = DB::table('stoklogs')->where('barang_id', $barang->id)->where('tipe', 'opname')->sum('qty');

            DB::table('barangs')->where('id', $barang->id)->update([
                'stok' => $stokIn - $stokOut + $opname,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('barangs', function (Blueprint $table) {
            $table->dropColumn('stok');
        });
    }
};
