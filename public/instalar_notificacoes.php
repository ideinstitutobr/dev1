<?php
/**
 * Instalador do Sistema de Notificações
 * Execute este arquivo UMA VEZ pelo navegador para criar as tabelas
 */

// Define constante do sistema
define('SGC_SYSTEM', true);

// Carrega configurações
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/classes/Database.php';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalar Sistema de Notificações</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

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

        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .header p {
            opacity: 0.9;
            font-size: 16px;
        }

        .content {
            padding: 40px;
        }

        .alert {
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid;
        }

        .alert-info {
            background: #d1ecf1;
            border-color: #0c5460;
            color: #0c5460;
        }

        .alert-warning {
            background: #fff3cd;
            border-color: #856404;
            color: #856404;
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
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
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

        .steps {
            margin: 30px 0;
        }

        .step {
            padding: 15px;
            margin: 10px 0;
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            border-radius: 4px;
        }

        .footer {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            color: #6c757d;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📧 Instalador do Sistema de Notificações</h1>
            <p>Configure o envio automático de e-mails para participantes</p>
        </div>

        <div class="content">
            <?php
            // Verifica se já foi executado
            $db = Database::getInstance();
            $pdo = $db->getConnection();

            $tabelaExiste = false;
            try {
                $stmt = $pdo->query("SHOW TABLES LIKE 'configuracoes_email'");
                $tabelaExiste = $stmt->rowCount() > 0;
            } catch (Exception $e) {
                // Ignora erro
            }

            if ($tabelaExiste && !isset($_GET['force'])) {
                ?>
                <div class="alert alert-warning">
                    <strong>⚠️ Atenção!</strong><br>
                    O sistema de notificações parece já estar instalado.<br>
                    <small>Se deseja reinstalar, <a href="?force=1">clique aqui</a></small>
                </div>

                <div class="steps">
                    <div class="step">
                        <strong>✅ Passo 1:</strong> Acessar Configurações > E-mail (SMTP)
                    </div>
                    <div class="step">
                        <strong>✅ Passo 2:</strong> Preencher dados do servidor SMTP
                    </div>
                    <div class="step">
                        <strong>✅ Passo 3:</strong> Testar conexão
                    </div>
                    <div class="step">
                        <strong>✅ Passo 4:</strong> Habilitar sistema de e-mail
                    </div>
                </div>

                <div style="text-align: center; margin-top: 30px;">
                    <a href="configuracoes/email.php" class="btn">
                        ⚙️ Ir para Configurações de E-mail
                    </a>
                </div>
                <?php
            } else {
                // EXECUTAR MIGRATION
                if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['force'])) {
                    echo '<div class="log-box" id="logBox">';

                    try {
                        // Lê arquivo SQL
                        $sqlFile = __DIR__ . '/../database/migrations/migration_notificacoes.sql';

                        if (!file_exists($sqlFile)) {
                            throw new Exception("Arquivo SQL não encontrado: $sqlFile");
                        }

                        $sql = file_get_contents($sqlFile);

                        if (!$sql) {
                            throw new Exception('Erro ao ler arquivo SQL');
                        }

                        // Separa e executa cada comando
                        $statements = array_filter(array_map('trim', explode(';', $sql)));

                        $sucessos = 0;
                        $erros = 0;

                        echo "<strong>🚀 Iniciando instalação...</strong><br><br>";

                        foreach ($statements as $statement) {
                            if (empty($statement)) continue;

                            // Remove comentários
                            $statement = preg_replace('/--.*$/m', '', $statement);
                            $statement = trim($statement);

                            if (empty($statement)) continue;

                            try {
                                $pdo->exec($statement);
                                $sucessos++;

                                // Identifica o tipo de comando
                                if (stripos($statement, 'CREATE TABLE') !== false) {
                                    preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches);
                                    $tabela = $matches[1] ?? 'desconhecida';
                                    echo "✅ Tabela '<strong>$tabela</strong>' criada com sucesso<br>";
                                } elseif (stripos($statement, 'INSERT INTO') !== false) {
                                    echo "✅ Dados iniciais inseridos com sucesso<br>";
                                } else {
                                    echo "✅ Comando executado com sucesso<br>";
                                }

                            } catch (PDOException $e) {
                                $erros++;

                                // Ignora erro se tabela já existe
                                if (strpos($e->getMessage(), 'already exists') !== false) {
                                    preg_match('/Table \'(\w+)\'/', $e->getMessage(), $matches);
                                    $tabela = $matches[1] ?? 'desconhecida';
                                    echo "⚠️ Tabela '<strong>$tabela</strong>' já existe (pulando)<br>";
                                } elseif (strpos($e->getMessage(), 'Duplicate') !== false) {
                                    echo "⚠️ Registro já existe (pulando)<br>";
                                } else {
                                    echo "❌ Erro: " . htmlspecialchars($e->getMessage()) . "<br>";
                                }
                            }
                        }

                        echo "<br><strong>📊 Resultado:</strong><br>";
                        echo "✅ Comandos executados com sucesso: <strong>$sucessos</strong><br>";
                        echo "⚠️ Avisos/Erros: <strong>$erros</strong><br>";

                        // Verifica se as tabelas foram criadas
                        echo "<br><strong>🔍 Verificando instalação:</strong><br>";

                        $tabelas = ['notificacoes', 'configuracoes_email', 'email_logs'];
                        $todasCriadas = true;

                        foreach ($tabelas as $tabela) {
                            $stmt = $pdo->query("SHOW TABLES LIKE '$tabela'");
                            if ($stmt->rowCount() > 0) {
                                echo "✅ Tabela '<strong>$tabela</strong>' instalada<br>";
                            } else {
                                echo "❌ Tabela '<strong>$tabela</strong>' NÃO foi criada<br>";
                                $todasCriadas = false;
                            }
                        }

                        echo '</div>';

                        if ($todasCriadas) {
                            ?>
                            <div class="success-icon">🎉</div>
                            <div class="alert alert-success">
                                <strong>✅ Instalação concluída com sucesso!</strong><br>
                                O sistema de notificações está pronto para uso.
                            </div>

                            <div class="steps">
                                <div class="step">
                                    <strong>Próximo passo 1:</strong> Configure o servidor SMTP em Configurações > E-mail
                                </div>
                                <div class="step">
                                    <strong>Próximo passo 2:</strong> Teste o envio de e-mail
                                </div>
                                <div class="step">
                                    <strong>Próximo passo 3:</strong> Ative o sistema de notificações
                                </div>
                                <div class="step">
                                    <strong>Próximo passo 4:</strong> Envie convites aos participantes!
                                </div>
                            </div>

                            <div style="text-align: center; margin-top: 30px;">
                                <a href="configuracoes/email.php" class="btn">
                                    ⚙️ Ir para Configurações de E-mail
                                </a>
                            </div>
                            <?php
                        } else {
                            ?>
                            <div class="alert alert-danger">
                                <strong>❌ Erro na instalação</strong><br>
                                Algumas tabelas não foram criadas. Verifique os logs acima.
                            </div>
                            <?php
                        }

                    } catch (Exception $e) {
                        echo '</div>';
                        ?>
                        <div class="alert alert-danger">
                            <strong>❌ Erro fatal na instalação:</strong><br>
                            <?php echo htmlspecialchars($e->getMessage()); ?>
                        </div>
                        <?php
                    }

                } else {
                    // FORMULÁRIO INICIAL
                    ?>
                    <div class="alert alert-info">
                        <strong>ℹ️ Sobre este instalador</strong><br>
                        Este script irá criar as seguintes tabelas no banco de dados:
                        <ul style="margin-top: 10px;">
                            <li><code>notificacoes</code> - Controle de notificações enviadas</li>
                            <li><code>configuracoes_email</code> - Configurações SMTP</li>
                            <li><code>email_logs</code> - Logs de envio de e-mails</li>
                        </ul>
                    </div>

                    <div class="steps">
                        <div class="step">
                            <strong>📋 Passo 1:</strong> Criar tabelas no banco de dados
                        </div>
                        <div class="step">
                            <strong>⚙️ Passo 2:</strong> Configurar servidor SMTP (Gmail, Office365, etc.)
                        </div>
                        <div class="step">
                            <strong>✉️ Passo 3:</strong> Testar envio de e-mails
                        </div>
                        <div class="step">
                            <strong>🚀 Passo 4:</strong> Enviar notificações aos participantes
                        </div>
                    </div>

                    <div style="text-align: center; margin-top: 30px;">
                        <form method="POST">
                            <button type="submit" class="btn">
                                🚀 Iniciar Instalação
                            </button>
                        </form>
                    </div>

                    <div class="alert alert-warning" style="margin-top: 30px;">
                        <strong>⚠️ Atenção:</strong> Este processo irá modificar o banco de dados.<br>
                        Execute apenas UMA VEZ.
                    </div>
                    <?php
                }
            }
            ?>
        </div>

        <div class="footer">
            <p>© <?php echo date('Y'); ?> SGC - Sistema de Gestão de Capacitações</p>
            <p>Sistema de Notificações v1.0</p>
        </div>
    </div>
</body>
</html>
