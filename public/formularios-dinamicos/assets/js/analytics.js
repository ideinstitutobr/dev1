/**
 * Analytics JavaScript
 * Gerencia gráficos e visualizações de dados
 */

// Variáveis globais para os gráficos
let statusChart, timelineChart, scoreDistributionChart, activityChart;
let currentPeriod = 7;

// Cores do tema
const colors = {
    primary: '#667eea',
    success: '#48bb78',
    warning: '#ed8936',
    danger: '#f56565',
    info: '#4299e1',
    secondary: '#a0aec0'
};

// Inicialização
$(document).ready(function() {
    initializeCharts();
    loadAnalyticsData();
    setupEventListeners();
});

/**
 * Inicializa os gráficos vazios
 */
function initializeCharts() {
    // Gráfico de Status (Pizza)
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    statusChart = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Concluídas', 'Em Andamento', 'Incompletas'],
            datasets: [{
                data: [statsData.concluidas, statsData.em_andamento, statsData.incompletas],
                backgroundColor: [colors.success, colors.warning, colors.secondary],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });

    // Gráfico de Timeline (Linha)
    const timelineCtx = document.getElementById('timelineChart').getContext('2d');
    timelineChart = new Chart(timelineCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [
                {
                    label: 'Total',
                    data: [],
                    borderColor: colors.primary,
                    backgroundColor: colors.primary + '20',
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Concluídas',
                    data: [],
                    borderColor: colors.success,
                    backgroundColor: colors.success + '20',
                    fill: true,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });

    // Gráfico de Distribuição de Pontuação (Barras)
    const scoreCtx = document.getElementById('scoreDistributionChart').getContext('2d');
    scoreDistributionChart = new Chart(scoreCtx, {
        type: 'bar',
        data: {
            labels: ['0-20%', '21-40%', '41-60%', '61-80%', '81-100%'],
            datasets: [{
                label: 'Quantidade de Respostas',
                data: [],
                backgroundColor: [
                    colors.danger,
                    colors.warning + 'cc',
                    colors.warning,
                    colors.info,
                    colors.success
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });

    // Gráfico de Atividade por Horário (Barras)
    const activityCtx = document.getElementById('activityChart').getContext('2d');
    activityChart = new Chart(activityCtx, {
        type: 'bar',
        data: {
            labels: Array.from({length: 24}, (_, i) => `${i}h`),
            datasets: [{
                label: 'Respostas',
                data: [],
                backgroundColor: colors.info,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

/**
 * Carrega dados de analytics da API
 */
function loadAnalyticsData() {
    $.ajax({
        url: BASE_URL + 'formularios-dinamicos/api/analytics_data.php',
        method: 'GET',
        data: {
            formulario_id: FORMULARIO_ID,
            tipo: 'all',
            periodo: currentPeriod
        },
        success: function(response) {
            if (response.success) {
                updateCharts(response.data);
                renderQuestionsAnalysis(response.data.questions);
            } else {
                showError('Erro ao carregar dados: ' + response.message);
            }
        },
        error: function() {
            showError('Erro ao carregar dados de analytics');
        }
    });
}

/**
 * Atualiza os gráficos com novos dados
 */
function updateCharts(data) {
    // Atualizar timeline
    if (data.timeline) {
        const labels = [];
        const totalData = [];
        const concluidasData = [];

        Object.keys(data.timeline).forEach(date => {
            labels.push(formatDate(date));
            totalData.push(data.timeline[date].total);
            concluidasData.push(data.timeline[date].concluidas);
        });

        timelineChart.data.labels = labels;
        timelineChart.data.datasets[0].data = totalData;
        timelineChart.data.datasets[1].data = concluidasData;
        timelineChart.update();
    }

    // Atualizar distribuição de pontuação
    if (data.score_distribution) {
        const scoreData = [
            data.score_distribution['0-20'],
            data.score_distribution['21-40'],
            data.score_distribution['41-60'],
            data.score_distribution['61-80'],
            data.score_distribution['81-100']
        ];

        scoreDistributionChart.data.datasets[0].data = scoreData;
        scoreDistributionChart.update();
    }

    // Atualizar atividade por horário
    if (data.activity) {
        activityChart.data.datasets[0].data = data.activity;
        activityChart.update();
    }
}

/**
 * Renderiza análise de perguntas
 */
function renderQuestionsAnalysis(questions) {
    const $container = $('#questionsAnalysis');
    $container.empty();

    if (!questions || questions.length === 0) {
        $container.html('<p class="text-muted text-center">Nenhuma resposta disponível para análise</p>');
        return;
    }

    const $table = $('<div class="table-responsive"></div>');
    const $tableElement = $('<table class="table table-hover"></table>');

    // Cabeçalho
    $tableElement.append(`
        <thead>
            <tr>
                <th style="width: 40%">Pergunta</th>
                <th class="text-center" style="width: 15%">Total Respostas</th>
                <th class="text-center" style="width: 15%">Taxa de Acerto</th>
                <th class="text-center" style="width: 15%">Pontuação Média</th>
                <th style="width: 15%">Distribuição</th>
            </tr>
        </thead>
    `);

    const $tbody = $('<tbody></tbody>');

    questions.forEach(q => {
        const taxaAcerto = q.taxa_acerto.toFixed(1);
        const corTaxa = taxaAcerto >= 70 ? 'success' : (taxaAcerto >= 50 ? 'warning' : 'danger');

        let distribuicaoHTML = '';
        if (Object.keys(q.distribuicao_opcoes).length > 0) {
            distribuicaoHTML = '<div class="small">';
            const total = Object.values(q.distribuicao_opcoes).reduce((a, b) => a + b, 0);

            Object.entries(q.distribuicao_opcoes)
                .sort((a, b) => b[1] - a[1])
                .slice(0, 3)
                .forEach(([opcao, count]) => {
                    const percent = ((count / total) * 100).toFixed(0);
                    distribuicaoHTML += `
                        <div class="mb-1">
                            <div class="d-flex justify-content-between small text-muted mb-1">
                                <span class="text-truncate" style="max-width: 150px;" title="${escapeHtml(opcao)}">${escapeHtml(opcao.substring(0, 30))}</span>
                                <span>${count} (${percent}%)</span>
                            </div>
                            <div class="progress" style="height: 5px;">
                                <div class="progress-bar bg-info" style="width: ${percent}%"></div>
                            </div>
                        </div>
                    `;
                });

            distribuicaoHTML += '</div>';
        } else {
            distribuicaoHTML = '<span class="text-muted small">N/A</span>';
        }

        $tbody.append(`
            <tr>
                <td>
                    <div class="fw-bold">${escapeHtml(q.pergunta.substring(0, 80))}${q.pergunta.length > 80 ? '...' : ''}</div>
                    <small class="text-muted">${q.tipo.replace('_', ' ')}</small>
                </td>
                <td class="text-center">
                    <span class="badge bg-secondary">${q.total_respostas}</span>
                </td>
                <td class="text-center">
                    <div class="mb-1">
                        <span class="badge bg-${corTaxa}">${taxaAcerto}%</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-${corTaxa}" style="width: ${taxaAcerto}%"></div>
                    </div>
                </td>
                <td class="text-center">
                    <strong>${q.pontuacao_media.toFixed(1)}</strong>
                    <small class="text-muted d-block">pts</small>
                </td>
                <td>${distribuicaoHTML}</td>
            </tr>
        `);
    });

    $tableElement.append($tbody);
    $table.append($tableElement);
    $container.append($table);
}

/**
 * Configura event listeners
 */
function setupEventListeners() {
    // Botões de período
    $('.btn-group button[data-period]').on('click', function() {
        $('.btn-group button[data-period]').removeClass('active');
        $(this).addClass('active');

        currentPeriod = parseInt($(this).data('period'));
        loadAnalyticsData();
    });
}

/**
 * Formata data para exibição
 */
function formatDate(dateString) {
    const date = new Date(dateString + 'T00:00:00');
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');

    if (currentPeriod <= 7) {
        const weekdays = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
        return `${day}/${month} (${weekdays[date.getDay()]})`;
    } else {
        return `${day}/${month}`;
    }
}

/**
 * Escapa HTML
 */
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

/**
 * Exibe mensagem de erro
 */
function showError(message) {
    alert(message);
}
