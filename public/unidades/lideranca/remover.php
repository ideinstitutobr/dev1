<?php
/**
 * View: Remover Liderança
 * Remove cargo de liderança de uma unidade (soft delete)
 */

define('SGC_SYSTEM', true);
require_once __DIR__ . '/../../../app/config/config.php';
require_once __DIR__ . '/../../../app/classes/Database.php';
require_once __DIR__ . '/../../../app/classes/Auth.php';
require_once __DIR__ . '/../../../app/models/Unidade.php';
require_once __DIR__ . '/../../../app/models/UnidadeSetor.php';
require_once __DIR__ . '/../../../app/models/UnidadeLideranca.php';
require_once __DIR__ . '/../../../app/controllers/UnidadeController.php';
require_once __DIR__ . '/../../../app/controllers/UnidadeLiderancaController.php';

Auth::requireLogin();
Auth::requireAdmin();

// Verifica se ID da liderança foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = 'ID da liderança não fornecido';
    header('Location: ../listar.php');
    exit;
}

$liderancaId = filter_var($_GET['id'], FILTER_VALIDATE_INT);
if (!$liderancaId) {
    $_SESSION['error_message'] = 'ID da liderança inválido';
    header('Location: ../listar.php');
    exit;
}

$controllerLideranca = new UnidadeLiderancaController();
$controllerUnidade = new UnidadeController();

// Busca liderança
$lideranca = $controllerLideranca->buscarPorId($liderancaId);
if (!$lideranca) {
    $_SESSION['error_message'] = 'Liderança não encontrada';
    header('Location: ../listar.php');
    exit;
}

// Busca unidade
$unidade = $controllerUnidade->buscarPorId($lideranca['unidade_id']);
if (!$unidade) {
    $_SESSION['error_message'] = 'Unidade não encontrada';
    header('Location: ../listar.php');
    exit;
}

$pageTitle = 'Remover Liderança';
$breadcrumb = '<a href="../../dashboard.php">Dashboard</a> > <a href="../listar.php">Unidades</a> > <a href="../visualizar.php?id=' . $unidade['id'] . '">' . e($unidade['nome']) . '</a> > Remover Liderança';

$erro = '';
$sucesso = '';

// Processa remoção
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultado = $controllerLideranca->processarRemocao();
    if ($resultado['success']) {
        $_SESSION['success_message'] = $resultado['message'];
        header('Location: ../visualizar.php?id=' . $unidade['id']);
        exit;
    } else {
        $erro = $resultado['message'];
    }
}

// Conta colaboradores sob esta liderança (se for gerente ou diretor)
$db = Database::getInstance();
$pdo = $db->getConnection();

$totalColaboradores = 0;
if (in_array($lideranca['cargo_lideranca'], ['diretor_varejo', 'gerente_loja'])) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM unidade_colaboradores
        WHERE unidade_id = ? AND ativo = 1
    ");
    $stmt->execute([$unidade['id']]);
    $totalColaboradores = $stmt->fetch()['total'];
} elseif ($lideranca['cargo_lideranca'] == 'supervisor_loja' && $lideranca['unidade_setor_id']) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM unidade_colaboradores
        WHERE unidade_setor_id = ? AND ativo = 1
    ");
    $stmt->execute([$lideranca['unidade_setor_id']]);
    $totalColaboradores = $stmt->fetch()['total'];
}

// Mapeia nomes de cargos
$cargoLabels = [
    'diretor_varejo' => 'Diretor de Varejo',
    'gerente_loja' => 'Gerente de Loja',
    'supervisor_loja' => 'Supervisor de Loja'
];

include __DIR__ . '/../../../app/views/layouts/header.php';
?>

<style>
    .form-container {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        max-width: 700px;
        margin: 0 auto;
    }
    .warning-box {
        background: #fff3cd;
        border: 2px solid #ffc107;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 25px;
    }
    .warning-box h3 {
        margin: 0 0 15px 0;
        color: #856404;
        font-size: 18px;
    }
    .warning-icon {
        font-size: 48px;
        text-align: center;
        margin-bottom: 15px;
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
    .impact-box {
        background: #e7f3ff;
        border: 1px solid #0066cc;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
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
    .btn-danger {
        background: #dc3545;
        color: white;
    }
    .btn-danger:hover {
        background: #c82333;
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
    .form-group {
        margin-bottom: 20px;
    }
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #2d3748;
    }
    .form-group input, .form-group textarea {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e1e8ed;
        border-radius: 5px;
        font-size: 14px;
        font-family: inherit;
    }
    .form-group textarea {
        resize: vertical;
        min-height: 80px;
    }
    .help-text {
        font-size: 13px;
        color: #718096;
        margin-top: 5px;
    }
</style>

<div class="form-container">
    <?php if ($erro): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
    <?php endif; ?>

    <div class="warning-box">
        <div class="warning-icon">⚠️</div>
        <h3 style="text-align: center;">Confirmar Remoção de Liderança</h3>
        <p style="text-align: center; margin: 0; color: #856404;">
            Esta ação removerá o cargo de liderança, mas não excluirá o colaborador da unidade.
        </p>
    </div>

    <div class="info-card">
        <h4 style="margin-top: 0; color: #2d3748;">📋 Detalhes da Liderança</h4>
        <div class="info-row">
            <span class="info-label">Unidade:</span>
            <span class="info-value"><?php echo e($unidade['nome']); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Cargo:</span>
            <span class="info-value"><?php echo $cargoLabels[$lideranca['cargo_lideranca']] ?? $lideranca['cargo_lideranca']; ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Colaborador:</span>
            <span class="info-value"><?php echo e($lideranca['colaborador_nome']); ?></span>
        </div>
        <?php if ($lideranca['setor_supervisionado']): ?>
        <div class="info-row">
            <span class="info-label">Setor Supervisionado:</span>
            <span class="info-value"><?php echo e($lideranca['setor_supervisionado']); ?></span>
        </div>
        <?php endif; ?>
        <div class="info-row">
            <span class="info-label">Desde:</span>
            <span class="info-value"><?php echo date('d/m/Y', strtotime($lideranca['data_inicio'])); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Tempo no Cargo:</span>
            <span class="info-value">
                <?php
                $inicio = new DateTime($lideranca['data_inicio']);
                $hoje = new DateTime();
                $diff = $inicio->diff($hoje);
                $anos = $diff->y;
                $meses = $diff->m;
                $dias = $diff->d;

                $tempo = [];
                if ($anos > 0) $tempo[] = $anos . ' ano' . ($anos > 1 ? 's' : '');
                if ($meses > 0) $tempo[] = $meses . ' mês' . ($meses > 1 ? 'es' : '');
                if ($dias > 0) $tempo[] = $dias . ' dia' . ($dias > 1 ? 's' : '');

                echo implode(', ', $tempo) ?: 'Menos de 1 dia';
                ?>
            </span>
        </div>
    </div>

    <?php if ($totalColaboradores > 0): ?>
    <div class="impact-box">
        <strong>ℹ️ Impacto:</strong> Esta liderança atualmente gerencia/supervisiona
        <strong><?php echo $totalColaboradores; ?> colaborador(es)</strong>.
        Ao remover, a unidade<?php echo $lideranca['setor_supervisionado'] ? '/setor' : ''; ?> ficará temporariamente sem
        <?php echo strtolower($cargoLabels[$lideranca['cargo_lideranca']] ?? $lideranca['cargo_lideranca']); ?>.
    </div>
    <?php endif; ?>

    <form method="POST" action="" id="formRemover">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="lideranca_id" value="<?php echo $liderancaId; ?>">

        <!-- Data de Término -->
        <div class="form-group">
            <label>Data de Término<span style="color: #e53e3e;">*</span></label>
            <input
                type="date"
                name="data_fim"
                required
                value="<?php echo date('Y-m-d'); ?>"
                max="<?php echo date('Y-m-d'); ?>"
            >
            <div class="help-text">
                Data em que o colaborador deixou o cargo de liderança
            </div>
        </div>

        <!-- Motivo -->
        <div class="form-group">
            <label>Motivo da Remoção</label>
            <textarea
                name="observacoes"
                placeholder="Ex: Transferência, promoção, reestruturação..."
            ></textarea>
            <div class="help-text">
                Informações adicionais sobre a remoção (opcional)
            </div>
        </div>

        <!-- Ações -->
        <div class="actions">
            <button type="submit" class="btn btn-danger">🗑️ Confirmar Remoção</button>
            <a href="../visualizar.php?id=<?php echo $unidade['id']; ?>" class="btn btn-secondary">❌ Cancelar</a>
        </div>
    </form>
</div>

<script>
// Confirmação adicional ao submeter
document.getElementById('formRemover').addEventListener('submit', function(e) {
    const confirmacao = confirm(
        'Tem certeza que deseja remover esta liderança?\n\n' +
        'O colaborador permanecerá vinculado à unidade, mas perderá o cargo de liderança.'
    );

    if (!confirmacao) {
        e.preventDefault();
    }
});
</script>

<?php include __DIR__ . '/../../../app/views/layouts/footer.php'; ?>
