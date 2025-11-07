<?php
/**
 * INSTALADOR DO SISTEMA DE CHECKLIST DE LOJAS
 *
 * Este script instala automaticamente as tabelas e dados iniciais
 * do sistema de checklist de lojas.
 *
 * INSTRUÇÕES:
 * 1. Acesse este arquivo pelo navegador: http://seudominio.com/instalar_checklist.php
 * 2. Clique em "Instalar Banco de Dados"
 * 3. Aguarde a conclusão
 * 4. Por segurança, DELETE este arquivo após a instalação
 *
 * ATENÇÃO: Este script irá criar as tabelas. Se elas já existirem, pode dar erro.
 */

// Configurações
define('INSTALADOR_ATIVO', true);

// Incluir configuração do banco de dados
require_once __DIR__ . '/../app/config/database.php';

// Verificar se já foi instalado (opcional - pode comentar esta linha para reinstalar)
// if (file_exists(__DIR__ . '/uploads/.checklist_instalado')) {
//     die('Sistema de checklist já foi instalado! Delete o arquivo uploads/.checklist_instalado para reinstalar.');
// }

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador - Sistema de Checklist de Lojas</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 800px;
            width: 100%;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .header p {
            opacity: 0.9;
            font-size: 14px;
        }
        .content {
            padding: 30px;
        }
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .info-box h3 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 18px;
        }
        .info-box ul {
            margin-left: 20px;
            line-height: 1.8;
        }
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .warning-box h3 {
            color: #856404;
            margin-bottom: 10px;
        }
        .success-box {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .success-box h3 {
            color: #155724;
            margin-bottom: 10px;
        }
        .error-box {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .error-box h3 {
            color: #721c24;
            margin-bottom: 10px;
        }
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-block;
            text-decoration: none;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }
        .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        }
        .step {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
        }
        .step:last-child {
            border-bottom: none;
        }
        .step-number {
            background: #667eea;
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
            flex-shrink: 0;
        }
        .step-text {
            flex: 1;
        }
        .log-box {
            background: #1e1e1e;
            color: #00ff00;
            padding: 20px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            max-height: 400px;
            overflow-y: auto;
            margin: 20px 0;
        }
        .log-box .error {
            color: #ff5555;
        }
        .log-box .success {
            color: #50fa7b;
        }
        .log-box .info {
            color: #8be9fd;
        }
        .button-group {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Instalador do Sistema de Checklist de Lojas</h1>
            <p>Versão 1.0 - Instalação Automática</p>
        </div>

        <div class="content">
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['instalar'])) {
                // PROCESSAR INSTALAÇÃO
                echo '<div class="log-box" id="log">';

                try {
                    // Conectar ao banco
                    $db = Database::getInstance();
                    $pdo = $db->getConnection();

                    echo '<span class="info">[INFO] Conectado ao banco de dados com sucesso!</span><br>';
                    echo '<span class="info">[INFO] Iniciando instalação...</span><br><br>';

                    // Ler arquivo de schema
                    $schemaFile = __DIR__ . '/../database/migrations/checklist_lojas_schema.sql';
                    if (!file_exists($schemaFile)) {
                        throw new Exception('Arquivo de schema não encontrado: ' . $schemaFile);
                    }

                    $schema = file_get_contents($schemaFile);
                    echo '<span class="success">[OK] Arquivo de schema carregado</span><br>';

                    // Executar schema (criar tabelas)
                    $statements = array_filter(array_map('trim', explode(';', $schema)));
                    $totalStatements = count($statements);
                    $executados = 0;

                    foreach ($statements as $statement) {
                        if (empty($statement) || strpos($statement, '--') === 0) {
                            continue;
                        }

                        try {
                            $pdo->exec($statement);
                            $executados++;
                        } catch (PDOException $e) {
                            // Ignora erros de tabela já existente
                            if (strpos($e->getMessage(), 'already exists') !== false) {
                                echo '<span class="info">[INFO] Tabela já existe, pulando...</span><br>';
                            } else {
                                throw $e;
                            }
                        }
                    }

                    echo '<span class="success">[OK] Tabelas criadas: ' . $executados . ' de ' . $totalStatements . ' comandos executados</span><br><br>';

                    // Ler arquivo de seed
                    $seedFile = __DIR__ . '/../database/migrations/checklist_lojas_seed.sql';
                    if (!file_exists($seedFile)) {
                        throw new Exception('Arquivo de seed não encontrado: ' . $seedFile);
                    }

                    $seed = file_get_contents($seedFile);
                    echo '<span class="success">[OK] Arquivo de seed carregado</span><br>';

                    // Executar seed (dados iniciais)
                    $statements = array_filter(array_map('trim', explode(';', $seed)));
                    $totalStatements = count($statements);
                    $executados = 0;

                    foreach ($statements as $statement) {
                        if (empty($statement) || strpos($statement, '--') === 0) {
                            continue;
                        }

                        try {
                            $pdo->exec($statement);
                            $executados++;
                        } catch (PDOException $e) {
                            // Ignora erros de duplicata
                            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                                echo '<span class="info">[INFO] Registro já existe, pulando...</span><br>';
                            } else {
                                throw $e;
                            }
                        }
                    }

                    echo '<span class="success">[OK] Dados iniciais inseridos: ' . $executados . ' de ' . $totalStatements . ' comandos executados</span><br><br>';

                    // Criar diretório de uploads
                    $uploadDir = __DIR__ . '/uploads/fotos_checklist';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                        echo '<span class="success">[OK] Diretório de uploads criado</span><br>';
                    } else {
                        echo '<span class="info">[INFO] Diretório de uploads já existe</span><br>';
                    }

                    // Marcar como instalado
                    file_put_contents(__DIR__ . '/uploads/.checklist_instalado', date('Y-m-d H:i:s'));

                    echo '<br><span class="success">[SUCESSO] ✅ Instalação concluída com sucesso!</span><br>';
                    echo '<span class="info">[INFO] Sistema pronto para uso!</span><br>';

                    echo '</div>';

                    echo '<div class="success-box">';
                    echo '<h3>✅ Instalação Concluída!</h3>';
                    echo '<p><strong>O que foi instalado:</strong></p>';
                    echo '<ul>';
                    echo '<li>✅ 8 tabelas do banco de dados</li>';
                    echo '<li>✅ 8 módulos de avaliação</li>';
                    echo '<li>✅ 58 perguntas pré-cadastradas</li>';
                    echo '<li>✅ 4 lojas de exemplo</li>';
                    echo '<li>✅ 4 cargos padrão</li>';
                    echo '<li>✅ Configurações de pesos de pontuação</li>';
                    echo '<li>✅ Diretório de uploads criado</li>';
                    echo '</ul>';
                    echo '<p style="margin-top: 15px;"><strong>⚠️ IMPORTANTE:</strong> Por questões de segurança, DELETE este arquivo (instalar_checklist.php) após a instalação!</p>';
                    echo '</div>';

                    echo '<div class="button-group">';
                    echo '<a href="' . BASE_URL . 'checklist/" class="btn btn-success">🚀 Acessar Sistema de Checklist</a>';
                    echo '<a href="' . BASE_URL . 'dashboard.php" class="btn">📊 Ir para Dashboard</a>';
                    echo '</div>';

                } catch (Exception $e) {
                    echo '<span class="error">[ERRO] ' . $e->getMessage() . '</span><br>';
                    echo '</div>';

                    echo '<div class="error-box">';
                    echo '<h3>❌ Erro na Instalação</h3>';
                    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
                    echo '<p style="margin-top: 10px;">Verifique as configurações do banco de dados e tente novamente.</p>';
                    echo '</div>';
                }

            } else {
                // EXIBIR FORMULÁRIO DE INSTALAÇÃO
            ?>

            <div class="info-box">
                <h3>📦 O que será instalado?</h3>
                <ul>
                    <li><strong>8 Tabelas:</strong> lojas, cargos, módulos, perguntas, checklists, respostas, fotos, configurações</li>
                    <li><strong>8 Módulos de Avaliação:</strong> Organização, Caixas, Ovos, Gôndolas, Frios, Câmara Fria, Estoque, Áreas Comuns</li>
                    <li><strong>58 Perguntas:</strong> Distribuídas entre os 8 módulos</li>
                    <li><strong>4 Lojas de Exemplo:</strong> Para começar a usar imediatamente</li>
                    <li><strong>Sistema de Pontuação:</strong> Pesos configurados para 6 e 8 perguntas por módulo</li>
                </ul>
            </div>

            <div class="warning-box">
                <h3>⚠️ Atenção</h3>
                <ul>
                    <li>Certifique-se de que as configurações do banco de dados estão corretas</li>
                    <li>O banco de dados será modificado (novas tabelas serão criadas)</li>
                    <li>Se as tabelas já existirem, alguns erros podem aparecer (é normal)</li>
                    <li>Após a instalação, DELETE este arquivo por segurança</li>
                </ul>
            </div>

            <div class="info-box">
                <h3>📋 Passos da Instalação</h3>
                <div class="step">
                    <div class="step-number">1</div>
                    <div class="step-text">Conectar ao banco de dados</div>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-text">Criar 8 tabelas do sistema</div>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-text">Inserir dados iniciais (módulos, perguntas, lojas)</div>
                </div>
                <div class="step">
                    <div class="step-number">4</div>
                    <div class="step-text">Criar diretório de uploads</div>
                </div>
                <div class="step">
                    <div class="step-number">5</div>
                    <div class="step-text">Finalizar instalação</div>
                </div>
            </div>

            <form method="POST" style="text-align: center;">
                <button type="submit" name="instalar" class="btn" onclick="return confirm('Deseja iniciar a instalação do Sistema de Checklist de Lojas?');">
                    🚀 Instalar Banco de Dados
                </button>
            </form>

            <?php } ?>
        </div>
    </div>
</body>
</html>
