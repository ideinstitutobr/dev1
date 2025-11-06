<?php
/**
 * Portal do Colaborador - Resetar Senha (via Token)
 */

// Define constante do sistema
define('SGC_SYSTEM', true);

// Carrega configurações e classes
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/models/ColaboradorSenha.php';

$erro = '';
$sucesso = '';
$tokenValido = false;
$colaborador = null;

// Verifica se o token foi fornecido
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $erro = 'Link inválido. Solicite a recuperação de senha novamente.';
} else {
    // Valida o token
    $modelSenha = new ColaboradorSenha();
    $colaborador = $modelSenha->validarTokenReset($token);

    if ($colaborador) {
        $tokenValido = true;
    } else {
        $erro = 'Este link de recuperação expirou ou é inválido. Solicite um novo link.';
    }
}

// Processa a nova senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resetar_senha'])) {
    $token = $_POST['token'] ?? '';
    $novaSenha = $_POST['nova_senha'] ?? '';
    $confirmarSenha = $_POST['confirmar_senha'] ?? '';

    // Validações
    if (empty($novaSenha) || empty($confirmarSenha)) {
        $erro = 'Por favor, preencha todos os campos.';
    } elseif (strlen($novaSenha) < 6) {
        $erro = 'A senha deve ter no mínimo 6 caracteres.';
    } elseif ($novaSenha !== $confirmarSenha) {
        $erro = 'As senhas não coincidem.';
    } else {
        $modelSenha = new ColaboradorSenha();
        $resultado = $modelSenha->resetarSenha($token, $novaSenha);

        if ($resultado['success']) {
            header("Location: index.php?senha_alterada=1");
            exit;
        } else {
            $erro = $resultado['message'] ?? 'Erro ao redefinir senha. Tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - Portal do Colaborador</title>
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

        .reset-container {
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

        .reset-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }

        .reset-header i {
            font-size: 50px;
            margin-bottom: 15px;
        }

        .reset-header h1 {
            font-size: 26px;
            margin-bottom: 8px;
        }

        .reset-header p {
            font-size: 14px;
            opacity: 0.9;
        }

        .reset-body {
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

        .alert i {
            margin-right: 10px;
        }

        .user-info {
            background: #f8f9fa;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            text-align: center;
        }

        .user-info i {
            font-size: 40px;
            color: #667eea;
            margin-bottom: 10px;
        }

        .user-info strong {
            display: block;
            font-size: 18px;
            color: #333;
            margin-bottom: 5px;
        }

        .user-info small {
            color: #666;
            font-size: 13px;
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

        .password-strength {
            margin-top: 10px;
            font-size: 13px;
        }

        .strength-bar {
            height: 5px;
            border-radius: 3px;
            background: #e1e8ed;
            margin-top: 5px;
            overflow: hidden;
        }

        .strength-bar-fill {
            height: 100%;
            width: 0%;
            transition: all 0.3s;
        }

        .strength-weak { background: #dc3545; width: 33%; }
        .strength-medium { background: #ffc107; width: 66%; }
        .strength-strong { background: #28a745; width: 100%; }

        .password-requirements {
            margin-top: 15px;
            font-size: 13px;
            color: #666;
            line-height: 1.8;
        }

        .password-requirements li {
            margin-left: 20px;
        }

        .requirement-met {
            color: #28a745;
        }

        .btn-reset {
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

        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-reset i {
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

        @media (max-width: 480px) {
            .reset-container {
                max-width: 100%;
            }

            .reset-header {
                padding: 30px 20px;
            }

            .reset-body {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <i class="fas fa-shield-alt"></i>
            <h1>Nova Senha</h1>
            <p>Defina uma senha segura para sua conta</p>
        </div>

        <div class="reset-body">
            <?php if (!empty($erro)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($erro); ?>
                </div>

                <div class="back-link">
                    <a href="recuperar_senha.php">
                        <i class="fas fa-redo"></i>
                        Solicitar novo link de recuperação
                    </a>
                </div>

            <?php elseif ($tokenValido): ?>
                <div class="user-info">
                    <i class="fas fa-user-circle"></i>
                    <strong><?php echo htmlspecialchars($colaborador['nome']); ?></strong>
                    <small><?php echo htmlspecialchars($colaborador['email']); ?></small>
                </div>

                <form method="POST" action="" id="formReset">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                    <div class="form-group">
                        <label for="nova_senha">
                            <i class="fas fa-lock"></i>
                            Nova Senha
                        </label>
                        <div class="input-wrapper">
                            <input
                                type="password"
                                id="nova_senha"
                                name="nova_senha"
                                placeholder="Digite sua nova senha"
                                required
                                autofocus
                            >
                            <i class="fas fa-eye toggle-password" data-target="nova_senha"></i>
                        </div>
                        <div class="password-strength">
                            <div class="strength-bar">
                                <div class="strength-bar-fill" id="strengthBar"></div>
                            </div>
                            <span id="strengthText"></span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirmar_senha">
                            <i class="fas fa-lock"></i>
                            Confirmar Nova Senha
                        </label>
                        <div class="input-wrapper">
                            <input
                                type="password"
                                id="confirmar_senha"
                                name="confirmar_senha"
                                placeholder="Digite a senha novamente"
                                required
                            >
                            <i class="fas fa-eye toggle-password" data-target="confirmar_senha"></i>
                        </div>
                    </div>

                    <div class="password-requirements">
                        <strong>Requisitos da senha:</strong>
                        <ul>
                            <li id="req-length">Mínimo de 6 caracteres</li>
                            <li id="req-match">As senhas devem ser idênticas</li>
                        </ul>
                    </div>

                    <button type="submit" name="resetar_senha" class="btn-reset">
                        <i class="fas fa-check"></i>
                        Redefinir Senha
                    </button>
                </form>

                <div class="back-link">
                    <a href="index.php">
                        <i class="fas fa-arrow-left"></i>
                        Voltar para o login
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Toggle mostrar/ocultar senha
        const toggleButtons = document.querySelectorAll('.toggle-password');
        toggleButtons.forEach(function(btn) {
            btn.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);
                const tipo = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', tipo);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        });

        // Verificação de força da senha
        const novaSenhaInput = document.getElementById('nova_senha');
        const confirmarSenhaInput = document.getElementById('confirmar_senha');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');
        const reqLength = document.getElementById('req-length');
        const reqMatch = document.getElementById('req-match');

        function checkPasswordStrength() {
            const senha = novaSenhaInput.value;
            let strength = 0;

            if (senha.length >= 6) {
                strength++;
                reqLength.classList.add('requirement-met');
            } else {
                reqLength.classList.remove('requirement-met');
            }

            if (senha.length >= 8) strength++;
            if (/[a-z]/.test(senha) && /[A-Z]/.test(senha)) strength++;
            if (/[0-9]/.test(senha)) strength++;
            if (/[^a-zA-Z0-9]/.test(senha)) strength++;

            strengthBar.className = 'strength-bar-fill';
            if (strength <= 2) {
                strengthBar.classList.add('strength-weak');
                strengthText.textContent = 'Senha fraca';
                strengthText.style.color = '#dc3545';
            } else if (strength <= 3) {
                strengthBar.classList.add('strength-medium');
                strengthText.textContent = 'Senha média';
                strengthText.style.color = '#ffc107';
            } else {
                strengthBar.classList.add('strength-strong');
                strengthText.textContent = 'Senha forte';
                strengthText.style.color = '#28a745';
            }
        }

        function checkPasswordMatch() {
            const senha = novaSenhaInput.value;
            const confirmar = confirmarSenhaInput.value;

            if (confirmar.length > 0) {
                if (senha === confirmar) {
                    reqMatch.classList.add('requirement-met');
                } else {
                    reqMatch.classList.remove('requirement-met');
                }
            }
        }

        novaSenhaInput.addEventListener('input', function() {
            checkPasswordStrength();
            checkPasswordMatch();
        });

        confirmarSenhaInput.addEventListener('input', checkPasswordMatch);

        // Animação ao submeter
        const form = document.getElementById('formReset');
        if (form) {
            form.addEventListener('submit', function() {
                const btn = form.querySelector('.btn-reset');
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Redefinindo...';
                btn.disabled = true;
            });
        }
    </script>
</body>
</html>
