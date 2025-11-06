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
                ORDER BY a.data_inicio ASC, a.hora_inicio ASC";

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
                    (treinamento_id, data_inicio, data_fim, hora_inicio, hora_fim,
                     local, vagas_disponiveis, instrutor, observacoes, carga_horaria_dia)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $dados['treinamento_id'],
                $dados['data_inicio'],
                $dados['data_fim'] ?? $dados['data_inicio'],
                $dados['hora_inicio'] ?? null,
                $dados['hora_fim'] ?? null,
                $dados['local'] ?? null,
                $dados['vagas_disponiveis'] ?? null,
                $dados['instrutor'] ?? null,
                $dados['observacoes'] ?? null,
                $dados['carga_horaria_dia'] ?? null
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
                    data_inicio = ?,
                    data_fim = ?,
                    hora_inicio = ?,
                    hora_fim = ?,
                    local = ?,
                    vagas_disponiveis = ?,
                    instrutor = ?,
                    observacoes = ?,
                    carga_horaria_dia = ?
                    WHERE id = ?";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $dados['data_inicio'],
                $dados['data_fim'] ?? $dados['data_inicio'],
                $dados['hora_inicio'] ?? null,
                $dados['hora_fim'] ?? null,
                $dados['local'] ?? null,
                $dados['vagas_disponiveis'] ?? null,
                $dados['instrutor'] ?? null,
                $dados['observacoes'] ?? null,
                $dados['carga_horaria_dia'] ?? null,
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
     * Verificar disponibilidade de vagas
     */
    public function temVagasDisponiveis($agendaId) {
        $agenda = $this->buscarPorId($agendaId);

        if (!$agenda || !$agenda['vagas_disponiveis'] || $agenda['vagas_disponiveis'] == 0) {
            return true; // Sem limite de vagas
        }

        // Conta quantos participantes estão vinculados
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM treinamento_participantes WHERE agenda_id = ?");
        $stmt->execute([$agendaId]);
        $ocupadas = $stmt->fetchColumn();

        return $ocupadas < $agenda['vagas_disponiveis'];
    }

    /**
     * Contar vagas ocupadas de uma agenda
     */
    public function contarVagasOcupadas($agendaId) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM treinamento_participantes WHERE agenda_id = ?");
        $stmt->execute([$agendaId]);
        return $stmt->fetchColumn();
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
                ORDER BY a.data_inicio ASC
                LIMIT ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limite]);
        return $stmt->fetchAll();
    }

    /**
     * Contar total de agendas
     */
    public function contarTotal($treinamentoId = null) {
        if ($treinamentoId) {
            $sql = "SELECT COUNT(*) FROM agenda_treinamentos WHERE treinamento_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$treinamentoId]);
        } else {
            $sql = "SELECT COUNT(*) FROM agenda_treinamentos";
            $stmt = $this->pdo->query($sql);
        }

        return $stmt->fetchColumn();
    }
}
