<?php
/**
 * View: Relatório por Nível Hierárquico
 */

define('SGC_SYSTEM', true);
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';
require_once __DIR__ . '/../../app/models/Relatorio.php';
require_once __DIR__ . '/../../app/controllers/RelatorioController.php';

Auth::requireLogin(BASE_URL);

$controller = new RelatorioController();
$niveis = $controller->getRelatorioNiveis();

$pageTitle = 'Relatório por Nível Hierárquico';
$breadcrumb = '<a href="../dashboard.php">Dashboard</a> > <a href="dashboard.php">Relatórios</a> > Por Nível';

include __DIR__ . '/../../app/views/layouts/header.php';
?>

<style>
    .header-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 10px; margin-bottom: 30px; }
    .header-card h2 { margin: 0 0 10px 0; font-size: 24px; }
    .actions-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .btn { padding: 10px 20px; border-radius: 5px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s; border: none; cursor: pointer; font-size: 14px; }
    .btn-success { background: #28a745; color: white; }
    .btn-success:hover { background: #218838; }
    .btn-secondary { background: #6c757d; color: white; }
    .btn-secondary:hover { background: #5a6268; }
    .table-container { background: white; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); overflow: hidden; }
    table { width: 100%; border-collapse: collapse; }
    thead { background: #f8f9fa; }
    th { padding: 15px; text-align: left; font-weight: 600; color: #333; border-bottom: 2px solid #e1e8ed; font-size: 13px; }
    td { padding: 15px; border-bottom: 1px solid #f0f0f0; }
</style>

<div class="header-card">
    <h2>🧭 Relatório por Nível Hierárquico</h2>
    <p>Visão consolidada por nível (Estratégico, Tático, Operacional)</p>
    </div>

<div class="actions-bar">
    <div>
        <strong><?php echo count($niveis); ?></strong> nível(is)
    </div>
    <div style="display:flex;gap:10px;">
        <a href="actions.php?action=exportar&tipo=niveis&formato=csv" class="btn btn-success">📥 Exportar CSV</a>
        <a href="actions.php?action=exportar&tipo=niveis&formato=xlsx" class="btn btn-success">📊 Exportar Excel</a>
        <a href="actions.php?action=exportar&tipo=niveis&formato=pdf" class="btn btn-success">📄 Exportar PDF</a>
        <a href="dashboard.php" class="btn btn-secondary">← Voltar</a>
    </div>
</div>

<div class="table-container">
    <?php if (empty($niveis)): ?>
        <div style="text-align:center; padding:60px 20px; color:#999;">
            <div style="font-size:80px; margin-bottom:20px; opacity:0.5;">🧭</div>
            <h3>Nenhum dado encontrado</h3>
            <p>Não há dados por nível no momento</p>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Nível</th>
                    <th style="text-align:center;">Colaboradores</th>
                    <th style="text-align:center;">Participações</th>
                    <th style="text-align:center;">Horas</th>
                    <th style="text-align:center;">Avaliação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($niveis as $n): ?>
                    <tr>
                        <td><strong><?php echo e($n['nivel_hierarquico']); ?></strong></td>
                        <td style="text-align:center;"><strong><?php echo $n['total_colaboradores']; ?></strong></td>
                        <td style="text-align:center;"><strong><?php echo $n['total_participacoes']; ?></strong></td>
                        <td style="text-align:center;"><?php echo number_format($n['total_horas'] ?? 0, 2); ?>h</td>
                        <td style="text-align:center;"><strong><?php echo number_format($n['media_avaliacao'] ?? 0, 2); ?></strong> / 10</strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>

