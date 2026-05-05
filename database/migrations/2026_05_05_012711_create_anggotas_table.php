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
        Schema::create('anggotas', function (Blueprint $table) {
            $table->id();
            $table->string('npk');
            $table->string('nama_lengkap');
            $table->enum('jk', ['L', 'P']);
            $table->string('tempat_lahir');
            $table->date('tanggal_lahir');
            $table->text('alamat');
            $table->string('pendidikan_terakhir');
            $table->string('email');
            $table->string('email_verified_at')->nullable();
            $table->string('no_whatsapp');
            $table->string('jenis_bank');
            $table->string('no_rekening');
            $table->string('nama_ahli_waris');
            $table->string('hubungan_ahli_waris');
            $table->string('hubungan_lainnya')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->boolean('join_date')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anggotas');
    }
};
