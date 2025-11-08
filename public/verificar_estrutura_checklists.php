<?php
/**
 * Script para verificar estrutura da tabela checklists
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/classes/Database.php';

echo "<h1>📋 Estrutura da Tabela: checklists</h1>";
echo "<hr>";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // Buscar estrutura da tabela
    $stmt = $pdo->query("DESCRIBE checklists");
    $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' cellpadding='8' style='border-collapse: collapse; font-family: monospace;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th>";
    echo "</tr>";

    foreach ($colunas as $col) {
        echo "<tr>";
        echo "<td><strong>{$col['Field']}</strong></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "<td>{$col['Extra']}</td>";
        echo "</tr>";
    }

    echo "</table>";

    echo "<hr>";
    echo "<h2>✅ Consulta concluída</h2>";

} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Erro</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
