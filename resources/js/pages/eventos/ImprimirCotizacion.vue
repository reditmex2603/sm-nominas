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
    nombre_contratante: string | null;
    telefono_contratante: string | null;
    contacto_nombre: string | null;
    contacto_telefono: string | null;
}

interface CotizacionBaseItem {
    colaborador_id: number;
    nombre: string;
    apellidos: string;
    categoria: string | null;
    nivel: number | null;
    sueldo: number;
    bono: number;
    total: number;
}

interface Cotizacion {
    dias: number | null;
    base: CotizacionBaseItem[];
    total_base: number;
    freelance_count: number;
    pago_por_freelance: number;
    total_freelance: number;
    total: number;
}

interface Requisitos {
    base: Record<string, Record<string, number>>;
    freelance: number;
}

const props = defineProps<{
    evento: Evento;
    cotizacion: Cotizacion;
    requisitos: Requisitos;
}>();

const tamanoLabel: Record<Tamano, string> = {
    CHICO: 'Chico',
    MEDIANO: 'Mediano',
    GRANDE: 'Grande',
};

const fmtMoney = (val: number | string) =>
    new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', minimumFractionDigits: 2 }).format(Number(val));

const requisitosLista = () => {
    const items: { label: string; cantidad: number }[] = [];

    for (const [categoria, niveles] of Object.entries(props.requisitos.base)) {
        for (const [nivel, cantidad] of Object.entries(niveles)) {
            if (cantidad > 0) {
                items.push({ label: `${categoria} · Nivel ${nivel}`, cantidad });
            }
        }
    }

    if (props.requisitos.freelance > 0) {
        items.push({ label: 'Freelance', cantidad: props.requisitos.freelance });
    }

    return items;
};

const doPrint = () => window.print();

onMounted(() => {
    setTimeout(doPrint, 500);
});
</script>

<template>
    <Head :title="`Cotización — ${evento.nombre}`" />

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
                        <h1 class="text-2xl font-bold uppercase tracking-tight text-slate-900">Cotización de Evento</h1>
                        <p class="mt-1 text-lg font-semibold text-slate-800">{{ evento.nombre }}</p>
                        <p class="mt-0.5 text-sm text-slate-500">
                            {{ evento.lugar ?? '' }}
                            <template v-if="evento.fecha_inicio || evento.fecha_fin">
                                <template v-if="evento.lugar"> · </template>
                                {{ fmtFecha(evento.fecha_inicio) }} – {{ fmtFecha(evento.fecha_fin) }}
                            </template>
                        </p>
                        <div v-if="evento.nombre_contratante || evento.contacto_nombre" class="mt-2 flex flex-wrap gap-x-6 gap-y-0.5 text-xs text-slate-600">
                            <p v-if="evento.nombre_contratante">
                                Contratante: <span class="font-medium text-slate-800">{{ evento.nombre_contratante }}</span>
                                <template v-if="evento.telefono_contratante"> · {{ evento.telefono_contratante }}</template>
                            </p>
                            <p v-if="evento.contacto_nombre">
                                Contacto: <span class="font-medium text-slate-800">{{ evento.contacto_nombre }}</span>
                                <template v-if="evento.contacto_telefono"> · {{ evento.contacto_telefono }}</template>
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-slate-500">Tamaño</p>
                        <p class="text-sm font-medium text-slate-900">{{ tamanoLabel[evento.tamano] }}</p>
                        <p class="mt-2 text-xs text-slate-500">Fecha de emisión</p>
                        <p class="text-sm font-medium text-slate-900">{{ fmtFecha(new Date().toISOString().slice(0, 10)) }}</p>
                    </div>
                </div>
            </div>

            <!-- Resumen -->
            <div class="grid grid-cols-4 gap-4 border-b px-8 py-4">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Duración</p>
                    <p class="mt-0.5 text-lg font-semibold text-slate-900">{{ cotizacion.dias ?? '—' }} día(s)</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Subtotal Base</p>
                    <p class="mt-0.5 text-lg font-semibold text-slate-900">{{ fmtMoney(cotizacion.total_base) }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Freelance</p>
                    <p class="mt-0.5 text-lg font-semibold text-slate-900">{{ fmtMoney(cotizacion.total_freelance) }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total cotizado</p>
                    <p class="mt-0.5 text-lg font-semibold text-emerald-600">{{ fmtMoney(cotizacion.total) }}</p>
                </div>
            </div>

            <!-- Requisitos de personal -->
            <div v-if="requisitosLista().length > 0" class="border-b px-8 py-4">
                <h2 class="mb-2 text-base font-bold uppercase tracking-wide text-slate-700">Personal requerido</h2>
                <div class="flex flex-wrap gap-2">
                    <span
                        v-for="r in requisitosLista()"
                        :key="r.label"
                        class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs text-slate-700"
                    >
                        {{ r.label }}: <span class="ml-1 font-semibold">{{ r.cantidad }}</span>
                    </span>
                </div>
            </div>

            <!-- Sin colaboradores -->
            <p v-if="cotizacion.base.length === 0 && cotizacion.freelance_count === 0" class="px-8 py-10 text-center text-sm text-slate-500">
                Sin colaboradores asignados todavía.
            </p>

            <!-- Detalle Base -->
            <div v-if="cotizacion.base.length > 0" class="border-b px-8 py-4">
                <h2 class="mb-3 text-base font-bold uppercase tracking-wide text-slate-700">Desglose Base</h2>

                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200">
                            <th class="py-2 text-left font-medium text-slate-500">Colaborador</th>
                            <th class="py-2 text-left font-medium text-slate-500">Categoría</th>
                            <th class="py-2 text-right font-medium text-slate-500">Sueldo</th>
                            <th class="py-2 text-right font-medium text-slate-500">Extra evento</th>
                            <th class="py-2 text-right font-medium text-slate-500">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="b in cotizacion.base" :key="b.colaborador_id" class="border-b border-slate-100">
                            <td class="py-2 font-medium whitespace-nowrap text-slate-900">{{ b.apellidos }}, {{ b.nombre }}</td>
                            <td class="py-2 text-slate-600 whitespace-nowrap">
                                {{ b.categoria }}<template v-if="b.nivel"> · Nivel {{ b.nivel }}</template>
                            </td>
                            <td class="py-2 text-right tabular-nums text-slate-800">{{ fmtMoney(b.sueldo) }}</td>
                            <td class="py-2 text-right tabular-nums text-slate-800">{{ fmtMoney(b.bono) }}</td>
                            <td class="py-2 text-right tabular-nums font-semibold text-slate-900">{{ fmtMoney(b.total) }}</td>
                        </tr>
                    </tbody>
                </table>

                <div class="mt-2 flex items-center justify-between text-sm">
                    <span class="text-slate-500">
                        {{ cotizacion.base.length }} colaborador(es) Base · {{ cotizacion.dias ?? '—' }} día(s)
                    </span>
                    <span class="font-semibold text-slate-900">{{ fmtMoney(cotizacion.total_base) }}</span>
                </div>
            </div>

            <!-- Freelance -->
            <div v-if="cotizacion.freelance_count > 0" class="border-b px-8 py-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-bold uppercase tracking-wide text-slate-700">Freelance</h2>
                    <span class="text-sm font-semibold text-slate-900">{{ fmtMoney(cotizacion.total_freelance) }}</span>
                </div>
                <p class="mt-1 text-xs text-slate-500">
                    {{ cotizacion.freelance_count }} colaborador(es) × {{ fmtMoney(cotizacion.pago_por_freelance) }} por evento completo
                </p>
            </div>

            <!-- Total -->
            <div class="bg-slate-50 px-8 py-4">
                <div class="flex items-center justify-between border-t border-slate-300 pt-3">
                    <span class="text-lg font-bold uppercase tracking-wide text-slate-900">Total cotizado</span>
                    <span class="text-lg font-bold tabular-nums text-slate-900">{{ fmtMoney(cotizacion.total) }}</span>
                </div>
                <p class="mt-1 text-right text-xs italic text-slate-500">
                    Cotización al 100% de participación — sin compensaciones ni días adicionales.
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