<?php
/**
 * Endpoint AJAX: Finalizar Checklist
 */

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';

// Verificar login
if (!Auth::isLogged()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

require_once APP_PATH . 'models/Checklist.php';
require_once APP_PATH . 'models/Pergunta.php';
require_once APP_PATH . 'models/RespostaChecklist.php';

header('Content-Type: application/json');

try {
    // Receber dados JSON
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('Dados inválidos');
    }

    $checklistId = $input['checklist_id'] ?? null;

    if (!$checklistId) {
        throw new Exception('ID do checklist não informado');
    }

    // Buscar checklist
    $checklistModel = new Checklist();
    $checklist = $checklistModel->buscarPorId($checklistId);

    if (!$checklist) {
        throw new Exception('Checklist não encontrado');
    }

    if ($checklist['status'] !== 'rascunho') {
        throw new Exception('Checklist já foi finalizado');
    }

    // Verificar se todas as perguntas foram respondidas
    require_once APP_PATH . 'models/ModuloAvaliacao.php';

    $perguntaModel = new Pergunta();
    $moduloModel = new ModuloAvaliacao();

    // Extrair tipo do checklist para validação correta
    $tipo = $checklist['tipo'] ?? 'quinzenal_mensal';

    // Buscar APENAS módulos ativos do tipo específico e suas perguntas
    $modulos = $moduloModel->listarAtivos($tipo);
    $totalPerguntas = 0;
    foreach ($modulos as $modulo) {
        $perguntasModulo = $perguntaModel->listarPorModulo($modulo['id'], true, $tipo);
        $totalPerguntas += count($perguntasModulo);
    }

    $respostaModel = new RespostaChecklist();
    $respostas = $respostaModel->obterRespostasCompletas($checklistId);
    $totalRespostas = count($respostas);

    if ($totalRespostas < $totalPerguntas) {
        throw new Exception("Apenas {$totalRespostas} de {$totalPerguntas} perguntas foram respondidas");
    }

    // Finalizar checklist
    $resultado = $checklistModel->finalizar($checklistId);

    if (!$resultado) {
        throw new Exception('Erro ao finalizar checklist');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Checklist finalizado com sucesso'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
