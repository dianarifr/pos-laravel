<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembelians', function (Blueprint $table) {
            $table->enum('status_pembayaran', ['tunai', 'kredit'])->default('tunai')->after('tanggal');
            $table->date('tanggal_jatuh_tempo')->nullable()->after('status_pembayaran');
        });

        Schema::table('pembelian_details', function (Blueprint $table) {
            $table->boolean('update_harga_jual_master')->default(false)->after('subtotal');
            $table->decimal('harga_jual_baru', 15, 2)->nullable()->after('update_harga_jual_master');
        });
    }

    public function down(): void
    {
        Schema::table('pembelian_details', function (Blueprint $table) {
            $table->dropColumn(['update_harga_jual_master', 'harga_jual_baru']);
        });

        Schema::table('pembelians', function (Blueprint $table) {
            $table->dropColumn(['status_pembayaran', 'tanggal_jatuh_tempo']);
        });
    }
};
