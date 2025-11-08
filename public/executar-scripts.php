<?php
/**
 * Página: Executar Scripts SQL
 * Permite executar scripts de migração via interface web
 */

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/classes/Auth.php';
require_once __DIR__ . '/../app/classes/Database.php';

Auth::requireLogin();

$resultado = '';
$erro = '';
$sucesso = '';

// Processar execução de script
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $db = Database::getInstance()->getConnection();

        switch ($_POST['action']) {
            case 'limpar':
                // Executar script de limpeza
                $sql = file_get_contents(__DIR__ . '/../database/migrations/008_limpar_e_recriar_estrutura.sql');

                // Remover comentários e dividir por statements
                // Primeiro, remover linhas de comentário
                $lines = explode("\n", $sql);
                $cleanedLines = array_filter($lines, function($line) {
                    $trimmed = trim($line);
                    return !empty($trimmed) && !preg_match('/^--/', $trimmed);
                });
                $cleanedSql = implode("\n", $cleanedLines);

                // Agora dividir por statements
                $statements = array_filter(
                    array_map('trim', explode(';', $cleanedSql)),
                    function($stmt) {
                        $trimmed = trim($stmt);
                        return !empty($trimmed) &&
                               !preg_match('/^SELECT/i', $trimmed);
                    }
                );

                foreach ($statements as $statement) {
                    if (!empty($statement)) {
                        $db->exec($statement);
                    }
                }

                $sucesso = 'Banco de dados limpo com sucesso! Todos os dados de avaliações foram removidos.';
                break;

            case 'popular':
                // Executar script de população
                $sql = file_get_contents(__DIR__ . '/../database/migrations/009_criar_dados_iniciais.sql');

                // Remover comentários e dividir por statements
                // Primeiro, remover linhas de comentário
                $lines = explode("\n", $sql);
                $cleanedLines = array_filter($lines, function($line) {
                    $trimmed = trim($line);
                    return !empty($trimmed) && !preg_match('/^--/', $trimmed);
                });
                $cleanedSql = implode("\n", $cleanedLines);

                // Agora dividir por statements
                $statements = array_filter(
                    array_map('trim', explode(';', $cleanedSql)),
                    function($stmt) {
                        $trimmed = trim($stmt);
                        return !empty($trimmed) &&
                               !preg_match('/^SELECT/i', $trimmed);
                    }
                );

                foreach ($statements as $statement) {
                    if (!empty($statement)) {
                        $db->exec($statement);
                    }
                }

                $sucesso = 'Dados iniciais criados com sucesso! Estrutura de módulos e perguntas populada.';
                break;

            case 'completo':
                // Executar ambos os scripts

                // 1. Limpar
                $sql = file_get_contents(__DIR__ . '/../database/migrations/008_limpar_e_recriar_estrutura.sql');

                // Remover comentários
                $lines = explode("\n", $sql);
                $cleanedLines = array_filter($lines, function($line) {
                    $trimmed = trim($line);
                    return !empty($trimmed) && !preg_match('/^--/', $trimmed);
                });
                $cleanedSql = implode("\n", $cleanedLines);

                // Dividir por statements
                $statements = array_filter(
                    array_map('trim', explode(';', $cleanedSql)),
                    function($stmt) {
                        $trimmed = trim($stmt);
                        return !empty($trimmed) &&
                               !preg_match('/^SELECT/i', $trimmed);
                    }
                );

                foreach ($statements as $statement) {
                    if (!empty($statement)) {
                        $db->exec($statement);
                    }
                }

                // 2. Popular
                $sql = file_get_contents(__DIR__ . '/../database/migrations/009_criar_dados_iniciais.sql');

                // Remover comentários
                $lines = explode("\n", $sql);
                $cleanedLines = array_filter($lines, function($line) {
                    $trimmed = trim($line);
                    return !empty($trimmed) && !preg_match('/^--/', $trimmed);
                });
                $cleanedSql = implode("\n", $cleanedLines);

                // Dividir por statements
                $statements = array_filter(
                    array_map('trim', explode(';', $cleanedSql)),
                    function($stmt) {
                        $trimmed = trim($stmt);
                        return !empty($trimmed) &&
                               !preg_match('/^SELECT/i', $trimmed);
                    }
                );

                foreach ($statements as $statement) {
                    if (!empty($statement)) {
                        $db->exec($statement);
                    }
                }

                $sucesso = 'Processo completo executado! Banco limpo e populado com dados iniciais.';
                break;
        }

    } catch (Exception $e) {
        $erro = 'Erro ao executar script: ' . $e->getMessage();
    }
}

$pageTitle = 'Executar Scripts SQL';
include APP_PATH . 'views/layouts/header.php';
?>

<style>
    .container {
        max-width: 1000px;
        margin: 0 auto;
        padding: 20px;
    }
    .header {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        padding: 30px;
        border-radius: 10px;
        margin-bottom: 30px;
    }
    .header h1 {
        margin: 0 0 10px 0;
        font-size: 28px;
    }
    .warning-box {
        background: #fff3cd;
        border: 2px solid #ffc107;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 30px;
    }
    .warning-box h3 {
        color: #856404;
        margin-top: 0;
    }
    .warning-box ul {
        color: #856404;
        margin-bottom: 0;
    }
    .card {
        background: white;
        border-radius: 10px;
        padding: 30px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .card h3 {
        margin-top: 0;
        color: #333;
    }
    .btn {
        padding: 12px 24px;
        border-radius: 5px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
        display: inline-block;
        font-size: 16px;
    }
    .btn-danger {
        background: #dc3545;
        color: white;
    }
    .btn-danger:hover {
        background: #c82333;
    }
    .btn-primary {
        background: #007bff;
        color: white;
    }
    .btn-primary:hover {
        background: #0056b3;
    }
    .btn-success {
        background: #28a745;
        color: white;
    }
    .btn-success:hover {
        background: #218838;
    }
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    .btn-secondary:hover {
        background: #5a6268;
    }
    .alert {
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .script-description {
        color: #666;
        margin: 10px 0 20px 0;
        line-height: 1.6;
    }
</style>

<div class="container">
    <div class="header">
        <h1>⚙️ Executar Scripts SQL</h1>
        <p style="margin: 0; opacity: 0.9;">Gerencie a estrutura do banco de dados via interface web</p>
    </div>

    <?php if ($erro): ?>
        <div class="alert alert-danger">
            <strong>❌ Erro:</strong> <?php echo htmlspecialchars($erro); ?>
        </div>
    <?php endif; ?>

    <?php if ($sucesso): ?>
        <div class="alert alert-success">
            <strong>✅ Sucesso:</strong> <?php echo htmlspecialchars($sucesso); ?>
            <br><br>
            <a href="debug.php" class="btn btn-secondary">🔍 Ver Diagnóstico Completo</a>
        </div>
    <?php endif; ?>

    <div class="warning-box">
        <h3>⚠️ ATENÇÃO - LEIA ANTES DE EXECUTAR</h3>
        <p><strong>Estes scripts modificam permanentemente o banco de dados!</strong></p>
        <ul>
            <li>O script de limpeza <strong>DELETA</strong> todos os dados de avaliações</li>
            <li>Isso inclui: respostas, checklists, perguntas e módulos</li>
            <li>Esta ação <strong>NÃO PODE SER DESFEITA</strong></li>
            <li>Recomenda-se fazer backup antes de executar</li>
        </ul>
    </div>

    <div class="card">
        <h3>🗑️ Opção 1: Limpar Banco de Dados</h3>
        <p class="script-description">
            Remove todos os dados de avaliações existentes:<br>
            • Deleta todas as respostas de checklist<br>
            • Deleta todos os checklists<br>
            • Deleta todas as perguntas<br>
            • Deleta todos os módulos de avaliação<br>
            • Reseta os contadores (AUTO_INCREMENT)
        </p>
        <form method="POST" onsubmit="return confirm('TEM CERTEZA que deseja DELETAR TODOS OS DADOS?\n\nEsta ação não pode ser desfeita!');">
            <input type="hidden" name="action" value="limpar">
            <button type="submit" class="btn btn-danger">🗑️ Limpar Banco de Dados</button>
        </form>
    </div>

    <div class="card">
        <h3>📊 Opção 2: Popular com Dados Iniciais</h3>
        <p class="script-description">
            Cria estrutura inicial de módulos e perguntas:<br>
            <strong>Formulários Diários:</strong><br>
            • 2 módulos: "Limpeza e Organização" e "Atendimento"<br>
            • 10 perguntas no total (5 por módulo)<br>
            <br>
            <strong>Formulários Quinzenais/Mensais:</strong><br>
            • 3 módulos: "Infraestrutura", "Gestão de Pessoas" e "Gestão Comercial"<br>
            • 25 perguntas no total (8 + 7 + 10)
        </p>
        <form method="POST" onsubmit="return confirm('Deseja criar os dados iniciais de módulos e perguntas?');">
            <input type="hidden" name="action" value="popular">
            <button type="submit" class="btn btn-primary">📊 Criar Dados Iniciais</button>
        </form>
    </div>

    <div class="card">
        <h3>🔄 Opção 3: Processo Completo (Limpar + Popular)</h3>
        <p class="script-description">
            Executa ambos os scripts em sequência:<br>
            1. Limpa todos os dados existentes<br>
            2. Cria estrutura nova com dados iniciais<br>
            <br>
            <strong>Use esta opção para começar do zero com uma estrutura limpa.</strong>
        </p>
        <form method="POST" onsubmit="return confirm('ATENÇÃO!\n\nEsta ação irá:\n1. DELETAR todos os dados atuais\n2. Criar nova estrutura com dados iniciais\n\nTEM CERTEZA?');">
            <input type="hidden" name="action" value="completo">
            <button type="submit" class="btn btn-success">🔄 Executar Processo Completo</button>
        </form>
    </div>

    <div style="text-align: center; margin-top: 30px;">
        <a href="debug.php" class="btn btn-secondary">🔍 Ver Diagnóstico do Sistema</a>
        <a href="gestao/index.php" class="btn btn-secondary">⬅️ Voltar para Gestão</a>
    </div>
</div>

<?php include APP_PATH . 'views/layouts/footer.php'; ?>
