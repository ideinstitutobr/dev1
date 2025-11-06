<?php
define('SGC_SYSTEM', true);
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/classes/Database.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Diagnóstico Agenda</title>";
echo "<style>body{font-family:Arial;padding:20px;} table{border-collapse:collapse;width:100%;margin:20px 0;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#667eea;color:white;} .success{color:green;} .error{color:red;}</style></head><body>";
echo "<h1>Diagnóstico: Tabela agenda_treinamentos</h1>";

try {
    // Verificar estrutura da tabela
    echo "<h2>Estrutura da Tabela:</h2>";
    $colunas = $pdo->query("SHOW COLUMNS FROM agenda_treinamentos")->fetchAll(PDO::FETCH_ASSOC);
    echo "<table><tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
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

    // Verificar registros
    echo "<h2>Registros na Tabela:</h2>";
    $stmt = $pdo->query("SELECT * FROM agenda_treinamentos ORDER BY id DESC LIMIT 10");
    $agendas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($agendas)) {
        echo "<p class='error'>❌ Nenhuma agenda encontrada na tabela!</p>";
    } else {
        echo "<p class='success'>✅ Total de agendas: " . count($agendas) . "</p>";
        echo "<table><tr>";
        foreach (array_keys($agendas[0]) as $coluna) {
            echo "<th>{$coluna}</th>";
        }
        echo "</tr>";
        foreach ($agendas as $agenda) {
            echo "<tr>";
            foreach ($agenda as $valor) {
                echo "<td>" . htmlspecialchars($valor ?? '-') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }

    // Verificar total de registros
    $total = $pdo->query("SELECT COUNT(*) FROM agenda_treinamentos")->fetchColumn();
    echo "<h3>Total geral de agendas: {$total}</h3>";

} catch (Exception $e) {
    echo "<p class='error'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
?>
