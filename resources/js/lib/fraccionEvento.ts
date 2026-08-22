/** Modo elegido en el Select; el porcentaje exacto de TRASLAPE se captura en un input aparte. */
export type FraccionEventoModo = 'COMPLETO' | 'TRASLAPE';

export const fraccionEventoModoOpciones: { value: FraccionEventoModo; label: string }[] = [
    { value: 'COMPLETO', label: '100%' },
    { value: 'TRASLAPE',  label: 'Traslape' },
];

/** Shape vieja (enum fijo), congelada en desgloses de nóminas guardadas antes de esta actualización. */
type FraccionEventoLegacy = 'COMPLETO' | 'TRASLAPE_50' | 'TRASLAPE_40';

const LEGACY_PCT: Record<FraccionEventoLegacy, number> = {
    COMPLETO: 100,
    TRASLAPE_50: 50,
    TRASLAPE_40: 40,
};

const esLegacy = (v: number | FraccionEventoLegacy): v is FraccionEventoLegacy => typeof v === 'string';

/** 100 = completo → sin Select en TRASLAPE; cualquier otro valor cae en modo TRASLAPE. */
export const fraccionEventoModo = (v: number | FraccionEventoLegacy | null): FraccionEventoModo => {
    const pct = v === null ? 100 : (esLegacy(v) ? LEGACY_PCT[v] : v);
    return pct === 100 ? 'COMPLETO' : 'TRASLAPE';
};

/** Acepta el porcentaje nuevo (número) o el enum viejo congelado en desgloses históricos. */
export const fraccionEventoLabel = (v: number | FraccionEventoLegacy | null): string | null => {
    if (v === null) return null;
    const pct = esLegacy(v) ? LEGACY_PCT[v] : v;
    return pct === 100 ? null : `Traslape ${pct}%`;
};
