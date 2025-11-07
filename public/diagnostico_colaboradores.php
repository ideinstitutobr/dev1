<?php
/**
 * Script de Diagnóstico - Listagem de Colaboradores
 * Acesse via navegador: http://seusite.com/diagnostico_colaboradores.php
 */

// Define constante do sistema
define('SGC_SYSTEM', true);

// Carrega configurações
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/classes/Database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico - Colaboradores</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #667eea; padding-bottom: 10px; }
        h2 { color: #667eea; margin-top: 30px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #667eea; color: white; font-weight: 600; }
        tr:hover { background: #f5f5f5; }
        .badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
        code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔍 Diagnóstico - Sistema de Colaboradores</h1>

    <?php
    try {
        $pdo = Database::getInstance()->getConnection();
        echo '<div class="success">✅ Conexão com banco de dados estabelecida com sucesso!</div>';

        // 1. Verifica estrutura da tabela
        echo '<h2>1️⃣ Estrutura da Tabela Colaboradores</h2>';
        $stmt = $pdo->prepare("DESCRIBE colaboradores");
        $stmt->execute();
        $colunas = $stmt->fetchAll();

        echo '<table>';
        echo '<thead><tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Default</th><th>Extra</th></tr></thead>';
        echo '<tbody>';
        $temNivelHierarquico = false;
        $temUnidadePrincipal = false;
        $temSetorPrincipal = false;
        foreach ($colunas as $col) {
            echo '<tr>';
            echo '<td><strong>' . htmlspecialchars($col['Field']) . '</strong></td>';
            echo '<td>' . htmlspecialchars($col['Type']) . '</td>';
            echo '<td>' . htmlspecialchars($col['Null']) . '</td>';
            echo '<td>' . htmlspecialchars($col['Default'] ?? 'NULL') . '</td>';
            echo '<td>' . htmlspecialchars($col['Extra']) . '</td>';
            echo '</tr>';

            if ($col['Field'] === 'nivel_hierarquico') $temNivelHierarquico = true;
            if ($col['Field'] === 'unidade_principal_id') $temUnidadePrincipal = true;
            if ($col['Field'] === 'setor_principal') $temSetorPrincipal = true;
        }
        echo '</tbody></table>';

        // Verifica campos críticos
        echo '<div class="info">';
        echo '<strong>Status dos Campos:</strong><br>';
        echo '• Campo <code>nivel_hierarquico</code>: ' . ($temNivelHierarquico ? '<span class="badge badge-success">EXISTE</span>' : '<span class="badge badge-danger">NÃO EXISTE</span>') . '<br>';
        echo '• Campo <code>unidade_principal_id</code>: ' . ($temUnidadePrincipal ? '<span class="badge badge-success">EXISTE</span>' : '<span class="badge badge-danger">NÃO EXISTE</span>') . '<br>';
        echo '• Campo <code>setor_principal</code>: ' . ($temSetorPrincipal ? '<span class="badge badge-success">EXISTE</span>' : '<span class="badge badge-danger">NÃO EXISTE</span>') . '<br>';
        echo '</div>';

        // 2. Conta colaboradores
        echo '<h2>2️⃣ Estatísticas da Tabela</h2>';
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM colaboradores");
        $total = $stmt->fetch()['total'];

        $stmt = $pdo->query("SELECT COUNT(*) as total FROM colaboradores WHERE nivel_hierarquico IS NOT NULL AND nivel_hierarquico != ''");
        $totalComNivel = $stmt->fetch()['total'];

        echo '<div class="info">';
        echo '<strong>Total de colaboradores:</strong> ' . $total . '<br>';
        echo '<strong>Colaboradores com Nível Hierárquico preenchido:</strong> ' . $totalComNivel . '<br>';
        echo '<strong>Colaboradores SEM Nível Hierárquico:</strong> ' . ($total - $totalComNivel);
        echo '</div>';

        // 3. Busca primeiros 10 registros
        echo '<h2>3️⃣ Primeiros 10 Colaboradores (Dados Brutos)</h2>';

        if ($temUnidadePrincipal && $temSetorPrincipal) {
            $sql = "SELECT
                        c.id,
                        c.nome,
                        c.email,
                        c.nivel_hierarquico,
                        c.cargo,
                        c.departamento,
                        c.unidade_principal_id,
                        c.setor_principal,
                        u.nome as unidade_nome
                    FROM colaboradores c
                    LEFT JOIN unidades u ON c.unidade_principal_id = u.id
                    ORDER BY c.id ASC
                    LIMIT 10";
        } else {
            $sql = "SELECT
                        c.id,
                        c.nome,
                        c.email,
                        c.nivel_hierarquico,
                        c.cargo,
                        c.departamento
                    FROM colaboradores c
                    ORDER BY c.id ASC
                    LIMIT 10";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $colaboradores = $stmt->fetchAll();

        if (empty($colaboradores)) {
            echo '<div class="warning">⚠️ Nenhum colaborador encontrado no banco de dados!</div>';
        } else {
            echo '<table>';
            echo '<thead><tr>';
            echo '<th>ID</th>';
            echo '<th>Nome</th>';
            echo '<th>Email</th>';
            echo '<th>Nível Hierárquico</th>';
            echo '<th>Cargo</th>';
            echo '<th>Departamento/Setor</th>';
            if ($temUnidadePrincipal) echo '<th>Unidade</th>';
            echo '</tr></thead>';
            echo '<tbody>';

            foreach ($colaboradores as $col) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($col['id']) . '</td>';
                echo '<td><strong>' . htmlspecialchars($col['nome']) . '</strong></td>';
                echo '<td>' . htmlspecialchars($col['email']) . '</td>';

                // Nível Hierárquico
                if (!empty($col['nivel_hierarquico'])) {
                    echo '<td><span class="badge badge-info">' . htmlspecialchars($col['nivel_hierarquico']) . '</span></td>';
                } else {
                    echo '<td><span class="badge badge-danger">VAZIO</span></td>';
                }

                // Cargo
                echo '<td>' . (empty($col['cargo']) ? '-' : htmlspecialchars($col['cargo'])) . '</td>';

                // Departamento/Setor
                if ($temSetorPrincipal) {
                    $setor = $col['setor_principal'] ?? $col['departamento'] ?? null;
                    echo '<td>' . (empty($setor) ? '-' : htmlspecialchars($setor)) . '</td>';
                } else {
                    echo '<td>' . (empty($col['departamento']) ? '-' : htmlspecialchars($col['departamento'])) . '</td>';
                }

                // Unidade
                if ($temUnidadePrincipal) {
                    echo '<td>' . (empty($col['unidade_nome']) ? '-' : htmlspecialchars($col['unidade_nome'])) . '</td>';
                }

                echo '</tr>';
            }

            echo '</tbody></table>';
        }

        // 4. Query usada pelo sistema
        echo '<h2>4️⃣ Query SQL Usada pelo Sistema</h2>';
        echo '<div class="info">';
        echo '<strong>Estrutura detectada:</strong> ' . ($temUnidadePrincipal && $temSetorPrincipal ? 'Nova (com unidades e setores)' : 'Antiga (sem unidades)') . '<br><br>';
        echo '<strong>Query SQL:</strong>';
        echo '<pre>' . htmlspecialchars($sql) . '</pre>';
        echo '</div>';

        // 5. Recomendações
        echo '<h2>5️⃣ Análise e Recomendações</h2>';

        if ($totalComNivel == 0 && $total > 0) {
            echo '<div class="error">';
            echo '<strong>❌ PROBLEMA IDENTIFICADO:</strong><br>';
            echo 'Existem ' . $total . ' colaboradores cadastrados, mas NENHUM tem o campo <code>nivel_hierarquico</code> preenchido!<br><br>';
            echo '<strong>Possíveis causas:</strong><br>';
            echo '1. O campo não está sendo salvo no formulário de cadastro<br>';
            echo '2. Os dados foram importados sem este campo<br>';
            echo '3. Houve uma migração incompleta<br><br>';
            echo '<strong>Solução:</strong> Edite um colaborador existente e selecione o Nível Hierárquico, depois salve para testar.';
            echo '</div>';
        } elseif ($totalComNivel < $total) {
            echo '<div class="warning">';
            echo '<strong>⚠️ AVISO:</strong><br>';
            echo ($total - $totalComNivel) . ' colaboradores estão sem Nível Hierárquico preenchido.<br>';
            echo 'Estes aparecerão com "-" na listagem.';
            echo '</div>';
        } else {
            echo '<div class="success">';
            echo '<strong>✅ TUDO CERTO:</strong><br>';
            echo 'Todos os colaboradores têm Nível Hierárquico preenchido!';
            echo '</div>';
        }

    } catch (Exception $e) {
        echo '<div class="error">❌ Erro: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    ?>

    <div style="margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 5px; border-left: 4px solid #667eea;">
        <strong>💡 Próximos Passos:</strong><br>
        1. Verifique se o campo "Nível Hierárquico" está aparecendo nos dados acima<br>
        2. Se estiver vazio, teste cadastrar/editar um colaborador<br>
        3. Volte nesta página e recarregue para verificar se foi salvo<br>
        4. <strong>IMPORTANTE:</strong> Apague este arquivo após o diagnóstico por questões de segurança!
    </div>
</div>
</body>
</html>
