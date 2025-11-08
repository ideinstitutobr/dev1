<?php
/**
 * Script de Debug - Verificar HTML dos Módulos
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/classes/Database.php';
require_once APP_PATH . 'models/Checklist.php';
require_once APP_PATH . 'models/Pergunta.php';
require_once APP_PATH . 'models/RespostaChecklist.php';
require_once APP_PATH . 'models/ModuloAvaliacao.php';

$checklistId = $_GET['id'] ?? 1;

$checklistModel = new Checklist();
$perguntaModel = new Pergunta();
$respostaModel = new RespostaChecklist();
$moduloModel = new ModuloAvaliacao();

$checklist = $checklistModel->buscarPorId($checklistId);

if (!$checklist) {
    die('Checklist não encontrado');
}

// Buscar TODOS os módulos ativos e suas perguntas
$modulos = $moduloModel->listarAtivos();
$todasPerguntas = [];
$totalPerguntas = 0;

foreach ($modulos as &$modulo) {
    $modulo['perguntas'] = $perguntaModel->listarPorModulo($modulo['id'], true);
    $todasPerguntas = array_merge($todasPerguntas, $modulo['perguntas']);
    $totalPerguntas += count($modulo['perguntas']);
}

// Buscar respostas já salvas
$respostasExistentes = $respostaModel->obterRespostasCompletas($checklistId);
$respostasMap = [];
foreach ($respostasExistentes as $resp) {
    $respostasMap[$resp['pergunta_id']] = $resp;
}

echo "<h1>🔍 Debug - HTML dos Módulos</h1>";
echo "<hr>";
echo "<p><strong>Checklist ID:</strong> {$checklistId}</p>";
echo "<p><strong>Total de módulos:</strong> " . count($modulos) . "</p>";
echo "<p><strong>Total de perguntas:</strong> {$totalPerguntas}</p>";
echo "<hr>";

echo "<h2>Estrutura de Renderização</h2>";
echo "<pre style='background: #f5f5f5; padding: 15px; border: 1px solid #ddd; overflow-x: auto;'>";

$perguntaGlobalIndex = 0;
$moduloIndex = 0;

foreach ($modulos as $modulo):
    $moduloIndex++;
    if (empty($modulo['perguntas'])) continue;

    echo "\n";
    echo "=================================================================\n";
    echo "MÓDULO #{$moduloIndex}: {$modulo['nome']}\n";
    echo "ID: {$modulo['id']} | Ordem: {$modulo['ordem']} | Perguntas: " . count($modulo['perguntas']) . "\n";
    echo "=================================================================\n";

    echo "→ ABRE: <div class=\"modulo-section\"> (linha 461)\n";
    echo "→ ABRE: <div class=\"modulo-header\"> (linha 462)\n";
    echo "→ FECHA: </div> modulo-header (linha 466)\n";
    echo "\n";

    $perguntaIndex = 0;
    foreach ($modulo['perguntas'] as $index => $pergunta):
        $perguntaGlobalIndex++;
        $perguntaIndex++;

        echo "   → PERGUNTA #{$perguntaIndex} (Global: #{$perguntaGlobalIndex})\n";
        echo "     ID: {$pergunta['id']} | Texto: " . substr($pergunta['texto'], 0, 50) . "...\n";
        echo "     → ABRE: <div class=\"pergunta-card\"> (linha 478)\n";
        echo "     → FECHA: </div> pergunta-card (linha 563)\n";

    endforeach;

    echo "\n";
    echo "→ ENDFOREACH perguntas (linha 564)\n";
    echo "→ FECHA: </div> modulo-section (linha 565)\n";
    echo "\n";

endforeach;

echo "=================================================================\n";
echo "→ ENDFOREACH módulos (linha 566)\n";
echo "=================================================================\n";

echo "</pre>";

echo "<hr>";
echo "<h2>Contadores Finais</h2>";
echo "<ul>";
echo "<li><strong>Total de módulos renderizados:</strong> {$moduloIndex}</li>";
echo "<li><strong>Total de perguntas renderizadas:</strong> {$perguntaGlobalIndex}</li>";
echo "<li><strong>Total esperado de perguntas:</strong> {$totalPerguntas}</li>";
echo "<li><strong>Match:</strong> " . ($perguntaGlobalIndex == $totalPerguntas ? '✅ OK' : '❌ ERRO') . "</li>";
echo "</ul>";

echo "<hr>";
echo "<h2>Análise de Divs</h2>";
echo "<p>Vou contar quantas vezes cada div é aberta e fechada:</p>";

ob_start();
$perguntaGlobalIndex = 0;
foreach ($modulos as $modulo):
    if (empty($modulo['perguntas'])) continue;
?>
    <div class="modulo-section">
        <div class="modulo-header">
            <h2><?php echo $modulo['nome']; ?></h2>
        </div>

        <?php
        foreach ($modulo['perguntas'] as $index => $pergunta):
            $perguntaGlobalIndex++;
        ?>
        <div class="pergunta-card" data-pergunta-id="<?php echo $pergunta['id']; ?>">
            <p>Pergunta <?php echo $perguntaGlobalIndex; ?></p>
        </div>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>
<?php
$htmlGerado = ob_get_clean();

// Contar divs
$abreModuloSection = substr_count($htmlGerado, '<div class="modulo-section">');
$fechaModuloSection = substr_count($htmlGerado, '</div>');
$abrePerguntaCard = substr_count($htmlGerado, '<div class="pergunta-card"');

echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr style='background: #f0f0f0;'><th>Tag</th><th>Quantidade</th><th>Esperado</th><th>Status</th></tr>";

echo "<tr>";
echo "<td><code>&lt;div class=\"modulo-section\"&gt;</code></td>";
echo "<td><strong>{$abreModuloSection}</strong></td>";
echo "<td>" . count($modulos) . "</td>";
echo "<td>" . ($abreModuloSection == count($modulos) ? '✅' : '❌') . "</td>";
echo "</tr>";

echo "<tr>";
echo "<td><code>&lt;div class=\"pergunta-card\"&gt;</code></td>";
echo "<td><strong>{$abrePerguntaCard}</strong></td>";
echo "<td>{$totalPerguntas}</td>";
echo "<td>" . ($abrePerguntaCard == $totalPerguntas ? '✅' : '❌') . "</td>";
echo "</tr>";

echo "</table>";

echo "<hr>";
echo "<h2>HTML Gerado (Simplificado)</h2>";
echo "<textarea style='width: 100%; height: 400px; font-family: monospace; font-size: 12px;'>";
echo htmlspecialchars($htmlGerado);
echo "</textarea>";

echo "<hr>";
echo "<p><a href='checklist/editar.php?id={$checklistId}'>← Voltar para Editar</a></p>";
?>
