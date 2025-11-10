# ANÁLISE DE MIGRAÇÃO - MÓDULO COLABORADORES

**Sistema de Gestão de Capacitações (SGC)**
**Sprint:** 4
**Data Início:** 10 de Novembro de 2025
**Responsável:** Arquitetura Core v2.0

---

## 📋 ÍNDICE

1. [Resumo Executivo](#resumo-executivo)
2. [Análise do Código Legacy](#análise-do-código-legacy)
3. [Estrutura do Banco de Dados](#estrutura-do-banco-de-dados)
4. [Mapeamento para Nova Arquitetura](#mapeamento-para-nova-arquitetura)
5. [Complexidade Estimada](#complexidade-estimada)
6. [Checklist de Migração](#checklist-de-migração)

---

## 🎯 RESUMO EXECUTIVO

### Informações do Módulo

| Item | Detalhes |
|------|----------|
| **Nome do Módulo** | Colaboradores (Funcionários) |
| **Propósito** | Gerenciar cadastro de colaboradores/funcionários |
| **Complexidade** | **Média** |
| **Estimativa** | 6-8 horas |
| **Prioridade** | Alta (Módulo core do sistema) |
| **Dependências** | Treinamentos (já migrado) |

### Arquivos Legacy Identificados

```
app/
├── models/
│   ├── Colaborador.php                    (524 linhas) ⚠️ MIGRAR
│   ├── ColaboradorSenha.php               (auth - fora do escopo)
│   └── UnidadeColaborador.php             (relacionamento)
│
├── controllers/
│   ├── ColaboradorController.php          (270 linhas) ⚠️ MIGRAR
│   └── UnidadeColaboradorController.php   (relacionamento)
│
├── classes/
│   └── ColaboradorAuth.php                (auth - fora do escopo)
│
└── views/colaboradores/                   ⚠️ NÃO EXISTEM (criar do zero)
```

### Funcionalidades Atuais

✅ **CRUD Completo:**
- [x] Listagem com paginação e filtros avançados
- [x] Cadastro com validação de email/CPF únicos
- [x] Edição com validação
- [x] Inativação (soft delete via campo `ativo`)
- [x] Reativação

✅ **Validações:**
- [x] Email único e formato válido
- [x] CPF único e formato válido (com validação matemática)
- [x] Salário numérico com formatação brasileira
- [x] Campos obrigatórios: nome, email, nivel_hierarquico

✅ **Recursos Avançados:**
- [x] Histórico de treinamentos do colaborador
- [x] Estatísticas (total treinamentos, horas, avaliações)
- [x] Exportação para CSV
- [x] Detecção dinâmica de colunas (suporte a migração de DB)

✅ **Integrações:**
- [x] Relacionamento com Treinamentos (treinamento_participantes)
- [x] Relacionamento com Unidades (unidade_colaboradores)
- [x] Sincronização com WordPress (campo `origem` + `wordpress_id`)

---

## 📂 ANÁLISE DO CÓDIGO LEGACY

### Model: app/models/Colaborador.php

**Linhas:** 524
**Qualidade:** ⭐⭐⭐⭐ (Boa - bem estruturado)

#### Métodos Principais:

| Método | Linhas | Complexidade | Ação |
|--------|--------|--------------|------|
| `listar()` | 19-142 | Alta | ✅ Migrar para scope + query builder |
| `buscarPorId()` | 160-232 | Média | ✅ Migrar para find() |
| `criar()` | 237-306 | Média | ✅ Migrar para save() com validação |
| `atualizar()` | 311-395 | Média | ✅ Migrar para save() |
| `inativar()` | 400-408 | Baixa | ✅ Migrar para softDelete() |
| `ativar()` | 413-421 | Baixa | ✅ Criar método personalizado |
| `emailExiste()` | 426-437 | Baixa | ✅ Migrar para validação |
| `cpfExiste()` | 442-453 | Baixa | ✅ Migrar para validação |
| `buscarHistoricoTreinamentos()` | 458-479 | Alta | ✅ Migrar para relacionamento |
| `getEstatisticas()` | 484-522 | Alta | ✅ Migrar para método no Model |
| `hasColumn()` | 147-155 | Média | ❌ Remover (legacy migration support) |

#### Observações Importantes:

⚠️ **Código de Migração Legacy:**
- O model atual tem lógica complexa de detecção dinâmica de colunas (`hasColumn()`)
- Isso existe para suportar migração gradual do banco de dados antigo
- **Na nova arquitetura:** Assumir que todas as colunas já existem

⚠️ **Estrutura Dupla (Legacy + Nova):**
- Campo antigo: `departamento`
- Campos novos: `unidade_principal_id` + `setor_principal`
- **Estratégia:** Migrar para estrutura nova (campos separados)

✅ **Pontos Positivos:**
- Validações bem implementadas (email, CPF)
- Sanitização de dados
- Queries otimizadas com prepared statements
- Relacionamentos claros com treinamentos

### Controller: app/controllers/ColaboradorController.php

**Linhas:** 270
**Qualidade:** ⭐⭐⭐ (Médio - precisa modernização)

#### Actions Identificadas:

| Action | Linhas | Método HTTP | Ação |
|--------|--------|-------------|------|
| `listar()` | 17-29 | GET | ✅ Migrar para index() |
| `exibirFormularioCadastro()` | 34-36 | GET | ✅ Migrar para create() |
| `processarCadastro()` | 41-62 | POST | ✅ Migrar para store() |
| `exibirFormularioEdicao()` | 67-69 | GET | ✅ Migrar para edit($id) |
| `processarEdicao()` | 74-95 | POST | ✅ Migrar para update($id) |
| `visualizar()` | 100-110 | GET | ✅ Migrar para show($id) |
| `inativar()` | 115-117 | POST | ✅ Migrar para destroy($id) |
| `ativar()` | 122-124 | POST | ✅ Adicionar ativar($id) |
| `validarDados()` | 129-165 | - | ✅ Migrar para Model $rules |
| `sanitizarDados()` | 170-190 | - | ✅ Migrar para Model $fillable |
| `validarCPF()` | 195-219 | - | ✅ Migrar para helper ou validação customizada |
| `exportarCSV()` | 231-268 | GET | ✅ Adicionar como action separada |

#### Problemas Identificados:

❌ **Validação Manual:**
```php
$erros = $this->validarDados($_POST);
if (!empty($erros)) {
    return ['success' => false, 'message' => implode('<br>', $erros)];
}
```
**Solução:** Usar `$this->validate()` do Core\Controller

❌ **Sanitização Manual:**
```php
$dados = $this->sanitizarDados($_POST);
```
**Solução:** Usar `$fillable` do Model + `$this->validate()`

❌ **Retorno Misto (array vs void):**
```php
return ['success' => true, 'message' => '...'];
```
**Solução:** Usar `redirectWithSuccess()` e `redirectWithError()`

❌ **CSRF Validação Manual:**
```php
if (!csrf_validate($_POST['csrf_token'] ?? '')) {
```
**Solução:** Usar `$this->verifyCsrfToken()` automático

### Views: Não existem

⚠️ **Status:** Views não foram criadas no sistema legacy
✅ **Ação:** Criar do zero usando padrão da Sprint 3 (Treinamentos)

**Views a criar:**
1. `app/views/colaboradores/index.php` - Listagem
2. `app/views/colaboradores/form.php` - Criar/Editar
3. `app/views/colaboradores/show.php` - Detalhes + Histórico

---

## 🗄️ ESTRUTURA DO BANCO DE DADOS

### Tabela: `colaboradores`

```sql
CREATE TABLE IF NOT EXISTS colaboradores (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(200) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    cpf VARCHAR(14) UNIQUE,
    nivel_hierarquico ENUM('Estratégico', 'Tático', 'Operacional') NOT NULL,
    cargo VARCHAR(100),
    departamento VARCHAR(100),
    salario DECIMAL(10,2) COMMENT 'Salário mensal',
    data_admissao DATE,
    telefone VARCHAR(20),
    ativo BOOLEAN DEFAULT 1,
    origem ENUM('local', 'wordpress') DEFAULT 'local',
    wordpress_id INT NULL,
    foto_perfil VARCHAR(255),
    observacoes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_email (email),
    INDEX idx_nivel (nivel_hierarquico),
    INDEX idx_ativo (ativo),
    INDEX idx_origem (origem)
);
```

### Campos por Categoria:

#### 🔑 Identificação (5 campos)
- `id` - PK
- `nome` - VARCHAR(200) NOT NULL
- `email` - VARCHAR(150) UNIQUE NOT NULL
- `cpf` - VARCHAR(14) UNIQUE
- `foto_perfil` - VARCHAR(255)

#### 💼 Informações Profissionais (5 campos)
- `nivel_hierarquico` - ENUM('Estratégico', 'Tático', 'Operacional') NOT NULL
- `cargo` - VARCHAR(100)
- `departamento` - VARCHAR(100)
- `salario` - DECIMAL(10,2)
- `data_admissao` - DATE

#### 📞 Contato (1 campo)
- `telefone` - VARCHAR(20)

#### ⚙️ Sistema (6 campos)
- `ativo` - BOOLEAN DEFAULT 1 (soft delete)
- `origem` - ENUM('local', 'wordpress')
- `wordpress_id` - INT NULL
- `observacoes` - TEXT
- `created_at` - TIMESTAMP
- `updated_at` - TIMESTAMP

### Validações de Banco:

✅ **Constraints:**
- UNIQUE: email, cpf
- NOT NULL: nome, email, nivel_hierarquico
- DEFAULT: ativo (1), origem ('local')

✅ **Índices:**
- idx_email - Performance em buscas
- idx_nivel - Filtros por hierarquia
- idx_ativo - Filtros ativos/inativos
- idx_origem - Filtros por origem

### Relacionamentos:

```
colaboradores
    ├── 1:N → treinamento_participantes (já migrado)
    ├── 1:N → unidade_colaboradores (Sprint futura)
    └── 1:1 → colaboradores_senhas (auth - fora escopo)
```

---

## 🔄 MAPEAMENTO PARA NOVA ARQUITETURA

### Model: ColaboradorModel extends Model

```php
<?php
namespace App\Models;

use App\Core\Model;

class ColaboradorModel extends Model
{
    protected $table = 'colaboradores';

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

    protected $rules = [
        'nome' => 'required|min:3|max:200',
        'email' => 'required|email|unique:colaboradores,email',
        'cpf' => 'cpf|unique:colaboradores,cpf',  // Validação customizada
        'nivel_hierarquico' => 'required|in:Estratégico,Tático,Operacional',
        'salario' => 'numeric|min:0',
        'data_admissao' => 'date',
        'telefone' => 'min:10|max:20'
    ];

    protected $casts = [
        'salario' => 'decimal:2',
        'ativo' => 'boolean',
        'data_admissao' => 'date'
    ];

    protected $timestamps = true;
    protected $softDeletes = false; // Usa campo 'ativo' ao invés de deleted_at

    // Scopes
    public function porNivel($nivel) { }
    public function ativos() { }
    public function inativos() { }
    public function porOrigem($origem) { }
    public function buscar($termo) { }

    // Relacionamentos
    public function treinamentos() { }
    public function unidades() { }

    // Métodos personalizados
    public function getHistoricoTreinamentos() { }
    public function getEstatisticas() { }
    public function ativar() { }
    public function inativar() { }

    // Eventos
    protected function onCreated() { }
    protected function onUpdated() { }
}
```

**Estimativa:** 350-400 linhas

### Controller: ColaboradorController extends Controller

```php
<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\ColaboradorModel;

class ColaboradorController extends Controller
{
    protected $colaboradorModel;

    public function __construct() { }

    // CRUD
    public function index() { }           // GET  /colaboradores
    public function create() { }          // GET  /colaboradores/criar
    public function store() { }           // POST /colaboradores
    public function show($id) { }         // GET  /colaboradores/{id}
    public function edit($id) { }         // GET  /colaboradores/{id}/editar
    public function update($id) { }       // PUT  /colaboradores/{id}
    public function destroy($id) { }      // DELETE /colaboradores/{id}

    // Ações especiais
    public function ativar($id) { }       // POST /colaboradores/{id}/ativar
    public function exportarCSV() { }     // GET  /colaboradores/exportar
    public function api() { }             // GET  /api/colaboradores (JSON)
}
```

**Estimativa:** 550-600 linhas

### Views: Sistema de Templates

**Estrutura:**
```
app/views/colaboradores/
├── index.php       (Listagem com filtros)
├── form.php        (Criar/Editar unificado)
└── show.php        (Detalhes + Histórico)
```

**Estimativa:**
- index.php: 320 linhas
- form.php: 480 linhas (formulário complexo)
- show.php: 450 linhas (detalhes + histórico + estatísticas)

**Total:** 1,250 linhas

---

## 📊 COMPLEXIDADE ESTIMADA

### Análise de Complexidade

| Aspecto | Complexidade | Justificativa |
|---------|--------------|---------------|
| **Model** | ⭐⭐⭐ Média | - 17 campos<br>- Validações customizadas (CPF)<br>- Relacionamentos<br>- Métodos de estatísticas |
| **Controller** | ⭐⭐ Baixa | - CRUD padrão<br>- Validações simples<br>- 2 actions extras (ativar, exportar) |
| **Views** | ⭐⭐⭐ Média | - Formulário com muitos campos<br>- Página de detalhes complexa (histórico)<br>- Filtros avançados |
| **Validações** | ⭐⭐⭐⭐ Alta | - CPF com validação matemática<br>- Email + CPF únicos<br>- Formatação de salário BR |
| **Relacionamentos** | ⭐⭐ Baixa | - 1:N com treinamentos (já existe)<br>- Future: unidades |
| **Migrações** | ⭐ Muito Baixa | - Tabela já existe<br>- Sem alterações necessárias |

### Comparação com Treinamentos (Sprint 3)

| Métrica | Treinamentos | Colaboradores | Diferença |
|---------|--------------|---------------|-----------|
| **Campos no Model** | 14 | 17 | +3 (+21%) |
| **Validações** | 8 regras | 7 regras | -1 (-12%) |
| **Actions Controller** | 11 | 11 | 0 (=) |
| **Views** | 3 (1,365 linhas) | 3 (1,250 linhas) | -115 (-8%) |
| **Scopes** | 6 | 5 | -1 |
| **Relacionamentos** | 3 | 2 | -1 |
| **Complexidade Geral** | Média-Alta | Média | Menor |

**Conclusão:** Colaboradores é **15% menos complexo** que Treinamentos devido a:
- Menos relacionamentos
- Views mais simples (sem agenda, sem avaliações)
- Lógica de negócio mais direta

### Estimativa de Tempo

| Fase | Tempo | Notas |
|------|-------|-------|
| **Fase 1: Análise** | ✅ 1h | Este documento |
| **Fase 2: Model** | 2h | Validação CPF customizada |
| **Fase 3: Controller** | 1.5h | CRUD + export |
| **Fase 4: Views** | 2.5h | Formulário extenso |
| **Fase 5: Rotas** | 0.5h | Padrão REST |
| **Fase 6: Testes** | 2h | 35 testes |
| **TOTAL** | **9.5h** | Média: 6-8h |

**Com experiência da Sprint 3:** Redução de 30% → **6-7 horas**

---

## ✅ CHECKLIST DE MIGRAÇÃO

### Fase 1: Análise ✅
- [x] Identificar arquivos legacy
- [x] Analisar estrutura do banco
- [x] Mapear funcionalidades
- [x] Estimar complexidade
- [x] Criar este documento

### Fase 2: Model (2h)
- [ ] Criar `app/Models/ColaboradorModel.php`
- [ ] Definir `$table = 'colaboradores'`
- [ ] Definir `$fillable` (17 campos)
- [ ] Definir `$rules` com validação CPF customizada
- [ ] Configurar `$casts` (salario, ativo, data_admissao)
- [ ] Implementar 5 scopes (porNivel, ativos, inativos, porOrigem, buscar)
- [ ] Implementar 2 relacionamentos (treinamentos, unidades)
- [ ] Implementar métodos personalizados:
  - [ ] `getHistoricoTreinamentos()`
  - [ ] `getEstatisticas()`
  - [ ] `ativar()`
  - [ ] `inativar()`
- [ ] Implementar eventos (onCreated, onUpdated)
- [ ] Testar CRUD básico

### Fase 3: Controller (1.5h)
- [ ] Criar `app/Controllers/ColaboradorController.php`
- [ ] Injetar ColaboradorModel via construtor
- [ ] Implementar `index()` com filtros
- [ ] Implementar `create()`
- [ ] Implementar `store()` com validação
- [ ] Implementar `show($id)` com histórico
- [ ] Implementar `edit($id)`
- [ ] Implementar `update($id)`
- [ ] Implementar `destroy($id)` (inativação)
- [ ] Implementar `ativar($id)`
- [ ] Implementar `exportarCSV()`
- [ ] Implementar `api()` (JSON endpoint)
- [ ] Testar todas as actions

### Fase 4: Views (2.5h)
- [ ] Criar `app/views/colaboradores/index.php`
  - [ ] Herdar layout principal
  - [ ] Formulário de filtros (nome, email, nível, status)
  - [ ] Tabela responsiva
  - [ ] Paginação
  - [ ] Botões de ação (visualizar, editar, ativar/inativar)
- [ ] Criar `app/views/colaboradores/form.php`
  - [ ] Formulário unificado (criar/editar)
  - [ ] Seção Identificação (nome, email, cpf, foto)
  - [ ] Seção Profissional (nível, cargo, departamento, salário, admissão)
  - [ ] Seção Contato (telefone)
  - [ ] Seção Sistema (ativo, observações)
  - [ ] Validação client-side
  - [ ] Máscaras (CPF, telefone, salário)
- [ ] Criar `app/views/colaboradores/show.php`
  - [ ] Informações do colaborador
  - [ ] Cards de estatísticas (treinamentos, horas, média)
  - [ ] Tabela de histórico de treinamentos
  - [ ] Botões contextuais (editar, ativar/inativar)

### Fase 5: Rotas (0.5h)
- [ ] Adicionar rotas em `app/routes.php`:
```php
$router->group(['middleware' => ['auth']], function ($router) {
    // Colaboradores
    $router->get('/colaboradores', 'ColaboradorController@index');
    $router->get('/colaboradores/criar', 'ColaboradorController@create');
    $router->post('/colaboradores', 'ColaboradorController@store', ['csrf']);
    $router->get('/colaboradores/exportar', 'ColaboradorController@exportarCSV');
    $router->get('/colaboradores/{id}', 'ColaboradorController@show');
    $router->get('/colaboradores/{id}/editar', 'ColaboradorController@edit');
    $router->put('/colaboradores/{id}', 'ColaboradorController@update', ['csrf']);
    $router->delete('/colaboradores/{id}', 'ColaboradorController@destroy', ['csrf', 'admin']);
    $router->post('/colaboradores/{id}/ativar', 'ColaboradorController@ativar', ['csrf', 'admin']);

    // API
    $router->get('/api/colaboradores', 'ColaboradorController@api');
});
```

### Fase 6: Testes (2h)
- [ ] Criar `COLABORADORES_TESTES.md`
- [ ] Documentar 35 test cases:
  - [ ] 10 CRUD tests
  - [ ] 7 Validation tests (incluindo CPF)
  - [ ] 8 UI/UX tests
  - [ ] 5 Security tests
  - [ ] 3 Performance tests
  - [ ] 2 API tests
- [ ] Executar testes
- [ ] Documentar resultados

### Fase 7: Documentação (0.5h)
- [ ] Criar `MIGRACAO_COLABORADORES_STATUS.md`
- [ ] Atualizar `PROGRESSO_DESENVOLVIMENTO.md`
- [ ] Atualizar `README.md` (Sprint 4 progress)
- [ ] Commit e push

---

## 🎯 PRÓXIMOS PASSOS

### Imediato (Fase 2)
1. Criar validação customizada de CPF
2. Criar ColaboradorModel
3. Testar validações

### Sequência
1. ✅ **Fase 1:** Análise (completa)
2. ⏭️ **Fase 2:** Model (próximo)
3. **Fase 3:** Controller
4. **Fase 4:** Views
5. **Fase 5:** Rotas
6. **Fase 6:** Testes
7. **Fase 7:** Documentação

### Meta
🎯 **Completar Sprint 4 em 6-7 horas**
🎯 **Colaboradores 100% migrado para arquitetura Core v2.0**

---

## 📚 REFERÊNCIAS

- `GUIA_MIGRACAO_MODULOS_V2.md` - Template de migração
- `MIGRACAO_TREINAMENTOS_STATUS.md` - Exemplo Sprint 3
- `app/Core/Model.php` - Base Model
- `app/Core/Controller.php` - Base Controller
- `app/Models/TreinamentoModel.php` - Exemplo de Model moderno

---

**STATUS:** ✅ Fase 1 Completa - Análise Finalizada
**PRÓXIMO:** Fase 2 - Criar ColaboradorModel
**ETA:** 2 horas

---

**FIM DA ANÁLISE**
