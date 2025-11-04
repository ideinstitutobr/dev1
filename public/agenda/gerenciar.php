<?php
/**
 * View: Gerenciar Agenda de Treinamento
 * Lista e gerencia turmas/datas do treinamento
 */

define('SGC_SYSTEM', true);

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';
require_once __DIR__ . '/../../app/models/Agenda.php';
require_once __DIR__ . '/../../app/controllers/AgendaController.php';

Auth::requireLogin(BASE_URL);

// Verifica parâmetro
if (!isset($_GET['treinamento_id'])) {
    $_SESSION['flash_error'] = 'Treinamento não informado';
    header('Location: ../treinamentos/listar.php');
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
    header('Location: ../treinamentos/listar.php');
    exit;
}

// Buscar agendas
$controller = new AgendaController();
$agendas = $controller->listarAgendas($treinamentoId);

// Configurações da página
$pageTitle = 'Agenda do Treinamento';
$breadcrumb = '<a href="../dashboard.php">Dashboard</a> > <a href="../treinamentos/listar.php">Treinamentos</a> > <a href="../treinamentos/visualizar.php?id=' . $treinamentoId . '">' . htmlspecialchars($treinamento['nome']) . '</a> > Agenda';

include __DIR__ . '/../../app/views/layouts/header.php';
?>

<style>
    .page-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        border-radius: 10px;
        margin-bottom: 30px;
    }

    .page-header h1 {
        margin: 0 0 10px 0;
        font-size: 28px;
    }

    .actions-bar {
        background: white;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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
    }

    .btn-primary {
        background: #667eea;
        color: white;
    }

    .btn-primary:hover {
        background: #5568d3;
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

    .badge-programado { background: #d1ecf1; color: #0c5460; }
    .badge-andamento { background: #fff3cd; color: #856404; }
    .badge-concluido { background: #d4edda; color: #155724; }
    .badge-cancelado { background: #f8d7da; color: #721c24; }

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

    .vagas-info {
        font-size: 12px;
        color: #666;
    }

    .vagas-completo {
        color: #dc3545;
        font-weight: 600;
    }

    .vagas-disponivel {
        color: #28a745;
    }
</style>

<div class="page-header">
    <h1>📅 Agenda do Treinamento</h1>
    <p><strong><?php echo htmlspecialchars($treinamento['nome']); ?></strong></p>
</div>

<div class="actions-bar">
    <div>
        <?php if (Auth::hasLevel(['admin', 'gestor'])): ?>
        <a href="criar.php?treinamento_id=<?php echo $treinamentoId; ?>" class="btn btn-primary">
            ➕ Nova Turma/Data
        </a>
        <?php endif; ?>
    </div>
    <div>
        <a href="../treinamentos/visualizar.php?id=<?php echo $treinamentoId; ?>" class="btn btn-secondary">
            ← Voltar ao Treinamento
        </a>
    </div>
</div>

<div class="table-container">
    <?php if (empty($agendas)): ?>
        <div class="empty-state">
            <div class="icon">📅</div>
            <h3>Nenhuma agenda cadastrada</h3>
            <p>Crie turmas e datas para este treinamento</p>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Turma</th>
                    <th>Período</th>
                    <th>Horário</th>
                    <th>Local</th>
                    <th>Vagas</th>
                    <th>Status</th>
                    <th style="text-align: right;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($agendas as $agenda): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($agenda['turma'] ?? '-'); ?></strong></td>
                        <td>
                            <?php echo date('d/m/Y', strtotime($agenda['data_inicio'])); ?>
                            <?php if ($agenda['data_fim']): ?>
                                até <?php echo date('d/m/Y', strtotime($agenda['data_fim'])); ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($agenda['hora_inicio']): ?>
                                <?php echo substr($agenda['hora_inicio'], 0, 5); ?>
                                <?php if ($agenda['hora_fim']): ?>
                                    - <?php echo substr($agenda['hora_fim'], 0, 5); ?>
                                <?php endif; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($agenda['local'] ?? '-'); ?></td>
                        <td>
                            <?php
                            $vagasTotal = $agenda['vagas_total'];
                            $vagasOcupadas = $agenda['total_inscritos'];

                            if ($vagasTotal > 0):
                                $percentual = ($vagasOcupadas / $vagasTotal) * 100;
                                $classe = $vagasOcupadas >= $vagasTotal ? 'vagas-completo' : 'vagas-disponivel';
                            ?>
                                <span class="<?php echo $classe; ?>">
                                    <?php echo $vagasOcupadas; ?>/<?php echo $vagasTotal; ?>
                                </span>
                            <?php else: ?>
                                <span class="vagas-info">Sem limite</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo strtolower(str_replace(' ', '', $agenda['status'])); ?>">
                                <?php echo $agenda['status']; ?>
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <?php if (Auth::hasLevel(['admin', 'gestor'])): ?>
                                <a href="editar.php?id=<?php echo $agenda['id']; ?>" class="btn btn-primary btn-sm">
                                    ✏️ Editar
                                </a>
                                <a href="actions.php?action=deletar&id=<?php echo $agenda['id']; ?>&treinamento_id=<?php echo $treinamentoId; ?>"
                                   class="btn btn-secondary btn-sm"
                                   onclick="return confirm('Excluir esta agenda?')">
                                    🗑️ Excluir
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>
