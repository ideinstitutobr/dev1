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
require_once __DIR__ . '/../../app/classes/SystemConfig.php';

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
     * SALVAR CONFIGURAÇÕES DO SISTEMA
     * ==========================================
     */
    case 'salvar_config_sistema':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: sistema.php');
            exit;
        }

        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Token de segurança inválido';
            header('Location: sistema.php');
            exit;
        }

        // Campos texto/cores
        $appName = trim($_POST['app_name'] ?? '');
        $primary = trim($_POST['primary_color'] ?? '#667eea');
        $gradStart = trim($_POST['gradient_start'] ?? '#667eea');
        $gradEnd = trim($_POST['gradient_end'] ?? '#764ba2');
        $loginText = trim($_POST['login_text'] ?? '');
        $footerText = trim($_POST['footer_text'] ?? '');
        $sidebarCollapsed = isset($_POST['sidebar_default_collapsed']) ? '1' : '0';

        // Persistir
        SystemConfig::set('app_name', $appName !== '' ? $appName : APP_NAME);
        SystemConfig::set('primary_color', $primary);
        SystemConfig::set('gradient_start', $gradStart);
        SystemConfig::set('gradient_end', $gradEnd);
        SystemConfig::set('login_text', $loginText);
        SystemConfig::set('footer_text', $footerText);
        SystemConfig::set('sidebar_default_collapsed', $sidebarCollapsed);

        // Uploads
        $uploadBase = PUBLIC_PATH . 'uploads/branding';
        if (!is_dir($uploadBase)) { @mkdir($uploadBase, 0775, true); }

        $allowed = ['image/png' => 'png', 'image/jpeg' => 'jpg'];

        // Logo
        if (!empty($_FILES['logo_file']['name']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
            // Validar tipo MIME
            $type = mime_content_type($_FILES['logo_file']['tmp_name']);
            if (!isset($allowed[$type])) {
                $_SESSION['flash_error'] = 'Formato de logo inválido. Use PNG ou JPEG.';
                header('Location: sistema.php');
                exit;
            }

            // Validar tamanho do arquivo (máximo 2MB)
            $maxSize = 2 * 1024 * 1024; // 2MB em bytes
            if ($_FILES['logo_file']['size'] > $maxSize) {
                $sizeMB = round($_FILES['logo_file']['size'] / (1024 * 1024), 2);
                $_SESSION['flash_error'] = "Logo muito grande ({$sizeMB} MB). Tamanho máximo: 2 MB.";
                header('Location: sistema.php');
                exit;
            }

            // Validar dimensões (aviso se não for ideal)
            $imageInfo = getimagesize($_FILES['logo_file']['tmp_name']);
            if ($imageInfo !== false) {
                list($width, $height) = $imageInfo;
                $ratio = $width / $height;

                // Aviso se a proporção não for horizontal (opcional, não bloqueia)
                if ($ratio < 2 || $ratio > 6) {
                    $_SESSION['flash_warning'] = "Logo carregado com dimensões {$width}x{$height}px. Recomendado: proporção horizontal (ex: 300x80px) para melhor visualização.";
                }
            }

            $ext = $allowed[$type];
            $dest = $uploadBase . '/logo.' . $ext;
            if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $dest)) {
                $rel = 'uploads/branding/logo.' . $ext;
                SystemConfig::set('logo_path', $rel);
            } else {
                $_SESSION['flash_error'] = 'Erro ao fazer upload do logo. Verifique as permissões do diretório.';
                header('Location: sistema.php');
                exit;
            }
        }

        // Favicon
        if (!empty($_FILES['favicon_file']['name']) && $_FILES['favicon_file']['error'] === UPLOAD_ERR_OK) {
            // Validar tipo MIME
            $type = mime_content_type($_FILES['favicon_file']['tmp_name']);
            if (!isset($allowed[$type])) {
                $_SESSION['flash_error'] = 'Formato de favicon inválido. Use PNG ou JPEG.';
                header('Location: sistema.php');
                exit;
            }

            // Validar tamanho do arquivo (máximo 500KB)
            $maxSize = 500 * 1024; // 500KB em bytes
            if ($_FILES['favicon_file']['size'] > $maxSize) {
                $sizeKB = round($_FILES['favicon_file']['size'] / 1024, 2);
                $_SESSION['flash_error'] = "Favicon muito grande ({$sizeKB} KB). Tamanho máximo: 500 KB.";
                header('Location: sistema.php');
                exit;
            }

            // Validar dimensões (aviso se não for quadrado)
            $imageInfo = getimagesize($_FILES['favicon_file']['tmp_name']);
            if ($imageInfo !== false) {
                list($width, $height) = $imageInfo;

                // Aviso se não for quadrado (opcional, não bloqueia)
                if ($width !== $height) {
                    $_SESSION['flash_warning'] = "Favicon carregado com dimensões {$width}x{$height}px. Recomendado: formato quadrado (ex: 32x32 ou 64x64px).";
                }
            }

            $ext = $allowed[$type];
            $dest = $uploadBase . '/favicon.' . $ext;
            if (move_uploaded_file($_FILES['favicon_file']['tmp_name'], $dest)) {
                $rel = 'uploads/branding/favicon.' . $ext;
                SystemConfig::set('favicon_path', $rel);
            } else {
                $_SESSION['flash_error'] = 'Erro ao fazer upload do favicon. Verifique as permissões do diretório.';
                header('Location: sistema.php');
                exit;
            }
        }

        $_SESSION['flash_success'] = 'Configurações do sistema salvas com sucesso!';
        header('Location: sistema.php');
        exit;
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
