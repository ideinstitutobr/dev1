<?php
/**
 * Actions: Relatórios
 * Processa ações de export dos relatórios
 */

// Define constante do sistema
define('SGC_SYSTEM', true);

// Carrega configurações e classes
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';
require_once __DIR__ . '/../../app/models/Relatorio.php';
require_once __DIR__ . '/../../app/controllers/RelatorioController.php';

// Verifica autenticação
Auth::requireLogin(BASE_URL);

// Instancia controller
$controller = new RelatorioController();

// Identifica ação
$action = $_GET['action'] ?? '';
$tipo = $_GET['tipo'] ?? '';
$formato = $_GET['formato'] ?? 'csv';

if ($action === 'exportar' && !empty($tipo)) {
    switch (strtolower($formato)) {
        case 'csv':
            $controller->exportarCSV($tipo);
            break;
        case 'xlsx':
            $controller->exportarExcel($tipo);
            break;
        case 'pdf':
            $controller->exportarPDF($tipo);
            break;
        default:
            $_SESSION['error_message'] = 'Formato inválido';
            header('Location: dashboard.php');
            exit;
    }
} else {
    $_SESSION['error_message'] = 'Ação inválida';
    header('Location: dashboard.php');
    exit;
}
