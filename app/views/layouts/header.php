<?php
/**
 * Layout: Header
 * Cabeçalho padrão do sistema
 */

// Verifica se usuário está logado
if (!Auth::isLogged()) {
    header('Location: ' . BASE_URL);
    exit;
}

// Verifica timeout de sessão
if (Auth::checkSessionTimeout()) {
    header('Location: ' . BASE_URL . 'logout.php?timeout=1');
    exit;
}
// Carrega configurações visuais do sistema
require_once APP_PATH . 'classes/SystemConfig.php';
$appNameCfg = SystemConfig::get('app_name', APP_NAME);
$primaryColor = SystemConfig::get('primary_color', '#667eea');
$gradStart = SystemConfig::get('gradient_start', '#667eea');
$gradEnd = SystemConfig::get('gradient_end', '#764ba2');
$cfgFavicon = SystemConfig::get('favicon_path');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php echo $pageTitle ?? 'Dashboard'; ?> - <?php echo $appNameCfg; ?></title>

    <!-- CSS do Sistema -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/theme-variables.php">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/global.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/main.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/dashboard.css">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo $cfgFavicon ? BASE_URL . $cfgFavicon : (ASSETS_URL . 'img/favicon.png'); ?>">

    <!-- Meta tags -->
    <meta name="description" content="Sistema de Gestão de Capacitações">
    <meta name="author" content="Comercial do Norte">

    <!-- CSS adicional específico da página -->
    <?php if (isset($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>

    <style>
        :root {
            --primary-color: <?php echo $primaryColor; ?>;
            --gradient-start: <?php echo $gradStart; ?>;
            --gradient-end: <?php echo $gradEnd; ?>;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            color: #333;
        }

        .wrapper {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            margin-left: 260px;
            transition: margin-left 0.3s;
        }

        .main-content.sidebar-collapsed {
            margin-left: 70px;
        }

        .content-wrapper {
            padding: 20px 30px;
            max-width: 1400px;
        }

        .page-header {
            background: white;
            padding: 25px 30px;
            margin-bottom: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .page-header h1 {
            color: #2c3e50;
            font-size: 28px;
            margin-bottom: 5px;
        }

        .page-header .breadcrumb {
            color: #7f8c8d;
            font-size: 14px;
        }

        .page-header .breadcrumb a {
            color: var(--primary-color);
            text-decoration: none;
        }

        .page-header .breadcrumb a:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left-color: #28a745;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left-color: #dc3545;
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-left-color: #ffc107;
        }

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border-left-color: #17a2b8;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }

            .content-wrapper {
                padding: 15px;
            }

            .page-header h1 {
                font-size: 22px;
            }
        }
        /* Overrides globais para cor primária */
        .btn-primary {
            background: var(--primary-color);
            color: #fff;
        }
        .btn-primary:hover {
            filter: brightness(0.92);
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar será incluída aqui -->
        <?php include __DIR__ . '/sidebar.php'; ?>

        <div class="main-content" id="mainContent">
            <!-- Navbar será incluída aqui -->
            <?php include __DIR__ . '/navbar.php'; ?>

            <div class="content-wrapper">
                <!-- Mensagens flash da sessão -->
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success">
                        ✅ <?php echo e($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-error">
                        ❌ <?php echo e($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['warning_message'])): ?>
                    <div class="alert alert-warning">
                        ⚠️ <?php echo e($_SESSION['warning_message']); unset($_SESSION['warning_message']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['info_message'])): ?>
                    <div class="alert alert-info">
                        ℹ️ <?php echo e($_SESSION['info_message']); unset($_SESSION['info_message']); ?>
                    </div>
                <?php endif; ?>

                <!-- Cabeçalho da página -->
                <?php if (isset($pageTitle)): ?>
                    <div class="page-header">
                        <h1><?php echo e($pageTitle); ?></h1>
                        <?php if (isset($breadcrumb)): ?>
                            <div class="breadcrumb">
                                <?php echo $breadcrumb; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Conteúdo principal da página -->
