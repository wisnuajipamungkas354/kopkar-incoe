<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | PENGAJUAN PERUBAHAN POTONGAN PAYROLL
        |--------------------------------------------------------------------------
        */

        Schema::create('pengajuan_perubahan_potongan_payroll', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();

            $table->enum('jenis_potongan', [
                'simpanan_wajib',
                'simpanan_sukarela',
                'lazis',
            ]);

            $table->string('sub_jenis_potongan')->nullable();

            $table->decimal('nominal_lama', 15, 2)->default(0);
            $table->decimal('nominal_baru', 15, 2);

            $table->date('tanggal_berlaku');

            $table->enum('status', [
                'pending',
                'disetujui',
                'ditolak',
            ])->default('pending');

            $table->foreignId('diajukan_oleh')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('disetujui_oleh')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('tanggal_pengajuan')->nullable();
            $table->timestamp('tanggal_persetujuan')->nullable();

            $table->text('catatan')->nullable();

            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | POTONGAN PAYROLL employee (RECURRING)
        |--------------------------------------------------------------------------
        | Potongan rutin / tetap bulanan
        */

        Schema::create('potongan_payroll_employee', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();

            $table->enum('jenis_potongan', [
                'simpanan_wajib',
                'simpanan_sukarela',
                'lazis'
            ]);

            $table->string('sub_jenis_potongan')->nullable();

            $table->decimal('nominal', 15, 2);

            $table->date('tanggal_mulai_berlaku');

            $table->date('tanggal_selesai')->nullable();

            $table->foreignId('pengajuan_perubahan_id')
                ->nullable()
                ->constrained('pengajuan_perubahan_potongan_payroll')
                ->nullOnDelete();

            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | PENGATURAN PPOB employee
        |--------------------------------------------------------------------------
        */

        Schema::create('pengaturan_ppob_employee', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();

            $table->string('jenis_ppob'); // listrik, pdam, internet, dll

            $table->string('nomor_pelanggan');

            $table->boolean('aktif')->default(true);

            $table->timestamps();
        });

        
        /*
        |--------------------------------------------------------------------------
        | TAGIHAN PAYROLL employee
        |--------------------------------------------------------------------------
        | Semua tagihan payroll yang sifatnya transactional
        | dan tidak tetap nominalnya
        */

        Schema::create('tagihan_payroll_employee', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();

            $table->string('jenis_tagihan'); // pinjaman, pembiayaan, ppob, toko, operasional, lazis

            $table->string('tagihanable_type'); 
            $table->unsignedBigInteger('tagihanable_id'); 
            
            $table->unsignedTinyInteger('periode_bulan');
            $table->year('periode_tahun');

            // Payroll pemotongan
            $table->unsignedTinyInteger('periode_payroll_bulan');
            $table->year('periode_payroll_tahun');
            
            $table->decimal('nominal', 15, 2);

            $table->enum('status', [
                'pending',
                'masuk_payroll',
                'lunas',
            ])->default('pending');

            $table->text('keterangan')->nullable();

            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | PAYROLL employee
        |--------------------------------------------------------------------------
        */

        Schema::create('payroll_employee', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('bulan');
            $table->year('tahun');

            $table->decimal('total_potongan', 15, 2)->default(0);

            $table->decimal('total_gaji', 15, 2)->default(0);

            $table->decimal('total_gaji_bersih', 15, 2)->default(0);

            $table->enum('status', [
                'draft',
                'diproses',
                'selesai',
            ])->default('draft');

            $table->timestamp('tanggal_generate')->nullable();

            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | DETAIL PAYROLL employee
        |--------------------------------------------------------------------------
        */

        Schema::create('detail_payroll_employee', function (Blueprint $table) {
            $table->id();

            $table->foreignId('payroll_employee_id')
                ->constrained('payroll_employee')
                ->cascadeOnDelete();

            $table->enum('jenis_potongan', [
                'simpanan_pokok',
                'simpanan_wajib',
                'simpanan_sukarela',
                'lazis',
                'ppob',
                'pinjaman',
                'pembiayaan',
                'toko',
            ]);

            $table->string('sub_jenis_potongan')->nullable();

            $table->unsignedBigInteger('referensi_id')->nullable();

            $table->string('keterangan')->nullable();

            $table->decimal('nominal', 15, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_payroll_employee');
        Schema::dropIfExists('payroll_employee');
        Schema::dropIfExists('tagihan_payroll_employee');
        Schema::dropIfExists('pengaturan_ppob_employee');
        Schema::dropIfExists('potongan_payroll_employee');
        Schema::dropIfExists('pengajuan_perubahan_potongan_payroll');
    }
};