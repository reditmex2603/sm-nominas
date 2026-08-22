<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sistema de niveles por categoría (Base): cada categoría (Encargado de área, Técnico,
     * Stagehand SM) tiene 2 niveles, que a su vez determinan el extra por día de evento en
     * Parámetros del sistema. Nullable igual que `categoria` — los colaboradores existentes se
     * migran gradualmente; el módulo de Colaboradores exige ambos al crear o editar un Base.
     */
    public function up(): void
    {
        Schema::table('colaboradores', function (Blueprint $table) {
            $table->unsignedTinyInteger('nivel')->nullable()->after('categoria');
        });
    }

    public function down(): void
    {
        Schema::table('colaboradores', function (Blueprint $table) {
            $table->dropColumn('nivel');
        });
    }
};
