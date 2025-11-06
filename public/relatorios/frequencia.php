<?php
/**
 * View: Relatório de Frequência (Taxa de Presença)
 */

define('SGC_SYSTEM', true);
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';
require_once __DIR__ . '/../../app/models/Relatorio.php';
require_once __DIR__ . '/../../app/controllers/RelatorioController.php';

Auth::requireLogin(BASE_URL);

$controller = new RelatorioController();
$frequencia = $controller->getRelatorioFrequencia();

$pageTitle = 'Relatório de Frequência';
$breadcrumb = '<a href="../dashboard.php">Dashboard</a> > <a href="dashboard.php">Relatórios</a> > Frequência';

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
    <h2>⏱️ Relatório de Frequência</h2>
    <p>Taxa de presença por treinamento executado</p>
</div>

<div class="actions-bar">
    <div>
        <strong><?php echo count($frequencia); ?></strong> treinamento(s)
    </div>
    <div style="display:flex;gap:10px;">
        <a href="actions.php?action=exportar&tipo=frequencia&formato=csv" class="btn btn-success">📥 Exportar CSV</a>
        <a href="actions.php?action=exportar&tipo=frequencia&formato=xlsx" class="btn btn-success">📊 Exportar Excel</a>
        <a href="actions.php?action=exportar&tipo=frequencia&formato=pdf" class="btn btn-success">📄 Exportar PDF</a>
        <a href="dashboard.php" class="btn btn-secondary">← Voltar</a>
    </div>
</div>

<div class="table-container">
    <?php if (empty($frequencia)): ?>
        <div style="text-align:center; padding:60px 20px; color:#999;">
            <div style="font-size:80px; margin-bottom:20px; opacity:0.5;">⏱️</div>
            <h3>Nenhum dado encontrado</h3>
            <p>Não há dados de frequência no momento</p>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Treinamento</th>
                    <th>Data Início</th>
                    <th style="text-align:center;">Participantes</th>
                    <th style="text-align:center;">Presentes</th>
                    <th style="text-align:center;">Taxa Presença</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($frequencia as $row): ?>
                    <tr>
                        <td><strong><?php echo e($row['nome']); ?></strong></td>
                        <td><?php echo date('d/m/Y', strtotime($row['data_inicio'])); ?></td>
                        <td style="text-align:center;"><strong><?php echo (int)$row['total_participantes']; ?></strong></td>
                        <td style="text-align:center;"><strong><?php echo (int)$row['presentes']; ?></strong></td>
                        <td style="text-align:center;"><span style="background:#fff3cd; padding:2px 6px; border-radius:3px; font-weight:600;"><?php echo number_format($row['taxa_presenca'] ?? 0, 2); ?>%</span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>

