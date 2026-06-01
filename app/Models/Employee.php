<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Employee extends Model
{
    protected $fillable = [
        'npk',
        'nama_lengkap',
        'jk',
        'tempat_lahir',
        'tanggal_lahir',
        'alamat',
        'no_telp',
        'pendidikan_terakhir',
        'seksi',
        'grade_category',
        'employment_status',
        'no_rekening',
        'nama_bank',
        'nama_pemilik_rekening',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function koperasiMember(): HasOne
    {
        return $this->hasOne(KoperasiMember::class);
    }

    public function koperasiManagements(): HasMany
    {
        return $this->hasMany(KoperasiManagement::class);
    }

    public function user(): MorphOne
    {
        return $this->morphOne(User::class, 'userable');
    }

    public function pengajuanPerubahanPotonganPayroll(): HasMany
    {
        return $this->hasMany(PengajuanPerubahanPotonganPayroll::class);
    }

    public function potonganPayrollEmployee(): HasMany
    {
        return $this->hasMany(PotonganPayrollEmployee::class);
    }

    public function tagihanPayrollEmployee(): HasMany
    {
        return $this->hasMany(TagihanPayrollEmployee::class);
    }

    public function pengaturanPpobEmployee(): HasOne
    {
        return $this->hasOne(PengaturanPpobEmployee::class);
    }

    public function mutasiSaldoMember(): HasMany
    {
        return $this->hasMany(MutasiSaldoMember::class);
    }

    public function getSisaTagihanAttribute()
    {
        $pinjamanBerjalan = \App\Models\Pinjaman::where('employee_id', $this->id)->where('status', 'berjalan')->get();
        $totalPinjaman = $pinjamanBerjalan->sum('nominal_disetujui');
        $pinjamanLunas = \App\Models\TagihanPayrollEmployee::where('employee_id', $this->id)
            ->where('jenis_tagihan', 'pinjaman')
            ->where('status', 'lunas')
            ->whereIn('tagihanable_id', $pinjamanBerjalan->pluck('id'))
            ->sum('nominal');
        $sisaPinjaman = $totalPinjaman - $pinjamanLunas;

        $pembiayaanBerjalan = \App\Models\Pembiayaan::where('employee_id', $this->id)->where('status', 'berjalan')->get();
        $totalPembiayaan = $pembiayaanBerjalan->sum('total_pembiayaan');
        $pembiayaanLunas = \App\Models\TagihanPayrollEmployee::where('employee_id', $this->id)
            ->where('jenis_tagihan', 'pembiayaan')
            ->where('status', 'lunas')
            ->whereIn('tagihanable_id', $pembiayaanBerjalan->pluck('id'))
            ->sum('nominal');
        $sisaPembiayaan = $totalPembiayaan - $pembiayaanLunas;

        return $sisaPinjaman + $sisaPembiayaan;
    }

    public function getPlafonAttribute()
    {
        $isAstra = $this->koperasiMember && !is_null($this->koperasiMember->join_koperasi_astra);
        return $isAstra ? 25000000 : 35000000;
    }

    public function getSisaPlafonAttribute()
    {
        return $this->plafon - $this->sisa_tagihan;
    }
}