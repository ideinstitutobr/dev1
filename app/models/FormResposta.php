<?php
/**
 * Model: FormResposta
 * Gerencia respostas de formulários preenchidos
 */

require_once __DIR__ . '/../classes/Database.php';

class FormResposta {
    private $db;
    private $pdo;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    /**
     * Inicia uma nova resposta
     */
    public function iniciar($dados) {
        $sql = "INSERT INTO form_respostas
                (formulario_id, respondente_email, respondente_nome, respondente_ip, status_resposta)
                VALUES (?, ?, ?, ?, 'em_andamento')";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $dados['formulario_id'],
            $dados['respondente_email'] ?? null,
            $dados['respondente_nome'] ?? null,
            $dados['respondente_ip'] ?? $_SERVER['REMOTE_ADDR'] ?? null
        ]);

        return $this->pdo->lastInsertId();
    }

    /**
     * Busca resposta por ID
     */
    public function buscarPorId($id) {
        $sql = "SELECT r.*,
                       f.titulo as formulario_titulo,
                       f.tipo_pontuacao,
                       f.pontuacao_maxima as formulario_pontuacao_max,
                       COUNT(DISTINCT rd.id) as total_respostas_detalhes
                FROM form_respostas r
                INNER JOIN formularios_dinamicos f ON r.formulario_id = f.id
                LEFT JOIN form_respostas_detalhes rd ON r.id = rd.resposta_id
                WHERE r.id = ?
                GROUP BY r.id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Lista respostas de um formulário
     */
    public function listarPorFormulario($formularioId, $filtros = []) {
        $where = ['r.formulario_id = ?'];
        $params = [$formularioId];

        // Filtro por status
        if (!empty($filtros['status_resposta'])) {
            $where[] = "r.status_resposta = ?";
            $params[] = $filtros['status_resposta'];
        }

        // Filtro por email
        if (!empty($filtros['respondente_email'])) {
            $where[] = "r.respondente_email LIKE ?";
            $params[] = '%' . $filtros['respondente_email'] . '%';
        }

        // Filtro por data
        if (!empty($filtros['data_inicio'])) {
            $where[] = "DATE(r.iniciado_em) >= ?";
            $params[] = $filtros['data_inicio'];
        }

        if (!empty($filtros['data_fim'])) {
            $where[] = "DATE(r.concluido_em) <= ?";
            $params[] = $filtros['data_fim'];
        }

        $whereClause = implode(' AND ', $where);

        $sql = "SELECT r.*,
                       COUNT(DISTINCT rd.id) as total_respostas_detalhes
                FROM form_respostas r
                LEFT JOIN form_respostas_detalhes rd ON r.id = rd.resposta_id
                WHERE {$whereClause}
                GROUP BY r.id
                ORDER BY r.iniciado_em DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Atualiza resposta
     */
    public function atualizar($id, $dados) {
        $campos = [];
        $valores = [];

        $camposPermitidos = [
            'respondente_email', 'respondente_nome', 'pontuacao_total',
            'percentual_acerto', 'status_resposta', 'tempo_resposta', 'concluido_em'
        ];

        foreach ($dados as $campo => $valor) {
            if (in_array($campo, $camposPermitidos)) {
                $campos[] = "{$campo} = ?";
                $valores[] = $valor;
            }
        }

        if (empty($campos)) {
            throw new Exception('Nenhum campo válido para atualizar');
        }

        $valores[] = $id;

        $sql = "UPDATE form_respostas SET " . implode(', ', $campos) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($valores);
    }

    /**
     * Marca resposta como concluída
     */
    public function concluir($id) {
        // Buscar resposta
        $resposta = $this->buscarPorId($id);
        if (!$resposta) {
            throw new Exception('Resposta não encontrada');
        }

        // Calcular tempo de resposta
        $iniciado = new DateTime($resposta['iniciado_em']);
        $concluido = new DateTime();
        $tempoResposta = $concluido->getTimestamp() - $iniciado->getTimestamp();

        // Calcular pontuação
        $pontuacao = $this->calcularPontuacao($id);

        // Atualizar resposta
        return $this->atualizar($id, [
            'status_resposta' => 'concluida',
            'concluido_em' => $concluido->format('Y-m-d H:i:s'),
            'tempo_resposta' => $tempoResposta,
            'pontuacao_total' => $pontuacao['total'],
            'percentual_acerto' => $pontuacao['percentual']
        ]);
    }

    /**
     * Calcula pontuação total da resposta
     */
    public function calcularPontuacao($respostaId) {
        $resposta = $this->buscarPorId($respostaId);
        if (!$resposta) {
            throw new Exception('Resposta não encontrada');
        }

        // Buscar todos os detalhes
        $sql = "SELECT SUM(pontuacao_obtida) as pontuacao_total
                FROM form_respostas_detalhes
                WHERE resposta_id = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$respostaId]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        $pontuacaoTotal = $resultado['pontuacao_total'] ?? 0;
        $pontuacaoMaxima = $resposta['formulario_pontuacao_max'] ?? 1;

        $percentual = $pontuacaoMaxima > 0
            ? ($pontuacaoTotal / $pontuacaoMaxima) * 100
            : 0;

        return [
            'total' => $pontuacaoTotal,
            'maxima' => $pontuacaoMaxima,
            'percentual' => round($percentual, 2)
        ];
    }

    /**
     * Deleta resposta
     */
    public function deletar($id) {
        $sql = "DELETE FROM form_respostas WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Obtém estatísticas de respostas de um formulário
     */
    public function obterEstatisticas($formularioId) {
        $sql = "SELECT
                    COUNT(*) as total_respostas,
                    SUM(CASE WHEN status_resposta = 'concluida' THEN 1 ELSE 0 END) as completas,
                    SUM(CASE WHEN status_resposta = 'em_andamento' THEN 1 ELSE 0 END) as em_andamento,
                    SUM(CASE WHEN status_resposta = 'incompleta' THEN 1 ELSE 0 END) as incompletas,
                    ROUND(AVG(CASE WHEN status_resposta = 'concluida' THEN pontuacao_total END), 2) as media_pontuacao,
                    ROUND(AVG(CASE WHEN status_resposta = 'concluida' THEN percentual_acerto END), 2) as media_percentual,
                    ROUND(AVG(CASE WHEN status_resposta = 'concluida' THEN tempo_resposta END), 0) as tempo_medio,
                    MIN(CASE WHEN status_resposta = 'concluida' THEN pontuacao_total END) as pontuacao_minima,
                    MAX(CASE WHEN status_resposta = 'concluida' THEN pontuacao_total END) as pontuacao_maxima
                FROM form_respostas
                WHERE formulario_id = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$formularioId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtém respostas por período
     */
    public function obterRespostasPorPeriodo($formularioId, $dataInicio, $dataFim) {
        $sql = "SELECT
                    DATE(concluido_em) as data,
                    COUNT(*) as total,
                    ROUND(AVG(pontuacao_total), 2) as media_pontuacao,
                    ROUND(AVG(percentual_acerto), 2) as media_percentual
                FROM form_respostas
                WHERE formulario_id = ?
                AND status_resposta = 'concluida'
                AND concluido_em BETWEEN ? AND ?
                GROUP BY DATE(concluido_em)
                ORDER BY data";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$formularioId, $dataInicio, $dataFim]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtém top respondentes
     */
    public function obterTopRespondentes($formularioId, $limite = 10) {
        $sql = "SELECT
                    respondente_nome,
                    respondente_email,
                    COUNT(*) as total_respostas,
                    ROUND(AVG(pontuacao_total), 2) as media_pontuacao,
                    ROUND(AVG(percentual_acerto), 2) as media_percentual,
                    MAX(concluido_em) as ultima_resposta
                FROM form_respostas
                WHERE formulario_id = ?
                AND status_resposta = 'concluida'
                AND respondente_email IS NOT NULL
                GROUP BY respondente_email, respondente_nome
                ORDER BY media_pontuacao DESC, total_respostas DESC
                LIMIT ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$formularioId, $limite]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Verifica se respondente já respondeu
     */
    public function jaRespondeu($formularioId, $email) {
        $sql = "SELECT COUNT(*) as total
                FROM form_respostas
                WHERE formulario_id = ?
                AND respondente_email = ?
                AND status_resposta = 'concluida'";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$formularioId, $email]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado['total'] > 0;
    }

    /**
     * Exporta respostas para CSV
     */
    public function exportarParaCSV($formularioId) {
        // Buscar formulário
        require_once __DIR__ . '/FormularioDinamico.php';
        $formularioModel = new FormularioDinamico();
        $formulario = $formularioModel->buscarPorId($formularioId);

        if (!$formulario) {
            throw new Exception('Formulário não encontrado');
        }

        // Buscar perguntas
        require_once __DIR__ . '/FormPergunta.php';
        $perguntaModel = new FormPergunta();
        $perguntas = $perguntaModel->listarPorFormulario($formularioId);

        // Buscar respostas
        $respostas = $this->listarPorFormulario($formularioId, ['status_resposta' => 'concluida']);

        // Montar CSV
        $csv = [];

        // Cabeçalho
        $header = [
            'ID',
            'Respondente Nome',
            'Respondente Email',
            'Data/Hora',
            'Tempo (seg)',
            'Pontuação',
            'Percentual (%)'
        ];

        foreach ($perguntas as $pergunta) {
            $header[] = $pergunta['pergunta'];
        }

        $csv[] = $header;

        // Dados
        require_once __DIR__ . '/FormRespostaDetalhe.php';
        $detalheModel = new FormRespostaDetalhe();

        foreach ($respostas as $resposta) {
            $row = [
                $resposta['id'],
                $resposta['respondente_nome'] ?? 'Anônimo',
                $resposta['respondente_email'] ?? '-',
                $resposta['concluido_em'],
                $resposta['tempo_resposta'],
                $resposta['pontuacao_total'],
                $resposta['percentual_acerto']
            ];

            $detalhes = $detalheModel->listarPorResposta($resposta['id']);
            $respostasPorPergunta = [];

            foreach ($detalhes as $detalhe) {
                $respostasPorPergunta[$detalhe['pergunta_id']] = $detalhe;
            }

            foreach ($perguntas as $pergunta) {
                $detalhe = $respostasPorPergunta[$pergunta['id']] ?? null;

                if ($detalhe) {
                    $valorResposta = $detalhe['valor_texto']
                        ?? $detalhe['valor_numero']
                        ?? $detalhe['valor_data']
                        ?? $detalhe['opcao_texto']
                        ?? '-';
                } else {
                    $valorResposta = '-';
                }

                $row[] = $valorResposta;
            }

            $csv[] = $row;
        }

        return $csv;
    }
}
