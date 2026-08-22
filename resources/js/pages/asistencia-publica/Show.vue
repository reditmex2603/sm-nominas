<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { CheckCircle2, RefreshCw } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type TipoColaborador = 'COLABORADOR BASE' | 'FREELANCE' | 'CONDUCTOR' | 'CONDUCTOR BASE';
type TipoActividad   = 'Bodega' | 'Evento' | 'Transporte';

interface ColaboradorInfo {
    id: number;
    nombre: string;
    apellidos: string;
    tipo: TipoColaborador;
}

interface EventoRef    { id: number; nombre: string }
interface VehiculoRef  { id: number; nombre: string }
interface DistanciaRef { id: number; nombre: string; es_standby: boolean }
interface UnidadRef    { id: number; marca: string; modelo: string; numero_placas: string | null; transporte_vehiculo_id: number | null }

const props = defineProps<{
    colaborador: ColaboradorInfo;
    eventos: EventoRef[];
    vehiculos: VehiculoRef[];
    distancias: DistanciaRef[];
    unidades: UnidadRef[];
    token: string;
}>();

// Unidades de la flotilla de la categoría de vehículo elegida (el Select de vehículo guarda el
// nombre de la categoría, no su id).
const unidadesDelVehiculo = (nombreVehiculo: string): UnidadRef[] => {
    const vehiculoId = props.vehiculos.find(v => v.nombre === nombreVehiculo)?.id;
    if (!vehiculoId) return [];
    return props.unidades.filter(u => u.transporte_vehiculo_id === vehiculoId);
};

const unidadLabel = (u: UnidadRef): string =>
    `${u.marca} ${u.modelo}` + (u.numero_placas ? ` — ${u.numero_placas}` : '');

// ─── Constantes ─────────────────────────────────────────────────────────────
const BODEGA_ACTIVIDADES = [
    'Stagehand / Apoyo general',
    'Carga / Descarga',
    'Mantenimiento',
    'Inventario',
    'Acomodo',
    'Limpieza',
    'Otro',
];

const BODEGA_EXTRAS = [
    'Chofer / Manejo',
    'Carga pesada / Maniobras',
    'Responsable del transporte',
    'Inventario / Conteo crítico',
    'Mantenimiento especializado',
    'Trabajo nocturno',
    'Apoyo técnico (Cableado/Reparación)',
    'Otro',
];

const EVENTO_EXTRAS = [
    'AUDIO | FOH',
    'AUDIO | Monitores',
    'AUDIO | Backline / Instrumentos',
    'AUDIO | RF / IEM',
    'AUDIO | Sistema de PA / Instalación',
    'VIDEO | Operación',
    'VIDEO | Proyección / Pantallas',
    'VIDEO | Cámara / Livestream',
    'VIDEO | Cableado / Patch',
    'ILUMINACIÓN | Operación',
    'ILUMINACIÓN | Cableado / Patch',
    'ILUMINACIÓN | Moving Heads / Robóticos',
    'ILUMINACIÓN | Efectos especiales',
    'ESCENOGRAFÍA | Montaje / Desmontaje',
    'ESCENOGRAFÍA | Estructura / Tarimas',
    'ESCENOGRAFÍA | Decoración / Backline visual',
    'LOGÍSTICA | Coordinación de proveedores',
    'LOGÍSTICA | Control de acceso / Pulseras',
    'LOGÍSTICA | Manejo de inventario',
    'LOGÍSTICA | Limpieza / Mantenimiento del lugar',
    'PRODUCCIÓN | Stage Manager',
    'PRODUCCIÓN | Runner / Mensajería',
    'PRODUCCIÓN | Hospitalidad / Catering',
    'BONO | Actitud / Desempeño (requiere aprobación)',
    'Otro',
];

// ─── Tipos de actividad permitidos por tipo de colaborador ───────────────────
const TIPOS_POR_COLABORADOR: Record<TipoColaborador, TipoActividad[]> = {
    'COLABORADOR BASE': ['Bodega', 'Evento'],
    'FREELANCE':        ['Evento'],
    'CONDUCTOR':        ['Transporte'],
    'CONDUCTOR BASE':   ['Bodega', 'Transporte'],
};

const tiposPermitidos = TIPOS_POR_COLABORADOR[props.colaborador.tipo];
const defaultTipo     = tiposPermitidos[0];

// ─── Estado ──────────────────────────────────────────────────────────────────
const hoy       = new Date().toISOString().split('T')[0];
const ahoraHora = new Date().toTimeString().slice(0, 5);

const ETAPAS = ['Montaje', 'Show', 'Desmontaje'] as const;

const extras          = ref<string[]>([]);
const etapas          = ref<string[]>([]);
const enviado         = ref(false);
const clientErrors    = ref<Record<string, string>>({});
const submitAttempted = ref(false);

const toggleExtra = (val: string) => {
    const idx = extras.value.indexOf(val);
    if (idx >= 0) extras.value.splice(idx, 1);
    else extras.value.push(val);
};

const toggleEtapa = (val: string) => {
    const idx = etapas.value.indexOf(val);
    if (idx >= 0) etapas.value.splice(idx, 1);
    else etapas.value.push(val);
};

const form = useForm({
    tipo_actividad: defaultTipo,
    actividad:   '',
    evento_raw:  '',
    etapa:       '',
    vehiculo:    '',
    distancia:   '',
    transporte_unidad_id: '' as string,
    origen:      '',
    destino:     '',
    extras:      '',
    evidencia:   null as File | null,
    comentarios: '',
    fecha:       hoy,
    hora:        ahoraHora,
    hora_salida: '',
});

watch(() => form.tipo_actividad, () => { extras.value = []; etapas.value = []; });
watch(() => form.vehiculo, () => { form.transporte_unidad_id = ''; });

// ─── Validación client-side ───────────────────────────────────────────────────
const buildErrors = (): Record<string, string> => {
    const e: Record<string, string> = {};
    if (!form.fecha) e.fecha = 'La fecha es obligatoria.';
    if (!form.hora)  e.hora  = 'La hora de entrada es obligatoria.';

    if (form.tipo_actividad === 'Bodega' && !form.actividad)
        e.actividad = 'Selecciona una actividad.';
    if (form.tipo_actividad === 'Evento' && !form.evento_raw)
        e.evento_raw = 'Selecciona un evento.';
    if (form.tipo_actividad === 'Transporte') {
        if (!form.vehiculo)  e.vehiculo  = 'El vehículo es obligatorio.';
        if (!form.origen)    e.origen    = 'El origen es obligatorio.';
        if (!form.destino)   e.destino   = 'El destino es obligatorio.';
        if (!form.distancia) e.distancia = 'La distancia / ruta es obligatoria.';
        if (!form.transporte_unidad_id) e.transporte_unidad_id = 'La unidad es obligatoria.';
    }
    if (!form.evidencia) e.evidencia = 'La fotografía de evidencia es obligatoria.';
    return e;
};

// Re-valida reactivamente después del primer intento de envío
watch(
    () => [form.fecha, form.hora, form.hora_salida, form.tipo_actividad,
           form.actividad, form.evento_raw, form.vehiculo, form.origen,
           form.destino, form.distancia, form.transporte_unidad_id, form.evidencia],
    () => { if (submitAttempted.value) clientErrors.value = buildErrors(); },
);

// ─── Flash del servidor ───────────────────────────────────────────────────────
const page        = usePage();
const flashSuccess = computed(() => (page.props as any).flash?.success as string | undefined);

// ─── Submit ───────────────────────────────────────────────────────────────────
const submit = () => {
    submitAttempted.value = true;
    clientErrors.value = buildErrors();
    if (Object.keys(clientErrors.value).length > 0) return;

    form.extras = extras.value.join(', ');
    form.etapa  = etapas.value.join(', ');
    form.post(`/asistencia/${props.token}`, {
        forceFormData: true,
        onSuccess: () => { enviado.value = true; },
    });
};

const registrarOtro = () => {
    enviado.value         = false;
    submitAttempted.value = false;
    clientErrors.value    = {};
    extras.value          = [];
    etapas.value          = [];
    form.reset();
    form.tipo_actividad = defaultTipo;
    form.fecha       = new Date().toISOString().split('T')[0];
    form.hora        = new Date().toTimeString().slice(0, 5);
    form.hora_salida = '';
};

// ─── Tipo de colaborador (etiqueta legible) ──────────────────────────────────
const tipoLabel: Record<TipoColaborador, string> = {
    'COLABORADOR BASE': 'Colaborador Base',
    'FREELANCE':        'Freelance',
    'CONDUCTOR':        'Conductor',
    'CONDUCTOR BASE':   'Conductor base',
};

// ─── Evidencia ───────────────────────────────────────────────────────────────
const onEvidencia = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0] ?? null;
    form.evidencia = file;
};
</script>

<template>
    <Head title="Registrar Asistencia" />

    <div class="min-h-screen bg-gradient-to-b from-slate-50 to-slate-100 dark:from-slate-900 dark:to-slate-950 px-4 py-8">
        <div class="mx-auto max-w-lg space-y-6">

            <!-- Encabezado -->
            <div class="text-center space-y-1">
                <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">SM Producciones</p>
                <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-100">Registro de Asistencia</h1>
            </div>

            <!-- Card del colaborador -->
            <div class="rounded-2xl border bg-white dark:bg-slate-800 p-5 shadow-sm">
                <p class="text-xs text-slate-400 uppercase tracking-wide mb-0.5">Registrando como</p>
                <p class="text-xl font-bold text-slate-800 dark:text-slate-100">
                    {{ colaborador.apellidos }}, {{ colaborador.nombre }}
                </p>
                <span class="inline-block mt-1 rounded-full bg-slate-100 dark:bg-slate-700 px-3 py-0.5 text-xs font-medium text-slate-600 dark:text-slate-300">
                    {{ tipoLabel[colaborador.tipo] }}
                </span>
            </div>

            <!-- Estado: registro enviado ─────────────────────────────── -->
            <div v-if="enviado" class="rounded-2xl border border-emerald-200 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-950/40 p-6 text-center space-y-4 shadow-sm">
                <CheckCircle2 class="mx-auto size-12 text-emerald-500" />
                <div>
                    <p class="text-lg font-semibold text-emerald-700 dark:text-emerald-400">¡Registro enviado!</p>
                    <p class="text-sm text-emerald-600 dark:text-emerald-500 mt-1">
                        Tu asistencia fue registrada correctamente.
                    </p>
                </div>
                <Button variant="outline" class="w-full" @click="registrarOtro">
                    <RefreshCw class="size-4 mr-2" />
                    Registrar otra asistencia
                </Button>
            </div>

            <!-- Formulario ──────────────────────────────────────────── -->
            <form v-else class="rounded-2xl border bg-white dark:bg-slate-800 p-5 shadow-sm space-y-5" @submit.prevent="submit">

                <!-- Fecha y horas -->
                <div class="space-y-1">
                    <Label class="text-sm font-medium">Fecha <span class="text-destructive">*</span></Label>
                    <Input v-model="form.fecha" type="date" class="text-base h-11" :class="(clientErrors.fecha || form.errors.fecha) ? 'border-destructive' : ''" />
                    <p v-if="clientErrors.fecha || form.errors.fecha" class="text-destructive text-xs">{{ clientErrors.fecha || form.errors.fecha }}</p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <Label class="text-sm font-medium">Hora de entrada <span class="text-destructive">*</span></Label>
                        <Input v-model="form.hora" type="time" class="text-base h-11" :class="(clientErrors.hora || form.errors.hora) ? 'border-destructive' : ''" />
                        <p v-if="clientErrors.hora || form.errors.hora" class="text-destructive text-xs">{{ clientErrors.hora || form.errors.hora }}</p>
                    </div>
                    <div class="space-y-1">
                        <Label class="text-sm font-medium">Hora de salida (opcional)</Label>
                        <Input v-model="form.hora_salida" type="time" class="text-base h-11" :class="(clientErrors.hora_salida || form.errors.hora_salida) ? 'border-destructive' : ''" />
                        <p v-if="clientErrors.hora_salida || form.errors.hora_salida" class="text-destructive text-xs">{{ clientErrors.hora_salida || form.errors.hora_salida }}</p>
                    </div>
                </div>

                <!-- Tipo de actividad -->
                <div class="space-y-1">
                    <Label class="text-sm font-medium">Tipo de actividad</Label>
                    <!-- Un solo tipo permitido: solo se muestra, no se puede cambiar -->
                    <div
                        v-if="tiposPermitidos.length === 1"
                        class="flex h-11 items-center rounded-md border border-input bg-muted px-3 text-sm text-muted-foreground"
                    >
                        {{ tiposPermitidos[0] }}
                    </div>
                    <Select v-else v-model="form.tipo_actividad">
                        <SelectTrigger class="h-11 text-base">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="tipo in tiposPermitidos" :key="tipo" :value="tipo">
                                {{ tipo }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <!-- ── Bodega ─────────────────────────────────────── -->
                <template v-if="form.tipo_actividad === 'Bodega'">
                    <div class="space-y-1">
                        <Label class="text-sm font-medium">Actividad <span class="text-destructive">*</span></Label>
                        <Select v-model="form.actividad">
                            <SelectTrigger class="h-11 text-base" :class="(clientErrors.actividad || form.errors.actividad) ? 'border-destructive' : ''">
                                <SelectValue placeholder="Seleccionar actividad..." />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="a in BODEGA_ACTIVIDADES" :key="a" :value="a">{{ a }}</SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="clientErrors.actividad || form.errors.actividad" class="text-destructive text-xs">{{ clientErrors.actividad || form.errors.actividad }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label class="text-sm font-medium">Extras (opcional)</Label>
                        <div class="grid grid-cols-1 gap-2">
                            <label
                                v-for="ex in BODEGA_EXTRAS"
                                :key="ex"
                                class="flex items-center gap-3 rounded-lg border p-3 cursor-pointer transition-colors"
                                :class="extras.includes(ex) ? 'border-blue-400 bg-blue-50 dark:border-blue-600 dark:bg-blue-950/30' : 'border-slate-200 dark:border-slate-700'"
                            >
                                <Checkbox
                                    :model-value="extras.includes(ex)"
                                    @update:model-value="toggleExtra(ex)"
                                />
                                <span class="text-sm">{{ ex }}</span>
                            </label>
                        </div>
                    </div>
                </template>

                <!-- ── Evento ─────────────────────────────────────── -->
                <template v-else-if="form.tipo_actividad === 'Evento'">
                    <div class="space-y-1">
                        <Label class="text-sm font-medium">Evento <span class="text-destructive">*</span></Label>
                        <Select v-model="form.evento_raw">
                            <SelectTrigger class="h-11 text-base" :class="(clientErrors.evento_raw || form.errors.evento_raw) ? 'border-destructive' : ''">
                                <SelectValue placeholder="Seleccionar evento..." />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="e in eventos" :key="e.id" :value="e.nombre">
                                    {{ e.nombre }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="clientErrors.evento_raw || form.errors.evento_raw" class="text-destructive text-xs">{{ clientErrors.evento_raw || form.errors.evento_raw }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label class="text-sm font-medium">Etapa(s)</Label>
                        <div class="flex flex-wrap gap-2">
                            <label
                                v-for="etapa in ETAPAS"
                                :key="etapa"
                                class="flex items-center gap-3 rounded-lg border px-4 py-3 cursor-pointer transition-colors flex-1 min-w-24"
                                :class="etapas.includes(etapa)
                                    ? 'border-blue-400 bg-blue-50 dark:border-blue-600 dark:bg-blue-950/30'
                                    : 'border-slate-200 dark:border-slate-700'"
                            >
                                <Checkbox
                                    :model-value="etapas.includes(etapa)"
                                    @update:model-value="toggleEtapa(etapa)"
                                />
                                <span class="text-sm font-medium">{{ etapa }}</span>
                            </label>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label class="text-sm font-medium">Funciones / Extras (opcional)</Label>
                        <div class="space-y-1 max-h-72 overflow-y-auto rounded-lg border border-slate-200 dark:border-slate-700 p-2">
                            <label
                                v-for="ex in EVENTO_EXTRAS"
                                :key="ex"
                                class="flex items-center gap-3 rounded-md px-2 py-2.5 cursor-pointer transition-colors"
                                :class="extras.includes(ex) ? 'bg-blue-50 dark:bg-blue-950/30' : 'hover:bg-slate-50 dark:hover:bg-slate-700/40'"
                            >
                                <Checkbox
                                    :model-value="extras.includes(ex)"
                                    @update:model-value="toggleExtra(ex)"
                                />
                                <span class="text-sm leading-tight">{{ ex }}</span>
                            </label>
                        </div>
                    </div>
                </template>

                <!-- ── Transporte ─────────────────────────────────── -->
                <template v-else-if="form.tipo_actividad === 'Transporte'">
                    <div class="space-y-1">
                        <Label class="text-sm font-medium">Vehículo <span class="text-destructive">*</span></Label>
                        <Select v-model="form.vehiculo">
                            <SelectTrigger class="h-11 text-base" :class="(clientErrors.vehiculo || form.errors.vehiculo) ? 'border-destructive' : ''">
                                <SelectValue placeholder="Seleccionar vehículo..." />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="v in vehiculos" :key="v.id" :value="v.nombre">{{ v.nombre }}</SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="clientErrors.vehiculo || form.errors.vehiculo" class="text-destructive text-xs">{{ clientErrors.vehiculo || form.errors.vehiculo }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <Label class="text-sm font-medium">Origen <span class="text-destructive">*</span></Label>
                            <Input v-model="form.origen" class="h-11 text-base" :class="(clientErrors.origen || form.errors.origen) ? 'border-destructive' : ''" />
                            <p v-if="clientErrors.origen || form.errors.origen" class="text-destructive text-xs">{{ clientErrors.origen || form.errors.origen }}</p>
                        </div>
                        <div class="space-y-1">
                            <Label class="text-sm font-medium">Destino <span class="text-destructive">*</span></Label>
                            <Input v-model="form.destino" class="h-11 text-base" :class="(clientErrors.destino || form.errors.destino) ? 'border-destructive' : ''" />
                            <p v-if="clientErrors.destino || form.errors.destino" class="text-destructive text-xs">{{ clientErrors.destino || form.errors.destino }}</p>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <Label class="text-sm font-medium">Distancia / Ruta <span class="text-destructive">*</span></Label>
                        <Select v-model="form.distancia">
                            <SelectTrigger class="h-11 text-base" :class="(clientErrors.distancia || form.errors.distancia) ? 'border-destructive' : ''">
                                <SelectValue placeholder="Seleccionar distancia..." />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="d in distancias" :key="d.id" :value="d.nombre">
                                    {{ d.nombre }}<template v-if="d.es_standby"> · Standby</template>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="clientErrors.distancia || form.errors.distancia" class="text-destructive text-xs">{{ clientErrors.distancia || form.errors.distancia }}</p>
                    </div>
                    <div class="space-y-1">
                        <Label class="text-sm font-medium">Unidad <span class="text-destructive">*</span></Label>
                        <Select v-model="form.transporte_unidad_id" :disabled="!form.vehiculo">
                            <SelectTrigger class="h-11 text-base" :class="(clientErrors.transporte_unidad_id || form.errors.transporte_unidad_id) ? 'border-destructive' : ''">
                                <SelectValue :placeholder="form.vehiculo ? 'Seleccionar unidad...' : 'Elige primero un vehículo'" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="u in unidadesDelVehiculo(form.vehiculo)" :key="u.id" :value="String(u.id)">
                                    {{ unidadLabel(u) }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.vehiculo && unidadesDelVehiculo(form.vehiculo).length === 0" class="text-muted-foreground text-xs">
                            Sin unidades registradas de este tipo. Regístralas en Transportes antes de continuar.
                        </p>
                        <p v-if="clientErrors.transporte_unidad_id || form.errors.transporte_unidad_id" class="text-destructive text-xs">{{ clientErrors.transporte_unidad_id || form.errors.transporte_unidad_id }}</p>
                    </div>
                    <div class="space-y-1">
                        <Label class="text-sm font-medium">Extras (opcional)</Label>
                        <Input v-model="form.extras" class="h-11 text-base" placeholder="Descripción adicional" />
                    </div>
                </template>

                <!-- Comentarios -->
                <div class="space-y-1">
                    <Label class="text-sm font-medium">Comentarios (opcional)</Label>
                    <textarea
                        v-model="form.comentarios"
                        rows="3"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-base ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        placeholder="Observaciones, notas adicionales..."
                    />
                </div>

                <!-- Evidencia fotográfica -->
                <div class="space-y-1">
                    <Label class="text-sm font-medium">Evidencia fotográfica <span class="text-destructive">*</span></Label>
                    <input
                        type="file"
                        accept="image/*"
                        capture="environment"
                        class="w-full rounded-md border bg-background px-3 py-2 text-sm file:border-0 file:bg-transparent file:text-sm file:font-medium"
                        :class="(clientErrors.evidencia || form.errors.evidencia) ? 'border-destructive' : 'border-input'"
                        @change="onEvidencia"
                    />
                    <p class="text-xs text-slate-400">Máx. 5 MB. Puedes tomar una foto directamente con la cámara.</p>
                    <p v-if="clientErrors.evidencia || form.errors.evidencia" class="text-destructive text-xs">{{ clientErrors.evidencia || form.errors.evidencia }}</p>
                </div>

                <!-- Botón submit -->
                <Button
                    type="submit"
                    class="w-full h-12 text-base font-semibold"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Enviando...' : 'Enviar registro' }}
                </Button>
            </form>

            <!-- Footer -->
            <p class="text-center text-xs text-slate-400 pb-4">
                Este enlace es personal. No lo compartas con otras personas.
            </p>
        </div>
    </div>
</template>
