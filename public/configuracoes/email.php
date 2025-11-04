<?php
/**
 * View: Configurações de E-mail (SMTP)
 * Interface para configurar credenciais de envio de e-mail
 */

// Define constante do sistema
define('SGC_SYSTEM', true);

// Carrega configurações e classes
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';

// Verifica autenticação
Auth::requireLogin(BASE_URL);

// Apenas admin pode configurar
if (Auth::getUserLevel() !== 'admin') {
    $_SESSION['flash_error'] = 'Acesso negado. Apenas administradores podem configurar o sistema.';
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

// Buscar configurações atuais
$db = Database::getInstance();
$pdo = $db->getConnection();

$stmt = $pdo->query("SELECT * FROM configuracoes_email LIMIT 1");
$config = $stmt->fetch();

// Se não existe, criar
if (!$config) {
    $pdo->exec("INSERT INTO configuracoes_email (smtp_host, smtp_port) VALUES ('smtp.gmail.com', 587)");
    $stmt = $pdo->query("SELECT * FROM configuracoes_email LIMIT 1");
    $config = $stmt->fetch();
}

// Configurações da página
$pageTitle = 'Configurações de E-mail';
$breadcrumb = '<a href="../dashboard.php">Dashboard</a> > <a href="email.php">Configurações</a> > E-mail';

// Inclui header
include __DIR__ . '/../../app/views/layouts/header.php';
?>

<style>
    .config-card {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        max-width: 800px;
        margin: 0 auto;
    }

    .config-card h2 {
        margin: 0 0 10px 0;
        color: #333;
        font-size: 24px;
    }

    .config-card .subtitle {
        color: #666;
        margin-bottom: 30px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #333;
        font-size: 14px;
    }

    .form-group label .required {
        color: #dc3545;
    }

    .form-group input, .form-group select {
        width: 100%;
        padding: 12px;
        border: 2px solid #e1e8ed;
        border-radius: 5px;
        font-size: 14px;
    }

    .form-group input:focus, .form-group select:focus {
        outline: none;
        border-color: #667eea;
    }

    .form-group small {
        display: block;
        margin-top: 5px;
        color: #666;
        font-size: 12px;
    }

    .form-row {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }

    .alert {
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
        border-left: 4px solid;
    }

    .alert-info {
        background: #d1ecf1;
        border-color: #0c5460;
        color: #0c5460;
    }

    .alert-warning {
        background: #fff3cd;
        border-color: #856404;
        color: #856404;
    }

    .form-actions {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 2px solid #f0f0f0;
    }

    .btn {
        padding: 12px 24px;
        border-radius: 5px;
        text-decoration: none;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
        font-size: 14px;
    }

    .btn-success {
        background: #28a745;
        color: white;
    }

    .btn-success:hover {
        background: #218838;
    }

    .btn-info {
        background: #17a2b8;
        color: white;
    }

    .btn-info:hover {
        background: #138496;
    }

    .status-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: 600;
    }

    .status-ativo {
        background: #d4edda;
        color: #155724;
    }

    .status-inativo {
        background: #f8d7da;
        color: #721c24;
    }
</style>

<div class="config-card">
    <h2>⚙️ Configurações de E-mail (SMTP)</h2>
    <p class="subtitle">Configure as credenciais para envio de notificações aos participantes</p>

    <?php if (!$config['habilitado']): ?>
        <div class="alert alert-warning">
            <strong>⚠️ Sistema de e-mail desativado</strong><br>
            Preencha os campos abaixo e marque como "Habilitado" para começar a enviar notificações.
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            <strong>✅ Sistema de e-mail ativo</strong><br>
            Status: <span class="status-badge status-ativo">Habilitado</span>
        </div>
    <?php endif; ?>

    <form method="POST" action="actions.php" id="formConfigEmail">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
        <input type="hidden" name="action" value="salvar_config_email">

        <div class="form-row">
            <div class="form-group">
                <label>
                    Servidor SMTP <span class="required">*</span>
                </label>
                <input type="text" name="smtp_host" required
                       value="<?php echo htmlspecialchars($config['smtp_host']); ?>"
                       placeholder="smtp.gmail.com">
                <small>Exemplo: smtp.gmail.com, smtp.office365.com, smtp.mailtrap.io</small>
            </div>

            <div class="form-group">
                <label>
                    Porta SMTP <span class="required">*</span>
                </label>
                <input type="number" name="smtp_port" required
                       value="<?php echo $config['smtp_port']; ?>"
                       placeholder="587">
                <small>Porta comum: 587 (TLS) ou 465 (SSL)</small>
            </div>
        </div>

        <div class="form-group">
            <label>
                Segurança <span class="required">*</span>
            </label>
            <select name="smtp_secure" required>
                <option value="tls" <?php echo $config['smtp_secure'] === 'tls' ? 'selected' : ''; ?>>TLS</option>
                <option value="ssl" <?php echo $config['smtp_secure'] === 'ssl' ? 'selected' : ''; ?>>SSL</option>
            </select>
            <small>Recomendado: TLS para porta 587, SSL para porta 465</small>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>
                    Usuário SMTP <span class="required">*</span>
                </label>
                <input type="text" name="smtp_user" required
                       value="<?php echo htmlspecialchars($config['smtp_user'] ?? ''); ?>"
                       placeholder="seu-email@gmail.com">
                <small>Seu e-mail de login no servidor SMTP</small>
            </div>

            <div class="form-group">
                <label>
                    Senha SMTP <span class="required">*</span>
                </label>
                <input type="password" name="smtp_password"
                       placeholder="<?php echo $config['smtp_password'] ? '••••••••' : 'Digite a senha'; ?>">
                <small>Senha do e-mail ou senha de aplicativo (Gmail)</small>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>
                    E-mail Remetente <span class="required">*</span>
                </label>
                <input type="email" name="email_remetente" required
                       value="<?php echo htmlspecialchars($config['email_remetente'] ?? ''); ?>"
                       placeholder="noreply@empresa.com">
                <small>E-mail que aparecerá como remetente</small>
            </div>

            <div class="form-group">
                <label>
                    Nome Remetente <span class="required">*</span>
                </label>
                <input type="text" name="nome_remetente" required
                       value="<?php echo htmlspecialchars($config['nome_remetente']); ?>"
                       placeholder="SGC - Sistema de Capacitações">
                <small>Nome que aparecerá como remetente</small>
            </div>
        </div>

        <div class="form-group">
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                <input type="checkbox" name="habilitado" value="1"
                       <?php echo $config['habilitado'] ? 'checked' : ''; ?>
                       style="width: auto; margin: 0;">
                <span>Habilitar sistema de e-mail</span>
            </label>
            <small>Marque esta opção para ativar o envio de notificações</small>
        </div>

        <div class="form-actions">
            <button type="button" onclick="testarConexao()" class="btn btn-info">
                📧 Testar Conexão
            </button>
            <button type="submit" class="btn btn-success">
                💾 Salvar Configurações
            </button>
        </div>
    </form>
</div>

<script>
function testarConexao() {
    if (!confirm('Deseja enviar um e-mail de teste para verificar a configuração?')) {
        return;
    }

    // Desabilita botão
    const btn = event.target;
    btn.disabled = true;
    btn.textContent = 'Testando...';

    fetch('actions.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=testar_email&csrf_token=<?php echo csrf_token(); ?>'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message);
        } else {
            alert('❌ ' + data.message);
        }
        btn.disabled = false;
        btn.innerHTML = '📧 Testar Conexão';
    })
    .catch(e => {
        alert('Erro ao testar conexão: ' + e.message);
        btn.disabled = false;
        btn.innerHTML = '📧 Testar Conexão';
    });
}
</script>

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>
