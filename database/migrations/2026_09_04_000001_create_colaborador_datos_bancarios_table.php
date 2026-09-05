<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Datos de registro bancario de un colaborador. Un colaborador puede tener 1 o más
     * registros (banco, beneficiario, CLABE, número de tarjeta, alias y comentario).
     */
    public function up(): void
    {
        Schema::create('colaborador_datos_bancarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('colaborador_id')->constrained('colaboradores')->cascadeOnDelete();
            $table->string('banco')->nullable();
            $table->string('beneficiario')->nullable();
            // La CLABE se guarda cifrada por el cast EncryptedOrDefault, por eso el ancho
            // de la columna es generoso (no el de una CLABE en texto plano).
            $table->string('clave_interbancaria')->nullable();
            $table->string('numero_tarjeta')->nullable();
            $table->string('alias')->nullable();
            $table->text('comentario')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colaborador_datos_bancarios');
    }
};
