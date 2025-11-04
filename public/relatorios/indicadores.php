<?php
/**
 * View: Indicadores de RH
 * Dashboard com 6 KPIs principais de capacitação
 */

define('SGC_SYSTEM', true);

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';
require_once __DIR__ . '/../../app/models/IndicadoresRH.php';

Auth::requireLogin(BASE_URL);

$pageTitle = 'Indicadores de RH';
$breadcrumb = '<a href="../dashboard.php">Dashboard</a> > <a href="index.php">Relatórios</a> > Indicadores de RH';

// Filtro de ano
$anoSelecionado = $_GET['ano'] ?? date('Y');

// Buscar indicadores
$indicadoresModel = new IndicadoresRH();
$dashboard = $indicadoresModel->getDashboardCompleto($anoSelecionado);
$comparacao = $indicadoresModel->getComparacaoAnual();

include __DIR__ . '/../../app/views/layouts/header.php';
?>

<style>
    .indicadores-container {
        max-width: 1400px;
        margin: 0 auto;
    }

    .page-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        border-radius: 10px;
        margin-bottom: 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .page-header h1 {
        margin: 0;
        font-size: 28px;
    }

    .year-selector select {
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
    }

    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }

    .kpi-card {
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        border-left: 5px solid;
        transition: transform 0.3s, box-shadow 0.3s;
    }

    .kpi-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }

    .kpi-card.blue { border-color: #3498db; }
    .kpi-card.green { border-color: #2ecc71; }
    .kpi-card.orange { border-color: #e67e22; }
    .kpi-card.purple { border-color: #9b59b6; }
    .kpi-card.red { border-color: #e74c3c; }
    .kpi-card.teal { border-color: #1abc9c; }

    .kpi-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 15px;
    }

    .kpi-icon {
        font-size: 32px;
    }

    .kpi-title {
        font-size: 14px;
        color: #666;
        font-weight: 600;
        text-transform: uppercase;
    }

    .kpi-value {
        font-size: 42px;
        font-weight: 700;
        color: #333;
        margin: 10px 0;
    }

    .kpi-subtitle {
        font-size: 13px;
        color: #888;
    }

    .kpi-details {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #eee;
    }

    .kpi-detail-item {
        display: flex;
        justify-content: space-between;
        margin: 8px 0;
        font-size: 13px;
    }

    .kpi-detail-item .label {
        color: #666;
    }

    .kpi-detail-item .value {
        font-weight: 600;
        color: #333;
    }

    .nivel-table-card {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 30px;
    }

    .nivel-table-card h2 {
        margin: 0 0 20px 0;
        color: #333;
        font-size: 20px;
    }

    .nivel-table {
        width: 100%;
        border-collapse: collapse;
    }

    .nivel-table thead {
        background: #f8f9fa;
    }

    .nivel-table th {
        padding: 15px;
        text-align: left;
        font-weight: 600;
        color: #555;
        border-bottom: 2px solid #dee2e6;
    }

    .nivel-table td {
        padding: 15px;
        border-bottom: 1px solid #f0f0f0;
    }

    .nivel-badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: 600;
    }

    .nivel-badge.estrategico {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .nivel-badge.tatico {
        background: #e3f2fd;
        color: #1565c0;
    }

    .nivel-badge.operacional {
        background: #fff3e0;
        color: #e65100;
    }

    .comparacao-card {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 30px;
    }

    .comparacao-card h2 {
        margin: 0 0 20px 0;
        color: #333;
        font-size: 20px;
    }

    .comparacao-table {
        width: 100%;
        border-collapse: collapse;
    }

    .comparacao-table thead {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .comparacao-table th {
        padding: 15px;
        text-align: center;
        font-weight: 600;
    }

    .comparacao-table td {
        padding: 15px;
        text-align: center;
        border-bottom: 1px solid #f0f0f0;
    }

    .comparacao-table tbody tr:hover {
        background: #f8f9fa;
    }

    .trend-up {
        color: #2ecc71;
        font-weight: 600;
    }

    .trend-down {
        color: #e74c3c;
        font-weight: 600;
    }

    @media (max-width: 768px) {
        .kpi-grid {
            grid-template-columns: 1fr;
        }

        .page-header {
            flex-direction: column;
            gap: 15px;
        }

        .kpi-value {
            font-size: 32px;
        }
    }

    .chart-container {
        position: relative;
        height: 300px;
        margin-top: 20px;
    }
</style>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<div class="indicadores-container">
    <div class="page-header">
        <div>
            <h1>📊 Indicadores de RH</h1>
            <p style="margin: 5px 0 0 0; opacity: 0.9;">Gestão de Capacitação e Desenvolvimento</p>
        </div>
        <div class="year-selector">
            <select onchange="window.location.href='?ano=' + this.value">
                <?php for ($ano = date('Y'); $ano >= date('Y') - 5; $ano--): ?>
                    <option value="<?php echo $ano; ?>" <?php echo $ano == $anoSelecionado ? 'selected' : ''; ?>>
                        <?php echo $ano; ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>
    </div>

    <!-- Grid de KPIs -->
    <div class="kpi-grid">
        <!-- 1. HTC - Horas de Treinamento por Colaborador -->
        <div class="kpi-card blue">
            <div class="kpi-header">
                <span class="kpi-icon">⏱️</span>
                <span class="kpi-title">HTC - Horas por Colaborador</span>
            </div>
            <div class="kpi-value"><?php echo number_format($dashboard['htc']['htc'], 1, ',', '.'); ?>h</div>
            <div class="kpi-subtitle">Média de horas de treinamento</div>
            <div class="kpi-details">
                <div class="kpi-detail-item">
                    <span class="label">Total de Horas:</span>
                    <span class="value"><?php echo number_format($dashboard['htc']['total_horas'], 0, ',', '.'); ?>h</span>
                </div>
                <div class="kpi-detail-item">
                    <span class="label">Colaboradores Ativos:</span>
                    <span class="value"><?php echo $dashboard['htc']['total_colaboradores']; ?></span>
                </div>
            </div>
        </div>

        <!-- 2. CTC - Custo por Colaborador -->
        <div class="kpi-card green">
            <div class="kpi-header">
                <span class="kpi-icon">💰</span>
                <span class="kpi-title">CTC - Custo por Colaborador</span>
            </div>
            <div class="kpi-value">R$ <?php echo number_format($dashboard['ctc']['ctc'], 2, ',', '.'); ?></div>
            <div class="kpi-subtitle">Investimento médio por pessoa</div>
            <div class="kpi-details">
                <div class="kpi-detail-item">
                    <span class="label">Total Investido:</span>
                    <span class="value">R$ <?php echo number_format($dashboard['ctc']['total_investido'], 2, ',', '.'); ?></span>
                </div>
                <div class="kpi-detail-item">
                    <span class="label">Colaboradores Ativos:</span>
                    <span class="value"><?php echo $dashboard['ctc']['total_colaboradores']; ?></span>
                </div>
            </div>
        </div>

        <!-- 3. % sobre Folha -->
        <div class="kpi-card orange">
            <div class="kpi-header">
                <span class="kpi-icon">📈</span>
                <span class="kpi-title">% Investimento / Folha</span>
            </div>
            <div class="kpi-value"><?php echo number_format($dashboard['percentual_folha']['percentual'], 2, ',', '.'); ?>%</div>
            <div class="kpi-subtitle">Do total da folha de pagamento</div>
            <div class="kpi-details">
                <div class="kpi-detail-item">
                    <span class="label">Total Investido:</span>
                    <span class="value">R$ <?php echo number_format($dashboard['percentual_folha']['total_investido'], 2, ',', '.'); ?></span>
                </div>
                <div class="kpi-detail-item">
                    <span class="label">Folha Anual:</span>
                    <span class="value">R$ <?php echo number_format($dashboard['percentual_folha']['folha_anual'], 2, ',', '.'); ?></span>
                </div>
            </div>
        </div>

        <!-- 4. Taxa de Conclusão -->
        <div class="kpi-card purple">
            <div class="kpi-header">
                <span class="kpi-icon">✅</span>
                <span class="kpi-title">Taxa de Conclusão</span>
            </div>
            <div class="kpi-value"><?php echo number_format($dashboard['taxa_conclusao']['taxa_conclusao'], 1, ',', '.'); ?>%</div>
            <div class="kpi-subtitle">Treinamentos executados</div>
            <div class="kpi-details">
                <div class="kpi-detail-item">
                    <span class="label">Executados:</span>
                    <span class="value"><?php echo $dashboard['taxa_conclusao']['executados']; ?></span>
                </div>
                <div class="kpi-detail-item">
                    <span class="label">Total Programados:</span>
                    <span class="value"><?php echo $dashboard['taxa_conclusao']['total']; ?></span>
                </div>
                <div class="kpi-detail-item">
                    <span class="label">Pendentes:</span>
                    <span class="value"><?php echo $dashboard['taxa_conclusao']['pendentes']; ?></span>
                </div>
            </div>
        </div>

        <!-- 5. % Colaboradores Capacitados -->
        <div class="kpi-card red">
            <div class="kpi-header">
                <span class="kpi-icon">👥</span>
                <span class="kpi-title">% Colaboradores Capacitados</span>
            </div>
            <div class="kpi-value"><?php echo number_format($dashboard['percentual_capacitados']['percentual'], 1, ',', '.'); ?>%</div>
            <div class="kpi-subtitle">Participaram de treinamentos</div>
            <div class="kpi-details">
                <div class="kpi-detail-item">
                    <span class="label">Capacitados:</span>
                    <span class="value"><?php echo $dashboard['percentual_capacitados']['capacitados']; ?></span>
                </div>
                <div class="kpi-detail-item">
                    <span class="label">Não Capacitados:</span>
                    <span class="value"><?php echo $dashboard['percentual_capacitados']['nao_capacitados']; ?></span>
                </div>
                <div class="kpi-detail-item">
                    <span class="label">Total Colaboradores:</span>
                    <span class="value"><?php echo $dashboard['percentual_capacitados']['total_colaboradores']; ?></span>
                </div>
            </div>
        </div>

        <!-- 6. Índice Geral -->
        <div class="kpi-card teal">
            <div class="kpi-header">
                <span class="kpi-icon">🎯</span>
                <span class="kpi-title">Índice Geral de Capacitação</span>
            </div>
            <?php
            // Calcular índice geral (média ponderada)
            $indiceGeral = (
                $dashboard['taxa_conclusao']['taxa_conclusao'] * 0.3 +
                $dashboard['percentual_capacitados']['percentual'] * 0.4 +
                min(($dashboard['htc']['htc'] / 40) * 100, 100) * 0.3 // Meta: 40h/ano
            );
            ?>
            <div class="kpi-value"><?php echo number_format($indiceGeral, 1, ',', '.'); ?>%</div>
            <div class="kpi-subtitle">Performance geral do programa</div>
            <div class="kpi-details">
                <div class="kpi-detail-item">
                    <span class="label">Taxa Conclusão (30%):</span>
                    <span class="value"><?php echo number_format($dashboard['taxa_conclusao']['taxa_conclusao'], 1, ',', '.'); ?>%</span>
                </div>
                <div class="kpi-detail-item">
                    <span class="label">% Capacitados (40%):</span>
                    <span class="value"><?php echo number_format($dashboard['percentual_capacitados']['percentual'], 1, ',', '.'); ?>%</span>
                </div>
                <div class="kpi-detail-item">
                    <span class="label">HTC vs Meta (30%):</span>
                    <span class="value"><?php echo number_format(min(($dashboard['htc']['htc'] / 40) * 100, 100), 1, ',', '.'); ?>%</span>
                </div>
            </div>
        </div>
    </div>

    <!-- HTC por Nível Hierárquico -->
    <div class="nivel-table-card">
        <h2>📊 HTC por Nível Hierárquico</h2>

        <!-- Gráfico de HTC por Nível -->
        <div class="chart-container">
            <canvas id="chartNivel"></canvas>
        </div>

        <table class="nivel-table">
            <thead>
                <tr>
                    <th>Nível</th>
                    <th>Colaboradores</th>
                    <th>Total de Horas</th>
                    <th>HTC (Horas/Colaborador)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dashboard['htc_por_nivel'] as $nivel): ?>
                <tr>
                    <td>
                        <span class="nivel-badge <?php echo strtolower($nivel['nivel']); ?>">
                            <?php echo htmlspecialchars($nivel['nivel']); ?>
                        </span>
                    </td>
                    <td><?php echo $nivel['total_colaboradores']; ?></td>
                    <td><?php echo number_format($nivel['total_horas'], 0, ',', '.'); ?>h</td>
                    <td><strong><?php echo number_format($nivel['htc'], 1, ',', '.'); ?>h</strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Comparação Anual -->
    <div class="comparacao-card">
        <h2>📅 Comparação dos Últimos 3 Anos</h2>

        <!-- Gráfico de Comparação Anual -->
        <div class="chart-container" style="height: 350px;">
            <canvas id="chartComparacao"></canvas>
        </div>

        <table class="comparacao-table">
            <thead>
                <tr>
                    <th>Indicador</th>
                    <?php foreach ($comparacao as $ano => $dados): ?>
                        <th><?php echo $ano; ?></th>
                    <?php endforeach; ?>
                    <th>Tendência</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>HTC (horas)</strong></td>
                    <?php
                    $valores = array_column($comparacao, 'htc');
                    foreach ($valores as $valor): ?>
                        <td><?php echo number_format($valor, 1, ',', '.'); ?>h</td>
                    <?php endforeach; ?>
                    <td>
                        <?php
                        $tendencia = end($valores) - reset($valores);
                        echo $tendencia > 0 ? '<span class="trend-up">↑ ' . number_format($tendencia, 1, ',', '.') . 'h</span>' :
                             ($tendencia < 0 ? '<span class="trend-down">↓ ' . number_format(abs($tendencia), 1, ',', '.') . 'h</span>' : '→ Estável');
                        ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>CTC (R$)</strong></td>
                    <?php
                    $valores = array_column($comparacao, 'ctc');
                    foreach ($valores as $valor): ?>
                        <td>R$ <?php echo number_format($valor, 2, ',', '.'); ?></td>
                    <?php endforeach; ?>
                    <td>
                        <?php
                        $tendencia = end($valores) - reset($valores);
                        echo $tendencia > 0 ? '<span class="trend-up">↑ R$ ' . number_format($tendencia, 2, ',', '.') . '</span>' :
                             ($tendencia < 0 ? '<span class="trend-down">↓ R$ ' . number_format(abs($tendencia), 2, ',', '.') . '</span>' : '→ Estável');
                        ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>Taxa de Conclusão (%)</strong></td>
                    <?php
                    $valores = array_column($comparacao, 'taxa_conclusao');
                    foreach ($valores as $valor): ?>
                        <td><?php echo number_format($valor, 1, ',', '.'); ?>%</td>
                    <?php endforeach; ?>
                    <td>
                        <?php
                        $tendencia = end($valores) - reset($valores);
                        echo $tendencia > 0 ? '<span class="trend-up">↑ ' . number_format($tendencia, 1, ',', '.') . '%</span>' :
                             ($tendencia < 0 ? '<span class="trend-down">↓ ' . number_format(abs($tendencia), 1, ',', '.') . '%</span>' : '→ Estável');
                        ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>% Capacitados</strong></td>
                    <?php
                    $valores = array_column($comparacao, 'percentual_capacitados');
                    foreach ($valores as $valor): ?>
                        <td><?php echo number_format($valor, 1, ',', '.'); ?>%</td>
                    <?php endforeach; ?>
                    <td>
                        <?php
                        $tendencia = end($valores) - reset($valores);
                        echo $tendencia > 0 ? '<span class="trend-up">↑ ' . number_format($tendencia, 1, ',', '.') . '%</span>' :
                             ($tendencia < 0 ? '<span class="trend-down">↓ ' . number_format(abs($tendencia), 1, ',', '.') . '%</span>' : '→ Estável');
                        ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div style="text-align: center; margin-top: 30px;">
        <a href="index.php" class="btn" style="background: #6c757d; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px;">
            ← Voltar aos Relatórios
        </a>
    </div>
</div>

<script>
// Configuração global
Chart.defaults.font.family = 'Segoe UI, Tahoma, Geneva, Verdana, sans-serif';
Chart.defaults.color = '#666';

// 1. Gráfico HTC por Nível Hierárquico (Barras)
const ctxNivel = document.getElementById('chartNivel').getContext('2d');
new Chart(ctxNivel, {
    type: 'bar',
    data: {
        labels: [
            <?php foreach ($dashboard['htc_por_nivel'] as $nivel): ?>
                '<?php echo $nivel['nivel']; ?>',
            <?php endforeach; ?>
        ],
        datasets: [{
            label: 'HTC (Horas/Colaborador)',
            data: [
                <?php foreach ($dashboard['htc_por_nivel'] as $nivel): ?>
                    <?php echo $nivel['htc']; ?>,
                <?php endforeach; ?>
            ],
            backgroundColor: [
                'rgba(46, 125, 50, 0.8)',   // Estratégico - Verde
                'rgba(21, 101, 192, 0.8)',  // Tático - Azul
                'rgba(230, 81, 0, 0.8)'     // Operacional - Laranja
            ],
            borderColor: [
                '#2e7d32',
                '#1565c0',
                '#e65100'
            ],
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: 'rgba(0,0,0,0.8)',
                padding: 12,
                callbacks: {
                    label: function(context) {
                        return `HTC: ${context.parsed.y.toFixed(1)} horas`;
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Horas de Treinamento'
                },
                grid: {
                    color: 'rgba(0,0,0,0.05)'
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});

// 2. Gráfico de Comparação Anual (Linhas Múltiplas)
const ctxComparacao = document.getElementById('chartComparacao').getContext('2d');

<?php
$anos = array_keys($comparacao);
$htcData = [];
$ctcData = [];
$taxaData = [];
$capacitadosData = [];

foreach ($comparacao as $ano => $dados) {
    $htcData[] = $dados['htc'];
    $ctcData[] = $dados['ctc'];
    $taxaData[] = $dados['taxa_conclusao'];
    $capacitadosData[] = $dados['percentual_capacitados'];
}
?>

new Chart(ctxComparacao, {
    type: 'line',
    data: {
        labels: [<?php echo "'" . implode("','", $anos) . "'"; ?>],
        datasets: [
            {
                label: 'HTC (horas)',
                data: [<?php echo implode(',', $htcData); ?>],
                borderColor: '#3498db',
                backgroundColor: 'rgba(52, 152, 219, 0.1)',
                borderWidth: 3,
                fill: false,
                tension: 0.4,
                pointBackgroundColor: '#3498db',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 6,
                pointHoverRadius: 8,
                yAxisID: 'y'
            },
            {
                label: 'Taxa Conclusão (%)',
                data: [<?php echo implode(',', $taxaData); ?>],
                borderColor: '#9b59b6',
                backgroundColor: 'rgba(155, 89, 182, 0.1)',
                borderWidth: 3,
                fill: false,
                tension: 0.4,
                pointBackgroundColor: '#9b59b6',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 6,
                pointHoverRadius: 8,
                yAxisID: 'y1'
            },
            {
                label: '% Capacitados',
                data: [<?php echo implode(',', $capacitadosData); ?>],
                borderColor: '#2ecc71',
                backgroundColor: 'rgba(46, 204, 113, 0.1)',
                borderWidth: 3,
                fill: false,
                tension: 0.4,
                pointBackgroundColor: '#2ecc71',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 6,
                pointHoverRadius: 8,
                yAxisID: 'y1'
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false
        },
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    padding: 15,
                    font: {
                        size: 12,
                        weight: 'bold'
                    },
                    usePointStyle: true
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0,0,0,0.8)',
                padding: 12,
                titleFont: {
                    size: 14
                },
                bodyFont: {
                    size: 13
                }
            }
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'HTC (horas)',
                    color: '#3498db',
                    font: {
                        weight: 'bold'
                    }
                },
                ticks: {
                    color: '#3498db'
                },
                grid: {
                    color: 'rgba(0,0,0,0.05)'
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                beginAtZero: true,
                max: 100,
                title: {
                    display: true,
                    text: 'Percentual (%)',
                    color: '#9b59b6',
                    font: {
                        weight: 'bold'
                    }
                },
                ticks: {
                    color: '#9b59b6'
                },
                grid: {
                    drawOnChartArea: false
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});
</script>

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>
