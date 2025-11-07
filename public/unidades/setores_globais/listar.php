<?php
/**
 * View: Listar Setores Globais
 * Gerencia o catálogo global de setores disponíveis no sistema
 */

define('SGC_SYSTEM', true);
require_once __DIR__ . '/../../../app/config/config.php';
require_once __DIR__ . '/../../../app/classes/Database.php';
require_once __DIR__ . '/../../../app/classes/Auth.php';

Auth::requireLogin();
Auth::requireAdmin();

$pageTitle = 'Setores Globais';
$breadcrumb = '<a href="../../dashboard.php">Dashboard</a> > <a href="../listar.php">Unidades</a> > Setores Globais';

$db = Database::getInstance();
$pdo = $db->getConnection();

// Processa exclusão
if (isset($_GET['acao']) && $_GET['acao'] === 'excluir' && isset($_GET['id'])) {
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($id) {
        try {
            // Verifica se há unidades usando este setor
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total
                FROM unidade_setores
                WHERE setor COLLATE utf8mb4_unicode_ci = (SELECT valor FROM field_categories WHERE id = ?)
            ");
            $stmt->execute([$id]);
            $uso = $stmt->fetch();

            if ($uso['total'] > 0) {
                $_SESSION['error_message'] = "Não é possível excluir este setor. Existem {$uso['total']} unidade(s) usando-o.";
            } else {
                // Exclui o setor
                $stmt = $pdo->prepare("DELETE FROM field_categories WHERE id = ? AND tipo = 'setor'");
                $stmt->execute([$id]);
                $_SESSION['success_message'] = 'Setor excluído com sucesso!';
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = 'Erro ao excluir setor: ' . $e->getMessage();
        }
    }
    header('Location: listar.php');
    exit;
}

// Busca setores
$search = $_GET['search'] ?? '';
$sql = "SELECT
            fc.id,
            fc.valor as nome,
            fc.descricao,
            fc.created_at,
            (SELECT COUNT(*) FROM unidade_setores us WHERE us.setor COLLATE utf8mb4_unicode_ci = fc.valor AND us.ativo = 1) as unidades_ativas,
            (SELECT COUNT(DISTINCT uc.colaborador_id)
             FROM unidade_colaboradores uc
             INNER JOIN unidade_setores us ON uc.unidade_setor_id = us.id
             WHERE us.setor COLLATE utf8mb4_unicode_ci = fc.valor AND uc.ativo = 1) as colaboradores_vinculados
        FROM field_categories fc
        WHERE fc.tipo = 'setor' AND fc.ativo = 1";

if (!empty($search)) {
    $sql .= " AND fc.valor LIKE :search";
}

$sql .= " ORDER BY fc.valor ASC";

$stmt = $pdo->prepare($sql);
if (!empty($search)) {
    $stmt->bindValue(':search', "%{$search}%");
}
$stmt->execute();
$setores = $stmt->fetchAll();

// Mensagens
$sucesso = $_SESSION['success_message'] ?? '';
$erro = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

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
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    .search-box {
        background: white;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    .search-box form {
        display: flex;
        gap: 10px;
    }
    .search-box input {
        flex: 1;
        padding: 10px 15px;
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
        display: inline-block;
        text-align: center;
        min-width: 60px;
    }
    .badge-info {
        background: #d1ecf1;
        color: #0c5460;
    }
    .badge-warning {
        background: #fff3cd;
        color: #856404;
    }
    .badge-success {
        background: #d4edda;
        color: #155724;
    }
    .actions {
        display: flex;
        gap: 5px;
    }
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #718096;
    }
    .empty-state-icon {
        font-size: 64px;
        margin-bottom: 20px;
    }
    .info-box {
        background: #e7f3ff;
        border: 1px solid #b3d9ff;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        color: #004085;
    }
</style>

<?php if ($sucesso): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($sucesso); ?></div>
        <?php endif; ?>

        <?php if ($erro): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>

        <div class="info-box">
            <strong>ℹ️ O que são Setores Globais?</strong><br>
            Setores Globais são categorias de áreas/departamentos que podem ser ativados nas unidades.
            Exemplos: Vendas, Caixa, Estoque, Administrativo, etc.
            Os colaboradores serão vinculados aos setores ativos da sua unidade.
        </div>

        <div class="page-actions">
            <div>
                <h2 style="margin: 0; color: #2d3748;">Catálogo de Setores</h2>
                <p style="margin: 5px 0 0 0; color: #718096;">Gerencie os setores disponíveis no sistema</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="cadastrar.php" class="btn btn-primary">
                    ➕ Novo Setor
                </a>
                <a href="../listar.php" class="btn btn-secondary">
                    ← Voltar para Unidades
                </a>
            </div>
        </div>

        <!-- Busca -->
        <div class="search-box">
            <form method="GET" action="">
                <input type="text" name="search" placeholder="🔍 Buscar setor por nome..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">Buscar</button>
                <?php if (!empty($search)): ?>
                    <a href="listar.php" class="btn btn-secondary">Limpar</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Tabela -->
        <div class="table-container">
            <?php if (empty($setores)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📦</div>
                    <h3>Nenhum setor encontrado</h3>
                    <p>Comece criando os setores que estarão disponíveis nas unidades.</p>
                    <a href="cadastrar.php" class="btn btn-primary">➕ Criar Primeiro Setor</a>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Nome do Setor</th>
                            <th>Descrição</th>
                            <th style="text-align: center;">Unidades</th>
                            <th style="text-align: center;">Colaboradores</th>
                            <th>Criado em</th>
                            <th style="text-align: center;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($setores as $setor): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($setor['nome']); ?></strong>
                                </td>
                                <td>
                                    <?php if ($setor['descricao']): ?>
                                        <?php echo htmlspecialchars($setor['descricao']); ?>
                                    <?php else: ?>
                                        <span style="color: #999;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($setor['unidades_ativas'] > 0): ?>
                                        <span class="badge badge-success"><?php echo $setor['unidades_ativas']; ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-info">0</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($setor['colaboradores_vinculados'] > 0): ?>
                                        <span class="badge badge-success"><?php echo $setor['colaboradores_vinculados']; ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-info">0</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($setor['created_at'])); ?></td>
                                <td class="actions" style="justify-content: center;">
                                    <a href="editar.php?id=<?php echo $setor['id']; ?>"
                                       class="btn btn-success btn-sm"
                                       title="Editar">✏️</a>
                                    <?php if ($setor['unidades_ativas'] == 0 && $setor['colaboradores_vinculados'] == 0): ?>
                                        <a href="listar.php?acao=excluir&id=<?php echo $setor['id']; ?>"
                                           class="btn btn-danger btn-sm"
                                           title="Excluir"
                                           onclick="return confirm('Deseja realmente excluir este setor?');">🗑️</a>
                                    <?php else: ?>
                                        <button class="btn btn-secondary btn-sm"
                                                title="Não pode ser excluído (em uso)"
                                                disabled>🔒</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <p style="text-align: center; color: #718096; margin-top: 20px;">
                    Total: <?php echo count($setores); ?> setor(es)
                </p>
            <?php endif; ?>
        </div>
    

<?php include __DIR__ . '/../../../app/views/layouts/footer.php'; ?>
