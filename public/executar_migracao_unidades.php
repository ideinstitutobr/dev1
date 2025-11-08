<?php
/**
 * Script de Migração: Lojas para Unidades
 *
 * Este script executa a migração que altera o sistema de avaliações
 * para usar Unidades ao invés de Lojas
 *
 * ATENÇÃO: Execute este script apenas uma vez!
 */

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/classes/Database.php';
require_once __DIR__ . '/../app/classes/Auth.php';

// Verificar autenticação (apenas admin pode executar)
Auth::requireLogin();
if (!Auth::isAdmin()) {
    die('❌ Acesso negado! Apenas administradores podem executar migrações.');
}

$db = Database::getInstance();
$pdo = $db->getConnection();

// Caminho do arquivo de migração
$migrationFile = __DIR__ . '/../database/migrations/006_migrar_lojas_para_unidades.sql';

// Verificar se o arquivo existe
if (!file_exists($migrationFile)) {
    die('❌ Erro: Arquivo de migração não encontrado em: ' . $migrationFile);
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migração: Lojas para Unidades</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 800px;
            width: 100%;
            padding: 40px;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .success-box {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .error-box {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .status {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
        .status-item {
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .status-item:last-child {
            border-bottom: none;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            margin: 10px 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        li {
            margin: 5px 0;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Migração: Lojas → Unidades</h1>
        <p class="subtitle">Sistema de Avaliações - Atualização de Estrutura</p>

        <?php
        // Verificar se já foi executado
        $verificarColuna = "SHOW COLUMNS FROM checklists LIKE 'unidade_id'";
        try {
            $stmt = $pdo->query($verificarColuna);
            $colunaExiste = $stmt->fetch(PDO::FETCH_ASSOC);
            $jaMigrado = !empty($colunaExiste);
        } catch (Exception $e) {
            $jaMigrado = false;
        }

        if ($jaMigrado) {
            ?>
            <div class="warning-box">
                <strong>⚠️ Aviso:</strong> A migração parece já ter sido executada!
                <br>A coluna <code>unidade_id</code> já existe na tabela <code>checklists</code>.
            </div>

            <div class="info-box">
                <strong>ℹ️ Informação:</strong>
                <ul>
                    <li>Se você deseja executar novamente, primeiro reverta as alterações no banco de dados</li>
                    <li>Verifique se o sistema está funcionando corretamente</li>
                    <li>Acesse: <a href="<?php echo BASE_URL; ?>checklist/novo.php">Nova Avaliação</a></li>
                </ul>
            </div>

            <a href="<?php echo BASE_URL; ?>checklist/" class="btn btn-primary">
                📋 Ir para Checklists
            </a>
            <a href="<?php echo BASE_URL; ?>dashboard.php" class="btn btn-secondary">
                🏠 Dashboard
            </a>
            <?php
            exit;
        }

        // Processar migração
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['executar_migracao'])) {
            echo '<div class="status">';
            echo '<strong>📊 Executando migração...</strong><br><br>';

            try {
                // Ler o arquivo SQL
                $sql = file_get_contents($migrationFile);

                // Dividir em comandos individuais
                $comandos = array_filter(
                    array_map('trim', explode(';', $sql)),
                    function($cmd) {
                        return !empty($cmd) && !preg_match('/^--/', $cmd);
                    }
                );

                $pdo->beginTransaction();

                $sucesso = 0;
                $erros = 0;

                foreach ($comandos as $index => $comando) {
                    if (trim($comando) === '') continue;

                    echo "<div class='status-item'>";
                    echo "Comando " . ($index + 1) . ": ";

                    try {
                        $pdo->exec($comando);
                        echo "✅ <span style='color: #28a745;'>Sucesso</span>";
                        $sucesso++;
                    } catch (PDOException $e) {
                        // Ignorar erros de "constraint já existe" ou "coluna já existe"
                        if (strpos($e->getMessage(), 'Duplicate') !== false ||
                            strpos($e->getMessage(), 'already exists') !== false) {
                            echo "⚠️ <span style='color: #ffc107;'>Já existe (ignorado)</span>";
                        } else {
                            echo "❌ <span style='color: #dc3545;'>Erro: " . htmlspecialchars($e->getMessage()) . "</span>";
                            $erros++;
                        }
                    }
                    echo "</div>";
                }

                echo '</div>';

                if ($erros === 0) {
                    $pdo->commit();
                    ?>
                    <div class="success-box">
                        <strong>✅ Migração concluída com sucesso!</strong>
                        <ul>
                            <li>Total de comandos executados: <?php echo $sucesso; ?></li>
                            <li>Coluna <code>loja_id</code> foi removida</li>
                            <li>Coluna <code>unidade_id</code> foi criada</li>
                            <li>Foreign key configurada para a tabela <code>unidades</code></li>
                        </ul>
                    </div>

                    <div class="info-box">
                        <strong>📝 Próximos passos:</strong>
                        <ol>
                            <li>Teste criar uma nova avaliação</li>
                            <li>Verifique se as unidades aparecem no formulário</li>
                            <li>Confira se avaliações antigas (se houver) foram migradas corretamente</li>
                            <li><strong>Delete este arquivo após confirmar que tudo está funcionando!</strong></li>
                        </ol>
                    </div>

                    <a href="<?php echo BASE_URL; ?>checklist/novo.php" class="btn btn-success">
                        ➕ Criar Nova Avaliação
                    </a>
                    <a href="<?php echo BASE_URL; ?>checklist/" class="btn btn-primary">
                        📋 Ver Checklists
                    </a>
                    <?php
                } else {
                    $pdo->rollBack();
                    ?>
                    <div class="error-box">
                        <strong>❌ Migração falhou!</strong>
                        <p>Foram encontrados <?php echo $erros; ?> erro(s) durante a execução.</p>
                        <p>Nenhuma alteração foi aplicada ao banco de dados (rollback).</p>
                        <p>Por favor, revise os erros acima e tente novamente.</p>
                    </div>

                    <button onclick="location.reload()" class="btn btn-primary">
                        🔄 Tentar Novamente
                    </button>
                    <?php
                }

            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                ?>
                <div class="error-box">
                    <strong>❌ Erro crítico!</strong>
                    <p><?php echo htmlspecialchars($e->getMessage()); ?></p>
                </div>
                <?php
            }

        } else {
            // Mostrar informações antes de executar
            ?>
            <div class="info-box">
                <strong>ℹ️ Sobre esta migração:</strong>
                <p>Este script irá modificar a estrutura do banco de dados para integrar o sistema de avaliações com as Unidades.</p>
            </div>

            <div class="warning-box">
                <strong>⚠️ Atenção - Leia antes de executar:</strong>
                <ul>
                    <li><strong>Backup:</strong> É altamente recomendado fazer backup do banco de dados antes de executar</li>
                    <li><strong>Migração de dados:</strong> Se você tem checklists com <code>loja_id</code>, eles precisarão ser migrados manualmente para <code>unidade_id</code></li>
                    <li><strong>Irreversível:</strong> Esta operação remove a coluna <code>loja_id</code> e a tabela <code>lojas</code></li>
                    <li><strong>Apenas uma vez:</strong> Execute este script apenas uma vez</li>
                </ul>
            </div>

            <div class="status">
                <strong>📋 O que será executado:</strong>
                <div class="status-item">1. Adicionar coluna <code>unidade_id</code> na tabela <code>checklists</code></div>
                <div class="status-item">2. Remover foreign key antiga de <code>loja_id</code></div>
                <div class="status-item">3. Remover coluna <code>loja_id</code></div>
                <div class="status-item">4. Tornar <code>unidade_id</code> obrigatório (NOT NULL)</div>
                <div class="status-item">5. Criar foreign key para <code>unidades(id)</code></div>
                <div class="status-item">6. Criar índice em <code>unidade_id</code></div>
            </div>

            <form method="POST" onsubmit="return confirm('⚠️ Tem certeza que deseja executar a migração?\n\nCertifique-se de ter feito backup do banco de dados!');">
                <button type="submit" name="executar_migracao" value="1" class="btn btn-primary">
                    🚀 Executar Migração
                </button>
                <a href="<?php echo BASE_URL; ?>dashboard.php" class="btn btn-secondary">
                    ❌ Cancelar
                </a>
            </form>
            <?php
        }
        ?>
    </div>
</body>
</html>
