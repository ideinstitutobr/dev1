<?php
/**
 * Página: Dashboard de Relatórios
 * Dashboard completo com gráficos e estatísticas
 */

require_once __DIR__ . '/../../../app/config/config.php';
require_once __DIR__ . '/../../../app/config/database.php';
require_once __DIR__ . '/../../../app/classes/Auth.php';

Auth::requireLogin();

require_once APP_PATH . 'models/Checklist.php';
require_once APP_PATH . 'models/Loja.php';
require_once APP_PATH . 'models/ModuloAvaliacao.php';
require_once APP_PATH . 'services/RelatorioService.php';
require_once APP_PATH . 'helpers/PontuacaoHelper.php';
require_once APP_PATH . 'helpers/RelatorioHelper.php';
require_once APP_PATH . 'controllers/RelatorioChecklistController.php';

$controller = new RelatorioChecklistController();
$dados = $controller->dashboard();

$pageTitle = 'Dashboard - Checklist de Lojas';
include APP_PATH . 'views/layouts/header.php';
include APP_PATH . 'views/layouts/sidebar.php';
?>

<style>
    .main-content {
        margin-left: 260px;
        padding: 30px;
        transition: margin-left 0.3s;
    }
    .main-content.sidebar-collapsed {
        margin-left: 70px;
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
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }
    .stat-card .icon {
        font-size: 40px;
        margin-bottom: 15px;
    }
    .stat-card .value {
        font-size: 36px;
        font-weight: bold;
        color: #667eea;
        margin-bottom: 5px;
    }
    .stat-card .label {
        color: #999;
        font-size: 14px;
    }
    .chart-card {
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        margin-bottom: 20px;
    }
    .chart-card h3 {
        margin-bottom: 20px;
        color: #333;
    }
    .ranking-list {
        list-style: none;
        padding: 0;
    }
    .ranking-item {
        display: flex;
        align-items: center;
        padding: 15px;
        border-bottom: 1px solid #f0f0f0;
    }
    .ranking-item:last-child {
        border-bottom: none;
    }
    .ranking-position {
        font-size: 24px;
        font-weight: bold;
        color: #667eea;
        margin-right: 15px;
        min-width: 40px;
    }
    .ranking-info {
        flex: 1;
    }
    .ranking-name {
        font-weight: 600;
        color: #333;
    }
    .ranking-score {
        text-align: right;
    }
    .score-value {
        font-size: 24px;
        font-weight: bold;
        color: #28a745;
    }
    .filters-card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        margin-bottom: 20px;
    }
</style>

<div class="main-content" id="mainContent">
    <div class="page-header" style="margin-bottom: 30px;">
        <h1>📊 Dashboard - Checklist de Lojas</h1>
        <p>Visão geral de todas as avaliações realizadas</p>
    </div>

    <!-- Filtros -->
    <div class="filters-card">
        <form method="GET" style="display: flex; gap: 15px; align-items: end;">
            <div>
                <label style="display: block; margin-bottom: 5px; font-size: 14px;">Loja</label>
                <select name="loja_id" class="form-control" style="padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                    <option value="">Todas as lojas</option>
                    <?php foreach ($dados['lojas'] as $loja): ?>
                        <option value="<?php echo $loja['id']; ?>" <?php echo ($dados['filtros']['loja_id'] == $loja['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($loja['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label style="display: block; margin-bottom: 5px; font-size: 14px;">Data Início</label>
                <input type="date" name="data_inicio" class="form-control" value="<?php echo $dados['filtros']['data_inicio']; ?>" style="padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
            </div>
            <div>
                <label style="display: block; margin-bottom: 5px; font-size: 14px;">Data Fim</label>
                <input type="date" name="data_fim" class="form-control" value="<?php echo $dados['filtros']['data_fim']; ?>" style="padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
            </div>
            <div>
                <button type="submit" style="padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer;">
                    🔍 Filtrar
                </button>
            </div>
        </form>
    </div>

    <!-- Cards de Estatísticas -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="icon">📋</div>
            <div class="value"><?php echo $dados['estatisticas_gerais']['total_checklists'] ?? 0; ?></div>
            <div class="label">Total de Avaliações</div>
        </div>
        <div class="stat-card">
            <div class="icon">⭐</div>
            <div class="value"><?php echo number_format($dados['estatisticas_gerais']['media_percentual'] ?? 0, 1); ?>%</div>
            <div class="label">Média Geral</div>
        </div>
        <div class="stat-card">
            <div class="icon">✅</div>
            <div class="value" style="color: #28a745;"><?php echo $dados['estatisticas_gerais']['taxa_aprovacao'] ?? 0; ?>%</div>
            <div class="label">Taxa de Aprovação</div>
        </div>
        <div class="stat-card">
            <div class="icon">🏪</div>
            <div class="value"><?php echo $dados['estatisticas_gerais']['total_lojas'] ?? 0; ?></div>
            <div class="label">Lojas Avaliadas</div>
        </div>
    </div>

    <!-- Ranking de Lojas -->
    <div class="chart-card">
        <h3>🏆 Ranking de Lojas</h3>
        <?php if (empty($dados['ranking_lojas'])): ?>
            <p style="text-align: center; padding: 40px; color: #999;">Nenhum dado disponível para o período selecionado</p>
        <?php else: ?>
            <ul class="ranking-list">
                <?php foreach (array_slice($dados['ranking_lojas'], 0, 10) as $index => $loja): ?>
                    <li class="ranking-item">
                        <div class="ranking-position"><?php echo $index + 1; ?>º</div>
                        <div class="ranking-info">
                            <div class="ranking-name"><?php echo htmlspecialchars($loja['nome']); ?></div>
                            <div style="font-size: 12px; color: #999;">
                                <?php echo $loja['cidade'] ?? ''; ?> •
                                <?php echo $loja['total_avaliacoes']; ?> avaliações
                            </div>
                        </div>
                        <div class="ranking-score">
                            <div class="score-value"><?php echo number_format($loja['media_percentual'], 1); ?>%</div>
                            <div style="font-size: 12px; color: #999;">
                                <?php
                                $estrelas = round($loja['media_percentual'] / 20);
                                echo str_repeat('⭐', $estrelas);
                                ?>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <!-- Distribuição de Notas -->
    <?php if (!empty($dados['distribuicao_notas'])): ?>
        <div class="chart-card">
            <h3>📊 Distribuição de Classificações</h3>
            <div style="padding: 20px;">
                <?php foreach ($dados['distribuicao_notas'] as $item): ?>
                    <?php
                    $percentual = $dados['estatisticas_gerais']['total_checklists'] > 0
                        ? ($item['total'] / $dados['estatisticas_gerais']['total_checklists']) * 100
                        : 0;
                    $cores = [
                        'Excelente' => '#28a745',
                        'Bom' => '#007bff',
                        'Regular' => '#ffc107',
                        'Ruim' => '#fd7e14',
                        'Muito Ruim' => '#dc3545'
                    ];
                    $cor = $cores[$item['categoria']] ?? '#999';
                    ?>
                    <div style="margin-bottom: 15px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span><?php echo $item['categoria']; ?></span>
                            <span style="font-weight: bold;"><?php echo $item['total']; ?> (<?php echo number_format($percentual, 1); ?>%)</span>
                        </div>
                        <div style="background: #f0f0f0; height: 20px; border-radius: 10px; overflow: hidden;">
                            <div style="width: <?php echo $percentual; ?>%; height: 100%; background: <?php echo $cor; ?>; transition: width 0.3s;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Desempenho por Setor -->
    <?php if (!empty($dados['desempenho_setores'])): ?>
        <div class="chart-card">
            <h3>📊 Desempenho por Setor</h3>
            <div style="padding: 20px;">
                <?php foreach ($dados['desempenho_setores'] as $setor): ?>
                    <?php
                    $percentual = $setor['media_percentual'] ?? 0;
                    $cor = PontuacaoHelper::obterCorPercentual($percentual);
                    ?>
                    <div style="margin-bottom: 15px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span><?php echo htmlspecialchars($setor['setor']); ?></span>
                            <span style="font-weight: bold;"><?php echo number_format($percentual, 1); ?>%</span>
                        </div>
                        <div style="background: #f0f0f0; height: 20px; border-radius: 10px; overflow: hidden;">
                            <div style="width: <?php echo $percentual; ?>%; height: 100%; background: <?php echo $cor; ?>; transition: width 0.3s;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include APP_PATH . 'views/layouts/footer.php'; ?>
