<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { CalendarDays, Camera, Lock, Pencil, Plus, RefreshCw, Trash2 } from '@lucide/vue';
import { ref, computed, nextTick, watch } from 'vue';
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
import * as jornadas from '@/routes/jornadas';
import * as registroAsistencia from '@/routes/registro-asistencia';

type TipoActividad = 'Bodega' | 'Evento' | 'Transporte';
type TipoColaborador = 'COLABORADOR BASE' | 'FREELANCE' | 'CONDUCTOR' | 'CONDUCTOR BASE';

interface ColaboradorRef {
    id: number;
    nombre: string;
    apellidos: string;
    tipo: TipoColaborador;
    eventos: EventoRef[];
}

interface EventoRef {
    id: number;
    nombre: string;
}

interface VehiculoRef {
    id: number;
    nombre: string;
}

interface DistanciaRef {
    id: number;
    nombre: string;
    es_standby: boolean;
}

interface UnidadRef {
    id: number;
    marca: string;
    modelo: string;
    numero_placas: string | null;
    transporte_vehiculo_id: number | null;
}

interface Registro {
    id: number;
    colaborador_id: number;
    tipo_actividad: TipoActividad;
    actividad: string | null;
    evento_raw: string | null;
    etapa: string | null;
    vehiculo: string | null;
    distancia: string | null;
    transporte_unidad_id: number | null;
    origen: string | null;
    destino: string | null;
    extras: string | null;
    evidencia_path: string | null;
    evidencia_url: string | null;
    comentarios: string | null;
    fecha: string;
    hora: string;
    hora_salida: string | null;
    jornada_validada: boolean;
    en_nomina: boolean;
    colaborador: ColaboradorRef;
    unidad: UnidadRef | null;
}

const props = defineProps<{
    registros: Registro[];
    colaboradores: ColaboradorRef[];
    eventos: EventoRef[];
    vehiculos: VehiculoRef[];
    distancias: DistanciaRef[];
    unidades: UnidadRef[];
}>();

// Unidades de la flotilla que pertenecen a la categoría de vehículo elegida (por nombre, ya
// que el Select de vehículo guarda el nombre de la categoría, no su id).
const unidadesDelVehiculo = (nombreVehiculo: string): UnidadRef[] => {
    const vehiculoId = props.vehiculos.find(v => v.nombre === nombreVehiculo)?.id;

    if (!vehiculoId) {
return [];
}

    return props.unidades.filter(u => u.transporte_vehiculo_id === vehiculoId);
};

const unidadLabel = (u: UnidadRef): string =>
    `${u.marca} ${u.modelo}` + (u.numero_placas ? ` — ${u.numero_placas}` : '');

// ---------- listas de opciones ----------
const BODEGA_ACTIVIDADES = [
    'Stagehand / Apoyo general',
    'Carga / Descarga',
    'Mantenimiento',
    'Inventario',
    'Acomodo',
    'Limpieza',
    'Otro',
] as const;

const BODEGA_EXTRAS = [
    'Chofer / Manejo',
    'Carga pesada / Maniobras',
    'Responsable del transporte',
    'Inventario / Conteo crítico',
    'Mantenimiento especializado',
    'Trabajo nocturno',
    'Apoyo técnico (Cableado/Reparación)',
    'Otro',
] as const;

const EVENTO_EXTRAS = [
    'AUDIO | FOH',
    'AUDIO | Monitores',
    'AUDIO | Patch / Stage patch',
    'AUDIO | RF / Inalámbricos',
    'LUCES | Operador',
    'VIDEO | LED / Switcher',
    'VIDEO | Cámara',
    'VIDEO | Streaming / Grabación',
    'ENERGÍA | Operar planta de luz',
    'ENERGÍA | Distribución eléctrica',
    'ENERGÍA | Guardia energía durante el show',
    'LOGISTICA | Chofer / Manejo',
    'LOGISTICA | Responsable unidad',
    'LOGISTICA | Carga pesada / maniobras',
    'LOGISTICA | Cuidar Delay / Zonas',
    'ESP | Rigging / Motores',
    'ESP | Armado truss / estructuras',
    'ESP | Stage Manager',
    'ESP | Intercom / Comunicación',
    'ESP | Programación consola / showfile',
    'COND | Pernocta',
    'COND | Día Adicional (Multi-día)',
    'COND | Nocturno',
    'BONO | Actitud / Desempeño (requiere aprobación)',
    'Otro',
] as const;

// ---------- helpers ----------
const today = new Date().toISOString().split('T')[0];
const nowTime = new Date().toTimeString().slice(0, 5);

const actividadLabel = (r: Registro): string => {
    if (r.tipo_actividad === 'Bodega') {
return r.actividad ?? '—';
}

    if (r.tipo_actividad === 'Evento') {
return `${r.evento_raw ?? '—'} (${r.etapa ?? '—'})`;
}

    if (r.tipo_actividad === 'Transporte') {
        const unidad = r.unidad ? ` (${unidadLabel(r.unidad)})` : '';

        return `${r.vehiculo ?? '—'} · ${r.distancia ?? '—'}${unidad}`;
    }

    return '—';
};

const tipoBadgeClass: Record<TipoActividad, string> = {
    Bodega: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
    Evento: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300',
    Transporte: 'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300',
};

const toggleExtra = (arr: string[], val: string) => {
    const i = arr.indexOf(val);

    if (i === -1) {
arr.push(val);
} else {
arr.splice(i, 1);
}
};

const ETAPAS = ['Montaje', 'Show', 'Desmontaje'] as const;

// ---------- CREAR registro ----------
const showAdd = ref(false);
const addExtras = ref<string[]>([]);
const addEtapas = ref<string[]>([]);

const addForm = useForm({
    colaborador_id: '' as string,
    tipo_actividad: 'Bodega' as TipoActividad,
    actividad: '',
    evento_raw: '',
    etapa: '',
    vehiculo: '',
    distancia: '',
    transporte_unidad_id: '' as string,
    origen: '',
    destino: '',
    extras: '',
    evidencia: null as File | null,
    comentarios: '',
    fecha: today,
    hora: nowTime,
    hora_salida: '',
});

watch(() => addForm.tipo_actividad, () => {
    addExtras.value = [];
    addEtapas.value = [];
});
watch(() => addForm.vehiculo, () => {
    addForm.transporte_unidad_id = '';
});

const addIntentado = ref(false);

const erroresAdd = computed<Record<string, string>>(() => {
    const e: Record<string, string> = {};

    if (!addForm.colaborador_id) {
        e.colaborador_id = 'Selecciona un colaborador.';
    }

    if (!addForm.fecha) {
        e.fecha = 'La fecha es obligatoria.';
    }

    if (!addForm.hora) {
        e.hora = 'La hora de entrada es obligatoria.';
    }

    if (addForm.tipo_actividad === 'Transporte' && !addForm.transporte_unidad_id) {
        e.transporte_unidad_id = 'Selecciona la unidad de transporte.';
    }

    return e;
});

const msgAdd = (campo: string): string => {
    const cliente = erroresAdd.value[campo];

    if (addIntentado.value && cliente) {
        return cliente;
    }

    return (addForm.errors as Record<string, string>)[campo] ?? '';
};

watch(() => showAdd, (open) => {
    if (open) {
        addIntentado.value = false;
    }
});

const submitAdd = () => {
    addForm.extras = addExtras.value.join(', ');
    addForm.etapa  = addEtapas.value.join(', ');
    addIntentado.value = true;

    if (Object.keys(erroresAdd.value).length > 0) {
        return;
    }

    addForm.post(registroAsistencia.store.url(), {
        forceFormData: true,
        onSuccess: () => {
            showAdd.value = false;
            addIntentado.value = false;
            addForm.reset();
            addForm.fecha = today;
            addForm.hora = nowTime;
            addExtras.value = [];
            addEtapas.value = [];
        },
    });
};

const MAX_EVIDENCIA_MB = 5;
const evidenciaError = ref('');

const onEvidenciaChange = (e: Event) => {
    const input = e.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;

    if (file) {
        if (!file.type.startsWith('image/')) {
            evidenciaError.value = 'La evidencia debe ser una imagen (PNG o JPG).';
            input.value = '';

            return;
        }

        if (file.size > MAX_EVIDENCIA_MB * 1024 * 1024) {
            evidenciaError.value = `El archivo excede los ${MAX_EVIDENCIA_MB} MB permitidos.`;
            input.value = '';

            return;
        }
    }

    evidenciaError.value = '';
    addForm.evidencia = file;
};

// ---------- EDITAR registro ----------
const editando = ref<Registro | null>(null);
const editExtras = ref<string[]>([]);
const editEtapas = ref<string[]>([]);

const editForm = useForm({
    tipo_actividad: 'Bodega' as TipoActividad,
    actividad: '',
    evento_raw: '',
    etapa: '',
    vehiculo: '',
    distancia: '',
    transporte_unidad_id: '' as string,
    origen: '',
    destino: '',
    extras: '',
    comentarios: '',
    fecha: today,
    hora: nowTime,
    hora_salida: '',
});

watch(() => editForm.tipo_actividad, () => {
 editExtras.value = []; editEtapas.value = []; 
});

// true mientras abrirEdicion() precarga el formulario, para que el watch de abajo no borre la
// unidad recién cargada (solo debe limpiarla cuando el usuario cambia el vehículo a mano).
const cargandoEdicion = ref(false);
watch(() => editForm.vehiculo, () => {
    if (!cargandoEdicion.value) {
editForm.transporte_unidad_id = '';
}
});

const abrirEdicion = (r: Registro) => {
    cargandoEdicion.value = true;
    editando.value = r;
    editForm.tipo_actividad = r.tipo_actividad;
    editForm.actividad = r.actividad ?? '';
    editForm.evento_raw = r.evento_raw ?? '';
    editForm.etapa = r.etapa ?? '';
    editEtapas.value = (r.etapa ?? '').split(', ').filter(Boolean);
    editForm.vehiculo = r.vehiculo ?? '';
    editForm.distancia = r.distancia ?? '';
    editForm.transporte_unidad_id = r.transporte_unidad_id ? String(r.transporte_unidad_id) : '';
    editForm.origen = r.origen ?? '';
    editForm.destino = r.destino ?? '';
    editForm.extras = r.extras ?? '';
    editForm.comentarios = r.comentarios ?? '';
    editForm.fecha = r.fecha;
    editForm.hora = r.hora.slice(0, 5);
    editForm.hora_salida = r.hora_salida?.slice(0, 5) ?? '';
    editExtras.value = (r.extras ?? '').split(',').map(s => s.trim()).filter(Boolean);
    nextTick(() => {
 cargandoEdicion.value = false; 
});
};

const submitEdit = () => {
    if (!editando.value) {
return;
}

    editForm.extras = editExtras.value.join(', ');
    editForm.etapa  = editEtapas.value.join(', ');
    editForm.put(registroAsistencia.update.url(editando.value.id), {
        preserveScroll: true,
        onSuccess: () => {
 editando.value = null; 
},
    });
};

// ---------- ELIMINAR ----------
const { confirm } = useConfirm();
const eliminandoId = ref<number | null>(null);

const eliminar = async (r: Registro) => {
    const ok = await confirm(`¿Eliminar el registro de ${r.colaborador.nombre} ${r.colaborador.apellidos} del ${r.fecha}?`, {
        title: 'Eliminar registro',
    });

    if (!ok) {
return;
}

    eliminandoId.value = r.id;
    router.delete(registroAsistencia.destroy.url(r.id), {
        preserveScroll: true,
        onFinish: () => {
 eliminandoId.value = null; 
},
    });
};

// ---------- REGENERAR JORNADAS ----------
const generando = ref(false);
const regenerar = () => {
    generando.value = true;
    router.post(jornadas.generar.url(), {}, {
        preserveScroll: true,
        onFinish: () => {
 generando.value = false; 
},
    });
};

// ---------- filtros ----------
const filtroTipo = ref<TipoActividad | ''>('');
const filtroColaborador = ref('');

const registrosFiltrados = computed(() => {
    return props.registros.filter(r => {
        if (filtroTipo.value && r.tipo_actividad !== filtroTipo.value) {
return false;
}

        if (filtroColaborador.value) {
            const texto = `${r.colaborador.nombre} ${r.colaborador.apellidos}`.toLowerCase();

            if (!texto.includes(filtroColaborador.value.toLowerCase())) {
return false;
}
        }

        return true;
    });
});

const eventosParaAgregar = computed(() => {
    if (!addForm.colaborador_id) {
return props.eventos;
}

    const colab = props.colaboradores.find(c => String(c.id) === addForm.colaborador_id);

    if (!colab || colab.tipo === 'CONDUCTOR') {
return props.eventos;
}

    return colab.eventos;
});

const eventosParaEditar = computed(() => {
    if (!editando.value) {
return props.eventos;
}

    const colab = props.colaboradores.find(c => c.id === editando.value!.colaborador_id);

    if (!colab || colab.tipo === 'CONDUCTOR') {
return props.eventos;
}

    return colab.eventos;
});
</script>

<template>
    <Head title="Registro de Asistencia" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4 sm:p-6">
        <!-- Header -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
            <div>
                <h1 class="text-2xl font-semibold">Registro de Asistencia</h1>
                <p class="text-muted-foreground mt-1 text-sm">
                    {{ registros.length }} registros en total
                </p>
            </div>

            <div class="flex gap-2">
                <Button variant="outline" :disabled="generando" @click="regenerar">
                    <RefreshCw class="size-4" :class="generando ? 'animate-spin' : ''" />
                    Regenerar jornadas
                </Button>

                <Dialog v-model:open="showAdd">
                    <DialogTrigger as-child>
                        <Button>
                            <Plus class="size-4" />
                            Nuevo registro
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-lg max-h-[90vh] overflow-y-auto">
                        <DialogHeader>
                            <DialogTitle>Nuevo registro de asistencia</DialogTitle>
                        </DialogHeader>

                        <form class="grid gap-4" @submit.prevent="submitAdd">
                            <!-- Colaborador -->
                            <div class="space-y-1">
                                <Label>Colaborador <span class="text-destructive">*</span></Label>
                                <Select v-model="addForm.colaborador_id" required>
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
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="msgAdd('colaborador_id')" />
                            </div>

                            <!-- Fecha -->
                            <div class="space-y-1">
                                <Label>Fecha <span class="text-destructive">*</span></Label>
                                <Input v-model="addForm.fecha" type="date" required />
                                <InputError :message="msgAdd('fecha')" />
                            </div>

                            <!-- Hora entrada / salida -->
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div class="space-y-1">
                                    <Label>Hora de entrada <span class="text-destructive">*</span></Label>
                                    <Input v-model="addForm.hora" type="time" required />
                                    <InputError :message="msgAdd('hora')" />
                                </div>
                                <div class="space-y-1">
                                    <Label>Hora de salida</Label>
                                    <Input v-model="addForm.hora_salida" type="time" />
                                </div>
                            </div>

                            <!-- Tipo de actividad -->
                            <div class="space-y-1">
                                <Label>Tipo de actividad <span class="text-destructive">*</span></Label>
                                <Select v-model="addForm.tipo_actividad">
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="Bodega">Bodega</SelectItem>
                                        <SelectItem value="Evento">Evento</SelectItem>
                                        <SelectItem value="Transporte">Transporte</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Campos condicionales -->
                            <template v-if="addForm.tipo_actividad === 'Bodega'">
                                <div class="space-y-3 rounded-lg border bg-muted/20 p-2 sm:p-3">
                                    <div class="space-y-1">
                                        <Label>Actividad</Label>
                                        <Select v-model="addForm.actividad">
                                            <SelectTrigger>
                                                <SelectValue placeholder="Seleccionar actividad..." />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="act in BODEGA_ACTIVIDADES" :key="act" :value="act">{{ act }}</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p v-if="addForm.errors.actividad" class="text-destructive text-xs">{{ addForm.errors.actividad }}</p>
                                    </div>
                                    <div class="space-y-2">
                                        <Label>Extras</Label>
                                        <div class="grid grid-cols-1 gap-x-4 gap-y-2.5 rounded-md border bg-background p-2 sm:p-3 sm:grid-cols-2">
                                            <label
                                                v-for="opt in BODEGA_EXTRAS"
                                                :key="opt"
                                                class="flex cursor-pointer items-center gap-2 text-sm leading-tight"
                                            >
                                                <Checkbox
                                                    :model-value="addExtras.includes(opt)"
                                                    @update:model-value="toggleExtra(addExtras, opt)"
                                                />
                                                {{ opt }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <template v-else-if="addForm.tipo_actividad === 'Evento'">
                                <div class="space-y-3 rounded-lg border bg-muted/20 p-2 sm:p-3">
                                    <div class="space-y-1">
                                        <Label>Nombre del evento</Label>
                                        <Select v-model="addForm.evento_raw">
                                            <SelectTrigger>
                                                <SelectValue placeholder="Seleccionar evento..." />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="ev in eventosParaAgregar" :key="ev.id" :value="ev.nombre">{{ ev.nombre }}</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p v-if="addForm.errors.evento_raw" class="text-destructive text-xs">{{ addForm.errors.evento_raw }}</p>
                                    </div>
                                    <div class="space-y-2">
                                        <Label>Etapa(s)</Label>
                                        <div class="flex flex-wrap gap-2 rounded-md border bg-background p-2 sm:gap-4 sm:p-3">
                                            <label
                                                v-for="etapa in ETAPAS"
                                                :key="etapa"
                                                class="flex cursor-pointer items-center gap-2 text-sm"
                                            >
                                                <Checkbox
                                                    :model-value="addEtapas.includes(etapa)"
                                                    @update:model-value="toggleExtra(addEtapas, etapa)"
                                                />
                                                {{ etapa }}
                                            </label>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <Label>Extras / Funciones</Label>
                                        <div class="grid grid-cols-1 gap-x-4 gap-y-2.5 rounded-md border bg-background p-2 sm:p-3 sm:grid-cols-2">
                                            <label
                                                v-for="opt in EVENTO_EXTRAS"
                                                :key="opt"
                                                class="flex cursor-pointer items-center gap-2 text-sm leading-tight"
                                        >
                                            <Checkbox
                                                :model-value="addExtras.includes(opt)"
                                                @update:model-value="toggleExtra(addExtras, opt)"
                                            />
                                            {{ opt }}
                                        </label>
                                    </div>
                                </div>
                                </div>
                            </template>

                            <template v-else-if="addForm.tipo_actividad === 'Transporte'">
                                <div class="space-y-3 rounded-lg border bg-muted/20 p-2 sm:p-3">
                                    <div class="space-y-1">
                                        <Label>Vehículo</Label>
                                        <Select v-model="addForm.vehiculo">
                                            <SelectTrigger>
                                                <SelectValue placeholder="Seleccionar vehículo..." />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="v in vehiculos" :key="v.id" :value="v.nombre">{{ v.nombre }}</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p v-if="addForm.errors.vehiculo" class="text-destructive text-xs">{{ addForm.errors.vehiculo }}</p>
                                    </div>
                                    <div class="space-y-1">
                                        <Label>Distancia / Ruta</Label>
                                        <Select v-model="addForm.distancia">
                                            <SelectTrigger>
                                                <SelectValue placeholder="Seleccionar distancia..." />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="d in distancias" :key="d.id" :value="d.nombre">
                                                    {{ d.nombre }}<template v-if="d.es_standby"> · Standby</template>
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p v-if="addForm.errors.distancia" class="text-destructive text-xs">{{ addForm.errors.distancia }}</p>
                                    </div>
                                    <div class="space-y-1">
                                        <Label>Unidad <span class="text-destructive">*</span></Label>
                                        <Select v-model="addForm.transporte_unidad_id" :disabled="!addForm.vehiculo">
                                            <SelectTrigger>
                                                <SelectValue :placeholder="addForm.vehiculo ? 'Seleccionar unidad...' : 'Elige primero un vehículo'" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="u in unidadesDelVehiculo(addForm.vehiculo)" :key="u.id" :value="String(u.id)">
                                                    {{ unidadLabel(u) }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p v-if="addForm.vehiculo && unidadesDelVehiculo(addForm.vehiculo).length === 0" class="text-muted-foreground text-xs">
                                            Sin unidades registradas de este tipo en Transportes.
                                        </p>
                                        <InputError :message="msgAdd('transporte_unidad_id')" />
                                    </div>
                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        <div class="space-y-1">
                                            <Label>Origen</Label>
                                            <Input v-model="addForm.origen" placeholder="Ciudad/lugar de salida" />
                                        </div>
                                        <div class="space-y-1">
                                            <Label>Destino</Label>
                                            <Input v-model="addForm.destino" placeholder="Ciudad/lugar de llegada" />
                                        </div>
                                    </div>
                                    <div class="space-y-1">
                                        <Label>Extras</Label>
                                        <Input v-model="addForm.extras" placeholder="Ej. Tiempo extra, viático..." />
                                    </div>
                                </div>
                            </template>

                            <!-- Evidencia -->
                            <div class="space-y-1.5">
                                <Label>Evidencia fotográfica</Label>
                                <div class="flex flex-col items-stretch gap-3 rounded-lg border border-dashed p-3 sm:flex-row sm:items-center">
                                    <div class="flex items-center gap-3">
                                        <div class="text-muted-foreground flex size-10 shrink-0 items-center justify-center rounded-md border bg-background">
                                            <Camera class="size-5" />
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium">Subir foto</p>
                                            <p class="text-muted-foreground text-xs">PNG, JPG hasta 5 MB. Puedes tomar una foto directamente con la cámara.</p>
                                        </div>
                                    </div>
                                    <input
                                        type="file"
                                        accept="image/*"
                                        capture="environment"
                                        class="w-full text-sm file:mr-2 file:rounded-md file:border-0 file:bg-muted file:px-3 file:py-1 file:text-sm file:font-medium cursor-pointer sm:w-auto"
                                        @change="onEvidenciaChange"
                                    />
                                </div>
                                <p v-if="evidenciaError" class="text-destructive flex items-center gap-1 text-xs" role="alert">
                                    {{ evidenciaError }}
                                </p>
                                <InputError :message="addForm.errors.evidencia" />
                            </div>

                            <!-- Comentarios -->
                            <div class="space-y-1">
                                <Label>Comentarios</Label>
                                <Input v-model="addForm.comentarios" placeholder="Observaciones..." />
                            </div>

                            <DialogFooter>
                                <Button type="button" variant="outline" @click="showAdd = false">Cancelar</Button>
                                <Button type="submit" :disabled="addForm.processing" class="gap-1.5">
                                    <Spinner v-if="addForm.processing" class="size-4" />
                                    {{ addForm.processing ? 'Guardando…' : 'Guardar' }}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </div>

        <!-- Filtros -->
        <div class="flex flex-wrap gap-3">
            <Input
                v-model="filtroColaborador"
                placeholder="Buscar colaborador..."
                class="max-w-56"
            />
            <Select v-model="filtroTipo">
                <SelectTrigger class="w-44">
                    <SelectValue placeholder="Todos los tipos" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="">Todos</SelectItem>
                    <SelectItem value="Bodega">Bodega</SelectItem>
                    <SelectItem value="Evento">Evento</SelectItem>
                    <SelectItem value="Transporte">Transporte</SelectItem>
                </SelectContent>
            </Select>
            <p class="text-muted-foreground self-center text-sm">
                {{ registrosFiltrados.length }} de {{ registros.length }}
            </p>
        </div>

        <!-- Tabla escritorio (≥ lg) -->
        <div class="hidden overflow-x-auto rounded-xl border lg:block">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Fecha</th>
                        <th class="px-4 py-3 text-left font-medium">Entrada / Salida</th>
                        <th class="px-4 py-3 text-left font-medium">Colaborador</th>
                        <th class="px-4 py-3 text-left font-medium">Tipo</th>
                        <th class="px-4 py-3 text-left font-medium">Descripción</th>
                        <th class="px-4 py-3 text-left font-medium">Evidencia</th>
                        <th class="px-4 py-3 text-right font-medium">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-if="registrosFiltrados.length === 0">
                        <td colspan="7" class="text-muted-foreground px-4 py-10 text-center text-sm">
                            Sin registros. Usa "+ Nuevo registro" para capturar asistencia.
                        </td>
                    </tr>
                    <tr v-for="r in registrosFiltrados" :key="r.id" class="hover:bg-muted/30">
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="inline-flex items-center gap-1.5">
                                <CalendarDays class="text-muted-foreground size-3.5" />
                                {{ fmtFecha(r.fecha) }}
                            </span>
                        </td>
                        <td class="text-muted-foreground px-4 py-3 whitespace-nowrap text-xs tabular-nums">
                            {{ r.hora.slice(0, 5) }}
                            <template v-if="r.hora_salida"> → {{ r.hora_salida.slice(0, 5) }}</template>
                        </td>
                        <td class="px-4 py-3 font-medium whitespace-nowrap">
                            {{ r.colaborador.apellidos }}, {{ r.colaborador.nombre }}
                        </td>
                        <td class="px-4 py-3">
                            <span
                                class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="tipoBadgeClass[r.tipo_actividad]"
                            >
                                {{ r.tipo_actividad }}
                            </span>
                        </td>
                        <td class="px-4 py-3 max-w-xs">
                            <span class="text-sm">{{ actividadLabel(r) }}</span>
                            <span v-if="r.extras" class="text-muted-foreground block text-xs">
                                {{ r.extras }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <a
                                v-if="r.evidencia_url"
                                :href="r.evidencia_url"
                                target="_blank"
                                rel="noopener"
                            >
                                <img
                                    :src="r.evidencia_url"
                                    alt="Evidencia"
                                    class="size-10 rounded object-cover ring-1 ring-border hover:ring-primary transition-all"
                                />
                            </a>
                            <span v-else class="text-muted-foreground text-xs">—</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-1">
                                <Button size="sm" variant="outline" @click="abrirEdicion(r)">
                                    <Pencil class="size-3.5" />
                                </Button>
                                <Button
                                    v-if="r.jornada_validada || r.en_nomina"
                                    size="sm"
                                    variant="ghost"
                                    class="text-muted-foreground cursor-not-allowed"
                                    :title="r.jornada_validada ? 'No se puede eliminar: la jornada ya fue validada' : 'No se puede eliminar: la jornada ya forma parte de una nómina guardada'"
                                    disabled
                                >
                                    <Lock class="size-3.5" />
                                </Button>
                                <Button
                                    v-else
                                    size="sm"
                                    variant="ghost"
                                    class="text-destructive"
                                    :disabled="eliminandoId === r.id"
                                    @click="eliminar(r)"
                                >
                                    <Spinner v-if="eliminandoId === r.id" class="size-3.5" />
                                    <Trash2 v-else class="size-3.5" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Cards móvil (< lg) -->
        <div class="flex flex-col gap-3 lg:hidden">
            <div v-if="registrosFiltrados.length === 0" class="text-muted-foreground rounded-xl border border-dashed py-10 text-center text-sm">
                Sin registros. Usa "+ Nuevo registro" para capturar asistencia.
            </div>

            <div v-for="r in registrosFiltrados" :key="r.id" class="rounded-xl border p-4">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="truncate font-medium">{{ r.colaborador.apellidos }}, {{ r.colaborador.nombre }}</p>
                        <p class="text-muted-foreground mt-0.5 flex items-center gap-1.5 text-xs">
                            <CalendarDays class="size-3.5" />
                            {{ fmtFecha(r.fecha) }}
                            <span class="tabular-nums">
                                {{ r.hora.slice(0, 5) }}
                                <template v-if="r.hora_salida"> → {{ r.hora_salida.slice(0, 5) }}</template>
                            </span>
                        </p>
                    </div>
                    <div class="flex flex-shrink-0 items-center gap-2">
                        <a
                            v-if="r.evidencia_url"
                            :href="r.evidencia_url"
                            target="_blank"
                            rel="noopener"
                        >
                            <img
                                :src="r.evidencia_url"
                                alt="Evidencia"
                                class="size-10 rounded object-cover ring-1 ring-border hover:ring-primary transition-all"
                            />
                        </a>
                        <span
                            class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium"
                            :class="tipoBadgeClass[r.tipo_actividad]"
                        >
                            {{ r.tipo_actividad }}
                        </span>
                    </div>
                </div>

                <p class="mt-2 text-sm">{{ actividadLabel(r) }}</p>
                <p v-if="r.extras" class="text-muted-foreground mt-0.5 text-xs">{{ r.extras }}</p>

                <div class="mt-3 flex justify-end gap-1">
                    <Button size="sm" variant="outline" @click="abrirEdicion(r)">
                        <Pencil class="size-3.5" />
                    </Button>
                    <Button
                        v-if="r.jornada_validada || r.en_nomina"
                        size="sm"
                        variant="ghost"
                        class="text-muted-foreground cursor-not-allowed"
                        :title="r.jornada_validada ? 'No se puede eliminar: la jornada ya fue validada' : 'No se puede eliminar: la jornada ya forma parte de una nómina guardada'"
                        disabled
                    >
                        <Lock class="size-3.5" />
                    </Button>
                    <Button
                        v-else
                        size="sm"
                        variant="ghost"
                        class="text-destructive"
                        :disabled="eliminandoId === r.id"
                        @click="eliminar(r)"
                    >
                        <Spinner v-if="eliminandoId === r.id" class="size-3.5" />
                        <Trash2 v-else class="size-3.5" />
                    </Button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de edición / corrección -->
    <Dialog :open="!!editando" @update:open="(v) => { if (!v) editando = null }">
        <DialogContent class="sm:max-w-md max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>Corregir registro</DialogTitle>
            </DialogHeader>

            <form v-if="editando" class="grid gap-4" @submit.prevent="submitEdit">
                <!-- Fecha -->
                <div class="space-y-1">
                    <Label>Fecha</Label>
                    <Input v-model="editForm.fecha" type="date" required />
                </div>

                <!-- Hora entrada / salida -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <Label>Hora de entrada</Label>
                        <Input v-model="editForm.hora" type="time" required />
                    </div>
                    <div class="space-y-1">
                        <Label>Hora de salida</Label>
                        <Input v-model="editForm.hora_salida" type="time" />
                    </div>
                </div>

                <!-- Tipo de actividad -->
                <div class="space-y-1">
                    <Label>Tipo de actividad</Label>
                    <Select v-model="editForm.tipo_actividad">
                        <SelectTrigger><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="Bodega">Bodega</SelectItem>
                            <SelectItem value="Evento">Evento</SelectItem>
                            <SelectItem value="Transporte">Transporte</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <template v-if="editForm.tipo_actividad === 'Bodega'">
                    <div class="space-y-3 rounded-lg border bg-muted/20 p-2 sm:p-3">
                        <div class="space-y-1">
                            <Label>Actividad</Label>
                            <Select v-model="editForm.actividad">
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccionar actividad..." />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="act in BODEGA_ACTIVIDADES" :key="act" :value="act">{{ act }}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-2">
                            <Label>Extras</Label>
                            <div class="grid grid-cols-1 gap-x-4 gap-y-2.5 rounded-md border bg-background p-2 sm:grid-cols-2 sm:p-3">
                                <label
                                    v-for="opt in BODEGA_EXTRAS"
                                    :key="opt"
                                    class="flex cursor-pointer items-center gap-2 text-sm leading-tight"
                                >
                                    <Checkbox
                                        :model-value="editExtras.includes(opt)"
                                        @update:model-value="toggleExtra(editExtras, opt)"
                                    />
                                    {{ opt }}
                                </label>
                            </div>
                        </div>
                    </div>
                </template>

                <template v-else-if="editForm.tipo_actividad === 'Evento'">
                    <div class="space-y-3 rounded-lg border bg-muted/20 p-2 sm:p-3">
                        <div class="space-y-1">
                            <Label>Nombre del evento</Label>
                            <Select v-model="editForm.evento_raw">
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccionar evento..." />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="ev in eventosParaEditar" :key="ev.id" :value="ev.nombre">{{ ev.nombre }}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-2">
                            <Label>Etapa(s)</Label>
                            <div class="flex flex-wrap gap-2 rounded-md border bg-background p-2 sm:gap-4 sm:p-3">
                                <label
                                    v-for="etapa in ETAPAS"
                                    :key="etapa"
                                    class="flex cursor-pointer items-center gap-2 text-sm"
                                >
                                    <Checkbox
                                        :model-value="editEtapas.includes(etapa)"
                                        @update:model-value="toggleExtra(editEtapas, etapa)"
                                    />
                                    {{ etapa }}
                                </label>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <Label>Extras / Funciones</Label>
                            <div class="grid grid-cols-1 gap-x-4 gap-y-2.5 rounded-md border bg-background p-2 sm:grid-cols-2 sm:p-3">
                                <label
                                    v-for="opt in EVENTO_EXTRAS"
                                    :key="opt"
                                    class="flex cursor-pointer items-center gap-2 text-sm leading-tight"
                                >
                                    <Checkbox
                                        :model-value="editExtras.includes(opt)"
                                        @update:model-value="toggleExtra(editExtras, opt)"
                                    />
                                    {{ opt }}
                                </label>
                            </div>
                        </div>
                    </div>
                </template>

                <template v-else-if="editForm.tipo_actividad === 'Transporte'">
                    <div class="space-y-3 rounded-lg border bg-muted/20 p-2 sm:p-3">
                        <div class="space-y-1">
                            <Label>Vehículo</Label>
                            <Select v-model="editForm.vehiculo">
                                <SelectTrigger><SelectValue placeholder="Seleccionar vehículo..." /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="v in vehiculos" :key="v.id" :value="v.nombre">{{ v.nombre }}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-1">
                            <Label>Distancia / Ruta</Label>
                            <Select v-model="editForm.distancia">
                                <SelectTrigger><SelectValue placeholder="Seleccionar distancia..." /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="d in distancias" :key="d.id" :value="d.nombre">
                                        {{ d.nombre }}<template v-if="d.es_standby"> · Standby</template>
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-1">
                            <Label>Unidad <span class="text-destructive">*</span></Label>
                            <Select v-model="editForm.transporte_unidad_id" :disabled="!editForm.vehiculo">
                                <SelectTrigger>
                                    <SelectValue :placeholder="editForm.vehiculo ? 'Seleccionar unidad...' : 'Elige primero un vehículo'" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="u in unidadesDelVehiculo(editForm.vehiculo)" :key="u.id" :value="String(u.id)">
                                        {{ unidadLabel(u) }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="editForm.vehiculo && unidadesDelVehiculo(editForm.vehiculo).length === 0" class="text-muted-foreground text-xs">
                                Sin unidades registradas de este tipo en Transportes.
                            </p>
                            <p v-if="editForm.errors.transporte_unidad_id" class="text-destructive text-xs">{{ editForm.errors.transporte_unidad_id }}</p>
                        </div>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div class="space-y-1">
                                <Label>Origen</Label>
                                <Input v-model="editForm.origen" />
                            </div>
                            <div class="space-y-1">
                                <Label>Destino</Label>
                                <Input v-model="editForm.destino" />
                            </div>
                        </div>
                        <div class="space-y-1">
                            <Label>Extras</Label>
                            <Input v-model="editForm.extras" />
                        </div>
                    </div>
                </template>

                <div class="space-y-1">
                    <Label>Comentarios</Label>
                    <Input v-model="editForm.comentarios" />
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="editando = null">Cancelar</Button>
                    <Button type="submit" :disabled="editForm.processing" class="gap-1.5">
                        <Spinner v-if="editForm.processing" class="size-4" />
                        {{ editForm.processing ? 'Guardando…' : 'Guardar corrección' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
