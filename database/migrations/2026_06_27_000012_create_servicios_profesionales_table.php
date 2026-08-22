<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servicios_profesionales', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('apellidos')->nullable();
            $table->enum('tipo', ['RIGGER', 'OPERADOR_AUDIO', 'OPERADOR_VIDEO', 'OPERADOR_LUZ', 'OTRO']);
            $table->foreignId('evento_id')->nullable()->constrained('eventos')->nullOnDelete();
            $table->string('concepto')->nullable();
            $table->decimal('monto', 10, 2);
            $table->date('fecha');
            $table->string('autoriza')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servicios_profesionales');
    }
};
