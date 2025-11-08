<?php
/**
 * Script de Migração: Refatorar Checklists
 * Remove modulo_id e adiciona responsavel_id
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/classes/Database.php';

echo "<h1>🔄 Migração: Refatorar Checklists para Todos os Módulos</h1>";
echo "<hr>";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // Verificar se a migração já foi executada
    $stmt = $pdo->query("SHOW COLUMNS FROM checklists LIKE 'responsavel_id'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: orange;'><strong>⚠️ Migração já foi executada!</strong></p>";
        echo "<p>A coluna 'responsavel_id' já existe na tabela checklists.</p>";
        exit;
    }

    echo "<h2>Executando comandos SQL...</h2>";

    $comandos = [
        "1. Adicionar coluna responsavel_id" => "
            ALTER TABLE checklists
            ADD COLUMN responsavel_id INT NULL
            COMMENT 'Liderança responsável pela unidade (gerente/supervisor)'
            AFTER colaborador_id
        ",

        "2. Adicionar foreign key para responsavel_id" => "
            ALTER TABLE checklists
            ADD CONSTRAINT fk_checklist_responsavel
            FOREIGN KEY (responsavel_id) REFERENCES colaboradores(id)
            ON DELETE RESTRICT ON UPDATE CASCADE
        ",

        "3. Criar índice para responsavel_id" => "
            CREATE INDEX idx_responsavel_id ON checklists(responsavel_id)
        ",

        "4. Descobrir nome da constraint de modulo_id" => "
            SELECT CONSTRAINT_NAME INTO @constraint_name
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'checklists'
            AND COLUMN_NAME = 'modulo_id'
            AND REFERENCED_TABLE_NAME IS NOT NULL
            LIMIT 1
        ",
    ];

    foreach ($comandos as $descricao => $sql) {
        try {
            $pdo->exec($sql);
            echo "<p><strong>{$descricao}:</strong> <span style='color: green;'>✅ OK</span></p>";
        } catch (PDOException $e) {
            echo "<p><strong>{$descricao}:</strong> <span style='color: red;'>❌ ERRO: {$e->getMessage()}</span></p>";
        }
    }

    // Remover FK constraint de modulo_id (dinâmico)
    echo "<p><strong>5. Remover foreign key de modulo_id:</strong> ";
    try {
        $stmt = $pdo->query("
            SELECT CONSTRAINT_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'checklists'
            AND COLUMN_NAME = 'modulo_id'
            AND REFERENCED_TABLE_NAME IS NOT NULL
            LIMIT 1
        ");

        $constraint = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($constraint) {
            $constraintName = $constraint['CONSTRAINT_NAME'];
            $pdo->exec("ALTER TABLE checklists DROP FOREIGN KEY {$constraintName}");
            echo "<span style='color: green;'>✅ OK (constraint: {$constraintName})</span></p>";
        } else {
            echo "<span style='color: blue;'>⚠️ Nenhuma FK encontrada</span></p>";
        }
    } catch (PDOException $e) {
        echo "<span style='color: red;'>❌ ERRO: {$e->getMessage()}</span></p>";
    }

    // Remover coluna modulo_id
    echo "<p><strong>6. Remover coluna modulo_id:</strong> ";
    try {
        $pdo->exec("ALTER TABLE checklists DROP COLUMN modulo_id");
        echo "<span style='color: green;'>✅ OK</span></p>";
    } catch (PDOException $e) {
        echo "<span style='color: red;'>❌ ERRO: {$e->getMessage()}</span></p>";
    }

    echo "<hr>";
    echo "<h2 style='color: green;'>✅ Migração Concluída!</h2>";

    echo "<h3>📋 Resumo das Alterações:</h3>";
    echo "<ul>";
    echo "<li>✅ Coluna <code>responsavel_id</code> adicionada (para armazenar gerente/supervisor da unidade)</li>";
    echo "<li>✅ Foreign key criada para <code>responsavel_id</code></li>";
    echo "<li>✅ Índice criado para melhor performance</li>";
    echo "<li>❌ Coluna <code>modulo_id</code> removida (não é mais necessária)</li>";
    echo "<li>📝 Agora cada checklist avaliará <strong>TODOS os módulos ativos</strong> de uma vez</li>";
    echo "</ul>";

    echo "<h3>⚠️ Importante:</h3>";
    echo "<ul>";
    echo "<li>Checklists antigos foram mantidos (compatibilidade retroativa)</li>";
    echo "<li>Novos checklists precisarão ter um responsável selecionado</li>";
    echo "<li>As perguntas agora serão agrupadas por módulo na interface</li>";
    echo "</ul>";

    echo "<hr>";
    echo "<p><a href='" . BASE_URL . "checklist/novo.php' style='padding: 10px 20px; background: #667eea; color: white; border-radius: 5px; text-decoration: none; display: inline-block;'>📝 Testar: Criar Nova Avaliação</a></p>";

    echo "<hr>";
    echo "<p style='color: red;'><strong>🔒 Segurança:</strong> Após confirmar que tudo está funcionando, <strong>DELETE ESTE ARQUIVO</strong> por questões de segurança!</p>";

} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Erro</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
