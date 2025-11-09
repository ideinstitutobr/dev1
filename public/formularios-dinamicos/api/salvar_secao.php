<?php
/**
 * API: Salvar Seção
 * Cria ou atualiza uma seção do formulário
 */

define('SGC_SYSTEM', true);

$APP_PATH = '../../../app/';
require_once $APP_PATH . 'config/config.php';
require_once $APP_PATH . 'classes/Database.php';
require_once $APP_PATH . 'classes/Auth.php';
require_once $APP_PATH . 'models/FormularioDinamico.php';
require_once $APP_PATH . 'models/FormSecao.php';

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

    $secaoModel = new FormSecao();
    $formularioModel = new FormularioDinamico();
    $userId = Auth::getUserId();
    $isAdmin = Auth::isAdmin();

    // Atualizar seção existente
    if (!empty($dados['id'])) {
        $secaoId = $dados['id'];

        // Buscar seção
        $secao = $secaoModel->buscarPorId($secaoId);
        if (!$secao) {
            throw new Exception('Seção não encontrada');
        }

        // Verificar permissão no formulário
        $formulario = $formularioModel->buscarPorId($secao['formulario_id']);
        if (!$formulario) {
            throw new Exception('Formulário não encontrado');
        }

        if ($formulario['usuario_id'] != $userId && !$isAdmin) {
            throw new Exception('Sem permissão para editar esta seção');
        }

        // Preparar dados para atualização
        $dadosAtualizacao = [];
        $camposPermitidos = ['titulo', 'descricao', 'ordem', 'obrigatoria'];

        foreach ($camposPermitidos as $campo) {
            if (array_key_exists($campo, $dados)) {
                $dadosAtualizacao[$campo] = $dados[$campo];
            }
        }

        // Atualizar
        $resultado = $secaoModel->atualizar($secaoId, $dadosAtualizacao);

        if ($resultado) {
            echo json_encode([
                'success' => true,
                'message' => 'Seção atualizada com sucesso',
                'secao_id' => $secaoId
            ]);
        } else {
            throw new Exception('Erro ao atualizar seção');
        }

    }
    // Criar nova seção
    else {
        if (empty($dados['formulario_id'])) {
            throw new Exception('ID do formulário não informado');
        }

        $formularioId = $dados['formulario_id'];

        // Verificar permissão no formulário
        $formulario = $formularioModel->buscarPorId($formularioId);
        if (!$formulario) {
            throw new Exception('Formulário não encontrado');
        }

        if ($formulario['usuario_id'] != $userId && !$isAdmin) {
            throw new Exception('Sem permissão para adicionar seções a este formulário');
        }

        // Preparar dados
        $dadosCriacao = [
            'formulario_id' => $formularioId,
            'titulo' => $dados['titulo'] ?? 'Nova Seção',
            'descricao' => $dados['descricao'] ?? '',
            'ordem' => $dados['ordem'] ?? null,
            'obrigatoria' => $dados['obrigatoria'] ?? 0
        ];

        // Criar seção
        $novaSecaoId = $secaoModel->criar($dadosCriacao);

        if ($novaSecaoId) {
            echo json_encode([
                'success' => true,
                'message' => 'Seção criada com sucesso',
                'secao_id' => $novaSecaoId
            ]);
        } else {
            throw new Exception('Erro ao criar seção');
        }
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
