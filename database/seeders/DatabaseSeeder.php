<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Ferramenta;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Criando os Almoxarifes
        User::create([
            'cracha' => '1234',
            'nome' => 'GEOVANE',
            'oficina' => 'MPR',
            'turno' => 'ADM',
            'password' => Hash::make('123456'),
            'role' => 'almoxarife'
        ]);

        User::create([
            'cracha' => '0001',
            'nome' => 'AGATHA',
            'oficina' => 'MME',
            'turno' => 'ADM',
            'password' => Hash::make('123456'),
            'role' => 'almoxarife'
        ]);

        // 2. Criando os Colaboradores (Manutentores)
        User::create([
            'cracha' => '1236',
            'nome' => 'PEDRO',
            'oficina' => 'MEE',
            'turno' => '1º TURNO',
            'password' => Hash::make('123456'),
            'role' => 'colaborador'
        ]);

        User::create([
            'cracha' => '1237',
            'nome' => 'BRUNO',
            'oficina' => 'MME',
            'turno' => 'ADM',
            'password' => Hash::make('123456'),
            'role' => 'colaborador'
        ]);

        // 3. Criando as Ferramentas do seu Excel
        Ferramenta::create([
            'codigo_patrimonio' => '50036280',
            'descricao' => 'FURADEIRA BOSCH 2',
            'quantidade' => 1,
            'status' => 'disponivel'
        ]);

        Ferramenta::create([
            'codigo_patrimonio' => '50038854',
            'descricao' => 'ARCO DE SERRA',
            'quantidade' => 1,
            'status' => 'disponivel'
        ]);

        Ferramenta::create([
            'codigo_patrimonio' => '50046617',
            'descricao' => 'LIXADEIRA BOSCH 8',
            'quantidade' => 1,
            'status' => 'disponivel'
        ]);
    }
}