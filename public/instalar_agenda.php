<?php
/**
 * Instalador do Módulo de Agenda
 * Execute este arquivo UMA VEZ pelo navegador
 */

define('SGC_SYSTEM', true);

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/classes/Database.php';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalar Módulo de Agenda</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .header h1 { font-size: 32px; margin-bottom: 10px; }
        .content { padding: 40px; }
        .alert {
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid;
        }
        .alert-success {
            background: #d4edda;
            border-color: #155724;
            color: #155724;
        }
        .alert-danger {
            background: #f8d7da;
            border-color: #721c24;
            color: #721c24;
        }
        .btn {
            display: inline-block;
            padding: 15px 40px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        .log-box {
            background: #f8f9fa;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            max-height: 400px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.6;
        }
        .success-icon {
            font-size: 64px;
            text-align: center;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📅 Instalador do Módulo de Agenda</h1>
            <p>Gestão de Turmas e Datas de Treinamentos</p>
        </div>

        <div class="content">
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                echo '<div class="log-box">';

                try {
                    $db = Database::getInstance();
                    $pdo = $db->getConnection();

                    $sqlFile = __DIR__ . '/../database/migrations/migration_agenda.sql';

                    if (!file_exists($sqlFile)) {
                        throw new Exception("Arquivo SQL não encontrado");
                    }

                    $sql = file_get_contents($sqlFile);
                    $statements = array_filter(array_map('trim', explode(';', $sql)));

                    $sucessos = 0;
                    $erros = 0;

                    echo "<strong>🚀 Iniciando instalação...</strong><br><br>";

                    foreach ($statements as $statement) {
                        if (empty($statement)) continue;

                        $statement = preg_replace('/--.*$/m', '', $statement);
                        $statement = trim($statement);

                        if (empty($statement)) continue;

                        try {
                            $pdo->exec($statement);
                            $sucessos++;

                            if (stripos($statement, 'CREATE TABLE') !== false) {
                                preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches);
                                $tabela = $matches[1] ?? 'desconhecida';
                                echo "✅ Tabela '<strong>$tabela</strong>' criada<br>";
                            } elseif (stripos($statement, 'ALTER TABLE') !== false) {
                                echo "✅ Tabela alterada com sucesso<br>";
                            } else {
                                echo "✅ Comando executado<br>";
                            }

                        } catch (PDOException $e) {
                            $erros++;

                            if (strpos($e->getMessage(), 'already exists') !== false ||
                                strpos($e->getMessage(), 'Duplicate column') !== false) {
                                echo "⚠️ Já existe (pulando)<br>";
                            } else {
                                echo "❌ Erro: " . htmlspecialchars($e->getMessage()) . "<br>";
                            }
                        }
                    }

                    echo "<br><strong>📊 Resultado:</strong><br>";
                    echo "✅ Sucessos: <strong>$sucessos</strong><br>";
                    echo "⚠️ Avisos: <strong>$erros</strong><br>";

                    echo '</div>';

                    ?>
                    <div class="success-icon">🎉</div>
                    <div class="alert alert-success">
                        <strong>✅ Instalação concluída!</strong><br>
                        O módulo de Agenda está pronto para uso.
                    </div>

                    <div style="text-align: center; margin-top: 30px;">
                        <a href="treinamentos/listar.php" class="btn">
                            📚 Ir para Treinamentos
                        </a>
                    </div>
                    <?php

                } catch (Exception $e) {
                    echo '</div>';
                    ?>
                    <div class="alert alert-danger">
                        <strong>❌ Erro:</strong><br>
                        <?php echo htmlspecialchars($e->getMessage()); ?>
                    </div>
                    <?php
                }

            } else {
                ?>
                <div style="text-align: center;">
                    <p style="margin-bottom: 30px;">Este script criará a tabela <code>agenda_treinamentos</code> e adicionará o campo <code>agenda_id</code> na tabela de participantes.</p>

                    <form method="POST">
                        <button type="submit" class="btn">
                            🚀 Iniciar Instalação
                        </button>
                    </form>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
</body>
</html>
