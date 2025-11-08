<?php
/**
 * Model: Pergunta
 * Gerencia perguntas dos módulos de avaliação
 */

require_once __DIR__ . '/../classes/Database.php';

class Pergunta {
    private $db;
    private $pdo;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    /**
     * Lista perguntas por módulo
     */
    public function listarPorModulo($moduloId, $apenasAtivas = true) {
        $sql = "SELECT * FROM perguntas WHERE modulo_id = ?";

        if ($apenasAtivas) {
            $sql .= " AND ativo = 1";
        }

        $sql .= " ORDER BY ordem, id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$moduloId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca pergunta por ID
     */
    public function buscarPorId($id) {
        $sql = "SELECT p.*, m.nome as modulo_nome, m.total_perguntas
                FROM perguntas p
                INNER JOIN modulos_avaliacao m ON p.modulo_id = m.id
                WHERE p.id = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Cria nova pergunta
     */
    public function criar($dados) {
        $sql = "INSERT INTO perguntas
                (modulo_id, texto, descricao, ordem, obrigatoria, permite_foto, ativo)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $dados['modulo_id'],
            $dados['texto'],
            $dados['descricao'] ?? null,
            $dados['ordem'] ?? 0,
            $dados['obrigatoria'] ?? 1,
            $dados['permite_foto'] ?? 1,
            $dados['ativo'] ?? 1
        ]);

        return $this->pdo->lastInsertId();
    }

    /**
     * Atualiza pergunta
     */
    public function atualizar($id, $dados) {
        $campos = [];
        $valores = [];

        foreach ($dados as $campo => $valor) {
            $campos[] = "{$campo} = ?";
            $valores[] = $valor;
        }

        $valores[] = $id;

        $sql = "UPDATE perguntas SET " . implode(', ', $campos) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($valores);
    }

    /**
     * Deleta pergunta (ou desativa se tiver respostas)
     */
    public function deletar($id) {
        // Verificar se a pergunta tem respostas associadas
        $sqlCheck = "SELECT COUNT(*) as total FROM respostas_checklist WHERE pergunta_id = ?";
        $stmtCheck = $this->pdo->prepare($sqlCheck);
        $stmtCheck->execute([$id]);
        $resultado = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($resultado['total'] > 0) {
            // Se tiver respostas, desativar (soft delete) para preservar histórico
            $sql = "UPDATE perguntas SET ativo = 0 WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$id]);
        } else {
            // Se não tiver respostas, pode deletar permanentemente
            $sql = "DELETE FROM perguntas WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$id]);
        }
    }

    /**
     * Verifica se uma pergunta tem respostas
     */
    public function temRespostas($id) {
        $sql = "SELECT COUNT(*) as total FROM respostas_checklist WHERE pergunta_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['total'] > 0;
    }

    /**
     * Reordena perguntas
     */
    public function reordenar($perguntasOrdenadas) {
        $this->pdo->beginTransaction();

        try {
            $sql = "UPDATE perguntas SET ordem = ? WHERE id = ?";
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
}
