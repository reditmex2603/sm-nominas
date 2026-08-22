<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Printer, ArrowLeft } from '@lucide/vue';
import { onMounted } from 'vue';
import PrintLogo from '@/components/PrintLogo.vue';
import { fmtFecha } from '@/lib/fecha';

type Tamano = 'CHICO' | 'MEDIANO' | 'GRANDE';

interface Evento {
    id: number;
    nombre: string;
    lugar: string | null;
    fecha_inicio: string | null;
    fecha_fin: string | null;
    tamano: Tamano;
}

interface ResumenEvento {
    dias: number | null;
    total_colaboradores: number;
    base_count: number;
    freelance_count: number;
    conductor_count: number;
    subtotal_nomina_freelance: number;
    subtotal_nomina_base: number;
    subtotal_nomina: number;
    subtotal_viaticos: number;
    subtotal_servicios: number;
    total_gastos: number;
    total_pagado: number;
    total_por_pagar: number;
    cotizacion_total: number;
}

defineProps<{
    evento: Evento;
    resumen: ResumenEvento;
}>();

const tamanoLabel: Record<Tamano, string> = {
    CHICO: 'Chico',
    MEDIANO: 'Mediano',
    GRANDE: 'Grande',
};

const fmtMoney = (val: number | string) =>
    new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', minimumFractionDigits: 2 }).format(Number(val));

const doPrint = () => window.print();

onMounted(() => {
    setTimeout(doPrint, 500);
});
</script>

<template>
    <Head :title="`Resumen de gastos — ${evento.nombre}`" />

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
                        <h1 class="text-2xl font-bold uppercase tracking-tight text-slate-900">Resumen de Gastos de Evento</h1>
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
                        <p class="text-sm font-medium text-slate-900">{{ tamanoLabel[evento.tamano] }}</p>
                        <p class="mt-2 text-xs text-slate-500">Fecha de emisión</p>
                        <p class="text-sm font-medium text-slate-900">{{ fmtFecha(new Date().toISOString().slice(0, 10)) }}</p>
                    </div>
                </div>
            </div>

            <!-- Rasgos generales -->
            <div class="grid grid-cols-4 gap-4 border-b px-8 py-4">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Días del evento</p>
                    <p class="mt-0.5 text-lg font-semibold text-slate-900">{{ resumen.dias ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Colaboradores asignados</p>
                    <p class="mt-0.5 text-lg font-semibold text-slate-900">{{ resumen.total_colaboradores }}</p>
                    <p class="mt-0.5 text-xs text-slate-500">
                        {{ resumen.base_count }} base · {{ resumen.freelance_count }} freelance
                        <template v-if="resumen.conductor_count"> · {{ resumen.conductor_count }} conductor</template>
                    </p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total cotizado</p>
                    <p class="mt-0.5 text-lg font-semibold text-slate-900">{{ fmtMoney(resumen.cotizacion_total) }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total gastos</p>
                    <p class="mt-0.5 text-lg font-semibold text-red-600">{{ fmtMoney(resumen.total_gastos) }}</p>
                </div>
            </div>

            <!-- Desglose de gastos -->
            <div class="px-8 py-4">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-base font-bold uppercase tracking-wide text-slate-700">Gastos del evento</h2>
                    <span class="text-sm font-semibold text-slate-900">{{ fmtMoney(resumen.total_gastos) }}</span>
                </div>

                <table class="w-full text-sm">
                    <tbody>
                        <tr class="border-b border-slate-200">
                            <td class="py-2 text-slate-600">Nómina freelance</td>
                            <td class="py-2 text-right tabular-nums text-slate-800">{{ fmtMoney(resumen.subtotal_nomina_freelance) }}</td>
                        </tr>
                        <tr class="border-b border-slate-200">
                            <td class="py-2 text-slate-600">
                                Nómina base - extra por evento
                                <span class="block text-xs text-slate-400">solo el bono de los días de evento</span>
                            </td>
                            <td class="py-2 text-right tabular-nums text-slate-800">{{ fmtMoney(resumen.subtotal_nomina_base) }}</td>
                        </tr>
                        <tr class="border-b border-slate-200">
                            <td class="py-2 text-slate-600">Viáticos</td>
                            <td class="py-2 text-right tabular-nums text-slate-800">{{ fmtMoney(resumen.subtotal_viaticos) }}</td>
                        </tr>
                        <tr class="border-b border-slate-200">
                            <td class="py-2 text-slate-600">Servicios profesionales</td>
                            <td class="py-2 text-right tabular-nums text-slate-800">{{ fmtMoney(resumen.subtotal_servicios) }}</td>
                        </tr>
                        <tr class="border-b border-slate-300">
                            <td class="py-2.5 font-bold uppercase tracking-wide text-slate-900">Total gastos</td>
                            <td class="py-2.5 text-right text-base font-bold tabular-nums text-red-600">{{ fmtMoney(resumen.total_gastos) }}</td>
                        </tr>
                    </tbody>
                </table>

                <p class="mt-3 text-xs text-slate-500">
                    Nóminas: pagado {{ fmtMoney(resumen.total_pagado) }} · por pagar {{ fmtMoney(resumen.total_por_pagar) }}
                </p>
            </div>

            <!-- Footer -->
            <div class="border-t px-8 pb-6 pt-4">
                <p class="text-center text-xs text-slate-400">
                    Este documento fue generado electrónicamente. Evento: {{ evento.nombre }} · {{ fmtFecha(new Date().toISOString().slice(0, 10)) }}
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