<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Plus, Save } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
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
import { fmtFecha } from '@/lib/fecha';
import * as viaticosRoutes from '@/routes/viaticos';

type TipoViatico = 'TRANSPORTE' | 'HOSPEDAJE' | 'ALIMENTOS' | 'CASETAS_GASOLINA' | 'OTRO';
type Modo = 'colaborador' | 'general';

const CONCEPTO_DIARIO = 'VIÁTICO DIARIO';

interface ColaboradorRef {
    id: number;
    nombre: string;
    apellidos: string;
    tipo: string;
}
interface EventoRef {
    id: number;
    nombre: string;
    fecha_inicio: string | null;
    fecha_fin: string | null;
    viatico_diario: string | null;
    colaboradores: ColaboradorRef[];
}
interface ColaboradorMini { id: number; nombre: string; apellidos: string }

interface Viatico {
    id: number;
    nombre: string | null;
    apellidos: string | null;
    tipo: TipoViatico;
    evento_id: number;
    colaborador_id: number | null;
    concepto: string;
    monto: string;
    fecha: string;
    autoriza: string | null;
    evento: { id: number; nombre: string };
    colaborador: ColaboradorMini | null;
}

interface MatrizCelda {
    checked: boolean;
    monto: string;
}

interface MatrizFila {
    colaborador_id: number;
    nombre: string;
    apellidos: string;
    dias: Record<string, MatrizCelda>;
}

const props = defineProps<{
    viaticos: Viatico[];
    eventos: EventoRef[];
}>();

const tipoOpciones: { value: TipoViatico; label: string }[] = [
    { value: 'TRANSPORTE',        label: 'Transporte' },
    { value: 'HOSPEDAJE',         label: 'Hospedaje' },
    { value: 'ALIMENTOS',         label: 'Alimentos' },
    { value: 'CASETAS_GASOLINA',  label: 'Casetas y Gasolina' },
    { value: 'OTRO',              label: 'Otro' },
];

const tipoBadgeClass: Record<TipoViatico, string> = {
    TRANSPORTE:       'bg-blue-100 text-blue-800',
    HOSPEDAJE:        'bg-purple-100 text-purple-800',
    ALIMENTOS:        'bg-emerald-100 text-emerald-800',
    CASETAS_GASOLINA: 'bg-orange-100 text-orange-800',
    OTRO:             'bg-slate-100 text-slate-700',
};

const tipoLabel = (t: TipoViatico) => tipoOpciones.find(o => o.value === t)?.label ?? t;

const nombreDisplay = (v: Viatico) => v.colaborador
    ? `${v.colaborador.apellidos}, ${v.colaborador.nombre}`
    : (v.apellidos ? `${v.apellidos}, ${v.nombre}` : (v.nombre ?? 'General'));

const today    = new Date().toISOString().split('T')[0];
const showForm = ref(false);
const modo     = ref<Modo>('general');

const form = useForm({
    nombre:         '',
    apellidos:      '',
    tipo:           'TRANSPORTE' as TipoViatico,
    evento_id:      '' as string,
    colaborador_id: '' as string,
    concepto:       '',
    monto:          '' as string,
    fecha:          today,
    autoriza:       '',
});

// Colaboradores asignados al evento elegido — solo relevantes en modo "Por colaborador".
const colaboradoresDelEvento = computed<ColaboradorRef[]>(() => {
    const evento = props.eventos.find(e => String(e.id) === form.evento_id);

    return evento?.colaboradores ?? [];
});

// Si cambia el evento o el modo, el colaborador elegido ya no es válido — se limpia.
watch([() => form.evento_id, modo], () => {
    form.colaborador_id = '';
});

const formInvalido = computed(() =>
    modo.value === 'colaborador' ? !form.colaborador_id : !form.nombre,
);

const submit = () => {
    form.post(viaticosRoutes.store.url(), {
        onSuccess: () => {
            showForm.value = false;
            form.reset();
            form.fecha = today;
            form.tipo = 'TRANSPORTE';
            modo.value = 'general';
        },
    });
};

// ── Matriz de viáticos por evento ───────────────────────────────────
const matrizEventoId = ref('');

const selEvento = computed(() => props.eventos.find(e => String(e.id) === matrizEventoId.value));

const formatDate = (d: Date) =>
    `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;

const diasDelEvento = computed<string[]>(() => {
    const e = selEvento.value;

    if (!e?.fecha_inicio || !e.fecha_fin) {
        return [];
    }

    // Laravel serializa las fechas como ISO con hora (p. ej. "2026-08-09T06:00:00.000000Z");
    // nos quedamos solo con la parte de día para construir el rango.
    const inicio = e.fecha_inicio.slice(0, 10);
    const fin = new Date(`${e.fecha_fin.slice(0, 10)}T00:00:00`);
    const dias: string[] = [];

    for (let cur = new Date(`${inicio}T00:00:00`); cur <= fin; cur.setDate(cur.getDate() + 1)) {
        dias.push(formatDate(cur));
    }

    return dias;
});

const etiquetaDia = (dia: string) =>
    new Date(`${dia}T00:00:00`).toLocaleDateString('es-MX', { day: '2-digit', month: 'short' });

const matrizFilas = ref<MatrizFila[]>([]);
const defDiario = ref('');

const construirMatriz = () => {
    const e = selEvento.value;

    if (!e) {
        matrizFilas.value = [];
        defDiario.value = '';

        return;
    }

    defDiario.value = e.viatico_diario ?? '';

    const viaticosEvento = props.viaticos.filter(v => v.evento_id === e.id && v.colaborador_id !== null);

    matrizFilas.value = e.colaboradores.map((c) => {
        const dias: Record<string, MatrizCelda> = {};

        for (const dia of diasDelEvento.value) {
            const v = viaticosEvento.find(x =>
                x.colaborador_id === c.id && x.concepto === CONCEPTO_DIARIO && String(x.fecha).slice(0, 10) === dia,
            );

            // Monto vacío = usar el gasto global del evento; solo se muestra el valor si difiere.
            const monto = v && defDiario.value && parseFloat(v.monto) === parseFloat(defDiario.value) ? '' : (v?.monto ?? '');

            dias[dia] = { checked: !!v, monto };
        }

        return {
            colaborador_id: c.id,
            nombre: c.nombre,
            apellidos: c.apellidos,
            dias,
        };
    });
};

watch(matrizEventoId, construirMatriz);

const guardarMatriz = () => {
    const filas = matrizFilas.value.map((f) => ({
        colaborador_id: f.colaborador_id,
        dias: Object.fromEntries(
            Object.entries(f.dias)
                .filter(([, celda]) => celda.checked)
                .map(([dia, celda]) => [dia, celda.monto]),
        ),
    }));

    router.post('/viaticos/matriz', {
        evento_id: matrizEventoId.value,
        def_diario: defDiario.value,
        filas,
    }, {
        preserveScroll: true,
        onSuccess: () => toast.success('Matriz de viáticos actualizada.'),
        onError: (errors) => toast.error(Object.values(errors).join(' ')),
    });
};
</script>

<template>
    <Head title="Viáticos" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4 sm:p-6">
        <!-- Header -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
            <div>
                <h1 class="text-2xl font-semibold">Viáticos</h1>
                <p class="text-muted-foreground mt-1 text-sm">
                    {{ props.viaticos.length }} registros · Panel de matriz por evento
                </p>
            </div>

            <Button @click="showForm = true">
                <Plus class="size-4" />
                Nuevo viático
            </Button>
        </div>

        <!-- Matriz de viáticos por evento -->
        <div class="flex flex-col gap-4 rounded-xl border p-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="w-full max-w-xs space-y-1">
                        <Label>Matriz por evento</Label>
                        <Select v-model="matrizEventoId">
                            <SelectTrigger>
                                <SelectValue placeholder="Seleccionar evento..." />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="e in eventos" :key="e.id" :value="String(e.id)">
                                    {{ e.nombre }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="w-full max-w-40 space-y-1">
                        <Label>Gasto por día</Label>
                        <Input
                            v-model="defDiario"
                            type="number" step="0.01" min="0"
                            class="tabular-nums"
                            placeholder="0.00"
                            :disabled="!matrizEventoId"
                        />
                    </div>
                </div>

                <Button
                    :disabled="!matrizEventoId || matrizFilas.length === 0"
                    @click="guardarMatriz"
                >
                    <Save class="size-4" />
                    Guardar matriz
                </Button>
            </div>

            <p v-if="selEvento && selEvento.colaboradores.length === 0" class="text-muted-foreground text-sm">
                Este evento no tiene colaboradores asignados. Asígnalos primero en Eventos.
            </p>

            <div v-if="matrizFilas.length" class="overflow-x-auto rounded-lg border">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 border-b">
                        <tr>
                            <th class="px-3 py-2 text-left font-medium whitespace-nowrap">Colaborador</th>
                            <th
                                v-for="dia in diasDelEvento"
                                :key="dia"
                                class="px-2 py-2 text-center font-medium whitespace-nowrap"
                                :title="fmtFecha(dia)"
                            >
                                {{ etiquetaDia(dia) }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="f in matrizFilas" :key="f.colaborador_id">
                            <td class="px-3 py-2 font-medium whitespace-nowrap">
                                {{ f.apellidos }}, {{ f.nombre }}
                            </td>
                            <td
                                v-for="dia in diasDelEvento"
                                :key="dia"
                                class="px-1.5 py-2 text-center"
                            >
                                <div class="flex flex-col items-center gap-1">
                                    <input
                                        v-model="f.dias[dia].checked"
                                        type="checkbox"
                                        class="size-4 rounded accent-primary"
                                    />
                                    <Input
                                        v-model="f.dias[dia].monto"
                                        type="number" step="0.01" min="0"
                                        :disabled="!f.dias[dia].checked"
                                        class="h-6 w-16 px-1 text-center text-xs tabular-nums"
                                        placeholder="—"
                                    />
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <p v-if="selEvento" class="text-muted-foreground text-xs">
                El gasto por día se define una sola vez (campo "Gasto por día") y aplica a todo el evento.
                Marca con un check los días en que cada colaborador recibió viático y, si ese día el monto fue
                diferente al global, escríbelo en el campo de la celda (vacío = monto del evento). Los viáticos
                adicionales (extras) se registran con "Nuevo viático".
            </p>
        </div>

        <Dialog :open="showForm" @update:open="showForm = $event">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>Registrar viático</DialogTitle>
                </DialogHeader>

                <form class="grid gap-4" @submit.prevent="submit">
                    <div class="space-y-1">
                        <Label>Evento <span class="text-destructive">*</span></Label>
                        <Select v-model="form.evento_id">
                            <SelectTrigger>
                                <SelectValue placeholder="Seleccionar evento..." />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="e in eventos" :key="e.id" :value="String(e.id)">
                                    {{ e.nombre }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.evento_id" class="text-destructive text-xs">{{ form.errors.evento_id }}</p>
                    </div>

                    <div class="space-y-1">
                        <Label>Registrar como <span class="text-destructive">*</span></Label>
                        <div class="flex gap-1 rounded-lg border p-1">
                            <button
                                type="button"
                                class="flex-1 rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                                :class="modo === 'colaborador' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:text-foreground'"
                                @click="modo = 'colaborador'"
                            >
                                Colaborador
                            </button>
                            <button
                                type="button"
                                class="flex-1 rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                                :class="modo === 'general' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:text-foreground'"
                                @click="modo = 'general'"
                            >
                                General
                            </button>
                        </div>
                    </div>

                    <template v-if="modo === 'colaborador'">
                        <div class="space-y-1">
                            <Label>Colaborador <span class="text-destructive">*</span></Label>
                            <Select v-model="form.colaborador_id" :disabled="!form.evento_id">
                                <SelectTrigger>
                                    <SelectValue :placeholder="form.evento_id ? 'Seleccionar colaborador...' : 'Elige primero un evento'" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="c in colaboradoresDelEvento" :key="c.id" :value="String(c.id)">
                                        {{ c.apellidos }}, {{ c.nombre }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.evento_id && colaboradoresDelEvento.length === 0" class="text-muted-foreground text-xs">
                                Este evento no tiene colaboradores asignados. Asígnalos primero en Eventos.
                            </p>
                            <p v-if="form.errors.colaborador_id" class="text-destructive text-xs">{{ form.errors.colaborador_id }}</p>
                        </div>
                    </template>

                    <template v-else>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <Label>Nombre <span class="text-destructive">*</span></Label>
                                <Input v-model="form.nombre" required />
                                <p v-if="form.errors.nombre" class="text-destructive text-xs">{{ form.errors.nombre }}</p>
                            </div>
                            <div class="space-y-1">
                                <Label>Apellidos</Label>
                                <Input v-model="form.apellidos" />
                            </div>
                        </div>
                    </template>

                    <div class="space-y-1">
                        <Label>Tipo <span class="text-destructive">*</span></Label>
                        <Select v-model="form.tipo">
                            <SelectTrigger><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="op in tipoOpciones" :key="op.value" :value="op.value">
                                    {{ op.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-1">
                        <Label>Concepto <span class="text-destructive">*</span></Label>
                        <Input v-model="form.concepto" placeholder="Descripción del gasto" required />
                        <p v-if="form.errors.concepto" class="text-destructive text-xs">{{ form.errors.concepto }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <Label>Monto <span class="text-destructive">*</span></Label>
                            <Input v-model="form.monto" type="number" step="0.01" min="0" required />
                            <p v-if="form.errors.monto" class="text-destructive text-xs">{{ form.errors.monto }}</p>
                        </div>
                        <div class="space-y-1">
                            <Label>Fecha <span class="text-destructive">*</span></Label>
                            <Input v-model="form.fecha" type="date" required />
                        </div>
                    </div>

                    <div class="space-y-1">
                        <Label>Autoriza</Label>
                        <Input v-model="form.autoriza" placeholder="Nombre de quien autoriza" />
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" @click="showForm = false">Cancelar</Button>
                        <Button type="submit" :disabled="form.processing || formInvalido || !form.evento_id">Registrar</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Tabla (solo lectura) -->
        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Fecha</th>
                        <th class="px-4 py-3 text-left font-medium">Nombre</th>
                        <th class="px-4 py-3 text-left font-medium">Tipo</th>
                        <th class="px-4 py-3 text-left font-medium">Evento</th>
                        <th class="px-4 py-3 text-left font-medium">Concepto</th>
                        <th class="px-4 py-3 text-right font-medium">Monto</th>
                        <th class="px-4 py-3 text-left font-medium">Autoriza</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-if="props.viaticos.length === 0">
                        <td colspan="7" class="text-muted-foreground px-4 py-10 text-center text-sm">
                            Sin viáticos registrados.
                        </td>
                    </tr>
                    <tr v-for="v in props.viaticos" :key="v.id" class="hover:bg-muted/30">
                        <td class="px-4 py-3 whitespace-nowrap tabular-nums">{{ fmtFecha(v.fecha) }}</td>
                        <td class="px-4 py-3 whitespace-nowrap font-medium">
                            {{ nombreDisplay(v) }}
                            <span v-if="!v.colaborador" class="text-muted-foreground ml-1 text-xs font-normal">(General)</span>
                        </td>
                        <td class="px-4 py-3">
                            <span
                                class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="tipoBadgeClass[v.tipo]"
                            >
                                {{ tipoLabel(v.tipo) }}
                            </span>
                        </td>
                        <td class="text-muted-foreground px-4 py-3 whitespace-nowrap">
                            {{ v.evento.nombre }}
                        </td>
                        <td class="text-muted-foreground px-4 py-3 max-w-xs">{{ v.concepto }}</td>
                        <td class="px-4 py-3 text-right tabular-nums font-medium">
                            ${{ parseFloat(v.monto).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
                        </td>
                        <td class="text-muted-foreground px-4 py-3">{{ v.autoriza ?? '—' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>