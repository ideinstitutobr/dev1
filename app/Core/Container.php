<?php
/**
 * Classe Container - Dependency Injection Container
 * Sistema de Gestão de Capacitações (SGC)
 *
 * Implementa um container de injeção de dependências simples e eficiente
 * Inspirado em Laravel Container e PHP-DI
 */

namespace App\Core;

use ReflectionClass;
use ReflectionParameter;
use Exception;
use Closure;

class Container
{
    /**
     * Instância singleton do container
     *
     * @var Container|null
     */
    private static $instance = null;

    /**
     * Bindings registrados
     *
     * @var array
     */
    protected $bindings = [];

    /**
     * Instâncias singleton
     *
     * @var array
     */
    protected $instances = [];

    /**
     * Aliases de classes
     *
     * @var array
     */
    protected $aliases = [];

    /**
     * Stack de resolução (para detectar dependências circulares)
     *
     * @var array
     */
    protected $buildStack = [];

    /**
     * Construtor privado (Singleton)
     */
    private function __construct()
    {
        // Registrar o próprio container
        $this->instance(self::class, $this);
        $this->alias(self::class, 'Container');
    }

    /**
     * Obter instância singleton do container
     *
     * @return self
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Registrar uma binding no container
     *
     * @param string $abstract Nome abstrato (interface/classe)
     * @param Closure|string|null $concrete Implementação concreta
     * @param bool $shared Se deve ser singleton
     * @return void
     */
    public function bind(string $abstract, $concrete = null, bool $shared = false): void
    {
        // Se não foi fornecido concrete, usar o próprio abstract
        if ($concrete === null) {
            $concrete = $abstract;
        }

        // Remover instância existente se for singleton
        if ($shared) {
            unset($this->instances[$abstract]);
        }

        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared' => $shared
        ];
    }

    /**
     * Registrar uma binding singleton
     *
     * @param string $abstract Nome abstrato
     * @param Closure|string|null $concrete Implementação
     * @return void
     */
    public function singleton(string $abstract, $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Registrar uma instância existente como singleton
     *
     * @param string $abstract Nome abstrato
     * @param mixed $instance Instância
     * @return void
     */
    public function instance(string $abstract, $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    /**
     * Registrar um alias
     *
     * @param string $abstract Nome original
     * @param string $alias Alias
     * @return void
     */
    public function alias(string $abstract, string $alias): void
    {
        $this->aliases[$alias] = $abstract;
    }

    /**
     * Resolver uma dependência do container
     *
     * @param string $abstract Nome abstrato
     * @param array $parameters Parâmetros adicionais
     * @return mixed Instância resolvida
     * @throws Exception
     */
    public function make(string $abstract, array $parameters = [])
    {
        // Resolver alias
        $abstract = $this->getAlias($abstract);

        // Se já existe instância singleton, retornar
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // Obter implementação concreta
        $concrete = $this->getConcrete($abstract);

        // Se é um Closure, executar
        if ($concrete instanceof Closure) {
            $object = $concrete($this, $parameters);
        } else {
            $object = $this->build($concrete, $parameters);
        }

        // Se é singleton, guardar instância
        if ($this->isShared($abstract)) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * Alias para make()
     *
     * @param string $abstract
     * @param array $parameters
     * @return mixed
     */
    public function get(string $abstract, array $parameters = [])
    {
        return $this->make($abstract, $parameters);
    }

    /**
     * Construir uma instância de uma classe
     *
     * @param string $concrete Nome da classe
     * @param array $parameters Parâmetros
     * @return mixed Instância
     * @throws Exception
     */
    protected function build(string $concrete, array $parameters = [])
    {
        // Verificar dependência circular
        if (in_array($concrete, $this->buildStack)) {
            throw new Exception(
                "Dependência circular detectada: " . implode(' -> ', $this->buildStack) . " -> {$concrete}"
            );
        }

        // Adicionar ao stack
        $this->buildStack[] = $concrete;

        try {
            $reflection = new ReflectionClass($concrete);

            // Verificar se a classe é instanciável
            if (!$reflection->isInstantiable()) {
                throw new Exception("A classe {$concrete} não é instanciável.");
            }

            $constructor = $reflection->getConstructor();

            // Se não tem construtor, criar instância diretamente
            if ($constructor === null) {
                array_pop($this->buildStack);
                return new $concrete;
            }

            // Resolver dependências do construtor
            $dependencies = $this->resolveDependencies(
                $constructor->getParameters(),
                $parameters
            );

            // Criar instância com dependências
            $instance = $reflection->newInstanceArgs($dependencies);

            array_pop($this->buildStack);

            return $instance;

        } catch (Exception $e) {
            array_pop($this->buildStack);
            throw $e;
        }
    }

    /**
     * Resolver dependências dos parâmetros
     *
     * @param ReflectionParameter[] $parameters Parâmetros do construtor
     * @param array $primitives Valores primitivos fornecidos
     * @return array Dependências resolvidas
     * @throws Exception
     */
    protected function resolveDependencies(array $parameters, array $primitives = []): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $dependency = $parameter->getType();

            // Se o parâmetro foi fornecido diretamente
            if (array_key_exists($parameter->getName(), $primitives)) {
                $dependencies[] = $primitives[$parameter->getName()];
                continue;
            }

            // Se não tem tipo, verificar valor padrão
            if ($dependency === null) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new Exception(
                        "Não foi possível resolver o parâmetro \${$parameter->getName()}"
                    );
                }
                continue;
            }

            // Se é tipo built-in (string, int, etc)
            if ($dependency->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new Exception(
                        "Não foi possível resolver o parâmetro primitivo \${$parameter->getName()}"
                    );
                }
                continue;
            }

            // Resolver classe como dependência
            $dependencyClass = $dependency->getName();
            $dependencies[] = $this->make($dependencyClass);
        }

        return $dependencies;
    }

    /**
     * Obter implementação concreta
     *
     * @param string $abstract Nome abstrato
     * @return string|Closure
     */
    protected function getConcrete(string $abstract)
    {
        // Se não está registrado, retornar o próprio abstract
        if (!isset($this->bindings[$abstract])) {
            return $abstract;
        }

        return $this->bindings[$abstract]['concrete'];
    }

    /**
     * Verificar se é singleton
     *
     * @param string $abstract Nome abstrato
     * @return bool
     */
    protected function isShared(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) &&
               $this->bindings[$abstract]['shared'] === true;
    }

    /**
     * Resolver alias
     *
     * @param string $abstract Nome ou alias
     * @return string Nome real
     */
    protected function getAlias(string $abstract): string
    {
        if (!isset($this->aliases[$abstract])) {
            return $abstract;
        }

        // Resolver recursivamente (alias de alias)
        return $this->getAlias($this->aliases[$abstract]);
    }

    /**
     * Verificar se está registrado
     *
     * @param string $abstract Nome abstrato
     * @return bool
     */
    public function has(string $abstract): bool
    {
        $abstract = $this->getAlias($abstract);

        return isset($this->bindings[$abstract]) ||
               isset($this->instances[$abstract]);
    }

    /**
     * Verificar se é singleton
     *
     * @param string $abstract Nome abstrato
     * @return bool
     */
    public function isSingleton(string $abstract): bool
    {
        return isset($this->instances[$abstract]) ||
               $this->isShared($abstract);
    }

    /**
     * Remover binding
     *
     * @param string $abstract Nome abstrato
     * @return void
     */
    public function forget(string $abstract): void
    {
        unset(
            $this->bindings[$abstract],
            $this->instances[$abstract],
            $this->aliases[$abstract]
        );
    }

    /**
     * Limpar todos os bindings e instâncias
     *
     * @return void
     */
    public function flush(): void
    {
        $this->bindings = [];
        $this->instances = [];
        $this->aliases = [];
        $this->buildStack = [];

        // Re-registrar o container
        $this->instance(self::class, $this);
        $this->alias(self::class, 'Container');
    }

    /**
     * Chamar um método de uma classe com injeção de dependências
     *
     * @param callable|string|array $callback Callback
     * @param array $parameters Parâmetros
     * @return mixed Resultado
     */
    public function call($callback, array $parameters = [])
    {
        // TODO: Implementar resolução de dependências para callbacks
        // Por enquanto, apenas chamar diretamente
        if (is_callable($callback)) {
            return call_user_func_array($callback, $parameters);
        }

        throw new Exception("Callback inválido");
    }

    /**
     * Obter todos os bindings registrados
     *
     * @return array
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * Obter todas as instâncias singleton
     *
     * @return array
     */
    public function getInstances(): array
    {
        return $this->instances;
    }

    /**
     * Prevent cloning
     */
    private function __clone()
    {
    }

    /**
     * Prevent unserialization
     */
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }
}
