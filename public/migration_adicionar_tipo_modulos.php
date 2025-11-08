<?php
/**
 * Migration: Adicionar Tipo aos Módulos e Perguntas
 *
 * Objetivo: Permitir que cada tipo de formulário tenha seus próprios módulos e perguntas
 * - quinzenal_mensal: Módulos específicos para avaliações quinzenais/mensais
 * - diario: Módulos específicos para avaliações diárias
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/classes/Database.php';

echo "<h1>🔄 Migration: Adicionar Tipo aos Módulos</h1>";
echo "<hr>";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // Iniciar transação
    $pdo->beginTransaction();

    echo "<h2>📋 Passo 1: Verificar estrutura atual dos módulos</h2>";

    // Verificar se a coluna já existe em modulos_avaliacao
    $stmt = $pdo->query("SHOW COLUMNS FROM modulos_avaliacao LIKE 'tipo'");
    $colunaExiste = $stmt->fetch();

    if ($colunaExiste) {
        echo "<p style='color: orange;'>⚠️ A coluna 'tipo' já existe em modulos_avaliacao!</p>";
        $pdo->rollBack();
        echo "<p style='color: green;'>✅ Migration já foi executada anteriormente.</p>";
        exit;
    }

    echo "<p>✅ Coluna 'tipo' não existe. Prosseguindo...</p>";

    echo "<h2>📋 Passo 2: Adicionar coluna 'tipo' em modulos_avaliacao</h2>";

    $sql = "ALTER TABLE modulos_avaliacao
            ADD COLUMN tipo ENUM('quinzenal_mensal', 'diario')
            NOT NULL DEFAULT 'quinzenal_mensal'
            AFTER nome";

    $pdo->exec($sql);
    echo "<p>✅ Coluna 'tipo' adicionada em modulos_avaliacao!</p>";

    echo "<h2>📋 Passo 3: Adicionar coluna 'tipo' em perguntas</h2>";

    $sql = "ALTER TABLE perguntas
            ADD COLUMN tipo ENUM('quinzenal_mensal', 'diario')
            NOT NULL DEFAULT 'quinzenal_mensal'
            AFTER modulo_id";

    $pdo->exec($sql);
    echo "<p>✅ Coluna 'tipo' adicionada em perguntas!</p>";

    echo "<h2>📋 Passo 4: Migrar dados existentes</h2>";

    // Contar módulos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM modulos_avaliacao");
    $totalModulos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    echo "<p>Total de módulos existentes: <strong>{$totalModulos}</strong></p>";
    echo "<p>✅ Todos serão marcados como 'quinzenal_mensal' (valor padrão)</p>";

    // Contar perguntas
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM perguntas");
    $totalPerguntas = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    echo "<p>Total de perguntas existentes: <strong>{$totalPerguntas}</strong></p>";
    echo "<p>✅ Todas serão marcadas como 'quinzenal_mensal' (valor padrão)</p>";

    echo "<h2>📋 Passo 5: Criar índices para otimização</h2>";

    $sql = "CREATE INDEX idx_modulo_tipo ON modulos_avaliacao(tipo)";
    $pdo->exec($sql);
    echo "<p>✅ Índice 'idx_modulo_tipo' criado!</p>";

    $sql = "CREATE INDEX idx_pergunta_tipo ON perguntas(tipo)";
    $pdo->exec($sql);
    echo "<p>✅ Índice 'idx_pergunta_tipo' criado!</p>";

    $sql = "CREATE INDEX idx_pergunta_modulo_tipo ON perguntas(modulo_id, tipo)";
    $pdo->exec($sql);
    echo "<p>✅ Índice composto 'idx_pergunta_modulo_tipo' criado!</p>";

    echo "<h2>📋 Passo 6: Verificar estrutura final</h2>";

    // Verificar modulos_avaliacao
    $stmt = $pdo->query("DESCRIBE modulos_avaliacao");
    $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h3>Tabela: modulos_avaliacao</h3>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; font-size: 12px;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Campo</th><th>Tipo</th><th>Null</th><th>Default</th></tr>";

    foreach ($colunas as $col) {
        $destaque = $col['Field'] === 'tipo' ? " style='background: #d4edda;'" : "";
        echo "<tr{$destaque}>";
        echo "<td><strong>{$col['Field']}</strong></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Verificar perguntas
    $stmt = $pdo->query("DESCRIBE perguntas");
    $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h3>Tabela: perguntas</h3>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; font-size: 12px;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Campo</th><th>Tipo</th><th>Null</th><th>Default</th></tr>";

    foreach ($colunas as $col) {
        $destaque = $col['Field'] === 'tipo' ? " style='background: #d4edda;'" : "";
        echo "<tr{$destaque}>";
        echo "<td><strong>{$col['Field']}</strong></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
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
    echo "<li>✅ Coluna 'tipo' adicionada à tabela 'modulos_avaliacao'</li>";
    echo "<li>✅ Coluna 'tipo' adicionada à tabela 'perguntas'</li>";
    echo "<li>✅ {$totalModulos} módulos migrados como 'quinzenal_mensal'</li>";
    echo "<li>✅ {$totalPerguntas} perguntas migradas como 'quinzenal_mensal'</li>";
    echo "<li>✅ Índices criados para otimização</li>";
    echo "</ul>";
    echo "</div>";

    echo "<div style='background: #fff3cd; padding: 20px; border-radius: 8px; margin-top: 20px;'>";
    echo "<h3>📌 Próximos Passos:</h3>";
    echo "<ol>";
    echo "<li>Atualizar Model 'ModuloAvaliacao.php' para filtrar por tipo</li>";
    echo "<li>Atualizar Model 'Pergunta.php' para filtrar por tipo</li>";
    echo "<li>Criar módulos específicos para formulários diários</li>";
    echo "<li>Criar perguntas específicas para formulários diários</li>";
    echo "</ol>";
    echo "</div>";

    echo "<hr>";
    echo "<p><a href='checklist/'>← Voltar para Checklists</a></p>";

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo "<h2 style='color: red;'>❌ ERRO NA MIGRATION</h2>";
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 8px;'>";
    echo "<p><strong>Mensagem:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Código:</strong> " . $e->getCode() . "</p>";
    echo "</div>";
    echo "<h3>🔄 Rollback executado</h3>";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo "<h2 style='color: red;'>❌ ERRO</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
