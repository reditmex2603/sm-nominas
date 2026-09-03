<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('evento');                 // p.ej. nomina.pagada, cuota.pagada
            $table->string('modelo')->nullable();     // clase del sujeto (HistoricoNomina, PrestamoCuota)
            $table->unsignedBigInteger('modelo_id')->nullable();
            $table->json('detalles')->nullable();     // metadatos (montos, ids, antes/después)
            $table->timestamps();

            $table->index(['modelo', 'modelo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditorias');
    }
};
