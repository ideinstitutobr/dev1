<?php
define('SGC_SYSTEM', true);
require_once __DIR__ . '/../../../app/config/config.php';
require_once __DIR__ . '/../../../app/classes/Database.php';
require_once __DIR__ . '/../../../app/classes/Auth.php';
require_once __DIR__ . '/../../../app/models/Unidade.php';
require_once __DIR__ . '/../../../app/models/UnidadeSetor.php';
require_once __DIR__ . '/../../../app/controllers/UnidadeController.php';
require_once __DIR__ . '/../../../app/controllers/UnidadeSetorController.php';

Auth::requireLogin();

// Verifica se ID da unidade foi fornecido
if (!isset($_GET['unidade_id']) || empty($_GET['unidade_id'])) {
    $_SESSION['error_message'] = 'ID da unidade não fornecido';
    header('Location: ../listar.php');
    exit;
}

$unidadeId = filter_var($_GET['unidade_id'], FILTER_VALIDATE_INT);
if (!$unidadeId) {
    $_SESSION['error_message'] = 'ID da unidade inválido';
    header('Location: ../listar.php');
    exit;
}

$controllerUnidade = new UnidadeController();
$controllerSetor = new UnidadeSetorController();

// Busca unidade
$unidade = $controllerUnidade->buscarPorId($unidadeId);
if (!$unidade) {
    $_SESSION['error_message'] = 'Unidade não encontrada';
    header('Location: ../listar.php');
    exit;
}

$pageTitle = 'Gerenciar Setores';
$breadcrumb = '<a href="../../dashboard.php">Dashboard</a> > <a href="../listar.php">Unidades</a> > <a href="../visualizar.php?id=' . $unidadeId . '">' . e($unidade['nome']) . '</a> > Setores';

$sucesso = $_SESSION['success_message'] ?? '';
$erro = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Processa ações
if (isset($_GET['acao']) && isset($_GET['setor_id'])) {
    $setorId = filter_var($_GET['setor_id'], FILTER_VALIDATE_INT);
    if ($setorId) {
        if ($_GET['acao'] === 'inativar') {
            $resultado = $controllerSetor->inativar($setorId);
        } elseif ($_GET['acao'] === 'ativar') {
            $resultado = $controllerSetor->ativar($setorId);
        }

        if (isset($resultado)) {
            if ($resultado['success']) {
                $_SESSION['success_message'] = $resultado['message'];
            } else {
                $_SESSION['error_message'] = $resultado['message'];
            }
            header('Location: gerenciar.php?unidade_id=' . $unidadeId);
            exit;
        }
    }
}

// Busca setores da unidade (todos, ativos e inativos)
$setoresAtivos = $controllerSetor->buscarPorUnidade($unidadeId, true);
$setoresInativos = $controllerSetor->buscarPorUnidade($unidadeId, false);
// Remove ativos do array de inativos
$setoresInativos = array_filter($setoresInativos, function($setor) {
    return !$setor['ativo'];
});

// Busca setores disponíveis para adicionar
$setoresDisponiveis = $controllerSetor->getSetoresDisponiveis();

// Busca colaboradores vinculados à unidade para seleção de responsável
$db = Database::getInstance();
$pdo = $db->getConnection();

$colaboradoresVinculados = $pdo->prepare("
    SELECT DISTINCT
        c.id,
        c.nome,
        uc.unidade_setor_id
    FROM colaboradores c
    INNER JOIN unidade_colaboradores uc ON c.id = uc.colaborador_id
    WHERE uc.unidade_id = ?
      AND uc.ativo = 1
      AND c.ativo = 1
    ORDER BY c.nome ASC
");
$colaboradoresVinculados->execute([$unidadeId]);
$colaboradores = $colaboradoresVinculados->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../../../app/views/layouts/header.php';
?>

<style>
    .unit-info {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 25px;
    }
    .unit-info h2 {
        margin: 0 0 10px 0;
        color: #2d3748;
        font-size: 24px;
    }
    .unit-info p {
        margin: 0;
        color: #718096;
        font-size: 14px;
    }
    .actions-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 15px;
    }
    .btn {
        padding: 12px 24px;
        border-radius: 5px;
        text-decoration: none;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        border: none;
        font-size: 14px;
        transition: all 0.3s;
    }
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    .btn:hover {
        opacity: 0.9;
        transform: translateY(-2px);
    }
    .setores-grid {
        display: grid;
        gap: 20px;
        margin-bottom: 30px;
    }
    .setor-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        transition: all 0.3s;
    }
    .setor-card:hover {
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
    .setor-card.inativo {
        opacity: 0.6;
        background: #f8f9fa;
    }
    .setor-info {
        flex: 1;
    }
    .setor-nome {
        font-size: 18px;
        font-weight: 600;
        color: #2d3748;
        margin-bottom: 8px;
    }
    .setor-descricao {
        color: #718096;
        font-size: 14px;
        margin-bottom: 10px;
    }
    .setor-responsavel {
        font-size: 13px;
        color: #667eea;
        font-weight: 600;
    }
    .setor-actions {
        display: flex;
        gap: 8px;
        flex-direction: column;
    }
    .action-btn {
        padding: 8px 16px;
        border-radius: 4px;
        text-decoration: none;
        font-size: 12px;
        font-weight: 600;
        text-align: center;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
    }
    .action-btn-edit {
        background: #667eea;
        color: white;
    }
    .action-btn-toggle {
        background: #6c757d;
        color: white;
    }
    .action-btn-success {
        background: #48bb78;
        color: white;
    }
    .action-btn:hover {
        opacity: 0.8;
    }
    .alert {
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    .alert-warning {
        background: #fff3cd;
        color: #856404;
        border: 1px solid #ffeaa7;
    }
    .alert-info {
        background: #d1ecf1;
        color: #0c5460;
        border: 1px solid #bee5eb;
    }
    .section-title {
        font-size: 20px;
        font-weight: 600;
        color: #2d3748;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .badge {
        background: #667eea;
        color: white;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .badge-warning {
        background: #f59e0b;
    }
    .badge-danger {
        background: #ef4444;
    }
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #718096;
    }
    .empty-state-icon {
        font-size: 64px;
        margin-bottom: 20px;
    }
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }
    .modal.active {
        display: flex;
    }
    .modal-content {
        background: white;
        border-radius: 10px;
        padding: 30px;
        max-width: 500px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
    }
    .modal-header {
        margin-bottom: 20px;
    }
    .modal-header h3 {
        margin: 0;
        color: #2d3748;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #2d3748;
    }
    .form-group select, .form-group input, .form-group textarea {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e1e8ed;
        border-radius: 5px;
        font-size: 14px;
        font-family: inherit;
    }
    .form-group select:focus, .form-group input:focus, .form-group textarea:focus {
        border-color: #667eea;
        outline: none;
    }
    .help-text {
        font-size: 13px;
        color: #718096;
        margin-top: 5px;
    }
    .modal-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }
</style>

<div class="main-content">
    <div class="page-header">
        <h1>🏛️ Gerenciar Setores</h1>
        <p><?php echo $breadcrumb; ?></p>
    </div>

    <?php if ($sucesso): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($sucesso); ?></div>
    <?php endif; ?>

    <?php if ($erro): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
    <?php endif; ?>

    <div class="unit-info">
        <h2>🏢 <?php echo e($unidade['nome']); ?></h2>
        <p>
            <?php if ($unidade['cidade'] && $unidade['estado']): ?>
                📍 <?php echo e($unidade['cidade']); ?> - <?php echo e($unidade['estado']); ?>
            <?php endif; ?>
            <?php if ($unidade['categoria_nome']): ?>
                • <?php echo e($unidade['categoria_nome']); ?>
            <?php endif; ?>
        </p>
    </div>

    <div class="actions-bar">
        <div>
            <h2 style="margin: 0; color: #2d3748;">Setores da Unidade</h2>
        </div>
        <div style="display: flex; gap: 10px;">
            <button onclick="openAddModal()" class="btn btn-primary">➕ Adicionar Setor</button>
            <a href="../visualizar.php?id=<?php echo $unidadeId; ?>" class="btn btn-secondary">← Voltar</a>
        </div>
    </div>

    <!-- Setores Ativos -->
    <div class="section-title">
        ✅ Setores Ativos
        <span class="badge"><?php echo count($setoresAtivos); ?></span>
    </div>

    <?php if (empty($setoresAtivos)): ?>
        <div class="alert alert-info">
            ℹ️ Nenhum setor ativo nesta unidade. Clique em "Adicionar Setor" para começar.
        </div>
    <?php else: ?>
        <div class="setores-grid">
            <?php foreach ($setoresAtivos as $setor): ?>
                <div class="setor-card">
                    <div class="setor-info">
                        <div class="setor-nome"><?php echo e($setor['setor']); ?></div>
                        <?php if ($setor['descricao']): ?>
                            <div class="setor-descricao"><?php echo e($setor['descricao']); ?></div>
                        <?php endif; ?>
                        <?php if ($setor['responsavel_nome']): ?>
                            <div class="setor-responsavel">👤 Responsável: <?php echo e($setor['responsavel_nome']); ?></div>
                        <?php else: ?>
                            <div class="setor-responsavel" style="color: #f59e0b;">⚠️ Sem responsável definido</div>
                        <?php endif; ?>
                    </div>
                    <div class="setor-actions">
                        <button onclick="openResponsavelModal(<?php echo $setor['id']; ?>, '<?php echo e($setor['setor']); ?>', <?php echo $setor['responsavel_colaborador_id'] ?? 'null'; ?>)" class="action-btn action-btn-edit">
                            👤 Responsável
                        </button>
                        <a href="gerenciar.php?unidade_id=<?php echo $unidadeId; ?>&acao=inativar&setor_id=<?php echo $setor['id']; ?>"
                           onclick="return confirm('Deseja realmente inativar este setor?');"
                           class="action-btn action-btn-toggle">
                            🔒 Inativar
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Setores Inativos -->
    <?php if (!empty($setoresInativos)): ?>
        <div class="section-title" style="margin-top: 40px;">
            ❌ Setores Inativos
            <span class="badge badge-warning"><?php echo count($setoresInativos); ?></span>
        </div>

        <div class="setores-grid">
            <?php foreach ($setoresInativos as $setor): ?>
                <div class="setor-card inativo">
                    <div class="setor-info">
                        <div class="setor-nome"><?php echo e($setor['setor']); ?></div>
                        <?php if ($setor['descricao']): ?>
                            <div class="setor-descricao"><?php echo e($setor['descricao']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="setor-actions">
                        <a href="gerenciar.php?unidade_id=<?php echo $unidadeId; ?>&acao=ativar&setor_id=<?php echo $setor['id']; ?>"
                           class="action-btn action-btn-success">
                            ✅ Reativar
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal: Adicionar Setor -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>➕ Adicionar Setor</h3>
        </div>
        <form method="POST" action="actions.php">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="acao" value="adicionar">
            <input type="hidden" name="unidade_id" value="<?php echo $unidadeId; ?>">

            <div class="form-group">
                <label>Setor <span style="color: #e53e3e;">*</span></label>
                <select name="setor" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($setoresDisponiveis as $setor): ?>
                        <option value="<?php echo e($setor); ?>"><?php echo e($setor); ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="help-text">Selecione o setor que deseja ativar nesta unidade</div>
            </div>

            <div class="form-group">
                <label>Descrição (opcional)</label>
                <textarea name="descricao" rows="3" placeholder="Descrição específica para este setor nesta unidade..."></textarea>
            </div>

            <div class="modal-actions">
                <button type="submit" class="btn btn-primary">💾 Adicionar</button>
                <button type="button" onclick="closeAddModal()" class="btn btn-secondary">❌ Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Definir Responsável -->
<div id="responsavelModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>👤 Definir Responsável</h3>
            <p id="responsavelSetorNome" style="color: #718096; margin: 5px 0 0 0;"></p>
        </div>
        <form method="POST" action="actions.php">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="acao" value="definir_responsavel">
            <input type="hidden" name="unidade_id" value="<?php echo $unidadeId; ?>">
            <input type="hidden" name="setor_id" id="responsavel_setor_id" value="">

            <div class="form-group">
                <label>Colaborador Responsável</label>
                <select name="colaborador_id" id="responsavel_colaborador_id">
                    <option value="">Nenhum (remover responsável)</option>
                    <?php foreach ($colaboradores as $col): ?>
                        <option value="<?php echo $col['id']; ?>">
                            <?php echo e($col['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="help-text">
                    Apenas colaboradores vinculados a esta unidade podem ser responsáveis
                </div>
            </div>

            <div class="modal-actions">
                <button type="submit" class="btn btn-primary">💾 Salvar</button>
                <button type="button" onclick="closeResponsavelModal()" class="btn btn-secondary">❌ Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('addModal').classList.add('active');
}

function closeAddModal() {
    document.getElementById('addModal').classList.remove('active');
}

function openResponsavelModal(setorId, setorNome, responsavelId) {
    document.getElementById('responsavel_setor_id').value = setorId;
    document.getElementById('responsavelSetorNome').textContent = 'Setor: ' + setorNome;

    const select = document.getElementById('responsavel_colaborador_id');
    select.value = responsavelId || '';

    document.getElementById('responsavelModal').classList.add('active');
}

function closeResponsavelModal() {
    document.getElementById('responsavelModal').classList.remove('active');
}

// Fechar modais ao clicar fora
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.classList.remove('active');
    }
}
</script>

<?php include __DIR__ . '/../../../app/views/layouts/footer.php'; ?>
