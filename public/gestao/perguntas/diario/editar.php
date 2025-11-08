<?php
/**
 * Página: Editar Pergunta Diária
 * Formulário para editar pergunta de avaliação diária
 */

require_once __DIR__ . '/../../../app/config/config.php';
require_once __DIR__ . '/../../../app/classes/Auth.php';
require_once __DIR__ . '/../../../app/classes/Database.php';
require_once __DIR__ . '/../../../app/models/Pergunta.php';
require_once __DIR__ . '/../../../app/models/ModuloAvaliacao.php';

Auth::requireLogin();

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$perguntaModel = new Pergunta();
$moduloModel = new ModuloAvaliacao();

$pergunta = $perguntaModel->buscarPorId($_GET['id']);

if (!$pergunta || $pergunta['tipo'] !== 'diario') {
    header('Location: index.php');
    exit;
}

$modulos = $moduloModel->listarAtivos('diario');

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $dados = [
            'modulo_id' => (int)$_POST['modulo_id'],
            'texto' => $_POST['texto'],
            'descricao' => $_POST['descricao'] ?? null,
            'ordem' => (int)$_POST['ordem'],
            'obrigatoria' => isset($_POST['obrigatoria']) ? 1 : 0,
            'permite_foto' => isset($_POST['permite_foto']) ? 1 : 0,
            'ativo' => isset($_POST['ativo']) ? 1 : 0
        ];

        $perguntaModel->atualizar($_GET['id'], $dados);
        $sucesso = 'Pergunta atualizada com sucesso!';

        // Recarregar dados
        $pergunta = $perguntaModel->buscarPorId($_GET['id']);
    } catch (Exception $e) {
        $erro = 'Erro ao atualizar pergunta: ' . $e->getMessage();
    }
}

$pageTitle = 'Editar Pergunta Diária';
include APP_PATH . 'views/layouts/header.php';
?>

<style>
    .container {
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
    }
    .header {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
    .form-group select,
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
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #28a745;
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
        <h1>✏️ Editar Pergunta Diária</h1>
        <p style="margin: 5px 0 0 0; opacity: 0.9;">Atualize as informações da pergunta</p>
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
                <label>Módulo *</label>
                <select name="modulo_id" required>
                    <?php foreach ($modulos as $modulo): ?>
                        <option value="<?php echo $modulo['id']; ?>" <?php echo $modulo['id'] == $pergunta['modulo_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($modulo['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="help-text">Módulo ao qual esta pergunta pertence</div>
            </div>

            <div class="form-group">
                <label>Texto da Pergunta *</label>
                <textarea name="texto" required><?php echo htmlspecialchars($pergunta['texto']); ?></textarea>
                <div class="help-text">Texto principal da pergunta</div>
            </div>

            <div class="form-group">
                <label>Descrição/Orientação</label>
                <textarea name="descricao"><?php echo htmlspecialchars($pergunta['descricao'] ?? ''); ?></textarea>
                <div class="help-text">Texto opcional para orientar o avaliador</div>
            </div>

            <div class="form-group">
                <label>Ordem de Exibição *</label>
                <input type="number" name="ordem" required min="1" value="<?php echo $pergunta['ordem']; ?>">
                <div class="help-text">Ordem dentro do módulo</div>
            </div>

            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" name="obrigatoria" id="obrigatoria" <?php echo $pergunta['obrigatoria'] ? 'checked' : ''; ?>>
                    <label for="obrigatoria" style="margin: 0;">Pergunta obrigatória</label>
                </div>
                <div class="help-text">Se marcado, o avaliador será obrigado a responder</div>
            </div>

            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" name="permite_foto" id="permite_foto" <?php echo $pergunta['permite_foto'] ? 'checked' : ''; ?>>
                    <label for="permite_foto" style="margin: 0;">Permite anexar foto</label>
                </div>
                <div class="help-text">Se marcado, o avaliador poderá tirar foto</div>
            </div>

            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" name="ativo" id="ativo" <?php echo $pergunta['ativo'] ? 'checked' : ''; ?>>
                    <label for="ativo" style="margin: 0;">Pergunta ativa</label>
                </div>
                <div class="help-text">Apenas perguntas ativas aparecem nos formulários</div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-success">✅ Salvar Alterações</button>
                <a href="index.php" class="btn btn-secondary">❌ Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php include APP_PATH . 'views/layouts/footer.php'; ?>
