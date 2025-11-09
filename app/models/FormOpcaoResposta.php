<?php
/**
 * Model: FormOpcaoResposta
 * Gerencia opções de resposta para perguntas de múltipla escolha
 */

require_once __DIR__ . '/../classes/Database.php';

class FormOpcaoResposta {
    private $db;
    private $pdo;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    /**
     * Cria uma nova opção de resposta
     */
    public function criar($dados) {
        if (!isset($dados['ordem'])) {
            $dados['ordem'] = $this->obterProximaOrdem($dados['pergunta_id']);
        }

        $sql = "INSERT INTO form_opcoes_resposta
                (pergunta_id, texto_opcao, ordem, pontuacao, vai_para_secao, vai_para_pergunta, cor)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $dados['pergunta_id'],
            $dados['texto_opcao'],
            $dados['ordem'],
            $dados['pontuacao'] ?? 0,
            $dados['vai_para_secao'] ?? null,
            $dados['vai_para_pergunta'] ?? null,
            $dados['cor'] ?? null
        ]);

        return $this->pdo->lastInsertId();
    }

    /**
     * Busca opção por ID
     */
    public function buscarPorId($id) {
        $sql = "SELECT * FROM form_opcoes_resposta WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Lista opções de uma pergunta
     */
    public function listarPorPergunta($perguntaId) {
        $sql = "SELECT * FROM form_opcoes_resposta WHERE pergunta_id = ? ORDER BY ordem, id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$perguntaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Atualiza opção
     */
    public function atualizar($id, $dados) {
        $campos = [];
        $valores = [];

        $camposPermitidos = ['texto_opcao', 'ordem', 'pontuacao', 'vai_para_secao', 'vai_para_pergunta', 'cor'];

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

        $sql = "UPDATE form_opcoes_resposta SET " . implode(', ', $campos) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($valores);
    }

    /**
     * Deleta opção
     */
    public function deletar($id) {
        $sql = "DELETE FROM form_opcoes_resposta WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Reordena opções
     */
    public function reordenar($opcoesOrdenadas) {
        $this->pdo->beginTransaction();

        try {
            $sql = "UPDATE form_opcoes_resposta SET ordem = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);

            foreach ($opcoesOrdenadas as $ordem => $opcaoId) {
                $stmt->execute([$ordem + 1, $opcaoId]);
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
    private function obterProximaOrdem($perguntaId) {
        $sql = "SELECT COALESCE(MAX(ordem), 0) + 1 as proxima_ordem
                FROM form_opcoes_resposta
                WHERE pergunta_id = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$perguntaId]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado['proxima_ordem'];
    }

    /**
     * Duplica opção
     */
    public function duplicar($opcaoId, $novaPerguntaId = null) {
        $original = $this->buscarPorId($opcaoId);
        if (!$original) {
            throw new Exception('Opção não encontrada');
        }

        $novosDados = [
            'pergunta_id' => $novaPerguntaId ?? $original['pergunta_id'],
            'texto_opcao' => $original['texto_opcao'],
            'pontuacao' => $original['pontuacao'],
            'cor' => $original['cor']
        ];

        return $this->criar($novosDados);
    }
}
