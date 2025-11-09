<?php
/**
 * Instalador Web - Formulários Dinâmicos
 * Este script cria as tabelas do módulo de Formulários Dinâmicos
 */

// Verificar se está sendo executado via web
if (php_sapi_name() === 'cli') {
    die("Este script deve ser executado via navegador.\n");
}

session_start();

// Configuração
define('SGC_SYSTEM', true);
$APP_PATH = __DIR__ . '/../../app/';
require_once $APP_PATH . 'config/config.php';
require_once $APP_PATH . 'classes/Database.php';
require_once $APP_PATH . 'classes/Auth.php';

// Verificar autenticação e permissão de admin
$auth = new Auth();
if (!$auth->verificarAutenticacao()) {
    header('Location: /public/index.php?erro=acesso_negado');
    exit;
}

$usuarioLogado = $auth->getUsuarioLogado();
if ($usuarioLogado['nivel_acesso'] !== 'admin') {
    die('<h1>Acesso Negado</h1><p>Apenas administradores podem executar o instalador.</p><a href="/public/dashboard.php">Voltar</a>');
}

// Processar instalação
$mensagens = [];
$erros = [];
$instalado = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_instalacao'])) {
    try {
        // Conectar ao banco de dados
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );

        // Ler arquivo SQL
        $sqlFile = __DIR__ . '/../../database/migrations/020_criar_formularios_dinamicos.sql';
        if (!file_exists($sqlFile)) {
            throw new Exception("Arquivo SQL não encontrado: $sqlFile");
        }

        $sql = file_get_contents($sqlFile);

        // Executar SQL em partes (separar por ponto-e-vírgula)
        $pdo->beginTransaction();

        // Remover comentários SQL
        $sql = preg_replace('/--.*$/m', '', $sql);

        // Executar comandos
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($stmt) {
                return !empty($stmt) &&
                       !preg_match('/^(SELECT|SHOW)/i', $stmt);
            }
        );

        $comandosExecutados = 0;
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                try {
                    $pdo->exec($statement);
                    $comandosExecutados++;
                } catch (PDOException $e) {
                    // Ignorar erros de tabela já existente
                    if (strpos($e->getMessage(), 'already exists') === false) {
                        throw $e;
                    }
                }
            }
        }

        $pdo->commit();

        // Verificar tabelas criadas
        $stmt = $pdo->query("SHOW TABLES LIKE 'form%'");
        $tabelasForm = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $stmt = $pdo->query("SHOW TABLES LIKE 'formularios_dinamicos'");
        $tabelaPrincipal = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $totalTabelas = count($tabelasForm) + count($tabelaPrincipal);

        $mensagens[] = "✅ Instalação concluída com sucesso!";
        $mensagens[] = "📊 Total de comandos SQL executados: $comandosExecutados";
        $mensagens[] = "🗄️ Total de tabelas criadas/verificadas: $totalTabelas";
        $mensagens[] = "✅ Tabelas: " . implode(', ', array_merge($tabelaPrincipal, $tabelasForm));

        // Verificar formulário de exemplo
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM formularios_dinamicos");
        $totalFormularios = $stmt->fetch()['total'];
        $mensagens[] = "📋 Formulários de exemplo: $totalFormularios";

        $instalado = true;

    } catch (PDOException $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $erros[] = "❌ Erro no banco de dados: " . $e->getMessage();
    } catch (Exception $e) {
        $erros[] = "❌ Erro: " . $e->getMessage();
    }
}

// Verificar se já está instalado
$jaInstalado = false;
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $stmt = $pdo->query("SHOW TABLES LIKE 'formularios_dinamicos'");
    $jaInstalado = $stmt->rowCount() > 0;

    if ($jaInstalado) {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM formularios_dinamicos");
        $totalFormulariosExistentes = $stmt->fetch()['total'];
    }
} catch (Exception $e) {
    // Ignorar erro
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador - Formulários Dinâmicos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .installer-card {
            max-width: 800px;
            width: 100%;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .installer-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .installer-body {
            padding: 40px 30px;
        }
        .step {
            display: flex;
            align-items: start;
            margin-bottom: 25px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }
        .step-number {
            background: #667eea;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 20px;
            flex-shrink: 0;
        }
        .step-content h5 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .step-content p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .success-box {
            background: #d1ecf1;
            border: 1px solid #17a2b8;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .error-box {
            background: #f8d7da;
            border: 1px solid #dc3545;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .btn-install {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 15px 40px;
            font-size: 18px;
            font-weight: bold;
            border-radius: 50px;
            transition: transform 0.2s;
        }
        .btn-install:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .code-box {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            overflow-x: auto;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="installer-card">
            <div class="installer-header">
                <h1><i class="fas fa-rocket"></i> Instalador de Formulários Dinâmicos</h1>
                <p class="mb-0">Sistema Avançado de Criação de Formulários com Pontuação e Analytics</p>
            </div>

            <div class="installer-body">
                <?php if (!empty($mensagens)): ?>
                    <div class="success-box">
                        <h4><i class="fas fa-check-circle text-success"></i> Instalação Concluída!</h4>
                        <?php foreach ($mensagens as $msg): ?>
                            <p class="mb-1"><?= htmlspecialchars($msg) ?></p>
                        <?php endforeach; ?>
                        <hr>
                        <div class="d-grid gap-2 mt-3">
                            <a href="<?= BASE_URL ?>formularios-dinamicos/" class="btn btn-success btn-lg">
                                <i class="fas fa-list"></i> Ver Meus Formulários
                            </a>
                            <a href="<?= BASE_URL ?>formularios-dinamicos/criar.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Criar Primeiro Formulário
                            </a>
                            <a href="<?= BASE_URL ?>dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-home"></i> Voltar ao Dashboard
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($erros)): ?>
                    <div class="error-box">
                        <h4><i class="fas fa-exclamation-triangle text-danger"></i> Erros Durante a Instalação</h4>
                        <?php foreach ($erros as $erro): ?>
                            <p class="mb-1"><?= htmlspecialchars($erro) ?></p>
                        <?php endforeach; ?>
                        <hr>
                        <p class="mb-0">
                            <strong>Soluções possíveis:</strong><br>
                            1. Verifique as credenciais do banco de dados em <code>app/config/database.php</code><br>
                            2. Certifique-se que o usuário MySQL tem permissões para CREATE TABLE<br>
                            3. Verifique se o arquivo SQL existe em <code>database/migrations/020_criar_formularios_dinamicos.sql</code>
                        </p>
                    </div>
                <?php endif; ?>

                <?php if ($jaInstalado && !$instalado): ?>
                    <div class="warning-box">
                        <h4><i class="fas fa-info-circle text-warning"></i> Módulo Já Instalado</h4>
                        <p>O módulo de Formulários Dinâmicos já está instalado no sistema.</p>
                        <p class="mb-0">Total de formulários criados: <strong><?= $totalFormulariosExistentes ?? 0 ?></strong></p>
                        <hr>
                        <div class="d-grid gap-2 mt-3">
                            <a href="<?= BASE_URL ?>formularios-dinamicos/" class="btn btn-primary btn-lg">
                                <i class="fas fa-list"></i> Acessar Formulários
                            </a>
                            <a href="<?= BASE_URL ?>dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-home"></i> Voltar ao Dashboard
                            </a>
                        </div>
                        <hr>
                        <p class="text-muted mb-0">
                            <small>Se deseja reinstalar, você pode executar novamente o instalador abaixo. As tabelas existentes não serão afetadas.</small>
                        </p>
                    </div>
                <?php endif; ?>

                <?php if (!$instalado): ?>
                    <h3 class="mb-4">O que será instalado?</h3>

                    <div class="step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h5>8 Tabelas no Banco de Dados</h5>
                            <p>
                                Serão criadas as seguintes tabelas:<br>
                                <code>formularios_dinamicos</code>, <code>form_secoes</code>,
                                <code>form_perguntas</code>, <code>form_opcoes_resposta</code>,
                                <code>form_respostas</code>, <code>form_respostas_detalhes</code>,
                                <code>form_faixas_pontuacao</code>, <code>form_compartilhamentos</code>
                            </p>
                        </div>
                    </div>

                    <div class="step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h5>Formulário de Exemplo</h5>
                            <p>Um formulário de demonstração será criado para você testar o sistema.</p>
                        </div>
                    </div>

                    <div class="step">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h5>Sistema de Checklists Preservado</h5>
                            <p>O sistema atual de checklists NÃO será afetado. Este é um módulo completamente separado.</p>
                        </div>
                    </div>

                    <div class="warning-box">
                        <h5><i class="fas fa-exclamation-triangle"></i> Importante!</h5>
                        <ul class="mb-0">
                            <li>É recomendado fazer backup do banco de dados antes de prosseguir</li>
                            <li>O processo levará alguns segundos</li>
                            <li>Não feche esta janela durante a instalação</li>
                            <li>Apenas administradores podem executar este instalador</li>
                        </ul>
                    </div>

                    <div class="alert alert-info">
                        <h5><i class="fas fa-database"></i> Configuração do Banco</h5>
                        <div class="code-box">
Host: <?= DB_HOST ?><br>
Database: <?= DB_NAME ?><br>
User: <?= DB_USER ?><br>
Status: <span class="text-success">✓ Conectado</span>
                        </div>
                    </div>

                    <form method="POST" onsubmit="return confirm('Deseja realmente instalar o módulo de Formulários Dinâmicos?\n\nCertifique-se de ter feito backup do banco de dados.');">
                        <div class="d-grid gap-2">
                            <button type="submit" name="confirmar_instalacao" class="btn btn-primary btn-install">
                                <i class="fas fa-download"></i> Instalar Agora
                            </button>
                            <a href="<?= BASE_URL ?>dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                        </div>
                    </form>
                <?php endif; ?>

                <hr class="my-4">

                <div class="text-center text-muted">
                    <small>
                        <i class="fas fa-info-circle"></i>
                        Após a instalação, você terá acesso ao módulo via menu lateral:
                        <strong>Formulários Dinâmicos</strong>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
