<?php
/**
 * API: Get Colaboradores de uma Unidade
 * Retorna colaboradores vinculados a uma unidade específica
 */

define('SGC_SYSTEM', true);
require_once __DIR__ . '/../../../app/config/config.php';
require_once __DIR__ . '/../../../app/classes/Database.php';
require_once __DIR__ . '/../../../app/classes/Auth.php';

header('Content-Type: application/json');

// Requer autenticação
if (!Auth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

// Valida parâmetros
if (!isset($_GET['unidade_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID da unidade não fornecido']);
    exit;
}

$unidadeId = filter_var($_GET['unidade_id'], FILTER_VALIDATE_INT);
if (!$unidadeId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID da unidade inválido']);
    exit;
}

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // Parâmetros opcionais
    $setorId = isset($_GET['setor_id']) ? filter_var($_GET['setor_id'], FILTER_VALIDATE_INT) : null;
    $apenasAtivos = isset($_GET['apenas_ativos']) ? (bool)$_GET['apenas_ativos'] : true;

    // Monta query
    $sql = "
        SELECT
            uc.id as vinculo_id,
            uc.colaborador_id,
            c.nome as colaborador_nome,
            c.email as colaborador_email,
            c.telefone as colaborador_telefone,
            uc.unidade_setor_id,
            us.setor,
            uc.cargo_especifico,
            uc.data_vinculacao,
            uc.data_desvinculacao,
            uc.is_vinculo_principal,
            uc.ativo,
            us.responsavel_colaborador_id = uc.colaborador_id as is_responsavel_setor
        FROM unidade_colaboradores uc
        INNER JOIN colaboradores c ON uc.colaborador_id = c.id
        INNER JOIN unidade_setores us ON uc.unidade_setor_id = us.id
        WHERE uc.unidade_id = ?
    ";

    $params = [$unidadeId];

    if ($setorId) {
        $sql .= " AND uc.unidade_setor_id = ?";
        $params[] = $setorId;
    }

    if ($apenasAtivos) {
        $sql .= " AND uc.ativo = 1";
    }

    $sql .= " ORDER BY c.nome ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $colaboradores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formata dados
    foreach ($colaboradores as &$col) {
        $col['data_vinculacao_formatada'] = $col['data_vinculacao'] ?
            date('d/m/Y', strtotime($col['data_vinculacao'])) : null;
        $col['data_desvinculacao_formatada'] = $col['data_desvinculacao'] ?
            date('d/m/Y', strtotime($col['data_desvinculacao'])) : null;
        $col['is_responsavel_setor'] = (bool)$col['is_responsavel_setor'];
        $col['is_vinculo_principal'] = (bool)$col['is_vinculo_principal'];
        $col['ativo'] = (bool)$col['ativo'];
    }

    echo json_encode([
        'success' => true,
        'data' => $colaboradores,
        'total' => count($colaboradores)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar colaboradores: ' . $e->getMessage()
    ]);
}
