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
        Schema::create('transaksi_mutasi_qris', function (Blueprint $table) {
            $table->id();
            // Relasi One-to-One ke tabel transaksi_mutasi induk
            $table->foreignId('transaksi_mutasi_id')
                ->constrained('transaksi_mutasi')
                ->cascadeOnDelete();

            $table->string('url_image_qris'); // Menyimpan path/url gambar QRIS
            $table->string('transaction_id_vendor')->nullable(); // Opsional: ID transaksi dari Midtrans/Xendit jika pakai gateway

            // --- KOLOM UNTUK BEBAN BIAYA ANGGOTA ---
            $table->decimal('fee_aplikasi_diwajibkan', 10, 2)->default(0); // Rp 3.500 (Beban ke Anggota)
            $table->decimal('total_bayar_anggota', 10, 2)->default(0);     // Rp 503.500 (Nominal QRIS asli)
            $table->decimal('fee_vendor', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi_mutasi_qris');
    }
};
