<?php
define('SGC_SYSTEM', true);
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';
require_once __DIR__ . '/../../app/models/Unidade.php';
require_once __DIR__ . '/../../app/models/CategoriaLocalUnidade.php';
require_once __DIR__ . '/../../app/models/UnidadeSetor.php';
require_once __DIR__ . '/../../app/controllers/UnidadeController.php';
require_once __DIR__ . '/../../app/controllers/CategoriaLocalUnidadeController.php';
require_once __DIR__ . '/../../app/controllers/UnidadeSetorController.php';

$pageTitle = 'Nova Unidade';
$breadcrumb = '<a href="../dashboard.php">Dashboard</a> > <a href="listar.php">Unidades</a> > Nova';

$controllerUnidade = new UnidadeController();
$controllerCategoria = new CategoriaLocalUnidadeController();
$controllerSetor = new UnidadeSetorController();

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultado = $controllerUnidade->processarCadastro();
    if ($resultado['success']) {
        $_SESSION['success_message'] = $resultado['message'];
        header('Location: listar.php');
        exit;
    } else {
        $erro = $resultado['message'];
    }
}

$categorias = $controllerCategoria->getCategoriasAtivas();
$setoresDisponiveis = $controllerSetor->getSetoresDisponiveis();

include __DIR__ . '/../../app/views/layouts/header.php';
?>

<style>
    .form-container {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        max-width: 900px;
        margin: 0 auto;
    }
    .form-section {
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid #e1e8ed;
    }
    .form-section:last-child {
        border-bottom: none;
    }
    .form-section h3 {
        color: #2d3748;
        margin-bottom: 15px;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
        color: #2d3748;
    }
    .form-group input, .form-group select, .form-group textarea {
        width: 100%;
        padding: 10px 15px;
        border: 2px solid #e1e8ed;
        border-radius: 5px;
        font-size: 14px;
    }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
        border-color: #667eea;
        outline: none;
    }
    .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
    }
    .checkbox-group {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 10px;
    }
    .checkbox-item {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .checkbox-item input[type="checkbox"] {
        width: auto;
    }
    .btn {
        padding: 12px 30px;
        border-radius: 8px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.3s;
    }
    .btn-primary {
        background: #667eea;
        color: white;
    }
    .btn-primary:hover {
        background: #5568d3;
        transform: translateY(-2px);
    }
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    .actions {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        margin-top: 20px;
    }
    .required {
        color: #dc3545;
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
</style>

<div class="form-container">
    <h2>Nova Unidade</h2>

    <?php if ($erro): ?>
        <div class="alert alert-danger"><?php echo $erro; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

        <!-- Dados Básicos -->
        <div class="form-section">
            <h3>📋 Dados Básicos</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>Nome <span class="required">*</span></label>
                    <input type="text" name="nome" required value="<?php echo e($_POST['nome'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Código</label>
                    <input type="text" name="codigo" value="<?php echo e($_POST['codigo'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Categoria de Local <span class="required">*</span></label>
                <select name="categoria_local_id" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo ($_POST['categoria_local_id'] ?? '') == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo e($cat['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Endereço -->
        <div class="form-section">
            <h3>📍 Endereço</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>CEP</label>
                    <input type="text" name="cep" placeholder="00000-000" value="<?php echo e($_POST['cep'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Estado</label>
                    <input type="text" name="estado" maxlength="2" placeholder="UF" value="<?php echo e($_POST['estado'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Cidade</label>
                    <input type="text" name="cidade" value="<?php echo e($_POST['cidade'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Bairro</label>
                    <input type="text" name="bairro" value="<?php echo e($_POST['bairro'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label>Endereço</label>
                    <input type="text" name="endereco" value="<?php echo e($_POST['endereco'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Número</label>
                    <input type="text" name="numero" value="<?php echo e($_POST['numero'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Complemento</label>
                    <input type="text" name="complemento" value="<?php echo e($_POST['complemento'] ?? ''); ?>">
                </div>
            </div>
        </div>

        <!-- Contato -->
        <div class="form-section">
            <h3>📞 Contato</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>Telefone</label>
                    <input type="text" name="telefone" placeholder="(00) 00000-0000" value="<?php echo e($_POST['telefone'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo e($_POST['email'] ?? ''); ?>">
                </div>
            </div>
        </div>

        <!-- Dados Operacionais -->
        <div class="form-section">
            <h3>🏢 Dados Operacionais</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>Data de Inauguração</label>
                    <input type="date" name="data_inauguracao" value="<?php echo e($_POST['data_inauguracao'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Área (m²)</label>
                    <input type="number" step="0.01" name="area_m2" value="<?php echo e($_POST['area_m2'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Capacidade de Pessoas</label>
                    <input type="number" name="capacidade_pessoas" value="<?php echo e($_POST['capacidade_pessoas'] ?? ''); ?>">
                </div>
            </div>
        </div>

        <!-- Setores Iniciais -->
        <div class="form-section">
            <h3>📦 Setores Iniciais</h3>
            <p style="color: #718096; margin-bottom: 15px;">Selecione os setores que estarão disponíveis nesta unidade:</p>
            <div class="checkbox-group">
                <?php foreach ($setoresDisponiveis as $setor): ?>
                    <div class="checkbox-item">
                        <input type="checkbox" name="setores_iniciais[]" value="<?php echo e($setor); ?>" id="setor_<?php echo md5($setor); ?>">
                        <label for="setor_<?php echo md5($setor); ?>" style="margin: 0; font-weight: normal;"><?php echo e($setor); ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Observações -->
        <div class="form-section">
            <h3>📝 Observações</h3>
            <div class="form-group">
                <textarea name="observacoes" rows="4" placeholder="Observações adicionais..."><?php echo e($_POST['observacoes'] ?? ''); ?></textarea>
            </div>
        </div>

        <!-- Ações -->
        <div class="actions">
            <a href="listar.php" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Cadastrar Unidade</button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>
