<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
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
import { fmtFecha } from '@/lib/fecha';
import * as anticiposRoutes from '@/routes/anticipos';

type TipoColaborador = 'COLABORADOR BASE' | 'FREELANCE' | 'CONDUCTOR' | 'CONDUCTOR BASE';

interface Colaborador {
    id: number;
    nombre: string;
    apellidos: string;
    tipo: TipoColaborador;
}

interface Anticipo {
    id: number;
    colaborador_id: number;
    concepto: string | null;
    tipo: 'EVENTO' | 'SUELTO';
    evento_id: number | null;
    monto: string;
    fecha: string;
    entregado_por: string | null;
    colaborador: Colaborador;
    evento: { id: number; nombre: string } | null;
}

interface Evento {
    id: number;
    nombre: string;
}

const props = defineProps<{
    anticipos: Anticipo[];
    colaboradores: Colaborador[];
    colaboradores_eventos: Record<string, Evento[]>;
}>();

// ── Filtros (client-side) ──────────────────────────────────────────
const filtroColaborador = ref('');
const filtroTipo        = ref<TipoColaborador | ''>('');
const filtroOrigen      = ref<'EVENTO' | 'SUELTO' | ''>('');
const filtroFechaIni    = ref('');
const filtroFechaFin    = ref('');

const anticiposFiltrados = computed(() => {
    return props.anticipos.filter(a => {
        if (filtroColaborador.value) {
            const texto = `${a.colaborador.nombre} ${a.colaborador.apellidos}`.toLowerCase();

            if (!texto.includes(filtroColaborador.value.toLowerCase())) {
                return false;
            }
        }

        if (filtroTipo.value && a.colaborador.tipo !== filtroTipo.value) {
            return false;
        }

        if (filtroOrigen.value && a.tipo !== filtroOrigen.value) {
            return false;
        }

        if (filtroFechaIni.value && a.fecha < filtroFechaIni.value) {
            return false;
        }

        if (filtroFechaFin.value && a.fecha > filtroFechaFin.value) {
            return false;
        }

        return true;
    });
});

const totalFiltrado = computed(() =>
    anticiposFiltrados.value.reduce((s, a) => s + parseFloat(a.monto), 0),
);

// ── Badges ────────────────────────────────────────────────────────
const tipoBadgeClass: Record<TipoColaborador, string> = {
    'COLABORADOR BASE': 'bg-emerald-100 text-emerald-800',
    'FREELANCE':        'bg-yellow-100 text-yellow-800',
    'CONDUCTOR':        'bg-blue-100 text-blue-800',
    'CONDUCTOR BASE':   'bg-amber-100 text-amber-800',
};
const tipoLabel: Record<TipoColaborador, string> = {
    'COLABORADOR BASE': 'Base',
    'FREELANCE':        'Freelance',
    'CONDUCTOR':        'Conductor',
    'CONDUCTOR BASE':   'Conductor base',
};

// ── Formulario de nuevo anticipo ──────────────────────────────────
const today    = new Date().toISOString().split('T')[0];
const showForm = ref(false);

const form = useForm({
    colaborador_id: '' as string,
    monto:          '' as string,
    concepto:       '',
    tipo:           'SUELTO' as 'EVENTO' | 'SUELTO',
    evento_id:      '' as string,
    fecha:          today,
    entregado_por:  '',
});

const esDeEvento = computed(() => form.tipo === 'EVENTO');

const eventosDelColaborador = computed<Evento[]>(() =>
    props.colaboradores_eventos[form.colaborador_id] ?? [],
);

watch(() => form.colaborador_id, () => {
    const ids = eventosDelColaborador.value.map(e => String(e.id));

    if (form.evento_id && !ids.includes(form.evento_id)) {
        form.evento_id = '';
    }
});

// ── Validación cliente (en vivo tras el primer intento) ────────────
const intentado = ref(false);

const erroresForm = computed<Record<string, string>>(() => {
    const e: Record<string, string> = {};

    if (!form.colaborador_id) {
        e.colaborador_id = 'Selecciona un colaborador.';
    }

    if (!Number(form.monto) || Number(form.monto) < 0.01) {
        e.monto = 'El monto debe ser mayor a 0.';
    }

    if (esDeEvento.value && !form.evento_id) {
        e.evento_id = 'Selecciona el evento asignado.';
    }

    return e;
});

const msg = (campo: string): string => {
    const cliente = erroresForm.value[campo];

    if (intentado.value && cliente) {
        return cliente;
    }

    return (form.errors as Record<string, string>)[campo] ?? '';
};

watch(() => showForm, (open) => {
    if (open) {
        intentado.value = false;
    }
});

const submit = () => {
    if (!esDeEvento.value) {
        form.evento_id = '';
    }

    intentado.value = true;

    if (Object.keys(erroresForm.value).length > 0) {
        return;
    }

    form.post(anticiposRoutes.store.url(), {
        onSuccess: () => {
            showForm.value = false;
            intentado.value = false;
            form.reset();
            form.fecha = today;
            form.tipo = 'SUELTO';
        },
    });
};
</script>

<template>
    <Head title="Anticipos" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4 sm:p-6">
        <!-- Header -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
            <div>
                <h1 class="text-2xl font-semibold">Anticipos</h1>
                <p class="text-muted-foreground mt-1 text-sm">
                    {{ props.anticipos.length }} registros · Solo creación (no editable)
                </p>
            </div>

            <Dialog v-model:open="showForm">
                <DialogTrigger as-child>
                    <Button>
                        <Plus class="size-4" />
                        Nuevo anticipo
                    </Button>
                </DialogTrigger>
                <DialogContent class="w-full max-w-md sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Registrar anticipo</DialogTitle>
                    </DialogHeader>

                    <form class="grid gap-4" @submit.prevent="submit">
                        <!-- Colaborador -->
                        <div class="space-y-1">
                            <Label>Colaborador <span class="text-destructive">*</span></Label>
                            <Select v-model="form.colaborador_id" required>
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

                        <!-- Monto -->
                        <div class="space-y-1">
                            <Label>Monto <span class="text-destructive">*</span></Label>
                            <Input v-model="form.monto" type="number" step="0.01" min="0.01" inputmode="decimal" required />
                            <InputError :message="msg('monto')" />
                        </div>

                        <!-- Origen -->
                        <div class="space-y-1">
                            <Label>Origen <span class="text-destructive">*</span></Label>
                            <Select v-model="form.tipo">
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="SUELTO">Suelto (sin evento)</SelectItem>
                                    <SelectItem value="EVENTO">Por evento</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Evento (solo si es por evento) -->
                        <div v-if="esDeEvento" class="space-y-1">
                            <Label>Evento <span class="text-destructive">*</span></Label>
                            <Select
                                v-model="form.evento_id"
                                :disabled="!form.colaborador_id || eventosDelColaborador.length === 0"
                                required
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccionar evento asignado..." />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="e in eventosDelColaborador" :key="e.id" :value="String(e.id)">
                                        {{ e.nombre }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p
                                v-if="!form.colaborador_id"
                                class="text-muted-foreground text-xs"
                            >
                                Primero selecciona el colaborador para listar sus eventos asignados.
                            </p>
                            <p
                                v-else-if="eventosDelColaborador.length === 0"
                                class="text-muted-foreground text-xs"
                            >
                                El colaborador no tiene eventos asignados; solo podrá marcarse como suelto.
                            </p>
                            <InputError :message="msg('evento_id')" />
                        </div>

                        <!-- Concepto -->
                        <div class="space-y-1">
                            <Label>Concepto</Label>
                            <Input v-model="form.concepto" placeholder="Ej. Festival de Verano 2024" maxlength="500" />
                            <p v-if="esDeEvento" class="text-muted-foreground text-xs">Si se deja vacío, se usará el nombre del evento para el descuento automático.</p>
                            <p v-else class="text-muted-foreground text-xs">Para freelance: debe coincidir con el nombre del evento para descuento automático.</p>
                        </div>

                        <!-- Fecha de descuento -->
                        <div class="space-y-1">
                            <Label>Fecha de descuento</Label>
                            <Input v-model="form.fecha" type="date" />
                            <p class="text-muted-foreground text-xs">Fecha en que se aplicará el descuento en la nómina del colaborador.</p>
                        </div>

                        <!-- Entregado por -->
                        <div class="space-y-1">
                            <Label>¿Quién entrega?</Label>
                            <Input v-model="form.entregado_por" placeholder="Nombre del responsable" maxlength="255" />
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

        <!-- Filtros -->
        <div class="flex flex-wrap gap-3">
            <Input
                v-model="filtroColaborador"
                placeholder="Buscar colaborador..."
                class="w-full sm:max-w-48"
            />
            <Select v-model="filtroTipo">
                <SelectTrigger class="w-full sm:w-40">
                    <SelectValue placeholder="Todos los tipos" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="">Todos</SelectItem>
                    <SelectItem value="COLABORADOR BASE">Base</SelectItem>
                    <SelectItem value="FREELANCE">Freelance</SelectItem>
                    <SelectItem value="CONDUCTOR">Conductor</SelectItem>
                    <SelectItem value="CONDUCTOR BASE">Conductor base</SelectItem>
                </SelectContent>
            </Select>
            <Select v-model="filtroOrigen">
                <SelectTrigger class="w-full sm:w-36">
                    <SelectValue placeholder="Origen" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="">Todos</SelectItem>
                    <SelectItem value="EVENTO">Por evento</SelectItem>
                    <SelectItem value="SUELTO">Suelto</SelectItem>
                </SelectContent>
            </Select>
            <Input v-model="filtroFechaIni" type="date" class="w-full sm:w-36" title="Fecha desde" />
            <Input v-model="filtroFechaFin" type="date" class="w-full sm:w-36" title="Fecha hasta" />
            <p class="text-muted-foreground self-center text-sm">
                {{ anticiposFiltrados.length }} registros ·
                Total: <strong>${{ totalFiltrado.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</strong>
            </p>
        </div>

        <!-- Tabla (solo lectura) — escritorio -->
        <div class="hidden overflow-x-auto rounded-xl border lg:block">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Fecha descuento</th>
                        <th class="px-4 py-3 text-left font-medium">Colaborador</th>
                        <th class="px-4 py-3 text-left font-medium">Tipo</th>
                        <th class="px-4 py-3 text-left font-medium">Origen</th>
                        <th class="px-4 py-3 text-left font-medium">Concepto</th>
                        <th class="px-4 py-3 text-right font-medium">Monto</th>
                        <th class="px-4 py-3 text-left font-medium">Entregado por</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-if="anticiposFiltrados.length === 0">
                        <td colspan="7" class="text-muted-foreground px-4 py-10 text-center text-sm">
                            Sin anticipos registrados.
                        </td>
                    </tr>
                    <tr v-for="a in anticiposFiltrados" :key="a.id" class="transition-colors hover:bg-muted/50">
                        <td class="px-4 py-3 whitespace-nowrap tabular-nums">{{ fmtFecha(a.fecha) }}</td>
                        <td class="px-4 py-3 whitespace-nowrap font-medium">
                            {{ a.colaborador.apellidos }}, {{ a.colaborador.nombre }}
                        </td>
                        <td class="px-4 py-3">
                            <span
                                class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="tipoBadgeClass[a.colaborador.tipo]"
                            >
                                {{ tipoLabel[a.colaborador.tipo] }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span
                                class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="a.tipo === 'EVENTO' ? 'bg-violet-100 text-violet-800' : 'bg-slate-100 text-slate-700'"
                            >
                                {{ a.tipo === 'EVENTO' ? (a.evento?.nombre ?? 'Evento') : 'Suelto' }}
                            </span>
                        </td>
                        <td class="text-muted-foreground px-4 py-3 max-w-xs">
                            {{ a.concepto ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums font-medium">
                            ${{ parseFloat(a.monto).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
                        </td>
                        <td class="text-muted-foreground px-4 py-3">
                            {{ a.entregado_por ?? '—' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Cards móvil (< lg) -->
        <div class="flex flex-col gap-3 lg:hidden">
            <div v-if="anticiposFiltrados.length === 0" class="text-muted-foreground rounded-xl border border-dashed py-10 text-center text-sm">
                Sin anticipos registrados.
            </div>

            <div v-for="a in anticiposFiltrados" :key="a.id" class="rounded-xl border p-4">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="truncate font-medium">{{ a.colaborador.apellidos }}, {{ a.colaborador.nombre }}</p>
                        <p class="text-muted-foreground mt-0.5 text-xs tabular-nums">{{ fmtFecha(a.fecha) }}</p>
                    </div>
                    <div class="flex flex-shrink-0 flex-col items-end gap-1">
                        <span
                            class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium"
                            :class="tipoBadgeClass[a.colaborador.tipo]"
                        >
                            {{ tipoLabel[a.colaborador.tipo] }}
                        </span>
                        <span
                            class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium"
                            :class="a.tipo === 'EVENTO' ? 'bg-violet-100 text-violet-800' : 'bg-slate-100 text-slate-700'"
                        >
                            {{ a.tipo === 'EVENTO' ? (a.evento?.nombre ?? 'Evento') : 'Suelto' }}
                        </span>
                    </div>
                </div>

                <dl class="mt-3 space-y-1.5 text-sm">
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-muted-foreground text-xs">Concepto</dt>
                        <dd class="text-right">{{ a.concepto ?? '—' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-muted-foreground text-xs">Entregado por</dt>
                        <dd class="text-right">{{ a.entregado_por ?? '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between border-t pt-2">
                        <dt class="text-muted-foreground text-xs">Monto</dt>
                        <dd class="font-medium tabular-nums">
                            ${{ parseFloat(a.monto).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</template>