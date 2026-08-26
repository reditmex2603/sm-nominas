<script setup lang="ts">
import { fmtFecha } from '@/lib/fecha';
import { fraccionEventoLabel } from '@/lib/fraccionEvento';
import { tipoPagoBadgeClass, tipoPagoLabel  } from '@/lib/tipoPago';
import type {TipoPago} from '@/lib/tipoPago';

interface Desglose {
    _jornadas?: {
        fecha: string;
        tipo_pago: TipoPago;
        traslape_pct?: number | null;
        detalle: string | null;
        extras: string | null;
        bono_evento: number;
        dia_fraccion?: number;
        eventos_dia?: { nombre: string; tamano: string; pct_etapas: number; bono: number; compensacion?: number; fraccion: number | 'COMPLETO' | 'TRASLAPE_50' | 'TRASLAPE_40' | null }[];
    }[];
    _categoria?: string | null;
    _nivel?: number | null;
    _rutas?: { fecha?: string; vehiculo: string; distancia: string; monto: number; extras: string | null }[];
    _etapas?: string[];
    _registros?: { fecha: string; etapa: string | null; extras: string | null; contabiliza: boolean }[];
    _prestamo_detalle?: { id: number; concepto: string | null; numero_plazo: number; monto: number; fecha_programada: string }[];
    _bono_septimo?: number;
    _sueldo_diario?: number;
    sueldo_diario?: number;
}

const props = defineProps<{
    tipoColaborador: 'COLABORADOR BASE' | 'FREELANCE' | 'CONDUCTOR' | 'CONDUCTOR BASE';
    desglose: Desglose | null;
    /** Fallback con el valor de la columna `sueldo_diario` de la nómina (nóminas viejas no lo tienen en el desglose). */
    sueldoDiario?: number;
}>();

const rutasDe = (fecha: string) =>
    (props.desglose?._rutas ?? []).filter((r) => String(r.fecha).slice(0, 10) === fecha);

const pagoRutasFecha = (fecha: string): number =>
    rutasDe(fecha).reduce((acc, r) => acc + (r.monto ?? 0), 0);

// Sueldo diario efectivo del desglose (persistido en `_sueldo_diario` desde el cálculo, con
// fallback al desglose en memoria y al prop `sueldoDiario` para nóminas viejas).
const sueldoDia = (fraccion = 1): number =>
    (props.desglose?._sueldo_diario ?? props.desglose?.sueldo_diario ?? props.sueldoDiario ?? 0) * fraccion;

// El `detalle` de la jornada vuelve a nombrar cada evento ("Evento: X · etapa"); como los eventos
// ya se muestran uno por uno en `eventos_dia` con (tipo - % + $total), lo filtramos para no
// mostrarlo dos veces (solo se conservan las líneas que NO describen un evento).
const detalleSinEventos = (detalle: string | null): string =>
    (detalle ?? '')
        .split('\n')
        .filter((linea) => !/^Evento:/i.test(linea.trim()))
        .join(' · ');
</script>

<template>
    <div class="space-y-1.5 text-sm">
        <p v-if="!desglose" class="text-muted-foreground">
            Detalle no disponible para nóminas guardadas antes de esta actualización. Vuelve a calcular y guardar la nómina en el Panel de Validación para generar el desglose.
        </p>

        <template v-else-if="tipoColaborador === 'COLABORADOR BASE'">
            <!-- _nivel puede faltar por dos razones distintas en JSON congelado: `undefined` (la
                 nómina se calculó antes de que existiera el nivel — no se puede saber si aplicaba)
                 vs. `null` explícito (el cálculo sí conocía el campo pero el colaborador no tenía
                 nivel asignado) — solo la segunda amerita el aviso. -->
            <p v-if="!desglose._categoria || desglose._nivel === null" class="mb-1 rounded bg-amber-50 px-2 py-1 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400">
                Este colaborador no tenía categoría y/o nivel asignado al momento del cálculo — el extra de evento se calculó en $0.
            </p>
            <div v-for="j in desglose._jornadas ?? []" :key="j.fecha" class="space-y-0.5">
                <div class="flex items-center justify-between gap-2">
                    <span class="tabular-nums whitespace-nowrap shrink-0">{{ fmtFecha(j.fecha) }}</span>
                    <span class="text-muted-foreground min-w-0 flex-1 truncate">{{ detalleSinEventos(j.detalle) }}</span>
                    <span v-if="sueldoDia(j.dia_fraccion) > 0" class="tabular-nums font-medium whitespace-nowrap shrink-0">
                        +${{ sueldoDia(j.dia_fraccion).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
                    </span>
                    <span v-if="j.bono_evento > 0" class="tabular-nums font-medium whitespace-nowrap shrink-0">+${{ j.bono_evento.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
                    <span class="rounded px-1.5 py-0.5 text-xs font-medium whitespace-nowrap shrink-0" :class="tipoPagoBadgeClass(j.tipo_pago)">
                        {{ tipoPagoLabel(j.tipo_pago, j.traslape_pct) }}
                    </span>
                </div>
                <p v-for="ev in j.eventos_dia ?? []" :key="ev.nombre" class="pl-[3.25rem] text-muted-foreground">
                    {{ ev.nombre }} ({{ ev.tamano }} - {{ ev.pct_etapas }}%<template v-if="fraccionEventoLabel(ev.fraccion)"> · {{ fraccionEventoLabel(ev.fraccion) }}</template>): +${{ ev.bono.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}<span v-if="ev.compensacion" class="text-emerald-600 dark:text-emerald-500"> (incl. compensación +${{ ev.compensacion.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }})</span>
                </p>
                <p v-if="j.extras" class="pl-[3.25rem] text-amber-600 dark:text-amber-500">Extra: {{ j.extras }}</p>
            </div>
            <p v-if="(desglose._jornadas ?? []).length === 0" class="text-muted-foreground">Sin jornadas registradas en este cálculo.</p>
            <p v-if="desglose._categoria" class="mt-1 text-muted-foreground">
                Categoría al momento del cálculo: {{ desglose._categoria }}<template v-if="desglose._nivel"> · Nivel {{ desglose._nivel }}</template>
            </p>
        </template>

        <template v-else-if="tipoColaborador === 'FREELANCE'">
            <div v-for="(r, i) in desglose._registros ?? []" :key="i" class="space-y-0.5">
                <div class="flex items-center justify-between gap-2">
                    <span class="tabular-nums whitespace-nowrap shrink-0">{{ fmtFecha(r.fecha) }}</span>
                    <span class="text-muted-foreground min-w-0 flex-1 truncate">{{ r.etapa ?? '—' }}</span>
                    <span
                        class="rounded px-1.5 py-0.5 text-xs font-medium whitespace-nowrap shrink-0"
                        :class="r.contabiliza
                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-400'
                            : 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-400'"
                    >
                        {{ r.contabiliza ? 'Contabilizada' : 'Sin validar' }}
                    </span>
                </div>
                <p v-if="r.extras" class="pl-[3.25rem] text-amber-600 dark:text-amber-500">Extra: {{ r.extras }}</p>
            </div>
            <p v-if="(desglose._registros ?? []).length === 0" class="text-muted-foreground">Sin registros de asistencia para este evento.</p>
        </template>

        <template v-else-if="tipoColaborador === 'CONDUCTOR'">
            <div v-for="(ruta, i) in desglose._rutas ?? []" :key="i" class="space-y-0.5">
                <div class="flex items-center justify-between gap-2">
                    <span class="tabular-nums whitespace-nowrap shrink-0">{{ fmtFecha(ruta.fecha) }}</span>
                    <span class="text-muted-foreground min-w-0 flex-1 truncate">{{ ruta.vehiculo }} · {{ ruta.distancia }}</span>
                    <span class="tabular-nums font-medium whitespace-nowrap shrink-0">${{ ruta.monto.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
                </div>
                <p v-if="ruta.extras" class="pl-[3.25rem] text-amber-600 dark:text-amber-500">Extra: {{ ruta.extras }}</p>
            </div>
            <p v-if="(desglose._rutas ?? []).length === 0" class="text-muted-foreground">Sin rutas detectadas en este período.</p>
        </template>

        <template v-else-if="tipoColaborador === 'CONDUCTOR BASE'">
            <!-- Cada jornada válida (Bodega o Ruta) cuenta un día completo de sueldo; además se
                 desglosan las rutas de transporte detectadas ese día (tarifa x traslape). -->
            <div v-for="j in desglose._jornadas ?? []" :key="j.fecha" class="space-y-0.5">
                <div class="flex items-center justify-between gap-2">
                    <span class="tabular-nums whitespace-nowrap shrink-0">{{ fmtFecha(j.fecha) }}</span>
                    <span class="text-muted-foreground min-w-0 flex-1 truncate">{{ detalleSinEventos(j.detalle) }}</span>
                    <span class="tabular-nums font-medium whitespace-nowrap shrink-0">
                        +${{ sueldoDia().toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
                    </span>
                    <span v-if="pagoRutasFecha(j.fecha) > 0" class="tabular-nums font-medium whitespace-nowrap shrink-0">
                        Transporte: +${{ pagoRutasFecha(j.fecha).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
                    </span>
                    <span class="rounded px-1.5 py-0.5 text-xs font-medium whitespace-nowrap shrink-0" :class="tipoPagoBadgeClass(j.tipo_pago)">
                        {{ tipoPagoLabel(j.tipo_pago, j.traslape_pct) }}
                    </span>
                </div>
                <p v-for="r in rutasDe(j.fecha)" :key="`${r.vehiculo}·${r.distancia}·${j.fecha}`" class="pl-[3.25rem] text-muted-foreground">
                    Ruta: {{ r.vehiculo }} · {{ r.distancia }}
                    <span class="tabular-nums font-medium">(${{ r.monto.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }})</span>
                </p>
                <p v-if="j.extras" class="pl-[3.25rem] text-amber-600 dark:text-amber-500">Extra: {{ j.extras }}</p>
            </div>
            <p v-if="(desglose._jornadas ?? []).length === 0" class="text-muted-foreground">Sin jornadas registradas en este período.</p>
            <p v-if="(desglose._bono_septimo ?? 0) > 0" class="mt-1 text-muted-foreground">
                Incluye bono de séptimo día: +${{ (desglose._bono_septimo ?? 0).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
            </p>
        </template>

        <div v-if="(desglose?._prestamo_detalle ?? []).length > 0" class="mt-2 border-t pt-2">
            <p class="text-muted-foreground mb-1 text-xs font-medium">Cuotas de préstamo aplicadas:</p>
            <p v-for="c in desglose?._prestamo_detalle ?? []" :key="c.id" class="text-muted-foreground text-xs">
                Plazo {{ c.numero_plazo }}<template v-if="c.concepto"> ({{ c.concepto }})</template> — {{ fmtFecha(c.fecha_programada) }}: -${{ c.monto.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
            </p>
        </div>
    </div>
</template>
