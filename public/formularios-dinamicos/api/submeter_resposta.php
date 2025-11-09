<?php
/**
 * API: Submeter Resposta
 * Processa e salva respostas de formulário
 */

define('SGC_SYSTEM', true);

$APP_PATH = '../../../app/';
require_once $APP_PATH . 'config/config.php';
require_once $APP_PATH . 'classes/Database.php';
require_once $APP_PATH . 'classes/Auth.php';
require_once $APP_PATH . 'models/FormularioDinamico.php';
require_once $APP_PATH . 'models/FormPergunta.php';
require_once $APP_PATH . 'models/FormResposta.php';
require_once $APP_PATH . 'models/FormRespostaDetalhe.php';
require_once $APP_PATH . 'models/FormFaixaPontuacao.php';

header('Content-Type: application/json');

try {
    // Obter dados JSON
    $json = file_get_contents('php://input');
    $dados = json_decode($json, true);

    if (!$dados) {
        throw new Exception('Dados inválidos');
    }

    // Validar dados obrigatórios
    if (empty($dados['formulario_id'])) {
        throw new Exception('ID do formulário não informado');
    }

    if (empty($dados['perguntas']) || !is_array($dados['perguntas'])) {
        throw new Exception('Nenhuma resposta fornecida');
    }

    $formularioId = $dados['formulario_id'];
    $respondente = $dados['respondente'] ?? [];
    $perguntas = $dados['perguntas'];

    // Buscar formulário
    $formularioModel = new FormularioDinamico();
    $formulario = $formularioModel->buscarPorId($formularioId);

    if (!$formulario) {
        throw new Exception('Formulário não encontrado');
    }

    // Validar se formulário está ativo
    if ($formulario['status'] !== 'ativo') {
        throw new Exception('Formulário não está ativo');
    }

    // Validar período
    $agora = date('Y-m-d H:i:s');
    if ($formulario['data_inicio'] && $agora < $formulario['data_inicio']) {
        throw new Exception('Formulário ainda não está disponível');
    }

    if ($formulario['data_fim'] && $agora > $formulario['data_fim']) {
        throw new Exception('Formulário já encerrado');
    }

    // Validar múltiplas respostas
    if (!$formulario['permite_multiplas_respostas'] && !empty($respondente['email'])) {
        $respostaModel = new FormResposta();
        if ($respostaModel->jaRespondeu($formularioId, $respondente['email'])) {
            throw new Exception('Você já respondeu este formulário');
        }
    }

    // Validar perguntas obrigatórias
    $perguntaModel = new FormPergunta();
    $todasPerguntas = $perguntaModel->listarPorFormulario($formularioId);

    foreach ($todasPerguntas as $pergunta) {
        if ($pergunta['obrigatoria']) {
            $respondida = false;
            foreach ($perguntas as $resposta) {
                if ($resposta['pergunta_id'] == $pergunta['id']) {
                    $respondida = true;
                    break;
                }
            }

            if (!$respondida) {
                throw new Exception('Pergunta obrigatória não respondida: ' . $pergunta['pergunta']);
            }
        }
    }

    // Iniciar resposta
    $respostaModel = new FormResposta();
    $respostaId = $respostaModel->iniciar([
        'formulario_id' => $formularioId,
        'respondente_nome' => $respondente['nome'] ?? null,
        'respondente_email' => $respondente['email'] ?? null,
        'respondente_ip' => $_SERVER['REMOTE_ADDR'] ?? null
    ]);

    // Salvar detalhes das respostas
    $detalheModel = new FormRespostaDetalhe();

    foreach ($perguntas as $resposta) {
        $perguntaId = $resposta['pergunta_id'];
        $valor = $resposta['valor'];
        $tipo = $resposta['tipo'];

        // Buscar dados da pergunta
        $pergunta = $perguntaModel->buscarPorId($perguntaId);
        if (!$pergunta) {
            continue;
        }

        // Salvar resposta baseada no tipo
        $detalheModel->salvarResposta($respostaId, $perguntaId, $valor, $tipo);
    }

    // Concluir resposta (calcula pontuação)
    $respostaModel->concluir($respostaId);

    // Buscar resposta atualizada
    $respostaCompleta = $respostaModel->buscarPorId($respostaId);

    // Identificar faixa de pontuação
    $faixaModel = new FormFaixaPontuacao();
    $faixa = null;

    if ($formulario['tipo_pontuacao'] !== 'nenhum') {
        if ($formulario['tipo_pontuacao'] === 'percentual') {
            $faixa = $faixaModel->identificarFaixaPorPercentual($formularioId, $respostaCompleta['percentual_acerto']);
        } else {
            $faixa = $faixaModel->identificarFaixa($formularioId, $respostaCompleta['pontuacao_total']);
        }
    }

    // Preparar resultado
    $resultado = [
        'success' => true,
        'message' => 'Resposta enviada com sucesso!',
        'resposta_id' => $respostaId,
        'pontuacao_total' => $respostaCompleta['pontuacao_total'],
        'pontuacao_maxima' => $respostaCompleta['formulario_pontuacao_max'],
        'percentual_acerto' => $respostaCompleta['percentual_acerto'],
        'tempo_resposta' => $respostaCompleta['tempo_resposta'],
        'faixa' => $faixa ? [
            'titulo' => $faixa['titulo'],
            'mensagem' => $faixa['mensagem'],
            'cor' => $faixa['cor'],
            'recomendacoes' => $faixa['recomendacoes']
        ] : null
    ];

    // Criar cookie se não permitir múltiplas respostas
    if (!$formulario['permite_multiplas_respostas'] && empty($respondente['email'])) {
        setcookie('respondente_' . $formularioId, '1', time() + (86400 * 365), '/');
    }

    echo json_encode($resultado, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
