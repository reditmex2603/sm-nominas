<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anticipos', function (Blueprint $table) {
            $table->string('tipo')->default('SUELTO')->after('concepto');
            $table->foreignId('evento_id')->nullable()->after('tipo')->constrained('eventos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('anticipos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('evento_id');
            $table->dropColumn('tipo');
        });
    }
};
