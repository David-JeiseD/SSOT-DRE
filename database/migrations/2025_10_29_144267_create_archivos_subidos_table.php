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
        Schema::create('archivos_subidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subido_por_user_id')->constrained('users');
            $table->string('nombre_original');
            $table->string('ruta_archivo');
            $table->unsignedInteger('filas_procesadas')->default(0);
            $table->unsignedInteger('filas_omitidas')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archivos_subidos');
    }
};
