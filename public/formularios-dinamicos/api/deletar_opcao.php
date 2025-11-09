<?php
/**
 * API: Deletar Opção de Resposta
 * Remove uma opção de múltipla escolha
 */

define('SGC_SYSTEM', true);

$APP_PATH = '../../../app/';
require_once $APP_PATH . 'config/config.php';
require_once $APP_PATH . 'classes/Database.php';
require_once $APP_PATH . 'classes/Auth.php';
require_once $APP_PATH . 'models/FormularioDinamico.php';
require_once $APP_PATH . 'models/FormSecao.php';
require_once $APP_PATH . 'models/FormPergunta.php';
require_once $APP_PATH . 'models/FormOpcaoResposta.php';

header('Content-Type: application/json');

try {
    // Verificar autenticação
    if (!Auth::isLogged()) {
        throw new Exception('Usuário não autenticado');
    }

    // Obter dados JSON
    $json = file_get_contents('php://input');
    $dados = json_decode($json, true);

    if (!$dados || empty($dados['id'])) {
        throw new Exception('ID da opção não informado');
    }

    $opcaoModel = new FormOpcaoResposta();
    $perguntaModel = new FormPergunta();
    $secaoModel = new FormSecao();
    $formularioModel = new FormularioDinamico();
    $userId = Auth::getUserId();
    $isAdmin = Auth::isAdmin();

    $opcaoId = $dados['id'];

    // Buscar opção
    $opcao = $opcaoModel->buscarPorId($opcaoId);
    if (!$opcao) {
        throw new Exception('Opção não encontrada');
    }

    // Verificar permissão
    $pergunta = $perguntaModel->buscarPorId($opcao['pergunta_id']);
    if (!$pergunta) {
        throw new Exception('Pergunta não encontrada');
    }

    $secao = $secaoModel->buscarPorId($pergunta['secao_id']);
    if (!$secao) {
        throw new Exception('Seção não encontrada');
    }

    $formulario = $formularioModel->buscarPorId($secao['formulario_id']);
    if (!$formulario) {
        throw new Exception('Formulário não encontrado');
    }

    if ($formulario['usuario_id'] != $userId && !$isAdmin) {
        throw new Exception('Sem permissão para deletar esta opção');
    }

    // Deletar opção
    $resultado = $opcaoModel->deletar($opcaoId);

    if ($resultado) {
        echo json_encode([
            'success' => true,
            'message' => 'Opção deletada com sucesso'
        ]);
    } else {
        throw new Exception('Erro ao deletar opção');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
