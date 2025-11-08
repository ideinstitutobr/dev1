<?php
/**
 * Página: Gestão de Perguntas Diárias
 * Lista todas as perguntas de formulários diários
 */

require_once __DIR__ . '/../../../app/config/config.php';
require_once __DIR__ . '/../../../app/classes/Auth.php';
require_once __DIR__ . '/../../../app/classes/Database.php';
require_once __DIR__ . '/../../../app/models/Pergunta.php';
require_once __DIR__ . '/../../../app/models/ModuloAvaliacao.php';

Auth::requireLogin();

$perguntaModel = new Pergunta();
$moduloModel = new ModuloAvaliacao();

$perguntas = $perguntaModel->listarPorTipo('diario', true);
$modulos = $moduloModel->listarAtivos('diario', true);

$pageTitle = 'Gestão de Perguntas Diárias';
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
    }
    .header h1 {
        margin: 0 0 10px 0;
        font-size: 28px;
    }
    .header-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    .btn {
        padding: 12px 24px;
        border-radius: 5px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s;
        display: inline-block;
        border: none;
        cursor: pointer;
    }
    .btn-primary {
        background: #007bff;
        color: white;
    }
    .btn-primary:hover {
        background: #0056b3;
    }
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    .btn-secondary:hover {
        background: #5a6268;
    }
    .btn-sm {
        padding: 6px 12px;
        font-size: 14px;
    }
    .btn-warning {
        background: #ffc107;
        color: #000;
    }
    .btn-warning:hover {
        background: #e0a800;
    }
    .btn-danger {
        background: #dc3545;
        color: white;
    }
    .btn-danger:hover {
        background: #c82333;
    }
    .table-card {
        background: white;
        border-radius: 10px;
        padding: 30px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    table {
        width: 100%;
        border-collapse: collapse;
    }
    th, td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #dee2e6;
    }
    th {
        background: #f8f9fa;
        font-weight: 600;
        color: #495057;
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
        background: #28a745;
        color: white;
    }
    .badge-danger {
        background: #dc3545;
        color: white;
    }
    .alert {
        padding: 15px;
        border-radius: 5px;
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
        <h1>📝 Gestão de Perguntas Diárias</h1>
        <p style="margin: 0; opacity: 0.9;">Gerencie perguntas dos formulários diários</p>
    </div>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">Pergunta excluída com sucesso!</div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger">Erro: <?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <div class="header-actions">
        <div>
            <a href="criar.php" class="btn btn-primary">➕ Nova Pergunta</a>
        </div>
        <div>
            <a href="../../index.php" class="btn btn-secondary">⬅️ Voltar</a>
        </div>
    </div>

    <div class="table-card">
        <?php if (empty($perguntas)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">❓</div>
                <h3>Nenhuma pergunta cadastrada</h3>
                <p>Comece criando sua primeira pergunta para formulários diários</p>
                <a href="criar.php" class="btn btn-primary">➕ Criar Primeira Pergunta</a>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Módulo</th>
                        <th>Pergunta</th>
                        <th>Ordem</th>
                        <th>Obrigatória</th>
                        <th>Permite Foto</th>
                        <th>Status</th>
                        <th style="text-align: center;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($perguntas as $pergunta): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($pergunta['modulo_nome']); ?></strong></td>
                            <td><?php echo htmlspecialchars($pergunta['texto']); ?></td>
                            <td><?php echo $pergunta['ordem']; ?></td>
                            <td>
                                <?php if ($pergunta['obrigatoria']): ?>
                                    <span class="badge badge-success">Sim</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Não</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($pergunta['permite_foto']): ?>
                                    <span class="badge badge-success">Sim</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Não</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($pergunta['ativo']): ?>
                                    <span class="badge badge-success">Ativo</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <a href="editar.php?id=<?php echo $pergunta['id']; ?>" class="btn btn-warning btn-sm">✏️ Editar</a>
                                <a href="excluir.php?id=<?php echo $pergunta['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza que deseja excluir esta pergunta?');">🗑️ Excluir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php include APP_PATH . 'views/layouts/footer.php'; ?>
