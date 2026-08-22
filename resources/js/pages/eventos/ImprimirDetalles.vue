<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Printer, ArrowLeft } from '@lucide/vue';
import { onMounted } from 'vue';
import PrintLogo from '@/components/PrintLogo.vue';
import { fmtFecha } from '@/lib/fecha';

type TipoColaborador = 'COLABORADOR BASE' | 'FREELANCE' | 'CONDUCTOR' | 'CONDUCTOR BASE';
type Categoria = 'Encargado de área' | 'Técnico' | 'Stagehand SM';
type Tamano = 'CHICO' | 'MEDIANO' | 'GRANDE';

interface Evento {
    id: number;
    nombre: string;
    lugar: string | null;
    fecha_inicio: string | null;
    fecha_fin: string | null;
    tamano: Tamano;
    pago_por_evento_completo: string;
    descripcion: string | null;
    observaciones_tecnicas: string | null;
    enlace_ubicacion: string | null;
    contacto_nombre: string | null;
    contacto_telefono: string | null;
}

interface Requisitos {
    base: Record<string, Record<string, number>>;
    freelance: number;
}

interface CotizacionBaseItem {
    colaborador_id: number;
    nombre: string;
    apellidos: string;
    categoria: Categoria | null;
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

interface Colaborador {
    id: number;
    nombre: string;
    apellidos: string;
    tipo: TipoColaborador;
    categoria: Categoria | null;
    nivel: number | null;
    sueldo_diario: number;
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
}

interface PerfilItem {
    colaborador: Colaborador;
    perfil: Perfil | null;
}

interface UnidadTransporte {
    id: number;
    marca: string;
    modelo: string;
    numero_placas: string | null;
    pertenencia: 'PROPIA' | 'RENTADA';
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
    evento: Evento;
    cotizacion: Cotizacion;
    requisitos: Requisitos;
    perfiles: PerfilItem[];
    unidades: UnidadTransporte[];
}>();

const tamanoLabel: Record<Tamano, string> = {
    CHICO: 'Chico',
    MEDIANO: 'Mediano',
    GRANDE: 'Grande',
};

const tipoLabel: Record<TipoColaborador, string> = {
    'COLABORADOR BASE': 'Colaborador base',
    'FREELANCE': 'Freelance',
    'CONDUCTOR': 'Conductor',
    'CONDUCTOR BASE': 'Conductor base',
};

const tipoBadgeClass: Record<TipoColaborador, string> = {
    'COLABORADOR BASE': 'bg-emerald-100 text-emerald-800',
    'FREELANCE': 'bg-yellow-100 text-yellow-800',
    'CONDUCTOR': 'bg-blue-100 text-blue-800',
    'CONDUCTOR BASE': 'bg-amber-100 text-amber-800',
};

const fmtMoney = (val: number | string) =>
    new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', minimumFractionDigits: 2 }).format(Number(val));

const rolLinea = (c: Colaborador): string => {
    if (c.tipo === 'COLABORADOR BASE' && c.categoria) {
        return `${c.categoria}${c.nivel ? ` · Nivel ${c.nivel}` : ''}`;
    }

    return tipoLabel[c.tipo];
};

const iniciales = (c: Colaborador): string =>
    `${(c.nombre ?? '').charAt(0)}${(c.apellidos ?? '').charAt(0)}`.toUpperCase();

const pertenenciaLabel: Record<UnidadTransporte['pertenencia'], string> = {
    PROPIA: 'Propia',
    RENTADA: 'Rentada',
};

const documentosUnidad: { clave: keyof UnidadTransporte; label: string }[] = [
    { clave: 'placas_documento_url', label: 'Placas' },
    { clave: 'tarjeta_circulacion_documento_url', label: 'Tarjeta de circulación' },
    { clave: 'poliza_seguro_documento_url', label: 'Póliza de seguro' },
    { clave: 'verificacion_documento_url', label: 'Verificación' },
    { clave: 'tenencia_documento_url', label: 'Tenencia' },
];

const inicialesUnidad = (u: UnidadTransporte): string =>
    `${u.marca.charAt(0)}${u.modelo.charAt(0)}`.toUpperCase();

const requisitosLista = (): { label: string; cantidad: number }[] => {
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

const hoy = new Date().toISOString().slice(0, 10);

const doPrint = () => window.print();

onMounted(() => {
    setTimeout(doPrint, 500);
});
</script>

<template>
    <Head :title="`Ficha del evento — ${evento.nombre}`" />

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
                        <h1 class="text-2xl font-bold uppercase tracking-tight text-slate-900">Ficha de Evento</h1>
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
                        <p class="text-sm font-medium text-slate-900">{{ fmtFecha(hoy) }}</p>
                    </div>
                </div>
            </div>

            <!-- Detalles del evento -->
            <section class="border-b border-slate-200 px-8 py-4">
                <h2 class="mb-2 text-base font-bold uppercase tracking-wide text-slate-700">Detalles del evento</h2>

                <dl class="grid grid-cols-3 gap-x-6 gap-y-2 text-sm">
                    <div>
                        <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Duración</dt>
                        <dd class="text-slate-900">{{ cotizacion.dias ?? '—' }} día(s)</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Pago por freelance</dt>
                        <dd class="text-slate-900">{{ fmtMoney(evento.pago_por_evento_completo) }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Contacto</dt>
                        <dd class="text-slate-900">{{ evento.contacto_nombre || '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Teléfono de contacto</dt>
                        <dd class="text-slate-900">{{ evento.contacto_telefono || '—' }}</dd>
                    </div>
                    <div class="col-span-3">
                        <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Descripción</dt>
                        <dd class="text-slate-900 whitespace-pre-line">{{ evento.descripcion || '—' }}</dd>
                    </div>
                    <div class="col-span-3">
                        <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Observaciones técnicas</dt>
                        <dd class="text-slate-900 whitespace-pre-line">{{ evento.observaciones_tecnicas || '—' }}</dd>
                    </div>
                    <div v-if="evento.enlace_ubicacion" class="col-span-3">
                        <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Enlace de ubicación</dt>
                        <dd class="text-slate-900 break-all">{{ evento.enlace_ubicacion }}</dd>
                    </div>
                </dl>
            </section>

            <!-- Cotización -->
            <section class="border-b border-slate-200 px-8 py-4">
                <div class="mb-2 flex items-center justify-between">
                    <h2 class="text-base font-bold uppercase tracking-wide text-slate-700">Cotización</h2>
                    <span class="text-base font-bold text-slate-900">{{ fmtMoney(cotizacion.total) }}</span>
                </div>

                <div v-if="requisitosLista().length > 0" class="mb-3 flex flex-wrap gap-2">
                    <span
                        v-for="r in requisitosLista()"
                        :key="r.label"
                        class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs text-slate-700"
                    >
                        {{ r.label }}: <span class="ml-1 font-semibold">{{ r.cantidad }}</span>
                    </span>
                </div>

                <p v-if="cotizacion.base.length === 0 && cotizacion.freelance_count === 0" class="text-sm text-slate-500">
                    Sin colaboradores asignados todavía.
                </p>

                <template v-if="cotizacion.base.length > 0">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200">
                                <th class="py-1.5 text-left font-medium text-slate-500">Colaborador</th>
                                <th class="py-1.5 text-left font-medium text-slate-500">Categoría</th>
                                <th class="py-1.5 text-right font-medium text-slate-500">Sueldo</th>
                                <th class="py-1.5 text-right font-medium text-slate-500">Extra evento</th>
                                <th class="py-1.5 text-right font-medium text-slate-500">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="b in cotizacion.base" :key="b.colaborador_id" class="border-b border-slate-100">
                                <td class="py-1.5 font-medium whitespace-nowrap text-slate-900">{{ b.apellidos }}, {{ b.nombre }}</td>
                                <td class="py-1.5 text-slate-600 whitespace-nowrap">
                                    {{ b.categoria }}<template v-if="b.nivel"> · Nivel {{ b.nivel }}</template>
                                </td>
                                <td class="py-1.5 text-right tabular-nums text-slate-800">{{ fmtMoney(b.sueldo) }}</td>
                                <td class="py-1.5 text-right tabular-nums text-slate-800">{{ fmtMoney(b.bono) }}</td>
                                <td class="py-1.5 text-right tabular-nums font-semibold text-slate-900">{{ fmtMoney(b.total) }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <p class="mt-1.5 text-xs text-slate-500">
                        {{ cotizacion.base.length }} colaborador(es) Base · {{ cotizacion.dias ?? '—' }} día(s) — Subtotal
                        {{ fmtMoney(cotizacion.total_base) }}
                    </p>
                </template>

                <p v-if="cotizacion.freelance_count > 0" class="mt-3 text-sm text-slate-600">
                    Freelance:
                    {{ cotizacion.freelance_count }} colaborador(es) × {{ fmtMoney(cotizacion.pago_por_freelance) }} por evento completo
                    = <span class="font-semibold text-slate-900">{{ fmtMoney(cotizacion.total_freelance) }}</span>
                </p>

                <p class="mt-2 text-right text-xs italic text-slate-500">
                    Cotización al 100% de participación — sin compensaciones ni días adicionales.
                </p>
            </section>

            <!-- Asignación -->
            <section class="border-b border-slate-200 px-8 py-4">
                <div class="mb-2 flex items-center justify-between">
                    <h2 class="text-base font-bold uppercase tracking-wide text-slate-700">Asignación</h2>
                    <span class="text-sm font-semibold text-slate-900">
                        {{ perfiles.length }} colaborador(es)
                        <template v-if="unidades.length"> · {{ unidades.length }} unidad(es)</template>
                    </span>
                </div>

                <p v-if="perfiles.length === 0" class="text-sm text-slate-500">Sin colaboradores asignados todavía.</p>

                <table v-else class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200">
                            <th class="py-1.5 text-left font-medium text-slate-500">Colaborador</th>
                            <th class="py-1.5 text-left font-medium text-slate-500">Tipo</th>
                            <th class="py-1.5 text-left font-medium text-slate-500">Categoría · Nivel</th>
                            <th class="py-1.5 text-right font-medium text-slate-500">Sueldo diario</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="p in perfiles" :key="p.colaborador.id" class="border-b border-slate-100">
                            <td class="py-1.5 font-medium whitespace-nowrap text-slate-900">
                                {{ p.colaborador.apellidos }}, {{ p.colaborador.nombre }}
                            </td>
                            <td class="py-1.5">
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="tipoBadgeClass[p.colaborador.tipo]">
                                    {{ tipoLabel[p.colaborador.tipo] }}
                                </span>
                            </td>
                            <td class="py-1.5 text-slate-600">
                                {{ p.colaborador.categoria ? `${p.colaborador.categoria}${p.colaborador.nivel ? ` · Nivel ${p.colaborador.nivel}` : ''}` : '—' }}
                            </td>
                            <td class="py-1.5 text-right tabular-nums text-slate-800">{{ fmtMoney(p.colaborador.sueldo_diario) }}</td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <!-- Unidades de transporte asignadas -->
            <section class="border-b border-slate-200 px-8 py-4">
                <div class="mb-2 flex items-center justify-between">
                    <h2 class="text-base font-bold uppercase tracking-wide text-slate-700">Unidades de transporte</h2>
                    <span class="text-sm font-semibold text-slate-900">{{ unidades.length }} unidad(es)</span>
                </div>

                <p v-if="unidades.length === 0" class="text-sm text-slate-500">Sin unidades de transporte asignadas.</p>

                <table v-else class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200">
                            <th class="py-1.5 text-left font-medium text-slate-500">Unidad</th>
                            <th class="py-1.5 text-left font-medium text-slate-500">Placas</th>
                            <th class="py-1.5 text-left font-medium text-slate-500">Pertenencia</th>
                            <th class="py-1.5 text-left font-medium text-slate-500">Categoría (tarifa)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="u in unidades" :key="u.id" class="border-b border-slate-100">
                            <td class="py-1.5 font-medium whitespace-nowrap text-slate-900">
                                {{ u.marca }} {{ u.modelo }}
                                <span v-if="u.alias" class="text-slate-500"> ({{ u.alias }})</span>
                            </td>
                            <td class="py-1.5 text-slate-800 tabular-nums">{{ u.numero_placas ?? '—' }}</td>
                            <td class="py-1.5 text-slate-800">{{ pertenenciaLabel[u.pertenencia] }}</td>
                            <td class="py-1.5 text-slate-600">{{ u.vehiculo?.nombre || '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <!-- Perfiles de los colaboradores asignados -->
            <section class="px-8 py-4 print:break-before-page">
                <h2 class="mb-3 text-base font-bold uppercase tracking-wide text-slate-700">Perfiles de los colaboradores asignados</h2>

                <p v-if="perfiles.length === 0" class="text-sm text-slate-500">Sin colaboradores asignados todavía.</p>

                <div v-for="(p, i) in perfiles" :key="p.colaborador.id" class="break-inside-avoid" :class="i > 0 ? 'print:break-before-page mt-6' : ''">
                    <!-- Header del perfil -->
                    <div class="flex items-center gap-4 border-b-2 border-slate-800 pb-3">
                        <div class="flex-1">
                            <h3 class="text-lg font-bold uppercase tracking-tight text-slate-900">
                                {{ p.colaborador.apellidos }}, {{ p.colaborador.nombre }}
                            </h3>
                            <p class="text-sm font-semibold text-slate-700">{{ rolLinea(p.colaborador) }}</p>
                        </div>
                        <div class="flex size-16 shrink-0 items-center justify-center overflow-hidden rounded-full border-2 border-slate-300 bg-slate-100">
                            <img
                                v-if="p.perfil?.fotografia_url"
                                :src="p.perfil.fotografia_url"
                                alt="Fotografía"
                                class="size-full object-cover"
                            />
                            <span v-else class="text-lg font-bold text-slate-500">{{ iniciales(p.colaborador) }}</span>
                        </div>
                    </div>

                    <!-- Datos personales -->
                    <div class="border-b border-slate-200 py-3">
                        <h4 class="mb-1.5 text-xs font-bold uppercase tracking-wide text-slate-700">Datos personales</h4>

                        <dl class="grid grid-cols-2 gap-x-6 gap-y-1 text-[13px] md:grid-cols-3">
                            <div>
                                <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Alias</dt>
                                <dd class="text-slate-900">{{ p.perfil?.alias || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Fecha de nacimiento</dt>
                                <dd class="text-slate-900">{{ p.perfil?.fecha_nacimiento ? fmtFecha(p.perfil.fecha_nacimiento) : '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Género</dt>
                                <dd class="text-slate-900">{{ p.perfil?.genero || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Fecha de ingreso</dt>
                                <dd class="text-slate-900">{{ p.perfil?.fecha_ingreso ? fmtFecha(p.perfil.fecha_ingreso) : '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Correo</dt>
                                <dd class="text-slate-900 break-words">{{ p.perfil?.correo || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Teléfono</dt>
                                <dd class="text-slate-900">{{ p.perfil?.telefono || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">WhatsApp</dt>
                                <dd class="text-slate-900">{{ p.perfil?.whatsapp || '—' }}</dd>
                            </div>
                            <div class="col-span-2">
                                <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Redes sociales</dt>
                                <dd class="text-slate-900 break-words">{{ p.perfil?.redes_sociales || '—' }}</dd>
                            </div>
                            <div v-if="p.perfil?.ubicacion_maps" class="col-span-2">
                                <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Ubicación</dt>
                                <dd class="text-slate-900 break-all">{{ p.perfil.ubicacion_maps }}</dd>
                            </div>
                            <div class="col-span-3">
                                <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Domicilio</dt>
                                <dd class="text-slate-900">{{ p.perfil?.domicilio || '—' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Datos de emergencia -->
                    <div class="border-b border-slate-200 py-3">
                        <h4 class="mb-1.5 text-xs font-bold uppercase tracking-wide text-slate-700">Datos de emergencia</h4>

                        <div class="grid grid-cols-2 gap-x-6 gap-y-1 text-[13px] md:grid-cols-3">
                            <div>
                                <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Tipo de sangre</dt>
                                <dd class="text-slate-900">{{ p.perfil?.tipo_sangre || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Número de seguro social</dt>
                                <dd class="text-slate-900">{{ p.perfil?.numero_seguro_social || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Padecimientos crónicos</dt>
                                <dd class="text-slate-900">{{ p.perfil?.padecimientos_cronicos || '—' }}</dd>
                            </div>
                            <div class="col-span-2">
                                <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Alergias</dt>
                                <dd class="text-slate-900">{{ p.perfil?.alergias || '—' }}</dd>
                            </div>
                        </div>

                        <div class="mt-2 grid grid-cols-2 gap-6 border-t border-slate-100 pt-2">
                            <div>
                                <p class="mb-1 text-[10px] font-semibold uppercase tracking-wide text-slate-600">Contacto de emergencia 1</p>
                                <dl class="space-y-0.5 text-[13px]">
                                    <div>
                                        <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Nombre</dt>
                                        <dd class="text-slate-900">{{ p.perfil?.contacto_emergencia_1_nombre || '—' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Parentesco</dt>
                                        <dd class="text-slate-900">{{ p.perfil?.contacto_emergencia_1_parentesco || '—' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Teléfono</dt>
                                        <dd class="text-slate-900">{{ p.perfil?.contacto_emergencia_1_telefono || '—' }}</dd>
                                    </div>
                                </dl>
                            </div>
                            <div>
                                <p class="mb-1 text-[10px] font-semibold uppercase tracking-wide text-slate-600">Contacto de emergencia 2</p>
                                <dl class="space-y-0.5 text-[13px]">
                                    <div>
                                        <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Nombre</dt>
                                        <dd class="text-slate-900">{{ p.perfil?.contacto_emergencia_2_nombre || '—' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Parentesco</dt>
                                        <dd class="text-slate-900">{{ p.perfil?.contacto_emergencia_2_parentesco || '—' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Teléfono</dt>
                                        <dd class="text-slate-900">{{ p.perfil?.contacto_emergencia_2_telefono || '—' }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </div>

                    <!-- Datos bancarios -->
                    <div class="py-3">
                        <h4 class="mb-1.5 text-xs font-bold uppercase tracking-wide text-slate-700">Datos bancarios</h4>

                        <dl class="grid grid-cols-2 gap-x-6 gap-y-1 text-[13px] md:grid-cols-3">
                            <div>
                                <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Banco</dt>
                                <dd class="text-slate-900">{{ p.perfil?.banco || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Beneficiario</dt>
                                <dd class="text-slate-900">{{ p.perfil?.beneficiario || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">CLABE</dt>
                                <dd class="text-slate-900 tabular-nums">{{ p.perfil?.clave_interbancaria || '—' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </section>

            <!-- Perfiles de las unidades de transporte asignadas -->
            <section class="px-8 py-4 print:break-before-page">
                <h2 class="mb-3 text-base font-bold uppercase tracking-wide text-slate-700">Unidades de transporte asignadas</h2>

                <p v-if="unidades.length === 0" class="text-sm text-slate-500">Sin unidades de transporte asignadas.</p>

                <div v-for="(u, i) in unidades" :key="u.id" class="break-inside-avoid" :class="i > 0 ? 'print:break-before-page mt-6' : ''">
                    <!-- Header de la unidad -->
                    <div class="flex items-center gap-4 border-b-2 border-slate-800 pb-3">
                        <div class="flex-1">
                            <h3 class="text-lg font-bold uppercase tracking-tight text-slate-900">
                                {{ u.marca }} {{ u.modelo }}
                            </h3>
                            <p class="text-sm font-semibold text-slate-700">
                                Unidad de transporte<template v-if="u.vehiculo"> · {{ u.vehiculo.nombre }}</template>
                            </p>
                        </div>
                        <div class="flex size-16 shrink-0 items-center justify-center overflow-hidden rounded-lg border-2 border-slate-300 bg-slate-100">
                            <img
                                v-if="u.fotografia_url"
                                :src="u.fotografia_url"
                                :alt="`${u.marca} ${u.modelo}`"
                                class="size-full object-cover"
                            />
                            <span v-else class="text-base font-bold text-slate-500">{{ inicialesUnidad(u) }}</span>
                        </div>
                    </div>

                    <!-- Datos generales -->
                    <div class="border-b border-slate-200 py-3">
                        <h4 class="mb-1.5 text-xs font-bold uppercase tracking-wide text-slate-700">Datos generales</h4>

                        <dl class="grid grid-cols-2 gap-x-6 gap-y-1 text-[13px] md:grid-cols-3">
                            <div>
                                <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Placas</dt>
                                <dd class="text-slate-900">{{ u.numero_placas || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Pertenencia</dt>
                                <dd class="text-slate-900">{{ pertenenciaLabel[u.pertenencia] }}</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Categoría (tarifa)</dt>
                                <dd class="text-slate-900">{{ u.vehiculo?.nombre || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Alias</dt>
                                <dd class="text-slate-900">{{ u.alias || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Número de serie</dt>
                                <dd class="text-slate-900 break-all tabular-nums">{{ u.numero_serie || '—' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Póliza -->
                    <div class="border-b border-slate-200 py-3">
                        <h4 class="mb-1.5 text-xs font-bold uppercase tracking-wide text-slate-700">Póliza de seguro</h4>

                        <dl class="grid grid-cols-2 gap-x-6 gap-y-1 text-[13px] md:grid-cols-3">
                            <div>
                                <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Número de póliza</dt>
                                <dd class="text-slate-900">{{ u.numero_poliza_seguro || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Vigencia</dt>
                                <dd class="text-slate-900">{{ u.vigencia_poliza_seguro ? fmtFecha(u.vigencia_poliza_seguro) : '—' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Verificación -->
                    <div class="border-b border-slate-200 py-3">
                        <h4 class="mb-1.5 text-xs font-bold uppercase tracking-wide text-slate-700">Verificación</h4>

                        <dl class="grid grid-cols-2 gap-x-6 gap-y-1 text-[13px] md:grid-cols-3">
                            <div>
                                <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Vencimiento</dt>
                                <dd class="text-slate-900">{{ u.vigencia_verificacion ? fmtFecha(u.vigencia_verificacion) : '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Tipo de engomado</dt>
                                <dd class="text-slate-900">{{ u.tipo_engomado || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Color de engomado</dt>
                                <dd class="text-slate-900">{{ u.color_engomado || '—' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Documentos en archivo -->
                    <div class="py-3">
                        <h4 class="mb-1.5 text-xs font-bold uppercase tracking-wide text-slate-700">Documentos en archivo</h4>

                        <dl class="grid grid-cols-2 gap-x-6 gap-y-1 text-[13px] md:grid-cols-3">
                            <div v-for="d in documentosUnidad" :key="d.clave">
                                <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">{{ d.label }}</dt>
                                <dd class="text-slate-900">{{ u[d.clave] ? 'En archivo' : '—' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </section>

            <!-- Footer -->
            <div class="border-t px-8 pb-6 pt-4">
                <p class="text-center text-xs text-slate-400">
                    Este documento fue generado electrónicamente. Evento: {{ evento.nombre }} · {{ fmtFecha(hoy) }}
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