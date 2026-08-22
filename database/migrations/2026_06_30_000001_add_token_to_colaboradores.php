<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('colaboradores', function (Blueprint $table) {
            $table->string('token', 36)->nullable()->unique()->after('id');
        });

        // Generar token para colaboradores existentes
        DB::table('colaboradores')->whereNull('token')->orderBy('id')->each(
            fn ($row) => DB::table('colaboradores')
                ->where('id', $row->id)
                ->update(['token' => (string) Str::uuid()])
        );
    }

    public function down(): void
    {
        Schema::table('colaboradores', function (Blueprint $table) {
            $table->dropColumn('token');
        });
    }
};
