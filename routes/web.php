<?php

use App\Http\Controllers\AnticipController;
use App\Http\Controllers\ArchivoController;
use App\Http\Controllers\AsignacionController;
use App\Http\Controllers\AsistenciaPublicaController;
use App\Http\Controllers\ColaboradorController;
use App\Http\Controllers\ColaboradorPerfilController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventoController;
use App\Http\Controllers\HistoricoController;
use App\Http\Controllers\JornadaController;
use App\Http\Controllers\ManualController;
use App\Http\Controllers\MarcaController;
use App\Http\Controllers\NominaController;
use App\Http\Controllers\ParametroController;
use App\Http\Controllers\PrestamoController;
use App\Http\Controllers\RegistroAsistenciaController;
use App\Http\Controllers\ServicioProfesionalController;
use App\Http\Controllers\TransporteController;
use App\Http\Controllers\TransporteUnidadController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ValidacionController;
use App\Http\Controllers\ViaticoController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController;

Route::redirect('/', '/login')->name('home');

// Rate limiting sobre el envío del enlace de reseteo de contraseña. Fortify registra esta
// ruta sin throttle; se sobreescribe para mitigar enumeración de cuentas y spam de correo.
// El controlador y la respuesta son los mismos de Fortify (solo cambia el middleware).
Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
    ->middleware(['guest', 'throttle:6,1'])
    ->name('password.email');

// Registro de asistencia público (sin autenticación, identificado por token UUID).
// Rate limiting: endpoint público de escritura y subida de archivos — se acota para mitigar
// spam/abuso de almacenamiento con un token válido, sin romper el uso legítimo del colaborador.
Route::get('asistencia/{token}', [AsistenciaPublicaController::class, 'show'])
    ->middleware('throttle:30,1')
    ->name('asistencia-publica.show');
Route::post('asistencia/{token}', [AsistenciaPublicaController::class, 'store'])
    ->middleware('throttle:20,1')
    ->name('asistencia-publica.store');

// Documentos sensibles y evidencias: SOLO autenticados (+ permiso del módulo).
// Nunca colgar de /storage ni de URLs públicas.
Route::get('archivos/{tipo}/{path}', [ArchivoController::class, 'mostrar'])
    ->whereIn('tipo', ['perfil', 'fotografia', 'unidad', 'flotilla', 'evidencia'])
    ->where('path', '.*')
    ->middleware(['auth', 'verified'])
    ->name('archivos.mostrar');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

// Cada módulo exige su permiso; el super admin (rol admin) los tiene todos.
Route::middleware(['auth', 'verified', 'ver_permiso:validacion'])->group(function () {
    // Panel Validación
    Route::get('validacion', [ValidacionController::class, 'index'])->name('validacion.index');
});

Route::middleware(['auth', 'verified', 'ver_permiso:colaboradores'])->group(function () {
    // Colaboradores
    Route::get('colaboradores', [ColaboradorController::class, 'index'])->name('colaboradores.index');
    Route::post('colaboradores', [ColaboradorController::class, 'store'])->name('colaboradores.store');
    Route::put('colaboradores/{colaborador}', [ColaboradorController::class, 'update'])->name('colaboradores.update');
    Route::post('colaboradores/{colaborador}/token/regenerar', [ColaboradorController::class, 'regenerarToken'])->name('colaboradores.token.regenerar');
    Route::delete('colaboradores/{colaborador}', [ColaboradorController::class, 'destroy'])->name('colaboradores.destroy');

    // Perfil de colaborador (datos de emergencia + documentos de identificación, opcionales)
    Route::get('colaboradores/{colaborador}/perfil', [ColaboradorPerfilController::class, 'show'])->name('colaboradores.perfil.show');
    Route::get('colaboradores/{colaborador}/perfil/imprimir', [ColaboradorPerfilController::class, 'imprimirPerfil'])->name('colaboradores.perfil.imprimir');

    Route::get('colaboradores/{colaborador}/perfil/documentos/imprimir', [ColaboradorPerfilController::class, 'imprimirDocumentos'])->name('colaboradores.perfil.documentos.imprimir');
    Route::get('colaboradores/{colaborador}/perfil/datos', [ColaboradorPerfilController::class, 'datosJson'])->name('colaboradores.perfil.datos');
    Route::post('colaboradores/{colaborador}/perfil', [ColaboradorPerfilController::class, 'update'])->name('colaboradores.perfil.update');
    Route::delete('colaboradores/{colaborador}/perfil/documento/{campo}', [ColaboradorPerfilController::class, 'eliminarDocumento'])
        ->whereIn('campo', ['seguro_social', 'ine', 'curp', 'comprobante_domicilio', 'licencia_conducir'])
        ->name('colaboradores.perfil.documento.eliminar');
});

Route::middleware(['auth', 'verified', 'ver_permiso:eventos'])->group(function () {
    // Eventos
    Route::get('eventos', [EventoController::class, 'index'])->name('eventos.index');
    Route::post('eventos', [EventoController::class, 'store'])->name('eventos.store');
    Route::put('eventos/{evento}', [EventoController::class, 'update'])->name('eventos.update');
    Route::delete('eventos/{evento}', [EventoController::class, 'destroy'])->name('eventos.destroy');
    Route::get('eventos/{evento}/asignacion', [EventoController::class, 'asignacion'])->name('eventos.asignacion');
    Route::post('eventos/{evento}/asignaciones/sync', [AsignacionController::class, 'sync'])->name('eventos.asignaciones.sync');
    Route::post('eventos/{evento}/unidades/sync', [EventoController::class, 'syncUnidades'])->name('eventos.unidades.sync');
    Route::put('eventos/{evento}/requisitos', [EventoController::class, 'guardarRequisitos'])->name('eventos.requisitos.update');

    // Nómina del evento (imprimir)
    Route::get('eventos/{evento}/nomina/imprimir', [EventoController::class, 'imprimirNomina'])->name('eventos.nomina.imprimir');
    Route::get('eventos/{evento}/cotizacion/imprimir', [EventoController::class, 'imprimirCotizacion'])->name('eventos.cotizacion.imprimir');
    Route::get('eventos/{evento}/resumen/imprimir', [EventoController::class, 'imprimirResumen'])->name('eventos.resumen.imprimir');
    Route::get('eventos/{evento}/detalles/imprimir', [EventoController::class, 'imprimirDetalles'])->name('eventos.detalles.imprimir');
});

Route::middleware(['auth', 'verified', 'ver_permiso:transportes'])->group(function () {
    // Transportes
    Route::get('transportes', [TransporteController::class, 'index'])->name('transportes.index');
    Route::post('transportes/guardar', [TransporteController::class, 'guardar'])->name('transportes.guardar');
    Route::delete('transportes/distancia/{distancia}', [TransporteController::class, 'eliminarDistancia'])->name('transportes.distancia.eliminar');
    Route::delete('transportes/vehiculo/{vehiculo}', [TransporteController::class, 'eliminarVehiculo'])->name('transportes.vehiculo.eliminar');

    // Unidades de transporte (flotilla: marca/modelo/placas/documentos, distinto de las
    // categorías de tarifa arriba)
    Route::post('transportes/unidades', [TransporteUnidadController::class, 'store'])->name('transportes.unidades.store');
    Route::get('transportes/unidades/{unidad}', [TransporteUnidadController::class, 'show'])->name('transportes.unidades.show');
    Route::put('transportes/unidades/{unidad}', [TransporteUnidadController::class, 'update'])->name('transportes.unidades.update');
    Route::delete('transportes/unidades/{unidad}', [TransporteUnidadController::class, 'destroy'])->name('transportes.unidades.destroy');
    Route::get('transportes/unidades/{unidad}/perfil/imprimir', [TransporteUnidadController::class, 'imprimirPerfil'])->name('transportes.unidades.perfil.imprimir');
    Route::post('transportes/unidades/{unidad}/documentos', [TransporteUnidadController::class, 'actualizarDocumentos'])->name('transportes.unidades.documentos.update');
    Route::delete('transportes/unidades/{unidad}/documentos/{campo}', [TransporteUnidadController::class, 'eliminarDocumento'])
        ->whereIn('campo', ['placas', 'tarjeta_circulacion', 'poliza_seguro', 'verificacion', 'tenencia', 'fotografia'])
        ->name('transportes.unidades.documentos.eliminar');
});

Route::middleware(['auth', 'verified', 'ver_permiso:anticipos'])->group(function () {
    // Anticipos (solo creación — no edición ni eliminación)
    Route::get('anticipos', [AnticipController::class, 'index'])->name('anticipos.index');
    Route::post('anticipos', [AnticipController::class, 'store'])->name('anticipos.store');
});

Route::middleware(['auth', 'verified', 'ver_permiso:prestamos'])->group(function () {
    // Préstamos (como Anticipos, pero con calendario de cuotas — solo Base/Conductor)
    Route::get('prestamos', [PrestamoController::class, 'index'])->name('prestamos.index');
    Route::post('prestamos', [PrestamoController::class, 'store'])->name('prestamos.store');
    Route::delete('prestamos/{prestamo}', [PrestamoController::class, 'destroy'])->name('prestamos.destroy');
    Route::patch('prestamos/cuotas/{cuota}/pagar', [PrestamoController::class, 'pagarCuota'])->name('prestamos.cuotas.pagar');
    Route::patch('prestamos/cuotas/{cuota}/revertir', [PrestamoController::class, 'revertirCuota'])->name('prestamos.cuotas.revertir');
    Route::post('prestamos/cuotas/aplazar', [PrestamoController::class, 'aplazar'])->name('prestamos.cuotas.aplazar');
    Route::post('prestamos/cuotas/distribuir', [PrestamoController::class, 'distribuir'])->name('prestamos.cuotas.distribuir');
});

Route::middleware(['auth', 'verified', 'ver_permiso:servicios-profesionales'])->group(function () {
    // Servicios Profesionales (solo creación)
    Route::get('servicios-profesionales', [ServicioProfesionalController::class, 'index'])->name('servicios-profesionales.index');
    Route::post('servicios-profesionales', [ServicioProfesionalController::class, 'store'])->name('servicios-profesionales.store');
});

Route::middleware(['auth', 'verified', 'ver_permiso:viaticos'])->group(function () {
    // Viáticos (solo creación) — gasto siempre ligado a un evento
    Route::get('viaticos', [ViaticoController::class, 'index'])->name('viaticos.index');
    Route::post('viaticos', [ViaticoController::class, 'store'])->name('viaticos.store');
    Route::post('viaticos/matriz', [ViaticoController::class, 'matriz'])->name('viaticos.matriz');
});

Route::middleware(['auth', 'verified', 'ver_permiso:historial'])->group(function () {
    // Historial
    Route::get('historial', [HistoricoController::class, 'index'])->name('historial.index');
    Route::get('historial/imprimir-rango', [HistoricoController::class, 'imprimirRango'])->name('historial.imprimir-rango');
    Route::get('historial/{nomina}/imprimir', [HistoricoController::class, 'imprimir'])->name('historial.imprimir');
});

Route::middleware(['auth', 'verified', 'ver_permiso:registro-asistencia'])->group(function () {
    // Registro de Asistencia
    Route::get('registro-asistencia', [RegistroAsistenciaController::class, 'index'])->name('registro-asistencia.index');
    Route::post('registro-asistencia', [RegistroAsistenciaController::class, 'store'])->name('registro-asistencia.store');
    Route::put('registro-asistencia/{registro}', [RegistroAsistenciaController::class, 'update'])->name('registro-asistencia.update');
    Route::delete('registro-asistencia/{registro}', [RegistroAsistenciaController::class, 'destroy'])->name('registro-asistencia.destroy');
});

Route::middleware(['auth', 'verified', 'ver_permiso:nomina'])->group(function () {
    // Jornadas
    Route::post('jornadas/generar', [JornadaController::class, 'generar'])->name('jornadas.generar');
    Route::patch('jornadas/{jornada}/validado', [JornadaController::class, 'actualizarValidado'])->name('jornadas.validado');
    Route::patch('jornadas/{jornada}/tipo-pago', [JornadaController::class, 'actualizarTipoPago'])->name('jornadas.tipo-pago');
    Route::patch('jornadas/{jornada}/fraccion-evento', [JornadaController::class, 'actualizarFraccionEvento'])->name('jornadas.fraccion-evento');
    Route::patch('jornadas/{jornada}/compensacion', [JornadaController::class, 'actualizarCompensacion'])->name('jornadas.compensacion');

    // Nómina (calcular→JSON, guardar→PENDIENTE, pagar→PAGADO)
    Route::get('nomina/calcular', [NominaController::class, 'calcular'])->name('nomina.calcular');
    Route::get('nomina/freelance-datos', [NominaController::class, 'freelanceDatos'])->name('nomina.freelance-datos');
    Route::post('nomina/guardar', [NominaController::class, 'guardar'])->name('nomina.guardar');
    Route::patch('nomina/{nomina}/pagar', [NominaController::class, 'pagar'])->name('nomina.pagar');
    Route::delete('nomina/{nomina}', [NominaController::class, 'eliminar'])->name('nomina.eliminar');
});

// Parámetros del sistema y administración de usuarios — solo super admin (rol admin).
Route::middleware(['auth', 'verified', 'es_admin'])->group(function () {
    Route::get('parametros', [ParametroController::class, 'index'])->name('parametros.index');
    Route::put('parametros', [ParametroController::class, 'update'])->name('parametros.update');

    // Administración de usuarios y permisos de acceso por módulo
    Route::get('parametros/usuarios', [UsuarioController::class, 'index'])->name('parametros.usuarios.index');
    Route::post('parametros/usuarios', [UsuarioController::class, 'store'])->name('parametros.usuarios.store');
    Route::put('parametros/usuarios/{usuario}', [UsuarioController::class, 'update'])->name('parametros.usuarios.update');
    Route::delete('parametros/usuarios/{usuario}', [UsuarioController::class, 'destroy'])->name('parametros.usuarios.destroy');

    // Personalización de marca (colores + logo/isotipo)
    Route::get('parametros/marca', [MarcaController::class, 'index'])->name('parametros.marca.index');
    Route::post('parametros/marca/colores', [MarcaController::class, 'colores'])->name('parametros.marca.colores');
    Route::post('parametros/marca/logo/{cual}', [MarcaController::class, 'subirLogo'])->whereIn('cual', ['logo', 'isotipo'])->name('parametros.marca.logo.subir');
    Route::delete('parametros/marca/logo/{cual}', [MarcaController::class, 'eliminarLogo'])->whereIn('cual', ['logo', 'isotipo'])->name('parametros.marca.logo.eliminar');
});

// Manual de usuario
Route::middleware(['auth', 'verified', 'ver_permiso:manual'])->group(function () {
    Route::get('manual', [ManualController::class, 'index'])->name('manual.index');
});

require __DIR__.'/settings.php';
