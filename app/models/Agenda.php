<?php
/**
 * Model: Agenda
 * Gerencia agenda/cronograma de treinamentos
 */

class Agenda {
    private $db;
    private $pdo;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    /**
     * Listar agendas de um treinamento
     */
    public function listarPorTreinamento($treinamentoId) {
        $sql = "SELECT
                    a.*,
                    (SELECT COUNT(*) FROM treinamento_participantes WHERE agenda_id = a.id) as total_inscritos
                FROM agenda_treinamentos a
                WHERE a.treinamento_id = ?
                ORDER BY a.data_inicio ASC, a.turma ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$treinamentoId]);
        return $stmt->fetchAll();
    }

    /**
     * Buscar agenda por ID
     */
    public function buscarPorId($id) {
        $sql = "SELECT a.*, t.nome as treinamento_nome
                FROM agenda_treinamentos a
                INNER JOIN treinamentos t ON a.treinamento_id = t.id
                WHERE a.id = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Criar nova agenda
     */
    public function criar($dados) {
        try {
            $sql = "INSERT INTO agenda_treinamentos
                    (treinamento_id, turma, data_inicio, data_fim, hora_inicio, hora_fim,
                     dias_semana, local, vagas_total, instrutor, observacoes, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $dados['treinamento_id'],
                $dados['turma'] ?? null,
                $dados['data_inicio'],
                $dados['data_fim'] ?? null,
                $dados['hora_inicio'] ?? null,
                $dados['hora_fim'] ?? null,
                $dados['dias_semana'] ?? null,
                $dados['local'] ?? null,
                $dados['vagas_total'] ?? 0,
                $dados['instrutor'] ?? null,
                $dados['observacoes'] ?? null,
                $dados['status'] ?? 'Programado'
            ]);

            return [
                'success' => true,
                'message' => 'Agenda criada com sucesso!',
                'agenda_id' => $this->pdo->lastInsertId()
            ];

        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao criar agenda: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Atualizar agenda
     */
    public function atualizar($id, $dados) {
        try {
            $sql = "UPDATE agenda_treinamentos SET
                    turma = ?,
                    data_inicio = ?,
                    data_fim = ?,
                    hora_inicio = ?,
                    hora_fim = ?,
                    dias_semana = ?,
                    local = ?,
                    vagas_total = ?,
                    instrutor = ?,
                    observacoes = ?,
                    status = ?
                    WHERE id = ?";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $dados['turma'] ?? null,
                $dados['data_inicio'],
                $dados['data_fim'] ?? null,
                $dados['hora_inicio'] ?? null,
                $dados['hora_fim'] ?? null,
                $dados['dias_semana'] ?? null,
                $dados['local'] ?? null,
                $dados['vagas_total'] ?? 0,
                $dados['instrutor'] ?? null,
                $dados['observacoes'] ?? null,
                $dados['status'] ?? 'Programado',
                $id
            ]);

            return [
                'success' => true,
                'message' => 'Agenda atualizada com sucesso!'
            ];

        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao atualizar agenda: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Deletar agenda
     */
    public function deletar($id) {
        try {
            // Verifica se há participantes vinculados
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM treinamento_participantes WHERE agenda_id = ?");
            $stmt->execute([$id]);
            $totalParticipantes = $stmt->fetchColumn();

            if ($totalParticipantes > 0) {
                return [
                    'success' => false,
                    'message' => "Não é possível excluir esta agenda pois há {$totalParticipantes} participante(s) vinculado(s)"
                ];
            }

            $stmt = $this->pdo->prepare("DELETE FROM agenda_treinamentos WHERE id = ?");
            $stmt->execute([$id]);

            return [
                'success' => true,
                'message' => 'Agenda excluída com sucesso!'
            ];

        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao deletar agenda: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Atualizar contagem de vagas ocupadas
     */
    public function atualizarVagasOcupadas($agendaId) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM treinamento_participantes
            WHERE agenda_id = ?
        ");
        $stmt->execute([$agendaId]);
        $ocupadas = $stmt->fetchColumn();

        $stmt = $this->pdo->prepare("UPDATE agenda_treinamentos SET vagas_ocupadas = ? WHERE id = ?");
        $stmt->execute([$ocupadas, $agendaId]);

        return $ocupadas;
    }

    /**
     * Verificar disponibilidade de vagas
     */
    public function temVagasDisponiveis($agendaId) {
        $agenda = $this->buscarPorId($agendaId);

        if (!$agenda || $agenda['vagas_total'] == 0) {
            return true; // Sem limite de vagas
        }

        return $agenda['vagas_ocupadas'] < $agenda['vagas_total'];
    }

    /**
     * Listar próximas agendas (todas os treinamentos)
     */
    public function listarProximas($limite = 10) {
        $sql = "SELECT
                    a.*,
                    t.nome as treinamento_nome,
                    (SELECT COUNT(*) FROM treinamento_participantes WHERE agenda_id = a.id) as total_inscritos
                FROM agenda_treinamentos a
                INNER JOIN treinamentos t ON a.treinamento_id = t.id
                WHERE a.data_inicio >= CURDATE()
                AND a.status IN ('Programado', 'Em Andamento')
                ORDER BY a.data_inicio ASC
                LIMIT ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limite]);
        return $stmt->fetchAll();
    }

    /**
     * Contar total de agendas por status
     */
    public function contarPorStatus($treinamentoId = null) {
        $sql = "SELECT status, COUNT(*) as total
                FROM agenda_treinamentos";

        if ($treinamentoId) {
            $sql .= " WHERE treinamento_id = ?";
        }

        $sql .= " GROUP BY status";

        $stmt = $this->pdo->prepare($sql);

        if ($treinamentoId) {
            $stmt->execute([$treinamentoId]);
        } else {
            $stmt->execute();
        }

        $resultado = [];
        while ($row = $stmt->fetch()) {
            $resultado[$row['status']] = $row['total'];
        }

        return $resultado;
    }

    /**
     * Buscar agenda por turma
     */
    public function buscarPorTurma($treinamentoId, $turma) {
        $sql = "SELECT * FROM agenda_treinamentos
                WHERE treinamento_id = ? AND turma = ?
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$treinamentoId, $turma]);
        return $stmt->fetch();
    }
}
