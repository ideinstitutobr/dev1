<?php
/**
 * Instalador: Portal do Colaborador + Sistema de Certificados
 * Execute este arquivo UMA ÚNICA VEZ via navegador
 */

// Define constante do sistema
define('SGC_SYSTEM', true);

// Carrega configurações
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/classes/Database.php';

// Conecta ao banco usando Singleton
$db = Database::getInstance();
$pdo = $db->getConnection();

$instalado = false;
$erros = [];
$sucessos = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['instalar'])) {
    try {
        $sucessos[] = "Iniciando instalação do Portal do Colaborador...";

        // Lê o arquivo SQL da migration
        $sqlFile = __DIR__ . '/../database/migrations/migration_portal_colaborador.sql';

        if (!file_exists($sqlFile)) {
            throw new Exception("Arquivo de migration não encontrado: $sqlFile");
        }

        $sql = file_get_contents($sqlFile);

        // Remove comentários SQL (linhas que começam com --)
        $sql = preg_replace('/^--.*$/m', '', $sql);

        // Separa os comandos SQL por ponto e vírgula
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($stmt) {
                // Remove apenas statements vazios
                return !empty($stmt);
            }
        );

        $executados = 0;
        $pulados = 0;

        // Executa cada comando separadamente
        foreach ($statements as $statement) {
            if (empty(trim($statement))) continue;

            try {
                // Comandos DDL fazem commit implícito, não precisam de transação
                $pdo->exec($statement);
                $executados++;

                // Identifica o tipo de comando executado
                if (stripos($statement, 'CREATE TABLE') !== false) {
                    preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches);
                    $tabela = $matches[1] ?? 'desconhecida';
                    $sucessos[] = "✅ Tabela '{$tabela}' criada com sucesso";
                } elseif (stripos($statement, 'ALTER TABLE') !== false) {
                    preg_match('/ALTER TABLE\s+`?(\w+)`?/i', $statement, $matches);
                    $tabela = $matches[1] ?? 'desconhecida';
                    $sucessos[] = "✅ Tabela '{$tabela}' alterada com sucesso";
                } elseif (stripos($statement, 'INSERT INTO') !== false) {
                    preg_match('/INSERT INTO\s+`?(\w+)`?/i', $statement, $matches);
                    $tabela = $matches[1] ?? 'desconhecida';
                    $sucessos[] = "✅ Dados inseridos em '{$tabela}'";
                } elseif (stripos($statement, 'CREATE INDEX') !== false) {
                    preg_match('/CREATE INDEX\s+`?(\w+)`?/i', $statement, $matches);
                    $indice = $matches[1] ?? 'desconhecido';
                    $sucessos[] = "✅ Índice '{$indice}' criado";
                }

            } catch (PDOException $e) {
                // Ignora erros de "já existe" (comandos com IF NOT EXISTS)
                if (
                    stripos($e->getMessage(), 'already exists') !== false ||
                    stripos($e->getMessage(), 'Duplicate column') !== false ||
                    stripos($e->getMessage(), 'Duplicate key') !== false
                ) {
                    $pulados++;
                    continue;
                }
                throw $e;
            }
        }

        $sucessos[] = "";
        $sucessos[] = "📊 RESUMO DA INSTALAÇÃO:";
        $sucessos[] = "✅ Comandos executados: $executados";
        if ($pulados > 0) {
            $sucessos[] = "ℹ️ Comandos pulados (já existiam): $pulados";
        }
        $sucessos[] = "";

        // Insere template padrão (via PHP para evitar problemas de aspas)
        // Executa APÓS todos os comandos SQL para garantir que as tabelas existam
        try {
            $templateHtml = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: "Times New Roman", Times, serif; margin: 0; padding: 0; }
        .certificado { width: 100%; height: 100%; border: 10px solid #667eea; padding: 50px 60px; text-align: center; position: relative; box-sizing: border-box; }
        .borda-interna { border: 2px solid #667eea; padding: 40px; height: 100%; }
        .titulo { font-size: 52px; color: #667eea; margin-top: 40px; font-weight: bold; letter-spacing: 4px; }
        .subtitulo { font-size: 22px; margin-top: 15px; color: #333; font-style: italic; }
        .nome { font-size: 36px; color: #000; margin-top: 50px; font-weight: bold; text-transform: uppercase; border-bottom: 3px solid #667eea; padding-bottom: 10px; display: inline-block; }
        .conteudo { font-size: 20px; margin-top: 40px; line-height: 1.9; color: #333; text-align: justify; padding: 0 40px; }
        .conteudo strong { color: #667eea; }
        .dados { margin-top: 50px; font-size: 16px; color: #555; line-height: 1.8; }
        .assinatura { margin-top: 70px; display: inline-block; }
        .linha-assinatura { border-top: 2px solid #000; width: 350px; margin: 0 auto; padding-top: 8px; font-size: 14px; color: #333; }
        .validacao { position: absolute; bottom: 30px; right: 40px; font-size: 11px; color: #999; text-align: right; line-height: 1.6; }
        .rodape { position: absolute; bottom: 30px; left: 40px; font-size: 11px; color: #999; text-align: left; }
    </style>
</head>
<body>
    <div class="certificado">
        <div class="borda-interna">
            <div class="titulo">CERTIFICADO</div>
            <div class="subtitulo">de Conclusão de Treinamento</div>
            <div class="nome">{COLABORADOR_NOME}</div>
            <div class="conteudo">
                Certificamos que o(a) colaborador(a) acima identificado(a), portador(a) do CPF <strong>{COLABORADOR_CPF}</strong>,
                ocupante do cargo de <strong>{COLABORADOR_CARGO}</strong>, participou com aproveitamento do treinamento
                <strong>{TREINAMENTO_NOME}</strong>, do tipo <strong>{TREINAMENTO_TIPO}</strong>,
                realizado no período de <strong>{DATA_INICIO_EXTENSO}</strong> a <strong>{DATA_FIM_EXTENSO}</strong>,
                com carga horária total de <strong>{CARGA_HORARIA} horas</strong>.
            </div>
            <div class="dados">
                <strong>Programa:</strong> {TREINAMENTO_PROGRAMA}<br>
                <strong>Instrutor:</strong> {INSTRUTOR}<br>
                <strong>Local:</strong> {LOCAL}
            </div>
            <div class="assinatura">
                <div class="linha-assinatura">
                    <strong>Recursos Humanos</strong><br>
                    Comercial do Norte
                </div>
            </div>
            <div class="rodape">Emitido em: {DATA_EMISSAO}</div>
            <div class="validacao">
                <strong>Certificado Nº:</strong> {NUMERO_CERTIFICADO}<br>
                <strong>Código de Validação:</strong><br>
                <span style="font-family: monospace; font-size: 9px;">{HASH_VALIDACAO}</span><br>
                <em>Valide em: https://comercial.ideinstituto.com.br/validar</em>
            </div>
        </div>
    </div>
</body>
</html>
HTML;

            // Verifica se template padrão já existe
            $checkTemplate = $pdo->query("SELECT COUNT(*) FROM certificado_templates WHERE padrao = 1")->fetchColumn();

            if ($checkTemplate == 0) {
                $stmtTemplate = $pdo->prepare("
                    INSERT INTO certificado_templates (
                        nome, descricao, orientacao, tamanho_papel,
                        cor_fundo, cor_borda, largura_borda,
                        cor_texto_principal, cor_texto_secundario,
                        padrao, ativo, campos_disponiveis, template_html
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmtTemplate->execute([
                    'Template Padrão - Comercial do Norte',
                    'Template padrão do sistema para certificados de treinamento',
                    'landscape',
                    'A4',
                    '#FFFFFF',
                    '#667eea',
                    10,
                    '#000000',
                    '#666666',
                    1,
                    1,
                    '["COLABORADOR_NOME", "COLABORADOR_CPF", "COLABORADOR_CARGO", "TREINAMENTO_NOME", "TREINAMENTO_TIPO", "TREINAMENTO_PROGRAMA", "CARGA_HORARIA", "DATA_INICIO", "DATA_FIM", "DATA_INICIO_EXTENSO", "DATA_FIM_EXTENSO", "DATA_EMISSAO", "INSTRUTOR", "LOCAL", "NUMERO_CERTIFICADO", "HASH_VALIDACAO", "ANO_ATUAL", "EMPRESA_NOME"]',
                    $templateHtml
                ]);

                $sucessos[] = "✅ Template padrão de certificado criado";
            } else {
                $sucessos[] = "ℹ️ Template padrão já existe";
            }
        } catch (PDOException $e) {
            $sucessos[] = "⚠️ Aviso: Não foi possível criar o template padrão: " . $e->getMessage();
        }

        $sucessos[] = "";
        $sucessos[] = "🎉 INSTALAÇÃO CONCLUÍDA COM SUCESSO!";
        $sucessos[] = "";
        $sucessos[] = "📋 O QUE FOI CRIADO:";
        $sucessos[] = "• Tabela 'colaboradores_senhas' - Senhas e autenticação";
        $sucessos[] = "• Tabela 'certificado_templates' - Templates de certificados";
        $sucessos[] = "• Tabela 'certificados_emitidos' - Controle de certificados";
        $sucessos[] = "• Campo 'portal_ativo' em 'colaboradores'";
        $sucessos[] = "• Template padrão de certificado";
        $sucessos[] = "• Índices para otimização de performance";

        $instalado = true;

    } catch (Exception $e) {
        $erros[] = "❌ Erro durante a instalação: " . $e->getMessage();
        $erros[] = "Detalhes: " . $e->getFile() . " linha " . $e->getLine();

        // Log do erro para debug
        error_log("ERRO INSTALAÇÃO PORTAL: " . $e->getMessage());
    }
}

// Verificação de status
$statusTabelas = [];
try {
    $tabelas = ['colaboradores_senhas', 'certificado_templates', 'certificados_emitidos'];
    foreach ($tabelas as $tabela) {
        $check = $pdo->query("SHOW TABLES LIKE '$tabela'")->fetch();
        $statusTabelas[$tabela] = $check ? '✅ Instalada' : '❌ Não instalada';
    }

    // Verifica campo portal_ativo
    $checkCampo = $pdo->query("SHOW COLUMNS FROM colaboradores LIKE 'portal_ativo'")->fetch();
    $statusTabelas['portal_ativo (campo)'] = $checkCampo ? '✅ Instalado' : '❌ Não instalado';

    // Conta template padrão
    $stmt = $pdo->query("SELECT COUNT(*) FROM certificado_templates WHERE padrao = 1");
    $temTemplate = $stmt->fetchColumn() > 0;
    $statusTabelas['Template padrão'] = $temTemplate ? '✅ Instalado' : '❌ Não instalado';

} catch (Exception $e) {
    // Ignora erros de verificação
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalar Portal do Colaborador - SGC</title>
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
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 50px 40px;
            text-align: center;
        }

        .header h1 {
            font-size: 36px;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 16px;
            opacity: 0.9;
        }

        .content {
            padding: 50px 40px;
        }

        .info-box {
            background: #e7f3ff;
            border-left: 5px solid #2196F3;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 5px;
        }

        .info-box h3 {
            color: #1976D2;
            margin-bottom: 15px;
            font-size: 18px;
        }

        .info-box ul {
            margin-left: 20px;
            color: #555;
            line-height: 1.8;
        }

        .warning-box {
            background: #fff3cd;
            border-left: 5px solid #ff9800;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 5px;
            color: #856404;
        }

        .warning-box strong {
            color: #cc6600;
        }

        .success-box {
            background: #d4edda;
            border-left: 5px solid #28a745;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .success-box p {
            color: #155724;
            margin: 8px 0;
            font-size: 15px;
        }

        .error-box {
            background: #f8d7da;
            border-left: 5px solid #dc3545;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .error-box p {
            color: #721c24;
            margin: 8px 0;
            font-size: 15px;
        }

        .status-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .status-table th,
        .status-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .status-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .status-table tr:hover {
            background: #f8f9fa;
        }

        .btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 18px 40px;
            font-size: 18px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s;
            margin-top: 20px;
        }

        .btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .btn-secondary {
            background: #6c757d;
            margin-top: 10px;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }

        .feature-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .feature-card h4 {
            color: #667eea;
            margin-bottom: 10px;
        }

        .feature-card ul {
            margin-left: 20px;
            color: #555;
            font-size: 14px;
            line-height: 1.8;
        }

        .code {
            background: #f5f5f5;
            padding: 3px 8px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            color: #d63384;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Instalador do Portal do Colaborador</h1>
            <p>Sistema de Login + Certificados Personalizados</p>
        </div>

        <div class="content">
            <?php if (!$instalado && empty($erros)): ?>
                <div class="info-box">
                    <h3>📋 O que será instalado:</h3>
                    <ul>
                        <li><strong>Portal do Colaborador:</strong> Sistema de login para colaboradores acessarem seus dados</li>
                        <li><strong>Sistema de Certificados:</strong> Templates personalizáveis e geração automática de PDFs</li>
                        <li><strong>3 novas tabelas:</strong> colaboradores_senhas, certificado_templates, certificados_emitidos</li>
                        <li><strong>Template padrão:</strong> Um template de certificado pronto para uso</li>
                    </ul>
                </div>

                <div class="features">
                    <div class="feature-card">
                        <h4>👤 Portal do Colaborador</h4>
                        <ul>
                            <li>Login seguro</li>
                            <li>Dashboard personalizado</li>
                            <li>Histórico de treinamentos</li>
                            <li>Download de certificados</li>
                        </ul>
                    </div>

                    <div class="feature-card">
                        <h4>🎓 Sistema de Certificados</h4>
                        <ul>
                            <li>Templates HTML customizáveis</li>
                            <li>Geração automática em PDF</li>
                            <li>Campos dinâmicos</li>
                            <li>Validação por hash</li>
                        </ul>
                    </div>

                    <div class="feature-card">
                        <h4>🔐 Área do RH</h4>
                        <ul>
                            <li>Gerenciar senhas</li>
                            <li>Criar templates</li>
                            <li>Emitir certificados</li>
                            <li>Controle de acessos</li>
                        </ul>
                    </div>
                </div>

                <div class="warning-box">
                    ⚠️ <strong>Importante:</strong> Este instalador só precisa ser executado UMA ÚNICA VEZ.<br>
                    Certifique-se de que você tem permissão para alterar o banco de dados.
                </div>

                <?php if (!empty($statusTabelas)): ?>
                    <h3 style="margin: 30px 0 15px 0;">📊 Status Atual:</h3>
                    <table class="status-table">
                        <thead>
                            <tr>
                                <th>Componente</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($statusTabelas as $nome => $status): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($nome); ?></td>
                                    <td><?php echo $status; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <form method="POST">
                    <button type="submit" name="instalar" class="btn">
                        ▶️ Iniciar Instalação
                    </button>
                </form>

            <?php endif; ?>

            <?php if (!empty($sucessos)): ?>
                <div class="success-box">
                    <?php foreach ($sucessos as $sucesso): ?>
                        <p><?php echo htmlspecialchars($sucesso); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($erros)): ?>
                <div class="error-box">
                    <?php foreach ($erros as $erro): ?>
                        <p><?php echo htmlspecialchars($erro); ?></p>
                    <?php endforeach; ?>
                </div>
                <form method="POST">
                    <button type="submit" name="instalar" class="btn">
                        🔄 Tentar Novamente
                    </button>
                </form>
            <?php endif; ?>

            <?php if ($instalado): ?>
                <div class="info-box">
                    <h3>🎯 Próximos Passos:</h3>
                    <ul>
                        <li>Acesse <strong>RH > Colaboradores > Gerenciar Senhas</strong> para criar senhas de acesso</li>
                        <li>Acesse <strong>RH > Certificados > Templates</strong> para personalizar certificados</li>
                        <li>Os colaboradores poderão acessar o portal em: <span class="code">/portal</span></li>
                        <li>Teste o template padrão emitindo um certificado</li>
                    </ul>
                </div>

                <a href="../dashboard.php" class="btn">
                    ✅ Ir para o Dashboard
                </a>

                <a href="../rh/colaboradores/gerenciar_senhas.php" class="btn btn-secondary">
                    🔑 Gerenciar Senhas de Colaboradores
                </a>
            <?php endif; ?>

            <div style="margin-top: 40px; padding-top: 20px; border-top: 2px solid #e1e8ed; text-align: center;">
                <p style="color: #999; font-size: 14px;">
                    📚 Documentação completa em: <span class="code">PROGRESSO_DO_PROJETO.md</span>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
