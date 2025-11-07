<?php
/**
 * Script de Migration: Adicionar campo foto_evidencia
 * Acesse via navegador: /public/checklist/migrate_foto_evidencia.php
 *
 * ATENÇÃO: Remova este arquivo após executar a migration!
 */

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';

// Verificar se usuário está logado (segurança)
Auth::requireLogin();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migration - Foto Evidência</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 50px auto;
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
            color: #333;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #28a745;
            margin: 10px 0;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #dc3545;
            margin: 10px 0;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #17a2b8;
            margin: 10px 0;
        }
        pre {
            background: #f4f4f4;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .btn:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Migration: Adicionar Campo foto_evidencia</h1>

        <?php
        try {
            $db = Database::getInstance();
            $pdo = $db->getConnection();

            echo "<div class='info'><strong>Status:</strong> Conectado ao banco de dados</div>";

            // Verificar se a coluna já existe
            $stmt = $pdo->query("SHOW COLUMNS FROM respostas_checklist LIKE 'foto_evidencia'");
            $columnExists = $stmt->fetch();

            if ($columnExists) {
                echo "<div class='success'>";
                echo "<h3>✓ Migration Já Executada</h3>";
                echo "<p>A coluna 'foto_evidencia' já existe na tabela respostas_checklist.</p>";
                echo "<p>Não é necessário executar novamente.</p>";
                echo "</div>";
            } else {
                echo "<div class='info'>";
                echo "<h3>Executando Migration...</h3>";
                echo "</div>";

                // Adicionar coluna foto_evidencia
                $sql1 = "ALTER TABLE respostas_checklist
                        ADD COLUMN foto_evidencia VARCHAR(255) NULL AFTER observacao";

                $pdo->exec($sql1);
                echo "<div class='success'>✓ Coluna 'foto_evidencia' adicionada com sucesso</div>";

                // Criar índice
                $sql2 = "CREATE INDEX idx_foto_evidencia ON respostas_checklist(foto_evidencia)";
                $pdo->exec($sql2);
                echo "<div class='success'>✓ Índice 'idx_foto_evidencia' criado com sucesso</div>";

                echo "<div class='success'>";
                echo "<h3>✓ Migration Concluída!</h3>";
                echo "<p>A estrutura do banco de dados foi atualizada com sucesso.</p>";
                echo "</div>";
            }

            // Mostrar estrutura atual da tabela
            echo "<h3>Estrutura Atual da Tabela respostas_checklist:</h3>";
            $stmt = $pdo->query("DESCRIBE respostas_checklist");
            $columns = $stmt->fetchAll();

            echo "<pre>";
            foreach ($columns as $col) {
                echo sprintf("%-20s %-20s %-8s %-8s\n",
                    $col['Field'],
                    $col['Type'],
                    $col['Null'],
                    $col['Key']
                );
            }
            echo "</pre>";

            echo "<div class='info'>";
            echo "<h3>⚠️ Importante:</h3>";
            echo "<p>Por questões de segurança, <strong>remova este arquivo</strong> após executar a migration:</p>";
            echo "<pre>rm public/checklist/migrate_foto_evidencia.php</pre>";
            echo "</div>";

        } catch (PDOException $e) {
            echo "<div class='error'>";
            echo "<h3>✗ Erro ao Executar Migration</h3>";
            echo "<p><strong>Mensagem:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</div>";
        }
        ?>

        <a href="index.php" class="btn">← Voltar para Formulários</a>
    </div>
</body>
</html>
