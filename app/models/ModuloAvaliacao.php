<?php
/**
 * Model: ModuloAvaliacao
 * Gerencia módulos de avaliação (setores)
 */

require_once __DIR__ . '/../classes/Database.php';

class ModuloAvaliacao {
    private $db;
    private $pdo;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    /**
     * Lista todos os módulos ativos
     */
    public function listarAtivos() {
        $sql = "SELECT * FROM modulos_avaliacao WHERE ativo = 1 ORDER BY ordem, nome";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lista todos os módulos
     */
    public function listar($params = []) {
        $page = $params['page'] ?? 1;
        $perPage = $params['per_page'] ?? 20;
        $offset = ($page - 1) * $perPage;

        // Conta total
        $sqlCount = "SELECT COUNT(*) as total FROM modulos_avaliacao";
        $stmtCount = $this->pdo->query($sqlCount);
        $total = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];

        // Busca registros
        $sql = "SELECT * FROM modulos_avaliacao ORDER BY ordem, nome LIMIT ? OFFSET ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$perPage, $offset]);
        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'registros' => $registros,
            'total' => $total,
            'pagina_atual' => $page,
            'total_paginas' => ceil($total / $perPage)
        ];
    }

    /**
     * Busca módulo por ID
     */
    public function buscarPorId($id) {
        $sql = "SELECT * FROM modulos_avaliacao WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Cria novo módulo
     */
    public function criar($dados) {
        $sql = "INSERT INTO modulos_avaliacao
                (nome, descricao, total_perguntas, peso_por_pergunta, ordem, ativo)
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $dados['nome'],
            $dados['descricao'] ?? null,
            $dados['total_perguntas'],
            $dados['peso_por_pergunta'],
            $dados['ordem'] ?? 0,
            $dados['ativo'] ?? 1
        ]);

        return $this->pdo->lastInsertId();
    }

    /**
     * Atualiza módulo
     */
    public function atualizar($id, $dados) {
        $campos = [];
        $valores = [];

        foreach ($dados as $campo => $valor) {
            $campos[] = "{$campo} = ?";
            $valores[] = $valor;
        }

        $valores[] = $id;

        $sql = "UPDATE modulos_avaliacao SET " . implode(', ', $campos) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($valores);
    }

    /**
     * Deleta módulo
     */
    public function deletar($id) {
        $sql = "DELETE FROM modulos_avaliacao WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Obtém total de perguntas do módulo
     */
    public function contarPerguntas($moduloId) {
        $sql = "SELECT COUNT(*) as total FROM perguntas WHERE modulo_id = ? AND ativo = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$moduloId]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['total'];
    }
}
