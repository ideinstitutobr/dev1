<?php
/**
 * Model: RespostaChecklist
 * Gerencia respostas de checklists
 */

require_once __DIR__ . '/../classes/Database.php';

class RespostaChecklist {
    private $db;
    private $pdo;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    /**
     * Salva resposta e calcula pontuação automaticamente
     */
    public function salvarResposta($dados) {
        // Buscar informações do módulo para calcular peso correto
        $sql = "SELECT p.*, m.total_perguntas
                FROM perguntas p
                INNER JOIN modulos_avaliacao m ON p.modulo_id = m.id
                WHERE p.id = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$dados['pergunta_id']]);
        $pergunta = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$pergunta) {
            throw new Exception('Pergunta não encontrada');
        }

        $totalPerguntas = $pergunta['total_perguntas'];
        $estrelas = $dados['estrelas'];

        // Calcular pontuação baseado no número de perguntas do módulo
        $pontuacao = $this->calcularPontuacao($estrelas, $totalPerguntas);

        $dados['pontuacao'] = $pontuacao;

        // Verificar se já existe resposta
        $sqlExiste = "SELECT id FROM respostas_checklist
                      WHERE checklist_id = ? AND pergunta_id = ?";

        $stmtExiste = $this->pdo->prepare($sqlExiste);
        $stmtExiste->execute([$dados['checklist_id'], $dados['pergunta_id']]);
        $existente = $stmtExiste->fetch(PDO::FETCH_ASSOC);

        if ($existente) {
            // Atualizar resposta existente
            $sqlUpdate = "UPDATE respostas_checklist
                          SET estrelas = ?, pontuacao = ?, observacao = ?, foto_evidencia = ?
                          WHERE id = ?";

            $stmtUpdate = $this->pdo->prepare($sqlUpdate);
            $stmtUpdate->execute([
                $dados['estrelas'],
                $dados['pontuacao'],
                $dados['observacao'] ?? null,
                $dados['foto_evidencia'] ?? null,
                $existente['id']
            ]);

            return $existente['id'];
        } else {
            // Criar nova resposta
            $sqlInsert = "INSERT INTO respostas_checklist
                          (checklist_id, pergunta_id, estrelas, pontuacao, observacao, foto_evidencia)
                          VALUES (?, ?, ?, ?, ?, ?)";

            $stmtInsert = $this->pdo->prepare($sqlInsert);
            $stmtInsert->execute([
                $dados['checklist_id'],
                $dados['pergunta_id'],
                $dados['estrelas'],
                $dados['pontuacao'],
                $dados['observacao'] ?? null,
                $dados['foto_evidencia'] ?? null
            ]);

            return $this->pdo->lastInsertId();
        }
    }

    /**
     * Calcula pontuação baseado no número de estrelas e total de perguntas
     */
    private function calcularPontuacao($estrelas, $totalPerguntas) {
        $pesosKey = "peso_{$totalPerguntas}_perguntas_{$estrelas}_estrela";
        if ($estrelas > 1) {
            $pesosKey .= 's';
        }

        // Buscar peso da configuração
        $sql = "SELECT valor FROM configuracoes_sistema WHERE chave = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$pesosKey]);
        $config = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($config) {
            return (float) $config['valor'];
        }

        // Fallback: cálculo proporcional
        $pontuacaoMaxima = 5 / $totalPerguntas;
        return ($estrelas / 5) * $pontuacaoMaxima;
    }

    /**
     * Obtém respostas de um checklist com informações das perguntas
     */
    public function obterRespostasCompletas($checklistId) {
        $sql = "SELECT
                    r.*,
                    p.texto as pergunta_texto,
                    p.descricao as pergunta_descricao,
                    p.ordem as pergunta_ordem,
                    m.nome as modulo_nome
                FROM respostas_checklist r
                INNER JOIN perguntas p ON r.pergunta_id = p.id
                INNER JOIN modulos_avaliacao m ON p.modulo_id = m.id
                WHERE r.checklist_id = ?
                ORDER BY p.ordem";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$checklistId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Adiciona foto à resposta
     */
    public function adicionarFoto($respostaId, $caminhoFoto, $legenda = null) {
        $sql = "INSERT INTO fotos_checklist (resposta_id, caminho, legenda)
                VALUES (?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$respostaId, $caminhoFoto, $legenda]);
    }

    /**
     * Busca fotos de uma resposta
     */
    public function buscarFotos($respostaId) {
        $sql = "SELECT * FROM fotos_checklist WHERE resposta_id = ? ORDER BY created_at";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$respostaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca resposta por ID
     */
    public function buscarPorId($id) {
        $sql = "SELECT * FROM respostas_checklist WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Deleta resposta
     */
    public function deletar($id) {
        $sql = "DELETE FROM respostas_checklist WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }
}
