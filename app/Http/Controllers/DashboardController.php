<?php

namespace App\Http\Controllers;

use App\Models\Anticipo;
use App\Models\Colaborador;
use App\Models\Evento;
use App\Models\HistoricoNomina;
use App\Models\JornadaConsolidada;
use App\Models\Prestamo;
use App\Models\PrestamoCuota;
use App\Models\ServicioProfesional;
use App\Models\TransporteTarifa;
use App\Models\TransporteUnidad;
use App\Models\TransporteVehiculo;
use App\Models\Viatico;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $ano = (int) now()->format('Y');

        $totalColaboradores = Colaborador::count();
        $colaboradoresPorTipo = Colaborador::selectRaw('tipo, count(*) as total')
            ->groupBy('tipo')->pluck('total', 'tipo');

        $totalEventos = Evento::count();
        $eventosPorTamano = Evento::selectRaw('tamano, count(*) as total')
            ->groupBy('tamano')->pluck('total', 'tamano');
        $eventosVigentesQuery = Evento::where(function ($q) {
            $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', now()->toDateString());
        });
        $totalEventosVigentes = (clone $eventosVigentesQuery)->count();
        $eventosVigentesPorTamano = (clone $eventosVigentesQuery)
            ->selectRaw('tamano, COUNT(*) as total')
            ->groupBy('tamano')->pluck('total', 'tamano');
        $eventosPorAnio = Evento::selectRaw('YEAR(fecha_inicio) as anio, COUNT(*) as total')
            ->whereNotNull('fecha_inicio')
            ->groupBy('anio')->orderBy('anio', 'desc')->limit(6)->get();
        $proximosEventos = Evento::where(function ($q) {
            $q->where('fecha_inicio', '>=', now()->subDays(1))
                ->orWhereNull('fecha_inicio');
        })->orderBy('fecha_inicio')->limit(5)->get();

        $nominaTotalAnio = HistoricoNomina::whereYear('periodo_inicio', $ano)->sum('total_final');
        $nominaPendienteAnio = HistoricoNomina::where('estado', 'PENDIENTE')
            ->whereYear('periodo_inicio', $ano)->sum('total_final');
        $nominaPagadoAnio = HistoricoNomina::where('estado', 'PAGADO')
            ->whereYear('periodo_inicio', $ano)->sum('total_final');
        $nominaPagadoMes = HistoricoNomina::where('estado', 'PAGADO')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->sum('total_final');
        $ultimasNominas = HistoricoNomina::with('colaborador:id,nombre,apellidos')
            ->orderBy('created_at', 'desc')
            ->limit(10)->get();
        $nominaPorAnio = HistoricoNomina::selectRaw(
            'YEAR(periodo_inicio) as anio, SUM(total_final) as total, COUNT(*) as registros'
        )
            ->where('estado', 'PAGADO')
            ->whereNotNull('periodo_inicio')
            ->groupBy('anio')->orderBy('anio', 'desc')->limit(6)->get();

        $anticiposAnio = Anticipo::whereYear('fecha', $ano)->sum('monto');
        $anticiposMes = Anticipo::whereMonth('fecha', now()->month)
            ->whereYear('fecha', now()->year)->sum('monto');

        $prestamosActivos = Prestamo::whereHas('cuotas', function ($q) {
            $q->where('estado', 'PENDIENTE');
        })->count();
        $prestamosPendienteMonto = PrestamoCuota::where('estado', 'PENDIENTE')->sum('monto');

        $viaticosAnio = Viatico::whereYear('fecha', $ano)->sum('monto');
        $viaticosMes = Viatico::whereMonth('fecha', now()->month)
            ->whereYear('fecha', now()->year)->sum('monto');

        $serviciosAnio = ServicioProfesional::whereYear('fecha', $ano)->sum('monto');
        $serviciosMes = ServicioProfesional::whereMonth('fecha', now()->month)
            ->whereYear('fecha', now()->year)->sum('monto');

        $totalJornadas = JornadaConsolidada::count();
        $jornadasValidadas = JornadaConsolidada::where('validado', true)->count();
        $totalJornadasAnio = JornadaConsolidada::whereYear('fecha', $ano)->count();
        $jornadasValidadasAnio = JornadaConsolidada::whereYear('fecha', $ano)->where('validado', true)->count();
        $jornadasPorAnio = JornadaConsolidada::selectRaw(
            'YEAR(fecha) as anio, COUNT(*) as total, SUM(CASE WHEN validado = 1 THEN 1 ELSE 0 END) as validadas'
        )
            ->groupBy('anio')->orderBy('anio', 'desc')->limit(6)->get();

        // Alertas
        $colaboradoresSinPerfil = Colaborador::doesntHave('perfil')->count();
        $jornadasAtrasadas = JornadaConsolidada::where('validado', false)
            ->where('fecha', '<', now()->startOfWeek()->format('Y-m-d'))
            ->count();
        $cuotasProximas = PrestamoCuota::where('estado', 'PENDIENTE')
            ->whereBetween('fecha_programada', [now()->startOfDay(), now()->addDays(7)->endOfDay()])
            ->with('prestamo.colaborador:id,nombre,apellidos')
            ->orderBy('fecha_programada')
            ->get();

        // Transporte
        $totalUnidades = TransporteUnidad::count();
        $unidadesPropias = TransporteUnidad::where('pertenencia', 'PROPIA')->count();
        $unidadesRentadas = TransporteUnidad::where('pertenencia', 'RENTADA')->count();
        $totalCategorias = TransporteVehiculo::count();
        $totalTarifas = TransporteTarifa::count();
        $tarifaPromedio = TransporteTarifa::avg('tarifa');

        // Vencimientos (próximos 30 días)
        $cuotasVencimiento = PrestamoCuota::where('estado', 'PENDIENTE')
            ->whereBetween('fecha_programada', [now()->startOfDay(), now()->addDays(30)->endOfDay()])
            ->with('prestamo.colaborador:id,nombre,apellidos')
            ->orderBy('fecha_programada')
            ->limit(10)
            ->get();
        $segurosPorVencer = TransporteUnidad::whereNotNull('vigencia_poliza_seguro')
            ->whereBetween('vigencia_poliza_seguro', [now()->startOfDay(), now()->addDays(30)->endOfDay()])
            ->orderBy('vigencia_poliza_seguro')
            ->get(['id', 'marca', 'modelo', 'numero_placas', 'vigencia_poliza_seguro']);

        return Inertia::render('dashboard/Index', [
            'stats' => [
                'colaboradores' => [
                    'total' => $totalColaboradores,
                    'por_tipo' => $colaboradoresPorTipo,
                ],
                'eventos' => [
                    'total' => $totalEventos,
                    'total_vigentes' => $totalEventosVigentes,
                    'por_tamano' => $eventosPorTamano,
                    'vigentes_por_tamano' => $eventosVigentesPorTamano,
                    'por_anio' => $eventosPorAnio,
                    'proximos' => $proximosEventos,
                ],
                'nomina' => [
                    'total_anio' => (float) $nominaTotalAnio,
                    'pendiente_anio' => (float) $nominaPendienteAnio,
                    'pagado_anio' => (float) $nominaPagadoAnio,
                    'pagado_mes' => (float) $nominaPagadoMes,
                    'ultimas' => $ultimasNominas,
                    'por_anio' => $nominaPorAnio,
                ],
                'anticipos_mes' => (float) $anticiposMes,
                'prestamos' => [
                    'activos' => $prestamosActivos,
                    'pendiente_monto' => (float) $prestamosPendienteMonto,
                ],
                'viaticos_mes' => (float) $viaticosMes,
                'servicios_mes' => (float) $serviciosMes,
                'validacion' => [
                    'total' => $totalJornadas,
                    'validadas' => $jornadasValidadas,
                    'total_anio' => $totalJornadasAnio,
                    'validadas_anio' => $jornadasValidadasAnio,
                    'por_anio' => $jornadasPorAnio,
                ],
                'alertas' => [
                    'colaboradores_sin_perfil' => $colaboradoresSinPerfil,
                    'jornadas_atrasadas' => $jornadasAtrasadas,
                    'cuotas_proximas' => $cuotasProximas,
                ],
                'transporte' => [
                    'total_unidades' => $totalUnidades,
                    'propias' => $unidadesPropias,
                    'rentadas' => $unidadesRentadas,
                    'total_categorias' => $totalCategorias,
                    'total_tarifas' => $totalTarifas,
                    'tarifa_promedio' => $tarifaPromedio ? (float) $tarifaPromedio : 0,
                ],
                'vencimientos' => [
                    'cuotas' => $cuotasVencimiento,
                    'seguros' => $segurosPorVencer,
                ],
                'gastos_anio' => [
                    'nomina' => (float) $nominaPagadoAnio,
                    'anticipos' => (float) $anticiposAnio,
                    'viaticos' => (float) $viaticosAnio,
                    'servicios' => (float) $serviciosAnio,
                ],
            ],
        ]);
    }
}
