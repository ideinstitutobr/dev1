<?php
/**
 * Model: FormFaixaPontuacao
 * Gerencia faixas de pontuação e classificação dos formulários
 */

require_once __DIR__ . '/../classes/Database.php';

class FormFaixaPontuacao {
    private $db;
    private $pdo;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    /**
     * Cria uma nova faixa de pontuação
     */
    public function criar($dados) {
        // Validar faixa
        if ($dados['pontuacao_minima'] >= $dados['pontuacao_maxima']) {
            throw new Exception('Pontuação mínima deve ser menor que máxima');
        }

        // Verificar sobreposição de faixas
        if ($this->verificarSobreposicao(
            $dados['formulario_id'],
            $dados['pontuacao_minima'],
            $dados['pontuacao_maxima']
        )) {
            throw new Exception('Esta faixa sobrepõe outra faixa existente');
        }

        if (!isset($dados['ordem'])) {
            $dados['ordem'] = $this->obterProximaOrdem($dados['formulario_id']);
        }

        $sql = "INSERT INTO form_faixas_pontuacao
                (formulario_id, titulo, pontuacao_minima, pontuacao_maxima,
                 percentual_minimo, percentual_maximo, mensagem, recomendacoes, cor, ordem)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $dados['formulario_id'],
            $dados['titulo'],
            $dados['pontuacao_minima'],
            $dados['pontuacao_maxima'],
            $dados['percentual_minimo'] ?? null,
            $dados['percentual_maximo'] ?? null,
            $dados['mensagem'] ?? null,
            $dados['recomendacoes'] ?? null,
            $dados['cor'] ?? '#28a745',
            $dados['ordem']
        ]);

        return $this->pdo->lastInsertId();
    }

    /**
     * Busca faixa por ID
     */
    public function buscarPorId($id) {
        $sql = "SELECT * FROM form_faixas_pontuacao WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Lista faixas de um formulário
     */
    public function listarPorFormulario($formularioId) {
        $sql = "SELECT * FROM form_faixas_pontuacao WHERE formulario_id = ? ORDER BY ordem, pontuacao_minima";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$formularioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Identifica faixa para uma pontuação
     */
    public function identificarFaixa($formularioId, $pontuacao) {
        $sql = "SELECT * FROM form_faixas_pontuacao
                WHERE formulario_id = ?
                AND pontuacao_minima <= ?
                AND pontuacao_maxima >= ?
                ORDER BY ordem
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$formularioId, $pontuacao, $pontuacao]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Identifica faixa por percentual
     */
    public function identificarFaixaPorPercentual($formularioId, $percentual) {
        $sql = "SELECT * FROM form_faixas_pontuacao
                WHERE formulario_id = ?
                AND percentual_minimo <= ?
                AND percentual_maximo >= ?
                ORDER BY ordem
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$formularioId, $percentual, $percentual]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Atualiza faixa
     */
    public function atualizar($id, $dados) {
        $campos = [];
        $valores = [];

        $camposPermitidos = [
            'titulo', 'pontuacao_minima', 'pontuacao_maxima',
            'percentual_minimo', 'percentual_maximo', 'mensagem',
            'recomendacoes', 'cor', 'ordem'
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

        $sql = "UPDATE form_faixas_pontuacao SET " . implode(', ', $campos) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($valores);
    }

    /**
     * Deleta faixa
     */
    public function deletar($id) {
        $sql = "DELETE FROM form_faixas_pontuacao WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Deleta todas as faixas de um formulário
     */
    public function deletarTodosPorFormulario($formularioId) {
        $sql = "DELETE FROM form_faixas_pontuacao WHERE formulario_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$formularioId]);
    }

    /**
     * Verifica sobreposição de faixas
     */
    private function verificarSobreposicao($formularioId, $minima, $maxima, $excluirId = null) {
        $sql = "SELECT COUNT(*) as total FROM form_faixas_pontuacao
                WHERE formulario_id = ?
                AND (
                    (pontuacao_minima <= ? AND pontuacao_maxima >= ?)
                    OR (pontuacao_minima <= ? AND pontuacao_maxima >= ?)
                    OR (pontuacao_minima >= ? AND pontuacao_maxima <= ?)
                )";

        $params = [$formularioId, $minima, $minima, $maxima, $maxima, $minima, $maxima];

        if ($excluirId) {
            $sql .= " AND id != ?";
            $params[] = $excluirId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado['total'] > 0;
    }

    /**
     * Obtém próxima ordem disponível
     */
    private function obterProximaOrdem($formularioId) {
        $sql = "SELECT COALESCE(MAX(ordem), 0) + 1 as proxima_ordem
                FROM form_faixas_pontuacao
                WHERE formulario_id = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$formularioId]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado['proxima_ordem'];
    }

    /**
     * Cria faixas padrão para um formulário
     */
    public function criarFaixasPadrao($formularioId, $pontuacaoMaxima) {
        $faixasPadrao = [
            [
                'titulo' => 'Crítico',
                'pontuacao_minima' => 0,
                'pontuacao_maxima' => $pontuacaoMaxima * 0.25,
                'percentual_minimo' => 0,
                'percentual_maximo' => 25,
                'cor' => '#dc3545',
                'mensagem' => 'Resultado crítico. Atenção necessária.',
                'ordem' => 1
            ],
            [
                'titulo' => 'Regular',
                'pontuacao_minima' => $pontuacaoMaxima * 0.25 + 0.01,
                'pontuacao_maxima' => $pontuacaoMaxima * 0.50,
                'percentual_minimo' => 25.01,
                'percentual_maximo' => 50,
                'cor' => '#ffc107',
                'mensagem' => 'Resultado regular. Há espaço para melhorias.',
                'ordem' => 2
            ],
            [
                'titulo' => 'Bom',
                'pontuacao_minima' => $pontuacaoMaxima * 0.50 + 0.01,
                'pontuacao_maxima' => $pontuacaoMaxima * 0.75,
                'percentual_minimo' => 50.01,
                'percentual_maximo' => 75,
                'cor' => '#17a2b8',
                'mensagem' => 'Bom resultado. Continue assim!',
                'ordem' => 3
            ],
            [
                'titulo' => 'Excelente',
                'pontuacao_minima' => $pontuacaoMaxima * 0.75 + 0.01,
                'pontuacao_maxima' => $pontuacaoMaxima,
                'percentual_minimo' => 75.01,
                'percentual_maximo' => 100,
                'cor' => '#28a745',
                'mensagem' => 'Resultado excelente. Parabéns!',
                'ordem' => 4
            ]
        ];

        foreach ($faixasPadrao as $faixa) {
            $faixa['formulario_id'] = $formularioId;
            $this->criar($faixa);
        }

        return true;
    }

    /**
     * Obtém distribuição de respostas por faixa
     */
    public function obterDistribuicaoRespostas($formularioId) {
        $sql = "SELECT
                    f.id,
                    f.titulo,
                    f.cor,
                    f.pontuacao_minima,
                    f.pontuacao_maxima,
                    COUNT(r.id) as total_respostas,
                    ROUND(AVG(r.pontuacao_total), 2) as media_pontuacao
                FROM form_faixas_pontuacao f
                LEFT JOIN form_respostas r ON r.formulario_id = f.formulario_id
                    AND r.pontuacao_total >= f.pontuacao_minima
                    AND r.pontuacao_total <= f.pontuacao_maxima
                    AND r.status_resposta = 'concluida'
                WHERE f.formulario_id = ?
                GROUP BY f.id
                ORDER BY f.ordem";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$formularioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
