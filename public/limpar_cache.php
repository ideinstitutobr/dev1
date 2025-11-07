<?php
/**
 * Script para Limpar Cache do PHP/OPcache
 * Acesse este arquivo via navegador para limpar o cache
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Limpar Cache PHP</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 15px rgba(0,0,0,0.1); }
        h1 { color: #667eea; border-bottom: 3px solid #667eea; padding-bottom: 10px; }
        .success { background: #d4edda; color: #155724; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 5px solid #28a745; }
        .error { background: #f8d7da; color: #721c24; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 5px solid #dc3545; }
        .info { background: #d1ecf1; color: #0c5460; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 5px solid #17a2b8; }
        .warning { background: #fff3cd; color: #856404; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 5px solid #ffc107; }
        ul { line-height: 2; }
        .btn { display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; font-weight: 600; }
        .btn:hover { background: #5568d3; }
    </style>
</head>
<body>
<div class="container">
    <h1>🧹 Limpar Cache do PHP</h1>

    <?php
    $cacheCleared = false;
    $opcacheEnabled = false;
    $apcuEnabled = false;
    $results = [];

    // Verifica e limpa OPcache
    if (function_exists('opcache_reset')) {
        $opcacheEnabled = true;
        try {
            if (opcache_reset()) {
                $results[] = '✅ OPcache limpo com sucesso!';
                $cacheCleared = true;
            } else {
                $results[] = '⚠️ OPcache: Falha ao limpar (pode estar desabilitado)';
            }
        } catch (Exception $e) {
            $results[] = '⚠️ OPcache: ' . $e->getMessage();
        }
    }

    // Verifica e limpa APCu
    if (function_exists('apcu_clear_cache')) {
        $apcuEnabled = true;
        try {
            if (apcu_clear_cache()) {
                $results[] = '✅ APCu cache limpo com sucesso!';
                $cacheCleared = true;
            } else {
                $results[] = '⚠️ APCu: Falha ao limpar';
            }
        } catch (Exception $e) {
            $results[] = '⚠️ APCu: ' . $e->getMessage();
        }
    }

    // Verifica realpath cache
    if (function_exists('clearstatcache')) {
        clearstatcache(true);
        $results[] = '✅ Realpath/stat cache limpo com sucesso!';
        $cacheCleared = true;
    }

    // Exibe resultados
    if ($cacheCleared) {
        echo '<div class="success">';
        echo '<h2>✅ Cache Limpo!</h2>';
        foreach ($results as $result) {
            echo '<p>' . $result . '</p>';
        }
        echo '</div>';
    } else {
        echo '<div class="warning">';
        echo '<h2>⚠️ Nenhum Cache Encontrado</h2>';
        echo '<p>Nenhum sistema de cache PHP está ativo ou não foi possível limpar.</p>';
        echo '</div>';
    }

    // Informações do sistema
    echo '<h2>ℹ️ Informações do Sistema</h2>';
    echo '<div class="info">';
    echo '<strong>Versão do PHP:</strong> ' . PHP_VERSION . '<br>';
    echo '<strong>OPcache:</strong> ' . ($opcacheEnabled ? '✅ Habilitado' : '❌ Desabilitado') . '<br>';
    if ($opcacheEnabled && function_exists('opcache_get_status')) {
        $status = opcache_get_status(false);
        if ($status) {
            echo '<strong>OPcache Status:</strong> ' . ($status['opcache_enabled'] ? '✅ Ativo' : '❌ Inativo') . '<br>';
        }
    }
    echo '<strong>APCu:</strong> ' . ($apcuEnabled ? '✅ Habilitado' : '❌ Desabilitado') . '<br>';
    echo '<strong>Server API:</strong> ' . php_sapi_name() . '<br>';
    echo '</div>';

    // Instruções
    echo '<h2>📋 Instruções</h2>';
    echo '<div class="info">';
    echo '<ol style="line-height: 2;">';
    echo '<li>O cache do PHP foi limpo (se disponível)</li>';
    echo '<li>Agora acesse: <a href="test_listagem.php" target="_blank"><strong>test_listagem.php</strong></a> para testar</li>';
    echo '<li>Compare os resultados com a <a href="colaboradores/listar.php" target="_blank"><strong>listagem real</strong></a></li>';
    echo '<li>Se necessário, reinicie o serviço PHP-FPM/Apache do servidor</li>';
    echo '<li><strong>IMPORTANTE:</strong> Apague este arquivo após o teste!</li>';
    echo '</ol>';
    echo '</div>';

    // Comandos para reiniciar serviços (apenas informativo)
    echo '<h2>🔧 Comandos para Reiniciar Serviços PHP</h2>';
    echo '<div class="warning">';
    echo '<strong>Se o problema persistir, execute no servidor:</strong><br><br>';
    echo '<strong>PHP-FPM (Ubuntu/Debian):</strong><br>';
    echo '<code style="background: #f8f9fa; padding: 5px 10px; border-radius: 3px; display: inline-block; margin: 5px 0;">sudo service php8.2-fpm restart</code><br>';
    echo '<code style="background: #f8f9fa; padding: 5px 10px; border-radius: 3px; display: inline-block; margin: 5px 0;">sudo systemctl restart php8.2-fpm</code><br><br>';

    echo '<strong>Apache:</strong><br>';
    echo '<code style="background: #f8f9fa; padding: 5px 10px; border-radius: 3px; display: inline-block; margin: 5px 0;">sudo service apache2 restart</code><br><br>';

    echo '<strong>Nginx + PHP-FPM:</strong><br>';
    echo '<code style="background: #f8f9fa; padding: 5px 10px; border-radius: 3px; display: inline-block; margin: 5px 0;">sudo service nginx restart && sudo service php8.2-fpm restart</code><br><br>';

    echo '<em>Nota: Substitua "8.2" pela sua versão do PHP</em>';
    echo '</div>';
    ?>

    <div style="text-align: center; margin-top: 30px;">
        <a href="test_listagem.php" class="btn">🧪 Testar Listagem</a>
        <a href="colaboradores/listar.php" class="btn">📋 Ver Listagem Real</a>
        <a href="diagnostico_colaboradores.php" class="btn">🔍 Diagnóstico Completo</a>
    </div>

    <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px; border: 2px dashed #667eea;">
        <h3 style="margin-top: 0;">🔐 Segurança</h3>
        <p style="margin: 0;">Após concluir os testes, apague os seguintes arquivos:</p>
        <ul style="margin: 10px 0;">
            <li><code>public/limpar_cache.php</code> (este arquivo)</li>
            <li><code>public/test_listagem.php</code></li>
            <li><code>public/diagnostico_colaboradores.php</code></li>
        </ul>
    </div>
</div>
</body>
</html>
