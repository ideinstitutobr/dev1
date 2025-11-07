<?php
/**
 * View: Editar Vínculo de Colaborador
 * Permite alterar o setor de um colaborador vinculado à unidade
 */

define('SGC_SYSTEM', true);
require_once __DIR__ . '/../../../app/config/config.php';
require_once __DIR__ . '/../../../app/classes/Database.php';
require_once __DIR__ . '/../../../app/classes/Auth.php';
require_once __DIR__ . '/../../../app/models/Colaborador.php';
require_once __DIR__ . '/../../../app/models/Unidade.php';
require_once __DIR__ . '/../../../app/models/UnidadeSetor.php';
require_once __DIR__ . '/../../../app/models/UnidadeColaborador.php';
require_once __DIR__ . '/../../../app/controllers/UnidadeController.php';
require_once __DIR__ . '/../../../app/controllers/UnidadeSetorController.php';
require_once __DIR__ . '/../../../app/controllers/UnidadeColaboradorController.php';

Auth::requireLogin();
Auth::requireAdmin();

// Verifica se ID do vínculo foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = 'ID do vínculo não fornecido';
    header('Location: ../listar.php');
    exit;
}

$vinculoId = filter_var($_GET['id'], FILTER_VALIDATE_INT);
if (!$vinculoId) {
    $_SESSION['error_message'] = 'ID do vínculo inválido';
    header('Location: ../listar.php');
    exit;
}

$controllerColaborador = new UnidadeColaboradorController();
$controllerUnidade = new UnidadeController();
$controllerSetor = new UnidadeSetorController();

// Busca vínculo
$vinculo = $controllerColaborador->buscarPorId($vinculoId);
if (!$vinculo) {
    $_SESSION['error_message'] = 'Vínculo não encontrado';
    header('Location: ../listar.php');
    exit;
}

// Busca unidade
$unidade = $controllerUnidade->buscarPorId($vinculo['unidade_id']);
if (!$unidade) {
    $_SESSION['error_message'] = 'Unidade não encontrada';
    header('Location: ../listar.php');
    exit;
}

$pageTitle = 'Editar Setor do Colaborador';
$breadcrumb = '<a href="../../dashboard.php">Dashboard</a> > <a href="../listar.php">Unidades</a> > <a href="../visualizar.php?id=' . $unidade['id'] . '">' . e($unidade['nome']) . '</a> > Editar Setor';

$erro = '';
$sucesso = '';

// Processa edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultado = $controllerColaborador->processarEdicaoVinculo();
    if ($resultado['success']) {
        $_SESSION['success_message'] = $resultado['message'];
        header('Location: ../visualizar.php?id=' . $unidade['id']);
        exit;
    } else {
        $erro = $resultado['message'];
    }
}

// Busca setores ativos da unidade
$setores = $controllerSetor->buscarPorUnidade($vinculo['unidade_id'], true);

// Verifica se colaborador tem liderança no setor atual
$db = Database::getInstance();
$pdo = $db->getConnection();

$stmt = $pdo->prepare("
    SELECT ul.*, us.setor as setor_supervisionado
    FROM unidade_lideranca ul
    INNER JOIN unidade_setores us ON ul.unidade_setor_id = us.id
    WHERE ul.colaborador_id = ?
      AND ul.unidade_setor_id = ?
      AND ul.ativo = 1
");
$stmt->execute([$vinculo['colaborador_id'], $vinculo['unidade_setor_id']]);
$liderancaSetorAtual = $stmt->fetch();

include __DIR__ . '/../../../app/views/layouts/header.php';
?>

<style>
    .form-container {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        max-width: 800px;
        margin: 0 auto;
    }
    .header-card {
        background: linear-gradient(135deg, #ffa500 0%, #ff8c00 100%);
        color: white;
        padding: 25px;
        border-radius: 10px;
        margin-bottom: 25px;
    }
    .header-card h2 {
        margin: 0 0 10px 0;
        font-size: 24px;
    }
    .header-card p {
        margin: 0;
        opacity: 0.9;
        font-size: 14px;
    }
    .info-card {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #e1e8ed;
    }
    .info-row:last-child {
        border-bottom: none;
    }
    .info-label {
        font-weight: 600;
        color: #718096;
    }
    .info-value {
        color: #2d3748;
    }
    .warning-box {
        background: #fff3cd;
        border: 2px solid #ffc107;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    .warning-box strong {
        color: #856404;
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
        border-color: #ffa500;
        outline: none;
    }
    .form-group textarea {
        resize: vertical;
        min-height: 100px;
    }
    .help-text {
        font-size: 13px;
        color: #718096;
        margin-top: 5px;
    }
    .required {
        color: #e53e3e;
        margin-left: 3px;
    }
    .current-setor {
        background: #e7f3ff;
        border: 2px solid #0066cc;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        text-align: center;
    }
    .current-setor h4 {
        margin: 0 0 5px 0;
        color: #0066cc;
        font-size: 18px;
    }
    .current-setor p {
        margin: 0;
        color: #2d3748;
        font-size: 14px;
    }
    .actions {
        display: flex;
        gap: 10px;
        margin-top: 30px;
    }
    .btn {
        padding: 12px 30px;
        border-radius: 5px;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
        cursor: pointer;
        border: none;
        font-size: 14px;
        transition: all 0.3s;
    }
    .btn-primary {
        background: #ffa500;
        color: white;
    }
    .btn-primary:hover {
        background: #ff8c00;
        transform: translateY(-2px);
    }
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    .btn-secondary:hover {
        background: #5a6268;
    }
    .alert {
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    .badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: 600;
        margin-left: 8px;
    }
    .badge-primary {
        background: #dce7ff;
        color: #0066cc;
    }
</style>

<div class="header-card">
    <h2>✏️ Editar Setor do Colaborador</h2>
    <p>Altere o setor onde o colaborador está alocado nesta unidade</p>
</div>

<div class="form-container">
    <?php if ($erro): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
    <?php endif; ?>

    <div class="info-card">
        <h4 style="margin-top: 0; color: #2d3748;">👤 Informações do Colaborador</h4>
        <div class="info-row">
            <span class="info-label">Nome:</span>
            <span class="info-value">
                <?php echo e($vinculo['colaborador_nome']); ?>
                <?php if ($vinculo['is_vinculo_principal']): ?>
                    <span class="badge badge-primary">Vínculo Principal</span>
                <?php endif; ?>
            </span>
        </div>
        <div class="info-row">
            <span class="info-label">E-mail:</span>
            <span class="info-value"><?php echo e($vinculo['colaborador_email'] ?: '-'); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Cargo:</span>
            <span class="info-value"><?php echo e($vinculo['cargo_especifico'] ?: $vinculo['colaborador_cargo'] ?: '-'); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Unidade:</span>
            <span class="info-value"><?php echo e($unidade['nome']); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Vinculado desde:</span>
            <span class="info-value"><?php echo date('d/m/Y', strtotime($vinculo['data_vinculacao'])); ?></span>
        </div>
    </div>

    <div class="current-setor">
        <h4>📦 Setor Atual</h4>
        <p><?php echo e($vinculo['setor_nome']); ?></p>
    </div>

    <?php if ($liderancaSetorAtual): ?>
    <div class="warning-box">
        <strong>⚠️ Atenção!</strong> Este colaborador possui liderança no setor atual
        <strong>(<?php echo e($liderancaSetorAtual['setor_supervisionado']); ?>)</strong>.
        Ao mudar de setor, considere se a liderança também precisa ser ajustada.
    </div>
    <?php endif; ?>

    <?php if (empty($setores)): ?>
        <div class="alert alert-warning" style="background: #fff3cd; color: #856404; border: 1px solid #ffeaa7;">
            ⚠️ <strong>Atenção:</strong> Esta unidade não possui outros setores ativos.
            É necessário ativar outros setores para poder fazer a transferência.
            <br><br>
            <a href="../setores/gerenciar.php?unidade_id=<?php echo $unidade['id']; ?>" class="btn btn-secondary">
                Gerenciar Setores
            </a>
        </div>
    <?php elseif (count($setores) == 1 && $setores[0]['id'] == $vinculo['unidade_setor_id']): ?>
        <div class="alert alert-warning" style="background: #fff3cd; color: #856404; border: 1px solid #ffeaa7;">
            ⚠️ <strong>Atenção:</strong> Esta unidade possui apenas o setor atual ativo.
            Ative outros setores para poder fazer a transferência.
            <br><br>
            <a href="../setores/gerenciar.php?unidade_id=<?php echo $unidade['id']; ?>" class="btn btn-secondary">
                Gerenciar Setores
            </a>
        </div>
    <?php else: ?>

    <form method="POST" action="" id="formEditarSetor">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="vinculo_id" value="<?php echo $vinculoId; ?>">
        <input type="hidden" name="unidade_id" value="<?php echo $unidade['id']; ?>">

        <!-- Novo Setor -->
        <div class="form-group">
            <label>Novo Setor<span class="required">*</span></label>
            <select name="novo_setor_id" required id="setorSelect">
                <option value="">Selecione o novo setor...</option>
                <?php foreach ($setores as $setor): ?>
                    <?php if ($setor['id'] != $vinculo['unidade_setor_id']): ?>
                        <option value="<?php echo $setor['id']; ?>">
                            <?php echo e($setor['setor']); ?>
                            <?php if ($setor['responsavel_nome']): ?>
                                (Resp: <?php echo e($setor['responsavel_nome']); ?>)
                            <?php endif; ?>
                        </option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
            <div class="help-text">
                Selecione o setor para onde o colaborador será transferido
            </div>
        </div>

        <!-- Data da Mudança -->
        <div class="form-group">
            <label>Data da Mudança<span class="required">*</span></label>
            <input
                type="date"
                name="data_mudanca"
                required
                value="<?php echo date('Y-m-d'); ?>"
                max="<?php echo date('Y-m-d'); ?>"
            >
            <div class="help-text">
                Data em que ocorreu ou ocorrerá a mudança de setor
            </div>
        </div>

        <!-- Motivo da Mudança -->
        <div class="form-group">
            <label>Motivo da Mudança<span class="required">*</span></label>
            <textarea
                name="motivo_mudanca"
                required
                placeholder="Ex: Reestruturação organizacional, necessidade do setor, desenvolvimento profissional..."
            ></textarea>
            <div class="help-text">
                Descreva o motivo desta mudança de setor (será registrado no histórico)
            </div>
        </div>

        <!-- Observações Adicionais -->
        <div class="form-group">
            <label>Observações Adicionais</label>
            <textarea
                name="observacoes_adicionais"
                placeholder="Informações complementares (opcional)..."
                rows="3"
            ></textarea>
        </div>

        <!-- Ações -->
        <div class="actions">
            <button type="submit" class="btn btn-primary">💾 Salvar Mudança de Setor</button>
            <a href="../visualizar.php?id=<?php echo $unidade['id']; ?>" class="btn btn-secondary">❌ Cancelar</a>
        </div>
    </form>

    <?php endif; ?>
</div>

<script>
// Highlight do setor selecionado
document.getElementById('setorSelect')?.addEventListener('change', function() {
    if (this.value) {
        this.style.borderColor = '#ffa500';
        this.style.backgroundColor = '#fff9e6';
    } else {
        this.style.borderColor = '#e1e8ed';
        this.style.backgroundColor = 'white';
    }
});
</script>

<?php include __DIR__ . '/../../../app/views/layouts/footer.php'; ?>
