<?php
/**
 * Analytics e Relatórios
 * Dashboard com gráficos e estatísticas avançadas
 */

define('SGC_SYSTEM', true);

$APP_PATH = '../../app/';
require_once $APP_PATH . 'config/config.php';
require_once $APP_PATH . 'classes/Database.php';
require_once $APP_PATH . 'classes/Auth.php';
require_once $APP_PATH . 'models/FormularioDinamico.php';
require_once $APP_PATH . 'models/FormResposta.php';
require_once $APP_PATH . 'models/FormPergunta.php';

// Verificar autenticação
if (!Auth::isLogged()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

// Validar ID
if (empty($_GET['id'])) {
    die('ID do formulário não informado');
}

$formularioId = (int)$_GET['id'];

// Buscar formulário
$formularioModel = new FormularioDinamico();
$formulario = $formularioModel->buscarPorId($formularioId);

if (!$formulario) {
    die('Formulário não encontrado');
}

// Verificar permissão
$userId = Auth::getUserId();
$isAdmin = Auth::isAdmin();

if ($formulario['usuario_id'] != $userId && !$isAdmin) {
    die('Sem permissão para visualizar analytics deste formulário');
}

// Buscar estatísticas básicas
$respostaModel = new FormResposta();
$todasRespostas = $respostaModel->listarPorFormulario($formularioId);

// Calcular estatísticas
$stats = [
    'total' => count($todasRespostas),
    'concluidas' => 0,
    'em_andamento' => 0,
    'incompletas' => 0,
    'pontuacao_media' => 0,
    'percentual_medio' => 0,
    'tempo_medio' => 0,
    'taxa_conclusao' => 0
];

$somaPontuacao = 0;
$somaPercentual = 0;
$somaTempo = 0;
$contagemTempo = 0;

foreach ($todasRespostas as $resposta) {
    // Contagem por status
    if ($resposta['status_resposta'] === 'concluida') {
        $stats['concluidas']++;
    } elseif ($resposta['status_resposta'] === 'em_andamento') {
        $stats['em_andamento']++;
    } else {
        $stats['incompletas']++;
    }

    // Pontuação e percentual
    if ($resposta['pontuacao_total'] !== null) {
        $somaPontuacao += $resposta['pontuacao_total'];
    }
    if ($resposta['percentual_acerto'] !== null) {
        $somaPercentual += $resposta['percentual_acerto'];
    }

    // Tempo
    if ($resposta['tempo_resposta'] !== null && $resposta['tempo_resposta'] > 0) {
        $somaTempo += $resposta['tempo_resposta'];
        $contagemTempo++;
    }
}

if ($stats['total'] > 0) {
    $stats['pontuacao_media'] = $somaPontuacao / $stats['total'];
    $stats['percentual_medio'] = $somaPercentual / $stats['total'];
    $stats['taxa_conclusao'] = ($stats['concluidas'] / $stats['total']) * 100;
}

if ($contagemTempo > 0) {
    $stats['tempo_medio'] = $somaTempo / $contagemTempo;
}

// Incluir header
include $APP_PATH . 'views/layouts/header.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>formularios-dinamicos/assets/css/analytics.css">

<div class="container-fluid mt-4">
    <!-- Cabeçalho -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>
                <i class="fas fa-chart-line"></i>
                Analytics e Relatórios
            </h2>
            <p class="text-muted">
                <?= htmlspecialchars($formulario['titulo']) ?>
            </p>
        </div>
        <div class="col-md-4 text-end">
            <a href="respostas.php?id=<?= $formulario['id'] ?>" class="btn btn-outline-secondary">
                <i class="fas fa-list"></i> Ver Respostas
            </a>
            <a href="builder.php?id=<?= $formulario['id'] ?>" class="btn btn-outline-primary">
                <i class="fas fa-edit"></i> Editar Formulário
            </a>
        </div>
    </div>

    <!-- Cards de Estatísticas -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stats-card stats-card-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total de Respostas</h6>
                            <h2 class="mb-0"><?= $stats['total'] ?></h2>
                        </div>
                        <div class="stats-icon">
                            <i class="fas fa-inbox"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stats-card stats-card-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Taxa de Conclusão</h6>
                            <h2 class="mb-0"><?= number_format($stats['taxa_conclusao'], 1) ?>%</h2>
                            <small class="text-muted"><?= $stats['concluidas'] ?> de <?= $stats['total'] ?></small>
                        </div>
                        <div class="stats-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stats-card stats-card-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Pontuação Média</h6>
                            <h2 class="mb-0"><?= number_format($stats['percentual_medio'], 1) ?>%</h2>
                            <small class="text-muted"><?= number_format($stats['pontuacao_media'], 1) ?> pts</small>
                        </div>
                        <div class="stats-icon">
                            <i class="fas fa-trophy"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stats-card stats-card-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Tempo Médio</h6>
                            <h2 class="mb-0"><?= gmdate('i:s', $stats['tempo_medio']) ?></h2>
                            <small class="text-muted">minutos</small>
                        </div>
                        <div class="stats-icon">
                            <i class="fas fa-stopwatch"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="row mb-4">
        <!-- Distribuição de Status -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie"></i>
                        Distribuição de Status
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="statusChart" height="250"></canvas>
                </div>
            </div>
        </div>

        <!-- Respostas ao Longo do Tempo -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line"></i>
                        Respostas ao Longo do Tempo
                    </h5>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-primary active" data-period="7">7 dias</button>
                        <button type="button" class="btn btn-outline-primary" data-period="30">30 dias</button>
                        <button type="button" class="btn btn-outline-primary" data-period="90">90 dias</button>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="timelineChart" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <!-- Distribuição de Pontuação -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar"></i>
                        Distribuição de Pontuação
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="scoreDistributionChart" height="150"></canvas>
                </div>
            </div>
        </div>

        <!-- Horário de Atividade -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-clock"></i>
                        Horário de Atividade
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="activityChart" height="150"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Análise por Pergunta -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-question-circle"></i>
                        Análise por Pergunta
                    </h5>
                </div>
                <div class="card-body">
                    <div id="questionsAnalysis">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Carregando...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
const BASE_URL = '<?= BASE_URL ?>';
const FORMULARIO_ID = <?= $formularioId ?>;

// Dados de estatísticas do PHP
const statsData = <?= json_encode($stats) ?>;
</script>

<script src="<?= BASE_URL ?>formularios-dinamicos/assets/js/analytics.js"></script>

<?php
// Incluir footer
include $APP_PATH . 'views/layouts/footer.php';
?>
