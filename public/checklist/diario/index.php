<?php
/**
 * Página: Lista de Checklists
 * Exibe todos os checklists com filtros
 */

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';

// Verificar autenticação
Auth::requireLogin();

// Incluir models e controllers necessários
require_once APP_PATH . 'models/Checklist.php';
require_once APP_PATH . 'models/RespostaChecklist.php';
require_once APP_PATH . 'models/ModuloAvaliacao.php';
require_once APP_PATH . 'models/Pergunta.php';
require_once APP_PATH . 'models/Unidade.php';
require_once APP_PATH . 'helpers/PontuacaoHelper.php';
require_once APP_PATH . 'controllers/ChecklistController.php';

// Instanciar controller
$controller = new ChecklistController();

// Adicionar filtro de tipo para diário
$_GET['tipo'] = 'diario';

// Obter dados
$dados = $controller->listar();

// Título da página
$pageTitle = 'Avaliações Diárias';

// Incluir header
include APP_PATH . 'views/layouts/header.php';
?>

<style>
    .page-header {
        margin-bottom: 30px;
    }
    .page-header h1 {
        font-size: 32px;
        color: #333;
        margin-bottom: 10px;
    }
    .stats-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }
    .stat-card .label {
        color: #999;
        font-size: 14px;
        margin-bottom: 5px;
    }
    .stat-card .value {
        font-size: 32px;
        font-weight: bold;
        color: #667eea;
    }
    .filters-card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        margin-bottom: 20px;
    }
    .filters-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        align-items: end;
    }
    .form-group {
        display: flex;
        flex-direction: column;
    }
    .form-group label {
        font-size: 14px;
        color: #666;
        margin-bottom: 5px;
    }
    .form-control {
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 14px;
    }
    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.3s;
    }
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }
    .table-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        overflow: hidden;
    }
    table {
        width: 100%;
        border-collapse: collapse;
    }
    th {
        background: #f8f9fa;
        padding: 15px;
        text-align: left;
        font-weight: 600;
        color: #333;
        border-bottom: 2px solid #e9ecef;
    }
    td {
        padding: 15px;
        border-bottom: 1px solid #e9ecef;
    }
    tr:hover {
        background: #f8f9fa;
    }
    .badge {
        padding: 5px 10px;
        border-radius: 20px;
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
    .badge-secondary {
        background: #e2e3e5;
        color: #383d41;
    }
</style>

<div class="page-header">
        <h1>📋 Checklists de Unidades</h1>
        <p>Gerencie e visualize todas as avaliações realizadas</p>
    </div>

    <!-- Estatísticas -->
    <div class="stats-cards">
        <div class="stat-card">
            <div class="label">Total de Avaliações</div>
            <div class="value"><?php echo $dados['estatisticas']['total_checklists'] ?? 0; ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Média Geral</div>
            <div class="value"><?php echo number_format($dados['estatisticas']['media_percentual'] ?? 0, 1); ?>%</div>
        </div>
        <div class="stat-card">
            <div class="label">Aprovados</div>
            <div class="value" style="color: #28a745;"><?php echo $dados['estatisticas']['total_aprovados'] ?? 0; ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Reprovados</div>
            <div class="value" style="color: #dc3545;"><?php echo $dados['estatisticas']['total_reprovados'] ?? 0; ?></div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="filters-card">
        <form method="GET">
            <div class="filters-row">
                <div class="form-group">
                    <label>Unidade</label>
                    <select name="unidade_id" class="form-control">
                        <option value="">Todas as unidades</option>
                        <?php foreach ($dados['unidades'] as $unidade): ?>
                            <option value="<?php echo $unidade['id']; ?>" <?php echo ($dados['filtros']['unidade_id'] == $unidade['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($unidade['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Data Início</label>
                    <input type="date" name="data_inicio" class="form-control" value="<?php echo $dados['filtros']['data_inicio'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label>Data Fim</label>
                    <input type="date" name="data_fim" class="form-control" value="<?php echo $dados['filtros']['data_fim'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="">Todos</option>
                        <option value="rascunho" <?php echo ($dados['filtros']['status'] == 'rascunho') ? 'selected' : ''; ?>>Rascunho</option>
                        <option value="finalizado" <?php echo ($dados['filtros']['status'] == 'finalizado') ? 'selected' : ''; ?>>Finalizado</option>
                        <option value="revisado" <?php echo ($dados['filtros']['status'] == 'revisado') ? 'selected' : ''; ?>>Revisado</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">🔍 Filtrar</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Tabela de Checklists -->
    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Data</th>
                    <th>Unidade</th>
                    <th>Responsável</th>
                    <th>Pontuação</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($dados['checklists'])): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px;">
                            Nenhum checklist encontrado. <a href="<?php echo BASE_URL; ?>checklist/novo.php">Criar primeiro checklist</a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($dados['checklists'] as $checklist): ?>
                        <tr>
                            <td>#<?php echo $checklist['id']; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($checklist['data_avaliacao'])); ?></td>
                            <td><?php echo htmlspecialchars($checklist['unidade_nome']); ?></td>
                            <td><?php echo htmlspecialchars($checklist['responsavel_nome'] ?? 'N/A'); ?></td>
                            <td>
                                <strong><?php echo number_format($checklist['percentual'], 1); ?>%</strong>
                                <?php if ($checklist['atingiu_meta']): ?>
                                    <span style="color: #28a745;">✅</span>
                                <?php else: ?>
                                    <span style="color: #dc3545;">❌</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $badges = [
                                    'rascunho' => 'badge-secondary',
                                    'finalizado' => 'badge-success',
                                    'revisado' => 'badge-warning'
                                ];
                                $badgeClass = $badges[$checklist['status']] ?? 'badge-secondary';
                                ?>
                                <span class="badge <?php echo $badgeClass; ?>">
                                    <?php echo ucfirst($checklist['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($checklist['status'] == 'rascunho'): ?>
                                    <a href="editar.php?id=<?php echo $checklist['id']; ?>" style="color: #667eea;">✏️ Editar</a>
                                <?php else: ?>
                                    <a href="visualizar.php?id=<?php echo $checklist['id']; ?>" style="color: #667eea;">👁️ Visualizar</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginação -->
    <?php if ($dados['paginacao']['total_paginas'] > 1): ?>
        <div style="margin-top: 20px; text-align: center;">
            Página <?php echo $dados['paginacao']['pagina_atual']; ?> de <?php echo $dados['paginacao']['total_paginas']; ?>
        </div>
    <?php endif; ?>

<?php include APP_PATH . 'views/layouts/footer.php'; ?>
