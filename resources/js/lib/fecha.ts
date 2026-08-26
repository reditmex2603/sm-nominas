/** Convierte "yyyy-mm-dd" → "dd-mm-yyyy". Devuelve "—" si el valor es nulo. */
export const fmtFecha = (fecha: string | null | undefined): string => {
    if (!fecha) {
return '—';
}

    const [y, m, d] = fecha.slice(0, 10).split('-');

    return `${d}-${m}-${y}`;
};

const toYmd = (d: Date): string => {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');

    return `${y}-${m}-${day}`;
};

const lunesDe = (fecha: Date): Date => {
    const dia = fecha.getDay(); // 0=Dom,1=Lun,...,6=Sáb
    const offset = dia === 0 ? -6 : 1 - dia;
    const lunes = new Date(fecha);
    lunes.setDate(fecha.getDate() + offset);

    return lunes;
};

/** Semana laboral (lunes→sábado, regla de corte semanal) que contiene `fecha` (hoy por defecto). */
export const semanaBase = (fecha: Date = new Date()): { inicio: string; fin: string } => {
    const lunes = lunesDe(fecha);
    const sabado = new Date(lunes);
    sabado.setDate(lunes.getDate() + 5);

    return { inicio: toYmd(lunes), fin: toYmd(sabado) };
};

const parseYmd = (fecha: string): Date | null => {
    const [y, m, d] = fecha.split('-').map(Number);

    if (!y || !m || !d) {
return null;
}

    return new Date(y, m - 1, d);
};

/**
 * true si [inicio, fin] es un corte de una O VARIAS semanas completas (inicio en lunes, fin en
 * sábado, fin >= inicio) — el período de nómina ya no está limitado a una sola semana, pero debe
 * seguir respetando el corte semanal lunes→sábado en sus extremos.
 */
export const esRangoSemanasCompleto = (inicio: string, fin: string): boolean => {
    const dIni = parseYmd(inicio);
    const dFin = parseYmd(fin);

    if (!dIni || !dFin) {
return false;
}

    return dIni.getDay() === 1 && dFin.getDay() === 6 && dFin >= dIni;
};

/** Desplaza `fecha` (yyyy-mm-dd) N semanas (7 × n días); n negativo retrocede. */
export const desplazarSemanas = (fecha: string, n: number): string => {
    const d = parseYmd(fecha);

    if (!d) {
return fecha;
}

    d.setDate(d.getDate() + n * 7);

    return toYmd(d);
};
