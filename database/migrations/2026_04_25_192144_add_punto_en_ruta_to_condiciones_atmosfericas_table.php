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
        Schema::table('condiciones_atmosfericas', function (Blueprint $table) {
            $table->string('punto_en_ruta')->nullable()->after('ruta_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('condiciones_atmosfericas', function (Blueprint $table) {
            $table->dropColumn('punto_en_ruta');
        });
    }
};
