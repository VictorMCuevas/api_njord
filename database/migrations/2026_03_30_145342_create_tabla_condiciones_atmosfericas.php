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
        Schema::create('condiciones_atmosfericas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ruta_id')->constrained('rutas')->onDelete('cascade');
            $table->date('fecha'); //Fecha de ruta
            $table->decimal('temperatura', 5, 2)->nullable(); // Temp °C
            $table->integer('humedad')->nullable(); // % humedad
            $table->decimal('velocidad_viento', 5, 2)->nullable(); //km/h
            $table->decimal('precipitacion', 5, 2)->nullable(); // mm
            $table->string('tipo_clima')->nullable(); // soleado, nublado, lluvia, nieve, etc
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
   public function down(): void
    {
        Schema::dropIfExists('condiciones_atmosfericas');
    }
};
