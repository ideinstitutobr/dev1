<?php
/**
 * Classe Model - Base para Models com Active Record Pattern
 * Sistema de Gestão de Capacitações (SGC)
 *
 * Fornece funcionalidades comuns para models:
 * - CRUD básico (Create, Read, Update, Delete)
 * - Query builder simples
 * - Validações
 * - Timestamps automáticos
 * - Eventos de model (creating, created, updating, updated, etc)
 * - Soft deletes (opcional)
 * - Paginação
 */

namespace App\Core;

use PDO;
use Exception;

abstract class Model
{
    /**
     * Nome da tabela no banco de dados
     * Deve ser definido nas classes filhas
     *
     * @var string
     */
    protected $table;

    /**
     * Nome da chave primária
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Conexão com o banco de dados
     *
     * @var PDO|null
     */
    protected $db = null;

    /**
     * Atributos do model
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * Atributos originais (antes de modificações)
     *
     * @var array
     */
    protected $original = [];

    /**
     * Atributos preenchíveis via mass assignment
     *
     * @var array
     */
    protected $fillable = [];

    /**
     * Atributos protegidos contra mass assignment
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Usar timestamps automáticos (created_at, updated_at)
     *
     * @var bool
     */
    protected $timestamps = true;

    /**
     * Usar soft deletes (deleted_at)
     *
     * @var bool
     */
    protected $softDeletes = false;

    /**
     * Validações do model
     *
     * @var array
     */
    protected $rules = [];

    /**
     * Mensagens de erro de validação
     *
     * @var array
     */
    protected $errors = [];

    /**
     * Indica se o model existe no banco (salvo)
     *
     * @var bool
     */
    protected $exists = false;

    /**
     * Query conditions
     *
     * @var array
     */
    protected $where = [];

    /**
     * Order by clauses
     *
     * @var array
     */
    protected $orderBy = [];

    /**
     * Limit
     *
     * @var int|null
     */
    protected $limit = null;

    /**
     * Offset
     *
     * @var int|null
     */
    protected $offset = null;

    /**
     * Construtor
     *
     * @param array $attributes Atributos iniciais
     */
    public function __construct(array $attributes = [])
    {
        $this->db = $this->getConnection();
        $this->fill($attributes);

        // Inferir nome da tabela se não definido
        if (!$this->table) {
            $this->table = $this->getTableName();
        }

        // Disparar evento de construção
        $this->fireEvent('constructed');
    }

    /**
     * Obter conexão com banco de dados
     *
     * @return PDO
     */
    protected function getConnection(): PDO
    {
        if (function_exists('app')) {
            try {
                return app('Database')->getConnection();
            } catch (Exception $e) {
                // Fallback para Database singleton
            }
        }

        // Fallback: usar classe Database diretamente
        if (class_exists('Database')) {
            return \Database::getInstance()->getConnection();
        }

        throw new Exception("Conexão com banco de dados não configurada");
    }

    /**
     * Inferir nome da tabela baseado no nome da classe
     *
     * @return string
     */
    protected function getTableName(): string
    {
        $class = get_class($this);
        $shortName = substr($class, strrpos($class, '\\') + 1);

        // Remover sufixo "Model" se existir
        $shortName = preg_replace('/Model$/', '', $shortName);

        // Converter CamelCase para snake_case e pluralizar (simples)
        $tableName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $shortName));

        // Pluralização simples (adicionar 's')
        // Em produção, usar biblioteca de pluralização
        return $tableName . 's';
    }

    /**
     * Preencher model com dados (mass assignment)
     *
     * @param array $attributes Atributos
     * @return self
     */
    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }

        return $this;
    }

    /**
     * Verificar se atributo pode ser preenchido
     *
     * @param string $key Nome do atributo
     * @return bool
     */
    protected function isFillable(string $key): bool
    {
        // Se fillable está definido, usar apenas o que está lá
        if (!empty($this->fillable)) {
            return in_array($key, $this->fillable);
        }

        // Caso contrário, permitir tudo exceto guarded
        return !in_array($key, $this->guarded);
    }

    /**
     * Definir atributo
     *
     * @param string $key Chave
     * @param mixed $value Valor
     * @return void
     */
    public function setAttribute(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Obter atributo
     *
     * @param string $key Chave
     * @param mixed $default Valor padrão
     * @return mixed
     */
    public function getAttribute(string $key, $default = null)
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * Magic getter
     *
     * @param string $key Chave
     * @return mixed
     */
    public function __get(string $key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Magic setter
     *
     * @param string $key Chave
     * @param mixed $value Valor
     * @return void
     */
    public function __set(string $key, $value): void
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Magic isset
     *
     * @param string $key Chave
     * @return bool
     */
    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    /**
     * Buscar por ID
     *
     * @param int $id ID
     * @return static|null
     */
    public static function find(int $id): ?self
    {
        $instance = new static();

        $stmt = $instance->db->prepare(
            "SELECT * FROM {$instance->table} WHERE {$instance->primaryKey} = ? LIMIT 1"
        );

        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }

        return $instance->newFromDatabase($data);
    }

    /**
     * Buscar por ID ou lançar exceção
     *
     * @param int $id ID
     * @return static
     * @throws Exception
     */
    public static function findOrFail(int $id): self
    {
        $model = static::find($id);

        if (!$model) {
            $class = static::class;
            throw new Exception("Model {$class} com ID {$id} não encontrado", 404);
        }

        return $model;
    }

    /**
     * Buscar todos os registros
     *
     * @return array
     */
    public static function all(): array
    {
        $instance = new static();
        return $instance->get();
    }

    /**
     * Iniciar query com WHERE
     *
     * @param string $column Coluna
     * @param mixed $operator Operador ou valor
     * @param mixed $value Valor (opcional)
     * @return self
     */
    public function where(string $column, $operator, $value = null): self
    {
        // Se apenas 2 parâmetros, assumir operador '='
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->where[] = compact('column', 'operator', 'value');

        return $this;
    }

    /**
     * Ordenar resultados
     *
     * @param string $column Coluna
     * @param string $direction Direção (ASC ou DESC)
     * @return self
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderBy[] = "{$column} {$direction}";
        return $this;
    }

    /**
     * Limitar resultados
     *
     * @param int $limit Limite
     * @return self
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Offset de resultados
     *
     * @param int $offset Offset
     * @return self
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Executar query e obter resultados
     *
     * @return array
     */
    public function get(): array
    {
        $sql = "SELECT * FROM {$this->table}";

        $bindings = [];

        // WHERE clauses
        if (!empty($this->where)) {
            $whereClauses = [];
            foreach ($this->where as $condition) {
                $whereClauses[] = "{$condition['column']} {$condition['operator']} ?";
                $bindings[] = $condition['value'];
            }
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }

        // Soft deletes
        if ($this->softDeletes) {
            $prefix = !empty($this->where) ? " AND" : " WHERE";
            $sql .= "{$prefix} deleted_at IS NULL";
        }

        // ORDER BY
        if (!empty($this->orderBy)) {
            $sql .= " ORDER BY " . implode(', ', $this->orderBy);
        }

        // LIMIT
        if ($this->limit !== null) {
            $sql .= " LIMIT " . (int) $this->limit;
        }

        // OFFSET
        if ($this->offset !== null) {
            $sql .= " OFFSET " . (int) $this->offset;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($bindings);

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $this->newFromDatabase($row);
        }

        // Resetar query
        $this->resetQuery();

        return $results;
    }

    /**
     * Obter primeiro resultado
     *
     * @return static|null
     */
    public function first(): ?self
    {
        $this->limit(1);
        $results = $this->get();

        return $results[0] ?? null;
    }

    /**
     * Criar nova instância do model a partir de dados do banco
     *
     * @param array $data Dados
     * @return static
     */
    protected function newFromDatabase(array $data): self
    {
        $instance = new static();
        $instance->attributes = $data;
        $instance->original = $data;
        $instance->exists = true;

        return $instance;
    }

    /**
     * Resetar condições de query
     *
     * @return void
     */
    protected function resetQuery(): void
    {
        $this->where = [];
        $this->orderBy = [];
        $this->limit = null;
        $this->offset = null;
    }

    /**
     * Salvar model (insert ou update)
     *
     * @return bool
     */
    public function save(): bool
    {
        // Validar
        if (!$this->validate()) {
            return false;
        }

        // Disparar evento
        $event = $this->exists ? 'updating' : 'creating';
        if ($this->fireEvent($event) === false) {
            return false;
        }

        // Atualizar timestamps
        if ($this->timestamps) {
            $this->updateTimestamps();
        }

        // Insert ou Update
        $result = $this->exists ? $this->performUpdate() : $this->performInsert();

        if ($result) {
            $this->exists = true;
            $this->original = $this->attributes;

            // Disparar evento
            $event = $this->exists ? 'updated' : 'created';
            $this->fireEvent($event);
        }

        return $result;
    }

    /**
     * Executar INSERT
     *
     * @return bool
     */
    protected function performInsert(): bool
    {
        $columns = array_keys($this->attributes);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute(array_values($this->attributes));

        // Definir ID
        if ($result) {
            $this->setAttribute($this->primaryKey, $this->db->lastInsertId());
        }

        return $result;
    }

    /**
     * Executar UPDATE
     *
     * @return bool
     */
    protected function performUpdate(): bool
    {
        $dirty = $this->getDirty();

        if (empty($dirty)) {
            return true; // Nada para atualizar
        }

        $sets = [];
        $bindings = [];

        foreach ($dirty as $key => $value) {
            $sets[] = "{$key} = ?";
            $bindings[] = $value;
        }

        $bindings[] = $this->getKey();

        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s = ?",
            $this->table,
            implode(', ', $sets),
            $this->primaryKey
        );

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($bindings);
    }

    /**
     * Deletar model
     *
     * @return bool
     */
    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        // Disparar evento
        if ($this->fireEvent('deleting') === false) {
            return false;
        }

        // Soft delete
        if ($this->softDeletes) {
            $this->setAttribute('deleted_at', date('Y-m-d H:i:s'));
            $result = $this->save();
        } else {
            // Hard delete
            $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$this->getKey()]);
        }

        if ($result) {
            $this->fireEvent('deleted');
            $this->exists = false;
        }

        return $result;
    }

    /**
     * Atualizar timestamps
     *
     * @return void
     */
    protected function updateTimestamps(): void
    {
        $timestamp = date('Y-m-d H:i:s');

        if (!$this->exists) {
            $this->setAttribute('created_at', $timestamp);
        }

        $this->setAttribute('updated_at', $timestamp);
    }

    /**
     * Obter atributos modificados
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

    /**
     * Obter valor da chave primária
     *
     * @return mixed
     */
    public function getKey()
    {
        return $this->getAttribute($this->primaryKey);
    }

    /**
     * Validar model
     *
     * @return bool
     */
    protected function validate(): bool
    {
        $this->errors = [];

        foreach ($this->rules as $field => $rules) {
            $value = $this->getAttribute($field);

            foreach (explode('|', $rules) as $rule) {
                if (!$this->validateRule($field, $value, $rule)) {
                    break; // Parar na primeira falha para esse campo
                }
            }
        }

        return empty($this->errors);
    }

    /**
     * Validar uma regra específica
     *
     * @param string $field Campo
     * @param mixed $value Valor
     * @param string $rule Regra
     * @return bool
     */
    protected function validateRule(string $field, $value, string $rule): bool
    {
        // required
        if ($rule === 'required' && empty($value)) {
            $this->errors[$field] = "O campo {$field} é obrigatório";
            return false;
        }

        // email
        if ($rule === 'email' && !empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "O campo {$field} deve ser um email válido";
            return false;
        }

        // min:N
        if (strpos($rule, 'min:') === 0) {
            $min = (int) substr($rule, 4);
            if (strlen($value) < $min) {
                $this->errors[$field] = "O campo {$field} deve ter no mínimo {$min} caracteres";
                return false;
            }
        }

        // max:N
        if (strpos($rule, 'max:') === 0) {
            $max = (int) substr($rule, 4);
            if (strlen($value) > $max) {
                $this->errors[$field] = "O campo {$field} deve ter no máximo {$max} caracteres";
                return false;
            }
        }

        return true;
    }

    /**
     * Obter erros de validação
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Disparar evento de model
     *
     * @param string $event Nome do evento
     * @return mixed
     */
    protected function fireEvent(string $event)
    {
        $method = 'on' . ucfirst($event);

        // Chamar método do model se existir
        if (method_exists($this, $method)) {
            $result = $this->$method();
            if ($result === false) {
                return false;
            }
        }

        // Disparar evento global se EventManager disponível
        if (function_exists('event')) {
            $modelClass = get_class($this);
            event()->dispatch("model.{$modelClass}.{$event}", [$this]);
        }

        return true;
    }

    /**
     * Converter model para array
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * Converter model para JSON
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * Paginação
     *
     * @param int $perPage Itens por página
     * @param int $currentPage Página atual
     * @return array ['data' => [], 'total' => int, 'per_page' => int, 'current_page' => int, 'last_page' => int]
     */
    public function paginate(int $perPage = 15, int $currentPage = 1): array
    {
        // Contar total
        $countSql = "SELECT COUNT(*) FROM {$this->table}";
        $stmt = $this->db->query($countSql);
        $total = (int) $stmt->fetchColumn();

        // Calcular offset
        $offset = ($currentPage - 1) * $perPage;

        // Buscar dados
        $data = $this->limit($perPage)->offset($offset)->get();

        return [
            'data' => $data,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $currentPage,
            'last_page' => (int) ceil($total / $perPage)
        ];
    }
}
