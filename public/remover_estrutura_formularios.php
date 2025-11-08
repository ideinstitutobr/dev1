<?php
/**
 * Script de Remoção: Estrutura de Formulários/Checklists
 *
 * ATENÇÃO: Este script remove PERMANENTEMENTE:
 * - Diretório public/checklist/ e todos os arquivos
 * - Tabelas do banco de dados relacionadas a checklists
 * - Models e Controllers relacionados
 * - Links do menu
 *
 * A interface (HTML/CSS/JS) foi documentada em: DOCUMENTACAO_INTERFACE_PERGUNTAS.md
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Remover Estrutura de Formulários</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #dc3545; border-bottom: 3px solid #dc3545; padding-bottom: 10px; }
        h2 { color: #333; margin-top: 30px; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .danger { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 20px 0; border-radius: 5px; color: #721c24; }
        .success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0; border-radius: 5px; color: #155724; }
        .info { background: #d1ecf1; border-left: 4px solid #17a2b8; padding: 15px; margin: 20px 0; border-radius: 5px; color: #0c5460; }
        ul { line-height: 1.8; }
        .btn { padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: 600; margin: 10px 5px; text-decoration: none; display: inline-block; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; }
        code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; font-family: 'Courier New', monospace; }
        .file-list { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; max-height: 300px; overflow-y: auto; }
        .file-list li { font-family: 'Courier New', monospace; font-size: 13px; }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>🗑️ Remover Estrutura de Formulários/Checklists</h1>";

// Verificar se foi confirmado
if (!isset($_POST['confirmar']) || $_POST['confirmar'] !== 'SIM_DELETAR_TUDO') {

    echo "<div class='danger'>
        <h3>⚠️ ATENÇÃO - OPERAÇÃO IRREVERSÍVEL</h3>
        <p><strong>Este script irá DELETAR PERMANENTEMENTE toda a estrutura de formulários!</strong></p>
    </div>";

    echo "<div class='info'>
        <h3>📋 A documentação da interface foi salva em:</h3>
        <p><code>DOCUMENTACAO_INTERFACE_PERGUNTAS.md</code></p>
        <p>Este arquivo contém TODO o código HTML, CSS e JavaScript do sistema de estrelas, observações e upload de fotos.</p>
    </div>";

    echo "<h2>📁 Arquivos que serão removidos:</h2>";
    echo "<div class='file-list'><ul>";

    $diretorios = [
        'public/checklist/',
        'app/models/ (Checklist.php, ModuloAvaliacao.php, Pergunta.php, RespostaChecklist.php)',
        'app/controllers/ (ChecklistController.php)',
        'app/helpers/ (PontuacaoHelper.php)'
    ];

    foreach ($diretorios as $dir) {
        echo "<li>$dir</li>";
    }
    echo "</ul></div>";

    echo "<h2>🗄️ Tabelas do banco que serão removidas:</h2>";
    echo "<div class='file-list'><ul>";

    $tabelas = [
        'checklists',
        'respostas_checklist',
        'modulos_avaliacao',
        'perguntas',
        'fotos_evidencia_checklist (se existir)'
    ];

    foreach ($tabelas as $tabela) {
        echo "<li>$tabela</li>";
    }
    echo "</ul></div>";

    echo "<h2>🔧 Outras alterações:</h2>";
    echo "<ul>
        <li>Remoção de links de formulários do menu lateral (sidebar.php)</li>
        <li>Limpeza de arquivos de migração relacionados</li>
        <li>Arquivos de verificação e diagnóstico serão removidos</li>
    </ul>";

    echo "<div class='warning'>
        <h3>⚠️ Antes de continuar:</h3>
        <ul>
            <li>✅ Verifique se você salvou o arquivo <code>DOCUMENTACAO_INTERFACE_PERGUNTAS.md</code></li>
            <li>✅ Faça backup do banco de dados se necessário</li>
            <li>✅ Confirme que deseja remover TUDO relacionado a formulários</li>
        </ul>
    </div>";

    echo "<form method='POST' style='margin-top: 30px;'>
        <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; border: 2px solid #dc3545;'>
            <label style='font-weight: 600; display: block; margin-bottom: 10px;'>
                Digite exatamente <code>SIM_DELETAR_TUDO</code> para confirmar:
            </label>
            <input type='text' name='confirmar' required
                   style='padding: 10px; font-size: 16px; width: 300px; border: 2px solid #ddd; border-radius: 5px;'
                   autocomplete='off'>
        </div>
        <div style='margin-top: 20px;'>
            <button type='submit' class='btn btn-danger'>🗑️ Remover Tudo</button>
            <a href='/' class='btn btn-secondary'>❌ Cancelar e Voltar</a>
        </div>
    </form>";

} else {
    // EXECUTAR REMOÇÃO

    echo "<h2>🔄 Iniciando remoção...</h2>";

    $erros = [];
    $sucessos = [];

    // 1. Remover tabelas do banco
    echo "<h3>1️⃣ Removendo tabelas do banco de dados...</h3>";
    try {
        require_once __DIR__ . '/../app/config/config.php';
        require_once __DIR__ . '/../app/config/database.php';
        require_once __DIR__ . '/../app/classes/Database.php';

        $db = Database::getInstance();
        $pdo = $db->getConnection();

        $tabelas = [
            'respostas_checklist',
            'checklists',
            'perguntas',
            'modulos_avaliacao'
        ];

        foreach ($tabelas as $tabela) {
            try {
                $pdo->exec("DROP TABLE IF EXISTS $tabela");
                $sucessos[] = "✅ Tabela <code>$tabela</code> removida";
            } catch (Exception $e) {
                $erros[] = "❌ Erro ao remover tabela <code>$tabela</code>: " . $e->getMessage();
            }
        }

    } catch (Exception $e) {
        $erros[] = "❌ Erro de conexão com banco: " . $e->getMessage();
    }

    // 2. Remover diretório public/checklist
    echo "<h3>2️⃣ Removendo diretório public/checklist/...</h3>";
    $checklistDir = __DIR__ . '/checklist';
    if (is_dir($checklistDir)) {
        $command = "rm -rf " . escapeshellarg($checklistDir);
        exec($command, $output, $returnCode);
        if ($returnCode === 0) {
            $sucessos[] = "✅ Diretório <code>public/checklist/</code> removido";
        } else {
            $erros[] = "❌ Erro ao remover diretório public/checklist/";
        }
    } else {
        $sucessos[] = "✅ Diretório public/checklist/ não existe";
    }

    // 3. Remover Models
    echo "<h3>3️⃣ Removendo Models...</h3>";
    $models = [
        __DIR__ . '/../app/models/Checklist.php',
        __DIR__ . '/../app/models/ModuloAvaliacao.php',
        __DIR__ . '/../app/models/Pergunta.php',
        __DIR__ . '/../app/models/RespostaChecklist.php'
    ];

    foreach ($models as $file) {
        if (file_exists($file)) {
            if (unlink($file)) {
                $sucessos[] = "✅ Removido: " . basename($file);
            } else {
                $erros[] = "❌ Erro ao remover: " . basename($file);
            }
        }
    }

    // 4. Remover Controllers
    echo "<h3>4️⃣ Removendo Controllers...</h3>";
    $controller = __DIR__ . '/../app/controllers/ChecklistController.php';
    if (file_exists($controller)) {
        if (unlink($controller)) {
            $sucessos[] = "✅ Removido: ChecklistController.php";
        } else {
            $erros[] = "❌ Erro ao remover: ChecklistController.php";
        }
    }

    // 5. Remover Helpers
    echo "<h3>5️⃣ Removendo Helpers...</h3>";
    $helper = __DIR__ . '/../app/helpers/PontuacaoHelper.php';
    if (file_exists($helper)) {
        if (unlink($helper)) {
            $sucessos[] = "✅ Removido: PontuacaoHelper.php";
        } else {
            $erros[] = "❌ Erro ao remover: PontuacaoHelper.php";
        }
    }

    // 6. Remover scripts de verificação
    echo "<h3>6️⃣ Removendo scripts auxiliares...</h3>";
    $scripts = [
        __DIR__ . '/verificar_estrutura_modulos.php',
        __DIR__ . '/migration_adicionar_tipo_modulos.php'
    ];

    foreach ($scripts as $file) {
        if (file_exists($file)) {
            if (unlink($file)) {
                $sucessos[] = "✅ Removido: " . basename($file);
            } else {
                $erros[] = "❌ Erro ao remover: " . basename($file);
            }
        }
    }

    // 7. Atualizar sidebar - remover links de formulários
    echo "<h3>7️⃣ Atualizando menu (sidebar)...</h3>";
    $sidebarFile = __DIR__ . '/../app/views/layouts/sidebar.php';
    if (file_exists($sidebarFile)) {
        $sidebarContent = file_get_contents($sidebarFile);

        // Remover seção de formulários
        $sidebarContent = preg_replace(
            '/<!-- Formulários START -->.*?<!-- Formulários END -->/s',
            '',
            $sidebarContent
        );

        // Alternativa: remover seção completa de Formulários
        $sidebarContent = preg_replace(
            '/<li>\s*<a[^>]*toggleSubmenu\(\'formularios\'\)[^>]*>.*?<\/ul>\s*<\/li>/s',
            '',
            $sidebarContent
        );

        if (file_put_contents($sidebarFile, $sidebarContent)) {
            $sucessos[] = "✅ Menu atualizado (links de formulários removidos)";
        } else {
            $erros[] = "❌ Erro ao atualizar menu";
        }
    }

    // Exibir resultados
    echo "<div style='margin-top: 30px;'>";

    if (!empty($sucessos)) {
        echo "<div class='success'><h3>✅ Operações bem-sucedidas:</h3><ul>";
        foreach ($sucessos as $msg) {
            echo "<li>$msg</li>";
        }
        echo "</ul></div>";
    }

    if (!empty($erros)) {
        echo "<div class='danger'><h3>❌ Erros encontrados:</h3><ul>";
        foreach ($erros as $msg) {
            echo "<li>$msg</li>";
        }
        echo "</ul></div>";
    }

    echo "</div>";

    echo "<div class='info' style='margin-top: 30px;'>
        <h3>📝 Próximos passos:</h3>
        <ul>
            <li>✅ Verifique o arquivo <code>DOCUMENTACAO_INTERFACE_PERGUNTAS.md</code> com a interface documentada</li>
            <li>✅ Faça commit das alterações</li>
            <li>✅ Reconstrua a estrutura de formulários do zero conforme necessário</li>
        </ul>
    </div>";

    echo "<div style='margin-top: 30px;'>
        <a href='/' class='btn btn-secondary'>← Voltar para o Sistema</a>
    </div>";
}

echo "</div></body></html>";
