# PLANO DE REFATORAÇÃO - ARQUITETURA MODULAR E PLUGIN-BASED

**Data:** 09 de Novembro de 2025
**Sistema:** SGC - Sistema de Gestão de Capacitações
**Objetivo:** Transformar o sistema em uma arquitetura modular que permita adicionar funcionalidades sem quebrar o código existente
**Inspiração:** WordPress Plugins, Laravel Packages, Symfony Bundles

---

## 📋 ÍNDICE

1. [Visão Geral](#1-visão-geral)
2. [Estado Atual vs Estado Desejado](#2-estado-atual-vs-estado-desejado)
3. [Arquitetura Proposta](#3-arquitetura-proposta)
4. [Fases de Implementação](#4-fases-de-implementação)
5. [Sistema de Módulos/Plugins](#5-sistema-de-módulosplugins)
6. [Sistema de Eventos e Hooks](#6-sistema-de-eventos-e-hooks)
7. [Dependency Injection Container](#7-dependency-injection-container)
8. [Refatoração de Views](#8-refatoração-de-views)
9. [Sistema de Rotas Centralizado](#9-sistema-de-rotas-centralizado)
10. [Guia de Migração](#10-guia-de-migração)

---

## 1. VISÃO GERAL

### 1.1 Problema Atual

Ao analisar o código do SGC, identificamos os seguintes problemas que dificultam a adição de novas funcionalidades:

🔴 **Problemas Críticos:**
- **Acoplamento Forte**: Controllers dependem diretamente de Models específicos
- **Lógica em Views**: SQL e lógica de negócio em arquivos de apresentação
- **Sem Extensibilidade**: Não há hooks, eventos ou plugins
- **Código Duplicado**: Validações e lógicas repetidas em múltiplos lugares
- **Dependências Hardcoded**: `require_once` manual, sem DI

### 1.2 Objetivo da Refatoração

Criar uma arquitetura modular onde:

✅ **Novos módulos podem ser adicionados sem modificar o core**
✅ **Cada módulo é independente e desacoplado**
✅ **Sistema de eventos permite extensões**
✅ **Dependency Injection facilita testes**
✅ **Padrões claros para criar novos recursos**

### 1.3 Benefícios Esperados

| Antes | Depois |
|-------|--------|
| Adicionar feature quebra sistema | Features isoladas em módulos |
| Código duplicado em vários lugares | Reutilização através de serviços |
| Difícil testar | Testes unitários com mocks |
| 1 aplicação monolítica | Core + Módulos independentes |
| Mudanças arriscadas | Mudanças seguras e isoladas |

---

## 2. ESTADO ATUAL VS ESTADO DESEJADO

### 2.1 Arquitetura Atual

```
┌─────────────────────────────────────────────────┐
│           APLICAÇÃO MONOLÍTICA                  │
│                                                 │
│  public/dashboard.php                           │
│    ├─ SQL direto                                │
│    ├─ require_once Models                       │
│    └─ HTML inline                               │
│                                                 │
│  Controllers (15)                               │
│    ├─ TreinamentoController                     │
│    │   └─ new Treinamento() [hardcoded]         │
│    ├─ ColaboradorController                     │
│    │   └─ new Colaborador() [hardcoded]         │
│    └─ ...                                       │
│                                                 │
│  Models (26)                                    │
│    └─ Queries SQL diretas                       │
│                                                 │
│  Database (Singleton)                           │
└─────────────────────────────────────────────────┘

PROBLEMA: Tudo está entrelaçado
```

### 2.2 Arquitetura Desejada (Modular)

```
┌─────────────────────────────────────────────────┐
│                CORE SYSTEM                      │
│  ┌───────────────────────────────────────────┐  │
│  │  Router → Middleware → Controller         │  │
│  │     ↓           ↓            ↓            │  │
│  │  Events    DI Container   Services        │  │
│  └───────────────────────────────────────────┘  │
└─────────────────────────────────────────────────┘
              ↓          ↓          ↓
    ┌─────────────┐ ┌─────────────┐ ┌─────────────┐
    │  MÓDULO 1   │ │  MÓDULO 2   │ │  MÓDULO 3   │
    │ Treinamento │ │ Colaborador │ │ Checklist   │
    ├─────────────┤ ├─────────────┤ ├─────────────┤
    │ - Routes    │ │ - Routes    │ │ - Routes    │
    │ - Controller│ │ - Controller│ │ - Controller│
    │ - Models    │ │ - Models    │ │ - Models    │
    │ - Views     │ │ - Views     │ │ - Views     │
    │ - Events    │ │ - Events    │ │ - Events    │
    └─────────────┘ └─────────────┘ └─────────────┘

BENEFÍCIO: Módulos independentes, desacoplados
```

---

## 3. ARQUITETURA PROPOSTA

### 3.1 Estrutura de Diretórios Nova

```
/app/
├── Core/                          # Sistema base (não modificar)
│   ├── Application.php            # Bootstrap da aplicação
│   ├── Router.php                 # Roteamento centralizado
│   ├── Container.php              # Dependency Injection
│   ├── EventManager.php           # Sistema de eventos/hooks
│   ├── ModuleManager.php          # Gerenciador de módulos
│   ├── ServiceProvider.php        # Base para providers
│   ├── Controller.php             # Controller base
│   ├── Model.php                  # Model base
│   └── View.php                   # Motor de template
│
├── Modules/                       # Módulos do sistema (plugins)
│   ├── Treinamento/               # Módulo de treinamentos
│   │   ├── module.json            # Metadados do módulo
│   │   ├── TreinamentoServiceProvider.php
│   │   ├── Controllers/
│   │   │   └── TreinamentoController.php
│   │   ├── Models/
│   │   │   └── Treinamento.php
│   │   ├── Views/
│   │   │   ├── index.php
│   │   │   ├── create.php
│   │   │   └── edit.php
│   │   ├── Routes/
│   │   │   └── web.php
│   │   ├── Events/
│   │   │   └── TreinamentoCriadoEvent.php
│   │   ├── Services/
│   │   │   └── TreinamentoService.php
│   │   └── Migrations/
│   │       └── 001_create_treinamentos.sql
│   │
│   ├── Colaborador/               # Módulo de colaboradores
│   │   └── ... (mesma estrutura)
│   │
│   ├── Checklist/                 # Módulo de checklists
│   │   └── ...
│   │
│   └── FormularioDinamico/        # Módulo de formulários
│       └── ...
│
├── Services/                      # Serviços globais compartilhados
│   ├── AuthService.php
│   ├── ValidationService.php
│   ├── DatabaseService.php
│   └── MailService.php
│
├── Middleware/                    # Middleware global
│   ├── AuthMiddleware.php
│   ├── CsrfMiddleware.php
│   └── RateLimitMiddleware.php
│
├── config/                        # Configurações
│   ├── app.php
│   ├── database.php
│   ├── modules.php                # Lista de módulos ativos
│   └── .env.example
│
└── bootstrap/                     # Inicialização
    └── app.php
```

### 3.2 Como Funciona

#### Fluxo de Requisição

```
1. public/index.php
   ↓
2. bootstrap/app.php
   ├─ Carrega .env
   ├─ Inicializa Container (DI)
   ├─ Registra Services
   └─ Carrega Módulos ativos
   ↓
3. Core/Application.php
   ├─ Router recebe requisição
   ├─ Middleware Chain
   └─ Dispatch para Controller
   ↓
4. Módulo/Controller
   ├─ Usa Services (injetados)
   ├─ Dispara Eventos
   └─ Retorna View
   ↓
5. Core/View.php
   └─ Renderiza template
```

---

## 4. FASES DE IMPLEMENTAÇÃO

### FASE 1: FUNDAÇÃO (1-2 semanas, 20-30 horas)

**Objetivo:** Criar o core do sistema sem quebrar o código existente

#### Sprint 1.1: Core Classes (8 horas)
- [ ] Criar `Core/Container.php` - Dependency Injection
- [ ] Criar `Core/EventManager.php` - Sistema de eventos
- [ ] Criar `Core/ModuleManager.php` - Gerenciar módulos
- [ ] Criar `Core/ServiceProvider.php` - Base para providers
- [ ] Testes unitários do core

#### Sprint 1.2: Router e Middleware (6 horas)
- [ ] Criar `Core/Router.php` - Roteamento centralizado
- [ ] Criar `Middleware/AuthMiddleware.php`
- [ ] Criar `Middleware/CsrfMiddleware.php`
- [ ] Migrar 2-3 rotas existentes para testar

#### Sprint 1.3: Base Classes (6 horas)
- [ ] Criar `Core/Controller.php` - Controller base
- [ ] Criar `Core/Model.php` - Model base
- [ ] Criar `Core/View.php` - Motor de template
- [ ] Helpers e utilities

---

### FASE 2: MIGRAÇÃO DE 1 MÓDULO (1 semana, 15 horas)

**Objetivo:** Migrar o módulo "Treinamento" como prova de conceito

#### Sprint 2.1: Estrutura do Módulo (4 horas)
- [ ] Criar estrutura `Modules/Treinamento/`
- [ ] Criar `module.json` com metadados
- [ ] Criar `TreinamentoServiceProvider.php`
- [ ] Criar arquivo de rotas `Routes/web.php`

#### Sprint 2.2: Migração do Código (6 horas)
- [ ] Migrar `TreinamentoController.php` para o módulo
- [ ] Adaptar para usar DI e eventos
- [ ] Migrar `Treinamento.php` (model)
- [ ] Criar `TreinamentoService.php` (lógica de negócio)

#### Sprint 2.3: Views e Testes (5 horas)
- [ ] Migrar views para `Views/`
- [ ] Adaptar templates
- [ ] Testes de integração do módulo
- [ ] Documentação do módulo

---

### FASE 3: MIGRAÇÃO COMPLETA (4-6 semanas, 60-80 horas)

**Objetivo:** Migrar todos os módulos restantes

#### Sprint 3.1-3.14: Migrar Módulos (15 módulos)
Para cada módulo (~4 horas cada):
- [ ] Colaborador
- [ ] Participante
- [ ] Frequencia
- [ ] Checklist
- [ ] FormularioDinamico
- [ ] Relatorio
- [ ] Portal
- [ ] Unidade
- [ ] UnidadeColaborador
- [ ] UnidadeSetor
- [ ] UnidadeLideranca
- [ ] CategoriaLocalUnidade
- [ ] Agenda
- [ ] RelatorioChecklist
- [ ] Configuracao

---

### FASE 4: REFINAMENTO (1-2 semanas, 15-20 horas)

**Objetivo:** Polir e otimizar

#### Sprint 4.1: Performance (6 horas)
- [ ] Implementar cache (Redis/Memcached)
- [ ] Otimizar queries
- [ ] Lazy loading de módulos

#### Sprint 4.2: Documentação (5 horas)
- [ ] Documentar API de módulos
- [ ] Guia de criação de módulos
- [ ] Exemplos práticos

#### Sprint 4.3: Testes Finais (4 horas)
- [ ] Testes de integração completos
- [ ] Testes de performance
- [ ] Testes de segurança

---

## 5. SISTEMA DE MÓDULOS/PLUGINS

### 5.1 Estrutura de um Módulo

Cada módulo segue este padrão:

```
Modules/NomeDoModulo/
├── module.json                    # Metadados
├── NomeDoModuloServiceProvider.php # Provider principal
├── Controllers/                   # Controladores
├── Models/                        # Modelos de dados
├── Views/                         # Templates
├── Routes/                        # Definição de rotas
│   ├── web.php
│   └── api.php
├── Events/                        # Eventos disparados
├── Listeners/                     # Ouvintes de eventos
├── Services/                      # Lógica de negócio
├── Migrations/                    # Migrações de BD
├── Config/                        # Configurações do módulo
├── Assets/                        # CSS, JS específicos
└── README.md                      # Documentação
```

### 5.2 Arquivo `module.json`

```json
{
  "name": "Treinamento",
  "slug": "treinamento",
  "description": "Módulo de gestão de treinamentos",
  "version": "1.0.0",
  "author": "IDE Instituto",
  "dependencies": [
    "core": ">=1.0.0",
    "colaborador": ">=1.0.0"
  ],
  "provider": "TreinamentoServiceProvider",
  "autoload": {
    "psr-4": {
      "App\\Modules\\Treinamento\\": "src/"
    }
  },
  "routes": [
    "Routes/web.php",
    "Routes/api.php"
  ],
  "migrations": "Migrations/",
  "assets": "Assets/",
  "permissions": [
    "treinamento.view",
    "treinamento.create",
    "treinamento.edit",
    "treinamento.delete"
  ]
}
```

### 5.3 Service Provider

```php
<?php
// Modules/Treinamento/TreinamentoServiceProvider.php

namespace App\Modules\Treinamento;

use App\Core\ServiceProvider;
use App\Core\Container;

class TreinamentoServiceProvider extends ServiceProvider
{
    /**
     * Registrar serviços no container
     */
    public function register(Container $container)
    {
        // Registrar model
        $container->bind('Treinamento', function($c) {
            return new Models\Treinamento($c->get('Database'));
        });

        // Registrar service
        $container->bind('TreinamentoService', function($c) {
            return new Services\TreinamentoService(
                $c->get('Treinamento'),
                $c->get('EventManager')
            );
        });
    }

    /**
     * Executar após todos os módulos registrados
     */
    public function boot(Container $container)
    {
        // Registrar rotas
        $this->loadRoutes(__DIR__ . '/Routes/web.php');

        // Registrar listeners de eventos
        $events = $container->get('EventManager');
        $events->listen('colaborador.criado', function($colaborador) {
            // Fazer algo quando colaborador é criado
        });

        // Registrar views
        $this->loadViews(__DIR__ . '/Views', 'treinamento');

        // Registrar migrations
        $this->loadMigrations(__DIR__ . '/Migrations');
    }
}
```

### 5.4 Module Manager

```php
<?php
// Core/ModuleManager.php

namespace App\Core;

class ModuleManager
{
    private $modules = [];
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Carregar módulos ativos
     */
    public function loadModules()
    {
        $modulesConfig = require APP_PATH . 'config/modules.php';

        foreach ($modulesConfig['active'] as $moduleName) {
            $this->loadModule($moduleName);
        }
    }

    /**
     * Carregar um módulo específico
     */
    private function loadModule(string $moduleName)
    {
        $modulePath = APP_PATH . "Modules/{$moduleName}/";
        $moduleFile = $modulePath . 'module.json';

        if (!file_exists($moduleFile)) {
            throw new \Exception("Módulo {$moduleName} não encontrado");
        }

        $moduleConfig = json_decode(file_get_contents($moduleFile), true);

        // Verificar dependências
        $this->checkDependencies($moduleConfig['dependencies'] ?? []);

        // Instanciar provider
        $providerClass = "App\\Modules\\{$moduleName}\\" . $moduleConfig['provider'];
        $provider = new $providerClass();

        // Registrar no container
        $provider->register($this->container);

        // Salvar para boot posterior
        $this->modules[$moduleName] = [
            'config' => $moduleConfig,
            'provider' => $provider
        ];
    }

    /**
     * Boot de todos os módulos
     */
    public function bootModules()
    {
        foreach ($this->modules as $name => $module) {
            $module['provider']->boot($this->container);
        }
    }

    /**
     * Verificar se dependências estão satisfeitas
     */
    private function checkDependencies(array $dependencies)
    {
        foreach ($dependencies as $dep => $version) {
            // Implementar verificação de versão
        }
    }

    /**
     * Listar módulos ativos
     */
    public function getActiveModules(): array
    {
        return array_keys($this->modules);
    }

    /**
     * Ativar um módulo
     */
    public function enableModule(string $moduleName)
    {
        // Implementar lógica de ativação
    }

    /**
     * Desativar um módulo
     */
    public function disableModule(string $moduleName)
    {
        // Implementar lógica de desativação
    }
}
```

### 5.5 Configuração de Módulos

```php
<?php
// config/modules.php

return [
    'active' => [
        'Colaborador',
        'Treinamento',
        'Participante',
        'Frequencia',
        'Checklist',
        'FormularioDinamico',
        'Relatorio',
        'Portal',
        'Unidade',
        // ... outros módulos
    ],

    'disabled' => [
        // Módulos desativados
    ],

    'paths' => [
        'modules' => APP_PATH . 'Modules/',
    ]
];
```

---

## 6. SISTEMA DE EVENTOS E HOOKS

### 6.1 Event Manager

```php
<?php
// Core/EventManager.php

namespace App\Core;

class EventManager
{
    private $listeners = [];

    /**
     * Registrar um listener para um evento
     */
    public function listen(string $event, callable $callback, int $priority = 0)
    {
        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }

        $this->listeners[$event][] = [
            'callback' => $callback,
            'priority' => $priority
        ];

        // Ordenar por prioridade
        usort($this->listeners[$event], function($a, $b) {
            return $b['priority'] <=> $a['priority'];
        });
    }

    /**
     * Disparar um evento
     */
    public function dispatch(string $event, $data = null)
    {
        if (!isset($this->listeners[$event])) {
            return $data;
        }

        foreach ($this->listeners[$event] as $listener) {
            $result = call_user_func($listener['callback'], $data);

            // Se retornar false, para a propagação
            if ($result === false) {
                break;
            }

            // Se retornar um valor, usa como novo $data
            if ($result !== null) {
                $data = $result;
            }
        }

        return $data;
    }

    /**
     * Remover listeners de um evento
     */
    public function remove(string $event)
    {
        unset($this->listeners[$event]);
    }
}
```

### 6.2 Eventos Principais do Sistema

```php
// Eventos de Colaborador
'colaborador.antes.criar'     // Antes de criar
'colaborador.criado'          // Depois de criado
'colaborador.antes.atualizar'
'colaborador.atualizado'
'colaborador.antes.deletar'
'colaborador.deletado'

// Eventos de Treinamento
'treinamento.criado'
'treinamento.atualizado'
'treinamento.cancelado'
'treinamento.executado'

// Eventos de Participante
'participante.inscrito'
'participante.removido'

// Eventos de Frequência
'frequencia.checkin'
'frequencia.atualizada'

// Eventos de Sistema
'sistema.iniciado'
'usuario.logado'
'usuario.deslogado'
'rota.resolvida'
'view.renderizada'
```

### 6.3 Exemplo de Uso de Eventos

```php
<?php
// Módulo de Notificações ouvindo evento de treinamento

// Modules/Notificacao/NotificacaoServiceProvider.php
public function boot(Container $container)
{
    $events = $container->get('EventManager');

    // Quando um treinamento é criado, enviar e-mail
    $events->listen('treinamento.criado', function($treinamento) {
        $mailer = Container::get('MailService');
        $mailer->send([
            'to' => 'admin@exemplo.com',
            'subject' => 'Novo Treinamento Criado',
            'body' => "Treinamento: {$treinamento['nome']}"
        ]);
    });

    // Quando participante é inscrito, notificar
    $events->listen('participante.inscrito', function($participante) {
        // Enviar e-mail de boas-vindas
    }, priority: 10);
}
```

### 6.4 Hooks WordPress-Style

```php
<?php
// Core/Hooks.php - Camada de compatibilidade com estilo WordPress

function add_action(string $hook, callable $callback, int $priority = 10)
{
    app('EventManager')->listen($hook, $callback, $priority);
}

function do_action(string $hook, $data = null)
{
    return app('EventManager')->dispatch($hook, $data);
}

function add_filter(string $hook, callable $callback, int $priority = 10)
{
    app('EventManager')->listen($hook, $callback, $priority);
}

function apply_filters(string $hook, $value)
{
    return app('EventManager')->dispatch($hook, $value);
}

// Uso:
add_action('treinamento.criado', function($treinamento) {
    // Fazer algo
});

do_action('treinamento.criado', $treinamento);

// Filtros:
$titulo = apply_filters('treinamento.titulo', $treinamento->nome);
```

---

## 7. DEPENDENCY INJECTION CONTAINER

### 7.1 Container Simples

```php
<?php
// Core/Container.php

namespace App\Core;

class Container
{
    private static $instance = null;
    private $bindings = [];
    private $instances = [];

    /**
     * Singleton
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Registrar uma binding
     */
    public function bind(string $abstract, $concrete = null, bool $singleton = false)
    {
        if ($concrete === null) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'singleton' => $singleton
        ];
    }

    /**
     * Registrar um singleton
     */
    public function singleton(string $abstract, $concrete = null)
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Resolver uma dependência
     */
    public function get(string $abstract)
    {
        // Se já foi instanciado como singleton, retornar
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // Se não está registrado, tentar autoload
        if (!isset($this->bindings[$abstract])) {
            return $this->build($abstract);
        }

        $concrete = $this->bindings[$abstract]['concrete'];

        // Se é um closure, executar
        if ($concrete instanceof \Closure) {
            $object = $concrete($this);
        } else {
            $object = $this->build($concrete);
        }

        // Se é singleton, guardar instância
        if ($this->bindings[$abstract]['singleton']) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * Construir uma classe com injeção automática
     */
    private function build(string $class)
    {
        $reflection = new \ReflectionClass($class);

        // Se não tem construtor, instanciar direto
        if (!$reflection->isInstantiable()) {
            throw new \Exception("Classe {$class} não é instanciável");
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return new $class;
        }

        // Resolver dependências do construtor
        $dependencies = [];
        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();

            if ($type === null) {
                throw new \Exception("Parâmetro sem tipo: {$param->getName()}");
            }

            $dependencies[] = $this->get($type->getName());
        }

        return $reflection->newInstanceArgs($dependencies);
    }

    /**
     * Verificar se está registrado
     */
    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }
}
```

### 7.2 Exemplo de Uso

```php
<?php
// bootstrap/app.php

$container = Container::getInstance();

// Registrar Database como singleton
$container->singleton('Database', function($c) {
    return Database::getInstance();
});

// Registrar Auth
$container->singleton('Auth', function($c) {
    return new Auth($c->get('Database'));
});

// Registrar EventManager
$container->singleton('EventManager', function($c) {
    return new EventManager();
});

// Registrar TreinamentoService
$container->bind('TreinamentoService', function($c) {
    return new TreinamentoService(
        $c->get('Treinamento'),      // Injeção automática
        $c->get('EventManager')
    );
});

// Uso nos controllers (injeção automática):
class TreinamentoController extends Controller
{
    private $service;
    private $events;

    public function __construct(
        TreinamentoService $service,    // Injetado automaticamente
        EventManager $events
    ) {
        $this->service = $service;
        $this->events = $events;
    }

    public function criar()
    {
        $treinamento = $this->service->criar($_POST);
        $this->events->dispatch('treinamento.criado', $treinamento);
    }
}
```

### 7.3 Helper Global

```php
<?php
// app/helpers.php

/**
 * Resolver do container
 */
function app(string $abstract = null)
{
    $container = \App\Core\Container::getInstance();

    if ($abstract === null) {
        return $container;
    }

    return $container->get($abstract);
}

// Uso:
$auth = app('Auth');
$db = app('Database');
```

---

## 8. REFATORAÇÃO DE VIEWS

### 8.1 Motor de Template Simples

```php
<?php
// Core/View.php

namespace App\Core;

class View
{
    private $viewPath;
    private $layoutPath;
    private $data = [];

    public function __construct(string $viewsPath, string $layoutsPath)
    {
        $this->viewPath = $viewsPath;
        $this->layoutPath = $layoutsPath;
    }

    /**
     * Renderizar uma view
     */
    public function render(string $view, array $data = [], string $layout = 'main')
    {
        $this->data = $data;

        // Capturar conteúdo da view
        ob_start();
        extract($data);
        require $this->viewPath . '/' . $view . '.php';
        $content = ob_get_clean();

        // Se tem layout, renderizar dentro dele
        if ($layout) {
            ob_start();
            require $this->layoutPath . '/' . $layout . '.php';
            return ob_get_clean();
        }

        return $content;
    }

    /**
     * Incluir uma partial
     */
    public function partial(string $name, array $data = [])
    {
        extract(array_merge($this->data, $data));
        require $this->viewPath . '/partials/' . $name . '.php';
    }

    /**
     * Escapar HTML
     */
    public function e(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}
```

### 8.2 Layout Base

```php
<!-- app/views/layouts/main.php -->
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $this->e($pageTitle ?? 'SGC'); ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/main.css">
</head>
<body>
    <?php $this->partial('header'); ?>

    <div class="container">
        <?php $this->partial('sidebar'); ?>

        <main class="content">
            <?php echo $content; ?>
        </main>
    </div>

    <?php $this->partial('footer'); ?>

    <script src="<?php echo ASSETS_URL; ?>js/main.js"></script>
</body>
</html>
```

### 8.3 View de Módulo

```php
<!-- Modules/Treinamento/Views/index.php -->

<div class="page-header">
    <h1><?php echo $this->e($titulo); ?></h1>
    <a href="<?php echo url('treinamentos/criar'); ?>" class="btn btn-primary">
        Novo Treinamento
    </a>
</div>

<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Tipo</th>
                <th>Data</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($treinamentos as $t): ?>
            <tr>
                <td><?php echo $this->e($t['nome']); ?></td>
                <td><?php echo $this->e($t['tipo']); ?></td>
                <td><?php echo date('d/m/Y', strtotime($t['data_inicio'])); ?></td>
                <td>
                    <a href="<?php echo url("treinamentos/{$t['id']}"); ?>">Ver</a>
                    <a href="<?php echo url("treinamentos/{$t['id']}/editar"); ?>">Editar</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
```

---

## 9. SISTEMA DE ROTAS CENTRALIZADO

### 9.1 Router

```php
<?php
// Core/Router.php

namespace App\Core;

class Router
{
    private $routes = [];
    private $container;
    private $middleware = [];

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Registrar rota GET
     */
    public function get(string $uri, $action, array $middleware = [])
    {
        $this->addRoute('GET', $uri, $action, $middleware);
    }

    /**
     * Registrar rota POST
     */
    public function post(string $uri, $action, array $middleware = [])
    {
        $this->addRoute('POST', $uri, $action, $middleware);
    }

    /**
     * Adicionar rota
     */
    private function addRoute(string $method, string $uri, $action, array $middleware)
    {
        $this->routes[] = [
            'method' => $method,
            'uri' => $uri,
            'action' => $action,
            'middleware' => $middleware
        ];
    }

    /**
     * Resolver rota
     */
    public function resolve(string $method, string $uri)
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = $this->convertToRegex($route['uri']);

            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches); // Remove full match

                // Executar middleware
                $this->runMiddleware($route['middleware']);

                // Executar action
                return $this->callAction($route['action'], $matches);
            }
        }

        throw new \Exception("Rota não encontrada: {$method} {$uri}", 404);
    }

    /**
     * Converter URI para regex
     */
    private function convertToRegex(string $uri): string
    {
        $uri = preg_replace('/\{(\w+)\}/', '([^/]+)', $uri);
        return '#^' . $uri . '$#';
    }

    /**
     * Executar middleware
     */
    private function runMiddleware(array $middleware)
    {
        foreach ($middleware as $mw) {
            $instance = $this->container->get($mw);
            $instance->handle();
        }
    }

    /**
     * Chamar action
     */
    private function callAction($action, array $params)
    {
        if (is_callable($action)) {
            return call_user_func_array($action, $params);
        }

        if (is_string($action)) {
            [$controller, $method] = explode('@', $action);
            $instance = $this->container->get($controller);
            return call_user_func_array([$instance, $method], $params);
        }
    }
}
```

### 9.2 Definição de Rotas de Módulo

```php
<?php
// Modules/Treinamento/Routes/web.php

$router->get('/treinamentos', 'TreinamentoController@index', [
    'auth'
]);

$router->get('/treinamentos/criar', 'TreinamentoController@create', [
    'auth', 'admin'
]);

$router->post('/treinamentos', 'TreinamentoController@store', [
    'auth', 'csrf', 'admin'
]);

$router->get('/treinamentos/{id}', 'TreinamentoController@show', [
    'auth'
]);

$router->get('/treinamentos/{id}/editar', 'TreinamentoController@edit', [
    'auth', 'admin'
]);

$router->post('/treinamentos/{id}', 'TreinamentoController@update', [
    'auth', 'csrf', 'admin'
]);

$router->post('/treinamentos/{id}/deletar', 'TreinamentoController@destroy', [
    'auth', 'csrf', 'admin'
]);
```

---

## 10. GUIA DE MIGRAÇÃO

### 10.1 Checklist de Migração por Módulo

Para cada módulo:

```
[ ] 1. Criar estrutura de diretórios
[ ] 2. Criar module.json
[ ] 3. Criar ServiceProvider
[ ] 4. Migrar Controller
    [ ] Adicionar injeção de dependências
    [ ] Remover require_once
    [ ] Adicionar eventos
[ ] 5. Migrar Model
    [ ] Herdar de Core\Model
    [ ] Mover queries complexas para Service
[ ] 6. Criar Service (se necessário)
[ ] 7. Migrar Views
    [ ] Adaptar para novo template engine
    [ ] Remover lógica SQL
[ ] 8. Criar rotas em Routes/web.php
[ ] 9. Migrar migrations
[ ] 10. Testes
[ ] 11. Documentação
```

### 10.2 Exemplo Completo: Migração do Módulo Treinamento

#### Antes (Código Atual)

```php
// public/treinamentos/index.php (ANTES)
<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Auth.php';
require_once __DIR__ . '/../../app/controllers/TreinamentoController.php';

// Verificar autenticação
if (!Auth::isLogged()) {
    header('Location: ' . BASE_URL);
    exit;
}

// Buscar treinamentos
$controller = new TreinamentoController();
$result = $controller->listar();
$treinamentos = $result['data'];

include __DIR__ . '/../../app/views/layouts/header.php';
?>

<h1>Treinamentos</h1>
<table>
    <?php foreach ($treinamentos as $t): ?>
    <tr>
        <td><?php echo e($t['nome']); ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>
```

#### Depois (Código Modular)

```php
// Modules/Treinamento/Controllers/TreinamentoController.php (DEPOIS)
<?php

namespace App\Modules\Treinamento\Controllers;

use App\Core\Controller;
use App\Modules\Treinamento\Services\TreinamentoService;
use App\Core\EventManager;

class TreinamentoController extends Controller
{
    private $service;
    private $events;

    // Injeção de dependências
    public function __construct(
        TreinamentoService $service,
        EventManager $events
    ) {
        $this->service = $service;
        $this->events = $events;
    }

    /**
     * Listar treinamentos
     */
    public function index()
    {
        $treinamentos = $this->service->listar($_GET);

        return $this->view('treinamento::index', [
            'titulo' => 'Treinamentos',
            'treinamentos' => $treinamentos
        ]);
    }

    /**
     * Criar treinamento
     */
    public function store()
    {
        $treinamento = $this->service->criar($_POST);

        // Disparar evento
        $this->events->dispatch('treinamento.criado', $treinamento);

        return $this->redirect('/treinamentos')
            ->with('success', 'Treinamento criado com sucesso!');
    }
}
```

```php
// Modules/Treinamento/Services/TreinamentoService.php
<?php

namespace App\Modules\Treinamento\Services;

use App\Modules\Treinamento\Models\Treinamento;
use App\Core\EventManager;

class TreinamentoService
{
    private $model;
    private $events;

    public function __construct(Treinamento $model, EventManager $events)
    {
        $this->model = $model;
        $this->events = $events;
    }

    public function listar(array $params = [])
    {
        // Aplicar filtros
        $params = $this->events->dispatch('treinamento.filtros', $params);

        return $this->model->listar($params);
    }

    public function criar(array $dados)
    {
        // Validar
        $this->validar($dados);

        // Evento antes de criar
        $dados = $this->events->dispatch('treinamento.antes.criar', $dados);

        // Criar
        $treinamento = $this->model->criar($dados);

        return $treinamento;
    }

    private function validar(array $dados)
    {
        // Lógica de validação
    }
}
```

```php
// Modules/Treinamento/Routes/web.php
<?php

$router->get('/treinamentos', 'TreinamentoController@index', ['auth']);
$router->post('/treinamentos', 'TreinamentoController@store', ['auth', 'csrf']);
```

```php
// Modules/Treinamento/module.json
{
  "name": "Treinamento",
  "slug": "treinamento",
  "version": "1.0.0",
  "provider": "TreinamentoServiceProvider"
}
```

---

## 11. RESUMO E PRÓXIMOS PASSOS

### Benefícios da Refatoração

✅ **Modularidade**: Cada feature é um módulo independente
✅ **Extensibilidade**: Adicionar recursos sem modificar o core
✅ **Testabilidade**: DI facilita testes unitários
✅ **Manutenibilidade**: Código organizado e desacoplado
✅ **Escalabilidade**: Módulos podem ser ativados/desativados
✅ **Reusabilidade**: Services e eventos compartilhados

### Timeline Estimada

| Fase | Duração | Esforço |
|------|---------|---------|
| Fase 1: Fundação | 1-2 semanas | 20-30h |
| Fase 2: 1 Módulo POC | 1 semana | 15h |
| Fase 3: Migração Completa | 4-6 semanas | 60-80h |
| Fase 4: Refinamento | 1-2 semanas | 15-20h |
| **TOTAL** | **7-11 semanas** | **110-145h** |

### Próximos Passos Imediatos

1. **Aprovar o plano** - Revisar e ajustar conforme necessário
2. **Sprint 1.1** - Começar com Core/Container.php e Core/EventManager.php
3. **POC** - Migrar módulo Treinamento como prova de conceito
4. **Avaliar** - Avaliar o resultado e ajustar estratégia
5. **Continuar** - Migrar os demais módulos gradualmente

---

**FIM DO PLANO DE REFATORAÇÃO**

Próximo arquivo: `GUIA_IMPLEMENTACAO_NOVOS_RECURSOS.md`
