<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create
                            {--email= : Email del administrador}
                            {--name= : Nombre a mostrar}
                            {--password= : Contraseña (no recomendado pasarlo por flag)}
                            {--no-verify : No enviar correo de verificación}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crear un usuario administrador con verificación por correo';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->option('name') ?? $this->ask('Nombre', 'Administrador');
        $email = $this->option('email') ?? $this->ask('Email');

        if (! $email) {
            $this->error('Debes ingresar un email válido.');
            return self::FAILURE;
        }

        if (User::where('email', $email)->exists()) {
            $this->error('Ya existe un usuario con ese email.');
            return self::FAILURE;
        }

        $password = $this->option('password');
        if (! $password) {
            $password = $this->secret('Contraseña (no se mostrará)');
            $confirm = $this->secret('Confirmar contraseña');

            if ($password !== $confirm) {
                $this->error('Las contraseñas no coinciden.');
                return self::FAILURE;
            }
        }

        $validator = Validator::make(
            [
                'name' => $name,
                'email' => $email,
                'password' => $password,
            ],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', Rule::unique(User::class, 'email')],
                'password' => ['required', Password::defaults()],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'admin',
            'email_verified_at' => null,
        ]);

        $this->info("Administrador creado: {$user->email} (pendiente de verificación)");

        return self::SUCCESS;
    }
}
