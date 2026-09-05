import { describe, expect, it } from 'vitest';
import { tipoPagoLabel } from './tipoPago';

describe('tipoPagoLabel', () => {
    it('etiqueta simple para cada tipo', () => {
        expect(tipoPagoLabel('JORNADA_COMPLETA')).toBe('Jornada completa');
        expect(tipoPagoLabel('JORNADA_COMPLETA + EVENTO')).toBe('Jornada + Evento');
        expect(tipoPagoLabel('SIN_PAGO')).toBe('Sin pago');
        expect(tipoPagoLabel('ERROR_EVENTO')).toBe('Error: evento');
    });

    it('TRASLAPE sin porcentaje muestra solo "Traslape"', () => {
        expect(tipoPagoLabel('TRASLAPE')).toBe('Traslape');
        expect(tipoPagoLabel('TRASLAPE', null)).toBe('Traslape');
    });

    it('TRASLAPE con porcentaje muestra "Traslape {pct}%"', () => {
        expect(tipoPagoLabel('TRASLAPE', 40)).toBe('Traslape 40%');
        expect(tipoPagoLabel('TRASLAPE', 0)).toBe('Traslape 0%');
    });
});