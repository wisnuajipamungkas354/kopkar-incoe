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
        Schema::create('rekening_koperasi', function (Blueprint $table) {
            $table->id();
            $table->string('nama_rekening');
            $table->string('kode_rekening');
            $table->string('nama_bank');
            $table->string('no_rekening')->nullable();
            $table->string('atas_nama')->nullable();
            $table->decimal('saldo_saat_ini', 15, 2)->default(0);
            $table->boolean('is_cash')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekening_koperasi');
    }
};
