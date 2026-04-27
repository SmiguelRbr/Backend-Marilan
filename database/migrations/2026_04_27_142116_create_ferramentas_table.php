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
        Schema::create('ferramentas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_patrimonio')->unique(); // O "Cod. Ferramenta" do seu Excel (ex: 50036280)
            $table->string('descricao'); // "Desc. Ferramenta" (ex: FURADEIRA BOSCH)
            $table->enum('status', ['disponivel', 'em_uso', 'manutencao'])->default('disponivel');
            $table->integer('quantidade')->default(1); // Caso tenha itens como "Alicate" que não têm patrimônio único
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ferramentas');
    }
};
