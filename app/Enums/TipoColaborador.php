<?php

namespace App\Enums;

/** Tipos de personal en la nómina (colaboradores.tipo, historico_nomina.tipo_colaborador). */
enum TipoColaborador: string
{
    case Base = 'COLABORADOR BASE';
    case Freelance = 'FREELANCE';
    case Conductor = 'CONDUCTOR';
    case ConductorBase = 'CONDUCTOR BASE';

    /** ¿Recibe sueldo diario fijo (Base y Conductor Base)? */
    public function tieneSueldoDiario(): bool
    {
        return in_array($this, [self::Base, self::ConductorBase], true);
    }

    /** Actividades permitidas por tipo (ver AsistenciaPublicaController). */
    /** @return array<int, string> */
    public function actividadesPermitidas(): array
    {
        return match ($this) {
            self::Base => ['Bodega', 'Evento'],
            self::Freelance => ['Evento'],
            self::Conductor => ['Transporte'],
            self::ConductorBase => ['Bodega', 'Transporte'],
        };
    }
}
