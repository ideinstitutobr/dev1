<?php
/**
 * Model: FormSecao
 * Gerencia seções dos formulários dinâmicos
 */

require_once __DIR__ . '/../classes/Database.php';

class FormSecao {
    private $db;
    private $pdo;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    /**
     * Cria uma nova seção
     */
    public function criar($dados) {
        // Determinar ordem automaticamente se não fornecida
        if (!isset($dados['ordem'])) {
            $dados['ordem'] = $this->obterProximaOrdem($dados['formulario_id']);
        }

        $sql = "INSERT INTO form_secoes
                (formulario_id, titulo, descricao, ordem, peso, cor, icone, visivel)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $dados['formulario_id'],
            $dados['titulo'],
            $dados['descricao'] ?? null,
            $dados['ordem'],
            $dados['peso'] ?? 1.00,
            $dados['cor'] ?? '#007bff',
            $dados['icone'] ?? null,
            $dados['visivel'] ?? 1
        ]);

        return $this->pdo->lastInsertId();
    }

    /**
     * Busca seção por ID
     */
    public function buscarPorId($id) {
        $sql = "SELECT s.*,
                       COUNT(DISTINCT p.id) as total_perguntas
                FROM form_secoes s
                LEFT JOIN form_perguntas p ON s.id = p.secao_id
                WHERE s.id = ?
                GROUP BY s.id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Lista seções de um formulário
     */
    public function listarPorFormulario($formularioId, $apenasVisiveis = false) {
        $sql = "SELECT s.*,
                       COUNT(DISTINCT p.id) as total_perguntas
                FROM form_secoes s
                LEFT JOIN form_perguntas p ON s.id = p.secao_id
                WHERE s.formulario_id = ?";

        if ($apenasVisiveis) {
            $sql .= " AND s.visivel = 1";
        }

        $sql .= " GROUP BY s.id ORDER BY s.ordem, s.id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$formularioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Atualiza seção
     */
    public function atualizar($id, $dados) {
        $campos = [];
        $valores = [];

        $camposPermitidos = ['titulo', 'descricao', 'ordem', 'peso', 'cor', 'icone', 'visivel'];

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

        $sql = "UPDATE form_secoes SET " . implode(', ', $campos) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($valores);
    }

    /**
     * Deleta seção
     */
    public function deletar($id) {
        $sql = "DELETE FROM form_secoes WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Reordena seções
     */
    public function reordenar($secoesOrdenadas) {
        $this->pdo->beginTransaction();

        try {
            $sql = "UPDATE form_secoes SET ordem = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);

            foreach ($secoesOrdenadas as $ordem => $secaoId) {
                $stmt->execute([$ordem + 1, $secaoId]);
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
    private function obterProximaOrdem($formularioId) {
        $sql = "SELECT COALESCE(MAX(ordem), 0) + 1 as proxima_ordem
                FROM form_secoes
                WHERE formulario_id = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$formularioId]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado['proxima_ordem'];
    }

    /**
     * Duplica seção
     */
    public function duplicar($secaoId, $novoFormularioId = null) {
        $original = $this->buscarPorId($secaoId);
        if (!$original) {
            throw new Exception('Seção não encontrada');
        }

        $novosDados = [
            'formulario_id' => $novoFormularioId ?? $original['formulario_id'],
            'titulo' => $original['titulo'],
            'descricao' => $original['descricao'],
            'peso' => $original['peso'],
            'cor' => $original['cor'],
            'icone' => $original['icone'],
            'visivel' => $original['visivel']
        ];

        return $this->criar($novosDados);
    }
}
