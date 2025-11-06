<?php
/**
 * Portal do Colaborador - Página de Login
 */

// Define constante do sistema
define('SGC_SYSTEM', true);

// Carrega configurações e classes
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/ColaboradorAuth.php';

// Inicia sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$auth = new ColaboradorAuth();

// Se já está logado, redireciona para dashboard
if ($auth->isLogged()) {
    header("Location: dashboard.php");
    exit;
}

// Bloqueia acesso de usuários RH
ColaboradorAuth::bloquearRH();

$erro = '';
$sucesso = '';

// Processa login via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    // Validação básica
    if (empty($email) || empty($senha)) {
        $erro = 'Por favor, preencha todos os campos.';
    } else {
        $resultado = $auth->login($email, $senha);

        if ($resultado['success']) {
            // Login bem-sucedido
            // Verifica se tem senha temporária
            if ($resultado['data']['senha_temporaria']) {
                header("Location: trocar_senha.php");
            } else {
                header("Location: dashboard.php");
            }
            exit;
        } else {
            $erro = $resultado['message'];
        }
    }
}

// Exibe mensagem se veio de logout
if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $sucesso = 'Logout realizado com sucesso!';
}

// Exibe mensagem se senha foi trocada
if (isset($_GET['senha_alterada']) && $_GET['senha_alterada'] === '1') {
    $sucesso = 'Senha alterada com sucesso! Faça login com sua nova senha.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal do Colaborador - Login</title>
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

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
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

        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }

        .login-header i {
            font-size: 50px;
            margin-bottom: 15px;
        }

        .login-header h1 {
            font-size: 28px;
            margin-bottom: 8px;
        }

        .login-header p {
            font-size: 15px;
            opacity: 0.9;
        }

        .login-body {
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

        .input-wrapper {
            position: relative;
        }

        .form-group input {
            width: 100%;
            padding: 15px 20px;
            padding-right: 50px;
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

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
            transition: color 0.3s;
        }

        .toggle-password:hover {
            color: #667eea;
        }

        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login i {
            margin-right: 8px;
        }

        .login-footer {
            margin-top: 25px;
            text-align: center;
        }

        .login-footer a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
        }

        .login-footer a:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .divider {
            margin: 30px 0;
            text-align: center;
            position: relative;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e1e8ed;
        }

        .divider span {
            background: white;
            padding: 0 15px;
            color: #999;
            font-size: 13px;
            position: relative;
        }

        .help-link {
            display: inline-block;
            margin-top: 15px;
            color: #667eea;
            font-size: 13px;
            text-decoration: none;
            transition: all 0.3s;
        }

        .help-link:hover {
            color: #764ba2;
        }

        .help-link i {
            margin-right: 5px;
        }

        .back-to-site {
            text-align: center;
            margin-top: 20px;
        }

        .back-to-site a {
            color: white;
            text-decoration: none;
            font-size: 14px;
            opacity: 0.9;
            transition: opacity 0.3s;
        }

        .back-to-site a:hover {
            opacity: 1;
        }

        .back-to-site i {
            margin-right: 8px;
        }

        @media (max-width: 480px) {
            .login-container {
                max-width: 100%;
            }

            .login-header {
                padding: 30px 20px;
            }

            .login-body {
                padding: 30px 20px;
            }

            .login-header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-user-circle"></i>
            <h1>Portal do Colaborador</h1>
            <p>Acesse sua área pessoal</p>
        </div>

        <div class="login-body">
            <?php if (!empty($erro)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($erro); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($sucesso)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($sucesso); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i>
                        Email
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

                <div class="form-group">
                    <label for="senha">
                        <i class="fas fa-lock"></i>
                        Senha
                    </label>
                    <div class="input-wrapper">
                        <input
                            type="password"
                            id="senha"
                            name="senha"
                            placeholder="Digite sua senha"
                            required
                        >
                        <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                    </div>
                </div>

                <button type="submit" name="login" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i>
                    Entrar no Portal
                </button>
            </form>

            <div class="login-footer">
                <a href="recuperar_senha.php">
                    <i class="fas fa-question-circle"></i>
                    Esqueceu sua senha?
                </a>
            </div>

            <div class="divider">
                <span>Precisa de ajuda?</span>
            </div>

            <div style="text-align: center;">
                <a href="mailto:rh@comercialdonorte.com.br" class="help-link">
                    <i class="fas fa-envelope"></i>
                    Entre em contato com o RH
                </a>
            </div>
        </div>
    </div>

    <div class="back-to-site">
        <a href="<?php echo BASE_URL; ?>">
            <i class="fas fa-arrow-left"></i>
            Voltar ao site principal
        </a>
    </div>

    <script>
        // Toggle mostrar/ocultar senha
        const togglePassword = document.getElementById('togglePassword');
        const senhaInput = document.getElementById('senha');

        togglePassword.addEventListener('click', function() {
            const tipo = senhaInput.getAttribute('type') === 'password' ? 'text' : 'password';
            senhaInput.setAttribute('type', tipo);

            // Alterna o ícone
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        // Auto-hide alertas após 5 segundos
        const alertas = document.querySelectorAll('.alert');
        alertas.forEach(function(alerta) {
            setTimeout(function() {
                alerta.style.transition = 'opacity 0.5s, transform 0.5s';
                alerta.style.opacity = '0';
                alerta.style.transform = 'translateY(-10px)';
                setTimeout(function() {
                    alerta.remove();
                }, 500);
            }, 5000);
        });

        // Animação ao submeter formulário
        const form = document.querySelector('form');
        form.addEventListener('submit', function() {
            const btn = document.querySelector('.btn-login');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Entrando...';
            btn.disabled = true;
        });
    </script>
</body>
</html>
