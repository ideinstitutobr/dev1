<?php
/**
 * Lista de Respostas do Formulário
 * Visualização para administradores
 */

define('SGC_SYSTEM', true);

$APP_PATH = '../../app/';
require_once $APP_PATH . 'config/config.php';
require_once $APP_PATH . 'classes/Database.php';
require_once $APP_PATH . 'classes/Auth.php';
require_once $APP_PATH . 'models/FormularioDinamico.php';
require_once $APP_PATH . 'models/FormResposta.php';

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

// Verificar permissão (proprietário ou admin)
$userId = Auth::getUserId();
$isAdmin = Auth::isAdmin();

if ($formulario['usuario_id'] != $userId && !$isAdmin) {
    die('Sem permissão para visualizar respostas deste formulário');
}

// Filtros
$filtros = [];
if (!empty($_GET['status'])) {
    $filtros['status_resposta'] = $_GET['status'];
}
if (!empty($_GET['email'])) {
    $filtros['respondente_email'] = $_GET['email'];
}
if (!empty($_GET['data_inicio'])) {
    $filtros['data_inicio'] = $_GET['data_inicio'];
}
if (!empty($_GET['data_fim'])) {
    $filtros['data_fim'] = $_GET['data_fim'];
}

// Buscar respostas
$respostaModel = new FormResposta();
$respostas = $respostaModel->listarPorFormulario($formularioId, $filtros);

// Obter estatísticas
$estatisticas = $respostaModel->obterEstatisticas($formularioId);

// Incluir header
include $APP_PATH . 'views/layouts/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>
                <i class="fas fa-inbox"></i>
                Respostas: <?= htmlspecialchars($formulario['titulo']) ?>
            </h2>
            <p class="text-muted">
                Visualize e analise todas as respostas recebidas
            </p>
        </div>
        <div class="col-md-4 text-end">
            <a href="builder.php?id=<?= $formularioId ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Voltar ao Builder
            </a>
            <a href="analytics.php?id=<?= $formularioId ?>" class="btn btn-outline-primary">
                <i class="fas fa-chart-line"></i> Analytics
            </a>
            <a href="api/exportar_csv.php?formulario_id=<?= $formularioId ?>" class="btn btn-success">
                <i class="fas fa-file-excel"></i> Exportar CSV
            </a>
        </div>
    </div>

    <!-- Estatísticas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-inbox"></i> Total de Respostas
                    </h5>
                    <h2 class="mb-0"><?= $estatisticas['total_respostas'] ?? 0 ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-check-circle"></i> Completas
                    </h5>
                    <h2 class="mb-0"><?= $estatisticas['completas'] ?? 0 ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-clock"></i> Em Andamento
                    </h5>
                    <h2 class="mb-0"><?= $estatisticas['em_andamento'] ?? 0 ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-chart-bar"></i> Média
                    </h5>
                    <h2 class="mb-0"><?= number_format($estatisticas['media_percentual'] ?? 0, 1) ?>%</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-filter"></i> Filtros
        </div>
        <div class="card-body">
            <form method="GET" action="">
                <input type="hidden" name="id" value="<?= $formularioId ?>">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">Todos</option>
                            <option value="concluida" <?= ($_GET['status'] ?? '') == 'concluida' ? 'selected' : '' ?>>Concluída</option>
                            <option value="em_andamento" <?= ($_GET['status'] ?? '') == 'em_andamento' ? 'selected' : '' ?>>Em Andamento</option>
                            <option value="incompleta" <?= ($_GET['status'] ?? '') == 'incompleta' ? 'selected' : '' ?>>Incompleta</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">E-mail</label>
                        <input type="email" name="email" class="form-control"
                               value="<?= htmlspecialchars($_GET['email'] ?? '') ?>"
                               placeholder="Filtrar por e-mail">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Data Início</label>
                        <input type="date" name="data_inicio" class="form-control"
                               value="<?= htmlspecialchars($_GET['data_inicio'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Data Fim</label>
                        <input type="date" name="data_fim" class="form-control"
                               value="<?= htmlspecialchars($_GET['data_fim'] ?? '') ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de Respostas -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-list"></i> Respostas Recebidas (<?= count($respostas) ?>)
            </div>
            <div id="compareButton" style="display: none;">
                <button onclick="compararSelecionadas()" class="btn btn-sm btn-info">
                    <i class="fas fa-columns"></i> Comparar Selecionadas
                </button>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($respostas)): ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle"></i>
                    Nenhuma resposta encontrada para este formulário.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="40"><input type="checkbox" id="selectAll" onclick="toggleSelectAll()"></th>
                                <th>ID</th>
                                <th>Respondente</th>
                                <th>Data/Hora</th>
                                <th>Status</th>
                                <th>Pontuação</th>
                                <th>Percentual</th>
                                <th>Tempo</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($respostas as $resposta): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox"
                                               class="compare-checkbox"
                                               value="<?= $resposta['id'] ?>"
                                               onchange="updateCompareButton()">
                                    </td>
                                    <td><strong>#<?= $resposta['id'] ?></strong></td>
                                    <td>
                                        <?php if ($resposta['respondente_nome']): ?>
                                            <strong><?= htmlspecialchars($resposta['respondente_nome']) ?></strong><br>
                                            <small class="text-muted"><?= htmlspecialchars($resposta['respondente_email'] ?? 'Sem e-mail') ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">Anônimo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($resposta['concluido_em']): ?>
                                            <?= date('d/m/Y H:i', strtotime($resposta['concluido_em'])) ?>
                                        <?php else: ?>
                                            <?= date('d/m/Y H:i', strtotime($resposta['iniciado_em'])) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $badges = [
                                            'concluida' => 'success',
                                            'em_andamento' => 'warning',
                                            'incompleta' => 'secondary'
                                        ];
                                        $badge = $badges[$resposta['status_resposta']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $badge ?>">
                                            <?= ucfirst(str_replace('_', ' ', $resposta['status_resposta'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($resposta['status_resposta'] == 'concluida'): ?>
                                            <strong><?= number_format($resposta['pontuacao_total'], 1) ?></strong>
                                            <small class="text-muted">/ <?= $formulario['pontuacao_maxima'] ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($resposta['status_resposta'] == 'concluida'): ?>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-<?= $resposta['percentual_acerto'] >= 70 ? 'success' : ($resposta['percentual_acerto'] >= 50 ? 'warning' : 'danger') ?>"
                                                     style="width: <?= $resposta['percentual_acerto'] ?>%">
                                                    <?= number_format($resposta['percentual_acerto'], 1) ?>%
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($resposta['tempo_resposta']): ?>
                                            <?= gmdate('H:i:s', $resposta['tempo_resposta']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="ver_resposta.php?id=<?= $resposta['id'] ?>"
                                           class="btn btn-sm btn-primary"
                                           title="Ver Detalhes">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button class="btn btn-sm btn-danger"
                                                onclick="deletarResposta(<?= $resposta['id'] ?>)"
                                                title="Deletar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
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
                location.reload();
            } else {
                alert('Erro: ' + response.message);
            }
        },
        error: function(xhr) {
            let mensagemErro = 'Erro ao deletar resposta. Tente novamente.';

            try {
                const response = JSON.parse(xhr.responseText);
                if (response.message) {
                    mensagemErro = response.message;
                }
            } catch (e) {
                // Ignora erro de parse
            }

            alert(mensagemErro);
        }
    });
}

function updateCompareButton() {
    const checkboxes = document.querySelectorAll('.compare-checkbox:checked');
    const compareButton = document.getElementById('compareButton');

    if (checkboxes.length === 2) {
        compareButton.style.display = 'block';
    } else {
        compareButton.style.display = 'none';
    }

    // Desabilitar outras checkboxes se já houver 2 selecionadas
    document.querySelectorAll('.compare-checkbox').forEach(cb => {
        if (!cb.checked && checkboxes.length >= 2) {
            cb.disabled = true;
        } else {
            cb.disabled = false;
        }
    });
}

function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.compare-checkbox');

    checkboxes.forEach(cb => {
        cb.checked = selectAll.checked;
        cb.disabled = false;
    });

    updateCompareButton();
}

function compararSelecionadas() {
    const checkboxes = document.querySelectorAll('.compare-checkbox:checked');

    if (checkboxes.length !== 2) {
        alert('Selecione exatamente 2 respostas para comparar');
        return;
    }

    const id1 = checkboxes[0].value;
    const id2 = checkboxes[1].value;

    window.location.href = '<?= BASE_URL ?>formularios-dinamicos/comparar.php?id1=' + id1 + '&id2=' + id2;
}
</script>

<?php
// Incluir footer
include $APP_PATH . 'views/layouts/footer.php';
?>
