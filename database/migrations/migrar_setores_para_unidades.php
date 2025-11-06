<?php
/**
 * Migração: Sistema de Setores
 *
 * Este script migra o sistema de setores do modelo antigo (vinculado a colaboradores)
 * para o novo modelo hierárquico (Setores Globais → Unidades → Colaboradores).
 *
 * IMPORTANTE: Faça backup do banco de dados antes de executar!
 *
 * O que este script faz:
 * 1. Migra setores de 'departamento' para 'setor' no field_categories
 * 2. Adiciona campos unidade_principal_id e setor_principal na tabela colaboradores
 * 3. Migra dados de departamento para setor_principal
 * 4. Popular unidade_setores com setores usados (se houver unidades cadastradas)
 */

define('SGC_SYSTEM', true);
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';

// Verifica se é CLI ou Web
$isCLI = php_sapi_name() === 'cli';

if (!$isCLI) {
    Auth::requireLogin();
    Auth::requireAdmin();

    echo '<!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Migração de Setores</title>
        <style>
            body { font-family: monospace; padding: 20px; background: #f5f5f5; }
            .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            h1 { color: #2c3e50; }
            .step { padding: 15px; margin: 10px 0; border-left: 4px solid #667eea; background: #f8f9fa; }
            .success { border-left-color: #28a745; background: #d4edda; color: #155724; }
            .warning { border-left-color: #ffc107; background: #fff3cd; color: #856404; }
            .error { border-left-color: #dc3545; background: #f8d7da; color: #721c24; }
            .info { border-left-color: #17a2b8; background: #d1ecf1; color: #0c5460; }
            .btn { display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; }
            code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🔄 Migração do Sistema de Setores</h1>
            <p>Este script irá migrar o sistema de setores para a nova estrutura hierárquica.</p>
    ';
}

$db = Database::getInstance();
$pdo = $db->getConnection();

$logs = [];
$erros = [];
$avisos = [];

function log_msg($msg, $type = 'info') {
    global $logs, $isCLI;
    $logs[] = ['msg' => $msg, 'type' => $type];

    if ($isCLI) {
        $prefix = ['success' => '✓', 'error' => '✗', 'warning' => '⚠', 'info' => 'ℹ'][$type] ?? '•';
        echo "$prefix $msg\n";
    } else {
        echo "<div class='step $type'>$msg</div>";
        flush();
    }
}

try {
    log_msg('Iniciando migração...', 'info');
    log_msg('Data/Hora: ' . date('d/m/Y H:i:s'), 'info');

    // ========================================
    // ETAPA 1: Migrar setores de departamento para setor
    // ========================================
    log_msg('', 'info');
    log_msg('📦 ETAPA 1: Migrando setores de "departamento" para "setor"', 'info');

    // Busca todos os departamentos
    $stmt = $pdo->query("SELECT * FROM field_categories WHERE tipo = 'departamento' AND ativo = 1");
    $departamentos = $stmt->fetchAll();

    log_msg("Encontrados " . count($departamentos) . " departamento(s) para migrar", 'info');

    $migrados = 0;
    $duplicados = 0;

    foreach ($departamentos as $dep) {
        // Verifica se já existe como setor
        $stmt = $pdo->prepare("SELECT id FROM field_categories WHERE tipo = 'setor' AND valor = ?");
        $stmt->execute([$dep['valor']]);

        if ($stmt->fetch()) {
            log_msg("  • '{$dep['valor']}' já existe como setor (pulando)", 'warning');
            $duplicados++;
        } else {
            // Insere como setor
            $stmt = $pdo->prepare("INSERT INTO field_categories (tipo, valor, descricao, ativo, created_at) VALUES ('setor', ?, ?, 1, NOW())");
            $stmt->execute([$dep['valor'], $dep['descricao']]);
            log_msg("  • '{$dep['valor']}' migrado com sucesso", 'success');
            $migrados++;
        }
    }

    log_msg("Migração de setores concluída: $migrados migrado(s), $duplicados já existente(s)", 'success');

    // ========================================
    // ETAPA 2: Adicionar campos na tabela colaboradores
    // ========================================
    log_msg('', 'info');
    log_msg('📊 ETAPA 2: Adicionando campos na tabela colaboradores', 'info');

    // Verifica se campos já existem
    $stmt = $pdo->query("SHOW COLUMNS FROM colaboradores LIKE 'unidade_principal_id'");
    $tem_unidade = $stmt->rowCount() > 0;

    $stmt = $pdo->query("SHOW COLUMNS FROM colaboradores LIKE 'setor_principal'");
    $tem_setor = $stmt->rowCount() > 0;

    if (!$tem_unidade) {
        $pdo->exec("ALTER TABLE colaboradores ADD COLUMN unidade_principal_id INT NULL AFTER departamento");
        $pdo->exec("ALTER TABLE colaboradores ADD CONSTRAINT fk_colab_unidade_principal FOREIGN KEY (unidade_principal_id) REFERENCES unidades(id) ON DELETE SET NULL");
        log_msg("  • Campo 'unidade_principal_id' adicionado com sucesso", 'success');
    } else {
        log_msg("  • Campo 'unidade_principal_id' já existe", 'info');
    }

    if (!$tem_setor) {
        $pdo->exec("ALTER TABLE colaboradores ADD COLUMN setor_principal VARCHAR(100) NULL AFTER unidade_principal_id");
        log_msg("  • Campo 'setor_principal' adicionado com sucesso", 'success');
    } else {
        log_msg("  • Campo 'setor_principal' já existe", 'info');
    }

    // ========================================
    // ETAPA 3: Migrar dados de departamento para setor_principal
    // ========================================
    log_msg('', 'info');
    log_msg('🔄 ETAPA 3: Migrando dados de departamento → setor_principal', 'info');

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM colaboradores WHERE departamento IS NOT NULL AND departamento != '' AND (setor_principal IS NULL OR setor_principal = '')");
    $total_migrar = $stmt->fetch()['total'];

    if ($total_migrar > 0) {
        $pdo->exec("UPDATE colaboradores SET setor_principal = departamento WHERE departamento IS NOT NULL AND departamento != '' AND (setor_principal IS NULL OR setor_principal = '')");
        log_msg("  • $total_migrar colaborador(es) tiveram o setor atualizado", 'success');
    } else {
        log_msg("  • Nenhum colaborador precisa ser atualizado", 'info');
    }

    // ========================================
    // ETAPA 4: Popular unidade_setores (opcional)
    // ========================================
    log_msg('', 'info');
    log_msg('🏢 ETAPA 4: Populando setores nas unidades existentes', 'info');

    // Verifica se há unidades cadastradas
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM unidades WHERE ativo = 1");
    $total_unidades = $stmt->fetch()['total'];

    if ($total_unidades == 0) {
        log_msg("  • Nenhuma unidade cadastrada ainda. Os setores serão ativados quando as unidades forem criadas.", 'warning');
    } else {
        // Para cada unidade, adiciona todos os setores disponíveis (desativados por padrão)
        log_msg("  • Encontradas $total_unidades unidade(s)", 'info');

        $stmt_unidades = $pdo->query("SELECT id, nome FROM unidades WHERE ativo = 1");
        $unidades = $stmt_unidades->fetchAll();

        $stmt_setores = $pdo->query("SELECT DISTINCT valor FROM field_categories WHERE tipo = 'setor' AND ativo = 1");
        $setores = $stmt_setores->fetchAll(PDO::FETCH_COLUMN);

        log_msg("  • Encontrados " . count($setores) . " setor(es) disponíveis", 'info');

        $adicionados = 0;
        $ja_existentes = 0;

        foreach ($unidades as $unidade) {
            foreach ($setores as $setor) {
                // Verifica se já existe
                $stmt = $pdo->prepare("SELECT id FROM unidade_setores WHERE unidade_id = ? AND setor = ?");
                $stmt->execute([$unidade['id'], $setor]);

                if (!$stmt->fetch()) {
                    // Adiciona setor à unidade (inativo por padrão)
                    $stmt = $pdo->prepare("INSERT INTO unidade_setores (unidade_id, setor, ativo) VALUES (?, ?, 0)");
                    $stmt->execute([$unidade['id'], $setor]);
                    $adicionados++;
                } else {
                    $ja_existentes++;
                }
            }
        }

        log_msg("  • $adicionados setor(es) adicionado(s) às unidades", 'success');
        if ($ja_existentes > 0) {
            log_msg("  • $ja_existentes vínculo(s) já existiam", 'info');
        }

        log_msg("  • Importante: Os setores foram adicionados como INATIVOS. Acesse cada unidade para ativar os setores necessários.", 'warning');
    }

    // ========================================
    // RESUMO FINAL
    // ========================================
    log_msg('', 'info');
    log_msg('✅ MIGRAÇÃO CONCLUÍDA COM SUCESSO!', 'success');
    log_msg('', 'info');
    log_msg('📋 Próximos passos:', 'info');
    log_msg('1. Acesse "Unidades > Setores Globais" para gerenciar os setores', 'info');
    log_msg('2. Em cada unidade, ative os setores necessários', 'info');
    log_msg('3. Ao cadastrar colaboradores, selecione a unidade e depois o setor', 'info');
    log_msg('4. (Opcional) Remova o campo "departamento" do cadastro de colaboradores', 'info');

} catch (Exception $e) {
    log_msg('', 'error');
    log_msg('❌ ERRO NA MIGRAÇÃO: ' . $e->getMessage(), 'error');
    log_msg('Linha: ' . $e->getLine() . ' | Arquivo: ' . $e->getFile(), 'error');

    if (!$isCLI) {
        echo '<div class="step error"><strong>A migração foi interrompida devido a um erro.</strong><br>Verifique os logs acima e corrija o problema antes de executar novamente.</div>';
    }
}

if (!$isCLI) {
    echo '<a href="../../public/unidades/setores_globais/listar.php" class="btn">Ir para Setores Globais</a>';
    echo '</div></body></html>';
}
