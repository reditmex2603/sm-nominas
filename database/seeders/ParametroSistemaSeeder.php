<?php

namespace Database\Seeders;

use App\Models\ParametroSistema;
use Illuminate\Database\Seeder;

class ParametroSistemaSeeder extends Seeder
{
    public function run(): void
    {
        $parametros = [
            [
                'clave' => 'pago_default_chico',
                'valor' => '1500',
                'descripcion' => 'Pago por defecto para eventos CHICO',
            ],
            [
                'clave' => 'pago_default_mediano',
                'valor' => '2500',
                'descripcion' => 'Pago por defecto para eventos MEDIANO',
            ],
            [
                'clave' => 'pago_default_grande',
                'valor' => '3000',
                'descripcion' => 'Pago por defecto para eventos GRANDE',
            ],
            [
                'clave' => 'dias_bono_septimo',
                'valor' => '6',
                'descripcion' => 'Días requeridos de lunes a sábado para generar bono de 7° día',
            ],
            // Extra por día de evento (Base): depende de la categoría del colaborador, su NIVEL
            // (1 o 2 dentro de esa categoría) Y del tamaño del evento. CHICO no tiene parámetro:
            // nunca genera bono. Valores placeholder — nivel 2 arranca con un +20% sobre nivel 1,
            // el usuario los debe ajustar en /parametros.
            [
                'clave' => 'bono_evento_encargado_nivel1_mediano',
                'valor' => '500',
                'descripcion' => 'Extra por día de evento MEDIANO (Base) — Encargado de área, nivel 1',
            ],
            [
                'clave' => 'bono_evento_encargado_nivel1_grande',
                'valor' => '700',
                'descripcion' => 'Extra por día de evento GRANDE (Base) — Encargado de área, nivel 1',
            ],
            [
                'clave' => 'bono_evento_encargado_nivel2_mediano',
                'valor' => '600',
                'descripcion' => 'Extra por día de evento MEDIANO (Base) — Encargado de área, nivel 2',
            ],
            [
                'clave' => 'bono_evento_encargado_nivel2_grande',
                'valor' => '840',
                'descripcion' => 'Extra por día de evento GRANDE (Base) — Encargado de área, nivel 2',
            ],
            [
                'clave' => 'bono_evento_tecnico_nivel1_mediano',
                'valor' => '350',
                'descripcion' => 'Extra por día de evento MEDIANO (Base) — Técnico, nivel 1',
            ],
            [
                'clave' => 'bono_evento_tecnico_nivel1_grande',
                'valor' => '500',
                'descripcion' => 'Extra por día de evento GRANDE (Base) — Técnico, nivel 1',
            ],
            [
                'clave' => 'bono_evento_tecnico_nivel2_mediano',
                'valor' => '420',
                'descripcion' => 'Extra por día de evento MEDIANO (Base) — Técnico, nivel 2',
            ],
            [
                'clave' => 'bono_evento_tecnico_nivel2_grande',
                'valor' => '600',
                'descripcion' => 'Extra por día de evento GRANDE (Base) — Técnico, nivel 2',
            ],
            [
                'clave' => 'bono_evento_stagehand_nivel1_mediano',
                'valor' => '200',
                'descripcion' => 'Extra por día de evento MEDIANO (Base) — Stagehand SM, nivel 1',
            ],
            [
                'clave' => 'bono_evento_stagehand_nivel1_grande',
                'valor' => '300',
                'descripcion' => 'Extra por día de evento GRANDE (Base) — Stagehand SM, nivel 1',
            ],
            [
                'clave' => 'bono_evento_stagehand_nivel2_mediano',
                'valor' => '240',
                'descripcion' => 'Extra por día de evento MEDIANO (Base) — Stagehand SM, nivel 2',
            ],
            [
                'clave' => 'bono_evento_stagehand_nivel2_grande',
                'valor' => '360',
                'descripcion' => 'Extra por día de evento GRANDE (Base) — Stagehand SM, nivel 2',
            ],
        ];

        // Los parámetros viejos (un solo monto por categoría+tamaño, sin distinguir nivel) ya no
        // se usan — se eliminan si existen de una siembra anterior.
        ParametroSistema::whereIn('clave', [
            'bono_evento_encargado', 'bono_evento_tecnico', 'bono_evento_stagehand',
            'bono_evento_encargado_mediano', 'bono_evento_encargado_grande',
            'bono_evento_tecnico_mediano', 'bono_evento_tecnico_grande',
            'bono_evento_stagehand_mediano', 'bono_evento_stagehand_grande',
        ])->delete();

        foreach ($parametros as $parametro) {
            ParametroSistema::updateOrCreate(
                ['clave' => $parametro['clave']],
                $parametro
            );
        }
    }
}
