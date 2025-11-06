<?php
/**
 * CORREÇÃO: Collation das tabelas de setores
 * Execute para corrigir incompatibilidade de collation
 */

define('SGC_SYSTEM', true);
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/classes/Database.php';
require_once __DIR__ . '/../app/classes/Auth.php';

// Requer admin
Auth::requireLogin();
Auth::requireAdmin();

$db = Database::getInstance();
$pdo = $db->getConnection();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corrigir Collation - Setores</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2d3748;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        .status {
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover {
            background: #5568d3;
        }
        .btn-success {
            background: #28a745;
        }
        .btn-success:hover {
            background: #218838;
        }
        pre {
            background: #2d3748;
            color: #68d391;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Correção de Collation</h1>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['executar'])) {
            echo '<h2>Executando correções...</h2>';
            echo '<pre>';

            try {
                // 1. Corrige collation de unidade_setores.setor
                echo "1. Corrigindo collation de unidade_setores.setor...\n";
                try {
                    $pdo->exec("
                        ALTER TABLE unidade_setores
                        MODIFY COLUMN setor VARCHAR(100)
                        CHARACTER SET utf8mb4
                        COLLATE utf8mb4_unicode_ci
                        NOT NULL
                        COMMENT 'Nome do setor (referencia field_categories.valor)'
                    ");
                    echo "✅ Collation de unidade_setores.setor corrigida!\n\n";
                } catch (PDOException $e) {
                    echo "⚠️  " . $e->getMessage() . "\n\n";
                }

                // 2. Corrige collation de colaboradores.setor_principal (se existir)
                echo "2. Verificando e corrigindo colaboradores.setor_principal...\n";
                try {
                    $stmt = $pdo->query("SHOW COLUMNS FROM colaboradores LIKE 'setor_principal'");
                    if ($stmt->rowCount() > 0) {
                        $pdo->exec("
                            ALTER TABLE colaboradores
                            MODIFY COLUMN setor_principal VARCHAR(100)
                            CHARACTER SET utf8mb4
                            COLLATE utf8mb4_unicode_ci
                        ");
                        echo "✅ Collation de colaboradores.setor_principal corrigida!\n\n";
                    } else {
                        echo "⚠️  Coluna setor_principal não existe (OK, pode não estar migrado ainda)\n\n";
                    }
                } catch (PDOException $e) {
                    echo "⚠️  " . $e->getMessage() . "\n\n";
                }

                // 3. Verifica resultado
                echo "3. Verificando resultado...\n";
                $stmt = $pdo->query("
                    SELECT COLUMN_NAME, COLLATION_NAME
                    FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'unidade_setores'
                    AND COLUMN_NAME = 'setor'
                ");
                $col = $stmt->fetch(PDO::FETCH_ASSOC);
                echo "   unidade_setores.setor: {$col['COLLATION_NAME']}\n";

                $stmt = $pdo->query("
                    SELECT COLUMN_NAME, COLLATION_NAME
                    FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'field_categories'
                    AND COLUMN_NAME = 'valor'
                ");
                $col = $stmt->fetch(PDO::FETCH_ASSOC);
                echo "   field_categories.valor: {$col['COLLATION_NAME']}\n\n";

                echo "==================================================\n";
                echo "✅ CORREÇÃO CONCLUÍDA!\n";
                echo "==================================================\n";
                echo "</pre>";

                echo '<div class="status success">';
                echo '<strong>✅ Collation corrigida com sucesso!</strong><br>';
                echo 'As tabelas agora usam a mesma collation (utf8mb4_unicode_ci).<br>';
                echo 'O erro de collation não deve mais aparecer.';
                echo '</div>';

                echo '<div style="margin-top: 20px;">';
                echo '<a href="/public/unidades/setores_globais/listar.php" class="btn btn-success">✅ Testar Setores Globais</a>';
                echo '<a href="/public/dashboard.php" class="btn">🏠 Dashboard</a>';
                echo '</div>';

            } catch (Exception $e) {
                echo "</pre>";
                echo '<div class="status error">';
                echo '<strong>❌ Erro ao executar correção:</strong><br>';
                echo htmlspecialchars($e->getMessage());
                echo '</div>';
            }

        } else {
            // Mostra status atual
            echo '<div class="status error">';
            echo '<strong>❌ Problema: Incompatibilidade de Collation</strong><br>';
            echo 'Erro: <code>Illegal mix of collations (utf8mb4_uca1400_ai_ci,IMPLICIT) and (utf8mb4_unicode_ci,IMPLICIT)</code><br><br>';
            echo 'As tabelas <code>unidade_setores</code> e <code>field_categories</code> estão usando collations diferentes.';
            echo '</div>';

            // Verifica collations atuais
            try {
                echo '<h2>Collations Atuais:</h2>';
                echo '<div class="info">';

                $stmt = $pdo->query("
                    SELECT COLUMN_NAME, COLLATION_NAME
                    FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'unidade_setores'
                    AND COLUMN_NAME = 'setor'
                ");
                $col = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($col) {
                    echo "<strong>unidade_setores.setor:</strong> {$col['COLLATION_NAME']}<br>";
                }

                $stmt = $pdo->query("
                    SELECT COLUMN_NAME, COLLATION_NAME
                    FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'field_categories'
                    AND COLUMN_NAME = 'valor'
                ");
                $col = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($col) {
                    echo "<strong>field_categories.valor:</strong> {$col['COLLATION_NAME']}<br>";
                }

                echo '</div>';
            } catch (Exception $e) {
                echo '<div class="status warning">Não foi possível verificar collations: ' . $e->getMessage() . '</div>';
            }

            echo '<h2>O que será feito:</h2>';
            echo '<ul>';
            echo '<li>✅ Alterar collation de <code>unidade_setores.setor</code> para <strong>utf8mb4_unicode_ci</strong></li>';
            echo '<li>✅ Alterar collation de <code>colaboradores.setor_principal</code> para <strong>utf8mb4_unicode_ci</strong> (se existir)</li>';
            echo '<li>✅ Garantir compatibilidade com <code>field_categories.valor</code></li>';
            echo '</ul>';

            echo '<div class="status warning">';
            echo '<strong>⚠️ Importante:</strong> Esta operação irá alterar a estrutura das tabelas.<br>';
            echo 'Recomenda-se fazer backup antes de executar.';
            echo '</div>';

            echo '<form method="POST" action="">';
            echo '<input type="hidden" name="executar" value="1">';
            echo '<button type="submit" class="btn btn-success" style="font-size: 18px; padding: 15px 40px;">🔧 EXECUTAR CORREÇÃO AGORA</button>';
            echo '</form>';

            echo '<div style="margin-top: 30px;">';
            echo '<a href="/public/dashboard.php" class="btn">🏠 Voltar ao Dashboard</a>';
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>
