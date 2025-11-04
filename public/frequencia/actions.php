<?php
/**
 * Actions: Frequência
 * Processa ações relacionadas a frequência e sessões
 */

// Define constante do sistema
define('SGC_SYSTEM', true);

// Carrega configurações e classes
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';
require_once __DIR__ . '/../../app/models/Frequencia.php';
require_once __DIR__ . '/../../app/controllers/FrequenciaController.php';

// Verifica autenticação
Auth::requireLogin(BASE_URL);

// Instancia controller
$controller = new FrequenciaController();

// Determina ação
$action = $_REQUEST['action'] ?? '';

switch ($action) {
    /**
     * ==========================================
     * CRIAR SESSÃO
     * ==========================================
     */
    case 'criar_sessao':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: selecionar_treinamento.php');
            exit;
        }

        // Valida CSRF
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Token de segurança inválido';
            header('Location: selecionar_treinamento.php');
            exit;
        }

        $dados = [
            'treinamento_id' => $_POST['treinamento_id'] ?? null,
            'nome' => $_POST['nome'] ?? '',
            'data_sessao' => $_POST['data_sessao'] ?? '',
            'hora_inicio' => $_POST['hora_inicio'] ?? null,
            'hora_fim' => $_POST['hora_fim'] ?? null,
            'local' => $_POST['local'] ?? null,
            'observacoes' => $_POST['observacoes'] ?? null
        ];

        $result = $controller->criarSessao($dados);

        if ($result['success']) {
            $_SESSION['flash_success'] = $result['message'];
            header('Location: sessoes.php?treinamento_id=' . $dados['treinamento_id']);
        } else {
            $_SESSION['flash_error'] = $result['message'] ?? 'Erro ao criar sessão';
            if (!empty($result['errors'])) {
                $_SESSION['flash_error'] .= ': ' . implode(', ', $result['errors']);
            }
            header('Location: criar_sessao.php?treinamento_id=' . $dados['treinamento_id']);
        }
        exit;

    /**
     * ==========================================
     * ATUALIZAR SESSÃO
     * ==========================================
     */
    case 'atualizar_sessao':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: selecionar_treinamento.php');
            exit;
        }

        // Valida CSRF
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Token de segurança inválido';
            header('Location: selecionar_treinamento.php');
            exit;
        }

        $id = $_POST['id'] ?? null;
        $treinamentoId = $_POST['treinamento_id'] ?? null;

        $dados = [
            'nome' => $_POST['nome'] ?? '',
            'data_sessao' => $_POST['data_sessao'] ?? '',
            'hora_inicio' => $_POST['hora_inicio'] ?? null,
            'hora_fim' => $_POST['hora_fim'] ?? null,
            'local' => $_POST['local'] ?? null,
            'observacoes' => $_POST['observacoes'] ?? null
        ];

        $result = $controller->atualizarSessao($id, $dados);

        if ($result['success']) {
            $_SESSION['flash_success'] = $result['message'];
        } else {
            $_SESSION['flash_error'] = $result['message'] ?? 'Erro ao atualizar sessão';
        }

        header('Location: sessoes.php?treinamento_id=' . $treinamentoId);
        exit;

    /**
     * ==========================================
     * DELETAR SESSÃO
     * ==========================================
     */
    case 'deletar_sessao':
        $id = $_GET['id'] ?? null;
        $treinamentoId = $_GET['treinamento_id'] ?? null;

        if (!$id || !$treinamentoId) {
            $_SESSION['flash_error'] = 'Parâmetros inválidos';
            header('Location: selecionar_treinamento.php');
            exit;
        }

        $result = $controller->deletarSessao($id);

        if ($result['success']) {
            $_SESSION['flash_success'] = $result['message'];
        } else {
            $_SESSION['flash_error'] = $result['message'];
        }

        header('Location: sessoes.php?treinamento_id=' . $treinamentoId);
        exit;

    /**
     * ==========================================
     * REGISTRAR FREQUÊNCIA MÚLTIPLA
     * ==========================================
     */
    case 'registrar_frequencia_multipla':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: selecionar_treinamento.php');
            exit;
        }

        // Valida CSRF
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Token de segurança inválido';
            header('Location: selecionar_treinamento.php');
            exit;
        }

        $sessaoId = $_POST['sessao_id'] ?? null;
        $treinamentoId = $_POST['treinamento_id'] ?? null;
        $presencas = $_POST['presencas'] ?? [];
        $usuarioId = Auth::getUserId();

        if (!$sessaoId || empty($presencas)) {
            $_SESSION['flash_error'] = 'Dados incompletos';
            header('Location: sessoes.php?treinamento_id=' . $treinamentoId);
            exit;
        }

        $result = $controller->registrarPresencaMultipla($sessaoId, $presencas, $usuarioId);

        if ($result['success']) {
            $_SESSION['flash_success'] = $result['message'];
        } else {
            $_SESSION['flash_error'] = $result['message'];
        }

        header('Location: registrar_frequencia.php?sessao_id=' . $sessaoId);
        exit;

    /**
     * ==========================================
     * REGISTRAR PRESENÇA INDIVIDUAL
     * ==========================================
     */
    case 'registrar_presenca':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: selecionar_treinamento.php');
            exit;
        }

        $frequenciaId = $_POST['frequencia_id'] ?? null;
        $sessaoId = $_POST['sessao_id'] ?? null;
        $status = $_POST['status'] ?? 'Presente';
        $observacoes = $_POST['observacoes'] ?? null;
        $justificativa = $_POST['justificativa'] ?? null;
        $usuarioId = Auth::getUserId();

        $result = $controller->registrarPresenca($frequenciaId, $status, $usuarioId, $observacoes, $justificativa);

        if ($result['success']) {
            $_SESSION['flash_success'] = $result['message'];
        } else {
            $_SESSION['flash_error'] = $result['message'];
        }

        header('Location: registrar_frequencia.php?sessao_id=' . $sessaoId);
        exit;

    /**
     * ==========================================
     * EXPORTAR CSV
     * ==========================================
     */
    case 'exportar':
        $sessaoId = $_GET['sessao_id'] ?? null;

        if (!$sessaoId) {
            $_SESSION['flash_error'] = 'Sessão não informada';
            header('Location: selecionar_treinamento.php');
            exit;
        }

        // Chama método de exportação (que faz exit)
        $controller->exportarCSV($sessaoId);
        break;

    /**
     * ==========================================
     * CHECK-IN QR CODE
     * ==========================================
     */
    case 'checkin_qr':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: selecionar_treinamento.php');
            exit;
        }

        $qrToken = $_POST['qr_token'] ?? null;
        $colaboradorId = $_POST['colaborador_id'] ?? null;

        $result = $controller->checkinPorQR($qrToken, $colaboradorId);

        // Retorna JSON para AJAX
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;

    /**
     * ==========================================
     * AÇÃO INVÁLIDA
     * ==========================================
     */
    default:
        $_SESSION['flash_error'] = 'Ação inválida';
        header('Location: selecionar_treinamento.php');
        exit;
}
