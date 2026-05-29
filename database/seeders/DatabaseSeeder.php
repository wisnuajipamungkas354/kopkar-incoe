<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\KoperasiManagement;
use App\Models\KoperasiMember;
use App\Models\KoperasiStaff;
use App\Models\NamaBank;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create nama_bank records
        $banks = [
            ['kode_bank' => 'CASH', 'nama_bank' => 'Cash'],
            ['kode_bank' => 'BCA', 'nama_bank' => 'Bank Central Asia'],
            ['kode_bank' => 'BRI', 'nama_bank' => 'Bank Rakyat Indonesia'],
            ['kode_bank' => 'BNI', 'nama_bank' => 'Bank Negara Indonesia'],
            ['kode_bank' => 'BTN', 'nama_bank' => 'Bank Tabungan Negara'],
            ['kode_bank' => 'BSI', 'nama_bank' => 'Bank Syariah Indonesia'],
            ['kode_bank' => 'BJB', 'nama_bank' => 'Bank Jawa Barat'],
            ['kode_bank' => 'MANDIRI', 'nama_bank' => 'Bank Mandiri'],
            ['kode_bank' => 'CIMB', 'nama_bank' => 'CIMB Niaga'],
            ['kode_bank' => 'PERMATA', 'nama_bank' => 'Bank Permata'],
            ['kode_bank' => 'DANAMON', 'nama_bank' => 'Bank Danamon'],
            ['kode_bank' => 'MAYBANK', 'nama_bank' => 'Maybank Indonesia'],
            ['kode_bank' => 'OCBC', 'nama_bank' => 'OCBC NISP'],
            ['kode_bank' => 'BUKOPIN', 'nama_bank' => 'Bank Bukopin'],
        ];

        NamaBank::insert($banks);

        /*
        |--------------------------------------------------------------------------
        | EMPLOYEES
        |--------------------------------------------------------------------------
        */
        $employees = [];

        for ($i = 1; $i <= 50; $i++) {

            $employee = Employee::create([
                'npk' => str_pad($i, 4, '0', STR_PAD_LEFT),

                'nama_lengkap' => fake()->name(),

                'jk' => fake()->randomElement(['L', 'P']),

                'tempat_lahir' => fake()->city(),

                'tanggal_lahir' => fake()->date(),

                'alamat' => fake()->address(),

                'no_telp' => fake()->phoneNumber(),

                'pendidikan_terakhir' => fake()->randomElement([
                    'SMA',
                    'D3',
                    'S1',
                    'S2',
                ]),

                'seksi' => fake()->randomElement([
                    'Produksi',
                    'Warehouse',
                    'QC',
                    'HR',
                    'IT',
                    'Finance',
                ]),

                'grade_category' => fake()->randomElement([
                    'A',
                    'B',
                    'C',
                ]),

                'employment_status' => fake()->randomElement([
                    'tetap',
                    'kontrak',
                ]),
            ]);

            $employees[] = $employee;
        }

        /*
        |--------------------------------------------------------------------------
        | KOPERASI MEMBERS
        |--------------------------------------------------------------------------
        | 35 employee menjadi anggota koperasi
        */
        $memberEmployees = collect($employees)->random(35);

        foreach ($memberEmployees as $index => $employee) {
            $employee->update([
                'no_rekening' => fake()->bankAccountNumber(),
                'nama_bank' => fake()->randomElement([
                    'BCA',
                    'BRI',
                    'BNI',
                    'Mandiri',
                    'CIMB',
                ]),
                'nama_pemilik_rekening' => $employee->nama_lengkap,
            ]);

            KoperasiMember::create([
                'employee_id' => $employee->id,
                'member_number' => 'M' . $employee->npk,
                'join_koperasi_astra' => now()->subMonths(rand(1, 60)),
                'join_date' => now()->subMonths(rand(1, 60)),
                'status' => 'active',
                'is_approved' => true,
                'approved_at' => now(),

                /*
                |--------------------------------------------------------------------------
                | Ahli Waris
                |--------------------------------------------------------------------------
                */
                'nama_ahli_waris' => fake()->name(),

                'hubungan_ahli_waris' => fake()->randomElement([
                    'suami_istri',
                    'anak',
                    'orang_tua',
                    'saudara',
                ]),
            ]);

            /*
            |--------------------------------------------------------------------------
            | USER LOGIN UNTUK MEMBER
            |--------------------------------------------------------------------------
            */
            User::create([
                'userable_id' => $employee->id,
                'userable_type' => Employee::class,

                'username' => $employee->npk,

                'email' => 'member' . $employee->npk . '@koperasi.test',

                'email_verified_at' => now(),

                'password' => Hash::make('password'),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | KOPERASI MANAGEMENT
        |--------------------------------------------------------------------------
        | 5 pengurus koperasi dari member
        */
        $managements = $memberEmployees->random(5);

        $positions = [
            'Ketua',
            'Wakil Ketua',
            'Bendahara',
            'Sekretaris',
            'Pengawas',
        ];

        foreach ($managements as $index => $employee) {

            KoperasiManagement::create([
                'employee_id' => $employee->id,

                'jabatan' => $positions[$index],

                'start_date' => now()->subYear(),

                'status' => 'active',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | KOPERASI STAFF
        |--------------------------------------------------------------------------
        | Pegawai koperasi eksternal
        */
        for ($i = 1; $i <= 10; $i++) {

            $staff = KoperasiStaff::create([
                'npk' => 'K' . str_pad($i, 3, '0', STR_PAD_LEFT),

                'nama' => fake()->name(),

                'jk' => fake()->randomElement(['L', 'P']),

                'tempat_lahir' => fake()->city(),

                'tanggal_lahir' => fake()->date(),

                'alamat' => fake()->address(),

                'no_telp' => fake()->phoneNumber(),

                'jabatan' => fake()->randomElement([
                    'Admin',
                    'Kasir',
                    'Accounting',
                    'Staff Operasional',
                ]),

                'hire_date' => now()->subMonths(rand(1, 36)),

                'employment_status' => 'active',
            ]);

            /*
            |--------------------------------------------------------------------------
            | USER LOGIN STAFF KOPERASI
            |--------------------------------------------------------------------------
            */
            User::create([
                'userable_id' => $staff->id,
                'userable_type' => KoperasiStaff::class,

                'username' => $staff->npk,

                'email' => 'staff' . $staff->npk . '@koperasi.test',

                'email_verified_at' => now(),

                'password' => Hash::make('password'),
            ]);
        }
    }
}