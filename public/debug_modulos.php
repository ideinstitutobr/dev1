<?php
/**
 * Script de Debug - Verificar Módulos Duplicados
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/classes/Database.php';

echo "<h1>🔍 Debug - Módulos</h1>";
echo "<hr>";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    echo "<h2>1. Todos os Módulos (ativos e inativos)</h2>";

    $stmt = $pdo->query("SELECT * FROM modulos_avaliacao ORDER BY ordem, nome");
    $todosModulos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>ID</th><th>Nome</th><th>Ordem</th><th>Total Perguntas</th><th>Ativo</th>";
    echo "</tr>";

    foreach ($todosModulos as $modulo) {
        $bgColor = $modulo['ativo'] ? 'white' : '#ffe0e0';
        echo "<tr style='background: {$bgColor};'>";
        echo "<td>{$modulo['id']}</td>";
        echo "<td><strong>{$modulo['nome']}</strong></td>";
        echo "<td>{$modulo['ordem']}</td>";
        echo "<td>{$modulo['total_perguntas']}</td>";
        echo "<td>" . ($modulo['ativo'] ? '✅ Sim' : '❌ Não') . "</td>";
        echo "</tr>";
    }

    echo "</table>";

    echo "<h2>2. Verificar Duplicados</h2>";

    $stmt = $pdo->query("
        SELECT nome, COUNT(*) as total
        FROM modulos_avaliacao
        WHERE ativo = 1
        GROUP BY nome
        HAVING COUNT(*) > 1
    ");

    $duplicados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($duplicados)) {
        echo "<p style='color: green;'>✅ Nenhum módulo duplicado encontrado!</p>";
    } else {
        echo "<p style='color: red;'>❌ MÓDULOS DUPLICADOS ENCONTRADOS:</p>";
        echo "<ul>";
        foreach ($duplicados as $dup) {
            echo "<li><strong>{$dup['nome']}</strong> - {$dup['total']} vezes</li>";
        }
        echo "</ul>";

        // Mostrar detalhes dos duplicados
        foreach ($duplicados as $dup) {
            echo "<h3>Detalhes do módulo '{$dup['nome']}':</h3>";

            $stmt = $pdo->prepare("SELECT * FROM modulos_avaliacao WHERE nome = ? AND ativo = 1");
            $stmt->execute([$dup['nome']]);
            $instancias = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
            echo "<tr style='background: #f0f0f0;'>";
            echo "<th>ID</th><th>Nome</th><th>Descrição</th><th>Ordem</th><th>Total Perguntas</th><th>Criado em</th>";
            echo "</tr>";

            foreach ($instancias as $inst) {
                echo "<tr>";
                echo "<td>{$inst['id']}</td>";
                echo "<td>{$inst['nome']}</td>";
                echo "<td>{$inst['descricao']}</td>";
                echo "<td>{$inst['ordem']}</td>";
                echo "<td>{$inst['total_perguntas']}</td>";
                echo "<td>" . ($inst['criado_em'] ?? 'N/A') . "</td>";
                echo "</tr>";
            }

            echo "</table>";

            echo "<h4>Ações sugeridas:</h4>";
            echo "<p>Você pode desativar um dos registros duplicados executando:</p>";
            echo "<pre style='background: #f5f5f5; padding: 10px;'>";
            foreach ($instancias as $index => $inst) {
                if ($index > 0) { // Manter apenas o primeiro
                    echo "UPDATE modulos_avaliacao SET ativo = 0 WHERE id = {$inst['id']};\n";
                }
            }
            echo "</pre>";
        }
    }

    echo "<h2>3. Testando a query listarAtivos()</h2>";

    $stmt = $pdo->query("SELECT * FROM modulos_avaliacao WHERE ativo = 1 ORDER BY ordem, nome");
    $modulosAtivos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<p>Total de módulos retornados: <strong>" . count($modulosAtivos) . "</strong></p>";

    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Posição</th><th>ID</th><th>Nome</th><th>Ordem</th>";
    echo "</tr>";

    foreach ($modulosAtivos as $index => $modulo) {
        echo "<tr>";
        echo "<td>" . ($index + 1) . "</td>";
        echo "<td>{$modulo['id']}</td>";
        echo "<td><strong>{$modulo['nome']}</strong></td>";
        echo "<td>{$modulo['ordem']}</td>";
        echo "</tr>";
    }

    echo "</table>";

    echo "<hr>";
    echo "<h2>✅ Debug Concluído</h2>";
    echo "<p><a href='checklist/editar.php?id=" . ($_GET['checklist_id'] ?? '1') . "'>← Voltar para Editar</a></p>";

} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Erro</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
