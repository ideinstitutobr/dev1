<?php
/**
 * API: Exportar Respostas para CSV
 * Exporta todas as respostas de um formulário em formato CSV
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

try {
    // Verificar autenticação
    if (!Auth::isLogged()) {
        die('Usuário não autenticado');
    }

    // Validar ID do formulário
    if (empty($_GET['formulario_id'])) {
        die('ID do formulário não informado');
    }

    $formularioId = (int)$_GET['formulario_id'];

    // Buscar formulário
    $formularioModel = new FormularioDinamico();
    $formulario = $formularioModel->buscarPorId($formularioId);

    if (!$formulario) {
        die('Formulário não encontrado');
    }

    // Verificar permissão (proprietário ou admin)
    $userId = Auth::getUserId();
    $isAdmin = Auth::isAdmin();

    if ($formulario['usuario_id'] != $userId && !$isAdmin) {
        die('Sem permissão para exportar respostas deste formulário');
    }

    // Buscar todas as respostas
    $respostaModel = new FormResposta();
    $respostas = $respostaModel->listarPorFormulario($formularioId);

    if (empty($respostas)) {
        die('Nenhuma resposta encontrada para exportar');
    }

    // Buscar todas as perguntas do formulário
    $perguntaModel = new FormPergunta();
    $perguntas = $perguntaModel->listarPorFormulario($formularioId);

    // Buscar detalhes de todas as respostas
    $detalheModel = new FormRespostaDetalhe();

    // Preparar dados para CSV
    $dadosCSV = [];

    // Cabeçalho do CSV
    $cabecalho = [
        'ID Resposta',
        'Nome',
        'E-mail',
        'Data/Hora Início',
        'Data/Hora Conclusão',
        'Status',
        'Pontuação Total',
        'Percentual',
        'Tempo (segundos)'
    ];

    // Adicionar colunas para cada pergunta
    foreach ($perguntas as $pergunta) {
        $cabecalho[] = htmlspecialchars_decode(strip_tags($pergunta['pergunta']));
        if ($formulario['tipo_pontuacao'] !== 'nenhum') {
            $cabecalho[] = 'Pontos - ' . htmlspecialchars_decode(strip_tags($pergunta['pergunta']));
        }
    }

    $dadosCSV[] = $cabecalho;

    // Adicionar linhas de dados
    foreach ($respostas as $resposta) {
        $linha = [
            $resposta['id'],
            $resposta['respondente_nome'] ?? 'Anônimo',
            $resposta['respondente_email'] ?? '',
            $resposta['iniciado_em'],
            $resposta['concluido_em'] ?? '',
            ucfirst(str_replace('_', ' ', $resposta['status_resposta'])),
            $resposta['pontuacao_total'] ?? 0,
            number_format($resposta['percentual_acerto'] ?? 0, 2) . '%',
            $resposta['tempo_resposta'] ?? 0
        ];

        // Buscar detalhes desta resposta
        $detalhes = $detalheModel->listarPorResposta($resposta['id']);

        // Organizar detalhes por pergunta_id
        $detalhesPorPergunta = [];
        foreach ($detalhes as $detalhe) {
            $detalhesPorPergunta[$detalhe['pergunta_id']] = $detalhe;
        }

        // Adicionar respostas de cada pergunta
        foreach ($perguntas as $pergunta) {
            $respostaTexto = '';
            $pontuacaoObtida = 0;

            if (isset($detalhesPorPergunta[$pergunta['id']])) {
                $detalhe = $detalhesPorPergunta[$pergunta['id']];

                // Extrair texto da resposta baseado no tipo
                if ($detalhe['valor_texto']) {
                    $respostaTexto = $detalhe['valor_texto'];
                } elseif ($detalhe['opcao_selecionada']) {
                    $respostaTexto = $detalhe['opcao_selecionada'];
                } elseif ($detalhe['opcoes_selecionadas']) {
                    $opcoes = json_decode($detalhe['opcoes_selecionadas'], true);
                    $respostaTexto = implode('; ', $opcoes);
                } elseif ($detalhe['valor_numerico'] !== null) {
                    $respostaTexto = $detalhe['valor_numerico'];
                } elseif ($detalhe['valor_data']) {
                    $respostaTexto = date('d/m/Y', strtotime($detalhe['valor_data']));
                } elseif ($detalhe['arquivo_path']) {
                    $respostaTexto = basename($detalhe['arquivo_path']);
                }

                $pontuacaoObtida = $detalhe['pontuacao_obtida'] ?? 0;
            }

            $linha[] = $respostaTexto;
            if ($formulario['tipo_pontuacao'] !== 'nenhum') {
                $linha[] = $pontuacaoObtida;
            }
        }

        $dadosCSV[] = $linha;
    }

    // Gerar arquivo CSV
    $nomeArquivo = 'respostas_' . preg_replace('/[^a-z0-9]/i', '_', $formulario['titulo']) . '_' . date('Y-m-d_His') . '.csv';

    // Headers para download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
    header('Cache-Control: max-age=0');

    // Criar output stream
    $output = fopen('php://output', 'w');

    // BOM para UTF-8 (para Excel reconhecer acentos)
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Escrever dados
    foreach ($dadosCSV as $linha) {
        fputcsv($output, $linha, ';'); // Usando ; como delimitador para Excel pt-BR
    }

    fclose($output);
    exit;

} catch (Exception $e) {
    die('Erro ao exportar: ' . $e->getMessage());
}
