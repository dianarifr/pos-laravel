<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembelians', function (Blueprint $table) {
            $table->text('void_reason')->nullable()->after('tanggal_jatuh_tempo');
            $table->foreignId('void_by')->nullable()->after('void_reason')->constrained('users')->nullOnDelete();
            $table->softDeletes();
        });

        Schema::table('pembelian_details', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('pembelian_details', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('pembelians', function (Blueprint $table) {
            $table->dropConstrainedForeignId('void_by');
            $table->dropColumn('void_reason');
            $table->dropSoftDeletes();
        });
    }
};
