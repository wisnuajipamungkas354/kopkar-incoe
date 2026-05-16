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
        // Tabel Utama
        Schema::create('pengajuan_utama', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nomor_pengajuan')->unique(); // Contoh: FORM-KKI-SRY-001
            $table->date('tanggal_pengajuan');

            // Kolom Finansial Terakumulasi
            $table->decimal('total_pembiayaan_syariah', 15, 2)->default(0);
            $table->decimal('margin_terakumulasi', 15, 2)->default(0);
            $table->decimal('total_estimasi_nilai', 15, 2)->default(0); // Nominal Pengajuan Dana Bersih (Pembiayaan + Qard Hasan + Bon Sementara)

            $table->enum('status_approval', ['pending', 'diterima', 'ditolak'])->default('pending');
            $table->text('keterangan')->nullable();
            $table->boolean('approved_bendahara')->nullable();
            $table->dateTime('approved_bendahara_at')->nullable();
            $table->boolean('approved_ketua')->nullable();
            $table->dateTime('approved_ketua_at')->nullable();
            $table->timestamps();
        });

        // 2. TABEL PIVOT (Pusat Aturan Bisnis & Multi-Checklist)
        Schema::create('pengajuan_item_jenis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_utama_id')->constrained('pengajuan_utama')->cascadeOnDelete();
            $table->enum('kategori_utama', ['pembiayaan_syariah', 'pinjaman_qard_hasan', 'bon_sementara']);
            $table->string('sub_jenis'); // barang, pendidikan, kesehatan, renovasi, cash_qard, cash_bon, dll.
            $table->decimal('nominal_per_item', 15, 2);
            $table->integer('tenor_bulan')->nullable(); // Nullable khusus untuk Bon Sementara (1x potong)
            $table->text('deskripsi')->nullable(); // Kolom deskripsi
            $table->timestamps();
        });

        // 3. TABEL RINCIAN BARANG (Kondisional - Hanya Jika sub_jenis = 'barang')
        Schema::create('pembiayaan_rincian_barang', function (Blueprint $table) {
            $table->id();
            // Berelasi ke tabel pivot pengajuan_item_jenis demi kaidah normalisasi
            $table->foreignId('pengajuan_item_jenis_id')->constrained('pengajuan_item_jenis')->cascadeOnDelete();
            $table->string('nama_barang_jasa');
            $table->decimal('harga', 15, 2);
            $table->timestamps();
        });

        // 4. TABEL REFERENSI PIHAK KETIGA (Opsional/Pendukung Form)
        Schema::create('pembiayaan_referensi_pihak_ketiga', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_utama_id')->constrained('pengajuan_utama')->cascadeOnDelete();
            $table->string('nama_lembaga');
            $table->string('no_telp_wa');
            $table->text('alamat');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembiayaan_referensi_pihak_ketiga');
        Schema::dropIfExists('pembiayaan_rincian_barang');
        Schema::dropIfExists('pengajuan_item_jenis');
        Schema::dropIfExists('pengajuan_utama');
    }
};
