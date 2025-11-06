<?php
/**
 * Instalador: Tabela de Categorias de Campos
 * Cria tabela para armazenar cargos, departamentos e setores no banco de dados
 *
 * Este script resolve o problema de perda de dados em deploys,
 * migrando de field_catalog.json para o banco de dados.
 */

// Define constante do sistema
define('SGC_SYSTEM', true);

// Carrega configurações
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/classes/Database.php';
require_once __DIR__ . '/../app/classes/Auth.php';

// Verifica autenticação
Auth::requireLogin(BASE_URL);

// Obtém conexão
$db = Database::getInstance();
$pdo = $db->getConnection();

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // 1. Cria tabela de categorias
        $sql = "CREATE TABLE IF NOT EXISTS field_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tipo ENUM('cargo', 'departamento') NOT NULL COMMENT 'Tipo de categoria',
            valor VARCHAR(100) NOT NULL COMMENT 'Nome da categoria',
            ativo TINYINT(1) DEFAULT 1 COMMENT 'Se está ativo',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_tipo_valor (tipo, valor),
            KEY idx_tipo (tipo),
            KEY idx_ativo (ativo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='Armazena categorias de campos (cargos, departamentos, setores)'";

        $pdo->exec($sql);

        $message .= "✅ Tabela 'field_categories' criada com sucesso!\n\n";

        // 2. Migra dados do JSON para o banco (se existir)
        $catalogPath = __DIR__ . '/../app/config/field_catalog.json';
        if (file_exists($catalogPath)) {
            $catalog = json_decode(file_get_contents($catalogPath), true);

            $migratedCount = 0;

            // Migra cargos
            if (isset($catalog['cargos']) && is_array($catalog['cargos'])) {
                $stmt = $pdo->prepare("INSERT IGNORE INTO field_categories (tipo, valor) VALUES ('cargo', ?)");
                foreach ($catalog['cargos'] as $cargo) {
                    if (!empty($cargo)) {
                        $stmt->execute([$cargo]);
                        if ($stmt->rowCount() > 0) $migratedCount++;
                    }
                }
            }

            // Migra departamentos
            if (isset($catalog['departamentos']) && is_array($catalog['departamentos'])) {
                $stmt = $pdo->prepare("INSERT IGNORE INTO field_categories (tipo, valor) VALUES ('departamento', ?)");
                foreach ($catalog['departamentos'] as $dep) {
                    if (!empty($dep)) {
                        $stmt->execute([$dep]);
                        if ($stmt->rowCount() > 0) $migratedCount++;
                    }
                }
            }

            $message .= "✅ Migrados {$migratedCount} itens do JSON para o banco!\n\n";
        }

        // 3. Migra dados existentes dos colaboradores
        $stmt = $pdo->prepare("INSERT IGNORE INTO field_categories (tipo, valor) VALUES ('cargo', ?)");
        $cargos = $pdo->query("SELECT DISTINCT cargo FROM colaboradores WHERE cargo IS NOT NULL AND cargo <> ''")->fetchAll(PDO::FETCH_COLUMN);
        $cargosMigrados = 0;
        foreach ($cargos as $cargo) {
            $stmt->execute([$cargo]);
            if ($stmt->rowCount() > 0) $cargosMigrados++;
        }

        $stmt = $pdo->prepare("INSERT IGNORE INTO field_categories (tipo, valor) VALUES ('departamento', ?)");
        $deps = $pdo->query("SELECT DISTINCT departamento FROM colaboradores WHERE departamento IS NOT NULL AND departamento <> ''")->fetchAll(PDO::FETCH_COLUMN);
        $depsMigrados = 0;
        foreach ($deps as $dep) {
            $stmt->execute([$dep]);
            if ($stmt->rowCount() > 0) $depsMigrados++;
        }

        $message .= "✅ Migrados {$cargosMigrados} cargos e {$depsMigrados} departamentos dos colaboradores!\n\n";

        $pdo->commit();

        $message .= "🎉 Instalação concluída com sucesso!\n\n";
        $message .= "Agora os dados das categorias estão salvos no banco de dados e não serão perdidos em deploys.\n";

        $success = true;

    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "❌ Erro: " . $e->getMessage();
    }
}

// Verifica se já está instalado
$installed = false;
try {
    $result = $pdo->query("SHOW TABLES LIKE 'field_categories'")->fetch();
    $installed = !empty($result);
} catch (Exception $e) {}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalar Categorias no Banco</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .info-box {
            background: #f0f4ff;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .info-box h3 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 16px;
        }
        .info-box ul {
            margin-left: 20px;
            color: #555;
            line-height: 1.8;
        }
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .success-box {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .error-box {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.3);
        }
        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .message {
            white-space: pre-line;
            font-family: monospace;
            font-size: 13px;
        }
        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .status.installed {
            background: #d4edda;
            color: #155724;
        }
        .status.not-installed {
            background: #fff3cd;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗄️ Instalar Categorias no Banco</h1>
        <p class="subtitle">Migração de Dados de Campos (Cargo e Setor)</p>

        <?php if ($installed): ?>
            <div class="status installed">✅ Tabela já instalada</div>
        <?php else: ?>
            <div class="status not-installed">⚠️ Tabela não instalada</div>
        <?php endif; ?>

        <?php if (!empty($message)): ?>
            <?php if ($success): ?>
                <div class="success-box">
                    <div class="message"><?php echo htmlspecialchars($message); ?></div>
                </div>
            <?php else: ?>
                <div class="error-box">
                    <div class="message"><?php echo htmlspecialchars($message); ?></div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="info-box">
            <h3>📋 O que este instalador faz:</h3>
            <ul>
                <li>Cria tabela <code>field_categories</code> no banco de dados</li>
                <li>Migra dados do arquivo JSON (se existir)</li>
                <li>Migra cargos e departamentos já cadastrados</li>
                <li>Garante que dados não sejam perdidos em deploys</li>
            </ul>
        </div>

        <div class="warning-box">
            <strong>⚠️ Importante:</strong> Após instalar, o sistema usará o banco de dados como fonte principal. O arquivo JSON não será mais necessário.
        </div>

        <?php if (!$success): ?>
            <form method="POST" style="margin-top: 20px;">
                <button type="submit" class="btn" <?php echo $installed ? 'disabled' : ''; ?>>
                    <?php echo $installed ? '✅ Já Instalado' : '🚀 Instalar Agora'; ?>
                </button>
                <a href="<?php echo BASE_URL; ?>colaboradores/config_campos.php" class="btn btn-secondary" style="margin-left: 10px;">
                    ← Voltar
                </a>
            </form>
        <?php else: ?>
            <a href="<?php echo BASE_URL; ?>colaboradores/config_campos.php" class="btn">
                ✅ Ir para Configuração de Campos
            </a>
        <?php endif; ?>
    </div>
</body>
</html>
