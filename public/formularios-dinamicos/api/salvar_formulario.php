<?php
/**
 * API: Salvar Formulário
 * Atualiza dados de um formulário existente
 */

define('SGC_SYSTEM', true);

$APP_PATH = '../../../app/';
require_once $APP_PATH . 'config/config.php';
require_once $APP_PATH . 'classes/Database.php';
require_once $APP_PATH . 'classes/Auth.php';
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

    if (!$dados) {
        throw new Exception('Dados inválidos');
    }

    // Validar ID
    if (empty($dados['id'])) {
        throw new Exception('ID do formulário não informado');
    }

    $formularioId = $dados['id'];
    $model = new FormularioDinamico();

    // Buscar formulário
    $formulario = $model->buscarPorId($formularioId);
    if (!$formulario) {
        throw new Exception('Formulário não encontrado');
    }

    // Verificar permissão (proprietário ou admin)
    $userId = Auth::getUserId();
    $isAdmin = Auth::isAdmin();

    if ($formulario['usuario_id'] != $userId && !$isAdmin) {
        throw new Exception('Sem permissão para editar este formulário');
    }

    // Preparar dados para atualização
    $dadosAtualizacao = [];

    // Campos permitidos
    $camposPermitidos = [
        'titulo',
        'descricao',
        'slug',
        'status',
        'permite_multiplas_respostas',
        'requer_autenticacao',
        'mostrar_pontuacao',
        'tipo_pontuacao',
        'data_inicio',
        'data_fim',
        'mensagem_boas_vindas',
        'mensagem_conclusao',
        'config_adicional'
    ];

    foreach ($camposPermitidos as $campo) {
        if (array_key_exists($campo, $dados)) {
            // Validações específicas
            if ($campo === 'status') {
                $statusPermitidos = ['rascunho', 'ativo', 'inativo', 'arquivado'];
                if (!in_array($dados[$campo], $statusPermitidos)) {
                    throw new Exception('Status inválido');
                }
            }

            if ($campo === 'tipo_pontuacao') {
                $tiposPermitidos = ['nenhum', 'soma', 'media', 'percentual'];
                if (!in_array($dados[$campo], $tiposPermitidos)) {
                    throw new Exception('Tipo de pontuação inválido');
                }
            }

            if ($campo === 'config_adicional' && is_array($dados[$campo])) {
                $dadosAtualizacao[$campo] = json_encode($dados[$campo]);
            } else {
                $dadosAtualizacao[$campo] = $dados[$campo];
            }
        }
    }

    // Atualizar formulário
    $resultado = $model->atualizar($formularioId, $dadosAtualizacao);

    if ($resultado) {
        echo json_encode([
            'success' => true,
            'message' => 'Formulário atualizado com sucesso'
        ]);
    } else {
        throw new Exception('Erro ao atualizar formulário');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
