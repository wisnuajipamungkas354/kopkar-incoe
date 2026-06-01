<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KategoriPpob;

class KategoriPpobSeeder extends Seeder
{
    public function run(): void
    {
        $kategori = [
            [
                'kode'  => 'listrik',
                'nama'  => 'Listrik (PLN)',
                'aktif' => true,
            ],
            [
                'kode'  => 'pdam',
                'nama'  => 'Air (PDAM)',
                'aktif' => true,
            ],
            [
                'kode'  => 'internet',
                'nama'  => 'Internet / WiFi',
                'aktif' => true,
            ],
            [
                'kode'  => 'bpjs',
                'nama'  => 'BPJS Kesehatan / Ketenagakerjaan',
                'aktif' => true,
            ],
            [
                'kode'  => 'tv',
                'nama'  => 'TV Kabel / Streaming',
                'aktif' => true,
            ],
            [
                'kode'  => 'lainnya',
                'nama'  => 'Lain-lain',
                'aktif' => true,
            ],
        ];

        foreach ($kategori as $kat) {
            KategoriPpob::updateOrCreate(
                ['kode' => $kat['kode']],
                $kat
            );
        }
    }
}
