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
        // /*
        // |--------------------------------------------------------------------------
        // | EMPLOYEES
        // |--------------------------------------------------------------------------
        // | Master seluruh karyawan perusahaan
        // */
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            $table->string('npk')->unique();
            $table->string('nama_lengkap');

            $table->enum('jk', ['L', 'P'])->nullable();

            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();

            $table->text('alamat')->nullable();
            $table->string('no_telp')->nullable();

            $table->string('pendidikan_terakhir')->nullable();

            $table->string('seksi')->nullable();
            $table->string('grade_category')->nullable();

            $table->string('employment_status')->nullable();
            
            $table->string('no_rekening')->nullable();
            $table->string('nama_bank')->nullable();
            $table->string('nama_pemilik_rekening')->nullable();

            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | KOPERASI MEMBERS
        |--------------------------------------------------------------------------
        | Anggota koperasi (subset dari employees)
        */
        Schema::create('koperasi_members', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();
            $table->string('member_number')->unique()->nullable();
            $table->boolean('is_koperasi_astra_member')->default(false);
            $table->date('join_koperasi_astra')->nullable();
            $table->date('join_date')->nullable();
            $table->date('leave_date')->nullable();

            $table->enum('status', [
                'pending',
                'active',
                'inactive',
                'rejected',
            ])->default('pending');

            $table->boolean('is_approved')->default(false);
            $table->timestamp('approved_at')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Ahli Waris
            |--------------------------------------------------------------------------
            */
            $table->string('nama_ahli_waris')->nullable();
            $table->string('hubungan_ahli_waris', 100)->nullable();
            $table->string('hubungan_lainnya')->nullable();

            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | KOPERASI MANAGEMENT
        |--------------------------------------------------------------------------
        | Pengurus koperasi dari employee perusahaan
        */
        Schema::create('koperasi_management', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();

            $table->string('jabatan');

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->enum('status', [
                'active',
                'inactive',
            ])->default('active');

            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | KOPERASI STAFF
        |--------------------------------------------------------------------------
        | Pegawai koperasi eksternal (bukan employee perusahaan)
        */
        Schema::create('koperasi_staff', function (Blueprint $table) {
            $table->id();
            $table->string('npk')->unique();
            $table->string('nama');

            $table->enum('jk', ['L', 'P'])->nullable();

            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();

            $table->text('alamat')->nullable();
            $table->string('no_telp')->nullable();

            $table->string('jabatan')->nullable();

            $table->date('hire_date')->nullable();
            $table->date('end_date')->nullable();

            $table->enum('employment_status', [
                'active',
                'inactive',
                'resign',
            ])->default('active');

            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | USERS
        |--------------------------------------------------------------------------
        | Authentication polymorphic
        |--------------------------------------------------------------------------
        | userable_type:
        | - App\Models\Employee
        | - App\Models\KoperasiStaff
        */
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->morphs('userable');

            $table->string('username')->unique();
            $table->string('email')->unique()->nullable();

            $table->timestamp('email_verified_at')->nullable();

            $table->string('password')->nullable();

            $table->rememberToken();

            $table->timestamps();
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
        Schema::dropIfExists('sessions');

        Schema::dropIfExists('users');

        Schema::dropIfExists('koperasi_staff');

        Schema::dropIfExists('koperasi_management');

        Schema::dropIfExists('koperasi_members');

        Schema::dropIfExists('employees');
    }
};