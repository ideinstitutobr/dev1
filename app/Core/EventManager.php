<?php
/**
 * Classe EventManager - Sistema de Eventos e Hooks
 * Sistema de Gestão de Capacitações (SGC)
 *
 * Implementa um sistema de eventos flexível que permite
 * extensibilidade através de listeners e hooks
 */

namespace App\Core;

use Closure;
use Exception;

class EventManager
{
    /**
     * Listeners registrados
     *
     * @var array
     */
    protected $listeners = [];

    /**
     * Listeners "wildcard" (*)
     *
     * @var array
     */
    protected $wildcards = [];

    /**
     * Cache de listeners já ordenados
     *
     * @var array
     */
    protected $sorted = [];

    /**
     * Eventos sendo disparados (para prevenir loops infinitos)
     *
     * @var array
     */
    protected $firing = [];

    /**
     * Registrar um listener para um evento
     *
     * @param string|array $events Nome do evento ou array de eventos
     * @param Closure|string $listener Callback do listener
     * @param int $priority Prioridade (maior = executa primeiro)
     * @return void
     */
    public function listen($events, $listener, int $priority = 0): void
    {
        foreach ((array) $events as $event) {
            if (strpos($event, '*') !== false) {
                $this->setupWildcardListen($event, $listener, $priority);
            } else {
                $this->listeners[$event][] = [
                    'listener' => $listener,
                    'priority' => $priority
                ];

                unset($this->sorted[$event]);
            }
        }
    }

    /**
     * Configurar listener wildcard
     *
     * @param string $event Nome do evento com *
     * @param Closure|string $listener Listener
     * @param int $priority Prioridade
     * @return void
     */
    protected function setupWildcardListen(string $event, $listener, int $priority): void
    {
        $this->wildcards[$event][] = [
            'listener' => $listener,
            'priority' => $priority
        ];
    }

    /**
     * Registrar um listener que executa apenas uma vez
     *
     * @param string $event Nome do evento
     * @param Closure|string $listener Listener
     * @param int $priority Prioridade
     * @return void
     */
    public function once(string $event, $listener, int $priority = 0): void
    {
        $wrapper = function (...$args) use ($event, $listener) {
            $this->forget($event, $listener);
            return call_user_func_array($listener, $args);
        };

        $this->listen($event, $wrapper, $priority);
    }

    /**
     * Disparar um evento
     *
     * @param string|object $event Nome do evento ou objeto
     * @param mixed $payload Dados do evento
     * @param bool $halt Parar ao receber false
     * @return array|mixed|null Respostas dos listeners
     */
    public function dispatch($event, $payload = [], bool $halt = false)
    {
        // Normalizar nome do evento
        list($event, $payload) = $this->parseEventAndPayload($event, $payload);

        // Prevenir loops infinitos
        if ($this->shouldPreventRecursion($event)) {
            return null;
        }

        $this->firing[] = $event;

        $responses = [];

        foreach ($this->getListeners($event) as $listener) {
            $response = $this->callListener($listener, $event, $payload);

            // Se halt=true e retornou false, parar
            if ($halt && $response === false) {
                array_pop($this->firing);
                return $response;
            }

            // Se retornou valor, adicionar às respostas
            if ($response !== null) {
                $responses[] = $response;
            }
        }

        array_pop($this->firing);

        return $halt ? null : $responses;
    }

    /**
     * Disparar evento até que um listener retorne um valor não-null
     *
     * @param string|object $event Nome do evento
     * @param mixed $payload Dados
     * @return mixed Primeira resposta não-null
     */
    public function until($event, $payload = [])
    {
        return $this->dispatch($event, $payload, true);
    }

    /**
     * Verificar se deve prevenir recursão
     *
     * @param string $event Nome do evento
     * @return bool
     */
    protected function shouldPreventRecursion(string $event): bool
    {
        // Permitir até 3 níveis de recursão
        $count = array_count_values($this->firing);
        return isset($count[$event]) && $count[$event] >= 3;
    }

    /**
     * Fazer parse do evento e payload
     *
     * @param string|object $event Evento
     * @param mixed $payload Payload
     * @return array [evento, payload]
     */
    protected function parseEventAndPayload($event, $payload): array
    {
        if (is_object($event)) {
            list($payload, $event) = [[$event], get_class($event)];
        }

        return [$event, (array) $payload];
    }

    /**
     * Chamar um listener
     *
     * @param Closure|string $listener Listener
     * @param string $event Nome do evento
     * @param array $payload Dados
     * @return mixed Resposta do listener
     */
    protected function callListener($listener, string $event, array $payload)
    {
        try {
            if (is_string($listener) && strpos($listener, '@') !== false) {
                return $this->callClassListener($listener, $event, $payload);
            }

            return call_user_func_array($listener, $payload);

        } catch (Exception $e) {
            $this->handleListenerException($e, $event, $listener);
            return null;
        }
    }

    /**
     * Chamar listener de classe
     *
     * @param string $listener Formato: 'Classe@metodo'
     * @param string $event Nome do evento
     * @param array $payload Dados
     * @return mixed
     */
    protected function callClassListener(string $listener, string $event, array $payload)
    {
        list($class, $method) = explode('@', $listener);

        // Tentar resolver do container
        if (function_exists('app')) {
            $instance = app($class);
        } else {
            $instance = new $class;
        }

        return call_user_func_array([$instance, $method], $payload);
    }

    /**
     * Tratar exceção de listener
     *
     * @param Exception $e Exceção
     * @param string $event Nome do evento
     * @param mixed $listener Listener
     * @return void
     */
    protected function handleListenerException(Exception $e, string $event, $listener): void
    {
        // Log do erro
        $listenerName = is_string($listener) ? $listener : 'Closure';
        error_log("Erro no listener '{$listenerName}' do evento '{$event}': " . $e->getMessage());

        // Em desenvolvimento, re-lançar exceção
        if (defined('APP_DEBUG') && APP_DEBUG) {
            throw $e;
        }
    }

    /**
     * Obter listeners de um evento
     *
     * @param string $event Nome do evento
     * @return array Listeners ordenados por prioridade
     */
    public function getListeners(string $event): array
    {
        // Usar cache se disponível
        if (isset($this->sorted[$event])) {
            return $this->sorted[$event];
        }

        $listeners = [];

        // Listeners diretos
        if (isset($this->listeners[$event])) {
            $listeners = array_merge($listeners, $this->listeners[$event]);
        }

        // Listeners wildcard
        foreach ($this->wildcards as $pattern => $wildcardListeners) {
            if ($this->eventMatches($event, $pattern)) {
                $listeners = array_merge($listeners, $wildcardListeners);
            }
        }

        // Ordenar por prioridade (maior primeiro)
        usort($listeners, function ($a, $b) {
            return $b['priority'] <=> $a['priority'];
        });

        // Extrair apenas os callbacks
        $listeners = array_map(function ($item) {
            return $item['listener'];
        }, $listeners);

        // Cache
        $this->sorted[$event] = $listeners;

        return $listeners;
    }

    /**
     * Verificar se evento corresponde ao padrão wildcard
     *
     * @param string $event Nome do evento
     * @param string $pattern Padrão com *
     * @return bool
     */
    protected function eventMatches(string $event, string $pattern): bool
    {
        if ($event === $pattern) {
            return true;
        }

        $pattern = preg_quote($pattern, '#');
        $pattern = str_replace('\*', '.*', $pattern);

        return (bool) preg_match('#^' . $pattern . '$#', $event);
    }

    /**
     * Verificar se tem listeners para um evento
     *
     * @param string $event Nome do evento
     * @return bool
     */
    public function hasListeners(string $event): bool
    {
        return count($this->getListeners($event)) > 0;
    }

    /**
     * Remover listener(s) de um evento
     *
     * @param string $event Nome do evento
     * @param Closure|string|null $listener Listener específico ou null para todos
     * @return void
     */
    public function forget(string $event, $listener = null): void
    {
        if ($listener === null) {
            // Remover todos os listeners do evento
            unset($this->listeners[$event], $this->sorted[$event]);
            return;
        }

        // Remover listener específico
        if (isset($this->listeners[$event])) {
            foreach ($this->listeners[$event] as $index => $registered) {
                if ($registered['listener'] === $listener) {
                    unset($this->listeners[$event][$index]);
                }
            }

            unset($this->sorted[$event]);
        }
    }

    /**
     * Remover todos os listeners
     *
     * @return void
     */
    public function flush(): void
    {
        $this->listeners = [];
        $this->wildcards = [];
        $this->sorted = [];
        $this->firing = [];
    }

    /**
     * Obter todos os eventos registrados
     *
     * @return array
     */
    public function getEvents(): array
    {
        return array_keys($this->listeners);
    }

    /**
     * Criar um subscriber de eventos
     *
     * @param object|string $subscriber Instância ou nome da classe
     * @return void
     */
    public function subscribe($subscriber): void
    {
        if (is_string($subscriber)) {
            $subscriber = function_exists('app') ? app($subscriber) : new $subscriber;
        }

        $subscriber->subscribe($this);
    }

    /**
     * Alias para listen() - estilo WordPress
     *
     * @param string $hook Nome do hook
     * @param Closure|string $callback Callback
     * @param int $priority Prioridade
     * @return void
     */
    public function addAction(string $hook, $callback, int $priority = 10): void
    {
        $this->listen($hook, $callback, $priority);
    }

    /**
     * Alias para dispatch() - estilo WordPress
     *
     * @param string $hook Nome do hook
     * @param mixed ...$args Argumentos
     * @return void
     */
    public function doAction(string $hook, ...$args): void
    {
        $this->dispatch($hook, $args);
    }

    /**
     * Filtro - estilo WordPress
     * Listeners devem retornar o valor modificado
     *
     * @param string $hook Nome do filtro
     * @param mixed $value Valor a filtrar
     * @param mixed ...$args Argumentos adicionais
     * @return mixed Valor filtrado
     */
    public function applyFilters(string $hook, $value, ...$args): mixed
    {
        array_unshift($args, $value);

        foreach ($this->getListeners($hook) as $listener) {
            $value = $this->callListener($listener, $hook, $args);
            $args[0] = $value;
        }

        return $value;
    }

    /**
     * Alias para forget() - estilo WordPress
     *
     * @param string $hook Nome do hook
     * @param Closure|string|null $callback Callback
     * @return void
     */
    public function removeAction(string $hook, $callback = null): void
    {
        $this->forget($hook, $callback);
    }

    /**
     * Verificar se hook tem ações - estilo WordPress
     *
     * @param string $hook Nome do hook
     * @return bool
     */
    public function hasAction(string $hook): bool
    {
        return $this->hasListeners($hook);
    }
}
