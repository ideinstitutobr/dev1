<?php
/**
 * Comparar Respostas
 * Compara duas respostas lado a lado
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

// Verificar autenticação
if (!Auth::isLogged()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

// Validar IDs
if (empty($_GET['id1']) || empty($_GET['id2'])) {
    die('IDs das respostas não informados. Use: ?id1=X&id2=Y');
}

$resposta1Id = (int)$_GET['id1'];
$resposta2Id = (int)$_GET['id2'];

// Buscar respostas
$respostaModel = new FormResposta();
$resposta1 = $respostaModel->buscarPorId($resposta1Id);
$resposta2 = $respostaModel->buscarPorId($resposta2Id);

if (!$resposta1 || !$resposta2) {
    die('Uma ou ambas as respostas não foram encontradas');
}

// Verificar se são do mesmo formulário
if ($resposta1['formulario_id'] !== $resposta2['formulario_id']) {
    die('As respostas devem ser do mesmo formulário');
}

// Buscar formulário
$formularioModel = new FormularioDinamico();
$formulario = $formularioModel->buscarPorId($resposta1['formulario_id']);

if (!$formulario) {
    die('Formulário não encontrado');
}

// Verificar permissão
$userId = Auth::getUserId();
$isAdmin = Auth::isAdmin();

if ($formulario['usuario_id'] != $userId && !$isAdmin) {
    die('Sem permissão para comparar respostas deste formulário');
}

// Buscar detalhes das respostas
$detalheModel = new FormRespostaDetalhe();
$detalhes1 = $detalheModel->listarPorResposta($resposta1Id);
$detalhes2 = $detalheModel->listarPorResposta($resposta2Id);

// Organizar respostas por pergunta
$respostasPorPergunta1 = [];
foreach ($detalhes1 as $detalhe) {
    $respostasPorPergunta1[$detalhe['pergunta_id']] = $detalhe;
}

$respostasPorPergunta2 = [];
foreach ($detalhes2 as $detalhe) {
    $respostasPorPergunta2[$detalhe['pergunta_id']] = $detalhe;
}

// Buscar seções e perguntas
$secaoModel = new FormSecao();
$secoes = $secaoModel->listarPorFormulario($formulario['id']);

$perguntaModel = new FormPergunta();
$perguntas = $perguntaModel->listarPorFormulario($formulario['id']);

// Função helper para extrair valor da resposta
function extrairValorResposta($detalhe) {
    if (!$detalhe) return null;

    if ($detalhe['valor_texto']) {
        return $detalhe['valor_texto'];
    } elseif ($detalhe['opcao_selecionada']) {
        return $detalhe['opcao_selecionada'];
    } elseif ($detalhe['opcoes_selecionadas']) {
        $opcoes = json_decode($detalhe['opcoes_selecionadas'], true);
        return implode(', ', $opcoes);
    } elseif ($detalhe['valor_numerico'] !== null) {
        return $detalhe['valor_numerico'];
    } elseif ($detalhe['valor_data']) {
        return date('d/m/Y', strtotime($detalhe['valor_data']));
    } elseif ($detalhe['arquivo_path']) {
        return basename($detalhe['arquivo_path']);
    }

    return null;
}

// Incluir header
include $APP_PATH . 'views/layouts/header.php';
?>

<style>
.comparison-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 12px;
    margin-bottom: 2rem;
}

.respondente-card {
    border-radius: 12px;
    border: 2px solid #e2e8f0;
    padding: 1.5rem;
    background: white;
}

.respondente-card.winner {
    border-color: #48bb78;
    background: #f0fff4;
}

.comparison-table {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.comparison-table th {
    background: #f7fafc;
    padding: 1rem;
    font-weight: 600;
    border-bottom: 2px solid #e2e8f0;
}

.comparison-table td {
    padding: 1rem;
    vertical-align: top;
    border-bottom: 1px solid #e2e8f0;
}

.comparison-table tr:hover {
    background: #f7fafc;
}

.valor-igual {
    background: #e6fffa;
    border-left: 3px solid #38b2ac;
}

.valor-diferente {
    background: #fff5f5;
    border-left: 3px solid #fc8181;
}

.secao-header {
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 0.75rem 1rem;
    font-weight: 600;
}

.stat-badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    margin: 0.25rem;
}
</style>

<div class="container-fluid mt-4">
    <!-- Cabeçalho -->
    <div class="comparison-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2 class="mb-2">
                    <i class="fas fa-columns"></i>
                    Comparação de Respostas
                </h2>
                <p class="mb-0 opacity-75">
                    <?= htmlspecialchars($formulario['titulo']) ?>
                </p>
            </div>
            <div class="col-md-4 text-end">
                <a href="respostas.php?id=<?= $formulario['id'] ?>" class="btn btn-light">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
            </div>
        </div>
    </div>

    <!-- Cards dos Respondentes -->
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="respondente-card <?= $resposta1['percentual_acerto'] > $resposta2['percentual_acerto'] ? 'winner' : '' ?>">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h5 class="mb-1">Resposta #<?= $resposta1['id'] ?></h5>
                        <p class="text-muted mb-0">
                            <?= $resposta1['respondente_nome'] ? htmlspecialchars($resposta1['respondente_nome']) : 'Anônimo' ?>
                        </p>
                        <small class="text-muted">
                            <?= $resposta1['respondente_email'] ? htmlspecialchars($resposta1['respondente_email']) : '' ?>
                        </small>
                    </div>
                    <?php if ($resposta1['percentual_acerto'] > $resposta2['percentual_acerto']): ?>
                        <span class="badge bg-success" style="font-size: 1rem;">
                            <i class="fas fa-trophy"></i> Melhor
                        </span>
                    <?php endif; ?>
                </div>

                <div class="row">
                    <div class="col-6">
                        <div class="stat-badge bg-primary text-white">
                            <div class="small">Pontuação</div>
                            <div style="font-size: 1.25rem;"><?= number_format($resposta1['percentual_acerto'] ?? 0, 1) ?>%</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-badge bg-info text-white">
                            <div class="small">Tempo</div>
                            <div style="font-size: 1.25rem;"><?= gmdate('i:s', $resposta1['tempo_resposta'] ?? 0) ?></div>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <small class="text-muted">
                        <i class="fas fa-calendar"></i>
                        <?= date('d/m/Y H:i', strtotime($resposta1['concluido_em'] ?? $resposta1['iniciado_em'])) ?>
                    </small>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <div class="respondente-card <?= $resposta2['percentual_acerto'] > $resposta1['percentual_acerto'] ? 'winner' : '' ?>">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h5 class="mb-1">Resposta #<?= $resposta2['id'] ?></h5>
                        <p class="text-muted mb-0">
                            <?= $resposta2['respondente_nome'] ? htmlspecialchars($resposta2['respondente_nome']) : 'Anônimo' ?>
                        </p>
                        <small class="text-muted">
                            <?= $resposta2['respondente_email'] ? htmlspecialchars($resposta2['respondente_email']) : '' ?>
                        </small>
                    </div>
                    <?php if ($resposta2['percentual_acerto'] > $resposta1['percentual_acerto']): ?>
                        <span class="badge bg-success" style="font-size: 1rem;">
                            <i class="fas fa-trophy"></i> Melhor
                        </span>
                    <?php endif; ?>
                </div>

                <div class="row">
                    <div class="col-6">
                        <div class="stat-badge bg-primary text-white">
                            <div class="small">Pontuação</div>
                            <div style="font-size: 1.25rem;"><?= number_format($resposta2['percentual_acerto'] ?? 0, 1) ?>%</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-badge bg-info text-white">
                            <div class="small">Tempo</div>
                            <div style="font-size: 1.25rem;"><?= gmdate('i:s', $resposta2['tempo_resposta'] ?? 0) ?></div>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <small class="text-muted">
                        <i class="fas fa-calendar"></i>
                        <?= date('d/m/Y H:i', strtotime($resposta2['concluido_em'] ?? $resposta2['iniciado_em'])) ?>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Comparação Detalhada -->
    <?php foreach ($secoes as $secao): ?>
        <?php
        $perguntasSecao = array_filter($perguntas, function($p) use ($secao) {
            return $p['secao_id'] == $secao['id'];
        });

        if (empty($perguntasSecao)) continue;
        ?>

        <div class="card mb-4">
            <div class="secao-header">
                <i class="fas fa-folder"></i>
                <?= htmlspecialchars($secao['titulo']) ?>
            </div>

            <div class="table-responsive">
                <table class="table comparison-table mb-0">
                    <thead>
                        <tr>
                            <th style="width: 40%">Pergunta</th>
                            <th style="width: 30%">Resposta #<?= $resposta1['id'] ?></th>
                            <th style="width: 30%">Resposta #<?= $resposta2['id'] ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($perguntasSecao as $pergunta): ?>
                            <?php
                            $detalhe1 = $respostasPorPergunta1[$pergunta['id']] ?? null;
                            $detalhe2 = $respostasPorPergunta2[$pergunta['id']] ?? null;

                            $valor1 = extrairValorResposta($detalhe1);
                            $valor2 = extrairValorResposta($detalhe2);

                            $iguais = ($valor1 === $valor2);
                            $classeComparacao = $iguais ? 'valor-igual' : 'valor-diferente';
                            ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($pergunta['pergunta']) ?></strong>
                                    <?php if ($pergunta['obrigatoria']): ?>
                                        <span class="badge bg-danger ms-2" style="font-size: 0.7rem;">Obrigatória</span>
                                    <?php endif; ?>
                                </td>
                                <td class="<?= $classeComparacao ?>">
                                    <?php if ($valor1): ?>
                                        <div><?= nl2br(htmlspecialchars($valor1)) ?></div>
                                        <?php if ($detalhe1 && $detalhe1['pontuacao_obtida'] !== null): ?>
                                            <small class="text-muted">
                                                <i class="fas fa-star"></i>
                                                <?= number_format($detalhe1['pontuacao_obtida'], 1) ?> pts
                                            </small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">Não respondida</span>
                                    <?php endif; ?>
                                </td>
                                <td class="<?= $classeComparacao ?>">
                                    <?php if ($valor2): ?>
                                        <div><?= nl2br(htmlspecialchars($valor2)) ?></div>
                                        <?php if ($detalhe2 && $detalhe2['pontuacao_obtida'] !== null): ?>
                                            <small class="text-muted">
                                                <i class="fas fa-star"></i>
                                                <?= number_format($detalhe2['pontuacao_obtida'], 1) ?> pts
                                            </small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">Não respondida</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Resumo de Diferenças -->
    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0">
                <i class="fas fa-chart-bar"></i>
                Resumo da Comparação
            </h5>
        </div>
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-3">
                    <div class="stat-badge bg-success text-white w-100">
                        <div class="small">Diferença de Pontuação</div>
                        <div style="font-size: 1.5rem;">
                            <?= abs(number_format($resposta1['percentual_acerto'] - $resposta2['percentual_acerto'], 1)) ?>%
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-badge bg-info text-white w-100">
                        <div class="small">Diferença de Tempo</div>
                        <div style="font-size: 1.5rem;">
                            <?= abs(gmdate('i:s', abs(($resposta1['tempo_resposta'] ?? 0) - ($resposta2['tempo_resposta'] ?? 0)))) ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-badge bg-warning text-white w-100">
                        <div class="small">Total de Perguntas</div>
                        <div style="font-size: 1.5rem;"><?= count($perguntas) ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-badge bg-secondary text-white w-100">
                        <div class="small">Status</div>
                        <div style="font-size: 1rem;">
                            <?= ucfirst($resposta1['status_resposta']) ?><br>
                            vs<br>
                            <?= ucfirst($resposta2['status_resposta']) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Incluir footer
include $APP_PATH . 'views/layouts/footer.php';
?>
