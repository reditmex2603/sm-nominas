<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Printer, ArrowLeft } from '@lucide/vue';
import { onMounted } from 'vue';
import PrintLogo from '@/components/PrintLogo.vue';
import { fmtFecha } from '@/lib/fecha';
import { tipoPagoLabel } from '@/lib/tipoPago';

interface ColaboradorRef { id: number; nombre: string; apellidos: string }
interface Evento { id: number; nombre: string; lugar: string | null; fecha_inicio: string | null; fecha_fin: string | null; tamano: string }

interface RegistroFreelance { fecha: string; etapa: string | null; extras: string | null; contabiliza: boolean }
interface JornadaBase { fecha: string; tipo_pago: string; traslape_pct: number | null; detalle: string | null; bono: number; pct_etapas: number }
interface NominaResumenFreelance { nomina_id: number; colaborador: ColaboradorRef; estado: string; total_final: number; registros: RegistroFreelance[] }
interface NominaResumenBase { nomina_id: number; colaborador: ColaboradorRef; estado: string; periodo_inicio: string | null; periodo_fin: string | null; jornadas: JornadaBase[]; subtotal: number }

interface Resumen {
    freelance: NominaResumenFreelance[];
    base: NominaResumenBase[];
    subtotal_freelance: number;
    subtotal_base: number;
    total_pagado: number;
    total_por_pagar: number;
}

type TipoViatico = 'TRANSPORTE' | 'HOSPEDAJE' | 'ALIMENTOS' | 'CASETAS_GASOLINA' | 'OTRO';

interface Viatico {
    id: number;
    nombre: string | null;
    apellidos: string | null;
    tipo: TipoViatico;
    concepto: string;
    monto: string;
    fecha: string;
    autoriza: string | null;
    colaborador: { id: number; nombre: string; apellidos: string } | null;
}

interface ViaticosEvento {
    items: Viatico[];
    subtotal: number;
}

interface NominaFull {
    id: number;
    tipo_colaborador: string;
    periodo_inicio: string | null;
    periodo_fin: string | null;
    dias: string;
    sueldo_diario: string;
    total_base: string;
    bonos_evento: string;
    compensaciones: string;
    comentario: string | null;
    anticipos: string;
    prestamos: string;
    total_final: string;
    estado: string;
    fecha_calculo: string | null;
    colaborador: ColaboradorRef;
    desglose: Record<string, any> | null;
}

defineProps<{
    evento: Evento;
    resumen: Resumen;
    nominas: Record<number, NominaFull>;
    viaticos: ViaticosEvento;
}>();

const tipoViaticoLabel: Record<TipoViatico, string> = {
    TRANSPORTE:        'Transporte',
    HOSPEDAJE:         'Hospedaje',
    ALIMENTOS:         'Alimentos',
    CASETAS_GASOLINA:  'Casetas y Gasolina',
    OTRO:              'Otro',
};

const nombreViatico = (v: Viatico) => v.colaborador
    ? `${v.colaborador.apellidos}, ${v.colaborador.nombre}`
    : (v.apellidos ? `${v.apellidos}, ${v.nombre}` : (v.nombre ?? 'General'));

const fmtMoney = (val: number | string) =>
    new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', minimumFractionDigits: 2 }).format(Number(val));

const doPrint = () => window.print();

onMounted(() => {
 setTimeout(doPrint, 500); 
});
</script>

<template>
    <Head :title="`Nómina — ${evento.nombre}`" />

    <div class="print-container min-h-screen bg-slate-100 p-4 print:m-0 print:bg-white print:p-0">
        <!-- Toolbar (solo pantalla) -->
        <div class="mb-4 flex items-center justify-between print:hidden">
            <Link
                :href="`/eventos/${evento.id}/asignacion`"
                class="inline-flex items-center gap-1.5 text-sm text-slate-600 hover:text-slate-900"
            >
                <ArrowLeft class="size-4" />
                Volver al evento
            </Link>
            <button
                class="inline-flex items-center gap-1.5 rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700"
                @click="doPrint"
            >
                <Printer class="size-4" />
                Imprimir
            </button>
        </div>

        <!-- Documento -->
        <div class="mx-auto max-w-5xl bg-white shadow-lg print:shadow-none">
            <!-- Header -->
            <div class="border-b-2 border-slate-800 px-8 pb-4 pt-6">
                <div class="flex items-start justify-between">
                    <div>
                        <PrintLogo class="mb-3" />
                        <h1 class="text-2xl font-bold uppercase tracking-tight text-slate-900">Nómina de Evento</h1>
                        <p class="mt-1 text-lg font-semibold text-slate-800">{{ evento.nombre }}</p>
                        <p class="mt-0.5 text-sm text-slate-500">
                            {{ evento.lugar ?? '' }}
                            <template v-if="evento.fecha_inicio || evento.fecha_fin">
                                <template v-if="evento.lugar"> · </template>
                                {{ fmtFecha(evento.fecha_inicio) }} – {{ fmtFecha(evento.fecha_fin) }}
                            </template>
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-slate-500">Tamaño</p>
                        <p class="text-sm font-medium text-slate-900">{{ evento.tamano }}</p>
                        <p class="mt-2 text-xs text-slate-500">Fecha de emisión</p>
                        <p class="text-sm font-medium text-slate-900">{{ fmtFecha(new Date().toISOString().slice(0, 10)) }}</p>
                    </div>
                </div>
            </div>

            <!-- Resumen -->
            <div class="grid grid-cols-4 gap-4 border-b px-8 py-4">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Pagado</p>
                    <p class="mt-0.5 text-lg font-semibold text-emerald-600">{{ fmtMoney(resumen.total_pagado) }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Por pagar</p>
                    <p class="mt-0.5 text-lg font-semibold text-amber-600">{{ fmtMoney(resumen.total_por_pagar) }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Subtotal Base</p>
                    <p class="mt-0.5 text-lg font-semibold text-slate-900">{{ fmtMoney(resumen.subtotal_base) }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Subtotal Freelance</p>
                    <p class="mt-0.5 text-lg font-semibold text-slate-900">{{ fmtMoney(resumen.subtotal_freelance) }}</p>
                </div>
            </div>

            <!-- Sin nóminas -->
            <p v-if="resumen.freelance.length === 0 && resumen.base.length === 0" class="px-8 py-10 text-center text-sm text-slate-500">
                Sin nóminas guardadas relacionadas con este evento.
            </p>

            <!-- Freelance -->
            <div v-if="resumen.freelance.length > 0" class="px-8 py-4">
                <h2 class="mb-3 text-base font-bold uppercase tracking-wide text-slate-700">Freelance</h2>

                <div v-for="f in resumen.freelance" :key="f.nomina_id" class="mb-6 break-inside-avoid">
                    <div class="mb-2 flex items-center justify-between border-b pb-1">
                        <p class="text-sm font-semibold text-slate-900">
                            {{ f.colaborador.apellidos }}, {{ f.colaborador.nombre }}
                        </p>
                        <div class="flex items-center gap-3">
                            <span class="text-xs" :class="f.estado === 'PAGADO' ? 'text-emerald-600' : 'text-amber-600'">
                                {{ f.estado }}
                            </span>
                            <span class="text-sm font-bold text-slate-900">{{ fmtMoney(f.total_final) }}</span>
                        </div>
                    </div>

                    <table v-if="f.registros.length > 0" class="mb-1 w-full text-xs">
                        <thead>
                            <tr class="border-b border-slate-200">
                                <th class="py-1 text-left font-medium text-slate-500">Fecha</th>
                                <th class="py-1 text-left font-medium text-slate-500">Etapa</th>
                                <th class="py-1 text-center font-medium text-slate-500">Estado</th>
                                <th class="py-1 text-left font-medium text-slate-500">Extras</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(r, i) in f.registros" :key="i" class="border-b border-slate-100">
                                <td class="py-1 text-slate-800 tabular-nums">{{ fmtFecha(r.fecha) }}</td>
                                <td class="py-1 text-slate-600">{{ r.etapa ?? '—' }}</td>
                                <td class="py-1 text-center" :class="r.contabiliza ? 'text-emerald-600' : 'text-amber-600'">
                                    {{ r.contabiliza ? 'Contabilizada' : 'Sin validar' }}
                                </td>
                                <td class="py-1 text-slate-600">{{ r.extras ?? '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-else class="text-xs text-slate-500">Sin registros.</p>

                    <!-- Datos completos de la nómina freelance -->
                    <div v-if="nominas[f.nomina_id]" class="mt-1 text-xs text-slate-500">
                        <template v-if="parseFloat(nominas[f.nomina_id].compensaciones) > 0">
                            Compensación: {{ fmtMoney(nominas[f.nomina_id].compensaciones) }}
                        </template>
                        <template v-if="nominas[f.nomina_id].comentario">
                            · {{ nominas[f.nomina_id].comentario }}
                        </template>
                    </div>
                </div>
            </div>

            <!-- Base -->
            <div v-if="resumen.base.length > 0" class="border-t px-8 py-4">
                <h2 class="mb-1 text-base font-bold uppercase tracking-wide text-slate-700">Base</h2>
                <p class="mb-3 text-xs text-slate-500">Extra por día de evento (el sueldo base no es específico de este evento).</p>

                <div v-for="b in resumen.base" :key="b.nomina_id" class="mb-6 break-inside-avoid">
                    <div class="mb-2 flex items-center justify-between border-b pb-1">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">
                                {{ b.colaborador.apellidos }}, {{ b.colaborador.nombre }}
                            </p>
                            <p class="text-xs text-slate-500">
                                Nómina: {{ fmtFecha(b.periodo_inicio) }} – {{ fmtFecha(b.periodo_fin) }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-xs" :class="b.estado === 'PAGADO' ? 'text-emerald-600' : 'text-amber-600'">
                                {{ b.estado }}
                            </span>
                            <span class="text-sm font-bold text-slate-900">{{ fmtMoney(b.subtotal) }}</span>
                        </div>
                    </div>

                    <table v-if="b.jornadas.length > 0" class="mb-1 w-full text-xs">
                        <thead>
                            <tr class="border-b border-slate-200">
                                <th class="py-1 text-left font-medium text-slate-500">Fecha</th>
                                <th class="py-1 text-left font-medium text-slate-500">Detalle</th>
                                <th class="py-1 text-right font-medium text-slate-500">Tipo</th>
                                <th class="py-1 text-right font-medium text-slate-500">Bono</th>
                                <th class="py-1 text-right font-medium text-slate-500">% Etapas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="j in b.jornadas" :key="j.fecha" class="border-b border-slate-100">
                                <td class="py-1 text-slate-800 tabular-nums">{{ fmtFecha(j.fecha) }}</td>
                                <td class="py-1 text-slate-600">{{ j.detalle ?? '—' }}</td>
                                <td class="py-1 text-right text-slate-600">{{ tipoPagoLabel(j.tipo_pago as any, j.traslape_pct) }}</td>
                                <td class="py-1 text-right text-slate-800 tabular-nums font-medium">{{ fmtMoney(j.bono) }}</td>
                                <td class="py-1 text-right text-slate-600 tabular-nums">{{ j.pct_etapas }}%</td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-else class="text-xs text-slate-500">Sin jornadas.</p>
                </div>
            </div>

            <!-- Viáticos -->
            <div v-if="viaticos.items.length > 0" class="border-t px-8 py-4">
                <div class="mb-2 flex items-center justify-between">
                    <h2 class="text-base font-bold uppercase tracking-wide text-slate-700">Viáticos</h2>
                    <span class="text-sm font-semibold text-slate-900">{{ fmtMoney(viaticos.subtotal) }}</span>
                </div>

                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b border-slate-200">
                            <th class="py-1 text-left font-medium text-slate-500">Fecha</th>
                            <th class="py-1 text-left font-medium text-slate-500">Nombre</th>
                            <th class="py-1 text-left font-medium text-slate-500">Tipo</th>
                            <th class="py-1 text-left font-medium text-slate-500">Concepto</th>
                            <th class="py-1 text-right font-medium text-slate-500">Monto</th>
                            <th class="py-1 text-left font-medium text-slate-500">Autoriza</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="v in viaticos.items" :key="v.id" class="border-b border-slate-100">
                            <td class="py-1 whitespace-nowrap tabular-nums text-slate-800">{{ fmtFecha(v.fecha) }}</td>
                            <td class="py-1 font-medium text-slate-900">{{ nombreViatico(v) }}</td>
                            <td class="py-1 text-slate-600">{{ tipoViaticoLabel[v.tipo] ?? v.tipo }}</td>
                            <td class="py-1 text-slate-600">{{ v.concepto }}</td>
                            <td class="py-1 text-right tabular-nums font-medium text-slate-900">{{ fmtMoney(v.monto) }}</td>
                            <td class="py-1 text-slate-500">{{ v.autoriza ?? '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Footer -->
            <div class="border-t px-8 pb-6 pt-4">
                <p class="text-center text-xs text-slate-400">
                    Este reporte fue generado electrónicamente. Evento: {{ evento.nombre }} · {{ fmtFecha(new Date().toISOString().slice(0, 10)) }}
                </p>
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
    .break-inside-avoid {
        break-inside: avoid;
        page-break-inside: avoid;
    }
}
</style>
