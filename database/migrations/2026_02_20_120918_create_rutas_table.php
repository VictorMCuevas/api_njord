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
        Schema::create('rutas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_user')
                    ->constrained('users')
                    ->onDelete('cascade');
            $table->string('path',200)->unique();
            $table->text('descripcion');
            $table->string('provincia_inicio', 50);
            $table->string('provincia_fin', 50);
            $table->integer('temperatura');
            $table->foreignId('id_condicion_atmosferica')
                    ->constrained('condiciones_atmosfericas');
            $table->integer('puntuacion');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rutas');
    }
};
