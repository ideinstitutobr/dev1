<?php
/**
 * View: Visualizar Colaborador
 */

// Define constante do sistema
define('SGC_SYSTEM', true);

// Carrega configurações e classes
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';
require_once __DIR__ . '/../../app/models/Colaborador.php';
require_once __DIR__ . '/../../app/controllers/ColaboradorController.php';

// Instancia controller
$controller = new ColaboradorController();

// Busca colaborador
$id = $_GET['id'] ?? 0;
$colaborador = $controller->visualizar($id);

if (!$colaborador) {
    $_SESSION['error_message'] = 'Colaborador não encontrado';
    header('Location: listar.php');
    exit;
}

// Configurações da página
$pageTitle = 'Visualizar Colaborador';
$breadcrumb = '<a href="../dashboard.php">Dashboard</a> > <a href="listar.php">Colaboradores</a> > ' . $colaborador['nome'];

// Inclui header
include __DIR__ . '/../../app/views/layouts/header.php';
?>

<style>
    .colaborador-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        border-radius: 10px;
        margin-bottom: 30px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .colaborador-header h2 {
        margin: 0 0 10px 0;
        font-size: 32px;
    }

    .colaborador-header .meta {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
        opacity: 0.95;
    }

    .colaborador-header .meta span {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
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
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
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

    .badge-success {
        background: #d4edda;
        color: #155724;
    }

    .badge-danger {
        background: #f8d7da;
        color: #721c24;
    }

    .badge-info {
        background: #d1ecf1;
        color: #0c5460;
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
        padding: 20px;
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
        font-size: 40px;
        margin-bottom: 10px;
    }

    .stat-card .number {
        font-size: 32px;
        font-weight: 700;
        color: #667eea;
        margin-bottom: 5px;
    }

    .stat-card .label {
        font-size: 14px;
        color: #666;
    }

    .historico-table {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        overflow: hidden;
    }

    .historico-table h3 {
        padding: 20px;
        margin: 0;
        color: #667eea;
        border-bottom: 2px solid #f0f0f0;
    }

    .historico-table table {
        width: 100%;
        border-collapse: collapse;
    }

    .historico-table th,
    .historico-table td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid #f0f0f0;
    }

    .historico-table th {
        background: #f8f9fa;
        font-weight: 600;
        color: #333;
    }

    .historico-table tr:hover {
        background: #f8f9fa;
    }

    .empty-state {
        text-align: center;
        padding: 50px 20px;
        color: #999;
    }

    .empty-state .icon {
        font-size: 60px;
        margin-bottom: 15px;
        opacity: 0.5;
    }

    .btn-group {
        display: flex;
        gap: 10px;
        margin-top: 30px;
    }

    .btn {
        padding: 12px 30px;
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
</style>

<div class="colaborador-header">
    <h2><?php echo e($colaborador['nome']); ?></h2>
    <div class="meta">
        <span>📧 <?php echo e($colaborador['email']); ?></span>
        <?php if ($colaborador['cargo']): ?>
            <span>💼 <?php echo e($colaborador['cargo']); ?></span>
        <?php endif; ?>
        <?php if ($colaborador['departamento_exibicao']): ?>
            <span>🏢 <?php echo e($colaborador['departamento_exibicao']); ?></span>
        <?php endif; ?>
        <span>
            <?php if ($colaborador['ativo']): ?>
                <span class="badge badge-success">✅ Ativo</span>
            <?php else: ?>
                <span class="badge badge-danger">❌ Inativo</span>
            <?php endif; ?>
        </span>
    </div>
</div>

<!-- Estatísticas -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="icon">📚</div>
        <div class="number"><?php echo $colaborador['estatisticas']['total_treinamentos']; ?></div>
        <div class="label">Total de Treinamentos</div>
    </div>

    <div class="stat-card">
        <div class="icon">✅</div>
        <div class="number"><?php echo $colaborador['estatisticas']['concluidos']; ?></div>
        <div class="label">Concluídos</div>
    </div>

    <div class="stat-card">
        <div class="icon">⏱️</div>
        <div class="number"><?php echo number_format($colaborador['estatisticas']['horas_totais'], 1); ?>h</div>
        <div class="label">Horas de Treinamento</div>
    </div>

    <div class="stat-card">
        <div class="icon">⭐</div>
        <div class="number"><?php echo number_format($colaborador['estatisticas']['media_avaliacao'], 1); ?></div>
        <div class="label">Média de Avaliação</div>
    </div>
</div>

<!-- Informações -->
<div class="info-grid">
    <div class="info-card">
        <h3>📋 Dados Pessoais</h3>
        <div class="info-row">
            <span class="info-label">CPF:</span>
            <span class="info-value"><?php echo $colaborador['cpf'] ? e($colaborador['cpf']) : '-'; ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Telefone:</span>
            <span class="info-value"><?php echo $colaborador['telefone'] ? e($colaborador['telefone']) : '-'; ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">E-mail:</span>
            <span class="info-value"><?php echo e($colaborador['email']); ?></span>
        </div>
    </div>

    <div class="info-card">
        <h3>💼 Dados Profissionais</h3>
        <div class="info-row">
            <span class="info-label">Nível Hierárquico:</span>
            <span class="info-value">
                <?php
                $nivelClass = match($colaborador['nivel_hierarquico']) {
                    'Estratégico' => 'badge-danger',
                    'Tático' => 'badge-warning',
                    'Operacional' => 'badge-info',
                    default => 'badge-secondary'
                };
                ?>
                <span class="badge <?php echo $nivelClass; ?>"><?php echo e($colaborador['nivel_hierarquico']); ?></span>
            </span>
        </div>
        <div class="info-row">
            <span class="info-label">Cargo:</span>
            <span class="info-value"><?php echo $colaborador['cargo'] ? e($colaborador['cargo']) : '-'; ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Setor/Departamento:</span>
            <span class="info-value"><?php echo $colaborador['departamento_exibicao'] ? e($colaborador['departamento_exibicao']) : '-'; ?></span>
        </div>
        <?php
        // Exibe Setor quando a coluna existir
        try {
            $pdo = Database::getInstance()->getConnection();
            $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'colaboradores' AND column_name = 'setor'");
            $stmt->execute();
            $hasSetor = ((int)($stmt->fetch()['cnt'] ?? 0)) > 0;
        } catch (Exception $e) { $hasSetor = false; }
        if ($hasSetor): ?>
        <div class="info-row">
            <span class="info-label">Setor:</span>
            <span class="info-value"><?php echo isset($colaborador['setor']) && $colaborador['setor'] !== '' ? e($colaborador['setor']) : '-'; ?></span>
        </div>
        <?php endif; ?>
        <div class="info-row">
            <span class="info-label">Data de Admissão:</span>
            <span class="info-value"><?php echo $colaborador['data_admissao'] ? date('d/m/Y', strtotime($colaborador['data_admissao'])) : '-'; ?></span>
        </div>
    </div>

    <div class="info-card">
        <h3>📊 Informações Adicionais</h3>
        <div class="info-row">
            <span class="info-label">Salário:</span>
            <span class="info-value"><?php echo $colaborador['salario'] ? 'R$ ' . number_format($colaborador['salario'], 2, ',', '.') : '-'; ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Origem:</span>
            <span class="info-value">
                <?php if ($colaborador['origem'] === 'wordpress'): ?>
                    <span class="badge badge-info">WordPress</span>
                <?php else: ?>
                    <span class="badge badge-success">Local</span>
                <?php endif; ?>
            </span>
        </div>
        <div class="info-row">
            <span class="info-label">Cadastrado em:</span>
            <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($colaborador['created_at'])); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Atualizado em:</span>
            <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($colaborador['updated_at'])); ?></span>
        </div>
    </div>
</div>

<?php if ($colaborador['observacoes']): ?>
    <div class="info-card" style="margin-bottom: 30px;">
        <h3>📝 Observações</h3>
        <p style="margin: 0; color: #666; line-height: 1.6;"><?php echo nl2br(e($colaborador['observacoes'])); ?></p>
    </div>
<?php endif; ?>

<!-- Histórico de Treinamentos -->
<div class="historico-table">
    <h3>📚 Histórico de Treinamentos</h3>
    <?php if (empty($colaborador['historico'])): ?>
        <div class="empty-state">
            <div class="icon">📋</div>
            <p>Nenhum treinamento registrado ainda</p>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Treinamento</th>
                    <th>Tipo</th>
                    <th>Período</th>
                    <th>Horas</th>
                    <th>Status</th>
                    <th>Avaliação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($colaborador['historico'] as $item): ?>
                    <tr>
                        <td><?php echo e($item['treinamento_nome']); ?></td>
                        <td>
                            <?php
                            $tipoClass = match($item['tipo']) {
                                'Interno' => 'badge-success',
                                'Externo' => 'badge-info',
                                default => 'badge-secondary'
                            };
                            ?>
                            <span class="badge <?php echo $tipoClass; ?>"><?php echo e($item['tipo']); ?></span>
                        </td>
                        <td>
                            <?php if ($item['data_inicio'] && $item['data_fim']): ?>
                                <?php echo date('d/m/Y', strtotime($item['data_inicio'])); ?> -
                                <?php echo date('d/m/Y', strtotime($item['data_fim'])); ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><?php echo number_format($item['horas_totais'] ?? 0, 1); ?>h</td>
                        <td>
                            <?php
                            $statusClass = match($item['status_participacao']) {
                                'Presente' => 'badge-success',
                                'Ausente' => 'badge-danger',
                                'Parcial' => 'badge-warning',
                                default => 'badge-secondary'
                            };
                            ?>
                            <span class="badge <?php echo $statusClass; ?>"><?php echo e($item['status_participacao']); ?></span>
                        </td>
                        <td>
                            <?php if ($item['nota_avaliacao_reacao']): ?>
                                ⭐ <?php echo number_format($item['nota_avaliacao_reacao'], 1); ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Ações -->
<div class="btn-group">
    <a href="editar.php?id=<?php echo $colaborador['id']; ?>" class="btn btn-primary">
        ✏️ Editar
    </a>
    <?php if ($colaborador['ativo']): ?>
        <a href="actions.php?action=inativar&id=<?php echo $colaborador['id']; ?>"
           class="btn btn-danger"
           onclick="return confirm('Deseja realmente inativar este colaborador?')">
            ❌ Inativar
        </a>
    <?php else: ?>
        <a href="actions.php?action=ativar&id=<?php echo $colaborador['id']; ?>"
           class="btn btn-success"
           onclick="return confirm('Deseja realmente ativar este colaborador?')">
            ✅ Ativar
        </a>
    <?php endif; ?>
    <a href="listar.php" class="btn btn-secondary">
        ⬅️ Voltar
    </a>
</div>

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>
