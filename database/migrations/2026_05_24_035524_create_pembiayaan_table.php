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
        Schema::create('pembiayaan', function (Blueprint $table) {
            $table->id();

            $table->string('nomor_pengajuan')->unique();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();

            $table->string('kategori_pembiayaan');

            $table->text('tujuan_pembiayaan');
            $table->json('rincian_barang')->nullable();

            $table->decimal('nominal_pengajuan', 15, 2);
            $table->decimal('nominal_disetujui', 15, 2)
                ->nullable();
            $table->unsignedTinyInteger('tenor_bulan');
            $table->decimal('margin_persen', 5, 2)
                ->default(8.5);
            $table->decimal('total_margin', 15, 2)
                ->nullable();
            $table->decimal('total_pembiayaan', 15, 2)
                ->nullable();
            $table->decimal('nominal_angsuran', 15, 2)
                ->nullable();

            $table->enum('pencairan_dana_ke', [
                'pihak_ketiga',
                'anggota',
            ])->nullable();

            $table->string('nama_pihak_ketiga')
                ->nullable();

            $table->string('no_telp_pihak_ketiga')
                ->nullable();

            $table->text('alamat_pihak_ketiga')
                ->nullable();

            $table->string('no_rekening');
            $table->string('nama_bank');
            $table->string('nama_pemilik_rekening');

            $table->enum('status', [
                'draft',
                'diajukan',
                'disetujui_bendahara',
                'disetujui_ketua',
                'ditolak',
                'dicairkan',
                'berjalan',
                'lunas',
            ])->default('draft');

            $table->text('catatan')
                ->nullable();

            $table->timestamp('diajukan_pada')
                ->nullable();
            $table->foreignId('diajukan_oleh')
                ->nullable()
                ->constrained('users')->nullOnDelete();

            $table->foreignId('disetujui_bendahara_oleh')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('disetujui_bendahara_pada')
                ->nullable();

            $table->foreignId('disetujui_ketua_oleh')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('disetujui_ketua_pada')
                ->nullable();

            $table->foreignId('ditolak_oleh')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('ditolak_pada')
                ->nullable();

            $table->text('alasan_penolakan')
                ->nullable();

            $table->foreignId('diproses_oleh')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('diproses_pada')
                ->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembiayaan');
    }
};
