<?php
/**
 * Model: FormularioDinamico
 * Gerencia formulários dinâmicos criados no sistema
 */

require_once __DIR__ . '/../classes/Database.php';

class FormularioDinamico {
    private $db;
    private $pdo;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    /**
     * Cria um novo formulário dinâmico
     */
    public function criar($dados) {
        // Gerar slug único se não fornecido
        if (empty($dados['slug'])) {
            $dados['slug'] = $this->gerarSlug($dados['titulo']);
        }

        $sql = "INSERT INTO formularios_dinamicos
                (titulo, descricao, slug, usuario_id, status, tipo_pontuacao,
                 pontuacao_maxima, exibir_pontuacao, permite_multiplas_respostas,
                 data_inicio, data_fim)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $dados['titulo'],
            $dados['descricao'] ?? null,
            $dados['slug'],
            $dados['usuario_id'],
            $dados['status'] ?? 'rascunho',
            $dados['tipo_pontuacao'] ?? 'soma_simples',
            $dados['pontuacao_maxima'] ?? 0,
            $dados['exibir_pontuacao'] ?? 1,
            $dados['permite_multiplas_respostas'] ?? 0,
            $dados['data_inicio'] ?? null,
            $dados['data_fim'] ?? null
        ]);

        return $this->pdo->lastInsertId();
    }

    /**
     * Busca formulário por ID
     */
    public function buscarPorId($id) {
        $sql = "SELECT f.*,
                       u.nome as usuario_nome,
                       u.email as usuario_email,
                       COUNT(DISTINCT r.id) as total_respostas
                FROM formularios_dinamicos f
                INNER JOIN usuarios_sistema u ON f.usuario_id = u.id
                LEFT JOIN form_respostas r ON f.id = r.formulario_id AND r.status_resposta = 'concluida'
                WHERE f.id = ?
                GROUP BY f.id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Busca formulário por slug
     */
    public function buscarPorSlug($slug) {
        $sql = "SELECT * FROM formularios_dinamicos WHERE slug = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Lista formulários com filtros e paginação
     */
    public function listar($filtros = [], $params = []) {
        $page = $params['page'] ?? 1;
        $perPage = $params['per_page'] ?? 12;
        $offset = ($page - 1) * $perPage;

        $where = ['1=1'];
        $bindings = [];

        // Filtro por status
        if (!empty($filtros['status'])) {
            $where[] = "f.status = ?";
            $bindings[] = $filtros['status'];
        }

        // Filtro por usuário
        if (!empty($filtros['usuario_id'])) {
            $where[] = "f.usuario_id = ?";
            $bindings[] = $filtros['usuario_id'];
        }

        // Busca textual
        if (!empty($filtros['busca'])) {
            $where[] = "(f.titulo LIKE ? OR f.descricao LIKE ?)";
            $termo = '%' . $filtros['busca'] . '%';
            $bindings[] = $termo;
            $bindings[] = $termo;
        }

        $whereClause = implode(' AND ', $where);

        // Conta total
        $sqlCount = "SELECT COUNT(*) as total FROM formularios_dinamicos f WHERE {$whereClause}";
        $stmtCount = $this->pdo->prepare($sqlCount);
        $stmtCount->execute($bindings);
        $total = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];

        // Busca registros
        $sql = "SELECT
                    f.*,
                    u.nome as usuario_nome,
                    COUNT(DISTINCT r.id) as total_respostas,
                    COUNT(DISTINCT s.id) as total_secoes
                FROM formularios_dinamicos f
                INNER JOIN usuarios_sistema u ON f.usuario_id = u.id
                LEFT JOIN form_respostas r ON f.id = r.formulario_id AND r.status_resposta = 'concluida'
                LEFT JOIN form_secoes s ON f.id = s.formulario_id
                WHERE {$whereClause}
                GROUP BY f.id
                ORDER BY f.atualizado_em DESC
                LIMIT ? OFFSET ?";

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
     * Atualiza formulário
     */
    public function atualizar($id, $dados) {
        $campos = [];
        $valores = [];

        $camposPermitidos = [
            'titulo', 'descricao', 'slug', 'status', 'tipo_pontuacao',
            'pontuacao_maxima', 'exibir_pontuacao', 'permite_multiplas_respostas',
            'data_inicio', 'data_fim'
        ];

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

        $sql = "UPDATE formularios_dinamicos SET " . implode(', ', $campos) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($valores);
    }

    /**
     * Arquiva formulário
     */
    public function arquivar($id) {
        return $this->atualizar($id, ['status' => 'arquivado']);
    }

    /**
     * Deleta formulário
     */
    public function deletar($id) {
        $sql = "DELETE FROM formularios_dinamicos WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Duplica um formulário
     */
    public function duplicar($id) {
        // Buscar formulário original
        $original = $this->buscarPorId($id);
        if (!$original) {
            throw new Exception('Formulário não encontrado');
        }

        // Criar cópia
        $novosDados = [
            'titulo' => $original['titulo'] . ' (Cópia)',
            'descricao' => $original['descricao'],
            'slug' => $this->gerarSlug($original['titulo'] . ' copia'),
            'usuario_id' => $original['usuario_id'],
            'status' => 'rascunho',
            'tipo_pontuacao' => $original['tipo_pontuacao'],
            'pontuacao_maxima' => $original['pontuacao_maxima'],
            'exibir_pontuacao' => $original['exibir_pontuacao'],
            'permite_multiplas_respostas' => $original['permite_multiplas_respostas']
        ];

        $novoId = $this->criar($novosDados);

        // Copiar seções (será implementado em FormSecao)
        // Copiar perguntas (será implementado em FormPergunta)
        // Copiar faixas de pontuação (será implementado em FormFaixaPontuacao)

        return $novoId;
    }

    /**
     * Gera slug único a partir do título
     */
    private function gerarSlug($titulo) {
        // Converter para minúsculas
        $slug = strtolower($titulo);

        // Remover acentos
        $slug = iconv('UTF-8', 'ASCII//TRANSLIT', $slug);

        // Substituir espaços e caracteres especiais por hífen
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);

        // Remover hífens duplicados
        $slug = preg_replace('/-+/', '-', $slug);

        // Remover hífens do início e fim
        $slug = trim($slug, '-');

        // Verificar se slug já existe
        $slugOriginal = $slug;
        $contador = 1;
        while ($this->slugExiste($slug)) {
            $slug = $slugOriginal . '-' . $contador;
            $contador++;
        }

        return $slug;
    }

    /**
     * Verifica se slug já existe
     */
    private function slugExiste($slug) {
        $sql = "SELECT COUNT(*) as total FROM formularios_dinamicos WHERE slug = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$slug]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['total'] > 0;
    }

    /**
     * Verifica se formulário pertence a um usuário
     */
    public function pertenceAoUsuario($formularioId, $usuarioId) {
        $sql = "SELECT COUNT(*) as total FROM formularios_dinamicos WHERE id = ? AND usuario_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$formularioId, $usuarioId]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['total'] > 0;
    }

    /**
     * Obtém estatísticas do formulário
     */
    public function obterEstatisticas($formularioId) {
        $sql = "SELECT
                    COUNT(*) as total_respostas,
                    SUM(CASE WHEN status_resposta = 'concluida' THEN 1 ELSE 0 END) as respostas_completas,
                    SUM(CASE WHEN status_resposta = 'em_andamento' THEN 1 ELSE 0 END) as respostas_andamento,
                    AVG(pontuacao_total) as pontuacao_media,
                    AVG(percentual_acerto) as percentual_medio,
                    AVG(tempo_resposta) as tempo_medio
                FROM form_respostas
                WHERE formulario_id = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$formularioId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Recalcula pontuação máxima do formulário
     */
    public function recalcularPontuacaoMaxima($formularioId) {
        $sql = "SELECT SUM(p.pontuacao_maxima * p.peso * s.peso) as pontuacao_max
                FROM form_perguntas p
                INNER JOIN form_secoes s ON p.secao_id = s.id
                WHERE s.formulario_id = ? AND p.tem_pontuacao = 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$formularioId]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        $pontuacaoMax = $resultado['pontuacao_max'] ?? 0;

        $this->atualizar($formularioId, ['pontuacao_maxima' => $pontuacaoMax]);

        return $pontuacaoMax;
    }
}
