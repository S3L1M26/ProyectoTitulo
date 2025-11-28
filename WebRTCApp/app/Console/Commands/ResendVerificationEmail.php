<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Console\Command;

class ResendVerificationEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:resend-verification {email : Email del usuario}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reenviar correo de verificación a un usuario (cualquier rol)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error('Usuario no encontrado.');
            return self::FAILURE;
        }

        if ($user->hasVerifiedEmail()) {
            $this->info('El usuario ya tiene el correo verificado.');
            return self::SUCCESS;
        }

        event(new Registered($user));
        $this->info("Correo de verificación reenviado a {$user->email}");

        return self::SUCCESS;
    }
}
