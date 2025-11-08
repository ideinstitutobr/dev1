<?php
/**
 * Script de Migração SIMPLES - Checklists com Responsável
 * Execute apenas UMA vez!
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/classes/Database.php';

echo "<h1>🔄 Migração: Adicionar Responsável aos Checklists</h1>";
echo "<hr>";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // Verificar se já foi executada
    $stmt = $pdo->query("SHOW COLUMNS FROM checklists LIKE 'responsavel_id'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: orange; font-size: 18px;'><strong>⚠️ Migração já executada!</strong></p>";
        echo "<p>A coluna 'responsavel_id' já existe.</p>";
        echo "<hr>";
        echo "<p><a href='checklist/novo.php'>← Voltar para criar avaliação</a></p>";
        exit;
    }

    echo "<h2>Executando migração...</h2>";

    // 1. Adicionar coluna responsavel_id
    echo "<p><strong>1. Adicionando coluna responsavel_id...</strong> ";
    try {
        $pdo->exec("ALTER TABLE checklists ADD COLUMN responsavel_id INT NULL AFTER colaborador_id");
        echo "<span style='color: green;'>✅ OK</span></p>";
    } catch (PDOException $e) {
        echo "<span style='color: red;'>❌ ERRO: " . $e->getMessage() . "</span></p>";
    }

    // 2. Adicionar foreign key
    echo "<p><strong>2. Adicionando foreign key...</strong> ";
    try {
        $pdo->exec("ALTER TABLE checklists ADD CONSTRAINT fk_checklist_responsavel FOREIGN KEY (responsavel_id) REFERENCES colaboradores(id) ON DELETE RESTRICT ON UPDATE CASCADE");
        echo "<span style='color: green;'>✅ OK</span></p>";
    } catch (PDOException $e) {
        echo "<span style='color: red;'>❌ ERRO: " . $e->getMessage() . "</span></p>";
    }

    // 3. Criar índice
    echo "<p><strong>3. Criando índice...</strong> ";
    try {
        $pdo->exec("CREATE INDEX idx_responsavel_id ON checklists(responsavel_id)");
        echo "<span style='color: green;'>✅ OK</span></p>";
    } catch (PDOException $e) {
        echo "<span style='color: red;'>❌ ERRO: " . $e->getMessage() . "</span></p>";
    }

    // 4. Remover foreign key de modulo_id
    echo "<p><strong>4. Removendo foreign key de modulo_id...</strong> ";
    try {
        // Buscar nome da constraint
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
            echo "<span style='color: green;'>✅ OK (removida: {$constraintName})</span></p>";
        } else {
            echo "<span style='color: blue;'>⚠️ Nenhuma FK encontrada</span></p>";
        }
    } catch (PDOException $e) {
        echo "<span style='color: orange;'>⚠️ " . $e->getMessage() . "</span></p>";
    }

    // 5. Remover coluna modulo_id
    echo "<p><strong>5. Removendo coluna modulo_id...</strong> ";
    try {
        $pdo->exec("ALTER TABLE checklists DROP COLUMN modulo_id");
        echo "<span style='color: green;'>✅ OK</span></p>";
    } catch (PDOException $e) {
        echo "<span style='color: orange;'>⚠️ " . $e->getMessage() . "</span></p>";
    }

    echo "<hr>";
    echo "<h2 style='color: green;'>✅ Migração Concluída!</h2>";
    echo "<p style='font-size: 16px;'>Agora você pode criar novas avaliações com responsável!</p>";
    echo "<hr>";
    echo "<p><a href='checklist/novo.php' style='padding: 15px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;'>📝 Criar Nova Avaliação</a></p>";
    echo "<hr>";
    echo "<p style='color: red;'><strong>⚠️ IMPORTANTE:</strong> Delete este arquivo após confirmar que tudo funciona!</p>";

} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Erro Fatal</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
