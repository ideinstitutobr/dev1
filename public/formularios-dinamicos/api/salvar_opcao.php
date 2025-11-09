<?php
/**
 * API: Salvar Opção de Resposta
 * Cria ou atualiza opções para perguntas de múltipla escolha
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

    if (!$dados) {
        throw new Exception('Dados inválidos');
    }

    $opcaoModel = new FormOpcaoResposta();
    $perguntaModel = new FormPergunta();
    $secaoModel = new FormSecao();
    $formularioModel = new FormularioDinamico();
    $userId = Auth::getUserId();
    $isAdmin = Auth::isAdmin();

    // Função para verificar permissão
    $verificarPermissao = function($perguntaId) use ($perguntaModel, $secaoModel, $formularioModel, $userId, $isAdmin) {
        $pergunta = $perguntaModel->buscarPorId($perguntaId);
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
            throw new Exception('Sem permissão para editar esta pergunta');
        }

        return true;
    };

    // Atualizar opção existente
    if (!empty($dados['id'])) {
        $opcaoId = $dados['id'];

        // Buscar opção
        $opcao = $opcaoModel->buscarPorId($opcaoId);
        if (!$opcao) {
            throw new Exception('Opção não encontrada');
        }

        // Verificar permissão
        $verificarPermissao($opcao['pergunta_id']);

        // Preparar dados para atualização
        $dadosAtualizacao = [];
        $camposPermitidos = ['texto_opcao', 'pontuacao', 'ordem', 'ir_para_secao'];

        foreach ($camposPermitidos as $campo) {
            if (array_key_exists($campo, $dados)) {
                $dadosAtualizacao[$campo] = $dados[$campo];
            }
        }

        // Atualizar
        $resultado = $opcaoModel->atualizar($opcaoId, $dadosAtualizacao);

        if ($resultado) {
            echo json_encode([
                'success' => true,
                'message' => 'Opção atualizada com sucesso',
                'opcao_id' => $opcaoId
            ]);
        } else {
            throw new Exception('Erro ao atualizar opção');
        }

    }
    // Criar nova opção
    else {
        if (empty($dados['pergunta_id'])) {
            throw new Exception('ID da pergunta não informado');
        }

        $perguntaId = $dados['pergunta_id'];

        // Verificar permissão
        $verificarPermissao($perguntaId);

        // Preparar dados
        $dadosCriacao = [
            'pergunta_id' => $perguntaId,
            'texto_opcao' => $dados['texto_opcao'] ?? 'Nova opção',
            'pontuacao' => $dados['pontuacao'] ?? 0,
            'ordem' => $dados['ordem'] ?? null,
            'ir_para_secao' => $dados['ir_para_secao'] ?? null
        ];

        // Criar opção
        $novaOpcaoId = $opcaoModel->criar($dadosCriacao);

        if ($novaOpcaoId) {
            echo json_encode([
                'success' => true,
                'message' => 'Opção criada com sucesso',
                'opcao_id' => $novaOpcaoId
            ]);
        } else {
            throw new Exception('Erro ao criar opção');
        }
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
