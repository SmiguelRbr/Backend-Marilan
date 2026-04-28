<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Almoxarifado - Marilan ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        marilan: {
                            500: '#F26419',
                            600: '#D95311',
                            700: '#B0410C',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'Helvetica', 'Arial', 'sans-serif'],
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-[#f0f2f5] font-sans text-gray-800 antialiased">

    <nav class="bg-[#1a1c23] px-6 py-3 flex justify-between items-center shadow-md">
        <div class="flex items-center gap-5">
            <div class="bg-white p-1.5 rounded-sm">
                <img src="https://upload.wikimedia.org/wikipedia/commons/f/f2/Grupo_marilan.png" alt="Marilan Logo" class="h-6">
            </div>
            <span class="text-sm font-bold text-gray-200 uppercase tracking-widest border-l border-gray-600 pl-5">
                Gestão de Ferramental
            </span>
        </div>
        <div class="flex items-center gap-3">
            <div class="text-right">
                <p class="text-xs font-bold text-gray-200 uppercase">Almoxarifado Central</p>
                <p class="text-[10px] text-gray-400">ID SESSÃO: {{ session()->getId() ?? 'LOCAL' }}</p>
            </div>
            <div class="h-8 w-8 rounded-sm bg-marilan-500 text-white flex items-center justify-center font-bold text-xs">
                AL
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <div class="mb-6 flex justify-between items-end border-b border-gray-300 pb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Visão Geral do Estoque</h1>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mt-1">Indicadores de Performance e Movimentações Recentes</p>
            </div>

            <div class="relative inline-block text-left" id="dropdownContainer">
                <button onclick="toggleDropdown()" class="bg-marilan-500 hover:bg-marilan-600 text-white px-4 py-2 rounded-sm text-xs font-bold uppercase tracking-wider shadow-sm transition-colors flex items-center gap-2 border border-marilan-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Gerar Relatório
                    <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>

                <div id="exportMenu" class="hidden origin-top-right absolute right-0 mt-1 w-48 bg-white border border-gray-200 shadow-lg rounded-sm z-50">
                    <div class="py-1">
                        <a href="{{ route('relatorios.web.exportar', ['formato' => 'pdf']) }}"
                            class="flex items-center gap-3 px-4 py-2.5 text-xs text-gray-700 hover:bg-gray-100 hover:text-marilan-600 transition-colors font-medium">
                            <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 15h-2v-2h2v2zm0-4h-2V7h2v6z" />
                            </svg>
                            Exportar em PDF
                        </a>

                        <a href="{{ route('relatorios.web.exportar', ['formato' => 'excel']) }}"
                            class="flex items-center gap-3 px-4 py-2.5 text-xs text-gray-700 hover:bg-gray-100 hover:text-marilan-600 transition-colors font-medium border-t border-gray-100">
                            <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z" />
                            </svg>
                            Exportar Planilha (XLSX)
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-sm shadow-sm border border-gray-300 p-5">
                <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Total Cadastrado</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ $kpis['total'] }} <span class="text-xs font-normal text-gray-400">ativos</span></p>
            </div>
            <div class="bg-white rounded-sm shadow-sm border border-gray-300 p-5 border-t-4 border-t-green-500">
                <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Em Estoque</p>
                <p class="text-2xl font-bold text-green-600 mt-1">{{ $kpis['disponiveis'] }} <span class="text-xs font-normal text-gray-400">unidades</span></p>
            </div>
            <div class="bg-white rounded-sm shadow-sm border border-gray-300 p-5 border-t-4 border-t-marilan-500">
                <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Operando (Campo)</p>
                <p class="text-2xl font-bold text-marilan-600 mt-1">{{ $kpis['em_uso'] }} <span class="text-xs font-normal text-gray-400">unidades</span></p>
            </div>
            <div class="bg-white rounded-sm shadow-sm border border-gray-300 p-5 border-t-4 border-t-red-600">
                <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Aguardando MNT</p>
                <p class="text-2xl font-bold text-red-600 mt-1">{{ $kpis['manutencao'] }} <span class="text-xs font-normal text-gray-400">unidades</span></p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-sm shadow-sm border border-gray-300 p-5 col-span-1">
                <h2 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-4 border-b border-gray-200 pb-2">Distribuição de Status</h2>
                <div class="relative h-56">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>

            <div class="bg-white rounded-sm shadow-sm border border-gray-300 p-5 col-span-2">
                <h2 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-4 border-b border-gray-200 pb-2">Histórico de Movimentações (7 Dias)</h2>
                <div class="relative h-56 w-full">
                    <canvas id="fluxoChart"></canvas>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-sm shadow-sm border border-gray-300">
            <div class="px-5 py-3 border-b border-gray-300 bg-gray-50 flex justify-between items-center">
                <h2 class="text-xs font-bold text-gray-700 uppercase tracking-wider">Últimos Registros (Log)</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-700">
                    <thead class="bg-gray-100 text-gray-600 text-[10px] uppercase tracking-wider border-b-2 border-gray-300">
                        <tr>
                            <th class="px-5 py-3 font-bold">Data/Hora</th>
                            <th class="px-5 py-3 font-bold">Colaborador</th>
                            <th class="px-5 py-3 font-bold">Patrimônio / Equipamento</th>
                            <th class="px-5 py-3 font-bold">Operação</th>
                            <th class="px-5 py-3 font-bold">Almoxarife Resp.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($ultimasMovimentacoes as $mov)
                        <tr class="hover:bg-blue-50 transition-colors">
                            <td class="px-5 py-2.5 text-xs text-gray-500 font-mono">{{ $mov->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-2.5 text-xs font-semibold">{{ $mov->usuario->cracha }} - {{ $mov->usuario->nome }}</td>
                            <td class="px-5 py-2.5 text-xs">
                                <span class="font-mono bg-gray-100 px-1.5 py-0.5 rounded border border-gray-200">{{ $mov->ferramenta->codigo_patrimonio }}</span>
                                <span class="ml-2 text-gray-600">{{ $mov->ferramenta->descricao }}</span>
                            </td>
                            <td class="px-5 py-2.5">
                                {{-- LÓGICA DE STATUS REFINADA --}}
                                @if($mov->status === 'aberto' && $mov->almoxarife_id)
                                <span class="border border-orange-200 bg-orange-50 text-orange-700 px-2 py-0.5 text-[10px] font-bold uppercase tracking-widest">Retirada</span>
                                @elseif($mov->status === 'aberto' && !$mov->almoxarife_id)
                                <span class="border border-blue-200 bg-blue-50 text-blue-700 px-2 py-0.5 text-[10px] font-bold uppercase tracking-widest">Transferência</span>
                                @else
                                <span class="border border-green-200 bg-green-50 text-green-700 px-2 py-0.5 text-[10px] font-bold uppercase tracking-widest">Devolução</span>
                                @endif
                            </td>
                            <td class="px-5 py-2.5 text-xs text-gray-600">
                                {{ $mov->almoxarife->nome ?? 'Troca Direta' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <script>
        // Lógica do Dropdown
        function toggleDropdown() {
            const menu = document.getElementById('exportMenu');
            menu.classList.toggle('hidden');
        }

        // Fecha o dropdown se o usuário clicar fora dele
        window.addEventListener('click', function(e) {
            const dropdownContainer = document.getElementById('dropdownContainer');
            if (!dropdownContainer.contains(e.target)) {
                document.getElementById('exportMenu').classList.add('hidden');
            }
        });

        // Configuração dos Gráficos (Chart.js)
        Chart.defaults.font.family = "'Inter', 'Helvetica', 'Arial', sans-serif";
        Chart.defaults.color = '#6b7280';

        const kpis = @json($kpis);
        const graficoLinha = @json($graficoLinha);

        // Gráfico Status
        new Chart(document.getElementById('statusChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Estoque', 'Em Uso', 'MNT'],
                datasets: [{
                    data: [kpis.disponiveis, kpis.em_uso, kpis.manutencao],
                    backgroundColor: ['#10b981', '#F26419', '#dc2626'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            font: {
                                size: 10
                            }
                        }
                    }
                }
            }
        });

        // Gráfico Linhas
        new Chart(document.getElementById('fluxoChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: graficoLinha.labels,
                datasets: [{
                        label: 'Saídas',
                        data: graficoLinha.saidas,
                        backgroundColor: '#F26419',
                        borderRadius: 2
                    },
                    {
                        label: 'Entradas',
                        data: graficoLinha.entradas,
                        backgroundColor: '#10b981',
                        borderRadius: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            borderDash: [2, 4]
                        },
                        ticks: {
                            stepSize: 1,
                            font: {
                                size: 10
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 10
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            boxWidth: 12,
                            font: {
                                size: 10
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>

</html>