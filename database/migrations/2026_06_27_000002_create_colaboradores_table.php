<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colaboradores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('apellidos');
            $table->enum('tipo', ['COLABORADOR BASE', 'FREELANCE', 'CONDUCTOR']);
            $table->decimal('sueldo_diario', 10, 2)->nullable();
            $table->decimal('bono_por_evento', 10, 2)->nullable();
            $table->decimal('extra_dia_adicional', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colaboradores');
    }
};
