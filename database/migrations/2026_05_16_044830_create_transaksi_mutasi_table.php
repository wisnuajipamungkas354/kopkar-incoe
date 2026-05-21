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
        // 1. TABEL PUSAT MUTASI KEUANGAN (Shared Ledger)
        // Menyimpan semua riwayat pergerakan dana keuangan koperasi
        Schema::create('transaksi_mutasi', function (Blueprint $table) {
            $table->id();
            // Menghubungkan ke tabel users existing (sistem lama)
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('nomor_transaksi')->unique(); // Contoh: TX-SMP-2026-0001, GP11560

            // Kategori utama transaksi finansial di koperasi
            $table->enum('kategori_transaksi', ['pokok', 'wajib', 'sukarela', 'shu', 'smp_lain_lain', 'lazis', 'ppob', 'pembiayaan', 'pinjaman']);

            // Jenis aksi mutasi dana
            $table->enum('jenis_transaksi', ['setoran_awal', 'payroll_rutin', 'setoran_tambahan', 'pencairan_dana', 'angsuran_bulanan']);

            // Metode perpindahan dana
            $table->enum('metode_pembayaran', ['payroll', 'qris', 'cash']);

            // Nominal transaksi (Untuk PPOB menyimpan total HPP + FEE, Penarikan SS total nominal + admin bank jika ada)
            $table->decimal('nominal', 15, 2);
            $table->enum('status_pembayaran', ['pending', 'success', 'failed'])->default('pending');

            // Log admin penanggung jawab jika metode pembayaran dilakukan lewat 'cash'
            $table->foreignId('admin_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->dateTime('tanggal_transaksi'); // Tanggal mutasi riil (bisa dari callback/excel mitra)
            $table->timestamps();
        });

        // 2. TABEL PENGATURAN SIMPANAN SUKARELA (Multi-User Approval Workflow)
        Schema::create('simpanan_sukarela_pengaturan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->decimal('nominal_rutin_saat_ini', 15, 2)->default(0);
            $table->decimal('nominal_baru_diajukan', 15, 2)->nullable();
            $table->enum('status_persetujuan', ['none', 'pending_approval', 'approved', 'rejected'])->default('none');
            $table->timestamps();
        });

        // 3. TABEL PENGATURAN LAZIS RUTIN (Real-time tanpa Approval)
        Schema::create('lazis_pengaturan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->decimal('nominal_rutin', 15, 2)->default(0);
            $table->timestamps();
        });

        // 4. TABEL PENGATURAN PPOB RUTIN (Potongan Payroll Utilitas)
        Schema::create('ppob_rutin_pengaturan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('jenis_layanan', ['listrik_pasca', 'internet', 'pdam', 'bpjs', 'lainnya']);
            $table->string('nomor_pelanggan');
            $table->decimal('nominal_maksimal_gaji', 15, 2)->nullable(); // Plafon potong gaji
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 5. TABEL METADATA DETAIL TAGIHAN PPOB
        // Kondisional: Terisi khusus untuk baris transaksi_mutasi berkategori 'ppob'
        Schema::create('ppob_detail_tagihan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_mutasi_id')->constrained('transaksi_mutasi')->cascadeOnDelete();
            $table->string('jenis_layanan'); // Diambil dari Detail Kategori Excel Mitra (PULSA, LISTRIK)
            $table->string('produk_vendor'); // Kode produk Mitra (IDTK1, IK10, PLH200)
            $table->string('nomor_pelanggan')->nullable(); // Terisi jika transaksi mandiri via web
            $table->decimal('hpp', 15, 2); // Harga Pokok dari Mitra Garuda
            $table->decimal('fee_koperasi', 15, 2); // Nilai keuntungan kotor koperasi
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ppob_detail_tagihan');
        Schema::dropIfExists('ppob_rutin_pengaturan');
        Schema::dropIfExists('lazis_pengaturan');
        Schema::dropIfExists('simpanan_sukarela_pengaturan');
        Schema::dropIfExists('transaksi_mutasi');
    }
};
