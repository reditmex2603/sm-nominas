<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('viaticos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('apellidos')->nullable();
            $table->enum('tipo', ['TRANSPORTE', 'HOSPEDAJE', 'ALIMENTOS', 'CASETAS_GASOLINA', 'OTRO']);
            // A diferencia de Servicios Profesionales, el evento es obligatorio — un viático
            // siempre es un gasto DE un evento. restrictOnDelete: no se puede borrar un evento
            // con viáticos registrados (mismo criterio que ya aplica EventoController::destroy
            // para nóminas).
            $table->foreignId('evento_id')->constrained('eventos')->restrictOnDelete();
            $table->string('concepto')->nullable();
            $table->decimal('monto', 10, 2);
            $table->date('fecha');
            $table->string('autoriza')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('viaticos');
    }
};
