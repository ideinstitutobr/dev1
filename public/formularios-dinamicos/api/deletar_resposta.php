<?php
/**
 * API: Deletar Resposta
 * Remove uma resposta específica do sistema
 */

define('SGC_SYSTEM', true);

$APP_PATH = '../../../app/';
require_once $APP_PATH . 'config/config.php';
require_once $APP_PATH . 'classes/Database.php';
require_once $APP_PATH . 'classes/Auth.php';
require_once $APP_PATH . 'models/FormResposta.php';
require_once $APP_PATH . 'models/FormularioDinamico.php';

header('Content-Type: application/json');

try {
    // Verificar autenticação
    if (!Auth::isLogged()) {
        throw new Exception('Usuário não autenticado');
    }

    // Obter dados JSON
    $json = file_get_contents('php://input');
    $dados = json_decode($json, true);

    if (!$dados || empty($dados['resposta_id'])) {
        throw new Exception('ID da resposta não informado');
    }

    $respostaId = (int)$dados['resposta_id'];

    // Buscar resposta
    $respostaModel = new FormResposta();
    $resposta = $respostaModel->buscarPorId($respostaId);

    if (!$resposta) {
        throw new Exception('Resposta não encontrada');
    }

    // Buscar formulário
    $formularioModel = new FormularioDinamico();
    $formulario = $formularioModel->buscarPorId($resposta['formulario_id']);

    if (!$formulario) {
        throw new Exception('Formulário não encontrado');
    }

    // Verificar permissão (proprietário do formulário ou admin)
    $userId = Auth::getUserId();
    $isAdmin = Auth::isAdmin();

    if ($formulario['usuario_id'] != $userId && !$isAdmin) {
        throw new Exception('Sem permissão para deletar esta resposta');
    }

    // Deletar resposta (o model cuida de deletar os detalhes também)
    $resultado = $respostaModel->deletar($respostaId);

    if (!$resultado) {
        throw new Exception('Erro ao deletar resposta');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Resposta deletada com sucesso'
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
