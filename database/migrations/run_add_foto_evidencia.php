<?php
/**
 * Script para executar migration: add_foto_evidencia_to_respostas
 */

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/classes/Database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    echo "Executando migration: add_foto_evidencia_to_respostas.sql\n";
    echo "=======================================================\n\n";

    // Verificar se a coluna já existe
    $stmt = $pdo->query("SHOW COLUMNS FROM respostas_checklist LIKE 'foto_evidencia'");
    $columnExists = $stmt->fetch();

    if ($columnExists) {
        echo "✓ Coluna 'foto_evidencia' já existe na tabela respostas_checklist\n";
        echo "Migration já foi executada anteriormente.\n";
        exit(0);
    }

    // Adicionar coluna foto_evidencia
    $sql = "ALTER TABLE respostas_checklist
            ADD COLUMN foto_evidencia VARCHAR(255) NULL AFTER observacao";

    $pdo->exec($sql);
    echo "✓ Coluna 'foto_evidencia' adicionada com sucesso\n";

    // Criar índice
    $sql = "CREATE INDEX idx_foto_evidencia ON respostas_checklist(foto_evidencia)";
    $pdo->exec($sql);
    echo "✓ Índice 'idx_foto_evidencia' criado com sucesso\n";

    echo "\n=======================================================\n";
    echo "Migration executada com sucesso!\n";

} catch (PDOException $e) {
    echo "✗ Erro ao executar migration: " . $e->getMessage() . "\n";
    exit(1);
}
