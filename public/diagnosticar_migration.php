<?php
/**
 * Script de Diagnóstico - Foreign Key Issue
 * Identifica registros órfãos antes de criar a foreign key
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/classes/Database.php';

echo "<h1>🔍 Diagnóstico: Foreign Key Issue</h1>";
echo "<hr>";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    echo "<h2>1. Verificando estrutura da tabela checklists:</h2>";
    $stmt = $pdo->query("DESCRIBE checklists");
    $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Coluna</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($colunas as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<h2>2. Verificando valores de unidade_id na tabela checklists:</h2>";
    $stmt = $pdo->query("SELECT DISTINCT unidade_id, COUNT(*) as total FROM checklists GROUP BY unidade_id ORDER BY unidade_id");
    $unidadesChecklists = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<p><strong>Total de unidade_id distintos:</strong> " . count($unidadesChecklists) . "</p>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>unidade_id</th><th>Total de Checklists</th></tr>";
    foreach ($unidadesChecklists as $row) {
        $unidadeId = $row['unidade_id'] ?? 'NULL';
        echo "<tr><td>{$unidadeId}</td><td>{$row['total']}</td></tr>";
    }
    echo "</table>";

    echo "<h2>3. Verificando IDs existentes na tabela unidades:</h2>";
    $stmt = $pdo->query("SELECT id, nome FROM unidades ORDER BY id");
    $unidadesExistentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<p><strong>Total de unidades cadastradas:</strong> " . count($unidadesExistentes) . "</p>";
    if (count($unidadesExistentes) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Nome</th></tr>";
        foreach ($unidadesExistentes as $unidade) {
            echo "<tr><td>{$unidade['id']}</td><td>{$unidade['nome']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'><strong>⚠️ PROBLEMA: Nenhuma unidade cadastrada!</strong></p>";
    }

    echo "<h2>4. Identificando registros órfãos:</h2>";

    // Buscar checklists com unidade_id que não existe em unidades ou NULL
    $stmt = $pdo->query("
        SELECT c.id, c.unidade_id, c.data_avaliacao, c.status
        FROM checklists c
        LEFT JOIN unidades u ON c.unidade_id = u.id
        WHERE u.id IS NULL OR c.unidade_id IS NULL
        ORDER BY c.id
    ");
    $orfaos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($orfaos) > 0) {
        echo "<p style='color: red;'><strong>❌ Encontrados " . count($orfaos) . " registro(s) órfão(s):</strong></p>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Checklist ID</th><th>unidade_id</th><th>Data</th><th>Status</th></tr>";
        foreach ($orfaos as $orfao) {
            $unidadeId = $orfao['unidade_id'] ?? 'NULL';
            echo "<tr>";
            echo "<td>{$orfao['id']}</td>";
            echo "<td style='color: red;'>{$unidadeId}</td>";
            echo "<td>{$orfao['data_avaliacao']}</td>";
            echo "<td>{$orfao['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: green;'><strong>✅ Nenhum registro órfão encontrado!</strong></p>";
    }

    echo "<hr>";
    echo "<h2>📋 Resumo do Diagnóstico:</h2>";
    echo "<ul>";
    echo "<li>Total de checklists: " . array_sum(array_column($unidadesChecklists, 'total')) . "</li>";
    echo "<li>Total de unidades cadastradas: " . count($unidadesExistentes) . "</li>";
    echo "<li>Registros órfãos encontrados: <strong style='color: red;'>" . count($orfaos) . "</strong></li>";
    echo "</ul>";

    if (count($orfaos) > 0) {
        echo "<h2>💡 Soluções Possíveis:</h2>";
        echo "<ol>";
        echo "<li><strong>Deletar registros órfãos</strong> (se forem dados de teste ou inválidos)</li>";
        echo "<li><strong>Atualizar para uma unidade existente</strong> (se souber qual unidade deveria ser)</li>";
        echo "<li><strong>Criar unidades faltantes</strong> (se os IDs forem válidos mas as unidades não foram criadas)</li>";
        echo "</ol>";

        echo "<h3>🔧 Ação Sugerida:</h3>";

        if (count($unidadesExistentes) > 0) {
            $primeiraUnidade = $unidadesExistentes[0];
            echo "<p>Atualizar todos os registros órfãos para apontar para a primeira unidade (<strong>{$primeiraUnidade['nome']}</strong> - ID {$primeiraUnidade['id']})?</p>";
            echo "<form method='POST' style='display: inline;'>";
            echo "<input type='hidden' name='acao' value='atualizar_para_primeira'>";
            echo "<button type='submit' style='padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;'>";
            echo "🔄 Atualizar para Unidade: {$primeiraUnidade['nome']}";
            echo "</button>";
            echo "</form>";

            echo " ou ";

            echo "<form method='POST' style='display: inline;'>";
            echo "<input type='hidden' name='acao' value='deletar_orfaos'>";
            echo "<button type='submit' onclick='return confirm(\"Tem certeza que deseja DELETAR " . count($orfaos) . " registro(s)?\")' style='padding: 10px 20px; background: #dc3545; color: white; border: none; border-radius: 5px; cursor: pointer;'>";
            echo "🗑️ Deletar Registros Órfãos";
            echo "</button>";
            echo "</form>";
        } else {
            echo "<p style='color: red;'><strong>⚠️ Você precisa primeiro cadastrar pelo menos uma unidade!</strong></p>";
            echo "<p><a href='" . BASE_URL . "unidades/nova.php' style='padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; text-decoration: none; display: inline-block;'>➕ Cadastrar Nova Unidade</a></p>";
        }
    }

    // Processar ações
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $acao = $_POST['acao'] ?? '';

        if ($acao === 'atualizar_para_primeira' && count($unidadesExistentes) > 0) {
            $primeiraUnidadeId = $unidadesExistentes[0]['id'];

            echo "<hr><h2>🔄 Atualizando registros...</h2>";

            $stmt = $pdo->prepare("
                UPDATE checklists c
                LEFT JOIN unidades u ON c.unidade_id = u.id
                SET c.unidade_id = ?
                WHERE u.id IS NULL OR c.unidade_id IS NULL
            ");
            $stmt->execute([$primeiraUnidadeId]);

            echo "<p style='color: green;'><strong>✅ Registros atualizados com sucesso!</strong></p>";
            echo "<p>Agora você pode tentar adicionar a foreign key novamente.</p>";
            echo "<a href='executar_migracao_SIMPLES.php' style='padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 5px; text-decoration: none; display: inline-block;'>🔄 Executar Migração Novamente</a>";

        } elseif ($acao === 'deletar_orfaos') {
            echo "<hr><h2>🗑️ Deletando registros órfãos...</h2>";

            $stmt = $pdo->query("
                DELETE c FROM checklists c
                LEFT JOIN unidades u ON c.unidade_id = u.id
                WHERE u.id IS NULL OR c.unidade_id IS NULL
            ");

            echo "<p style='color: green;'><strong>✅ Registros deletados com sucesso!</strong></p>";
            echo "<p>Agora você pode tentar adicionar a foreign key novamente.</p>";
            echo "<a href='executar_migracao_SIMPLES.php' style='padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 5px; text-decoration: none; display: inline-block;'>🔄 Executar Migração Novamente</a>";
        }
    }

} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Erro</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
