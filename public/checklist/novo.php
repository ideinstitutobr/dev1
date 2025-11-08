<?php
/**
 * Página: Novo Checklist
 * Formulário para criar uma nova avaliação
 */

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';

Auth::requireLogin();

require_once APP_PATH . 'models/Checklist.php';
require_once APP_PATH . 'models/ModuloAvaliacao.php';
require_once APP_PATH . 'models/Unidade.php';
require_once APP_PATH . 'controllers/ChecklistController.php';

$controller = new ChecklistController();

// Processar criação
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultado = $controller->criar();

    if ($resultado['success']) {
        $_SESSION['success_message'] = $resultado['message'];
        header('Location: editar.php?id=' . $resultado['checklist_id']);
        exit;
    } else {
        $erro = $resultado['message'];
    }
}

// Obter dados para o formulário
$dados = $controller->exibirFormularioNovo();

$pageTitle = 'Nova Avaliação';
include APP_PATH . 'views/layouts/header.php';
?>

<style>
    .form-card {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        max-width: 600px;
        margin: 0 auto;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #333;
    }
    .form-control {
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 14px;
    }
    .form-control:focus {
        outline: none;
        border-color: #667eea;
    }
    .btn {
        padding: 12px 30px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
        font-weight: 600;
        transition: all 0.3s;
    }
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }
</style>

<?php if (isset($erro)): ?>
    <div class="alert alert-error">
        <?php echo htmlspecialchars($erro); ?>
    </div>
<?php endif; ?>

<div class="form-card">
    <form method="POST">
        <div class="form-group">
            <label>Unidade *</label>
            <select name="unidade_id" class="form-control" required>
                <option value="">Selecione uma unidade</option>
                <?php foreach ($dados['unidades'] as $unidade): ?>
                    <option value="<?php echo $unidade['id']; ?>">
                        <?php echo htmlspecialchars($unidade['nome']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Módulo de Avaliação *</label>
            <select name="modulo_id" class="form-control" required>
                <option value="">Selecione um módulo</option>
                <?php foreach ($dados['modulos'] as $modulo): ?>
                    <option value="<?php echo $modulo['id']; ?>">
                        <?php echo htmlspecialchars($modulo['nome']); ?>
                        (<?php echo $modulo['total_perguntas']; ?> perguntas)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Data da Avaliação *</label>
            <input type="date" name="data_avaliacao" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
        </div>

        <div class="form-group">
            <label>Observações Gerais</label>
            <textarea name="observacoes_gerais" class="form-control" rows="4" placeholder="Observações gerais sobre a avaliação..."></textarea>
        </div>

        <div class="form-group" style="text-align: center;">
            <button type="submit" class="btn btn-primary">
                ✅ Criar e Começar Avaliação
            </button>
        </div>
    </form>
</div>

<?php include APP_PATH . 'views/layouts/footer.php'; ?>
