<?php
/**
 * Classe NotificationManager
 * Gerencia envio de notificações por e-mail aos participantes
 * Usa PHPMailer para envio
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class NotificationManager {
    private $pdo;
    private $mailer;
    private $config;
    private $lastError;

    /**
     * Construtor
     */
    public function __construct() {
        $db = Database::getInstance();
        $this->pdo = $db->getConnection();
        $this->loadConfig();

        if ($this->config && $this->config['habilitado']) {
            $this->setupMailer();
        }
    }

    /**
     * Envia e-mail genérico (credenciais, avisos, etc.)
     * @param string $destinatario
     * @param string $assunto
     * @param string $corpoHtml
     * @return bool
     */
    public function enviarEmailGenerico($destinatario, $assunto, $corpoHtml) {
        if (!$this->isConfigured()) {
            $this->lastError = 'Sistema de e-mail não configurado';
            return false;
        }

        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($destinatario);
            $this->mailer->Subject = $assunto;
            $this->mailer->isHTML(true);
            $this->mailer->Body = $corpoHtml;
            $this->mailer->send();

            // Log opcional
            if ($this->pdo) {
                $stmt = $this->pdo->prepare("INSERT INTO email_logs (destinatario, assunto, status) VALUES (?, ?, 'sucesso')");
                $stmt->execute([$destinatario, $assunto]);
            }
            return true;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            if ($this->pdo) {
                $stmt = $this->pdo->prepare("INSERT INTO email_logs (destinatario, assunto, status, mensagem_erro) VALUES (?, ?, 'erro', ?)");
                $stmt->execute([$destinatario, $assunto, $e->getMessage()]);
            }
            return false;
        }
    }

    /**
     * Retorna última mensagem de erro ao enviar e-mail
     */
    public function getLastError() {
        return $this->lastError ?? null;
    }

    /**
     * Carrega configurações SMTP do banco
     */
    private function loadConfig() {
        $stmt = $this->pdo->query("SELECT * FROM configuracoes_email LIMIT 1");
        $this->config = $stmt->fetch();
    }

    /**
     * Configura PHPMailer com dados do banco
     */
    private function setupMailer() {
        // Verifica se PHPMailer está disponível
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            // Tenta carregar via Composer autoload
            if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
                require_once __DIR__ . '/../../vendor/autoload.php';
            }
            // Senão, tenta caminho standalone
            elseif (file_exists(__DIR__ . '/../../vendor/phpmailer/src/PHPMailer.php')) {
                require_once __DIR__ . '/../../vendor/phpmailer/src/PHPMailer.php';
                require_once __DIR__ . '/../../vendor/phpmailer/src/SMTP.php';
                require_once __DIR__ . '/../../vendor/phpmailer/src/Exception.php';
            }
            // Tenta instalação global do servidor
            elseif (file_exists('/usr/share/php/PHPMailer/PHPMailer.php')) {
                require_once '/usr/share/php/PHPMailer/PHPMailer.php';
                require_once '/usr/share/php/PHPMailer/SMTP.php';
                require_once '/usr/share/php/PHPMailer/Exception.php';
            }
            // Versão antiga (class.phpmailer.php)
            elseif (file_exists(__DIR__ . '/../../lib/PHPMailer/class.phpmailer.php')) {
                require_once __DIR__ . '/../../lib/PHPMailer/class.phpmailer.php';
                require_once __DIR__ . '/../../lib/PHPMailer/class.smtp.php';
            }
        }

        // Usa namespace se disponível, senão classe antiga
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            $this->mailer = new PHPMailer(true);
        } else {
            // Fallback para versão antiga
            $this->mailer = new \PHPMailer(true);
        }

        // Configurações SMTP
        $this->mailer->isSMTP();
        $this->mailer->Host = $this->config['smtp_host'];
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = $this->config['smtp_user'];
        $this->mailer->Password = $this->config['smtp_password'];
        $this->mailer->SMTPSecure = $this->config['smtp_secure'];
        $this->mailer->Port = $this->config['smtp_port'];
        $this->mailer->CharSet = 'UTF-8';

        // Remetente padrão
        $this->mailer->setFrom(
            $this->config['email_remetente'],
            $this->config['nome_remetente']
        );
    }

    /**
     * Verifica se o sistema de e-mail está configurado
     */
    public function isConfigured() {
        return $this->config &&
               $this->config['habilitado'] &&
               !empty($this->config['smtp_user']) &&
               !empty($this->config['smtp_password']) &&
               !empty($this->config['email_remetente']);
    }

    /**
     * Envia convite para participante
     */
    public function enviarConvite($participanteId) {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Sistema de e-mail não configurado. Configure em Configurações > E-mail'
            ];
        }

        try {
            // Busca dados do participante e treinamento
            $stmt = $this->pdo->prepare("
                SELECT
                    tp.id as participante_id,
                    c.nome as colaborador_nome,
                    c.email as colaborador_email,
                    t.id as treinamento_id,
                    t.nome as treinamento_nome,
                    t.carga_horaria,
                    t.data_inicio,
                    t.data_fim,
                    t.instrutor,
                    t.observacoes
                FROM treinamento_participantes tp
                JOIN colaboradores c ON tp.colaborador_id = c.id
                JOIN treinamentos t ON tp.treinamento_id = t.id
                WHERE tp.id = ?
            ");
            $stmt->execute([$participanteId]);
            $dados = $stmt->fetch();

            if (!$dados) {
                throw new Exception("Participante não encontrado");
            }

            // Gera token único para check-in
            $token = bin2hex(random_bytes(32));
            $expiracao = date('Y-m-d H:i:s', strtotime('+30 days'));

            // Salva notificação no banco
            $stmt = $this->pdo->prepare("
                INSERT INTO notificacoes
                (participante_id, tipo, email_destinatario, token_check_in, expiracao_token, assunto)
                VALUES (?, 'convite', ?, ?, ?, ?)
            ");
            $assunto = "Convite: {$dados['treinamento_nome']}";
            $stmt->execute([$participanteId, $dados['colaborador_email'], $token, $expiracao, $assunto]);
            $notificacaoId = $this->pdo->lastInsertId();

            // Monta corpo do e-mail
            $linkCheckin = BASE_URL . "checkin.php?token={$token}";
            $corpo = $this->montarTemplateConvite($dados, $linkCheckin);

            // Envia e-mail
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($dados['colaborador_email'], $dados['colaborador_nome']);
            $this->mailer->Subject = $assunto;
            $this->mailer->isHTML(true);
            $this->mailer->Body = $corpo;

            $this->mailer->send();

            // Atualiza notificação como enviada
            $stmt = $this->pdo->prepare("
                UPDATE notificacoes
                SET email_enviado = 1,
                    data_envio = NOW(),
                    corpo_email = ?
                WHERE id = ?
            ");
            $stmt->execute([$corpo, $notificacaoId]);

            // Log de sucesso
            $this->logEmail($notificacaoId, $dados['colaborador_email'], $assunto, 'sucesso');

            return [
                'success' => true,
                'message' => 'Convite enviado com sucesso!',
                'token' => $token
            ];

        } catch (Exception $e) {
            // Registra erro no banco
            if (isset($notificacaoId)) {
                $stmt = $this->pdo->prepare("
                    UPDATE notificacoes
                    SET tentativas_envio = tentativas_envio + 1,
                        erro_envio = ?
                    WHERE id = ?
                ");
                $stmt->execute([$e->getMessage(), $notificacaoId]);

                // Log de erro
                $this->logEmail($notificacaoId, $dados['colaborador_email'], $assunto, 'erro', $e->getMessage());
            }

            return [
                'success' => false,
                'message' => 'Erro ao enviar convite: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Envia lembrete (1 dia antes do treinamento)
     */
    public function enviarLembrete($participanteId) {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'Sistema de e-mail não configurado'];
        }

        try {
            // Busca dados
            $stmt = $this->pdo->prepare("
                SELECT
                    tp.id as participante_id,
                    c.nome as colaborador_nome,
                    c.email as colaborador_email,
                    t.nome as treinamento_nome,
                    t.data_inicio,
                    t.instrutor
                FROM treinamento_participantes tp
                JOIN colaboradores c ON tp.colaborador_id = c.id
                JOIN treinamentos t ON tp.treinamento_id = t.id
                WHERE tp.id = ?
            ");
            $stmt->execute([$participanteId]);
            $dados = $stmt->fetch();

            if (!$dados) {
                throw new Exception("Participante não encontrado");
            }

            $assunto = "Lembrete: {$dados['treinamento_nome']} amanhã!";
            $corpo = $this->montarTemplateLembrete($dados);

            // Salva notificação
            $stmt = $this->pdo->prepare("
                INSERT INTO notificacoes
                (participante_id, tipo, email_destinatario, assunto, corpo_email)
                VALUES (?, 'lembrete', ?, ?, ?)
            ");
            $stmt->execute([$participanteId, $dados['colaborador_email'], $assunto, $corpo]);
            $notificacaoId = $this->pdo->lastInsertId();

            // Envia
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($dados['colaborador_email'], $dados['colaborador_nome']);
            $this->mailer->Subject = $assunto;
            $this->mailer->isHTML(true);
            $this->mailer->Body = $corpo;
            $this->mailer->send();

            // Atualiza status
            $stmt = $this->pdo->prepare("UPDATE notificacoes SET email_enviado = 1, data_envio = NOW() WHERE id = ?");
            $stmt->execute([$notificacaoId]);

            $this->logEmail($notificacaoId, $dados['colaborador_email'], $assunto, 'sucesso');

            return ['success' => true, 'message' => 'Lembrete enviado!'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro: ' . $e->getMessage()];
        }
    }

    /**
     * Registra log de envio de e-mail
     */
    private function logEmail($notificacaoId, $destinatario, $assunto, $status, $erro = null) {
        $stmt = $this->pdo->prepare("
            INSERT INTO email_logs (notificacao_id, destinatario, assunto, status, mensagem_erro)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$notificacaoId, $destinatario, $assunto, $status, $erro]);
    }

    /**
     * Monta template HTML do convite
     */
    private function montarTemplateConvite($dados, $linkCheckin) {
        $dataFormatada = date('d/m/Y', strtotime($dados['data_inicio']));

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .ticket { max-width: 600px; margin: 20px auto; border: 3px solid #667eea; border-radius: 10px; overflow: hidden; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
                .header h1 { margin: 0; font-size: 28px; }
                .content { padding: 40px 30px; background: #f9f9f9; }
                .treinamento-nome { color: #667eea; font-size: 22px; margin: 20px 0; font-weight: bold; }
                .info-item { margin: 15px 0; padding: 10px; background: white; border-left: 4px solid #667eea; }
                .button-container { text-align: center; margin: 40px 0 20px; }
                .button { background: #667eea; color: white !important; padding: 15px 40px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold; font-size: 16px; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; background: #e9e9e9; }
            </style>
        </head>
        <body>
            <div class='ticket'>
                <div class='header'>
                    <h1>🎓 Ticket de Participação</h1>
                    <p style='margin: 10px 0 0 0; font-size: 16px;'>Sistema de Gestão de Capacitações</p>
                </div>

                <div class='content'>
                    <p>Olá, <strong>{$dados['colaborador_nome']}</strong>!</p>
                    <p>Você foi inscrito(a) no seguinte treinamento:</p>

                    <div class='treinamento-nome'>{$dados['treinamento_nome']}</div>

                    <div class='info-item'><strong>📅 Data:</strong> {$dataFormatada}</div>
                    <div class='info-item'><strong>👨‍🏫 Instrutor:</strong> {$dados['instrutor']}</div>
                    <div class='info-item'><strong>⏱️ Carga Horária:</strong> {$dados['carga_horaria']}h</div>

                    <div class='button-container'>
                        <a href='{$linkCheckin}' class='button'>✅ CONFIRMAR PRESENÇA</a>
                    </div>

                    <p style='font-size: 14px; color: #666; text-align: center;'>
                        É importante confirmar sua presença clicando no botão acima.<br>
                        Este link é válido por 30 dias.
                    </p>
                </div>

                <div class='footer'>
                    <p>Este é um e-mail automático. Por favor, não responda.</p>
                    <p>Para dúvidas, entre em contato com o RH.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Monta template de lembrete
     */
    private function montarTemplateLembrete($dados) {
        $dataFormatada = date('d/m/Y', strtotime($dados['data_inicio']));

        return "
        <!DOCTYPE html>
        <html>
        <head><meta charset='UTF-8'></head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 20px auto; border: 3px solid #ffc107; border-radius: 10px; overflow: hidden;'>
                <div style='background: #ffc107; color: #333; padding: 30px; text-align: center;'>
                    <h1 style='margin: 0;'>⏰ Lembrete de Treinamento</h1>
                </div>
                <div style='padding: 40px 30px; background: #fff;'>
                    <p>Olá, <strong>{$dados['colaborador_nome']}</strong>!</p>
                    <p>Lembrando que amanhã, <strong>{$dataFormatada}</strong>, você tem o treinamento:</p>
                    <h2 style='color: #ffc107;'>{$dados['treinamento_nome']}</h2>
                    <p><strong>👨‍🏫 Instrutor:</strong> {$dados['instrutor']}</p>
                    <p style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;'>
                        <strong>⚠️ Importante:</strong> Não esqueça de comparecer!
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Testa configuração de e-mail
     */
    public function testarConexao() {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'Sistema não configurado'];
        }

        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($this->config['email_remetente']);
            $this->mailer->Subject = 'Teste de Conexão - SGC';
            $this->mailer->isHTML(true);
            $this->mailer->Body = '<p>Este é um e-mail de teste do Sistema de Gestão de Capacitações.</p>';

            $this->mailer->send();

            return ['success' => true, 'message' => 'E-mail de teste enviado com sucesso!'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro: ' . $e->getMessage()];
        }
    }
}
