<?php
/**
 * Model: Loja
 * Gerencia lojas/unidades
 */

require_once __DIR__ . '/../classes/Database.php';

class Loja {
    private $db;
    private $pdo;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    /**
     * Lista todas as lojas ativas
     */
    public function listarAtivas() {
        $sql = "SELECT * FROM lojas WHERE ativo = 1 ORDER BY nome";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lista todas as lojas com paginação e filtros
     */
    public function listar($params = []) {
        $page = $params['page'] ?? 1;
        $perPage = $params['per_page'] ?? 20;
        $search = $params['search'] ?? '';
        $ativo = $params['ativo'] ?? '';

        $offset = ($page - 1) * $perPage;

        $where = ['1=1'];
        $bindings = [];

        if (!empty($search)) {
            $where[] = "(nome LIKE ? OR codigo LIKE ? OR cidade LIKE ?)";
            $searchTerm = "%{$search}%";
            $bindings[] = $searchTerm;
            $bindings[] = $searchTerm;
            $bindings[] = $searchTerm;
        }

        if ($ativo !== '') {
            $where[] = "ativo = ?";
            $bindings[] = $ativo;
        }

        $whereClause = implode(' AND ', $where);

        // Conta total
        $sqlCount = "SELECT COUNT(*) as total FROM lojas WHERE {$whereClause}";
        $stmtCount = $this->pdo->prepare($sqlCount);
        $stmtCount->execute($bindings);
        $total = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];

        // Busca registros
        $sql = "SELECT * FROM lojas WHERE {$whereClause} ORDER BY nome LIMIT ? OFFSET ?";
        $bindingsWithLimit = array_merge($bindings, [$perPage, $offset]);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindingsWithLimit);
        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'registros' => $registros,
            'total' => $total,
            'pagina_atual' => $page,
            'total_paginas' => ceil($total / $perPage)
        ];
    }

    /**
     * Busca loja por ID
     */
    public function buscarPorId($id) {
        $sql = "SELECT * FROM lojas WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Busca loja por código
     */
    public function buscarPorCodigo($codigo) {
        $sql = "SELECT * FROM lojas WHERE codigo = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$codigo]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Cria nova loja
     */
    public function criar($dados) {
        $sql = "INSERT INTO lojas (nome, codigo, endereco, cidade, estado, ativo)
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $dados['nome'],
            $dados['codigo'] ?? null,
            $dados['endereco'] ?? null,
            $dados['cidade'] ?? null,
            $dados['estado'] ?? null,
            $dados['ativo'] ?? 1
        ]);

        return $this->pdo->lastInsertId();
    }

    /**
     * Atualiza loja
     */
    public function atualizar($id, $dados) {
        $campos = [];
        $valores = [];

        foreach ($dados as $campo => $valor) {
            $campos[] = "{$campo} = ?";
            $valores[] = $valor;
        }

        $valores[] = $id;

        $sql = "UPDATE lojas SET " . implode(', ', $campos) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($valores);
    }

    /**
     * Deleta loja
     */
    public function deletar($id) {
        $sql = "DELETE FROM lojas WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Obtém estatísticas da loja
     */
    public function obterEstatisticas($lojaId, $dataInicio = null, $dataFim = null) {
        $where = ["l.id = ?", "c.status = 'finalizado'"];
        $bindings = [$lojaId];

        if ($dataInicio) {
            $where[] = "c.data_avaliacao >= ?";
            $bindings[] = $dataInicio;
        }

        if ($dataFim) {
            $where[] = "c.data_avaliacao <= ?";
            $bindings[] = $dataFim;
        }

        $whereClause = implode(' AND ', $where);

        $sql = "SELECT
                    COUNT(c.id) as total_avaliacoes,
                    AVG(c.percentual) as media_percentual,
                    SUM(CASE WHEN c.atingiu_meta = 1 THEN 1 ELSE 0 END) as total_aprovados,
                    MAX(c.percentual) as melhor_nota,
                    MIN(c.percentual) as pior_nota
                FROM lojas l
                LEFT JOIN checklists c ON l.id = c.loja_id
                WHERE {$whereClause}
                GROUP BY l.id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
