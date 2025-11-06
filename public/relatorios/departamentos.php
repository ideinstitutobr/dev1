<?php
/**
 * View: Relatório por Departamento
 */

// Define constante do sistema
define('SGC_SYSTEM', true);

// Carrega configurações e classes
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';
require_once __DIR__ . '/../../app/models/Relatorio.php';
require_once __DIR__ . '/../../app/controllers/RelatorioController.php';

// Verifica autenticação
Auth::requireLogin(BASE_URL);

// Instancia controller
$controller = new RelatorioController();

// Busca dados
$departamentos = $controller->getRelatorioDepartamentos();

// Configurações da página
$pageTitle = 'Relatório por Departamento';
$breadcrumb = '<a href="../dashboard.php">Dashboard</a> > <a href="dashboard.php">Relatórios</a> > Por Departamento';

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

    .progress-bar {
        background: #e1e8ed;
        height: 8px;
        border-radius: 4px;
        overflow: hidden;
        margin-top: 5px;
    }

    .progress-fill {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        height: 100%;
        transition: width 0.3s;
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

    .highlight {
        background: #fff3cd;
        padding: 2px 6px;
        border-radius: 3px;
        font-weight: 600;
    }
</style>

<!-- Header -->
<div class="header-card">
    <h2>🏢 Relatório por Departamento</h2>
    <p>Análise de capacitação por departamento da organização</p>
</div>

<!-- Ações -->
<div class="actions-bar">
    <div>
        <strong><?php echo count($departamentos); ?></strong> departamento(s) encontrado(s)
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="actions.php?action=exportar&tipo=departamentos&formato=csv" class="btn btn-success">📥 Exportar CSV</a>
        <a href="actions.php?action=exportar&tipo=departamentos&formato=xlsx" class="btn btn-success">📊 Exportar Excel</a>
        <a href="actions.php?action=exportar&tipo=departamentos&formato=pdf" class="btn btn-success">📄 Exportar PDF</a>
        <a href="dashboard.php" class="btn btn-secondary">
            ← Voltar
        </a>
    </div>
</div>

<!-- Tabela -->
<div class="table-container">
    <?php if (empty($departamentos)): ?>
        <div class="empty-state">
            <div class="icon">🏢</div>
            <h3>Nenhum departamento encontrado</h3>
            <p>Não há dados de capacitação por departamento no momento</p>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Departamento</th>
                    <th style="text-align: center;">Colaboradores</th>
                    <th style="text-align: center;">Participações</th>
                    <th style="text-align: center;">Média por Colab.</th>
                    <th style="text-align: center;">Total Horas</th>
                    <th style="text-align: right;">Investimento</th>
                    <th style="text-align: center;">Avaliação</th>
                    <th style="width: 25%;">Performance</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $totalColaboradores = array_sum(array_column($departamentos, 'total_colaboradores'));
                foreach ($departamentos as $dept):
                    $mediaPorColab = $dept['total_colaboradores'] > 0
                        ? $dept['total_participacoes'] / $dept['total_colaboradores']
                        : 0;
                    $percentualColab = $totalColaboradores > 0
                        ? ($dept['total_colaboradores'] / $totalColaboradores) * 100
                        : 0;
                ?>
                    <tr>
                        <td>
                            <strong><?php echo e($dept['departamento']); ?></strong><br>
                            <small style="color: #999;">
                                <?php echo number_format($percentualColab, 1); ?>% dos colaboradores
                            </small>
                        </td>
                        <td style="text-align: center;">
                            <strong><?php echo $dept['total_colaboradores']; ?></strong>
                        </td>
                        <td style="text-align: center;">
                            <strong><?php echo $dept['total_participacoes']; ?></strong>
                        </td>
                        <td style="text-align: center;">
                            <span class="highlight"><?php echo number_format($mediaPorColab, 1); ?></span>
                        </td>
                        <td style="text-align: center;">
                            <?php echo number_format($dept['total_horas'] ?? 0, 0); ?>h
                        </td>
                        <td style="text-align: right;">
                            R$ <?php echo number_format($dept['total_investimento'] ?? 0, 2, ',', '.'); ?>
                        </td>
                        <td style="text-align: center;">
                            <strong><?php echo number_format($dept['media_avaliacao'] ?? 0, 1); ?></strong> / 10
                        </td>
                        <td>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo ($dept['media_avaliacao'] ?? 0) * 10; ?>%"></div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background: #f8f9fa; font-weight: 600;">
                    <td>TOTAL</td>
                    <td style="text-align: center;">
                        <?php echo array_sum(array_column($departamentos, 'total_colaboradores')); ?>
                    </td>
                    <td style="text-align: center;">
                        <?php echo array_sum(array_column($departamentos, 'total_participacoes')); ?>
                    </td>
                    <td style="text-align: center;">-</td>
                    <td style="text-align: center;">
                        <?php echo number_format(array_sum(array_column($departamentos, 'total_horas')), 0); ?>h
                    </td>
                    <td style="text-align: right;">
                        R$ <?php echo number_format(array_sum(array_column($departamentos, 'total_investimento')), 2, ',', '.'); ?>
                    </td>
                    <td colspan="2" style="text-align: center;">-</td>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>
