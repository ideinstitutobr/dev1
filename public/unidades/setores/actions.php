<?php
/**
 * Actions: Gerenciamento de Setores
 * Processa ações de adicionar, inativar, ativar e definir responsável
 */

define('SGC_SYSTEM', true);
require_once __DIR__ . '/../../../app/config/config.php';
require_once __DIR__ . '/../../../app/classes/Database.php';
require_once __DIR__ . '/../../../app/classes/Auth.php';
require_once __DIR__ . '/../../../app/models/Unidade.php';
require_once __DIR__ . '/../../../app/models/UnidadeSetor.php';
require_once __DIR__ . '/../../../app/controllers/UnidadeSetorController.php';

Auth::requireLogin();
Auth::requireAdmin();

// Valida CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = 'Token de segurança inválido';
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../listar.php'));
        exit;
    }
}

$controller = new UnidadeSetorController();

// Verifica ID da unidade
$unidadeId = null;
if (isset($_POST['unidade_id'])) {
    $unidadeId = filter_var($_POST['unidade_id'], FILTER_VALIDATE_INT);
} elseif (isset($_GET['unidade_id'])) {
    $unidadeId = filter_var($_GET['unidade_id'], FILTER_VALIDATE_INT);
}

if (!$unidadeId) {
    $_SESSION['error_message'] = 'ID da unidade não fornecido ou inválido';
    header('Location: ../listar.php');
    exit;
}

// Verifica se a unidade existe
$unidadeModel = new Unidade();
$unidade = $unidadeModel->buscarPorId($unidadeId);
if (!$unidade) {
    $_SESSION['error_message'] = 'Unidade não encontrada';
    header('Location: ../listar.php');
    exit;
}

$redirectUrl = "gerenciar.php?unidade_id={$unidadeId}";

// ========================================
// AÇÃO: ADICIONAR SETOR
// ========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['acao'] === 'adicionar') {
    $setor = trim($_POST['setor'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');

    if (empty($setor)) {
        $_SESSION['error_message'] = 'Nome do setor é obrigatório';
        header("Location: {$redirectUrl}");
        exit;
    }

    // Valida se o setor existe nas categorias
    $setorModel = new UnidadeSetor();
    $setoresDisponiveis = $setorModel->getSetoresDisponiveis();

    if (!in_array($setor, $setoresDisponiveis)) {
        $_SESSION['error_message'] = 'Setor inválido ou não disponível';
        header("Location: {$redirectUrl}");
        exit;
    }

    // Valida se já não existe esse setor na unidade (mesmo inativo)
    if ($setorModel->setorExisteNaUnidade($unidadeId, $setor)) {
        // Busca o setor existente para verificar status
        $setoresExistentes = $setorModel->buscarPorUnidade($unidadeId, false); // Busca todos, inclusive inativos
        $setorExistente = null;
        foreach ($setoresExistentes as $s) {
            if ($s['setor'] === $setor) {
                $setorExistente = $s;
                break;
            }
        }

        // Se existe mas está inativo, reativa
        if ($setorExistente && !$setorExistente['ativo']) {
            $resultado = $setorModel->ativar($setorExistente['id']);
            if ($resultado['success']) {
                $_SESSION['success_message'] = "Setor '{$setor}' foi reativado com sucesso!";
            } else {
                $_SESSION['error_message'] = $resultado['message'];
            }
        } else {
            $_SESSION['error_message'] = "O setor '{$setor}' já está ativo nesta unidade";
        }

        header("Location: {$redirectUrl}");
        exit;
    }

    // Cria o novo setor
    $dados = [
        'unidade_id' => $unidadeId,
        'setor' => $setor,
        'descricao' => $descricao
    ];

    $resultado = $setorModel->criar($dados);

    if ($resultado['success']) {
        $_SESSION['success_message'] = "Setor '{$setor}' adicionado com sucesso!";
    } else {
        $_SESSION['error_message'] = $resultado['message'];
    }

    header("Location: {$redirectUrl}");
    exit;
}

// ========================================
// AÇÃO: DEFINIR RESPONSÁVEL
// ========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['acao'] === 'definir_responsavel') {
    $setorId = filter_var($_POST['setor_id'] ?? 0, FILTER_VALIDATE_INT);
    $colaboradorId = filter_var($_POST['colaborador_id'] ?? 0, FILTER_VALIDATE_INT);

    if (!$setorId) {
        $_SESSION['error_message'] = 'ID do setor não fornecido ou inválido';
        header("Location: {$redirectUrl}");
        exit;
    }

    // Permite colaboradorId = 0 para remover responsável
    if ($colaboradorId < 0) {
        $_SESSION['error_message'] = 'ID do colaborador inválido';
        header("Location: {$redirectUrl}");
        exit;
    }

    $setorModel = new UnidadeSetor();

    // Se colaboradorId for 0, remove o responsável
    if ($colaboradorId === 0) {
        $resultado = $setorModel->removerResponsavel($setorId);
    } else {
        $resultado = $setorModel->definirResponsavel($setorId, $colaboradorId);
    }

    if ($resultado['success']) {
        $_SESSION['success_message'] = $resultado['message'];
    } else {
        $_SESSION['error_message'] = $resultado['message'];
    }

    header("Location: {$redirectUrl}");
    exit;
}

// ========================================
// AÇÃO: INATIVAR SETOR
// ========================================
if (isset($_GET['acao']) && $_GET['acao'] === 'inativar') {
    $setorId = filter_var($_GET['setor_id'] ?? 0, FILTER_VALIDATE_INT);

    if (!$setorId) {
        $_SESSION['error_message'] = 'ID do setor não fornecido ou inválido';
        header("Location: {$redirectUrl}");
        exit;
    }

    $setorModel = new UnidadeSetor();

    // Verifica se há colaboradores vinculados ao setor
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM unidade_colaboradores
        WHERE unidade_setor_id = ? AND ativo = 1
    ");
    $stmt->execute([$setorId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['total'] > 0) {
        $_SESSION['error_message'] = "Não é possível inativar este setor pois há {$result['total']} colaborador(es) vinculado(s) a ele. Remova ou transfira os colaboradores primeiro.";
        header("Location: {$redirectUrl}");
        exit;
    }

    $resultado = $setorModel->inativar($setorId);

    if ($resultado['success']) {
        $_SESSION['success_message'] = 'Setor inativado com sucesso!';
    } else {
        $_SESSION['error_message'] = $resultado['message'];
    }

    header("Location: {$redirectUrl}");
    exit;
}

// ========================================
// AÇÃO: ATIVAR SETOR
// ========================================
if (isset($_GET['acao']) && $_GET['acao'] === 'ativar') {
    $setorId = filter_var($_GET['setor_id'] ?? 0, FILTER_VALIDATE_INT);

    if (!$setorId) {
        $_SESSION['error_message'] = 'ID do setor não fornecido ou inválido';
        header("Location: {$redirectUrl}");
        exit;
    }

    $setorModel = new UnidadeSetor();
    $resultado = $setorModel->ativar($setorId);

    if ($resultado['success']) {
        $_SESSION['success_message'] = 'Setor ativado com sucesso!';
    } else {
        $_SESSION['error_message'] = $resultado['message'];
    }

    header("Location: {$redirectUrl}");
    exit;
}

// Se chegou aqui, ação inválida
$_SESSION['error_message'] = 'Ação inválida';
header("Location: {$redirectUrl}");
exit;
