<?php
/**
 * ExemploTreinamentoModel - Exemplo de Model Moderno
 * Sistema de Gestão de Capacitações (SGC)
 *
 * Este é um EXEMPLO de como criar models usando a nova arquitetura Core.
 * Use como referência para migrar os models existentes.
 *
 * CARACTERÍSTICAS:
 * - Extends App\Core\Model (Active Record Pattern)
 * - CRUD automático
 * - Validações
 * - Timestamps automáticos
 * - Eventos de model
 * - Query Builder integrado
 */

namespace App\Models;

use App\Core\Model;

class ExemploTreinamentoModel extends Model
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
     * Atributos que podem ser preenchidos em massa (mass assignment)
     *
     * @var array
     */
    protected $fillable = [
        'titulo',
        'descricao',
        'instrutor_id',
        'data_inicio',
        'data_fim',
        'carga_horaria',
        'local',
        'vagas_total',
        'vagas_disponiveis',
        'status',
        'ativo'
    ];

    /**
     * Atributos protegidos contra mass assignment
     *
     * @var array
     */
    protected $guarded = [
        'id',
        'created_at',
        'updated_at'
    ];

    /**
     * Usar timestamps automáticos (created_at, updated_at)
     *
     * @var bool
     */
    protected $timestamps = true;

    /**
     * Usar soft deletes (deleted_at)
     * Quando true, delete() não remove do banco, apenas marca como deletado
     *
     * @var bool
     */
    protected $softDeletes = false;

    /**
     * Regras de validação
     *
     * Validações disponíveis:
     * - required: Campo obrigatório
     * - email: Deve ser email válido
     * - min:N: Mínimo de N caracteres
     * - max:N: Máximo de N caracteres
     * - numeric: Deve ser numérico
     * - confirmed: Deve ter campo {nome}_confirmation igual
     * - unique:tabela,coluna: Valor único no banco
     *
     * @var array
     */
    protected $rules = [
        'titulo' => 'required|min:3|max:200',
        'descricao' => 'required|min:10',
        'instrutor_id' => 'required|numeric',
        'data_inicio' => 'required',
        'carga_horaria' => 'required|numeric',
        'vagas_total' => 'numeric'
    ];

    // =========================================================================
    // EVENTOS DO MODEL
    // =========================================================================

    /**
     * Executado antes de criar (INSERT)
     *
     * @return void
     */
    protected function onCreating(): void
    {
        // Definir valores padrão
        if (!isset($this->attributes['status'])) {
            $this->setAttribute('status', 'planejado');
        }

        if (!isset($this->attributes['ativo'])) {
            $this->setAttribute('ativo', 1);
        }

        // Calcular vagas disponíveis
        if (isset($this->attributes['vagas_total'])) {
            $this->setAttribute('vagas_disponiveis', $this->attributes['vagas_total']);
        }
    }

    /**
     * Executado após criar (INSERT)
     *
     * @return void
     */
    protected function onCreated(): void
    {
        // Disparar evento global
        event()->dispatch('treinamento.criado', $this);

        // Enviar notificações
        // $this->notificarCriacao();

        // Log
        logger("Treinamento criado: {$this->titulo} (ID: {$this->id})");
    }

    /**
     * Executado antes de atualizar (UPDATE)
     *
     * @return void
     */
    protected function onUpdating(): void
    {
        // Validações adicionais
        if (isset($this->attributes['vagas_disponiveis'])) {
            if ($this->attributes['vagas_disponiveis'] < 0) {
                throw new \Exception("Vagas disponíveis não pode ser negativo");
            }
        }
    }

    /**
     * Executado após atualizar (UPDATE)
     *
     * @return void
     */
    protected function onUpdated(): void
    {
        event()->dispatch('treinamento.atualizado', $this);
        logger("Treinamento atualizado: {$this->titulo} (ID: {$this->id})");
    }

    /**
     * Executado antes de deletar (DELETE)
     *
     * @return bool|void Retornar false para cancelar deleção
     */
    protected function onDeleting()
    {
        // Verificar se tem inscrições
        // if ($this->temInscricoes()) {
        //     return false; // Cancelar deleção
        // }
    }

    /**
     * Executado após deletar (DELETE)
     *
     * @return void
     */
    protected function onDeleted(): void
    {
        event()->dispatch('treinamento.deletado', $this);
        logger("Treinamento deletado: {$this->titulo} (ID: {$this->id})");
    }

    // =========================================================================
    // MÉTODOS PERSONALIZADOS (BUSINESS LOGIC)
    // =========================================================================

    /**
     * Obter treinamentos ativos
     *
     * @return array
     */
    public static function ativos(): array
    {
        return (new static())
            ->where('ativo', 1)
            ->orderBy('data_inicio', 'DESC')
            ->get();
    }

    /**
     * Obter treinamentos por status
     *
     * @param string $status Status (planejado, em_andamento, concluido)
     * @return array
     */
    public static function porStatus(string $status): array
    {
        return (new static())
            ->where('status', $status)
            ->where('ativo', 1)
            ->orderBy('data_inicio', 'DESC')
            ->get();
    }

    /**
     * Obter treinamentos disponíveis (com vagas)
     *
     * @return array
     */
    public static function disponiveis(): array
    {
        return (new static())
            ->where('ativo', 1)
            ->where('vagas_disponiveis', '>', 0)
            ->where('status', 'planejado')
            ->orderBy('data_inicio', 'ASC')
            ->get();
    }

    /**
     * Verificar se tem vagas disponíveis
     *
     * @return bool
     */
    public function temVagas(): bool
    {
        return $this->getAttribute('vagas_disponiveis', 0) > 0;
    }

    /**
     * Reservar vaga
     *
     * @return bool
     */
    public function reservarVaga(): bool
    {
        if (!$this->temVagas()) {
            return false;
        }

        $vagasAtuais = $this->getAttribute('vagas_disponiveis');
        $this->setAttribute('vagas_disponiveis', $vagasAtuais - 1);

        return $this->save();
    }

    /**
     * Liberar vaga
     *
     * @return bool
     */
    public function liberarVaga(): bool
    {
        $vagasAtuais = $this->getAttribute('vagas_disponiveis');
        $vagasTotal = $this->getAttribute('vagas_total');

        // Não pode liberar mais vagas que o total
        if ($vagasAtuais >= $vagasTotal) {
            return false;
        }

        $this->setAttribute('vagas_disponiveis', $vagasAtuais + 1);

        return $this->save();
    }

    /**
     * Iniciar treinamento (mudar status)
     *
     * @return bool
     */
    public function iniciar(): bool
    {
        $this->setAttribute('status', 'em_andamento');
        return $this->save();
    }

    /**
     * Concluir treinamento
     *
     * @return bool
     */
    public function concluir(): bool
    {
        $this->setAttribute('status', 'concluido');
        return $this->save();
    }

    // =========================================================================
    // RELACIONAMENTOS (PARA IMPLEMENTAÇÃO FUTURA)
    // =========================================================================

    /**
     * Obter instrutor do treinamento
     *
     * @return object|null
     */
    /*
    public function instrutor()
    {
        $instrutorId = $this->getAttribute('instrutor_id');
        return Usuario::find($instrutorId);
    }
    */

    /**
     * Obter inscrições do treinamento
     *
     * @return array
     */
    /*
    public function inscricoes(): array
    {
        return Inscricao::where('treinamento_id', $this->getKey())->get();
    }
    */

    // =========================================================================
    // EXEMPLO DE USO
    // =========================================================================

    /*
    // Criar novo treinamento
    $treinamento = new ExemploTreinamentoModel([
        'titulo' => 'PHP Avançado',
        'descricao' => 'Curso completo de PHP',
        'instrutor_id' => 1,
        'data_inicio' => '2025-01-15',
        'carga_horaria' => 40,
        'vagas_total' => 30
    ]);
    $treinamento->save();

    // Buscar por ID
    $treinamento = ExemploTreinamentoModel::find(1);

    // Buscar ou lançar erro
    $treinamento = ExemploTreinamentoModel::findOrFail(1);

    // Buscar todos
    $todos = ExemploTreinamentoModel::all();

    // Query Builder
    $treinamentos = ExemploTreinamentoModel::where('ativo', 1)
        ->where('status', 'planejado')
        ->orderBy('data_inicio', 'DESC')
        ->limit(10)
        ->get();

    // Primeiro resultado
    $primeiro = ExemploTreinamentoModel::where('ativo', 1)->first();

    // Métodos personalizados
    $ativos = ExemploTreinamentoModel::ativos();
    $disponiveis = ExemploTreinamentoModel::disponiveis();

    // Atualizar
    $treinamento = ExemploTreinamentoModel::find(1);
    $treinamento->titulo = 'Novo Título';
    $treinamento->save();

    // Deletar
    $treinamento = ExemploTreinamentoModel::find(1);
    $treinamento->delete();

    // Paginação
    $paginated = ExemploTreinamentoModel::paginate(15, 1);
    // Retorna: ['data' => [...], 'total' => 100, 'per_page' => 15, 'current_page' => 1, 'last_page' => 7]

    // Converter para array/JSON
    $array = $treinamento->toArray();
    $json = $treinamento->toJson();
    */
}
