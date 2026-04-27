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
    Schema::create('movimentacoes', function (Blueprint $table) {
        $table->id();
        $table->foreignId('ferramenta_id')->constrained('ferramentas');
        $table->foreignId('usuario_id')->constrained('users'); // Quem pegou
        $table->foreignId('almoxarife_id')->nullable()->constrained('users'); // Quem entregou
        
        // Campos do Excel que agregam valor:
        $table->integer('qtd')->default(1);
        $table->string('checklist')->default('REALIZADO');
        $table->text('observacao')->nullable(); // Se a ferramenta voltou quebrada, etc.
        
        // O nosso controle inteligente de tempo e posse:
        $table->timestamp('data_retirada')->useCurrent();
        $table->timestamp('data_devolucao')->nullable();
        $table->enum('status', ['aberto', 'concluido'])->default('aberto');
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimentacoes');
    }
};
