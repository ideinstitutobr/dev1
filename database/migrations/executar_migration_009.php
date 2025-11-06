<?php
/**
 * Script para executar a migration 009 (correção field_categories)
 */

define('SGC_SYSTEM', true);
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Database.php';

echo "<h2>Executando Migration 009 - Correção field_categories</h2>";
echo "<pre>";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    echo "Iniciando correção da tabela field_categories...\n\n";

    // Lê o arquivo SQL
    $sql = file_get_contents(__DIR__ . '/009_fix_field_categories_setores.sql');

    // Remove comentários
    $sql = preg_replace('/--.*$/m', '', $sql);

    // Divide em statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt);
        }
    );

    echo "Total de comandos a executar: " . count($statements) . "\n\n";

    foreach ($statements as $i => $statement) {
        echo "Executando comando " . ($i + 1) . "...\n";
        try {
            $pdo->exec($statement);
            echo "✅ Sucesso!\n\n";
        } catch (PDOException $e) {
            // Ignora erros de "já existe"
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "⚠️  Coluna já existe (ignorado)\n\n";
            } else {
                echo "❌ Erro: " . $e->getMessage() . "\n\n";
            }
        }
    }

    echo "==================================================\n";
    echo "Verificando estrutura da tabela...\n";
    echo "==================================================\n";

    $stmt = $pdo->query("DESCRIBE field_categories");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($columns as $col) {
        echo "{$col['Field']} - {$col['Type']}\n";
    }

    echo "\n==================================================\n";
    echo "✅ Migration 009 concluída!\n";
    echo "==================================================\n";

} catch (Exception $e) {
    echo "❌ ERRO FATAL: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

echo "</pre>";
echo "<p><a href='../../public/unidades/setores_globais/cadastrar.php'>Testar Cadastro de Setor</a></p>";
