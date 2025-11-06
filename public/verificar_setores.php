<?php
/**
 * Verificador e Corretor do Sistema de Setores
 * Este script verifica e corrige problemas no sistema de setores
 */

define('SGC_SYSTEM', true);
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/classes/Database.php';
require_once __DIR__ . '/../app/classes/Auth.php';

// Requer admin
Auth::requireLogin();
Auth::requireAdmin();

$db = Database::getInstance();
$pdo = $db->getConnection();

$problemas = [];
$correcoes = [];
$avisos = [];

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar Sistema de Setores</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2d3748;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        .status {
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .check-item {
            padding: 10px;
            margin: 5px 0;
            border-left: 4px solid #667eea;
            background: #f8f9fa;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            font-weight: 600;
        }
        .btn:hover {
            background: #5568d3;
        }
        .code {
            background: #2d3748;
            color: #68d391;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-family: monospace;
            margin: 10px 0;
        }
        ul {
            margin: 10px 0;
            padding-left: 30px;
        }
        li {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Verificação do Sistema de Setores</h1>

        <?php
        // 1. Verificar tabela unidade_setores
        echo "<h2>1. Verificando Tabela unidade_setores</h2>";
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'unidade_setores'");
            if ($stmt->fetch()) {
                echo '<div class="status success">✓ Tabela <strong>unidade_setores</strong> existe</div>';

                // Verifica estrutura
                $stmt = $pdo->query("DESCRIBE unidade_setores");
                $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $requiredColumns = ['id', 'unidade_id', 'setor', 'descricao', 'responsavel_colaborador_id', 'ativo', 'created_at', 'updated_at'];
                $missing = array_diff($requiredColumns, $columns);

                if (empty($missing)) {
                    echo '<div class="status success">✓ Estrutura da tabela está correta</div>';
                } else {
                    $problemas[] = "Faltam colunas na tabela unidade_setores: " . implode(', ', $missing);
                    echo '<div class="status error">✗ Faltam colunas: ' . implode(', ', $missing) . '</div>';
                }

                // Conta registros
                $stmt = $pdo->query("SELECT COUNT(*) FROM unidade_setores");
                $count = $stmt->fetchColumn();
                echo "<div class='status info'>ℹ️ Total de setores cadastrados em unidades: <strong>{$count}</strong></div>";

            } else {
                $problemas[] = "Tabela unidade_setores não existe";
                echo '<div class="status error">✗ Tabela <strong>unidade_setores</strong> NÃO existe</div>';
                echo '<div class="status warning">';
                echo '<strong>Solução:</strong> Execute a migration 003_create_unidade_setores.sql<br>';
                echo '<code>mysql -u root -p sgc_db < database/migrations/003_create_unidade_setores.sql</code>';
                echo '</div>';
            }
        } catch (Exception $e) {
            $problemas[] = "Erro ao verificar tabela: " . $e->getMessage();
            echo '<div class="status error">✗ Erro: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }

        // 2. Verificar setores globais em field_categories
        echo "<h2>2. Verificando Setores Globais</h2>";
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM field_categories WHERE tipo = 'setor' AND ativo = 1");
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                echo "<div class='status success'>✓ Existem <strong>{$count}</strong> setores globais cadastrados</div>";

                echo "<div class='check-item'>";
                echo "<strong>Setores disponíveis:</strong><ul>";
                $stmt = $pdo->query("SELECT valor FROM field_categories WHERE tipo = 'setor' AND ativo = 1 ORDER BY valor");
                $setores = $stmt->fetchAll(PDO::FETCH_COLUMN);
                foreach ($setores as $setor) {
                    echo "<li>" . htmlspecialchars($setor) . "</li>";
                }
                echo "</ul></div>";

            } else {
                $problemas[] = "Nenhum setor global cadastrado";
                echo '<div class="status error">✗ Nenhum setor global cadastrado</div>';
                echo '<div class="status warning">';
                echo '<strong>Solução:</strong> Execute a migration 008_populate_setores_iniciais.sql<br>';
                echo 'Ou crie setores manualmente em: <a href="/public/unidades/setores_globais/cadastrar.php">Cadastrar Setores Globais</a>';
                echo '</div>';
            }
        } catch (Exception $e) {
            $problemas[] = "Erro ao verificar setores globais: " . $e->getMessage();
            echo '<div class="status error">✗ Erro: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }

        // 3. Verificar tabela unidade_colaboradores
        echo "<h2>3. Verificando Tabela unidade_colaboradores</h2>";
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'unidade_colaboradores'");
            if ($stmt->fetch()) {
                echo '<div class="status success">✓ Tabela <strong>unidade_colaboradores</strong> existe</div>';

                $stmt = $pdo->query("SELECT COUNT(*) FROM unidade_colaboradores");
                $count = $stmt->fetchColumn();
                echo "<div class='status info'>ℹ️ Total de vínculos colaborador-setor: <strong>{$count}</strong></div>";
            } else {
                $problemas[] = "Tabela unidade_colaboradores não existe";
                echo '<div class="status error">✗ Tabela <strong>unidade_colaboradores</strong> NÃO existe</div>';
                echo '<div class="status warning">';
                echo '<strong>Solução:</strong> Execute a migration 004_create_unidade_colaboradores.sql';
                echo '</div>';
            }
        } catch (Exception $e) {
            echo '<div class="status warning">⚠️ ' . htmlspecialchars($e->getMessage()) . '</div>';
        }

        // 4. Verificar unidades disponíveis
        echo "<h2>4. Verificando Unidades</h2>";
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM unidades WHERE ativo = 1");
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                echo "<div class='status success'>✓ Existem <strong>{$count}</strong> unidades ativas</div>";

                echo "<div class='check-item'>";
                echo "<strong>Primeiras 5 unidades:</strong><ul>";
                $stmt = $pdo->query("SELECT id, nome FROM unidades WHERE ativo = 1 LIMIT 5");
                $unidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($unidades as $unidade) {
                    echo "<li>" . htmlspecialchars($unidade['nome']) . " (ID: {$unidade['id']})</li>";
                }
                echo "</ul></div>";
            } else {
                $avisos[] = "Nenhuma unidade cadastrada";
                echo '<div class="status warning">⚠️ Nenhuma unidade cadastrada. Você precisa cadastrar unidades primeiro.</div>';
            }
        } catch (Exception $e) {
            echo '<div class="status error">✗ Erro: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }

        // 5. Verificar arquivos necessários
        echo "<h2>5. Verificando Arquivos do Sistema</h2>";
        $arquivos = [
            '/app/models/UnidadeSetor.php' => 'Model UnidadeSetor',
            '/app/controllers/UnidadeSetorController.php' => 'Controller UnidadeSetorController',
            '/public/unidades/setores_globais/listar.php' => 'Listagem de Setores Globais',
            '/public/unidades/setores_globais/cadastrar.php' => 'Cadastro de Setores Globais',
            '/public/unidades/setores/gerenciar.php' => 'Gerenciamento de Setores por Unidade',
            '/public/unidades/setores/actions.php' => 'Actions de Setores',
        ];

        $basePath = dirname(__DIR__);
        $arquivosOk = true;
        foreach ($arquivos as $arquivo => $desc) {
            if (file_exists($basePath . $arquivo)) {
                echo "<div class='check-item'>✓ {$desc}</div>";
            } else {
                $arquivosOk = false;
                $problemas[] = "Arquivo ausente: {$arquivo}";
                echo "<div class='status error'>✗ {$desc} não encontrado: {$arquivo}</div>";
            }
        }

        if ($arquivosOk) {
            echo '<div class="status success">✓ Todos os arquivos necessários estão presentes</div>';
        }

        // Resumo Final
        echo "<h2>📊 Resumo Final</h2>";

        if (empty($problemas)) {
            echo '<div class="status success">';
            echo '<strong>✓ Sistema de Setores está funcionando corretamente!</strong><br><br>';
            echo '<strong>Como usar:</strong><ul>';
            echo '<li><strong>1. Criar Setores Globais:</strong> <a href="/public/unidades/setores_globais/listar.php">Acesse aqui</a> para criar setores como "Vendas", "Caixa", "Estoque", etc.</li>';
            echo '<li><strong>2. Ativar Setores em Unidades:</strong> Vá em cada unidade e ative os setores que ela possui</li>';
            echo '<li><strong>3. Vincular Colaboradores:</strong> Vincule colaboradores aos setores de cada unidade</li>';
            echo '</ul></div>';

            if (!empty($avisos)) {
                echo '<div class="status warning">';
                echo '<strong>⚠️ Avisos:</strong><ul>';
                foreach ($avisos as $aviso) {
                    echo "<li>{$aviso}</li>";
                }
                echo '</ul></div>';
            }
        } else {
            echo '<div class="status error">';
            echo '<strong>✗ Foram encontrados problemas:</strong><ul>';
            foreach ($problemas as $problema) {
                echo "<li>{$problema}</li>";
            }
            echo '</ul></div>';

            echo '<div class="status info">';
            echo '<strong>📝 Solução Recomendada:</strong><br><br>';
            echo 'Execute o script de migração completo que irá criar todas as tabelas e popular os dados iniciais:';
            echo '<div class="code">';
            echo 'cd /home/user/dev1/database/migrations<br>';
            echo 'php executar_migrations_unidades.php';
            echo '</div>';
            echo '</div>';
        }
        ?>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #e1e8ed;">
            <a href="/public/unidades/setores_globais/listar.php" class="btn">📋 Ir para Setores Globais</a>
            <a href="/public/unidades/listar.php" class="btn" style="background: #6c757d;">🏢 Ir para Unidades</a>
            <a href="/public/dashboard.php" class="btn" style="background: #48bb78;">🏠 Voltar ao Dashboard</a>
        </div>
    </div>
</body>
</html>
