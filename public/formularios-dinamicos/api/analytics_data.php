<?php
/**
 * API: Dados de Analytics
 * Retorna dados processados para gráficos e análises
 */

define('SGC_SYSTEM', true);

$APP_PATH = '../../../app/';
require_once $APP_PATH . 'config/config.php';
require_once $APP_PATH . 'classes/Database.php';
require_once $APP_PATH . 'classes/Auth.php';
require_once $APP_PATH . 'models/FormularioDinamico.php';
require_once $APP_PATH . 'models/FormResposta.php';
require_once $APP_PATH . 'models/FormRespostaDetalhe.php';
require_once $APP_PATH . 'models/FormPergunta.php';
require_once $APP_PATH . 'models/FormOpcaoResposta.php';

header('Content-Type: application/json');

try {
    // Verificar autenticação
    if (!Auth::isLogged()) {
        throw new Exception('Usuário não autenticado');
    }

    // Validar parâmetros
    if (empty($_GET['formulario_id'])) {
        throw new Exception('ID do formulário não informado');
    }

    $formularioId = (int)$_GET['formulario_id'];
    $tipo = $_GET['tipo'] ?? 'all';
    $periodo = isset($_GET['periodo']) ? (int)$_GET['periodo'] : 30;

    // Buscar formulário
    $formularioModel = new FormularioDinamico();
    $formulario = $formularioModel->buscarPorId($formularioId);

    if (!$formulario) {
        throw new Exception('Formulário não encontrado');
    }

    // Verificar permissão
    $userId = Auth::getUserId();
    $isAdmin = Auth::isAdmin();

    if ($formulario['usuario_id'] != $userId && !$isAdmin) {
        throw new Exception('Sem permissão para acessar analytics deste formulário');
    }

    $respostaModel = new FormResposta();
    $respostas = $respostaModel->listarPorFormulario($formularioId);

    $resultado = [];

    // Timeline de respostas
    if ($tipo === 'timeline' || $tipo === 'all') {
        $timeline = [];
        $dataInicio = date('Y-m-d', strtotime("-{$periodo} days"));

        foreach ($respostas as $resposta) {
            $data = date('Y-m-d', strtotime($resposta['iniciado_em']));

            if ($data >= $dataInicio) {
                if (!isset($timeline[$data])) {
                    $timeline[$data] = [
                        'total' => 0,
                        'concluidas' => 0
                    ];
                }

                $timeline[$data]['total']++;
                if ($resposta['status_resposta'] === 'concluida') {
                    $timeline[$data]['concluidas']++;
                }
            }
        }

        // Preencher dias sem respostas
        for ($i = $periodo; $i >= 0; $i--) {
            $data = date('Y-m-d', strtotime("-{$i} days"));
            if (!isset($timeline[$data])) {
                $timeline[$data] = ['total' => 0, 'concluidas' => 0];
            }
        }

        ksort($timeline);
        $resultado['timeline'] = $timeline;
    }

    // Distribuição de pontuação
    if ($tipo === 'score_distribution' || $tipo === 'all') {
        $distribuicao = [
            '0-20' => 0,
            '21-40' => 0,
            '41-60' => 0,
            '61-80' => 0,
            '81-100' => 0
        ];

        foreach ($respostas as $resposta) {
            if ($resposta['status_resposta'] === 'concluida' && $resposta['percentual_acerto'] !== null) {
                $percentual = $resposta['percentual_acerto'];

                if ($percentual <= 20) {
                    $distribuicao['0-20']++;
                } elseif ($percentual <= 40) {
                    $distribuicao['21-40']++;
                } elseif ($percentual <= 60) {
                    $distribuicao['41-60']++;
                } elseif ($percentual <= 80) {
                    $distribuicao['61-80']++;
                } else {
                    $distribuicao['81-100']++;
                }
            }
        }

        $resultado['score_distribution'] = $distribuicao;
    }

    // Atividade por horário
    if ($tipo === 'activity' || $tipo === 'all') {
        $atividade = array_fill(0, 24, 0);

        foreach ($respostas as $resposta) {
            $hora = (int)date('H', strtotime($resposta['iniciado_em']));
            $atividade[$hora]++;
        }

        $resultado['activity'] = $atividade;
    }

    // Análise por pergunta
    if ($tipo === 'questions' || $tipo === 'all') {
        $perguntaModel = new FormPergunta();
        $perguntas = $perguntaModel->listarPorFormulario($formularioId);

        $detalheModel = new FormRespostaDetalhe();
        $opcaoModel = new FormOpcaoResposta();

        $analisePerguntas = [];

        foreach ($perguntas as $pergunta) {
            $analise = [
                'id' => $pergunta['id'],
                'pergunta' => $pergunta['pergunta'],
                'tipo' => $pergunta['tipo_pergunta'],
                'total_respostas' => 0,
                'respostas_corretas' => 0,
                'taxa_acerto' => 0,
                'pontuacao_media' => 0,
                'tempo_medio' => 0,
                'distribuicao_opcoes' => []
            ];

            // Buscar todos os detalhes desta pergunta
            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT frd.*, fr.status_resposta
                FROM form_respostas_detalhes frd
                INNER JOIN form_respostas fr ON fr.id = frd.resposta_id
                WHERE frd.pergunta_id = ? AND fr.formulario_id = ?
            ");
            $stmt->execute([$pergunta['id'], $formularioId]);
            $detalhes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $somaPontuacao = 0;
            $contagemPontuacao = 0;

            foreach ($detalhes as $detalhe) {
                $analise['total_respostas']++;

                // Pontuação
                if ($detalhe['pontuacao_obtida'] !== null) {
                    $somaPontuacao += $detalhe['pontuacao_obtida'];
                    $contagemPontuacao++;

                    if ($detalhe['pontuacao_obtida'] >= $pergunta['pontos']) {
                        $analise['respostas_corretas']++;
                    }
                }

                // Distribuição de opções
                if ($pergunta['tipo_pergunta'] === 'multipla_escolha' ||
                    $pergunta['tipo_pergunta'] === 'lista_suspensa') {

                    if ($detalhe['opcao_selecionada']) {
                        if (!isset($analise['distribuicao_opcoes'][$detalhe['opcao_selecionada']])) {
                            $analise['distribuicao_opcoes'][$detalhe['opcao_selecionada']] = 0;
                        }
                        $analise['distribuicao_opcoes'][$detalhe['opcao_selecionada']]++;
                    }
                } elseif ($pergunta['tipo_pergunta'] === 'caixas_selecao') {
                    if ($detalhe['opcoes_selecionadas']) {
                        $opcoes = json_decode($detalhe['opcoes_selecionadas'], true);
                        foreach ($opcoes as $opcao) {
                            if (!isset($analise['distribuicao_opcoes'][$opcao])) {
                                $analise['distribuicao_opcoes'][$opcao] = 0;
                            }
                            $analise['distribuicao_opcoes'][$opcao]++;
                        }
                    }
                }
            }

            if ($analise['total_respostas'] > 0) {
                $analise['taxa_acerto'] = ($analise['respostas_corretas'] / $analise['total_respostas']) * 100;
            }

            if ($contagemPontuacao > 0) {
                $analise['pontuacao_media'] = $somaPontuacao / $contagemPontuacao;
            }

            $analisePerguntas[] = $analise;
        }

        // Ordenar por taxa de acerto (perguntas mais difíceis primeiro)
        usort($analisePerguntas, function($a, $b) {
            return $a['taxa_acerto'] <=> $b['taxa_acerto'];
        });

        $resultado['questions'] = $analisePerguntas;
    }

    echo json_encode([
        'success' => true,
        'data' => $resultado
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
