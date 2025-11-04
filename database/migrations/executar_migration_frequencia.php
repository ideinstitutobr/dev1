<?php
/**
 * Script para executar migration do módulo de Frequência
 * Execute este arquivo UMA VEZ para criar as tabelas necessárias
 */

// Define constante do sistema
define('SGC_SYSTEM', true);

// Carrega configurações
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Database.php';

echo "<h1>Migration: Módulo de Frequência</h1>";
echo "<p>Executando migration para criar tabelas de frequência...</p>";

try {
    // Conecta ao banco
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // Lê arquivo SQL
    $sql = file_get_contents(__DIR__ . '/migration_frequencia.sql');

    if (!$sql) {
        throw new Exception('Erro ao ler arquivo SQL');
    }

    // Separa e executa cada comando
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    $sucessos = 0;
    $erros = 0;

    echo "<h2>Executando comandos SQL:</h2>";
    echo "<div style='background: #f5f5f5; padding: 15px; border-radius: 5px;'>";

    foreach ($statements as $statement) {
        if (empty($statement)) continue;

        // Remove comentários
        $statement = preg_replace('/--.*$/m', '', $statement);
        $statement = trim($statement);

        if (empty($statement)) continue;

        try {
            $pdo->exec($statement);
            $sucessos++;

            // Identifica o tipo de comando
            if (stripos($statement, 'CREATE TABLE') !== false) {
                preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches);
                $tabela = $matches[1] ?? 'desconhecida';
                echo "✅ Tabela '<strong>$tabela</strong>' criada com sucesso<br>";
            } elseif (stripos($statement, 'ALTER TABLE') !== false) {
                preg_match('/ALTER TABLE.*?`?(\w+)`?/i', $statement, $matches);
                $tabela = $matches[1] ?? 'desconhecida';
                echo "✅ Tabela '<strong>$tabela</strong>' alterada com sucesso<br>";
            } else {
                echo "✅ Comando executado com sucesso<br>";
            }

        } catch (PDOException $e) {
            $erros++;

            // Ignora erro se tabela já existe
            if (strpos($e->getMessage(), 'already exists') !== false) {
                preg_match('/Table \'(\w+)\'/', $e->getMessage(), $matches);
                $tabela = $matches[1] ?? 'desconhecida';
                echo "⚠️ Tabela '<strong>$tabela</strong>' já existe (pulando)<br>";
            } elseif (strpos($e->getMessage(), 'Duplicate column') !== false) {
                echo "⚠️ Coluna já existe (pulando)<br>";
            } else {
                echo "❌ Erro: " . $e->getMessage() . "<br>";
            }
        }
    }

    echo "</div>";

    echo "<h2>Resultado:</h2>";
    echo "<p><strong>✅ Comandos executados com sucesso:</strong> $sucessos</p>";
    echo "<p><strong>❌ Erros/Avisos:</strong> $erros</p>";

    // Verifica se as tabelas foram criadas
    echo "<h2>Verificando tabelas criadas:</h2>";
    echo "<div style='background: #f5f5f5; padding: 15px; border-radius: 5px;'>";

    $tabelas = ['treinamento_sessoes', 'frequencia'];

    foreach ($tabelas as $tabela) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$tabela'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Tabela '<strong>$tabela</strong>' existe no banco de dados<br>";

            // Mostra estrutura
            $stmt = $pdo->query("DESCRIBE $tabela");
            $colunas = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo "&nbsp;&nbsp;&nbsp;&nbsp;Colunas: " . implode(', ', $colunas) . "<br>";
        } else {
            echo "❌ Tabela '<strong>$tabela</strong>' NÃO foi criada<br>";
        }
    }

    echo "</div>";

    echo "<h2 style='color: green;'>✅ Migration executada com sucesso!</h2>";
    echo "<p>As tabelas do módulo de Frequência foram criadas e estão prontas para uso.</p>";
    echo "<p><strong>Próximos passos:</strong></p>";
    echo "<ul>";
    echo "<li>Acesse o sistema e teste o módulo de Frequência</li>";
    echo "<li>Crie algumas sessões de treinamento</li>";
    echo "<li>Registre a frequência dos participantes</li>";
    echo "<li>Exporte os dados para CSV</li>";
    echo "</ul>";

    echo "<p><a href='/public/frequencia/' style='background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 20px;'>Acessar Módulo de Frequência</a></p>";

} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Erro na execução da migration:</h2>";
    echo "<p style='background: #fee; padding: 15px; border-radius: 5px; border-left: 4px solid red;'>";
    echo $e->getMessage();
    echo "</p>";
}

echo "<hr>";
echo "<p style='color: #999; font-size: 12px;'>SGC - Sistema de Gestão de Capacitações | Migration executada em " . date('d/m/Y H:i:s') . "</p>";
