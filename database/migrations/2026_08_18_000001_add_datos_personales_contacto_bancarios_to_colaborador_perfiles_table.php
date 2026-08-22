<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Propiedades adicionales del perfil de colaborador: datos personales de contacto,
     * contactos de emergencia y datos bancarios. Fecha de ingreso, teléfono y WhatsApp
     * son obligatorios; el resto queda opcional (mismo criterio que el perfil existente).
     */
    public function up(): void
    {
        Schema::table('colaborador_perfiles', function (Blueprint $table) {
            // Datos personales
            $table->string('alias')->nullable();
            $table->string('fotografia_path')->nullable();
            $table->date('fecha_ingreso')->nullable();
            $table->string('correo')->nullable();
            $table->string('telefono')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('redes_sociales', 500)->nullable();
            $table->text('domicilio')->nullable();
            $table->string('genero')->nullable();
            $table->string('ubicacion_maps', 1000)->nullable();
            $table->date('fecha_nacimiento')->nullable();

            // Contactos de emergencia (hasta 2)
            $table->string('contacto_emergencia_1_nombre')->nullable();
            $table->string('contacto_emergencia_1_parentesco')->nullable();
            $table->string('contacto_emergencia_1_telefono')->nullable();
            $table->string('contacto_emergencia_2_nombre')->nullable();
            $table->string('contacto_emergencia_2_parentesco')->nullable();
            $table->string('contacto_emergencia_2_telefono')->nullable();

            // Datos bancarios
            $table->string('banco')->nullable();
            $table->string('beneficiario')->nullable();
            $table->string('clave_interbancaria')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('colaborador_perfiles', function (Blueprint $table) {
            $table->dropColumn([
                'alias',
                'fotografia_path',
                'fecha_ingreso',
                'correo',
                'telefono',
                'whatsapp',
                'redes_sociales',
                'domicilio',
                'genero',
                'ubicacion_maps',
                'fecha_nacimiento',
                'contacto_emergencia_1_nombre',
                'contacto_emergencia_1_parentesco',
                'contacto_emergencia_1_telefono',
                'contacto_emergencia_2_nombre',
                'contacto_emergencia_2_parentesco',
                'contacto_emergencia_2_telefono',
                'banco',
                'beneficiario',
                'clave_interbancaria',
            ]);
        });
    }
};
