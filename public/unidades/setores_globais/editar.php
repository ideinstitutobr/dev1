<?php
/**
 * View: Editar Setor Global
 */

define('SGC_SYSTEM', true);
require_once __DIR__ . '/../../../app/config/config.php';
require_once __DIR__ . '/../../../app/classes/Database.php';
require_once __DIR__ . '/../../../app/classes/Auth.php';

Auth::requireLogin();
Auth::requireAdmin();

$pageTitle = 'Editar Setor Global';
$breadcrumb = '<a href="../../dashboard.php">Dashboard</a> > <a href="../listar.php">Unidades</a> > <a href="listar.php">Setores Globais</a> > Editar';

$db = Database::getInstance();
$pdo = $db->getConnection();

$id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
if (!$id) {
    $_SESSION['error_message'] = 'ID do setor inválido';
    header('Location: listar.php');
    exit;
}

// Busca setor
$stmt = $pdo->prepare("SELECT * FROM field_categories WHERE id = ? AND tipo = 'setor'");
$stmt->execute([$id]);
$setor = $stmt->fetch();

if (!$setor) {
    $_SESSION['error_message'] = 'Setor não encontrado';
    header('Location: listar.php');
    exit;
}

// Busca estatísticas de uso
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM unidade_setores WHERE setor = ? AND ativo = 1");
$stmt->execute([$setor['valor']]);
$unidades_usando = $stmt->fetch()['total'];

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
                // Se mudou o nome, verifica duplicidade
                if ($nome !== $setor['valor']) {
                    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM field_categories WHERE tipo = 'setor' AND valor = ? AND id != ?");
                    $stmt->execute([$nome, $id]);
                    $exists = $stmt->fetch();

                    if ($exists['total'] > 0) {
                        $erro = 'Já existe um setor com este nome';
                    } else {
                        // Atualiza o nome nas tabelas relacionadas
                        $pdo->beginTransaction();

                        try {
                            // Atualiza field_categories
                            $stmt = $pdo->prepare("UPDATE field_categories SET valor = ?, descricao = ? WHERE id = ?");
                            $stmt->execute([$nome, $descricao, $id]);

                            // Atualiza unidade_setores
                            $stmt = $pdo->prepare("UPDATE unidade_setores SET setor = ? WHERE setor = ?");
                            $stmt->execute([$nome, $setor['valor']]);

                            // Atualiza colaboradores.setor_principal (se existir)
                            $stmt = $pdo->prepare("SHOW COLUMNS FROM colaboradores LIKE 'setor_principal'");
                            $stmt->execute();
                            if ($stmt->rowCount() > 0) {
                                $stmt = $pdo->prepare("UPDATE colaboradores SET setor_principal = ? WHERE setor_principal = ?");
                                $stmt->execute([$nome, $setor['valor']]);
                            }

                            $pdo->commit();
                            $_SESSION['success_message'] = 'Setor atualizado com sucesso em todas as referências!';
                            header('Location: listar.php');
                            exit;
                        } catch (Exception $e) {
                            $pdo->rollBack();
                            throw $e;
                        }
                    }
                } else {
                    // Só atualiza descrição
                    $stmt = $pdo->prepare("UPDATE field_categories SET descricao = ? WHERE id = ?");
                    $stmt->execute([$descricao, $id]);

                    $_SESSION['success_message'] = 'Setor atualizado com sucesso!';
                    header('Location: listar.php');
                    exit;
                }
            } catch (Exception $e) {
                $erro = 'Erro ao atualizar setor: ' . $e->getMessage();
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
    .info-box {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        color: #856404;
    }
    .usage-box {
        background: #e7f3ff;
        border: 1px solid #b3d9ff;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        color: #004085;
    }
</style>

<div class="main-content">
    <div class="content-wrapper">
        <div class="page-header">
            <h1>✏️ <?php echo $pageTitle; ?></h1>
            <p class="breadcrumb"><?php echo $breadcrumb; ?></p>
        </div>

        <?php if ($erro): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>

        <?php if ($unidades_usando > 0): ?>
            <div class="info-box">
                <strong>⚠️ Atenção:</strong> Este setor está sendo usado por <strong><?php echo $unidades_usando; ?> unidade(s)</strong>.
                Se você alterar o nome, ele será atualizado automaticamente em todas as unidades e colaboradores vinculados.
            </div>
        <?php endif; ?>

        <div class="form-container">
            <h2>Editar Setor Global</h2>
            <p style="color: #666; margin-bottom: 30px;">
                Atualize as informações do setor.
            </p>

            <div class="usage-box">
                <strong>📊 Uso Atual:</strong><br>
                • Unidades usando este setor: <strong><?php echo $unidades_usando; ?></strong><br>
                • Criado em: <strong><?php echo date('d/m/Y H:i', strtotime($setor['created_at'])); ?></strong>
            </div>

            <form method="POST" action="">
                <?php echo csrf_field(); ?>

                <div class="form-group">
                    <label>Nome do Setor <span class="required">*</span></label>
                    <input type="text"
                           name="nome"
                           required
                           maxlength="100"
                           value="<?php echo htmlspecialchars($_POST['nome'] ?? $setor['valor']); ?>"
                           placeholder="Ex: Vendas, Caixa, Estoque...">
                    <div class="help-text">
                        <?php if ($unidades_usando > 0): ?>
                            Ao alterar o nome, todas as <?php echo $unidades_usando; ?> unidade(s) serão atualizadas automaticamente
                        <?php else: ?>
                            Nome único e descritivo do setor
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label>Descrição (opcional)</label>
                    <textarea name="descricao"
                              placeholder="Descreva as atividades e responsabilidades deste setor..."><?php echo htmlspecialchars($_POST['descricao'] ?? $setor['descricao'] ?? ''); ?></textarea>
                    <div class="help-text">Informações adicionais sobre o setor</div>
                </div>

                <div class="actions">
                    <a href="listar.php" class="btn btn-secondary">❌ Cancelar</a>
                    <button type="submit" class="btn btn-primary">💾 Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../../app/views/layouts/footer.php'; ?>
