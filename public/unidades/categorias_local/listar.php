<?php
define('SGC_SYSTEM', true);
require_once __DIR__ . '/../../../app/config/config.php';
require_once __DIR__ . '/../../../app/classes/Database.php';
require_once __DIR__ . '/../../../app/classes/Auth.php';
require_once __DIR__ . '/../../../app/models/CategoriaLocalUnidade.php';
require_once __DIR__ . '/../../../app/controllers/CategoriaLocalUnidadeController.php';

Auth::requireLogin();
Auth::requireAdmin(); // Apenas admins podem gerenciar categorias

$pageTitle = 'Categorias de Local';
$breadcrumb = '<a href="../../dashboard.php">Dashboard</a> > <a href="../listar.php">Unidades</a> > Categorias de Local';

$controller = new CategoriaLocalUnidadeController();

// Processa ações
$sucesso = $_SESSION['success_message'] ?? '';
$erro = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Ações de ativar/inativar
if (isset($_GET['acao']) && isset($_GET['id'])) {
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($id) {
        if ($_GET['acao'] === 'inativar') {
            $resultado = $controller->inativar($id);
        } elseif ($_GET['acao'] === 'ativar') {
            $resultado = $controller->ativar($id);
        }

        if (isset($resultado)) {
            if ($resultado['success']) {
                $_SESSION['success_message'] = $resultado['message'];
            } else {
                $_SESSION['error_message'] = $resultado['message'];
            }
            header('Location: listar.php');
            exit;
        }
    }
}

// Busca categorias
$params = [
    'search' => $_GET['search'] ?? '',
    'ativo' => $_GET['ativo'] ?? ''
];
$resultado = $controller->listar($params);
$categorias = $resultado['data'] ?? [];

include __DIR__ . '/../../../app/views/layouts/header.php';
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
    .search-form {
        display: flex;
        gap: 10px;
        flex: 1;
        max-width: 600px;
    }
    .search-form input, .search-form select {
        padding: 10px 15px;
        border: 2px solid #e1e8ed;
        border-radius: 5px;
        font-size: 14px;
    }
    .search-form input {
        flex: 1;
    }
    .search-form button {
        padding: 10px 20px;
        background: #667eea;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: 600;
    }
    .btn {
        padding: 12px 24px;
        border-radius: 5px;
        text-decoration: none;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        border: none;
        transition: all 0.3s;
    }
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .btn:hover {
        opacity: 0.9;
        transform: translateY(-2px);
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
    th {
        background: #f8f9fa;
        padding: 15px;
        text-align: left;
        font-weight: 600;
        color: #2d3748;
        border-bottom: 2px solid #e1e8ed;
    }
    td {
        padding: 15px;
        border-bottom: 1px solid #e1e8ed;
        color: #2d3748;
    }
    tr:hover {
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
    .action-buttons {
        display: flex;
        gap: 8px;
    }
    .action-btn {
        padding: 6px 12px;
        border-radius: 4px;
        text-decoration: none;
        font-size: 12px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: all 0.3s;
    }
    .action-btn-edit {
        background: #667eea;
        color: white;
    }
    .action-btn-toggle {
        background: #6c757d;
        color: white;
    }
    .action-btn:hover {
        opacity: 0.8;
    }
    .alert {
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    .empty-state {
        padding: 60px 20px;
        text-align: center;
        color: #718096;
    }
    .empty-state-icon {
        font-size: 64px;
        margin-bottom: 20px;
    }
    .usage-count {
        font-size: 12px;
        color: #718096;
        margin-top: 5px;
    }
</style>

    <?php if ($sucesso): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($sucesso); ?></div>
    <?php endif; ?>

    <?php if ($erro): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
    <?php endif; ?>

    <div class="page-actions">
        <form method="GET" class="search-form">
            <input
                type="text"
                name="search"
                placeholder="Buscar categoria..."
                value="<?php echo htmlspecialchars($params['search']); ?>"
            >
            <select name="ativo">
                <option value="">Todos os Status</option>
                <option value="1" <?php echo $params['ativo'] === '1' ? 'selected' : ''; ?>>Ativos</option>
                <option value="0" <?php echo $params['ativo'] === '0' ? 'selected' : ''; ?>>Inativos</option>
            </select>
            <button type="submit">🔍 Buscar</button>
        </form>

        <a href="cadastrar.php" class="btn btn-primary">➕ Nova Categoria</a>
    </div>

    <div class="table-container">
        <?php if (empty($categorias)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📁</div>
                <h3>Nenhuma categoria encontrada</h3>
                <p>Crie sua primeira categoria de local para começar.</p>
                <br>
                <a href="cadastrar.php" class="btn btn-primary">➕ Criar Categoria</a>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Descrição</th>
                        <th>Status</th>
                        <th>Unidades</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categorias as $cat): ?>
                        <tr>
                            <td>
                                <strong><?php echo e($cat['nome']); ?></strong>
                                <div class="usage-count">
                                    Criado em <?php echo date('d/m/Y', strtotime($cat['created_at'])); ?>
                                </div>
                            </td>
                            <td><?php echo e($cat['descricao']); ?></td>
                            <td>
                                <?php if ($cat['ativo']): ?>
                                    <span class="badge badge-success">✓ Ativo</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">✗ Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $unidadesVinculadas = $controller->contarUnidadesVinculadas($cat['id']);
                                echo $unidadesVinculadas;
                                ?>
                                <?php echo $unidadesVinculadas == 1 ? 'unidade' : 'unidades'; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="editar.php?id=<?php echo $cat['id']; ?>" class="action-btn action-btn-edit">
                                        ✏️ Editar
                                    </a>
                                    <?php if ($cat['ativo']): ?>
                                        <a
                                            href="listar.php?acao=inativar&id=<?php echo $cat['id']; ?>"
                                            class="action-btn action-btn-toggle"
                                            onclick="return confirm('Deseja realmente inativar esta categoria?');"
                                        >
                                            🔒 Inativar
                                        </a>
                                    <?php else: ?>
                                        <a
                                            href="listar.php?acao=ativar&id=<?php echo $cat['id']; ?>"
                                            class="action-btn action-btn-toggle"
                                        >
                                            ✅ Ativar
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>
