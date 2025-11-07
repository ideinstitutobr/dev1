<?php
/**
 * View: Listar Unidades
 */

define('SGC_SYSTEM', true);

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';
require_once __DIR__ . '/../../app/models/Unidade.php';
require_once __DIR__ . '/../../app/models/UnidadeSetor.php';
require_once __DIR__ . '/../../app/models/CategoriaLocalUnidade.php';
require_once __DIR__ . '/../../app/controllers/UnidadeController.php';
require_once __DIR__ . '/../../app/controllers/CategoriaLocalUnidadeController.php';

$pageTitle = 'Unidades';
$breadcrumb = '<a href="../dashboard.php">Dashboard</a> > Unidades';

$controllerUnidade = new UnidadeController();
$controllerCategoria = new CategoriaLocalUnidadeController();

// Lista unidades
$resultado = $controllerUnidade->listar();
$unidades = $resultado['data'];
$pagination = [
    'total' => $resultado['total'],
    'page' => $resultado['page'],
    'per_page' => $resultado['per_page'],
    'total_pages' => $resultado['total_pages']
];

// Opções para filtros
$categorias = $controllerCategoria->getCategoriasAtivas();
$estados = $controllerUnidade->getEstados();

include __DIR__ . '/../../app/views/layouts/header.php';
?>

<style>
    .page-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 15px;
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
    }
    .btn-primary {
        background: #667eea;
        color: white;
    }
    .btn-primary:hover {
        background: #5568d3;
        transform: translateY(-2px);
    }
    .btn-sm {
        padding: 5px 10px;
        font-size: 13px;
    }
    .btn-success {
        background: #28a745;
        color: white;
    }
    .btn-danger {
        background: #dc3545;
        color: white;
    }
    .search-filters {
        background: white;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    .filter-group {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 15px;
    }
    .filter-group input, .filter-group select {
        padding: 10px;
        border: 2px solid #e1e8ed;
        border-radius: 5px;
        font-size: 14px;
    }
    .table-container {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        overflow-x: auto;
    }
    table {
        width: 100%;
        border-collapse: collapse;
    }
    thead {
        background: #f8f9fa;
    }
    th, td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #e1e8ed;
    }
    th {
        font-weight: 600;
        color: #2d3748;
    }
    tbody tr:hover {
        background: #f8f9fa;
    }
    .badge {
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
    .actions {
        display: flex;
        gap: 5px;
    }
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
        margin-top: 20px;
    }
    .pagination a, .pagination span {
        padding: 8px 12px;
        border: 1px solid #e1e8ed;
        border-radius: 5px;
        text-decoration: none;
        color: #667eea;
    }
    .pagination .active {
        background: #667eea;
        color: white;
    }
    .empty-state {
        text-align: center;
        padding: 40px;
        color: #6c757d;
    }
</style>

<div class="page-actions">
            <div>
                <h2 style="margin: 0; color: #2d3748;">Gerencie as Unidades</h2>
                <p style="margin: 5px 0 0 0; color: #718096;">Visualize e gerencie todas as unidades/lojas da empresa</p>
            </div>
            <div>
                <a href="cadastrar.php" class="btn btn-primary">
                    ➕ Nova Unidade
                </a>
            </div>
        </div>

<!-- Filtros -->
<div class="search-filters">
    <form method="GET" action="">
        <div class="filter-group">
            <input type="text" name="search" placeholder="🔍 Buscar por nome, código ou endereço" value="<?php echo e($_GET['search'] ?? ''); ?>">

            <select name="categoria">
                <option value="">Todas as Categorias</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo ($_GET['categoria'] ?? '') == $cat['id'] ? 'selected' : ''; ?>>
                        <?php echo e($cat['nome']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="estado">
                <option value="">Todos os Estados</option>
                <?php foreach ($estados as $estado): ?>
                    <option value="<?php echo e($estado); ?>" <?php echo ($_GET['estado'] ?? '') == $estado ? 'selected' : ''; ?>>
                        <?php echo e($estado); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="ativo">
                <option value="">Todos os Status</option>
                <option value="1" <?php echo ($_GET['ativo'] ?? '') === '1' ? 'selected' : ''; ?>>Ativas</option>
                <option value="0" <?php echo ($_GET['ativo'] ?? '') === '0' ? 'selected' : ''; ?>>Inativas</option>
            </select>
        </div>
        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn btn-primary btn-sm">🔍 Filtrar</button>
            <a href="listar.php" class="btn btn-secondary btn-sm">🔄 Limpar</a>
        </div>
    </form>
</div>

<!-- Tabela -->
<div class="table-container">
    <?php if (empty($unidades)): ?>
        <div class="empty-state">
            <p style="font-size: 48px; margin: 0;">🏢</p>
            <h3>Nenhuma unidade encontrada</h3>
            <p>Comece cadastrando uma nova unidade.</p>
            <a href="cadastrar.php" class="btn btn-primary">➕ Cadastrar Primeira Unidade</a>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Código</th>
                    <th>Categoria</th>
                    <th>Cidade/Estado</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($unidades as $unidade): ?>
                    <tr>
                        <td>
                            <strong><?php echo e($unidade['nome']); ?></strong>
                        </td>
                        <td><?php echo e($unidade['codigo'] ?? '-'); ?></td>
                        <td><?php echo e($unidade['categoria_nome'] ?? '-'); ?></td>
                        <td>
                            <?php if ($unidade['cidade'] || $unidade['estado']): ?>
                                <?php echo e($unidade['cidade'] ?? ''); ?><?php echo $unidade['cidade'] && $unidade['estado'] ? '/' : ''; ?><?php echo e($unidade['estado'] ?? ''); ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($unidade['ativo']): ?>
                                <span class="badge badge-success">Ativa</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inativa</span>
                            <?php endif; ?>
                        </td>
                        <td class="actions">
                            <a href="visualizar.php?id=<?php echo $unidade['id']; ?>" class="btn btn-primary btn-sm" title="Visualizar">👁️</a>
                            <a href="editar.php?id=<?php echo $unidade['id']; ?>" class="btn btn-success btn-sm" title="Editar">✏️</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Paginação -->
        <?php if ($pagination['total_pages'] > 1): ?>
            <div class="pagination">
                <?php if ($pagination['page'] > 1): ?>
                    <a href="?page=<?php echo $pagination['page'] - 1; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?>">← Anterior</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                    <?php if ($i == $pagination['page']): ?>
                        <span class="active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($pagination['page'] < $pagination['total_pages']): ?>
                    <a href="?page=<?php echo $pagination['page'] + 1; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?>">Próxima →</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <p style="text-align: center; color: #718096; margin-top: 20px;">
            Total: <?php echo $pagination['total']; ?> unidade(s)
        </p>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>
