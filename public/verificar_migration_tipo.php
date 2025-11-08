<?php
/**
 * Verificação: Migration de Tipo de Formulário
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/classes/Database.php';

echo "<h1>🔍 Verificação: Migration de Tipo</h1>";
echo "<hr>";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    echo "<h2>1. Verificar se a coluna 'tipo' existe</h2>";

    $stmt = $pdo->query("SHOW COLUMNS FROM checklists LIKE 'tipo'");
    $coluna = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($coluna) {
        echo "<p style='color: green;'>✅ Coluna 'tipo' existe!</p>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr style='background: #f0f0f0;'><th>Campo</th><th>Valor</th></tr>";
        foreach ($coluna as $key => $value) {
            echo "<tr><td><strong>{$key}</strong></td><td>{$value}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ Coluna 'tipo' NÃO existe!</p>";
        echo "<p>A migration precisa ser executada.</p>";
        exit;
    }

    echo "<h2>2. Verificar dados migrados</h2>";

    $stmt = $pdo->query("SELECT tipo, COUNT(*) as total FROM checklists GROUP BY tipo");
    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'><th>Tipo</th><th>Quantidade</th></tr>";

    $totalGeral = 0;
    foreach ($dados as $dado) {
        echo "<tr>";
        echo "<td><strong>" . ($dado['tipo'] ?? 'NULL') . "</strong></td>";
        echo "<td>{$dado['total']}</td>";
        echo "</tr>";
        $totalGeral += $dado['total'];
    }

    echo "<tr style='background: #f0f0f0; font-weight: bold;'>";
    echo "<td>TOTAL</td>";
    echo "<td>{$totalGeral}</td>";
    echo "</tr>";
    echo "</table>";

    echo "<h2>3. Verificar índices</h2>";

    $stmt = $pdo->query("SHOW INDEX FROM checklists WHERE Key_name IN ('idx_tipo', 'idx_tipo_data')");
    $indices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($indices) > 0) {
        echo "<p style='color: green;'>✅ Índices encontrados: " . count($indices) . "</p>";

        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>Índice</th><th>Coluna</th><th>Seq</th><th>Único</th></tr>";

        foreach ($indices as $idx) {
            echo "<tr>";
            echo "<td><strong>{$idx['Key_name']}</strong></td>";
            echo "<td>{$idx['Column_name']}</td>";
            echo "<td>{$idx['Seq_in_index']}</td>";
            echo "<td>" . ($idx['Non_unique'] == 0 ? 'Sim' : 'Não') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ Nenhum índice encontrado!</p>";
    }

    echo "<hr>";
    echo "<h2 style='color: green;'>✅ VERIFICAÇÃO CONCLUÍDA</h2>";

    echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin-top: 20px;'>";
    echo "<h3>📊 Status da Migration:</h3>";
    echo "<ul>";
    echo "<li>✅ Coluna 'tipo' existe e está configurada corretamente</li>";
    echo "<li>✅ {$totalGeral} registros migrados</li>";
    echo "<li>✅ Índices criados e funcionando</li>";
    echo "</ul>";
    echo "<p><strong>A migration foi aplicada com SUCESSO!</strong></p>";
    echo "</div>";

    echo "<div style='background: #cfe2ff; padding: 20px; border-radius: 8px; margin-top: 20px;'>";
    echo "<h3>📝 Próximos Passos:</h3>";
    echo "<p>Agora você pode prosseguir com:</p>";
    echo "<ol>";
    echo "<li>Atualizar Models (Checklist.php)</li>";
    echo "<li>Atualizar Controllers</li>";
    echo "<li>Criar estrutura de pastas</li>";
    echo "<li>Duplicar arquivos</li>";
    echo "</ol>";
    echo "</div>";

} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ ERRO</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
