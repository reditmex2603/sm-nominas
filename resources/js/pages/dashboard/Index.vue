<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    AlertTriangle,
    Banknote,
    Briefcase,
    CalendarDays,
    CircleDollarSign,
    ClipboardCheck,
    HandCoins,
    History,
    Receipt,
    ShieldAlert,
    Truck,
    Users,
    Wallet,
} from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import AnimatedNumber from '@/components/AnimatedNumber.vue';
import { Button } from '@/components/ui/button';
import { fmtFecha } from '@/lib/fecha';
import * as eventos from '@/routes/eventos';
import * as historial from '@/routes/historial';
import * as prestamos from '@/routes/prestamos';
import * as transportes from '@/routes/transportes';

const fmt = (n: number) =>
    new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', minimumFractionDigits: 2 }).format(n);

const fmtEntero = (n: number) =>
    new Intl.NumberFormat('es-MX').format(n);

const fmtInt = (n: number) => fmtEntero(Math.round(n));
const fmtPct = (n: number) => `${Math.round(n)}%`;

const mounted = ref(false);
onMounted(() => {
    requestAnimationFrame(() => {
        mounted.value = true;
    });
});

type Stats = {
    colaboradores: { total: number; por_tipo: Record<string, number> };
    eventos: {
        total: number;
        total_vigentes: number;
        por_tamano: Record<string, number>;
        vigentes_por_tamano: Record<string, number>;
        por_anio: any[];
        proximos: any[];
    };
    nomina: {
        total_anio: number;
        pendiente_anio: number;
        pagado_anio: number;
        pagado_mes: number;
        ultimas: any[];
        por_anio: any[];
    };
    anticipos_mes: number;
    prestamos: { activos: number; pendiente_monto: number };
    viaticos_mes: number;
    servicios_mes: number;
    validacion: {
        total: number;
        validadas: number;
        total_anio: number;
        validadas_anio: number;
        por_anio: any[];
    };
    alertas: {
        colaboradores_sin_perfil: number;
        jornadas_atrasadas: number;
        cuotas_proximas: any[];
    };
    transporte: {
        total_unidades: number;
        propias: number;
        rentadas: number;
        total_categorias: number;
        total_tarifas: number;
        tarifa_promedio: number;
    };
    vencimientos: {
        cuotas: any[];
        seguros: any[];
    };
    gastos_anio: {
        nomina: number;
        anticipos: number;
        viaticos: number;
        servicios: number;
    };
};

const props = defineProps<{ stats: Stats }>();

const pctValidacion = computed(() =>
    props.stats.validacion.total_anio > 0
        ? Math.round((props.stats.validacion.validadas_anio / props.stats.validacion.total_anio) * 100)
        : 0
);

const nominaPorAnio = computed(() => {
    const items = props.stats.nomina.por_anio ?? [];
    const max = Math.max(...items.map((i: any) => Number(i.total)), 1);

    return items
        .map((i: any) => ({ ...i, total: Number(i.total), pct: (Number(i.total) / max) * 100 }))
        .sort((a: any, b: any) => Number(a.anio) - Number(b.anio));
});

const eventosPorAnio = computed(() => {
    const items = props.stats.eventos.por_anio ?? [];
    const max = Math.max(...items.map((i: any) => Number(i.total)), 1);

    return items
        .map((i: any) => ({ ...i, total: Number(i.total), pct: (Number(i.total) / max) * 100 }))
        .sort((a: any, b: any) => Number(a.anio) - Number(b.anio));
});

const validacionPorAnio = computed(() => {
    const items = props.stats.validacion.por_anio ?? [];

    return items
        .map((i: any) => {
            const total = Number(i.total);
            const validadas = Number(i.validadas);

            return { ...i, pct: total > 0 ? Math.round((validadas / total) * 100) : 0 };
        })
        .sort((a: any, b: any) => Number(b.anio) - Number(a.anio));
});

const gastosPorcentajes = computed(() => {
    const g = props.stats.gastos_anio;
    const total = g.nomina + g.anticipos + g.viaticos + g.servicios;

    if (total === 0) {
return [];
}

    return [
        { label: 'Nómina', value: g.nomina, pct: Math.round((g.nomina / total) * 100), color: 'bg-violet-500' },
        { label: 'Anticipos', value: g.anticipos, pct: Math.round((g.anticipos / total) * 100), color: 'bg-rose-500' },
        { label: 'Viáticos', value: g.viaticos, pct: Math.round((g.viaticos / total) * 100), color: 'bg-teal-500' },
        { label: 'Servicios Prof.', value: g.servicios, pct: Math.round((g.servicios / total) * 100), color: 'bg-cyan-500' },
    ];
});

const totalGastos = computed(() =>
    props.stats.gastos_anio.nomina + props.stats.gastos_anio.anticipos +
    props.stats.gastos_anio.viaticos + props.stats.gastos_anio.servicios
);

const tipoLabel: Record<string, string> = {
    'COLABORADOR BASE': 'Base',
    FREELANCE: 'Freelance',
    CONDUCTOR: 'Conductor',
    'CONDUCTOR BASE': 'Conductor base',
};

const tipoColor: Record<string, string> = {
    'COLABORADOR BASE': 'bg-emerald-500',
    FREELANCE: 'bg-amber-500',
    CONDUCTOR: 'bg-blue-500',
    'CONDUCTOR BASE': 'bg-amber-500',
};

const tamanoLabel: Record<string, string> = {
    CHICO: 'Chico',
    MEDIANO: 'Mediano',
    GRANDE: 'Grande',
};

const tamanoColor: Record<string, string> = {
    CHICO: 'bg-slate-400',
    MEDIANO: 'bg-orange-500',
    GRANDE: 'bg-purple-500',
};

const maxTipo = computed(() => Math.max(...Object.values(props.stats.colaboradores.por_tipo), 1));
const maxTamanoVigentes = computed(() => Math.max(...Object.values(props.stats.eventos.vigentes_por_tamano), 1));

const tieneAlertas = computed(() =>
    props.stats.alertas.colaboradores_sin_perfil > 0 ||
    props.stats.alertas.jornadas_atrasadas > 0 ||
    props.stats.alertas.cuotas_proximas.length > 0
);

const conicoSegments = computed(() => {
    const items = gastosPorcentajes.value;

    if (items.length === 0) {
return '';
}

    let cumulative = 0;

    return items.map((item, i) => {
        const start = cumulative;
        cumulative += item.pct;
        const end = cumulative;

        if (item.pct === 0) {
return '';
}

        const x1 = 50 + 40 * Math.cos((2 * Math.PI * start) / 100 - Math.PI / 2);
        const y1 = 50 + 40 * Math.sin((2 * Math.PI * start) / 100 - Math.PI / 2);
        const x2 = 50 + 40 * Math.cos((2 * Math.PI * end) / 100 - Math.PI / 2);
        const y2 = 50 + 40 * Math.sin((2 * Math.PI * end) / 100 - Math.PI / 2);
        const large = item.pct > 50 ? 1 : 0;

        return `<path d="M50 50 L${x1} ${y1} A40 40 0 ${large} 1 ${x2} ${y2} Z" fill="${['#8b5cf6', '#f43f5e', '#14b8a6', '#06b6d4'][i]}" />`;
    }).join('');
});
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 sm:p-6">
        <!-- Header -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Dashboard</h1>
                <p class="text-muted-foreground mt-1 text-sm">
                    Resumen general del sistema
                </p>
            </div>
        </div>

        <!-- Stats cards -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="dashboard-card rounded-xl border bg-card p-4 flex items-start gap-3">
                <div class="rounded-lg bg-emerald-100 p-2.5 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                    <Users class="size-5" />
                </div>
                <div class="min-w-0">
                    <p class="text-muted-foreground text-xs font-medium uppercase tracking-wide">Colaboradores</p>
                    <p class="mt-0.5 text-2xl font-bold"><AnimatedNumber :value="stats.colaboradores.total" :format="fmtInt" /></p>
                    <div class="mt-1 flex flex-wrap gap-x-3 gap-y-0.5 text-xs text-muted-foreground">
                        <span v-for="(v, k) in stats.colaboradores.por_tipo" :key="k">
                            {{ tipoLabel[k] || k }}: {{ v }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="dashboard-card rounded-xl border bg-card p-4 flex items-start gap-3">
                <div class="rounded-lg bg-orange-100 p-2.5 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400">
                    <CalendarDays class="size-5" />
                </div>
                <div class="min-w-0">
                    <p class="text-muted-foreground text-xs font-medium uppercase tracking-wide">Eventos</p>
                    <p class="mt-0.5 text-2xl font-bold"><AnimatedNumber :value="stats.eventos.total" :format="fmtInt" /></p>
                    <p class="mt-0.5 text-xs text-muted-foreground">
                        Vigentes: {{ fmtEntero(stats.eventos.total_vigentes) }}
                    </p>
                </div>
            </div>

            <div class="dashboard-card rounded-xl border bg-card p-4 flex items-start gap-3">
                <div class="rounded-lg bg-blue-100 p-2.5 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                    <ClipboardCheck class="size-5" />
                </div>
                <div class="min-w-0">
                    <p class="text-muted-foreground text-xs font-medium uppercase tracking-wide">Nómina Total del Año</p>
                    <p class="mt-0.5 text-2xl font-bold"><AnimatedNumber :value="stats.nomina.total_anio" :format="fmt" /></p>
                    <p class="mt-0.5 text-xs text-muted-foreground">
                        Pendiente + pagado
                    </p>
                </div>
            </div>

            <div class="dashboard-card rounded-xl border bg-card p-4 flex items-start gap-3">
                <div class="rounded-lg bg-amber-100 p-2.5 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                    <Wallet class="size-5" />
                </div>
                <div class="min-w-0">
                    <p class="text-muted-foreground text-xs font-medium uppercase tracking-wide">Nómina Pendiente del Año</p>
                    <p class="mt-0.5 text-2xl font-bold"><AnimatedNumber :value="stats.nomina.pendiente_anio" :format="fmt" /></p>
                    <p class="mt-0.5 text-xs text-muted-foreground">
                        Pagado del año: {{ fmt(stats.nomina.pagado_anio) }}
                    </p>
                </div>
            </div>

            <div class="dashboard-card rounded-xl border bg-card p-4 flex items-start gap-3">
                <div class="rounded-lg bg-violet-100 p-2.5 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400">
                    <Wallet class="size-5" />
                </div>
                <div class="min-w-0">
                    <p class="text-muted-foreground text-xs font-medium uppercase tracking-wide">Nómina Pagada del Año</p>
                    <p class="mt-0.5 text-2xl font-bold"><AnimatedNumber :value="stats.nomina.pagado_anio" :format="fmt" /></p>
                    <p class="mt-0.5 text-xs text-muted-foreground">
                        Este mes: {{ fmt(stats.nomina.pagado_mes) }}
                    </p>
                </div>
            </div>

            <div class="dashboard-card rounded-xl border bg-card p-4 flex items-start gap-3">
                <div class="rounded-lg bg-rose-100 p-2.5 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400">
                    <Banknote class="size-5" />
                </div>
                <div class="min-w-0">
                    <p class="text-muted-foreground text-xs font-medium uppercase tracking-wide">Anticipos del Mes</p>
                    <p class="mt-0.5 text-2xl font-bold"><AnimatedNumber :value="stats.anticipos_mes" :format="fmt" /></p>
                </div>
            </div>

            <div class="dashboard-card rounded-xl border bg-card p-4 flex items-start gap-3">
                <div class="rounded-lg bg-indigo-100 p-2.5 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400">
                    <HandCoins class="size-5" />
                </div>
                <div class="min-w-0">
                    <p class="text-muted-foreground text-xs font-medium uppercase tracking-wide">Préstamos</p>
                    <p class="mt-0.5 text-2xl font-bold"><AnimatedNumber :value="stats.prestamos.activos" :format="fmtInt" /> activos</p>
                    <p class="mt-0.5 text-xs text-muted-foreground">
                        {{ fmt(stats.prestamos.pendiente_monto) }} por cobrar
                    </p>
                </div>
            </div>

            <div class="dashboard-card rounded-xl border bg-card p-4 flex items-start gap-3">
                <div class="rounded-lg bg-teal-100 p-2.5 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400">
                    <Receipt class="size-5" />
                </div>
                <div class="min-w-0">
                    <p class="text-muted-foreground text-xs font-medium uppercase tracking-wide">Viáticos del Mes</p>
                    <p class="mt-0.5 text-2xl font-bold"><AnimatedNumber :value="stats.viaticos_mes" :format="fmt" /></p>
                </div>
            </div>

            <div class="dashboard-card rounded-xl border bg-card p-4 flex items-start gap-3">
                <div class="rounded-lg bg-cyan-100 p-2.5 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-400">
                    <Briefcase class="size-5" />
                </div>
                <div class="min-w-0">
                    <p class="text-muted-foreground text-xs font-medium uppercase tracking-wide">Servicios Prof. del Mes</p>
                    <p class="mt-0.5 text-2xl font-bold"><AnimatedNumber :value="stats.servicios_mes" :format="fmt" /></p>
                </div>
            </div>
        </div>

        <!-- Alertas -->
        <div v-if="tieneAlertas" class="dashboard-card rounded-xl border border-red-200/50 bg-red-50/50 dark:border-red-900/30 dark:bg-red-950/20 p-4">
            <div class="flex items-center gap-2 mb-3">
                <AlertTriangle class="size-4 text-red-600" />
                <h3 class="font-semibold text-sm text-red-700 dark:text-red-400">Alertas</h3>
            </div>
            <div class="grid gap-3 sm:grid-cols-3">
                <div v-if="stats.alertas.colaboradores_sin_perfil > 0" class="flex items-center gap-2 text-sm">
                    <span class="size-2 rounded-full bg-red-500 shrink-0" />
                    <span class="text-red-700 dark:text-red-400">
                        <strong>{{ stats.alertas.colaboradores_sin_perfil }}</strong> colaborador(es) sin perfil
                    </span>
                </div>
                <div v-if="stats.alertas.jornadas_atrasadas > 0" class="flex items-center gap-2 text-sm">
                    <span class="size-2 rounded-full bg-amber-500 shrink-0" />
                    <span class="text-amber-700 dark:text-amber-400">
                        <strong>{{ stats.alertas.jornadas_atrasadas }}</strong> jornadas sin validar de semanas anteriores
                    </span>
                </div>
                <div v-if="stats.alertas.cuotas_proximas.length > 0" class="flex items-center gap-2 text-sm">
                    <span class="size-2 rounded-full bg-orange-500 shrink-0" />
                    <span class="text-orange-700 dark:text-orange-400">
                        <strong>{{ stats.alertas.cuotas_proximas.length }}</strong> cuota(s) por vencer en 7 días
                    </span>
                </div>
            </div>
        </div>

        <!-- Middle row: charts + validation -->
        <div class="grid gap-6 lg:grid-cols-2">
            <!-- Colaboradores por tipo -->
            <div class="dashboard-card rounded-xl border bg-card p-5">
                <h3 class="font-semibold mb-4 flex items-center gap-2">
                    <Users class="size-4 text-muted-foreground" />
                    Colaboradores por tipo
                </h3>
                <div class="space-y-3">
                    <div v-for="(v, k, i) in stats.colaboradores.por_tipo" :key="k" class="flex items-center gap-3">
                        <span class="text-sm w-24 text-muted-foreground truncate">{{ tipoLabel[k] || k }}</span>
                        <div class="flex-1 h-5 rounded-full bg-muted overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="tipoColor[k] || 'bg-primary'"
                                :style="{
                                    width: mounted ? (v / maxTipo) * 100 + '%' : '0%',
                                    transitionDelay: (450 + i * 90) + 'ms',
                                }"
                            />
                        </div>
                        <span class="text-sm font-medium w-10 text-right">{{ v }}</span>
                    </div>
                    <div v-if="Object.keys(stats.colaboradores.por_tipo).length === 0" class="text-sm text-muted-foreground py-4 text-center">
                        Sin datos
                    </div>
                </div>
            </div>

            <!-- Eventos vigentes por tamaño -->
            <div class="dashboard-card rounded-xl border bg-card p-5">
                <h3 class="font-semibold mb-4 flex items-center gap-2">
                    <CalendarDays class="size-4 text-muted-foreground" />
                    Eventos vigentes por tamaño
                </h3>
                <div class="space-y-3">
                    <div v-for="(v, k, i) in stats.eventos.vigentes_por_tamano" :key="k" class="flex items-center gap-3">
                        <span class="text-sm w-24 text-muted-foreground truncate">{{ tamanoLabel[k] || k }}</span>
                        <div class="flex-1 h-5 rounded-full bg-muted overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="tamanoColor[k] || 'bg-primary'"
                                :style="{
                                    width: mounted ? (v / maxTamanoVigentes) * 100 + '%' : '0%',
                                    transitionDelay: (450 + i * 90) + 'ms',
                                }"
                            />
                        </div>
                        <span class="text-sm font-medium w-10 text-right">{{ v }}</span>
                    </div>
                    <div v-if="Object.keys(stats.eventos.vigentes_por_tamano).length === 0" class="text-sm text-muted-foreground py-4 text-center">
                        Sin eventos vigentes
                    </div>
                </div>
            </div>

            <!-- Eventos totales por año -->
            <div class="dashboard-card rounded-xl border bg-card p-5">
                <h3 class="font-semibold mb-4 flex items-center gap-2">
                    <CalendarDays class="size-4 text-muted-foreground" />
                    Eventos totales por año
                </h3>
                <div class="space-y-3">
                    <div v-for="(item, i) in eventosPorAnio" :key="item.anio" class="flex items-center gap-3">
                        <span class="text-sm w-12 text-muted-foreground tabular-nums">{{ item.anio }}</span>
                        <div class="flex-1 h-5 rounded-full bg-muted overflow-hidden">
                            <div
                                class="h-full rounded-full bg-primary transition-all duration-500"
                                :style="{
                                    width: mounted ? item.pct + '%' : '0%',
                                    transitionDelay: (450 + i * 90) + 'ms',
                                }"
                            />
                        </div>
                        <span class="text-sm font-medium w-10 text-right">{{ item.total }}</span>
                    </div>
                    <div v-if="eventosPorAnio.length === 0" class="text-sm text-muted-foreground py-4 text-center">
                        Sin eventos con fecha registrada
                    </div>
                </div>
            </div>

            <!-- Nómina pagada por año -->
            <div class="dashboard-card rounded-xl border bg-card p-5">
                <h3 class="font-semibold mb-4 flex items-center gap-2">
                    <Banknote class="size-4 text-muted-foreground" />
                    Nómina pagada por año
                </h3>
                <div class="space-y-3">
                    <div v-for="(item, i) in nominaPorAnio" :key="item.anio" class="flex items-center gap-3">
                        <span class="text-sm w-12 text-muted-foreground tabular-nums">{{ item.anio }}</span>
                        <div class="flex-1 h-5 rounded-full bg-muted overflow-hidden">
                            <div
                                class="h-full rounded-full bg-primary transition-all duration-500"
                                :style="{
                                    width: mounted ? item.pct + '%' : '0%',
                                    transitionDelay: (450 + i * 90) + 'ms',
                                }"
                            />
                        </div>
                        <span class="text-sm font-medium w-28 text-right truncate shrink-0" :title="`${item.registros} registros`">
                            {{ fmt(item.total) }}
                        </span>
                    </div>
                    <div v-if="nominaPorAnio.length === 0" class="text-sm text-muted-foreground py-4 text-center">
                        Sin nóminas pagadas aún
                    </div>
                </div>
            </div>

            <!-- Validación de jornadas por año -->
            <div class="rounded-xl border bg-card p-5 flex flex-col items-center">
                <h3 class="font-semibold mb-4 flex items-center gap-2 self-start">
                    <ClipboardCheck class="size-4 text-muted-foreground" />
                    Validación de jornadas por año
                </h3>
                <div class="flex flex-col items-center gap-3 w-full">
                    <div class="relative size-32">
                        <svg class="size-32 -rotate-90" viewBox="0 0 36 36">
                            <circle cx="18" cy="18" r="15.5" fill="none" stroke="hsl(var(--muted))" stroke-width="3.5" />
                            <circle
                                cx="18" cy="18" r="15.5" fill="none"
                                :stroke="`hsl(${pctValidacion === 100 ? '142 76% 36%' : pctValidacion > 50 ? '142 76% 46%' : '35 92% 50%'})`"
                                stroke-width="3.5" stroke-linecap="round"
                                :stroke-dasharray="100"
                                :stroke-dashoffset="mounted ? 100 - pctValidacion : 100"
                                class="transition-all duration-1000 ease-out"
                                style="transition-delay: 450ms"
                            />
                        </svg>
                        <span
                            class="absolute inset-0 flex items-center justify-center text-2xl font-bold"
                            :class="pctValidacion === 100 ? 'text-emerald-600' : 'text-foreground'"
                        >
                            <AnimatedNumber :value="pctValidacion" :format="fmtPct" />
                        </span>
                    </div>
                    <div class="flex items-center gap-6 text-sm">
                        <div class="flex items-center gap-1.5">
                            <span class="size-2.5 rounded-full bg-emerald-500" />
                            <span class="text-muted-foreground">{{ stats.validacion.validadas_anio }} validadas ({{ stats.validacion.total_anio }})</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="size-2.5 rounded-full bg-muted-foreground/30" />
                            <span class="text-muted-foreground">{{ stats.validacion.total_anio - stats.validacion.validadas_anio }} pendientes</span>
                        </div>
                    </div>
                    <div class="w-full mt-2 space-y-2 border-t border-muted pt-3">
                        <div v-for="item in validacionPorAnio" :key="item.anio" class="flex items-center gap-3">
                            <span class="text-xs w-10 text-muted-foreground tabular-nums">{{ item.anio }}</span>
                            <div class="flex-1 h-4 rounded-full bg-muted overflow-hidden">
                                <div class="h-full rounded-full" :class="item.pct === 100 ? 'bg-emerald-500' : 'bg-primary'" :style="{ width: item.pct + '%' }" />
                            </div>
                            <span class="text-xs font-medium w-16 text-right tabular-nums">{{ item.validadas }}/{{ item.total }}</span>
                        </div>
                        <div v-if="validacionPorAnio.length === 0" class="text-xs text-muted-foreground py-1 text-center">
                            Sin jornadas registradas
                        </div>
                    </div>
                </div>
            </div>

            <!-- Distribución de gastos del año -->
            <div class="rounded-xl border bg-card p-5 flex flex-col items-center">
                <h3 class="font-semibold mb-4 flex items-center gap-2 self-start">
                    <CircleDollarSign class="size-4 text-muted-foreground" />
                    Distribución de gastos del año
                </h3>
                <div v-if="totalGastos > 0" class="flex flex-col items-center gap-4 w-full">
                    <div class="relative size-32">
                        <svg
                            class="size-32 transition-all duration-700 ease-out"
                            :class="mounted ? 'opacity-100 scale-100' : 'opacity-0 scale-95'"
                            style="transition-delay: 450ms"
                            viewBox="0 0 100 100"
                            v-html="conicoSegments"
                        />
                    </div>
                    <div class="grid grid-cols-2 gap-x-6 gap-y-1.5 text-sm w-full max-w-xs">
                        <div v-for="item in gastosPorcentajes" :key="item.label" class="flex items-center gap-2">
                            <span class="size-2.5 rounded-full shrink-0" :class="item.color" />
                            <span class="text-muted-foreground truncate">{{ item.label }}</span>
                            <span class="font-medium ml-auto tabular-nums">{{ item.pct }}%</span>
                        </div>
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Total del año: <AnimatedNumber :value="totalGastos" :format="fmt" />
                    </p>
                </div>
                <div v-else class="flex flex-col items-center justify-center py-8 text-sm text-muted-foreground">
                    Sin gastos registrados este año
                </div>
            </div>
        </div>

        <!-- Bottom grid: transport + vencimientos -->
        <div class="grid gap-6 lg:grid-cols-2">
            <!-- Resumen de transporte -->
            <div class="dashboard-card rounded-xl border bg-card p-5">
                <h3 class="font-semibold mb-4 flex items-center gap-2">
                    <Truck class="size-4 text-muted-foreground" />
                    Resumen de transporte
                </h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-muted-foreground">Unidades totales</span>
                        <span class="font-medium">{{ fmtEntero(stats.transporte.total_unidades) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-muted-foreground">Propias</span>
                        <span class="font-medium">{{ fmtEntero(stats.transporte.propias) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-muted-foreground">Rentadas</span>
                        <span class="font-medium">{{ fmtEntero(stats.transporte.rentadas) }}</span>
                    </div>
                    <hr class="border-muted" />
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-muted-foreground">Categorías de vehículo</span>
                        <span class="font-medium">{{ fmtEntero(stats.transporte.total_categorias) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-muted-foreground">Tarifas registradas</span>
                        <span class="font-medium">{{ fmtEntero(stats.transporte.total_tarifas) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-muted-foreground">Tarifa promedio</span>
                        <span class="font-medium">{{ fmt(stats.transporte.tarifa_promedio) }}</span>
                    </div>
                    <Button size="sm" variant="outline" class="w-full mt-2" as-child>
                        <Link :href="transportes.index.url()">Gestionar transportes</Link>
                    </Button>
                </div>
            </div>

            <!-- Vencimientos próximos -->
            <div class="dashboard-card rounded-xl border bg-card p-5">
                <h3 class="font-semibold mb-4 flex items-center gap-2">
                    <ShieldAlert class="size-4 text-muted-foreground" />
                    Vencimientos próximos (30 días)
                </h3>
                <div class="space-y-3">
                    <div v-if="stats.vencimientos.cuotas.length > 0">
                        <p class="text-xs font-medium text-muted-foreground uppercase mb-2">Cuotas de préstamo</p>
                        <div v-for="c in stats.vencimientos.cuotas" :key="'cuota-' + c.id" class="flex items-center justify-between text-sm py-1.5 border-b border-muted/50 last:border-0">
                            <div class="min-w-0">
                                <p class="truncate font-medium">{{ c.prestamo?.colaborador?.apellidos }}, {{ c.prestamo?.colaborador?.nombre }}</p>
                                <p class="text-xs text-muted-foreground">Cuota #{{ c.numero_plazo }} · vence {{ fmtFecha(c.fecha_programada) }}</p>
                            </div>
                            <span class="font-medium tabular-nums shrink-0 ml-2">{{ fmt(Number(c.monto)) }}</span>
                        </div>
                    </div>
                    <div v-if="stats.vencimientos.seguros.length > 0">
                        <p class="text-xs font-medium text-muted-foreground uppercase mb-2 mt-3">Seguros de unidades</p>
                        <div v-for="s in stats.vencimientos.seguros" :key="'seguro-' + s.id" class="flex items-center justify-between text-sm py-1.5">
                            <div class="min-w-0">
                                <p class="truncate font-medium">{{ s.marca }} {{ s.modelo }}</p>
                                <p class="text-xs text-muted-foreground">{{ s.numero_placas || 'Sin placas' }} · vence {{ fmtFecha(s.vigencia_poliza_seguro) }}</p>
                            </div>
                        </div>
                    </div>
                    <div v-if="stats.vencimientos.cuotas.length === 0 && stats.vencimientos.seguros.length === 0" class="text-sm text-muted-foreground py-6 text-center">
                        Sin vencimientos próximos
                    </div>
                    <Button size="sm" variant="outline" class="w-full mt-1" as-child>
                        <Link :href="prestamos.index.url()">Gestionar préstamos</Link>
                    </Button>
                </div>
            </div>
        </div>

        <!-- Bottom row: tables -->
        <div class="grid gap-6 lg:grid-cols-2">
            <!-- Últimas nóminas -->
            <div class="dashboard-card rounded-xl border bg-card">
                <div class="flex items-center justify-between px-5 py-4 border-b">
                    <h3 class="font-semibold flex items-center gap-2">
                        <History class="size-4 text-muted-foreground" />
                        Últimas nóminas
                    </h3>
                    <Button size="sm" variant="ghost" as-child>
                        <Link :href="historial.index.url()">Ver todas</Link>
                    </Button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="max-sm:hidden">
                            <tr class="border-b bg-muted/50">
                                <th class="px-4 py-2 text-left font-medium text-xs text-muted-foreground">Colaborador</th>
                                <th class="px-4 py-2 text-left font-medium text-xs text-muted-foreground">Período</th>
                                <th class="px-4 py-2 text-right font-medium text-xs text-muted-foreground">Total</th>
                                <th class="px-4 py-2 text-center font-medium text-xs text-muted-foreground">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="max-sm:block">
                            <tr v-for="n in stats.nomina.ultimas" :key="n.id" class="max-sm:flex max-sm:flex-col max-sm:gap-1 max-sm:p-3 max-sm:border-b max-sm:border-muted/50">
                                <td data-label="Colaborador" class="px-4 py-2 font-medium whitespace-nowrap max-sm:px-0 max-sm:py-0 max-sm:flex max-sm:items-center max-sm:gap-2">
                                    <span class="hidden max-sm:inline text-xs text-muted-foreground shrink-0">Colaborador</span>
                                    {{ n.colaborador?.apellidos }}, {{ n.colaborador?.nombre }}
                                </td>
                                <td data-label="Período" class="px-4 py-2 text-muted-foreground text-xs whitespace-nowrap tabular-nums max-sm:px-0 max-sm:py-0 max-sm:flex max-sm:items-center max-sm:gap-2">
                                    <span class="hidden max-sm:inline text-xs text-muted-foreground shrink-0">Período</span>
                                    {{ fmtFecha(n.periodo_inicio) }} – {{ fmtFecha(n.periodo_fin) }}
                                </td>
                                <td data-label="Total" class="px-4 py-2 text-right font-medium whitespace-nowrap max-sm:px-0 max-sm:py-0 max-sm:flex max-sm:items-center max-sm:gap-2">
                                    <span class="hidden max-sm:inline text-xs text-muted-foreground shrink-0">Total</span>
                                    {{ fmt(Number(n.total_final)) }}
                                </td>
                                <td data-label="Estado" class="px-4 py-2 text-center whitespace-nowrap max-sm:px-0 max-sm:py-0 max-sm:flex max-sm:items-center max-sm:gap-2">
                                    <span class="hidden max-sm:inline text-xs text-muted-foreground shrink-0">Estado</span>
                                    <span
                                        class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="n.estado === 'PAGADO'
                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                            : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'"
                                    >
                                        {{ n.estado }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="stats.nomina.ultimas.length === 0">
                                <td colspan="4" class="text-muted-foreground px-4 py-8 text-center text-sm">
                                    Sin nóminas registradas
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Próximos eventos -->
            <div class="dashboard-card rounded-xl border bg-card">
                <div class="flex items-center justify-between px-5 py-4 border-b">
                    <h3 class="font-semibold flex items-center gap-2">
                        <CalendarDays class="size-4 text-muted-foreground" />
                        Próximos eventos
                    </h3>
                    <Button size="sm" variant="ghost" as-child>
                        <Link :href="eventos.index.url()">Ver todos</Link>
                    </Button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="max-sm:hidden">
                            <tr class="border-b bg-muted/50">
                                <th class="px-4 py-2 text-left font-medium text-xs text-muted-foreground">Evento</th>
                                <th class="px-4 py-2 text-left font-medium text-xs text-muted-foreground">Inicio</th>
                                <th class="px-4 py-2 text-left font-medium text-xs text-muted-foreground">Tamaño</th>
                                <th class="px-4 py-2 text-right font-medium text-xs text-muted-foreground">Pago</th>
                            </tr>
                        </thead>
                        <tbody class="max-sm:block">
                            <tr v-for="e in stats.eventos.proximos" :key="e.id" class="max-sm:flex max-sm:flex-col max-sm:gap-1 max-sm:p-3 max-sm:border-b max-sm:border-muted/50">
                                <td data-label="Evento" class="px-4 py-2 font-medium max-sm:px-0 max-sm:py-0 max-sm:flex max-sm:items-center max-sm:gap-2">
                                    <span class="hidden max-sm:inline text-xs text-muted-foreground shrink-0">Evento</span>
                                    {{ e.nombre }}
                                </td>
                                <td data-label="Inicio" class="px-4 py-2 text-muted-foreground text-xs max-sm:px-0 max-sm:py-0 max-sm:flex max-sm:items-center max-sm:gap-2">
                                    <span class="hidden max-sm:inline text-xs text-muted-foreground shrink-0">Inicio</span>
                                    {{ e.fecha_inicio || '—' }}
                                </td>
                                <td data-label="Tamaño" class="px-4 py-2 max-sm:px-0 max-sm:py-0 max-sm:flex max-sm:items-center max-sm:gap-2">
                                    <span class="hidden max-sm:inline text-xs text-muted-foreground shrink-0">Tamaño</span>
                                    <span
                                        class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium"
                                        :class="e.tamano === 'CHICO'
                                            ? 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'
                                            : e.tamano === 'MEDIANO'
                                            ? 'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300'
                                            : 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300'"
                                    >
                                        {{ tamanoLabel[e.tamano] || e.tamano }}
                                    </span>
                                </td>
                                <td data-label="Pago" class="px-4 py-2 text-right font-medium text-xs max-sm:px-0 max-sm:py-0 max-sm:flex max-sm:items-center max-sm:gap-2">
                                    <span class="hidden max-sm:inline text-xs text-muted-foreground shrink-0">Pago</span>
                                    {{ Number(e.pago_por_evento_completo) ? fmt(Number(e.pago_por_evento_completo)) : '—' }}
                                </td>
                            </tr>
                            <tr v-if="stats.eventos.proximos.length === 0">
                                <td colspan="4" class="text-muted-foreground px-4 py-8 text-center text-sm">
                                    Sin eventos próximos
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.dashboard-card:nth-child(1) { animation-delay: 60ms; }
.dashboard-card:nth-child(2) { animation-delay: 120ms; }
.dashboard-card:nth-child(3) { animation-delay: 180ms; }
.dashboard-card:nth-child(4) { animation-delay: 240ms; }
.dashboard-card:nth-child(5) { animation-delay: 300ms; }
.dashboard-card:nth-child(6) { animation-delay: 360ms; }
.dashboard-card:nth-child(7) { animation-delay: 420ms; }
.dashboard-card:nth-child(8) { animation-delay: 480ms; }
</style>
