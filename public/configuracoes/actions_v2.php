<?php
/**
 * Actions: Configurações V2 - Tema Completo
 * Processa customização completa do tema
 */

define('SGC_SYSTEM', true);

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';
require_once __DIR__ . '/../../app/classes/SystemConfig.php';

// Verifica autenticação
Auth::requireLogin(BASE_URL);

// Apenas admin pode configurar
if (Auth::getUserLevel() !== 'admin') {
    $_SESSION['flash_error'] = 'Acesso negado';
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

// Determina ação
$action = $_REQUEST['action'] ?? '';

switch ($action) {
    /**
     * ==========================================
     * SALVAR TEMA COMPLETO
     * ==========================================
     */
    case 'salvar_tema_completo':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: sistema_v2.php');
            exit;
        }

        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Token de segurança inválido';
            header('Location: sistema_v2.php');
            exit;
        }

        // Identidade
        $appName = trim($_POST['app_name'] ?? '');

        // Cores Primárias
        $primaryColor = trim($_POST['primary_color'] ?? '#667eea');
        $primaryDark = trim($_POST['primary_dark'] ?? '#5568d3');
        $primaryLight = trim($_POST['primary_light'] ?? '#7c8ef7');

        // Cores Secundárias
        $secondaryColor = trim($_POST['secondary_color'] ?? '#6c757d');
        $secondaryDark = trim($_POST['secondary_dark'] ?? '#5a6268');
        $secondaryLight = trim($_POST['secondary_light'] ?? '#868e96');

        // Cores de Status
        $successColor = trim($_POST['success_color'] ?? '#28a745');
        $dangerColor = trim($_POST['danger_color'] ?? '#dc3545');
        $warningColor = trim($_POST['warning_color'] ?? '#ffc107');
        $infoColor = trim($_POST['info_color'] ?? '#17a2b8');

        // Cores de Texto
        $textPrimary = trim($_POST['text_primary'] ?? '#2c3e50');
        $textSecondary = trim($_POST['text_secondary'] ?? '#6c757d');
        $textMuted = trim($_POST['text_muted'] ?? '#999999');

        // Cores de Links
        $linkColor = trim($_POST['link_color'] ?? '#667eea');
        $linkHover = trim($_POST['link_hover'] ?? '#5568d3');

        // Cores de Fundo
        $bgBody = trim($_POST['bg_body'] ?? '#f5f6fa');
        $bgContent = trim($_POST['bg_content'] ?? '#ffffff');

        // Cores do Sidebar
        $sidebarBg = trim($_POST['sidebar_bg'] ?? '#2c3e50');
        $sidebarText = trim($_POST['sidebar_text'] ?? '#ecf0f1');
        $sidebarHover = trim($_POST['sidebar_hover'] ?? '#34495e');
        $sidebarActive = trim($_POST['sidebar_active'] ?? '#667eea');
        $sidebarActiveBorder = trim($_POST['sidebar_active_border'] ?? '#ffffff');
        $sidebarHeaderBorder = trim($_POST['sidebar_header_border'] ?? 'rgba(255,255,255,0.1)');
        $sidebarSubmenuBg = trim($_POST['sidebar_submenu_bg'] ?? 'rgba(0,0,0,0.15)');
        $sidebarToggleBg = trim($_POST['sidebar_toggle_bg'] ?? '#ffffff');
        $sidebarToggleColor = trim($_POST['sidebar_toggle_color'] ?? '#333333');

        // Gradientes
        $gradStart = trim($_POST['gradient_start'] ?? '#667eea');
        $gradEnd = trim($_POST['gradient_end'] ?? '#764ba2');

        // Tipografia
        $fontFamily = trim($_POST['font_family'] ?? "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif");
        $fontFamilyHeadings = trim($_POST['font_family_headings'] ?? "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif");
        $fontSizeBase = trim($_POST['font_size_base'] ?? '14px');
        $fontSizeLarge = trim($_POST['font_size_large'] ?? '16px');
        $fontSizeSmall = trim($_POST['font_size_small'] ?? '12px');

        // Outros
        $borderRadius = trim($_POST['border_radius'] ?? '6px');
        $sidebarCollapsed = isset($_POST['sidebar_default_collapsed']) ? '1' : '0';

        // Textos
        $loginText = trim($_POST['login_text'] ?? '');
        $footerText = trim($_POST['footer_text'] ?? '');

        // Salvar todas as configurações
        try {
            // Identidade
            SystemConfig::set('app_name', $appName !== '' ? $appName : APP_NAME);

            // Cores Primárias
            SystemConfig::set('primary_color', $primaryColor);
            SystemConfig::set('primary_dark', $primaryDark);
            SystemConfig::set('primary_light', $primaryLight);

            // Cores Secundárias
            SystemConfig::set('secondary_color', $secondaryColor);
            SystemConfig::set('secondary_dark', $secondaryDark);
            SystemConfig::set('secondary_light', $secondaryLight);

            // Cores de Status
            SystemConfig::set('success_color', $successColor);
            SystemConfig::set('danger_color', $dangerColor);
            SystemConfig::set('warning_color', $warningColor);
            SystemConfig::set('info_color', $infoColor);

            // Cores de Texto
            SystemConfig::set('text_primary', $textPrimary);
            SystemConfig::set('text_secondary', $textSecondary);
            SystemConfig::set('text_muted', $textMuted);

            // Cores de Links
            SystemConfig::set('link_color', $linkColor);
            SystemConfig::set('link_hover', $linkHover);

            // Cores de Fundo
            SystemConfig::set('bg_body', $bgBody);
            SystemConfig::set('bg_content', $bgContent);

            // Cores do Sidebar
            SystemConfig::set('sidebar_bg', $sidebarBg);
            SystemConfig::set('sidebar_text', $sidebarText);
            SystemConfig::set('sidebar_hover', $sidebarHover);
            SystemConfig::set('sidebar_active', $sidebarActive);
            SystemConfig::set('sidebar_active_border', $sidebarActiveBorder);
            SystemConfig::set('sidebar_header_border', $sidebarHeaderBorder);
            SystemConfig::set('sidebar_submenu_bg', $sidebarSubmenuBg);
            SystemConfig::set('sidebar_toggle_bg', $sidebarToggleBg);
            SystemConfig::set('sidebar_toggle_color', $sidebarToggleColor);

            // Gradientes
            SystemConfig::set('gradient_start', $gradStart);
            SystemConfig::set('gradient_end', $gradEnd);

            // Tipografia
            SystemConfig::set('font_family', $fontFamily);
            SystemConfig::set('font_family_headings', $fontFamilyHeadings);
            SystemConfig::set('font_size_base', $fontSizeBase);
            SystemConfig::set('font_size_large', $fontSizeLarge);
            SystemConfig::set('font_size_small', $fontSizeSmall);

            // Outros
            SystemConfig::set('border_radius', $borderRadius);
            SystemConfig::set('sidebar_default_collapsed', $sidebarCollapsed);

            // Textos
            SystemConfig::set('login_text', $loginText);
            SystemConfig::set('footer_text', $footerText);

            // Uploads de logo e favicon (usar lógica existente do actions.php)
            $uploadBase = PUBLIC_PATH . 'uploads/branding';
            if (!is_dir($uploadBase)) { @mkdir($uploadBase, 0775, true); }

            $allowed = ['image/png' => 'png', 'image/jpeg' => 'jpg'];

            // Logo
            if (!empty($_FILES['logo_file']['name']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
                $type = mime_content_type($_FILES['logo_file']['tmp_name']);
                if (!isset($allowed[$type])) {
                    $_SESSION['flash_error'] = 'Formato de logo inválido. Use PNG ou JPEG.';
                    header('Location: sistema_v2.php');
                    exit;
                }

                $maxSize = 2 * 1024 * 1024;
                if ($_FILES['logo_file']['size'] > $maxSize) {
                    $sizeMB = round($_FILES['logo_file']['size'] / (1024 * 1024), 2);
                    $_SESSION['flash_error'] = "Logo muito grande ({$sizeMB} MB). Tamanho máximo: 2 MB.";
                    header('Location: sistema_v2.php');
                    exit;
                }

                $ext = $allowed[$type];
                $dest = $uploadBase . '/logo.' . $ext;
                if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $dest)) {
                    $rel = 'uploads/branding/logo.' . $ext;
                    SystemConfig::set('logo_path', $rel);
                }
            }

            // Favicon
            if (!empty($_FILES['favicon_file']['name']) && $_FILES['favicon_file']['error'] === UPLOAD_ERR_OK) {
                $type = mime_content_type($_FILES['favicon_file']['tmp_name']);
                if (!isset($allowed[$type])) {
                    $_SESSION['flash_error'] = 'Formato de favicon inválido. Use PNG ou JPEG.';
                    header('Location: sistema_v2.php');
                    exit;
                }

                $maxSize = 500 * 1024;
                if ($_FILES['favicon_file']['size'] > $maxSize) {
                    $sizeKB = round($_FILES['favicon_file']['size'] / 1024, 2);
                    $_SESSION['flash_error'] = "Favicon muito grande ({$sizeKB} KB). Tamanho máximo: 500 KB.";
                    header('Location: sistema_v2.php');
                    exit;
                }

                $ext = $allowed[$type];
                $dest = $uploadBase . '/favicon.' . $ext;
                if (move_uploaded_file($_FILES['favicon_file']['tmp_name'], $dest)) {
                    $rel = 'uploads/branding/favicon.' . $ext;
                    SystemConfig::set('favicon_path', $rel);
                }
            }

            $_SESSION['flash_success'] = '🎨 Tema customizado com sucesso! As alterações foram aplicadas em todo o sistema.';

        } catch (Exception $e) {
            $_SESSION['flash_error'] = 'Erro ao salvar configurações: ' . $e->getMessage();
        }

        header('Location: sistema_v2.php');
        exit;

    /**
     * ==========================================
     * RESETAR PARA PADRÕES
     * ==========================================
     */
    case 'reset_tema':
        if (!csrf_validate($_GET['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Token de segurança inválido';
            header('Location: sistema_v2.php');
            exit;
        }

        try {
            $db = Database::getInstance();
            $pdo = $db->getConnection();

            // Deleta todas as configurações de tema (mantém logos e textos)
            $configsToReset = [
                'primary_color', 'primary_dark', 'primary_light',
                'secondary_color', 'secondary_dark', 'secondary_light',
                'success_color', 'danger_color', 'warning_color', 'info_color',
                'text_primary', 'text_secondary', 'text_muted',
                'link_color', 'link_hover',
                'bg_body', 'bg_content',
                'sidebar_bg', 'sidebar_text', 'sidebar_hover', 'sidebar_active',
                'sidebar_active_border', 'sidebar_header_border', 'sidebar_submenu_bg',
                'sidebar_toggle_bg', 'sidebar_toggle_color',
                'gradient_start', 'gradient_end',
                'font_family', 'font_family_headings',
                'font_size_base', 'font_size_large', 'font_size_small',
                'border_radius'
            ];

            $stmt = $pdo->prepare('DELETE FROM configuracoes_sistema WHERE chave = ?');
            foreach ($configsToReset as $key) {
                $stmt->execute([$key]);
            }

            $_SESSION['flash_success'] = '🔄 Tema restaurado para os valores padrão!';

        } catch (Exception $e) {
            $_SESSION['flash_error'] = 'Erro ao resetar tema: ' . $e->getMessage();
        }

        header('Location: sistema_v2.php');
        exit;

    /**
     * ==========================================
     * AÇÃO INVÁLIDA
     * ==========================================
     */
    default:
        $_SESSION['flash_error'] = 'Ação inválida';
        header('Location: sistema_v2.php');
        exit;
}
