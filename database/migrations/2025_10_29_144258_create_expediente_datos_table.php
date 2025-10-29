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
        Schema::create('expediente_datos', function (Blueprint $table) {
            $table->foreignId('expediente_id')->constrained('expedientes')->onDelete('cascade');
            $table->foreignId('dato_unificado_id')->constrained('datos_unificados')->onDelete('cascade');

            // Clave primaria compuesta para evitar duplicados y mejorar rendimiento
            $table->primary(['expediente_id', 'dato_unificado_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expediente_datos');
    }
};
