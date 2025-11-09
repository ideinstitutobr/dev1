<?php
/**
 * API: Reordenar
 * Atualiza ordem de perguntas ou seções após drag-and-drop
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
    if (empty($dados['tipo']) || empty($dados['ordens'])) {
        throw new Exception('Tipo e ordens são obrigatórios');
    }

    if (!is_array($dados['ordens'])) {
        throw new Exception('Ordens deve ser um array');
    }

    $tipo = $dados['tipo'];
    $ordens = $dados['ordens'];

    $formularioModel = new FormularioDinamico();
    $secaoModel = new FormSecao();
    $perguntaModel = new FormPergunta();
    $userId = Auth::getUserId();
    $isAdmin = Auth::isAdmin();

    // Reordenar perguntas
    if ($tipo === 'perguntas') {
        // Validar estrutura
        foreach ($ordens as $item) {
            if (!isset($item['id']) || !isset($item['ordem'])) {
                throw new Exception('Cada item deve ter id e ordem');
            }
        }

        // Verificar permissão na primeira pergunta (assumindo que todas pertencem à mesma seção/formulário)
        if (!empty($ordens)) {
            $primeiraPergunta = $perguntaModel->buscarPorId($ordens[0]['id']);
            if (!$primeiraPergunta) {
                throw new Exception('Pergunta não encontrada');
            }

            $secao = $secaoModel->buscarPorId($primeiraPergunta['secao_id']);
            if (!$secao) {
                throw new Exception('Seção não encontrada');
            }

            $formulario = $formularioModel->buscarPorId($secao['formulario_id']);
            if (!$formulario) {
                throw new Exception('Formulário não encontrado');
            }

            if ($formulario['usuario_id'] != $userId && !$isAdmin) {
                throw new Exception('Sem permissão para reordenar perguntas');
            }
        }

        // Atualizar ordem de cada pergunta
        $atualizadas = 0;
        foreach ($ordens as $item) {
            $resultado = $perguntaModel->atualizar($item['id'], ['ordem' => $item['ordem']]);
            if ($resultado) {
                $atualizadas++;
            }
        }

        echo json_encode([
            'success' => true,
            'message' => 'Perguntas reordenadas com sucesso',
            'total_atualizadas' => $atualizadas
        ]);
    }
    // Reordenar seções
    elseif ($tipo === 'secoes') {
        // Validar estrutura
        foreach ($ordens as $item) {
            if (!isset($item['id']) || !isset($item['ordem'])) {
                throw new Exception('Cada item deve ter id e ordem');
            }
        }

        // Verificar permissão na primeira seção
        if (!empty($ordens)) {
            $primeiraSecao = $secaoModel->buscarPorId($ordens[0]['id']);
            if (!$primeiraSecao) {
                throw new Exception('Seção não encontrada');
            }

            $formulario = $formularioModel->buscarPorId($primeiraSecao['formulario_id']);
            if (!$formulario) {
                throw new Exception('Formulário não encontrado');
            }

            if ($formulario['usuario_id'] != $userId && !$isAdmin) {
                throw new Exception('Sem permissão para reordenar seções');
            }
        }

        // Atualizar ordem de cada seção
        $atualizadas = 0;
        foreach ($ordens as $item) {
            $resultado = $secaoModel->atualizar($item['id'], ['ordem' => $item['ordem']]);
            if ($resultado) {
                $atualizadas++;
            }
        }

        echo json_encode([
            'success' => true,
            'message' => 'Seções reordenadas com sucesso',
            'total_atualizadas' => $atualizadas
        ]);
    }
    else {
        throw new Exception('Tipo inválido. Use "perguntas" ou "secoes"');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
