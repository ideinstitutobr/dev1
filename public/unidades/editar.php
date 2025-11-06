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

Auth::requireLogin();

// Verifica se ID foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = 'ID da unidade não fornecido';
    header('Location: listar.php');
    exit;
}

$id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
if (!$id) {
    $_SESSION['error_message'] = 'ID inválido';
    header('Location: listar.php');
    exit;
}

$controllerUnidade = new UnidadeController();
$controllerCategoria = new CategoriaLocalUnidadeController();
$controllerSetor = new UnidadeSetorController();

// Busca dados da unidade
$unidade = $controllerUnidade->buscarPorId($id);
if (!$unidade) {
    $_SESSION['error_message'] = 'Unidade não encontrada';
    header('Location: listar.php');
    exit;
}

$pageTitle = 'Editar Unidade';
$breadcrumb = '<a href="../dashboard.php">Dashboard</a> > <a href="listar.php">Unidades</a> > Editar';

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultado = $controllerUnidade->processarEdicao($id);
    if ($resultado['success']) {
        $_SESSION['success_message'] = $resultado['message'];
        header('Location: visualizar.php?id=' . $id);
        exit;
    } else {
        $erro = $resultado['message'];
    }
}

$categorias = $controllerCategoria->getCategoriasAtivas();
$setoresDisponiveis = $controllerSetor->getSetoresDisponiveis();
$setoresAtivos = $controllerSetor->buscarPorUnidade($id, true);

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
    .actions {
        display: flex;
        gap: 10px;
        margin-top: 30px;
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
    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .required {
        color: #e53e3e;
        margin-left: 3px;
    }
    .help-text {
        font-size: 12px;
        color: #718096;
        margin-top: 5px;
    }
</style>

<div class="main-content">
    <div class="page-header">
        <h1>✏️ Editar Unidade</h1>
        <p><?php echo $breadcrumb; ?></p>
    </div>

    <div class="form-container">
        <?php if ($erro): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($erro); ?>
            </div>
        <?php endif; ?>

        <?php if ($sucesso): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($sucesso); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <?php echo csrf_field(); ?>

            <!-- Dados Básicos -->
            <div class="form-section">
                <h3>📋 Dados Básicos</h3>

                <div class="form-row">
                    <div class="form-group">
                        <label>Nome da Unidade<span class="required">*</span></label>
                        <input type="text" name="nome" value="<?php echo e($unidade['nome']); ?>" required>
                        <div class="help-text">Nome completo da unidade/loja</div>
                    </div>

                    <div class="form-group">
                        <label>Código</label>
                        <input type="text" name="codigo" value="<?php echo e($unidade['codigo']); ?>">
                        <div class="help-text">Código único de identificação</div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Categoria de Local<span class="required">*</span></label>
                    <select name="categoria_local_id" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"
                                <?php echo $cat['id'] == $unidade['categoria_local_id'] ? 'selected' : ''; ?>>
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
                    <div class="form-group" style="grid-column: span 2;">
                        <label>Endereço</label>
                        <input type="text" name="endereco" value="<?php echo e($unidade['endereco']); ?>">
                    </div>

                    <div class="form-group">
                        <label>Número</label>
                        <input type="text" name="numero" value="<?php echo e($unidade['numero']); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Complemento</label>
                        <input type="text" name="complemento" value="<?php echo e($unidade['complemento']); ?>">
                    </div>

                    <div class="form-group">
                        <label>Bairro</label>
                        <input type="text" name="bairro" value="<?php echo e($unidade['bairro']); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Cidade</label>
                        <input type="text" name="cidade" value="<?php echo e($unidade['cidade']); ?>">
                    </div>

                    <div class="form-group">
                        <label>Estado</label>
                        <select name="estado">
                            <option value="">Selecione...</option>
                            <?php
                            $estados = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
                            foreach ($estados as $uf):
                            ?>
                                <option value="<?php echo $uf; ?>" <?php echo $uf == $unidade['estado'] ? 'selected' : ''; ?>>
                                    <?php echo $uf; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>CEP</label>
                        <input type="text" name="cep" value="<?php echo e($unidade['cep']); ?>" maxlength="10">
                    </div>
                </div>
            </div>

            <!-- Contato -->
            <div class="form-section">
                <h3>📞 Contato</h3>

                <div class="form-row">
                    <div class="form-group">
                        <label>Telefone</label>
                        <input type="text" name="telefone" value="<?php echo e($unidade['telefone']); ?>">
                    </div>

                    <div class="form-group">
                        <label>E-mail</label>
                        <input type="email" name="email" value="<?php echo e($unidade['email']); ?>">
                    </div>
                </div>
            </div>

            <!-- Dados Operacionais -->
            <div class="form-section">
                <h3>🏗️ Dados Operacionais</h3>

                <div class="form-row">
                    <div class="form-group">
                        <label>Data de Inauguração</label>
                        <input type="date" name="data_inauguracao" value="<?php echo e($unidade['data_inauguracao']); ?>">
                    </div>

                    <div class="form-group">
                        <label>Área (m²)</label>
                        <input type="number" name="area_m2" step="0.01" value="<?php echo e($unidade['area_m2']); ?>">
                    </div>

                    <div class="form-group">
                        <label>Capacidade (pessoas)</label>
                        <input type="number" name="capacidade_pessoas" value="<?php echo e($unidade['capacidade_pessoas']); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Observações</label>
                    <textarea name="observacoes" rows="4"><?php echo e($unidade['observacoes']); ?></textarea>
                </div>
            </div>

            <!-- Setores Ativos (gerenciamento básico) -->
            <div class="form-section">
                <h3>🏢 Setores Ativos nesta Unidade</h3>
                <p style="color: #718096; font-size: 14px; margin-bottom: 15px;">
                    Selecione os setores que estão ativos nesta unidade. Para gerenciar setores de forma mais detalhada,
                    use a aba "Gerenciar Setores" na visualização da unidade.
                </p>

                <div class="checkbox-group">
                    <?php
                    // Cria array de setores ativos para checagem
                    $setoresAtivosArray = array_column($setoresAtivos, 'setor');
                    foreach ($setoresDisponiveis as $setor):
                    ?>
                        <div class="checkbox-item">
                            <input
                                type="checkbox"
                                name="setores[]"
                                value="<?php echo e($setor); ?>"
                                id="setor_<?php echo e($setor); ?>"
                                <?php echo in_array($setor, $setoresAtivosArray) ? 'checked' : ''; ?>
                            >
                            <label for="setor_<?php echo e($setor); ?>"><?php echo e($setor); ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Status -->
            <div class="form-section">
                <h3>⚙️ Status</h3>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="ativo" value="1" <?php echo $unidade['ativo'] ? 'checked' : ''; ?>>
                        Unidade ativa
                    </label>
                    <div class="help-text">Desmarque para inativar a unidade (não será excluída)</div>
                </div>
            </div>

            <!-- Ações -->
            <div class="actions">
                <button type="submit" class="btn btn-primary">💾 Salvar Alterações</button>
                <a href="visualizar.php?id=<?php echo $id; ?>" class="btn btn-secondary">❌ Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>
