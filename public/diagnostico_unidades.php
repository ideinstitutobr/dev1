<?php
define('SGC_SYSTEM', true);
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/classes/Database.php';
require_once __DIR__ . '/../app/classes/Auth.php';

Auth::requireLogin();
Auth::requireLevel(['admin']);

header('Content-Type: text/plain; charset=utf-8');

echo "=== DIAGNÓSTICO DO BANCO DE DADOS ===\n\n";

try {
    $pdo = Database::getInstance()->getConnection();

    // 1. Verifica tabela treinamentos
    echo "1. ESTRUTURA DA TABELA TREINAMENTOS:\n";
    echo str_repeat("-", 50) . "\n";

    try {
        $stmt = $pdo->query("DESCRIBE treinamentos");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo sprintf("%-30s %-20s\n", $row['Field'], $row['Type']);
        }
    } catch (PDOException $e) {
        echo "ERRO: " . $e->getMessage() . "\n";
    }

    echo "\n2. ESTRUTURA DA TABELA COLABORADORES:\n";
    echo str_repeat("-", 50) . "\n";

    try {
        $stmt = $pdo->query("DESCRIBE colaboradores");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo sprintf("%-30s %-20s\n", $row['Field'], $row['Type']);
        }
    } catch (PDOException $e) {
        echo "ERRO: " . $e->getMessage() . "\n";
    }

    echo "\n3. TABELAS EXISTENTES NO BANCO:\n";
    echo str_repeat("-", 50) . "\n";

    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        echo "- $table\n";
    }

    echo "\n4. TABELAS DE UNIDADES:\n";
    echo str_repeat("-", 50) . "\n";

    $unidadesTables = ['categorias_local_unidade', 'unidades', 'unidade_setores', 'unidade_colaboradores', 'unidade_lideranca'];
    foreach ($unidadesTables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->fetch()) {
            $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            echo "✅ $table ($count registros)\n";
        } else {
            echo "❌ $table (não existe)\n";
        }
    }

    echo "\n5. FOREIGN KEYS EM COLABORADORES:\n";
    echo str_repeat("-", 50) . "\n";

    try {
        $stmt = $pdo->query("
            SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'colaboradores'
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "- {$row['CONSTRAINT_NAME']} -> {$row['REFERENCED_TABLE_NAME']}({$row['REFERENCED_COLUMN_NAME']})\n";
        }
    } catch (PDOException $e) {
        echo "ERRO: " . $e->getMessage() . "\n";
    }

    echo "\n6. SETORES NO FIELD_CATEGORIES:\n";
    echo str_repeat("-", 50) . "\n";

    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM field_categories WHERE tipo = 'setor'");
        $count = $stmt->fetchColumn();
        echo "Total de setores: $count\n";

        if ($count > 0) {
            $stmt = $pdo->query("SELECT valor FROM field_categories WHERE tipo = 'setor' AND ativo = 1 ORDER BY valor");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "- {$row['valor']}\n";
            }
        }
    } catch (PDOException $e) {
        echo "ERRO: " . $e->getMessage() . "\n";
    }

} catch (Exception $e) {
    echo "ERRO FATAL: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
