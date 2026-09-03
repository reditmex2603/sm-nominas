<?php

use App\Models\User;
use App\Support\Modulos;
use Illuminate\Support\Facades\Gate;

/*
 * Autorización por módulo vía Gates (reemplaza a los middlewares VerPermiso/EsAdmin).
 * Verifica que cada permiso del catálogo protege su ruta y que el admin tiene acceso total.
 */

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

test('el admin tiene acceso a todos los módulos', function (string $ruta) {
    $this->actingAs($this->admin)->get($ruta)->assertStatus(200);
})->with([
    'validacion' => '/validacion',
    'colaboradores' => '/colaboradores',
    'eventos' => '/eventos',
    'transportes' => '/transportes',
    'anticipos' => '/anticipos',
    'prestamos' => '/prestamos',
    'servicios profesionales' => '/servicios-profesionales',
    'viaticos' => '/viaticos',
    'historial' => '/historial',
    'registro asistencia' => '/registro-asistencia',
    'manual' => '/manual',
]);

test('un usuario sin el permiso del módulo recibe 403', function (string $permiso, string $ruta) {
    $usuario = User::factory()->conPermisos([])->create();

    $this->actingAs($usuario)->get($ruta)->assertForbidden();
})->with([
    'validacion' => ['validacion', '/validacion'],
    'colaboradores' => ['colaboradores', '/colaboradores'],
    'eventos' => ['eventos', '/eventos'],
    'nomina' => ['nomina', '/nomina/calcular'],
]);

test('un usuario con el permiso del módulo accede', function (string $permiso, string $ruta) {
    $usuario = User::factory()->conPermisos([$permiso])->create();

    $this->actingAs($usuario)->get($ruta)->assertStatus(200);
})->with([
    'colaboradores' => ['colaboradores', '/colaboradores'],
    'eventos' => ['eventos', '/eventos'],
]);

test('los parámetros de sistema requieren rol admin', function () {
    $capturista = User::factory()->conPermisos(['colaboradores'])->create();

    $this->actingAs($capturista)->get('/parametros')->assertForbidden();
    $this->actingAs($this->admin)->get('/parametros')->assertStatus(200);
});

test('la administración de usuarios requiere rol admin', function () {
    $capturista = User::factory()->conPermisos(['colaboradores'])->create();

    $this->actingAs($capturista)->get('/parametros/usuarios')->assertForbidden();
    $this->actingAs($this->admin)->get('/parametros/usuarios')->assertStatus(200);
});

test('cada permiso del catálogo tiene su Gate registrado', function () {
    foreach (Modulos::claves() as $modulo) {
        expect(Gate::has("modulo:{$modulo}"))->toBeTrue();
    }

    expect(Gate::has('es-admin'))->toBeTrue();
});

test('Gate::before otorga acceso total al rol admin en cualquier habilidad', function () {
    expect(Gate::forUser($this->admin)->allows('modulo:validacion'))->toBeTrue()
        ->and(Gate::forUser($this->admin)->allows('es-admin'))->toBeTrue();
});
