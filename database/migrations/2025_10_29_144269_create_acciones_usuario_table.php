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
        Schema::create('acciones_usuario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('tipo_accion');
            
            // ...
            $table->unsignedBigInteger('referencia_id');
            $table->string('referencia_tipo');

            // Añadimos un índice manualmente para que las búsquedas sean rápidas,
            // algo que morphs() hacía automáticamente.
            $table->index(['referencia_id', 'referencia_tipo']);
            // ...
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acciones_usuario');
    }
};
