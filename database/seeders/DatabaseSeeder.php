<?php

namespace Database\Seeders;

use App\Models\Colaborador;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Administrador',
            'email' => 'admin@example.com',
            'rol' => 'admin',
        ]);

        $this->call(ParametroSistemaSeeder::class);
        $this->call(DatosEjemploSeeder::class);

        // WithoutModelEvents suprime el hook creating, así que generamos tokens manualmente
        Colaborador::whereNull('token')->each(
            fn ($c) => $c->update(['token' => (string) Str::uuid()])
        );
    }
}
