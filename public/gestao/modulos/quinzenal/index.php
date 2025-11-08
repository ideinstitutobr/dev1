<?php
/**
 * Página: Gestão de Módulos Quinzenais/Mensais
 * Listagem e gerenciamento dos módulos de avaliação quinzenal e mensal
 */

require_once __DIR__ . '/../../../app/config/config.php';
require_once __DIR__ . '/../../../app/classes/Auth.php';
require_once __DIR__ . '/../../../app/classes/Database.php';
require_once __DIR__ . '/../../../app/models/ModuloAvaliacao.php';

Auth::requireLogin();

$moduloModel = new ModuloAvaliacao();

// Buscar apenas módulos quinzenais/mensais
$modulos = $moduloModel->listarAtivos('quinzenal_mensal', true); // true = incluir inativos

$pageTitle = 'Gestão de Módulos Quinzenais/Mensais';
include APP_PATH . 'views/layouts/header.php';
?>

<style>
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    .header {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        color: white;
        padding: 30px;
        border-radius: 10px;
        margin-bottom: 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .header h1 {
        margin: 0;
        font-size: 28px;
    }
    .btn {
        padding: 12px 24px;
        border-radius: 5px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
        display: inline-block;
    }
    .btn-primary {
        background: white;
        color: #007bff;
    }
    .btn-primary:hover {
        background: #f8f9fa;
    }
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    .btn-secondary:hover {
        background: #5a6268;
    }
    .btn-success {
        background: #28a745;
        color: white;
    }
    .btn-success:hover {
        background: #218838;
    }
    .btn-danger {
        background: #dc3545;
        color: white;
    }
    .btn-danger:hover {
        background: #c82333;
    }
    .btn-sm {
        padding: 6px 12px;
        font-size: 14px;
    }
    .table-container {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    table {
        width: 100%;
        border-collapse: collapse;
    }
    thead {
        background: #f8f9fa;
    }
    th {
        padding: 12px;
        text-align: left;
        font-weight: 600;
        border-bottom: 2px solid #dee2e6;
    }
    td {
        padding: 12px;
        border-bottom: 1px solid #dee2e6;
    }
    tr:hover {
        background: #f8f9fa;
    }
    .badge {
        padding: 4px 8px;
        border-radius: 4px;
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
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #6c757d;
    }
    .empty-state-icon {
        font-size: 64px;
        margin-bottom: 20px;
    }
</style>

<div class="container">
    <div class="header">
        <div>
            <h1>📦 Gestão de Módulos Quinzenais/Mensais</h1>
            <p style="margin: 5px 0 0 0; opacity: 0.9;">Gerencie os módulos de avaliação quinzenal e mensal</p>
        </div>
        <div>
            <a href="criar.php" class="btn btn-primary">➕ Novo Módulo</a>
            <a href="../../index.php" class="btn btn-secondary">⬅️ Voltar</a>
        </div>
    </div>

    <div class="table-container">
        <?php if (empty($modulos)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📦</div>
                <h3>Nenhum módulo cadastrado</h3>
                <p>Comece criando seu primeiro módulo de avaliação quinzenal/mensal</p>
                <a href="criar.php" class="btn btn-success">➕ Criar Primeiro Módulo</a>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Descrição</th>
                        <th>Total Perguntas</th>
                        <th>Peso</th>
                        <th>Ordem</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($modulos as $modulo): ?>
                        <tr>
                            <td><?php echo $modulo['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($modulo['nome']); ?></strong></td>
                            <td><?php echo htmlspecialchars($modulo['descricao'] ?? '-'); ?></td>
                            <td><?php echo $modulo['total_perguntas']; ?></td>
                            <td><?php echo number_format($modulo['peso_por_pergunta'], 2); ?>%</td>
                            <td><?php echo $modulo['ordem']; ?></td>
                            <td>
                                <?php if ($modulo['ativo']): ?>
                                    <span class="badge badge-success">Ativo</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="actions">
                                    <a href="editar.php?id=<?php echo $modulo['id']; ?>" class="btn btn-secondary btn-sm">✏️ Editar</a>
                                    <a href="../../perguntas/quinzenal/index.php?modulo_id=<?php echo $modulo['id']; ?>" class="btn btn-success btn-sm">❓ Perguntas</a>
                                    <button onclick="confirmarExclusao(<?php echo $modulo['id']; ?>)" class="btn btn-danger btn-sm">🗑️</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
function confirmarExclusao(id) {
    if (confirm('Tem certeza que deseja excluir este módulo?\n\nAtenção: Todas as perguntas associadas também serão excluídas!')) {
        window.location.href = 'excluir.php?id=' + id;
    }
}
</script>

<?php include APP_PATH . 'views/layouts/footer.php'; ?>
