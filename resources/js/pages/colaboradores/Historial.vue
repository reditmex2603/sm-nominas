<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, ChevronDown, ChevronRight } from '@lucide/vue';
import { computed, ref } from 'vue';
import NominaDesgloseDetalle from '@/components/NominaDesgloseDetalle.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { fmtFecha } from '@/lib/fecha';

type TipoColaborador =
    'COLABORADOR BASE' | 'FREELANCE' | 'CONDUCTOR' | 'CONDUCTOR BASE';
type EstadoNomina = 'PENDIENTE' | 'PAGADO';
type EstadoCuota = 'PENDIENTE' | 'PAGADA';
type Periodicidad = 'SEMANAL' | 'QUINCENAL' | 'MENSUAL';

interface Colaborador {
    id: number;
    nombre: string;
    apellidos: string;
    tipo: TipoColaborador;
    categoria: string | null;
    nivel: number | null;
    area: string | null;
    sueldo_diario: string | null;
    extra_dia_adicional: string | null;
    compensacion_pct: number;
}

interface PerfilRef {
    telefono: string | null;
    whatsapp: string | null;
}

interface Desglose {
    _jornadas?: unknown[];
    _rutas?: unknown[];
    _registros?: unknown[];
    [key: string]: unknown;
}

interface Nomina {
    id: number;
    tipo_colaborador: TipoColaborador;
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
    estado: EstadoNomina;
    evento_id: number | null;
    fecha_calculo: string;
    desglose: Desglose | null;
}

interface EventoRef {
    id: number;
    nombre: string | null;
}

interface Anticipo {
    id: number;
    concepto: string | null;
    tipo: string;
    monto: string;
    fecha: string;
    entregado_por: string | null;
    evento: EventoRef | null;
}

interface Cuota {
    id: number;
    numero_plazo: number;
    monto: string;
    fecha_programada: string;
    estado: EstadoCuota;
    fecha_pago: string | null;
    historico_nomina_id: number | null;
}

interface Prestamo {
    id: number;
    monto_total: string;
    num_plazos: number;
    periodicidad: Periodicidad;
    fecha_inicio: string;
    concepto: string | null;
    autoriza: string | null;
    cuotas: Cuota[];
}

const props = defineProps<{
    colaborador: Colaborador;
    perfil: PerfilRef | null;
    nominas: Nomina[];
    anticipos: Anticipo[];
    prestamos: Prestamo[];
}>();

const tipoBadge: Record<TipoColaborador, { label: string; class: string }> = {
    'COLABORADOR BASE': {
        label: 'Base',
        class: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200',
    },
    FREELANCE: {
        label: 'Freelance',
        class: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    },
    CONDUCTOR: {
        label: 'Conductor',
        class: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    },
    'CONDUCTOR BASE': {
        label: 'Conductor base',
        class: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
    },
};

const rolLinea = computed(() => {
    if (props.colaborador.tipo === 'COLABORADOR BASE') {
        return `${props.colaborador.categoria ?? 'Sin categoría'}${props.colaborador.nivel ? ` · Nivel ${props.colaborador.nivel}` : ''}`;
    }

    return tipoBadge[props.colaborador.tipo].label;
});

type Tab = 'nominas' | 'anticipos' | 'prestamos';
const activeTab = ref<Tab>('nominas');

const fmt = (n: string | number): string => {
    const v = parseFloat(String(n));

    return `$${Math.abs(v).toLocaleString('es-MX', { minimumFractionDigits: 2 })}`;
};

const estadoNominaBadge: Record<EstadoNomina, string> = {
    PENDIENTE:
        'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-400',
    PAGADO: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-400',
};

const periodicidadLabel: Record<Periodicidad, string> = {
    SEMANAL: 'Semanal',
    QUINCENAL: 'Quincenal',
    MENSUAL: 'Mensual',
};

// ── Desglose expandible por movimiento ─────────────────────────────
const nominasExpandidas = ref(new Set<number>());
const toggleNomina = (id: number) => {
    const next = new Set(nominasExpandidas.value);

    if (next.has(id)) {
        next.delete(id);
    } else {
        next.add(id);
    }

    nominasExpandidas.value = next;
};

const prestamosExpandidos = ref(new Set<number>());
const togglePrestamo = (id: number) => {
    const next = new Set(prestamosExpandidos.value);

    if (next.has(id)) {
        next.delete(id);
    } else {
        next.add(id);
    }

    prestamosExpandidos.value = next;
};

const totalPendiente = computed(() =>
    props.prestamos.reduce(
        (s, p) =>
            s +
            p.cuotas
                .filter((c) => c.estado === 'PENDIENTE')
                .reduce((s2, c) => s2 + parseFloat(c.monto), 0),
        0,
    ),
);
</script>

<template>
    <Head
        :title="`Historial — ${colaborador.apellidos}, ${colaborador.nombre}`"
    />

    <div class="flex h-full flex-1 flex-col gap-4 p-4 sm:p-6">
        <div class="flex items-start gap-3 sm:items-center">
            <Button variant="ghost" size="sm" as-child>
                <Link href="/colaboradores">
                    <ArrowLeft class="size-4" />
                </Link>
            </Button>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-2xl font-semibold">
                    {{ colaborador.apellidos }}, {{ colaborador.nombre }}
                </h1>
                <Badge
                    variant="outline"
                    :class="tipoBadge[colaborador.tipo].class"
                    >{{ tipoBadge[colaborador.tipo].label }}</Badge
                >
                <span class="text-sm text-muted-foreground">{{
                    rolLinea
                }}</span>
                <span
                    v-if="colaborador.area"
                    class="text-sm text-muted-foreground"
                    >· Área: {{ colaborador.area }}</span
                >
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <div class="rounded-xl border p-3">
                <p class="text-xs text-muted-foreground">Nóminas</p>
                <p class="text-lg font-semibold">{{ nominas.length }}</p>
            </div>
            <div class="rounded-xl border p-3">
                <p class="text-xs text-muted-foreground">Anticipos</p>
                <p class="text-lg font-semibold">{{ anticipos.length }}</p>
            </div>
            <div class="rounded-xl border p-3">
                <p class="text-xs text-muted-foreground">Préstamos</p>
                <p class="text-lg font-semibold">{{ prestamos.length }}</p>
            </div>
            <div v-if="perfil" class="ml-auto text-xs text-muted-foreground">
                Tel: {{ perfil.telefono ?? '—' }} · WhatsApp:
                {{ perfil.whatsapp ?? '—' }}
            </div>
        </div>

        <!-- Tabs nav -->
        <div class="flex gap-1 overflow-x-auto border-b">
            <button
                class="relative px-4 py-2 text-sm font-medium whitespace-nowrap transition-colors"
                :class="
                    activeTab === 'nominas'
                        ? '-mb-px border-b-2 border-primary bg-transparent text-foreground'
                        : 'text-muted-foreground hover:text-foreground'
                "
                @click="activeTab = 'nominas'"
            >
                Nóminas ({{ nominas.length }})
            </button>
            <button
                class="relative px-4 py-2 text-sm font-medium whitespace-nowrap transition-colors"
                :class="
                    activeTab === 'anticipos'
                        ? '-mb-px border-b-2 border-primary bg-transparent text-foreground'
                        : 'text-muted-foreground hover:text-foreground'
                "
                @click="activeTab = 'anticipos'"
            >
                Anticipos ({{ anticipos.length }})
            </button>
            <button
                class="relative px-4 py-2 text-sm font-medium whitespace-nowrap transition-colors"
                :class="
                    activeTab === 'prestamos'
                        ? '-mb-px border-b-2 border-primary bg-transparent text-foreground'
                        : 'text-muted-foreground hover:text-foreground'
                "
                @click="activeTab = 'prestamos'"
            >
                Préstamos ({{ prestamos.length }})
            </button>
        </div>

        <!-- ── Nóminas ── -->
        <div v-if="activeTab === 'nominas'" class="space-y-2">
            <p
                v-if="nominas.length === 0"
                class="rounded-xl border border-dashed py-10 text-center text-sm text-muted-foreground"
            >
                Sin nóminas registradas para este colaborador.
            </p>

            <div v-else class="overflow-x-auto rounded-xl border">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/50">
                        <tr>
                            <th class="w-8 px-2 py-3"></th>
                            <th class="px-4 py-3 text-left font-medium">
                                Período
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                Días
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                Base
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                Bonos
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                Compensación
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                Anticipos
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                Préstamos
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                Total
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Comentario
                            </th>
                            <th class="px-4 py-3 text-center font-medium">
                                Estado
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <template v-for="n in nominas" :key="n.id">
                            <tr
                                class="cursor-pointer hover:bg-muted/30"
                                @click="toggleNomina(n.id)"
                            >
                                <td class="px-2 py-3">
                                    <component
                                        :is="
                                            nominasExpandidas.has(n.id)
                                                ? ChevronDown
                                                : ChevronRight
                                        "
                                        class="size-4 text-muted-foreground"
                                    />
                                </td>
                                <td
                                    class="px-4 py-3 text-xs whitespace-nowrap tabular-nums"
                                >
                                    {{ fmtFecha(n.periodo_inicio) }} →
                                    {{ fmtFecha(n.periodo_fin) }}
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums">
                                    {{ n.dias }}
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums">
                                    {{ fmt(n.total_base) }}
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums">
                                    {{ fmt(n.bonos_evento) }}
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums">
                                    {{ fmt(n.compensaciones) }}
                                </td>
                                <td
                                    class="px-4 py-3 text-right text-red-600 tabular-nums"
                                >
                                    -{{ fmt(n.anticipos) }}
                                </td>
                                <td
                                    class="px-4 py-3 text-right text-red-600 tabular-nums"
                                >
                                    -{{ fmt(n.prestamos) }}
                                </td>
                                <td
                                    class="px-4 py-3 text-right font-semibold tabular-nums"
                                >
                                    {{ fmt(n.total_final) }}
                                </td>
                                <td
                                    class="max-w-48 truncate px-4 py-3 text-xs text-muted-foreground"
                                    :title="n.comentario ?? ''"
                                >
                                    {{ n.comentario ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="estadoNominaBadge[n.estado]"
                                    >
                                        {{ n.estado }}
                                    </span>
                                </td>
                            </tr>
                            <tr
                                v-if="nominasExpandidas.has(n.id)"
                                class="bg-muted/20"
                            >
                                <td colspan="11" class="px-6 py-3">
                                    <NominaDesgloseDetalle
                                        :tipo-colaborador="n.tipo_colaborador"
                                        :desglose="n.desglose as any"
                                        :sueldo-diario="
                                            parseFloat(n.sueldo_diario)
                                        "
                                    />
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ── Anticipos ── -->
        <div v-if="activeTab === 'anticipos'" class="space-y-2">
            <p
                v-if="anticipos.length === 0"
                class="rounded-xl border border-dashed py-10 text-center text-sm text-muted-foreground"
            >
                Sin anticipos registrados para este colaborador.
            </p>

            <div v-else class="overflow-x-auto rounded-xl border">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">
                                Fecha
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Concepto
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Tipo
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Evento
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                Monto
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Entregado por
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="a in anticipos" :key="a.id">
                            <td
                                class="px-4 py-3 whitespace-nowrap tabular-nums"
                            >
                                {{ fmtFecha(a.fecha) }}
                            </td>
                            <td class="px-4 py-3">{{ a.concepto ?? '—' }}</td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ a.tipo }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ a.evento?.nombre ?? '—' }}
                            </td>
                            <td
                                class="px-4 py-3 text-right font-medium tabular-nums"
                            >
                                {{ fmt(a.monto) }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ a.entregado_por ?? '—' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ── Préstamos ── -->
        <div v-if="activeTab === 'prestamos'" class="space-y-3">
            <p
                v-if="prestamos.length === 0"
                class="rounded-xl border border-dashed py-10 text-center text-sm text-muted-foreground"
            >
                Sin préstamos registrados para este colaborador.
            </p>

            <div
                v-else
                class="flex items-center justify-end text-xs text-muted-foreground"
            >
                Total pendiente de cuotas:
                <span class="ml-1 font-semibold text-amber-600">{{
                    fmt(totalPendiente)
                }}</span>
            </div>

            <div
                v-for="p in prestamos"
                :key="p.id"
                class="overflow-hidden rounded-xl border"
            >
                <button
                    class="flex w-full flex-wrap items-center gap-3 px-4 py-3 text-left transition-colors hover:bg-muted/30"
                    @click="togglePrestamo(p.id)"
                >
                    <component
                        :is="
                            prestamosExpandidos.has(p.id)
                                ? ChevronDown
                                : ChevronRight
                        "
                        class="size-4 flex-shrink-0 text-muted-foreground"
                    />
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium">
                            {{ p.concepto ?? 'Préstamo' }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ fmtFecha(p.fecha_inicio) }} ·
                            {{ periodicidadLabel[p.periodicidad] }} ·
                            {{ p.num_plazos }} plazo(s)
                        </p>
                    </div>
                    <span class="text-xs text-muted-foreground"
                        >Autoriza: {{ p.autoriza ?? '—' }}</span
                    >
                    <span class="font-semibold tabular-nums">{{
                        fmt(p.monto_total)
                    }}</span>
                </button>

                <div v-if="prestamosExpandidos.has(p.id)" class="border-t">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/30">
                            <tr>
                                <th
                                    class="px-4 py-2 text-left text-xs font-medium"
                                >
                                    Plazo
                                </th>
                                <th
                                    class="px-4 py-2 text-left text-xs font-medium"
                                >
                                    Fecha programada
                                </th>
                                <th
                                    class="px-4 py-2 text-right text-xs font-medium"
                                >
                                    Monto
                                </th>
                                <th
                                    class="px-4 py-2 text-center text-xs font-medium"
                                >
                                    Estado
                                </th>
                                <th
                                    class="px-4 py-2 text-left text-xs font-medium"
                                >
                                    Fecha de pago
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="c in p.cuotas" :key="c.id">
                                <td class="px-4 py-2 tabular-nums">
                                    {{ c.numero_plazo }}
                                </td>
                                <td class="px-4 py-2 tabular-nums">
                                    {{ fmtFecha(c.fecha_programada) }}
                                </td>
                                <td
                                    class="px-4 py-2 text-right font-medium tabular-nums"
                                >
                                    {{ fmt(c.monto) }}
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <span
                                        class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="
                                            c.estado === 'PAGADA'
                                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-400'
                                                : 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-400'
                                        "
                                    >
                                        {{ c.estado }}
                                    </span>
                                </td>
                                <td
                                    class="px-4 py-2 text-muted-foreground tabular-nums"
                                >
                                    {{
                                        c.fecha_pago
                                            ? fmtFecha(c.fecha_pago)
                                            : '—'
                                    }}
                                </td>
                            </tr>
                            <tr v-if="p.cuotas.length === 0">
                                <td
                                    colspan="5"
                                    class="px-4 py-6 text-center text-sm text-muted-foreground"
                                >
                                    Sin cuotas.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
