<?php
/**
 * Model: FormPergunta
 * Gerencia perguntas dos formulários dinâmicos
 * IMPORTANTE: Nome diferente de "Pergunta" para evitar conflito
 */

require_once __DIR__ . '/../classes/Database.php';

class FormPergunta {
    private $db;
    private $pdo;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    /**
     * Cria uma nova pergunta
     */
    public function criar($dados) {
        // Validar tipo de pergunta
        $tiposPermitidos = [
            'texto_curto', 'texto_longo', 'multipla_escolha',
            'caixas_selecao', 'lista_suspensa', 'escala_linear',
            'grade_multipla', 'data', 'hora', 'arquivo'
        ];

        if (!in_array($dados['tipo_pergunta'], $tiposPermitidos)) {
            throw new Exception('Tipo de pergunta inválido');
        }

        // Determinar ordem automaticamente
        if (!isset($dados['ordem'])) {
            $dados['ordem'] = $this->obterProximaOrdem($dados['secao_id']);
        }

        // Converter config_adicional para JSON se for array
        if (isset($dados['config_adicional']) && is_array($dados['config_adicional'])) {
            $dados['config_adicional'] = json_encode($dados['config_adicional']);
        }

        $sql = "INSERT INTO form_perguntas
                (secao_id, tipo_pergunta, pergunta, descricao, ordem, obrigatoria,
                 peso, pontuacao_maxima, tem_pontuacao, config_adicional)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $dados['secao_id'],
            $dados['tipo_pergunta'],
            $dados['pergunta'],
            $dados['descricao'] ?? null,
            $dados['ordem'],
            $dados['obrigatoria'] ?? 0,
            $dados['peso'] ?? 1.00,
            $dados['pontuacao_maxima'] ?? 0,
            $dados['tem_pontuacao'] ?? 0,
            $dados['config_adicional'] ?? null
        ]);

        return $this->pdo->lastInsertId();
    }

    /**
     * Busca pergunta por ID
     */
    public function buscarPorId($id) {
        $sql = "SELECT p.*,
                       s.titulo as secao_titulo,
                       s.formulario_id,
                       COUNT(DISTINCT o.id) as total_opcoes
                FROM form_perguntas p
                INNER JOIN form_secoes s ON p.secao_id = s.id
                LEFT JOIN form_opcoes_resposta o ON p.id = o.pergunta_id
                WHERE p.id = ?
                GROUP BY p.id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        // Decodificar config_adicional
        if ($resultado && !empty($resultado['config_adicional'])) {
            $resultado['config_adicional'] = json_decode($resultado['config_adicional'], true);
        }

        return $resultado;
    }

    /**
     * Lista perguntas de uma seção
     */
    public function listarPorSecao($secaoId) {
        $sql = "SELECT p.*,
                       COUNT(DISTINCT o.id) as total_opcoes
                FROM form_perguntas p
                LEFT JOIN form_opcoes_resposta o ON p.id = o.pergunta_id
                WHERE p.secao_id = ?
                GROUP BY p.id
                ORDER BY p.ordem, p.id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$secaoId]);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Decodificar config_adicional para cada pergunta
        foreach ($resultados as &$resultado) {
            if (!empty($resultado['config_adicional'])) {
                $resultado['config_adicional'] = json_decode($resultado['config_adicional'], true);
            }
        }

        return $resultados;
    }

    /**
     * Lista todas as perguntas de um formulário
     */
    public function listarPorFormulario($formularioId) {
        $sql = "SELECT p.*,
                       s.titulo as secao_titulo,
                       s.ordem as secao_ordem,
                       COUNT(DISTINCT o.id) as total_opcoes
                FROM form_perguntas p
                INNER JOIN form_secoes s ON p.secao_id = s.id
                LEFT JOIN form_opcoes_resposta o ON p.id = o.pergunta_id
                WHERE s.formulario_id = ?
                GROUP BY p.id
                ORDER BY s.ordem, p.ordem, p.id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$formularioId]);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($resultados as &$resultado) {
            if (!empty($resultado['config_adicional'])) {
                $resultado['config_adicional'] = json_decode($resultado['config_adicional'], true);
            }
        }

        return $resultados;
    }

    /**
     * Atualiza pergunta
     */
    public function atualizar($id, $dados) {
        $campos = [];
        $valores = [];

        $camposPermitidos = [
            'tipo_pergunta', 'pergunta', 'descricao', 'ordem', 'obrigatoria',
            'peso', 'pontuacao_maxima', 'tem_pontuacao', 'config_adicional'
        ];

        foreach ($dados as $campo => $valor) {
            if (in_array($campo, $camposPermitidos)) {
                // Converter config_adicional para JSON
                if ($campo === 'config_adicional' && is_array($valor)) {
                    $valor = json_encode($valor);
                }
                $campos[] = "{$campo} = ?";
                $valores[] = $valor;
            }
        }

        if (empty($campos)) {
            throw new Exception('Nenhum campo válido para atualizar');
        }

        $valores[] = $id;

        $sql = "UPDATE form_perguntas SET " . implode(', ', $campos) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($valores);
    }

    /**
     * Deleta pergunta
     */
    public function deletar($id) {
        $sql = "DELETE FROM form_perguntas WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Reordena perguntas
     */
    public function reordenar($perguntasOrdenadas) {
        $this->pdo->beginTransaction();

        try {
            $sql = "UPDATE form_perguntas SET ordem = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);

            foreach ($perguntasOrdenadas as $ordem => $perguntaId) {
                $stmt->execute([$ordem + 1, $perguntaId]);
            }

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Obtém próxima ordem disponível
     */
    private function obterProximaOrdem($secaoId) {
        $sql = "SELECT COALESCE(MAX(ordem), 0) + 1 as proxima_ordem
                FROM form_perguntas
                WHERE secao_id = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$secaoId]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado['proxima_ordem'];
    }

    /**
     * Duplica pergunta
     */
    public function duplicar($perguntaId, $novaSecaoId = null) {
        $original = $this->buscarPorId($perguntaId);
        if (!$original) {
            throw new Exception('Pergunta não encontrada');
        }

        $novosDados = [
            'secao_id' => $novaSecaoId ?? $original['secao_id'],
            'tipo_pergunta' => $original['tipo_pergunta'],
            'pergunta' => $original['pergunta'],
            'descricao' => $original['descricao'],
            'obrigatoria' => $original['obrigatoria'],
            'peso' => $original['peso'],
            'pontuacao_maxima' => $original['pontuacao_maxima'],
            'tem_pontuacao' => $original['tem_pontuacao'],
            'config_adicional' => $original['config_adicional']
        ];

        return $this->criar($novosDados);
    }

    /**
     * Valida configuração adicional por tipo de pergunta
     */
    public function validarConfig($tipoPergunta, $config) {
        $configEsperada = $this->getConfigPadrao($tipoPergunta);

        // Implementar validação específica por tipo
        switch ($tipoPergunta) {
            case 'escala_linear':
                return isset($config['min']) && isset($config['max']);

            case 'arquivo':
                return isset($config['tipos_permitidos']) && isset($config['tamanho_max']);

            case 'grade_multipla':
                return isset($config['linhas']) && isset($config['colunas']);

            default:
                return true;
        }
    }

    /**
     * Obtém configuração padrão por tipo
     */
    public function getConfigPadrao($tipoPergunta) {
        $configs = [
            'texto_curto' => ['max_caracteres' => 255],
            'texto_longo' => ['max_caracteres' => 5000, 'min_caracteres' => 0],
            'escala_linear' => ['min' => 0, 'max' => 10, 'label_min' => '', 'label_max' => ''],
            'arquivo' => ['tipos_permitidos' => ['pdf', 'jpg', 'png'], 'tamanho_max' => 5242880], // 5MB
            'lista_suspensa' => ['permite_pesquisa' => false],
            'caixas_selecao' => ['min_selecoes' => 0, 'max_selecoes' => null],
            'grade_multipla' => ['linhas' => [], 'colunas' => [], 'tipo' => 'radio']
        ];

        return $configs[$tipoPergunta] ?? [];
    }
}
