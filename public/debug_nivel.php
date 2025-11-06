<?php
/**
 * Teste de Debug - Verificar Nível Hierárquico
 */

define('SGC_SYSTEM', true);
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/classes/Database.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

echo "<h1>🔍 Debug: Nível Hierárquico</h1>";
echo "<style>
body { font-family: monospace; padding: 20px; }
table { border-collapse: collapse; width: 100%; margin: 20px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background: #667eea; color: white; }
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
</style>";

try {
    // 1. Verifica se a coluna existe
    echo "<h2>1. Verificar se a coluna 'nivel_hierarquico' existe</h2>";
    $stmt = $pdo->prepare("
        SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
        AND table_name = 'colaboradores'
        AND column_name = 'nivel_hierarquico'
    ");
    $stmt->execute();
    $colInfo = $stmt->fetch();

    if ($colInfo) {
        echo "<p class='success'>✅ Coluna existe!</p>";
        echo "<pre>";
        print_r($colInfo);
        echo "</pre>";
    } else {
        echo "<p class='error'>❌ Coluna NÃO existe!</p>";
        die("Erro fatal: Coluna nivel_hierarquico não encontrada.");
    }

    // 2. Conta colaboradores
    echo "<h2>2. Total de Colaboradores</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM colaboradores");
    $total = $stmt->fetch()['total'];
    echo "<p>Total de colaboradores: <strong>{$total}</strong></p>";

    // 3. Verifica quantos têm nível hierárquico preenchido
    echo "<h2>3. Colaboradores com Nível Hierárquico</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM colaboradores WHERE nivel_hierarquico IS NOT NULL AND nivel_hierarquico != ''");
    $comNivel = $stmt->fetch()['total'];
    echo "<p>Colaboradores com nível preenchido: <strong>{$comNivel}</strong></p>";

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM colaboradores WHERE nivel_hierarquico IS NULL OR nivel_hierarquico = ''");
    $semNivel = $stmt->fetch()['total'];
    echo "<p>Colaboradores SEM nível: <strong class='error'>{$semNivel}</strong></p>";

    // 4. Mostra alguns registros
    echo "<h2>4. Primeiros 10 Colaboradores (incluindo nivel_hierarquico)</h2>";
    $stmt = $pdo->query("SELECT id, nome, email, nivel_hierarquico, cargo, departamento FROM colaboradores ORDER BY id DESC LIMIT 10");
    $colaboradores = $stmt->fetchAll();

    if (empty($colaboradores)) {
        echo "<p class='error'>Nenhum colaborador encontrado!</p>";
    } else {
        echo "<table>";
        echo "<tr><th>ID</th><th>Nome</th><th>Email</th><th>Nível Hierárquico</th><th>Cargo</th><th>Departamento</th></tr>";
        foreach ($colaboradores as $col) {
            $nivelDisplay = ($col['nivel_hierarquico'] ?? '') !== '' ? $col['nivel_hierarquico'] : '<span class="error">VAZIO</span>';
            echo "<tr>";
            echo "<td>{$col['id']}</td>";
            echo "<td>{$col['nome']}</td>";
            echo "<td>{$col['email']}</td>";
            echo "<td>{$nivelDisplay}</td>";
            echo "<td>" . ($col['cargo'] ?? '-') . "</td>";
            echo "<td>" . ($col['departamento'] ?? '-') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    // 5. Testa a query exata que o listar.php usa
    echo "<h2>5. Teste da Query do Controller</h2>";
    $sql = "SELECT * FROM colaboradores ORDER BY nome ASC LIMIT 5";
    $stmt = $pdo->query($sql);
    $resultado = $stmt->fetchAll();

    echo "<p>Campos retornados pela query:</p>";
    if (!empty($resultado)) {
        echo "<pre>";
        print_r(array_keys($resultado[0]));
        echo "</pre>";

        echo "<p>Primeiro registro completo:</p>";
        echo "<pre>";
        print_r($resultado[0]);
        echo "</pre>";
    }

    // 6. Valores ENUM possíveis
    echo "<h2>6. Valores ENUM de nivel_hierarquico</h2>";
    $stmt = $pdo->prepare("SELECT COLUMN_TYPE FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'colaboradores' AND column_name = 'nivel_hierarquico'");
    $stmt->execute();
    $row = $stmt->fetch();
    if ($row && isset($row['COLUMN_TYPE'])) {
        echo "<p>Definição do ENUM:</p>";
        echo "<pre>{$row['COLUMN_TYPE']}</pre>";

        if (preg_match("/^enum\\((.*)\\)$/i", $row['COLUMN_TYPE'], $m)) {
            preg_match_all("/'((?:\\\\'|[^'])*)'/", $m[1], $matches);
            echo "<p>Valores permitidos:</p><ul>";
            foreach ($matches[1] as $v) {
                echo "<li>" . str_replace("\\'", "'", $v) . "</li>";
            }
            echo "</ul>";
        }
    }

} catch (Exception $e) {
    echo "<p class='error'>❌ ERRO: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='colaboradores/listar.php'>← Voltar para Listagem</a></p>";
?>
