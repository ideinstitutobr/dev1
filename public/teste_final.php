<?php
/**
 * TESTE DEFINITIVO - Verificar qual arquivo está sendo carregado
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
    <title>TESTE DEFINITIVO</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f0f0f0; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #667eea; border-bottom: 3px solid #667eea; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 2px solid #ddd; padding: 12px; text-align: left; }
        th { background: #667eea; color: white; font-weight: bold; }
        tr:nth-child(even) { background: #f8f9fa; }
        .badge { padding: 8px 15px; border-radius: 20px; font-size: 14px; font-weight: 600; display: inline-block; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        .highlight { background: yellow !important; padding: 5px; font-weight: bold; font-size: 16px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info-box { background: #e7f3ff; border-left: 4px solid #2196F3; padding: 15px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔥 TESTE DEFINITIVO - Nível Hierárquico</h1>

        <div class="info-box">
            <strong>📍 Arquivo carregado:</strong> <?php echo __FILE__; ?><br>
            <strong>⏰ Timestamp:</strong> <?php echo date('Y-m-d H:i:s'); ?><br>
            <strong>🔢 Total de colaboradores:</strong> <?php echo count($colaboradores); ?>
        </div>

        <h2>✅ Tabela SIMPLES - Apenas Dados Brutos</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Nível (DIRETO)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($colaboradores as $col): ?>
                <tr>
                    <td><?php echo $col['id']; ?></td>
                    <td><?php echo $col['nome']; ?></td>
                    <td class="highlight"><?php echo $col['nivel_hierarquico']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2>✅ Tabela COM Badge (como no listar.php)</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Nível Hierárquico</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($colaboradores as $col): ?>
                <tr>
                    <td><?php echo $col['id']; ?></td>
                    <td><?php echo $col['nome']; ?></td>
                    <td>
                        <span class="badge badge-info">
                            <?php echo e($col['nivel_hierarquico']); ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2>📋 Dados Completos do Primeiro Colaborador</h2>
        <?php if (!empty($colaboradores)): ?>
            <pre style="background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto;">
<?php print_r($colaboradores[0]); ?>
            </pre>
        <?php endif; ?>

        <hr>
        <div style="margin-top: 30px; padding: 20px; background: #fff3cd; border-left: 4px solid #ffc107;">
            <h3>🎯 SE OS NÍVEIS APARECEM AQUI MAS NÃO NO listar.php:</h3>
            <ul>
                <li><strong>Cache do Servidor:</strong> O servidor pode estar servindo arquivo antigo em cache</li>
                <li><strong>URL Errada:</strong> Você pode estar acessando outro ambiente/domínio</li>
                <li><strong>Arquivo Diferente:</strong> Pode haver outro listar.php sendo carregado</li>
            </ul>
            <p><strong>SOLUÇÃO:</strong> Acesse o listar.php com esta URL exata:</p>
            <p><code style="background: #f0f0f0; padding: 5px 10px; border-radius: 3px;">
                <?php echo BASE_URL; ?>colaboradores/listar.php
            </code></p>
        </div>

        <hr>
        <p><a href="colaboradores/listar.php" style="background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 20px;">
            ← Ver Listagem Real (listar.php)
        </a></p>
    </div>
</body>
</html>
