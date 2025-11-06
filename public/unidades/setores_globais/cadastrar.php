<?php
/**
 * View: Cadastrar Setor Global
 */

define('SGC_SYSTEM', true);
require_once __DIR__ . '/../../../app/config/config.php';
require_once __DIR__ . '/../../../app/classes/Database.php';
require_once __DIR__ . '/../../../app/classes/Auth.php';

Auth::requireLogin();
Auth::requireAdmin();

$pageTitle = 'Novo Setor Global';
$breadcrumb = '<a href="../../dashboard.php">Dashboard</a> > <a href="../listar.php">Unidades</a> > <a href="listar.php">Setores Globais</a> > Novo';

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        $erro = 'Token de segurança inválido';
    } else {
        $nome = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');

        if (empty($nome)) {
            $erro = 'Nome do setor é obrigatório';
        } else {
            try {
                $db = Database::getInstance();
                $pdo = $db->getConnection();

                // Verifica se já existe
                $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM field_categories WHERE tipo = 'setor' AND valor = ?");
                $stmt->execute([$nome]);
                $exists = $stmt->fetch();

                if ($exists['total'] > 0) {
                    $erro = 'Já existe um setor com este nome';
                } else {
                    // Insere
                    $stmt = $pdo->prepare("INSERT INTO field_categories (tipo, valor, descricao) VALUES ('setor', ?, ?)");
                    $stmt->execute([$nome, $descricao]);

                    $_SESSION['success_message'] = 'Setor criado com sucesso!';
                    header('Location: listar.php');
                    exit;
                }
            } catch (Exception $e) {
                $erro = 'Erro ao criar setor: ' . $e->getMessage();
            }
        }
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
    }
    .btn {
        padding: 12px 30px;
        border-radius: 8px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
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
    .examples-box {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin-top: 20px;
        border-left: 4px solid #667eea;
    }
    .examples-box h4 {
        margin: 0 0 10px 0;
        color: #2d3748;
        font-size: 14px;
    }
    .examples-box ul {
        margin: 0;
        padding-left: 20px;
        color: #718096;
        font-size: 13px;
    }
</style>

<div class="main-content">
    <div class="content-wrapper">
        <div class="page-header">
            <h1>➕ <?php echo $pageTitle; ?></h1>
            <p class="breadcrumb"><?php echo $breadcrumb; ?></p>
        </div>

        <?php if ($erro): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>

        <div class="form-container">
            <h2>Novo Setor Global</h2>
            <p style="color: #666; margin-bottom: 30px;">
                Crie um novo setor que poderá ser ativado nas unidades.
            </p>

            <form method="POST" action="">
                <?php echo csrf_field(); ?>

                <div class="form-group">
                    <label>Nome do Setor <span class="required">*</span></label>
                    <input type="text"
                           name="nome"
                           required
                           maxlength="100"
                           value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>"
                           placeholder="Ex: Vendas, Caixa, Estoque, Administrativo...">
                    <div class="help-text">Nome único e descritivo do setor</div>
                </div>

                <div class="form-group">
                    <label>Descrição (opcional)</label>
                    <textarea name="descricao"
                              placeholder="Descreva as atividades e responsabilidades deste setor..."><?php echo htmlspecialchars($_POST['descricao'] ?? ''); ?></textarea>
                    <div class="help-text">Informações adicionais sobre o setor</div>
                </div>

                <div class="examples-box">
                    <h4>💡 Exemplos de Setores:</h4>
                    <ul>
                        <li><strong>Vendas:</strong> Equipe responsável pelo atendimento e vendas aos clientes</li>
                        <li><strong>Caixa:</strong> Colaboradores que operam caixas registradoras</li>
                        <li><strong>Estoque:</strong> Controle e organização de produtos</li>
                        <li><strong>Administrativo:</strong> Gestão administrativa da unidade</li>
                        <li><strong>Segurança:</strong> Equipe de segurança patrimonial</li>
                        <li><strong>Limpeza:</strong> Manutenção e limpeza das instalações</li>
                    </ul>
                </div>

                <div class="actions">
                    <a href="listar.php" class="btn btn-secondary">❌ Cancelar</a>
                    <button type="submit" class="btn btn-primary">💾 Criar Setor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../../app/views/layouts/footer.php'; ?>
