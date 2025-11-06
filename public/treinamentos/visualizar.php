<?php
/**
 * View: Visualizar Treinamento
 */

// Define constante do sistema
define('SGC_SYSTEM', true);

// Carrega configurações e classes
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';
require_once __DIR__ . '/../../app/models/Treinamento.php';
require_once __DIR__ . '/../../app/controllers/TreinamentoController.php';

// Instancia controller
$controller = new TreinamentoController();

// Busca treinamento
$id = $_GET['id'] ?? 0;
$treinamento = $controller->visualizar($id);

if (!$treinamento) {
    $_SESSION['error_message'] = 'Treinamento não encontrado';
    header('Location: listar.php');
    exit;
}

// Configurações da página
$pageTitle = 'Visualizar Treinamento';
$breadcrumb = '<a href="../dashboard.php">Dashboard</a> > <a href="listar.php">Treinamentos</a> > ' . $treinamento['nome'];

// Inclui header
include __DIR__ . '/../../app/views/layouts/header.php';
?>

<style>
    .treinamento-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        border-radius: 10px;
        margin-bottom: 30px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .treinamento-header h2 {
        margin: 0 0 15px 0;
        font-size: 32px;
    }

    .treinamento-header .meta {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
        opacity: 0.95;
        font-size: 15px;
    }

    .treinamento-header .meta span {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .info-card {
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    .info-card h3 {
        color: #667eea;
        font-size: 18px;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #f0f0f0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid #f5f5f5;
    }

    .info-row:last-child {
        border-bottom: none;
    }

    .info-label {
        font-weight: 600;
        color: #666;
    }

    .info-value {
        color: #333;
        text-align: right;
    }

    .badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .badge-primary {
        background: #d1ecf1;
        color: #0c5460;
    }

    .badge-success {
        background: #d4edda;
        color: #155724;
    }

    .badge-danger {
        background: #f8d7da;
        color: #721c24;
    }

    .badge-info {
        background: #e7f3ff;
        color: #0066cc;
    }

    .badge-warning {
        background: #fff3cd;
        color: #856404;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        text-align: center;
        transition: transform 0.3s;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }

    .stat-card .icon {
        font-size: 45px;
        margin-bottom: 15px;
    }

    .stat-card .number {
        font-size: 36px;
        font-weight: 700;
        color: #667eea;
        margin-bottom: 5px;
    }

    .stat-card .label {
        font-size: 14px;
        color: #666;
    }

    .participantes-table {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        overflow: hidden;
        margin-bottom: 30px;
    }

    .participantes-table h3 {
        padding: 20px;
        margin: 0;
        color: #667eea;
        border-bottom: 2px solid #f0f0f0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .participantes-table table {
        width: 100%;
        border-collapse: collapse;
    }

    .participantes-table th,
    .participantes-table td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid #f0f0f0;
    }

    .participantes-table th {
        background: #f8f9fa;
        font-weight: 600;
        color: #333;
    }

    .participantes-table tr:hover {
        background: #f8f9fa;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #999;
    }

    .empty-state .icon {
        font-size: 70px;
        margin-bottom: 20px;
        opacity: 0.5;
    }

    .btn-group {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .btn {
        padding: 12px 25px;
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

    .btn-secondary {
        background: #6c757d;
        color: white;
    }

    .btn-secondary:hover {
        background: #5a6268;
    }

    .btn-danger {
        background: #dc3545;
        color: white;
    }

    .btn-danger:hover {
        background: #c82333;
    }

    .btn-success {
        background: #28a745;
        color: white;
    }

    .btn-success:hover {
        background: #218838;
    }

    .btn-info {
        background: #17a2b8;
        color: white;
    }

    .btn-info:hover {
        background: #138496;
    }

    .observacoes-box {
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 30px;
    }

    .observacoes-box h3 {
        color: #667eea;
        font-size: 18px;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .observacoes-box p {
        color: #666;
        line-height: 1.6;
        margin: 0;
        white-space: pre-wrap;
    }

    .agenda-table {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        overflow: hidden;
        margin-bottom: 30px;
    }

    .agenda-table h3 {
        padding: 20px;
        margin: 0;
        color: #667eea;
        border-bottom: 2px solid #f0f0f0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .agenda-table table {
        width: 100%;
        border-collapse: collapse;
    }

    .agenda-table th,
    .agenda-table td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid #f0f0f0;
    }

    .agenda-table th {
        background: #f8f9fa;
        font-weight: 600;
        color: #333;
    }

    .agenda-table tr:hover {
        background: #f8f9fa;
    }
</style>

<!-- Header do Treinamento -->
<div class="treinamento-header">
    <h2><?php echo e($treinamento['nome']); ?></h2>
    <div class="meta">
        <span>
            <?php
            $statusClass = match($treinamento['status']) {
                'Programado' => 'badge-primary',
                'Em Andamento' => 'badge-warning',
                'Executado' => 'badge-success',
                'Cancelado' => 'badge-danger',
                default => 'badge-secondary'
            };
            ?>
            <span class="badge <?php echo $statusClass; ?>"><?php echo e($treinamento['status']); ?></span>
        </span>
        <span>
            <?php
            $tipoClass = match($treinamento['tipo']) {
                'Normativos' => 'badge-info',
                'Comportamentais' => 'badge-success',
                'Técnicos' => 'badge-warning',
                default => 'badge-secondary'
            };
            ?>
            <span class="badge <?php echo $tipoClass; ?>"><?php echo e($treinamento['tipo']); ?></span>
        </span>
        <span>
            <span class="badge badge-primary"><?php echo e($treinamento['modalidade'] ?? 'Presencial'); ?></span>
        </span>
        <span>📅 ID: #<?php echo $treinamento['id']; ?></span>
        <span>📅 Cadastrado em: <?php echo date('d/m/Y', strtotime($treinamento['created_at'])); ?></span>
    </div>
</div>

<!-- Estatísticas -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="icon">👥</div>
        <div class="number"><?php echo $treinamento['estatisticas']['total_participantes']; ?></div>
        <div class="label">Total de Inscritos</div>
    </div>

    <div class="stat-card">
        <div class="icon">✅</div>
        <div class="number"><?php echo $treinamento['estatisticas']['total_presentes']; ?></div>
        <div class="label">Total de Presentes</div>
    </div>

    <div class="stat-card">
        <div class="icon">❌</div>
        <div class="number"><?php echo $treinamento['estatisticas']['total_ausentes']; ?></div>
        <div class="label">Total de Ausentes</div>
    </div>

    <div class="stat-card">
        <div class="icon">📊</div>
        <div class="number"><?php echo number_format($treinamento['estatisticas']['percentual_presenca'], 1); ?>%</div>
        <div class="label">Indice de Frequência</div>
    </div>

    <?php if ($treinamento['estatisticas']['media_avaliacao'] > 0): ?>
    <div class="stat-card">
        <div class="icon">⭐</div>
        <div class="number"><?php echo number_format($treinamento['estatisticas']['media_avaliacao'], 1); ?></div>
        <div class="label">Avaliação Média</div>
    </div>
    <?php endif; ?>
</div>

<!-- Informações Gerais -->
<div class="info-grid">
    <!-- Dados Básicos -->
    <div class="info-card">
        <h3>📋 Dados Básicos</h3>
        <div class="info-row">
            <span class="info-label">Nome:</span>
            <span class="info-value"><?php echo e($treinamento['nome']); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Tipo:</span>
            <span class="info-value">
                <?php
                $tipoClass = match($treinamento['tipo']) {
                    'Normativos' => 'badge-info',
                    'Comportamentais' => 'badge-success',
                    'Técnicos' => 'badge-warning',
                    default => 'badge-secondary'
                };
                ?>
                <span class="badge <?php echo $tipoClass; ?>"><?php echo e($treinamento['tipo']); ?></span>
            </span>
        </div>
        <div class="info-row">
            <span class="info-label">Modalidade:</span>
            <span class="info-value">
                <span class="badge badge-primary"><?php echo e($treinamento['modalidade'] ?? 'Presencial'); ?></span>
            </span>
        </div>
        <?php if ($treinamento['fornecedor']): ?>
        <div class="info-row">
            <span class="info-label">Fornecedor:</span>
            <span class="info-value"><?php echo e($treinamento['fornecedor']); ?></span>
        </div>
        <?php endif; ?>
        <?php if ($treinamento['instrutor']): ?>
        <div class="info-row">
            <span class="info-label">Instrutor:</span>
            <span class="info-value"><?php echo e($treinamento['instrutor']); ?></span>
        </div>
        <?php endif; ?>
        <div class="info-row">
            <span class="info-label">Status:</span>
            <span class="info-value">
                <?php
                $statusClass = match($treinamento['status']) {
                    'Programado' => 'badge-primary',
                    'Em Andamento' => 'badge-warning',
                    'Executado' => 'badge-success',
                    'Cancelado' => 'badge-danger',
                    default => 'badge-secondary'
                };
                ?>
                <span class="badge <?php echo $statusClass; ?>"><?php echo e($treinamento['status']); ?></span>
            </span>
        </div>
    </div>

    <!-- Planejamento Estratégico -->
    <?php if ($treinamento['componente_pe'] || $treinamento['programa']): ?>
    <div class="info-card">
        <h3>🎯 Planejamento Estratégico</h3>
        <?php if ($treinamento['componente_pe']): ?>
        <div class="info-row">
            <span class="info-label">Componente P.E.:</span>
            <span class="info-value"><?php echo e($treinamento['componente_pe']); ?></span>
        </div>
        <?php endif; ?>
        <?php if ($treinamento['programa']): ?>
        <div class="info-row">
            <span class="info-label">Programa:</span>
            <span class="info-value"><?php echo e($treinamento['programa']); ?></span>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Período e Carga Horária -->
    <div class="info-card">
        <h3>📅 Período e Carga Horária</h3>
        <div class="info-row">
            <span class="info-label">Data de Início:</span>
            <span class="info-value">
                <?php echo $treinamento['data_inicio'] ? date('d/m/Y', strtotime($treinamento['data_inicio'])) : '-'; ?>
            </span>
        </div>
        <div class="info-row">
            <span class="info-label">Data de Término:</span>
            <span class="info-value">
                <?php echo $treinamento['data_fim'] ? date('d/m/Y', strtotime($treinamento['data_fim'])) : '-'; ?>
            </span>
        </div>
        <div class="info-row">
            <span class="info-label">Carga Horária:</span>
            <span class="info-value">
                <?php echo $treinamento['carga_horaria'] ? number_format($treinamento['carga_horaria'], 1) . 'h' : '-'; ?>
            </span>
        </div>
        <?php if ($treinamento['carga_horaria_complementar']): ?>
        <div class="info-row">
            <span class="info-label">CH Complementar:</span>
            <span class="info-value">
                <?php echo number_format($treinamento['carga_horaria_complementar'], 1); ?>h
            </span>
        </div>
        <?php endif; ?>
        <div class="info-row">
            <span class="info-label">Duração Total:</span>
            <span class="info-value">
                <?php
                if ($treinamento['data_inicio'] && $treinamento['data_fim']) {
                    $inicio = new DateTime($treinamento['data_inicio']);
                    $fim = new DateTime($treinamento['data_fim']);
                    $diferenca = $inicio->diff($fim);
                    echo $diferenca->days + 1 . ' dia(s)';
                } else {
                    echo '-';
                }
                ?>
            </span>
        </div>
    </div>

    <!-- Informações Financeiras -->
    <div class="info-card">
        <h3>💰 Informações Financeiras</h3>
        <div class="info-row">
            <span class="info-label">Custo Total:</span>
            <span class="info-value">
                <?php
                if ($treinamento['custo_total']) {
                    echo 'R$ ' . number_format($treinamento['custo_total'], 2, ',', '.');
                } else {
                    echo '-';
                }
                ?>
            </span>
        </div>
        <?php if ($treinamento['custo_total'] && $treinamento['estatisticas']['total_participantes'] > 0): ?>
        <div class="info-row">
            <span class="info-label">Custo por Participante:</span>
            <span class="info-value">
                R$ <?php echo number_format($treinamento['custo_total'] / $treinamento['estatisticas']['total_participantes'], 2, ',', '.'); ?>
            </span>
        </div>
        <?php endif; ?>
    </div>

    <!-- Informações do Sistema -->
    <div class="info-card">
        <h3>⚙️ Informações do Sistema</h3>
        <div class="info-row">
            <span class="info-label">ID:</span>
            <span class="info-value">#<?php echo $treinamento['id']; ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Origem:</span>
            <span class="info-value">
                <span class="badge badge-info"><?php echo strtoupper($treinamento['origem']); ?></span>
            </span>
        </div>
        <div class="info-row">
            <span class="info-label">Cadastrado em:</span>
            <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($treinamento['created_at'])); ?></span>
        </div>
        <?php if ($treinamento['updated_at'] && $treinamento['updated_at'] != $treinamento['created_at']): ?>
        <div class="info-row">
            <span class="info-label">Última atualização:</span>
            <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($treinamento['updated_at'])); ?></span>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Descritivos da Matriz -->
<?php if ($treinamento['objetivo'] || $treinamento['resultados_esperados'] || $treinamento['justificativa']): ?>
<div class="observacoes-box">
    <h3>📝 Descritivos da Matriz</h3>

    <?php if ($treinamento['objetivo']): ?>
    <div style="margin-bottom: 20px;">
        <strong style="color: #667eea;">O Que (Objetivo):</strong>
        <p style="margin-top: 5px;"><?php echo nl2br(e($treinamento['objetivo'])); ?></p>
    </div>
    <?php endif; ?>

    <?php if ($treinamento['resultados_esperados']): ?>
    <div style="margin-bottom: 20px;">
        <strong style="color: #667eea;">Resultados Esperados:</strong>
        <p style="margin-top: 5px;"><?php echo nl2br(e($treinamento['resultados_esperados'])); ?></p>
    </div>
    <?php endif; ?>

    <?php if ($treinamento['justificativa']): ?>
    <div>
        <strong style="color: #667eea;">Por Que (Justificativa):</strong>
        <p style="margin-top: 5px;"><?php echo nl2br(e($treinamento['justificativa'])); ?></p>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Observações -->
<?php if ($treinamento['observacoes']): ?>
<div class="observacoes-box">
    <h3>📝 Observações Adicionais</h3>
    <p><?php echo nl2br(e($treinamento['observacoes'])); ?></p>
</div>
<?php endif; ?>

<!-- Agenda -->
<?php if (!empty($treinamento['agenda'])): ?>
<div class="agenda-table">
    <h3>📆 Agenda do Treinamento</h3>
    <table>
        <thead>
            <tr>
                <th>Data/Hora</th>
                <th>Descrição</th>
                <th>Local</th>
                <th>Duração</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($treinamento['agenda'] as $item): ?>
            <tr>
                <td>
                    <?php echo date('d/m/Y', strtotime($item['data_inicio'])); ?>
                    <?php if ($item['hora_inicio']): ?>
                        <br><small><?php echo substr($item['hora_inicio'], 0, 5); ?></small>
                    <?php endif; ?>
                </td>
                <td><?php echo e($item['descricao']); ?></td>
                <td><?php echo e($item['local'] ?? '-'); ?></td>
                <td>
                    <?php
                    if ($item['data_inicio'] && $item['data_fim']) {
                        $inicio = new DateTime($item['data_inicio'] . ' ' . ($item['hora_inicio'] ?? '00:00:00'));
                        $fim = new DateTime($item['data_fim'] . ' ' . ($item['hora_fim'] ?? '23:59:59'));
                        $diff = $inicio->diff($fim);
                        if ($diff->days > 0) {
                            echo $diff->days . ' dia(s)';
                        } else {
                            echo $diff->h . 'h' . ($diff->i > 0 ? ' ' . $diff->i . 'min' : '');
                        }
                    } else {
                        echo '-';
                    }
                    ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Participantes -->
<div class="participantes-table">
    <h3>👥 Participantes (<?php echo count($treinamento['participantes']); ?>)</h3>
    <?php if (empty($treinamento['participantes'])): ?>
        <div class="empty-state">
            <div class="icon">👥</div>
            <p>Nenhum participante vinculado a este treinamento ainda</p>
            <a href="../participantes/vincular.php?treinamento_id=<?php echo $treinamento['id']; ?>" class="btn btn-primary" style="margin-top: 20px;">
                ➕ Vincular Participantes
            </a>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Cargo</th>
                    <th>Departamento</th>
                    <th>Status</th>
                    <th>Check-in</th>
                    <th>Avaliação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($treinamento['participantes'] as $participante): ?>
                <tr>
                    <td>
                        <strong><?php echo e($participante['colaborador_nome']); ?></strong>
                        <?php if ($participante['colaborador_email']): ?>
                            <br><small style="color: #999;"><?php echo e($participante['colaborador_email']); ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?php echo e($participante['cargo'] ?? '-'); ?></td>
                    <td><?php echo e($participante['departamento'] ?? '-'); ?></td>
                    <td>
                        <?php
                        $statusClass = match($participante['status_participacao'] ?? 'Confirmado') {
                            'Presente' => 'badge-success',
                            'Ausente' => 'badge-danger',
                            'Justificado' => 'badge-warning',
                            default => 'badge-primary'
                        };
                        ?>
                        <span class="badge <?php echo $statusClass; ?>">
                            <?php echo e($participante['status_participacao'] ?? 'Confirmado'); ?>
                        </span>
                    </td>
                    <td style="text-align: center;">
                        <?php echo $participante['check_in_realizado'] ? '✅' : '⏳'; ?>
                    </td>
                    <td style="text-align: center;">
                        <?php
                        if ($participante['nota_avaliacao_reacao']) {
                            echo str_repeat('⭐', (int)$participante['nota_avaliacao_reacao']);
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Botões de Ação -->
<div class="btn-group">
    <a href="editar.php?id=<?php echo $treinamento['id']; ?>" class="btn btn-primary">
        ✏️ Editar Treinamento
    </a>

    <?php if ($treinamento['status'] !== 'Cancelado'): ?>
    <a href="actions.php?action=cancelar&id=<?php echo $treinamento['id']; ?>"
       class="btn btn-danger"
       onclick="return confirm('Deseja realmente cancelar este treinamento?');">
        ❌ Cancelar Treinamento
    </a>
    <?php endif; ?>

    <?php if ($treinamento['status'] !== 'Executado' && $treinamento['status'] !== 'Cancelado'): ?>
    <a href="actions.php?action=marcar_executado&id=<?php echo $treinamento['id']; ?>"
       class="btn btn-success"
       onclick="return confirm('Marcar este treinamento como executado?');">
        ✅ Marcar como Executado
    </a>
    <?php endif; ?>

    <a href="../agenda/gerenciar.php?treinamento_id=<?php echo $treinamento['id']; ?>" class="btn btn-info">
        📅 Gerenciar Agenda/Turmas
    </a>

    <a href="../participantes/vincular.php?treinamento_id=<?php echo $treinamento['id']; ?>" class="btn btn-primary">
        ➕ Vincular Participantes
    </a>

    <a href="listar.php" class="btn btn-secondary">
        ← Voltar para Lista
    </a>
</div>

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>
