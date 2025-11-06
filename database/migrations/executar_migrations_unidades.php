<?php
/**
 * Script para executar migrations do sistema de Unidades
 * Execute via navegador ou linha de comando
 */

define('SGC_SYSTEM', true);

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Database.php';

echo "<h2>Executando Migrations - Sistema de Unidades</h2>";
echo "<pre>";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // Lista de migrations na ordem correta
    $migrations = [
        '001_create_categorias_local_unidade.sql',
        '002_create_unidades.sql',
        '003_create_unidade_setores.sql',
        '004_create_unidade_colaboradores.sql',
        '005_create_unidade_lideranca.sql',
        '006_alter_colaboradores_add_unidade.sql',
        '007_alter_treinamentos_add_unidade.sql',
        '008_populate_setores_iniciais.sql',
        '009_fix_field_categories_setores.sql',
        '010_fix_collation_unidade_setores.sql'
    ];

    echo "Iniciando execução de " . count($migrations) . " migrations...\n\n";

    foreach ($migrations as $migration) {
        echo "==================================================\n";
        echo "Executando: $migration\n";
        echo "==================================================\n";

        $filePath = __DIR__ . '/' . $migration;

        if (!file_exists($filePath)) {
            echo "❌ ERRO: Arquivo não encontrado: $migration\n\n";
            continue;
        }

        $sql = file_get_contents($filePath);

        if (empty($sql)) {
            echo "❌ ERRO: Arquivo vazio: $migration\n\n";
            continue;
        }

        // Divide o SQL em múltiplos statements
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($stmt) {
                return !empty($stmt) && !preg_match('/^\s*--/', $stmt);
            }
        );

        $success = true;
        foreach ($statements as $statement) {
            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                // Ignora erros de "já existe" para permitir re-execução
                if (strpos($e->getMessage(), 'already exists') !== false ||
                    strpos($e->getMessage(), 'Duplicate') !== false) {
                    echo "⚠️  Aviso: " . $e->getMessage() . "\n";
                } else {
                    echo "❌ ERRO: " . $e->getMessage() . "\n";
                    $success = false;
                    break;
                }
            }
        }

        if ($success) {
            echo "✅ Executado com sucesso!\n\n";
        } else {
            echo "❌ Falhou na execução\n\n";
        }
    }

    echo "==================================================\n";
    echo "✅ Processo concluído!\n";
    echo "==================================================\n";

    // Verifica se as tabelas foram criadas
    echo "\nVerificando tabelas criadas:\n";
    $tables = [
        'categorias_local_unidade',
        'unidades',
        'unidade_setores',
        'unidade_colaboradores',
        'unidade_lideranca'
    ];

    foreach ($tables as $table) {
        $result = $pdo->query("SHOW TABLES LIKE '$table'")->fetch();
        if ($result) {
            echo "✅ $table - OK\n";
        } else {
            echo "❌ $table - NÃO ENCONTRADA\n";
        }
    }

    // Conta registros iniciais
    echo "\nDados iniciais:\n";
    $count = $pdo->query("SELECT COUNT(*) FROM categorias_local_unidade")->fetchColumn();
    echo "- Categorias de Local: $count\n";

    $count = $pdo->query("SELECT COUNT(*) FROM field_categories WHERE tipo = 'setor'")->fetchColumn();
    echo "- Setores no field_categories: $count\n";

} catch (Exception $e) {
    echo "❌ ERRO FATAL: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

echo "</pre>";
echo "<p><a href='../../public/unidades/listar.php'>Ir para Unidades</a></p>";
