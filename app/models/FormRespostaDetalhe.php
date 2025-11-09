<?php
/**
 * Model: FormRespostaDetalhe
 * Gerencia respostas individuais de cada pergunta
 */

require_once __DIR__ . '/../classes/Database.php';

class FormRespostaDetalhe {
    private $db;
    private $pdo;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    /**
     * Cria um novo detalhe de resposta
     */
    public function criar($dados) {
        $sql = "INSERT INTO form_respostas_detalhes
                (resposta_id, pergunta_id, opcao_id, valor_texto, valor_numero,
                 valor_data, arquivo_path, pontuacao_obtida)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $dados['resposta_id'],
            $dados['pergunta_id'],
            $dados['opcao_id'] ?? null,
            $dados['valor_texto'] ?? null,
            $dados['valor_numero'] ?? null,
            $dados['valor_data'] ?? null,
            $dados['arquivo_path'] ?? null,
            $dados['pontuacao_obtida'] ?? 0
        ]);

        return $this->pdo->lastInsertId();
    }

    /**
     * Salva resposta de uma pergunta
     */
    public function salvarResposta($respostaId, $perguntaId, $valor, $tipoPergunta) {
        // Verificar se já existe resposta para essa pergunta
        $existente = $this->buscarPorRespostaEPergunta($respostaId, $perguntaId);

        if ($existente) {
            // Atualizar resposta existente
            return $this->atualizarResposta($existente['id'], $valor, $tipoPergunta);
        } else {
            // Criar nova resposta
            return $this->criarResposta($respostaId, $perguntaId, $valor, $tipoPergunta);
        }
    }

    /**
     * Cria resposta baseada no tipo
     */
    private function criarResposta($respostaId, $perguntaId, $valor, $tipoPergunta) {
        $dados = [
            'resposta_id' => $respostaId,
            'pergunta_id' => $perguntaId
        ];

        // Determinar campo baseado no tipo
        switch ($tipoPergunta) {
            case 'texto_curto':
            case 'texto_longo':
                $dados['valor_texto'] = $valor;
                break;

            case 'multipla_escolha':
            case 'lista_suspensa':
                $dados['opcao_id'] = $valor;
                $dados['pontuacao_obtida'] = $this->obterPontuacaoOpcao($valor);
                break;

            case 'caixas_selecao':
                // Valor pode ser array de IDs
                if (is_array($valor)) {
                    // Criar múltiplos detalhes
                    $ids = [];
                    foreach ($valor as $opcaoId) {
                        $dados['opcao_id'] = $opcaoId;
                        $dados['pontuacao_obtida'] = $this->obterPontuacaoOpcao($opcaoId);
                        $ids[] = $this->criar($dados);
                    }
                    return $ids;
                } else {
                    $dados['opcao_id'] = $valor;
                    $dados['pontuacao_obtida'] = $this->obterPontuacaoOpcao($valor);
                }
                break;

            case 'escala_linear':
                $dados['valor_numero'] = $valor;
                // Pontuação pode ser proporcional à escala
                $dados['pontuacao_obtida'] = $this->calcularPontuacaoEscala($perguntaId, $valor);
                break;

            case 'data':
                $dados['valor_data'] = $valor;
                break;

            case 'hora':
                $dados['valor_texto'] = $valor;
                break;

            case 'arquivo':
                $dados['arquivo_path'] = $valor;
                break;

            case 'grade_multipla':
                $dados['valor_texto'] = is_array($valor) ? json_encode($valor) : $valor;
                break;

            default:
                $dados['valor_texto'] = $valor;
        }

        return $this->criar($dados);
    }

    /**
     * Atualiza resposta existente
     */
    private function atualizarResposta($detalheId, $valor, $tipoPergunta) {
        $campos = [];
        $valores = [];

        // Limpar campos antigos
        $campos[] = "opcao_id = NULL";
        $campos[] = "valor_texto = NULL";
        $campos[] = "valor_numero = NULL";
        $campos[] = "valor_data = NULL";
        $campos[] = "arquivo_path = NULL";

        // Determinar campo baseado no tipo
        switch ($tipoPergunta) {
            case 'texto_curto':
            case 'texto_longo':
            case 'hora':
                $campos[] = "valor_texto = ?";
                $valores[] = $valor;
                break;

            case 'multipla_escolha':
            case 'lista_suspensa':
                $campos[] = "opcao_id = ?";
                $campos[] = "pontuacao_obtida = ?";
                $valores[] = $valor;
                $valores[] = $this->obterPontuacaoOpcao($valor);
                break;

            case 'escala_linear':
                $campos[] = "valor_numero = ?";
                $campos[] = "pontuacao_obtida = ?";
                $valores[] = $valor;
                $valores[] = $this->calcularPontuacaoEscala($detalheId, $valor);
                break;

            case 'data':
                $campos[] = "valor_data = ?";
                $valores[] = $valor;
                break;

            case 'arquivo':
                $campos[] = "arquivo_path = ?";
                $valores[] = $valor;
                break;

            default:
                $campos[] = "valor_texto = ?";
                $valores[] = $valor;
        }

        $valores[] = $detalheId;

        $sql = "UPDATE form_respostas_detalhes SET " . implode(', ', $campos) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($valores);
    }

    /**
     * Busca detalhe por ID
     */
    public function buscarPorId($id) {
        $sql = "SELECT rd.*,
                       p.pergunta,
                       p.tipo_pergunta,
                       o.texto_opcao as opcao_texto
                FROM form_respostas_detalhes rd
                INNER JOIN form_perguntas p ON rd.pergunta_id = p.id
                LEFT JOIN form_opcoes_resposta o ON rd.opcao_id = o.id
                WHERE rd.id = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Busca por resposta e pergunta
     */
    public function buscarPorRespostaEPergunta($respostaId, $perguntaId) {
        $sql = "SELECT * FROM form_respostas_detalhes
                WHERE resposta_id = ? AND pergunta_id = ?
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$respostaId, $perguntaId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Lista detalhes de uma resposta
     */
    public function listarPorResposta($respostaId) {
        $sql = "SELECT rd.*,
                       p.pergunta,
                       p.tipo_pergunta,
                       p.ordem as pergunta_ordem,
                       s.titulo as secao_titulo,
                       s.ordem as secao_ordem,
                       o.texto_opcao as opcao_texto
                FROM form_respostas_detalhes rd
                INNER JOIN form_perguntas p ON rd.pergunta_id = p.id
                INNER JOIN form_secoes s ON p.secao_id = s.id
                LEFT JOIN form_opcoes_resposta o ON rd.opcao_id = o.id
                WHERE rd.resposta_id = ?
                ORDER BY s.ordem, p.ordem";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$respostaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lista detalhes agrupados por seção
     */
    public function listarPorRespostaAgrupado($respostaId) {
        $detalhes = $this->listarPorResposta($respostaId);

        $agrupado = [];
        foreach ($detalhes as $detalhe) {
            $secao = $detalhe['secao_titulo'];

            if (!isset($agrupado[$secao])) {
                $agrupado[$secao] = [
                    'titulo' => $secao,
                    'ordem' => $detalhe['secao_ordem'],
                    'perguntas' => []
                ];
            }

            $agrupado[$secao]['perguntas'][] = $detalhe;
        }

        return array_values($agrupado);
    }

    /**
     * Obtém pontuação de uma opção
     */
    private function obterPontuacaoOpcao($opcaoId) {
        if (!$opcaoId) {
            return 0;
        }

        $sql = "SELECT pontuacao FROM form_opcoes_resposta WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$opcaoId]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado['pontuacao'] ?? 0;
    }

    /**
     * Calcula pontuação para escala linear
     */
    private function calcularPontuacaoEscala($perguntaId, $valorEscolhido) {
        // Buscar configuração da pergunta
        require_once __DIR__ . '/FormPergunta.php';
        $perguntaModel = new FormPergunta();
        $pergunta = $perguntaModel->buscarPorId($perguntaId);

        if (!$pergunta || !$pergunta['tem_pontuacao']) {
            return 0;
        }

        $config = $pergunta['config_adicional'] ?? [];
        $min = $config['min'] ?? 0;
        $max = $config['max'] ?? 10;
        $pontuacaoMax = $pergunta['pontuacao_maxima'] ?? 0;

        if ($max == $min) {
            return 0;
        }

        // Pontuação proporcional
        $proporcao = ($valorEscolhido - $min) / ($max - $min);
        return round($proporcao * $pontuacaoMax, 2);
    }

    /**
     * Deleta detalhe
     */
    public function deletar($id) {
        $sql = "DELETE FROM form_respostas_detalhes WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Deleta todos os detalhes de uma resposta
     */
    public function deletarPorResposta($respostaId) {
        $sql = "DELETE FROM form_respostas_detalhes WHERE resposta_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$respostaId]);
    }

    /**
     * Obtém estatísticas de respostas por pergunta
     */
    public function obterEstatisticasPorPergunta($perguntaId) {
        $sql = "SELECT
                    COUNT(*) as total_respostas,
                    AVG(pontuacao_obtida) as media_pontuacao,
                    MAX(pontuacao_obtida) as max_pontuacao,
                    MIN(pontuacao_obtida) as min_pontuacao
                FROM form_respostas_detalhes
                WHERE pergunta_id = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$perguntaId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtém distribuição de respostas por opção (para múltipla escolha)
     */
    public function obterDistribuicaoPorOpcao($perguntaId) {
        $sql = "SELECT
                    o.id,
                    o.texto_opcao,
                    o.pontuacao,
                    COUNT(rd.id) as total_escolhas,
                    ROUND((COUNT(rd.id) * 100.0 / (
                        SELECT COUNT(*) FROM form_respostas_detalhes WHERE pergunta_id = ?
                    )), 2) as percentual
                FROM form_opcoes_resposta o
                LEFT JOIN form_respostas_detalhes rd ON o.id = rd.opcao_id
                WHERE o.pergunta_id = ?
                GROUP BY o.id
                ORDER BY o.ordem";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$perguntaId, $perguntaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
