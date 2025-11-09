# GUIA DE MIGRAÇÃO DE MÓDULOS PARA ARQUITETURA CORE

**Sistema de Gestão de Capacitações (SGC)**
**Data:** 09 de Novembro de 2025
**Versão:** 2.0 - Nova Arquitetura MVC Modular

---

## 📋 ÍNDICE

1. [Visão Geral](#visão-geral)
2. [Pré-Requisitos](#pré-requisitos)
3. [Arquivos de Exemplo](#arquivos-de-exemplo)
4. [Passo a Passo](#passo-a-passo)
5. [Migração de Models](#migração-de-models)
6. [Migração de Controllers](#migração-de-controllers)
7. [Migração de Views](#migração-de-views)
8. [Checklist de Migração](#checklist-de-migração)
9. [Troubleshooting](#troubleshooting)

---

## 🎯 VISÃO GERAL

### O que é a migração?

A migração consiste em **atualizar módulos existentes** para usar as novas classes Core:

- `App\Core\Model` - Base para models com Active Record
- `App\Core\Controller` - Base para controllers com validações
- `App\Core\View` - Motor de templates
- `App\Core\Router` - Roteamento centralizado
- `App\Core\EventManager` - Sistema de eventos
- `App\Core\Container` - Dependency Injection

### Por que migrar?

✅ **Código mais limpo** - Menos duplicação
✅ **Mais seguro** - Validação e escape automáticos
✅ **Mais testável** - Dependency Injection
✅ **Mais extensível** - Sistema de eventos
✅ **Mais manutenível** - Padrões consistentes

### Estratégia de Migração

**Migração Gradual:** Um módulo por vez, sem quebrar o sistema atual.

**Ordem Recomendada:**
1. Treinamentos (mais usado, benefício imediato)
2. Usuários
3. Instrutores
4. Inscrições
5. Demais módulos

---

## ✅ PRÉ-REQUISITOS

Antes de iniciar a migração, certifique-se que:

- [x] Sprint 1 completa (Segurança)
- [x] Sprint 2 completa (Classes Core)
- [x] Backup do código atual
- [x] Ambiente de testes configurado

---

## 📁 ARQUIVOS DE EXEMPLO

Foram criados arquivos de exemplo para referência:

```
app/
├── Core/
│   ├── bootstrap.php              # Inicialização do sistema
│   ├── Container.php              # ✅ DI Container
│   ├── EventManager.php           # ✅ Sistema de eventos
│   ├── Router.php                 # ✅ Roteamento
│   ├── View.php                   # ✅ Templates
│   ├── Model.php                  # ✅ Base Model
│   ├── Controller.php             # ✅ Base Controller
│   └── helpers.php                # ✅ 80+ funções helper
│
├── routes.php                     # Exemplo de definição de rotas
│
├── Controllers/
│   └── ExemploTreinamentoController.php  # ✅ Controller moderno
│
├── Models/
│   └── ExemploTreinamentoModel.php       # ✅ Model moderno
│
public/
└── index.example.php              # ✅ Novo ponto de entrada
```

---

## 🚀 PASSO A PASSO - MIGRAR MÓDULO DE TREINAMENTOS

### PASSO 1: Criar Model Moderno

**Antes (app/classes/Treinamento.php):**
```php
class Treinamento {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAll() {
        $stmt = $this->db->prepare("SELECT * FROM treinamentos");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $stmt = $this->db->prepare("INSERT INTO treinamentos (titulo, descricao) VALUES (?, ?)");
        return $stmt->execute([$data['titulo'], $data['descricao']]);
    }
}
```

**Depois (app/Models/TreinamentoModel.php):**
```php
<?php
namespace App\Models;

use App\Core\Model;

class TreinamentoModel extends Model
{
    protected $table = 'treinamentos';

    protected $fillable = [
        'titulo',
        'descricao',
        'instrutor_id',
        'data_inicio',
        'carga_horaria'
    ];

    protected $rules = [
        'titulo' => 'required|min:3|max:200',
        'descricao' => 'required|min:10'
    ];

    protected $timestamps = true;

    // Evento: após criar
    protected function onCreated(): void
    {
        event()->dispatch('treinamento.criado', $this);
    }

    // Métodos personalizados
    public static function ativos(): array
    {
        return (new static())
            ->where('ativo', 1)
            ->orderBy('created_at', 'DESC')
            ->get();
    }
}
```

**Benefícios:**
- ✅ CRUD automático (find, save, delete)
- ✅ Validação integrada
- ✅ Eventos automáticos
- ✅ Query Builder
- ✅ Timestamps automáticos

---

### PASSO 2: Criar Controller Moderno

**Antes (app/Controllers/TreinamentoController.php):**
```php
class TreinamentoController {
    private $treinamento;

    public function __construct() {
        $this->treinamento = new Treinamento();
    }

    public function listar() {
        $dados = $this->treinamento->getAll();
        include 'app/views/treinamentos/listar.php';
    }

    public function salvar() {
        if (empty($_POST['titulo'])) {
            $_SESSION['erro'] = 'Título é obrigatório';
            header('Location: /treinamentos/criar');
            exit;
        }

        $this->treinamento->create($_POST);
        $_SESSION['sucesso'] = 'Criado com sucesso';
        header('Location: /treinamentos');
        exit;
    }
}
```

**Depois (app/Controllers/TreinamentoController.php):**
```php
<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\TreinamentoModel;

class TreinamentoController extends Controller
{
    protected $treinamentoModel;

    public function __construct()
    {
        parent::__construct();
        // DI automático
        $this->treinamentoModel = app(TreinamentoModel::class);
    }

    public function index(): string
    {
        $treinamentos = $this->treinamentoModel->where('ativo', 1)->get();

        event()->dispatch('treinamentos.listados', $treinamentos);

        return $this->render('treinamentos/index', [
            'titulo' => 'Treinamentos',
            'treinamentos' => $treinamentos
        ]);
    }

    public function store(): void
    {
        $this->verifyCsrfToken();

        // Validação automática
        $data = $this->validate([
            'titulo' => 'required|min:3|max:200',
            'descricao' => 'required|min:10'
        ]);

        $treinamento = new TreinamentoModel($data);
        $treinamento->save();

        $this->redirectWithSuccess(
            '/treinamentos',
            'Treinamento criado com sucesso!'
        );
    }
}
```

**Benefícios:**
- ✅ Validação automática
- ✅ CSRF protection
- ✅ Flash messages
- ✅ Código limpo
- ✅ Tipo de retorno definido

---

### PASSO 3: Atualizar Views

**Antes (app/views/treinamentos/listar.php):**
```php
<!DOCTYPE html>
<html>
<head>
    <title>Treinamentos</title>
</head>
<body>
    <?php if (isset($_SESSION['sucesso'])): ?>
        <div class="alert-success">
            <?= $_SESSION['sucesso'] ?>
            <?php unset($_SESSION['sucesso']); ?>
        </div>
    <?php endif; ?>

    <h1>Treinamentos</h1>

    <?php foreach ($dados as $item): ?>
        <div>
            <h2><?= htmlspecialchars($item['titulo']) ?></h2>
            <p><?= htmlspecialchars($item['descricao']) ?></p>
        </div>
    <?php endforeach; ?>
</body>
</html>
```

**Depois (app/views/treinamentos/index.php):**
```php
<?php $this->extends('layouts/main'); ?>

<?php $this->section('content'); ?>

    <?php if ($flash_success): ?>
        <div class="alert alert-success">
            <?= $this->e($flash_success) ?>
        </div>
    <?php endif; ?>

    <h1><?= $this->e($titulo) ?></h1>

    <?php foreach ($treinamentos as $treinamento): ?>
        <div class="card">
            <h2><?= $this->e($treinamento['titulo']) ?></h2>
            <p><?= $this->e($treinamento['descricao']) ?></p>
        </div>
    <?php endforeach; ?>

<?php $this->endSection(); ?>
```

**Layout (app/views/layouts/main.php):**
```php
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->e($titulo ?? 'SGC') ?> - <?= $this->e($app_name) ?></title>
    <?= $this->css('main.css') ?>
</head>
<body>
    <?php $this->partial('header'); ?>

    <main>
        <?php $this->yield('content'); ?>
    </main>

    <?php $this->partial('footer'); ?>

    <?= $this->js('main.js', true) ?>
</body>
</html>
```

**Benefícios:**
- ✅ Herança de layouts
- ✅ Escape automático (XSS)
- ✅ Partials reutilizáveis
- ✅ Dados compartilhados
- ✅ Flash messages automáticas

---

### PASSO 4: Definir Rotas

**Arquivo: app/routes.php**

```php
$router = app('Router');

$router->group(['middleware' => ['auth']], function ($router) {

    // Treinamentos
    $router->get('/treinamentos', 'TreinamentoController@index');
    $router->get('/treinamentos/criar', 'TreinamentoController@create');
    $router->post('/treinamentos', 'TreinamentoController@store', ['csrf']);
    $router->get('/treinamentos/{id}', 'TreinamentoController@show');
    $router->get('/treinamentos/{id}/editar', 'TreinamentoController@edit');
    $router->put('/treinamentos/{id}', 'TreinamentoController@update', ['csrf']);
    $router->delete('/treinamentos/{id}', 'TreinamentoController@destroy', ['csrf']);

});
```

**Benefícios:**
- ✅ Rotas centralizadas
- ✅ Middleware por rota
- ✅ Parâmetros dinâmicos
- ✅ Métodos HTTP corretos

---

## ✅ CHECKLIST DE MIGRAÇÃO POR MÓDULO

### □ Model
- [ ] Criar classe que extends `App\Core\Model`
- [ ] Definir `$table`
- [ ] Definir `$fillable` ou `$guarded`
- [ ] Definir `$rules` para validação
- [ ] Configurar `$timestamps` (true/false)
- [ ] Implementar eventos (onCreated, onUpdated, etc)
- [ ] Migrar métodos personalizados
- [ ] Testar CRUD básico

### □ Controller
- [ ] Criar classe que extends `App\Core\Controller`
- [ ] Injetar Model via construtor
- [ ] Migrar método index()
- [ ] Migrar método create()
- [ ] Migrar método store() com validação
- [ ] Migrar método show($id)
- [ ] Migrar método edit($id)
- [ ] Migrar método update($id)
- [ ] Migrar método destroy($id)
- [ ] Adicionar CSRF em forms
- [ ] Testar todas as actions

### □ Views
- [ ] Criar layout principal (layouts/main.php)
- [ ] Migrar view index
- [ ] Migrar view create/edit
- [ ] Migrar view show
- [ ] Usar `$this->extends()` e `$this->yield()`
- [ ] Usar `$this->e()` para escape
- [ ] Usar flash messages automáticas
- [ ] Testar renderização

### □ Rotas
- [ ] Adicionar rotas em app/routes.php
- [ ] Definir middleware necessários
- [ ] Testar todas as rotas

### □ Eventos (Opcional)
- [ ] Identificar pontos para eventos
- [ ] Implementar listeners
- [ ] Testar eventos

### □ Testes
- [ ] Testar criação (CREATE)
- [ ] Testar leitura (READ)
- [ ] Testar atualização (UPDATE)
- [ ] Testar deleção (DELETE)
- [ ] Testar validações
- [ ] Testar permissões

---

## 🔧 TROUBLESHOOTING

### Erro: "Class not found"

**Solução:** Verificar namespace e autoload
```php
// Arquivo deve ter namespace correto
namespace App\Controllers;

// E estar no caminho correto
app/Controllers/TreinamentoController.php
```

### Erro: "Table not found"

**Solução:** Definir `$table` no Model
```php
protected $table = 'treinamentos'; // Nome correto da tabela
```

### Erro: "CSRF token mismatch"

**Solução:** Adicionar token no formulário
```php
<input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
```

### View não encontrada

**Solução:** Verificar caminho
```php
// Correto
return $this->render('treinamentos/index', $data);

// Arquivo deve estar em:
app/views/treinamentos/index.php
```

---

## 📊 PROGRESSO DA MIGRAÇÃO

### Módulos a Migrar (15 total)

- [ ] Treinamentos (PRIORITÁRIO)
- [ ] Usuários
- [ ] Instrutores
- [ ] Inscrições
- [ ] Certificados
- [ ] Avaliações
- [ ] Turmas
- [ ] Presenças
- [ ] Categorias
- [ ] Documentos
- [ ] Notificações
- [ ] Relatórios
- [ ] Configurações
- [ ] Logs
- [ ] Dashboard

### Estimativa de Tempo

- **Por módulo:** 2-3 horas
- **Total:** 30-45 horas
- **Sprint 3 (1 módulo POC):** 15-20 horas

---

## 📚 RECURSOS ADICIONAIS

### Documentação Relacionada
- `PROGRESSO_DESENVOLVIMENTO.md` - Progresso geral
- `GUIA_IMPLEMENTACAO_NOVOS_RECURSOS.md` - Regras e padrões
- `EXEMPLOS_EVENTOS.md` - Sistema de eventos
- `RESUMO_FINAL.md` - Resumo completo

### Arquivos de Exemplo
- `app/Core/bootstrap.php` - Inicialização
- `app/routes.php` - Rotas
- `app/Controllers/ExemploTreinamentoController.php` - Controller
- `app/Models/ExemploTreinamentoModel.php` - Model
- `public/index.example.php` - Ponto de entrada

---

**FIM DO GUIA DE MIGRAÇÃO**
