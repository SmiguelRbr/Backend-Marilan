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
        Schema::create('notificacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Quem recebe a notificação
            $table->string('titulo');
            $table->text('mensagem');
            $table->string('tipo')->default('info'); // Pode ser: 'info', 'alerta', 'sucesso'
            $table->boolean('lida')->default(false); // Para o app saber se põe a bolinha vermelha ou não
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notificacaos');
    }
};
