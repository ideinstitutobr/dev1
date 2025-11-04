<?php
/**
 * Actions: Agenda
 */

define('SGC_SYSTEM', true);

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';
require_once __DIR__ . '/../../app/models/Agenda.php';
require_once __DIR__ . '/../../app/controllers/AgendaController.php';

Auth::requireLogin(BASE_URL);

$controller = new AgendaController();
$action = $_REQUEST['action'] ?? '';
$treinamentoId = $_REQUEST['treinamento_id'] ?? 0;

switch ($action) {
    case 'criar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: gerenciar.php?treinamento_id=' . $treinamentoId);
            exit;
        }

        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Token inválido';
            header('Location: gerenciar.php?treinamento_id=' . $treinamentoId);
            exit;
        }

        $result = $controller->criarAgenda($_POST);

        if ($result['success']) {
            $_SESSION['flash_success'] = $result['message'];
        } else {
            $_SESSION['flash_error'] = $result['message'];
        }

        header('Location: gerenciar.php?treinamento_id=' . $treinamentoId);
        exit;

    case 'atualizar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: gerenciar.php?treinamento_id=' . $treinamentoId);
            exit;
        }

        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Token inválido';
            header('Location: gerenciar.php?treinamento_id=' . $treinamentoId);
            exit;
        }

        $id = $_POST['id'] ?? 0;
        $result = $controller->atualizarAgenda($id, $_POST);

        if ($result['success']) {
            $_SESSION['flash_success'] = $result['message'];
        } else {
            $_SESSION['flash_error'] = $result['message'];
        }

        header('Location: gerenciar.php?treinamento_id=' . $treinamentoId);
        exit;

    case 'deletar':
        $id = $_GET['id'] ?? 0;

        if (!Auth::hasLevel(['admin', 'gestor'])) {
            $_SESSION['flash_error'] = 'Sem permissão';
            header('Location: gerenciar.php?treinamento_id=' . $treinamentoId);
            exit;
        }

        $result = $controller->deletarAgenda($id);

        if ($result['success']) {
            $_SESSION['flash_success'] = $result['message'];
        } else {
            $_SESSION['flash_error'] = $result['message'];
        }

        header('Location: gerenciar.php?treinamento_id=' . $treinamentoId);
        exit;

    default:
        $_SESSION['flash_error'] = 'Ação inválida';
        header('Location: gerenciar.php?treinamento_id=' . $treinamentoId);
        exit;
}
