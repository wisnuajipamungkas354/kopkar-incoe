<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       Schema::create('mutasi_kas_koperasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rekening_koperasi_id')
                ->constrained('rekening_koperasi')
                ->restrictOnDelete();
            $table->enum('jenis_transaksi', [
                'pemasukan',
                'pengeluaran',
            ]);

            $table->string('kategori_transaksi'); // payroll, pembiayaan, pinjaman, penarikan_saldo, ppob, toko, operasional, lazis, koreksi_saldo, migrasi_saldo

            $table->unsignedBigInteger('referensi_id')->nullable();
            $table->decimal('nominal', 15, 2);
            $table->enum('metode_transaksi', [
                'transfer',
                'cash',
                'payroll',
            ]);

            $table->text('keterangan')->nullable();
            $table->foreignId('diproses_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('tanggal_transaksi');
            $table->timestamps();

            $table->index([
                'kategori_transaksi',
                'referensi_id',
            ]);

            $table->index([
                'jenis_transaksi',
                'tanggal_transaksi',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mutasi_kas_koperasi');
    }
};
