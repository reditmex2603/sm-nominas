<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eventos', function (Blueprint $table) {
            $table->string('nombre_contratante')->nullable()->after('lugar');
            $table->string('telefono_contratante')->nullable()->after('nombre_contratante');
            $table->string('contacto_nombre')->nullable()->after('telefono_contratante');
            $table->string('contacto_telefono')->nullable()->after('contacto_nombre');
            $table->string('enlace_ubicacion')->nullable()->after('contacto_telefono');
            $table->text('descripcion')->nullable()->after('enlace_ubicacion');
            $table->text('observaciones_tecnicas')->nullable()->after('descripcion');
        });
    }

    public function down(): void
    {
        Schema::table('eventos', function (Blueprint $table) {
            $table->dropColumn([
                'nombre_contratante',
                'telefono_contratante',
                'contacto_nombre',
                'contacto_telefono',
                'enlace_ubicacion',
                'descripcion',
                'observaciones_tecnicas',
            ]);
        });
    }
};
