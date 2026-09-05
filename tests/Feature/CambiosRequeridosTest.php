<?php

use App\Models\Anticipo;
use App\Models\Colaborador;
use App\Models\ColaboradorDatoBancario;
use App\Models\Evento;
use App\Models\HistoricoNomina;
use App\Models\Prestamo;
use App\Models\RegistroNormalizado;
use App\Models\TransporteUnidad;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;

// ── Colaboradores: datos bancarios múltiples (1 o más) ─────────────────────────

test('el perfil guarda varios registros bancarios y espeja el primero en el perfil', function () {
    $admin = User::factory()->admin()->create();
    $colaborador = Colaborador::create(['nombre' => 'Juan', 'apellidos' => 'Pérez', 'tipo' => 'FREELANCE']);

    $this->actingAs($admin)
        ->post("/colaboradores/{$colaborador->id}/perfil", [
            'alias' => 'Charly',
            'fecha_ingreso' => '2026-01-05',
            'telefono' => '5512345678',
            'whatsapp' => '5512345678',
            'datos_bancarios' => [
                ['id' => null, 'banco' => 'BBVA', 'beneficiario' => 'Juan Pérez', 'clave_interbancaria' => '012345678901234567', 'numero_tarjeta' => '1234567890123456', 'alias' => 'Nómina', 'comentario' => 'Principal'],
                ['id' => null, 'banco' => 'Santander', 'beneficiario' => 'Juan Pérez', 'clave_interbancaria' => '987654321098765432', 'numero_tarjeta' => '6543210987654321', 'alias' => 'Ahorro', 'comentario' => null],
            ],
        ])
        ->assertSessionHasNoErrors();

    $registros = ColaboradorDatoBancario::where('colaborador_id', $colaborador->id)->orderBy('id')->get();

    expect($registros)->toHaveCount(2)
        ->and($registros[0]->banco)->toBe('BBVA')
        ->and($registros[0]->alias)->toBe('Nómina')
        ->and($registros[0]->numero_tarjeta)->toBe('1234567890123456')
        ->and($registros[0]->comentario)->toBe('Principal')
        ->and($registros[1]->banco)->toBe('Santander')
        ->and($registros[1]->numero_tarjeta)->toBe('6543210987654321');

    // El primer registro se espeja en colaborador_perfiles (retrocompatibilidad de impresiones).
    $perfil = $colaborador->fresh()->perfil;

    expect($perfil->banco)->toBe('BBVA')
        ->and($perfil->clave_interbancaria)->toBe('012345678901234567');

    // La CLABE y la tarjeta se almacenan cifradas.
    $raw = DB::table('colaborador_datos_bancarios')->where('id', $registros[0]->id)->value('numero_tarjeta');
    expect(Crypt::decryptString($raw))->toBe('1234567890123456');
});

test('el perfil sincroniza la lista bancaria: edita, agrega y elimina registros', function () {
    $admin = User::factory()->admin()->create();
    $colaborador = Colaborador::create(['nombre' => 'Ana', 'apellidos' => 'López', 'tipo' => 'COLABORADOR BASE']);
    $perfil = $colaborador->perfil()->create(['fecha_ingreso' => '2026-01-05', 'telefono' => '5512345678', 'whatsapp' => '5512345678']);
    $a = ColaboradorDatoBancario::create(['colaborador_id' => $colaborador->id, 'banco' => 'BBVA']);
    $b = ColaboradorDatoBancario::create(['colaborador_id' => $colaborador->id, 'banco' => 'Santander']);

    $this->actingAs($admin)
        ->post("/colaboradores/{$colaborador->id}/perfil", [
            'fecha_ingreso' => '2026-01-05',
            'telefono' => '5512345678',
            'whatsapp' => '5512345678',
            'datos_bancarios' => [
                ['id' => $a->id, 'banco' => 'BBVA', 'alias' => 'Editado'],
                ['id' => null, 'banco' => 'HSBC'],
                // $b se omite → se elimina
            ],
        ])
        ->assertSessionHasNoErrors();

    $registros = ColaboradorDatoBancario::where('colaborador_id', $colaborador->id)->get();

    expect($registros)->toHaveCount(2)
        ->and($registros->firstWhere('id', $a->id)->alias)->toBe('Editado')
        ->and($registros->firstWhere('banco', 'HSBC'))->not->toBeNull()
        ->and($registros->firstWhere('id', $b->id))->toBeNull();
});

// ── Colaboradores: campo Área ─────────────────────────────────────────────────

test('se crea un colaborador con área y se puede editar', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/colaboradores', [
            'nombre' => 'Juan',
            'apellidos' => 'Pérez',
            'tipo' => 'COLABORADOR BASE',
            'categoria' => 'Técnico',
            'nivel' => 1,
            'area' => 'Escenario',
        ])
        ->assertSessionHasNoErrors();

    $colaborador = Colaborador::where('nombre', 'Juan')->first();

    expect($colaborador->area)->toBe('Escenario');

    $this->actingAs($admin)
        ->put("/colaboradores/{$colaborador->id}", ['area' => 'Producción', 'categoria' => 'Técnico', 'nivel' => 1, 'sueldo_diario' => 500])
        ->assertSessionHasNoErrors();

    expect($colaborador->fresh()->area)->toBe('Producción');
});

// ── Eventos: responsable y conductor ──────────────────────────────────────────

test('se asigna un responsable entre los colaboradores asignados al evento', function () {
    $admin = User::factory()->admin()->create();
    $evento = Evento::factory()->create();
    $asignado = Colaborador::factory()->base()->create();
    $evento->colaboradores()->attach($asignado);

    $this->actingAs($admin)
        ->put("/eventos/{$evento->id}/responsable", ['responsable_colaborador_id' => $asignado->id])
        ->assertSessionHasNoErrors();

    expect($evento->fresh()->responsable_colaborador_id)->toBe($asignado->id);
});

test('el responsable debe ser un colaborador asignado al evento', function () {
    $admin = User::factory()->admin()->create();
    $evento = Evento::factory()->create();
    $ajeno = Colaborador::factory()->base()->create();

    $this->actingAs($admin)
        ->put("/eventos/{$evento->id}/responsable", ['responsable_colaborador_id' => $ajeno->id])
        ->assertSessionHasErrors('responsable_colaborador_id');

    expect($evento->fresh()->responsable_colaborador_id)->toBeNull();
});

test('se asigna un conductor (no Freelance) a una unidad del evento', function () {
    $admin = User::factory()->admin()->create();
    $evento = Evento::factory()->create();
    $colaborador = Colaborador::factory()->base()->create();
    $evento->colaboradores()->attach($colaborador);
    $unidad = TransporteUnidad::factory()->create();
    $evento->unidadesTransporte()->attach($unidad);

    $this->actingAs($admin)
        ->put("/eventos/{$evento->id}/unidades/{$unidad->id}/conductor", ['conductor_colaborador_id' => $colaborador->id])
        ->assertSessionHasNoErrors();

    expect(DB::table('evento_unidades')->where('transporte_unidad_id', $unidad->id)->value('conductor_colaborador_id'))
        ->toBe($colaborador->id);
});

test('un colaborador Freelance no puede ser conductor de una unidad', function () {
    $admin = User::factory()->admin()->create();
    $evento = Evento::factory()->create();
    $freelance = Colaborador::factory()->freelance()->create();
    $evento->colaboradores()->attach($freelance);
    $unidad = TransporteUnidad::factory()->create();
    $evento->unidadesTransporte()->attach($unidad);

    $this->actingAs($admin)
        ->put("/eventos/{$evento->id}/unidades/{$unidad->id}/conductor", ['conductor_colaborador_id' => $freelance->id])
        ->assertSessionHasErrors('conductor_colaborador_id');

    expect(DB::table('evento_unidades')->where('transporte_unidad_id', $unidad->id)->value('conductor_colaborador_id'))
        ->toBeNull();
});

// ── Historial por colaborador ─────────────────────────────────────────────────

test('el panel de historial muestra nóminas, anticipos y préstamos del colaborador', function () {
    $admin = User::factory()->admin()->create();
    $colaborador = Colaborador::factory()->base()->create();
    HistoricoNomina::factory()->create(['colaborador_id' => $colaborador->id, 'estado' => 'PAGADO']);
    Anticipo::factory()->create(['colaborador_id' => $colaborador->id]);
    $prestamo = Prestamo::factory()->create(['colaborador_id' => $colaborador->id]);
    $prestamo->cuotas()->create(['numero_plazo' => 1, 'monto' => 1000, 'fecha_programada' => now()->format('Y-m-d'), 'estado' => 'PENDIENTE']);

    $response = $this->actingAs($admin)
        ->get("/colaboradores/{$colaborador->id}/historial")
        ->assertOk();

    $response->assertInertia(fn ($page) => $page
        ->component('colaboradores/Historial')
        ->where('colaborador.id', $colaborador->id)
        ->has('nominas', 1)
        ->has('anticipos', 1)
        ->has('prestamos', 1)
        ->has('prestamos.0.cuotas', 1));
});

test('un usuario sin permiso de historial recibe 403 en el panel de historial', function () {
    $capturista = User::factory()->conPermisos(['modulo:colaboradores'])->create();
    $colaborador = Colaborador::factory()->base()->create();

    $this->actingAs($capturista)
        ->get("/colaboradores/{$colaborador->id}/historial")
        ->assertForbidden();
});

// ── Impresión a terceros ──────────────────────────────────────────────────────

test('la impresión a terceros incluye colaboradores, unidades y responsable', function () {
    $admin = User::factory()->admin()->create();
    $evento = Evento::factory()->create([
        'lugar' => 'Foro 1',
        'fecha_inicio' => '2026-09-10',
        'fecha_fin' => '2026-09-12',
        'contacto_nombre' => 'Contacto X',
        'contacto_telefono' => '5512345678',
    ]);
    $responsable = Colaborador::factory()->base()->create(['area' => 'Escenario']);
    $evento->colaboradores()->attach($responsable);
    $evento->update(['responsable_colaborador_id' => $responsable->id]);
    $unidad = TransporteUnidad::factory()->create();
    $evento->unidadesTransporte()->attach($unidad, ['conductor_colaborador_id' => $responsable->id]);

    $this->actingAs($admin)
        ->get("/eventos/{$evento->id}/terceros/imprimir")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('eventos/ImprimirTerceros')
            ->where('evento.lugar', 'Foro 1')
            ->where('evento.contacto_nombre', 'Contacto X')
            ->where('responsable.id', $responsable->id)
            ->where('dias', 3)
            ->has('colaboradores', 1)
            ->where('colaboradores.0.area', 'Escenario')
            ->has('unidades', 1)
            ->where('unidades.0.conductor.id', $responsable->id));
});

// ── Formulario público de asistencia: historial ──────────────────────────────

test('el formulario público de asistencia expone el historial del colaborador', function () {
    $colaborador = Colaborador::factory()->base()->create();
    RegistroNormalizado::create([
        'colaborador_id' => $colaborador->id,
        'tipo_actividad' => 'Bodega',
        'actividad' => 'Carga / Descarga',
        'fecha' => '2026-09-01',
        'hora' => '09:00',
    ]);

    $this->get("/asistencia/{$colaborador->token}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('asistencia-publica/Show')
            ->has('registros', 1)
            ->where('registros.0.actividad', 'Carga / Descarga'));
});
