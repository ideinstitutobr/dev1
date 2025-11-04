<?php
/**
 * Script de Diagnóstico - PHPMailer
 * Verifica se o PHPMailer está instalado e funcionando
 */

// Define constante do sistema
define('SGC_SYSTEM', true);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar PHPMailer</title>
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
            max-width: 800px;
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

        .content {
            padding: 40px;
        }

        .status-box {
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        .status-success {
            background: #d4edda;
            border: 3px solid #28a745;
            color: #155724;
        }

        .status-error {
            background: #f8d7da;
            border: 3px solid #dc3545;
            color: #721c24;
        }

        .status-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .status-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .info-box {
            background: #f8f9fa;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #495057;
        }

        .info-value {
            color: #212529;
            font-family: monospace;
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
            transition: all 0.3s;
            margin: 10px;
        }

        .btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }

        .instructions {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }

        .instructions h3 {
            margin-bottom: 15px;
            color: #856404;
        }

        .instructions ol {
            margin-left: 20px;
        }

        .instructions li {
            margin: 10px 0;
            color: #856404;
        }

        code {
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
            color: #e83e8c;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Diagnóstico PHPMailer</h1>
            <p>Verificação de dependências do sistema de notificações</p>
        </div>

        <div class="content">
            <?php
            // Verifica PHPMailer
            $phpmailerInstalado = false;
            $phpmailerVersao = 'Desconhecida';
            $caminhoEncontrado = '';

            // Tenta carregar via namespace (Composer)
            if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                $phpmailerInstalado = true;
                $reflection = new ReflectionClass('PHPMailer\PHPMailer\PHPMailer');
                $caminhoEncontrado = dirname($reflection->getFileName());

                // Tenta pegar versão
                if (defined('PHPMailer\PHPMailer\PHPMailer::VERSION')) {
                    $phpmailerVersao = constant('PHPMailer\PHPMailer\PHPMailer::VERSION');
                }
            }

            // Tenta carregar manualmente
            if (!$phpmailerInstalado) {
                $possiveisCaminhos = [
                    __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php',
                    __DIR__ . '/../../vendor/phpmailer/phpmailer/src/PHPMailer.php',
                    __DIR__ . '/../vendor/autoload.php'
                ];

                foreach ($possiveisCaminhos as $caminho) {
                    if (file_exists($caminho)) {
                        $caminhoEncontrado = $caminho;
                        if (strpos($caminho, 'autoload.php') !== false) {
                            require_once $caminho;
                            if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                                $phpmailerInstalado = true;
                            }
                        }
                        break;
                    }
                }
            }

            if ($phpmailerInstalado):
            ?>
                <!-- SUCESSO -->
                <div class="status-box status-success">
                    <div class="status-icon">✅</div>
                    <div class="status-title">PHPMailer Instalado!</div>
                    <p>O sistema de notificações está pronto para uso.</p>
                </div>

                <div class="info-box">
                    <div class="info-row">
                        <span class="info-label">Status:</span>
                        <span class="info-value" style="color: #28a745;">✅ Instalado</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Versão:</span>
                        <span class="info-value"><?php echo htmlspecialchars($phpmailerVersao); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Caminho:</span>
                        <span class="info-value" style="font-size: 12px;"><?php echo htmlspecialchars($caminhoEncontrado); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">PHP Versão:</span>
                        <span class="info-value"><?php echo PHP_VERSION; ?></span>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 30px;">
                    <a href="configuracoes/email.php" class="btn">
                        ⚙️ Configurar E-mail (SMTP)
                    </a>
                    <a href="dashboard.php" class="btn">
                        🏠 Voltar ao Dashboard
                    </a>
                </div>

            <?php else: ?>
                <!-- ERRO -->
                <div class="status-box status-error">
                    <div class="status-icon">❌</div>
                    <div class="status-title">PHPMailer NÃO Instalado</div>
                    <p>O sistema de notificações não funcionará sem o PHPMailer.</p>
                </div>

                <div class="info-box">
                    <div class="info-row">
                        <span class="info-label">Status:</span>
                        <span class="info-value" style="color: #dc3545;">❌ Não Instalado</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">PHP Versão:</span>
                        <span class="info-value"><?php echo PHP_VERSION; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Servidor:</span>
                        <span class="info-value"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Desconhecido'; ?></span>
                    </div>
                </div>

                <div class="instructions">
                    <h3>📋 Como Instalar o PHPMailer:</h3>

                    <p><strong>Opção 1: Via Composer (Recomendado)</strong></p>
                    <ol>
                        <li>Acesse o servidor via SSH</li>
                        <li>Navegue até a pasta do projeto</li>
                        <li>Execute: <code>composer require phpmailer/phpmailer</code></li>
                    </ol>

                    <p style="margin-top: 20px;"><strong>Opção 2: Download Manual</strong></p>
                    <ol>
                        <li>Baixe: <a href="https://github.com/PHPMailer/PHPMailer/archive/refs/tags/v6.9.1.zip" target="_blank">PHPMailer v6.9.1</a></li>
                        <li>Extraia o ZIP</li>
                        <li>Copie a pasta <code>src/</code> para <code>vendor/phpmailer/phpmailer/src/</code></li>
                        <li>Certifique-se que estes arquivos existem:
                            <ul>
                                <li><code>vendor/phpmailer/phpmailer/src/PHPMailer.php</code></li>
                                <li><code>vendor/phpmailer/phpmailer/src/SMTP.php</code></li>
                                <li><code>vendor/phpmailer/phpmailer/src/Exception.php</code></li>
                            </ul>
                        </li>
                        <li>Recarregue esta página</li>
                    </ol>
                </div>

                <div style="text-align: center; margin-top: 30px;">
                    <a href="?refresh=1" class="btn">
                        🔄 Verificar Novamente
                    </a>
                    <a href="dashboard.php" class="btn">
                        🏠 Voltar ao Dashboard
                    </a>
                </div>

            <?php endif; ?>

            <div style="margin-top: 40px; padding-top: 20px; border-top: 2px solid #e1e8ed;">
                <h3 style="margin-bottom: 15px;">📚 Informações Adicionais:</h3>
                <ul style="line-height: 2;">
                    <li>📄 <a href="../INSTRUCOES_PHPMAILER.md" target="_blank">Instruções Completas de Instalação</a></li>
                    <li>🌐 <a href="https://github.com/PHPMailer/PHPMailer" target="_blank">PHPMailer no GitHub</a></li>
                    <li>📖 <a href="https://github.com/PHPMailer/PHPMailer/wiki" target="_blank">Documentação Oficial</a></li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
