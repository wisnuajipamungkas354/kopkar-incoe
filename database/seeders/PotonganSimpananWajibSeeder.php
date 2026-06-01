<?php

namespace Database\Seeders;

use App\Models\KoperasiMember;
use App\Models\PotonganPayrollEmployee;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class PotonganSimpananWajibSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $members = KoperasiMember::where('status', 'active')->get();

        foreach ($members as $member) {
            PotonganPayrollEmployee::updateOrCreate(
                [
                    'employee_id'    => $member->employee_id,
                    'jenis_potongan' => 'simpanan_wajib',
                ],
                [
                    'nominal'               => 150000,
                    'tanggal_mulai_berlaku' => Carbon::now()->startOfMonth()->toDateString(),
                ]
            );
        }
    }
}
