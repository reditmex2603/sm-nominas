<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Calculator, CalendarClock, ChevronDown, DollarSign, Filter, HandCoins, RefreshCw, X } from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
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
import { Spinner } from '@/components/ui/spinner';
import { useConfirm } from '@/composables/useConfirm';
import { desplazarSemanas, esRangoSemanasCompleto, fmtFecha, semanaBase } from '@/lib/fecha';
import { fraccionEventoLabel, fraccionEventoModo, fraccionEventoModoOpciones } from '@/lib/fraccionEvento';
import { tipoPagoBadgeClass, tipoPagoLabel, tipoPagoOpciones  } from '@/lib/tipoPago';
import type {TipoPago} from '@/lib/tipoPago';
import * as jornadasApi from '@/routes/jornadas';
import * as nomina from '@/routes/nomina';
import * as prestamoCuotas from '@/routes/prestamos/cuotas';
import * as registroAsistencia from '@/routes/registro-asistencia';

type TipoColaborador = 'COLABORADOR BASE' | 'FREELANCE' | 'CONDUCTOR' | 'CONDUCTOR BASE';

interface Colaborador {
    id: number;
    nombre: string;
    apellidos: string;
    tipo: TipoColaborador;
    sueldo_diario: string | null;
    compensacion_pct?: number;
}

interface Jornada {
    id: number;
    fecha: string;
    entrada: string | null;
    salida: string | null;
    actividades: string[];
    detalle: string | null;
    extras: string | null;
    evidencias: string | null;
    comentarios: string | null;
    validado: boolean;
    tipo_pago: TipoPago;
    traslape_pct: number | null;
    fracciones_evento: Record<number, number> | null;
    compensacion_activa: boolean;
    colaborador: Colaborador;
    evidencia_urls: string[];
    nomina_estado: 'PENDIENTE' | 'PAGADO' | null;
    eventos_dia: { id: number; nombre: string; tamano: string }[];
}

interface ColaboradorRef { id: number; nombre: string; apellidos: string; tipo: TipoColaborador }

const props = defineProps<{
    jornadas: Jornada[];
    colaboradores: ColaboradorRef[];
    registrosSinProcesar: number;
}>();

// ---------- Tabs ----------
type Tab = 'base' | 'freelance' | 'conductores' | 'conductor_base';
const activeTab = ref<Tab>('base');

const tabs: { key: Tab; label: string; tipo: TipoColaborador }[] = [
    { key: 'base',        label: 'Base',        tipo: 'COLABORADOR BASE' },
    { key: 'freelance',   label: 'Freelance',   tipo: 'FREELANCE' },
    { key: 'conductores', label: 'Conductores', tipo: 'CONDUCTOR' },
    { key: 'conductor_base', label: 'Conductor base', tipo: 'CONDUCTOR BASE' },
];

const jornadasPorTipo = (tipo: TipoColaborador) =>
    props.jornadas.filter(j => j.colaborador.tipo === tipo);

const pendientesPorTipo = (tipo: TipoColaborador) =>
    jornadasPorTipo(tipo).filter(j => !j.validado).length;

const jornadasActivas = computed(() => {
    const tab = tabs.find(t => t.key === activeTab.value)!;

    return jornadasPorTipo(tab.tipo);
});

// Pendientes primero (por fecha), luego validadas (por fecha)
const jornadasOrdenadas = computed(() => [
    ...jornadasActivas.value.filter(j => !j.validado).sort((a, b) => a.fecha.localeCompare(b.fecha)),
    ...jornadasActivas.value.filter(j =>  j.validado).sort((a, b) => a.fecha.localeCompare(b.fecha)),
]);

const totalPendientes  = computed(() => jornadasActivas.value.filter(j => !j.validado).length);
const totalValidadas   = computed(() => jornadasActivas.value.filter(j =>  j.validado).length);
const progresoValidacion = computed(() => {
    const total = jornadasActivas.value.length;

    return total === 0 ? 100 : Math.round((totalValidadas.value / total) * 100);
});

// El `detalle` de la jornada vuelve a nombrar cada evento ("Evento: X · etapa"); como los eventos
// ya se muestran uno por uno en `eventos_dia` con (tipo · % · $total), lo filtramos para no
// mostrarlo dos veces (solo se conservan las líneas que NO describen un evento).
const detalleSinEventos = (detalle: string | null): string =>
    (detalle ?? '')
        .split('\n')
        .filter((linea) => !/^Evento:/i.test(linea.trim()))
        .join(' · ');

const rutasDe = (fecha: string) =>
    (desglose.value?._rutas ?? []).filter((r) => String(r.fecha).slice(0, 10) === fecha);

// ---------- tipoPago config ----------
const tipoPagoOpcionesFreelance = tipoPagoOpciones.filter(o =>
    ['JORNADA_COMPLETA', 'JORNADA_COMPLETA + EVENTO', 'SIN_PAGO', 'ERROR_EVENTO'].includes(o.value),
);

const tipoPagoOpcionesConductor = tipoPagoOpciones.filter(o =>
    ['JORNADA_COMPLETA', 'TRASLAPE', 'SIN_PAGO', 'ERROR_EVENTO'].includes(o.value),
);

// Con 2+ eventos el mismo día el traslape se pondera por evento (no como tipo del día): sin TRASLAPE.
const tipoPagoOpcionesBase = (j: Jornada) =>
    j.eventos_dia.length >= 2
        ? tipoPagoOpciones.filter(o => o.value !== 'TRASLAPE')
        : tipoPagoOpciones;

const rowClass = (j: Jornada): string => {
    if (j.tipo_pago === 'ERROR_EVENTO') {
return 'bg-red-50 dark:bg-red-950/20';
}

    if (j.validado) {
return 'bg-emerald-50/60 dark:bg-emerald-950/10';
}

    return 'hover:bg-muted/30';
};

const nominaBadgeClass = (estado: 'PENDIENTE' | 'PAGADO'): string =>
    estado === 'PAGADO'
        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-400'
        : 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-400';

const nominaBadgeTitle = (estado: 'PENDIENTE' | 'PAGADO'): string =>
    estado === 'PAGADO'
        ? 'Incluida en una nómina ya pagada'
        : 'Incluida en una nómina pendiente de pago';

// ---------- Filtros ----------
const mostrarFiltros = ref(false);

const filtros = ref({
    colaborador: '',
    fechaDesde: '',
    fechaHasta: '',
    estado: '' as '' | 'pendiente' | 'validada',
    enNomina: '' as '' | 'si' | 'no',
});

const hayFiltrosActivos = computed(() =>
    filtros.value.colaborador !== '' ||
    filtros.value.fechaDesde !== '' ||
    filtros.value.fechaHasta !== '' ||
    filtros.value.estado !== '' ||
    filtros.value.enNomina !== ''
);

const limpiarFiltros = () => {
    filtros.value = { colaborador: '', fechaDesde: '', fechaHasta: '', estado: '', enNomina: '' };
};

const jornadasFiltradas = computed(() => {
    let resultado = jornadasOrdenadas.value;
    const { colaborador, fechaDesde, fechaHasta, estado, enNomina } = filtros.value;

    if (colaborador) {
        const q = colaborador.toLowerCase();
        resultado = resultado.filter(j =>
            `${j.colaborador.nombre} ${j.colaborador.apellidos}`.toLowerCase().includes(q)
        );
    }

    if (fechaDesde) {
resultado = resultado.filter(j => j.fecha >= fechaDesde);
}

    if (fechaHasta) {
resultado = resultado.filter(j => j.fecha <= fechaHasta);
}

    if (estado === 'pendiente') {
resultado = resultado.filter(j => !j.validado);
} else if (estado === 'validada') {
resultado = resultado.filter(j => j.validado);
}

    if (enNomina === 'si') {
resultado = resultado.filter(j => j.nomina_estado !== null);
} else if (enNomina === 'no') {
resultado = resultado.filter(j => j.nomina_estado === null);
}

    return resultado;
});

const totalPendientesFiltrados = computed(() => jornadasFiltradas.value.filter(j => !j.validado).length);
const totalValidadasFiltradas  = computed(() => jornadasFiltradas.value.filter(j => j.validado).length);

// ---------- PATCH helpers ----------
// Track which rows have in-flight PATCH requests
const saving = ref(new Set<number>());

const setValidado = (j: Jornada, valor: boolean) => {
    saving.value = new Set(saving.value).add(j.id);
    router.patch(
        jornadasApi.validado.url(j.id),
        { validado: valor },
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                const next = new Set(saving.value);
                next.delete(j.id);
                saving.value = next;
            },
        },
    );
};

// ─── Regenerar jornadas ──────────────────────────────────────────────
const generando = ref(false);

const regenerarJornadas = () => {
    generando.value = true;
    router.post(jornadasApi.generar.url(), {}, {
        onFinish: () => {
 generando.value = false; 
},
    });
};

// ─── Sheet de cálculo ────────────────────────────────────────────────

interface Desglose {
    tipo: TipoColaborador;
    colaborador_id: number;
    periodo_inicio: string | null;
    periodo_fin: string | null;
    evento_id: number | null;
    dias: number;
    sueldo_diario: number;
    total_base: number;
    bonos_evento: number;
    compensaciones: number;
    anticipos: number;
    prestamos?: number;
    total_final: number;
    estado: 'PENDIENTE' | 'PAGADO' | null;
    nomina_id: number | null;
    _bonos_evento_puro?: number;
    _bono_septimo?: number;
    _jornadas?: {
        fecha: string;
        tipo_pago: TipoPago;
        traslape_pct: number | null;
        detalle: string | null;
        extras: string | null;
        dia_fraccion: number;
        bono_evento: number;
        eventos_dia: { nombre: string; tamano: string; pct_etapas: number; bono: number; compensacion: number; fraccion: number | null }[];
    }[];
    _categoria?: string | null;
    _nivel?: number | null;
    _rutas?: { fecha: string; vehiculo: string; distancia: string; monto: number; extras: string | null }[];
    _total_rutas?: number;
    _etapas?: string[];
    _registros?: { fecha: string; etapa: string | null; extras: string | null; contabiliza: boolean }[];
    _porcentaje?: number;
    _pago_base?: number;
    _pago_extras?: number;
    _prestamo_detalle?: { id: number; prestamo_id: number; concepto: string | null; numero_plazo: number; monto: number; fecha_programada: string }[];
    jornadas_sin_validar?: number;
}

const showCalc    = ref(false);
const calculando  = ref(false);
const desglose    = ref<Desglose | null>(null);
const calcError   = ref<string | null>(null);
const mostrarResumenEvaluado = ref(false);

// Lightbox para evidencias
const lightboxUrl = ref<string | null>(null);

// Rastrea la compensación que vino del servidor (para detectar cambios sin guardar)
const compensacionServidor = ref(0);
const hasUnsavedComp = computed(
    () => desglose.value !== null && calcParams.value.compensacion !== compensacionServidor.value,
);

const calcParams  = ref({
    colaborador_id: '' as string,
    inicio: '',
    fin: '',
    evento_id: '' as string,
    dias_adicionales: 0,
    compensacion: 0,
    comentario: '' as string,
});

// ─── Datos de asistencia del freelance (eventos filtrados + registros editables) ──
const ETAPAS = ['Montaje', 'Show', 'Desmontaje'] as const;

interface RegistroEvento {
    id: number;
    fecha: string;
    etapa: string | null;
    extras: string | null;
    comentarios: string | null;
    evidencia_url: string | null;
    jornada_validada: boolean;
}
interface EventoConAsistencia { id: number; nombre: string; tamano: string; pago_por_evento_completo: string }

const freelanceEventos   = ref<EventoConAsistencia[]>([]);
const freelanceRegistros = ref<Record<number, RegistroEvento[]>>({});
const cargandoFreelance  = ref(false);
const guardandoRegistro  = ref(new Set<number>());

const cargarFreelanceDatos = async (colaboradorId: string) => {
    freelanceEventos.value   = [];
    freelanceRegistros.value = {};

    if (!colaboradorId) {
return;
}

    cargandoFreelance.value = true;

    try {
        const url = new URL(nomina.freelanceDatos.url(), window.location.origin);
        url.searchParams.set('colaborador_id', colaboradorId);
        const res = await fetch(url.toString(), { headers: { Accept: 'application/json' } });

        if (res.ok) {
            const data = await res.json();
            freelanceEventos.value   = data.eventos;
            freelanceRegistros.value = data.registros;
        }
    } finally {
        cargandoFreelance.value = false;
    }
};

const registrosEventoSeleccionado = computed<RegistroEvento[]>(() => {
    if (!calcParams.value.evento_id) {
return [];
}

    return freelanceRegistros.value[Number(calcParams.value.evento_id)] ?? [];
});

const guardarRegistroEvento = (r: RegistroEvento) => {
    guardandoRegistro.value = new Set(guardandoRegistro.value).add(r.id);
    router.put(
        registroAsistencia.update.url(r.id),
        { etapa: r.etapa, extras: r.extras, comentarios: r.comentarios },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => cargarFreelanceDatos(calcParams.value.colaborador_id),
            onFinish: () => {
                const next = new Set(guardandoRegistro.value);
                next.delete(r.id);
                guardandoRegistro.value = next;
            },
        },
    );
};

// 'etapa' es un string libre que puede traer varias etapas combinadas ("Montaje, Show")
const etapasDeRegistro = (r: RegistroEvento): string[] =>
    (r.etapa ?? '').split(',').map(s => s.trim()).filter(Boolean);

const toggleEtapaRegistro = (r: RegistroEvento, etapa: string) => {
    const actuales = etapasDeRegistro(r);
    const idx = actuales.indexOf(etapa);

    if (idx === -1) {
actuales.push(etapa);
} else {
actuales.splice(idx, 1);
}

    r.etapa = actuales.join(', ');
    guardarRegistroEvento(r);
};

// Tipo del colaborador seleccionado (auto-detectado)
const tipoSeleccionado = computed<TipoColaborador | null>(() => {
    if (!calcParams.value.colaborador_id) {
return null;
}

    const col = props.colaboradores.find(c => String(c.id) === calcParams.value.colaborador_id);

    return col?.tipo ?? null;
});

// Reset al cambiar colaborador
watch(() => calcParams.value.colaborador_id, (colaboradorId) => {
    desglose.value = null;
    calcError.value = null;
    calcParams.value.inicio = '';
    calcParams.value.fin = '';
    calcParams.value.evento_id = '';
    calcParams.value.compensacion = 0;
    calcParams.value.comentario = '';
    calcParams.value.dias_adicionales = 0;

    if (tipoSeleccionado.value === 'FREELANCE') {
        cargarFreelanceDatos(colaboradorId);
    } else {
        freelanceEventos.value = [];
        freelanceRegistros.value = {};
    }

    // Base y Conductor se pagan por corte semanal lunes→sábado: proponemos la semana actual por defecto.
    if (tipoSeleccionado.value === 'COLABORADOR BASE' || tipoSeleccionado.value === 'CONDUCTOR' || tipoSeleccionado.value === 'CONDUCTOR BASE') {
        const { inicio, fin } = semanaBase();
        calcParams.value.inicio = inicio;
        calcParams.value.fin = fin;
    }
});

// Recalcular desglose (sin volver a llamar al servidor) al editar una actividad
watch(() => calcParams.value.evento_id, () => {
 desglose.value = null; 
});

// ─── Corte semanal (lunes→sábado, 1 o varias semanas) para Base y Conductor ────
const usarSemanaActual = () => {
    const { inicio, fin } = semanaBase();
    calcParams.value.inicio = inicio;
    calcParams.value.fin = fin;
};

const usarSemanaAnterior = () => {
    const haceUnaSemana = new Date();
    haceUnaSemana.setDate(haceUnaSemana.getDate() - 7);
    const { inicio, fin } = semanaBase(haceUnaSemana);
    calcParams.value.inicio = inicio;
    calcParams.value.fin = fin;
};

// Extiende/reduce el fin del período de 7 en 7 días (siguiente/anterior sábado) para cubrir
// varias semanas sin tener que editar la fecha a mano; no deja reducir por debajo de 1 semana.
const extenderSemana = (n: number) => {
    if (!calcParams.value.fin) {
return;
}

    const nuevoFin = desplazarSemanas(calcParams.value.fin, n);

    if (calcParams.value.inicio && nuevoFin < calcParams.value.inicio) {
return;
}

    calcParams.value.fin = nuevoFin;
};

const puedeReducirSemana = computed(() =>
    !!calcParams.value.inicio && !!calcParams.value.fin &&
    desplazarSemanas(calcParams.value.fin, -1) >= calcParams.value.inicio,
);

const semanaIncompleta = computed(() =>
    (tipoSeleccionado.value === 'COLABORADOR BASE' || tipoSeleccionado.value === 'CONDUCTOR' || tipoSeleccionado.value === 'CONDUCTOR BASE') &&
    !!calcParams.value.inicio && !!calcParams.value.fin &&
    !esRangoSemanasCompleto(calcParams.value.inicio, calcParams.value.fin),
);

const calcularNomina = async () => {
    if (!calcParams.value.colaborador_id) {
return;
}

    clampCompensacion();
    calculando.value = true;
    calcError.value  = null;
    desglose.value   = null;
    mostrarResumenEvaluado.value = false;

    const tipo = tipoSeleccionado.value;
    const params: Record<string, string> = {
        tipo: tipo ?? '',
        colaborador_id: calcParams.value.colaborador_id,
        compensacion: String(calcParams.value.compensacion),
    };

    if (tipo === 'COLABORADOR BASE' || tipo === 'CONDUCTOR' || tipo === 'CONDUCTOR BASE') {
        params.inicio = calcParams.value.inicio;
        params.fin    = calcParams.value.fin;
    } else {
        params.evento_id        = calcParams.value.evento_id;
        params.dias_adicionales = String(calcParams.value.dias_adicionales);
    }

    try {
        const url = new URL(nomina.calcular.url(), window.location.origin);
        Object.entries(params).forEach(([k, v]) => url.searchParams.set(k, v));

        const res = await fetch(url.toString(), { headers: { Accept: 'application/json' } });

        if (!res.ok) {
            const err = await res.json();
            calcError.value = err.message ?? 'Error al calcular';
        } else {
            desglose.value = await res.json();
            // Sincronizar compensación del servidor al formulario
            calcParams.value.compensacion = desglose.value?.compensaciones ?? 0;
            compensacionServidor.value    = calcParams.value.compensacion;
        }
    } catch {
        calcError.value = 'Error de conexión';
    } finally {
        calculando.value = false;
    }
};

// Recálculo local al cambiar compensación (sin nueva llamada al servidor)
const totalConCompensacion = computed(() => {
    if (!desglose.value) {
return 0;
}

    const base = desglose.value.total_final - desglose.value.compensaciones;

    return base + calcParams.value.compensacion;
});

// La compensación negativa funciona como descuento manual puntual.
const esDescuento = computed(() => calcParams.value.compensacion < 0);

const fmtSigned = (n: number): string => {
    const s = n < 0 ? '-' : '';

    return `${s}$${Math.abs(n).toLocaleString('es-MX', { minimumFractionDigits: 2 })}`;
};

// Tope: el descuento no puede superar el monto neto a pagar base (sin compensación).
const topeDescuento = computed(() => {
    if (!desglose.value) {
return 0;
}

    const base = desglose.value.total_final - desglose.value.compensaciones;

    return -Math.max(base, 0);
});

const compExcedeTope = computed(() => calcParams.value.compensacion < topeDescuento.value);

const clampCompensacion = () => {
    if (calcParams.value.compensacion < topeDescuento.value) {
        calcParams.value.compensacion = topeDescuento.value;
    }
};

// Actividades extra registradas en el período/evento evaluado: no se pagan solas,
// el admin debe decidir si ameritan compensación manual.
const extrasDelPeriodo = computed<string[]>(() => {
    if (!desglose.value) {
return [];
}

    const filas: { extras: string | null }[] =
        desglose.value.tipo === 'COLABORADOR BASE' ? (desglose.value._jornadas ?? []) :
        desglose.value.tipo === 'CONDUCTOR BASE'   ? (desglose.value._jornadas ?? []) :
        desglose.value.tipo === 'FREELANCE'        ? (desglose.value._registros ?? []) :
        (desglose.value._rutas ?? []);

    const todas = filas.flatMap(f => (f.extras ?? '').split(',').map(s => s.trim()).filter(Boolean));

    return [...new Set(todas)];
});

// Evita que el guardado dispare su propia advertencia de "cambios sin guardar":
// router.post() aquí abajo es en sí mismo una navegación Inertia, y el listener
// router.on('before') de más abajo intercepta TODAS las navegaciones, incluida esta.
const guardandoNomina = ref(false);

const guardarNomina = () => {
    if (!desglose.value || desglose.value.estado === 'PAGADO') {
return;
}

    clampCompensacion();

    const tipo = tipoSeleccionado.value;
    const payload: Record<string, string | number | null> = {
        tipo: tipo ?? '',
        colaborador_id: Number(calcParams.value.colaborador_id),
        compensacion: calcParams.value.compensacion,
        comentario: calcParams.value.comentario.trim() || null,
    };

    if (tipo === 'COLABORADOR BASE' || tipo === 'CONDUCTOR' || tipo === 'CONDUCTOR BASE') {
        payload.inicio = calcParams.value.inicio;
        payload.fin    = calcParams.value.fin;
    } else {
        payload.evento_id        = Number(calcParams.value.evento_id);
        payload.dias_adicionales = calcParams.value.dias_adicionales;
    }

    guardandoNomina.value = true;
    router.post(nomina.guardar.url(), payload, {
        preserveScroll: true,
        onSuccess: () => {
 calcularNomina(); 
},
        onError: (errors) => {
            calcError.value = Object.values(errors)[0] as string ?? 'Error al guardar la nómina.';
        },
        onFinish: () => {
 guardandoNomina.value = false; 
},
    });
};

const { confirm } = useConfirm();
const pagandoNomina = ref(false);

const pagarNomina = async () => {
    if (!desglose.value?.nomina_id) {
return;
}

    const ok = await confirm('¿Marcar esta nómina como PAGADA? Esta acción es irreversible.', {
        title: 'Marcar como pagada',
        confirmLabel: 'Sí, marcar como pagada',
        variant: 'default',
    });

    if (!ok) {
return;
}

    pagandoNomina.value = true;
    router.patch(nomina.pagar.url(desglose.value.nomina_id), {}, {
        preserveScroll: true,
        onSuccess: () => {
 calcularNomina(); 
},
        onFinish: () => {
 pagandoNomina.value = false; 
},
    });
};

// ─── Aplazar / distribuir plazos de préstamo en la vista previa ─────
const plazosSel = ref<Set<number>>(new Set());
const aplazarAbierto = ref(false);
const nuevaFechaPlazo = ref('');

const plazosEnDesglose = computed(() => desglose.value?._prestamo_detalle ?? []);
const selPlazos = computed(() => plazosEnDesglose.value.filter(c => plazosSel.value.has(c.id)));
const selPlazosMultiPrestamo = computed(() => new Set(selPlazos.value.map(c => c.prestamo_id)).size > 1);

const togglePlazo = (id: number) => {
    const s = new Set(plazosSel.value);

    if (s.has(id)) {
        s.delete(id);
    } else {
        s.add(id);
    }

    plazosSel.value = s;
};

const abrirAplazar = () => {
    // Por defecto: un día después del fin del período (el plazo sale de esta nómina).
    const base = calcParams.value.fin || new Date().toISOString().slice(0, 10);
    const d = new Date(`${base}T12:00:00`);
    d.setDate(d.getDate() + 1);
    nuevaFechaPlazo.value = d.toISOString().slice(0, 10);
    aplazarAbierto.value = true;
};

const aplazando = ref(false);

const confirmarAplazar = () => {
    aplazando.value = true;
    router.post(prestamoCuotas.aplazar.url(), {
        cuota_ids: [...plazosSel.value],
        nueva_fecha: nuevaFechaPlazo.value,
    }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            aplazarAbierto.value = false;
            calcularNomina();
        },
        onFinish: () => {
 aplazando.value = false; 
},
    });
};

const distribuirCarga = async () => {
    const total = selPlazos.value.reduce((s, c) => s + c.monto, 0);
    const plural = selPlazos.value.length === 1 ? 'plazo' : 'plazos';
    const ok = await confirm(
        `¿Distribuir la carga de ${selPlazos.value.length} ${plural} (${fmtSigned(total)}) entre los demás plazos pendientes del mismo préstamo? ` +
        `Los ${plural} elegidos se eliminarán y su monto se repartirá por igual en los restantes.`,
        { title: 'Distribuir carga', confirmLabel: 'Sí, distribuir' },
    );

    if (!ok) {
return;
}

    router.post(prestamoCuotas.distribuir.url(), { cuota_ids: [...plazosSel.value] }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => calcularNomina(),
    });
};

// Reinicia la selección al recalcular o cerrar la vista previa
watch(desglose, () => {
 plazosSel.value = new Set(); 
});

// ─── Advertencia cambios sin guardar ────────────────────────────────

const cerrarCalc = () => {
    showCalc.value = false;
    desglose.value = null;
    calcError.value = null;
    compensacionServidor.value = 0;
};

const handleCierreCalc = async (open: boolean) => {
    if (open) {
return;
}

    if (!hasUnsavedComp.value) {
 cerrarCalc();

 return; 
}

    const ok = await confirm('Tienes una compensación sin guardar. ¿Cerrar de todas formas?', {
        title: 'Cambios sin guardar',
        confirmLabel: 'Cerrar sin guardar',
    });

    if (ok) {
cerrarCalc();
}
};

const beforeUnloadHandler = (e: BeforeUnloadEvent) => {
    if (hasUnsavedComp.value) {
        e.preventDefault();
    }
};

let pendingNavUrl: string | null = null;
let removeInertiaListener: (() => void) | null = null;

onMounted(() => {
    window.addEventListener('beforeunload', beforeUnloadHandler);
    removeInertiaListener = router.on('before', (event) => {
        if (guardandoNomina.value) {
return;
}

        if (hasUnsavedComp.value && !pendingNavUrl) {
            event.preventDefault();
            pendingNavUrl = (event.detail.visit.url as URL).href;
            confirm('Tienes una compensación sin guardar. ¿Salir de todas formas?', {
                title: 'Cambios sin guardar',
                confirmLabel: 'Salir sin guardar',
            }).then(ok => {
                if (ok) {
                    const url = pendingNavUrl!;
                    pendingNavUrl = null;
                    router.visit(url);
                } else {
                    pendingNavUrl = null;
                }
            });
        }
    });
});

onUnmounted(() => {
    window.removeEventListener('beforeunload', beforeUnloadHandler);
    removeInertiaListener?.();
});

// ─────────────────────────────────────────────────────────────────────

const setTipoPago = (j: Jornada, valor: unknown, traslapePct?: number) => {
    const tipoPago = valor as TipoPago;
    saving.value = new Set(saving.value).add(j.id);
    router.patch(
        jornadasApi.tipoPago.url(j.id),
        {
            tipo_pago: tipoPago,
            traslape_pct: tipoPago === 'TRASLAPE' ? (traslapePct ?? j.traslape_pct ?? 50) : undefined,
        },
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                const next = new Set(saving.value);
                next.delete(j.id);
                saving.value = next;
            },
        },
    );
};

// Cuando el Select ya está en 'TRASLAPE' y el admin ajusta el % en el input aparte.
const setTraslapePct = (j: Jornada, pct: number) => setTipoPago(j, 'TRASLAPE', pct);

const setFraccionEvento = (j: Jornada, eventoId: number, porcentaje: number) => {
    saving.value = new Set(saving.value).add(j.id);
    router.patch(
        jornadasApi.fraccionEvento.url(j.id),
        { evento_id: eventoId, porcentaje },
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                const next = new Set(saving.value);
                next.delete(j.id);
                saving.value = next;
            },
        },
    );
};

const setFraccionEventoModo = (j: Jornada, eventoId: number, modo: unknown) => {
    if (modo === 'COMPLETO') {
        setFraccionEvento(j, eventoId, 100);

        return;
    }

    const actual = j.fracciones_evento?.[eventoId];
    setFraccionEvento(j, eventoId, actual && actual < 100 ? actual : 50);
};

const setCompensacion = (j: Jornada, activa: boolean) => {
    saving.value = new Set(saving.value).add(j.id);
    router.patch(
        jornadasApi.compensacion.url(j.id),
        { compensacion_activa: activa },
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                const next = new Set(saving.value);
                next.delete(j.id);
                saving.value = next;
            },
        },
    );
};
</script>

<template>
    <Head title="Panel Validación" />

    <div class="flex h-full flex-1 flex-col p-4 sm:p-6">
        <!-- Header -->
        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
            <div>
                <h1 class="text-xl font-semibold sm:text-2xl">Panel de Validación</h1>
                <p class="text-muted-foreground mt-1 text-sm">
                    Revisar y validar jornadas antes de calcular nómina
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <!-- Botón regenerar con indicador de registros sin procesar -->
                <div class="relative">
                    <Button
                        variant="outline"
                        :disabled="generando"
                        @click="regenerarJornadas"
                    >
                        <RefreshCw class="size-4" :class="generando ? 'animate-spin' : ''" />
                        Regenerar jornadas
                    </Button>
                    <span
                        v-if="registrosSinProcesar > 0"
                        class="absolute -right-1.5 -top-1.5 flex size-5 items-center justify-center rounded-full bg-amber-500 text-[10px] font-bold text-white"
                        :title="`${registrosSinProcesar} registro(s) de asistencia sin procesar`"
                    >
                        {{ registrosSinProcesar > 9 ? '9+' : registrosSinProcesar }}
                    </span>
                </div>
                <Button @click="showCalc = true">
                    <Calculator class="size-4" />
                    Calcular nómina
                </Button>
            </div>
        </div>

        <!-- Tabs nav -->
        <div class="mb-4 flex gap-1 border-b overflow-x-auto">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                class="relative flex items-center gap-2 px-4 py-2 text-sm font-medium transition-colors"
                :class="activeTab === tab.key
                    ? 'text-foreground border-b-2 border-primary -mb-px bg-transparent'
                    : 'text-muted-foreground hover:text-foreground'"
                @click="activeTab = tab.key"
            >
                {{ tab.label }}
                <span
                    v-if="pendientesPorTipo(tab.tipo) > 0"
                    class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-amber-100 px-1 text-xs font-semibold text-amber-700 dark:bg-amber-900 dark:text-amber-300"
                >
                    {{ pendientesPorTipo(tab.tipo) }}
                </span>
            </button>
        </div>

        <!-- Progreso de validación + toggle filtros -->
        <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-3">
            <div class="flex items-center gap-1.5 text-sm whitespace-nowrap">
                <span class="font-medium text-amber-600 dark:text-amber-400">{{ totalPendientes }} pendientes</span>
                <span class="text-muted-foreground">·</span>
                <span class="font-medium text-emerald-600 dark:text-emerald-400">{{ totalValidadas }} validadas</span>
                <span class="text-muted-foreground">de {{ jornadasActivas.length }}</span>
            </div>
            <div class="flex flex-1 items-center gap-2 min-w-0">
                <div class="h-2 flex-1 overflow-hidden rounded-full bg-amber-100 dark:bg-amber-950/50">
                    <div
                        class="h-full rounded-full bg-emerald-500 transition-all duration-300 dark:bg-emerald-600"
                        :style="{ width: progresoValidacion + '%' }"
                    />
                </div>
                <span class="text-muted-foreground min-w-[2.5rem] text-right text-xs tabular-nums">
                    {{ progresoValidacion }}%
                </span>
            </div>
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

        <!-- Panel de filtros (colapsable) -->
        <div v-if="mostrarFiltros" class="mb-3 flex flex-wrap items-end gap-3 rounded-lg border bg-muted/30 p-3">
            <div class="w-full space-y-1 sm:w-auto">
                <Label class="text-xs text-muted-foreground">Colaborador</Label>
                <Input
                    v-model="filtros.colaborador"
                    placeholder="Buscar nombre..."
                    class="h-8 w-full sm:w-44 text-xs"
                />
            </div>
            <div class="flex-1 space-y-1 min-w-[120px] sm:flex-initial">
                <Label class="text-xs text-muted-foreground">Fecha desde</Label>
                <Input v-model="filtros.fechaDesde" type="date" class="h-8 w-full text-xs" />
            </div>
            <div class="flex-1 space-y-1 min-w-[120px] sm:flex-initial">
                <Label class="text-xs text-muted-foreground">Fecha hasta</Label>
                <Input v-model="filtros.fechaHasta" type="date" class="h-8 w-full text-xs" />
            </div>
            <div class="w-full space-y-1 sm:w-auto">
                <Label class="text-xs text-muted-foreground">Estado</Label>
                <Select v-model="filtros.estado">
                    <SelectTrigger class="h-8 w-full sm:w-36 text-xs">
                        <SelectValue placeholder="Todas" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="">Todas</SelectItem>
                        <SelectItem value="pendiente">Solo pendientes</SelectItem>
                        <SelectItem value="validada">Solo validadas</SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <div class="w-full space-y-1 sm:w-auto">
                <Label class="text-xs text-muted-foreground">Nómina</Label>
                <Select v-model="filtros.enNomina">
                    <SelectTrigger class="h-8 w-full sm:w-32 text-xs">
                        <SelectValue placeholder="Todas" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="">Todas</SelectItem>
                        <SelectItem value="si">En nómina</SelectItem>
                        <SelectItem value="no">Sin nómina</SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <div class="flex w-full items-end gap-2 pb-0.5 sm:w-auto">
                <span v-if="hayFiltrosActivos" class="text-muted-foreground text-xs">
                    {{ jornadasFiltradas.length }} de {{ jornadasActivas.length }} visibles
                </span>
                <Button
                    v-if="hayFiltrosActivos"
                    size="sm"
                    variant="ghost"
                    class="h-8 gap-1 text-xs text-destructive hover:text-destructive"
                    @click="limpiarFiltros"
                >
                    <X class="size-3" />
                    Limpiar
                </Button>
            </div>
        </div>

        <!-- ─── TAB: BASE ─── -->
        <div v-if="activeTab === 'base'" class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Fecha</th>
                        <th class="px-4 py-3 text-left font-medium">Colaborador</th>
                        <th class="px-4 py-3 text-left font-medium">Entrada</th>
                        <th class="px-4 py-3 text-left font-medium">Salida</th>
                        <th class="px-4 py-3 text-left font-medium">Detalle</th>
                        <th class="px-4 py-3 text-left font-medium">Extras</th>
                        <th class="px-4 py-3 text-left font-medium">Evidencias</th>
                        <th class="px-4 py-3 text-left font-medium w-44">Tipo pago</th>
                        <th class="px-4 py-3 text-center font-medium">Compensación</th>
                        <th class="px-4 py-3 text-center font-medium">Válido</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-if="jornadasFiltradas.length === 0">
                        <td colspan="10" class="text-muted-foreground px-4 py-10 text-center text-sm">
                            <template v-if="jornadasActivas.length === 0">Sin jornadas. Captura asistencia y pulsa "Regenerar jornadas".</template>
                            <template v-else>Ninguna jornada coincide con los filtros aplicados.</template>
                        </td>
                    </tr>
                    <template v-for="(j, idx) in jornadasFiltradas" :key="j.id">
                    <!-- Cabecera de sección: Pendientes -->
                    <tr v-if="idx === 0 && !j.validado" class="bg-amber-50 dark:bg-amber-950/30">
                        <td colspan="10" class="px-4 py-1.5 text-xs font-semibold tracking-wide text-amber-700 dark:text-amber-400">
                            Pendientes de validar — {{ hayFiltrosActivos ? totalPendientesFiltrados : totalPendientes }}
                        </td>
                    </tr>
                    <!-- Cabecera de sección: Validadas -->
                    <tr v-if="j.validado && (idx === 0 || !jornadasFiltradas[idx - 1]?.validado)" class="bg-emerald-50 dark:bg-emerald-950/20">
                        <td colspan="10" class="px-4 py-1.5 text-xs font-semibold tracking-wide text-emerald-700 dark:text-emerald-400">
                            Validadas — {{ hayFiltrosActivos ? totalValidadasFiltradas : totalValidadas }}
                        </td>
                    </tr>
                    <tr
                        class="transition-colors"
                        :class="rowClass(j)"
                    >
                        <td class="px-4 py-3 whitespace-nowrap font-medium">
                            {{ fmtFecha(j.fecha) }}
                            <span
                                v-if="j.nomina_estado"
                                class="mt-0.5 block rounded px-1.5 py-0.5 text-[10px] font-medium"
                                :class="nominaBadgeClass(j.nomina_estado)"
                                :title="nominaBadgeTitle(j.nomina_estado)"
                            >
                                {{ j.nomina_estado === 'PAGADO' ? '✓ Nómina pagada' : '· En nómina' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            {{ j.colaborador.apellidos }}, {{ j.colaborador.nombre }}
                        </td>
                        <td class="text-muted-foreground px-4 py-3 whitespace-nowrap tabular-nums">
                            {{ j.entrada ? j.entrada.slice(0, 5) : '—' }}
                        </td>
                        <td class="text-muted-foreground px-4 py-3 whitespace-nowrap tabular-nums">
                            {{ j.salida ? j.salida.slice(0, 5) : '—' }}
                        </td>
                        <td class="px-4 py-3 max-w-xs">
                            <pre class="whitespace-pre-wrap font-sans text-xs leading-relaxed">{{ j.detalle ?? '—' }}</pre>
                            <!-- 2+ eventos el mismo día: el desempeño puede variar por evento,
                                 así que cada uno se pondera por separado en vez del tipo_pago único del día. -->
                            <div v-if="j.eventos_dia.length >= 2" class="mt-1.5 space-y-1 border-t pt-1.5">
                                <div v-for="ev in j.eventos_dia" :key="ev.id" class="flex items-center justify-between gap-2">
                                    <span class="text-muted-foreground truncate text-xs" :title="ev.nombre">{{ ev.nombre }}</span>
                                    <div class="flex shrink-0 items-center gap-1">
                                        <Select
                                            :model-value="fraccionEventoModo(j.fracciones_evento?.[ev.id] ?? 100)"
                                            :disabled="saving.has(j.id)"
                                            @update:model-value="(v) => setFraccionEventoModo(j, ev.id, v)"
                                        >
                                            <SelectTrigger class="h-7 w-28 text-xs">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem
                                                    v-for="op in fraccionEventoModoOpciones"
                                                    :key="op.value"
                                                    :value="op.value"
                                                    class="text-xs"
                                                >
                                                    {{ op.label }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <Input
                                            v-if="fraccionEventoModo(j.fracciones_evento?.[ev.id] ?? 100) === 'TRASLAPE'"
                                            type="number"
                                            min="1"
                                            max="99"
                                            :model-value="j.fracciones_evento?.[ev.id] ?? 50"
                                            :disabled="saving.has(j.id)"
                                            class="h-7 w-14 text-xs"
                                            @change="(e: Event) => setFraccionEvento(j, ev.id, Number((e.target as HTMLInputElement).value))"
                                        />
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="text-muted-foreground px-4 py-3 text-xs max-w-[8rem]">
                            {{ j.extras ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1">
                                <button
                                    v-for="(url, i) in j.evidencia_urls"
                                    :key="i"
                                    type="button"
                                    @click="lightboxUrl = url"
                                >
                                    <img
                                        :src="url"
                                        alt="Evidencia"
                                        class="size-9 rounded object-cover ring-1 ring-border hover:ring-primary transition-all cursor-zoom-in"
                                    />
                                </button>
                                <span v-if="j.evidencia_urls.length === 0" class="text-muted-foreground text-xs">—</span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1">
                                <Select
                                    :model-value="j.tipo_pago"
                                    :disabled="saving.has(j.id)"
                                    @update:model-value="(v) => setTipoPago(j, v)"
                                >
                                    <SelectTrigger class="h-8 w-full text-xs" :class="tipoPagoBadgeClass(j.tipo_pago)">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="op in tipoPagoOpcionesBase(j)"
                                            :key="op.value"
                                            :value="op.value"
                                            class="text-xs"
                                        >
                                            {{ op.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <Input
                                    v-if="j.tipo_pago === 'TRASLAPE'"
                                    type="number"
                                    min="1"
                                    max="99"
                                    :model-value="j.traslape_pct ?? 50"
                                    :disabled="saving.has(j.id)"
                                    class="h-8 w-14 shrink-0 text-xs"
                                    @change="(e: Event) => setTraslapePct(j, Number((e.target as HTMLInputElement).value))"
                                />
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <Checkbox
                                :model-value="j.compensacion_activa"
                                :disabled="saving.has(j.id)"
                                :title="!j.colaborador.compensacion_pct ? 'Este colaborador no tiene % de compensación asignado en Colaboradores' : undefined"
                                @update:model-value="(v) => setCompensacion(j, v === true)"
                            />
                        </td>
                        <td class="px-4 py-3 text-center">
                            <Checkbox
                                :model-value="j.validado"
                                :disabled="saving.has(j.id)"
                                @update:model-value="(v) => setValidado(j, v === true)"
                            />
                        </td>
                    </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- ─── TAB: FREELANCE ─── -->
        <div v-else-if="activeTab === 'freelance'" class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Fecha</th>
                        <th class="px-4 py-3 text-left font-medium">Colaborador</th>
                        <th class="px-4 py-3 text-left font-medium">Evento / Detalle</th>
                        <th class="px-4 py-3 text-left font-medium">Extras</th>
                        <th class="px-4 py-3 text-left font-medium">Evidencias</th>
                        <th class="px-4 py-3 text-left font-medium w-44">Tipo pago</th>
                        <th class="px-4 py-3 text-center font-medium">Válido</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-if="jornadasFiltradas.length === 0">
                        <td colspan="7" class="text-muted-foreground px-4 py-10 text-center text-sm">
                            <template v-if="jornadasActivas.length === 0">Sin jornadas para colaboradores freelance.</template>
                            <template v-else>Ninguna jornada coincide con los filtros aplicados.</template>
                        </td>
                    </tr>
                    <template v-for="(j, idx) in jornadasFiltradas" :key="j.id">
                    <tr v-if="idx === 0 && !j.validado" class="bg-amber-50 dark:bg-amber-950/30">
                        <td colspan="7" class="px-4 py-1.5 text-xs font-semibold tracking-wide text-amber-700 dark:text-amber-400">
                            Pendientes de validar — {{ hayFiltrosActivos ? totalPendientesFiltrados : totalPendientes }}
                        </td>
                    </tr>
                    <tr v-if="j.validado && (idx === 0 || !jornadasFiltradas[idx - 1]?.validado)" class="bg-emerald-50 dark:bg-emerald-950/20">
                        <td colspan="7" class="px-4 py-1.5 text-xs font-semibold tracking-wide text-emerald-700 dark:text-emerald-400">
                            Validadas — {{ hayFiltrosActivos ? totalValidadasFiltradas : totalValidadas }}
                        </td>
                    </tr>
                    <tr
                        class="transition-colors"
                        :class="rowClass(j)"
                    >
                        <td class="px-4 py-3 whitespace-nowrap font-medium">
                            {{ fmtFecha(j.fecha) }}
                            <span
                                v-if="j.nomina_estado"
                                class="mt-0.5 block rounded px-1.5 py-0.5 text-[10px] font-medium"
                                :class="nominaBadgeClass(j.nomina_estado)"
                                :title="nominaBadgeTitle(j.nomina_estado)"
                            >
                                {{ j.nomina_estado === 'PAGADO' ? '✓ Nómina pagada' : '· En nómina' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            {{ j.colaborador.apellidos }}, {{ j.colaborador.nombre }}
                        </td>
                        <td class="px-4 py-3 max-w-xs">
                            <pre class="whitespace-pre-wrap font-sans text-xs leading-relaxed">{{ j.detalle ?? '—' }}</pre>
                        </td>
                        <td class="text-muted-foreground px-4 py-3 text-xs max-w-[8rem]">
                            {{ j.extras ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1">
                                <button
                                    v-for="(url, i) in j.evidencia_urls"
                                    :key="i"
                                    type="button"
                                    @click="lightboxUrl = url"
                                >
                                    <img
                                        :src="url"
                                        alt="Evidencia"
                                        class="size-9 rounded object-cover ring-1 ring-border hover:ring-primary transition-all cursor-zoom-in"
                                    />
                                </button>
                                <span v-if="j.evidencia_urls.length === 0" class="text-muted-foreground text-xs">—</span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <Select
                                :model-value="j.tipo_pago"
                                :disabled="saving.has(j.id)"
                                @update:model-value="(v) => setTipoPago(j, v)"
                            >
                                <SelectTrigger class="h-8 w-full text-xs" :class="tipoPagoBadgeClass(j.tipo_pago)">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="op in tipoPagoOpcionesFreelance"
                                        :key="op.value"
                                        :value="op.value"
                                        class="text-xs"
                                    >
                                        {{ op.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <Checkbox
                                :model-value="j.validado"
                                :disabled="saving.has(j.id)"
                                @update:model-value="(v) => setValidado(j, v === true)"
                            />
                        </td>
                    </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- ─── TAB: CONDUCTORES ─── -->
        <div v-else-if="activeTab === 'conductores'" class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Fecha</th>
                        <th class="px-4 py-3 text-left font-medium">Conductor</th>
                        <th class="px-4 py-3 text-left font-medium">Ruta</th>
                        <th class="px-4 py-3 text-left font-medium">Extras</th>
                        <th class="px-4 py-3 text-left font-medium">Comentarios</th>
                        <th class="px-4 py-3 text-left font-medium w-40">Tipo pago</th>
                        <th class="px-4 py-3 text-center font-medium">Válido</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-if="jornadasFiltradas.length === 0">
                        <td colspan="7" class="text-muted-foreground px-4 py-10 text-center text-sm">
                            <template v-if="jornadasActivas.length === 0">Sin jornadas para conductores.</template>
                            <template v-else>Ninguna jornada coincide con los filtros aplicados.</template>
                        </td>
                    </tr>
                    <template v-for="(j, idx) in jornadasFiltradas" :key="j.id">
                    <tr v-if="idx === 0 && !j.validado" class="bg-amber-50 dark:bg-amber-950/30">
                        <td colspan="7" class="px-4 py-1.5 text-xs font-semibold tracking-wide text-amber-700 dark:text-amber-400">
                            Pendientes de validar — {{ hayFiltrosActivos ? totalPendientesFiltrados : totalPendientes }}
                        </td>
                    </tr>
                    <tr v-if="j.validado && (idx === 0 || !jornadasFiltradas[idx - 1]?.validado)" class="bg-emerald-50 dark:bg-emerald-950/20">
                        <td colspan="7" class="px-4 py-1.5 text-xs font-semibold tracking-wide text-emerald-700 dark:text-emerald-400">
                            Validadas — {{ hayFiltrosActivos ? totalValidadasFiltradas : totalValidadas }}
                        </td>
                    </tr>
                    <tr
                        class="transition-colors"
                        :class="rowClass(j)"
                    >
                        <td class="px-4 py-3 whitespace-nowrap font-medium">
                            {{ fmtFecha(j.fecha) }}
                            <span
                                v-if="j.nomina_estado"
                                class="mt-0.5 block rounded px-1.5 py-0.5 text-[10px] font-medium"
                                :class="nominaBadgeClass(j.nomina_estado)"
                                :title="nominaBadgeTitle(j.nomina_estado)"
                            >
                                {{ j.nomina_estado === 'PAGADO' ? '✓ Nómina pagada' : '· En nómina' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            {{ j.colaborador.apellidos }}, {{ j.colaborador.nombre }}
                        </td>
                        <td class="px-4 py-3 max-w-xs">
                            <pre class="whitespace-pre-wrap font-sans text-xs leading-relaxed">{{ j.detalle ?? '—' }}</pre>
                        </td>
                        <td class="text-muted-foreground px-4 py-3 text-xs max-w-[6rem]">
                            {{ j.extras ?? '—' }}
                        </td>
                        <td class="text-muted-foreground px-4 py-3 text-xs max-w-[8rem]">
                            {{ j.comentarios ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1">
                                <Select
                                    :model-value="j.tipo_pago"
                                    :disabled="saving.has(j.id)"
                                    @update:model-value="(v) => setTipoPago(j, v)"
                                >
                                    <SelectTrigger class="h-8 w-full text-xs" :class="tipoPagoBadgeClass(j.tipo_pago)">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="op in tipoPagoOpcionesConductor"
                                            :key="op.value"
                                            :value="op.value"
                                            class="text-xs"
                                        >
                                            {{ op.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <Input
                                    v-if="j.tipo_pago === 'TRASLAPE'"
                                    type="number"
                                    min="1"
                                    max="99"
                                    :model-value="j.traslape_pct ?? 50"
                                    :disabled="saving.has(j.id)"
                                    class="h-8 w-14 shrink-0 text-xs"
                                    @change="(e: Event) => setTraslapePct(j, Number((e.target as HTMLInputElement).value))"
                                />
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <Checkbox
                                :model-value="j.validado"
                                :disabled="saving.has(j.id)"
                                @update:model-value="(v) => setValidado(j, v === true)"
                            />
                        </td>
                    </tr>
                    </template>
                    </tbody>
                </table>
        </div>
        <div v-else-if="activeTab === 'conductor_base'" class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Fecha</th>
                        <th class="px-4 py-3 text-left font-medium">Colaborador</th>
                        <th class="px-4 py-3 text-left font-medium">Actividad (Bodega/Ruta)</th>
                        <th class="px-4 py-3 text-left font-medium">Extras</th>
                        <th class="px-4 py-3 text-left font-medium">Comentarios</th>
                        <th class="px-4 py-3 text-left font-medium w-40">Tipo pago</th>
                        <th class="px-4 py-3 text-center font-medium">Válido</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-if="jornadasFiltradas.length === 0">
                        <td colspan="7" class="text-muted-foreground px-4 py-10 text-center text-sm">
                            <template v-if="jornadasActivas.length === 0">Sin jornadas para conductores base.</template>
                            <template v-else>Ninguna jornada coincide con los filtros aplicados.</template>
                        </td>
                    </tr>
                    <template v-for="(j, idx) in jornadasFiltradas" :key="j.id">
                    <tr v-if="idx === 0 && !j.validado" class="bg-amber-50 dark:bg-amber-950/30">
                        <td colspan="7" class="px-4 py-1.5 text-xs font-semibold tracking-wide text-amber-700 dark:text-amber-400">
                            Pendientes de validar — {{ hayFiltrosActivos ? totalPendientesFiltrados : totalPendientes }}
                        </td>
                    </tr>
                    <tr v-if="j.validado && (idx === 0 || !jornadasFiltradas[idx - 1]?.validado)" class="bg-emerald-50 dark:bg-emerald-950/20">
                        <td colspan="7" class="px-4 py-1.5 text-xs font-semibold tracking-wide text-emerald-700 dark:text-emerald-400">
                            Validadas — {{ hayFiltrosActivos ? totalValidadasFiltradas : totalValidadas }}
                        </td>
                    </tr>
                    <tr
                        class="transition-colors"
                        :class="rowClass(j)"
                    >
                        <td class="px-4 py-3 whitespace-nowrap font-medium">
                            {{ fmtFecha(j.fecha) }}
                            <span
                                v-if="j.nomina_estado"
                                class="mt-0.5 block rounded px-1.5 py-0.5 text-[10px] font-medium"
                                :class="nominaBadgeClass(j.nomina_estado)"
                                :title="nominaBadgeTitle(j.nomina_estado)"
                            >
                                {{ j.nomina_estado === 'PAGADO' ? '✓ Nómina pagada' : '· En nómina' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            {{ j.colaborador.apellidos }}, {{ j.colaborador.nombre }}
                        </td>
                        <td class="px-4 py-3 max-w-xs">
                            <pre class="whitespace-pre-wrap font-sans text-xs leading-relaxed">{{ j.detalle ?? '—' }}</pre>
                        </td>
                        <td class="text-muted-foreground px-4 py-3 text-xs max-w-[6rem]">
                            {{ j.extras ?? '—' }}
                        </td>
                        <td class="text-muted-foreground px-4 py-3 text-xs max-w-[8rem]">
                            {{ j.comentarios ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1">
                                <Select
                                    :model-value="j.tipo_pago"
                                    :disabled="saving.has(j.id)"
                                    @update:model-value="(v) => setTipoPago(j, v)"
                                >
                                    <SelectTrigger class="h-8 w-full text-xs" :class="tipoPagoBadgeClass(j.tipo_pago)">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="op in tipoPagoOpcionesBase(j)"
                                            :key="op.value"
                                            :value="op.value"
                                            class="text-xs"
                                        >
                                            {{ op.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <Input
                                    v-if="j.tipo_pago === 'TRASLAPE'"
                                    type="number"
                                    min="1"
                                    max="99"
                                    :model-value="j.traslape_pct ?? 50"
                                    :disabled="saving.has(j.id)"
                                    class="h-8 w-14 shrink-0 text-xs"
                                    @change="(e: Event) => setTraslapePct(j, Number((e.target as HTMLInputElement).value))"
                                />
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <Checkbox
                                :model-value="j.validado"
                                :disabled="saving.has(j.id)"
                                @update:model-value="(v) => setValidado(j, v === true)"
                            />
                        </td>
                    </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ─── Dialog: Calcular nómina ─── -->
    <Dialog :open="showCalc" @update:open="handleCierreCalc">
        <DialogContent class="max-w-12xl sm:max-w-7xl max-h-[92vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2 text-lg">
                    <DollarSign class="size-5" />
                    Calcular nómina
                </DialogTitle>
            </DialogHeader>

            <div class="grid gap-6 py-2 md:grid-cols-2">
                <div class="space-y-4 max-h-[60vh] overflow-y-auto md:max-h-none md:overflow-visible pr-1">
                <!-- Selección de colaborador -->
                <div class="space-y-1">
                    <Label class="text-sm">Colaborador</Label>
                    <Select v-model="calcParams.colaborador_id">
                        <SelectTrigger>
                            <SelectValue placeholder="Seleccionar colaborador..." />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="c in colaboradores"
                                :key="c.id"
                                :value="String(c.id)"
                            >
                                {{ c.apellidos }}, {{ c.nombre }}
                                <span class="text-muted-foreground ml-1 text-sm">
                                    ({{ c.tipo === 'COLABORADOR BASE' ? 'Base' : c.tipo === 'FREELANCE' ? 'Freelance' : c.tipo === 'CONDUCTOR' ? 'Conductor' : 'Conductor base' }})
                                </span>
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <!-- Parámetros según tipo -->
                <template v-if="tipoSeleccionado === 'COLABORADOR BASE' || tipoSeleccionado === 'CONDUCTOR' || tipoSeleccionado === 'CONDUCTOR BASE'">
                    <div class="flex flex-wrap items-center gap-2">
                        <Button type="button" size="sm" variant="outline" class="h-8 text-sm" @click="usarSemanaActual">
                            Semana actual
                        </Button>
                        <Button type="button" size="sm" variant="outline" class="h-8 text-sm" @click="usarSemanaAnterior">
                            Semana anterior
                        </Button>
                        <div class="flex items-center gap-1 border-l pl-2">
                            <Button
                                type="button" size="sm" variant="outline" class="h-8 text-sm"
                                :disabled="!puedeReducirSemana"
                                @click="extenderSemana(-1)"
                            >
                                -1 semana
                            </Button>
                            <Button type="button" size="sm" variant="outline" class="h-8 text-sm" @click="extenderSemana(1)">
                                +1 semana
                            </Button>
                        </div>
                        <span class="text-muted-foreground text-sm">Corte semanal: lunes a sábado (puede abarcar varias semanas)</span>
                    </div>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="space-y-1">
                            <Label class="text-sm">Período inicio</Label>
                            <Input v-model="calcParams.inicio" type="date" />
                        </div>
                        <div class="space-y-1">
                            <Label class="text-sm">Período fin</Label>
                            <Input v-model="calcParams.fin" type="date" />
                        </div>
                    </div>
                    <p v-if="semanaIncompleta" class="rounded-md bg-amber-50 px-3 py-2 text-sm text-amber-700 dark:bg-amber-950/40 dark:text-amber-400">
                        Este período no respeta el corte semanal (lunes a sábado): debe iniciar en lunes y terminar en sábado, aunque abarque varias semanas — revisa las fechas si no es intencional.
                    </p>
                </template>

                <template v-else-if="tipoSeleccionado === 'FREELANCE'">
                    <div class="space-y-1">
                        <Label class="text-sm">Evento</Label>
                        <Select v-model="calcParams.evento_id" :disabled="cargandoFreelance">
                            <SelectTrigger>
                                <SelectValue :placeholder="cargandoFreelance ? 'Cargando eventos...' : 'Seleccionar evento...'" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="e in freelanceEventos"
                                    :key="e.id"
                                    :value="String(e.id)"
                                >
                                    {{ e.nombre }}
                                    <span class="text-muted-foreground ml-1 text-sm">{{ e.tamano }}</span>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="!cargandoFreelance && calcParams.colaborador_id && freelanceEventos.length === 0" class="text-muted-foreground text-sm">
                            Este colaborador no tiene asistencia registrada en ningún evento.
                        </p>
                    </div>

                    <!-- Actividades registradas del evento (editables) -->
                    <div v-if="calcParams.evento_id" class="space-y-1.5">
                        <Label class="text-sm">Actividades registradas</Label>
                        <div class="rounded-lg border divide-y">
                            <div
                                v-for="r in registrosEventoSeleccionado"
                                :key="r.id"
                                class="flex flex-wrap items-center gap-2 p-2 text-sm"
                            >
                                <span class="font-medium tabular-nums whitespace-nowrap">{{ fmtFecha(r.fecha) }}</span>
                                <div class="flex items-center gap-2">
                                    <label
                                        v-for="et in ETAPAS"
                                        :key="et"
                                        class="flex items-center gap-1 whitespace-nowrap"
                                    >
                                        <Checkbox
                                            :model-value="etapasDeRegistro(r).includes(et)"
                                            :disabled="guardandoRegistro.has(r.id)"
                                            @update:model-value="() => toggleEtapaRegistro(r, et)"
                                        />
                                        {{ et }}
                                    </label>
                                </div>
                                <Input
                                    :model-value="r.comentarios ?? ''"
                                    :disabled="guardandoRegistro.has(r.id)"
                                    placeholder="Comentarios"
                                    class="h-8 flex-1 min-w-32 text-sm"
                                    @change="(e: Event) => { r.comentarios = (e.target as HTMLInputElement).value; guardarRegistroEvento(r); }"
                                />
                                <span
                                    class="rounded px-1.5 py-0.5 text-xs font-medium whitespace-nowrap"
                                    :class="r.jornada_validada
                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-400'
                                        : 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-400'"
                                >
                                    {{ r.jornada_validada ? 'Validada' : 'Sin validar' }}
                                </span>
                            </div>
                            <p v-if="registrosEventoSeleccionado.length === 0" class="text-muted-foreground p-3 text-center text-sm">
                                Sin registros de asistencia para este evento.
                            </p>
                        </div>
                        <p class="text-muted-foreground text-sm">
                            Solo las etapas de jornadas ya validadas en el Panel de Validación cuentan para el % de pago. Si corriges algo aquí, revalida la jornada correspondiente.
                        </p>
                    </div>

                    <div class="space-y-1">
                        <Label class="text-sm">Días adicionales</Label>
                        <Input v-model.number="calcParams.dias_adicionales" type="number" min="0" />
                    </div>
                </template>

                <!-- Botón calcular -->
                <Button
                    :disabled="!calcParams.colaborador_id || calculando"
                    @click="calcularNomina"
                >
                    <Calculator class="size-4" :class="calculando ? 'animate-spin' : ''" />
                    {{ calculando ? 'Calculando...' : 'Calcular' }}
                </Button>

                <!-- Error -->
                <p v-if="calcError" class="text-destructive text-sm">{{ calcError }}</p>
                </div>

                <!-- ─── Desglose ─── -->
                <div class="space-y-4 max-h-[60vh] overflow-y-auto md:max-h-none md:overflow-visible pr-1">
                <template v-if="desglose">
                    <!-- Nómina ya pagada -->
                    <div
                        v-if="desglose.estado === 'PAGADO'"
                        class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800 dark:bg-emerald-950/30"
                    >
                        <p class="font-semibold text-emerald-700 dark:text-emerald-400">
                            Esta nómina ya fue pagada y no puede modificarse.
                        </p>
                        <p class="text-muted-foreground mt-1 text-sm">
                            Total pagado: <strong>${{ desglose.total_final.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</strong>
                        </p>
                    </div>

                    <!-- Desglose completo -->
                    <template v-else>
                        <div class="rounded-lg border bg-muted/30 p-4 space-y-2 text-base">
                            <h3 class="font-semibold mb-3 text-lg">Desglose</h3>

                            <!-- BASE -->
                            <template v-if="desglose.tipo === 'COLABORADOR BASE'">
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Días trabajados</span>
                                    <span class="tabular-nums">{{ desglose.dias }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Sueldo diario</span>
                                    <span class="tabular-nums">${{ Number(desglose.sueldo_diario).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Total base</span>
                                    <span class="tabular-nums">${{ desglose.total_base.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Bonos evento</span>
                                    <span class="tabular-nums">${{ (desglose._bonos_evento_puro ?? 0).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Bono 7° día</span>
                                    <span class="tabular-nums">${{ (desglose._bono_septimo ?? 0).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
                                </div>
                            </template>

                            <!-- FREELANCE -->
                            <template v-else-if="desglose.tipo === 'FREELANCE'">
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Pago evento ({{ desglose._porcentaje }}%)</span>
                                    <span class="tabular-nums">${{ (desglose._pago_base ?? 0).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
                                </div>
                                <div v-if="(desglose._pago_extras ?? 0) > 0" class="flex justify-between">
                                    <span class="text-muted-foreground">Extras días adicionales</span>
                                    <span class="tabular-nums">${{ (desglose._pago_extras ?? 0).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
                                </div>
                            </template>

                            <!-- CONDUCTOR -->
                            <template v-else-if="desglose.tipo === 'CONDUCTOR'">
                                <div class="flex justify-between font-medium">
                                    <span>Total rutas</span>
                                    <span class="tabular-nums">${{ (desglose._total_rutas ?? 0).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
                                </div>
                            </template>

                            <!-- CONDUCTOR BASE -->
                            <template v-else-if="desglose.tipo === 'CONDUCTOR BASE'">
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Días trabajados</span>
                                    <span class="tabular-nums">{{ desglose.dias }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Sueldo diario</span>
                                    <span class="tabular-nums">${{ Number(desglose.sueldo_diario).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Total base (días)</span>
                                    <span class="tabular-nums">${{ desglose.total_base.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Total rutas</span>
                                    <span class="tabular-nums">${{ (desglose._total_rutas ?? 0).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Bono 7° día</span>
                                    <span class="tabular-nums">${{ (desglose._bono_septimo ?? 0).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
                                </div>
                            </template>

                            <!-- Resumen colapsable de lo evaluado -->
                            <Collapsible v-model:open="mostrarResumenEvaluado" class="rounded-lg border bg-background">
                                <CollapsibleTrigger class="flex w-full items-center justify-between px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground">
                                    <span>Ver detalle de lo evaluado</span>
                                    <ChevronDown class="size-4 transition-transform" :class="mostrarResumenEvaluado ? 'rotate-180' : ''" />
                                </CollapsibleTrigger>
                                <CollapsibleContent class="space-y-2 border-t px-2 py-2 text-sm max-h-48 overflow-y-auto sm:px-3">
                                    <!-- BASE: jornadas del período -->
                                    <template v-if="desglose.tipo === 'COLABORADOR BASE'">
                                        <p v-if="!desglose._categoria || !desglose._nivel" class="rounded bg-amber-50 px-2 py-1.5 text-xs text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 sm:text-sm">
                                            Este colaborador no tiene categoría y/o nivel asignado — el extra de evento se calcula en $0. Asígnalos en Colaboradores.
                                        </p>
                                        <div v-for="j in desglose._jornadas ?? []" :key="j.fecha" class="rounded-md border bg-card p-2 sm:flex sm:items-center sm:justify-between sm:gap-3 sm:p-3">
                                            <div class="min-w-0 space-y-1 sm:flex-1">
                                                <div class="flex items-center gap-2">
                                                    <span class="tabular-nums font-medium shrink-0">{{ fmtFecha(j.fecha) }}</span>
                                                    <span class="rounded px-1.5 py-0.5 text-xs font-medium" :class="tipoPagoBadgeClass(j.tipo_pago)">
                                                        {{ tipoPagoLabel(j.tipo_pago, j.traslape_pct) }}
                                                    </span>
                                                    <span class="tabular-nums font-medium text-xs text-muted-foreground sm:text-sm">
                                                        +${{ (Number(desglose.sueldo_diario) * (j.dia_fraccion ?? 1)).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
                                                    </span>
                                                </div>
                                                <p v-if="detalleSinEventos(j.detalle)" class="text-muted-foreground text-xs leading-relaxed sm:text-sm">{{ detalleSinEventos(j.detalle) }}</p>
                                                <template v-for="ev in j.eventos_dia ?? []" :key="ev.nombre">
                                                    <p class="text-xs text-muted-foreground sm:text-sm">
                                                        {{ ev.nombre }} ({{ ev.tamano }} - {{ ev.pct_etapas }}%<template v-if="fraccionEventoLabel(ev.fraccion)"> · {{ fraccionEventoLabel(ev.fraccion) }}</template>): +${{ ev.bono.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}<span v-if="ev.compensacion > 0" class="text-emerald-600 dark:text-emerald-500"> (incl. compensación +${{ ev.compensacion.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }})</span>
                                                    </p>
                                                </template>
                                                <p v-if="j.extras" class="text-xs text-amber-600 dark:text-amber-500 sm:text-sm">Extra: {{ j.extras }}</p>
                                            </div>
                                        </div>
                                        <p v-if="(desglose._jornadas ?? []).length === 0" class="text-muted-foreground">Sin jornadas validadas en este período.</p>
                                        <p v-if="desglose._categoria" class="text-xs text-muted-foreground sm:text-sm">
                                            Categoría: {{ desglose._categoria }}<template v-if="desglose._nivel"> · Nivel {{ desglose._nivel }}</template> (el extra por día de evento varía según el tamaño de cada evento — Chico no genera extra)
                                        </p>
                                    </template>

                                    <!-- FREELANCE: registros del evento (validados y sin validar) -->
                                    <template v-else-if="desglose.tipo === 'FREELANCE'">
                                        <div v-for="(r, i) in desglose._registros ?? []" :key="i" class="rounded-md border bg-card p-2 sm:flex sm:items-center sm:justify-between sm:gap-3 sm:p-3">
                                            <div class="min-w-0 space-y-1 sm:flex-1">
                                                <div class="flex items-center gap-2">
                                                    <span class="tabular-nums font-medium shrink-0">{{ fmtFecha(r.fecha) }}</span>
                                                    <span
                                                        class="rounded px-1.5 py-0.5 text-xs font-medium"
                                                        :class="r.contabiliza
                                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-400'
                                                            : 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-400'"
                                                    >
                                                        {{ r.contabiliza ? 'Contabilizada' : 'Sin validar' }}
                                                    </span>
                                                </div>
                                                <p class="text-muted-foreground text-xs sm:text-sm">{{ r.etapa ?? '—' }}</p>
                                                <p v-if="r.extras" class="text-xs text-amber-600 dark:text-amber-500 sm:text-sm">Extra: {{ r.extras }}</p>
                                            </div>
                                        </div>
                                        <p v-if="(desglose._registros ?? []).length === 0" class="text-muted-foreground">Sin registros de asistencia para este evento.</p>
                                    </template>

                                    <!-- CONDUCTOR: rutas detectadas -->
                                    <template v-if="desglose.tipo === 'CONDUCTOR'">
                                        <div v-for="(ruta, i) in desglose._rutas ?? []" :key="i" class="flex min-w-0 flex-col rounded-md border bg-card p-2 sm:flex sm:items-center sm:justify-between sm:gap-3 sm:p-3">
                                            <div class="min-w-0 space-y-1 sm:flex-1">
                                                <div class="flex items-center gap-2">
                                                    <span class="tabular-nums font-medium shrink-0">{{ fmtFecha(ruta.fecha) }}</span>
                                                    <span class="tabular-nums font-medium text-xs sm:text-sm">${{ ruta.monto.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
                                                </div>
                                                <p class="text-muted-foreground text-xs sm:text-sm">{{ ruta.vehiculo }} · {{ ruta.distancia }}</p>
                                                <p v-if="ruta.extras" class="text-xs text-amber-600 dark:text-amber-500 sm:text-sm">Extra: {{ ruta.extras }}</p>
                                            </div>
                                        </div>
                                        <p v-if="(desglose._rutas ?? []).length === 0" class="text-muted-foreground">Sin rutas detectadas en este período.</p>
                                    </template>

                                    <!-- CONDUCTOR BASE: jornadas + rutas del período -->
                                    <template v-else-if="desglose.tipo === 'CONDUCTOR BASE'">
                                        <div v-for="j in desglose._jornadas ?? []" :key="j.fecha" class="rounded-md border bg-card p-2 sm:p-3">
                                            <div class="min-w-0 space-y-1 sm:flex-1">
                                                <div class="flex items-center gap-2">
                                                    <span class="tabular-nums font-medium shrink-0">{{ fmtFecha(j.fecha) }}</span>
                                                    <span class="rounded px-1.5 py-0.5 text-xs font-medium" :class="tipoPagoBadgeClass(j.tipo_pago)">
                                                        {{ tipoPagoLabel(j.tipo_pago, j.traslape_pct) }}
                                                    </span>
                                                    <span class="tabular-nums font-medium text-xs sm:text-sm">
                                                        +${{ Number(desglose.sueldo_diario).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
                                                    </span>
                                                    <span v-if="rutasDe(j.fecha).reduce((acc, r) => acc + (r.monto ?? 0), 0) > 0" class="tabular-nums font-medium text-xs text-emerald-600 dark:text-emerald-500 sm:text-sm">
                                                        Transporte: +${{ rutasDe(j.fecha).reduce((acc, r) => acc + (r.monto ?? 0), 0).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
                                                    </span>
                                                </div>
                                                <p v-if="detalleSinEventos(j.detalle)" class="text-muted-foreground text-xs leading-relaxed sm:text-sm">{{ detalleSinEventos(j.detalle) }}</p>
                                                <p v-for="r in rutasDe(j.fecha)" :key="`${r.vehiculo}·${r.distancia}·${j.fecha}`" class="text-xs text-muted-foreground sm:text-sm">
                                                    Ruta: {{ r.vehiculo }} · {{ r.distancia }}
                                                    <span class="tabular-nums font-medium">(${{ r.monto.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }})</span>
                                                </p>
                                                <p v-if="j.extras" class="text-xs text-amber-600 dark:text-amber-500 sm:text-sm">Extra: {{ j.extras }}</p>
                                            </div>
                                        </div>
                                        <p v-if="(desglose._jornadas ?? []).length === 0" class="text-muted-foreground">Sin jornadas validadas en este período.</p>
                                    </template>
                                </CollapsibleContent>
                            </Collapsible>

                            <hr class="border-border my-2" />

                            <div v-if="calcParams.compensacion !== 0" class="flex items-center justify-between">
                                <span class="text-muted-foreground">{{ esDescuento ? 'Descuento manual' : 'Compensación manual' }}</span>
                                <span
                                    class="tabular-nums font-medium"
                                    :class="esDescuento ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-500'"
                                >
                                    {{ fmtSigned(calcParams.compensacion) }}
                                </span>
                            </div>

                            <div class="flex justify-between text-red-600 dark:text-red-400">
                                <span>Anticipos</span>
                                <span class="tabular-nums">-${{ desglose.anticipos.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
                            </div>

                            <template v-if="desglose.prestamos">
                                <div class="flex justify-between text-red-600 dark:text-red-400">
                                    <span>Préstamos</span>
                                    <span class="tabular-nums">-${{ desglose.prestamos.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
                                </div>
                                <div class="space-y-1 rounded-lg border p-2">
                                    <div
                                        v-for="c in desglose._prestamo_detalle ?? []"
                                        :key="c.id"
                                        class="flex items-center gap-2 rounded-md px-1.5 py-1 text-xs"
                                        :class="plazosSel.has(c.id) ? 'bg-red-100/70 dark:bg-red-950/40' : 'hover:bg-muted/50'"
                                    >
                                        <Checkbox :model-value="plazosSel.has(c.id)" @update:model-value="() => togglePlazo(c.id)" />
                                        <span class="flex-1 text-muted-foreground">
                                            Plazo {{ c.numero_plazo }}<template v-if="c.concepto"> ({{ c.concepto }})</template>
                                            <span class="tabular-nums"> · {{ fmtFecha(c.fecha_programada) }}</span>
                                        </span>
                                        <span class="font-medium tabular-nums">-${{ c.monto.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
                                    </div>
                                    <p v-if="(desglose._prestamo_detalle ?? []).length === 0" class="text-muted-foreground px-1.5 py-1 text-xs">
                                        Sin plazos de préstamo en este período.
                                    </p>
                                    <div class="mt-1 flex flex-wrap items-center gap-2 border-t px-1.5 pt-2">
                                        <Button
                                            size="sm" variant="outline" class="h-7 text-xs"
                                            :disabled="plazosSel.size === 0"
                                            @click="abrirAplazar"
                                        >
                                            <CalendarClock class="size-3.5" />
                                            Aplazar
                                        </Button>
                                        <Button
                                            size="sm" variant="outline" class="h-7 text-xs"
                                            :disabled="plazosSel.size === 0 || selPlazosMultiPrestamo"
                                            :title="selPlazosMultiPrestamo ? 'Selecciona plazos de un solo préstamo' : undefined"
                                            @click="distribuirCarga"
                                        >
                                            <HandCoins class="size-3.5" />
                                            Distribuir carga
                                        </Button>
                                        <span class="text-muted-foreground text-[11px]">
                                            Aplazar cambia la fecha del plazo; distribuir reparte su monto entre los plazos restantes del mismo préstamo.
                                        </span>
                                    </div>
                                </div>
                            </template>

                            <!-- Actividades extra registradas: no se pagan automáticamente -->
                            <div
                                v-if="extrasDelPeriodo.length > 0"
                                class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm dark:border-amber-800 dark:bg-amber-950/40"
                            >
                                <p class="font-semibold text-amber-700 dark:text-amber-400">
                                    Actividades extra registradas (no incluidas en el cálculo automático):
                                </p>
                                <p class="mt-1 text-amber-600 dark:text-amber-500">{{ extrasDelPeriodo.join(' · ') }}</p>
                                <p class="mt-1 text-amber-600 dark:text-amber-500">
                                    Si ameritan pago adicional, agrégalo abajo en "Compensación manual".
                                </p>
                            </div>

                            <!-- Compensación manual (editable) -->
                            <div class="flex items-center justify-between gap-4">
                                <Label class="text-muted-foreground whitespace-nowrap">
                                    {{ esDescuento ? 'Descuento manual' : 'Compensación manual' }}
                                </Label>
                                <Input
                                    v-model.number="calcParams.compensacion"
                                    type="number"
                                    step="0.01"
                                    @change="clampCompensacion"
                                    class="h-8 w-32 text-right text-sm tabular-nums"
                                    :class="hasUnsavedComp ? 'ring-2 ring-amber-400' : ''"
                                />
                            </div>
                            <p v-if="compExcedeTope" class="rounded-md bg-red-50 px-3 py-1.5 text-xs text-red-700 dark:bg-red-950/40 dark:text-red-400">
                                El descuento no puede superar el total a pagar. Máximo: {{ fmtSigned(topeDescuento) }}
                            </p>
                            <p v-else-if="esDescuento" class="ml-auto w-max text-right text-xs text-amber-600 dark:text-amber-500">
                                Descuento: se resta del total.
                            </p>
                            <!-- Advertencia cambios sin guardar (§18.5) -->
                            <p
                                v-if="hasUnsavedComp"
                                class="rounded-md bg-amber-50 px-3 py-2 text-sm text-amber-700 dark:bg-amber-950/40 dark:text-amber-400"
                            >
                                Compensación modificada — guarda la nómina para no perder el cambio.
                            </p>

                            <!-- Comentario (opcional, se guarda junto con la nómina) -->
                            <div class="space-y-1">
                                <Label class="text-muted-foreground">Comentario (opcional)</Label>
                                <textarea
                                    v-model="calcParams.comentario"
                                    rows="2"
                                    placeholder="Notas sobre este cálculo de nómina…"
                                    class="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 dark:bg-input/30 w-full resize-none rounded-md border bg-transparent px-3 py-1.5 text-sm shadow-xs outline-none transition-[color,box-shadow] focus-visible:ring-[3px]"
                                />
                            </div>

                            <hr class="border-border my-2" />

                            <div class="flex justify-between text-xl font-bold">
                                <span>Total a pagar</span>
                                <span class="tabular-nums">{{ fmtSigned(totalConCompensacion) }}</span>
                            </div>

                            <p v-if="desglose.estado === 'PENDIENTE'" class="text-amber-600 dark:text-amber-400 text-sm">
                                Nómina guardada (PENDIENTE)
                            </p>
                        </div>

                        <!-- Advertencia: jornadas sin validar -->
                        <div
                            v-if="(desglose.jornadas_sin_validar ?? 0) > 0"
                            class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm dark:border-amber-800 dark:bg-amber-950/40"
                        >
                            <p class="font-semibold text-amber-700 dark:text-amber-400">
                                {{ desglose.jornadas_sin_validar }} jornada{{ desglose.jornadas_sin_validar === 1 ? '' : 's' }} sin validar en este período
                            </p>
                            <p class="mt-1 text-sm text-amber-600 dark:text-amber-500">
                                Valida todas las jornadas en el Panel de Validación antes de guardar la nómina.
                            </p>
                        </div>

                        <!-- Acciones -->
                        <div class="flex justify-end gap-2">
                            <Button
                                variant="outline"
                                class="gap-1.5"
                                :disabled="(desglose.jornadas_sin_validar ?? 0) > 0 || guardandoNomina"
                                @click="guardarNomina"
                            >
                                <Spinner v-if="guardandoNomina" class="size-4" />
                                {{ guardandoNomina ? 'Guardando…' : 'Guardar nómina' }}
                            </Button>
                            <Button
                                v-if="desglose.nomina_id"
                                variant="default"
                                class="gap-1.5 bg-emerald-600 hover:bg-emerald-700"
                                :disabled="pagandoNomina"
                                @click="pagarNomina"
                            >
                                <Spinner v-if="pagandoNomina" class="size-4" />
                                {{ pagandoNomina ? 'Pagando…' : 'Marcar como pagado' }}
                            </Button>
                        </div>
                    </template>
                </template>
                <div v-else class="flex h-full min-h-64 items-center justify-center rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground">
                    El desglose aparecerá aquí después de calcular.
                </div>
                </div>
            </div>
        </DialogContent>
    </Dialog>

    <!-- ─── Lightbox evidencias ─── -->
    <Dialog :open="lightboxUrl !== null" @update:open="(v) => { if (!v) lightboxUrl = null; }">
        <DialogContent class="max-w-3xl p-2">
            <DialogHeader class="sr-only">
                <DialogTitle>Evidencia fotográfica</DialogTitle>
            </DialogHeader>
            <img
                v-if="lightboxUrl"
                :src="lightboxUrl"
                alt="Evidencia"
                class="max-h-[80vh] w-full rounded object-contain"
            />
        </DialogContent>
    </Dialog>

    <!-- ─── Aplazar plazos de préstamo ─── -->
    <Dialog v-model:open="aplazarAbierto">
        <DialogContent class="max-w-sm">
            <DialogHeader>
                <DialogTitle>
                    Aplazar {{ plazosSel.size === 1 ? 'el plazo' : 'los plazos' }} seleccionad{{ plazosSel.size === 1 ? 'o' : 'os' }}
                </DialogTitle>
            </DialogHeader>
            <div class="space-y-3">
                <p class="text-muted-foreground text-sm">
                    El plazo se moverá a la nueva fecha y dejará de descontarse en esta nómina.
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
