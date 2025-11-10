<?php
/**
 * Model: Colaborador
 * Sistema de Gestão de Capacitações (SGC)
 *
 * Gerencia os colaboradores/funcionários do instituto
 * Migrado para arquitetura Core v2.0 (Sprint 4)
 */

namespace App\Models;

use App\Core\Model;

class ColaboradorModel extends Model
{
    /**
     * Nome da tabela
     *
     * @var string
     */
    protected $table = 'colaboradores';

    /**
     * Atributos preenchíveis via mass assignment
     *
     * @var array
     */
    protected $fillable = [
        'nome',
        'email',
        'cpf',
        'nivel_hierarquico',
        'cargo',
        'departamento',
        'salario',
        'data_admissao',
        'telefone',
        'ativo',
        'origem',
        'wordpress_id',
        'foto_perfil',
        'observacoes'
    ];

    /**
     * Regras de validação
     *
     * @var array
     */
    protected $rules = [
        'nome' => 'required|min:3|max:200',
        'email' => 'required|email',
        'nivel_hierarquico' => 'required',
        'cargo' => 'max:100',
        'departamento' => 'max:100',
        'telefone' => 'max:20',
        'foto_perfil' => 'max:255'
    ];

    /**
     * Conversão de tipos (casts)
     *
     * @var array
     */
    protected $casts = [
        'salario' => 'decimal:2',
        'ativo' => 'boolean',
        'wordpress_id' => 'integer'
    ];

    /**
     * Usar timestamps automáticos
     *
     * @var bool
     */
    protected $timestamps = true;

    /**
     * Usar soft deletes (usa campo 'ativo' ao invés de deleted_at)
     *
     * @var bool
     */
    protected $softDeletes = false;

    // ===================================================================
    // SCOPES (Query Filters)
    // ===================================================================

    /**
     * Scope: Filtrar por nível hierárquico
     *
     * @param string $nivel Nível (Estratégico, Tático, Operacional)
     * @return self
     */
    public function porNivel(string $nivel): self
    {
        return $this->where('nivel_hierarquico', $nivel);
    }

    /**
     * Scope: Somente colaboradores ativos
     *
     * @return self
     */
    public function ativos(): self
    {
        return $this->where('ativo', 1);
    }

    /**
     * Scope: Somente colaboradores inativos
     *
     * @return self
     */
    public function inativos(): self
    {
        return $this->where('ativo', 0);
    }

    /**
     * Scope: Filtrar por origem (local ou wordpress)
     *
     * @param string $origem Origem (local, wordpress)
     * @return self
     */
    public function porOrigem(string $origem): self
    {
        return $this->where('origem', $origem);
    }

    /**
     * Scope: Buscar por termo (nome, email, cpf)
     *
     * @param string $termo Termo de busca
     * @return self
     */
    public function buscar(string $termo): self
    {
        $termo = "%{$termo}%";
        return $this->whereRaw(
            "(nome LIKE ? OR email LIKE ? OR cpf LIKE ?)",
            [$termo, $termo, $termo]
        );
    }

    /**
     * Scope: Filtrar por cargo
     *
     * @param string $cargo Cargo
     * @return self
     */
    public function porCargo(string $cargo): self
    {
        return $this->where('cargo', $cargo);
    }

    /**
     * Scope: Filtrar por departamento
     *
     * @param string $departamento Departamento
     * @return self
     */
    public function porDepartamento(string $departamento): self
    {
        return $this->where('departamento', $departamento);
    }

    // ===================================================================
    // RELACIONAMENTOS
    // ===================================================================

    /**
     * Relacionamento: Treinamentos do colaborador (participações)
     *
     * @return array
     */
    public function treinamentos(): array
    {
        $sql = "SELECT
                    t.id,
                    t.nome,
                    t.tipo,
                    t.carga_horaria_total,
                    tp.status_participacao,
                    tp.check_in_realizado,
                    tp.nota_avaliacao_reacao,
                    tp.created_at as data_inscricao
                FROM treinamento_participantes tp
                JOIN treinamentos t ON tp.treinamento_id = t.id
                WHERE tp.colaborador_id = ?
                ORDER BY tp.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->attributes[$this->primaryKey] ?? null]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Relacionamento: Unidades do colaborador
     * (Implementação futura - Sprint de Unidades)
     *
     * @return array
     */
    public function unidades(): array
    {
        // TODO: Implementar quando migrar módulo de Unidades
        return [];
    }

    // ===================================================================
    // MÉTODOS PERSONALIZADOS
    // ===================================================================

    /**
     * Obter histórico completo de treinamentos com detalhes
     *
     * @return array
     */
    public function getHistoricoTreinamentos(): array
    {
        $sql = "SELECT
                    t.nome as treinamento_nome,
                    t.tipo,
                    t.programa,
                    tp.status_participacao,
                    tp.check_in_realizado,
                    tp.nota_avaliacao_reacao,
                    SUM(f.horas_participadas) as horas_totais,
                    MIN(at.data_inicio) as data_inicio,
                    MAX(at.data_fim) as data_fim,
                    t.instrutor,
                    t.local_padrao
                FROM treinamento_participantes tp
                JOIN treinamentos t ON tp.treinamento_id = t.id
                LEFT JOIN frequencia_treinamento f ON tp.id = f.participante_id
                LEFT JOIN agenda_treinamentos at ON t.id = at.treinamento_id
                WHERE tp.colaborador_id = ?
                GROUP BY t.id, t.nome, t.tipo, t.programa, tp.status_participacao,
                         tp.check_in_realizado, tp.nota_avaliacao_reacao,
                         t.instrutor, t.local_padrao
                ORDER BY at.data_inicio DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->attributes[$this->primaryKey] ?? null]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obter estatísticas do colaborador
     *
     * @return array
     */
    public function getEstatisticas(): array
    {
        $colaboradorId = $this->attributes[$this->primaryKey] ?? null;
        $stats = [];

        // Total de treinamentos
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total FROM treinamento_participantes WHERE colaborador_id = ?
        ");
        $stmt->execute([$colaboradorId]);
        $stats['total_treinamentos'] = $stmt->fetch(\PDO::FETCH_ASSOC)['total'] ?? 0;

        // Treinamentos concluídos (status = Presente)
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total FROM treinamento_participantes
            WHERE colaborador_id = ? AND status_participacao = 'Presente'
        ");
        $stmt->execute([$colaboradorId]);
        $stats['concluidos'] = $stmt->fetch(\PDO::FETCH_ASSOC)['total'] ?? 0;

        // Treinamentos pendentes (status = Confirmado)
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total FROM treinamento_participantes
            WHERE colaborador_id = ? AND status_participacao = 'Confirmado'
        ");
        $stmt->execute([$colaboradorId]);
        $stats['pendentes'] = $stmt->fetch(\PDO::FETCH_ASSOC)['total'] ?? 0;

        // Total de horas de treinamento
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(f.horas_participadas), 0) as total
            FROM frequencia_treinamento f
            JOIN treinamento_participantes tp ON f.participante_id = tp.id
            WHERE tp.colaborador_id = ? AND f.presente = 1
        ");
        $stmt->execute([$colaboradorId]);
        $stats['horas_totais'] = $stmt->fetch(\PDO::FETCH_ASSOC)['total'] ?? 0;

        // Média de avaliações de reação
        $stmt = $this->db->prepare("
            SELECT AVG(nota_avaliacao_reacao) as media
            FROM treinamento_participantes
            WHERE colaborador_id = ? AND nota_avaliacao_reacao IS NOT NULL
        ");
        $stmt->execute([$colaboradorId]);
        $media = $stmt->fetch(\PDO::FETCH_ASSOC)['media'] ?? 0;
        $stats['media_avaliacao'] = $media ? round($media, 2) : 0;

        // Último treinamento
        $stmt = $this->db->prepare("
            SELECT t.nome, at.data_inicio
            FROM treinamento_participantes tp
            JOIN treinamentos t ON tp.treinamento_id = t.id
            LEFT JOIN agenda_treinamentos at ON t.id = at.treinamento_id
            WHERE tp.colaborador_id = ?
            ORDER BY at.data_inicio DESC
            LIMIT 1
        ");
        $stmt->execute([$colaboradorId]);
        $ultimo = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stats['ultimo_treinamento'] = $ultimo ? $ultimo['nome'] : null;
        $stats['ultimo_treinamento_data'] = $ultimo ? $ultimo['data_inicio'] : null;

        // Taxa de conclusão (%)
        if ($stats['total_treinamentos'] > 0) {
            $stats['taxa_conclusao'] = round(
                ($stats['concluidos'] / $stats['total_treinamentos']) * 100,
                1
            );
        } else {
            $stats['taxa_conclusao'] = 0;
        }

        return $stats;
    }

    /**
     * Ativar colaborador
     *
     * @return bool
     */
    public function ativar(): bool
    {
        $this->attributes['ativo'] = 1;
        $result = $this->save();

        if ($result) {
            event()->dispatch('colaborador.ativado', $this);
        }

        return $result;
    }

    /**
     * Inativar colaborador (soft delete)
     *
     * @return bool
     */
    public function inativar(): bool
    {
        $this->attributes['ativo'] = 0;
        $result = $this->save();

        if ($result) {
            event()->dispatch('colaborador.inativado', $this);
        }

        return $result;
    }

    /**
     * Validar CPF
     *
     * @param string $cpf CPF (com ou sem formatação)
     * @return bool
     */
    public static function validarCPF(string $cpf): bool
    {
        // Remove caracteres não numéricos
        $cpf = preg_replace('/[^0-9]/', '', $cpf);

        // Verifica se tem 11 dígitos
        if (strlen($cpf) != 11) {
            return false;
        }

        // Verifica se todos os dígitos são iguais (ex: 111.111.111-11)
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        // Valida primeiro dígito verificador
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }

        return true;
    }

    /**
     * Limpar formatação do CPF
     *
     * @param string $cpf CPF com formatação
     * @return string CPF sem formatação
     */
    public static function limparCPF(string $cpf): string
    {
        return preg_replace('/[^0-9]/', '', $cpf);
    }

    /**
     * Verificar se email já existe (para validação)
     *
     * @param string $email Email
     * @param int|null $excluirId ID para excluir da verificação (ao editar)
     * @return bool
     */
    public function emailExiste(string $email, ?int $excluirId = null): bool
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE email = ?";
        $params = [$email];

        if ($excluirId) {
            $sql .= " AND id != ?";
            $params[] = $excluirId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(\PDO::FETCH_ASSOC)['total'] > 0;
    }

    /**
     * Verificar se CPF já existe (para validação)
     *
     * @param string $cpf CPF
     * @param int|null $excluirId ID para excluir da verificação (ao editar)
     * @return bool
     */
    public function cpfExiste(string $cpf, ?int $excluirId = null): bool
    {
        $cpf = self::limparCPF($cpf);

        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE cpf = ?";
        $params = [$cpf];

        if ($excluirId) {
            $sql .= " AND id != ?";
            $params[] = $excluirId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(\PDO::FETCH_ASSOC)['total'] > 0;
    }

    // ===================================================================
    // EVENTOS DO MODEL
    // ===================================================================

    /**
     * Evento: Antes de salvar (creating/updating)
     *
     * @return void
     */
    protected function onSaving(): void
    {
        // Limpa formatação do CPF antes de salvar
        if (isset($this->attributes['cpf'])) {
            $this->attributes['cpf'] = self::limparCPF($this->attributes['cpf']);
        }

        // Normaliza email para lowercase
        if (isset($this->attributes['email'])) {
            $this->attributes['email'] = strtolower(trim($this->attributes['email']));
        }

        // Define origem padrão se não informada
        if (empty($this->attributes['origem'])) {
            $this->attributes['origem'] = 'local';
        }

        // Define ativo padrão se não informado
        if (!isset($this->attributes['ativo'])) {
            $this->attributes['ativo'] = 1;
        }
    }

    /**
     * Evento: Após criar registro
     *
     * @return void
     */
    protected function onCreated(): void
    {
        event()->dispatch('colaborador.created', $this);

        // Log de auditoria
        if (function_exists('log_activity')) {
            log_activity('colaborador_criado', [
                'colaborador_id' => $this->attributes['id'] ?? null,
                'nome' => $this->attributes['nome'] ?? null,
                'email' => $this->attributes['email'] ?? null
            ]);
        }
    }

    /**
     * Evento: Após atualizar registro
     *
     * @return void
     */
    protected function onUpdated(): void
    {
        event()->dispatch('colaborador.updated', $this);

        // Log de auditoria
        if (function_exists('log_activity')) {
            log_activity('colaborador_atualizado', [
                'colaborador_id' => $this->attributes['id'] ?? null,
                'nome' => $this->attributes['nome'] ?? null,
                'campos_alterados' => array_keys($this->getDirty())
            ]);
        }
    }

    /**
     * Evento: Após deletar registro
     *
     * @return void
     */
    protected function onDeleted(): void
    {
        event()->dispatch('colaborador.deleted', $this);

        // Log de auditoria
        if (function_exists('log_activity')) {
            log_activity('colaborador_deletado', [
                'colaborador_id' => $this->attributes['id'] ?? null,
                'nome' => $this->attributes['nome'] ?? null
            ]);
        }
    }

    /**
     * Obter campos que foram modificados
     *
     * @return array
     */
    protected function getDirty(): array
    {
        $dirty = [];
        foreach ($this->attributes as $key => $value) {
            if (!isset($this->original[$key]) || $this->original[$key] !== $value) {
                $dirty[$key] = $value;
            }
        }
        return $dirty;
    }
}
