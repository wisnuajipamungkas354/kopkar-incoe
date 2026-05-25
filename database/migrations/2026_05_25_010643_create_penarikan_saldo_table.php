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
       Schema::create('penarikan_saldo', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_pengajuan')->unique();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->decimal('total_penarikan', 15, 2);
            $table->string('no_rekening');
            $table->string('nama_bank'); //BCA, BRI, BNI, BTN
            $table->string('nama_pemilik_rekening');
            $table->enum('status', ['diajukan', 'disetujui', 'ditolak', 'diproses', 'selesai'])->default('diajukan');

            $table->foreignId('diajukan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diajukan_pada')->nullable();
            $table->foreignId('disetujui_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('disetujui_pada')->nullable();

            $table->foreignId('ditolak_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ditolak_pada')->nullable();
            $table->text('alasan_penolakan')->nullable();

            $table->foreignId('diproses_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diproses_pada')->nullable();

            $table->foreignId('diselesaikan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diselesaikan_pada')->nullable();
            $table->text('catatan')->nullable();

            $table->timestamps();
        });

        Schema::create('detail_penarikan_saldo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penarikan_saldo_id')->constrained('penarikan_saldo')->cascadeOnDelete();
            $table->string('sumber_saldo'); //simpanan pokok, simpanan wajib, simpanan sukarela
            $table->decimal('nominal', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penarikan_saldo');
    }
};
