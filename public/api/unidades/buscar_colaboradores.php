<?php
/**
 * API: Buscar Colaboradores Disponíveis
 * Busca colaboradores por nome/email para vincular a uma unidade
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
$termo = isset($_GET['termo']) ? trim($_GET['termo']) : '';
if (strlen($termo) < 2) {
    echo json_encode(['success' => true, 'data' => [], 'message' => 'Digite pelo menos 2 caracteres']);
    exit;
}

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    $unidadeId = isset($_GET['unidade_id']) ? filter_var($_GET['unidade_id'], FILTER_VALIDATE_INT) : null;
    $apenasDisponiveis = isset($_GET['apenas_disponiveis']) ? (bool)$_GET['apenas_disponiveis'] : false;

    // Busca colaboradores
    $sql = "
        SELECT
            c.id,
            c.nome,
            c.email,
            c.telefone,
            c.cargo,
            c.setor_principal,
            c.unidade_principal_id,
            c.ativo
    ";

    // Se especificou unidade, verifica se já está vinculado
    if ($unidadeId) {
        $sql .= ",
            (SELECT COUNT(*)
             FROM unidade_colaboradores uc
             WHERE uc.colaborador_id = c.id
               AND uc.unidade_id = ?
               AND uc.ativo = 1
            ) as ja_vinculado
        ";
    }

    $sql .= "
        FROM colaboradores c
        WHERE c.ativo = 1
          AND (c.nome LIKE ? OR c.email LIKE ?)
    ";

    $params = [];
    if ($unidadeId) {
        $params[] = $unidadeId;
    }

    $termoBusca = '%' . $termo . '%';
    $params[] = $termoBusca;
    $params[] = $termoBusca;

    // Se apenas disponíveis e tem unidade, filtra os já vinculados
    if ($apenasDisponiveis && $unidadeId) {
        $sql .= "
            AND c.id NOT IN (
                SELECT colaborador_id
                FROM unidade_colaboradores
                WHERE unidade_id = ? AND ativo = 1
            )
        ";
        $params[] = $unidadeId;
    }

    $sql .= " ORDER BY c.nome ASC LIMIT 20";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $colaboradores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formata dados
    foreach ($colaboradores as &$col) {
        $col['ativo'] = (bool)$col['ativo'];
        if (isset($col['ja_vinculado'])) {
            $col['ja_vinculado'] = (bool)$col['ja_vinculado'];
        }

        // Adiciona label formatado para uso em select/autocomplete
        $col['label'] = $col['nome'];
        if ($col['email']) {
            $col['label'] .= ' (' . $col['email'] . ')';
        }
        if ($col['cargo']) {
            $col['label'] .= ' - ' . $col['cargo'];
        }
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
