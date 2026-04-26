<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rutas', function (Blueprint $table) {
            $table->decimal('latitud_medio', 10, 8)->nullable()->after('longitud');
            $table->decimal('longitud_medio', 11, 8)->nullable()->after('latitud_medio');
            $table->decimal('latitud_fin', 10, 8)->nullable()->after('longitud_medio');
            $table->decimal('longitud_fin', 11, 8)->nullable()->after('latitud_fin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rutas', function (Blueprint $table) {
            $table->dropColumn(['latitud_medio', 'longitud_medio', 'latitud_fin', 'longitud_fin']);
        });
    }
};
