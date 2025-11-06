<?php
/**
 * Portal do Colaborador - Recuperação de Senha
 */

// Define constante do sistema
define('SGC_SYSTEM', true);

// Carrega configurações e classes
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/models/ColaboradorSenha.php';

$erro = '';
$sucesso = '';
$enviado = false;

// Processa solicitação de reset
// Processa sempre que houver POST (não depender do nome do botão)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $erro = 'Por favor, informe seu email.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Email inválido.';
    } else {
        try {
            $db = Database::getInstance();
            $pdo = $db->getConnection();

            // Busca colaborador pelo email
            $sql = "SELECT id, nome, email FROM colaboradores WHERE email = ? AND ativo = 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$email]);
            $colaborador = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($colaborador) {
                // Gera token de reset
                $modelSenha = new ColaboradorSenha();
                $resultado = $modelSenha->gerarTokenReset($colaborador['id']);

                if ($resultado['success']) {
                    $token = $resultado['token'];
                    $linkReset = BASE_URL . "/portal/resetar_senha.php?token=" . $token;

                    // Envia email com link de reset
                    $assunto = "Recuperação de Senha - Portal do Colaborador";
                    $mensagem = "
                        <h2>Recuperação de Senha</h2>
                        <p>Olá, <strong>{$colaborador['nome']}</strong>!</p>
                        <p>Recebemos uma solicitação para redefinir sua senha no Portal do Colaborador.</p>
                        <p>Clique no link abaixo para criar uma nova senha:</p>
                        <p><a href='$linkReset' style='padding: 12px 24px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; display: inline-block;'>Redefinir Senha</a></p>
                        <p><small>Ou copie e cole este link no navegador: $linkReset</small></p>
                        <p><strong>Este link expira em 2 horas.</strong></p>
                        <p>Se você não solicitou esta alteração, ignore este email.</p>
                        <hr>
                        <p><small>Comercial do Norte - SGC</small></p>
                    ";

                    // Envia via NotificationManager (SMTP)
                    require_once __DIR__ . '/../../app/classes/NotificationManager.php';
                    $nm = new NotificationManager();
                    if ($nm->isConfigured()) {
                        $emailEnviado = $nm->enviarEmailGenerico($email, $assunto, $mensagem);
                        if ($emailEnviado) {
                            $sucesso = "Um email com instruções foi enviado para <strong>$email</strong>. Verifique sua caixa de entrada e spam.";
                            $enviado = true;
                        } else {
                            $erro = "Falha ao enviar e-mail via SMTP. Verifique as configurações em Configurações > E-mail.";
                        }
                    } else {
                        $erro = "Sistema de e-mail não configurado. Configure em Configurações > E-mail e tente novamente.";
                    }
                } else {
                    $erro = "Erro ao gerar token de recuperação.";
                }
            } else {
                // Por segurança, não revela se o email existe ou não
                $sucesso = "Se este email estiver cadastrado, você receberá instruções para redefinir sua senha.";
                $enviado = true;
            }

        } catch (PDOException $e) {
            error_log("ERRO RECUPERAR SENHA: " . $e->getMessage());
            $erro = "Erro ao processar solicitação. Tente novamente mais tarde.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - Portal do Colaborador</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .recovery-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            width: 100%;
            max-width: 500px;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .recovery-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }

        .recovery-header i {
            font-size: 50px;
            margin-bottom: 15px;
        }

        .recovery-header h1 {
            font-size: 26px;
            margin-bottom: 8px;
        }

        .recovery-header p {
            font-size: 14px;
            opacity: 0.9;
            line-height: 1.6;
        }

        .recovery-body {
            padding: 40px 30px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 14px;
            line-height: 1.5;
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-error {
            background: #fee;
            border-left: 4px solid #dc3545;
            color: #721c24;
        }

        .alert-success {
            background: #d4edda;
            border-left: 4px solid #28a745;
            color: #155724;
        }

        .alert i {
            margin-right: 10px;
        }

        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 14px;
            line-height: 1.7;
            color: #0d47a1;
        }

        .info-box i {
            margin-right: 10px;
            color: #2196F3;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        .form-group label i {
            margin-right: 8px;
            color: #667eea;
            width: 18px;
        }

        .form-group input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e1e8ed;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
            background: #f8f9fa;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .btn {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
            display: inline-block;
            text-decoration: none;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn i {
            margin-right: 8px;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
        }

        .back-link a:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .back-link i {
            margin-right: 5px;
        }

        .success-icon {
            text-align: center;
            margin-bottom: 20px;
        }

        .success-icon i {
            font-size: 60px;
            color: #28a745;
            animation: scaleIn 0.5s ease-out;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        @media (max-width: 480px) {
            .recovery-container {
                max-width: 100%;
            }

            .recovery-header {
                padding: 30px 20px;
            }

            .recovery-body {
                padding: 30px 20px;
            }

            .recovery-header h1 {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>
    <div class="recovery-container">
        <div class="recovery-header">
            <i class="fas fa-key"></i>
            <h1>Recuperar Senha</h1>
            <p>Esqueceu sua senha? Sem problemas! Informe seu email para receber instruções.</p>
        </div>

        <div class="recovery-body">
            <?php if (!$enviado): ?>
                <?php if (!empty($erro)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($erro); ?>
                    </div>
                <?php endif; ?>

                <div class="info-box">
                    <i class="fas fa-info-circle"></i>
                    Informe o email cadastrado no sistema. Enviaremos um link para redefinir sua senha.
                </div>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i>
                            Email Cadastrado
                        </label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="seu.email@empresa.com"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                            required
                            autofocus
                        >
                    </div>

                    <input type="hidden" name="solicitar_reset" value="1">
                    <button type="submit" name="solicitar_reset" value="1" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i>
                        Enviar Link de Recuperação
                    </button>
                </form>

                <div class="back-link">
                    <a href="index.php">
                        <i class="fas fa-arrow-left"></i>
                        Voltar para o login
                    </a>
                </div>

            <?php else: ?>
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>

                <div class="alert alert-success">
                    <i class="fas fa-envelope-open-text"></i>
                    <?php echo $sucesso; ?>
                </div>

                <div class="info-box">
                    <i class="fas fa-lightbulb"></i>
                    <strong>Não recebeu o email?</strong><br>
                    • Verifique sua caixa de spam<br>
                    • Aguarde alguns minutos<br>
                    • Verifique se o email está correto<br>
                    • Entre em contato com o RH se o problema persistir
                </div>

                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i>
                    Voltar para o Login
                </a>

                <a href="recuperar_senha.php" class="btn btn-secondary">
                    <i class="fas fa-redo"></i>
                    Solicitar Novamente
                </a>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-hide alertas após 8 segundos
        const alertas = document.querySelectorAll('.alert-error');
        alertas.forEach(function(alerta) {
            setTimeout(function() {
                alerta.style.transition = 'opacity 0.5s, transform 0.5s';
                alerta.style.opacity = '0';
                alerta.style.transform = 'translateY(-10px)';
                setTimeout(function() {
                    alerta.remove();
                }, 500);
            }, 8000);
        });

        // Animação ao submeter formulário
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function() {
                const btn = form.querySelector('.btn-primary');
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
                btn.disabled = true;
            });
        }
    </script>
</body>
</html>
