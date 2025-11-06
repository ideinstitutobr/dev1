<?php
/**
 * CORREÇÃO RÁPIDA: Atualiza field_categories para suportar setores
 * Execute este arquivo AGORA para corrigir o erro
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

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corrigir Banco de Dados - Setores</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
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
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover {
            background: #5568d3;
        }
        .btn-success {
            background: #28a745;
        }
        .btn-success:hover {
            background: #218838;
        }
        pre {
            background: #2d3748;
            color: #68d391;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Correção Rápida do Banco de Dados</h1>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['executar'])) {
            echo '<h2>Executando correções...</h2>';
            echo '<pre>';

            try {
                // 1. Adiciona coluna descricao
                echo "1. Adicionando coluna 'descricao'...\n";
                try {
                    $pdo->exec("ALTER TABLE field_categories ADD COLUMN descricao TEXT COMMENT 'Descrição detalhada da categoria'");
                    echo "✅ Coluna 'descricao' adicionada com sucesso!\n\n";
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                        echo "⚠️  Coluna 'descricao' já existe (OK)\n\n";
                    } else {
                        throw $e;
                    }
                }

                // 2. Modifica ENUM tipo
                echo "2. Atualizando ENUM 'tipo' para incluir 'setor'...\n";
                try {
                    $pdo->exec("ALTER TABLE field_categories MODIFY COLUMN tipo ENUM('cargo', 'departamento', 'setor') NOT NULL COMMENT 'Tipo de categoria'");
                    echo "✅ ENUM 'tipo' atualizado com sucesso!\n\n";
                } catch (PDOException $e) {
                    echo "⚠️  " . $e->getMessage() . "\n\n";
                }

                // 3. Verifica estrutura final
                echo "3. Verificando estrutura final...\n";
                $stmt = $pdo->query("SHOW COLUMNS FROM field_categories WHERE Field IN ('tipo', 'descricao')");
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($columns as $col) {
                    echo "   - {$col['Field']}: {$col['Type']}\n";
                }
                echo "\n";

                // 4. Popula setores iniciais se não existirem
                echo "4. Verificando setores iniciais...\n";
                $stmt = $pdo->query("SELECT COUNT(*) FROM field_categories WHERE tipo = 'setor'");
                $count = $stmt->fetchColumn();

                if ($count == 0) {
                    echo "   Nenhum setor cadastrado. Populando setores iniciais...\n";

                    $setoresIniciais = [
                        'Vendas',
                        'Estoque',
                        'Administrativo',
                        'Financeiro',
                        'Recursos Humanos',
                        'TI',
                        'Marketing',
                        'Atendimento ao Cliente',
                        'Logística',
                        'Compras',
                        'Comercial',
                        'Operações'
                    ];

                    $stmt = $pdo->prepare("INSERT IGNORE INTO field_categories (tipo, valor, ativo) VALUES ('setor', ?, 1)");
                    foreach ($setoresIniciais as $setor) {
                        $stmt->execute([$setor]);
                    }

                    $stmt = $pdo->query("SELECT COUNT(*) FROM field_categories WHERE tipo = 'setor'");
                    $count = $stmt->fetchColumn();
                    echo "   ✅ {$count} setores iniciais cadastrados!\n\n";
                } else {
                    echo "   ✅ Já existem {$count} setores cadastrados\n\n";
                }

                echo "==================================================\n";
                echo "✅ CORREÇÃO CONCLUÍDA COM SUCESSO!\n";
                echo "==================================================\n";
                echo "</pre>";

                echo '<div class="status success">';
                echo '<strong>✅ Banco de dados corrigido com sucesso!</strong><br>';
                echo 'Agora você pode criar setores normalmente.';
                echo '</div>';

                echo '<div style="margin-top: 20px;">';
                echo '<a href="/public/unidades/setores_globais/cadastrar.php" class="btn btn-success">✅ Testar Criar Setor</a>';
                echo '<a href="/public/unidades/setores_globais/listar.php" class="btn">📋 Ver Setores</a>';
                echo '<a href="/public/dashboard.php" class="btn">🏠 Dashboard</a>';
                echo '</div>';

            } catch (Exception $e) {
                echo "</pre>";
                echo '<div class="status error">';
                echo '<strong>❌ Erro ao executar correção:</strong><br>';
                echo htmlspecialchars($e->getMessage());
                echo '</div>';
            }

        } else {
            // Mostra status atual e botão para executar
            echo '<div class="status warning">';
            echo '<strong>⚠️ Problema Detectado:</strong><br>';
            echo 'A tabela field_categories não está preparada para suportar setores.<br>';
            echo 'Clique no botão abaixo para corrigir automaticamente.';
            echo '</div>';

            echo '<h2>O que será feito:</h2>';
            echo '<ul>';
            echo '<li>✅ Adicionar coluna <code>descricao</code> na tabela field_categories</li>';
            echo '<li>✅ Atualizar ENUM <code>tipo</code> para incluir \'setor\'</li>';
            echo '<li>✅ Popular 12 setores iniciais (se não existirem)</li>';
            echo '</ul>';

            echo '<div class="status info">';
            echo '<strong>ℹ️ Setores que serão criados:</strong><br>';
            echo 'Vendas, Estoque, Administrativo, Financeiro, Recursos Humanos, TI, Marketing, ';
            echo 'Atendimento ao Cliente, Logística, Compras, Comercial, Operações';
            echo '</div>';

            echo '<form method="POST" action="">';
            echo '<input type="hidden" name="executar" value="1">';
            echo '<button type="submit" class="btn btn-success" style="font-size: 18px; padding: 15px 40px;">🔧 EXECUTAR CORREÇÃO AGORA</button>';
            echo '</form>';

            echo '<div style="margin-top: 30px;">';
            echo '<a href="/public/verificar_setores.php" class="btn">🔍 Verificar Sistema</a>';
            echo '<a href="/public/dashboard.php" class="btn">🏠 Dashboard</a>';
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>
