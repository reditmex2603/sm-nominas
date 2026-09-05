<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Printer, ArrowLeft } from '@lucide/vue';
import { onMounted } from 'vue';
import PrintLogo from '@/components/PrintLogo.vue';
import { fmtFecha } from '@/lib/fecha';

type Tamano = 'CHICO' | 'MEDIANO' | 'GRANDE';
type TipoColaborador =
    'COLABORADOR BASE' | 'FREELANCE' | 'CONDUCTOR' | 'CONDUCTOR BASE';

interface Evento {
    id: number;
    nombre: string;
    lugar: string | null;
    fecha_inicio: string | null;
    fecha_fin: string | null;
    tamano: Tamano;
    contacto_nombre: string | null;
    contacto_telefono: string | null;
}

interface Responsable {
    id: number;
    nombre: string;
    apellidos: string;
    tipo: TipoColaborador;
    telefono: string | null;
}

interface Colaborador {
    id: number;
    nombre: string;
    apellidos: string;
    tipo: TipoColaborador;
    categoria: string | null;
    nivel: number | null;
    area: string | null;
}

interface Unidad {
    id: number;
    marca: string;
    modelo: string;
    numero_placas: string | null;
    vehiculo: { id: number; nombre: string } | null;
    conductor: { id: number; nombre: string; apellidos: string } | null;
}

const props = defineProps<{
    evento: Evento;
    responsable: Responsable | null;
    dias: number | null;
    colaboradores: Colaborador[];
    unidades: Unidad[];
}>();

const tamanoLabel: Record<Tamano, string> = {
    CHICO: 'Chico',
    MEDIANO: 'Mediano',
    GRANDE: 'Grande',
};

const tipoLabel: Record<TipoColaborador, string> = {
    'COLABORADOR BASE': 'Base',
    FREELANCE: 'Freelance',
    CONDUCTOR: 'Conductor',
    'CONDUCTOR BASE': 'Conductor base',
};

const duracion = (): string => {
    if (!props.evento.fecha_inicio || !props.evento.fecha_fin) {
        return props.dias !== null ? `${props.dias} día(s)` : '—';
    }

    const base = `${props.dias} día(s)`;

    return `${base} (${fmtFecha(props.evento.fecha_inicio)} al ${fmtFecha(props.evento.fecha_fin)})`;
};

const doPrint = () => window.print();

onMounted(() => {
    setTimeout(doPrint, 500);
});
</script>

<template>
    <Head :title="`Evento a terceros — ${evento.nombre}`" />

    <div
        class="print-container min-h-screen bg-slate-100 p-4 print:m-0 print:bg-white print:p-0"
    >
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
            <div class="border-b-2 border-slate-800 px-8 pt-6 pb-4">
                <div class="flex items-start justify-between">
                    <div>
                        <PrintLogo class="mb-3" />
                        <h1
                            class="text-2xl font-bold tracking-tight text-slate-900 uppercase"
                        >
                            Datos del evento
                        </h1>
                        <p class="mt-2 text-base font-semibold text-slate-800">
                            {{ evento.nombre }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-slate-500">Tamaño</p>
                        <p class="text-sm font-medium text-slate-900">
                            {{ tamanoLabel[evento.tamano] }}
                        </p>
                        <p class="mt-2 text-xs text-slate-500">
                            Fecha de emisión
                        </p>
                        <p class="text-sm font-medium text-slate-900">
                            {{
                                fmtFecha(new Date().toISOString().slice(0, 10))
                            }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Para / Lugar / Duración / Contacto / Responsable -->
            <div
                class="grid grid-cols-2 gap-x-6 gap-y-4 border-b px-8 py-5 md:grid-cols-3"
            >
                <div class="col-span-2 md:col-span-3">
                    <p
                        class="text-xs font-medium tracking-wide text-slate-500 uppercase"
                    >
                        Para
                    </p>
                    <p class="mt-0.5 text-lg font-semibold text-slate-900">
                        A quien corresponda
                    </p>
                </div>
                <div>
                    <p
                        class="text-xs font-medium tracking-wide text-slate-500 uppercase"
                    >
                        Lugar
                    </p>
                    <p class="mt-0.5 text-sm font-medium text-slate-900">
                        {{ evento.lugar ?? '—' }}
                    </p>
                </div>
                <div>
                    <p
                        class="text-xs font-medium tracking-wide text-slate-500 uppercase"
                    >
                        Duración
                    </p>
                    <p class="mt-0.5 text-sm font-medium text-slate-900">
                        {{ duracion() }}
                    </p>
                </div>
                <div></div>
                <div>
                    <p
                        class="text-xs font-medium tracking-wide text-slate-500 uppercase"
                    >
                        Contacto
                    </p>
                    <p class="mt-0.5 text-sm font-medium text-slate-900">
                        {{ evento.contacto_nombre ?? '—' }}
                    </p>
                    <p
                        v-if="evento.contacto_telefono"
                        class="text-sm text-slate-600 tabular-nums"
                    >
                        {{ evento.contacto_telefono }}
                    </p>
                </div>
                <div>
                    <p
                        class="text-xs font-medium tracking-wide text-slate-500 uppercase"
                    >
                        Teléfono de contacto
                    </p>
                    <p
                        class="mt-0.5 text-sm font-medium text-slate-900 tabular-nums"
                    >
                        {{ evento.contacto_telefono ?? '—' }}
                    </p>
                </div>
                <div>
                    <p
                        class="text-xs font-medium tracking-wide text-slate-500 uppercase"
                    >
                        Responsable
                    </p>
                    <p class="mt-0.5 text-sm font-medium text-slate-900">
                        {{
                            responsable
                                ? `${responsable.apellidos}, ${responsable.nombre}`
                                : '—'
                        }}
                    </p>
                    <p
                        v-if="responsable?.telefono"
                        class="text-sm text-slate-600 tabular-nums"
                    >
                        {{ responsable.telefono }}
                    </p>
                </div>
                <div>
                    <p
                        class="text-xs font-medium tracking-wide text-slate-500 uppercase"
                    >
                        Teléfono del responsable
                    </p>
                    <p
                        class="mt-0.5 text-sm font-medium text-slate-900 tabular-nums"
                    >
                        {{ responsable?.telefono ?? '—' }}
                    </p>
                </div>
            </div>

            <!-- Colaboradores -->
            <div class="px-8 py-5">
                <h2
                    class="mb-2 text-sm font-bold tracking-wide text-slate-700 uppercase"
                >
                    Colaboradores
                </h2>

                <table class="w-full text-sm">
                    <thead class="border-y border-slate-300 bg-slate-50">
                        <tr>
                            <th
                                class="w-12 px-2 py-2 text-left text-xs font-medium tracking-wide text-slate-500 uppercase"
                            >
                                No.
                            </th>
                            <th
                                class="px-3 py-2 text-left text-xs font-medium tracking-wide text-slate-500 uppercase"
                            >
                                Nombre
                            </th>
                            <th
                                class="px-3 py-2 text-left text-xs font-medium tracking-wide text-slate-500 uppercase"
                            >
                                Apellidos
                            </th>
                            <th
                                class="px-3 py-2 text-left text-xs font-medium tracking-wide text-slate-500 uppercase"
                            >
                                Área
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="(c, i) in colaboradores" :key="c.id">
                            <td class="px-2 py-1.5 text-slate-600 tabular-nums">
                                {{ i + 1 }}
                            </td>
                            <td class="px-3 py-1.5 font-medium text-slate-900">
                                {{ c.nombre }}
                            </td>
                            <td class="px-3 py-1.5 text-slate-900">
                                {{ c.apellidos }}
                            </td>
                            <td class="px-3 py-1.5 text-slate-700">
                                {{
                                    c.area ??
                                    (c.tipo === 'COLABORADOR BASE'
                                        ? `${c.categoria ?? ''}${c.nivel ? ` · N${c.nivel}` : ''}`
                                        : tipoLabel[c.tipo])
                                }}
                            </td>
                        </tr>
                        <tr v-if="colaboradores.length === 0">
                            <td
                                colspan="4"
                                class="px-3 py-6 text-center text-sm text-slate-500"
                            >
                                Sin colaboradores asignados.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Unidades de transporte -->
            <div class="px-8 pb-6">
                <h2
                    class="mb-2 text-sm font-bold tracking-wide text-slate-700 uppercase"
                >
                    Unidades de transporte
                </h2>

                <table class="w-full text-sm">
                    <thead class="border-y border-slate-300 bg-slate-50">
                        <tr>
                            <th
                                class="w-12 px-2 py-2 text-left text-xs font-medium tracking-wide text-slate-500 uppercase"
                            >
                                No.
                            </th>
                            <th
                                class="px-3 py-2 text-left text-xs font-medium tracking-wide text-slate-500 uppercase"
                            >
                                Categoría
                            </th>
                            <th
                                class="px-3 py-2 text-left text-xs font-medium tracking-wide text-slate-500 uppercase"
                            >
                                Marca
                            </th>
                            <th
                                class="px-3 py-2 text-left text-xs font-medium tracking-wide text-slate-500 uppercase"
                            >
                                Placas
                            </th>
                            <th
                                class="px-3 py-2 text-left text-xs font-medium tracking-wide text-slate-500 uppercase"
                            >
                                Conductor
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="(u, i) in unidades" :key="u.id">
                            <td class="px-2 py-1.5 text-slate-600 tabular-nums">
                                {{ i + 1 }}
                            </td>
                            <td class="px-3 py-1.5 text-slate-900">
                                {{ u.vehiculo?.nombre ?? '—' }}
                            </td>
                            <td class="px-3 py-1.5 font-medium text-slate-900">
                                {{ u.marca }} {{ u.modelo }}
                            </td>
                            <td class="px-3 py-1.5 text-slate-700 tabular-nums">
                                {{ u.numero_placas ?? '—' }}
                            </td>
                            <td class="px-3 py-1.5 text-slate-900">
                                {{
                                    u.conductor
                                        ? `${u.conductor.apellidos}, ${u.conductor.nombre}`
                                        : '—'
                                }}
                            </td>
                        </tr>
                        <tr v-if="unidades.length === 0">
                            <td
                                colspan="5"
                                class="px-3 py-6 text-center text-sm text-slate-500"
                            >
                                Sin unidades asignadas.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Footer -->
            <div class="border-t border-slate-200 px-8 pt-3 pb-4">
                <p class="text-center text-[10px] text-slate-400">
                    Este documento fue generado electrónicamente para el evento
                    {{ evento.nombre }}.
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
        min-height: 0 !important;
        background: white !important;
        padding: 0 !important;
    }
    .print\\:shadow-none {
        box-shadow: none !important;
    }
}
</style>
