<?php

use App\Models\Colaborador;
use App\Models\ColaboradorPerfil;
use App\Models\User;

test('página de perfil de colaborador se muestra', function () {
    $admin = User::factory()->admin()->create();
    $colaborador = Colaborador::create([
        'nombre' => 'Juan',
        'apellidos' => 'Pérez',
        'tipo' => 'FREELANCE',
    ]);

    $this->actingAs($admin)
        ->get("/colaboradores/{$colaborador->id}/perfil")
        ->assertOk();
});

test('el perfil exige fecha de ingreso, teléfono y whatsapp', function () {
    $admin = User::factory()->admin()->create();
    $colaborador = Colaborador::create([
        'nombre' => 'Juan',
        'apellidos' => 'Pérez',
        'tipo' => 'FREELANCE',
    ]);

    $this->actingAs($admin)
        ->post("/colaboradores/{$colaborador->id}/perfil", [
            'alias' => 'Charly',
        ])
        ->assertSessionHasErrors(['fecha_ingreso', 'telefono', 'whatsapp']);

    expect(ColaboradorPerfil::where('colaborador_id', $colaborador->id)->exists())->toBeFalse();
});

test('el perfil se guarda con los campos nuevos', function () {
    $admin = User::factory()->admin()->create();
    $colaborador = Colaborador::create([
        'nombre' => 'Juan',
        'apellidos' => 'Pérez',
        'tipo' => 'FREELANCE',
    ]);

    $this->actingAs($admin)
        ->post("/colaboradores/{$colaborador->id}/perfil", [
            'alias' => 'Charly',
            'fecha_ingreso' => '2026-01-05',
            'correo' => 'juan@example.com',
            'telefono' => '5512345678',
            'whatsapp' => '5512345678',
            'redes_sociales' => 'https://instagram.com/juan',
            'domicilio' => 'Av. Reforma 100, CDMX',
            'genero' => 'Masculino',
            'ubicacion_maps' => 'https://maps.app.goo.gl/abc123',
            'fecha_nacimiento' => '1990-05-20',
            'contacto_emergencia_1_nombre' => 'María',
            'contacto_emergencia_1_parentesco' => 'Esposa',
            'contacto_emergencia_1_telefono' => '5511111111',
            'contacto_emergencia_2_nombre' => 'Luis',
            'contacto_emergencia_2_parentesco' => 'Hermano',
            'contacto_emergencia_2_telefono' => '5522222222',
            'banco' => 'BBVA',
            'beneficiario' => 'Juan Pérez',
            'clave_interbancaria' => '012345678901234567',
        ])
        ->assertSessionHasNoErrors();

    $perfil = $colaborador->fresh()->perfil;

    expect($perfil)->not->toBeNull()
        ->and($perfil->alias)->toBe('Charly')
        ->and($perfil->fecha_ingreso)->toBe('2026-01-05')
        ->and($perfil->correo)->toBe('juan@example.com')
        ->and($perfil->telefono)->toBe('5512345678')
        ->and($perfil->whatsapp)->toBe('5512345678')
        ->and($perfil->redes_sociales)->toBe('https://instagram.com/juan')
        ->and($perfil->domicilio)->toBe('Av. Reforma 100, CDMX')
        ->and($perfil->genero)->toBe('Masculino')
        ->and($perfil->ubicacion_maps)->toBe('https://maps.app.goo.gl/abc123')
        ->and($perfil->fecha_nacimiento)->toBe('1990-05-20')
        ->and($perfil->contacto_emergencia_1_nombre)->toBe('María')
        ->and($perfil->contacto_emergencia_1_parentesco)->toBe('Esposa')
        ->and($perfil->contacto_emergencia_1_telefono)->toBe('5511111111')
        ->and($perfil->contacto_emergencia_2_nombre)->toBe('Luis')
        ->and($perfil->contacto_emergencia_2_parentesco)->toBe('Hermano')
        ->and($perfil->contacto_emergencia_2_telefono)->toBe('5522222222')
        ->and($perfil->banco)->toBe('BBVA')
        ->and($perfil->beneficiario)->toBe('Juan Pérez')
        ->and($perfil->clave_interbancaria)->toBe('012345678901234567');
});
