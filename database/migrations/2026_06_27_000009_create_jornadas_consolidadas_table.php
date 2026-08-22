<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jornadas_consolidadas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('colaborador_id')->constrained('colaboradores')->cascadeOnDelete();
            $table->date('fecha');
            $table->time('entrada')->nullable();
            $table->time('salida')->nullable();
            $table->json('actividades')->nullable();
            $table->text('detalle')->nullable();
            $table->text('extras')->nullable();
            $table->text('evidencias')->nullable();
            $table->text('comentarios')->nullable();
            $table->boolean('validado')->default(false);
            $table->enum('tipo_pago', [
                'JORNADA_COMPLETA',
                'JORNADA_COMPLETA + EVENTO',
                'TRASLAPE_40',
                'TRASLAPE_50',
                'SIN_PAGO',
                'ERROR_EVENTO',
            ])->default('SIN_PAGO');
            $table->unique(['colaborador_id', 'fecha']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jornadas_consolidadas');
    }
};
