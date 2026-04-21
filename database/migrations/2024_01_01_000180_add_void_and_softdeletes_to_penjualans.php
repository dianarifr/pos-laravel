<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penjualans', function (Blueprint $table) {
            $table->text('void_reason')->nullable()->after('status');
            $table->foreignId('void_by')->nullable()->constrained('users')->nullOnDelete()->after('void_reason');
            $table->softDeletes()->after('void_by');
        });

        Schema::table('penjualan_details', function (Blueprint $table) {
            $table->softDeletes()->after('subtotal');
        });
    }

    public function down(): void
    {
        Schema::table('penjualan_details', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('penjualans', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropConstrainedForeignId('void_by');
            $table->dropColumn('void_reason');
        });
    }
};
