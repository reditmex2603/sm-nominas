<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Printer, ArrowLeft } from '@lucide/vue';
import { onMounted } from 'vue';
import DatosBancarios from '@/components/DatosBancarios.vue';
import PrintLogo from '@/components/PrintLogo.vue';
import { fmtFecha } from '@/lib/fecha';
import { fraccionEventoLabel } from '@/lib/fraccionEvento';
import { tipoPagoLabel } from '@/lib/tipoPago';

interface PerfilBancario { banco: string | null; beneficiario: string | null; clave_interbancaria: string | null }
interface ColaboradorRef { id: number; nombre: string; apellidos: string; perfil?: PerfilBancario | null }

interface Jornada {
    fecha: string; tipo_pago: string; traslape_pct?: number | null;
    detalle: string | null; extras: string | null; bono_evento: number;
    pct_etapas: number | null; evento_tamano: string | null;
    eventos_dia?: { nombre: string; tamano: string; pct_etapas: number; bono: number; compensacion?: number; fraccion: number | string | null }[];
}
interface Ruta { fecha: string; vehiculo: string; distancia: string; monto: number; extras: string | null }
interface Registro { fecha: string; etapa: string | null; extras: string | null; contabiliza: boolean }
interface PrestamoDetalle { id: number; concepto: string | null; numero_plazo: number; monto: number; fecha_programada: string }

interface Desglose {
    _jornadas?: Jornada[];
    _rutas?: Ruta[];
    _registros?: Registro[];
    _categoria?: string | null;
    _nivel?: number | null;
    _total_rutas?: number;
    _porcentaje?: number;
    _pago_base?: number;
    _pago_extras?: number;
    _bonos_evento_puro?: number;
    _bono_septimo?: number;
    _etapas?: string[];
    _prestamo_detalle?: PrestamoDetalle[];
}

interface NominaRecord {
    id: number; tipo_colaborador: string;
    periodo_inicio: string | null; periodo_fin: string | null;
    dias: string; sueldo_diario: string;
    total_base: string; bonos_evento: string; compensaciones: string;
    comentario: string | null; anticipos: string; prestamos: string;
    total_final: string; estado: string; evento_id: number | null;
    fecha_calculo: string;
    colaborador: ColaboradorRef;
    desglose: Desglose | null;
}

const props = defineProps<{
    nominas: NominaRecord[];
    fecha_desde: string | null;
    fecha_hasta: string | null;
    tipo: string | null;
    colaborador_id: number | null;
    colaborador_nombre: string | null;
    estado: string | null;
}>();

const fmt = (val: string | number) => {
    const v = parseFloat(String(val));
    const s = v < 0 ? '-' : '';

    return `${s}$${Math.abs(v).toLocaleString('es-MX', { minimumFractionDigits: 2 })}`;
};

const doPrint = () => window.print();

const tituloTipo = props.tipo === 'base' ? 'Colaboradores Base'
    : props.tipo === 'freelance' ? 'Freelance'
    : props.tipo === 'conductores' ? 'Conductores'
    : 'General';

onMounted(() => {
    setTimeout(doPrint, 500);
});
</script>

<template>
    <Head title="Nóminas por rango" />

    <div class="print-container min-h-screen bg-slate-100 p-4 print:m-0 print:bg-white print:p-0">
        <!-- Toolbar (solo pantalla) -->
        <div class="mb-4 flex items-center justify-between print:hidden">
            <Link
                href="/historial"
                class="inline-flex items-center gap-1.5 text-sm text-slate-600 hover:text-slate-900"
            >
                <ArrowLeft class="size-4" />
                Volver al historial
            </Link>
            <button
                class="inline-flex items-center gap-1.5 rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700"
                @click="doPrint"
            >
                <Printer class="size-4" />
                Imprimir
            </button>
        </div>

        <!-- Portada / resumen general -->
        <div class="mx-auto max-w-4xl">
            <div class="page-break mb-6 rounded bg-white p-8 shadow-lg print:shadow-none">
                <div class="mb-4">
                    <PrintLogo />
                </div>
                <h1 class="text-2xl font-bold uppercase tracking-tight text-slate-900">Reporte de Nóminas</h1>
                <p class="mt-1 text-lg font-semibold text-slate-700">{{ tituloTipo }}</p>
                <p class="mt-1 text-sm text-slate-500">
                    Período:
                    {{ fecha_desde ? fmtFecha(fecha_desde) : '—' }}
                    →
                    {{ fecha_hasta ? fmtFecha(fecha_hasta) : '—' }}
                </p>
                <p v-if="colaborador_nombre" class="mt-0.5 text-sm text-slate-500">
                    Colaborador: {{ colaborador_nombre }}
                </p>
                <p v-if="estado" class="mt-0.5 text-sm text-slate-500">
                    Estado: {{ estado === 'PAGADO' ? 'Pagadas' : 'Pendientes' }}
                </p>
                <p class="mt-0.5 text-sm text-slate-500">{{ nominas.length }} nóminas encontradas</p>
            </div>

            <p v-if="nominas.length === 0" class="py-16 text-center text-sm text-slate-500">
                Ninguna nómina coincide con los filtros seleccionados.
            </p>

            <!-- Cada nómina -->
            <div
                v-for="n in nominas"
                :key="n.id"
                class="nomina-card page-break mb-6 rounded bg-white shadow-lg print:mb-0 print:shadow-none"
            >
                <div class="border-b-2 border-slate-800 px-8 pb-4 pt-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <h2 class="text-xl font-bold uppercase tracking-tight text-slate-900">Comprobante de Nómina</h2>
                            <p class="mt-0.5 text-sm text-slate-500">Folio: N-{{ String(n.id).padStart(5, '0') }}</p>
                        </div>
                        <div class="text-right text-sm text-slate-500">
                            <p>{{ n.colaborador.apellidos }}, {{ n.colaborador.nombre }}</p>
                            <p class="text-xs">{{ n.tipo_colaborador }}</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-6 border-b px-8 py-4 text-sm">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Colaborador</p>
                        <p class="mt-0.5 font-semibold text-slate-900">{{ n.colaborador.apellidos }}, {{ n.colaborador.nombre }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Tipo</p>
                        <p class="mt-0.5 text-slate-900">{{ n.tipo_colaborador }}</p>
                    </div>
                    <div v-if="n.periodo_inicio || n.periodo_fin">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Período</p>
                        <p class="mt-0.5 text-slate-900">{{ fmtFecha(n.periodo_inicio) }} → {{ fmtFecha(n.periodo_fin) }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Estado</p>
                        <p class="mt-0.5 font-medium" :class="n.estado === 'PAGADO' ? 'text-emerald-600' : 'text-amber-600'">
                            {{ n.estado === 'PAGADO' ? 'PAGADA' : 'PENDIENTE' }}
                        </p>
                    </div>
                </div>

                <!-- Resumen de nómina -->
                <div class="px-8 py-4">
                    <DatosBancarios :perfil="n.colaborador.perfil ?? null" class="mb-4" />
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200">
                                <th class="py-2 text-left font-medium text-slate-600">Concepto</th>
                                <th class="py-2 text-right font-medium text-slate-600">Detalle</th>
                                <th class="py-2 text-right font-medium text-slate-600">Importe</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="n.tipo_colaborador === 'COLABORADOR BASE' || n.tipo_colaborador === 'CONDUCTOR BASE'">
                                <td class="py-2 text-slate-800">Sueldo base</td>
                                <td class="py-2 text-right text-slate-600">{{ n.dias }} días × {{ fmt(n.sueldo_diario) }}</td>
                                <td class="py-2 text-right font-medium text-slate-900">{{ fmt(n.total_base) }}</td>
                            </tr>
                            <tr v-if="parseFloat(n.bonos_evento) > 0">
                                <td class="py-2 text-slate-800">
                                    {{ n.tipo_colaborador === 'COLABORADOR BASE' ? 'Bonos de evento' : 'Bono 7º día' }}
                                </td>
                                <td class="py-2 text-right text-slate-600">
                                    <template v-if="n.desglose?._bonos_evento_puro">Bono evento</template>
                                    <template v-if="n.desglose?._bono_septimo">
                                        <template v-if="n.desglose?._bonos_evento_puro"> + </template>
                                        Bono 7º día
                                    </template>
                                </td>
                                <td class="py-2 text-right font-medium text-emerald-700">{{ fmt(n.bonos_evento) }}</td>
                            </tr>
                            <tr v-if="parseFloat(n.compensaciones) > 0">
                                <td class="py-2 text-slate-800">Compensación</td>
                                <td class="py-2 text-right text-slate-600"></td>
                                <td class="py-2 text-right font-medium text-emerald-700">{{ fmt(n.compensaciones) }}</td>
                            </tr>
                            <tr v-if="n.tipo_colaborador === 'FREELANCE'">
                                <td class="py-2 text-slate-800">Pago por evento</td>
                                <td class="py-2 text-right text-slate-600">{{ n.desglose?._porcentaje != null ? `${n.desglose._porcentaje}%` : '' }}</td>
                                <td class="py-2 text-right font-medium text-slate-900">{{ fmt(n.total_final) }}</td>
                            </tr>
                            <tr v-if="(n.tipo_colaborador === 'CONDUCTOR' || n.tipo_colaborador === 'CONDUCTOR BASE') && (n.desglose?._rutas ?? []).length > 0">
                                <td class="py-2 text-slate-800">Rutas de transporte</td>
                                <td class="py-2 text-right text-slate-600">
                                    <template v-for="(r, i) in n.desglose?._rutas ?? []" :key="i">
                                        <template v-if="i > 0"><br /></template>
                                        {{ r.vehiculo }} · {{ r.distancia }}: {{ fmt(r.monto) }}
                                    </template>
                                </td>
                                <td class="py-2 text-right font-medium text-slate-900">{{ fmt(n.desglose?._total_rutas ?? 0) }}</td>
                            </tr>
                            <tr v-if="parseFloat(n.anticipos) > 0" class="border-t border-slate-200">
                                <td class="py-2 text-slate-800">Anticipos</td>
                                <td class="py-2 text-right text-slate-600"></td>
                                <td class="py-2 text-right font-medium text-red-600">-{{ fmt(n.anticipos) }}</td>
                            </tr>
                            <tr v-if="parseFloat(n.prestamos) > 0">
                                <td class="py-2 text-slate-800">Préstamos</td>
                                <td class="py-2 text-right text-slate-600">{{ n.desglose?._prestamo_detalle?.length ?? 0 }} cuotas</td>
                                <td class="py-2 text-right font-medium text-red-600">-{{ fmt(n.prestamos) }}</td>
                            </tr>
                            <tr class="border-t-2 border-slate-800">
                                <td class="py-3 text-base font-bold text-slate-900">Total</td>
                                <td class="py-3"></td>
                                <td class="py-3 text-right text-base font-bold text-slate-900">{{ fmt(n.total_final) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Desglose -->
                <div v-if="n.desglose" class="border-t px-8 py-4">
                    <template v-if="n.tipo_colaborador === 'COLABORADOR BASE'">
                        <p v-if="!n.desglose._categoria || n.desglose._nivel === null" class="mb-2 rounded bg-amber-50 p-2 text-xs text-amber-700">
                            Sin categoría/nivel al momento del cálculo.
                        </p>
                        <table v-if="(n.desglose._jornadas ?? []).length > 0" class="w-full text-xs">
                            <thead>
                                <tr class="border-b border-slate-200">
                                    <th class="py-1.5 text-left font-medium text-slate-500">Fecha</th>
                                    <th class="py-1.5 text-left font-medium text-slate-500">Detalle</th>
                                    <th class="py-1.5 text-right font-medium text-slate-500">Tipo</th>
                                    <th class="py-1.5 text-right font-medium text-slate-500">Bono</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="j in n.desglose._jornadas" :key="j.fecha" class="border-b border-slate-100">
                                    <td class="py-1.5 text-slate-800 tabular-nums">{{ fmtFecha(j.fecha) }}</td>
                                    <td class="py-1.5 text-slate-600">{{ j.detalle ?? '—' }}</td>
                                    <td class="py-1.5 text-right text-slate-600">{{ tipoPagoLabel(j.tipo_pago as any, j.traslape_pct) }}</td>
                                    <td class="py-1.5 text-right text-slate-800 tabular-nums">{{ j.bono_evento > 0 ? `+${fmt(j.bono_evento)}` : '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <div v-if="n.desglose._jornadas?.some(j => (j.eventos_dia ?? []).length > 0)" class="mt-3">
                            <p class="mb-1 text-xs font-medium text-slate-600">Desglose por evento:</p>
                            <div v-for="j in n.desglose._jornadas" :key="'ev-' + j.fecha">
                                <div v-for="ev in j.eventos_dia ?? []" :key="ev.nombre" class="mb-0.5 pl-4 text-xs text-slate-600">
                                    {{ fmtFecha(j.fecha) }} — {{ ev.nombre }} ({{ ev.tamano }} · {{ ev.pct_etapas }}%
                                    <template v-if="ev.fraccion"> · {{ fraccionEventoLabel(ev.fraccion as any) }}</template>):
                                    +{{ fmt(ev.bono) }}
                                    <span v-if="ev.compensacion" class="text-emerald-600">(incl. comp. +{{ fmt(ev.compensacion) }})</span>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template v-else-if="n.tipo_colaborador === 'FREELANCE'">
                        <table v-if="(n.desglose._registros ?? []).length > 0" class="w-full text-xs">
                            <thead>
                                <tr class="border-b border-slate-200">
                                    <th class="py-1.5 text-left font-medium text-slate-500">Fecha</th>
                                    <th class="py-1.5 text-left font-medium text-slate-500">Etapa</th>
                                    <th class="py-1.5 text-center font-medium text-slate-500">Estado</th>
                                    <th class="py-1.5 text-left font-medium text-slate-500">Extras</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(r, i) in n.desglose._registros" :key="i" class="border-b border-slate-100">
                                    <td class="py-1.5 text-slate-800 tabular-nums">{{ fmtFecha(r.fecha) }}</td>
                                    <td class="py-1.5 text-slate-600">{{ r.etapa ?? '—' }}</td>
                                    <td class="py-1.5 text-center">
                                        <span :class="r.contabiliza ? 'text-emerald-600' : 'text-amber-600'">
                                            {{ r.contabiliza ? 'Contabilizada' : 'Sin validar' }}
                                        </span>
                                    </td>
                                    <td class="py-1.5 text-slate-600">{{ r.extras ?? '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <p v-if="(n.desglose._registros ?? []).length === 0" class="text-xs text-slate-500">Sin registros de asistencia.</p>
                    </template>

                    <template v-else-if="n.tipo_colaborador === 'CONDUCTOR'">
                        <table v-if="(n.desglose._rutas ?? []).length > 0" class="w-full text-xs">
                            <thead>
                                <tr class="border-b border-slate-200">
                                    <th class="px-4 py-1.5 text-left font-medium text-slate-500">Fecha</th>
                                    <th class="px-4 py-1.5 text-left font-medium text-slate-500">Vehículo</th>
                                    <th class="px-4 py-1.5 text-left font-medium text-slate-500">Distancia</th>
                                    <th class="px-4 py-1.5 text-right font-medium text-slate-500">Monto</th>
                                    <th class="px-4 py-1.5 text-left font-medium text-slate-500">Extras</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(ruta, i) in n.desglose._rutas" :key="i" class="border-b border-slate-100">
                                    <td class="px-4 py-1.5 text-slate-800 tabular-nums">{{ fmtFecha(ruta.fecha) }}</td>
                                    <td class="px-4 py-1.5 text-slate-600">{{ ruta.vehiculo }}</td>
                                    <td class="px-4 py-1.5 text-slate-600">{{ ruta.distancia }}</td>
                                    <td class="px-4 py-1.5 text-right text-slate-800 tabular-nums">{{ fmt(ruta.monto) }}</td>
                                    <td class="px-4 py-1.5 text-slate-600">{{ ruta.extras ?? '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <p v-if="(n.desglose._rutas ?? []).length === 0" class="text-xs text-slate-500">Sin rutas registradas.</p>
                    </template>

                    <template v-else-if="n.tipo_colaborador === 'CONDUCTOR BASE'">
                        <table v-if="(n.desglose._jornadas ?? []).length > 0" class="w-full text-xs">
                            <thead>
                                <tr class="border-b border-slate-200">
                                    <th class="py-1.5 text-left font-medium text-slate-500">Fecha</th>
                                    <th class="py-1.5 text-left font-medium text-slate-500">Actividad (Bodega/Ruta)</th>
                                    <th class="py-1.5 text-right font-medium text-slate-500">Sueldo día</th>
                                    <th class="py-1.5 text-right font-medium text-slate-500">Tipo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="j in n.desglose._jornadas" :key="j.fecha" class="border-b border-slate-100">
                                    <td class="py-1.5 text-slate-800 tabular-nums">{{ fmtFecha(j.fecha) }}</td>
                                    <td class="py-1.5 text-slate-600">{{ j.detalle ?? '—' }}</td>
                                    <td class="py-1.5 text-right text-slate-800 tabular-nums">{{ fmt(n.sueldo_diario) }}</td>
                                    <td class="py-1.5 text-right text-slate-600">{{ tipoPagoLabel(j.tipo_pago as any, j.traslape_pct ?? null) }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <table v-if="(n.desglose._rutas ?? []).length > 0" class="mt-3 w-full text-xs">
                            <thead>
                                <tr class="border-b border-slate-200">
                                    <th class="py-1.5 text-left font-medium text-slate-500">Rutas detectadas</th>
                                    <th class="py-1.5 text-left font-medium text-slate-500">Vehículo</th>
                                    <th class="py-1.5 text-right font-medium text-slate-500">Monto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="ruta in n.desglose._rutas" :key="ruta.fecha + ruta.vehiculo + ruta.distancia" class="border-b border-slate-100">
                                    <td class="py-1.5 text-slate-800 tabular-nums">{{ fmtFecha(ruta.fecha) }}</td>
                                    <td class="py-1.5 text-slate-600">{{ ruta.vehiculo }} · {{ ruta.distancia }}</td>
                                    <td class="py-1.5 text-right text-slate-800 tabular-nums">{{ fmt(ruta.monto) }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <p v-if="(n.desglose._jornadas ?? []).length === 0" class="text-xs text-slate-500">Sin jornadas registradas en este período.</p>
                    </template>

                    <div v-if="(n.desglose._prestamo_detalle ?? []).length > 0" class="mt-3 border-t pt-2">
                        <p class="mb-1 text-xs font-semibold text-slate-600">Cuotas de préstamo aplicadas:</p>
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="border-b border-slate-200">
                                    <th class="py-1 text-left font-medium text-slate-500">Plazo</th>
                                    <th class="py-1 text-left font-medium text-slate-500">Concepto</th>
                                    <th class="py-1 text-center font-medium text-slate-500">Fecha</th>
                                    <th class="py-1 text-right font-medium text-slate-500">Monto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="c in n.desglose._prestamo_detalle" :key="c.id" class="border-b border-slate-100">
                                    <td class="py-1 text-slate-800">#{{ c.numero_plazo }}</td>
                                    <td class="py-1 text-slate-600">{{ c.concepto ?? '—' }}</td>
                                    <td class="py-1 text-center text-slate-600 tabular-nums">{{ fmtFecha(c.fecha_programada) }}</td>
                                    <td class="py-1 text-right text-red-600 tabular-nums">-{{ fmt(c.monto) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <p v-if="n.comentario" class="mt-3 border-t pt-2 text-xs text-slate-500">
                        <span class="font-medium text-slate-600">Comentario:</span> {{ n.comentario }}
                    </p>
                </div>

                <p v-else class="px-8 pb-4 text-xs text-slate-500">
                    Detalle no disponible — nómina guardada antes de la actualización de desglose.
                </p>

                <div class="border-t px-8 pb-6 pt-4">
                    <div class="grid grid-cols-2 gap-8 pt-2">
                        <div class="text-center">
                            <div class="mx-auto mb-1 h-px w-48 bg-slate-300"></div>
                            <p class="text-xs text-slate-500">Firma del colaborador</p>
                        </div>
                        <div class="text-center">
                            <div class="mx-auto mb-1 h-px w-48 bg-slate-300"></div>
                            <p class="text-xs text-slate-500">Firma del administrador</p>
                        </div>
                    </div>
                    <p class="mt-4 text-center text-xs text-slate-400">
                        Este comprobante fue generado electrónicamente. Folio N-{{ String(n.id).padStart(5, '0') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
@media print {
    @page {
        margin: 15mm 10mm;
        size: letter portrait;
    }
    body {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .print-container {
        padding: 0 !important;
        background: white !important;
    }
    .page-break {
        page-break-before: always;
        break-before: page;
    }
    .page-break:first-child {
        page-break-before: auto;
        break-before: auto;
    }
    .nomina-card {
        page-break-inside: avoid;
        break-inside: avoid;
    }
}
</style>
