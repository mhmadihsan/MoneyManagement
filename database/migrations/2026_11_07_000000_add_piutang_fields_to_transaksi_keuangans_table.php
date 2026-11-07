<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi_keuangans', function (Blueprint $table) {
            $table->string('tipe', 20)->default('masuk')->change();
        });

        Schema::table('transaksi_keuangans', function (Blueprint $table) {
            $table->foreignId('piutang_id')->nullable()->after('kategori_id')
                ->constrained('transaksi_keuangans')->nullOnDelete();
            $table->boolean('is_bayar_piutang')->default(false)->after('piutang_id');
        });
    }

    public function down(): void
    {
        Schema::table('transaksi_keuangans', function (Blueprint $table) {
            $table->dropForeign(['piutang_id']);
            $table->dropColumn(['piutang_id', 'is_bayar_piutang']);
        });

        Schema::table('transaksi_keuangans', function (Blueprint $table) {
            $table->enum('tipe', ['masuk', 'keluar'])->change();
        });
    }
};
