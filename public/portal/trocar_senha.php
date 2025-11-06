<?php
/**
 * Portal do Colaborador - Trocar Senha (Obrigatório para senha temporária)
 */

// Define constante do sistema
define('SGC_SYSTEM', true);

// Carrega configurações e classes
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/ColaboradorAuth.php';
require_once __DIR__ . '/../../app/models/ColaboradorSenha.php';

$auth = new ColaboradorAuth();

// Requer que esteja logado
if (!$auth->isLogged()) {
    header("Location: index.php");
    exit;
}

// Se não tem senha temporária, vai para o dashboard
if (!$auth->verificarSenhaTemporaria()) {
    header("Location: dashboard.php");
    exit;
}

$colaboradorId = $auth->getColaboradorId();
$colaboradorData = $auth->getColaboradorData();
$erro = '';
$sucesso = '';

// Processa troca de senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trocar_senha'])) {
    $senhaAtual = $_POST['senha_atual'] ?? '';
    $novaSenha = $_POST['nova_senha'] ?? '';
    $confirmarSenha = $_POST['confirmar_senha'] ?? '';

    // Validações
    if (empty($senhaAtual) || empty($novaSenha) || empty($confirmarSenha)) {
        $erro = 'Por favor, preencha todos os campos.';
    } elseif (strlen($novaSenha) < 6) {
        $erro = 'A nova senha deve ter no mínimo 6 caracteres.';
    } elseif ($novaSenha !== $confirmarSenha) {
        $erro = 'As senhas não coincidem.';
    } elseif ($senhaAtual === $novaSenha) {
        $erro = 'A nova senha deve ser diferente da senha temporária.';
    } else {
        try {
            $db = Database::getInstance();
            $pdo = $db->getConnection();

            // Verifica senha atual
            $sql = "SELECT senha_hash FROM colaboradores_senhas WHERE colaborador_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$colaboradorId]);
            $registro = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$registro || !password_verify($senhaAtual, $registro['senha_hash'])) {
                $erro = 'Senha temporária incorreta.';
            } else {
                // Atualiza senha
                $modelSenha = new ColaboradorSenha();
                $resultado = $modelSenha->atualizar($colaboradorId, $novaSenha, false); // false = não é mais temporária

                if ($resultado['success']) {
                    // Atualiza flag na sessão
                    $auth->setSenhaTemporaria(false);

                    // Redireciona para dashboard
                    header("Location: dashboard.php?senha_alterada=1");
                    exit;
                } else {
                    $erro = $resultado['message'] ?? 'Erro ao alterar senha.';
                }
            }

        } catch (PDOException $e) {
            error_log("ERRO TROCAR SENHA: " . $e->getMessage());
            $erro = "Erro ao processar alteração de senha.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trocar Senha - Portal do Colaborador</title>
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

        .change-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            width: 100%;
            max-width: 550px;
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

        .change-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }

        .change-header i {
            font-size: 50px;
            margin-bottom: 15px;
        }

        .change-header h1 {
            font-size: 26px;
            margin-bottom: 8px;
        }

        .change-header p {
            font-size: 14px;
            opacity: 0.9;
            line-height: 1.6;
        }

        .change-body {
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

        .alert-warning {
            background: #fff3cd;
            border-left: 4px solid #ff9800;
            color: #856404;
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

        .password-requirements {
            margin-top: 15px;
            font-size: 13px;
            color: #666;
            line-height: 1.8;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }

        .password-requirements strong {
            display: block;
            margin-bottom: 8px;
            color: #333;
        }

        .password-requirements li {
            margin-left: 20px;
        }

        .requirement-met {
            color: #28a745;
        }

        .btn-change {
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

        .btn-change:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-change:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .btn-change i {
            margin-right: 8px;
        }

        .logout-link {
            text-align: center;
            margin-top: 20px;
        }

        .logout-link a {
            color: #dc3545;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
        }

        .logout-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .change-container {
                max-width: 100%;
            }

            .change-header {
                padding: 30px 20px;
            }

            .change-body {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="change-container">
        <div class="change-header">
            <i class="fas fa-exclamation-triangle"></i>
            <h1>Troca de Senha Obrigatória</h1>
            <p>Você está usando uma senha temporária. Por segurança, defina uma nova senha antes de continuar.</p>
        </div>

        <div class="change-body">
            <?php if (!empty($erro)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($erro); ?>
                </div>
            <?php endif; ?>

            <div class="alert alert-warning">
                <i class="fas fa-shield-alt"></i>
                <strong>Importante:</strong> Você não poderá acessar o portal até definir uma nova senha permanente.
            </div>

            <div class="user-info">
                <strong><?php echo htmlspecialchars($colaboradorData['nome']); ?></strong>
                <small><?php echo htmlspecialchars($colaboradorData['email']); ?></small>
            </div>

            <form method="POST" action="" id="formTrocarSenha">
                <div class="form-group">
                    <label for="senha_atual">
                        <i class="fas fa-key"></i>
                        Senha Temporária Atual
                    </label>
                    <div class="input-wrapper">
                        <input
                            type="password"
                            id="senha_atual"
                            name="senha_atual"
                            placeholder="Digite a senha temporária"
                            required
                            autofocus
                        >
                        <i class="fas fa-eye toggle-password" data-target="senha_atual"></i>
                    </div>
                </div>

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
                        >
                        <i class="fas fa-eye toggle-password" data-target="nova_senha"></i>
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
                    <strong>Requisitos da nova senha:</strong>
                    <ul>
                        <li id="req-length">Mínimo de 6 caracteres</li>
                        <li id="req-different">Diferente da senha temporária</li>
                        <li id="req-match">As senhas devem ser idênticas</li>
                    </ul>
                </div>

                <button type="submit" name="trocar_senha" class="btn-change" id="btnSubmit">
                    <i class="fas fa-check"></i>
                    Alterar Senha e Continuar
                </button>
            </form>

            <div class="logout-link">
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    Sair do Portal
                </a>
            </div>
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

        // Validação de requisitos
        const senhaAtualInput = document.getElementById('senha_atual');
        const novaSenhaInput = document.getElementById('nova_senha');
        const confirmarSenhaInput = document.getElementById('confirmar_senha');
        const btnSubmit = document.getElementById('btnSubmit');
        const reqLength = document.getElementById('req-length');
        const reqDifferent = document.getElementById('req-different');
        const reqMatch = document.getElementById('req-match');

        function validateForm() {
            const senhaAtual = senhaAtualInput.value;
            const novaSenha = novaSenhaInput.value;
            const confirmarSenha = confirmarSenhaInput.value;
            let isValid = true;

            // Requisito 1: Mínimo de 6 caracteres
            if (novaSenha.length >= 6) {
                reqLength.classList.add('requirement-met');
            } else {
                reqLength.classList.remove('requirement-met');
                isValid = false;
            }

            // Requisito 2: Diferente da senha temporária
            if (senhaAtual.length > 0 && novaSenha.length > 0) {
                if (senhaAtual !== novaSenha) {
                    reqDifferent.classList.add('requirement-met');
                } else {
                    reqDifferent.classList.remove('requirement-met');
                    isValid = false;
                }
            }

            // Requisito 3: Senhas idênticas
            if (confirmarSenha.length > 0) {
                if (novaSenha === confirmarSenha) {
                    reqMatch.classList.add('requirement-met');
                } else {
                    reqMatch.classList.remove('requirement-met');
                    isValid = false;
                }
            }

            // Habilita/desabilita botão
            btnSubmit.disabled = !isValid;
        }

        senhaAtualInput.addEventListener('input', validateForm);
        novaSenhaInput.addEventListener('input', validateForm);
        confirmarSenhaInput.addEventListener('input', validateForm);

        // Animação ao submeter
        const form = document.getElementById('formTrocarSenha');
        form.addEventListener('submit', function() {
            btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Alterando...';
            btnSubmit.disabled = true;
        });
    </script>
</body>
</html>
