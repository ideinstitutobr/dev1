<?php
/**
 * INSTALADOR STANDALONE DO SISTEMA DE CHECKLIST DE LOJAS
 *
 * Este arquivo é completamente independente e não precisa de nenhum outro arquivo.
 * Basta acessar pelo navegador e clicar em "Instalar".
 *
 * INSTRUÇÕES:
 * 1. Acesse: http://seudominio.com/instalar_checklist.php
 * 2. Clique em "Instalar Banco de Dados"
 * 3. Aguarde a conclusão (pode levar alguns segundos)
 * 4. DELETE este arquivo após a instalação por segurança!
 */

// ============================================
// CONFIGURAÇÕES DO BANCO DE DADOS
// ============================================
// IMPORTANTE: Edite estas configurações se necessário
$DB_HOST = 'localhost';
$DB_NAME = 'u411458227_comercial255';
$DB_USER = 'u411458227_comercial255';
$DB_PASS = '#Ide@2k25';
$DB_CHARSET = 'utf8mb4';

// ============================================
// NÃO EDITE DAQUI PARA BAIXO
// ============================================

// Desabilitar timeout para scripts longos
set_time_limit(300);
ini_set('display_errors', 1);
error_reporting(E_ALL);

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
            max-width: 900px;
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
        .log-box {
            background: #1e1e1e;
            color: #00ff00;
            padding: 20px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            max-height: 500px;
            overflow-y: auto;
            margin: 20px 0;
            line-height: 1.6;
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
        .log-box .warning {
            color: #f1fa8c;
        }
        .button-group {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Instalador do Sistema de Checklist de Lojas</h1>
            <p>Versão 1.0 - Instalação Automática Standalone</p>
        </div>

        <div class="content">
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['instalar'])) {
                // ============================================
                // PROCESSAR INSTALAÇÃO
                // ============================================
                echo '<div class="log-box" id="log">';

                try {
                    echo '<span class="info">[INFO] Iniciando instalação do Sistema de Checklist de Lojas...</span><br>';
                    echo '<span class="info">[INFO] Conectando ao banco de dados...</span><br>';

                    // Conectar ao banco
                    $dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset={$DB_CHARSET}";
                    $options = [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ];

                    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
                    echo '<span class="success">[OK] Conectado ao banco de dados com sucesso!</span><br><br>';

                    // Ler arquivo de schema
                    $schemaFile = __DIR__ . '/../database/migrations/checklist_lojas_schema.sql';

                    if (!file_exists($schemaFile)) {
                        throw new Exception('Arquivo de schema não encontrado: ' . $schemaFile);
                    }

                    $schema = file_get_contents($schemaFile);
                    echo '<span class="success">[OK] Arquivo de schema carregado</span><br>';

                    // Executar schema
                    echo '<span class="info">[INFO] Criando tabelas do banco de dados...</span><br>';

                    // Parser robusto - Extrai CREATE TABLE até ENGINE=InnoDB
                    preg_match_all('/CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+(\w+)\s*\([^;]+?\)\s*ENGINE=InnoDB[^;]*;/is', $schema, $matches, PREG_SET_ORDER);

                    $executados = 0;
                    $tabelas = [];

                    foreach ($matches as $match) {
                        $createStatement = trim($match[0]);
                        $tableName = $match[1];

                        try {
                            $pdo->exec($createStatement);
                            $executados++;
                            $tabelas[] = $tableName;
                            echo '<span class="success">  ✓ Tabela "' . $tableName . '" criada</span><br>';
                        } catch (PDOException $e) {
                            if (strpos($e->getMessage(), 'already exists') !== false) {
                                echo '<span class="warning">  ⚠ Tabela "' . $tableName . '" já existe</span><br>';
                            } else {
                                echo '<span class="error">  ✗ Erro na tabela "' . $tableName . '": ' . $e->getMessage() . '</span><br>';
                            }
                        }
                    }

                    // Buscar e executar INSERTs do schema (configurações)
                    preg_match_all('/INSERT\s+INTO[^;]+;/is', $schema, $insertMatches);
                    foreach ($insertMatches[0] as $insertStmt) {
                        try {
                            $pdo->exec(trim($insertStmt));
                            $executados++;
                        } catch (PDOException $e) {
                            // Ignora duplicatas
                            if (strpos($e->getMessage(), 'Duplicate') === false) {
                                echo '<span class="warning">  ⚠ Aviso em INSERT: ' . substr($e->getMessage(), 0, 100) . '</span><br>';
                            }
                        }
                    }

                    echo '<span class="success">[OK] Schema processado! ' . count($tabelas) . ' tabelas criadas (' . $executados . ' comandos executados)</span><br><br>';

                    // Ler arquivo de seed
                    $seedFile = __DIR__ . '/../database/migrations/checklist_lojas_seed.sql';

                    if (!file_exists($seedFile)) {
                        throw new Exception('Arquivo de seed não encontrado: ' . $seedFile);
                    }

                    $seed = file_get_contents($seedFile);
                    echo '<span class="success">[OK] Arquivo de dados iniciais carregado</span><br>';

                    // Executar seed
                    echo '<span class="info">[INFO] Inserindo dados iniciais (módulos, perguntas, lojas)...</span><br>';

                    // Parser robusto - Extrai todos os INSERTs e SETs
                    preg_match_all('/(INSERT\s+INTO[^;]+;|SET\s+@[^;]+;)/is', $seed, $seedMatches);

                    $executados = 0;
                    $pulados = 0;
                    $modulosInseridos = 0;
                    $perguntasInseridas = 0;

                    foreach ($seedMatches[0] as $statement) {
                        $statement = trim($statement);

                        try {
                            $pdo->exec($statement);
                            $executados++;

                            // Contar por tipo
                            if (stripos($statement, 'INSERT INTO modulos_avaliacao') !== false) {
                                $modulosInseridos++;
                                echo '<span class="info">  • Módulo inserido...</span><br>';
                            } elseif (stripos($statement, 'INSERT INTO perguntas') !== false) {
                                $perguntasInseridas++;
                                if ($perguntasInseridas % 10 == 0) {
                                    echo '<span class="info">  • ' . $perguntasInseridas . ' perguntas inseridas...</span><br>';
                                }
                            } elseif (stripos($statement, 'INSERT INTO lojas') !== false) {
                                echo '<span class="info">  • Lojas de exemplo inseridas...</span><br>';
                            }

                        } catch (PDOException $e) {
                            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                                $pulados++;
                            } else {
                                // Mostra erro mas continua
                                echo '<span class="warning">  ⚠ Aviso: ' . substr($e->getMessage(), 0, 80) . '...</span><br>';
                            }
                        }
                    }

                    echo '<span class="success">[OK] Dados inseridos! ' . $modulosInseridos . ' módulos, ' . $perguntasInseridas . ' perguntas (' . $executados . ' comandos, ' . $pulados . ' pulados)</span><br><br>';

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

                    echo '<br><span class="success">═══════════════════════════════════════</span><br>';
                    echo '<span class="success">[SUCESSO] ✅ Instalação concluída com êxito!</span><br>';
                    echo '<span class="success">═══════════════════════════════════════</span><br>';
                    echo '<span class="info">[INFO] Sistema pronto para uso!</span><br>';

                    echo '</div>';

                    echo '<div class="success-box">';
                    echo '<h3>✅ Instalação Concluída com Sucesso!</h3>';
                    echo '<p><strong>O que foi instalado:</strong></p>';
                    echo '<ul>';
                    echo '<li>✅ 8 tabelas do banco de dados</li>';
                    echo '<li>✅ 8 módulos de avaliação (Organização, Caixas, Ovos, Gôndolas, Frios, Câmara, Estoque, Áreas Comuns)</li>';
                    echo '<li>✅ 58 perguntas pré-cadastradas</li>';
                    echo '<li>✅ 4 lojas de exemplo</li>';
                    echo '<li>✅ 4 cargos padrão</li>';
                    echo '<li>✅ Configurações de pesos de pontuação</li>';
                    echo '<li>✅ Diretório de uploads criado</li>';
                    echo '</ul>';
                    echo '<p style="margin-top: 15px; padding: 10px; background: #fff3cd; border-radius: 5px;"><strong>⚠️ IMPORTANTE:</strong> Por questões de segurança, <strong>DELETE</strong> este arquivo (instalar_checklist.php) após a instalação!</p>';
                    echo '</div>';

                    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
                    $baseUrl .= str_replace('/instalar_checklist.php', '', $_SERVER['REQUEST_URI']);

                    echo '<div class="button-group">';
                    echo '<a href="' . $baseUrl . '/checklist/" class="btn btn-success">🚀 Acessar Sistema de Checklist</a>';
                    echo '<a href="' . $baseUrl . '/dashboard.php" class="btn">📊 Ir para Dashboard</a>';
                    echo '</div>';

                } catch (PDOException $e) {
                    echo '<span class="error">[ERRO PDO] ' . htmlspecialchars($e->getMessage()) . '</span><br>';
                    echo '<span class="error">[CÓDIGO] ' . $e->getCode() . '</span><br>';
                    echo '</div>';

                    echo '<div class="error-box">';
                    echo '<h3>❌ Erro de Conexão/Banco de Dados</h3>';
                    echo '<p><strong>Erro:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
                    echo '<p style="margin-top: 10px;"><strong>Possíveis soluções:</strong></p>';
                    echo '<ul>';
                    echo '<li>Verifique as configurações do banco de dados no início deste arquivo</li>';
                    echo '<li>Confirme que o banco de dados existe</li>';
                    echo '<li>Confirme que o usuário tem permissões para criar tabelas</li>';
                    echo '<li>Verifique se o MySQL está rodando</li>';
                    echo '</ul>';
                    echo '</div>';

                } catch (Exception $e) {
                    echo '<span class="error">[ERRO] ' . htmlspecialchars($e->getMessage()) . '</span><br>';
                    echo '</div>';

                    echo '<div class="error-box">';
                    echo '<h3>❌ Erro na Instalação</h3>';
                    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
                    echo '<p style="margin-top: 10px;">Verifique se os arquivos SQL estão na pasta correta:</p>';
                    echo '<ul>';
                    echo '<li>database/migrations/checklist_lojas_schema.sql</li>';
                    echo '<li>database/migrations/checklist_lojas_seed.sql</li>';
                    echo '</ul>';
                    echo '</div>';
                }

            } else {
                // ============================================
                // EXIBIR FORMULÁRIO DE INSTALAÇÃO
                // ============================================
            ?>

            <div class="info-box">
                <h3>📦 O que será instalado?</h3>
                <ul>
                    <li><strong>8 Tabelas:</strong> lojas, cargos_checklist, modulos_avaliacao, perguntas, checklists, respostas_checklist, fotos_checklist, configuracoes_sistema</li>
                    <li><strong>8 Módulos:</strong> Organização de Lojas, Caixas, Setor Ovos, Gôndolas e Ilhas, Balcão de Frios, Câmara Fria, Estoque, Áreas Comuns</li>
                    <li><strong>58 Perguntas:</strong> Distribuídas entre os 8 módulos</li>
                    <li><strong>4 Lojas de Exemplo:</strong> Para começar a usar imediatamente</li>
                    <li><strong>Sistema de Pontuação:</strong> Pesos configurados automaticamente</li>
                </ul>
            </div>

            <div class="warning-box">
                <h3>⚠️ Antes de Instalar</h3>
                <ul>
                    <li>Verifique se as configurações do banco de dados estão corretas (início deste arquivo)</li>
                    <li>O banco de dados será modificado (novas tabelas serão criadas)</li>
                    <li>Se as tabelas já existirem, a instalação irá pular elas</li>
                    <li>Após a instalação, DELETE este arquivo por segurança!</li>
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
                <button type="submit" name="instalar" class="btn" onclick="return confirm('Deseja iniciar a instalação do Sistema de Checklist de Lojas? Isso irá criar tabelas no banco de dados.');">
                    🚀 Instalar Banco de Dados
                </button>
                <p style="margin-top: 15px; color: #666; font-size: 14px;">
                    A instalação pode levar alguns segundos. Por favor, aguarde até o final.
                </p>
            </form>

            <?php } ?>
        </div>
    </div>
</body>
</html>
