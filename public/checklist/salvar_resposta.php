<?php
/**
 * Endpoint AJAX: Salvar Resposta de Checklist
 */

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';

// Verificar login
if (!Auth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

require_once APP_PATH . 'models/Checklist.php';
require_once APP_PATH . 'models/RespostaChecklist.php';

header('Content-Type: application/json');

try {
    // Receber dados JSON
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('Dados inválidos');
    }

    $checklistId = $input['checklist_id'] ?? null;
    $perguntaId = $input['pergunta_id'] ?? null;
    $estrelas = $input['estrelas'] ?? null;
    $observacao = $input['observacao'] ?? '';

    // Validar dados
    if (!$checklistId || !$perguntaId || !$estrelas) {
        throw new Exception('Dados obrigatórios faltando');
    }

    if ($estrelas < 1 || $estrelas > 5) {
        throw new Exception('Estrelas devem ser entre 1 e 5');
    }

    // Verificar se checklist existe e está em rascunho
    $checklistModel = new Checklist();
    $checklist = $checklistModel->buscarPorId($checklistId);

    if (!$checklist) {
        throw new Exception('Checklist não encontrado');
    }

    if ($checklist['status'] !== 'rascunho') {
        throw new Exception('Checklist já foi finalizado');
    }

    // Salvar resposta
    $respostaModel = new RespostaChecklist();
    $respostaModel->salvarResposta([
        'checklist_id' => $checklistId,
        'pergunta_id' => $perguntaId,
        'estrelas' => $estrelas,
        'observacao' => $observacao
    ]);

    // Recalcular pontuação do checklist
    $pontuacao = $checklistModel->calcularPontuacao($checklistId);

    echo json_encode([
        'success' => true,
        'message' => 'Resposta salva com sucesso',
        'pontuacao_total' => $pontuacao['total'],
        'pontuacao_maxima' => $pontuacao['maximo'],
        'percentual' => $pontuacao['percentual'],
        'atingiu_meta' => $pontuacao['atingiu_meta']
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
