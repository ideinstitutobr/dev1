<?php
/**
 * Debug: Teste de Renderização da View
 */

define('SGC_SYSTEM', true);
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/classes/Database.php';
require_once __DIR__ . '/../app/classes/Auth.php';
require_once __DIR__ . '/../app/models/Colaborador.php';
require_once __DIR__ . '/../app/controllers/ColaboradorController.php';

$controller = new ColaboradorController();
$resultado = $controller->listar();
$colaboradores = $resultado['data'];

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Debug View</title>
    <style>
        body { font-family: monospace; padding: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #667eea; color: white; }
        .badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        .highlight { background: yellow; }
    </style>
</head>
<body>
    <h1>🔍 Debug: Renderização da View</h1>

    <h2>1. Dados Brutos do Controller</h2>
    <pre><?php print_r($resultado); ?></pre>

    <h2>2. Teste da Mesma Lógica do listar.php</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Nível Hierárquico (código)</th>
                <th>Nível Hierárquico (renderizado)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($colaboradores as $col): ?>
            <tr>
                <td><?php echo $col['id']; ?></td>
                <td><?php echo e($col['nome']); ?></td>
                <td>
                    <pre style="margin: 0; background: #f0f0f0; padding: 5px;">
<?php
echo "isset: " . (isset($col['nivel_hierarquico']) ? 'true' : 'false') . "\n";
echo "value: '" . ($col['nivel_hierarquico'] ?? 'NULL') . "'\n";
echo "empty: " . (empty($col['nivel_hierarquico']) ? 'true' : 'false') . "\n";
$nivelVal = $col['nivel_hierarquico'] ?? '';
echo "\$nivelVal = '" . $nivelVal . "'\n";
echo "strlen: " . strlen($nivelVal);
?>
                    </pre>
                </td>
                <td>
                    <?php $nivelVal = $col['nivel_hierarquico'] ?? ''; ?>
                    <span class="badge badge-info <?php echo $nivelVal !== '' ? 'highlight' : ''; ?>">
                        <?php echo $nivelVal !== '' ? e($nivelVal) : '-'; ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>3. Teste Simples (sem badge, sem e())</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Nível (direto)</th>
                <th>Nível (com e())</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($colaboradores as $col): ?>
            <tr>
                <td><?php echo $col['id']; ?></td>
                <td><?php echo $col['nome']; ?></td>
                <td><?php echo $col['nivel_hierarquico']; ?></td>
                <td><?php echo e($col['nivel_hierarquico']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>4. Array Keys do Primeiro Colaborador</h2>
    <?php if (!empty($colaboradores)): ?>
        <pre><?php print_r(array_keys($colaboradores[0])); ?></pre>
    <?php endif; ?>

    <hr>
    <p><a href="colaboradores/listar.php">← Ver Listagem Real</a></p>
</body>
</html>
