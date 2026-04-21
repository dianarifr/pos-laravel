<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('pembelians', 'nomor_nota_supplier')) {
            Schema::table('pembelians', function (Blueprint $table) {
                $table->dropColumn('nomor_nota_supplier');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('pembelians', 'nomor_nota_supplier')) {
            Schema::table('pembelians', function (Blueprint $table) {
                $table->string('nomor_nota_supplier')->nullable()->after('no_nota');
            });
        }
    }
};
