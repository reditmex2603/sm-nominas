<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Printer, ArrowLeft } from '@lucide/vue';
import { onMounted } from 'vue';
import PrintLogo from '@/components/PrintLogo.vue';
import { fmtFecha } from '@/lib/fecha';

type TipoColaborador =
    'COLABORADOR BASE' | 'FREELANCE' | 'CONDUCTOR' | 'CONDUCTOR BASE';

interface Colaborador {
    id: number;
    nombre: string;
    apellidos: string;
    tipo: TipoColaborador;
    categoria: string | null;
    nivel: number | null;
}

interface Perfil {
    fotografia_url: string | null;
    alias: string | null;
    fecha_ingreso: string | null;
    fecha_nacimiento: string | null;
    genero: string | null;
    correo: string | null;
    telefono: string | null;
    whatsapp: string | null;
    redes_sociales: string | null;
    domicilio: string | null;
    ubicacion_maps: string | null;
    tipo_sangre: string | null;
    alergias: string | null;
    padecimientos_cronicos: string | null;
    numero_seguro_social: string | null;
    contacto_emergencia_1_nombre: string | null;
    contacto_emergencia_1_parentesco: string | null;
    contacto_emergencia_1_telefono: string | null;
    contacto_emergencia_2_nombre: string | null;
    contacto_emergencia_2_parentesco: string | null;
    contacto_emergencia_2_telefono: string | null;
    banco: string | null;
    beneficiario: string | null;
    clave_interbancaria: string | null;
    datos_bancarios?: Array<{
        id: number;
        banco: string | null;
        beneficiario: string | null;
        clave_interbancaria: string | null;
        numero_tarjeta: string | null;
        alias: string | null;
        comentario: string | null;
    }>;
}

const props = defineProps<{
    colaborador: Colaborador;
    perfil: Perfil | null;
}>();

const tipoLabel: Record<TipoColaborador, string> = {
    'COLABORADOR BASE': 'Colaborador base',
    FREELANCE: 'Freelance',
    CONDUCTOR: 'Conductor',
    'CONDUCTOR BASE': 'Conductor base',
};

const rolLinea = (): string => {
    if (
        props.colaborador.tipo === 'COLABORADOR BASE' &&
        props.colaborador.categoria
    ) {
        return `${props.colaborador.categoria}${props.colaborador.nivel ? ` · Nivel ${props.colaborador.nivel}` : ''}`;
    }

    return tipoLabel[props.colaborador.tipo];
};

const iniciales = (): string => {
    const n = props.colaborador.nombre ?? '';
    const a = props.colaborador.apellidos ?? '';

    return `${n.charAt(0)}${a.charAt(0)}`.toUpperCase();
};

const hoy = new Date().toISOString().slice(0, 10);

const doPrint = () => window.print();

onMounted(() => {
    setTimeout(doPrint, 500);
});
</script>

<template>
    <Head :title="`Perfil — ${colaborador.nombre} ${colaborador.apellidos}`" />

    <div
        class="print-container min-h-screen bg-slate-100 p-4 print:m-0 print:bg-white print:p-0"
    >
        <!-- Toolbar (solo pantalla) -->
        <div class="mb-3 flex items-center justify-between print:hidden">
            <Link
                :href="`/colaboradores/${colaborador.id}/perfil`"
                class="inline-flex items-center gap-1.5 text-sm text-slate-600 hover:text-slate-900"
            >
                <ArrowLeft class="size-4" />
                Volver al perfil
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
            <!-- Header del CV -->
            <div
                class="flex items-center gap-5 border-b-2 border-slate-800 px-6 py-3"
            >
                <PrintLogo class="mr-2" />
                <div class="flex-1">
                    <h1
                        class="text-2xl font-bold tracking-tight text-slate-900 uppercase"
                    >
                        {{ colaborador.apellidos }}, {{ colaborador.nombre }}
                    </h1>
                    <p class="text-sm font-semibold text-slate-700">
                        {{ rolLinea() }}
                    </p>
                    <p class="mt-0.5 text-[11px] text-slate-500">
                        Fecha de emisión: {{ fmtFecha(hoy) }}
                    </p>
                </div>
                <div
                    class="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-full border-2 border-slate-300 bg-slate-100"
                >
                    <img
                        v-if="perfil?.fotografia_url"
                        :src="perfil.fotografia_url"
                        alt="Fotografía"
                        class="size-full object-cover"
                    />
                    <span v-else class="text-xl font-bold text-slate-500">{{
                        iniciales()
                    }}</span>
                </div>
            </div>

            <!-- Datos personales -->
            <section class="border-b border-slate-200 px-6 py-3">
                <h2
                    class="mb-1.5 text-xs font-bold tracking-wide text-slate-700 uppercase"
                >
                    Datos personales
                </h2>

                <dl
                    class="grid grid-cols-2 gap-x-6 gap-y-1 text-[13px] md:grid-cols-3"
                >
                    <div>
                        <dt
                            class="text-[10px] font-medium tracking-wide text-slate-500 uppercase"
                        >
                            Alias
                        </dt>
                        <dd class="text-slate-900">
                            {{ perfil?.alias || '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt
                            class="text-[10px] font-medium tracking-wide text-slate-500 uppercase"
                        >
                            Fecha de nacimiento
                        </dt>
                        <dd class="text-slate-900">
                            {{
                                perfil?.fecha_nacimiento
                                    ? fmtFecha(perfil.fecha_nacimiento)
                                    : '—'
                            }}
                        </dd>
                    </div>
                    <div>
                        <dt
                            class="text-[10px] font-medium tracking-wide text-slate-500 uppercase"
                        >
                            Género
                        </dt>
                        <dd class="text-slate-900">
                            {{ perfil?.genero || '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt
                            class="text-[10px] font-medium tracking-wide text-slate-500 uppercase"
                        >
                            Fecha de ingreso
                        </dt>
                        <dd class="text-slate-900">
                            {{
                                perfil?.fecha_ingreso
                                    ? fmtFecha(perfil.fecha_ingreso)
                                    : '—'
                            }}
                        </dd>
                    </div>
                    <div>
                        <dt
                            class="text-[10px] font-medium tracking-wide text-slate-500 uppercase"
                        >
                            Correo
                        </dt>
                        <dd class="break-words text-slate-900">
                            {{ perfil?.correo || '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt
                            class="text-[10px] font-medium tracking-wide text-slate-500 uppercase"
                        >
                            Teléfono
                        </dt>
                        <dd class="text-slate-900">
                            {{ perfil?.telefono || '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt
                            class="text-[10px] font-medium tracking-wide text-slate-500 uppercase"
                        >
                            WhatsApp
                        </dt>
                        <dd class="text-slate-900">
                            {{ perfil?.whatsapp || '—' }}
                        </dd>
                    </div>
                    <div class="col-span-2">
                        <dt
                            class="text-[10px] font-medium tracking-wide text-slate-500 uppercase"
                        >
                            Redes sociales
                        </dt>
                        <dd class="break-words text-slate-900">
                            {{ perfil?.redes_sociales || '—' }}
                        </dd>
                    </div>
                    <div v-if="perfil?.ubicacion_maps" class="col-span-2">
                        <dt
                            class="text-[10px] font-medium tracking-wide text-slate-500 uppercase"
                        >
                            Ubicación
                        </dt>
                        <dd class="break-all text-slate-900">
                            {{ perfil.ubicacion_maps }}
                        </dd>
                    </div>
                    <div class="col-span-3">
                        <dt
                            class="text-[10px] font-medium tracking-wide text-slate-500 uppercase"
                        >
                            Domicilio
                        </dt>
                        <dd class="text-slate-900">
                            {{ perfil?.domicilio || '—' }}
                        </dd>
                    </div>
                </dl>
            </section>

            <!-- Datos de emergencia -->
            <section class="border-b border-slate-200 px-6 py-3">
                <h2
                    class="mb-1.5 text-xs font-bold tracking-wide text-slate-700 uppercase"
                >
                    Datos de emergencia
                </h2>

                <div
                    class="grid grid-cols-2 gap-x-6 gap-y-1 text-[13px] md:grid-cols-3"
                >
                    <div>
                        <dt
                            class="text-[10px] font-medium tracking-wide text-slate-500 uppercase"
                        >
                            Tipo de sangre
                        </dt>
                        <dd class="text-slate-900">
                            {{ perfil?.tipo_sangre || '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt
                            class="text-[10px] font-medium tracking-wide text-slate-500 uppercase"
                        >
                            Número de seguro social
                        </dt>
                        <dd class="text-slate-900">
                            {{ perfil?.numero_seguro_social || '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt
                            class="text-[10px] font-medium tracking-wide text-slate-500 uppercase"
                        >
                            Padecimientos crónicos
                        </dt>
                        <dd class="text-slate-900">
                            {{ perfil?.padecimientos_cronicos || '—' }}
                        </dd>
                    </div>
                    <div class="col-span-2">
                        <dt
                            class="text-[10px] font-medium tracking-wide text-slate-500 uppercase"
                        >
                            Alergias
                        </dt>
                        <dd class="text-slate-900">
                            {{ perfil?.alergias || '—' }}
                        </dd>
                    </div>
                </div>

                <div
                    class="mt-2 grid grid-cols-2 gap-6 border-t border-slate-100 pt-2"
                >
                    <div>
                        <p
                            class="mb-1 text-[10px] font-semibold tracking-wide text-slate-600 uppercase"
                        >
                            Contacto de emergencia 1
                        </p>
                        <dl class="space-y-0.5 text-[13px]">
                            <div>
                                <dt
                                    class="text-[10px] font-medium tracking-wide text-slate-500 uppercase"
                                >
                                    Nombre
                                </dt>
                                <dd class="text-slate-900">
                                    {{
                                        perfil?.contacto_emergencia_1_nombre ||
                                        '—'
                                    }}
                                </dd>
                            </div>
                            <div>
                                <dt
                                    class="text-[10px] font-medium tracking-wide text-slate-500 uppercase"
                                >
                                    Parentesco
                                </dt>
                                <dd class="text-slate-900">
                                    {{
                                        perfil?.contacto_emergencia_1_parentesco ||
                                        '—'
                                    }}
                                </dd>
                            </div>
                            <div>
                                <dt
                                    class="text-[10px] font-medium tracking-wide text-slate-500 uppercase"
                                >
                                    Teléfono
                                </dt>
                                <dd class="text-slate-900">
                                    {{
                                        perfil?.contacto_emergencia_1_telefono ||
                                        '—'
                                    }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                    <div>
                        <p
                            class="mb-1 text-[10px] font-semibold tracking-wide text-slate-600 uppercase"
                        >
                            Contacto de emergencia 2
                        </p>
                        <dl class="space-y-0.5 text-[13px]">
                            <div>
                                <dt
                                    class="text-[10px] font-medium tracking-wide text-slate-500 uppercase"
                                >
                                    Nombre
                                </dt>
                                <dd class="text-slate-900">
                                    {{
                                        perfil?.contacto_emergencia_2_nombre ||
                                        '—'
                                    }}
                                </dd>
                            </div>
                            <div>
                                <dt
                                    class="text-[10px] font-medium tracking-wide text-slate-500 uppercase"
                                >
                                    Parentesco
                                </dt>
                                <dd class="text-slate-900">
                                    {{
                                        perfil?.contacto_emergencia_2_parentesco ||
                                        '—'
                                    }}
                                </dd>
                            </div>
                            <div>
                                <dt
                                    class="text-[10px] font-medium tracking-wide text-slate-500 uppercase"
                                >
                                    Teléfono
                                </dt>
                                <dd class="text-slate-900">
                                    {{
                                        perfil?.contacto_emergencia_2_telefono ||
                                        '—'
                                    }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </section>

            <!-- Datos bancarios -->
            <section class="px-6 py-3">
                <h2
                    class="mb-1.5 text-xs font-bold tracking-wide text-slate-700 uppercase"
                >
                    Datos bancarios
                </h2>

                <div
                    v-if="
                        perfil?.datos_bancarios &&
                        perfil.datos_bancarios.length > 0
                    "
                    class="space-y-2"
                >
                    <div
                        v-for="b in perfil.datos_bancarios"
                        :key="b.id"
                        class="rounded border border-slate-200 p-2"
                    >
                        <p class="text-[12px] font-semibold text-slate-800">
                            {{ b.alias || b.banco || 'Registro bancario' }}
                        </p>
                        <dl
                            class="mt-1 grid grid-cols-2 gap-x-6 gap-y-1 text-[13px] md:grid-cols-4"
                        >
                            <div>
                                <dt
                                    class="text-[10px] font-medium tracking-wide text-slate-500 uppercase"
                                >
                                    Banco
                                </dt>
                                <dd class="text-slate-900">
                                    {{ b.banco || '—' }}
                                </dd>
                            </div>
                            <div>
                                <dt
                                    class="text-[10px] font-medium tracking-wide text-slate-500 uppercase"
                                >
                                    Beneficiario
                                </dt>
                                <dd class="text-slate-900">
                                    {{ b.beneficiario || '—' }}
                                </dd>
                            </div>
                            <div>
                                <dt
                                    class="text-[10px] font-medium tracking-wide text-slate-500 uppercase"
                                >
                                    CLABE
                                </dt>
                                <dd class="text-slate-900 tabular-nums">
                                    {{ b.clave_interbancaria || '—' }}
                                </dd>
                            </div>
                            <div>
                                <dt
                                    class="text-[10px] font-medium tracking-wide text-slate-500 uppercase"
                                >
                                    Número de tarjeta
                                </dt>
                                <dd class="text-slate-900 tabular-nums">
                                    {{ b.numero_tarjeta || '—' }}
                                </dd>
                            </div>
                            <div
                                v-if="b.comentario"
                                class="col-span-2 md:col-span-4"
                            >
                                <dt
                                    class="text-[10px] font-medium tracking-wide text-slate-500 uppercase"
                                >
                                    Comentario
                                </dt>
                                <dd class="text-slate-900">
                                    {{ b.comentario }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <dl
                    v-else
                    class="grid grid-cols-2 gap-x-6 gap-y-1 text-[13px] md:grid-cols-3"
                >
                    <div>
                        <dt
                            class="text-[10px] font-medium tracking-wide text-slate-500 uppercase"
                        >
                            Banco
                        </dt>
                        <dd class="text-slate-900">
                            {{ perfil?.banco || '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt
                            class="text-[10px] font-medium tracking-wide text-slate-500 uppercase"
                        >
                            Beneficiario
                        </dt>
                        <dd class="text-slate-900">
                            {{ perfil?.beneficiario || '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt
                            class="text-[10px] font-medium tracking-wide text-slate-500 uppercase"
                        >
                            CLABE
                        </dt>
                        <dd class="text-slate-900 tabular-nums">
                            {{ perfil?.clave_interbancaria || '—' }}
                        </dd>
                    </div>
                </dl>
            </section>

            <!-- Footer -->
            <div class="border-t px-6 pt-2 pb-3">
                <p class="text-center text-[10px] text-slate-400">
                    Este documento fue generado electrónicamente. Colaborador:
                    {{ colaborador.nombre }} {{ colaborador.apellidos }} ·
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
