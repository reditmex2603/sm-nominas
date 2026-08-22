<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Compensación: bono extra para colaboradores Base, expresado como % (0-100) que se aplica
     * sobre el Extra por día de evento ya existente (categoría+nivel+tamaño). El % vive en el
     * colaborador (`compensacion_pct`, default 0); el admin decide día por día si se aplica
     * marcando `compensacion_activa` en la jornada desde el Panel de Validación.
     */
    public function up(): void
    {
        Schema::table('colaboradores', function (Blueprint $table) {
            $table->unsignedTinyInteger('compensacion_pct')->default(0)->after('nivel');
        });

        Schema::table('jornadas_consolidadas', function (Blueprint $table) {
            $table->boolean('compensacion_activa')->default(false)->after('fracciones_evento');
        });
    }

    public function down(): void
    {
        Schema::table('jornadas_consolidadas', function (Blueprint $table) {
            $table->dropColumn('compensacion_activa');
        });

        Schema::table('colaboradores', function (Blueprint $table) {
            $table->dropColumn('compensacion_pct');
        });
    }
};
