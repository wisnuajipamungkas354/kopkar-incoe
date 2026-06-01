<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KoperasiMember;
use App\Models\PotonganPayrollEmployee;
use Carbon\Carbon;

class PotonganSimpananWajibSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $members = KoperasiMember::all();
        $now = Carbon::now();

        foreach ($members as $member) {
            PotonganPayrollEmployee::updateOrCreate(
                [
                    'employee_id' => $member->employee_id,
                    'jenis_potongan' => 'simpanan_wajib',
                ],
                [
                    'sub_jenis_potongan' => null,
                    'nominal' => 150000,
                    'tanggal_mulai_berlaku' => $now->copy()->startOfMonth(),
                    'tanggal_selesai' => null,
                ]
            );
        }
    }
}
