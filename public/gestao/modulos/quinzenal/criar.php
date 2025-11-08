<?php
/**
 * Página: Criar Módulo Quinzenal/Mensal
 * Formulário para criar novo módulo de avaliação diária
 */

require_once __DIR__ . '/../../../app/config/config.php';
require_once __DIR__ . '/../../../app/classes/Auth.php';
require_once __DIR__ . '/../../../app/classes/Database.php';
require_once __DIR__ . '/../../../app/models/ModuloAvaliacao.php';

Auth::requireLogin();

$moduloModel = new ModuloAvaliacao();
$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $dados = [
            'nome' => $_POST['nome'],
            'tipo' => 'quinzenal_mensal',
            'descricao' => $_POST['descricao'] ?? null,
            'total_perguntas' => (int)$_POST['total_perguntas'],
            'peso_por_pergunta' => (float)$_POST['peso_por_pergunta'],
            'ordem' => (int)$_POST['ordem'],
            'ativo' => isset($_POST['ativo']) ? 1 : 0
        ];

        $id = $moduloModel->criar($dados);
        $sucesso = 'Módulo criado com sucesso!';
        header('Location: index.php?success=1');
        exit;
    } catch (Exception $e) {
        $erro = 'Erro ao criar módulo: ' . $e->getMessage();
    }
}

$pageTitle = 'Criar Módulo Quinzenal/Mensal';
include APP_PATH . 'views/layouts/header.php';
?>

<style>
    .container {
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
    }
    .header {
        background: linear-gradient(135deg, #007bff 0%, #20c997 100());
        color: white;
        padding: 30px;
        border-radius: 10px;
        margin-bottom: 30px;
    }
    .header h1 {
        margin: 0;
        font-size: 28px;
    }
    .form-card {
        background: white;
        border-radius: 10px;
        padding: 30px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 8px;
        color: #333;
    }
    .form-group input[type="text"],
    .form-group input[type="number"],
    .form-group textarea {
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 14px;
    }
    .form-group textarea {
        resize: vertical;
        min-height: 100px;
    }
    .form-group input:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #007bff;
    }
    .form-group-inline {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    .checkbox-group {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .checkbox-group input[type="checkbox"] {
        width: 20px;
        height: 20px;
        cursor: pointer;
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
        font-size: 16px;
    }
    .btn-success {
        background: #007bff;
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
    .form-actions {
        display: flex;
        gap: 10px;
        margin-top: 30px;
    }
    .alert {
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .help-text {
        font-size: 12px;
        color: #6c757d;
        margin-top: 5px;
    }
</style>

<div class="container">
    <div class="header">
        <h1>➕ Criar Módulo Quinzenal/Mensal</h1>
        <p style="margin: 5px 0 0 0; opacity: 0.9;">Preencha as informações abaixo para criar um novo módulo</p>
    </div>

    <?php if ($erro): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
    <?php endif; ?>

    <?php if ($sucesso): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($sucesso); ?></div>
    <?php endif; ?>

    <div class="form-card">
        <form method="POST">
            <div class="form-group">
                <label>Nome do Módulo *</label>
                <input type="text" name="nome" required placeholder="Ex: Limpeza e Organização">
                <div class="help-text">Nome que identifica o módulo na avaliação</div>
            </div>

            <div class="form-group">
                <label>Descrição</label>
                <textarea name="descricao" placeholder="Descreva o objetivo deste módulo..."></textarea>
                <div class="help-text">Descrição opcional para orientar os avaliadores</div>
            </div>

            <div class="form-group-inline">
                <div class="form-group">
                    <label>Total de Perguntas *</label>
                    <input type="number" name="total_perguntas" required min="1" value="5">
                    <div class="help-text">Quantidade de perguntas deste módulo</div>
                </div>

                <div class="form-group">
                    <label>Peso por Pergunta (%) *</label>
                    <input type="number" name="peso_por_pergunta" required min="0.01" step="0.01" value="20.00">
                    <div class="help-text">Peso de cada pergunta no cálculo final</div>
                </div>
            </div>

            <div class="form-group">
                <label>Ordem de Exibição *</label>
                <input type="number" name="ordem" required min="1" value="1">
                <div class="help-text">Define a ordem em que o módulo aparece no formulário</div>
            </div>

            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" name="ativo" id="ativo" checked>
                    <label for="ativo" style="margin: 0;">Módulo ativo</label>
                </div>
                <div class="help-text">Apenas módulos ativos aparecem nos formulários de avaliação</div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-success">✅ Criar Módulo</button>
                <a href="index.php" class="btn btn-secondary">❌ Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php include APP_PATH . 'views/layouts/footer.php'; ?>
