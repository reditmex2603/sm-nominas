<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Unidades de transporte asignadas a cada evento (flotilla para el evento).
     */
    public function up(): void
    {
        Schema::create('evento_unidades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evento_id')->constrained('eventos')->cascadeOnDelete();
            $table->foreignId('transporte_unidad_id')->constrained('transporte_unidades')->cascadeOnDelete();
            $table->unique(['evento_id', 'transporte_unidad_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evento_unidades');
    }
};
