<?php
/**
 * Script de Diagnóstico: Verificar Estrutura de Módulos e Perguntas
 * Verifica se os módulos e perguntas estão corretamente separados por tipo
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/classes/Database.php';
require_once __DIR__ . '/../app/models/ModuloAvaliacao.php';
require_once __DIR__ . '/../app/models/Pergunta.php';

echo "<h1>🔍 Diagnóstico: Estrutura de Módulos e Perguntas</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .ok { color: #28a745; font-weight: bold; }
    .error { color: #dc3545; font-weight: bold; }
    .warning { color: #ffc107; font-weight: bold; }
    table { width: 100%; border-collapse: collapse; margin: 15px 0; }
    th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
    th { background: #667eea; color: white; }
    .badge { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; }
    .badge-quinzenal { background: #667eea; color: white; }
    .badge-diario { background: #28a745; color: white; }
    .badge-ativo { background: #28a745; color: white; }
    .badge-inativo { background: #dc3545; color: white; }
</style>";

try {
    $moduloModel = new ModuloAvaliacao();
    $perguntaModel = new Pergunta();

    echo "<div class='section'>";
    echo "<h2>📊 Resumo Geral</h2>";

    // Buscar todos os módulos
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    $stmt = $pdo->query("SELECT COUNT(*) as total, tipo, ativo FROM modulos_avaliacao GROUP BY tipo, ativo");
    $resumoModulos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table>";
    echo "<tr><th>Tipo</th><th>Status</th><th>Quantidade</th></tr>";
    foreach ($resumoModulos as $res) {
        $tipoBadge = $res['tipo'] == 'quinzenal_mensal' ? 'badge-quinzenal' : 'badge-diario';
        $statusBadge = $res['ativo'] ? 'badge-ativo' : 'badge-inativo';
        $tipoLabel = $res['tipo'] == 'quinzenal_mensal' ? '📅 Quinzenal/Mensal' : '📆 Diário';
        $statusLabel = $res['ativo'] ? 'Ativo' : 'Inativo';

        echo "<tr>";
        echo "<td><span class='badge $tipoBadge'>$tipoLabel</span></td>";
        echo "<td><span class='badge $statusBadge'>$statusLabel</span></td>";
        echo "<td><strong>{$res['total']}</strong></td>";
        echo "</tr>";
    }
    echo "</table>";

    $stmt = $pdo->query("SELECT COUNT(*) as total, tipo, ativo FROM perguntas GROUP BY tipo, ativo");
    $resumoPerguntas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h3>Perguntas:</h3>";
    echo "<table>";
    echo "<tr><th>Tipo</th><th>Status</th><th>Quantidade</th></tr>";
    foreach ($resumoPerguntas as $res) {
        $tipoBadge = $res['tipo'] == 'quinzenal_mensal' ? 'badge-quinzenal' : 'badge-diario';
        $statusBadge = $res['ativo'] ? 'badge-ativo' : 'badge-inativo';
        $tipoLabel = $res['tipo'] == 'quinzenal_mensal' ? '📅 Quinzenal/Mensal' : '📆 Diário';
        $statusLabel = $res['ativo'] ? 'Ativo' : 'Inativo';

        echo "<tr>";
        echo "<td><span class='badge $tipoBadge'>$tipoLabel</span></td>";
        echo "<td><span class='badge $statusBadge'>$statusLabel</span></td>";
        echo "<td><strong>{$res['total']}</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";

    // Verificar módulos quinzenais/mensais
    echo "<div class='section'>";
    echo "<h2>📅 Módulos Quinzenais/Mensais</h2>";
    $modulosQuinzenal = $moduloModel->listarAtivos('quinzenal_mensal', true);

    if (empty($modulosQuinzenal)) {
        echo "<p class='warning'>⚠️ Nenhum módulo quinzenal/mensal encontrado!</p>";
        echo "<p>Você precisa criar módulos para formulários quinzenais/mensais em: <strong>Formulários > Quinzenais/Mensais > Módulos</strong></p>";
    } else {
        echo "<table>";
        echo "<tr><th>ID</th><th>Nome</th><th>Tipo</th><th>Perguntas</th><th>Status</th></tr>";
        foreach ($modulosQuinzenal as $mod) {
            $perguntas = $perguntaModel->listarPorModulo($mod['id'], false, 'quinzenal_mensal');
            $statusClass = $mod['ativo'] ? 'ok' : 'error';
            $statusLabel = $mod['ativo'] ? '✅ Ativo' : '❌ Inativo';

            echo "<tr>";
            echo "<td>{$mod['id']}</td>";
            echo "<td><strong>{$mod['nome']}</strong></td>";
            echo "<td><span class='badge badge-quinzenal'>📅 Quinzenal/Mensal</span></td>";
            echo "<td>" . count($perguntas) . " perguntas</td>";
            echo "<td class='$statusClass'>$statusLabel</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";

    // Verificar módulos diários
    echo "<div class='section'>";
    echo "<h2>📆 Módulos Diários</h2>";
    $modulosDiario = $moduloModel->listarAtivos('diario', true);

    if (empty($modulosDiario)) {
        echo "<p class='warning'>⚠️ Nenhum módulo diário encontrado!</p>";
        echo "<p>Você precisa criar módulos para formulários diários em: <strong>Formulários > Avaliações Diárias > Módulos</strong></p>";
    } else {
        echo "<table>";
        echo "<tr><th>ID</th><th>Nome</th><th>Tipo</th><th>Perguntas</th><th>Status</th></tr>";
        foreach ($modulosDiario as $mod) {
            $perguntas = $perguntaModel->listarPorModulo($mod['id'], false, 'diario');
            $statusClass = $mod['ativo'] ? 'ok' : 'error';
            $statusLabel = $mod['ativo'] ? '✅ Ativo' : '❌ Inativo';

            echo "<tr>";
            echo "<td>{$mod['id']}</td>";
            echo "<td><strong>{$mod['nome']}</strong></td>";
            echo "<td><span class='badge badge-diario'>📆 Diário</span></td>";
            echo "<td>" . count($perguntas) . " perguntas</td>";
            echo "<td class='$statusClass'>$statusLabel</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";

    // Verificar se há módulos/perguntas sem tipo definido
    echo "<div class='section'>";
    echo "<h2>🔍 Problemas Potenciais</h2>";

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM modulos_avaliacao WHERE tipo IS NULL OR tipo = ''");
    $modulosSemTipo = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM perguntas WHERE tipo IS NULL OR tipo = ''");
    $perguntasSemTipo = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    if ($modulosSemTipo > 0) {
        echo "<p class='error'>❌ Existem $modulosSemTipo módulo(s) sem tipo definido!</p>";

        $stmt = $pdo->query("SELECT id, nome FROM modulos_avaliacao WHERE tipo IS NULL OR tipo = ''");
        $mods = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<ul>";
        foreach ($mods as $m) {
            echo "<li>ID: {$m['id']} - {$m['nome']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p class='ok'>✅ Todos os módulos têm tipo definido</p>";
    }

    if ($perguntasSemTipo > 0) {
        echo "<p class='error'>❌ Existem $perguntasSemTipo pergunta(s) sem tipo definido!</p>";
    } else {
        echo "<p class='ok'>✅ Todas as perguntas têm tipo definido</p>";
    }

    echo "</div>";

    // Teste de filtros
    echo "<div class='section'>";
    echo "<h2>🧪 Teste de Filtros</h2>";

    echo "<h3>Teste 1: Buscar módulos quinzenais/mensais</h3>";
    $teste1 = $moduloModel->listarAtivos('quinzenal_mensal');
    echo "<p class='ok'>✅ Encontrados " . count($teste1) . " módulo(s) ativos do tipo quinzenal/mensal</p>";

    echo "<h3>Teste 2: Buscar módulos diários</h3>";
    $teste2 = $moduloModel->listarAtivos('diario');
    echo "<p class='ok'>✅ Encontrados " . count($teste2) . " módulo(s) ativos do tipo diário</p>";

    echo "<h3>Teste 3: Verificar perguntas de módulos quinzenais</h3>";
    $totalPerguntasQuinzenal = 0;
    foreach ($teste1 as $mod) {
        $pergs = $perguntaModel->listarPorModulo($mod['id'], true, 'quinzenal_mensal');
        $totalPerguntasQuinzenal += count($pergs);
    }
    echo "<p class='ok'>✅ Total de perguntas ativas em módulos quinzenais: $totalPerguntasQuinzenal</p>";

    echo "<h3>Teste 4: Verificar perguntas de módulos diários</h3>";
    $totalPerguntasDiario = 0;
    foreach ($teste2 as $mod) {
        $pergs = $perguntaModel->listarPorModulo($mod['id'], true, 'diario');
        $totalPerguntasDiario += count($pergs);
    }
    echo "<p class='ok'>✅ Total de perguntas ativas em módulos diários: $totalPerguntasDiario</p>";

    echo "</div>";

    // Conclusão
    echo "<div class='section'>";
    echo "<h2>✅ Conclusão</h2>";

    $problemas = [];

    if (empty($modulosQuinzenal)) {
        $problemas[] = "Não há módulos quinzenais/mensais cadastrados";
    }

    if (empty($modulosDiario)) {
        $problemas[] = "Não há módulos diários cadastrados";
    }

    if ($totalPerguntasQuinzenal == 0 && !empty($modulosQuinzenal)) {
        $problemas[] = "Módulos quinzenais/mensais não têm perguntas cadastradas";
    }

    if ($totalPerguntasDiario == 0 && !empty($modulosDiario)) {
        $problemas[] = "Módulos diários não têm perguntas cadastradas";
    }

    if ($modulosSemTipo > 0) {
        $problemas[] = "Existem módulos sem tipo definido";
    }

    if ($perguntasSemTipo > 0) {
        $problemas[] = "Existem perguntas sem tipo definido";
    }

    if (empty($problemas)) {
        echo "<p class='ok' style='font-size: 18px;'>🎉 Estrutura está correta! Todos os módulos e perguntas estão configurados adequadamente.</p>";
        echo "<p>Você pode criar avaliações quinzenais/mensais e diárias normalmente, cada uma verá apenas seus próprios módulos e perguntas.</p>";
    } else {
        echo "<p class='error' style='font-size: 18px;'>⚠️ Problemas encontrados:</p>";
        echo "<ul>";
        foreach ($problemas as $prob) {
            echo "<li class='error'>$prob</li>";
        }
        echo "</ul>";

        echo "<h3>📝 Ações Necessárias:</h3>";
        echo "<ol>";
        if (empty($modulosQuinzenal) || $totalPerguntasQuinzenal == 0) {
            echo "<li>Acesse <strong>Formulários > Quinzenais/Mensais > Módulos</strong> para criar módulos e perguntas quinzenais/mensais</li>";
        }
        if (empty($modulosDiario) || $totalPerguntasDiario == 0) {
            echo "<li>Acesse <strong>Formulários > Avaliações Diárias > Módulos</strong> para criar módulos e perguntas diários</li>";
        }
        echo "</ol>";
    }

    echo "</div>";

} catch (Exception $e) {
    echo "<div class='section'>";
    echo "<p class='error'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<p style='margin-top: 30px; text-align: center; color: #666;'>";
echo "<a href='" . BASE_URL . "' style='color: #667eea; text-decoration: none;'>← Voltar para o Sistema</a>";
echo "</p>";
