<?php
/**
 * View: Relatório Geral Completo
 * Relatório detalhado para impressão/PDF
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

// Busca dados completos
$stats = $controller->getDashboard();
$relatorio = $controller->getRelatorioGeral();
$departamentos = $controller->getRelatorioDepartamentos();
$niveis = $controller->getRelatorioNiveis();

// Configurações da página
$pageTitle = 'Relatório Geral Completo';
$breadcrumb = '<a href="../dashboard.php">Dashboard</a> > <a href="dashboard.php">Relatórios</a> > Relatório Geral';

// Inclui header
include __DIR__ . '/../../app/views/layouts/header.php';
?>

<style>
    @media print {
        .no-print {
            display: none !important;
        }
        .section-card {
            page-break-inside: avoid;
        }
    }

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
        justify-content: flex-end;
        gap: 10px;
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

    .section-card {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 30px;
    }

    .section-card h3 {
        color: #333;
        font-size: 20px;
        margin: 0 0 20px 0;
        padding-bottom: 15px;
        border-bottom: 2px solid #667eea;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }

    .stat-item {
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
        border-left: 4px solid #667eea;
    }

    .stat-item .label {
        font-size: 12px;
        color: #666;
        margin-bottom: 5px;
    }

    .stat-item .value {
        font-size: 24px;
        font-weight: bold;
        color: #333;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }

    th {
        background: #f8f9fa;
        padding: 12px;
        text-align: left;
        font-weight: 600;
        font-size: 13px;
        border-bottom: 2px solid #e1e8ed;
    }

    td {
        padding: 12px;
        border-bottom: 1px solid #f0f0f0;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
        margin-bottom: 20px;
    }

    .info-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 5px;
    }

    .info-item .icon {
        font-size: 24px;
    }

    .info-item .text {
        flex: 1;
    }

    .info-item .text .label {
        font-size: 12px;
        color: #666;
    }

    .info-item .text .value {
        font-size: 18px;
        font-weight: 600;
        color: #333;
    }
</style>

<!-- Ações -->
<div class="actions-bar no-print">
    <button onclick="window.print()" class="btn btn-success">
        🖨️ Imprimir/PDF
    </button>
    <a href="actions.php?action=exportar&tipo=geral&formato=csv" class="btn btn-success">📥 Exportar CSV</a>
    <a href="actions.php?action=exportar&tipo=geral&formato=xlsx" class="btn btn-success">📊 Exportar Excel</a>
    <a href="actions.php?action=exportar&tipo=geral&formato=pdf" class="btn btn-success">📄 Exportar PDF</a>
    <a href="dashboard.php" class="btn btn-secondary">
        ← Voltar
    </a>
</div>

<!-- Header -->
<div class="header-card">
    <h2>📊 Relatório Geral Completo</h2>
    <p>Sistema de Gestão de Capacitações - Gerado em <?php echo date('d/m/Y H:i'); ?></p>
</div>

<!-- Resumo Executivo -->
<div class="section-card">
    <h3>📋 Resumo Executivo</h3>

    <div class="info-grid">
        <div class="info-item">
            <div class="icon">👥</div>
            <div class="text">
                <div class="label">Colaboradores Ativos</div>
                <div class="value"><?php echo number_format($stats['total_colaboradores'], 0, ',', '.'); ?></div>
            </div>
        </div>

        <div class="info-item">
            <div class="icon">📚</div>
            <div class="text">
                <div class="label">Total de Treinamentos</div>
                <div class="value"><?php echo number_format($stats['total_treinamentos'], 0, ',', '.'); ?></div>
            </div>
        </div>

        <div class="info-item">
            <div class="icon">✅</div>
            <div class="text">
                <div class="label">Treinamentos Executados</div>
                <div class="value"><?php echo number_format($stats['treinamentos_executado'] ?? 0, 0, ',', '.'); ?></div>
            </div>
        </div>

        <div class="info-item">
            <div class="icon">🎯</div>
            <div class="text">
                <div class="label">Total de Participações</div>
                <div class="value"><?php echo number_format($stats['total_participacoes'], 0, ',', '.'); ?></div>
            </div>
        </div>

        <div class="info-item">
            <div class="icon">⏱️</div>
            <div class="text">
                <div class="label">Horas de Capacitação</div>
                <div class="value"><?php echo number_format($stats['total_horas_executadas'], 0, ',', '.'); ?>h</div>
            </div>
        </div>

        <div class="info-item">
            <div class="icon">💰</div>
            <div class="text">
                <div class="label">Investimento Total</div>
                <div class="value">R$ <?php echo number_format($stats['total_investimento'], 2, ',', '.'); ?></div>
            </div>
        </div>

        <div class="info-item">
            <div class="icon">⭐</div>
            <div class="text">
                <div class="label">Avaliação Média</div>
                <div class="value"><?php echo number_format($stats['media_avaliacao_geral'], 1, ',', '.'); ?> / 10</div>
            </div>
        </div>

        <div class="info-item">
            <div class="icon">📝</div>
            <div class="text">
                <div class="label">Taxa de Check-in</div>
                <div class="value">
                    <?php
                    $taxaCheckin = $stats['total_participacoes'] > 0
                        ? ($stats['total_checkins'] / $stats['total_participacoes']) * 100
                        : 0;
                    echo number_format($taxaCheckin, 1);
                    ?>%
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Análise por Departamento -->
<div class="section-card">
    <h3>🏢 Análise por Departamento</h3>
    <table>
        <thead>
            <tr>
                <th>Departamento</th>
                <th style="text-align: center;">Colaboradores</th>
                <th style="text-align: center;">Participações</th>
                <th style="text-align: center;">Horas</th>
                <th style="text-align: right;">Investimento</th>
                <th style="text-align: center;">Avaliação</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($departamentos as $dept): ?>
                <tr>
                    <td><strong><?php echo e($dept['departamento']); ?></strong></td>
                    <td style="text-align: center;"><?php echo $dept['total_colaboradores']; ?></td>
                    <td style="text-align: center;"><?php echo $dept['total_participacoes']; ?></td>
                    <td style="text-align: center;"><?php echo number_format($dept['total_horas'] ?? 0, 0); ?>h</td>
                    <td style="text-align: right;">R$ <?php echo number_format($dept['total_investimento'] ?? 0, 2, ',', '.'); ?></td>
                    <td style="text-align: center;"><?php echo number_format($dept['media_avaliacao'] ?? 0, 1); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Análise por Nível Hierárquico -->
<div class="section-card">
    <h3>📊 Análise por Nível Hierárquico</h3>
    <table>
        <thead>
            <tr>
                <th>Nível</th>
                <th style="text-align: center;">Colaboradores</th>
                <th style="text-align: center;">Participações</th>
                <th style="text-align: center;">Média por Colaborador</th>
                <th style="text-align: center;">Total Horas</th>
                <th style="text-align: center;">Avaliação</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($niveis as $nivel): ?>
                <?php
                $mediaPorColab = $nivel['total_colaboradores'] > 0
                    ? $nivel['total_participacoes'] / $nivel['total_colaboradores']
                    : 0;
                ?>
                <tr>
                    <td><strong><?php echo e($nivel['nivel_hierarquico']); ?></strong></td>
                    <td style="text-align: center;"><?php echo $nivel['total_colaboradores']; ?></td>
                    <td style="text-align: center;"><?php echo $nivel['total_participacoes']; ?></td>
                    <td style="text-align: center;"><?php echo number_format($mediaPorColab, 1); ?></td>
                    <td style="text-align: center;"><?php echo number_format($nivel['total_horas'] ?? 0, 0); ?>h</td>
                    <td style="text-align: center;"><?php echo number_format($nivel['media_avaliacao'] ?? 0, 1); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Treinamentos Mais Realizados -->
<div class="section-card">
    <h3>🏆 Top 10 Treinamentos Mais Realizados</h3>
    <table>
        <thead>
            <tr>
                <th>Posição</th>
                <th>Nome do Treinamento</th>
                <th>Tipo</th>
                <th style="text-align: center;">Participantes</th>
                <th style="text-align: center;">Avaliação</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $posicao = 1;
            foreach ($relatorio['treinamentos_mais_realizados'] as $t):
            ?>
                <tr>
                    <td style="text-align: center;"><strong><?php echo $posicao++; ?>º</strong></td>
                    <td><?php echo e($t['nome']); ?></td>
                    <td><?php echo e($t['tipo']); ?></td>
                    <td style="text-align: center;"><?php echo $t['total_participantes']; ?></td>
                    <td style="text-align: center;">
                        <strong><?php echo number_format($t['media_avaliacao'] ?? 0, 1); ?></strong> / 10
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Colaboradores Mais Capacitados -->
<div class="section-card">
    <h3>🎓 Top 10 Colaboradores Mais Capacitados</h3>
    <table>
        <thead>
            <tr>
                <th>Posição</th>
                <th>Nome</th>
                <th>Cargo</th>
                <th>Departamento</th>
                <th style="text-align: center;">Treinamentos</th>
                <th style="text-align: center;">Horas</th>
                <th style="text-align: center;">Avaliação</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $posicao = 1;
            foreach ($relatorio['colaboradores_mais_capacitados'] as $c):
            ?>
                <tr>
                    <td style="text-align: center;"><strong><?php echo $posicao++; ?>º</strong></td>
                    <td><?php echo e($c['nome']); ?></td>
                    <td><?php echo e($c['cargo'] ?? '-'); ?></td>
                    <td><?php echo e($c['departamento'] ?? '-'); ?></td>
                    <td style="text-align: center;"><?php echo $c['total_treinamentos']; ?></td>
                    <td style="text-align: center;"><?php echo number_format($c['total_horas'] ?? 0, 0); ?>h</td>
                    <td style="text-align: center;">
                        <strong><?php echo number_format($c['media_avaliacao'] ?? 0, 1); ?></strong> / 10
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Rodapé -->
<div class="section-card" style="text-align: center; color: #999; font-size: 12px;">
    <p>
        <strong>Sistema de Gestão de Capacitações (SGC)</strong><br>
        Relatório gerado automaticamente em <?php echo date('d/m/Y \à\s H:i:s'); ?><br>
        Usuário: <?php echo e(Auth::getUserName()); ?>
    </p>
</div>

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>
