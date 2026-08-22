export type TipoPago =
    | 'JORNADA_COMPLETA'
    | 'JORNADA_COMPLETA + EVENTO'
    | 'TRASLAPE'
    | 'SIN_PAGO'
    | 'ERROR_EVENTO';

export const tipoPagoOpciones: { value: TipoPago; label: string }[] = [
    { value: 'JORNADA_COMPLETA',          label: 'Jornada completa' },
    { value: 'JORNADA_COMPLETA + EVENTO', label: 'Jornada + Evento' },
    { value: 'TRASLAPE',                  label: 'Traslape' },
    { value: 'SIN_PAGO',                  label: 'Sin pago' },
    { value: 'ERROR_EVENTO',              label: 'Error: evento' },
];

export const tipoPagoBadgeClass = (v: TipoPago): string => {
    const mapa: Record<TipoPago, string> = {
        'JORNADA_COMPLETA':          'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200',
        'JORNADA_COMPLETA + EVENTO': 'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-200',
        'TRASLAPE':                  'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
        'SIN_PAGO':                  'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
        'ERROR_EVENTO':              'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
    };
    return mapa[v] ?? '';
};

/** Para TRASLAPE, pasa el porcentaje capturado (traslape_pct) para mostrarlo en el label. */
export const tipoPagoLabel = (v: TipoPago, traslapePct?: number | null): string => {
    if (v === 'TRASLAPE') {
        return traslapePct != null ? `Traslape ${traslapePct}%` : 'Traslape';
    }
    return tipoPagoOpciones.find(o => o.value === v)?.label ?? v;
};
