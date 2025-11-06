<?php
/**
 * View: Sessões do Treinamento
 * Lista e gerencia sessões de um treinamento
 */

// Define constante do sistema
define('SGC_SYSTEM', true);

// Carrega configurações e classes
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';
require_once __DIR__ . '/../../app/models/Frequencia.php';
require_once __DIR__ . '/../../app/controllers/FrequenciaController.php';

// Verifica autenticação
Auth::requireLogin(BASE_URL);

// Verifica parâmetro
if (!isset($_GET['treinamento_id'])) {
    header('Location: selecionar_treinamento.php');
    exit;
}

$treinamentoId = (int)$_GET['treinamento_id'];

// Buscar dados do treinamento
$db = Database::getInstance();
$pdo = $db->getConnection();

$stmt = $pdo->prepare("SELECT * FROM treinamentos WHERE id = ?");
$stmt->execute([$treinamentoId]);
$treinamento = $stmt->fetch();

if (!$treinamento) {
    $_SESSION['flash_error'] = 'Treinamento não encontrado';
    header('Location: selecionar_treinamento.php');
    exit;
}

// Buscar sessões
$controller = new FrequenciaController();
$sessoes = $controller->listarSessoes($treinamentoId);

// Configurações da página
$pageTitle = 'Sessões - ' . $treinamento['nome'];
$breadcrumb = '<a href="../dashboard.php">Dashboard</a> > <a href="selecionar_treinamento.php">Frequência</a> > Sessões';

// Inclui header
include __DIR__ . '/../../app/views/layouts/header.php';
?>

<style>
    /* Removidos overrides de .page-header para usar estilos globais do layout */

    .treinamento-info-card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 20px;
    }

    .treinamento-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
    }

    .info-item {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .info-item .icon {
        font-size: 24px;
    }

    .info-item .text .label {
        font-size: 12px;
        color: #666;
    }

    .info-item .text .value {
        font-size: 16px;
        font-weight: 600;
        color: #333;
    }

    .actions-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .btn {
        padding: 10px 20px;
        border-radius: 5px;
        text-decoration: none;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
        font-size: 14px;
    }

    /* Removidos overrides de .btn-primary para usar var(--primary-color) do layout */

    .btn-success {
        background: #28a745;
        color: white;
    }

    .btn-success:hover {
        background: #218838;
    }

    .btn-danger {
        background: #dc3545;
        color: white;
    }

    .btn-danger:hover {
        background: #c82333;
    }

    .btn-secondary {
        background: #6c757d;
        color: white;
    }

    .btn-secondary:hover {
        background: #5a6268;
    }

    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
    }

    .table-container {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        overflow: hidden;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    thead {
        background: #f8f9fa;
    }

    th {
        padding: 15px;
        text-align: left;
        font-weight: 600;
        color: #333;
        border-bottom: 2px solid #e1e8ed;
        font-size: 13px;
    }

    td {
        padding: 15px;
        border-bottom: 1px solid #f0f0f0;
    }

    tr:hover {
        background: #f8f9ff;
    }

    .badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: 600;
    }

    .badge-success {
        background: #d4edda;
        color: #155724;
    }

    .badge-warning {
        background: #fff3cd;
        color: #856404;
    }

    .badge-danger {
        background: #f8d7da;
        color: #721c24;
    }

    .actions-cell {
        display: flex;
        gap: 5px;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
    }

    .empty-state .icon {
        font-size: 80px;
        margin-bottom: 20px;
        opacity: 0.5;
    }

    .progress-bar {
        background: #e1e8ed;
        border-radius: 10px;
        height: 8px;
        overflow: hidden;
    }

    .progress-fill {
        background: linear-gradient(90deg, var(--gradient-start) 0%, var(--gradient-end) 100%);
        height: 100%;
        transition: width 0.3s;
    }
</style>

<!-- Header -->
<div class="page-header">
    <h1>📋 Sessões do Treinamento</h1>
    <p><?php echo htmlspecialchars($treinamento['nome']); ?></p>
</div>

<!-- Info do Treinamento -->
<div class="treinamento-info-card">
    <div class="treinamento-info-grid">
        <div class="info-item">
            <div class="icon">📚</div>
            <div class="text">
                <div class="label">Tipo</div>
                <div class="value"><?php echo $treinamento['tipo']; ?></div>
            </div>
        </div>

        <div class="info-item">
            <div class="icon">👨‍🏫</div>
            <div class="text">
                <div class="label">Instrutor</div>
                <div class="value"><?php echo htmlspecialchars($treinamento['instrutor'] ?? '-'); ?></div>
            </div>
        </div>

        <div class="info-item">
            <div class="icon">📅</div>
            <div class="text">
                <div class="label">Data Início</div>
                <div class="value"><?php echo date('d/m/Y', strtotime($treinamento['data_inicio'])); ?></div>
            </div>
        </div>

        <div class="info-item">
            <div class="icon">⏱️</div>
            <div class="text">
                <div class="label">Carga Horária</div>
                <div class="value"><?php echo $treinamento['carga_horaria']; ?>h</div>
            </div>
        </div>
    </div>
</div>

<!-- Ações -->
<div class="actions-bar">
    <div>
        <strong><?php echo count($sessoes); ?></strong> sessão(ões) cadastrada(s)
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="criar_sessao.php?treinamento_id=<?php echo $treinamentoId; ?>" class="btn btn-success">
            ➕ Nova Sessão
        </a>
        <a href="selecionar_treinamento.php" class="btn btn-secondary">
            ← Voltar
        </a>
    </div>
</div>

<!-- Tabela de Sessões -->
<div class="table-container">
    <?php if (empty($sessoes)): ?>
        <div class="empty-state">
            <div class="icon">📋</div>
            <h3>Nenhuma sessão cadastrada</h3>
            <p>Crie a primeira sessão para começar a registrar frequência</p>
            <br>
            <a href="criar_sessao.php?treinamento_id=<?php echo $treinamentoId; ?>" class="btn btn-success">
                ➕ Criar Primeira Sessão
            </a>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Nome da Sessão</th>
                    <th style="text-align: center;">Data</th>
                    <th style="text-align: center;">Horário</th>
                    <th>Local</th>
                    <th style="text-align: center;">Frequência</th>
                    <th style="width: 25%;">Taxa de Presença</th>
                    <th style="text-align: center;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sessoes as $sessao): ?>
                    <?php
                    $taxaPresenca = $sessao['total_participantes'] > 0
                        ? ($sessao['total_presentes'] / $sessao['total_participantes']) * 100
                        : 0;
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($sessao['nome']); ?></strong></td>
                        <td style="text-align: center;">
                            <?php echo date('d/m/Y', strtotime($sessao['data_sessao'])); ?>
                        </td>
                        <td style="text-align: center;">
                            <?php
                            if ($sessao['hora_inicio'] && $sessao['hora_fim']) {
                                echo substr($sessao['hora_inicio'], 0, 5) . ' - ' . substr($sessao['hora_fim'], 0, 5);
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($sessao['local'] ?? '-'); ?></td>
                        <td style="text-align: center;">
                            <?php echo $sessao['total_presentes']; ?> / <?php echo $sessao['total_participantes']; ?>
                        </td>
                        <td>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $taxaPresenca; ?>%"></div>
                            </div>
                            <small style="color: #666;"><?php echo number_format($taxaPresenca, 1); ?>%</small>
                        </td>
                        <td style="text-align: center;">
                            <div class="actions-cell">
                                <a href="registrar_frequencia.php?sessao_id=<?php echo $sessao['id']; ?>"
                                   class="btn btn-primary btn-sm">
                                    📝 Registrar
                                </a>
                                <a href="editar_sessao.php?id=<?php echo $sessao['id']; ?>"
                                   class="btn btn-secondary btn-sm">
                                    ✏️ Editar
                                </a>
                                <a href="actions.php?action=deletar_sessao&id=<?php echo $sessao['id']; ?>&treinamento_id=<?php echo $treinamentoId; ?>"
                                   onclick="return confirm('Tem certeza que deseja deletar esta sessão? Todos os registros de frequência serão perdidos.')"
                                   class="btn btn-danger btn-sm">
                                    🗑️
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>
