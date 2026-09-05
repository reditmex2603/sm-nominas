import { describe, expect, it } from 'vitest';
import { fraccionEventoLabel, fraccionEventoModo } from './fraccionEvento';

describe('fraccionEventoModo', () => {
    it('100 es COMPLETO', () => {
        expect(fraccionEventoModo(100)).toBe('COMPLETO');
    });

    it('null se trata como COMPLETO', () => {
        expect(fraccionEventoModo(null)).toBe('COMPLETO');
    });

    it('porcentaje menor a 100 es TRASLAPE', () => {
        expect(fraccionEventoModo(50)).toBe('TRASLAPE');
        expect(fraccionEventoModo(40)).toBe('TRASLAPE');
    });

    it('valores legacy congelados en desgloses históricos se interpretan', () => {
        expect(fraccionEventoModo('COMPLETO')).toBe('COMPLETO');
        expect(fraccionEventoModo('TRASLAPE_50')).toBe('TRASLAPE');
        expect(fraccionEventoModo('TRASLAPE_40')).toBe('TRASLAPE');
    });
});

describe('fraccionEventoLabel', () => {
    it('null no tiene label', () => {
        expect(fraccionEventoLabel(null)).toBeNull();
    });

    it('100 (completo) no tiene label', () => {
        expect(fraccionEventoLabel(100)).toBeNull();
    });

    it('porcentaje muestra "Traslape {pct}%"', () => {
        expect(fraccionEventoLabel(40)).toBe('Traslape 40%');
    });

    it('legacy 50 se traduce a "Traslape 50%"', () => {
        expect(fraccionEventoLabel('TRASLAPE_50')).toBe('Traslape 50%');
    });
});