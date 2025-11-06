<?php
/**
 * View: Registrar Frequência
 * Interface para registrar presença dos participantes
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
if (!isset($_GET['sessao_id'])) {
    header('Location: selecionar_treinamento.php');
    exit;
}

$sessaoId = (int)$_GET['sessao_id'];

// Buscar dados da sessão
$controller = new FrequenciaController();
$sessao = $controller->buscarSessao($sessaoId);

if (!$sessao) {
    $_SESSION['flash_error'] = 'Sessão não encontrada';
    header('Location: selecionar_treinamento.php');
    exit;
}

// Buscar frequências
$frequencias = $controller->listarFrequencia($sessaoId);

// Estatísticas
$totalParticipantes = count($frequencias);
$totalPresentes = count(array_filter($frequencias, fn($f) => $f['status'] === 'Presente'));
$totalAusentes = count(array_filter($frequencias, fn($f) => $f['status'] === 'Ausente'));
$totalJustificados = count(array_filter($frequencias, fn($f) => $f['status'] === 'Justificado'));
$taxaPresenca = $totalParticipantes > 0 ? ($totalPresentes / $totalParticipantes) * 100 : 0;

// Configurações da página
$pageTitle = 'Registrar Frequência';
$breadcrumb = '<a href="../dashboard.php">Dashboard</a> > <a href="selecionar_treinamento.php">Frequência</a> > <a href="sessoes.php?treinamento_id=' . $sessao['treinamento_id'] . '">Sessões</a> > Registrar';

// Inclui header
include __DIR__ . '/../../app/views/layouts/header.php';
?>

<style>
    /* Removidos overrides de .page-header para utilizar estilos globais do layout */

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    .stat-card .icon {
        font-size: 32px;
        margin-bottom: 10px;
    }

    .stat-card .value {
        font-size: 32px;
        font-weight: bold;
        color: #333;
        margin-bottom: 5px;
    }

    .stat-card .label {
        font-size: 14px;
        color: #666;
    }

    .stat-card.success .value { color: #28a745; }
    .stat-card.danger .value { color: #dc3545; }
    .stat-card.warning .value { color: #ffc107; }

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
        padding: 6px 12px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: 600;
    }

    .badge-presente { background: #d4edda; color: #155724; }
    .badge-ausente { background: #f8d7da; color: #721c24; }
    .badge-justificado { background: #fff3cd; color: #856404; }
    .badge-atrasado { background: #cce5ff; color: #004085; }

    .status-select {
        padding: 8px 12px;
        border: 2px solid #e1e8ed;
        border-radius: 5px;
        font-size: 13px;
        cursor: pointer;
        transition: all 0.3s;
    }

    .status-select:focus {
        outline: none;
        border-color: var(--primary-color);
    }

    .status-select.presente { background: #d4edda; color: #155724; border-color: #155724; }
    .status-select.ausente { background: #f8d7da; color: #721c24; border-color: #721c24; }
    .status-select.justificado { background: #fff3cd; color: #856404; border-color: #856404; }
    .status-select.atrasado { background: #cce5ff; color: #004085; border-color: #004085; }

    .quick-actions {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
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
</style>

<!-- Header -->
<div class="page-header">
    <h1>📝 Registrar Frequência</h1>
    <p><strong><?php echo htmlspecialchars($sessao['nome']); ?></strong> - <?php echo htmlspecialchars($sessao['treinamento_nome']); ?></p>
    <p>Data: <?php echo date('d/m/Y', strtotime($sessao['data_sessao'])); ?>
        <?php if ($sessao['hora_inicio']): ?>
            | <?php echo substr($sessao['hora_inicio'], 0, 5); ?>
            <?php if ($sessao['hora_fim']): ?>
                - <?php echo substr($sessao['hora_fim'], 0, 5); ?>
            <?php endif; ?>
        <?php endif; ?>
    </p>
</div>

<!-- Estatísticas -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="icon">👥</div>
        <div class="value"><?php echo $totalParticipantes; ?></div>
        <div class="label">Total Participantes</div>
    </div>

    <div class="stat-card success">
        <div class="icon">✅</div>
        <div class="value"><?php echo $totalPresentes; ?></div>
        <div class="label">Presentes</div>
    </div>

    <div class="stat-card danger">
        <div class="icon">❌</div>
        <div class="value"><?php echo $totalAusentes; ?></div>
        <div class="label">Ausentes</div>
    </div>

    <div class="stat-card warning">
        <div class="icon">📋</div>
        <div class="value"><?php echo $totalJustificados; ?></div>
        <div class="label">Justificados</div>
    </div>

    <div class="stat-card">
        <div class="icon">📊</div>
        <div class="value"><?php echo number_format($taxaPresenca, 1); ?>%</div>
        <div class="label">Taxa de Presença</div>
    </div>
</div>

<!-- Ações Rápidas -->
<div class="quick-actions">
    <button type="button" onclick="marcarTodos('Presente')" class="btn btn-success btn-sm">
        ✅ Marcar Todos Presentes
    </button>
    <button type="button" onclick="marcarTodos('Ausente')" class="btn btn-secondary btn-sm">
        ❌ Marcar Todos Ausentes
    </button>
</div>

<!-- Ações -->
<div class="actions-bar">
    <div>
        Registre a presença de cada participante
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="actions.php?action=exportar&sessao_id=<?php echo $sessaoId; ?>" class="btn btn-success">
            📥 Exportar CSV
        </a>
        <a href="sessoes.php?treinamento_id=<?php echo $sessao['treinamento_id']; ?>" class="btn btn-secondary">
            ← Voltar
        </a>
    </div>
</div>

<!-- Tabela de Frequência -->
<div class="table-container">
    <?php if (empty($frequencias)): ?>
        <div class="empty-state">
            <div class="icon">👥</div>
            <h3>Nenhum participante encontrado</h3>
            <p>Não há participantes vinculados a este treinamento</p>
        </div>
    <?php else: ?>
        <form method="POST" action="actions.php" id="formFrequencia">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="action" value="registrar_frequencia_multipla">
            <input type="hidden" name="sessao_id" value="<?php echo $sessaoId; ?>">
            <input type="hidden" name="treinamento_id" value="<?php echo $sessao['treinamento_id']; ?>">

            <table>
                <thead>
                    <tr>
                        <th>Colaborador</th>
                        <th>Cargo</th>
                        <th>Departamento</th>
                        <th style="text-align: center;">Status Atual</th>
                        <th style="text-align: center;">Alterar Status</th>
                        <th style="text-align: center;">Check-in</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($frequencias as $freq): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($freq['colaborador_nome']); ?></strong></td>
                            <td><?php echo htmlspecialchars($freq['cargo'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($freq['departamento'] ?? '-'); ?></td>
                            <td style="text-align: center;">
                                <span class="badge badge-<?php echo strtolower($freq['status']); ?>">
                                    <?php echo $freq['status']; ?>
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <select name="presencas[<?php echo $freq['id']; ?>]"
                                        class="status-select"
                                        data-id="<?php echo $freq['id']; ?>">
                                    <option value="Presente" <?php echo $freq['status'] === 'Presente' ? 'selected' : ''; ?>>✅ Presente</option>
                                    <option value="Ausente" <?php echo $freq['status'] === 'Ausente' ? 'selected' : ''; ?>>❌ Ausente</option>
                                    <option value="Justificado" <?php echo $freq['status'] === 'Justificado' ? 'selected' : ''; ?>>📋 Justificado</option>
                                    <option value="Atrasado" <?php echo $freq['status'] === 'Atrasado' ? 'selected' : ''; ?>>⏰ Atrasado</option>
                                </select>
                            </td>
                            <td style="text-align: center;">
                                <?php echo $freq['hora_checkin'] ? substr($freq['hora_checkin'], 0, 5) : '-'; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div style="padding: 20px; text-align: center; border-top: 2px solid #f0f0f0;">
                <button type="submit" class="btn btn-success">
                    💾 Salvar Frequência
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
// Atualizar cor do select baseado na opção
document.querySelectorAll('.status-select').forEach(select => {
    select.addEventListener('change', function() {
        this.className = 'status-select ' + this.value.toLowerCase();
    });

    // Aplicar cor inicial
    select.className = 'status-select ' + select.value.toLowerCase();
});

// Marcar todos
function marcarTodos(status) {
    if (!confirm(`Tem certeza que deseja marcar todos como "${status}"?`)) {
        return;
    }

    document.querySelectorAll('.status-select').forEach(select => {
        select.value = status;
        select.className = 'status-select ' + status.toLowerCase();
    });
}

// Confirmação ao submeter
document.getElementById('formFrequencia')?.addEventListener('submit', function(e) {
    if (!confirm('Confirma o registro de frequência?')) {
        e.preventDefault();
    }
});
</script>

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>
