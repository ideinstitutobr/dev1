<?php
/**
 * Model: FormCompartilhamento
 * Gerencia compartilhamento de formulários entre usuários
 */

require_once __DIR__ . '/../classes/Database.php';

class FormCompartilhamento {
    private $db;
    private $pdo;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    /**
     * Compartilha formulário com usuário
     */
    public function compartilhar($dados) {
        // Verificar se já existe compartilhamento
        if ($this->jaCompartilhado($dados['formulario_id'], $dados['usuario_id'])) {
            throw new Exception('Formulário já compartilhado com este usuário');
        }

        $sql = "INSERT INTO form_compartilhamentos
                (formulario_id, usuario_id, nivel_permissao)
                VALUES (?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $dados['formulario_id'],
            $dados['usuario_id'],
            $dados['nivel_permissao'] ?? 'visualizar'
        ]);

        return $this->pdo->lastInsertId();
    }

    /**
     * Busca compartilhamento por ID
     */
    public function buscarPorId($id) {
        $sql = "SELECT c.*,
                       u.nome as usuario_nome,
                       u.email as usuario_email,
                       f.titulo as formulario_titulo
                FROM form_compartilhamentos c
                INNER JOIN usuarios_sistema u ON c.usuario_id = u.id
                INNER JOIN formularios_dinamicos f ON c.formulario_id = f.id
                WHERE c.id = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Lista compartilhamentos de um formulário
     */
    public function listarPorFormulario($formularioId) {
        $sql = "SELECT c.*,
                       u.nome as usuario_nome,
                       u.email as usuario_email,
                       u.nivel_acesso as usuario_nivel
                FROM form_compartilhamentos c
                INNER JOIN usuarios_sistema u ON c.usuario_id = u.id
                WHERE c.formulario_id = ?
                ORDER BY c.compartilhado_em DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$formularioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lista formulários compartilhados com usuário
     */
    public function listarPorUsuario($usuarioId, $nivel = null) {
        $sql = "SELECT c.*,
                       f.titulo,
                       f.descricao,
                       f.status,
                       u.nome as proprietario_nome,
                       u.email as proprietario_email
                FROM form_compartilhamentos c
                INNER JOIN formularios_dinamicos f ON c.formulario_id = f.id
                INNER JOIN usuarios_sistema u ON f.usuario_id = u.id
                WHERE c.usuario_id = ?";

        $params = [$usuarioId];

        if ($nivel) {
            $sql .= " AND c.nivel_permissao = ?";
            $params[] = $nivel;
        }

        $sql .= " ORDER BY c.compartilhado_em DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Atualiza nível de permissão
     */
    public function atualizarPermissao($id, $nivelPermissao) {
        $niveisPermitidos = ['visualizar', 'editar', 'gerenciar'];

        if (!in_array($nivelPermissao, $niveisPermitidos)) {
            throw new Exception('Nível de permissão inválido');
        }

        $sql = "UPDATE form_compartilhamentos SET nivel_permissao = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$nivelPermissao, $id]);
    }

    /**
     * Remove compartilhamento
     */
    public function remover($id) {
        $sql = "DELETE FROM form_compartilhamentos WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Remove compartilhamento por formulário e usuário
     */
    public function removerPorFormularioEUsuario($formularioId, $usuarioId) {
        $sql = "DELETE FROM form_compartilhamentos WHERE formulario_id = ? AND usuario_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$formularioId, $usuarioId]);
    }

    /**
     * Remove todos os compartilhamentos de um formulário
     */
    public function removerTodosPorFormulario($formularioId) {
        $sql = "DELETE FROM form_compartilhamentos WHERE formulario_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$formularioId]);
    }

    /**
     * Verifica se formulário já está compartilhado com usuário
     */
    public function jaCompartilhado($formularioId, $usuarioId) {
        $sql = "SELECT COUNT(*) as total FROM form_compartilhamentos
                WHERE formulario_id = ? AND usuario_id = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$formularioId, $usuarioId]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado['total'] > 0;
    }

    /**
     * Verifica permissão do usuário no formulário
     */
    public function verificarPermissao($formularioId, $usuarioId, $nivelRequerido = 'visualizar') {
        $sql = "SELECT nivel_permissao FROM form_compartilhamentos
                WHERE formulario_id = ? AND usuario_id = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$formularioId, $usuarioId]);
        $compartilhamento = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$compartilhamento) {
            return false;
        }

        $nivelUsuario = $compartilhamento['nivel_permissao'];

        // Hierarquia de permissões
        $hierarquia = [
            'visualizar' => 1,
            'editar' => 2,
            'gerenciar' => 3
        ];

        $nivelUsuarioValor = $hierarquia[$nivelUsuario] ?? 0;
        $nivelRequeridoValor = $hierarquia[$nivelRequerido] ?? 0;

        return $nivelUsuarioValor >= $nivelRequeridoValor;
    }

    /**
     * Compartilha com múltiplos usuários
     */
    public function compartilharComMultiplos($formularioId, $usuariosIds, $nivelPermissao = 'visualizar') {
        $compartilhados = [];
        $erros = [];

        foreach ($usuariosIds as $usuarioId) {
            try {
                $compartilhados[] = $this->compartilhar([
                    'formulario_id' => $formularioId,
                    'usuario_id' => $usuarioId,
                    'nivel_permissao' => $nivelPermissao
                ]);
            } catch (Exception $e) {
                $erros[] = [
                    'usuario_id' => $usuarioId,
                    'erro' => $e->getMessage()
                ];
            }
        }

        return [
            'sucesso' => $compartilhados,
            'erros' => $erros,
            'total_sucesso' => count($compartilhados),
            'total_erros' => count($erros)
        ];
    }

    /**
     * Compartilha formulário por email
     */
    public function compartilharPorEmail($formularioId, $email, $nivelPermissao = 'visualizar') {
        // Buscar usuário por email
        $sql = "SELECT id FROM usuarios_sistema WHERE email = ? AND ativo = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            throw new Exception('Usuário não encontrado ou inativo');
        }

        return $this->compartilhar([
            'formulario_id' => $formularioId,
            'usuario_id' => $usuario['id'],
            'nivel_permissao' => $nivelPermissao
        ]);
    }

    /**
     * Obtém estatísticas de compartilhamento
     */
    public function obterEstatisticas($formularioId) {
        $sql = "SELECT
                    COUNT(*) as total_compartilhamentos,
                    SUM(CASE WHEN nivel_permissao = 'visualizar' THEN 1 ELSE 0 END) as visualizadores,
                    SUM(CASE WHEN nivel_permissao = 'editar' THEN 1 ELSE 0 END) as editores,
                    SUM(CASE WHEN nivel_permissao = 'gerenciar' THEN 1 ELSE 0 END) as gerenciadores
                FROM form_compartilhamentos
                WHERE formulario_id = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$formularioId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Busca usuários disponíveis para compartilhar (que ainda não têm acesso)
     */
    public function buscarUsuariosDisponiveis($formularioId, $busca = null) {
        $sql = "SELECT u.id, u.nome, u.email, u.nivel_acesso
                FROM usuarios_sistema u
                WHERE u.ativo = 1
                AND u.id NOT IN (
                    SELECT usuario_id FROM form_compartilhamentos WHERE formulario_id = ?
                )
                AND u.id != (SELECT usuario_id FROM formularios_dinamicos WHERE id = ?)";

        $params = [$formularioId, $formularioId];

        if ($busca) {
            $sql .= " AND (u.nome LIKE ? OR u.email LIKE ?)";
            $termo = '%' . $busca . '%';
            $params[] = $termo;
            $params[] = $termo;
        }

        $sql .= " ORDER BY u.nome LIMIT 50";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Clona compartilhamentos de um formulário para outro
     */
    public function clonarCompartilhamentos($formularioOrigemId, $formularioDestinoId) {
        $compartilhamentos = $this->listarPorFormulario($formularioOrigemId);
        $clonados = 0;

        foreach ($compartilhamentos as $comp) {
            try {
                $this->compartilhar([
                    'formulario_id' => $formularioDestinoId,
                    'usuario_id' => $comp['usuario_id'],
                    'nivel_permissao' => $comp['nivel_permissao']
                ]);
                $clonados++;
            } catch (Exception $e) {
                // Ignorar erro se usuário já tem acesso
                continue;
            }
        }

        return $clonados;
    }
}
