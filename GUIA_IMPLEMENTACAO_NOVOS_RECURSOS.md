# GUIA DE IMPLEMENTAÇÃO DE NOVOS RECURSOS

**Data:** 09 de Novembro de 2025
**Sistema:** SGC - Sistema de Gestão de Capacitações
**Objetivo:** Fornecer um guia passo a passo para implementar novos recursos sem quebrar o sistema existente

---

## 📋 ÍNDICE

1. [Visão Geral](#1-visão-geral)
2. [Regras e Padrões Obrigatórios](#2-regras-e-padrões-obrigatórios)
3. [Estrutura MVC do Projeto](#3-estrutura-mvc-do-projeto)
4. [Criando um Novo Módulo](#4-criando-um-novo-módulo)
5. [Criando um CRUD Completo](#5-criando-um-crud-completo)
6. [Trabalhando com Banco de Dados](#6-trabalhando-com-banco-de-dados)
7. [Sistema de Autenticação e Autorização](#7-sistema-de-autenticação-e-autorização)
8. [Validação de Dados](#8-validação-de-dados)
9. [Trabalhando com Views](#9-trabalhando-com-views)
10. [Integração entre Módulos](#10-integração-entre-módulos)
11. [Testes](#11-testes)
12. [Checklist Final](#12-checklist-final)

---

## 1. VISÃO GERAL

### 1.1 Filosofia do Sistema

O SGC segue o padrão **MVC (Model-View-Controller)** com arquitetura modular. Cada novo recurso deve:

✅ **Seguir o padrão MVC** - Separação clara de responsabilidades
✅ **Ser modular** - Funcionar como um módulo independente
✅ **Ser testável** - Código desacoplado e injetável
✅ **Ser seguro** - Validação, CSRF, prepared statements
✅ **Ser documentado** - Código claro e comentado

### 1.2 Fluxo de Desenvolvimento

```
1. Planejar → 2. Criar Módulo → 3. Model → 4. Controller → 5. View → 6. Rotas → 7. Testar → 8. Documentar
```

---

## 2. REGRAS E PADRÕES OBRIGATÓRIOS

### 2.1 Regras de Ouro

#### ✅ SEMPRE:
1. **Use prepared statements** - NUNCA concatene SQL
2. **Valide entradas** - Server-side sempre, client-side como bonus
3. **Use CSRF tokens** - Em todos os formulários POST
4. **Escape outputs** - Use `htmlspecialchars()` ou `e()`
5. **Verifique autenticação** - Em todas as rotas protegidas
6. **Trate exceções** - Use try-catch em operações de BD
7. **Siga PSR** - PSR-1, PSR-2, PSR-4 (autoloading)
8. **Documente** - PHPDoc em classes e métodos
9. **Injete dependências** - Não use `new` dentro de classes
10. **Dispare eventos** - Para permitir extensões

#### ❌ NUNCA:
1. **SQL injection** - Nunca concatene variáveis em SQL
2. **Credenciais hardcoded** - Use .env
3. **Senhas em texto puro** - Sempre use `password_hash()`
4. **Lógica em views** - Views só apresentam dados
5. **SQL em controllers** - Use models ou services
6. **require_once excessivo** - Use autoloading
7. **Superglobais diretas** - Sanitize $_POST, $_GET
8. **echo/print em classes** - Retorne dados
9. **exit/die em classes** - Lance exceções
10. **Código duplicado** - Crie helpers ou traits

### 2.2 Padrões de Nomenclatura

#### Classes
```php
// Singular, PascalCase
Treinamento.php
ColaboradorController.php
TreinamentoService.php
```

#### Métodos
```php
// camelCase, verbos descritivos
public function listar()
public function buscarPorId($id)
public function criarNovo($dados)
```

#### Variáveis
```php
// camelCase, descritivas
$treinamento
$listaColaboradores
$dadosFormulario
```

#### Constantes
```php
// UPPERCASE, snake_case
define('BASE_PATH', '/path');
const ITEMS_PER_PAGE = 20;
```

#### Tabelas do Banco
```php
// plural, snake_case
treinamentos
colaboradores
unidade_setores
```

### 2.3 Estrutura de Código

#### PSR-1 e PSR-2
```php
<?php
// Sempre <?php, nunca <?

namespace App\Modules\Treinamento\Controllers;  // Namespace obrigatório

use App\Core\Controller;  // Imports no topo

/**
 * Controller de Treinamentos
 */
class TreinamentoController extends Controller  // PascalCase
{
    private $service;  // Propriedades no topo

    /**
     * Construtor
     */
    public function __construct(TreinamentoService $service)  // 4 espaços
    {  // Chave em nova linha
        $this->service = $service;
    }

    /**
     * Listar treinamentos
     */
    public function listar()
    {
        $treinamentos = $this->service->listar();
        return $this->view('treinamento::index', [
            'treinamentos' => $treinamentos
        ]);
    }
}
```

---

## 3. ESTRUTURA MVC DO PROJETO

### 3.1 Camadas do MVC

```
┌─────────────────────────────────────────────────┐
│                  VIEW LAYER                     │
│  (Apresentação - HTML, Templates)               │
│  Responsabilidade: Exibir dados ao usuário      │
│  Localização: Modules/{Nome}/Views/             │
└─────────────────────────────────────────────────┘
                      ↑
                      │ Dados
                      │
┌─────────────────────────────────────────────────┐
│               CONTROLLER LAYER                  │
│  (Controle - Lógica de Fluxo)                   │
│  Responsabilidade:                              │
│    - Receber requisições                        │
│    - Validar dados                              │
│    - Chamar services/models                     │
│    - Retornar views                             │
│  Localização: Modules/{Nome}/Controllers/       │
└─────────────────────────────────────────────────┘
                      ↓
                      │ Chama
                      ↓
┌─────────────────────────────────────────────────┐
│                 SERVICE LAYER                   │
│  (Lógica de Negócio)                            │
│  Responsabilidade:                              │
│    - Validações complexas                       │
│    - Regras de negócio                          │
│    - Orquestrar múltiplos models                │
│  Localização: Modules/{Nome}/Services/          │
└─────────────────────────────────────────────────┘
                      ↓
                      │ Usa
                      ↓
┌─────────────────────────────────────────────────┐
│                  MODEL LAYER                    │
│  (Dados - Acesso ao Banco)                      │
│  Responsabilidade:                              │
│    - CRUD básico                                │
│    - Queries SQL                                │
│    - Validações de dados                        │
│  Localização: Modules/{Nome}/Models/            │
└─────────────────────────────────────────────────┘
                      ↓
                      │ Acessa
                      ↓
┌─────────────────────────────────────────────────┐
│                  DATABASE                       │
│  (MySQL 8.0)                                    │
└─────────────────────────────────────────────────┘
```

### 3.2 Quando Usar Cada Camada

| Camada | Quando Usar | Exemplo |
|--------|-------------|---------|
| **Model** | CRUD básico, queries simples | `buscarPorId()`, `listar()` |
| **Service** | Lógica complexa, múltiplos models | Criar treinamento + notificar participantes |
| **Controller** | Receber requisições, orquestrar fluxo | Receber POST, validar CSRF, chamar service |
| **View** | Apenas apresentação | Exibir lista, formulários |

---

## 4. CRIANDO UM NOVO MÓDULO

### 4.1 Decisão: Criar Módulo ou Adicionar ao Existente?

**Crie um NOVO módulo quando:**
- É uma funcionalidade completamente nova e independente
- Tem suas próprias tabelas no banco
- Pode ser ativado/desativado independentemente
- Exemplo: Sistema de Certificações, Sistema de Avaliações

**Adicione ao módulo EXISTENTE quando:**
- É uma extensão de funcionalidade existente
- Usa as mesmas tabelas
- É fortemente acoplado a um módulo existente
- Exemplo: Adicionar campo em Treinamento, novo relatório de Colaborador

### 4.2 Passo a Passo: Criar Novo Módulo

#### Exemplo: Módulo de "Certificados"

**Passo 1: Criar estrutura de diretórios**

```bash
mkdir -p app/Modules/Certificado/{Controllers,Models,Views,Routes,Services,Migrations,Events}
```

**Passo 2: Criar `module.json`**

```json
{
  "name": "Certificado",
  "slug": "certificado",
  "description": "Módulo de gestão de certificados",
  "version": "1.0.0",
  "author": "IDE Instituto",
  "dependencies": {
    "core": ">=1.0.0",
    "treinamento": ">=1.0.0",
    "colaborador": ">=1.0.0"
  },
  "provider": "CertificadoServiceProvider",
  "routes": [
    "Routes/web.php"
  ],
  "migrations": "Migrations/"
}
```

**Passo 3: Criar Service Provider**

```php
<?php
// app/Modules/Certificado/CertificadoServiceProvider.php

namespace App\Modules\Certificado;

use App\Core\ServiceProvider;
use App\Core\Container;

class CertificadoServiceProvider extends ServiceProvider
{
    public function register(Container $container)
    {
        // Registrar model
        $container->bind('Certificado', function($c) {
            return new Models\Certificado($c->get('Database'));
        });

        // Registrar service
        $container->bind('CertificadoService', function($c) {
            return new Services\CertificadoService(
                $c->get('Certificado'),
                $c->get('EventManager')
            );
        });
    }

    public function boot(Container $container)
    {
        // Carregar rotas
        $this->loadRoutes(__DIR__ . '/Routes/web.php');

        // Registrar listeners
        $events = $container->get('EventManager');

        // Quando treinamento é concluído, gerar certificado
        $events->listen('treinamento.concluido', function($treinamento) use ($container) {
            $service = $container->get('CertificadoService');
            $service->gerarAutomaticamente($treinamento);
        });
    }
}
```

**Passo 4: Ativar módulo**

```php
// app/config/modules.php

return [
    'active' => [
        'Colaborador',
        'Treinamento',
        'Certificado',  // ← Adicionar aqui
        // ...
    ]
];
```

---

## 5. CRIANDO UM CRUD COMPLETO

### 5.1 Planejamento

Antes de começar, defina:

1. **Nome da entidade**: `Certificado`
2. **Campos**: id, colaborador_id, treinamento_id, codigo, data_emissao, validade, status
3. **Relacionamentos**: belongsTo Colaborador, belongsTo Treinamento
4. **Validações**: codigo único, datas válidas
5. **Permissões**: Quem pode criar, editar, deletar?

### 5.2 Passo 1: Criar Migração

```php
<?php
// app/Modules/Certificado/Migrations/001_create_certificados.sql

CREATE TABLE IF NOT EXISTS certificados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    colaborador_id INT NOT NULL,
    treinamento_id INT NOT NULL,
    codigo VARCHAR(50) UNIQUE NOT NULL,
    data_emissao DATE NOT NULL,
    data_validade DATE,
    status ENUM('ativo', 'revogado', 'expirado') DEFAULT 'ativo',
    arquivo_pdf VARCHAR(255),
    observacoes TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id) ON DELETE CASCADE,
    FOREIGN KEY (treinamento_id) REFERENCES treinamentos(id) ON DELETE CASCADE,

    INDEX idx_colaborador (colaborador_id),
    INDEX idx_treinamento (treinamento_id),
    INDEX idx_status (status),
    INDEX idx_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Executar migração:**

```bash
mysql -u usuario -p database < app/Modules/Certificado/Migrations/001_create_certificados.sql
```

### 5.3 Passo 2: Criar Model

```php
<?php
// app/Modules/Certificado/Models/Certificado.php

namespace App\Modules\Certificado\Models;

use App\Core\Database;
use PDO;

class Certificado
{
    private $db;
    private $pdo;

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->pdo = $db->getConnection();
    }

    /**
     * Criar certificado
     */
    public function criar(array $dados): array
    {
        try {
            $sql = "INSERT INTO certificados
                    (colaborador_id, treinamento_id, codigo, data_emissao,
                     data_validade, status, observacoes)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $dados['colaborador_id'],
                $dados['treinamento_id'],
                $dados['codigo'],
                $dados['data_emissao'],
                $dados['data_validade'] ?? null,
                $dados['status'] ?? 'ativo',
                $dados['observacoes'] ?? null
            ]);

            return [
                'success' => true,
                'id' => $this->pdo->lastInsertId(),
                'message' => 'Certificado criado com sucesso'
            ];

        } catch (\PDOException $e) {
            error_log("Erro ao criar certificado: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro ao criar certificado'
            ];
        }
    }

    /**
     * Listar certificados com paginação e filtros
     */
    public function listar(array $params = []): array
    {
        try {
            // Construir WHERE dinamicamente
            $where = ['1=1'];
            $bindings = [];

            // Filtro por colaborador
            if (!empty($params['colaborador_id'])) {
                $where[] = "c.colaborador_id = ?";
                $bindings[] = $params['colaborador_id'];
            }

            // Filtro por status
            if (!empty($params['status'])) {
                $where[] = "c.status = ?";
                $bindings[] = $params['status'];
            }

            // Filtro por busca textual
            if (!empty($params['search'])) {
                $where[] = "(c.codigo LIKE ? OR col.nome LIKE ? OR t.nome LIKE ?)";
                $search = "%{$params['search']}%";
                $bindings[] = $search;
                $bindings[] = $search;
                $bindings[] = $search;
            }

            $whereClause = implode(' AND ', $where);

            // Paginação
            $page = $params['page'] ?? 1;
            $perPage = $params['per_page'] ?? 20;
            $offset = ($page - 1) * $perPage;

            // Contar total
            $sqlCount = "
                SELECT COUNT(*) as total
                FROM certificados c
                INNER JOIN colaboradores col ON c.colaborador_id = col.id
                INNER JOIN treinamentos t ON c.treinamento_id = t.id
                WHERE {$whereClause}
            ";

            $stmt = $this->pdo->prepare($sqlCount);
            $stmt->execute($bindings);
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Buscar dados
            $sql = "
                SELECT
                    c.*,
                    col.nome as colaborador_nome,
                    col.email as colaborador_email,
                    t.nome as treinamento_nome,
                    t.carga_horaria
                FROM certificados c
                INNER JOIN colaboradores col ON c.colaborador_id = col.id
                INNER JOIN treinamentos t ON c.treinamento_id = t.id
                WHERE {$whereClause}
                ORDER BY c.criado_em DESC
                LIMIT ? OFFSET ?
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(array_merge($bindings, [$perPage, $offset]));
            $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'data' => $dados,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'per_page' => $perPage,
                    'total_pages' => ceil($total / $perPage)
                ]
            ];

        } catch (\PDOException $e) {
            error_log("Erro ao listar certificados: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro ao listar certificados'
            ];
        }
    }

    /**
     * Buscar por ID
     */
    public function buscarPorId(int $id): ?array
    {
        $sql = "
            SELECT
                c.*,
                col.nome as colaborador_nome,
                col.email as colaborador_email,
                col.cpf as colaborador_cpf,
                t.nome as treinamento_nome,
                t.tipo as treinamento_tipo,
                t.carga_horaria
            FROM certificados c
            INNER JOIN colaboradores col ON c.colaborador_id = col.id
            INNER JOIN treinamentos t ON c.treinamento_id = t.id
            WHERE c.id = ?
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Buscar por código
     */
    public function buscarPorCodigo(string $codigo): ?array
    {
        $sql = "SELECT * FROM certificados WHERE codigo = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$codigo]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Atualizar certificado
     */
    public function atualizar(int $id, array $dados): array
    {
        try {
            $sql = "
                UPDATE certificados SET
                    data_validade = ?,
                    status = ?,
                    observacoes = ?,
                    atualizado_em = NOW()
                WHERE id = ?
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $dados['data_validade'] ?? null,
                $dados['status'] ?? 'ativo',
                $dados['observacoes'] ?? null,
                $id
            ]);

            return [
                'success' => true,
                'message' => 'Certificado atualizado com sucesso'
            ];

        } catch (\PDOException $e) {
            error_log("Erro ao atualizar certificado: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro ao atualizar certificado'
            ];
        }
    }

    /**
     * Deletar certificado
     */
    public function deletar(int $id): array
    {
        try {
            $sql = "DELETE FROM certificados WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);

            return [
                'success' => true,
                'message' => 'Certificado deletado com sucesso'
            ];

        } catch (\PDOException $e) {
            error_log("Erro ao deletar certificado: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro ao deletar certificado'
            ];
        }
    }

    /**
     * Verificar se código já existe
     */
    public function codigoExiste(string $codigo, ?int $excluirId = null): bool
    {
        $sql = "SELECT COUNT(*) as total FROM certificados WHERE codigo = ?";
        $bindings = [$codigo];

        if ($excluirId) {
            $sql .= " AND id != ?";
            $bindings[] = $excluirId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);

        return $stmt->fetch(PDO::FETCH_ASSOC)['total'] > 0;
    }
}
```

### 5.4 Passo 3: Criar Service (Lógica de Negócio)

```php
<?php
// app/Modules/Certificado/Services/CertificadoService.php

namespace App\Modules\Certificado\Services;

use App\Modules\Certificado\Models\Certificado;
use App\Core\EventManager;

class CertificadoService
{
    private $model;
    private $events;

    public function __construct(Certificado $model, EventManager $events)
    {
        $this->model = $model;
        $this->events = $events;
    }

    /**
     * Criar certificado
     */
    public function criar(array $dados): array
    {
        // Validar dados
        $erros = $this->validar($dados);
        if (!empty($erros)) {
            return [
                'success' => false,
                'message' => implode('<br>', $erros)
            ];
        }

        // Gerar código único se não fornecido
        if (empty($dados['codigo'])) {
            $dados['codigo'] = $this->gerarCodigo();
        }

        // Verificar se código já existe
        if ($this->model->codigoExiste($dados['codigo'])) {
            return [
                'success' => false,
                'message' => 'Código já existe'
            ];
        }

        // Evento antes de criar
        $dados = $this->events->dispatch('certificado.antes.criar', $dados);

        // Criar
        $resultado = $this->model->criar($dados);

        if ($resultado['success']) {
            // Evento após criar
            $certificado = $this->model->buscarPorId($resultado['id']);
            $this->events->dispatch('certificado.criado', $certificado);
        }

        return $resultado;
    }

    /**
     * Gerar código único
     */
    private function gerarCodigo(): string
    {
        do {
            $codigo = 'CERT-' . strtoupper(substr(uniqid(), -8));
        } while ($this->model->codigoExiste($codigo));

        return $codigo;
    }

    /**
     * Validar dados
     */
    private function validar(array $dados): array
    {
        $erros = [];

        if (empty($dados['colaborador_id'])) {
            $erros[] = 'Colaborador é obrigatório';
        }

        if (empty($dados['treinamento_id'])) {
            $erros[] = 'Treinamento é obrigatório';
        }

        if (empty($dados['data_emissao'])) {
            $erros[] = 'Data de emissão é obrigatória';
        }

        // Validar formato de data
        if (!empty($dados['data_emissao']) &&
            !$this->validarData($dados['data_emissao'])) {
            $erros[] = 'Data de emissão inválida';
        }

        // Permitir que outros módulos adicionem validações
        $erros = $this->events->dispatch('certificado.validar', $erros);

        return $erros;
    }

    /**
     * Validar formato de data
     */
    private function validarData(string $data): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $data);
        return $d && $d->format('Y-m-d') === $data;
    }

    /**
     * Gerar certificado automaticamente após conclusão de treinamento
     */
    public function gerarAutomaticamente(array $treinamento): void
    {
        // Buscar participantes que concluíram
        // Gerar certificado para cada um
        // Enviar e-mail com certificado
    }
}
```

### 5.5 Passo 4: Criar Controller

```php
<?php
// app/Modules/Certificado/Controllers/CertificadoController.php

namespace App\Modules\Certificado\Controllers;

use App\Core\Controller;
use App\Modules\Certificado\Services\CertificadoService;

class CertificadoController extends Controller
{
    private $service;

    public function __construct(CertificadoService $service)
    {
        $this->service = $service;
    }

    /**
     * Listar certificados
     * GET /certificados
     */
    public function index()
    {
        $resultado = $this->service->listar($_GET);

        return $this->view('certificado::index', [
            'titulo' => 'Certificados',
            'certificados' => $resultado['data'] ?? [],
            'pagination' => $resultado['pagination'] ?? []
        ]);
    }

    /**
     * Formulário de criação
     * GET /certificados/criar
     */
    public function create()
    {
        // Buscar dados para selects
        $colaboradores = app('Colaborador')->listarAtivos();
        $treinamentos = app('Treinamento')->listar();

        return $this->view('certificado::create', [
            'titulo' => 'Novo Certificado',
            'colaboradores' => $colaboradores,
            'treinamentos' => $treinamentos
        ]);
    }

    /**
     * Processar criação
     * POST /certificados
     */
    public function store()
    {
        // Validar CSRF
        if (!$this->validateCsrf($_POST['csrf_token'] ?? '')) {
            return $this->redirect('/certificados/criar')
                ->with('error', 'Token CSRF inválido');
        }

        // Criar certificado
        $resultado = $this->service->criar($_POST);

        if ($resultado['success']) {
            return $this->redirect('/certificados')
                ->with('success', $resultado['message']);
        }

        return $this->redirect('/certificados/criar')
            ->with('error', $resultado['message'])
            ->withInput($_POST);
    }

    /**
     * Exibir certificado
     * GET /certificados/{id}
     */
    public function show(int $id)
    {
        $certificado = app('Certificado')->buscarPorId($id);

        if (!$certificado) {
            return $this->redirect('/certificados')
                ->with('error', 'Certificado não encontrado');
        }

        return $this->view('certificado::show', [
            'titulo' => 'Certificado',
            'certificado' => $certificado
        ]);
    }

    /**
     * Formulário de edição
     * GET /certificados/{id}/editar
     */
    public function edit(int $id)
    {
        $certificado = app('Certificado')->buscarPorId($id);

        if (!$certificado) {
            return $this->redirect('/certificados')
                ->with('error', 'Certificado não encontrado');
        }

        return $this->view('certificado::edit', [
            'titulo' => 'Editar Certificado',
            'certificado' => $certificado
        ]);
    }

    /**
     * Processar edição
     * POST /certificados/{id}
     */
    public function update(int $id)
    {
        // Validar CSRF
        if (!$this->validateCsrf($_POST['csrf_token'] ?? '')) {
            return $this->redirect("/certificados/{$id}/editar")
                ->with('error', 'Token CSRF inválido');
        }

        $resultado = app('Certificado')->atualizar($id, $_POST);

        if ($resultado['success']) {
            return $this->redirect('/certificados')
                ->with('success', $resultado['message']);
        }

        return $this->redirect("/certificados/{$id}/editar")
            ->with('error', $resultado['message']);
    }

    /**
     * Deletar certificado
     * POST /certificados/{id}/deletar
     */
    public function destroy(int $id)
    {
        // Validar CSRF
        if (!$this->validateCsrf($_POST['csrf_token'] ?? '')) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Token CSRF inválido'
            ], 403);
        }

        $resultado = app('Certificado')->deletar($id);

        return $this->jsonResponse($resultado);
    }
}
```

### 5.6 Passo 5: Criar Rotas

```php
<?php
// app/Modules/Certificado/Routes/web.php

// Listar
$router->get('/certificados', 'CertificadoController@index', [
    'auth'
]);

// Criar
$router->get('/certificados/criar', 'CertificadoController@create', [
    'auth', 'admin'
]);

$router->post('/certificados', 'CertificadoController@store', [
    'auth', 'csrf', 'admin'
]);

// Ver
$router->get('/certificados/{id}', 'CertificadoController@show', [
    'auth'
]);

// Editar
$router->get('/certificados/{id}/editar', 'CertificadoController@edit', [
    'auth', 'admin'
]);

$router->post('/certificados/{id}', 'CertificadoController@update', [
    'auth', 'csrf', 'admin'
]);

// Deletar
$router->post('/certificados/{id}/deletar', 'CertificadoController@destroy', [
    'auth', 'csrf', 'admin'
]);
```

### 5.7 Passo 6: Criar Views

```php
<!-- app/Modules/Certificado/Views/index.php -->

<div class="page-header">
    <h1><?php echo $this->e($titulo); ?></h1>

    <?php if (Auth::isAdmin()): ?>
    <a href="<?php echo url('/certificados/criar'); ?>" class="btn btn-primary">
        <i class="fas fa-plus"></i> Novo Certificado
    </a>
    <?php endif; ?>
</div>

<!-- Filtros -->
<div class="filters">
    <form method="GET">
        <input type="text" name="search" placeholder="Buscar..."
               value="<?php echo $_GET['search'] ?? ''; ?>">

        <select name="status">
            <option value="">Todos os status</option>
            <option value="ativo" <?php echo ($_GET['status'] ?? '') === 'ativo' ? 'selected' : ''; ?>>
                Ativo
            </option>
            <option value="expirado">Expirado</option>
            <option value="revogado">Revogado</option>
        </select>

        <button type="submit" class="btn btn-secondary">Filtrar</button>
    </form>
</div>

<!-- Tabela -->
<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>Código</th>
                <th>Colaborador</th>
                <th>Treinamento</th>
                <th>Emissão</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($certificados)): ?>
            <tr>
                <td colspan="6" class="text-center">Nenhum certificado encontrado</td>
            </tr>
            <?php else: ?>
                <?php foreach ($certificados as $cert): ?>
                <tr>
                    <td><?php echo $this->e($cert['codigo']); ?></td>
                    <td><?php echo $this->e($cert['colaborador_nome']); ?></td>
                    <td><?php echo $this->e($cert['treinamento_nome']); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($cert['data_emissao'])); ?></td>
                    <td>
                        <span class="badge badge-<?php echo $cert['status']; ?>">
                            <?php echo ucfirst($cert['status']); ?>
                        </span>
                    </td>
                    <td>
                        <a href="<?php echo url("/certificados/{$cert['id']}"); ?>"
                           class="btn btn-sm btn-info" title="Ver">
                            <i class="fas fa-eye"></i>
                        </a>

                        <?php if (Auth::isAdmin()): ?>
                        <a href="<?php echo url("/certificados/{$cert['id']}/editar"); ?>"
                           class="btn btn-sm btn-warning" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>

                        <button onclick="deletar(<?php echo $cert['id']; ?>)"
                                class="btn btn-sm btn-danger" title="Deletar">
                            <i class="fas fa-trash"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Paginação -->
<?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
<div class="pagination">
    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
        <a href="?page=<?php echo $i; ?>"
           class="page-link <?php echo $i === $pagination['page'] ? 'active' : ''; ?>">
            <?php echo $i; ?>
        </a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<script>
function deletar(id) {
    if (!confirm('Tem certeza que deseja deletar este certificado?')) {
        return;
    }

    fetch(`/certificados/${id}/deletar`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            csrf_token: '<?php echo csrf_token(); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message);
        }
    });
}
</script>
```

---

## 6. TRABALHANDO COM BANCO DE DADOS

### 6.1 Prepared Statements (OBRIGATÓRIO)

#### ✅ CORRETO:

```php
// Usar prepared statements
$sql = "SELECT * FROM certificados WHERE colaborador_id = ? AND status = ?";
$stmt = $this->pdo->prepare($sql);
$stmt->execute([$colaboradorId, $status]);
```

#### ❌ ERRADO (SQL Injection):

```php
// NUNCA faça isso!
$sql = "SELECT * FROM certificados WHERE colaborador_id = $colaboradorId";
$stmt = $this->pdo->query($sql);  // VULNERÁVEL!
```

### 6.2 Transações

Use transações quando precisar fazer múltiplas operações que devem ter sucesso juntas:

```php
public function criarComDependencias(array $dados): array
{
    try {
        $this->pdo->beginTransaction();

        // 1. Criar certificado
        $certificadoId = $this->criarCertificado($dados);

        // 2. Atualizar status do treinamento
        $this->atualizarTreinamento($dados['treinamento_id']);

        // 3. Notificar colaborador
        $this->notificar($dados['colaborador_id'], $certificadoId);

        $this->pdo->commit();

        return ['success' => true, 'id' => $certificadoId];

    } catch (\Exception $e) {
        $this->pdo->rollBack();
        error_log("Erro na transação: " . $e->getMessage());

        return ['success' => false, 'message' => 'Erro ao criar certificado'];
    }
}
```

### 6.3 Índices

Sempre crie índices em colunas que você usa em:
- WHERE
- JOIN
- ORDER BY
- Chaves estrangeiras

```sql
-- Índices importantes
CREATE INDEX idx_colaborador ON certificados(colaborador_id);
CREATE INDEX idx_status ON certificados(status);
CREATE INDEX idx_data_emissao ON certificados(data_emissao);
```

---

## 7. SISTEMA DE AUTENTICAÇÃO E AUTORIZAÇÃO

### 7.1 Proteger Rotas

```php
// Verificar autenticação
if (!Auth::isLogged()) {
    return $this->redirect('/login')
        ->with('error', 'Faça login para continuar');
}

// Verificar nível de acesso
if (!Auth::hasLevel(['admin', 'gerente'])) {
    return $this->redirect('/dashboard')
        ->with('error', 'Acesso negado');
}

// Middleware automático (melhor opção)
$router->get('/certificados/criar', 'CertificadoController@create', [
    'auth',        // Verifica se está logado
    'admin'        // Verifica se é admin
]);
```

### 7.2 Verificar Permissões

```php
// Em views
<?php if (Auth::isAdmin()): ?>
    <button>Editar</button>
<?php endif; ?>

// Em controllers
public function editar($id)
{
    if (!Auth::canEdit('certificado')) {
        throw new \Exception('Sem permissão');
    }

    // ...
}
```

---

## 8. VALIDAÇÃO DE DADOS

### 8.1 Validação Server-Side (OBRIGATÓRIA)

```php
private function validar(array $dados): array
{
    $erros = [];

    // Campo obrigatório
    if (empty($dados['nome'])) {
        $erros[] = 'Nome é obrigatório';
    }

    // Tamanho mínimo/máximo
    if (strlen($dados['nome'] ?? '') < 3) {
        $erros[] = 'Nome deve ter pelo menos 3 caracteres';
    }

    // E-mail válido
    if (!filter_var($dados['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'E-mail inválido';
    }

    // CPF válido
    if (!$this->validarCPF($dados['cpf'] ?? '')) {
        $erros[] = 'CPF inválido';
    }

    // Data válida
    if (!$this->validarData($dados['data'])) {
        $erros[] = 'Data inválida';
    }

    // Valor único
    if ($this->emailExiste($dados['email'])) {
        $erros[] = 'E-mail já cadastrado';
    }

    return $erros;
}
```

### 8.2 Sanitização

```php
// Sanitizar entrada
$dados = [
    'nome' => trim(strip_tags($_POST['nome'])),
    'email' => filter_var($_POST['email'], FILTER_SANITIZE_EMAIL),
    'cpf' => preg_replace('/[^0-9]/', '', $_POST['cpf']),
];

// Helper de escape para output
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Uso em views
echo e($colaborador['nome']);  // Previne XSS
```

---

## 9. TRABALHANDO COM VIEWS

### 9.1 Separação de Responsabilidades

#### ❌ ERRADO (Lógica em View):

```php
<!-- NUNCA faça isso! -->
<?php
$db = Database::getInstance();
$stmt = $db->query("SELECT * FROM colaboradores");  // SQL na view!
$colaboradores = $stmt->fetchAll();
?>
```

#### ✅ CORRETO (View Limpa):

```php
<!-- Controller prepara dados -->
<?php
// Controller
public function index()
{
    $colaboradores = $this->service->listar();

    return $this->view('colaborador::index', [
        'colaboradores' => $colaboradores
    ]);
}
?>

<!-- View apenas exibe -->
<?php foreach ($colaboradores as $c): ?>
    <li><?php echo $this->e($c['nome']); ?></li>
<?php endforeach; ?>
```

### 9.2 Partials/Componentes

```php
<!-- app/views/partials/alert.php -->
<?php if (isset($_SESSION['success'])): ?>
<div class="alert alert-success">
    <?php echo e($_SESSION['success']); unset($_SESSION['success']); ?>
</div>
<?php endif; ?>

<!-- Uso em outras views -->
<?php $this->partial('alert'); ?>
```

---

## 10. INTEGRAÇÃO ENTRE MÓDULOS

### 10.1 Usando Eventos

```php
// Módulo A dispara evento
$events->dispatch('certificado.criado', $certificado);

// Módulo B ouve evento
$events->listen('certificado.criado', function($certificado) {
    // Enviar e-mail
    // Atualizar estatísticas
    // Logar auditoria
});
```

### 10.2 Dependências entre Módulos

```json
// module.json
{
  "dependencies": {
    "core": ">=1.0.0",
    "colaborador": ">=1.0.0",
    "treinamento": ">=1.0.0"
  }
}
```

---

## 11. TESTES

### 11.1 Teste Manual (Checklist)

```
[ ] Criar - OK
[ ] Listar - OK
[ ] Buscar por ID - OK
[ ] Editar - OK
[ ] Deletar - OK
[ ] Validação de campos obrigatórios - OK
[ ] SQL injection - OK
[ ] XSS - OK
[ ] CSRF - OK
[ ] Permissões - OK
[ ] Paginação - OK
[ ] Filtros - OK
```

### 11.2 Teste Automatizado (Futuro)

```php
<?php
// tests/CertificadoTest.php

class CertificadoTest extends TestCase
{
    public function testCriarCertificado()
    {
        $service = app('CertificadoService');

        $resultado = $service->criar([
            'colaborador_id' => 1,
            'treinamento_id' => 1,
            'data_emissao' => '2025-11-09'
        ]);

        $this->assertTrue($resultado['success']);
        $this->assertNotNull($resultado['id']);
    }
}
```

---

## 12. CHECKLIST FINAL

### Antes de Comitar

```
[ ] Código segue PSR-1/PSR-2
[ ] Prepared statements em todas as queries
[ ] CSRF token em todos os formulários POST
[ ] Validação server-side implementada
[ ] htmlspecialchars() em todos os outputs
[ ] Autenticação verificada nas rotas
[ ] Tratamento de exceções
[ ] Logs de erro implementados
[ ] Código documentado (PHPDoc)
[ ] Testes manuais realizados
[ ] Sem código duplicado
[ ] Sem SQL em views
[ ] Eventos disparados (se aplicável)
[ ] Module.json atualizado
[ ] Migrations criadas
[ ] README do módulo criado
```

---

## RESUMO

Este guia fornece tudo que você precisa para criar novos recursos no SGC seguindo os padrões:

1. **Planeje** - Defina entidade, campos, relacionamentos
2. **Crie o módulo** - Estrutura de diretórios, module.json
3. **Model** - Acesso ao banco com prepared statements
4. **Service** - Lógica de negócio e validações
5. **Controller** - Orquestração e fluxo
6. **Views** - Apresentação limpa
7. **Rotas** - Mapeamento de URLs
8. **Teste** - Verificar tudo funciona
9. **Documente** - Comentários e README

**Sempre lembre:**
- ✅ Segurança em primeiro lugar
- ✅ Separação de responsabilidades
- ✅ Código limpo e documentado
- ✅ Testável e extensível

**Dúvidas?** Consulte este guia ou os módulos existentes como exemplo.

**FIM DO GUIA**
