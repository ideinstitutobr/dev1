<?php
/**
 * Script de Verificação Completa - Sistema de Checklists
 * Verifica se todas as migrações e configurações estão corretas
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/classes/Database.php';

echo "<h1>🔍 Verificação Completa do Sistema</h1>";
echo "<hr>";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    echo "<h2>1️⃣ Verificando Estrutura da Tabela 'checklists'</h2>";

    $stmt = $pdo->query("DESCRIBE checklists");
    $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $colunasEsperadas = ['id', 'unidade_id', 'colaborador_id', 'responsavel_id', 'data_avaliacao', 'observacoes_gerais', 'status', 'pontuacao_total', 'pontuacao_maxima', 'percentual', 'atingiu_meta'];
    $colunasEncontradas = array_column($colunas, 'Field');

    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Coluna</th><th>Tipo</th><th>Status</th></tr>";

    foreach ($colunasEsperadas as $coluna) {
        $existe = in_array($coluna, $colunasEncontradas);
        $status = $existe ? "<span style='color: green;'>✅ OK</span>" : "<span style='color: red;'>❌ FALTA</span>";

        $tipo = '';
        if ($existe) {
            $key = array_search($coluna, $colunasEncontradas);
            $tipo = $colunas[$key]['Type'];
        }

        echo "<tr><td><strong>{$coluna}</strong></td><td>{$tipo}</td><td>{$status}</td></tr>";
    }

    // Verificar se modulo_id ainda existe (NÃO deveria)
    if (in_array('modulo_id', $colunasEncontradas)) {
        echo "<tr><td><strong>modulo_id</strong></td><td>-</td><td><span style='color: red;'>⚠️ DEVERIA TER SIDO REMOVIDA!</span></td></tr>";
    }

    echo "</table>";

    echo "<h2>2️⃣ Verificando Foreign Keys</h2>";

    $stmt = $pdo->query("
        SELECT
            CONSTRAINT_NAME,
            COLUMN_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'checklists'
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");

    $fks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Constraint</th><th>Coluna</th><th>Referencia</th></tr>";

    foreach ($fks as $fk) {
        echo "<tr>";
        echo "<td>{$fk['CONSTRAINT_NAME']}</td>";
        echo "<td>{$fk['COLUMN_NAME']}</td>";
        echo "<td>{$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}</td>";
        echo "</tr>";
    }

    echo "</table>";

    echo "<h2>3️⃣ Verificando Constraints UNIQUE</h2>";

    $stmt = $pdo->query("
        SELECT CONSTRAINT_NAME
        FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'checklists'
        AND CONSTRAINT_TYPE = 'UNIQUE'
    ");

    $uniques = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($uniques)) {
        echo "<p style='color: green;'>✅ Nenhuma constraint UNIQUE encontrada (correto!)</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Constraints UNIQUE encontradas:</p>";
        echo "<ul>";
        foreach ($uniques as $unique) {
            echo "<li>{$unique}";
            if ($unique === 'unique_checklist') {
                echo " <span style='color: red;'>← DEVERIA TER SIDO REMOVIDA!</span>";
            }
            echo "</li>";
        }
        echo "</ul>";
    }

    echo "<h2>4️⃣ Verificando Módulos Ativos</h2>";

    $stmt = $pdo->query("SELECT id, nome, total_perguntas, ativo FROM modulos_avaliacao ORDER BY ordem, id");
    $modulos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Nome</th><th>Perguntas</th><th>Status</th></tr>";

    foreach ($modulos as $modulo) {
        $statusBadge = $modulo['ativo'] ? "<span style='color: green;'>✅ Ativo</span>" : "<span style='color: orange;'>⚠️ Inativo</span>";
        echo "<tr>";
        echo "<td>{$modulo['id']}</td>";
        echo "<td>{$modulo['nome']}</td>";
        echo "<td>{$modulo['total_perguntas']}</td>";
        echo "<td>{$statusBadge}</td>";
        echo "</tr>";
    }

    echo "</table>";

    echo "<h2>5️⃣ Contando Perguntas por Módulo</h2>";

    $stmt = $pdo->query("
        SELECT
            m.id,
            m.nome,
            COUNT(p.id) as total_perguntas_cadastradas,
            m.total_perguntas as total_perguntas_esperado
        FROM modulos_avaliacao m
        LEFT JOIN perguntas p ON m.id = p.modulo_id AND p.ativo = 1
        WHERE m.ativo = 1
        GROUP BY m.id, m.nome, m.total_perguntas
        ORDER BY m.ordem, m.id
    ");

    $contagens = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Módulo</th><th>Perguntas Ativas</th><th>Total Esperado</th><th>Status</th></tr>";

    $totalGeral = 0;
    foreach ($contagens as $cont) {
        $match = $cont['total_perguntas_cadastradas'] == $cont['total_perguntas_esperado'];
        $statusBadge = $match ? "<span style='color: green;'>✅ OK</span>" : "<span style='color: orange;'>⚠️ Diferente</span>";

        echo "<tr>";
        echo "<td>{$cont['nome']}</td>";
        echo "<td>{$cont['total_perguntas_cadastradas']}</td>";
        echo "<td>{$cont['total_perguntas_esperado']}</td>";
        echo "<td>{$statusBadge}</td>";
        echo "</tr>";

        $totalGeral += $cont['total_perguntas_cadastradas'];
    }

    echo "<tr style='background: #f0f0f0; font-weight: bold;'>";
    echo "<td>TOTAL GERAL</td>";
    echo "<td colspan='3'>{$totalGeral} perguntas ativas</td>";
    echo "</tr>";

    echo "</table>";

    echo "<h2>6️⃣ Testando Criação de Checklist (Simulação)</h2>";

    $stmt = $pdo->query("SELECT id, nome FROM unidades WHERE ativo = 1 LIMIT 1");
    $unidade = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($unidade) {
        echo "<p>✅ Unidade encontrada: <strong>{$unidade['nome']}</strong></p>";

        // Verificar se tem lideranças
        $stmt = $pdo->query("
            SELECT COUNT(*) as total
            FROM unidade_lideranca
            WHERE unidade_id = {$unidade['id']} AND ativo = 1
        ");
        $liderancas = $stmt->fetch(PDO::FETCH_ASSOC);

        echo "<p>Lideranças na unidade: <strong>{$liderancas['total']}</strong></p>";

        if ($liderancas['total'] > 0) {
            echo "<p style='color: green;'>✅ Sistema pronto para criar avaliações!</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Esta unidade não tem lideranças cadastradas. Você pode criar a avaliação mas não conseguirá selecionar um responsável.</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Nenhuma unidade ativa encontrada!</p>";
    }

    echo "<hr>";
    echo "<h2 style='color: green;'>✅ Verificação Concluída!</h2>";

    echo "<h3>📋 Resumo:</h3>";
    echo "<ul>";
    echo "<li>Estrutura da tabela checklists: " . (in_array('responsavel_id', $colunasEncontradas) && !in_array('modulo_id', $colunasEncontradas) ? "<span style='color: green;'>✅ Correta</span>" : "<span style='color: red;'>❌ Precisa migração</span>") . "</li>";
    echo "<li>Total de perguntas ativas: <strong>{$totalGeral}</strong></li>";
    echo "<li>Sistema está: " . (in_array('responsavel_id', $colunasEncontradas) ? "<span style='color: green;'><strong>PRONTO PARA USO</strong></span>" : "<span style='color: red;'><strong>PRECISA EXECUTAR MIGRAÇÃO</strong></span>") . "</li>";
    echo "</ul>";

    if (!in_array('responsavel_id', $colunasEncontradas)) {
        echo "<hr>";
        echo "<h3 style='color: red;'>⚠️ AÇÃO NECESSÁRIA:</h3>";
        echo "<p><a href='migrar_responsavel_checklist.php' style='padding: 15px 30px; background: #dc3545; color: white; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;'>🔧 Executar Migração Agora</a></p>";
    } else {
        echo "<hr>";
        echo "<p><a href='checklist/novo.php' style='padding: 15px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;'>📝 Criar Nova Avaliação</a></p>";
    }

    echo "<hr>";
    echo "<p style='color: #666;'><small>Este script pode ser deletado após a verificação.</small></p>";

} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Erro</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
