<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registros_normalizados', function (Blueprint $table) {
            $table->time('hora_salida')->nullable()->after('hora');
        });
    }

    public function down(): void
    {
        Schema::table('registros_normalizados', function (Blueprint $table) {
            $table->dropColumn('hora_salida');
        });
    }
};
