<?php
/**
 * Actions: Configurações
 * Processa ações relacionadas a configurações do sistema
 */

// Define constante do sistema
define('SGC_SYSTEM', true);

// Carrega configurações e classes
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';
require_once __DIR__ . '/../../app/classes/NotificationManager.php';

// Verifica autenticação
Auth::requireLogin(BASE_URL);

// Apenas admin pode configurar
if (Auth::getUserLevel() !== 'admin') {
    $_SESSION['flash_error'] = 'Acesso negado';
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

// Instancia database
$db = Database::getInstance();
$pdo = $db->getConnection();

// Determina ação
$action = $_REQUEST['action'] ?? '';

switch ($action) {
    /**
     * ==========================================
     * SALVAR CONFIGURAÇÕES DE E-MAIL
     * ==========================================
     */
    case 'salvar_config_email':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: email.php');
            exit;
        }

        // Valida CSRF
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Token de segurança inválido';
            header('Location: email.php');
            exit;
        }

        $smtpHost = trim($_POST['smtp_host'] ?? '');
        $smtpPort = (int)($_POST['smtp_port'] ?? 587);
        $smtpSecure = $_POST['smtp_secure'] ?? 'tls';
        $smtpUser = trim($_POST['smtp_user'] ?? '');
        $smtpPassword = trim($_POST['smtp_password'] ?? '');
        $emailRemetente = trim($_POST['email_remetente'] ?? '');
        $nomeRemetente = trim($_POST['nome_remetente'] ?? '');
        $habilitado = isset($_POST['habilitado']) ? 1 : 0;

        // Validações
        if (empty($smtpHost) || empty($smtpUser) || empty($emailRemetente) || empty($nomeRemetente)) {
            $_SESSION['flash_error'] = 'Preencha todos os campos obrigatórios';
            header('Location: email.php');
            exit;
        }

        // Se senha não foi preenchida, mantém a atual
        $sql = "UPDATE configuracoes_email SET
                smtp_host = ?,
                smtp_port = ?,
                smtp_secure = ?,
                smtp_user = ?,
                email_remetente = ?,
                nome_remetente = ?,
                habilitado = ?";

        $params = [
            $smtpHost,
            $smtpPort,
            $smtpSecure,
            $smtpUser,
            $emailRemetente,
            $nomeRemetente,
            $habilitado
        ];

        // Se senha foi preenchida, atualiza também
        if (!empty($smtpPassword)) {
            $sql .= ", smtp_password = ?";
            $params[] = $smtpPassword;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $_SESSION['flash_success'] = 'Configurações salvas com sucesso!';
        header('Location: email.php');
        exit;

    /**
     * ==========================================
     * TESTAR CONEXÃO DE E-MAIL
     * ==========================================
     */
    case 'testar_email':
        // Define content-type antes de qualquer output
        ob_clean(); // Limpa qualquer output anterior
        header('Content-Type: application/json');

        // Valida CSRF
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Token inválido']);
            exit;
        }

        try {
            // Verifica se PHPMailer está disponível
            if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                echo json_encode([
                    'success' => false,
                    'message' => 'PHPMailer não está instalado. Instale via Composer: composer require phpmailer/phpmailer'
                ]);
                exit;
            }

            $notification = new NotificationManager();

            if (!$notification->isConfigured()) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Sistema de e-mail não configurado. Preencha todos os campos obrigatórios e marque como "Habilitado".'
                ]);
                exit;
            }

            $result = $notification->testarConexao();
            echo json_encode($result);

        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao testar conexão: ' . $e->getMessage()
            ]);
        }
        exit;

    /**
     * ==========================================
     * AÇÃO INVÁLIDA
     * ==========================================
     */
    default:
        $_SESSION['flash_error'] = 'Ação inválida';
        header('Location: email.php');
        exit;
}
