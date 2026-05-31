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
        Schema::create('pinjaman', function (Blueprint $table) {
            $table->id();

            $table->string('nomor_pengajuan')->unique();

            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();

            $table->string('jenis_pinjaman');

            $table->decimal('nominal_pengajuan', 15, 2);
            $table->decimal('nominal_disetujui', 15, 2)->nullable();
            $table->unsignedTinyInteger('tenor_bulan');

            $table->decimal('nominal_angsuran', 15, 2)->nullable();

            $table->string('no_rekening');
            $table->string('nama_bank');
            $table->string('nama_pemilik_rekening');

            $table->enum('status', [
                'diajukan',
                'diproses',
                'ditolak',
                'dibatalkan',
                'berjalan',
                'lunas',
            ])->default('diajukan');

            $table->date('tanggal_pencairan')->nullable();

            $table->string('diajukan_oleh')->nullable();
            $table->timestamp('diajukan_pada')->nullable();

            $table->string('diproses_oleh')->nullable();
            $table->timestamp('diproses_pada')->nullable();

            $table->string('dibatalkan_oleh')->nullable();
            $table->timestamp('dibatalkan_pada')->nullable();

            $table->string('ditolak_oleh')->nullable();
            $table->timestamp('ditolak_pada')->nullable();
            $table->text('alasan_penolakan')->nullable();

            $table->text('catatan')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pinjaman');
    }
};

