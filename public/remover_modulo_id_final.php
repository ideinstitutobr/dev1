<?php
/**
 * Script FINAL - Remover coluna modulo_id
 * Remove definitivamente a coluna modulo_id da tabela checklists
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/classes/Database.php';

echo "<h1>🔧 Correção Final - Remover modulo_id</h1>";
echo "<hr>";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    echo "<h2>1. Verificando se modulo_id existe...</h2>";

    $stmt = $pdo->query("SHOW COLUMNS FROM checklists LIKE 'modulo_id'");

    if ($stmt->rowCount() === 0) {
        echo "<p style='color: green; font-size: 18px;'><strong>✅ Coluna modulo_id já foi removida!</strong></p>";
        echo "<p>O sistema está pronto para uso.</p>";
        echo "<hr>";
        echo "<p><a href='checklist/novo.php'>← Criar Nova Avaliação</a></p>";
        exit;
    }

    echo "<p style='color: orange;'><strong>⚠️ Coluna modulo_id encontrada. Procedendo com remoção...</strong></p>";

    echo "<h2>2. Verificando foreign keys associadas...</h2>";

    // Buscar todas as FKs que usam modulo_id
    $stmt = $pdo->query("
        SELECT CONSTRAINT_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'checklists'
        AND COLUMN_NAME = 'modulo_id'
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");

    $fks = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($fks)) {
        echo "<p>Foreign keys encontradas: <strong>" . implode(', ', $fks) . "</strong></p>";

        foreach ($fks as $fkName) {
            echo "<p><strong>Removendo FK '{$fkName}'...</strong> ";
            try {
                $pdo->exec("ALTER TABLE checklists DROP FOREIGN KEY {$fkName}");
                echo "<span style='color: green;'>✅ OK</span></p>";
            } catch (PDOException $e) {
                echo "<span style='color: red;'>❌ ERRO: " . $e->getMessage() . "</span></p>";
            }
        }
    } else {
        echo "<p style='color: blue;'>ℹ️ Nenhuma foreign key encontrada.</p>";
    }

    echo "<h2>3. Verificando índices associados...</h2>";

    $stmt = $pdo->query("SHOW INDEX FROM checklists WHERE Column_name = 'modulo_id'");
    $indices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($indices)) {
        foreach ($indices as $index) {
            $indexName = $index['Key_name'];
            if ($indexName !== 'PRIMARY') {
                echo "<p><strong>Removendo índice '{$indexName}'...</strong> ";
                try {
                    $pdo->exec("ALTER TABLE checklists DROP INDEX {$indexName}");
                    echo "<span style='color: green;'>✅ OK</span></p>";
                } catch (PDOException $e) {
                    echo "<span style='color: orange;'>⚠️ " . $e->getMessage() . "</span></p>";
                }
            }
        }
    } else {
        echo "<p style='color: blue;'>ℹ️ Nenhum índice específico encontrado.</p>";
    }

    echo "<h2>4. Removendo coluna modulo_id...</h2>";

    try {
        $pdo->exec("ALTER TABLE checklists DROP COLUMN modulo_id");
        echo "<p style='color: green; font-size: 18px;'><strong>✅ Coluna modulo_id removida com sucesso!</strong></p>";
    } catch (PDOException $e) {
        echo "<p style='color: red;'><strong>❌ ERRO ao remover coluna:</strong></p>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<hr>";
        echo "<h3>🔍 Diagnóstico do Erro:</h3>";

        // Verificar se há constraint UNIQUE usando modulo_id
        $stmt = $pdo->query("
            SELECT CONSTRAINT_NAME
            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc
            JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
                ON tc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
            WHERE tc.TABLE_SCHEMA = DATABASE()
            AND tc.TABLE_NAME = 'checklists'
            AND kcu.COLUMN_NAME = 'modulo_id'
            AND tc.CONSTRAINT_TYPE = 'UNIQUE'
        ");

        $uniqueConstraints = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($uniqueConstraints)) {
            echo "<p style='color: red;'>❌ Encontrada constraint UNIQUE usando modulo_id:</p>";
            echo "<ul>";
            foreach ($uniqueConstraints as $constraint) {
                echo "<li><strong>{$constraint}</strong></li>";
            }
            echo "</ul>";

            echo "<h4>Tentando remover constraints UNIQUE...</h4>";

            foreach ($uniqueConstraints as $constraint) {
                echo "<p><strong>Removendo '{$constraint}'...</strong> ";
                try {
                    $pdo->exec("ALTER TABLE checklists DROP INDEX {$constraint}");
                    echo "<span style='color: green;'>✅ OK</span></p>";
                } catch (PDOException $e2) {
                    echo "<span style='color: red;'>❌ " . $e2->getMessage() . "</span></p>";
                }
            }

            echo "<p><strong>Tentando remover modulo_id novamente...</strong> ";
            try {
                $pdo->exec("ALTER TABLE checklists DROP COLUMN modulo_id");
                echo "<span style='color: green; font-size: 16px;'>✅ SUCESSO!</span></p>";
            } catch (PDOException $e3) {
                echo "<span style='color: red;'>❌ " . $e3->getMessage() . "</span></p>";
                echo "<p>Por favor, execute este SQL manualmente no phpMyAdmin:</p>";
                echo "<pre style='background: #f5f5f5; padding: 15px; border-radius: 5px;'>";
                echo "ALTER TABLE checklists DROP COLUMN modulo_id;";
                echo "</pre>";
            }
        }

        exit;
    }

    echo "<h2>5. Verificação Final...</h2>";

    $stmt = $pdo->query("SHOW COLUMNS FROM checklists LIKE 'modulo_id'");

    if ($stmt->rowCount() === 0) {
        echo "<p style='color: green; font-size: 18px;'><strong>✅ Confirmado: modulo_id foi removida com sucesso!</strong></p>";
    } else {
        echo "<p style='color: red;'><strong>❌ ERRO: modulo_id ainda existe!</strong></p>";
    }

    echo "<hr>";
    echo "<h2 style='color: green;'>✅ Processo Concluído!</h2>";
    echo "<p style='font-size: 16px;'>Agora você pode criar novas avaliações sem problemas!</p>";
    echo "<hr>";
    echo "<p><a href='verificar_sistema_checklist.php' style='padding: 15px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;'>🔍 Verificar Sistema Novamente</a></p>";
    echo "<p><a href='checklist/novo.php' style='padding: 15px 30px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold; margin-top: 10px;'>📝 Criar Nova Avaliação</a></p>";

} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Erro Fatal</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
