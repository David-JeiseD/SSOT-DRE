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
        Schema::create('columnas_maestras', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_normalizado')->unique();
            $table->string('nombre_display');
            $table->text('descripcion')->nullable();
            $table->boolean('es_fijo')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('columnas_maestras');
    }
};
