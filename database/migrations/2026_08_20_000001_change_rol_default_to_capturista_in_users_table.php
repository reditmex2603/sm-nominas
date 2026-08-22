<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Defensa en profundidad para M6: el default 'admin' permitía que cualquier creación de
     * usuario sin `rol` explícito naciera como super administrador (p. ej. registrados vía
     * CreateNewUser cuando el registro público estaba habilitado). El rol más bajo posible es
     * 'capturista' (sin permisos de módulo, que se asignan explícitamente por admin).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('rol', ['admin', 'supervisor', 'capturista'])->default('capturista')->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('rol', ['admin', 'supervisor', 'capturista'])->default('admin')->change();
        });
    }
};