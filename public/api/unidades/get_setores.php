<?php
/**
 * API: Obter setores de uma unidade
 * Retorna JSON com setores ativos de uma unidade específica
 */

define('SGC_SYSTEM', true);

require_once __DIR__ . '/../../../app/config/config.php';
require_once __DIR__ . '/../../../app/classes/Database.php';
require_once __DIR__ . '/../../../app/models/UnidadeSetor.php';
require_once __DIR__ . '/../../../app/controllers/UnidadeSetorController.php';

header('Content-Type: application/json');

// Verifica se unidade_id foi fornecido
$unidadeId = $_GET['unidade_id'] ?? null;

if (!$unidadeId) {
    echo json_encode([
        'success' => false,
        'message' => 'unidade_id é obrigatório',
        'data' => []
    ]);
    exit;
}

try {
    $controller = new UnidadeSetorController();
    $setores = $controller->getSetoresPorUnidade($unidadeId, true);

    echo json_encode([
        'success' => true,
        'data' => $setores
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar setores: ' . $e->getMessage(),
        'data' => []
    ]);
}
