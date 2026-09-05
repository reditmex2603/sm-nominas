<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ChevronDown,
    ChevronRight,
    IdCard,
    Plus,
    Printer,
    Save,
    Search,
    Trash2,
} from '@lucide/vue';
import {
    ComboboxAnchor,
    ComboboxContent,
    ComboboxEmpty,
    ComboboxGroup,
    ComboboxInput,
    ComboboxItem,
    ComboboxLabel,
    ComboboxPortal,
    ComboboxRoot,
    ComboboxViewport,
} from 'reka-ui';
import { computed, ref, watch } from 'vue';
import ColaboradorPerfilModal from '@/components/ColaboradorPerfilModal.vue';
import InputError from '@/components/InputError.vue';
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
import { Spinner } from '@/components/ui/spinner';
import { fmtFecha } from '@/lib/fecha';
import { tipoPagoBadgeClass, tipoPagoLabel } from '@/lib/tipoPago';
import type { TipoPago } from '@/lib/tipoPago';

type TipoColaborador =
    'COLABORADOR BASE' | 'FREELANCE' | 'CONDUCTOR' | 'CONDUCTOR BASE';
type EstadoNomina = 'PENDIENTE' | 'PAGADO';
type Categoria = 'Encargado de área' | 'Técnico' | 'Stagehand SM';
type Tamano = 'CHICO' | 'MEDIANO' | 'GRANDE';

interface ColaboradorRef {
    id: number;
    nombre: string;
    apellidos: string;
    tipo: TipoColaborador;
    categoria?: Categoria | null;
    nivel?: number | null;
}

interface UnidadTransporteRef {
    id: number;
    marca: string;
    modelo: string;
    numero_placas: string | null;
    pertenencia: 'PROPIA' | 'RENTADA';
    alias: string | null;
    vehiculo: { id: number; nombre: string } | null;
    fotografia_url?: string | null;
    conductor?: {
        id: number;
        nombre: string;
        apellidos: string;
        tipo: TipoColaborador;
    } | null;
}

interface ResponsableRef {
    id: number;
    nombre: string;
    apellidos: string;
    tipo: TipoColaborador;
    telefono: string | null;
}

interface DatoBancario {
    id: number;
    banco: string | null;
    beneficiario: string | null;
    clave_interbancaria: string | null;
    numero_tarjeta: string | null;
    alias: string | null;
    comentario: string | null;
}

interface InfoBancariaItem {
    id: number;
    nombre: string;
    apellidos: string;
    tipo: TipoColaborador;
    telefono: string | null;
    datos_bancarios: DatoBancario[];
}

interface Evento {
    id: number;
    nombre: string;
    lugar: string | null;
    fecha_inicio: string | null;
    fecha_fin: string | null;
    tamano: Tamano;
    pago_por_evento_completo: string;
    nombre_contratante: string | null;
    telefono_contratante: string | null;
    contacto_nombre: string | null;
    contacto_telefono: string | null;
    descripcion: string | null;
    observaciones_tecnicas: string | null;
    enlace_ubicacion: string | null;
}

interface RegistroFreelance {
    fecha: string;
    etapa: string | null;
    extras: string | null;
    contabiliza: boolean;
}

interface NominaFreelance {
    nomina_id: number;
    colaborador: { id: number; nombre: string; apellidos: string };
    estado: EstadoNomina;
    total_final: number;
    registros: RegistroFreelance[];
}

interface JornadaBase {
    fecha: string;
    tipo_pago: TipoPago;
    traslape_pct: number | null;
    detalle: string | null;
    bono: number;
    pct_etapas: number;
}

interface NominaBase {
    nomina_id: number;
    colaborador: { id: number; nombre: string; apellidos: string };
    estado: EstadoNomina;
    periodo_inicio: string | null;
    periodo_fin: string | null;
    jornadas: JornadaBase[];
    subtotal: number;
}

interface NominaEvento {
    freelance: NominaFreelance[];
    base: NominaBase[];
    subtotal_freelance: number;
    subtotal_base: number;
    total_pagado: number;
    total_por_pagar: number;
}

type TipoViatico =
    'TRANSPORTE' | 'HOSPEDAJE' | 'ALIMENTOS' | 'CASETAS_GASOLINA' | 'OTRO';

interface Viatico {
    id: number;
    nombre: string | null;
    apellidos: string | null;
    tipo: TipoViatico;
    concepto: string;
    monto: string;
    fecha: string;
    autoriza: string | null;
    colaborador: { id: number; nombre: string; apellidos: string } | null;
}

interface ViaticosEvento {
    items: Viatico[];
    subtotal: number;
}

interface ServicioProf {
    id: number;
    nombre: string | null;
    apellidos: string | null;
    concepto: string;
    monto: string;
    fecha: string;
    autoriza: string | null;
}

interface ServiciosEvento {
    items: ServicioProf[];
    subtotal: number;
}

interface ResumenEvento {
    dias: number | null;
    total_colaboradores: number;
    base_count: number;
    freelance_count: number;
    conductor_count: number;
    pago_por_freelance: number;
    subtotal_nomina_freelance: number;
    subtotal_nomina_base: number;
    subtotal_nomina: number;
    subtotal_viaticos: number;
    subtotal_servicios: number;
    total_gastos: number;
    total_pagado: number;
    total_por_pagar: number;
    cotizacion_total: number;
    margen_proyectado: number;
    rentabilidad_pct: number | null;
    gasto_promedio_dia: number | null;
    gasto_promedio_colaborador: number | null;
}

interface Requisitos {
    base: Record<Categoria, Record<string, number>>;
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

const props = defineProps<{
    evento: Evento;
    asignados: ColaboradorRef[];
    disponibles: ColaboradorRef[];
    unidades_asignadas: UnidadTransporteRef[];
    unidades_disponibles: UnidadTransporteRef[];
    nomina: NominaEvento;
    viaticos: ViaticosEvento;
    servicios: ServiciosEvento;
    requisitos: Requisitos;
    cotizacion: Cotizacion;
    resumen: ResumenEvento;
    responsable: ResponsableRef | null;
    info_bancaria: InfoBancariaItem[];
}>();

const activeTab = ref<
    | 'resumen'
    | 'gastos'
    | 'detalles'
    | 'asignacion'
    | 'unidades'
    | 'info_bancaria'
    | 'nomina'
    | 'viaticos'
>('resumen');

const tamanoBadge: Record<Tamano, { label: string; class: string }> = {
    CHICO: {
        label: 'Chico',
        class: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
    },
    MEDIANO: {
        label: 'Mediano',
        class: 'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300',
    },
    GRANDE: {
        label: 'Grande',
        class: 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300',
    },
};

const detalleForm = useForm({
    nombre: props.evento.nombre,
    lugar: props.evento.lugar ?? '',
    fecha_inicio: props.evento.fecha_inicio ?? '',
    fecha_fin: props.evento.fecha_fin ?? '',
    tamano: props.evento.tamano as Tamano,
    pago_por_evento_completo: props.evento.pago_por_evento_completo,
    nombre_contratante: props.evento.nombre_contratante ?? '',
    telefono_contratante: props.evento.telefono_contratante ?? '',
    contacto_nombre: props.evento.contacto_nombre ?? '',
    contacto_telefono: props.evento.contacto_telefono ?? '',
    descripcion: props.evento.descripcion ?? '',
    observaciones_tecnicas: props.evento.observaciones_tecnicas ?? '',
    enlace_ubicacion: props.evento.enlace_ubicacion ?? '',
});

const guardarDetalle = () => {
    detalleForm.put(`/eventos/${props.evento.id}`, { preserveScroll: true });
};

// ── Validación cliente del formulario de Detalles ──────────────────
const detalleIntentado = ref(false);

const digitos = (v?: string): string => (v ?? '').replace(/\D/g, '');
const telefonoValido = (v: string): boolean => {
    const d = digitos(v);

    return d.length >= 10 && d.length <= 13;
};

const erroresDetalle = computed<Record<string, string>>(() => {
    const e: Record<string, string> = {};

    if (!detalleForm.nombre.trim()) {
        e.nombre = 'El nombre es obligatorio.';
    }

    if (
        detalleForm.fecha_inicio &&
        detalleForm.fecha_fin &&
        detalleForm.fecha_fin < detalleForm.fecha_inicio
    ) {
        e.fecha_fin = 'La fecha de fin no puede ser anterior al inicio.';
    }

    if (
        detalleForm.telefono_contratante &&
        !telefonoValido(detalleForm.telefono_contratante)
    ) {
        e.telefono_contratante = 'Teléfono inválido (10 a 13 dígitos).';
    }

    if (
        detalleForm.contacto_telefono &&
        !telefonoValido(detalleForm.contacto_telefono)
    ) {
        e.contacto_telefono = 'Teléfono inválido (10 a 13 dígitos).';
    }

    if (
        detalleForm.enlace_ubicacion &&
        !/^https?:\/\/.+/i.test(detalleForm.enlace_ubicacion.trim())
    ) {
        e.enlace_ubicacion = 'Ingresa una URL válida (https://…).';
    }

    return e;
});

const msgDetalle = (campo: string): string => {
    const cliente = erroresDetalle.value[campo];

    if (detalleIntentado.value && cliente) {
        return cliente;
    }

    return (detalleForm.errors as Record<string, string>)[campo] ?? '';
};

const submitDetalle = () => {
    detalleIntentado.value = true;

    if (Object.keys(erroresDetalle.value).length > 0) {
        return;
    }

    guardarDetalle();
};

const CATEGORIAS: Categoria[] = [
    'Encargado de área',
    'Técnico',
    'Stagehand SM',
];
const NIVELES = [1, 2] as const;

const tipoViaticoLabel: Record<TipoViatico, string> = {
    TRANSPORTE: 'Transporte',
    HOSPEDAJE: 'Hospedaje',
    ALIMENTOS: 'Alimentos',
    CASETAS_GASOLINA: 'Casetas y Gasolina',
    OTRO: 'Otro',
};

const tipoViaticoBadgeClass: Record<TipoViatico, string> = {
    TRANSPORTE: 'bg-blue-100 text-blue-800',
    HOSPEDAJE: 'bg-purple-100 text-purple-800',
    ALIMENTOS: 'bg-emerald-100 text-emerald-800',
    CASETAS_GASOLINA: 'bg-orange-100 text-orange-800',
    OTRO: 'bg-slate-100 text-slate-700',
};

const nombreViatico = (v: Viatico) =>
    v.colaborador
        ? `${v.colaborador.apellidos}, ${v.colaborador.nombre}`
        : v.apellidos
          ? `${v.apellidos}, ${v.nombre}`
          : (v.nombre ?? 'General');

const estadoBadge: Record<EstadoNomina, string> = {
    PENDIENTE:
        'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-400',
    PAGADO: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-400',
};

const fmtMoney = (val: number) =>
    new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
        minimumFractionDigits: 2,
    }).format(val);

const abiertoFreelance = ref<Set<number>>(new Set());
const toggleFreelance = (id: number) => {
    const next = new Set(abiertoFreelance.value);

    if (next.has(id)) {
        next.delete(id);
    } else {
        next.add(id);
    }

    abiertoFreelance.value = next;
};

const abiertoBase = ref<Set<number>>(new Set());
const toggleBase = (id: number) => {
    const next = new Set(abiertoBase.value);

    if (next.has(id)) {
        next.delete(id);
    } else {
        next.add(id);
    }

    abiertoBase.value = next;
};

const abiertoGasto = ref<Set<string>>(new Set());
const toggleGasto = (clave: string) => {
    const next = new Set(abiertoGasto.value);

    if (next.has(clave)) {
        next.delete(clave);
    } else {
        next.add(clave);
    }

    abiertoGasto.value = next;
};

const nombreServicio = (s: ServicioProf): string =>
    s.apellidos ? `${s.apellidos}, ${s.nombre}` : (s.nombre ?? '—');

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

// Categoría + nivel en un solo texto, solo aplica a Base (Freelance/Conductor no la tienen).
const categoriaNivelLabel = (c: ColaboradorRef): string =>
    c.categoria && c.nivel ? `${c.categoria} · Nivel ${c.nivel}` : '—';

// Reactive local state (edición optimista; se resincroniza con el servidor en cada recarga de
// Inertia porque "disponibles" depende de los requisitos de cotización, que cambian sin pasar
// por agregar()/quitar()).
const asignadosLocal = ref<ColaboradorRef[]>([...props.asignados]);
const disponiblesLocal = ref<ColaboradorRef[]>([...props.disponibles]);
watch(
    () => props.asignados,
    (nuevos) => {
        asignadosLocal.value = [...nuevos];
    },
);
watch(
    () => props.disponibles,
    (nuevos) => {
        disponiblesLocal.value = [...nuevos];
    },
);
const procesando = ref(false);
const perfilModalId = ref<number | null>(null);

const sync = (nuevoIds: number[]) => {
    procesando.value = true;
    router.post(
        `/eventos/${props.evento.id}/asignaciones/sync`,
        { colaborador_ids: nuevoIds },
        {
            preserveScroll: true,
            onSuccess: () => {
                asignadosLocal.value = [...props.asignados];
                disponiblesLocal.value = [...props.disponibles];
                procesando.value = false;
            },
            onError: () => {
                procesando.value = false;
            },
        },
    );
};

const agregar = (colaborador: ColaboradorRef) => {
    asignadosLocal.value = [...asignadosLocal.value, colaborador];
    disponiblesLocal.value = disponiblesLocal.value.filter(
        (c) => c.id !== colaborador.id,
    );

    sync(asignadosLocal.value.map((c) => c.id));
};

const quitar = (colaborador: ColaboradorRef) => {
    asignadosLocal.value = asignadosLocal.value.filter(
        (c) => c.id !== colaborador.id,
    );
    disponiblesLocal.value = [...disponiblesLocal.value, colaborador].sort(
        (a, b) => a.apellidos.localeCompare(b.apellidos),
    );

    sync(asignadosLocal.value.map((c) => c.id));
};

// ─── Unidades de transporte asignadas al evento ─────────────────────────────
const unidadesAsignadasLocal = ref<UnidadTransporteRef[]>(
    props.unidades_asignadas,
);
const unidadesDisponiblesLocal = ref<UnidadTransporteRef[]>(
    props.unidades_disponibles,
);
watch(
    () => props.unidades_asignadas,
    (nuevas) => {
        unidadesAsignadasLocal.value = [...nuevas];
        conductorId.value = {};

        for (const u of nuevas) {
            if (u.conductor) {
                conductorId.value[u.id] = String(u.conductor.id);
            }
        }
    },
);
watch(
    () => props.unidades_disponibles,
    (nuevas) => {
        unidadesDisponiblesLocal.value = [...nuevas];
    },
);
const nuevaUnidadId = ref('');
const procesandoUnidades = ref(false);

const syncUnidades = (ids: number[]) => {
    procesandoUnidades.value = true;
    router.post(
        `/eventos/${props.evento.id}/unidades/sync`,
        { unidad_ids: ids },
        {
            preserveScroll: true,
            onSuccess: () => {
                unidadesAsignadasLocal.value = props.unidades_asignadas;
                unidadesDisponiblesLocal.value = props.unidades_disponibles;
                procesandoUnidades.value = false;
            },
            onError: () => {
                procesandoUnidades.value = false;
            },
        },
    );
};

const nombreUnidad = (u: UnidadTransporteRef): string =>
    `${u.marca} ${u.modelo}${u.numero_placas ? ` · ${u.numero_placas}` : ''}`;

const agregarUnidad = () => {
    const unidad = unidadesDisponiblesLocal.value.find(
        (u) => u.id === Number(nuevaUnidadId.value),
    );

    if (!unidad) {
        return;
    }

    unidadesAsignadasLocal.value = [...unidadesAsignadasLocal.value, unidad];
    unidadesDisponiblesLocal.value = unidadesDisponiblesLocal.value.filter(
        (u) => u.id !== unidad.id,
    );
    nuevaUnidadId.value = '';

    syncUnidades(unidadesAsignadasLocal.value.map((u) => u.id));
};

const quitarUnidad = (unidad: UnidadTransporteRef) => {
    unidadesAsignadasLocal.value = unidadesAsignadasLocal.value.filter(
        (u) => u.id !== unidad.id,
    );
    unidadesDisponiblesLocal.value = [
        ...unidadesDisponiblesLocal.value,
        unidad,
    ].sort((a, b) => nombreUnidad(a).localeCompare(nombreUnidad(b)));

    syncUnidades(unidadesAsignadasLocal.value.map((u) => u.id));
};

// ─── Responsable del evento ────────────────────────────────────────
const responsableId = ref<string>(
    props.responsable ? String(props.responsable.id) : '',
);
const guardandoResponsable = ref(false);

const guardarResponsable = () => {
    guardandoResponsable.value = true;
    router.put(
        `/eventos/${props.evento.id}/responsable`,
        {
            responsable_colaborador_id: responsableId.value
                ? Number(responsableId.value)
                : null,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                guardandoResponsable.value = false;
            },
            onError: () => {
                guardandoResponsable.value = false;
            },
        },
    );
};

// ─── Conductor por unidad asignada ─────────────────────────────────
const conductoresDisponibles = computed(() =>
    asignadosLocal.value.filter((c) => c.tipo !== 'FREELANCE'),
);

const conductorId = ref<Record<number, string>>({});
const guardandoConductor = ref<Set<number>>(new Set());

const guardarConductor = (unidad: UnidadTransporteRef) => {
    const id = Number(conductorId.value[unidad.id] ?? '');
    const next = new Set(guardandoConductor.value);
    next.add(unidad.id);
    guardandoConductor.value = next;

    router.put(
        `/eventos/${props.evento.id}/unidades/${unidad.id}/conductor`,
        {
            conductor_colaborador_id: id ? id : null,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                unidadesAsignadasLocal.value = props.unidades_asignadas;
                const proximo = new Set(guardandoConductor.value);
                proximo.delete(unidad.id);
                guardandoConductor.value = proximo;
            },
            onError: () => {
                const proximo = new Set(guardandoConductor.value);
                proximo.delete(unidad.id);
                guardandoConductor.value = proximo;
            },
        },
    );
};

// ─── Selector de colaboradores: buscador + agrupado por categoría/nivel ───────
type FiltroRapido =
    { categoria: Categoria; nivel: number } | 'freelance' | null;
const busqueda = ref('');
const filtroRapido = ref<FiltroRapido>(null);
const comboboxAbierto = ref(false);

const abrirFiltroRapido = (filtro: FiltroRapido) => {
    filtroRapido.value = filtro;
    busqueda.value = '';
    comboboxAbierto.value = true;
};

const alCambiarAbierto = (abierto: boolean) => {
    comboboxAbierto.value = abierto;

    if (!abierto) {
        filtroRapido.value = null;
        busqueda.value = '';
    }
};

const seleccionarColaborador = (valor: unknown) => {
    const id = Number(valor);
    const colaborador = disponiblesLocal.value.find((c) => c.id === id);

    if (!colaborador) {
        return;
    }

    agregar(colaborador);
    busqueda.value = '';
    filtroRapido.value = null;
    comboboxAbierto.value = false;
};

const disponiblesOrdenados = computed(() =>
    [...disponiblesLocal.value].sort((a, b) =>
        a.apellidos.localeCompare(b.apellidos),
    ),
);

const coincideBusqueda = (c: ColaboradorRef): boolean => {
    if (!busqueda.value.trim()) {
        return true;
    }

    const texto = `${c.apellidos} ${c.nombre}`.toLowerCase();

    return texto.includes(busqueda.value.trim().toLowerCase());
};

const coincideFiltroRapido = (c: ColaboradorRef): boolean => {
    if (!filtroRapido.value) {
        return true;
    }

    if (filtroRapido.value === 'freelance') {
        return c.tipo === 'FREELANCE';
    }

    return (
        c.tipo === 'COLABORADOR BASE' &&
        c.categoria === filtroRapido.value.categoria &&
        c.nivel === filtroRapido.value.nivel
    );
};

// Agrupa la lista de disponibles (ya filtrada por búsqueda/filtro rápido) por categoría/nivel
// para Base, y en bloques aparte Freelance/Conductores — más fácil de escanear que una lista plana.
const gruposDisponibles = computed(() => {
    const filtrados = disponiblesOrdenados.value.filter(
        (c) => coincideFiltroRapido(c) && coincideBusqueda(c),
    );
    const grupos: { label: string; items: ColaboradorRef[] }[] = [];

    for (const cat of CATEGORIAS) {
        for (const n of NIVELES) {
            const items = filtrados.filter(
                (c) =>
                    c.tipo === 'COLABORADOR BASE' &&
                    c.categoria === cat &&
                    c.nivel === n,
            );

            if (items.length) {
                grupos.push({ label: `${cat} · Nivel ${n}`, items });
            }
        }
    }

    const freelance = filtrados.filter((c) => c.tipo === 'FREELANCE');

    if (freelance.length) {
        grupos.push({ label: 'Freelance', items: freelance });
    }

    const conductores = filtrados.filter((c) => c.tipo === 'CONDUCTOR');

    if (conductores.length) {
        grupos.push({ label: 'Conductores', items: conductores });
    }

    return grupos;
});

// ─── Cotización: requisitos de personal (categoría/nivel/freelance) ───────
// structuredClone() no puede clonar el Proxy reactivo de las props de Inertia (DataCloneError),
// aunque el contenido sea JSON plano — de ahí el JSON.parse(JSON.stringify(...)).
const requisitosForm = ref<Requisitos>(
    JSON.parse(JSON.stringify(props.requisitos)),
);
const guardandoRequisitos = ref(false);

// Cuántos asignados hay hoy por combinación — para mostrar el progreso frente al requisito.
const asignadosPorCombo = computed(() => {
    const conteo: Record<string, number> = {};

    for (const c of asignadosLocal.value) {
        if (c.tipo !== 'COLABORADOR BASE' || !c.categoria || !c.nivel) {
            continue;
        }

        const clave = `${c.categoria}|${c.nivel}`;
        conteo[clave] = (conteo[clave] ?? 0) + 1;
    }

    return conteo;
});

const freelanceAsignados = computed(
    () => asignadosLocal.value.filter((c) => c.tipo === 'FREELANCE').length,
);

const asignadosDeCombo = (categoria: Categoria, nivel: number): number =>
    asignadosPorCombo.value[`${categoria}|${nivel}`] ?? 0;

const guardarRequisitos = () => {
    guardandoRequisitos.value = true;
    router.put(`/eventos/${props.evento.id}/requisitos`, requisitosForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            disponiblesLocal.value = [...props.disponibles];
            requisitosForm.value = JSON.parse(JSON.stringify(props.requisitos));
            mostrarRequisitosForm.value = totalRequerido.value === 0;
        },
        onFinish: () => {
            guardandoRequisitos.value = false;
        },
    });
};

const limpiarRequisitos = () => {
    for (const cat of CATEGORIAS) {
        for (const n of NIVELES) {
            requisitosForm.value.base[cat][String(n)] = 0;
        }
    }

    requisitosForm.value.freelance = 0;
    guardarRequisitos();
};

// Si el servidor manda props actualizadas (tras guardar), refleja el nuevo estado guardado.
watch(
    () => props.requisitos,
    (nuevos) => {
        requisitosForm.value = JSON.parse(JSON.stringify(nuevos));
    },
);

const totalRequerido = computed(() => {
    const base = CATEGORIAS.reduce(
        (s, cat) =>
            s +
            NIVELES.reduce(
                (s2, n) =>
                    s2 + (requisitosForm.value.base[cat]?.[String(n)] ?? 0),
                0,
            ),
        0,
    );

    return base + requisitosForm.value.freelance;
});

// El editor de requisitos arranca abierto solo si aún no hay nada definido; una vez capturados,
// se colapsa a un resumen de badges para no saturar la pestaña combinada de Asignación.
const mostrarRequisitosForm = ref(totalRequerido.value === 0);

const abrirImprimirNomina = () => {
    window.open(`/eventos/${props.evento.id}/nomina/imprimir`, '_blank');
};

const abrirImprimirCotizacion = () => {
    window.open(`/eventos/${props.evento.id}/cotizacion/imprimir`, '_blank');
};

const abrirImprimirResumen = () => {
    window.open(`/eventos/${props.evento.id}/resumen/imprimir`, '_blank');
};

const abrirImprimirDetalles = () => {
    window.open(`/eventos/${props.evento.id}/detalles/imprimir`, '_blank');
};

const abrirImprimirTerceros = () => {
    window.open(`/eventos/${props.evento.id}/terceros/imprimir`, '_blank');
};

const categoriaCortaLabel: Record<Categoria, string> = {
    'Encargado de área': 'Encargado',
    Técnico: 'Técnico',
    'Stagehand SM': 'Stagehand SM',
};
</script>

<template>
    <Head :title="`Evento — ${evento.nombre}`" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4 sm:p-6">
        <div class="flex items-start gap-3 sm:items-center">
            <Button variant="ghost" size="sm" as-child>
                <Link href="/eventos">
                    <ArrowLeft class="size-4" />
                </Link>
            </Button>
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-semibold">{{ evento.nombre }}</h1>
                    <span
                        class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium"
                        :class="tamanoBadge[evento.tamano].class"
                    >
                        {{ tamanoBadge[evento.tamano].label }}
                    </span>
                </div>
                <p class="mt-0.5 text-sm text-muted-foreground">
                    {{ evento.lugar || '' }}
                    <template v-if="evento.fecha_inicio || evento.fecha_fin">
                        · {{ fmtFecha(evento.fecha_inicio) }} –
                        {{ fmtFecha(evento.fecha_fin) }}
                    </template>
                </p>
            </div>
            <div class="ml-auto flex flex-wrap items-center gap-2">
                <Button
                    size="sm"
                    variant="outline"
                    class="gap-1.5"
                    @click="abrirImprimirTerceros"
                >
                    <Printer class="size-3.5" />
                    Imprimir a terceros
                </Button>
            </div>
        </div>

        <!-- Tabs nav -->
        <div class="flex gap-1 overflow-x-auto border-b">
            <button
                class="relative px-4 py-2 text-sm font-medium whitespace-nowrap transition-colors"
                :class="
                    activeTab === 'resumen'
                        ? '-mb-px border-b-2 border-primary bg-transparent text-foreground'
                        : 'text-muted-foreground hover:text-foreground'
                "
                @click="activeTab = 'resumen'"
            >
                Resumen
            </button>
            <button
                class="relative px-4 py-2 text-sm font-medium whitespace-nowrap transition-colors"
                :class="
                    activeTab === 'gastos'
                        ? '-mb-px border-b-2 border-primary bg-transparent text-foreground'
                        : 'text-muted-foreground hover:text-foreground'
                "
                @click="activeTab = 'gastos'"
            >
                Gastos
            </button>
            <button
                class="relative px-4 py-2 text-sm font-medium whitespace-nowrap transition-colors"
                :class="
                    activeTab === 'detalles'
                        ? '-mb-px border-b-2 border-primary bg-transparent text-foreground'
                        : 'text-muted-foreground hover:text-foreground'
                "
                @click="activeTab = 'detalles'"
            >
                Detalles
            </button>
            <button
                class="relative px-4 py-2 text-sm font-medium whitespace-nowrap transition-colors"
                :class="
                    activeTab === 'asignacion'
                        ? '-mb-px border-b-2 border-primary bg-transparent text-foreground'
                        : 'text-muted-foreground hover:text-foreground'
                "
                @click="activeTab = 'asignacion'"
            >
                Asignación y cotización
            </button>
            <button
                class="relative px-4 py-2 text-sm font-medium whitespace-nowrap transition-colors"
                :class="
                    activeTab === 'unidades'
                        ? '-mb-px border-b-2 border-primary bg-transparent text-foreground'
                        : 'text-muted-foreground hover:text-foreground'
                "
                @click="activeTab = 'unidades'"
            >
                Unidades
            </button>
            <button
                class="relative px-4 py-2 text-sm font-medium whitespace-nowrap transition-colors"
                :class="
                    activeTab === 'info_bancaria'
                        ? '-mb-px border-b-2 border-primary bg-transparent text-foreground'
                        : 'text-muted-foreground hover:text-foreground'
                "
                @click="activeTab = 'info_bancaria'"
            >
                Info bancaria
            </button>
            <button
                class="relative px-4 py-2 text-sm font-medium whitespace-nowrap transition-colors"
                :class="
                    activeTab === 'nomina'
                        ? '-mb-px border-b-2 border-primary bg-transparent text-foreground'
                        : 'text-muted-foreground hover:text-foreground'
                "
                @click="activeTab = 'nomina'"
            >
                Nómina
            </button>
            <button
                class="relative px-4 py-2 text-sm font-medium whitespace-nowrap transition-colors"
                :class="
                    activeTab === 'viaticos'
                        ? '-mb-px border-b-2 border-primary bg-transparent text-foreground'
                        : 'text-muted-foreground hover:text-foreground'
                "
                @click="activeTab = 'viaticos'"
            >
                Viáticos
            </button>
        </div>

        <template v-if="activeTab === 'resumen'">
            <!-- Widgets generales -->
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4"
            >
                <div class="grid flex-1 grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-xl border p-4">
                        <p class="text-xs text-muted-foreground">
                            Días del evento
                        </p>
                        <p class="mt-1 text-lg font-semibold">
                            {{ resumen.dias ?? '—' }}
                        </p>
                    </div>
                    <div class="rounded-xl border p-4">
                        <p class="text-xs text-muted-foreground">
                            Colaboradores asignados
                        </p>
                        <p class="mt-1 text-lg font-semibold">
                            {{ resumen.total_colaboradores }}
                        </p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            {{ resumen.base_count }} base ·
                            {{ resumen.freelance_count }} freelance
                            <template v-if="resumen.conductor_count">
                                ·
                                {{ resumen.conductor_count }}
                                conductor</template
                            >
                        </p>
                    </div>
                    <div class="rounded-xl border p-4">
                        <p class="text-xs text-muted-foreground">
                            Total cotizado (100% participación)
                        </p>
                        <p class="mt-1 text-lg font-semibold">
                            {{ fmtMoney(resumen.cotizacion_total) }}
                        </p>
                    </div>
                    <div class="rounded-xl border p-4">
                        <p class="text-xs text-muted-foreground">
                            Total gastos
                        </p>
                        <p
                            class="mt-1 text-lg font-semibold text-red-600 dark:text-red-400"
                        >
                            {{ fmtMoney(resumen.total_gastos) }}
                        </p>
                    </div>
                </div>
                <Button
                    size="sm"
                    variant="outline"
                    class="gap-1.5 self-start whitespace-nowrap sm:mt-1"
                    @click="abrirImprimirResumen"
                >
                    <Printer class="size-3.5" />
                    Imprimir resumen
                </Button>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <!-- Desglose de gastos -->
                <div class="overflow-hidden rounded-xl border">
                    <div
                        class="border-b bg-muted/50 px-4 py-2 text-sm font-medium"
                    >
                        Gastos del evento
                    </div>
                    <div class="divide-y">
                        <div
                            class="flex items-center justify-between gap-2 px-4 py-2.5 text-sm"
                        >
                            <span class="text-muted-foreground"
                                >Nómina freelance</span
                            >
                            <span class="font-medium tabular-nums">{{
                                fmtMoney(resumen.subtotal_nomina_freelance)
                            }}</span>
                        </div>
                        <div
                            class="flex items-center justify-between gap-2 px-4 py-2.5 text-sm"
                        >
                            <span class="text-muted-foreground">
                                Nómina base - extra por evento
                                <span
                                    class="block text-xs text-muted-foreground/70"
                                    >solo el bono de los días de evento</span
                                >
                            </span>
                            <span class="font-medium tabular-nums">{{
                                fmtMoney(resumen.subtotal_nomina_base)
                            }}</span>
                        </div>
                        <div
                            class="flex items-center justify-between gap-2 px-4 py-2.5 text-sm"
                        >
                            <span class="text-muted-foreground">Viáticos</span>
                            <span class="font-medium tabular-nums">{{
                                fmtMoney(resumen.subtotal_viaticos)
                            }}</span>
                        </div>
                        <div
                            class="flex items-center justify-between gap-2 px-4 py-2.5 text-sm"
                        >
                            <span class="text-muted-foreground"
                                >Servicios profesionales</span
                            >
                            <span class="font-medium tabular-nums">{{
                                fmtMoney(resumen.subtotal_servicios)
                            }}</span>
                        </div>
                        <div
                            class="flex items-center justify-between gap-2 bg-red-50 px-4 py-3 text-sm font-semibold dark:bg-red-950/30"
                        >
                            <span>Total gastos</span>
                            <span
                                class="text-red-600 tabular-nums dark:text-red-400"
                                >{{ fmtMoney(resumen.total_gastos) }}</span
                            >
                        </div>
                    </div>
                    <p class="border-t px-4 py-2 text-xs text-muted-foreground">
                        Pagado: {{ fmtMoney(resumen.total_pagado) }} · Por
                        pagar: {{ fmtMoney(resumen.total_por_pagar) }}
                    </p>
                </div>

                <!-- Indicadores -->
                <div class="overflow-hidden rounded-xl border">
                    <div
                        class="border-b bg-muted/50 px-4 py-2 text-sm font-medium"
                    >
                        Indicadores
                    </div>
                    <div class="divide-y text-sm">
                        <div
                            class="flex items-center justify-between gap-2 px-4 py-2.5"
                        >
                            <span class="text-muted-foreground"
                                >Margen proyectado (cotizado - gastos)</span
                            >
                            <span
                                class="font-medium tabular-nums"
                                :class="
                                    resumen.margen_proyectado >= 0
                                        ? 'text-emerald-600 dark:text-emerald-400'
                                        : 'text-red-600 dark:text-red-400'
                                "
                            >
                                {{ resumen.margen_proyectado >= 0 ? '' : '-'
                                }}{{
                                    fmtMoney(
                                        Math.abs(resumen.margen_proyectado),
                                    )
                                }}
                            </span>
                        </div>
                        <div
                            class="flex items-center justify-between gap-2 px-4 py-2.5"
                        >
                            <span class="text-muted-foreground"
                                >Rentabilidad proyectada</span
                            >
                            <span
                                class="font-medium tabular-nums"
                                :class="
                                    (resumen.rentabilidad_pct ?? 0) >= 0
                                        ? 'text-emerald-600 dark:text-emerald-400'
                                        : 'text-red-600 dark:text-red-400'
                                "
                            >
                                {{
                                    resumen.rentabilidad_pct !== null
                                        ? `${resumen.rentabilidad_pct.toLocaleString('es-MX')} %`
                                        : '—'
                                }}
                            </span>
                        </div>
                        <div
                            class="flex items-center justify-between gap-2 px-4 py-2.5"
                        >
                            <span class="text-muted-foreground"
                                >Gasto promedio por día</span
                            >
                            <span class="font-medium tabular-nums">
                                {{
                                    resumen.gasto_promedio_dia !== null
                                        ? fmtMoney(resumen.gasto_promedio_dia)
                                        : '—'
                                }}
                            </span>
                        </div>
                        <div
                            class="flex items-center justify-between gap-2 px-4 py-2.5"
                        >
                            <span class="text-muted-foreground"
                                >Gasto promedio por colaborador</span
                            >
                            <span class="font-medium tabular-nums">
                                {{
                                    resumen.gasto_promedio_colaborador !== null
                                        ? fmtMoney(
                                              resumen.gasto_promedio_colaborador,
                                          )
                                        : '—'
                                }}
                            </span>
                        </div>
                        <div
                            class="flex items-center justify-between gap-2 px-4 py-2.5"
                        >
                            <span class="text-muted-foreground"
                                >Pago por freelance (evento completo)</span
                            >
                            <span class="font-medium tabular-nums">{{
                                fmtMoney(resumen.pago_por_freelance)
                            }}</span>
                        </div>
                    </div>
                    <p class="border-t px-4 py-2 text-xs text-muted-foreground">
                        El margen es solo una proyección: compara lo cotizado
                        (100 % de participación) contra gastos incurridos hasta
                        ahora.
                    </p>
                </div>
            </div>
        </template>

        <template v-else-if="activeTab === 'gastos'">
            <!-- Widgets resumen -->
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4"
            >
                <div class="grid flex-1 grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-xl border p-4">
                        <p class="text-xs text-muted-foreground">
                            Total gastos
                        </p>
                        <p
                            class="mt-1 text-lg font-semibold text-red-600 dark:text-red-400"
                        >
                            {{ fmtMoney(resumen.total_gastos) }}
                        </p>
                    </div>
                    <div class="rounded-xl border p-4">
                        <p class="text-xs text-muted-foreground">Nómina</p>
                        <p class="mt-1 text-lg font-semibold">
                            {{ fmtMoney(resumen.subtotal_nomina) }}
                        </p>
                    </div>
                    <div class="rounded-xl border p-4">
                        <p class="text-xs text-muted-foreground">Viáticos</p>
                        <p class="mt-1 text-lg font-semibold">
                            {{ fmtMoney(resumen.subtotal_viaticos) }}
                        </p>
                    </div>
                    <div class="rounded-xl border p-4">
                        <p class="text-xs text-muted-foreground">
                            Servicios profesionales
                        </p>
                        <p class="mt-1 text-lg font-semibold">
                            {{ fmtMoney(resumen.subtotal_servicios) }}
                        </p>
                    </div>
                </div>
            </div>

            <p
                v-if="
                    nomina.freelance.length === 0 &&
                    nomina.base.length === 0 &&
                    viaticos.items.length === 0 &&
                    servicios.items.length === 0
                "
                class="py-10 text-center text-sm text-muted-foreground"
            >
                Sin gastos registrados para este evento todavía.
            </p>

            <div class="space-y-3">
                <!-- Nómina freelance -->
                <div class="overflow-hidden rounded-xl border">
                    <button
                        class="flex w-full items-center gap-2 bg-muted/30 px-4 py-3 text-left"
                        @click="toggleGasto('nomina_freelance')"
                    >
                        <component
                            :is="
                                abiertoGasto.has('nomina_freelance')
                                    ? ChevronDown
                                    : ChevronRight
                            "
                            class="size-4 shrink-0 text-muted-foreground"
                        />
                        <span class="flex-1 text-sm font-medium"
                            >Nómina freelance</span
                        >
                        <span
                            v-if="nomina.freelance.length > 0"
                            class="text-xs text-muted-foreground"
                        >
                            {{ nomina.freelance.length }} nómina(s)
                        </span>
                        <span class="text-sm font-semibold tabular-nums">{{
                            fmtMoney(nomina.subtotal_freelance)
                        }}</span>
                    </button>
                    <template v-if="abiertoGasto.has('nomina_freelance')">
                        <p
                            v-if="nomina.freelance.length === 0"
                            class="px-4 py-4 text-center text-sm text-muted-foreground"
                        >
                            Sin nóminas freelance registradas.
                        </p>
                        <table v-else class="w-full text-sm">
                            <thead class="border-t border-b bg-muted/50">
                                <tr>
                                    <th class="w-8 px-2 py-2"></th>
                                    <th
                                        class="px-4 py-2 text-left text-xs font-medium"
                                    >
                                        Colaborador
                                    </th>
                                    <th
                                        class="px-4 py-2 text-center text-xs font-medium"
                                    >
                                        Estado
                                    </th>
                                    <th
                                        class="px-4 py-2 text-right text-xs font-medium"
                                    >
                                        Total
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <template
                                    v-for="n in nomina.freelance"
                                    :key="n.nomina_id"
                                >
                                    <tr
                                        class="cursor-pointer hover:bg-muted/20"
                                        @click="toggleFreelance(n.nomina_id)"
                                    >
                                        <td class="px-2 py-2.5">
                                            <component
                                                :is="
                                                    abiertoFreelance.has(
                                                        n.nomina_id,
                                                    )
                                                        ? ChevronDown
                                                        : ChevronRight
                                                "
                                                class="size-4 text-muted-foreground"
                                            />
                                        </td>
                                        <td class="px-4 py-2.5 font-medium">
                                            {{ n.colaborador.apellidos }},
                                            {{ n.colaborador.nombre }}
                                        </td>
                                        <td class="px-4 py-2.5 text-center">
                                            <span
                                                class="rounded-full px-2 py-0.5 text-xs font-medium"
                                                :class="estadoBadge[n.estado]"
                                            >
                                                {{ n.estado }}
                                            </span>
                                        </td>
                                        <td
                                            class="px-4 py-2.5 text-right font-medium tabular-nums"
                                        >
                                            {{ fmtMoney(n.total_final) }}
                                        </td>
                                    </tr>
                                    <tr
                                        v-if="abiertoFreelance.has(n.nomina_id)"
                                    >
                                        <td
                                            colspan="4"
                                            class="border-t bg-muted/10 px-4 py-3"
                                        >
                                            <div
                                                v-for="(r, i) in n.registros"
                                                :key="i"
                                                class="flex items-center justify-between gap-2 py-0.5 text-xs"
                                            >
                                                <span
                                                    class="whitespace-nowrap tabular-nums"
                                                    >{{
                                                        fmtFecha(r.fecha)
                                                    }}</span
                                                >
                                                <span
                                                    class="flex-1 truncate text-muted-foreground"
                                                    >{{ r.etapa ?? '—' }}</span
                                                >
                                                <span
                                                    class="rounded px-1.5 py-0.5 font-medium whitespace-nowrap"
                                                    :class="
                                                        r.contabiliza
                                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-400'
                                                            : 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-400'
                                                    "
                                                >
                                                    {{
                                                        r.contabiliza
                                                            ? 'Contabilizada'
                                                            : 'Sin validar'
                                                    }}
                                                </span>
                                            </div>
                                            <p
                                                v-if="n.registros.length === 0"
                                                class="text-muted-foreground"
                                            >
                                                Sin registros.
                                            </p>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </template>
                </div>

                <!-- Nómina base (extra por evento) -->
                <div class="overflow-hidden rounded-xl border">
                    <button
                        class="flex w-full items-center gap-2 bg-muted/30 px-4 py-3 text-left"
                        @click="toggleGasto('nomina_base')"
                    >
                        <component
                            :is="
                                abiertoGasto.has('nomina_base')
                                    ? ChevronDown
                                    : ChevronRight
                            "
                            class="size-4 shrink-0 text-muted-foreground"
                        />
                        <span class="flex-1 text-sm font-medium">
                            Nómina base - extra por evento
                            <span
                                class="block text-xs font-normal text-muted-foreground"
                                >solo el bono de los días de evento</span
                            >
                        </span>
                        <span
                            v-if="nomina.base.length > 0"
                            class="text-xs text-muted-foreground"
                        >
                            {{ nomina.base.length }} nómina(s)
                        </span>
                        <span class="text-sm font-semibold tabular-nums">{{
                            fmtMoney(nomina.subtotal_base)
                        }}</span>
                    </button>
                    <template v-if="abiertoGasto.has('nomina_base')">
                        <p
                            v-if="nomina.base.length === 0"
                            class="px-4 py-4 text-center text-sm text-muted-foreground"
                        >
                            Sin extras por evento registrados en nóminas base.
                        </p>
                        <table v-else class="w-full text-sm">
                            <thead class="border-t border-b bg-muted/50">
                                <tr>
                                    <th class="w-8 px-2 py-2"></th>
                                    <th
                                        class="px-4 py-2 text-left text-xs font-medium"
                                    >
                                        Colaborador
                                    </th>
                                    <th
                                        class="px-4 py-2 text-left text-xs font-medium"
                                    >
                                        Período de la nómina
                                    </th>
                                    <th
                                        class="px-4 py-2 text-center text-xs font-medium"
                                    >
                                        Estado
                                    </th>
                                    <th
                                        class="px-4 py-2 text-right text-xs font-medium"
                                    >
                                        Subtotal
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <template
                                    v-for="n in nomina.base"
                                    :key="n.nomina_id"
                                >
                                    <tr
                                        class="cursor-pointer hover:bg-muted/20"
                                        @click="toggleBase(n.nomina_id)"
                                    >
                                        <td class="px-2 py-2.5">
                                            <component
                                                :is="
                                                    abiertoBase.has(n.nomina_id)
                                                        ? ChevronDown
                                                        : ChevronRight
                                                "
                                                class="size-4 text-muted-foreground"
                                            />
                                        </td>
                                        <td class="px-4 py-2.5 font-medium">
                                            {{ n.colaborador.apellidos }},
                                            {{ n.colaborador.nombre }}
                                        </td>
                                        <td
                                            class="px-4 py-2.5 whitespace-nowrap text-muted-foreground"
                                        >
                                            {{ fmtFecha(n.periodo_inicio) }} –
                                            {{ fmtFecha(n.periodo_fin) }}
                                        </td>
                                        <td class="px-4 py-2.5 text-center">
                                            <span
                                                class="rounded-full px-2 py-0.5 text-xs font-medium"
                                                :class="estadoBadge[n.estado]"
                                            >
                                                {{ n.estado }}
                                            </span>
                                        </td>
                                        <td
                                            class="px-4 py-2.5 text-right font-medium tabular-nums"
                                        >
                                            {{ fmtMoney(n.subtotal) }}
                                        </td>
                                    </tr>
                                    <tr v-if="abiertoBase.has(n.nomina_id)">
                                        <td
                                            colspan="5"
                                            class="border-t bg-muted/10 px-4 py-3"
                                        >
                                            <div
                                                v-for="j in n.jornadas"
                                                :key="j.fecha"
                                                class="flex items-center justify-between gap-2 py-0.5 text-xs"
                                            >
                                                <span
                                                    class="whitespace-nowrap tabular-nums"
                                                    >{{
                                                        fmtFecha(j.fecha)
                                                    }}</span
                                                >
                                                <span
                                                    class="flex-1 truncate text-muted-foreground"
                                                    >{{
                                                        (
                                                            j.detalle ?? '—'
                                                        ).replace('\n', ' · ')
                                                    }}</span
                                                >
                                                <span
                                                    class="font-medium whitespace-nowrap tabular-nums"
                                                    >+{{
                                                        fmtMoney(j.bono)
                                                    }}</span
                                                >
                                                <span
                                                    class="rounded px-1.5 py-0.5 font-medium whitespace-nowrap"
                                                    :class="
                                                        tipoPagoBadgeClass(
                                                            j.tipo_pago,
                                                        )
                                                    "
                                                >
                                                    {{
                                                        tipoPagoLabel(
                                                            j.tipo_pago,
                                                            j.traslape_pct,
                                                        )
                                                    }}
                                                </span>
                                            </div>
                                            <p
                                                v-if="n.jornadas.length === 0"
                                                class="text-muted-foreground"
                                            >
                                                Sin jornadas.
                                            </p>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </template>
                </div>

                <!-- Viáticos -->
                <div class="overflow-hidden rounded-xl border">
                    <button
                        class="flex w-full items-center gap-2 bg-muted/30 px-4 py-3 text-left"
                        @click="toggleGasto('viaticos')"
                    >
                        <component
                            :is="
                                abiertoGasto.has('viaticos')
                                    ? ChevronDown
                                    : ChevronRight
                            "
                            class="size-4 shrink-0 text-muted-foreground"
                        />
                        <span class="flex-1 text-sm font-medium">Viáticos</span>
                        <span
                            v-if="viaticos.items.length > 0"
                            class="text-xs text-muted-foreground"
                        >
                            {{ viaticos.items.length }} registro(s)
                        </span>
                        <span class="text-sm font-semibold tabular-nums">{{
                            fmtMoney(viaticos.subtotal)
                        }}</span>
                    </button>
                    <template v-if="abiertoGasto.has('viaticos')">
                        <p
                            v-if="viaticos.items.length === 0"
                            class="px-4 py-4 text-center text-sm text-muted-foreground"
                        >
                            Sin viáticos registrados.
                        </p>
                        <table v-else class="w-full text-sm">
                            <thead class="border-t border-b bg-muted/50">
                                <tr>
                                    <th
                                        class="px-4 py-2 text-left text-xs font-medium"
                                    >
                                        Fecha
                                    </th>
                                    <th
                                        class="px-4 py-2 text-left text-xs font-medium"
                                    >
                                        Nombre
                                    </th>
                                    <th
                                        class="px-4 py-2 text-left text-xs font-medium"
                                    >
                                        Tipo
                                    </th>
                                    <th
                                        class="px-4 py-2 text-left text-xs font-medium"
                                    >
                                        Concepto
                                    </th>
                                    <th
                                        class="px-4 py-2 text-right text-xs font-medium"
                                    >
                                        Monto
                                    </th>
                                    <th
                                        class="px-4 py-2 text-left text-xs font-medium"
                                    >
                                        Autoriza
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr v-for="v in viaticos.items" :key="v.id">
                                    <td
                                        class="px-4 py-2.5 whitespace-nowrap tabular-nums"
                                    >
                                        {{ fmtFecha(v.fecha) }}
                                    </td>
                                    <td
                                        class="px-4 py-2.5 font-medium whitespace-nowrap"
                                    >
                                        {{ nombreViatico(v) }}
                                        <span
                                            v-if="!v.colaborador"
                                            class="ml-1 text-xs font-normal text-muted-foreground"
                                            >(General)</span
                                        >
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <span
                                            class="rounded-full px-2 py-0.5 text-xs font-medium"
                                            :class="
                                                tipoViaticoBadgeClass[v.tipo]
                                            "
                                        >
                                            {{ tipoViaticoLabel[v.tipo] }}
                                        </span>
                                    </td>
                                    <td
                                        class="max-w-xs px-4 py-2.5 text-muted-foreground"
                                    >
                                        {{ v.concepto }}
                                    </td>
                                    <td
                                        class="px-4 py-2.5 text-right font-medium tabular-nums"
                                    >
                                        {{ fmtMoney(parseFloat(v.monto)) }}
                                    </td>
                                    <td
                                        class="px-4 py-2.5 text-muted-foreground"
                                    >
                                        {{ v.autoriza ?? '—' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </template>
                </div>

                <!-- Servicios profesionales -->
                <div class="overflow-hidden rounded-xl border">
                    <button
                        class="flex w-full items-center gap-2 bg-muted/30 px-4 py-3 text-left"
                        @click="toggleGasto('servicios')"
                    >
                        <component
                            :is="
                                abiertoGasto.has('servicios')
                                    ? ChevronDown
                                    : ChevronRight
                            "
                            class="size-4 shrink-0 text-muted-foreground"
                        />
                        <span class="flex-1 text-sm font-medium"
                            >Servicios profesionales</span
                        >
                        <span
                            v-if="servicios.items.length > 0"
                            class="text-xs text-muted-foreground"
                        >
                            {{ servicios.items.length }} servicio(s)
                        </span>
                        <span class="text-sm font-semibold tabular-nums">{{
                            fmtMoney(servicios.subtotal)
                        }}</span>
                    </button>
                    <template v-if="abiertoGasto.has('servicios')">
                        <p
                            v-if="servicios.items.length === 0"
                            class="px-4 py-4 text-center text-sm text-muted-foreground"
                        >
                            Sin servicios profesionales registrados.
                        </p>
                        <table v-else class="w-full text-sm">
                            <thead class="border-t border-b bg-muted/50">
                                <tr>
                                    <th
                                        class="px-4 py-2 text-left text-xs font-medium"
                                    >
                                        Fecha
                                    </th>
                                    <th
                                        class="px-4 py-2 text-left text-xs font-medium"
                                    >
                                        Nombre
                                    </th>
                                    <th
                                        class="px-4 py-2 text-left text-xs font-medium"
                                    >
                                        Concepto
                                    </th>
                                    <th
                                        class="px-4 py-2 text-right text-xs font-medium"
                                    >
                                        Monto
                                    </th>
                                    <th
                                        class="px-4 py-2 text-left text-xs font-medium"
                                    >
                                        Autoriza
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr v-for="s in servicios.items" :key="s.id">
                                    <td
                                        class="px-4 py-2.5 whitespace-nowrap tabular-nums"
                                    >
                                        {{ fmtFecha(s.fecha) }}
                                    </td>
                                    <td
                                        class="px-4 py-2.5 font-medium whitespace-nowrap"
                                    >
                                        {{ nombreServicio(s) }}
                                    </td>
                                    <td
                                        class="max-w-xs px-4 py-2.5 text-muted-foreground"
                                    >
                                        {{ s.concepto }}
                                    </td>
                                    <td
                                        class="px-4 py-2.5 text-right font-medium tabular-nums"
                                    >
                                        {{ fmtMoney(parseFloat(s.monto)) }}
                                    </td>
                                    <td
                                        class="px-4 py-2.5 text-muted-foreground"
                                    >
                                        {{ s.autoriza ?? '—' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </template>
                </div>
            </div>
        </template>

        <template v-else-if="activeTab === 'detalles'">
            <form class="flex flex-col gap-6" @submit.prevent="submitDetalle">
                <!-- Datos del evento -->
                <fieldset class="space-y-4 rounded-xl border p-4">
                    <legend class="px-1 text-sm font-medium">
                        Datos del evento
                    </legend>

                    <div
                        class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-4"
                    >
                        <div class="space-y-1 sm:col-span-2">
                            <Label
                                >Nombre
                                <span class="text-destructive">*</span></Label
                            >
                            <Input
                                v-model="detalleForm.nombre"
                                maxlength="255"
                                required
                            />
                            <InputError :message="msgDetalle('nombre')" />
                        </div>
                        <div class="space-y-1 sm:col-span-2">
                            <Label>Lugar</Label>
                            <Input
                                v-model="detalleForm.lugar"
                                maxlength="255"
                            />
                            <InputError :message="msgDetalle('lugar')" />
                        </div>
                        <div class="space-y-1">
                            <Label>Fecha inicio</Label>
                            <Input
                                v-model="detalleForm.fecha_inicio"
                                type="date"
                            />
                            <InputError :message="msgDetalle('fecha_inicio')" />
                        </div>
                        <div class="space-y-1">
                            <Label>Fecha fin</Label>
                            <Input
                                v-model="detalleForm.fecha_fin"
                                type="date"
                            />
                            <InputError :message="msgDetalle('fecha_fin')" />
                        </div>
                        <div class="space-y-1">
                            <Label>Tamaño</Label>
                            <Select v-model="detalleForm.tamano">
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="CHICO">Chico</SelectItem>
                                    <SelectItem value="MEDIANO"
                                        >Mediano</SelectItem
                                    >
                                    <SelectItem value="GRANDE"
                                        >Grande</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-1">
                            <Label>Pago por evento (freelance)</Label>
                            <Input
                                v-model="detalleForm.pago_por_evento_completo"
                                type="number"
                                step="0.01"
                                min="0"
                                inputmode="decimal"
                            />
                            <InputError
                                :message="
                                    msgDetalle('pago_por_evento_completo')
                                "
                            />
                        </div>
                    </div>
                </fieldset>

                <!-- Contratación y detalle -->
                <fieldset class="space-y-4 rounded-xl border p-4">
                    <legend class="px-1 text-sm font-medium">
                        Contratación y detalle
                    </legend>

                    <div
                        class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3"
                    >
                        <div class="space-y-1">
                            <Label>Nombre del contratante</Label>
                            <Input
                                v-model="detalleForm.nombre_contratante"
                                maxlength="255"
                            />
                        </div>
                        <div class="space-y-1">
                            <Label>Teléfono del contratante</Label>
                            <Input
                                v-model="detalleForm.telefono_contratante"
                                type="tel"
                                inputmode="tel"
                                maxlength="13"
                                placeholder="10 dígitos"
                            />
                            <InputError
                                :message="msgDetalle('telefono_contratante')"
                            />
                        </div>
                        <div class="space-y-1">
                            <Label>Contacto del evento</Label>
                            <Input
                                v-model="detalleForm.contacto_nombre"
                                maxlength="255"
                            />
                        </div>
                        <div class="space-y-1">
                            <Label>Teléfono del contacto</Label>
                            <Input
                                v-model="detalleForm.contacto_telefono"
                                type="tel"
                                inputmode="tel"
                                maxlength="13"
                                placeholder="10 dígitos"
                            />
                            <InputError
                                :message="msgDetalle('contacto_telefono')"
                            />
                        </div>
                        <div class="space-y-1 sm:col-span-2 md:col-span-1">
                            <Label>Enlace de la ubicación</Label>
                            <Input
                                v-model="detalleForm.enlace_ubicacion"
                                type="url"
                                placeholder="https://maps.app.goo.gl/..."
                                maxlength="1000"
                            />
                            <InputError
                                :message="msgDetalle('enlace_ubicacion')"
                            />
                        </div>
                        <div class="space-y-1 sm:col-span-2 md:col-span-2">
                            <Label>Descripción del evento</Label>
                            <textarea
                                v-model="detalleForm.descripcion"
                                rows="3"
                                maxlength="5000"
                                class="w-full resize-none rounded-md border border-input bg-transparent px-3 py-1.5 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                            />
                            <InputError :message="msgDetalle('descripcion')" />
                        </div>
                        <div class="space-y-1 sm:col-span-2 md:col-span-3">
                            <Label>Observaciones técnicas</Label>
                            <textarea
                                v-model="detalleForm.observaciones_tecnicas"
                                rows="2"
                                maxlength="5000"
                                class="w-full resize-none rounded-md border border-input bg-transparent px-3 py-1.5 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                            />
                            <InputError
                                :message="msgDetalle('observaciones_tecnicas')"
                            />
                        </div>
                    </div>
                </fieldset>

                <div class="flex flex-wrap items-center gap-2">
                    <Button
                        type="submit"
                        :disabled="detalleForm.processing"
                        class="gap-1.5"
                    >
                        <Spinner v-if="detalleForm.processing" class="size-4" />
                        <Save v-else class="size-4" />
                        {{
                            detalleForm.processing
                                ? 'Guardando…'
                                : 'Guardar evento'
                        }}
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        class="gap-1.5"
                        @click="abrirImprimirDetalles"
                    >
                        <Printer class="size-3.5" />
                        Imprimir ficha
                    </Button>
                </div>
            </form>
        </template>

        <template v-if="activeTab === 'asignacion'">
            <!-- Responsable del evento -->
            <fieldset class="space-y-3 rounded-xl border p-4">
                <legend class="px-1 text-sm font-medium">
                    Responsable del evento
                </legend>
                <div class="flex flex-wrap items-end gap-3">
                    <div class="min-w-64 flex-1 space-y-1 sm:max-w-md">
                        <Label>Responsable (colaborador asignado)</Label>
                        <Select
                            v-model="responsableId"
                            :disabled="asignadosLocal.length === 0"
                        >
                            <SelectTrigger>
                                <SelectValue
                                    placeholder="Sin responsable asignado"
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value=""
                                    >Sin responsable</SelectItem
                                >
                                <SelectItem
                                    v-for="c in asignadosLocal"
                                    :key="c.id"
                                    :value="String(c.id)"
                                >
                                    {{ c.apellidos }}, {{ c.nombre }}
                                    <template
                                        v-if="c.tipo !== 'COLABORADOR BASE'"
                                    >
                                        ({{
                                            tipoBadge[c.tipo].label
                                        }})</template
                                    >
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p
                            v-if="asignadosLocal.length === 0"
                            class="text-xs text-muted-foreground"
                        >
                            Asigna colaboradores al evento para poder elegir un
                            responsable.
                        </p>
                    </div>
                    <Button
                        size="sm"
                        :disabled="guardandoResponsable"
                        @click="guardarResponsable"
                    >
                        <Spinner v-if="guardandoResponsable" class="size-3.5" />
                        {{
                            guardandoResponsable
                                ? 'Guardando…'
                                : 'Guardar responsable'
                        }}
                    </Button>
                </div>
                <p v-if="responsable" class="text-xs text-muted-foreground">
                    Actual:
                    <span class="font-medium text-foreground"
                        >{{ responsable.apellidos }},
                        {{ responsable.nombre }}</span
                    >
                    <template v-if="responsable.telefono">
                        · Tel: {{ responsable.telefono }}</template
                    >
                </p>
            </fieldset>

            <!-- Requisitos de personal: colapsable — editor de la matriz o resumen de progreso -->
            <fieldset class="space-y-3 rounded-xl border p-4">
                <div class="flex items-center justify-between">
                    <legend class="px-1 text-sm font-medium">
                        Requisitos de personal
                    </legend>
                    <button
                        type="button"
                        class="flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground"
                        @click="mostrarRequisitosForm = !mostrarRequisitosForm"
                    >
                        <component
                            :is="
                                mostrarRequisitosForm
                                    ? ChevronDown
                                    : ChevronRight
                            "
                            class="size-3.5"
                        />
                        {{ mostrarRequisitosForm ? 'Ocultar' : 'Editar' }}
                    </button>
                </div>

                <template v-if="mostrarRequisitosForm">
                    <p class="text-xs text-muted-foreground">
                        Cuántos colaboradores de cada categoría/nivel y cuántos
                        freelance necesita este evento — limita el buscador de
                        abajo a quienes cubren un requisito con cupo libre.
                    </p>

                    <div class="overflow-x-auto">
                        <table class="text-sm">
                            <thead>
                                <tr>
                                    <th class="px-2 py-1 text-left font-medium">
                                        Categoría
                                    </th>
                                    <th
                                        class="px-2 py-1 text-center font-medium"
                                    >
                                        Nivel 1
                                    </th>
                                    <th
                                        class="px-2 py-1 text-center font-medium"
                                    >
                                        Nivel 2
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="cat in CATEGORIAS" :key="cat">
                                    <td class="px-2 py-1 whitespace-nowrap">
                                        {{ cat }}
                                    </td>
                                    <td
                                        v-for="n in NIVELES"
                                        :key="n"
                                        class="px-2 py-1 text-center"
                                    >
                                        <Input
                                            v-model.number="
                                                requisitosForm.base[cat][
                                                    String(n)
                                                ]
                                            "
                                            type="number"
                                            min="0"
                                            max="99"
                                            step="1"
                                            class="h-8 w-20 text-center"
                                        />
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-2 py-1 font-medium">
                                        Freelance
                                    </td>
                                    <td
                                        class="px-2 py-1 text-center"
                                        colspan="2"
                                    >
                                        <Input
                                            v-model.number="
                                                requisitosForm.freelance
                                            "
                                            type="number"
                                            min="0"
                                            max="99"
                                            step="1"
                                            class="mx-auto h-8 w-20 text-center"
                                        />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex gap-2">
                        <Button
                            size="sm"
                            class="gap-1.5"
                            :disabled="guardandoRequisitos"
                            @click="guardarRequisitos"
                        >
                            <Spinner
                                v-if="guardandoRequisitos"
                                class="size-3.5"
                            />
                            {{ guardandoRequisitos ? 'Guardando…' : 'Guardar' }}
                        </Button>
                        <Button
                            size="sm"
                            variant="outline"
                            :disabled="guardandoRequisitos"
                            @click="limpiarRequisitos"
                            >Limpiar</Button
                        >
                    </div>
                </template>

                <template v-else>
                    <div
                        v-if="totalRequerido > 0"
                        class="flex flex-wrap gap-2 text-xs"
                    >
                        <template v-for="cat in CATEGORIAS" :key="cat">
                            <span
                                v-for="n in NIVELES"
                                v-show="
                                    (requisitos.base[cat]?.[String(n)] ?? 0) > 0
                                "
                                :key="`${cat}-${n}`"
                                class="inline-flex items-center gap-1 rounded-full px-2 py-0.5"
                                :class="
                                    asignadosDeCombo(cat, n) >=
                                    (requisitos.base[cat]?.[String(n)] ?? 0)
                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-400'
                                        : 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-400'
                                "
                            >
                                {{ categoriaCortaLabel[cat] }} N{{ n }}:
                                {{ asignadosDeCombo(cat, n) }}/{{
                                    requisitos.base[cat]?.[String(n)] ?? 0
                                }}
                                <button
                                    v-if="
                                        asignadosDeCombo(cat, n) <
                                        (requisitos.base[cat]?.[String(n)] ?? 0)
                                    "
                                    type="button"
                                    class="rounded-full hover:bg-black/10 dark:hover:bg-white/10"
                                    title="Asignar colaborador para este requisito"
                                    @click="
                                        abrirFiltroRapido({
                                            categoria: cat,
                                            nivel: n,
                                        })
                                    "
                                >
                                    <Plus class="size-3" />
                                </button>
                            </span>
                        </template>
                        <span
                            v-if="requisitos.freelance > 0"
                            class="inline-flex items-center gap-1 rounded-full px-2 py-0.5"
                            :class="
                                freelanceAsignados >= requisitos.freelance
                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-400'
                                    : 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-400'
                            "
                        >
                            Freelance: {{ freelanceAsignados }}/{{
                                requisitos.freelance
                            }}
                            <button
                                v-if="freelanceAsignados < requisitos.freelance"
                                type="button"
                                class="rounded-full hover:bg-black/10 dark:hover:bg-white/10"
                                title="Asignar Freelance"
                                @click="abrirFiltroRapido('freelance')"
                            >
                                <Plus class="size-3" />
                            </button>
                        </span>
                    </div>
                    <div
                        v-else
                        class="rounded-lg border bg-amber-50 p-3 text-xs text-amber-800 dark:bg-amber-900/30 dark:text-amber-400"
                    >
                        Este evento aún no tiene requisitos de personal
                        definidos — solo puedes asignar Conductores. Usa
                        "Editar" arriba para definirlos y poder asignar
                        colaboradores Base y Freelance.
                    </div>
                </template>
            </fieldset>

            <!-- Agregar -->
            <div class="space-y-2">
                <div
                    v-if="filtroRapido"
                    class="flex items-center gap-2 text-xs"
                >
                    <span
                        class="rounded-full bg-primary/10 px-2 py-0.5 font-medium text-primary"
                    >
                        Filtrando:
                        {{
                            filtroRapido === 'freelance'
                                ? 'Freelance'
                                : `${filtroRapido.categoria} · Nivel ${filtroRapido.nivel}`
                        }}
                    </span>
                    <button
                        type="button"
                        class="text-muted-foreground underline-offset-2 hover:text-foreground hover:underline"
                        @click="filtroRapido = null"
                    >
                        Quitar filtro
                    </button>
                </div>

                <ComboboxRoot
                    :open="comboboxAbierto"
                    :ignore-filter="true"
                    :disabled="disponiblesLocal.length === 0 || procesando"
                    class="w-full max-w-xl"
                    @update:open="alCambiarAbierto"
                    @update:model-value="seleccionarColaborador"
                >
                    <ComboboxAnchor
                        class="flex h-9 w-full items-center gap-2 rounded-md border border-input bg-transparent px-3 shadow-xs"
                    >
                        <Search class="size-4 shrink-0 text-muted-foreground" />
                        <ComboboxInput
                            v-model="busqueda"
                            :display-value="() => ''"
                            placeholder="Buscar colaborador por nombre y agregarlo..."
                            class="w-full flex-1 bg-transparent text-sm outline-none placeholder:text-muted-foreground disabled:cursor-not-allowed"
                            :disabled="
                                disponiblesLocal.length === 0 || procesando
                            "
                            @focus="comboboxAbierto = true"
                            @click="comboboxAbierto = true"
                        />
                    </ComboboxAnchor>
                    <ComboboxPortal>
                        <ComboboxContent
                            position="popper"
                            class="z-50 max-h-80 w-[var(--reka-combobox-trigger-width)] overflow-y-auto rounded-md border bg-popover text-popover-foreground shadow-md"
                        >
                            <ComboboxViewport class="p-1">
                                <ComboboxEmpty
                                    class="py-6 text-center text-sm text-muted-foreground"
                                >
                                    Sin colaboradores disponibles.
                                </ComboboxEmpty>
                                <ComboboxGroup
                                    v-for="grupo in gruposDisponibles"
                                    :key="grupo.label"
                                >
                                    <ComboboxLabel
                                        class="px-2 py-1.5 text-xs font-medium text-muted-foreground"
                                    >
                                        {{ grupo.label }}
                                    </ComboboxLabel>
                                    <ComboboxItem
                                        v-for="c in grupo.items"
                                        :key="c.id"
                                        :value="c.id"
                                        class="flex cursor-default items-center rounded-sm px-2 py-1.5 text-sm outline-hidden select-none focus:bg-accent focus:text-accent-foreground data-[highlighted]:bg-accent data-[highlighted]:text-accent-foreground"
                                    >
                                        {{ c.apellidos }}, {{ c.nombre }}
                                        <span
                                            v-if="c.tipo !== 'COLABORADOR BASE'"
                                            class="ml-1 text-xs text-muted-foreground"
                                        >
                                            ({{ tipoBadge[c.tipo].label }})
                                        </span>
                                    </ComboboxItem>
                                </ComboboxGroup>
                            </ComboboxViewport>
                        </ComboboxContent>
                    </ComboboxPortal>
                </ComboboxRoot>
            </div>

            <!-- Tabla asignados -->
            <div class="overflow-hidden rounded-xl border">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">
                                Nombre
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Tipo
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Categoría / Nivel
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                Acción
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="c in asignadosLocal" :key="c.id">
                            <td class="px-4 py-3 font-medium">
                                {{ c.apellidos }}, {{ c.nombre }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium"
                                    :class="tipoBadge[c.tipo].class"
                                >
                                    {{ tipoBadge[c.tipo].label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ categoriaNivelLabel(c) }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-1">
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        class="gap-1 text-xs"
                                        @click="perfilModalId = c.id"
                                    >
                                        <IdCard class="size-3.5" />
                                        Perfil
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        class="text-destructive"
                                        :disabled="procesando"
                                        @click="quitar(c)"
                                    >
                                        <Trash2 class="size-3.5" />
                                        Quitar
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="asignadosLocal.length === 0">
                            <td
                                colspan="4"
                                class="px-4 py-8 text-center text-sm text-muted-foreground"
                            >
                                Sin personal asignado
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Total cotizado al 100% -->
            <fieldset class="space-y-3 rounded-xl border p-4">
                <div class="flex items-center justify-between">
                    <legend class="px-1 text-sm font-medium">
                        Cotización (100% de participación, sin compensación ni
                        días extra)
                    </legend>
                    <Button
                        size="sm"
                        variant="outline"
                        class="gap-1.5 whitespace-nowrap"
                        @click="abrirImprimirCotizacion"
                    >
                        <Printer class="size-3.5" />
                        Imprimir cotización
                    </Button>
                </div>

                <p
                    v-if="!cotizacion.dias"
                    class="rounded-md bg-amber-50 px-3 py-2 text-sm text-amber-700 dark:bg-amber-950/40 dark:text-amber-400"
                >
                    Este evento no tiene fecha de inicio/fin capturada — no se
                    puede calcular el sueldo ni el extra de los colaboradores
                    Base. Complétalas en "Detalles" desde el listado de Eventos.
                </p>
                <p v-else class="text-xs text-muted-foreground">
                    Duración del evento: {{ cotizacion.dias }} día(s).
                </p>

                <div
                    v-if="cotizacion.base.length > 0"
                    class="overflow-x-auto rounded-lg border"
                >
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th
                                    class="px-3 py-2 text-left text-xs font-medium"
                                >
                                    Colaborador
                                </th>
                                <th
                                    class="px-3 py-2 text-left text-xs font-medium"
                                >
                                    Categoría
                                </th>
                                <th
                                    class="px-3 py-2 text-right text-xs font-medium"
                                >
                                    Sueldo
                                </th>
                                <th
                                    class="px-3 py-2 text-right text-xs font-medium"
                                >
                                    Extra evento
                                </th>
                                <th
                                    class="px-3 py-2 text-right text-xs font-medium"
                                >
                                    Total
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr
                                v-for="b in cotizacion.base"
                                :key="b.colaborador_id"
                            >
                                <td
                                    class="px-3 py-2 font-medium whitespace-nowrap"
                                >
                                    {{ b.apellidos }}, {{ b.nombre }}
                                </td>
                                <td
                                    class="px-3 py-2 whitespace-nowrap text-muted-foreground"
                                >
                                    {{ b.categoria
                                    }}<template v-if="b.nivel">
                                        · Nivel {{ b.nivel }}</template
                                    >
                                </td>
                                <td class="px-3 py-2 text-right tabular-nums">
                                    {{ fmtMoney(b.sueldo) }}
                                </td>
                                <td class="px-3 py-2 text-right tabular-nums">
                                    {{ fmtMoney(b.bono) }}
                                </td>
                                <td
                                    class="px-3 py-2 text-right font-medium tabular-nums"
                                >
                                    {{ fmtMoney(b.total) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="text-sm text-muted-foreground">
                    Sin colaboradores Base asignados todavía.
                </p>

                <div class="flex items-center justify-between text-sm">
                    <span class="text-muted-foreground">Subtotal Base</span>
                    <span class="font-medium tabular-nums">{{
                        fmtMoney(cotizacion.total_base)
                    }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-muted-foreground">
                        Freelance ({{ cotizacion.freelance_count }} ×
                        {{ fmtMoney(cotizacion.pago_por_freelance) }})
                    </span>
                    <span class="font-medium tabular-nums">{{
                        fmtMoney(cotizacion.total_freelance)
                    }}</span>
                </div>

                <hr class="border-border" />

                <div
                    class="flex items-center justify-between text-lg font-bold"
                >
                    <span>Total cotizado</span>
                    <span class="tabular-nums">{{
                        fmtMoney(cotizacion.total)
                    }}</span>
                </div>
            </fieldset>
        </template>

        <template v-else-if="activeTab === 'unidades'">
            <!-- Panel de unidades de transporte asignadas al evento -->
            <div class="space-y-3">
                <p class="text-sm text-muted-foreground">
                    Asigna las unidades de transporte que estarán disponibles
                    para este evento. Las unidades asignadas se incluyen al
                    imprimir la ficha de detalles, con su perfil (mini CV) al
                    final.
                </p>

                <!-- Agregar unidad -->
                <div class="flex flex-wrap items-end gap-2">
                    <div class="space-y-1">
                        <Label>Agregar unidad</Label>
                        <Select
                            v-model="nuevaUnidadId"
                            :disabled="
                                unidadesDisponiblesLocal.length === 0 ||
                                procesandoUnidades
                            "
                            class="w-full min-w-64"
                        >
                            <SelectTrigger>
                                <SelectValue
                                    placeholder="Seleccionar unidad..."
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="u in unidadesDisponiblesLocal"
                                    :key="u.id"
                                    :value="String(u.id)"
                                >
                                    {{ u.marca }} {{ u.modelo }}
                                    <template v-if="u.numero_placas">
                                        · {{ u.numero_placas }}</template
                                    >
                                    <template v-if="u.vehiculo">
                                        ({{ u.vehiculo.nombre }})</template
                                    >
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <Button
                        size="sm"
                        :disabled="!nuevaUnidadId || procesandoUnidades"
                        @click="agregarUnidad"
                    >
                        <Plus class="size-3.5" />
                        Asignar
                    </Button>
                </div>
                <p
                    v-if="unidadesDisponiblesLocal.length === 0"
                    class="text-xs text-muted-foreground"
                >
                    No hay unidades disponibles para asignar. Regístralas
                    primero en el módulo de Transportes.
                </p>

                <!-- Tabla asignadas -->
                <div class="overflow-x-auto rounded-xl border">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium">
                                    Unidad
                                </th>
                                <th class="px-4 py-3 text-left font-medium">
                                    Placas
                                </th>
                                <th class="px-4 py-3 text-left font-medium">
                                    Pertenencia
                                </th>
                                <th class="px-4 py-3 text-left font-medium">
                                    Categoría (tarifa)
                                </th>
                                <th class="px-4 py-3 text-left font-medium">
                                    Conductor
                                </th>
                                <th class="px-4 py-3 text-right font-medium">
                                    Acción
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="u in unidadesAsignadasLocal" :key="u.id">
                                <td class="px-4 py-3 font-medium">
                                    {{ u.marca }} {{ u.modelo }}
                                    <span
                                        v-if="u.alias"
                                        class="ml-1 text-xs text-muted-foreground"
                                        >({{ u.alias }})</span
                                    >
                                </td>
                                <td class="px-4 py-3 tabular-nums">
                                    {{ u.numero_placas ?? '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium"
                                        :class="
                                            u.pertenencia === 'PROPIA'
                                                ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200'
                                                : 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200'
                                        "
                                    >
                                        {{
                                            u.pertenencia === 'PROPIA'
                                                ? 'Propia'
                                                : 'Rentada'
                                        }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-muted-foreground">
                                    {{ u.vehiculo?.nombre ?? '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <Select
                                            :model-value="
                                                conductorId[u.id] ??
                                                (u.conductor
                                                    ? String(u.conductor.id)
                                                    : '')
                                            "
                                            @update:model-value="
                                                (v) =>
                                                    (conductorId[u.id] = v
                                                        ? String(v)
                                                        : '')
                                            "
                                            :disabled="
                                                conductoresDisponibles.length ===
                                                0
                                            "
                                            class="min-w-40"
                                        >
                                            <SelectTrigger class="h-7 text-xs">
                                                <SelectValue
                                                    placeholder="Sin conductor"
                                                />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value=""
                                                    >Sin conductor</SelectItem
                                                >
                                                <SelectItem
                                                    v-for="c in conductoresDisponibles"
                                                    :key="c.id"
                                                    :value="String(c.id)"
                                                >
                                                    {{ c.apellidos }},
                                                    {{ c.nombre }}
                                                    <template
                                                        v-if="
                                                            c.tipo !==
                                                            'COLABORADOR BASE'
                                                        "
                                                    >
                                                        ({{
                                                            tipoBadge[c.tipo]
                                                                .label
                                                        }})</template
                                                    >
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            class="h-7 gap-1 text-xs"
                                            :disabled="
                                                guardandoConductor.has(u.id) ||
                                                (!(conductorId[u.id] ?? '') &&
                                                    !u.conductor)
                                            "
                                            @click="guardarConductor(u)"
                                        >
                                            <Spinner
                                                v-if="
                                                    guardandoConductor.has(u.id)
                                                "
                                                class="size-3"
                                            />
                                            Guardar
                                        </Button>
                                    </div>
                                    <p
                                        v-if="
                                            conductoresDisponibles.length === 0
                                        "
                                        class="mt-0.5 text-xs text-muted-foreground"
                                    >
                                        Sin colaboradores asignados (no
                                        Freelance) disponibles.
                                    </p>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        class="text-destructive"
                                        :disabled="procesandoUnidades"
                                        @click="quitarUnidad(u)"
                                    >
                                        <Trash2 class="size-3.5" />
                                        Quitar
                                    </Button>
                                </td>
                            </tr>
                            <tr v-if="unidadesAsignadasLocal.length === 0">
                                <td
                                    colspan="6"
                                    class="px-4 py-8 text-center text-sm text-muted-foreground"
                                >
                                    Sin unidades asignadas a este evento.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>

        <template v-else-if="activeTab === 'info_bancaria'">
            <!-- Panel de datos bancarios de los colaboradores asignados al evento -->
            <p
                v-if="info_bancaria.length === 0"
                class="rounded-xl border border-dashed py-10 text-center text-sm text-muted-foreground"
            >
                Sin colaboradores asignados a este evento.
            </p>

            <div v-else class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                <div
                    v-for="item in info_bancaria"
                    :key="item.id"
                    class="rounded-xl border p-4"
                >
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="font-medium">
                            {{ item.apellidos }}, {{ item.nombre }}
                        </p>
                        <span
                            class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium"
                            :class="tipoBadge[item.tipo].class"
                        >
                            {{ tipoBadge[item.tipo].label }}
                        </span>
                        <span
                            v-if="item.telefono"
                            class="ml-auto text-xs text-muted-foreground"
                            >Tel: {{ item.telefono }}</span
                        >
                    </div>

                    <div
                        v-if="item.datos_bancarios.length === 0"
                        class="mt-3 rounded-lg border border-dashed p-3 text-center text-xs text-muted-foreground"
                    >
                        Sin datos bancarios registrados.
                    </div>

                    <div v-else class="mt-3 space-y-2">
                        <div
                            v-for="b in item.datos_bancarios"
                            :key="b.id"
                            class="rounded-lg border bg-muted/20 p-3"
                        >
                            <p class="text-sm font-medium">
                                {{ b.alias || b.banco || 'Registro bancario' }}
                            </p>
                            <div
                                class="mt-1 grid grid-cols-1 gap-x-4 gap-y-0.5 text-xs text-muted-foreground sm:grid-cols-2"
                            >
                                <p v-if="b.banco">
                                    <span class="text-muted-foreground/70"
                                        >Banco:</span
                                    >
                                    {{ b.banco }}
                                </p>
                                <p v-if="b.beneficiario">
                                    <span class="text-muted-foreground/70"
                                        >Beneficiario:</span
                                    >
                                    {{ b.beneficiario }}
                                </p>
                                <p v-if="b.clave_interbancaria">
                                    <span class="text-muted-foreground/70"
                                        >CLABE:</span
                                    >
                                    <span class="tabular-nums">{{
                                        b.clave_interbancaria
                                    }}</span>
                                </p>
                                <p v-if="b.numero_tarjeta">
                                    <span class="text-muted-foreground/70"
                                        >Tarjeta:</span
                                    >
                                    <span class="tabular-nums">{{
                                        b.numero_tarjeta
                                    }}</span>
                                </p>
                                <p v-if="b.comentario" class="sm:col-span-2">
                                    <span class="text-muted-foreground/70"
                                        >Comentario:</span
                                    >
                                    {{ b.comentario }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <template v-else-if="activeTab === 'nomina'">
            <!-- Widgets resumen -->
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4"
            >
                <div class="grid flex-1 grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-xl border p-4">
                        <p class="text-xs text-muted-foreground">Pagado</p>
                        <p class="mt-1 text-lg font-semibold text-emerald-600">
                            {{ fmtMoney(nomina.total_pagado) }}
                        </p>
                    </div>
                    <div class="rounded-xl border p-4">
                        <p class="text-xs text-muted-foreground">Por pagar</p>
                        <p class="mt-1 text-lg font-semibold text-amber-600">
                            {{ fmtMoney(nomina.total_por_pagar) }}
                        </p>
                    </div>
                    <div class="rounded-xl border p-4">
                        <p class="text-xs text-muted-foreground">
                            Subtotal Base (extra evento)
                        </p>
                        <p class="mt-1 text-lg font-semibold">
                            {{ fmtMoney(nomina.subtotal_base) }}
                        </p>
                    </div>
                    <div class="rounded-xl border p-4">
                        <p class="text-xs text-muted-foreground">
                            Subtotal Freelance
                        </p>
                        <p class="mt-1 text-lg font-semibold">
                            {{ fmtMoney(nomina.subtotal_freelance) }}
                        </p>
                    </div>
                </div>
                <Button
                    size="sm"
                    variant="outline"
                    class="gap-1.5 self-start whitespace-nowrap sm:mt-1"
                    @click="abrirImprimirNomina"
                >
                    <Printer class="size-3.5" />
                    Imprimir
                </Button>
            </div>

            <p
                v-if="nomina.freelance.length === 0 && nomina.base.length === 0"
                class="py-10 text-center text-sm text-muted-foreground"
            >
                Sin nóminas guardadas relacionadas con este evento. Calcúlalas y
                guárdalas desde el Panel de Validación.
            </p>

            <!-- Freelance -->
            <div v-if="nomina.freelance.length > 0" class="space-y-2">
                <h2 class="text-sm font-medium">Freelance</h2>
                <div class="overflow-hidden rounded-xl border">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th class="w-8 px-2 py-2"></th>
                                <th
                                    class="px-4 py-2 text-left text-xs font-medium"
                                >
                                    Colaborador
                                </th>
                                <th
                                    class="px-4 py-2 text-center text-xs font-medium"
                                >
                                    Estado
                                </th>
                                <th
                                    class="px-4 py-2 text-right text-xs font-medium"
                                >
                                    Total
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <template
                                v-for="n in nomina.freelance"
                                :key="n.nomina_id"
                            >
                                <tr
                                    class="cursor-pointer hover:bg-muted/20"
                                    @click="toggleFreelance(n.nomina_id)"
                                >
                                    <td class="px-2 py-2.5">
                                        <component
                                            :is="
                                                abiertoFreelance.has(
                                                    n.nomina_id,
                                                )
                                                    ? ChevronDown
                                                    : ChevronRight
                                            "
                                            class="size-4 text-muted-foreground"
                                        />
                                    </td>
                                    <td class="px-4 py-2.5 font-medium">
                                        {{ n.colaborador.apellidos }},
                                        {{ n.colaborador.nombre }}
                                    </td>
                                    <td class="px-4 py-2.5 text-center">
                                        <span
                                            class="rounded-full px-2 py-0.5 text-xs font-medium"
                                            :class="estadoBadge[n.estado]"
                                        >
                                            {{ n.estado }}
                                        </span>
                                    </td>
                                    <td
                                        class="px-4 py-2.5 text-right font-medium tabular-nums"
                                    >
                                        {{ fmtMoney(n.total_final) }}
                                    </td>
                                </tr>
                                <tr v-if="abiertoFreelance.has(n.nomina_id)">
                                    <td
                                        colspan="4"
                                        class="border-t bg-muted/10 px-4 py-3"
                                    >
                                        <div
                                            v-for="(r, i) in n.registros"
                                            :key="i"
                                            class="flex items-center justify-between gap-2 py-0.5 text-xs"
                                        >
                                            <span
                                                class="whitespace-nowrap tabular-nums"
                                                >{{ fmtFecha(r.fecha) }}</span
                                            >
                                            <span
                                                class="flex-1 truncate text-muted-foreground"
                                                >{{ r.etapa ?? '—' }}</span
                                            >
                                            <span
                                                class="rounded px-1.5 py-0.5 font-medium whitespace-nowrap"
                                                :class="
                                                    r.contabiliza
                                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-400'
                                                        : 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-400'
                                                "
                                            >
                                                {{
                                                    r.contabiliza
                                                        ? 'Contabilizada'
                                                        : 'Sin validar'
                                                }}
                                            </span>
                                        </div>
                                        <p
                                            v-if="n.registros.length === 0"
                                            class="text-muted-foreground"
                                        >
                                            Sin registros.
                                        </p>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Base -->
            <div v-if="nomina.base.length > 0" class="space-y-2">
                <h2 class="text-sm font-medium">
                    Base
                    <span class="font-normal text-muted-foreground"
                        >— solo el extra por día de evento; el sueldo diario no
                        es específico de este evento</span
                    >
                </h2>
                <div class="overflow-hidden rounded-xl border">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th class="w-8 px-2 py-2"></th>
                                <th
                                    class="px-4 py-2 text-left text-xs font-medium"
                                >
                                    Colaborador
                                </th>
                                <th
                                    class="px-4 py-2 text-left text-xs font-medium"
                                >
                                    Período de la nómina
                                </th>
                                <th
                                    class="px-4 py-2 text-center text-xs font-medium"
                                >
                                    Estado
                                </th>
                                <th
                                    class="px-4 py-2 text-right text-xs font-medium"
                                >
                                    Subtotal
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <template
                                v-for="n in nomina.base"
                                :key="n.nomina_id"
                            >
                                <tr
                                    class="cursor-pointer hover:bg-muted/20"
                                    @click="toggleBase(n.nomina_id)"
                                >
                                    <td class="px-2 py-2.5">
                                        <component
                                            :is="
                                                abiertoBase.has(n.nomina_id)
                                                    ? ChevronDown
                                                    : ChevronRight
                                            "
                                            class="size-4 text-muted-foreground"
                                        />
                                    </td>
                                    <td class="px-4 py-2.5 font-medium">
                                        {{ n.colaborador.apellidos }},
                                        {{ n.colaborador.nombre }}
                                    </td>
                                    <td
                                        class="px-4 py-2.5 whitespace-nowrap text-muted-foreground"
                                    >
                                        {{ fmtFecha(n.periodo_inicio) }} –
                                        {{ fmtFecha(n.periodo_fin) }}
                                    </td>
                                    <td class="px-4 py-2.5 text-center">
                                        <span
                                            class="rounded-full px-2 py-0.5 text-xs font-medium"
                                            :class="estadoBadge[n.estado]"
                                        >
                                            {{ n.estado }}
                                        </span>
                                    </td>
                                    <td
                                        class="px-4 py-2.5 text-right font-medium tabular-nums"
                                    >
                                        {{ fmtMoney(n.subtotal) }}
                                    </td>
                                </tr>
                                <tr v-if="abiertoBase.has(n.nomina_id)">
                                    <td
                                        colspan="5"
                                        class="border-t bg-muted/10 px-4 py-3"
                                    >
                                        <div
                                            v-for="j in n.jornadas"
                                            :key="j.fecha"
                                            class="flex items-center justify-between gap-2 py-0.5 text-xs"
                                        >
                                            <span
                                                class="whitespace-nowrap tabular-nums"
                                                >{{ fmtFecha(j.fecha) }}</span
                                            >
                                            <span
                                                class="flex-1 truncate text-muted-foreground"
                                                >{{
                                                    (j.detalle ?? '—').replace(
                                                        '\n',
                                                        ' · ',
                                                    )
                                                }}</span
                                            >
                                            <span
                                                class="font-medium whitespace-nowrap tabular-nums"
                                                >+{{ fmtMoney(j.bono) }}</span
                                            >
                                            <span
                                                class="rounded px-1.5 py-0.5 font-medium whitespace-nowrap"
                                                :class="
                                                    tipoPagoBadgeClass(
                                                        j.tipo_pago,
                                                    )
                                                "
                                            >
                                                {{
                                                    tipoPagoLabel(
                                                        j.tipo_pago,
                                                        j.traslape_pct,
                                                    )
                                                }}
                                            </span>
                                        </div>
                                        <p
                                            v-if="n.jornadas.length === 0"
                                            class="text-muted-foreground"
                                        >
                                            Sin jornadas.
                                        </p>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>

        <template v-else-if="activeTab === 'viaticos'">
            <div class="max-w-xs rounded-xl border p-4">
                <p class="text-xs text-muted-foreground">Subtotal Viáticos</p>
                <p class="mt-1 text-lg font-semibold">
                    {{ fmtMoney(viaticos.subtotal) }}
                </p>
            </div>

            <p
                v-if="viaticos.items.length === 0"
                class="py-10 text-center text-sm text-muted-foreground"
            >
                Sin viáticos registrados para este evento. Regístralos desde el
                módulo Viáticos.
            </p>

            <div v-else class="overflow-hidden rounded-xl border">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium">
                                Fecha
                            </th>
                            <th class="px-4 py-2 text-left text-xs font-medium">
                                Nombre
                            </th>
                            <th class="px-4 py-2 text-left text-xs font-medium">
                                Tipo
                            </th>
                            <th class="px-4 py-2 text-left text-xs font-medium">
                                Concepto
                            </th>
                            <th
                                class="px-4 py-2 text-right text-xs font-medium"
                            >
                                Monto
                            </th>
                            <th class="px-4 py-2 text-left text-xs font-medium">
                                Autoriza
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="v in viaticos.items" :key="v.id">
                            <td
                                class="px-4 py-2.5 whitespace-nowrap tabular-nums"
                            >
                                {{ fmtFecha(v.fecha) }}
                            </td>
                            <td
                                class="px-4 py-2.5 font-medium whitespace-nowrap"
                            >
                                {{ nombreViatico(v) }}
                                <span
                                    v-if="!v.colaborador"
                                    class="ml-1 text-xs font-normal text-muted-foreground"
                                    >(General)</span
                                >
                            </td>
                            <td class="px-4 py-2.5">
                                <span
                                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="tipoViaticoBadgeClass[v.tipo]"
                                >
                                    {{ tipoViaticoLabel[v.tipo] }}
                                </span>
                            </td>
                            <td
                                class="max-w-xs px-4 py-2.5 text-muted-foreground"
                            >
                                {{ v.concepto }}
                            </td>
                            <td
                                class="px-4 py-2.5 text-right font-medium tabular-nums"
                            >
                                {{ fmtMoney(parseFloat(v.monto)) }}
                            </td>
                            <td class="px-4 py-2.5 text-muted-foreground">
                                {{ v.autoriza ?? '—' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </template>
    </div>

    <ColaboradorPerfilModal
        :colaborador-id="perfilModalId"
        @close="perfilModalId = null"
    />
</template>
