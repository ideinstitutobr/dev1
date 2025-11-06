<?php
define('SGC_SYSTEM', true);
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';
require_once __DIR__ . '/../../app/models/Unidade.php';
require_once __DIR__ . '/../../app/models/UnidadeSetor.php';
require_once __DIR__ . '/../../app/models/CategoriaLocalUnidade.php';
require_once __DIR__ . '/../../app/controllers/UnidadeController.php';
require_once __DIR__ . '/../../app/controllers/CategoriaLocalUnidadeController.php';

Auth::requireLogin();

$pageTitle = 'Dashboard - Unidades';
$breadcrumb = '<a href="../dashboard.php">Dashboard</a> > <a href="listar.php">Unidades</a> > Dashboard';

$controllerUnidade = new UnidadeController();
$controllerCategoria = new CategoriaLocalUnidadeController();

// Busca estatísticas gerais
$db = Database::getInstance();
$pdo = $db->getConnection();

// Total de unidades
$totalUnidades = $pdo->query("SELECT COUNT(*) FROM unidades WHERE ativo = 1")->fetchColumn();
$totalUnidadesInativas = $pdo->query("SELECT COUNT(*) FROM unidades WHERE ativo = 0")->fetchColumn();

// Total de colaboradores vinculados
$totalColaboradores = $pdo->query("SELECT COUNT(DISTINCT colaborador_id) FROM unidade_colaboradores WHERE ativo = 1")->fetchColumn();

// Total de setores ativos
$totalSetores = $pdo->query("SELECT COUNT(*) FROM unidade_setores WHERE ativo = 1")->fetchColumn();

// Total de posições de liderança
$totalLideranca = $pdo->query("SELECT COUNT(*) FROM unidade_lideranca WHERE ativo = 1")->fetchColumn();

// Unidades por categoria
$unidadesPorCategoria = $pdo->query("
    SELECT c.nome as categoria, COUNT(u.id) as total
    FROM unidades u
    INNER JOIN categorias_local_unidade c ON u.categoria_local_id = c.id
    WHERE u.ativo = 1
    GROUP BY c.id, c.nome
    ORDER BY total DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Unidades por estado
$unidadesPorEstado = $pdo->query("
    SELECT estado, COUNT(*) as total
    FROM unidades
    WHERE ativo = 1 AND estado IS NOT NULL AND estado != ''
    GROUP BY estado
    ORDER BY total DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Top 5 unidades com mais colaboradores
$topUnidadesColaboradores = $pdo->query("
    SELECT u.nome, u.cidade, u.estado, COUNT(DISTINCT uc.colaborador_id) as total_colaboradores
    FROM unidades u
    LEFT JOIN unidade_colaboradores uc ON u.id = uc.unidade_id AND uc.ativo = 1
    WHERE u.ativo = 1
    GROUP BY u.id, u.nome, u.cidade, u.estado
    ORDER BY total_colaboradores DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Unidades sem liderança definida
$unidadesSemLideranca = $pdo->query("
    SELECT u.nome, u.cidade, u.estado
    FROM unidades u
    LEFT JOIN unidade_lideranca ul ON u.id = ul.unidade_id AND ul.ativo = 1
    WHERE u.ativo = 1
    GROUP BY u.id, u.nome, u.cidade, u.estado
    HAVING COUNT(ul.id) = 0
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Setores mais comuns
$setoresMaisComuns = $pdo->query("
    SELECT setor, COUNT(*) as total_unidades
    FROM unidade_setores
    WHERE ativo = 1
    GROUP BY setor
    ORDER BY total_unidades DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../../app/views/layouts/header.php';
?>

<style>
    .dashboard {
        padding: 20px;
    }
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .stat-card {
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        display: flex;
        align-items: center;
        gap: 20px;
        transition: transform 0.3s, box-shadow 0.3s;
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    .stat-icon {
        font-size: 48px;
        opacity: 0.8;
    }
    .stat-content h3 {
        margin: 0;
        font-size: 36px;
        color: #2d3748;
    }
    .stat-content p {
        margin: 5px 0 0 0;
        color: #718096;
        font-size: 14px;
    }
    .chart-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .chart-card {
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    .chart-card h3 {
        margin: 0 0 20px 0;
        color: #2d3748;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .list-item {
        padding: 12px 0;
        border-bottom: 1px solid #e1e8ed;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .list-item:last-child {
        border-bottom: none;
    }
    .list-item-name {
        font-weight: 600;
        color: #2d3748;
    }
    .list-item-detail {
        color: #718096;
        font-size: 13px;
    }
    .badge {
        background: #667eea;
        color: white;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
    }
    .badge-warning {
        background: #f59e0b;
    }
    .empty-state {
        text-align: center;
        padding: 40px;
        color: #718096;
    }
    .progress-bar {
        height: 8px;
        background: #e1e8ed;
        border-radius: 4px;
        overflow: hidden;
        margin-top: 8px;
    }
    .progress-fill {
        height: 100%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 4px;
        transition: width 0.3s;
    }
    .quick-actions {
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 30px;
    }
    .quick-actions h3 {
        margin: 0 0 20px 0;
        color: #2d3748;
    }
    .action-buttons {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    .btn {
        padding: 12px 24px;
        border-radius: 5px;
        text-decoration: none;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
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
</style>

<div class="main-content">
    <div class="page-header">
        <h1>📊 Dashboard de Unidades</h1>
        <p><?php echo $breadcrumb; ?></p>
    </div>

    <div class="dashboard">
        <!-- Ações Rápidas -->
        <div class="quick-actions">
            <h3>⚡ Ações Rápidas</h3>
            <div class="action-buttons">
                <a href="cadastrar.php" class="btn btn-primary">➕ Nova Unidade</a>
                <a href="listar.php" class="btn btn-secondary">📋 Listar Todas</a>
                <a href="categorias_local/listar.php" class="btn btn-secondary">⚙️ Gerenciar Categorias</a>
            </div>
        </div>

        <!-- Cards de Estatísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">🏢</div>
                <div class="stat-content">
                    <h3><?php echo $totalUnidades; ?></h3>
                    <p>Unidades Ativas</p>
                    <?php if ($totalUnidadesInativas > 0): ?>
                        <small style="color: #f59e0b;"><?php echo $totalUnidadesInativas; ?> inativas</small>
                    <?php endif; ?>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-content">
                    <h3><?php echo $totalColaboradores; ?></h3>
                    <p>Colaboradores Vinculados</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🏛️</div>
                <div class="stat-content">
                    <h3><?php echo $totalSetores; ?></h3>
                    <p>Setores Ativos</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">👔</div>
                <div class="stat-content">
                    <h3><?php echo $totalLideranca; ?></h3>
                    <p>Posições de Liderança</p>
                </div>
            </div>
        </div>

        <!-- Gráficos e Listas -->
        <div class="chart-grid">
            <!-- Unidades por Categoria -->
            <div class="chart-card">
                <h3>📊 Unidades por Categoria</h3>
                <?php if (empty($unidadesPorCategoria)): ?>
                    <div class="empty-state">Nenhuma unidade cadastrada</div>
                <?php else: ?>
                    <?php
                    $maxCat = max(array_column($unidadesPorCategoria, 'total'));
                    foreach ($unidadesPorCategoria as $cat):
                        $percent = $maxCat > 0 ? ($cat['total'] / $maxCat) * 100 : 0;
                    ?>
                        <div class="list-item">
                            <div style="flex: 1;">
                                <div class="list-item-name"><?php echo e($cat['categoria']); ?></div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $percent; ?>%"></div>
                                </div>
                            </div>
                            <span class="badge"><?php echo $cat['total']; ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Unidades por Estado -->
            <div class="chart-card">
                <h3>🗺️ Unidades por Estado (Top 10)</h3>
                <?php if (empty($unidadesPorEstado)): ?>
                    <div class="empty-state">Nenhuma unidade com estado definido</div>
                <?php else: ?>
                    <?php
                    $maxEst = max(array_column($unidadesPorEstado, 'total'));
                    foreach ($unidadesPorEstado as $est):
                        $percent = $maxEst > 0 ? ($est['total'] / $maxEst) * 100 : 0;
                    ?>
                        <div class="list-item">
                            <div style="flex: 1;">
                                <div class="list-item-name"><?php echo e($est['estado']); ?></div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $percent; ?>%"></div>
                                </div>
                            </div>
                            <span class="badge"><?php echo $est['total']; ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Top Unidades por Colaboradores -->
            <div class="chart-card">
                <h3>🏆 Top 5 Unidades - Mais Colaboradores</h3>
                <?php if (empty($topUnidadesColaboradores)): ?>
                    <div class="empty-state">Nenhum colaborador vinculado</div>
                <?php else: ?>
                    <?php foreach ($topUnidadesColaboradores as $top): ?>
                        <div class="list-item">
                            <div>
                                <div class="list-item-name"><?php echo e($top['nome']); ?></div>
                                <div class="list-item-detail">
                                    <?php echo e($top['cidade']); ?> - <?php echo e($top['estado']); ?>
                                </div>
                            </div>
                            <span class="badge"><?php echo $top['total_colaboradores']; ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Setores Mais Comuns -->
            <div class="chart-card">
                <h3>🏛️ Setores Mais Comuns</h3>
                <?php if (empty($setoresMaisComuns)): ?>
                    <div class="empty-state">Nenhum setor cadastrado</div>
                <?php else: ?>
                    <?php
                    $maxSetor = max(array_column($setoresMaisComuns, 'total_unidades'));
                    foreach ($setoresMaisComuns as $setor):
                        $percent = $maxSetor > 0 ? ($setor['total_unidades'] / $maxSetor) * 100 : 0;
                    ?>
                        <div class="list-item">
                            <div style="flex: 1;">
                                <div class="list-item-name"><?php echo e($setor['setor']); ?></div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $percent; ?>%"></div>
                                </div>
                            </div>
                            <span class="badge"><?php echo $setor['total_unidades']; ?> unidades</span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Alertas e Pendências -->
        <?php if (!empty($unidadesSemLideranca)): ?>
        <div class="chart-card">
            <h3>⚠️ Unidades sem Liderança Definida</h3>
            <p style="color: #718096; font-size: 14px; margin-bottom: 15px;">
                As seguintes unidades não possuem nenhum cargo de liderança atribuído:
            </p>
            <?php foreach ($unidadesSemLideranca as $unid): ?>
                <div class="list-item">
                    <div>
                        <div class="list-item-name"><?php echo e($unid['nome']); ?></div>
                        <div class="list-item-detail">
                            <?php echo e($unid['cidade']); ?> - <?php echo e($unid['estado']); ?>
                        </div>
                    </div>
                    <span class="badge badge-warning">Sem liderança</span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>
