<?php
/**
 * View: Gerenciar Participantes do Treinamento
 * Lista participantes vinculados com opções de gerenciamento
 */

// Define constante do sistema
define('SGC_SYSTEM', true);

// Carrega configurações e classes
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';
require_once __DIR__ . '/../../app/models/Treinamento.php';
require_once __DIR__ . '/../../app/models/Participante.php';
require_once __DIR__ . '/../../app/controllers/ParticipanteController.php';

// Verifica autenticação
Auth::requireLogin(BASE_URL);

// Verifica se treinamento foi informado
$treinamentoId = $_GET['treinamento_id'] ?? 0;

if (!$treinamentoId) {
    $_SESSION['error_message'] = 'Treinamento não informado';
    header('Location: ../treinamentos/listar.php');
    exit;
}

// Instancia controllers e models
$participanteController = new ParticipanteController();
$treinamentoModel = new Treinamento();

// Busca dados do treinamento
$treinamento = $treinamentoModel->buscarPorId($treinamentoId);

if (!$treinamento) {
    $_SESSION['error_message'] = 'Treinamento não encontrado';
    header('Location: ../treinamentos/listar.php');
    exit;
}

// Busca participantes
$participantes = $participanteController->listarPorTreinamento($treinamentoId);

// Busca estatísticas
$stats = $participanteController->getEstatisticasTreinamento($treinamentoId);

// Configurações da página
$pageTitle = 'Gerenciar Participantes';
$breadcrumb = '<a href="../dashboard.php">Dashboard</a> > <a href="../treinamentos/listar.php">Treinamentos</a> > <a href="../treinamentos/visualizar.php?id=' . $treinamentoId . '">' . e($treinamento['nome']) . '</a> > Gerenciar Participantes';

// Inclui header
include __DIR__ . '/../../app/views/layouts/header.php';
?>

<style>
    .header-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 25px;
        border-radius: 10px;
        margin-bottom: 30px;
    }

    .header-card h2 {
        margin: 0 0 10px 0;
        font-size: 24px;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        text-align: center;
    }

    .stat-card .number {
        font-size: 36px;
        font-weight: bold;
        color: #667eea;
        margin: 10px 0;
    }

    .stat-card .label {
        color: #666;
        font-size: 14px;
    }

    .actions-bar {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .btn {
        padding: 12px 20px;
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

    .btn-primary {
        background: #667eea;
        color: white;
    }

    .btn-primary:hover {
        background: #5568d3;
    }

    .btn-success {
        background: #28a745;
        color: white;
    }

    .btn-success:hover {
        background: #218838;
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
        font-size: 13px;
        text-transform: uppercase;
    }

    td {
        padding: 15px;
        border-top: 1px solid #e1e8ed;
    }

    tbody tr:hover {
        background: #f8f9ff;
    }

    .badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 15px;
        font-size: 11px;
        font-weight: 600;
    }

    .badge-confirmado {
        background: #e7f3ff;
        color: #0066cc;
    }

    .badge-pendente {
        background: #fff3cd;
        color: #856404;
    }

    .badge-presente {
        background: #d4edda;
        color: #155724;
    }

    .badge-ausente {
        background: #f8d7da;
        color: #721c24;
    }

    .badge-cancelado {
        background: #e0e0e0;
        color: #666;
    }

    .check-status {
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .check-status.done {
        color: #28a745;
        font-weight: 600;
    }

    .check-status.pending {
        color: #999;
    }

    .actions-cell {
        display: flex;
        gap: 5px;
        justify-content: flex-end;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #999;
    }

    .empty-state .icon {
        font-size: 80px;
        margin-bottom: 20px;
        opacity: 0.5;
    }

    .filter-bar {
        background: white;
        padding: 15px 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 20px;
        display: flex;
        gap: 15px;
        align-items: center;
    }

    .filter-bar input,
    .filter-bar select {
        padding: 8px 12px;
        border: 2px solid #e1e8ed;
        border-radius: 5px;
        font-size: 14px;
    }

    .filter-bar input {
        flex: 1;
        max-width: 300px;
    }
</style>

<!-- Header -->
<div class="header-card">
    <h2>👥 Gerenciar Participantes</h2>
    <p><?php echo e($treinamento['nome']); ?></p>
    <small>
        📅 <?php echo date('d/m/Y', strtotime($treinamento['data_inicio'])); ?>
        <?php if ($treinamento['data_fim']): ?>
            até <?php echo date('d/m/Y', strtotime($treinamento['data_fim'])); ?>
        <?php endif; ?>
    </small>
</div>

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success">
        ✅ <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-error">
        ❌ <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
    </div>
<?php endif; ?>

<!-- Estatísticas -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="label">👥 Total Participantes</div>
        <div class="number"><?php echo $stats['total_participantes'] ?? 0; ?></div>
    </div>
    <div class="stat-card">
        <div class="label">✅ Check-ins</div>
        <div class="number"><?php echo $stats['total_checkins'] ?? 0; ?></div>
    </div>
    <div class="stat-card">
        <div class="label">📋 Avaliações</div>
        <div class="number"><?php echo $stats['total_avaliacoes'] ?? 0; ?></div>
    </div>
    <div class="stat-card">
        <div class="label">⭐ Média Geral</div>
        <div class="number">
            <?php
            $mediaGeral = 0;
            $count = 0;
            if (!empty($stats['media_avaliacao_reacao']) && $stats['media_avaliacao_reacao'] > 0) {
                $mediaGeral += $stats['media_avaliacao_reacao'];
                $count++;
            }
            if (!empty($stats['media_avaliacao_aprendizado']) && $stats['media_avaliacao_aprendizado'] > 0) {
                $mediaGeral += $stats['media_avaliacao_aprendizado'];
                $count++;
            }
            if (!empty($stats['media_avaliacao_comportamento']) && $stats['media_avaliacao_comportamento'] > 0) {
                $mediaGeral += $stats['media_avaliacao_comportamento'];
                $count++;
            }
            if ($count > 0) {
                echo number_format($mediaGeral / $count, 1);
            } else {
                echo '-';
            }
            ?>
        </div>
    </div>
</div>

<!-- Barra de Ações -->
<div class="actions-bar">
    <div>
        <a href="vincular.php?treinamento_id=<?php echo $treinamentoId; ?>" class="btn btn-primary">
            ➕ Adicionar Participantes
        </a>
        <?php if (count($participantes) > 0): ?>
            <?php if (Auth::hasLevel(['admin', 'gestor'])): ?>
            <button type="button" onclick="enviarConvitesTodos()" class="btn" style="background: #17a2b8; color: white;">
                📧 Enviar Convites a Todos
            </button>
            <?php endif; ?>
            <a href="actions.php?action=exportar&treinamento_id=<?php echo $treinamentoId; ?>" class="btn btn-success">
                📥 Exportar CSV
            </a>
        <?php endif; ?>
    </div>
    <div>
        <a href="../treinamentos/visualizar.php?id=<?php echo $treinamentoId; ?>" class="btn btn-secondary">
            ← Voltar para Treinamento
        </a>
    </div>
</div>

<!-- Filtros -->
<div class="filter-bar">
    <input type="text" id="searchInput" placeholder="🔍 Buscar por nome, email ou cargo..." onkeyup="filterTable()">
    <select id="statusFilter" onchange="filterTable()">
        <option value="">Todos os status</option>
        <option value="Confirmado">Confirmado</option>
        <option value="Pendente">Pendente</option>
        <option value="Presente">Presente</option>
        <option value="Ausente">Ausente</option>
        <option value="Cancelado">Cancelado</option>
    </select>
</div>

<!-- Tabela de Participantes -->
<?php if (empty($participantes)): ?>
    <div class="table-container">
        <div class="empty-state">
            <div class="icon">👥</div>
            <h3>Nenhum participante vinculado</h3>
            <p>Clique em "Adicionar Participantes" para começar a vincular colaboradores a este treinamento</p>
            <a href="vincular.php?treinamento_id=<?php echo $treinamentoId; ?>" class="btn btn-primary" style="margin-top: 20px;">
                ➕ Adicionar Participantes
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="table-container">
        <table id="participantesTable">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Cargo</th>
                    <th>Departamento</th>
                    <th>Status</th>
                    <th>Check-in</th>
                    <th>Avaliação</th>
                    <th style="text-align: right;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($participantes as $p): ?>
                    <tr>
                        <td><strong><?php echo e($p['colaborador_nome']); ?></strong></td>
                        <td><?php echo e($p['colaborador_email']); ?></td>
                        <td><?php echo e($p['cargo'] ?? '-'); ?></td>
                        <td><?php echo e($p['departamento'] ?? '-'); ?></td>
                        <td>
                            <span class="badge badge-<?php echo strtolower($p['status_participacao']); ?>">
                                <?php echo e($p['status_participacao']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($p['check_in_realizado']): ?>
                                <span class="check-status done">
                                    ✅ <?php echo date('d/m/Y H:i', strtotime($p['data_check_in'])); ?>
                                </span>
                            <?php else: ?>
                                <span class="check-status pending">⏳ Pendente</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($p['nota_avaliacao_reacao']): ?>
                                ⭐ <?php echo number_format($p['nota_avaliacao_reacao'], 1); ?>
                            <?php else: ?>
                                <span style="color: #999;">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="actions-cell">
                                <?php if (Auth::hasLevel(['admin', 'gestor'])): ?>
                                    <button type="button"
                                            onclick="enviarConvite(<?php echo $p['id']; ?>, '<?php echo e($p['colaborador_nome']); ?>')"
                                            class="btn btn-sm"
                                            style="background: #17a2b8; color: white;"
                                            title="Enviar convite por e-mail">
                                        📧
                                    </button>
                                <?php endif; ?>

                                <?php if (!$p['check_in_realizado'] && Auth::hasLevel(['admin', 'gestor', 'instrutor'])): ?>
                                    <a href="actions.php?action=checkin&id=<?php echo $p['id']; ?>&treinamento_id=<?php echo $treinamentoId; ?>"
                                       class="btn btn-success btn-sm"
                                       onclick="return confirm('Confirmar check-in de <?php echo e($p['colaborador_nome']); ?>?')">
                                        ✅ Check-in
                                    </a>
                                <?php endif; ?>

                                <a href="avaliar.php?id=<?php echo $p['id']; ?>&treinamento_id=<?php echo $treinamentoId; ?>"
                                   class="btn btn-primary btn-sm">
                                    ⭐ Avaliar
                                </a>

                                <?php if (Auth::hasLevel(['admin', 'gestor'])): ?>
                                    <a href="actions.php?action=desvincular&id=<?php echo $p['id']; ?>&treinamento_id=<?php echo $treinamentoId; ?>"
                                       class="btn btn-secondary btn-sm"
                                       onclick="return confirm('Deseja realmente desvincular <?php echo e($p['colaborador_nome']); ?>?')">
                                        🗑️
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<script>
// Filtro de busca
function filterTable() {
    const searchInput = document.getElementById('searchInput').value.toLowerCase();
    const statusFilter = document.getElementById('statusFilter').value.toLowerCase();
    const table = document.getElementById('participantesTable');
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

    for (let row of rows) {
        const text = row.textContent.toLowerCase();
        const status = row.cells[4].textContent.toLowerCase();

        const matchSearch = searchInput === '' || text.includes(searchInput);
        const matchStatus = statusFilter === '' || status.includes(statusFilter);

        if (matchSearch && matchStatus) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    }
}

// Enviar convite individual
function enviarConvite(participanteId, nomeColaborador) {
    if (!confirm(`Enviar convite por e-mail para ${nomeColaborador}?`)) {
        return;
    }

    const btn = event.target;
    btn.disabled = true;
    btn.textContent = '⏳';

    window.location.href = `actions.php?action=enviar_convite&id=${participanteId}&treinamento_id=<?php echo $treinamentoId; ?>`;
}

// Enviar convites para todos
function enviarConvitesTodos() {
    if (!confirm('Enviar convites por e-mail para TODOS os participantes?')) {
        return;
    }

    const btn = event.target;
    btn.disabled = true;
    btn.textContent = '⏳ Enviando...';

    // Coleta IDs de todos os participantes
    const participantes = <?php echo json_encode(array_column($participantes, 'id')); ?>;

    fetch('actions.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=enviar_convites_multiplos&participantes=${JSON.stringify(participantes)}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message);
            location.reload();
        } else {
            alert('❌ ' + data.message);
            btn.disabled = false;
            btn.textContent = '📧 Enviar Convites a Todos';
        }
    })
    .catch(e => {
        alert('Erro ao enviar convites: ' + e.message);
        btn.disabled = false;
        btn.textContent = '📧 Enviar Convites a Todos';
    });
}
</script>

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>
