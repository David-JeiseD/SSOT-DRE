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
        Schema::create('datos_unificados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('columna_maestra_id')->constrained('columnas_maestras')->onDelete('cascade');
            $table->text('valor');
            $table->date('fecha_registro');
            $table->timestamps();

            // Índices para mejorar el rendimiento de las búsquedas
            $table->index('fecha_registro');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('datos_unificados');
    }
};
