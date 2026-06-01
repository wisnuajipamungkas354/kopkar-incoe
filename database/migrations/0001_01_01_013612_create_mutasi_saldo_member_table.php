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
       Schema::create('mutasi_saldo_member', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();
            $table->enum('jenis_saldo', [
                'simpanan_pokok',
                'simpanan_wajib',
                'simpanan_sukarela',
                'simpanan_lain_lain',
                'shu',
            ]);

            $table->enum('jenis_mutasi', [
                'kredit',
                'debit',
            ]);

            $table->enum('sumber_transaksi', [
                'payroll',
                'penarikan_saldo',
                'pembagian_shu',
                'koreksi_admin',
                'migrasi_saldo',
            ]);

            $table->unsignedBigInteger('referensi_id')->nullable();
            $table->decimal('nominal', 15, 2);
            $table->decimal('saldo_sebelum', 15, 2);
            $table->decimal('saldo_sesudah', 15, 2);
            $table->text('keterangan')->nullable();

            $table->string('diproses_oleh')->nullable();
            $table->timestamps();

            $table->index([
                'employee_id',
                'jenis_saldo',
            ]);

            $table->index([
                'sumber_transaksi',
                'referensi_id',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mutasi_saldo_member');
    }
};
