<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Printer, Truck } from '@lucide/vue';
import { onMounted } from 'vue';
import PrintLogo from '@/components/PrintLogo.vue';
import { fmtFecha } from '@/lib/fecha';

type Pertenencia = 'PROPIA' | 'RENTADA';

interface Unidad {
    id: number;
    marca: string;
    modelo: string;
    numero_placas: string | null;
    pertenencia: Pertenencia;
    alias: string | null;
    numero_serie: string | null;
    numero_poliza_seguro: string | null;
    vigencia_poliza_seguro: string | null;
    vigencia_verificacion: string | null;
    tipo_engomado: string | null;
    color_engomado: string | null;
    fotografia_url: string | null;
    placas_documento_url: string | null;
    tarjeta_circulacion_documento_url: string | null;
    poliza_seguro_documento_url: string | null;
    verificacion_documento_url: string | null;
    tenencia_documento_url: string | null;
    vehiculo: { id: number; nombre: string } | null;
}

const props = defineProps<{
    unidad: Unidad;
}>();

const pertenenciaLabel: Record<Pertenencia, string> = {
    PROPIA: 'Propia',
    RENTADA: 'Rentada',
};

interface DocRef { clave: string; label: string }

const documentos: DocRef[] = [
    { clave: 'placas_documento_url', label: 'Placas' },
    { clave: 'tarjeta_circulacion_documento_url', label: 'Tarjeta de circulación' },
    { clave: 'poliza_seguro_documento_url', label: 'Póliza de seguro' },
    { clave: 'verificacion_documento_url', label: 'Verificación' },
    { clave: 'tenencia_documento_url', label: 'Tenencia' },
];

const iniciales = (): string => `${props.unidad.marca.charAt(0)}${props.unidad.modelo.charAt(0)}`.toUpperCase();

const hoy = new Date().toISOString().slice(0, 10);

const doPrint = () => window.print();

onMounted(() => {
    setTimeout(doPrint, 500);
});
</script>

<template>
    <Head :title="`Perfil — ${unidad.marca} ${unidad.modelo}`" />

    <div class="print-container min-h-screen bg-slate-100 p-4 print:m-0 print:bg-white print:p-0">
        <!-- Toolbar (solo pantalla) -->
        <div class="mb-3 flex items-center justify-between print:hidden">
            <Link
                :href="`/transportes/unidades/${unidad.id}`"
                class="inline-flex items-center gap-1.5 text-sm text-slate-600 hover:text-slate-900"
            >
                <ArrowLeft class="size-4" />
                Volver a la unidad
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
        <div class="mx-auto max-w-4xl bg-white shadow-lg print:shadow-none">
            <!-- Header -->
            <div class="flex items-center gap-5 border-b-2 border-slate-800 px-6 py-3">
                <PrintLogo class="mr-2" />
                <div class="flex-1">
                    <h1 class="text-2xl font-bold uppercase tracking-tight text-slate-900">
                        {{ unidad.marca }} {{ unidad.modelo }}
                    </h1>
                    <p class="text-sm font-semibold text-slate-700">
                        Unidad de transporte{{ unidad.vehiculo ? ` · ${unidad.vehiculo.nombre}` : '' }}
                    </p>
                    <p class="mt-0.5 text-[11px] text-slate-500">Fecha de emisión: {{ fmtFecha(hoy) }}</p>
                </div>
                <div class="flex size-28 shrink-0 items-center justify-center overflow-hidden rounded-lg border-2 border-slate-300 bg-slate-100">
                    <img
                        v-if="unidad.fotografia_url"
                        :src="unidad.fotografia_url"
                        :alt="`${unidad.marca} ${unidad.modelo}`"
                        class="size-full object-cover"
                    />
                    <span v-else class="flex flex-col items-center gap-1 text-xl font-bold text-slate-400">
                        <Truck class="size-8" />
                        {{ iniciales() }}
                    </span>
                </div>
            </div>

            <!-- Datos generales -->
            <section class="border-b border-slate-200 px-6 py-3">
                <h2 class="mb-1.5 text-xs font-bold uppercase tracking-wide text-slate-700">Datos generales</h2>

                <dl class="grid grid-cols-2 gap-x-6 gap-y-1 text-[13px] md:grid-cols-3">
                    <div>
                        <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Placas</dt>
                        <dd class="text-slate-900">{{ unidad.numero_placas || '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Pertenencia</dt>
                        <dd class="text-slate-900">{{ pertenenciaLabel[unidad.pertenencia] }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Categoría (tarifa)</dt>
                        <dd class="text-slate-900">{{ unidad.vehiculo?.nombre || '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Alias</dt>
                        <dd class="text-slate-900">{{ unidad.alias || '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Número de serie</dt>
                        <dd class="text-slate-900 break-all tabular-nums">{{ unidad.numero_serie || '—' }}</dd>
                    </div>
                </dl>
            </section>

            <!-- Póliza -->
            <section class="border-b border-slate-200 px-6 py-3">
                <h2 class="mb-1.5 text-xs font-bold uppercase tracking-wide text-slate-700">Póliza de seguro</h2>

                <dl class="grid grid-cols-2 gap-x-6 gap-y-1 text-[13px] md:grid-cols-3">
                    <div>
                        <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Número de póliza</dt>
                        <dd class="text-slate-900">{{ unidad.numero_poliza_seguro || '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Vigencia</dt>
                        <dd class="text-slate-900">{{ unidad.vigencia_poliza_seguro ? fmtFecha(unidad.vigencia_poliza_seguro) : '—' }}</dd>
                    </div>
                </dl>
            </section>

            <!-- Verificación -->
            <section class="border-b border-slate-200 px-6 py-3">
                <h2 class="mb-1.5 text-xs font-bold uppercase tracking-wide text-slate-700">Verificación</h2>

                <dl class="grid grid-cols-2 gap-x-6 gap-y-1 text-[13px] md:grid-cols-3">
                    <div>
                        <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Vencimiento</dt>
                        <dd class="text-slate-900">{{ unidad.vigencia_verificacion ? fmtFecha(unidad.vigencia_verificacion) : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Tipo de engomado</dt>
                        <dd class="text-slate-900">{{ unidad.tipo_engomado || '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Color de engomado</dt>
                        <dd class="text-slate-900">{{ unidad.color_engomado || '—' }}</dd>
                    </div>
                </dl>
            </section>

            <!-- Documentos en archivo -->
            <section class="px-6 py-3">
                <h2 class="mb-1.5 text-xs font-bold uppercase tracking-wide text-slate-700">Documentos en archivo</h2>

                <dl class="grid grid-cols-2 gap-x-6 gap-y-1 text-[13px] md:grid-cols-3">
                    <div v-for="d in documentos" :key="d.clave">
                        <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">{{ d.label }}</dt>
                        <dd class="text-slate-900">{{ (unidad as unknown as Record<string, string | null>)[d.clave] ? 'En archivo' : '—' }}</dd>
                    </div>
                </dl>
            </section>

            <!-- Footer -->
            <div class="border-t px-6 pb-3 pt-2">
                <p class="text-center text-[10px] text-slate-400">
                    Este documento fue generado electrónicamente. Unidad: {{ unidad.marca }} {{ unidad.modelo }} ·
                    {{ fmtFecha(hoy) }}
                </p>
            </div>
        </div>
    </div>
</template>

<style>
@media print {
    @page {
        margin: 8mm 10mm;
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
}
</style>