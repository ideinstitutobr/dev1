<?php
/**
 * API: Deletar
 * Deleta seção ou pergunta do formulário
 */

define('SGC_SYSTEM', true);

$APP_PATH = '../../../app/';
require_once $APP_PATH . 'config/config.php';
require_once $APP_PATH . 'classes/Database.php';
require_once $APP_PATH . 'classes/Auth.php';
require_once $APP_PATH . 'models/FormularioDinamico.php';
require_once $APP_PATH . 'models/FormSecao.php';
require_once $APP_PATH . 'models/FormPergunta.php';

header('Content-Type: application/json');

try {
    // Verificar autenticação
    if (!Auth::isLogged()) {
        throw new Exception('Usuário não autenticado');
    }

    // Obter dados JSON
    $json = file_get_contents('php://input');
    $dados = json_decode($json, true);

    if (!$dados) {
        throw new Exception('Dados inválidos');
    }

    // Validar parâmetros obrigatórios
    if (empty($dados['tipo']) || empty($dados['id'])) {
        throw new Exception('Tipo e ID são obrigatórios');
    }

    $tipo = $dados['tipo'];
    $id = $dados['id'];

    $formularioModel = new FormularioDinamico();
    $secaoModel = new FormSecao();
    $perguntaModel = new FormPergunta();
    $userId = Auth::getUserId();
    $isAdmin = Auth::isAdmin();

    // Deletar seção
    if ($tipo === 'secao') {
        // Buscar seção
        $secao = $secaoModel->buscarPorId($id);
        if (!$secao) {
            throw new Exception('Seção não encontrada');
        }

        // Verificar permissão no formulário
        $formulario = $formularioModel->buscarPorId($secao['formulario_id']);
        if (!$formulario) {
            throw new Exception('Formulário não encontrado');
        }

        if ($formulario['usuario_id'] != $userId && !$isAdmin) {
            throw new Exception('Sem permissão para deletar esta seção');
        }

        // Deletar seção (cascade irá deletar perguntas relacionadas)
        $resultado = $secaoModel->deletar($id);

        if ($resultado) {
            echo json_encode([
                'success' => true,
                'message' => 'Seção deletada com sucesso'
            ]);
        } else {
            throw new Exception('Erro ao deletar seção');
        }
    }
    // Deletar pergunta
    elseif ($tipo === 'pergunta') {
        // Buscar pergunta
        $pergunta = $perguntaModel->buscarPorId($id);
        if (!$pergunta) {
            throw new Exception('Pergunta não encontrada');
        }

        // Buscar seção para verificar permissão
        $secao = $secaoModel->buscarPorId($pergunta['secao_id']);
        if (!$secao) {
            throw new Exception('Seção não encontrada');
        }

        // Verificar permissão no formulário
        $formulario = $formularioModel->buscarPorId($secao['formulario_id']);
        if (!$formulario) {
            throw new Exception('Formulário não encontrado');
        }

        if ($formulario['usuario_id'] != $userId && !$isAdmin) {
            throw new Exception('Sem permissão para deletar esta pergunta');
        }

        // Deletar pergunta (cascade irá deletar opções relacionadas)
        $resultado = $perguntaModel->deletar($id);

        if ($resultado) {
            echo json_encode([
                'success' => true,
                'message' => 'Pergunta deletada com sucesso'
            ]);
        } else {
            throw new Exception('Erro ao deletar pergunta');
        }
    }
    else {
        throw new Exception('Tipo inválido. Use "secao" ou "pergunta"');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
