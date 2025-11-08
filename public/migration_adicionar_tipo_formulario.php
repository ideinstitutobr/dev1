<?php
/**
 * Migration: Adicionar Coluna 'tipo' na Tabela checklists
 *
 * Objetivo: Permitir dois tipos de formulários:
 * - quinzenal_mensal: Avaliações quinzenais ou mensais
 * - diario: Avaliações diárias
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/classes/Database.php';

echo "<h1>🔄 Migration: Adicionar Tipo de Formulário</h1>";
echo "<hr>";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // Iniciar transação
    $pdo->beginTransaction();

    echo "<h2>📋 Passo 1: Verificar estrutura atual</h2>";

    // Verificar se a coluna já existe
    $stmt = $pdo->query("SHOW COLUMNS FROM checklists LIKE 'tipo'");
    $colunaExiste = $stmt->fetch();

    if ($colunaExiste) {
        echo "<p style='color: orange;'>⚠️ A coluna 'tipo' já existe na tabela!</p>";
        echo "<p>Dados atuais:</p>";

        $stmt = $pdo->query("SELECT tipo, COUNT(*) as total FROM checklists GROUP BY tipo");
        $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr style='background: #f0f0f0;'><th>Tipo</th><th>Quantidade</th></tr>";
        foreach ($dados as $dado) {
            echo "<tr>";
            echo "<td>" . ($dado['tipo'] ?? 'NULL') . "</td>";
            echo "<td>{$dado['total']}</td>";
            echo "</tr>";
        }
        echo "</table>";

        $pdo->rollBack();
        echo "<p style='color: green;'>✅ Migration já foi executada anteriormente.</p>";
        exit;
    }

    echo "<p>✅ Coluna 'tipo' não existe. Prosseguindo com migration...</p>";

    echo "<h2>📋 Passo 2: Adicionar coluna 'tipo'</h2>";

    $sql = "ALTER TABLE checklists
            ADD COLUMN tipo ENUM('quinzenal_mensal', 'diario')
            NOT NULL DEFAULT 'quinzenal_mensal'
            AFTER responsavel_id";

    $pdo->exec($sql);
    echo "<p>✅ Coluna 'tipo' adicionada com sucesso!</p>";
    echo "<pre>Tipo: ENUM('quinzenal_mensal', 'diario')
Default: 'quinzenal_mensal'
Posição: Após 'responsavel_id'</pre>";

    echo "<h2>📋 Passo 3: Migrar dados existentes</h2>";

    // Contar registros existentes
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM checklists");
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    echo "<p>Total de registros existentes: <strong>{$total}</strong></p>";

    if ($total > 0) {
        // Todos os registros existentes serão marcados como 'quinzenal_mensal' (já é o default)
        echo "<p>✅ Todos os {$total} registros existentes foram automaticamente marcados como 'quinzenal_mensal' (valor padrão)</p>";
    } else {
        echo "<p>ℹ️ Não há registros existentes na tabela.</p>";
    }

    echo "<h2>📋 Passo 4: Criar índice para otimização</h2>";

    $sql = "CREATE INDEX idx_tipo ON checklists(tipo)";
    $pdo->exec($sql);
    echo "<p>✅ Índice 'idx_tipo' criado com sucesso!</p>";

    echo "<h2>📋 Passo 5: Criar índice composto (tipo + data)</h2>";

    $sql = "CREATE INDEX idx_tipo_data ON checklists(tipo, data_avaliacao)";
    $pdo->exec($sql);
    echo "<p>✅ Índice composto 'idx_tipo_data' criado com sucesso!</p>";

    echo "<h2>📋 Passo 6: Verificar estrutura final</h2>";

    $stmt = $pdo->query("DESCRIBE checklists");
    $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; font-family: monospace; font-size: 12px;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";

    foreach ($colunas as $col) {
        $destaque = $col['Field'] === 'tipo' ? " style='background: #d4edda;'" : "";
        echo "<tr{$destaque}>";
        echo "<td><strong>{$col['Field']}</strong></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<h2>📋 Passo 7: Verificar índices criados</h2>";

    $stmt = $pdo->query("SHOW INDEX FROM checklists WHERE Key_name IN ('idx_tipo', 'idx_tipo_data')");
    $indices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Índice</th><th>Coluna</th><th>Sequência</th></tr>";

    foreach ($indices as $idx) {
        echo "<tr>";
        echo "<td><strong>{$idx['Key_name']}</strong></td>";
        echo "<td>{$idx['Column_name']}</td>";
        echo "<td>{$idx['Seq_in_index']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Commit da transação
    $pdo->commit();

    echo "<hr>";
    echo "<h2 style='color: green;'>✅ MIGRATION CONCLUÍDA COM SUCESSO!</h2>";

    echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin-top: 20px;'>";
    echo "<h3>📝 Resumo da Migration:</h3>";
    echo "<ul>";
    echo "<li>✅ Coluna 'tipo' adicionada à tabela 'checklists'</li>";
    echo "<li>✅ Valores permitidos: 'quinzenal_mensal' ou 'diario'</li>";
    echo "<li>✅ Valor padrão: 'quinzenal_mensal'</li>";
    echo "<li>✅ {$total} registros existentes migrados automaticamente</li>";
    echo "<li>✅ Índices criados para otimização de queries</li>";
    echo "</ul>";
    echo "</div>";

    echo "<div style='background: #fff3cd; padding: 20px; border-radius: 8px; margin-top: 20px;'>";
    echo "<h3>⚠️ Próximos Passos:</h3>";
    echo "<ol>";
    echo "<li>Atualizar Model 'Checklist.php'</li>";
    echo "<li>Atualizar Controller 'ChecklistController.php'</li>";
    echo "<li>Criar estrutura de pastas (quinzenal, diario, shared)</li>";
    echo "<li>Duplicar e adaptar arquivos</li>";
    echo "<li>Atualizar menu sidebar</li>";
    echo "</ol>";
    echo "</div>";

    echo "<hr>";
    echo "<p><a href='checklist/'>← Voltar para Checklists</a></p>";

} catch (PDOException $e) {
    // Rollback em caso de erro
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo "<h2 style='color: red;'>❌ ERRO NA MIGRATION</h2>";
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 8px;'>";
    echo "<p><strong>Mensagem:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Código:</strong> " . $e->getCode() . "</p>";
    echo "</div>";

    echo "<h3>🔄 Rollback executado</h3>";
    echo "<p>Nenhuma alteração foi aplicada ao banco de dados.</p>";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo "<h2 style='color: red;'>❌ ERRO</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
