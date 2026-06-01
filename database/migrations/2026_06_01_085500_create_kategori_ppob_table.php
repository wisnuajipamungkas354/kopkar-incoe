<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kategori_ppob', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique(); // e.g. 'listrik', 'pdam'
            $table->string('nama'); // e.g. 'Listrik (PLN)'
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kategori_ppob');
    }
};
