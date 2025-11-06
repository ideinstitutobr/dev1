<?php
/**
 * View: Matriz de Capacitações
 * Mostra quais colaboradores fizeram quais treinamentos
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

// Busca departamentos para filtro
$db = Database::getInstance();
$pdo = $db->getConnection();
$stmt = $pdo->query("
    SELECT DISTINCT departamento
    FROM colaboradores
    WHERE ativo = 1 AND departamento IS NOT NULL AND departamento != ''
    ORDER BY departamento
");
$departamentos = $stmt->fetchAll();

// Filtro
$departamentoFiltro = $_GET['departamento'] ?? null;

// Busca dados
$matriz = $controller->getMatrizCapacitacoes($departamentoFiltro);

// Configurações da página
$pageTitle = 'Matriz de Capacitações';
$breadcrumb = '<a href="../dashboard.php">Dashboard</a> > <a href="dashboard.php">Relatórios</a> > Matriz de Capacitações';

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

    .filter-card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 20px;
    }

    .filter-row {
        display: flex;
        gap: 15px;
        align-items: end;
    }

    .filter-group {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .filter-group label {
        font-size: 12px;
        font-weight: 600;
        color: #666;
    }

    .filter-group select {
        padding: 10px;
        border: 2px solid #e1e8ed;
        border-radius: 5px;
        font-size: 14px;
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

    .btn-primary {
        background: #667eea;
        color: white;
    }

    .btn-primary:hover {
        background: #5568d3;
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
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        min-width: 900px;
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
        position: sticky;
        top: 0;
        background: #f8f9fa;
        z-index: 10;
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
        padding: 4px 10px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: 600;
        background: #667eea;
        color: white;
    }

    .treinamentos-lista {
        max-height: 100px;
        overflow-y: auto;
        padding-right: 10px;
    }

    .treinamentos-lista::-webkit-scrollbar {
        width: 6px;
    }

    .treinamentos-lista::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    .treinamentos-lista::-webkit-scrollbar-thumb {
        background: #667eea;
        border-radius: 10px;
    }

    .treinamento-item {
        padding: 5px 0;
        border-bottom: 1px solid #f0f0f0;
        font-size: 13px;
    }

    .treinamento-item:last-child {
        border-bottom: none;
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
    <h2>📋 Matriz de Capacitações</h2>
    <p>Visão completa de quem participou de quais treinamentos</p>
</div>

<!-- Filtros -->
<div class="filter-card">
    <form method="GET" action="">
        <div class="filter-row">
            <div class="filter-group">
                <label>🏢 Departamento</label>
                <select name="departamento">
                    <option value="">Todos os Departamentos</option>
                    <?php foreach ($departamentos as $d): ?>
                        <option value="<?php echo e($d['departamento']); ?>"
                                <?php echo ($departamentoFiltro === $d['departamento']) ? 'selected' : ''; ?>>
                            <?php echo e($d['departamento']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Filtrar</button>
        </div>
    </form>
</div>

<!-- Ações -->
<div class="actions-bar">
    <div>
        <strong><?php echo count($matriz); ?></strong> colaborador(es) encontrado(s)
        <?php if ($departamentoFiltro): ?>
            em <strong><?php echo e($departamentoFiltro); ?></strong>
        <?php endif; ?>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="actions.php?action=exportar&tipo=matriz&formato=csv<?php echo $departamentoFiltro ? '&departamento=' . urlencode($departamentoFiltro) : ''; ?>" class="btn btn-success">📥 Exportar CSV</a>
        <a href="actions.php?action=exportar&tipo=matriz&formato=xlsx<?php echo $departamentoFiltro ? '&departamento=' . urlencode($departamentoFiltro) : ''; ?>" class="btn btn-success">📊 Exportar Excel</a>
        <a href="actions.php?action=exportar&tipo=matriz&formato=pdf<?php echo $departamentoFiltro ? '&departamento=' . urlencode($departamentoFiltro) : ''; ?>" class="btn btn-success">📄 Exportar PDF</a>
        <a href="dashboard.php" class="btn btn-secondary">
            ← Voltar
        </a>
    </div>
</div>

<!-- Tabela -->
<div class="table-container">
    <?php if (empty($matriz)): ?>
        <div class="empty-state">
            <div class="icon">📋</div>
            <h3>Nenhum dado encontrado</h3>
            <p>Não há dados de capacitação para exibir</p>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Colaborador</th>
                    <th>Cargo</th>
                    <th>Departamento</th>
                    <th style="text-align: center;">Treinamentos</th>
                    <th style="text-align: center;">Horas</th>
                    <th style="width: 35%;">Treinamentos Realizados</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($matriz as $colab): ?>
                    <tr>
                        <td><strong><?php echo e($colab['colaborador_nome']); ?></strong></td>
                        <td><?php echo e($colab['cargo'] ?? '-'); ?></td>
                        <td><?php echo e($colab['departamento'] ?? '-'); ?></td>
                        <td style="text-align: center;">
                            <span class="badge"><?php echo $colab['total_treinamentos']; ?></span>
                        </td>
                        <td style="text-align: center;">
                            <span class="highlight"><?php echo number_format($colab['total_horas'] ?? 0, 0); ?>h</span>
                        </td>
                        <td>
                            <?php if ($colab['treinamentos']): ?>
                                <div class="treinamentos-lista">
                                    <?php
                                    $treinamentos = explode('|', $colab['treinamentos']);
                                    foreach ($treinamentos as $treinamento):
                                    ?>
                                        <div class="treinamento-item">
                                            ✓ <?php echo e($treinamento); ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <span style="color: #999;">Nenhum treinamento realizado</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>
