<?php
use Livewire\Component;
use Livewire\Layout;
use App\Models\User;

new #[Layout('layouts.app')] class extends Component
{
    public $email;

    public function mount()
    {
        $this->email = session('verification_email');
        if (!$this->email) {
            return redirect('/login');
        }
    }

    public function resend()
    {
        $user = User::where('email', $this->email)->first();
        if ($user && !$user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();

            session()->flash('status', 'Email verifikasi telah dikirim ulang. Silakan cek kotak masuk Anda.');
            
            $this->dispatch('email-resent');

        } elseif ($user && $user->hasVerifiedEmail()) {
            session()->flash('status', 'Email ini sudah diverifikasi.');
        }
    }
};
?>

<div class="relative w-full max-w-lg mx-auto mt-20 mb-20">
    <!-- Tombol Toggle Dark/Light Mode -->
    <div class="absolute -top-12 right-0">
        <flux:button variant="subtle" size="sm" x-data x-on:click="$flux.dark = ! $flux.dark" class="rounded-full !px-2">
            <flux:icon.sun x-show="$flux.appearance === 'light'" variant="mini" class="text-zinc-500 dark:text-white" />
            <flux:icon.moon x-show="$flux.appearance === 'dark'" variant="mini" class="text-zinc-500 dark:text-white" />
        </flux:button>
    </div>

    <div class="flex justify-center mb-6 mt-4">
        <img x-show="$flux.appearance === 'light'" src="{{ asset('img/kki-icon-2-light.png') }}" alt="Logo KKI" class="h-20 w-auto">
        <img x-show="$flux.appearance === 'dark'" src="{{ asset('img/kki-icon-2-dark.png') }}" alt="Logo KKI Dark" class="h-20 w-auto">
    </div>

    <flux:card class="text-center space-y-6">
        <div class="flex justify-center">
            <flux:icon.envelope class="w-16 h-16 text-blue-500" />
        </div>
        
        <flux:heading size="xl">Verifikasi Email Anda</flux:heading>
        
        <flux:text>
            Kami telah mengirimkan tautan verifikasi ke email <strong>{{ $email }}</strong>. 
            Silakan periksa kotak masuk atau folder spam Anda, lalu klik tautan tersebut untuk melanjutkan proses pendaftaran.
        </flux:text>

        @if (session('status'))
            <div class="bg-green-50 text-green-700 p-3 rounded-lg text-sm">
                {{ session('status') }}
            </div>
        @endif

        <div x-data="{ 
                cooldown: 60, 
                canResend: false,
                startTimer() {
                    this.canResend = false;
                    this.cooldown = 60;
                    let interval = setInterval(() => {
                        this.cooldown--;
                        if (this.cooldown <= 0) {
                            clearInterval(interval);
                            this.canResend = true;
                        }
                    }, 1000);
                }
            }" 
            x-init="startTimer()"
            @email-resent.window="startTimer()"
            class="pt-4 border-t border-zinc-200 dark:border-zinc-700"
        >
            <flux:text size="sm" class="mb-4">Tidak menerima email verifikasi?</flux:text>
            
            <!-- Tombol Disabled saat cooldown -->
            <flux:button 
                x-show="!canResend" 
                variant="subtle" 
                disabled
                class="w-full"
            >
                Kirim Ulang Email (<span x-text="cooldown"></span>s)
            </flux:button>

            <!-- Tombol Aktif setelah cooldown selesai -->
            <flux:button 
                x-show="canResend" 
                wire:click="resend" 
                variant="primary" 
                class="w-full"
                x-cloak
            >
                Kirim Ulang Email Verifikasi
            </flux:button>
        </div>

        <div class="mt-6">
            <flux:button href="{{ url('login') }}" wire:navigate variant="subtle" class="w-full">
                Kembali ke halaman Login
            </flux:button>
        </div>
    </flux:card>
</div>
