<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Perfil de colaborador: datos de emergencia y documentos de identificación, opcionales
     * (nunca se piden al crear un colaborador) — el admin los completa después, para
     * requerimientos de la empresa. Tabla aparte de `colaboradores` porque es información
     * sensible/poco consultada, no relevante en los listados operativos del día a día.
     */
    public function up(): void
    {
        Schema::create('colaborador_perfiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('colaborador_id')->unique()->constrained('colaboradores')->cascadeOnDelete();

            // Datos de emergencia
            $table->string('tipo_sangre')->nullable();
            $table->text('alergias')->nullable();
            $table->text('padecimientos_cronicos')->nullable();
            $table->string('numero_seguro_social')->nullable();
            $table->string('seguro_social_documento_path')->nullable();

            // Documentos de identificación
            $table->string('ine_documento_path')->nullable();
            $table->string('curp_documento_path')->nullable();
            $table->string('comprobante_domicilio_documento_path')->nullable();
            $table->string('licencia_conducir_documento_path')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colaborador_perfiles');
    }
};
