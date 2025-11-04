<?php
/**
 * Model: Frequencia
 * Gerencia registro de frequência em sessões de treinamento
 */

class Frequencia {
    private $db;
    private $pdo;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    /**
     * ==========================================
     * SESSÕES
     * ==========================================
     */

    /**
     * Criar nova sessão
     */
    public function criarSessao($dados) {
        $sql = "INSERT INTO treinamento_sessoes
                (treinamento_id, nome, data_sessao, hora_inicio, hora_fim, local, observacoes, qr_token)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        $qrToken = $this->gerarTokenQR();

        $result = $stmt->execute([
            $dados['treinamento_id'],
            $dados['nome'],
            $dados['data_sessao'],
            $dados['hora_inicio'] ?? null,
            $dados['hora_fim'] ?? null,
            $dados['local'] ?? null,
            $dados['observacoes'] ?? null,
            $qrToken
        ]);

        if ($result) {
            $sessaoId = $this->pdo->lastInsertId();

            // Criar registros de frequência para todos os participantes do treinamento
            $this->criarRegistrosFrequenciaParaSessao($sessaoId, $dados['treinamento_id']);

            return $sessaoId;
        }

        return false;
    }

    /**
     * Listar sessões de um treinamento
     */
    public function listarSessoesPorTreinamento($treinamentoId) {
        $sql = "SELECT
                    s.*,
                    t.nome as treinamento_nome,
                    (SELECT COUNT(*) FROM frequencia f WHERE f.sessao_id = s.id AND f.status = 'Presente') as total_presentes,
                    (SELECT COUNT(*) FROM frequencia f WHERE f.sessao_id = s.id) as total_participantes
                FROM treinamento_sessoes s
                INNER JOIN treinamentos t ON s.treinamento_id = t.id
                WHERE s.treinamento_id = ?
                ORDER BY s.data_sessao DESC, s.hora_inicio DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$treinamentoId]);
        return $stmt->fetchAll();
    }

    /**
     * Buscar sessão por ID
     */
    public function buscarSessao($id) {
        $sql = "SELECT
                    s.*,
                    t.nome as treinamento_nome,
                    t.tipo as treinamento_tipo,
                    (SELECT COUNT(*) FROM frequencia f WHERE f.sessao_id = s.id AND f.status = 'Presente') as total_presentes,
                    (SELECT COUNT(*) FROM frequencia f WHERE f.sessao_id = s.id AND f.status = 'Ausente') as total_ausentes,
                    (SELECT COUNT(*) FROM frequencia f WHERE f.sessao_id = s.id) as total_participantes
                FROM treinamento_sessoes s
                INNER JOIN treinamentos t ON s.treinamento_id = t.id
                WHERE s.id = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Atualizar sessão
     */
    public function atualizarSessao($id, $dados) {
        $sql = "UPDATE treinamento_sessoes
                SET nome = ?,
                    data_sessao = ?,
                    hora_inicio = ?,
                    hora_fim = ?,
                    local = ?,
                    observacoes = ?
                WHERE id = ?";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $dados['nome'],
            $dados['data_sessao'],
            $dados['hora_inicio'] ?? null,
            $dados['hora_fim'] ?? null,
            $dados['local'] ?? null,
            $dados['observacoes'] ?? null,
            $id
        ]);
    }

    /**
     * Deletar sessão
     */
    public function deletarSessao($id) {
        // A frequência é deletada automaticamente por CASCADE
        $sql = "DELETE FROM treinamento_sessoes WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * ==========================================
     * FREQUÊNCIA
     * ==========================================
     */

    /**
     * Criar registros de frequência para todos os participantes de uma sessão
     */
    private function criarRegistrosFrequenciaParaSessao($sessaoId, $treinamentoId) {
        $sql = "INSERT INTO frequencia (sessao_id, participante_id, status)
                SELECT ?, tp.id, 'Ausente'
                FROM treinamento_participantes tp
                WHERE tp.treinamento_id = ?";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$sessaoId, $treinamentoId]);
    }

    /**
     * Listar frequência de uma sessão
     */
    public function listarFrequenciaPorSessao($sessaoId) {
        $sql = "SELECT
                    f.*,
                    c.nome as colaborador_nome,
                    c.cargo,
                    c.departamento,
                    c.email
                FROM frequencia f
                INNER JOIN treinamento_participantes tp ON f.participante_id = tp.id
                INNER JOIN colaboradores c ON tp.colaborador_id = c.id
                WHERE f.sessao_id = ?
                ORDER BY c.nome";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$sessaoId]);
        return $stmt->fetchAll();
    }

    /**
     * Registrar presença
     */
    public function registrarPresenca($frequenciaId, $status, $usuarioId, $observacoes = null, $justificativa = null) {
        $sql = "UPDATE frequencia
                SET status = ?,
                    hora_checkin = NOW(),
                    observacoes = ?,
                    justificativa = ?,
                    registrado_por = ?
                WHERE id = ?";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $status,
            $observacoes,
            $justificativa,
            $usuarioId,
            $frequenciaId
        ]);
    }

    /**
     * Check-in rápido por QR Code
     */
    public function checkinPorQRCode($qrToken, $colaboradorId) {
        try {
            $this->pdo->beginTransaction();

            // Buscar sessão pelo QR token
            $sql = "SELECT id, treinamento_id FROM treinamento_sessoes WHERE qr_token = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$qrToken]);
            $sessao = $stmt->fetch();

            if (!$sessao) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Sessão não encontrada'];
            }

            // Buscar participante
            $sql = "SELECT tp.id
                    FROM treinamento_participantes tp
                    WHERE tp.treinamento_id = ?
                    AND tp.colaborador_id = ?";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$sessao['treinamento_id'], $colaboradorId]);
            $participante = $stmt->fetch();

            if (!$participante) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Participante não encontrado neste treinamento'];
            }

            // Buscar registro de frequência
            $sql = "SELECT id, status FROM frequencia
                    WHERE sessao_id = ? AND participante_id = ?";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$sessao['id'], $participante['id']]);
            $frequencia = $stmt->fetch();

            if (!$frequencia) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Registro de frequência não encontrado'];
            }

            if ($frequencia['status'] === 'Presente') {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Check-in já realizado'];
            }

            // Atualizar frequência
            $sql = "UPDATE frequencia
                    SET status = 'Presente',
                        hora_checkin = NOW()
                    WHERE id = ?";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$frequencia['id']]);

            $this->pdo->commit();
            return ['success' => true, 'message' => 'Check-in realizado com sucesso'];

        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Erro ao realizar check-in: ' . $e->getMessage()];
        }
    }

    /**
     * Registrar presença múltipla
     */
    public function registrarPresencaMultipla($sessaoId, $presencas, $usuarioId) {
        try {
            $this->pdo->beginTransaction();

            foreach ($presencas as $frequenciaId => $status) {
                $this->registrarPresenca($frequenciaId, $status, $usuarioId);
            }

            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    /**
     * ==========================================
     * RELATÓRIOS
     * ==========================================
     */

    /**
     * Relatório de frequência por treinamento
     */
    public function relatorioFrequenciaPorTreinamento($treinamentoId) {
        $sql = "SELECT
                    c.nome as colaborador_nome,
                    c.cargo,
                    c.departamento,
                    COUNT(f.id) as total_sessoes,
                    SUM(CASE WHEN f.status = 'Presente' THEN 1 ELSE 0 END) as total_presencas,
                    SUM(CASE WHEN f.status = 'Ausente' THEN 1 ELSE 0 END) as total_ausencias,
                    SUM(CASE WHEN f.status = 'Justificado' THEN 1 ELSE 0 END) as total_justificados,
                    ROUND((SUM(CASE WHEN f.status = 'Presente' THEN 1 ELSE 0 END) / COUNT(f.id) * 100), 1) as percentual_presenca
                FROM treinamento_participantes tp
                INNER JOIN colaboradores c ON tp.colaborador_id = c.id
                LEFT JOIN frequencia f ON f.participante_id = tp.id
                LEFT JOIN treinamento_sessoes s ON f.sessao_id = s.id
                WHERE tp.treinamento_id = ?
                GROUP BY tp.id, c.id
                ORDER BY c.nome";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$treinamentoId]);
        return $stmt->fetchAll();
    }

    /**
     * Estatísticas gerais de frequência
     */
    public function estatisticasGerais($treinamentoId = null) {
        $where = $treinamentoId ? "WHERE s.treinamento_id = ?" : "";

        $sql = "SELECT
                    COUNT(DISTINCT s.id) as total_sessoes,
                    COUNT(f.id) as total_registros,
                    SUM(CASE WHEN f.status = 'Presente' THEN 1 ELSE 0 END) as total_presencas,
                    SUM(CASE WHEN f.status = 'Ausente' THEN 1 ELSE 0 END) as total_ausencias,
                    SUM(CASE WHEN f.status = 'Justificado' THEN 1 ELSE 0 END) as total_justificados,
                    SUM(CASE WHEN f.status = 'Atrasado' THEN 1 ELSE 0 END) as total_atrasados,
                    ROUND((SUM(CASE WHEN f.status = 'Presente' THEN 1 ELSE 0 END) / COUNT(f.id) * 100), 1) as taxa_presenca
                FROM treinamento_sessoes s
                LEFT JOIN frequencia f ON f.sessao_id = s.id
                $where";

        $stmt = $this->pdo->prepare($sql);

        if ($treinamentoId) {
            $stmt->execute([$treinamentoId]);
        } else {
            $stmt->execute();
        }

        return $stmt->fetch();
    }

    /**
     * ==========================================
     * UTILITÁRIOS
     * ==========================================
     */

    /**
     * Gerar token único para QR Code
     */
    private function gerarTokenQR() {
        return bin2hex(random_bytes(16));
    }

    /**
     * Buscar sessão por QR token
     */
    public function buscarSessaoPorQR($qrToken) {
        $sql = "SELECT
                    s.*,
                    t.nome as treinamento_nome
                FROM treinamento_sessoes s
                INNER JOIN treinamentos t ON s.treinamento_id = t.id
                WHERE s.qr_token = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$qrToken]);
        return $stmt->fetch();
    }

    /**
     * Verificar se colaborador está vinculado ao treinamento
     */
    public function verificarVinculoColaborador($treinamentoId, $colaboradorId) {
        $sql = "SELECT id FROM treinamento_participantes
                WHERE treinamento_id = ?
                AND colaborador_id = ?
                AND status IN ('Confirmado', 'Presente')";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$treinamentoId, $colaboradorId]);
        return $stmt->fetch();
    }
}
