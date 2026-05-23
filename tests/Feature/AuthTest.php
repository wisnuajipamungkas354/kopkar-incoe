<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\KoperasiManagement;
use App\Models\KoperasiMember;
use App\Models\KoperasiStaff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_koperasi_staff_can_login_and_redirects_to_admin()
    {
        $staff = KoperasiStaff::create([
            'npk' => 'K001',
            'nama' => 'Staff Aktif',
            'jk' => 'L',
            'tempat_lahir' => 'Jakarta',
            'tanggal_lahir' => '1990-01-01',
            'alamat' => 'Jakarta',
            'no_telp' => '08123456789',
            'jabatan' => 'Admin',
            'hire_date' => '2023-01-01',
            'employment_status' => 'active',
        ]);

        $user = User::create([
            'userable_id' => $staff->id,
            'userable_type' => KoperasiStaff::class,
            'username' => 'staff1',
            'email' => 'staff1@koperasi.test',
            'password' => Hash::make('password123'),
        ]);

        Volt::test('pages::auth.login')
            ->set('username', 'staff1')
            ->set('password', 'password123')
            ->call('login')
            ->assertRedirect('admin/');

        $this->assertAuthenticatedAs($user);
    }

    public function test_inactive_koperasi_staff_cannot_login()
    {
        $staff = KoperasiStaff::create([
            'npk' => 'K002',
            'nama' => 'Staff Inaktif',
            'jk' => 'L',
            'tempat_lahir' => 'Jakarta',
            'tanggal_lahir' => '1990-01-01',
            'alamat' => 'Jakarta',
            'no_telp' => '08123456789',
            'jabatan' => 'Admin',
            'hire_date' => '2023-01-01',
            'employment_status' => 'inactive',
        ]);

        User::create([
            'userable_id' => $staff->id,
            'userable_type' => KoperasiStaff::class,
            'username' => 'staff2',
            'email' => 'staff2@koperasi.test',
            'password' => Hash::make('password123'),
        ]);

        Volt::test('pages::auth.login')
            ->set('username', 'staff2')
            ->set('password', 'password123')
            ->call('login')
            ->assertHasErrors(['username' => 'Akun anda tidak aktif.']);

        $this->assertGuest();
    }

    public function test_active_koperasi_management_can_login_and_redirects_to_admin()
    {
        $employee = Employee::create([
            'npk' => 'E001',
            'nama_lengkap' => 'Management Aktif',
            'jk' => 'L',
            'tempat_lahir' => 'Jakarta',
            'tanggal_lahir' => '1990-01-01',
            'alamat' => 'Jakarta',
            'no_telp' => '08123456789',
            'pendidikan_terakhir' => 'S1',
            'seksi' => 'IT',
            'grade_category' => 'A',
            'employment_status' => 'tetap',
        ]);

        KoperasiManagement::create([
            'employee_id' => $employee->id,
            'jabatan' => 'Ketua',
            'start_date' => '2023-01-01',
            'status' => 'active',
        ]);

        $user = User::create([
            'userable_id' => $employee->id,
            'userable_type' => Employee::class,
            'username' => 'manage1',
            'email' => 'manage1@koperasi.test',
            'password' => Hash::make('password123'),
        ]);

        Volt::test('pages::auth.login')
            ->set('username', 'manage1')
            ->set('password', 'password123')
            ->call('login')
            ->assertRedirect('admin/');

        $this->assertAuthenticatedAs($user);
    }

    public function test_active_and_approved_member_can_login_and_redirects_to_anggota()
    {
        $employee = Employee::create([
            'npk' => 'E002',
            'nama_lengkap' => 'Member Aktif',
            'jk' => 'L',
            'tempat_lahir' => 'Jakarta',
            'tanggal_lahir' => '1990-01-01',
            'alamat' => 'Jakarta',
            'no_telp' => '08123456789',
            'pendidikan_terakhir' => 'S1',
            'seksi' => 'IT',
            'grade_category' => 'A',
            'employment_status' => 'tetap',
        ]);

        KoperasiMember::create([
            'employee_id' => $employee->id,
            'member_number' => 'AGT0001',
            'status' => 'active',
            'is_approved' => true,
            'approved_at' => now(),
        ]);

        $user = User::create([
            'userable_id' => $employee->id,
            'userable_type' => Employee::class,
            'username' => 'member1',
            'email' => 'member1@koperasi.test',
            'password' => Hash::make('password123'),
        ]);

        Volt::test('pages::auth.login')
            ->set('username', 'member1')
            ->set('password', 'password123')
            ->call('login')
            ->assertRedirect('anggota/');

        $this->assertAuthenticatedAs($user);
    }

    public function test_pending_member_cannot_login()
    {
        $employee = Employee::create([
            'npk' => 'E003',
            'nama_lengkap' => 'Member Pending',
            'jk' => 'L',
            'tempat_lahir' => 'Jakarta',
            'tanggal_lahir' => '1990-01-01',
            'alamat' => 'Jakarta',
            'no_telp' => '08123456789',
            'pendidikan_terakhir' => 'S1',
            'seksi' => 'IT',
            'grade_category' => 'A',
            'employment_status' => 'tetap',
        ]);

        KoperasiMember::create([
            'employee_id' => $employee->id,
            'member_number' => 'AGT0002',
            'status' => 'pending',
            'is_approved' => false,
        ]);

        User::create([
            'userable_id' => $employee->id,
            'userable_type' => Employee::class,
            'username' => 'member2',
            'email' => 'member2@koperasi.test',
            'password' => Hash::make('password123'),
        ]);

        Volt::test('pages::auth.login')
            ->set('username', 'member2')
            ->set('password', 'password123')
            ->call('login')
            ->assertHasErrors(['username' => 'Akun anda belum di approve oleh ketua.']);

        $this->assertGuest();
    }

    public function test_inactive_member_cannot_login()
    {
        $employee = Employee::create([
            'npk' => 'E004',
            'nama_lengkap' => 'Member Inaktif',
            'jk' => 'L',
            'tempat_lahir' => 'Jakarta',
            'tanggal_lahir' => '1990-01-01',
            'alamat' => 'Jakarta',
            'no_telp' => '08123456789',
            'pendidikan_terakhir' => 'S1',
            'seksi' => 'IT',
            'grade_category' => 'A',
            'employment_status' => 'tetap',
        ]);

        KoperasiMember::create([
            'employee_id' => $employee->id,
            'member_number' => 'AGT0003',
            'status' => 'inactive',
            'is_approved' => true,
        ]);

        User::create([
            'userable_id' => $employee->id,
            'userable_type' => Employee::class,
            'username' => 'member3',
            'email' => 'member3@koperasi.test',
            'password' => Hash::make('password123'),
        ]);

        Volt::test('pages::auth.login')
            ->set('username', 'member3')
            ->set('password', 'password123')
            ->call('login')
            ->assertHasErrors(['username' => 'Akun anda tidak aktif.']);

        $this->assertGuest();
    }
}
