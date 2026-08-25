<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Crea el usuario administrador (super admin) del sistema.
 *
 * El registro público está deshabilitado y los usuarios se crean desde la
 * interfaz de admin (UsuarioController), que solo permite roles
 * supervisor/capturista. Para arrancar el sistema se necesita un primer
 * usuario con rol "admin" (acceso implícito a todos los módulos), con email
 * verificado (las rutas protegidas exigen el middleware "verified").
 */
class CrearAdmin extends Command
{
    protected $signature = 'usuarios:crear-admin';

    protected $description = 'Crea el usuario administrador (super admin) del sistema';

    public function handle(): int
    {
        if (User::where('rol', 'admin')->exists()) {
            $this->error('Ya existe un usuario con rol admin. No se crea otro.');

            return self::FAILURE;
        }

        $name = $this->ask('Nombre del administrador');
        $email = $this->ask('Email');
        $password = $this->secret('Contraseña');
        $confirmacion = $this->secret('Repite la contraseña');

        if ($password !== $confirmacion) {
            $this->error('Las contraseñas no coinciden.');

            return self::FAILURE;
        }

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::default()],
        ]);

        try {
            $validated = $validator->validate();
        } catch (ValidationException $e) {
            foreach ($e->errors() as $campo => $mensajes) {
                foreach ($mensajes as $mensaje) {
                    $this->error($campo.': '.$mensaje);
                }
            }

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        // rol y email_verified_at no están en $fillable (defensa contra
        // mass-assignment); se asignan explícitamente.
        $user->forceFill([
            'rol' => 'admin',
            'email_verified_at' => now(),
        ])->save();

        $this->info("Administrador '{$user->email}' creado (rol admin, email verificado).");

        return self::SUCCESS;
    }
}
