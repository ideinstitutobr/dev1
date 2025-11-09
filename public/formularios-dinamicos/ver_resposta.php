<?php
/**
 * Visualização Detalhada de Resposta
 * Exibe todas as respostas de um formulário específico
 */

define('SGC_SYSTEM', true);

$APP_PATH = '../../app/';
require_once $APP_PATH . 'config/config.php';
require_once $APP_PATH . 'classes/Database.php';
require_once $APP_PATH . 'classes/Auth.php';
require_once $APP_PATH . 'models/FormularioDinamico.php';
require_once $APP_PATH . 'models/FormResposta.php';
require_once $APP_PATH . 'models/FormRespostaDetalhe.php';
require_once $APP_PATH . 'models/FormPergunta.php';
require_once $APP_PATH . 'models/FormSecao.php';
require_once $APP_PATH . 'models/FormFaixaPontuacao.php';

// Verificar autenticação
if (!Auth::isLogged()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

// Validar ID
if (empty($_GET['id'])) {
    die('ID da resposta não informado');
}

$respostaId = (int)$_GET['id'];

// Buscar resposta
$respostaModel = new FormResposta();
$resposta = $respostaModel->buscarPorId($respostaId);

if (!$resposta) {
    die('Resposta não encontrada');
}

// Buscar formulário
$formularioModel = new FormularioDinamico();
$formulario = $formularioModel->buscarPorId($resposta['formulario_id']);

if (!$formulario) {
    die('Formulário não encontrado');
}

// Verificar permissão (proprietário ou admin)
$userId = Auth::getUserId();
$isAdmin = Auth::isAdmin();

if ($formulario['usuario_id'] != $userId && !$isAdmin) {
    die('Sem permissão para visualizar esta resposta');
}

// Buscar detalhes das respostas
$detalheModel = new FormRespostaDetalhe();
$detalhes = $detalheModel->listarPorResposta($respostaId);

// Buscar seções do formulário
$secaoModel = new FormSecao();
$secoes = $secaoModel->listarPorFormulario($resposta['formulario_id']);

// Buscar perguntas do formulário
$perguntaModel = new FormPergunta();
$perguntas = $perguntaModel->listarPorFormulario($resposta['formulario_id']);

// Organizar respostas por pergunta
$respostasPorPergunta = [];
foreach ($detalhes as $detalhe) {
    $respostasPorPergunta[$detalhe['pergunta_id']] = $detalhe;
}

// Buscar faixa de pontuação se aplicável
$faixa = null;
if ($formulario['tipo_pontuacao'] !== 'nenhum' && $resposta['status_resposta'] === 'concluida') {
    $faixaModel = new FormFaixaPontuacao();
    if ($formulario['tipo_pontuacao'] === 'percentual') {
        $faixa = $faixaModel->identificarFaixaPorPercentual($formulario['id'], $resposta['percentual_acerto']);
    } else {
        $faixa = $faixaModel->identificarFaixa($formulario['id'], $resposta['pontuacao_total']);
    }
}

// Incluir header
include $APP_PATH . 'views/layouts/header.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>formularios-dinamicos/assets/css/ver_resposta.css">

<div class="container-fluid mt-4">
    <!-- Cabeçalho -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>
                <i class="fas fa-file-alt"></i>
                Resposta #<?= $resposta['id'] ?>
            </h2>
            <p class="text-muted">
                <?= htmlspecialchars($formulario['titulo']) ?>
            </p>
        </div>
        <div class="col-md-4 text-end">
            <a href="respostas.php?id=<?= $formulario['id'] ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Voltar à Lista
            </a>
            <a href="analytics.php?id=<?= $formulario['id'] ?>" class="btn btn-outline-info">
                <i class="fas fa-chart-line"></i> Analytics
            </a>
            <button onclick="imprimirResposta()" class="btn btn-outline-primary">
                <i class="fas fa-print"></i> Imprimir
            </button>
        </div>
    </div>

    <div class="row">
        <!-- Coluna Principal -->
        <div class="col-lg-8">
            <!-- Informações do Respondente -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-user"></i> Informações do Respondente
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2">
                                <strong>Nome:</strong>
                                <?= $resposta['respondente_nome'] ? htmlspecialchars($resposta['respondente_nome']) : '<span class="text-muted">Anônimo</span>' ?>
                            </p>
                            <p class="mb-2">
                                <strong>E-mail:</strong>
                                <?= $resposta['respondente_email'] ? htmlspecialchars($resposta['respondente_email']) : '<span class="text-muted">Não informado</span>' ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2">
                                <strong>Iniciado em:</strong>
                                <?= date('d/m/Y H:i:s', strtotime($resposta['iniciado_em'])) ?>
                            </p>
                            <?php if ($resposta['concluido_em']): ?>
                                <p class="mb-2">
                                    <strong>Concluído em:</strong>
                                    <?= date('d/m/Y H:i:s', strtotime($resposta['concluido_em'])) ?>
                                </p>
                            <?php endif; ?>
                            <p class="mb-2">
                                <strong>IP:</strong>
                                <?= htmlspecialchars($resposta['respondente_ip'] ?? 'Não registrado') ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Respostas por Seção -->
            <?php foreach ($secoes as $secao): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-folder"></i>
                            <?= htmlspecialchars($secao['titulo']) ?>
                        </h4>
                        <?php if ($secao['descricao']): ?>
                            <small class="text-muted"><?= htmlspecialchars($secao['descricao']) ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php
                        // Filtrar perguntas desta seção
                        $perguntasSecao = array_filter($perguntas, function($p) use ($secao) {
                            return $p['secao_id'] == $secao['id'];
                        });

                        if (empty($perguntasSecao)): ?>
                            <p class="text-muted">Nenhuma pergunta nesta seção.</p>
                        <?php else: ?>
                            <?php foreach ($perguntasSecao as $pergunta): ?>
                                <div class="pergunta-resposta mb-4">
                                    <div class="pergunta-titulo">
                                        <strong><?= htmlspecialchars($pergunta['pergunta']) ?></strong>
                                        <?php if ($pergunta['obrigatoria']): ?>
                                            <span class="badge bg-danger ms-2">Obrigatória</span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($pergunta['descricao']): ?>
                                        <div class="pergunta-descricao text-muted">
                                            <?= htmlspecialchars($pergunta['descricao']) ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="resposta-valor mt-3">
                                        <?php
                                        if (isset($respostasPorPergunta[$pergunta['id']])) {
                                            $detalhe = $respostasPorPergunta[$pergunta['id']];

                                            // Exibir resposta baseada no tipo
                                            if ($detalhe['valor_texto']) {
                                                echo '<div class="resposta-texto">' . nl2br(htmlspecialchars($detalhe['valor_texto'])) . '</div>';
                                            } elseif ($detalhe['opcao_selecionada']) {
                                                echo '<div class="resposta-opcao"><i class="fas fa-check-circle text-success"></i> ' . htmlspecialchars($detalhe['opcao_selecionada']) . '</div>';
                                            } elseif ($detalhe['opcoes_selecionadas']) {
                                                $opcoes = json_decode($detalhe['opcoes_selecionadas'], true);
                                                echo '<div class="resposta-opcoes">';
                                                foreach ($opcoes as $opcao) {
                                                    echo '<div><i class="fas fa-check-square text-primary"></i> ' . htmlspecialchars($opcao) . '</div>';
                                                }
                                                echo '</div>';
                                            } elseif ($detalhe['valor_numerico'] !== null) {
                                                echo '<div class="resposta-numero"><strong>' . $detalhe['valor_numerico'] . '</strong></div>';
                                            } elseif ($detalhe['valor_data']) {
                                                echo '<div class="resposta-data"><i class="fas fa-calendar"></i> ' . date('d/m/Y', strtotime($detalhe['valor_data'])) . '</div>';
                                            } elseif ($detalhe['arquivo_path']) {
                                                echo '<div class="resposta-arquivo"><i class="fas fa-file"></i> ' . htmlspecialchars(basename($detalhe['arquivo_path'])) . '</div>';
                                            } else {
                                                echo '<div class="text-muted">Não respondida</div>';
                                            }

                                            // Exibir pontuação se houver
                                            if ($formulario['tipo_pontuacao'] !== 'nenhum' && $detalhe['pontuacao_obtida'] !== null) {
                                                $corPontuacao = $detalhe['pontuacao_obtida'] >= $pergunta['pontos'] ? 'success' : 'warning';
                                                echo '<div class="mt-2"><span class="badge bg-' . $corPontuacao . '">Pontuação: ' . number_format($detalhe['pontuacao_obtida'], 1) . ' / ' . $pergunta['pontos'] . '</span></div>';
                                            }
                                        } else {
                                            echo '<div class="text-muted"><i class="fas fa-times-circle"></i> Não respondida</div>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Coluna Lateral - Estatísticas -->
        <div class="col-lg-4">
            <!-- Status -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-info-circle"></i> Status da Resposta
                </div>
                <div class="card-body text-center">
                    <?php
                    $badges = [
                        'concluida' => ['success', 'check-circle'],
                        'em_andamento' => ['warning', 'clock'],
                        'incompleta' => ['secondary', 'times-circle']
                    ];
                    $badge = $badges[$resposta['status_resposta']] ?? ['secondary', 'question-circle'];
                    ?>
                    <div class="status-badge">
                        <span class="badge bg-<?= $badge[0] ?> p-3" style="font-size: 18px;">
                            <i class="fas fa-<?= $badge[1] ?>"></i>
                            <?= ucfirst(str_replace('_', ' ', $resposta['status_resposta'])) ?>
                        </span>
                    </div>
                </div>
            </div>

            <?php if ($resposta['status_resposta'] === 'concluida' && $formulario['tipo_pontuacao'] !== 'nenhum'): ?>
                <!-- Pontuação -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-trophy"></i> Pontuação
                    </div>
                    <div class="card-body text-center">
                        <div class="pontuacao-display mb-3">
                            <h1 class="display-4 mb-0">
                                <?= number_format($resposta['pontuacao_total'], 1) ?>
                            </h1>
                            <p class="text-muted">de <?= $formulario['pontuacao_maxima'] ?> pontos</p>
                        </div>

                        <div class="progress mb-3" style="height: 25px;">
                            <?php
                            $percentual = $resposta['percentual_acerto'];
                            $corBarra = $percentual >= 70 ? 'success' : ($percentual >= 50 ? 'warning' : 'danger');
                            ?>
                            <div class="progress-bar bg-<?= $corBarra ?>"
                                 role="progressbar"
                                 style="width: <?= $percentual ?>%">
                                <?= number_format($percentual, 1) ?>%
                            </div>
                        </div>

                        <?php if ($faixa): ?>
                            <div class="faixa-pontuacao p-3 rounded" style="background-color: <?= htmlspecialchars($faixa['cor']) ?>20; border: 2px solid <?= htmlspecialchars($faixa['cor']) ?>;">
                                <h5 style="color: <?= htmlspecialchars($faixa['cor']) ?>;">
                                    <i class="fas fa-award"></i>
                                    <?= htmlspecialchars($faixa['titulo']) ?>
                                </h5>
                                <?php if ($faixa['mensagem']): ?>
                                    <p class="mb-0 small"><?= htmlspecialchars($faixa['mensagem']) ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Tempo de Resposta -->
            <?php if ($resposta['tempo_resposta']): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-stopwatch"></i> Tempo de Resposta
                    </div>
                    <div class="card-body text-center">
                        <h3 class="mb-0">
                            <?= gmdate('H:i:s', $resposta['tempo_resposta']) ?>
                        </h3>
                        <small class="text-muted">
                            (<?= gmdate('i', $resposta['tempo_resposta']) ?> minutos e <?= gmdate('s', $resposta['tempo_resposta']) ?> segundos)
                        </small>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Ações -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-cog"></i> Ações
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button onclick="imprimirResposta()" class="btn btn-outline-primary">
                            <i class="fas fa-print"></i> Imprimir Resposta
                        </button>
                        <button onclick="deletarResposta(<?= $resposta['id'] ?>)" class="btn btn-outline-danger">
                            <i class="fas fa-trash"></i> Deletar Resposta
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function imprimirResposta() {
    window.print();
}

function deletarResposta(id) {
    if (!confirm('Tem certeza que deseja deletar esta resposta? Esta ação não pode ser desfeita.')) {
        return;
    }

    $.ajax({
        url: '<?= BASE_URL ?>formularios-dinamicos/api/deletar_resposta.php',
        method: 'POST',
        data: JSON.stringify({ resposta_id: id }),
        contentType: 'application/json',
        success: function(response) {
            if (response.success) {
                alert('Resposta deletada com sucesso!');
                window.location.href = '<?= BASE_URL ?>formularios-dinamicos/respostas.php?id=<?= $formulario['id'] ?>';
            } else {
                alert('Erro: ' + response.message);
            }
        },
        error: function() {
            alert('Erro ao deletar resposta. Tente novamente.');
        }
    });
}
</script>

<?php
// Incluir footer
include $APP_PATH . 'views/layouts/footer.php';
?>
