<?php
/**
 * API: Salvar Pergunta
 * Cria ou atualiza uma pergunta do formulário
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

    $perguntaModel = new FormPergunta();
    $secaoModel = new FormSecao();
    $formularioModel = new FormularioDinamico();
    $userId = Auth::getUserId();
    $isAdmin = Auth::isAdmin();

    // Função auxiliar para verificar permissão
    $verificarPermissao = function($secaoId) use ($secaoModel, $formularioModel, $userId, $isAdmin) {
        $secao = $secaoModel->buscarPorId($secaoId);
        if (!$secao) {
            throw new Exception('Seção não encontrada');
        }

        $formulario = $formularioModel->buscarPorId($secao['formulario_id']);
        if (!$formulario) {
            throw new Exception('Formulário não encontrado');
        }

        if ($formulario['usuario_id'] != $userId && !$isAdmin) {
            throw new Exception('Sem permissão para editar este formulário');
        }

        return true;
    };

    // Atualizar pergunta existente
    if (!empty($dados['id'])) {
        $perguntaId = $dados['id'];

        // Buscar pergunta
        $pergunta = $perguntaModel->buscarPorId($perguntaId);
        if (!$pergunta) {
            throw new Exception('Pergunta não encontrada');
        }

        // Verificar permissão
        $verificarPermissao($pergunta['secao_id']);

        // Preparar dados para atualização
        $dadosAtualizacao = [];
        $camposPermitidos = [
            'pergunta',
            'tipo_pergunta',
            'descricao',
            'ordem',
            'obrigatoria',
            'tem_pontuacao',
            'pontuacao_maxima',
            'config_adicional'
        ];

        foreach ($camposPermitidos as $campo) {
            if (array_key_exists($campo, $dados)) {
                // Validação de tipo de pergunta
                if ($campo === 'tipo_pergunta') {
                    $tiposPermitidos = [
                        'texto_curto', 'texto_longo', 'multipla_escolha',
                        'caixas_selecao', 'lista_suspensa', 'escala_linear',
                        'grade_multipla', 'data', 'hora', 'arquivo'
                    ];
                    if (!in_array($dados[$campo], $tiposPermitidos)) {
                        throw new Exception('Tipo de pergunta inválido');
                    }
                }

                // Serializar config_adicional se for array
                if ($campo === 'config_adicional' && is_array($dados[$campo])) {
                    $dadosAtualizacao[$campo] = json_encode($dados[$campo]);
                } else {
                    $dadosAtualizacao[$campo] = $dados[$campo];
                }
            }
        }

        // Atualizar
        $resultado = $perguntaModel->atualizar($perguntaId, $dadosAtualizacao);

        if ($resultado) {
            echo json_encode([
                'success' => true,
                'message' => 'Pergunta atualizada com sucesso',
                'pergunta_id' => $perguntaId
            ]);
        } else {
            throw new Exception('Erro ao atualizar pergunta');
        }

    }
    // Criar nova pergunta
    else {
        if (empty($dados['secao_id'])) {
            throw new Exception('ID da seção não informado');
        }

        if (empty($dados['tipo_pergunta'])) {
            throw new Exception('Tipo de pergunta não informado');
        }

        $secaoId = $dados['secao_id'];

        // Verificar permissão
        $verificarPermissao($secaoId);

        // Validar tipo de pergunta
        $tiposPermitidos = [
            'texto_curto', 'texto_longo', 'multipla_escolha',
            'caixas_selecao', 'lista_suspensa', 'escala_linear',
            'grade_multipla', 'data', 'hora', 'arquivo'
        ];
        if (!in_array($dados['tipo_pergunta'], $tiposPermitidos)) {
            throw new Exception('Tipo de pergunta inválido');
        }

        // Preparar dados
        $dadosCriacao = [
            'secao_id' => $secaoId,
            'tipo_pergunta' => $dados['tipo_pergunta'],
            'pergunta' => $dados['pergunta'] ?? 'Nova pergunta',
            'descricao' => $dados['descricao'] ?? '',
            'ordem' => $dados['ordem'] ?? null,
            'obrigatoria' => $dados['obrigatoria'] ?? 0,
            'tem_pontuacao' => $dados['tem_pontuacao'] ?? 0,
            'pontuacao_maxima' => $dados['pontuacao_maxima'] ?? 0
        ];

        if (isset($dados['config_adicional'])) {
            $dadosCriacao['config_adicional'] = is_array($dados['config_adicional'])
                ? json_encode($dados['config_adicional'])
                : $dados['config_adicional'];
        }

        // Criar pergunta
        $novaPerguntaId = $perguntaModel->criar($dadosCriacao);

        if ($novaPerguntaId) {
            echo json_encode([
                'success' => true,
                'message' => 'Pergunta criada com sucesso',
                'pergunta_id' => $novaPerguntaId
            ]);
        } else {
            throw new Exception('Erro ao criar pergunta');
        }
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
