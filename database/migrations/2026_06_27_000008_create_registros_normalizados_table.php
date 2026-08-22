<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registros_normalizados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('colaborador_id')->constrained('colaboradores')->cascadeOnDelete();
            $table->enum('tipo_actividad', ['Bodega', 'Evento', 'Transporte']);
            $table->string('actividad')->nullable();
            $table->string('evento_raw')->nullable();
            $table->enum('etapa', ['Montaje', 'Show', 'Desmontaje'])->nullable();
            $table->string('vehiculo')->nullable();
            $table->string('distancia')->nullable();
            $table->string('origen')->nullable();
            $table->string('destino')->nullable();
            $table->text('extras')->nullable();
            $table->string('evidencia_path')->nullable();
            $table->text('comentarios')->nullable();
            $table->date('fecha');
            $table->time('hora');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registros_normalizados');
    }
};
