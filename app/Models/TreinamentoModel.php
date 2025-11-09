<?php
/**
 * TreinamentoModel - Model de Treinamentos/Capacitações
 * Sistema de Gestão de Capacitações (SGC)
 *
 * Migrado para usar App\Core\Model com Active Record Pattern
 */

namespace App\Models;

use App\Core\Model;
use PDO;

class TreinamentoModel extends Model
{
    /**
     * Nome da tabela
     *
     * @var string
     */
    protected $table = 'treinamentos';

    /**
     * Chave primária
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Atributos fillable (mass assignment)
     * Baseado nos 14 campos da matriz de treinamentos
     *
     * @var array
     */
    protected $fillable = [
        'nome',
        'tipo',
        'modalidade',
        'componente_pe',
        'programa',
        'objetivo',
        'resultados_esperados',
        'justificativa',
        'fornecedor',
        'instrutor',
        'carga_horaria',
        'carga_horaria_complementar',
        'data_inicio',
        'data_fim',
        'custo_total',
        'observacoes',
        'status',
        'origem'
    ];

    /**
     * Atributos protegidos
     *
     * @var array
     */
    protected $guarded = [
        'id',
        'created_at',
        'updated_at'
    ];

    /**
     * Usar timestamps automáticos
     *
     * @var bool
     */
    protected $timestamps = true;

    /**
     * Regras de validação
     *
     * @var array
     */
    protected $rules = [
        'nome' => 'required|min:3|max:255',
        'tipo' => 'required',
        'modalidade' => 'required',
        'carga_horaria' => 'numeric',
        'carga_horaria_complementar' => 'numeric',
        'custo_total' => 'numeric',
        'data_inicio' => 'required',
        'status' => 'required'
    ];

    // =========================================================================
    // EVENTOS DO MODEL
    // =========================================================================

    /**
     * Antes de criar
     *
     * @return void
     */
    protected function onCreating(): void
    {
        // Definir valores padrão
        if (!isset($this->attributes['status'])) {
            $this->setAttribute('status', 'Programado');
        }

        if (!isset($this->attributes['modalidade'])) {
            $this->setAttribute('modalidade', 'Presencial');
        }

        if (!isset($this->attributes['origem'])) {
            $this->setAttribute('origem', 'local');
        }
    }

    /**
     * Após criar
     *
     * @return void
     */
    protected function onCreated(): void
    {
        // Disparar evento global
        event()->dispatch('treinamento.criado', [
            'id' => $this->getKey(),
            'nome' => $this->getAttribute('nome'),
            'tipo' => $this->getAttribute('tipo'),
            'data_inicio' => $this->getAttribute('data_inicio')
        ]);

        // Log
        if (function_exists('logger')) {
            logger("Treinamento criado: {$this->getAttribute('nome')} (ID: {$this->getKey()})");
        }
    }

    /**
     * Após atualizar
     *
     * @return void
     */
    protected function onUpdated(): void
    {
        event()->dispatch('treinamento.atualizado', [
            'id' => $this->getKey(),
            'nome' => $this->getAttribute('nome')
        ]);

        if (function_exists('logger')) {
            logger("Treinamento atualizado: {$this->getAttribute('nome')} (ID: {$this->getKey()})");
        }
    }

    /**
     * Após deletar
     *
     * @return void
     */
    protected function onDeleted(): void
    {
        event()->dispatch('treinamento.deletado', [
            'id' => $this->getKey()
        ]);
    }

    // =========================================================================
    // MÉTODOS DE CONSULTA (SCOPES)
    // =========================================================================

    /**
     * Listar com filtros e paginação
     *
     * @param array $params Parâmetros de filtro
     * @return array
     */
    public function listarComFiltros(array $params = []): array
    {
        $page = $params['page'] ?? 1;
        $perPage = $params['per_page'] ?? (defined('ITEMS_PER_PAGE') ? ITEMS_PER_PAGE : 15);
        $search = $params['search'] ?? '';
        $tipo = $params['tipo'] ?? '';
        $status = $params['status'] ?? '';
        $ano = $params['ano'] ?? '';

        // Construir query
        $sql = "SELECT t.*,
                (SELECT COUNT(*) FROM treinamento_participantes tp WHERE tp.treinamento_id = t.id) as total_participantes
                FROM {$this->table} t
                WHERE 1=1";

        $bindings = [];

        // Filtro de busca
        if (!empty($search)) {
            $sql .= " AND (t.nome LIKE ? OR t.fornecedor LIKE ? OR t.instrutor LIKE ?)";
            $searchTerm = "%{$search}%";
            $bindings[] = $searchTerm;
            $bindings[] = $searchTerm;
            $bindings[] = $searchTerm;
        }

        // Filtro de tipo
        if (!empty($tipo)) {
            $sql .= " AND t.tipo = ?";
            $bindings[] = $tipo;
        }

        // Filtro de status
        if (!empty($status)) {
            $sql .= " AND t.status = ?";
            $bindings[] = $status;
        }

        // Filtro de ano
        if (!empty($ano)) {
            $sql .= " AND YEAR(t.data_inicio) = ?";
            $bindings[] = $ano;
        }

        // Contar total
        $countSql = preg_replace('/SELECT t\.\*.*FROM/s', 'SELECT COUNT(*) as total FROM', $sql);
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($bindings);
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Adicionar ordenação e paginação
        $sql .= " ORDER BY t.data_inicio DESC";
        $offset = ($page - 1) * $perPage;
        $sql .= " LIMIT {$perPage} OFFSET {$offset}";

        // Executar query
        $stmt = $this->db->prepare($sql);
        $stmt->execute($bindings);
        $treinamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data' => $treinamentos,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Buscar treinamentos programados
     *
     * @return array
     */
    public static function programados(): array
    {
        return (new static())
            ->where('status', 'Programado')
            ->orderBy('data_inicio', 'ASC')
            ->get();
    }

    /**
     * Buscar treinamentos em andamento
     *
     * @return array
     */
    public static function emAndamento(): array
    {
        $instance = new static();

        $sql = "SELECT t.*,
                (SELECT COUNT(*) FROM treinamento_participantes tp WHERE tp.treinamento_id = t.id) as total_participantes
                FROM {$instance->table} t
                WHERE CURDATE() BETWEEN t.data_inicio AND t.data_fim
                AND t.status = 'Em Andamento'
                ORDER BY t.data_inicio ASC";

        $stmt = $instance->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar próximos treinamentos
     *
     * @param int $limite Limite de resultados
     * @return array
     */
    public static function proximos(int $limite = 5): array
    {
        $instance = new static();

        $sql = "SELECT t.*,
                (SELECT COUNT(*) FROM treinamento_participantes tp WHERE tp.treinamento_id = t.id) as total_participantes
                FROM {$instance->table} t
                WHERE t.data_inicio >= CURDATE()
                AND t.status = 'Programado'
                ORDER BY t.data_inicio ASC
                LIMIT ?";

        $stmt = $instance->db->prepare($sql);
        $stmt->execute([$limite]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar treinamentos executados
     *
     * @return array
     */
    public static function executados(): array
    {
        return (new static())
            ->where('status', 'Executado')
            ->orderBy('data_inicio', 'DESC')
            ->get();
    }

    /**
     * Buscar treinamentos cancelados
     *
     * @return array
     */
    public static function cancelados(): array
    {
        return (new static())
            ->where('status', 'Cancelado')
            ->orderBy('data_inicio', 'DESC')
            ->get();
    }

    // =========================================================================
    // MÉTODOS DE RELACIONAMENTO
    // =========================================================================

    /**
     * Buscar participantes do treinamento
     *
     * @return array
     */
    public function participantes(): array
    {
        $sql = "SELECT tp.*, c.nome as colaborador_nome, c.email as colaborador_email,
                c.cargo, c.departamento, c.nivel_hierarquico
                FROM treinamento_participantes tp
                JOIN colaboradores c ON tp.colaborador_id = c.id
                WHERE tp.treinamento_id = ?
                ORDER BY c.nome ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->getKey()]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar agenda do treinamento
     *
     * @return array
     */
    public function agenda(): array
    {
        $sql = "SELECT * FROM agenda_treinamentos
                WHERE treinamento_id = ?
                ORDER BY data_inicio ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->getKey()]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // MÉTODOS DE AÇÃO (BUSINESS LOGIC)
    // =========================================================================

    /**
     * Cancelar treinamento
     *
     * @return bool
     */
    public function cancelar(): bool
    {
        $this->setAttribute('status', 'Cancelado');
        $result = $this->save();

        if ($result) {
            event()->dispatch('treinamento.cancelado', [
                'id' => $this->getKey(),
                'nome' => $this->getAttribute('nome')
            ]);
        }

        return $result;
    }

    /**
     * Marcar como executado
     *
     * @return bool
     */
    public function marcarExecutado(): bool
    {
        $this->setAttribute('status', 'Executado');
        $result = $this->save();

        if ($result) {
            event()->dispatch('treinamento.executado', [
                'id' => $this->getKey(),
                'nome' => $this->getAttribute('nome')
            ]);
        }

        return $result;
    }

    /**
     * Iniciar treinamento
     *
     * @return bool
     */
    public function iniciar(): bool
    {
        $this->setAttribute('status', 'Em Andamento');
        return $this->save();
    }

    // =========================================================================
    // MÉTODOS DE ESTATÍSTICAS
    // =========================================================================

    /**
     * Obter estatísticas do treinamento
     *
     * @return array
     */
    public function getEstatisticas(): array
    {
        $id = $this->getKey();
        $stats = [];

        // Total de participantes
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total FROM treinamento_participantes WHERE treinamento_id = ?
        ");
        $stmt->execute([$id]);
        $stats['total_participantes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Participantes presentes
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total FROM treinamento_participantes
            WHERE treinamento_id = ? AND status_participacao = 'Presente'
        ");
        $stmt->execute([$id]);
        $stats['total_presentes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Participantes ausentes
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total FROM treinamento_participantes
            WHERE treinamento_id = ? AND status_participacao = 'Ausente'
        ");
        $stmt->execute([$id]);
        $stats['total_ausentes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Check-ins realizados
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total FROM treinamento_participantes
            WHERE treinamento_id = ? AND check_in_realizado = 1
        ");
        $stmt->execute([$id]);
        $stats['total_checkins'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Média de avaliação
        $stmt = $this->db->prepare("
            SELECT AVG(nota_avaliacao_reacao) as media
            FROM treinamento_participantes
            WHERE treinamento_id = ? AND nota_avaliacao_reacao IS NOT NULL
        ");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['media_avaliacao'] = $result['media'] ?? 0;

        // Percentual de presença
        if ($stats['total_participantes'] > 0) {
            $stats['percentual_presenca'] = ($stats['total_presentes'] / $stats['total_participantes']) * 100;
        } else {
            $stats['percentual_presenca'] = 0;
        }

        return $stats;
    }

    /**
     * Obter anos disponíveis
     *
     * @return array
     */
    public static function getAnosDisponiveis(): array
    {
        $instance = new static();

        $stmt = $instance->db->query("
            SELECT DISTINCT YEAR(data_inicio) as ano
            FROM {$instance->table}
            WHERE data_inicio IS NOT NULL
            ORDER BY ano DESC
        ");

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Verificar se treinamento está no futuro
     *
     * @return bool
     */
    public function isFuturo(): bool
    {
        $dataInicio = $this->getAttribute('data_inicio');
        return $dataInicio && strtotime($dataInicio) > time();
    }

    /**
     * Verificar se treinamento está em andamento
     *
     * @return bool
     */
    public function isEmAndamento(): bool
    {
        $dataInicio = $this->getAttribute('data_inicio');
        $dataFim = $this->getAttribute('data_fim');
        $agora = time();

        return $dataInicio && $dataFim &&
               strtotime($dataInicio) <= $agora &&
               strtotime($dataFim) >= $agora;
    }

    /**
     * Verificar se treinamento foi finalizado
     *
     * @return bool
     */
    public function isFinalizado(): bool
    {
        $dataFim = $this->getAttribute('data_fim');
        return $dataFim && strtotime($dataFim) < time();
    }

    /**
     * Obter duração em dias
     *
     * @return int
     */
    public function getDuracaoDias(): int
    {
        $dataInicio = $this->getAttribute('data_inicio');
        $dataFim = $this->getAttribute('data_fim');

        if (!$dataInicio || !$dataFim) {
            return 0;
        }

        $diff = strtotime($dataFim) - strtotime($dataInicio);
        return ceil($diff / (60 * 60 * 24)) + 1; // +1 para incluir o dia inicial
    }

    /**
     * Formatar custo
     *
     * @return string
     */
    public function getCustoFormatado(): string
    {
        $custo = $this->getAttribute('custo_total');

        if (!$custo) {
            return 'R$ 0,00';
        }

        return 'R$ ' . number_format($custo, 2, ',', '.');
    }
}
