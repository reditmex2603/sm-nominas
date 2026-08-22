<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transportes_tarifas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehiculo_id')->constrained('transportes_vehiculos')->cascadeOnDelete();
            $table->foreignId('distancia_id')->constrained('transportes_distancias')->cascadeOnDelete();
            $table->decimal('tarifa', 10, 2)->default(0);
            $table->unique(['vehiculo_id', 'distancia_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transportes_tarifas');
    }
};
