<?php
define('SGC_SYSTEM', true);
require_once __DIR__ . '/../../../app/config/config.php';
require_once __DIR__ . '/../../../app/classes/Database.php';
require_once __DIR__ . '/../../../app/classes/Auth.php';
require_once __DIR__ . '/../../../app/models/CategoriaLocalUnidade.php';
require_once __DIR__ . '/../../../app/controllers/CategoriaLocalUnidadeController.php';

Auth::requireLogin();
Auth::requireAdmin();

$pageTitle = 'Nova Categoria de Local';
$breadcrumb = '<a href="../../dashboard.php">Dashboard</a> > <a href="../listar.php">Unidades</a> > <a href="listar.php">Categorias</a> > Nova';

$controller = new CategoriaLocalUnidadeController();

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultado = $controller->criar($_POST);
    if ($resultado['success']) {
        $_SESSION['success_message'] = $resultado['message'];
        header('Location: listar.php');
        exit;
    } else {
        $erro = $resultado['message'];
    }
}

include __DIR__ . '/../../../app/views/layouts/header.php';
?>

<style>
    .form-container {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        max-width: 700px;
        margin: 0 auto;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #2d3748;
    }
    .form-group input, .form-group textarea {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e1e8ed;
        border-radius: 5px;
        font-size: 14px;
        font-family: inherit;
    }
    .form-group input:focus, .form-group textarea:focus {
        border-color: #667eea;
        outline: none;
    }
    .form-group textarea {
        resize: vertical;
        min-height: 100px;
    }
    .help-text {
        font-size: 13px;
        color: #718096;
        margin-top: 5px;
    }
    .required {
        color: #e53e3e;
        margin-left: 3px;
    }
    .actions {
        display: flex;
        gap: 10px;
        margin-top: 30px;
    }
    .btn {
        padding: 12px 30px;
        border-radius: 5px;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
        cursor: pointer;
        border: none;
        font-size: 14px;
        transition: all 0.3s;
    }
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    .btn:hover {
        opacity: 0.9;
        transform: translateY(-2px);
    }
    .alert {
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    .examples {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
        margin-top: 10px;
    }
    .examples h4 {
        margin: 0 0 10px 0;
        font-size: 14px;
        color: #2d3748;
    }
    .examples ul {
        margin: 0;
        padding-left: 20px;
    }
    .examples li {
        color: #718096;
        font-size: 13px;
        margin-bottom: 5px;
    }
</style>

    <div class="form-container">
        <?php if ($erro): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <?php echo csrf_field(); ?>

            <div class="form-group">
                <label>Nome da Categoria<span class="required">*</span></label>
                <input
                    type="text"
                    name="nome"
                    required
                    maxlength="100"
                    placeholder="Ex: Matriz, Filial, Shopping..."
                    value="<?php echo isset($_POST['nome']) ? e($_POST['nome']) : ''; ?>"
                >
                <div class="help-text">
                    Nome único que identifica o tipo de local da unidade
                </div>
            </div>

            <div class="form-group">
                <label>Descrição</label>
                <textarea
                    name="descricao"
                    rows="4"
                    placeholder="Descreva o tipo de local e suas características..."
                ><?php echo isset($_POST['descricao']) ? e($_POST['descricao']) : ''; ?></textarea>
                <div class="help-text">
                    Informações adicionais sobre este tipo de local (opcional)
                </div>
            </div>

            <div class="examples">
                <h4>💡 Exemplos de Categorias:</h4>
                <ul>
                    <li><strong>Matriz:</strong> Sede principal da empresa</li>
                    <li><strong>Filial:</strong> Unidade filial em outra localidade</li>
                    <li><strong>Shopping:</strong> Loja localizada em shopping center</li>
                    <li><strong>Franquia:</strong> Unidade franqueada</li>
                    <li><strong>Centro de Distribuição:</strong> Unidade focada em logística</li>
                    <li><strong>Outlet:</strong> Loja de produtos com desconto</li>
                </ul>
            </div>

            <div class="form-group" style="margin-top: 20px;">
                <label>
                    <input type="checkbox" name="ativo" value="1" checked>
                    Categoria ativa
                </label>
                <div class="help-text">
                    Categorias ativas ficam disponíveis para seleção ao cadastrar unidades
                </div>
            </div>

            <div class="actions">
                <button type="submit" class="btn btn-primary">💾 Salvar Categoria</button>
                <a href="listar.php" class="btn btn-secondary">❌ Cancelar</a>
            </div>
        </form>
    

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>
