<?php
/**
 * Teste Direto - Listagem de Colaboradores
 * Este arquivo testa exatamente o que o sistema está retornando
 */

// Define constante do sistema
define('SGC_SYSTEM', true);

// Carrega configurações e classes
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/classes/Database.php';
require_once __DIR__ . '/../app/models/Colaborador.php';
require_once __DIR__ . '/../app/controllers/ColaboradorController.php';

header('Content-Type: text/html; charset=utf-8');

// Instancia controller EXATAMENTE como na página real
$controller = new ColaboradorController();

// Lista colaboradores EXATAMENTE como na página real
$resultado = $controller->listar();
$colaboradores = $resultado['data'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste - Listagem Real</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1400px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #667eea; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 13px; }
        th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
        th { background: #667eea; color: white; }
        tr:hover { background: #f5f5f5; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
        .badge { padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-empty { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
<div class="container">
    <h1>🧪 Teste Real - Sistema de Listagem</h1>

    <div class="success">
        <strong>✅ Este teste usa EXATAMENTE o mesmo código da listagem real!</strong>
    </div>

    <h2>📊 Informações Retornadas</h2>
    <div class="warning">
        <strong>Total de colaboradores:</strong> <?php echo $resultado['total']; ?><br>
        <strong>Registros nesta página:</strong> <?php echo count($colaboradores); ?><br>
        <strong>Página atual:</strong> <?php echo $resultado['page']; ?><br>
        <strong>Por página:</strong> <?php echo $resultado['per_page']; ?>
    </div>

    <?php if (empty($colaboradores)): ?>
        <div class="error">
            ❌ ERRO: Nenhum colaborador retornado pelo controller!
        </div>
    <?php else: ?>

        <h2>📋 Primeiros 5 Colaboradores - Dados Completos</h2>

        <?php
        $primeiros5 = array_slice($colaboradores, 0, 5);
        foreach ($primeiros5 as $index => $col):
        ?>
            <h3 style="color: #667eea;">Colaborador #<?php echo ($index + 1); ?> (ID: <?php echo $col['id'] ?? 'SEM ID'; ?>)</h3>
            <pre><?php print_r($col); ?></pre>

            <div style="background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px;">
                <strong>Verificação de Campos:</strong><br><br>

                <table style="margin: 0;">
                    <tr>
                        <th>Campo</th>
                        <th>Valor</th>
                        <th>isset()</th>
                        <th>empty()</th>
                        <th>Status</th>
                    </tr>
                    <tr>
                        <td><code>$col['nivel_hierarquico']</code></td>
                        <td><?php echo var_export($col['nivel_hierarquico'] ?? null, true); ?></td>
                        <td><?php echo isset($col['nivel_hierarquico']) ? '✅ true' : '❌ false'; ?></td>
                        <td><?php echo empty($col['nivel_hierarquico']) ? '❌ true (vazio)' : '✅ false (tem valor)'; ?></td>
                        <td>
                            <?php if (!empty($col['nivel_hierarquico'])): ?>
                                <span class="badge badge-success">OK</span>
                            <?php else: ?>
                                <span class="badge badge-empty">VAZIO</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><code>$col['cargo']</code></td>
                        <td><?php echo var_export($col['cargo'] ?? null, true); ?></td>
                        <td><?php echo isset($col['cargo']) ? '✅ true' : '❌ false'; ?></td>
                        <td><?php echo empty($col['cargo']) ? '❌ true (vazio)' : '✅ false (tem valor)'; ?></td>
                        <td>
                            <?php if (!empty($col['cargo'])): ?>
                                <span class="badge badge-success">OK</span>
                            <?php else: ?>
                                <span class="badge badge-empty">VAZIO</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><code>$col['departamento_exibicao']</code></td>
                        <td><?php echo var_export($col['departamento_exibicao'] ?? null, true); ?></td>
                        <td><?php echo isset($col['departamento_exibicao']) ? '✅ true' : '❌ false'; ?></td>
                        <td><?php echo empty($col['departamento_exibicao']) ? '❌ true (vazio)' : '✅ false (tem valor)'; ?></td>
                        <td>
                            <?php if (!empty($col['departamento_exibicao'])): ?>
                                <span class="badge badge-success">OK</span>
                            <?php else: ?>
                                <span class="badge badge-empty">VAZIO</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
            <hr>
        <?php endforeach; ?>

        <h2>📊 Tabela Como Aparece na Listagem Real</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Nível Hierárquico</th>
                    <th>Cargo</th>
                    <th>Setor</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($primeiros5 as $col): ?>
                <tr>
                    <td><?php echo $col['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($col['nome']); ?></strong></td>
                    <td><?php echo htmlspecialchars($col['email']); ?></td>
                    <td>
                        <?php if (!empty($col['nivel_hierarquico'])): ?>
                            <span class="badge" style="background: #d1ecf1; color: #0c5460;">
                                <?php echo htmlspecialchars($col['nivel_hierarquico']); ?>
                            </span>
                        <?php else: ?>
                            <span style="color: #999;">-</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo !empty($col['cargo']) ? htmlspecialchars($col['cargo']) : '-'; ?></td>
                    <td><?php echo !empty($col['departamento_exibicao']) ? htmlspecialchars($col['departamento_exibicao']) : '-'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2>🔍 Análise</h2>
        <?php
        $totalNivelPreenchido = 0;
        $totalCargoPreenchido = 0;
        $totalSetorPreenchido = 0;

        foreach ($colaboradores as $col) {
            if (!empty($col['nivel_hierarquico'])) $totalNivelPreenchido++;
            if (!empty($col['cargo'])) $totalCargoPreenchido++;
            if (!empty($col['departamento_exibicao'])) $totalSetorPreenchido++;
        }

        $totalRegistros = count($colaboradores);
        ?>
        <div class="warning">
            <strong>Estatísticas desta página:</strong><br>
            • Colaboradores com Nível Hierárquico: <?php echo $totalNivelPreenchido; ?> de <?php echo $totalRegistros; ?><br>
            • Colaboradores com Cargo: <?php echo $totalCargoPreenchido; ?> de <?php echo $totalRegistros; ?><br>
            • Colaboradores com Setor: <?php echo $totalSetorPreenchido; ?> de <?php echo $totalRegistros; ?>
        </div>

        <?php if ($totalNivelPreenchido === 0): ?>
            <div class="error">
                <strong>❌ PROBLEMA CONFIRMADO:</strong><br>
                O campo 'nivel_hierarquico' NÃO está sendo retornado pelo controller, mesmo existindo no banco!<br><br>
                <strong>Causa provável:</strong> Cache do OPcache/PHP no servidor.<br><br>
                <strong>Solução:</strong> Reinicie o PHP-FPM ou Apache/Nginx do servidor.
            </div>
        <?php else: ?>
            <div class="success">
                <strong>✅ DADOS SENDO RETORNADOS CORRETAMENTE!</strong><br>
                Se não aparecem na listagem real, limpe o cache do navegador (Ctrl+Shift+R)
            </div>
        <?php endif; ?>

    <?php endif; ?>

    <div style="margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
        <strong>💡 Próximos Passos:</strong><br>
        1. Se os dados aparecem aqui mas não na listagem real, é cache do navegador<br>
        2. Se não aparecem aqui, é cache do servidor PHP/OPcache<br>
        3. Após verificar, apague este arquivo por segurança
    </div>
</div>
</body>
</html>
