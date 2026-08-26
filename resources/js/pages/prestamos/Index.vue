<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { CalendarClock, ChevronDown, ChevronRight, HandCoins, Plus, Trash2 } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { useConfirm } from '@/composables/useConfirm';
import { fmtFecha } from '@/lib/fecha';
import * as prestamosApi from '@/routes/prestamos';
import * as prestamoCuotas from '@/routes/prestamos/cuotas';

type TipoColaborador = 'COLABORADOR BASE' | 'CONDUCTOR' | 'CONDUCTOR BASE';
type Periodicidad = 'SEMANAL' | 'QUINCENAL' | 'MENSUAL';
type EstadoCuota = 'PENDIENTE' | 'PAGADA';

interface Colaborador {
    id: number;
    nombre: string;
    apellidos: string;
    tipo: TipoColaborador;
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
    colaborador_id: number;
    monto_total: string;
    num_plazos: number;
    periodicidad: Periodicidad;
    fecha_inicio: string;
    concepto: string | null;
    autoriza: string | null;
    colaborador: Colaborador;
    cuotas: Cuota[];
}

const props = defineProps<{
    prestamos: Prestamo[];
    colaboradores: Colaborador[];
}>();

const tipoLabel: Record<TipoColaborador, string> = {
    'COLABORADOR BASE': 'Base',
    'CONDUCTOR':        'Conductor',
    'CONDUCTOR BASE':   'Conductor base',
};

const periodicidadLabel: Record<Periodicidad, string> = {
    SEMANAL: 'Semanal',
    QUINCENAL: 'Quincenal',
    MENSUAL: 'Mensual',
};

const fmtMoney = (val: number | string) =>
    new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', minimumFractionDigits: 2 }).format(Number(val));

const saldoPendiente = (p: Prestamo): number =>
    p.cuotas.filter(c => c.estado === 'PENDIENTE').reduce((s, c) => s + parseFloat(c.monto), 0);

const estaLiquidado = (p: Prestamo): boolean => saldoPendiente(p) <= 0;

// ── Fila expandible (calendario de cuotas) ──────────────────────────
const abiertos = ref<Set<number>>(new Set());
const toggle = (id: number) => {
    const next = new Set(abiertos.value);

    if (next.has(id)) {
        next.delete(id);
    } else {
        next.add(id);
    }

    abiertos.value = next;
};

const cuotaBadge: Record<EstadoCuota, string> = {
    PENDIENTE: 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-400',
    PAGADA: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-400',
};

// ── Formulario de nuevo préstamo ────────────────────────────────────
const today    = new Date().toISOString().split('T')[0];
const showForm = ref(false);

const form = useForm({
    colaborador_id: '' as string,
    monto_total:    '' as string,
    num_plazos:     '4' as string,
    periodicidad:   'QUINCENAL' as Periodicidad,
    fecha_inicio:   today,
    concepto:       '',
    autoriza:       '',
});

const formIntentado = ref(false);

const erroresForm = computed<Record<string, string>>(() => {
    const e: Record<string, string> = {};

    if (!form.colaborador_id) {
        e.colaborador_id = 'Selecciona un colaborador.';
    }

    if (!Number(form.monto_total) || Number(form.monto_total) < 0.01) {
        e.monto_total = 'El monto debe ser mayor a 0.';
    }

    const plazos = parseInt(form.num_plazos, 10);

    if (!plazos || plazos < 1 || plazos > 52) {
        e.num_plazos = 'Indica entre 1 y 52 plazos.';
    }

    if (!form.fecha_inicio) {
        e.fecha_inicio = 'La fecha de inicio es obligatoria.';
    }

    return e;
});

const msg = (campo: string): string => {
    const cliente = erroresForm.value[campo];

    if (formIntentado.value && cliente) {
        return cliente;
    }

    return (form.errors as Record<string, string>)[campo] ?? '';
};

watch(() => showForm, (open) => {
    if (open) {
        formIntentado.value = false;
    }
});

// Vista previa del monto por cuota (informativa, el backend recalcula igual)
const montoPorCuotaPreview = computed(() => {
    const total = parseFloat(form.monto_total);
    const plazos = parseInt(form.num_plazos, 10);

    if (!total || !plazos) {
        return null;
    }

    return total / plazos;
});

const submit = () => {
    formIntentado.value = true;

    if (Object.keys(erroresForm.value).length > 0) {
        return;
    }

    form.post(prestamosApi.store.url(), {
        onSuccess: () => {
            showForm.value = false;
            formIntentado.value = false;
            form.reset();
            form.fecha_inicio = today;
            form.num_plazos = '4';
            form.periodicidad = 'QUINCENAL';
        },
    });
};

// ── Eliminar (solo si ninguna cuota está pagada — el backend también lo valida) ──
const { confirm } = useConfirm();
const eliminando = ref<number | null>(null);

const eliminar = async (p: Prestamo) => {
    const ok = await confirm(`¿Eliminar el préstamo de ${p.colaborador.nombre} ${p.colaborador.apellidos} por ${fmtMoney(p.monto_total)}? Esta acción no se puede deshacer.`, {
        title: 'Eliminar préstamo',
    });

    if (!ok) {
return;
}

    eliminando.value = p.id;
    router.delete(prestamosApi.destroy.url(p.id), {
        preserveScroll: true,
        onFinish: () => {
 eliminando.value = null; 
},
    });
};

// ── Pago manual de un plazo (independiente del descuento automático en nómina) ──
const pagando = ref<Set<number>>(new Set());

// ── Gestión completa de plazos: aplazar (cambiar fecha) y distribuir carga ──
const plazosSel = ref<Set<number>>(new Set());
const aplazarAbierto = ref(false);
const aplazarIds = ref<number[]>([]);
const nuevaFechaPlazo = ref('');

// Solo se puede tocar un plazo PENDIENTE que aún no esté incluido en una nómina guardada.
const cuotaModificable = (c: Cuota): boolean => c.estado === 'PENDIENTE' && c.historico_nomina_id === null;

const toggleCuota = (id: number) => {
    const s = new Set(plazosSel.value);

    if (s.has(id)) {
        s.delete(id);
    } else {
        s.add(id);
    }

    plazosSel.value = s;
};

const selDePrestamo = (p: Prestamo): Cuota[] =>
    p.cuotas.filter(c => cuotaModificable(c) && plazosSel.value.has(c.id));

const abrirAplazar = (cuotaIds: number[]) => {
    aplazarIds.value = cuotaIds;
    nuevaFechaPlazo.value = new Date().toISOString().slice(0, 10);
    aplazarAbierto.value = true;
};

const aplazando = ref(false);

const confirmarAplazar = () => {
    aplazando.value = true;
    router.post(prestamoCuotas.aplazar.url(), {
        cuota_ids: aplazarIds.value,
        nueva_fecha: nuevaFechaPlazo.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            aplazarAbierto.value = false;
        },
        onFinish: () => {
 aplazando.value = false; 
},
    });
};

const distribuyendo = ref<number | null>(null);

const distribuirCarga = async (p: Prestamo) => {
    const cuotas = selDePrestamo(p);
    const total = cuotas.reduce((s, c) => s + parseFloat(c.monto), 0);
    const plural = cuotas.length === 1 ? 'plazo' : 'plazos';

    const ok = await confirm(
        `¿Distribuir la carga de ${cuotas.length} ${plural} (${fmtMoney(total)}) de ${p.colaborador.nombre} ${p.colaborador.apellidos}? ` +
        `El monto se repartirá por igual entre los demás plazos pendientes del préstamo y los ${plural} elegidos se eliminarán.`,
        { title: 'Distribuir carga', confirmLabel: 'Sí, distribuir' },
    );

    if (!ok) {
        return;
    }

    distribuyendo.value = p.id;
    router.post(prestamoCuotas.distribuir.url(), { cuota_ids: cuotas.map(c => c.id) }, {
        preserveScroll: true,
        onFinish: () => {
 distribuyendo.value = null; 
},
    });
};

const pagarCuota = async (c: Cuota) => {
    const ok = await confirm(`¿Registrar el pago del plazo ${c.numero_plazo} por ${fmtMoney(c.monto)}?`, {
        title: 'Marcar cuota como pagada',
    });

    if (!ok) {
return;
}

    pagando.value = new Set(pagando.value).add(c.id);
    router.patch(prestamoCuotas.pagar.url({ cuota: c.id }), {}, {
        preserveScroll: true,
        onFinish: () => {
            const next = new Set(pagando.value);
            next.delete(c.id);
            pagando.value = next;
        },
    });
};

const revertirCuota = async (c: Cuota) => {
    const ok = await confirm(`¿Revertir el pago del plazo ${c.numero_plazo}? Volverá a quedar pendiente.`, {
        title: 'Revertir pago',
        variant: 'destructive',
    });

    if (!ok) {
return;
}

    pagando.value = new Set(pagando.value).add(c.id);
    router.patch(prestamoCuotas.revertir.url({ cuota: c.id }), {}, {
        preserveScroll: true,
        onFinish: () => {
            const next = new Set(pagando.value);
            next.delete(c.id);
            pagando.value = next;
        },
    });
};
</script>

<template>
    <Head title="Préstamos" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4 sm:p-6">
        <!-- Header -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
            <div>
                <h1 class="text-2xl font-semibold">Préstamos</h1>
                <p class="text-muted-foreground mt-1 text-sm">
                    {{ props.prestamos.length }} registros · Solo Base y Conductor · Las cuotas se descuentan automáticamente en la nómina
                </p>
            </div>

            <Dialog v-model:open="showForm">
                <DialogTrigger as-child>
                    <Button>
                        <Plus class="size-4" />
                        Nuevo préstamo
                    </Button>
                </DialogTrigger>
                <DialogContent class="max-w-md">
                    <DialogHeader>
                        <DialogTitle>Registrar préstamo</DialogTitle>
                    </DialogHeader>

                    <form class="grid gap-4" @submit.prevent="submit">
                        <div class="space-y-1">
                            <Label>Colaborador <span class="text-destructive">*</span></Label>
                            <Select v-model="form.colaborador_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccionar..." />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="c in colaboradores"
                                        :key="c.id"
                                        :value="String(c.id)"
                                    >
                                        {{ c.apellidos }}, {{ c.nombre }}
                                        <span class="text-muted-foreground ml-1 text-xs">({{ tipoLabel[c.tipo] }})</span>
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="msg('colaborador_id')" />
                        </div>

                        <div class="space-y-1">
                            <Label>Monto total <span class="text-destructive">*</span></Label>
                            <Input v-model="form.monto_total" type="number" step="0.01" min="0.01" inputmode="decimal" required />
                            <InputError :message="msg('monto_total')" />
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div class="space-y-1">
                                <Label>Número de plazos <span class="text-destructive">*</span></Label>
                                <Input v-model="form.num_plazos" type="number" step="1" min="1" max="52" inputmode="numeric" required />
                                <InputError :message="msg('num_plazos')" />
                            </div>
                            <div class="space-y-1">
                                <Label>Periodicidad <span class="text-destructive">*</span></Label>
                                <Select v-model="form.periodicidad">
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="SEMANAL">Semanal</SelectItem>
                                        <SelectItem value="QUINCENAL">Quincenal</SelectItem>
                                        <SelectItem value="MENSUAL">Mensual</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <p v-if="montoPorCuotaPreview" class="text-muted-foreground text-xs">
                            {{ form.num_plazos }} cuotas de ~{{ fmtMoney(montoPorCuotaPreview) }} cada una, empezando el {{ fmtFecha(form.fecha_inicio) }}.
                        </p>

                        <div class="space-y-1">
                            <Label>Fecha de inicio <span class="text-destructive">*</span></Label>
                            <Input v-model="form.fecha_inicio" type="date" required />
                            <InputError :message="msg('fecha_inicio')" />
                        </div>

                        <div class="space-y-1">
                            <Label>Concepto</Label>
                            <Input v-model="form.concepto" placeholder="Motivo del préstamo" maxlength="500" />
                        </div>

                        <div class="space-y-1">
                            <Label>Autoriza</Label>
                            <Input v-model="form.autoriza" placeholder="Nombre de quien autoriza" maxlength="255" />
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" @click="showForm = false">Cancelar</Button>
                            <Button type="submit" :disabled="form.processing" class="gap-1.5">
                                <Spinner v-if="form.processing" class="size-4" />
                                {{ form.processing ? 'Registrando…' : 'Registrar' }}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>

        <!-- Tabla escritorio (≥ lg) -->
        <div class="hidden overflow-x-auto rounded-xl border lg:block">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 border-b">
                    <tr>
                        <th class="w-8 px-2 py-3"></th>
                        <th class="px-4 py-3 text-left font-medium">Colaborador</th>
                        <th class="px-4 py-3 text-left font-medium">Tipo</th>
                        <th class="px-4 py-3 text-right font-medium">Monto total</th>
                        <th class="px-4 py-3 text-left font-medium">Plazos</th>
                        <th class="px-4 py-3 text-left font-medium">Inicio</th>
                        <th class="px-4 py-3 text-right font-medium">Saldo pendiente</th>
                        <th class="px-4 py-3 text-center font-medium">Estado</th>
                        <th class="px-4 py-3 text-right font-medium">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-if="props.prestamos.length === 0">
                        <td colspan="9" class="text-muted-foreground px-4 py-10 text-center text-sm">
                            Sin préstamos registrados.
                        </td>
                    </tr>
                    <template v-for="p in props.prestamos" :key="p.id">
                        <tr class="hover:bg-muted/20 cursor-pointer" @click="toggle(p.id)">
                            <td class="px-2 py-3">
                                <component :is="abiertos.has(p.id) ? ChevronDown : ChevronRight" class="text-muted-foreground size-4" />
                            </td>
                            <td class="px-4 py-3 font-medium whitespace-nowrap">
                                {{ p.colaborador.apellidos }}, {{ p.colaborador.nombre }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                    {{ tipoLabel[p.colaborador.tipo] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums font-medium">{{ fmtMoney(p.monto_total) }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ p.num_plazos }} · {{ periodicidadLabel[p.periodicidad] }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ fmtFecha(p.fecha_inicio) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ fmtMoney(saldoPendiente(p)) }}</td>
                            <td class="px-4 py-3 text-center">
                                <span
                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="estaLiquidado(p)
                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-400'
                                        : 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-400'"
                                >
                                    {{ estaLiquidado(p) ? 'Liquidado' : 'Activo' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right" @click.stop>
                                <Button
                                    size="sm" variant="ghost" class="text-destructive"
                                    :disabled="eliminando === p.id || p.cuotas.some(c => c.estado === 'PAGADA')"
                                    :title="p.cuotas.some(c => c.estado === 'PAGADA') ? 'Ya tiene cuotas pagadas, no se puede eliminar' : undefined"
                                    @click="eliminar(p)"
                                >
                                    <Spinner v-if="eliminando === p.id" class="size-3.5" />
                                    <Trash2 v-else class="size-3.5" />
                                </Button>
                            </td>
                        </tr>
                        <tr v-if="abiertos.has(p.id)">
                            <td colspan="9" class="bg-muted/10 border-t px-4 py-3">
                                <p v-if="p.concepto" class="mb-2 text-sm">
                                    <span class="text-muted-foreground">Concepto:</span> {{ p.concepto }}
                                    <template v-if="p.autoriza"> · <span class="text-muted-foreground">Autoriza:</span> {{ p.autoriza }}</template>
                                </p>
                                <div v-if="selDePrestamo(p).length > 0" class="mb-2 flex flex-wrap items-center gap-2 rounded-md border border-dashed px-3 py-2">
                                    <Button size="sm" variant="outline" class="h-7 text-xs" @click="abrirAplazar(selDePrestamo(p).map(c => c.id))">
                                        <CalendarClock class="size-3.5" />
                                        Aplazar seleccionados
                                    </Button>
                                    <Button
                                        size="sm" variant="outline" class="h-7 gap-1.5 text-xs"
                                        :disabled="distribuyendo === p.id"
                                        @click="distribuirCarga(p)"
                                    >
                                        <Spinner v-if="distribuyendo === p.id" class="size-3.5" />
                                        <HandCoins v-else class="size-3.5" />
                                        Distribuir carga ({{ selDePrestamo(p).length }})
                                    </Button>
                                    <span class="text-muted-foreground text-[11px]">
                                        Reparto por igual entre los plazos pendientes restantes del mismo préstamo.
                                    </span>
                                </div>
                                <div class="space-y-1">
                                    <div v-for="c in p.cuotas" :key="c.id" class="flex items-center gap-2 py-1 text-xs">
                                        <Checkbox
                                            v-if="cuotaModificable(c)"
                                            :model-value="plazosSel.has(c.id)"
                                            @update:model-value="() => toggleCuota(c.id)"
                                        />
                                        <span class="text-muted-foreground whitespace-nowrap">Plazo {{ c.numero_plazo }}/{{ p.num_plazos }}</span>
                                        <span class="tabular-nums whitespace-nowrap">{{ fmtFecha(c.fecha_programada) }}</span>
                                        <span class="tabular-nums font-medium whitespace-nowrap">{{ fmtMoney(c.monto) }}</span>
                                        <span class="rounded px-1.5 py-0.5 font-medium whitespace-nowrap" :class="cuotaBadge[c.estado]">
                                            {{ c.estado === 'PAGADA' ? 'Pagada' : 'Pendiente' }}
                                        </span>
                                        <span v-if="c.fecha_pago" class="text-muted-foreground min-w-0 flex-1 whitespace-nowrap">
                                            el {{ fmtFecha(c.fecha_pago) }}<template v-if="c.historico_nomina_id"> (vía nómina)</template>
                                        </span>
                                        <span v-if="!c.fecha_pago && c.historico_nomina_id" class="text-muted-foreground min-w-0 flex-1 truncate">
                                            Incluida en nómina guardada — no modificable
                                        </span>
                                        <div class="ml-auto flex shrink-0 items-center gap-1">
                                            <Button
                                                v-if="cuotaModificable(c)"
                                                size="sm" variant="ghost" class="h-6 shrink-0 text-xs"
                                                title="Cambiar fecha del plazo"
                                                @click="abrirAplazar([c.id])"
                                            >
                                                <CalendarClock class="size-3.5" />
                                            </Button>
                                            <Button
                                                v-if="c.estado === 'PENDIENTE'"
                                                size="sm" variant="outline" class="h-6 shrink-0 gap-1.5 text-xs"
                                                :disabled="pagando.has(c.id)"
                                                @click="pagarCuota(c)"
                                            >
                                                <Spinner v-if="pagando.has(c.id)" class="size-3" />
                                                Marcar pagada
                                            </Button>
                                            <Button
                                                v-else-if="!c.historico_nomina_id"
                                                size="sm" variant="ghost" class="h-6 shrink-0 gap-1 text-xs text-muted-foreground"
                                                :disabled="pagando.has(c.id)"
                                                @click="revertirCuota(c)"
                                            >
                                                <Spinner v-if="pagando.has(c.id)" class="size-3" />
                                                Revertir
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Cards móvil (< lg) -->
        <div class="flex flex-col gap-3 lg:hidden">
            <div v-if="props.prestamos.length === 0" class="text-muted-foreground rounded-xl border border-dashed py-10 text-center text-sm">
                Sin préstamos registrados.
            </div>

            <div v-for="p in props.prestamos" :key="p.id" class="rounded-xl border p-4">
                <button class="flex w-full items-start justify-between gap-2 text-left" @click="toggle(p.id)">
                    <div class="min-w-0">
                        <p class="truncate font-medium">{{ p.colaborador.apellidos }}, {{ p.colaborador.nombre }}</p>
                        <p class="text-muted-foreground mt-0.5 text-xs">
                            {{ tipoLabel[p.colaborador.tipo] }} · {{ p.num_plazos }} · {{ periodicidadLabel[p.periodicidad] }}
                        </p>
                    </div>
                    <div class="flex flex-shrink-0 items-center gap-2">
                        <span
                            class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium"
                            :class="estaLiquidado(p)
                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-400'
                                : 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-400'"
                        >
                            {{ estaLiquidado(p) ? 'Liquidado' : 'Activo' }}
                        </span>
                        <component :is="abiertos.has(p.id) ? ChevronDown : ChevronRight" class="text-muted-foreground size-4" />
                    </div>
                </button>

                <dl class="mt-3 space-y-1.5 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-muted-foreground text-xs">Monto total</dt>
                        <dd class="font-medium tabular-nums">{{ fmtMoney(p.monto_total) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-muted-foreground text-xs">Saldo pendiente</dt>
                        <dd class="tabular-nums">{{ fmtMoney(saldoPendiente(p)) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-muted-foreground text-xs">Inicio</dt>
                        <dd class="tabular-nums">{{ fmtFecha(p.fecha_inicio) }}</dd>
                    </div>
                </dl>

                <div class="mt-3 flex items-center justify-between">
                    <p v-if="p.concepto" class="text-muted-foreground text-xs">
                        {{ p.concepto }}<template v-if="p.autoriza"> · Autoriza: {{ p.autoriza }}</template>
                    </p>
                    <Button
                        size="sm" variant="ghost" class="text-destructive"
                        :disabled="eliminando === p.id || p.cuotas.some(c => c.estado === 'PAGADA')"
                        @click="eliminar(p)"
                    >
                        <Spinner v-if="eliminando === p.id" class="size-3.5" />
                        <Trash2 v-else class="size-3.5" />
                    </Button>
                </div>

                <div v-if="abiertos.has(p.id)" class="mt-3 border-t pt-3">
                    <div v-if="selDePrestamo(p).length > 0" class="mb-2 flex flex-wrap items-center gap-2 rounded-md border border-dashed px-3 py-2">
                        <Button size="sm" variant="outline" class="h-7 text-xs" @click="abrirAplazar(selDePrestamo(p).map(c => c.id))">
                            <CalendarClock class="size-3.5" />
                            Aplazar
                        </Button>
                        <Button
                            size="sm" variant="outline" class="h-7 gap-1.5 text-xs"
                            :disabled="distribuyendo === p.id"
                            @click="distribuirCarga(p)"
                        >
                            <Spinner v-if="distribuyendo === p.id" class="size-3.5" />
                            <HandCoins v-else class="size-3.5" />
                            Distribuir ({{ selDePrestamo(p).length }})
                        </Button>
                    </div>

                    <div class="space-y-2">
                        <div v-for="c in p.cuotas" :key="c.id" class="rounded-md border bg-muted/20 p-2 text-xs">
                            <div class="flex items-center gap-2">
                                <Checkbox
                                    v-if="cuotaModificable(c)"
                                    :model-value="plazosSel.has(c.id)"
                                    @update:model-value="() => toggleCuota(c.id)"
                                />
                                <span class="text-muted-foreground whitespace-nowrap">Plazo {{ c.numero_plazo }}/{{ p.num_plazos }}</span>
                                <span class="tabular-nums whitespace-nowrap">{{ fmtFecha(c.fecha_programada) }}</span>
                                <span class="tabular-nums font-medium whitespace-nowrap ml-auto">{{ fmtMoney(c.monto) }}</span>
                                <span class="rounded px-1.5 py-0.5 font-medium whitespace-nowrap" :class="cuotaBadge[c.estado]">
                                    {{ c.estado === 'PAGADA' ? 'Pagada' : 'Pendiente' }}
                                </span>
                            </div>
                            <p v-if="c.fecha_pago" class="text-muted-foreground mt-1">
                                Pagada el {{ fmtFecha(c.fecha_pago) }}<template v-if="c.historico_nomina_id"> (vía nómina)</template>
                            </p>
                            <p v-else-if="c.historico_nomina_id" class="text-muted-foreground mt-1">
                                Incluida en nómina guardada — no modificable
                            </p>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                <Button
                                    v-if="cuotaModificable(c)"
                                    size="sm" variant="ghost" class="h-6 text-xs"
                                    title="Cambiar fecha del plazo"
                                    @click="abrirAplazar([c.id])"
                                >
                                    <CalendarClock class="size-3.5" />
                                </Button>
                                <Button
                                    v-if="c.estado === 'PENDIENTE'"
                                    size="sm" variant="outline" class="h-6 gap-1.5 text-xs"
                                    :disabled="pagando.has(c.id)"
                                    @click="pagarCuota(c)"
                                >
                                    <Spinner v-if="pagando.has(c.id)" class="size-3" />
                                    Marcar pagada
                                </Button>
                                <Button
                                    v-else-if="!c.historico_nomina_id"
                                    size="sm" variant="ghost" class="h-6 gap-1 text-xs text-muted-foreground"
                                    :disabled="pagando.has(c.id)"
                                    @click="revertirCuota(c)"
                                >
                                    <Spinner v-if="pagando.has(c.id)" class="size-3" />
                                    Revertir
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ─── Cambiar fecha / aplazar plazos ─── -->
    <Dialog v-model:open="aplazarAbierto">
        <DialogContent class="max-w-sm">
            <DialogHeader>
                <DialogTitle>
                    Aplazar {{ aplazarIds.length === 1 ? 'el plazo' : 'los plazos' }} seleccionad{{ aplazarIds.length === 1 ? 'o' : 'os' }}
                </DialogTitle>
            </DialogHeader>
            <div class="space-y-3">
                <p class="text-muted-foreground text-sm">
                    El plazo se moverá a la nueva fecha de descuento.
                </p>
                <div class="space-y-1">
                    <Label>Nueva fecha</Label>
                    <Input v-model="nuevaFechaPlazo" type="date" required />
                </div>
            </div>
            <DialogFooter>
                <Button type="button" variant="outline" @click="aplazarAbierto = false">Cancelar</Button>
                <Button :disabled="!nuevaFechaPlazo || aplazando" class="gap-1.5" @click="confirmarAplazar">
                    <Spinner v-if="aplazando" class="size-4" />
                    {{ aplazando ? 'Aplazando…' : 'Confirmar' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
