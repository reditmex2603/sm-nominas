<?php

use App\Models\Colaborador;
use App\Models\Evento;
use App\Models\JornadaConsolidada;
use App\Models\RegistroNormalizado;
use App\Services\JornadaGenerator;

beforeEach(function () {
    $this->gen = app(JornadaGenerator::class);
});

test('consolida los registros del día con entrada y salida para colaborador base', function () {
    $base = Colaborador::factory()->base()->create();

    RegistroNormalizado::factory()->bodega('Carga de equipo')->create([
        'colaborador_id' => $base->id, 'fecha' => '2026-08-04', 'hora' => '09:00:00', 'hora_salida' => null,
    ]);
    RegistroNormalizado::factory()->bodega('Inventario')->create([
        'colaborador_id' => $base->id, 'fecha' => '2026-08-04', 'hora' => '12:00:00', 'hora_salida' => '18:00:00',
    ]);

    $errores = $this->gen->generar();

    $jornada = JornadaConsolidada::where('colaborador_id', $base->id)->where('fecha', '2026-08-04')->sole();

    expect($errores)->toBeEmpty()
        ->and($jornada->entrada)->toBe('09:00:00')
        ->and($jornada->salida)->toBe('18:00:00')
        ->and($jornada->actividades)->toBe(['Bodega'])
        ->and($jornada->detalle)->toContain('Bodega: Carga de equipo')
        ->and($jornada->detalle)->toContain('Bodega: Inventario')
        ->and($jornada->tipo_pago)->toBe('JORNADA_COMPLETA')
        ->and($jornada->validado)->toBeFalse();
});

test('freelance no lleva horario de entrada/salida', function () {
    $freelance = Colaborador::factory()->freelance()->create();
    $evento = Evento::factory()->create(['nombre' => 'Festival Prueba']);

    RegistroNormalizado::factory()->evento($evento->nombre, 'Show')->create([
        'colaborador_id' => $freelance->id, 'fecha' => '2026-08-04',
    ]);

    $this->gen->generar();

    $jornada = JornadaConsolidada::where('colaborador_id', $freelance->id)->sole();

    expect($jornada->entrada)->toBeNull()
        ->and($jornada->salida)->toBeNull();
});

test('un evento no chico propone jornada completa + evento', function () {
    $base = Colaborador::factory()->base()->create();
    $evento = Evento::factory()->mediano()->create(['nombre' => 'Festival Prueba']);

    RegistroNormalizado::factory()->evento($evento->nombre, 'Show')->create([
        'colaborador_id' => $base->id, 'fecha' => '2026-08-04',
    ]);

    $this->gen->generar();

    $jornada = JornadaConsolidada::where('colaborador_id', $base->id)->sole();

    expect($jornada->tipo_pago)->toBe('JORNADA_COMPLETA + EVENTO')
        ->and($jornada->detalle)->toBe("Evento: {$evento->nombre} - Show");
});

test('un evento chico propone jornada completa sin bono', function () {
    $base = Colaborador::factory()->base()->create();
    $evento = Evento::factory()->chico()->create(['nombre' => 'Reunión Chica']);

    RegistroNormalizado::factory()->evento($evento->nombre, 'Show')->create([
        'colaborador_id' => $base->id, 'fecha' => '2026-08-04',
    ]);

    $this->gen->generar();

    expect(JornadaConsolidada::where('colaborador_id', $base->id)->sole()->tipo_pago)->toBe('JORNADA_COMPLETA');
});

test('un evento no identificado marca ERROR_EVENTO y lo reporta', function () {
    $base = Colaborador::factory()->base()->create();

    RegistroNormalizado::factory()->evento('Evento Que No Existe', 'Show')->create([
        'colaborador_id' => $base->id, 'fecha' => '2026-08-04',
    ]);

    $errores = $this->gen->generar();

    $jornada = JornadaConsolidada::where('colaborador_id', $base->id)->sole();

    expect($jornada->tipo_pago)->toBe('ERROR_EVENTO')
        ->and($jornada->detalle)->toContain('NO IDENTIFICADO')
        ->and($errores)->not->toBeEmpty()
        ->and($errores[0])->toContain('Evento no identificado');
});

test('regenerar preserva la validación humana y el tipo de pago revisado', function () {
    $base = Colaborador::factory()->base()->create();
    $evento = Evento::factory()->mediano()->create(['nombre' => 'Festival Prueba']);

    RegistroNormalizado::factory()->evento($evento->nombre, 'Show')->create([
        'colaborador_id' => $base->id, 'fecha' => '2026-08-04',
    ]);

    // Jornada ya revisada por un humano: validada y marcada como TRASLAPE al 40%
    JornadaConsolidada::factory()->traslape(40, 'detalle viejo')->create([
        'colaborador_id' => $base->id, 'fecha' => '2026-08-04', 'validado' => true,
    ]);

    $this->gen->generar();

    $jornada = JornadaConsolidada::where('colaborador_id', $base->id)->sole();

    expect($jornada->validado)->toBeTrue()
        ->and($jornada->tipo_pago)->toBe('TRASLAPE')       // no se toca
        ->and($jornada->traslape_pct)->toBe(40)           // no se toca
        ->and($jornada->detalle)->toContain("Evento: {$evento->nombre} - Show"); // asistencia sí se actualiza
});

test('detecta actividad inválida según el tipo de colaborador', function (string $tipoColaborador, string $tipoActividad, string $mensaje) {
    $colaborador = Colaborador::factory()->create(['tipo' => $tipoColaborador]);

    // Para actividad Evento hace falta un evento identificable; si no, el error de
    // "evento no identificado" sobreescribe al de actividad inválida (el último gana).
    $eventoRaw = null;
    if ($tipoActividad === 'Evento') {
        $eventoRaw = Evento::factory()->create(['nombre' => 'Festival Prueba'])->nombre;
    }

    RegistroNormalizado::factory()->create([
        'colaborador_id' => $colaborador->id,
        'fecha' => '2026-08-04',
        'tipo_actividad' => $tipoActividad,
        'actividad' => $tipoActividad === 'Bodega' ? 'Carga' : null,
        'evento_raw' => $eventoRaw,
        'etapa' => $tipoActividad === 'Evento' ? 'Show' : null,
        'vehiculo' => $tipoActividad === 'Transporte' ? 'Camión' : null,
        'distancia' => $tipoActividad === 'Transporte' ? '100-200km' : null,
        'origen' => $tipoActividad === 'Transporte' ? 'A' : null,
        'destino' => $tipoActividad === 'Transporte' ? 'B' : null,
    ]);

    $errores = $this->gen->generar();

    expect($errores)->not->toBeEmpty()
        ->and($errores[0])->toContain($mensaje);
})->with([
    'freelance en bodega' => ['FREELANCE', 'Bodega', 'Freelance actividad inválida'],
    'conductor en bodega' => ['CONDUCTOR', 'Bodega', 'Conductor actividad inválida'],
    'base en transporte' => ['COLABORADOR BASE', 'Transporte', 'Base en transporte'],
    'conductor base en evento' => ['CONDUCTOR BASE', 'Evento', 'Conductor base en evento'],
]);
