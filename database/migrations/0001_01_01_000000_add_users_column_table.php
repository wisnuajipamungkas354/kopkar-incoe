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
        Schema::table('users', function (Blueprint $table) {
            // Informasi pribadi tambahan
            $table->string('ext_tempat_lahir')->nullable()->after('tanggal_lahir');
            $table->text('ext_alamat')->nullable()->after('ext_tempat_lahir');
            $table->string('ext_pendidikan_terakhir')->nullable()->after('ext_alamat');

            // Informasi ahli waris
            $table->string('ext_nama_ahli_waris')->nullable()->after('ext_pendidikan_terakhir');
            $table->string('ext_hubungan_ahli_waris')->nullable()->after('ext_nama_ahli_waris');
            $table->string('ext_hubungan_lainnya')->nullable()->after('ext_hubungan_ahli_waris');

            // Status tambahan aplikasi baru
            $table->boolean('ext_is_approved')
                ->default(false)
                ->after('ext_hubungan_lainnya');

            // Fitur authentication Laravel
            $table->timestamp('email_verified_at')
                ->nullable()
                ->after('email');

            $table->rememberToken();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->dropColumn([
                'ext_tempat_lahir',
                'ext_alamat',
                'ext_pendidikan_terakhir',
                'ext_nama_ahli_waris',
                'ext_hubungan_ahli_waris',
                'ext_hubungan_lainnya',
                'ext_is_approved',
                'email_verified_at',
                'remember_token',
            ]);
        });
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
