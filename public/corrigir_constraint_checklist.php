<?php
/**
 * Script de Correção - Remover Constraint Antiga
 * Remove a constraint UNIQUE que usa modulo_id
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/classes/Database.php';

echo "<h1>🔧 Correção: Remover Constraint Antiga</h1>";
echo "<hr>";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    echo "<h2>1. Verificando constraints existentes...</h2>";

    // Listar todas as constraints da tabela checklists
    $stmt = $pdo->query("
        SELECT CONSTRAINT_NAME, CONSTRAINT_TYPE
        FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'checklists'
    ");

    $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Constraint</th><th>Tipo</th></tr>";
    foreach ($constraints as $constraint) {
        echo "<tr><td>{$constraint['CONSTRAINT_NAME']}</td><td>{$constraint['CONSTRAINT_TYPE']}</td></tr>";
    }
    echo "</table>";

    echo "<h2>2. Buscando constraint 'unique_checklist'...</h2>";

    $stmt = $pdo->query("
        SELECT CONSTRAINT_NAME
        FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'checklists'
        AND CONSTRAINT_NAME = 'unique_checklist'
    ");

    $uniqueConstraint = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($uniqueConstraint) {
        echo "<p><strong>✅ Constraint 'unique_checklist' encontrada!</strong></p>";

        // Verificar quais colunas ela usa
        $stmt = $pdo->query("
            SELECT COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'checklists'
            AND CONSTRAINT_NAME = 'unique_checklist'
            ORDER BY ORDINAL_POSITION
        ");

        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>Colunas na constraint: <strong>" . implode(', ', $columns) . "</strong></p>";

        echo "<h2>3. Removendo constraint antiga...</h2>";
        try {
            $pdo->exec("ALTER TABLE checklists DROP INDEX unique_checklist");
            echo "<p style='color: green;'><strong>✅ Constraint removida com sucesso!</strong></p>";
        } catch (PDOException $e) {
            echo "<p style='color: red;'>❌ Erro ao remover: " . $e->getMessage() . "</p>";
        }

    } else {
        echo "<p style='color: blue;'>⚠️ Constraint 'unique_checklist' não encontrada. Talvez já tenha sido removida.</p>";
    }

    echo "<h2>4. Verificando se precisa criar nova constraint...</h2>";
    echo "<p>A nova estrutura permite múltiplas avaliações da mesma unidade na mesma data,</p>";
    echo "<p>desde que sejam de responsáveis diferentes ou com propósitos diferentes.</p>";
    echo "<p><strong>Decisão:</strong> Não criar constraint UNIQUE automática. Permitir flexibilidade.</p>";

    echo "<hr>";
    echo "<h2 style='color: green;'>✅ Correção Concluída!</h2>";
    echo "<p>Agora você pode criar novas avaliações sem problemas!</p>";
    echo "<hr>";
    echo "<p><a href='checklist/novo.php' style='padding: 15px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;'>📝 Criar Nova Avaliação</a></p>";
    echo "<hr>";
    echo "<p style='color: red;'><strong>⚠️ IMPORTANTE:</strong> Delete este arquivo e o arquivo de migração após confirmar que tudo funciona!</p>";

} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Erro Fatal</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
