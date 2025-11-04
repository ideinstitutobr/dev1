<?php
/**
 * Instalador: Adiciona coluna email_destinatario na tabela notificacoes
 * Execute este arquivo uma única vez via navegador
 */

// Define constante do sistema
define('SGC_SYSTEM', true);

// Carrega configurações
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/classes/Database.php';

// Conecta ao banco usando Singleton
$db = Database::getInstance();
$pdo = $db->getConnection();

$instalado = false;
$erros = [];
$sucessos = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['instalar'])) {
    try {
        $sucessos[] = "Iniciando correção da tabela notificacoes...";

        // Verifica se a coluna já existe
        $checkColumn = $pdo->query("SHOW COLUMNS FROM notificacoes LIKE 'email_destinatario'")->fetch();

        if (!$checkColumn) {
            // Adiciona a coluna email_destinatario
            // Nota: ALTER TABLE faz commit implícito, não precisa de transação
            $sql = "ALTER TABLE notificacoes
                    ADD COLUMN email_destinatario VARCHAR(150) NOT NULL
                    AFTER tipo";
            $pdo->exec($sql);
            $sucessos[] = "✅ Coluna 'email_destinatario' adicionada com sucesso";
        } else {
            $sucessos[] = "ℹ️ Coluna 'email_destinatario' já existe";
        }

        $instalado = true;
        $sucessos[] = "🎉 CORREÇÃO CONCLUÍDA COM SUCESSO!";

    } catch (Exception $e) {
        $erros[] = "❌ Erro durante a correção: " . $e->getMessage();
        $erros[] = "Detalhes: " . $e->getFile() . " linha " . $e->getLine();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corrigir Tabela Notificações - SGC</title>
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

        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 700px;
            width: 100%;
            padding: 40px;
        }

        h1 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 5px;
        }

        .info-box h3 {
            color: #1976D2;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .info-box ul {
            margin-left: 20px;
            color: #555;
        }

        .info-box li {
            margin: 5px 0;
        }

        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ff9800;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 5px;
            color: #856404;
        }

        .success-box {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
        }

        .success-box p {
            color: #155724;
            margin: 5px 0;
        }

        .error-box {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
        }

        .error-box p {
            color: #721c24;
            margin: 5px 0;
        }

        .btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s;
            margin-top: 20px;
        }

        .btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .code {
            background: #f5f5f5;
            padding: 3px 8px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            color: #d63384;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Corrigir Tabela Notificações</h1>
        <p class="subtitle">Adiciona a coluna <span class="code">email_destinatario</span> na tabela notificacoes</p>

        <?php if (!$instalado && empty($erros)): ?>
            <div class="info-box">
                <h3>📋 O que este instalador faz:</h3>
                <ul>
                    <li>Adiciona a coluna <strong>email_destinatario</strong> na tabela <strong>notificacoes</strong></li>
                    <li>Corrige o erro "Column not found: 1054 Unknown column 'email_destinatario'"</li>
                    <li>Permite o envio de convites por e-mail funcionar corretamente</li>
                </ul>
            </div>

            <div class="warning-box">
                ⚠️ <strong>Importante:</strong> Este instalador só precisa ser executado uma única vez.
            </div>

            <form method="POST">
                <button type="submit" name="instalar" class="btn">
                    ▶️ Executar Correção
                </button>
            </form>

        <?php endif; ?>

        <?php if (!empty($sucessos)): ?>
            <div class="success-box">
                <?php foreach ($sucessos as $sucesso): ?>
                    <p><?php echo htmlspecialchars($sucesso); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($erros)): ?>
            <div class="error-box">
                <?php foreach ($erros as $erro): ?>
                    <p><?php echo htmlspecialchars($erro); ?></p>
                <?php endforeach; ?>
            </div>
            <form method="POST">
                <button type="submit" name="instalar" class="btn">
                    🔄 Tentar Novamente
                </button>
            </form>
        <?php endif; ?>

        <?php if ($instalado): ?>
            <a href="treinamentos/listar.php" class="btn">
                ✅ Voltar para Treinamentos
            </a>
        <?php endif; ?>
    </div>
</body>
</html>
