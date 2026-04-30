<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trocas_p2p', function (Blueprint $table) {
            $table->id();
            $table->foreignId('de_user_id')->constrained('users');
            $table->foreignId('para_user_id')->constrained('users');
            $table->json('ferramentas');
            $table->enum('status', ['pendente', 'aceita', 'recusada'])->default('pendente');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trocas_p2p');
    }
};