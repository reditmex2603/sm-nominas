<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ChevronDown, ChevronRight, Filter, Printer, X } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import DatosBancarios from '@/components/DatosBancarios.vue';
import NominaDesgloseDetalle from '@/components/NominaDesgloseDetalle.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useConfirm } from '@/composables/useConfirm';
import { fmtFecha } from '@/lib/fecha';
import type {TipoPago} from '@/lib/tipoPago';
import * as nomina from '@/routes/nomina';

type Estado = 'PENDIENTE' | 'PAGADO';

interface PerfilBancario { banco: string | null; beneficiario: string | null; clave_interbancaria: string | null }
interface ColaboradorRef { id: number; nombre: string; apellidos: string; perfil?: PerfilBancario | null }
interface ColaboradorFull { id: number; nombre: string; apellidos: string; tipo: string }
interface EventoRef { id: number; nombre: string; tamano: string }

interface Desglose {
    _bonos_evento_puro?: number;
    _bono_septimo?: number;
    _jornadas?: {
        fecha: string;
        tipo_pago: TipoPago;
        detalle: string | null;
        extras: string | null;
        bono_evento: number;
        pct_etapas: number | null;
        evento_tamano: string | null;
    }[];
    _categoria?: string | null;
    _rutas?: { fecha: string; vehiculo: string; distancia: string; monto: number; extras: string | null }[];
    _total_rutas?: number;
    _etapas?: string[];
    _registros?: { fecha: string; etapa: string | null; extras: string | null; contabiliza: boolean }[];
    _porcentaje?: number;
    _pago_base?: number;
    _pago_extras?: number;
}

interface NominaRecord {
    id: number;
    colaborador_id: number;
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
    estado: Estado;
    evento_id: number | null;
    fecha_calculo: string;
    colaborador: ColaboradorRef;
    desglose: Desglose | null;
}

const props = defineProps<{
    nominas_base: NominaRecord[];
    nominas_freelance: NominaRecord[];
    nominas_conductores: NominaRecord[];
    nominas_conductor_base: NominaRecord[];
    eventos: EventoRef[];
    colaboradores: ColaboradorFull[];
}>();

// ── Tabs ───────────────────────────────────────────────────────────
type Tab = 'base' | 'eventos' | 'conductores' | 'conductor_base';
const activeTab = ref<Tab>('base');

// ── Filtros ────────────────────────────────────────────────────────
const mostrarFiltros = ref(false);

const filtros = ref({
    colaborador: '',
    estado:      '' as '' | 'PENDIENTE' | 'PAGADO',
    fechaDesde:  '',
    fechaHasta:  '',
    evento:      '',
});

// Al cambiar de tab, limpiar colaborador (las listas cambian por tipo)
watch(activeTab, () => {
 filtros.value.colaborador = ''; 
});

const hayFiltrosActivos = computed(() =>
    filtros.value.colaborador !== '' ||
    filtros.value.estado !== '' ||
    filtros.value.fechaDesde !== '' ||
    filtros.value.fechaHasta !== '' ||
    filtros.value.evento !== ''
);

const limpiarFiltros = () => {
    filtros.value = { colaborador: '', estado: '', fechaDesde: '', fechaHasta: '', evento: '' };
};

// Colaboradores según tab activo para el Select de filtro
const colaboradoresFiltro = computed(() => {
    if (activeTab.value === 'base')            {
return props.colaboradores.filter(c => c.tipo === 'COLABORADOR BASE');
}

    if (activeTab.value === 'eventos')         {
return props.colaboradores.filter(c => c.tipo === 'FREELANCE');
}

    if (activeTab.value === 'conductor_base')  {
return props.colaboradores.filter(c => c.tipo === 'CONDUCTOR BASE');
}

    return props.colaboradores.filter(c => c.tipo === 'CONDUCTOR');
});

// ── Filtrado cliente-side ──────────────────────────────────────────
function filtrarPorPeriodo(lista: NominaRecord[]): NominaRecord[] {
    let r = lista;

    if (filtros.value.colaborador) {
        r = r.filter(n => String(n.colaborador_id) === filtros.value.colaborador);
    }

    if (filtros.value.estado)     {
r = r.filter(n => n.estado === filtros.value.estado);
}

    if (filtros.value.fechaDesde) {
r = r.filter(n => !!n.periodo_inicio && String(n.periodo_inicio).slice(0, 10) >= filtros.value.fechaDesde);
}

    if (filtros.value.fechaHasta) {
r = r.filter(n => !!n.periodo_fin && String(n.periodo_fin).slice(0, 10) <= filtros.value.fechaHasta);
}

    return r;
}

const nominasBaseFiltradas       = computed(() => filtrarPorPeriodo(props.nominas_base));
const nominasCondFiltradas       = computed(() => filtrarPorPeriodo(props.nominas_conductores));
const nominasCondBaseFiltradas   = computed(() => filtrarPorPeriodo(props.nominas_conductor_base));

const nominasFreelanceFiltradas  = computed(() => {
    let r = props.nominas_freelance;

    if (filtros.value.colaborador) {
        r = r.filter(n => String(n.colaborador_id) === filtros.value.colaborador);
    }

    if (filtros.value.estado)  {
r = r.filter(n => n.estado === filtros.value.estado);
}

    if (filtros.value.evento)  {
r = r.filter(n => String(n.evento_id) === filtros.value.evento);
}

    return r;
});

// Eventos agrupando las nóminas freelance filtradas
const eventosFiltrados = computed(() => {
    const porEvento = new Map<number, NominaRecord[]>();
    nominasFreelanceFiltradas.value.forEach(n => {
        if (n.evento_id === null) {
return;
}

        const arr = porEvento.get(n.evento_id) ?? [];
        arr.push(n);
        porEvento.set(n.evento_id, arr);
    });

    return props.eventos
        .map(ev => ({ ...ev, nominas: porEvento.get(ev.id) ?? [] }))
        .filter(ev => !hayFiltrosActivos.value || ev.nominas.length > 0);
});

// ── Resúmenes ─────────────────────────────────────────────────────
function resumen(lista: NominaRecord[]) {
    const pendiente = lista.filter(n => n.estado === 'PENDIENTE');
    const pagado    = lista.filter(n => n.estado === 'PAGADO');

    return {
        porPagar: pendiente.reduce((s, n) => s + parseFloat(n.total_final), 0),
        pagado:   pagado.reduce((s, n)    => s + parseFloat(n.total_final), 0),
        total:    lista.length,
    };
}

const resumenBase      = computed(() => resumen(nominasBaseFiltradas.value));
const resumenFreelance = computed(() => resumen(nominasFreelanceFiltradas.value));
const resumenCond      = computed(() => resumen(nominasCondFiltradas.value));
const resumenCondBase  = computed(() => resumen(nominasCondBaseFiltradas.value));

// ── Pagar nómina ──────────────────────────────────────────────────
const { confirm } = useConfirm();

const pagar = async (id: number) => {
    const ok = await confirm('¿Marcar esta nómina como PAGADA? Esta acción es irreversible.', {
        title: 'Marcar como pagada',
        confirmLabel: 'Sí, marcar como pagada',
        variant: 'default',
    });

    if (ok) {
router.patch(nomina.pagar.url(id), {}, { preserveScroll: true });
}
};

// ── Eliminar nómina (para recalcular tras un error) ────────────────
const eliminando = ref(new Set<number>());

const eliminar = async (id: number) => {
    const ok = await confirm(
        '¿Eliminar este cálculo de nómina? Podrás volver a calcularlo desde el Panel de Validación.',
        { title: 'Eliminar nómina', confirmLabel: 'Sí, eliminar', variant: 'destructive' },
    );

    if (!ok) {
return;
}

    eliminando.value = new Set(eliminando.value).add(id);
    router.delete(nomina.eliminar.url(id), {
        preserveScroll: true,
        onFinish: () => {
            const next = new Set(eliminando.value);
            next.delete(id);
            eliminando.value = next;
        },
    });
};

// ── Collapsibles eventos ───────────────────────────────────────────
const expandidos = ref(new Set<number>());
const toggleEvento = (id: number) => {
    const next = new Set(expandidos.value);

    if (next.has(id)) {
        next.delete(id);
    } else {
        next.add(id);
    }

    expandidos.value = next;
};

// ── Detalle expandible por nómina (jornadas/registros/rutas evaluados) ─
const filasExpandidas = ref(new Set<number>());
const toggleFila = (id: number) => {
    const next = new Set(filasExpandidas.value);

    if (next.has(id)) {
        next.delete(id);
    } else {
        next.add(id);
    }

    filasExpandidas.value = next;
};

// ── Imprimir ──────────────────────────────────────────────────────
const imprimir = (id: number) => {
    window.open(`/historial/${id}/imprimir`, '_blank');
};

const imprimirRango = (tipo: string) => {
    const params = new URLSearchParams();

    if (filtros.value.fechaDesde) {
        params.set('fecha_desde', filtros.value.fechaDesde);
    }

    if (filtros.value.fechaHasta) {
        params.set('fecha_hasta', filtros.value.fechaHasta);
    }

    if (filtros.value.colaborador) {
        params.set('colaborador_id', filtros.value.colaborador);
    }

    if (filtros.value.estado) {
        params.set('estado', filtros.value.estado);
    }

    if (tipo) {
        params.set('tipo', tipo);
    }

    window.open(`/historial/imprimir-rango?${params.toString()}`, '_blank');
};

// ── Helpers ───────────────────────────────────────────────────────
const estadoBadgeClass = (e: Estado) =>
    e === 'PAGADO'
        ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200'
        : 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200';

const fmt = (n: string | number) => {
    const v = parseFloat(String(n));
    const s = v < 0 ? '-' : '';

    return `${s}$${Math.abs(v).toLocaleString('es-MX', { minimumFractionDigits: 2 })}`;
};

const estadoEvento = (ev: { nominas: NominaRecord[] }) => {
    if (ev.nominas.length === 0) {
return 'SIN_NOMINAS';
}

    if (ev.nominas.every(n => n.estado === 'PAGADO')) {
return 'PAGADO';
}

    return 'PENDIENTE';
};

const eventoBadgeClass = (estado: string) => ({
    SIN_NOMINAS: 'bg-slate-100 text-slate-600',
    PENDIENTE:   'bg-amber-100 text-amber-800',
    PAGADO:      'bg-emerald-100 text-emerald-800',
}[estado] ?? '');

// Conteo visible para el resumen general (tab activo)
const totalVisible = computed(() => {
    if (activeTab.value === 'base')       {
return nominasBaseFiltradas.value.length;
}

    if (activeTab.value === 'eventos')    {
return nominasFreelanceFiltradas.value.length;
}

    if (activeTab.value === 'conductor_base') {
return nominasCondBaseFiltradas.value.length;
}

    return nominasCondFiltradas.value.length;
});
const totalTotal = computed(() => {
    if (activeTab.value === 'base')       {
return props.nominas_base.length;
}

    if (activeTab.value === 'eventos')    {
return props.nominas_freelance.length;
}

    if (activeTab.value === 'conductor_base') {
return props.nominas_conductor_base.length;
}

    return props.nominas_conductores.length;
});
</script>

<template>
    <Head title="Historial de Nóminas" />

    <div class="flex h-full flex-1 flex-col p-4 sm:p-6">
        <!-- Header -->
        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
            <div>
                <h1 class="text-2xl font-semibold">Historial de Nóminas</h1>
                <p class="text-muted-foreground mt-1 text-sm">Registro permanente de nóminas calculadas</p>
            </div>
        </div>

        <!-- Tabs + toggle filtros -->
        <div class="mb-1 flex items-center gap-1 border-b overflow-x-auto">
            <button
                v-for="tab in ([
                    { key: 'base',        label: 'Base',        total: nominas_base.length },
                    { key: 'eventos',     label: 'Freelance',   total: nominas_freelance.length },
                    { key: 'conductores', label: 'Conductores', total: nominas_conductores.length },
                    { key: 'conductor_base', label: 'Conductor base', total: nominas_conductor_base.length },
                ] as const)"
                :key="tab.key"
                class="flex items-center gap-1.5 whitespace-nowrap px-4 py-2 text-sm font-medium transition-colors"
                :class="activeTab === tab.key
                    ? 'text-foreground border-b-2 border-primary -mb-px'
                    : 'text-muted-foreground hover:text-foreground'"
                @click="activeTab = tab.key"
            >
                {{ tab.label }}
                <span class="text-muted-foreground text-xs tabular-nums">({{ tab.total }})</span>
            </button>

            <!-- Spacer + toggle -->
            <div class="ml-auto mb-1 flex items-center gap-2">
                <span v-if="hayFiltrosActivos" class="text-muted-foreground text-xs">
                    {{ totalVisible }} de {{ totalTotal }} visibles
                </span>
                <Button
                    size="sm"
                    variant="outline"
                    class="relative gap-1.5 text-xs"
                    :class="mostrarFiltros ? 'bg-muted' : ''"
                    @click="mostrarFiltros = !mostrarFiltros"
                >
                    <Filter class="size-3.5" />
                    Filtros
                    <span
                        v-if="hayFiltrosActivos"
                        class="absolute -right-1 -top-1 size-2 rounded-full bg-blue-500"
                        title="Hay filtros activos"
                    />
                </Button>
            </div>
        </div>

        <!-- Panel de filtros (colapsable) -->
        <div v-if="mostrarFiltros" class="my-3 flex flex-wrap items-end gap-3 rounded-lg border bg-muted/30 p-3">
            <!-- Colaborador (opciones según tab) -->
            <div class="w-full space-y-1 sm:w-auto">
                <Label class="text-xs text-muted-foreground">Colaborador</Label>
                <Select v-model="filtros.colaborador">
                    <SelectTrigger class="h-8 w-full sm:w-48 text-xs">
                        <SelectValue placeholder="Todos" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="">Todos</SelectItem>
                        <SelectItem
                            v-for="c in colaboradoresFiltro"
                            :key="c.id"
                            :value="String(c.id)"
                        >
                            {{ c.apellidos }}, {{ c.nombre }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <!-- Estado -->
            <div class="space-y-1">
                <Label class="text-xs text-muted-foreground">Estado</Label>
                <Select v-model="filtros.estado">
                    <SelectTrigger class="h-8 w-full sm:w-32 text-xs">
                        <SelectValue placeholder="Todos" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="">Todos</SelectItem>
                        <SelectItem value="PENDIENTE">Pendiente</SelectItem>
                        <SelectItem value="PAGADO">Pagado</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <!-- Fecha desde / hasta (solo Base y Conductores) -->
            <template v-if="activeTab !== 'eventos'">
                <div class="space-y-1">
                    <Label class="text-xs text-muted-foreground">Período desde</Label>
                    <Input v-model="filtros.fechaDesde" type="date" class="h-8 text-xs" />
                </div>
                <div class="space-y-1">
                    <Label class="text-xs text-muted-foreground">Período hasta</Label>
                    <Input v-model="filtros.fechaHasta" type="date" class="h-8 text-xs" />
                </div>
            </template>

            <!-- Evento (solo Freelance) -->
            <div v-if="activeTab === 'eventos'" class="space-y-1">
                <Label class="text-xs text-muted-foreground">Evento</Label>
                <Select v-model="filtros.evento">
                    <SelectTrigger class="h-8 w-full sm:w-52 text-xs">
                        <SelectValue placeholder="Todos los eventos" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="">Todos</SelectItem>
                        <SelectItem v-for="ev in eventos" :key="ev.id" :value="String(ev.id)">
                            {{ ev.nombre }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <Button
                v-if="hayFiltrosActivos"
                size="sm"
                variant="ghost"
                class="h-8 gap-1 self-end text-xs text-destructive hover:text-destructive"
                @click="limpiarFiltros"
            >
                <X class="size-3" />
                Limpiar
            </Button>
        </div>

        <!-- ─── TAB: BASE ─── -->
        <div v-if="activeTab === 'base'" class="flex flex-col gap-4 pt-4">
            <!-- Widgets resumen -->
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="rounded-xl border p-4">
                    <p class="text-muted-foreground text-xs">Por pagar</p>
                    <p class="mt-1 text-lg font-semibold text-amber-600">{{ fmt(resumenBase.porPagar) }}</p>
                </div>
                <div class="rounded-xl border p-4">
                    <p class="text-muted-foreground text-xs">Pagado</p>
                    <p class="mt-1 text-lg font-semibold text-emerald-600">{{ fmt(resumenBase.pagado) }}</p>
                </div>
                <div class="rounded-xl border p-4">
                    <p class="text-muted-foreground text-xs">Total registros</p>
                    <p class="mt-1 text-lg font-semibold">{{ resumenBase.total }}</p>
                </div>
            </div>

            <!-- Imprimir rango -->
            <div class="flex justify-end">
                <Button
                    size="sm"
                    variant="outline"
                    class="gap-1.5 text-xs"
                    @click="imprimirRango('base')"
                >
                    <Printer class="size-3.5" />
                    Imprimir nóminas
                </Button>
            </div>

            <!-- Tabla -->
            <div class="overflow-x-auto rounded-xl border">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 border-b">
                        <tr>
                            <th class="w-8 px-2 py-3"></th>
                            <th class="px-4 py-3 text-left font-medium">Período</th>
                            <th class="px-4 py-3 text-left font-medium">Colaborador</th>
                            <th class="px-4 py-3 text-right font-medium">Días</th>
                            <th class="px-4 py-3 text-right font-medium">Base</th>
                            <th class="px-4 py-3 text-right font-medium">Bonos</th>
                            <th class="px-4 py-3 text-right font-medium">Compensación</th>
                            <th class="px-4 py-3 text-right font-medium">Anticipos</th>
                            <th class="px-4 py-3 text-right font-medium">Préstamos</th>
                            <th class="px-4 py-3 text-right font-medium">Total</th>
                            <th class="px-4 py-3 text-left font-medium">Comentario</th>
                            <th class="px-4 py-3 text-center font-medium">Estado</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-if="nominasBaseFiltradas.length === 0">
                            <td colspan="13" class="text-muted-foreground px-4 py-10 text-center text-sm">
                                <template v-if="nominas_base.length === 0">Sin nóminas de colaboradores base registradas.</template>
                                <template v-else>Ningún registro coincide con los filtros aplicados.</template>
                            </td>
                        </tr>
                        <template v-for="n in nominasBaseFiltradas" :key="n.id">
                            <tr class="hover:bg-muted/30 cursor-pointer" @click="toggleFila(n.id)">
                                <td class="px-2 py-3">
                                    <component
                                        :is="filasExpandidas.has(n.id) ? ChevronDown : ChevronRight"
                                        class="text-muted-foreground size-4"
                                    />
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-xs tabular-nums">
                                    {{ fmtFecha(n.periodo_inicio) }} → {{ fmtFecha(n.periodo_fin) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap font-medium">
                                    {{ n.colaborador.apellidos }}, {{ n.colaborador.nombre }}
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ n.dias }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ fmt(n.total_base) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ fmt(n.bonos_evento) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ fmt(n.compensaciones) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-red-600">-{{ fmt(n.anticipos) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-red-600">-{{ fmt(n.prestamos) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums font-semibold">{{ fmt(n.total_final) }}</td>
                                <td class="max-w-48 truncate px-4 py-3 text-xs text-muted-foreground" :title="n.comentario ?? ''">
                                    {{ n.comentario ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="estadoBadgeClass(n.estado)"
                                    >
                                        {{ n.estado }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-1.5">
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            class="gap-1 text-xs"
                                            @click.stop="imprimir(n.id)"
                                        >
                                            <Printer class="size-3.5" />
                                            Imprimir
                                        </Button>
                                        <template v-if="n.estado === 'PENDIENTE'">
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                class="text-xs"
                                                @click.stop="pagar(n.id)"
                                            >
                                                Marcar pagado
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                class="text-xs text-destructive hover:text-destructive"
                                                :disabled="eliminando.has(n.id)"
                                                @click.stop="eliminar(n.id)"
                                            >
                                                Eliminar
                                            </Button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filasExpandidas.has(n.id)" class="bg-muted/20">
                                <td colspan="13" class="px-6 py-3">
                                    <NominaDesgloseDetalle tipo-colaborador="COLABORADOR BASE" :desglose="n.desglose" />
                                    <DatosBancarios :perfil="n.colaborador.perfil ?? null" class="mt-3" />
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ─── TAB: EVENTOS (Freelance) ─── -->
        <div v-else-if="activeTab === 'eventos'" class="flex flex-col gap-3 pt-4">
            <!-- Widgets resumen -->
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="rounded-xl border p-4">
                    <p class="text-muted-foreground text-xs">Por pagar</p>
                    <p class="mt-1 text-lg font-semibold text-amber-600">{{ fmt(resumenFreelance.porPagar) }}</p>
                </div>
                <div class="rounded-xl border p-4">
                    <p class="text-muted-foreground text-xs">Pagado</p>
                    <p class="mt-1 text-lg font-semibold text-emerald-600">{{ fmt(resumenFreelance.pagado) }}</p>
                </div>
                <div class="rounded-xl border p-4">
                    <p class="text-muted-foreground text-xs">Nóminas</p>
                    <p class="mt-1 text-lg font-semibold">{{ resumenFreelance.total }}</p>
                </div>
            </div>

            <p v-if="eventosFiltrados.length === 0" class="text-muted-foreground py-10 text-center text-sm">
                <template v-if="nominas_freelance.length === 0">Sin nóminas freelance registradas.</template>
                <template v-else>Ningún evento coincide con los filtros aplicados.</template>
            </p>

            <div
                v-for="ev in eventosFiltrados"
                :key="ev.id"
                class="overflow-hidden rounded-xl border"
            >
                <!-- Cabecera colapsable -->
                <button
                    class="flex w-full flex-wrap items-center gap-3 px-4 py-3 text-left transition-colors hover:bg-muted/30"
                    :disabled="ev.nominas.length === 0"
                    @click="toggleEvento(ev.id)"
                >
                    <component
                        :is="expandidos.has(ev.id) ? ChevronDown : ChevronRight"
                        class="size-4 text-muted-foreground flex-shrink-0"
                        :class="ev.nominas.length === 0 ? 'opacity-0' : ''"
                    />
                    <span class="font-medium">{{ ev.nombre }}</span>
                    <span class="text-muted-foreground text-xs">{{ ev.tamano }}</span>
                    <span
                        class="ml-auto inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                        :class="eventoBadgeClass(estadoEvento(ev))"
                    >
                        {{ estadoEvento(ev) === 'SIN_NOMINAS' ? 'Sin nóminas' : estadoEvento(ev) }}
                    </span>
                    <span class="text-muted-foreground text-xs">
                        {{ ev.nominas.filter(n => n.estado === 'PAGADO').length }}/{{ ev.nominas.length }} pagados
                    </span>
                </button>

                <!-- Nóminas del evento -->
                <div v-if="expandidos.has(ev.id) && ev.nominas.length > 0" class="border-t">
                    <table class="w-full text-sm">
                        <thead class="bg-muted/30 border-b">
                            <tr>
                                <th class="w-8 px-2 py-2"></th>
                                <th class="px-4 py-2 text-left text-xs font-medium">Freelance</th>
                                <th class="px-4 py-2 text-right text-xs font-medium">Compensación</th>
                                <th class="px-4 py-2 text-right text-xs font-medium">Total</th>
                                <th class="px-4 py-2 text-left text-xs font-medium">Comentario</th>
                                <th class="px-4 py-2 text-center text-xs font-medium">Estado</th>
                                <th class="px-4 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <template v-for="n in ev.nominas" :key="n.id">
                                <tr class="hover:bg-muted/20 cursor-pointer" @click="toggleFila(n.id)">
                                    <td class="px-2 py-2.5">
                                        <component
                                            :is="filasExpandidas.has(n.id) ? ChevronDown : ChevronRight"
                                            class="text-muted-foreground size-4"
                                        />
                                    </td>
                                    <td class="px-4 py-2.5 font-medium">
                                        {{ n.colaborador.apellidos }}, {{ n.colaborador.nombre }}
                                    </td>
                                    <td class="px-4 py-2.5 text-right tabular-nums">
                                        {{ fmt(n.compensaciones) }}
                                    </td>
                                    <td class="px-4 py-2.5 text-right tabular-nums font-semibold">
                                        {{ fmt(n.total_final) }}
                                    </td>
                                    <td class="max-w-48 truncate px-4 py-2.5 text-xs text-muted-foreground" :title="n.comentario ?? ''">
                                        {{ n.comentario ?? '—' }}
                                    </td>
                                    <td class="px-4 py-2.5 text-center">
                                        <span
                                            class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                            :class="estadoBadgeClass(n.estado)"
                                        >
                                            {{ n.estado }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <div class="flex justify-end gap-1.5">
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                class="gap-1 text-xs"
                                                @click.stop="imprimir(n.id)"
                                            >
                                                <Printer class="size-3.5" />
                                                Imprimir
                                            </Button>
                                            <template v-if="n.estado === 'PENDIENTE'">
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    class="text-xs"
                                                    @click.stop="pagar(n.id)"
                                                >
                                                    Marcar pagado
                                                </Button>
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    class="text-xs text-destructive hover:text-destructive"
                                                    :disabled="eliminando.has(n.id)"
                                                    @click.stop="eliminar(n.id)"
                                                >
                                                    Eliminar
                                                </Button>
                                            </template>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="filasExpandidas.has(n.id)" class="bg-muted/10">
                                    <td colspan="7" class="px-8 py-3">
                                        <NominaDesgloseDetalle tipo-colaborador="FREELANCE" :desglose="n.desglose" />
                                        <DatosBancarios :perfil="n.colaborador.perfil ?? null" class="mt-3" />
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ─── TAB: CONDUCTORES ─── -->
        <div v-else-if="activeTab === 'conductores'" class="flex flex-col gap-4 pt-4">
            <!-- Widgets resumen -->
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="rounded-xl border p-4">
                    <p class="text-muted-foreground text-xs">Por pagar</p>
                    <p class="mt-1 text-lg font-semibold text-amber-600">{{ fmt(resumenCond.porPagar) }}</p>
                </div>
                <div class="rounded-xl border p-4">
                    <p class="text-muted-foreground text-xs">Pagado</p>
                    <p class="mt-1 text-lg font-semibold text-emerald-600">{{ fmt(resumenCond.pagado) }}</p>
                </div>
                <div class="rounded-xl border p-4">
                    <p class="text-muted-foreground text-xs">Total registros</p>
                    <p class="mt-1 text-lg font-semibold">{{ resumenCond.total }}</p>
                </div>
            </div>

            <!-- Imprimir rango -->
            <div class="flex justify-end">
                <Button
                    size="sm"
                    variant="outline"
                    class="gap-1.5 text-xs"
                    @click="imprimirRango('conductores')"
                >
                    <Printer class="size-3.5" />
                    Imprimir nóminas
                </Button>
            </div>

            <!-- Tabla -->
            <div class="overflow-x-auto rounded-xl border">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 border-b">
                        <tr>
                            <th class="w-8 px-2 py-3"></th>
                            <th class="px-4 py-3 text-left font-medium">Período</th>
                            <th class="px-4 py-3 text-left font-medium">Conductor</th>
                            <th class="px-4 py-3 text-right font-medium">Rutas</th>
                            <th class="px-4 py-3 text-right font-medium">Total rutas</th>
                            <th class="px-4 py-3 text-right font-medium">Compensación</th>
                            <th class="px-4 py-3 text-right font-medium">Anticipos</th>
                            <th class="px-4 py-3 text-right font-medium">Préstamos</th>
                            <th class="px-4 py-3 text-right font-medium">Total</th>
                            <th class="px-4 py-3 text-left font-medium">Comentario</th>
                            <th class="px-4 py-3 text-center font-medium">Estado</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-if="nominasCondFiltradas.length === 0">
                            <td colspan="12" class="text-muted-foreground px-4 py-10 text-center text-sm">
                                <template v-if="nominas_conductores.length === 0">Sin nóminas de conductores registradas.</template>
                                <template v-else>Ningún registro coincide con los filtros aplicados.</template>
                            </td>
                        </tr>
                        <template v-for="n in nominasCondFiltradas" :key="n.id">
                            <tr class="hover:bg-muted/30 cursor-pointer" @click="toggleFila(n.id)">
                                <td class="px-2 py-3">
                                    <component
                                        :is="filasExpandidas.has(n.id) ? ChevronDown : ChevronRight"
                                        class="text-muted-foreground size-4"
                                    />
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-xs tabular-nums">
                                    {{ fmtFecha(n.periodo_inicio) }} → {{ fmtFecha(n.periodo_fin) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap font-medium">
                                    {{ n.colaborador.apellidos }}, {{ n.colaborador.nombre }}
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ n.dias }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ fmt(n.total_base) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ fmt(n.compensaciones) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-red-600">-{{ fmt(n.anticipos) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-red-600">-{{ fmt(n.prestamos) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums font-semibold">{{ fmt(n.total_final) }}</td>
                                <td class="max-w-48 truncate px-4 py-3 text-xs text-muted-foreground" :title="n.comentario ?? ''">
                                    {{ n.comentario ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="estadoBadgeClass(n.estado)"
                                    >
                                        {{ n.estado }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-1.5">
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            class="gap-1 text-xs"
                                            @click.stop="imprimir(n.id)"
                                        >
                                            <Printer class="size-3.5" />
                                            Imprimir
                                        </Button>
                                        <template v-if="n.estado === 'PENDIENTE'">
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                class="text-xs"
                                                @click.stop="pagar(n.id)"
                                            >
                                                Marcar pagado
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                class="text-xs text-destructive hover:text-destructive"
                                                :disabled="eliminando.has(n.id)"
                                                @click.stop="eliminar(n.id)"
                                            >
                                                Eliminar
                                            </Button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filasExpandidas.has(n.id)" class="bg-muted/20">
                                <td colspan="12" class="px-6 py-3">
                                    <NominaDesgloseDetalle tipo-colaborador="CONDUCTOR" :desglose="n.desglose" />
                                    <DatosBancarios :perfil="n.colaborador.perfil ?? null" class="mt-3" />
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ─── TAB: CONDUCTORES BASE ─── -->
        <div v-else-if="activeTab === 'conductor_base'" class="flex flex-col gap-4 pt-4">
            <!-- Widgets resumen -->
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="rounded-xl border p-4">
                    <p class="text-muted-foreground text-xs">Por pagar</p>
                    <p class="mt-1 text-lg font-semibold text-amber-600">{{ fmt(resumenCondBase.porPagar) }}</p>
                </div>
                <div class="rounded-xl border p-4">
                    <p class="text-muted-foreground text-xs">Pagado</p>
                    <p class="mt-1 text-lg font-semibold text-emerald-600">{{ fmt(resumenCondBase.pagado) }}</p>
                </div>
                <div class="rounded-xl border p-4">
                    <p class="text-muted-foreground text-xs">Total registros</p>
                    <p class="mt-1 text-lg font-semibold">{{ resumenCondBase.total }}</p>
                </div>
            </div>

            <!-- Imprimir rango -->
            <div class="flex justify-end">
                <Button
                    size="sm"
                    variant="outline"
                    class="gap-1.5 text-xs"
                    @click="imprimirRango('conductor_base')"
                >
                    <Printer class="size-3.5" />
                    Imprimir nóminas
                </Button>
            </div>

            <!-- Tabla -->
            <div class="overflow-x-auto rounded-xl border">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 border-b">
                        <tr>
                            <th class="w-8 px-2 py-3"></th>
                            <th class="px-4 py-3 text-left font-medium">Período</th>
                            <th class="px-4 py-3 text-left font-medium">Colaborador</th>
                            <th class="px-4 py-3 text-right font-medium">Días</th>
                            <th class="px-4 py-3 text-right font-medium">Base</th>
                            <th class="px-4 py-3 text-right font-medium">Rutas</th>
                            <th class="px-4 py-3 text-right font-medium">Bono 7°</th>
                            <th class="px-4 py-3 text-right font-medium">Compensación</th>
                            <th class="px-4 py-3 text-right font-medium">Anticipos</th>
                            <th class="px-4 py-3 text-right font-medium">Préstamos</th>
                            <th class="px-4 py-3 text-right font-medium">Total</th>
                            <th class="px-4 py-3 text-left font-medium">Comentario</th>
                            <th class="px-4 py-3 text-center font-medium">Estado</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-if="nominasCondBaseFiltradas.length === 0">
                            <td colspan="14" class="text-muted-foreground px-4 py-10 text-center text-sm">
                                <template v-if="nominas_conductor_base.length === 0">Sin nóminas de conductores base registradas.</template>
                                <template v-else>Ningún registro coincide con los filtros aplicados.</template>
                            </td>
                        </tr>
                        <template v-for="n in nominasCondBaseFiltradas" :key="n.id">
                            <tr class="hover:bg-muted/30 cursor-pointer" @click="toggleFila(n.id)">
                                <td class="px-2 py-3">
                                    <component
                                        :is="filasExpandidas.has(n.id) ? ChevronDown : ChevronRight"
                                        class="text-muted-foreground size-4"
                                    />
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-xs tabular-nums">
                                    {{ fmtFecha(n.periodo_inicio) }} → {{ fmtFecha(n.periodo_fin) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap font-medium">
                                    {{ n.colaborador.apellidos }}, {{ n.colaborador.nombre }}
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ n.dias }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ fmt(n.total_base) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ fmt(n.desglose?._total_rutas ?? 0) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ fmt(n.bonos_evento) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ fmt(n.compensaciones) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-red-600">-{{ fmt(n.anticipos) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-red-600">-{{ fmt(n.prestamos) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums font-semibold">{{ fmt(n.total_final) }}</td>
                                <td class="max-w-48 truncate px-4 py-3 text-xs text-muted-foreground" :title="n.comentario ?? ''">
                                    {{ n.comentario ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="estadoBadgeClass(n.estado)"
                                    >
                                        {{ n.estado }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-1.5">
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            class="gap-1 text-xs"
                                            @click.stop="imprimir(n.id)"
                                        >
                                            <Printer class="size-3.5" />
                                            Imprimir
                                        </Button>
                                        <template v-if="n.estado === 'PENDIENTE'">
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                class="text-xs"
                                                @click.stop="pagar(n.id)"
                                            >
                                                Marcar pagado
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                class="text-xs text-destructive hover:text-destructive"
                                                :disabled="eliminando.has(n.id)"
                                                @click.stop="eliminar(n.id)"
                                            >
                                                Eliminar
                                            </Button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filasExpandidas.has(n.id)" class="bg-muted/20">
                                <td colspan="14" class="px-6 py-3">
                                    <NominaDesgloseDetalle tipo-colaborador="CONDUCTOR BASE" :desglose="n.desglose" :sueldo-diario="Number(n.sueldo_diario)" />
                                    <DatosBancarios :perfil="n.colaborador.perfil ?? null" class="mt-3" />
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
