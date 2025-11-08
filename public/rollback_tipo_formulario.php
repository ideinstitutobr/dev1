<?php
/**
 * Rollback: Remover Coluna 'tipo' da Tabela checklists
 *
 * ATENÇÃO: Este script remove a coluna 'tipo' e seus índices.
 * Use apenas se precisar reverter a migration.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/classes/Database.php';

echo "<h1>🔄 Rollback: Remover Tipo de Formulário</h1>";
echo "<hr>";

// Confirmação de segurança
if (!isset($_GET['confirmar']) || $_GET['confirmar'] !== 'sim') {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 8px; border: 2px solid #dc3545;'>";
    echo "<h2>⚠️ ATENÇÃO - OPERAÇÃO DESTRUTIVA</h2>";
    echo "<p>Este script irá <strong>REMOVER</strong> a coluna 'tipo' da tabela 'checklists'.</p>";
    echo "<p>Todos os dados da coluna serão <strong>PERMANENTEMENTE PERDIDOS</strong>.</p>";
    echo "<p><strong>Tem certeza que deseja continuar?</strong></p>";
    echo "<p><a href='?confirmar=sim' style='display: inline-block; padding: 10px 20px; background: #dc3545; color: white; text-decoration: none; border-radius: 5px; margin-top: 10px;'>SIM, EXECUTAR ROLLBACK</a></p>";
    echo "<p><a href='checklist/' style='display: inline-block; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;'>NÃO, CANCELAR</a></p>";
    echo "</div>";
    exit;
}

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // Iniciar transação
    $pdo->beginTransaction();

    echo "<h2>📋 Passo 1: Verificar se a coluna existe</h2>";

    $stmt = $pdo->query("SHOW COLUMNS FROM checklists LIKE 'tipo'");
    $colunaExiste = $stmt->fetch();

    if (!$colunaExiste) {
        echo "<p style='color: orange;'>⚠️ A coluna 'tipo' não existe na tabela!</p>";
        echo "<p>Nada a fazer.</p>";
        $pdo->rollBack();
        exit;
    }

    echo "<p>✅ Coluna 'tipo' encontrada.</p>";

    // Mostrar dados antes de remover
    echo "<h2>📋 Passo 2: Dados atuais (antes de remover)</h2>";

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

    echo "<h2>📋 Passo 3: Remover índices</h2>";

    // Remover índice idx_tipo
    try {
        $pdo->exec("DROP INDEX idx_tipo ON checklists");
        echo "<p>✅ Índice 'idx_tipo' removido.</p>";
    } catch (PDOException $e) {
        echo "<p style='color: orange;'>⚠️ Índice 'idx_tipo' não existe ou já foi removido.</p>";
    }

    // Remover índice idx_tipo_data
    try {
        $pdo->exec("DROP INDEX idx_tipo_data ON checklists");
        echo "<p>✅ Índice 'idx_tipo_data' removido.</p>";
    } catch (PDOException $e) {
        echo "<p style='color: orange;'>⚠️ Índice 'idx_tipo_data' não existe ou já foi removido.</p>";
    }

    echo "<h2>📋 Passo 4: Remover coluna 'tipo'</h2>";

    $pdo->exec("ALTER TABLE checklists DROP COLUMN tipo");
    echo "<p>✅ Coluna 'tipo' removida com sucesso!</p>";

    // Commit da transação
    $pdo->commit();

    echo "<hr>";
    echo "<h2 style='color: green;'>✅ ROLLBACK CONCLUÍDO COM SUCESSO!</h2>";

    echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin-top: 20px;'>";
    echo "<h3>📝 Resumo do Rollback:</h3>";
    echo "<ul>";
    echo "<li>✅ Índice 'idx_tipo' removido</li>";
    echo "<li>✅ Índice 'idx_tipo_data' removido</li>";
    echo "<li>✅ Coluna 'tipo' removida da tabela 'checklists'</li>";
    echo "</ul>";
    echo "</div>";

    echo "<hr>";
    echo "<p><a href='checklist/'>← Voltar para Checklists</a></p>";

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo "<h2 style='color: red;'>❌ ERRO NO ROLLBACK</h2>";
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 8px;'>";
    echo "<p><strong>Mensagem:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Código:</strong> " . $e->getCode() . "</p>";
    echo "</div>";

    echo "<h3>🔄 Rollback da operação executado</h3>";
    echo "<p>Nenhuma alteração foi aplicada ao banco de dados.</p>";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo "<h2 style='color: red;'>❌ ERRO</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
