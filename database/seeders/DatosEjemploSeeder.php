<?php

namespace Database\Seeders;

use App\Enums\PeriodicidadPrestamo;
use App\Enums\TipoColaborador;
use App\Models\Anticipo;
use App\Models\Asignacion;
use App\Models\Colaborador;
use App\Models\ColaboradorPerfil;
use App\Models\Evento;
use App\Models\HistoricoNomina;
use App\Models\JornadaConsolidada;
use App\Models\Prestamo;
use App\Models\PrestamoCuota;
use App\Models\RegistroNormalizado;
use App\Models\ServicioProfesional;
use App\Models\TransporteDistancia;
use App\Models\TransporteTarifa;
use App\Models\TransporteUnidad;
use App\Models\TransporteVehiculo;
use App\Models\Viatico;
use App\Services\NominaCalculator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatosEjemploSeeder extends Seeder
{
    /**
     * Siembra un flujo COMPLETO y coherente entre todos los módulos:
     *
     *   Colaboradores (Base/Freelance/Conductor) → Perfiles → Eventos (+requisitos) →
     *   Asignaciones → Catálogo de transporte (vehículos/distancias/tarifas) → Unidades de la
     *   flotilla → Registros de asistencia → Jornadas consolidadas (validación humana) →
     *   Anticipos → Préstamos + cuotas → Nómina (calculada con NominaCalculator, siempre
     *   consistente con las reglas de negocio vigentes, ligada a cuotas y anticipos) →
     *   Servicios profesionales y Viáticos.
     *
     * Las nóminas históricas NO se inventan a mano: se calculan con NominaCalculator sobre las
     * jornadas validadas del período, de modo que si el admin recalcula hoy obtiene exactamente
     * el mismo monto que quedó congelado en histórico (fuente única de verdad).
     *
     * Ejemplos de reglas de negocio ejercitadas (REGLAS_NEGOCIO_NOMINA_SM.md):
     *   - Extra de evento BASE fijo por categoría+nivel+tamaño (ponderado por % de etapas).
     *   - Bono de 7° día (semana L–S completa).
     *   - Evento CHICO nunca genera bono (aunque se registre como evento en la jornada).
     *   - Traslape (Base y Conductor) con porcentaje configurable (traslape_pct).
     *   - Freelance paga % de etapas × pago_por_evento_completo; etapa sin validar NO suma.
     *   - Conductor: pago por ruta (vehículo × distancia), STANDBY (día en espera).
     *   - SIN_PAGO / ERROR_EVENTO / jornadas sin validar quedan en el Panel de Validación.
     *   - Anticipos y cuotas de préstamo se descuentan automáticamente de la nómina.
     */
    public function run(): void
    {
        // La demo salva SIEMPRE en el mes en curso: el período cerrado (nómina) es la primera
        // semana Lun–Sáb completa del mes actual, así los registros/eventos/nómina "funcionan
        // a partir de este mes" y el bono de 7° día sigue aplicándose al recálculo.
        $primerLun = now()->startOfMonth()->copy();
        if ($primerLun->dayOfWeek !== Carbon::MONDAY) {
            $primerLun = $primerLun->next(Carbon::MONDAY); // primer lunes del mes actual (ej. Ago 2026 → 03).
        }
        $d = fn (int $dia) => $primerLun->copy()->addDays($dia - 1)->format('Y-m-d');

        // ── 1. COLABORADORES ────────────────────────────────────────────────
        $carlos = Colaborador::create([
            'nombre' => 'Carlos',
            'apellidos' => 'Mendoza López',
            'tipo' => 'COLABORADOR BASE',
            'categoria' => 'Encargado de área',
            'nivel' => 1,
            'sueldo_diario' => 900.00,
        ]);

        $ana = Colaborador::create([
            'nombre' => 'Ana María',
            'apellidos' => 'Pérez Ruiz',
            'tipo' => 'COLABORADOR BASE',
            'categoria' => 'Técnico',
            'nivel' => 2,
            'sueldo_diario' => 850.00,
        ]);

        $roberto = Colaborador::create([
            'nombre' => 'Roberto',
            'apellidos' => 'Jiménez Castro',
            'tipo' => 'COLABORADOR BASE',
            'categoria' => 'Stagehand SM',
            'nivel' => 1,
            'sueldo_diario' => 950.00,
        ]);

        $sofia = Colaborador::create([
            'nombre' => 'Sofía',
            'apellidos' => 'Ramírez Morales',
            'tipo' => 'FREELANCE',
            'extra_dia_adicional' => 400.00,
        ]);

        $luis = Colaborador::create([
            'nombre' => 'Luis',
            'apellidos' => 'Hernández Santos',
            'tipo' => 'FREELANCE',
            'extra_dia_adicional' => 450.00,
        ]);

        $miguel = Colaborador::create([
            'nombre' => 'Miguel Ángel',
            'apellidos' => 'Flores Díaz',
            'tipo' => 'CONDUCTOR',
        ]);

        $patricia = Colaborador::create([
            'nombre' => 'Patricia',
            'apellidos' => 'Gómez Reyes',
            'tipo' => 'CONDUCTOR',
        ]);

        $fernando = Colaborador::create([
            'nombre' => 'Fernando',
            'apellidos' => 'Ruiz Torres',
            'tipo' => 'CONDUCTOR BASE',
            'sueldo_diario' => 900.00,
        ]);

        // ── 2. PERFILES DE COLABORADOR (datos de emergencia + documentos, opcionales) ──
        ColaboradorPerfil::create([
            'colaborador_id' => $carlos->id,
            'tipo_sangre' => 'O+',
            'alergias' => 'Polvo, penicilina',
            'padecimientos_cronicos' => null,
            'numero_seguro_social' => '12345678901',
            'seguro_social_documento_path' => 'colaboradores/carlos/seguro_social.pdf',
            'ine_documento_path' => 'colaboradores/carlos/ine.png',
            'curp_documento_path' => 'colaboradores/carlos/curp.pdf',
            'comprobante_domicilio_documento_path' => 'colaboradores/carlos/comprobante_domicilio.pdf',
            'licencia_conducir_documento_path' => null,
        ]);

        ColaboradorPerfil::create([
            'colaborador_id' => $ana->id,
            'tipo_sangre' => 'A+',
            'alergias' => null,
            'padecimientos_cronicos' => 'Asma leve',
            'numero_seguro_social' => '98765432109',
            'seguro_social_documento_path' => 'colaboradores/ana/seguro_social.pdf',
        ]);

        ColaboradorPerfil::create([
            'colaborador_id' => $roberto->id,
            'numero_seguro_social' => '13579246802',
            'seguro_social_documento_path' => 'colaboradores/roberto/seguro_social.pdf',
        ]);

        ColaboradorPerfil::create([
            'colaborador_id' => $miguel->id,
            'tipo_sangre' => 'B+',
            'numero_seguro_social' => '24680135791',
            'seguro_social_documento_path' => 'colaboradores/miguel/seguro_social.pdf',
            'licencia_conducir_documento_path' => 'colaboradores/miguel/licencia.pdf',
        ]);

        // Sofía, Luis y Patricia no tienen perfil → aparece la alerta "colaboradores sin perfil".

        // ── 3. EVENTOS (con fechas y requisitos de cotización) ────────────────
        $festival = Evento::create([
            'nombre' => 'Festival de Verano 2026',
            'lugar' => 'Foro Sol, CDMX',
            'fecha_inicio' => $d(1),
            'fecha_fin' => $d(7),
            'tamano' => 'GRANDE',
            'pago_por_evento_completo' => 3000.00,
            'requisitos_cotizacion' => [
                'base' => [
                    'Encargado de área' => ['1' => 1, '2' => 0],
                    'Técnico' => ['1' => 1, '2' => 1],
                    'Stagehand SM' => ['1' => 1, '2' => 0],
                ],
                'freelance' => 2,
            ],
        ]);

        $boda = Evento::create([
            'nombre' => 'Boda García-Muñoz',
            'lugar' => 'Hacienda San Miguel, Texcoco',
            'fecha_inicio' => $d(5),
            'fecha_fin' => $d(5),
            'tamano' => 'CHICO',
            'pago_por_evento_completo' => 1500.00,
            'requisitos_cotizacion' => [
                'base' => [
                    'Encargado de área' => ['1' => 1, '2' => 0],
                    'Técnico' => ['1' => 0, '2' => 0],
                    'Stagehand SM' => ['1' => 0, '2' => 0],
                ],
                'freelance' => 1,
            ],
        ]);

        $concierto = Evento::create([
            'nombre' => 'Concierto Navidad SM',
            'lugar' => 'Teatro Metropolitan, CDMX',
            'fecha_inicio' => $d(5),
            'fecha_fin' => $d(6),
            'tamano' => 'MEDIANO',
            'pago_por_evento_completo' => 2500.00,
            'requisitos_cotizacion' => [
                'base' => [
                    'Técnico' => ['1' => 1, '2' => 1],
                    'Stagehand SM' => ['1' => 1, '2' => 0],
                ],
                'freelance' => 1,
            ],
        ]);

        $feria = Evento::create([
            'nombre' => 'Feria Tech Empresarial',
            'lugar' => 'Centro Banamex, CDMX',
            'fecha_inicio' => null, // fecha por definir → aparece en "Próximos eventos"
            'fecha_fin' => null,
            'tamano' => 'MEDIANO',
            'pago_por_evento_completo' => 2500.00,
            'requisitos_cotizacion' => [
                'base' => [
                    'Stagehand SM' => ['1' => 2, '2' => 0],
                ],
                'freelance' => 1,
            ],
        ]);

        // ── 4. ASIGNACIONES (colaborador ↔ evento) ──────────────────────────
        Asignacion::insert([
            ['colaborador_id' => $carlos->id,   'evento_id' => $festival->id, 'created_at' => now(), 'updated_at' => now()],
            ['colaborador_id' => $carlos->id,   'evento_id' => $boda->id,     'created_at' => now(), 'updated_at' => now()],
            ['colaborador_id' => $ana->id,      'evento_id' => $festival->id, 'created_at' => now(), 'updated_at' => now()],
            ['colaborador_id' => $ana->id,      'evento_id' => $concierto->id, 'created_at' => now(), 'updated_at' => now()],
            ['colaborador_id' => $roberto->id,  'evento_id' => $festival->id, 'created_at' => now(), 'updated_at' => now()],
            ['colaborador_id' => $sofia->id,    'evento_id' => $festival->id, 'created_at' => now(), 'updated_at' => now()],
            ['colaborador_id' => $sofia->id,    'evento_id' => $boda->id,     'created_at' => now(), 'updated_at' => now()],
            ['colaborador_id' => $luis->id,     'evento_id' => $festival->id, 'created_at' => now(), 'updated_at' => now()],
            // Luis NO está asignado a la Feria, pero registra asistencia ahí (el flujo no depende
            // de la asignación previa para listar eventos en la nómina freelance).
        ]);

        // ── 5. CATÁLOGO DE TRANSPORTE (matriz de tarifas) ────────────────────
        $vCamioneta = TransporteVehiculo::create(['nombre' => 'Camioneta 3.5T', 'orden' => 1]);
        $vVan = TransporteVehiculo::create(['nombre' => 'Van de Carga',    'orden' => 2]);
        $vSprinter = TransporteVehiculo::create(['nombre' => 'Sprinter',        'orden' => 3]);
        $vPickup = TransporteVehiculo::create(['nombre' => 'Pick-up',         'orden' => 4]);

        $dLocal = TransporteDistancia::create(['nombre' => 'Local (< 10 km)',          'es_standby' => false, 'orden' => 1]);
        $dZonaMet = TransporteDistancia::create(['nombre' => 'Zona Metropolitana (10-30 km)', 'es_standby' => false, 'orden' => 2]);
        $dForaneo = TransporteDistancia::create(['nombre' => 'Foráneo (30-80 km)',       'es_standby' => false, 'orden' => 3]);
        $dStandby = TransporteDistancia::create(['nombre' => 'STANDBY',                  'es_standby' => true,  'orden' => 4]);

        $tarifas = [
            [$vCamioneta->id, $dLocal->id,   400],
            [$vCamioneta->id, $dZonaMet->id, 700],
            [$vCamioneta->id, $dForaneo->id, 1200],
            [$vCamioneta->id, $dStandby->id, 300],
            [$vVan->id,       $dLocal->id,   350],
            [$vVan->id,       $dZonaMet->id, 600],
            [$vVan->id,       $dForaneo->id, 1000],
            [$vVan->id,       $dStandby->id, 250],
            [$vSprinter->id,  $dLocal->id,   500],
            [$vSprinter->id,  $dZonaMet->id, 850],
            [$vSprinter->id,  $dForaneo->id, 1500],
            [$vSprinter->id,  $dStandby->id, 400],
            [$vPickup->id,    $dLocal->id,   300],
            [$vPickup->id,    $dZonaMet->id, 500],
            [$vPickup->id,    $dForaneo->id, 900],
            [$vPickup->id,    $dStandby->id, 200],
        ];

        foreach ($tarifas as [$vid, $did, $tarifa]) {
            TransporteTarifa::create([
                'vehiculo_id' => $vid,
                'distancia_id' => $did,
                'tarifa' => $tarifa,
            ]);
        }

        // ── 6. UNIDADES DE TRANSPORTE (flotilla física, distinta de las categorías) ──
        $uSprinter = TransporteUnidad::create([
            'marca' => 'Mercedes-Benz',
            'modelo' => 'Sprinter 519 CDI',
            'numero_placas' => 'SM-8841-A',
            'pertenencia' => 'PROPIA',
            'transporte_vehiculo_id' => $vSprinter->id,
            'placas_documento_path' => 'unidades-transporte/sprinter-01/placas.pdf',
            'tarjeta_circulacion_documento_path' => 'unidades-transporte/sprinter-01/tarjeta.pdf',
            'poliza_seguro_documento_path' => 'unidades-transporte/sprinter-01/poliza.pdf',
            'numero_poliza_seguro' => 'POL-88121',
            'vigencia_poliza_seguro' => '2027-06-30',
        ]);

        $uCamioneta = TransporteUnidad::create([
            'marca' => 'Freightliner',
            'modelo' => 'M2 106',
            'numero_placas' => 'SM-456-B',
            'pertenencia' => 'PROPIA',
            'transporte_vehiculo_id' => $vCamioneta->id,
            'numero_poliza_seguro' => 'POL-88772',
            'vigencia_poliza_seguro' => '2026-08-20',
        ]);

        $uVan = TransporteUnidad::create([
            'marca' => 'Ram',
            'modelo' => 'ProMaster City',
            'numero_placas' => null,
            'pertenencia' => 'RENTADA',
            'transporte_vehiculo_id' => $vVan->id,
            'tarjeta_circulacion_documento_path' => 'documents-transporte/van-rentada/tarjeta.pdf',
        ]);

        $uPickup = TransporteUnidad::create([
            'marca' => 'Ford',
            'modelo' => 'F-150',
            'numero_placas' => 'SM-789-C',
            'pertenencia' => 'PROPIA',
            'transporte_vehiculo_id' => $vPickup->id,
            'numero_poliza_seguro' => 'POL-99003',
            'vigencia_poliza_seguro' => '2026-09-01',
        ]);

        // ── 7. REGISTROS NORMALIZADOS (asistencia) ───────────────────────────
        // Restricciones por tipo (AsistenciaPublicaController::store):
        //   BASE → Bodega + Evento   |   FREELANCE → solo Evento   |   CONDUCTOR → solo Transporte.
        // La semana cerrada es el primer Lun → Sáb completo del mes actual (bono de 7° día visible).
        //
        // CARLOS (BASE, Encargado n1)
        $this->registro($carlos->id, $d(1), '07:00', '15:00', 'Bodega', actividad: 'Stagehand / Apoyo general');
        $this->registro($carlos->id, $d(2), '07:30', '10:00', 'Bodega', actividad: 'Carga / Descarga');
        $this->registro($carlos->id, $d(2), '10:30', '21:00', 'Evento', eventoRaw: 'Festival de Verano 2026', etapa: 'Montaje');
        $this->registro($carlos->id, $d(3), '07:30', '15:30', 'Bodega', actividad: 'Inventario');
        $this->registro($carlos->id, $d(4), '08:00', '13:00', 'Bodega', actividad: 'Acomodo');
        $this->registro($carlos->id, $d(4), '14:00', '23:00', 'Evento', eventoRaw: 'Festival de Verano 2026', etapa: 'Show');
        $this->registro($carlos->id, $d(5), '18:00', '23:00', 'Evento', eventoRaw: 'Boda García-Muñoz', etapa: 'Show');
        $this->registro($carlos->id, $d(6), '08:00', '14:00', 'Bodega', actividad: 'Limpieza');
        $this->registro($carlos->id, $d(6), '15:00', '22:00', 'Evento', eventoRaw: 'Festival de Verano 2026', etapa: 'Montaje, Show, Desmontaje');
        // Pendientes del Panel de Validación (fuera de la semana cerrada):
        $this->registro($carlos->id, $d(16), '08:00', '18:00', 'Evento', eventoRaw: 'Concierto No Catalogado 2026', etapa: 'Montaje');

        // ANA (BASE, Técnico n2) — día 2 es evento SOLO (sin bodega); día 6 traslape 50%.
        $this->registro($ana->id, $d(1), '07:00', '15:00', 'Bodega', actividad: 'Carga / Descarga');
        $this->registro($ana->id, $d(2), '09:00', '20:00', 'Evento', eventoRaw: 'Festival de Verano 2026', etapa: 'Montaje');
        $this->registro($ana->id, $d(3), '07:00', '15:00', 'Bodega', actividad: 'Inventario');
        $this->registro($ana->id, $d(4), '07:00', '14:00', 'Bodega', actividad: 'Acomodo');
        $this->registro($ana->id, $d(4), '15:00', '21:00', 'Evento', eventoRaw: 'Festival de Verano 2026', etapa: 'Show, Desmontaje');
        $this->registro($ana->id, $d(5), '07:00', '15:00', 'Bodega', actividad: 'Carga / Descarga');
        $this->registro($ana->id, $d(6), '18:00', '23:00', 'Evento', eventoRaw: 'Concierto Navidad SM', etapa: 'Show');

        // ROBERTO (BASE, Stageband SM)
        $this->registro($roberto->id, $d(1), '07:00', '15:00', 'Bodega', actividad: 'Carga / Descarga');
        $this->registro($roberto->id, $d(2), '07:30', '10:00', 'Bodega', actividad: 'Inventario');
        $this->registro($roberto->id, $d(2), '10:30', '20:00', 'Evento', eventoRaw: 'Festival de Verano 2026', etapa: 'Montaje');
        $this->registro($roberto->id, $d(3), '07:00', '15:00', 'Bodega', actividad: 'Acomodo');
        $this->registro($roberto->id, $d(4), '07:00', '16:00', 'Bodega', actividad: 'Inventario');
        $this->registro($roberto->id, $d(5), '07:00', '15:00', 'Bodega', actividad: 'Carga / Descarga');
        $this->registro($roberto->id, $d(6), '07:00', '14:00', 'Bodega', actividad: 'Entrega / Clientes');
        $this->registro($roberto->id, $d(6), '15:00', '19:00', 'Evento', eventoRaw: 'Festival de Verano 2026', etapa: 'Show, Desmontaje');
        // Pendiente sin validar (Pipeline de validación):
        $this->registro($roberto->id, $d(15), '08:00', '16:00', 'Bodega', actividad: 'Mantenimiento');

        // SOFÍA (FREELANCE) — el Desmontaje del Festival (04) queda SIN validar → no suma al %.
        $this->registro($sofia->id, $d(2), '09:00', '21:00', 'Evento', eventoRaw: 'Festival de Verano 2026', etapa: 'Montaje', extras: 'AUDIO | FOH');
        $this->registro($sofia->id, $d(3), '10:00', '23:00', 'Evento', eventoRaw: 'Festival de Verano 2026', etapa: 'Show');
        $this->registro($sofia->id, $d(4), '09:00', '22:00', 'Evento', eventoRaw: 'Festival de Verano 2026', etapa: 'Desmontaje');
        $this->registro($sofia->id, $d(5), '18:00', '23:00', 'Evento', eventoRaw: 'Boda García-Muñoz', etapa: 'Show');

        // LUIS (FREELANCE) — Festival completo validado (100%); Feria sin asignar pero con asistencia.
        $this->registro($luis->id, $d(2), '08:30', '20:00', 'Evento', eventoRaw: 'Festival de Verano 2026', etapa: 'Montaje');
        $this->registro($luis->id, $d(3), '09:00', '18:00', 'Evento', eventoRaw: 'Festival de Verano 2026', etapa: 'Show');
        $this->registro($luis->id, $d(4), '09:00', '20:00', 'Evento', eventoRaw: 'Festival de Verano 2026', etapa: 'Desmontaje');
        $this->registro($luis->id, $d(8), '09:00', '20:00', 'Evento', eventoRaw: 'Feria Tech Empresarial', etapa: 'Show, Desmontaje');

        // MIGUEL ÁNGEL (CONDUCTOR) — rutas con traslape 40% y standby de la flotilla.
        $this->registro($miguel->id, $d(1), '07:00', '14:00', 'Transporte',
            vehiculo: 'Camioneta 3.5T', distancia: 'Zona Metropolitana (10-30 km)',
            origen: 'Bodega SM', destino: 'Foro Sol', unidadId: $uCamioneta->id, extras: 'Carga pesada / Maniobras');
        $this->registro($miguel->id, $d(2), '06:30', '15:30', 'Transporte',
            vehiculo: 'Sprinter', distancia: 'Foráneo (30-80 km)',
            origen: 'Bodega SM', destino: 'Querétaro, QRO', unidadId: $uSprinter->id);
        $this->registro($miguel->id, $d(3), '08:00', '12:00', 'Transporte',
            vehiculo: 'Van de Carga', distancia: 'Local (< 10 km)',
            origen: 'Almacén Norte', destino: 'Bodega SM', unidadId: $uVan->id);
        $this->registro($miguel->id, $d(4), '09:00', '16:00', 'Transporte',
            vehiculo: 'Pick-up', distancia: 'Zona Metropolitana (10-30 km)',
            origen: 'Bodega SM', destino: 'Coyoacán', unidadId: $uPickup->id);
        // Traslape: segunda ruta con registro el mismo día, admin lo marca TRASLAPE 40%.
        $this->registro($miguel->id, $d(5), '16:00', '18:00', 'Transporte',
            vehiculo: 'Pick-up', distancia: 'Local (< 10 km)',
            origen: 'Bodega SM', destino: 'Almacén Norte', unidadId: $uPickup->id);
        // Sin autorización del día 12 (SIN_PAGO, fuera del período cerrado).
        $this->registro($miguel->id, $d(12), '10:00', '10:30', 'Transporte',
            vehiculo: 'Pick-up', distancia: 'Local (< 10 km)',
            origen: 'Bodega SM', destino: 'Bodega SM', extras: 'Traslado interno no autorizado');

        // PATRICIA (CONDUCTORA) — día 2 STANDBY; un día pendiente por fuera (día 12).
        $this->registro($patricia->id, $d(2), '08:00', '18:00', 'Transporte',
            vehiculo: 'Sprinter', distancia: 'STANDBY',
            origen: 'Bodega SM', destino: 'Bodega SM', unidadId: $uSprinter->id, extras: 'Día en espera, evento cancelado');
        $this->registro($patricia->id, $d(3), '07:00', '13:00', 'Transporte',
            vehiculo: 'Van de Carga', distancia: 'Zona Metropolitana (10-30 km)',
            origen: 'Bodega SM', destino: 'Centro Cívico', unidadId: $uVan->id);
        $this->registro($patricia->id, $d(5), '06:30', '12:30', 'Transporte',
            vehiculo: 'Sprinter', distancia: 'Foráneo (30-80 km)',
            origen: 'Bodega SM', destino: 'Pachuca', unidadId: $uSprinter->id);
        $this->registro($patricia->id, $d(4), '09:00', '15:00', 'Transporte',
            vehiculo: 'Camioneta 3.5T', distancia: 'Zona Metropolitana (10-30 km)',
            origen: 'Almacén Norte', destino: 'Foro Sol', unidadId: $uCamioneta->id);
        $this->registro($patricia->id, $d(12), '08:00', '15:00', 'Transporte',
            vehiculo: 'Pick-up', distancia: 'Local (< 10 km)',
            origen: 'Almacén Norte', destino: 'Bodega SM');

        // FERNANDO (CONDUCTOR BASE) — semestre mixto: días de bodega y días con rutas.
        $this->registro($fernando->id, $d(1), '07:00', '15:00', 'Bodega', 'Carga / Descarga', extras: 'Revisión de equipo');
        $this->registro($fernando->id, $d(2), '07:30', '14:30', 'Transporte',
            vehiculo: 'Pick-up', distancia: 'Zona Metropolitana (10-30 km)',
            origen: 'Bodega SM', destino: 'Centro Cívico', unidadId: $uPickup->id);
        $this->registro($fernando->id, $d(3), '07:00', '15:00', 'Bodega', 'Inventario');
        $this->registro($fernando->id, $d(4), '06:30', '15:30', 'Transporte',
            vehiculo: 'Van de Carga', distancia: 'Foráneo (30-80 km)',
            origen: 'Bodega SM', destino: 'Puebla, PUE', unidadId: $uVan->id);
        $this->registro($fernando->id, $d(5), '08:00', '12:00', 'Transporte',
            vehiculo: 'Pick-up', distancia: 'Local (< 10 km)',
            origen: 'Almacén Norte', destino: 'Bodega SM', unidadId: $uPickup->id);
        $this->registro($fernando->id, $d(6), '07:00', '15:00', 'Transporte',
            vehiculo: 'Sprinter', distancia: 'STANDBY',
            origen: 'Bodega SM', destino: 'Bodega SM', unidadId: $uSprinter->id);

        // ── 8. JORNADAS CONSOLIDADAS (registro → consolidación → validación humana) ──
        // Periodo cerrado y PAGADO: Lun → Sáb (primera semana del mes actual, bono de 7° día).

        // CARLOS — 6 días validados; el día de Boda (CHICO) no genera extra.
        $this->jornada($carlos->id, $d(1), '07:00', '15:00', 'Bodega: Stagehand / Apoyo general', 'JORNADA_COMPLETA', true);
        $this->jornada($carlos->id, $d(2), '07:30', '21:00', "Bodega: Carga / Descarga\nEvento: Festival de Verano 2026 - Montaje", 'JORNADA_COMPLETA + EVENTO', true);
        $this->jornada($carlos->id, $d(3), '07:30', '15:30', 'Bodega: Inventario', 'JORNADA_COMPLETA', true);
        $this->jornada($carlos->id, $d(4), '08:00', '23:00', "Bodega: Acomodo\nEvento: Festival de Verano 2026 - Show", 'JORNADA_COMPLETA + EVENTO', true);
        $this->jornada($carlos->id, $d(5), '18:00', '23:00', 'Evento: Boda García-Muñoz - Show', 'JORNADA_COMPLETA', true);
        $this->jornada($carlos->id, $d(6), '08:00', '22:00', "Bodega: Limpieza\nEvento: Festival de Verano 2026 - Montaje, Show, Desmontaje", 'JORNADA_COMPLETA + EVENTO', true);
        // Pipeline pendiente:
        $this->jornada($carlos->id, $d(16), '08:00', '18:00', 'Evento: NO IDENTIFICADO - Montaje', 'ERROR_EVENTO', false);

        // ANA – 6 días; día 6 traslape del 50%.
        $this->jornada($ana->id, $d(1), '07:00', '15:00', 'Bodega: Carga / Descarga', 'JORNADA_COMPLETA', true);
        $this->jornada($ana->id, $d(2), '09:00', '20:00', 'Evento: Festival de Verano 2026 - Montaje', 'JORNADA_COMPLETA + EVENTO', true);
        $this->jornada($ana->id, $d(3), '07:00', '15:00', 'Bodega: Inventario', 'JORNADA_COMPLETA', true);
        $this->jornada($ana->id, $d(4), '07:00', '21:00', "Bodega: Acomodo\nEvento: Festival de Verano 2026 - Show, Desmontaje", 'JORNADA_COMPLETA + EVENTO', true);
        $this->jornada($ana->id, $d(5), '07:00', '15:00', 'Bodega: Carga / Descarga', 'JORNADA_COMPLETA', true);
        $this->jornada($ana->id, $d(6), '18:00', '23:00', 'Evento: Concierto Navidad SM - Show', 'TRASLAPE', true, traslapePct: 50);

        // ROBERTO – 6 días; etapa día 2 = Montaje (25%) y día 6 = Show+Desmontaje (75%).
        $this->jornada($roberto->id, $d(1), '07:00', '15:00', 'Bodega: Carga / Descarga', 'JORNADA_COMPLETA', true);
        $this->jornada($roberto->id, $d(2), '07:00', '20:00', "Bodega: Inventario\nEvento: Festival de Verano 2026 - Montaje", 'JORNADA_COMPLETA + EVENTO', true);
        $this->jornada($roberto->id, $d(3), '07:00', '15:00', 'Bodega: Acomodo', 'JORNADA_COMPLETA', true);
        $this->jornada($roberto->id, $d(4), '07:00', '16:00', 'Bodega: Inventario', 'JORNADA_COMPLETA', true);
        $this->jornada($roberto->id, $d(5), '07:00', '15:00', 'Bodega: Stock / Clientes', 'JORNADA_COMPLETA', true);
        $this->jornada($roberto->id, $d(6), '07:00', '19:00', "Bodega: Entrega / Clientes\nEvento: Festival de Verano 2026 - Show, Desmontaje", 'JORNADA_COMPLETA + EVENTO', true);
        // Pipeline pendiente:
        $this->jornada($roberto->id, $d(15), '08:00', '16:00', 'Bodega: Mantenimiento', 'JORNADA_COMPLETA', false);

        // Sofia (FREELANCE)
        $this->jornada($sofia->id, $d(2), null, null, 'Evento: Festival de Verano 2026 - Montaje', 'JORNADA_COMPLETA + EVENTO', true, extras: 'AUDIO | FOH');
        $this->jornada($sofia->id, $d(3), null, null, 'Evento: Festival de Verano 2026 - Show', 'JORNADA_COMPLETA + EVENTO', true);
        $this->jornada($sofia->id, $d(4), null, null, 'Evento: Festival de Verano 2026 - Desmontaje', 'JORNADA_COMPLETA + EVENTO', false);
        $this->jornada($sofia->id, $d(5), null, null, 'Evento: Boda García-Muñoz - Show', 'JORNADA_COMPLETA + EVENTO', true);

        // LUIS (FREELANCE)
        $this->jornada($luis->id, $d(2), null, null, 'Evento: Festival de Verano 2026 - Montaje', 'JORNADA_COMPLETA + EVENTO', true);
        $this->jornada($luis->id, $d(3), null, null, 'Evento: Festival de Verano 2026 - Show', 'JORNADA_COMPLETA + EVENTO', true);
        $this->jornada($luis->id, $d(4), null, null, 'Evento: Festival de Verano 2026 - Desmontaje', 'JORNADA_COMPLETA + EVENTO', true);
        $this->jornada($luis->id, $d(8), null, null, 'Evento: Feria Tech Empresarial - Show, Desmontaje', 'JORNADA_COMPLETA + EVENTO', true);

        // MIGUEL (CONDUCTOR)
        $this->jornada($miguel->id, $d(1), '07:00', '14:00',
            'Transporte: Manejó un(a) Camioneta 3.5T Zona Metropolitana (10-30 km), de Bodega SM a Foro Sol',
            'JORNADA_COMPLETA', true, extras: 'Carga pesada / Maniobras');
        $this->jornada($miguel->id, $d(2), '06:30', '15:30',
            'Transporte: Manejó un(a) Sprinter Foráneo (30-80 km), de Bodega SM a Querétaro',
            'JORNADA_COMPLETA', true);
        $this->jornada($miguel->id, $d(3), '08:00', '12:00',
            'Transporte: Manejó un(a) Van de Carga Local (< 10 km), de Almacén Norte a Bodega SM',
            'JORNADA_COMPLETA', true);
        $this->jornada($miguel->id, $d(4), '07:00', '16:00',
            'Transporte: Manejó un(a) Pick-up Zona Metropolitana (10-30 km), de Bodega SM a Coyoacán',
            'JORNADA_COMPLETA', true);
        $this->jornada($miguel->id, $d(5), '16:00', '18:00',
            'Transporte: Manejó un(a) Pick-up Local (< 10 km), de Bodega SM a Almacén Norte',
            'TRASLAPE', true, traslapePct: 40);
        $this->jornada($miguel->id, $d(6), '08:00', '18:00',
            'Transporte: Manejó un(a) Sprinter STANDBY, de Bodega SM a Bodega SM',
            'JORNADA_COMPLETA', true);
        // Fuera del período: SIN_PAGO no autorizada
        $this->jornada($miguel->id, $d(12), '10:00', '10:30',
            'Transporte: Manejó un(a) Pick-up Local (< 10 km), de Bodega SM a Bodega SM',
            'SIN_PAGO', true, extras: 'Traslado interno no autorizado');

        // PATRICIA (CONDUCTOR) — día 2 STANDBY; día 12 pendiente (fuera período)
        $this->jornada($patricia->id, $d(2), '08:00', '18:00',
            'Transporte: Manejó un(a) Sprinter STANDBY, de Bodega SM a Bodega SM',
            'JORNADA_COMPLETA', true, extras: 'Día en espera, evento cancelado');
        $this->jornada($patricia->id, $d(3), '07:00', '13:00',
            'Transporte: Manejó un(a) Van de Carga Zona Metropolitana (10-30 km), de Bodega SM a Centro Cívico',
            'JORNADA_COMPLETA', true);
        $this->jornada($patricia->id, $d(4), '09:00', '15:00',
            'Transporte: Manejó un(a) Camioneta 3.5T Zona Metropolitana (10-30 km), de Almacén Norte a Foro Sol',
            'JORNADA_COMPLETA', true);
        $this->jornada($patricia->id, $d(5), '06:30', '12:30',
            'Transporte: Manejó un(a) Sprinter Foraneo (30-80 km), de Bodega SM a Pachuca',
            'JORNADA_COMPLETA', true);
        $this->jornada($patricia->id, $d(12), '08:00', '15:00',
            'Transporte: Manejó un(a) Pick-up Local (< 10 km), de Almacén Norte a Bodega 2C',
            'JORNADA_COMPLETA', false);

        // FERNANDO (CONDUCTOR BASE): cada día (bodega o ruta) paga el sueldo diario y,
        // cuando hay ruta, además se abona la tarifa del vehículo/distancia.
        $this->jornada($fernando->id, $d(1), '07:00', '15:00', 'Bodega: Carga / Descarga', 'JORNADA_COMPLETA', true, extras: 'Revisión de equipo');
        $this->jornada($fernando->id, $d(2), '07:30', '14:30',
            'Transporte: Manejó un(a) Pick-up Zona Metropolitana (10-30 km), de Bodega SM a Centro Cívico',
            'JORNADA_COMPLETA', true);
        $this->jornada($fernando->id, $d(3), '07:00', '15:00', 'Bodega: Inventario', 'JORNADA_COMPLETA', true);
        $this->jornada($fernando->id, $d(4), '06:30', '15:30',
            'Transporte: Manejó un(a) Van de Carga Foraneo (30-80 km), de Bodega SM a Puebla',
            'JORNADA_COMPLETA', true);
        $this->jornada($fernando->id, $d(5), '08:00', '12:00',
            'Transporte: Manejó un(a) Pick-up Local (< 10 km), de Almacén Norte a Bodega SM',
            'JORNADA_COMPLETA', true);
        $this->jornada($fernando->id, $d(6), '07:00', '15:00', 'Bodega: Acomodo / Clientes', 'JORNADA_COMPLETA', true);

        // ── 9. ANTICIPOS (se descuentan automáticamente en la nómina del período) ──
        Anticipo::create([
            'colaborador_id' => $carlos->id,
            'monto' => 500.00,
            'concepto' => 'Anticipo semana 01',
            'fecha' => $d(1),
            'entregado_por' => 'Administración',
        ]);

        // Sofía: el concepto coincide por fuzzy-match con el evento → se descuenta en su nómina freelance del Festival.
        Anticipo::create([
            'colaborador_id' => $sofia->id,
            'monto' => 800.00,
            'concepto' => 'Festival de Verano 2026',
            'fecha' => $d(1),
            'entregado_por' => 'Producción',
        ]);

        // Luis: concepto vacío → NO se descuenta automáticamente.
        Anticipo::create([
            'colaborador_id' => $luis->id,
            'monto' => 600.00,
            'concepto' => null,
            'fecha' => $d(5),
            'entregado_por' => 'Producción',
        ]);

        // Miguel en el período cerrado (se deduce en su nómina de conductor).
        Anticipo::create([
            'colaborador_id' => $miguel->id,
            'monto' => 350.00,
            'concepto' => 'Anticipo semana 01',
            'fecha' => $d(2),
            'entregado_por' => 'Administración',
        ]);

        // Roberto: anticipo con fecha futura (semana 02) → NO se deduce de la semana cerrada
        // (ejemplo de agenda de anticipos que afectan períodos posteriores).
        Anticipo::create([
            'colaborador_id' => $roberto->id,
            'monto' => 400.00,
            'concepto' => 'Semana 02',
            'fecha' => $d(10),
            'entregado_por' => 'Administración',
        ]);

        // ── 10. PRÉSTAMOS + CUOTAS (solo Base/Conductor; las cuotas se deducen por período) ──
        $pCarlos = Prestamo::create([
            'colaborador_id' => $carlos->id,
            'monto_total' => 15000.00,
            'num_plazos' => 6,
            'periodicidad' => 'SEMANAL',
            'fecha_inicio' => $d(3),
            'concepto' => 'Préstamo personal',
            'autoriza' => 'Administración',
        ]);
        // Cuota 1 (03-jun) cae en la semana cerrada; las cuotas 2-6 quedan programadas adelante.
        $this->generarCuotas($pCarlos);

        $pAna = Prestamo::create([
            'colaborador_id' => $ana->id,
            'monto_total' => 5000.00,
            'num_plazos' => 5,
            'periodicidad' => 'MENSUAL',
            'fecha_inicio' => $d(1),
            'concepto' => 'Ajuste quincena',
            'autoriza' => 'Administración',
        ]);
        // Cuota 1 (01-jun) se ve deducida en la nómina en curso.
        $this->generarCuotas($pAna);

        $pMiguel = Prestamo::create([
            'colaborador_id' => $miguel->id,
            'monto_total' => 6000.00,
            'num_plazos' => 3,
            'periodicidad' => 'QUINCENAL',
            'fecha_inicio' => $d(2),
            'concepto' => 'Gasto familiar',
            'autoriza' => 'Administración',
        ]);
        $this->generarCuotas($pMiguel);

        // ── 11. NÓMINA — calculada y guardada a través de la misma fuente única ─
        // (NominaCalculator). Los montos quedan congelados en el Histórico tal como los
        // mostraría el botón "Calcular", y las cuotas se ligan como haría pagar().

        $calc = app(NominaCalculator::class);
        $inicioSemana = Carbon::parse($d(1));
        $finSemana = Carbon::parse($d(6));

        // Base
        $this->crearNominaBase($calc, $carlos, $inicioSemana, $finSemana, 'PAGADO', 'Semana completa con Festival. Anticipo y 1 cuota de préstamo descontados.');
        $this->crearNominaBase($calc, $ana, $inicioSemana, $finSemana, 'PENDIENTE', 'Traslape al 50 % capturado por el admin; por autorizar.');
        $this->crearNominaBase($calc, $roberto, $inicioSemana, $finSemana, 'PAGADO', null);

        // Freelance (por evento)
        $this->crearNominaFreelance($calc, $sofia, $festival, 'PENDIENTE', '75% por etapas (Desmontaje pendiente de validar).');
        $this->crearNominaFreelance($calc, $luis, $festival, 'PAGADO', '100% de etapas. Anticipo sin descuento (concepto vacío).');
        $this->crearNominaFreelance($calc, $luis, $feria, 'PENDIENTE', 'Sin asignación previa, se cae por asistencia registrada.');

        // Conductores
        $this->crearNominaConductor($calc, $miguel, $inicioSemana, $finSemana, 'PAGADO', 'Rutas semanal + standby + anticipo.');
        $this->crearNominaConductor($calc, $patricia, $inicioSemana, $finSemana, 'PENDIENTE', 'Espera semana; standby incluido.');

        // Conductores base (sueldo semanal + rutas)
        $this->crearNominaConductorBase($calc, $fernando, $inicioSemana, $finSemana, 'PENDIENTE', 'Semana mixta de bodega y rutas.');

        // ── 12. SERVICIOS PROFESIONALES (separadas de nómina) ──────────────────
        ServicioProfesional::create([
            'nombre' => 'Juan',
            'apellidos' => 'Pérez Villanueva',
            'tipo' => 'RIGGER',
            'evento_id' => $festival->id,
            'concepto' => 'Servicio de rigging para grúas de iluminación',
            'monto' => 2000.00,
            'fecha' => $d(2),
            'autoriza' => 'Dirección de Producción',
        ]);

        ServicioProfesional::create([
            'nombre' => 'María',
            'apellidos' => 'López Gutiérrez',
            'tipo' => 'OPERADOR_AUDIO',
            'evento_id' => $concierto->id,
            'concepto' => 'Operación de consola de audio FOH',
            'monto' => 1800.00,
            'fecha' => $d(5),
            'autoriza' => 'Coordinación Técnica',
        ]);

        ServicioProfesional::create([
            'nombre' => 'Arturo',
            'apellidos' => 'Vázquez Moreno',
            'tipo' => 'OPERADOR_VIDEO',
            'evento_id' => $feria->id,
            'concepto' => 'Operación de sistema de video y pantallas LED',
            'monto' => 2500.00,
            'fecha' => $d(8),
            'autoriza' => null,
        ]);

        ServicioProfesional::create([
            'nombre' => 'Fernanda',
            'tipo' => 'RIGGER',
            'evento_id' => null,
            'concepto' => 'Consultoría estructural para montaje en bodega',
            'monto' => 3500.00,
            'fecha' => $d(20),
            'autoriza' => 'Gerencia',
        ]);

        // ── 13. VIÁTICOS (siempre ligados a un evento; colaborador opcional) ───
        Viatico::create([
            'colaborador_id' => $carlos->id,
            'nombre' => null,
            'apellidos' => null,
            'tipo' => 'TRANSPORTE',
            'evento_id' => $festival->id,
            'concepto' => 'Traslado de equipo de audio',
            'monto' => 1850.00,
            'fecha' => $d(2),
            'autoriza' => 'Dirección de Producción',
        ]);

        Viatico::create([
            'colaborador_id' => $sofia->id,
            'nombre' => null,
            'apellidos' => null,
            'tipo' => 'HOSPEDAJE',
            'evento_id' => $festival->id,
            'concepto' => 'Hospedaje nocturno para staff',
            'monto' => 2600.00,
            'fecha' => $d(3),
            'autoriza' => 'Coordinación',
        ]);

        Viatico::create([
            'colaborador_id' => null,
            'nombre' => 'Equipo boda',
            'apellidos' => null,
            'tipo' => 'ALIMENTOS',
            'evento_id' => $boda->id,
            'concepto' => 'Comida para staff del evento',
            'monto' => 3200.00,
            'fecha' => $d(5),
            'autoriza' => null,
        ]);

        Viatico::create([
            'colaborador_id' => $ana->id,
            'nombre' => null,
            'apellidos' => null,
            'tipo' => 'CASETAS_GASOLINA',
            'evento_id' => $concierto->id,
            'concepto' => 'Combustible para traslados',
            'monto' => 800.00,
            'fecha' => $d(5),
            'autoriza' => 'Coordinación Técnica',
        ]);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Crea un registro normalizado de asistencia.
     */
    private function registro(
        int $colaboradorId,
        string $fecha,
        string $hora,
        ?string $horaSalida,
        string $tipoActividad,
        ?string $actividad = null,
        ?string $eventoRaw = null,
        ?string $etapa = null,
        ?string $vehiculo = null,
        ?string $distancia = null,
        ?string $origen = null,
        ?string $destino = null,
        ?int $unidadId = null,
        ?string $extras = null,
    ): void {
        RegistroNormalizado::create([
            'colaborador_id' => $colaboradorId,
            'tipo_actividad' => $tipoActividad,
            'actividad' => $actividad,
            'evento_raw' => $eventoRaw,
            'etapa' => $etapa,
            'vehiculo' => $vehiculo,
            'distancia' => $distancia,
            'transporte_unidad_id' => $unidadId,
            'origen' => $origen,
            'destino' => $destino,
            'extras' => $extras,
            'fecha' => $fecha,
            'hora' => $hora,
            'hora_salida' => $horaSalida,
        ]);
    }

    /**
     * Crea una jornada consolidada (producto de la validación humana).
     */
    private function jornada(
        int $colaboradorId,
        string $fecha,
        ?string $entrada,
        ?string $salida,
        string $detalle,
        string $tipoPago,
        bool $validado,
        ?string $extras = null,
        ?int $traslapePct = null,
    ): void {
        JornadaConsolidada::create([
            'colaborador_id' => $colaboradorId,
            'fecha' => $fecha,
            'entrada' => $entrada,
            'salida' => $salida,
            'detalle' => $detalle,
            'extras' => $extras,
            'tipo_pago' => $tipoPago,
            'traslape_pct' => $tipoPago === 'TRASLAPE' ? $traslapePct : null,
            'validado' => $validado,
        ]);
    }

    /**
     * Genera el calendario de cuotas de un préstamo (mismo algoritmo que PrestamoController).
     */
    private function generarCuotas(Prestamo $prestamo): void
    {
        $montoCuota = round((float) $prestamo->monto_total / $prestamo->num_plazos, 2);
        $montoUltima = round((float) $prestamo->monto_total - ($montoCuota * ($prestamo->num_plazos - 1)), 2);
        $fechaInicio = Carbon::parse($prestamo->fecha_inicio);

        for ($n = 1; $n <= $prestamo->num_plazos; $n++) {
            $fechaPlazo = match ($prestamo->periodicidad) {
                PeriodicidadPrestamo::Semanal => $fechaInicio->copy()->addWeeks($n - 1),
                PeriodicidadPrestamo::Quincenal => $fechaInicio->copy()->addDays(15 * ($n - 1)),
                PeriodicidadPrestamo::Mensual => $fechaInicio->copy()->addMonths($n - 1),
            };

            PrestamoCuota::create([
                'prestamo_id' => $prestamo->id,
                'numero_plazo' => $n,
                'monto' => $n === $prestamo->num_plazos ? $montoUltima : $montoCuota,
                'fecha_programada' => $fechaPlazo->format('Y-m-d'),
                'estado' => 'PENDIENTE',
            ]);
        }
    }

    /**
     * Calcula la nómina de un colaborador con NominaCalculator y la congela en HistoricoNomina,
     * replicando exactamente la secuencia guardar() → (pagar()) del controlador:
     * liga las cuotas de préstamo del período y, si el estado es PAGADO, las marca pagadas.
     */
    private function guardarNomina(
        NominaCalculator $calc,
        Colaborador $col,
        ?Carbon $inicio,
        ?Carbon $fin,
        ?Evento $evento,
        string $estado,
        ?string $comentario,
    ): void {
        $desglose = match (true) {
            $evento !== null => $calc->calcularFreelance($col, $evento),
            $col->tipo === TipoColaborador::Conductor => $calc->calcularConductor($col, $inicio, $fin),
            $col->tipo === TipoColaborador::ConductorBase => $calc->calcularConductorBase($col, $inicio, $fin),
            default => $calc->calcularBase($col, $inicio, $fin),
        };

        $desgloseInterno = array_filter(
            $desglose,
            fn ($valor, $clave) => str_starts_with($clave, '_'),
            ARRAY_FILTER_USE_BOTH,
        );

        $nomina = HistoricoNomina::create([
            'colaborador_id' => $col->id,
            'tipo_colaborador' => $desglose['tipo_colaborador'],
            'periodo_inicio' => $desglose['periodo_inicio'],
            'periodo_fin' => $desglose['periodo_fin'],
            'evento_id' => $desglose['evento_id'],
            'dias' => $desglose['dias'],
            'sueldo_diario' => $desglose['sueldo_diario'],
            'total_base' => $desglose['total_base'],
            'bonos_evento' => $desglose['bonos_evento'],
            'compensaciones' => $desglose['compensaciones'],
            'anticipos' => $desglose['anticipos'],
            'prestamos' => $desglose['prestamos'] ?? 0,
            'total_final' => $desglose['total_final'],
            'comentario' => $comentario,
            'estado' => $estado,
            'fecha_calculo' => now(),
            'desglose' => $desgloseInterno,
        ]);

        /** @var array<int, array{id: int}> $prestamoDetalle */
        $prestamoDetalle = $desglose['_prestamo_detalle'] ?? [];
        $cuotaIds = collect($prestamoDetalle)->pluck('id')->all();
        if (! empty($cuotaIds)) {
            PrestamoCuota::whereIn('id', $cuotaIds)->update(['historico_nomina_id' => $nomina->id]);
        }

        if ($estado === 'PAGADO') {
            PrestamoCuota::where('historico_nomina_id', $nomina->id)
                ->update(['estado' => 'PAGADA', 'fecha_pago' => now()->toDateString()]);
        }
    }

    private function crearNominaBase(NominaCalculator $calc, Colaborador $col, Carbon $inicio, Carbon $fin, string $estado, ?string $comentario): void
    {
        $this->guardarNomina($calc, $col, $inicio, $fin, null, $estado, $comentario);
    }

    private function crearNominaConductor(NominaCalculator $calc, Colaborador $col, Carbon $inicio, Carbon $fin, string $estado, ?string $comentario): void
    {
        $this->guardarNomina($calc, $col, $inicio, $fin, null, $estado, $comentario);
    }

    private function crearNominaConductorBase(NominaCalculator $calc, Colaborador $col, Carbon $inicio, Carbon $fin, string $estado, ?string $comentario): void
    {
        $this->guardarNomina($calc, $col, $inicio, $fin, null, $estado, $comentario);
    }

    private function crearNominaFreelance(NominaCalculator $calc, Colaborador $col, Evento $evento, string $estado, ?string $comentario): void
    {
        $this->guardarNomina($calc, $col, null, null, $evento, $estado, $comentario);
    }
}
