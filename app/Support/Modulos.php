<?php

namespace App\Support;

/** Catálogo de módulos del sistema con sus permisos (whitelist de acceso). */
final class Modulos
{
    public const MODULOS = [
        'validacion' => 'Panel Validación',
        'colaboradores' => 'Colaboradores',
        'eventos' => 'Eventos',
        'transportes' => 'Transportes',
        'anticipos' => 'Anticipos',
        'prestamos' => 'Préstamos',
        'servicios-profesionales' => 'Servicios Profesionales',
        'viaticos' => 'Viáticos',
        'historial' => 'Historial',
        'registro-asistencia' => 'Registro de Asistencia',
        'nomina' => 'Nómina y Jornadas',
        'manual' => 'Manual de usuario',
    ];

    /** @return array<int, string> */
    public static function claves(): array
    {
        return array_keys(self::MODULOS);
    }
}
