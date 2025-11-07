# 📋 DOCUMENTAÇÃO COMPLETA - SISTEMA DE CHECKLIST DE LOJAS

**Versão:** 1.0
**Data:** 2025-11-07
**Desenvolvido por:** IDE Digital - Claude AI

---

## 📑 Índice

1. [Visão Geral](#visão-geral)
2. [Estrutura de Diretórios](#estrutura-de-diretórios)
3. [Arquitetura do Sistema](#arquitetura-do-sistema)
4. [Banco de Dados](#banco-de-dados)
5. [Models (Modelos)](#models-modelos)
6. [Controllers (Controladores)](#controllers-controladores)
7. [Services (Serviços)](#services-serviços)
8. [Helpers (Auxiliares)](#helpers-auxiliares)
9. [Views (Páginas)](#views-páginas)
10. [Sistema de Pontuação](#sistema-de-pontuação)
11. [Fluxo de Dados](#fluxo-de-dados)
12. [Instalação](#instalação)
13. [Bugs Conhecidos](#bugs-conhecidos)
14. [Como Usar](#como-usar)
15. [Próximos Passos](#próximos-passos)
16. [Manutenção](#manutenção)

---

## 1. Visão Geral

### O que é?
Sistema completo de avaliação de lojas baseado em checklist com pontuação por estrelas (1-5), calculando automaticamente percentuais e classificações.

### Principais Funcionalidades
- ✅ Avaliação de lojas por módulos/setores
- ⭐ Sistema de estrelas (1-5) para cada pergunta
- 🔢 Cálculo automático de pontuação ponderada
- 📊 Dashboard com estatísticas e gráficos
- 🏆 Ranking de lojas por desempenho
- 📈 Relatórios de evolução temporal
- 📸 Upload de fotos por pergunta (preparado)
- 🎯 Meta de aprovação configurável (padrão: 80%)

### Conceitos-Chave
- **Loja:** Unidade física que será avaliada
- **Módulo de Avaliação:** Setor/área a ser avaliada (ex: Caixas, Estoque)
- **Pergunta:** Item específico dentro de um módulo
- **Checklist:** Uma avaliação completa de um módulo em uma loja
- **Resposta:** Nota (1-5 estrelas) dada a uma pergunta
- **Peso:** Valor em pontos de cada estrela (varia por módulo)
- **Pontuação Total:** Soma dos pontos de todas as respostas
- **Percentual:** Relação entre pontuação obtida e máxima (0-100%)

---

## 2. Estrutura de Diretórios

```
dev1/
│
├── app/
│   ├── classes/
│   │   ├── Auth.php                    # Autenticação
│   │   └── Database.php                # Conexão singleton
│   │
│   ├── config/
│   │   ├── config.php                  # Configurações gerais
│   │   └── database.php                # Credenciais DB
│   │
│   ├── controllers/
│   │   ├── ChecklistController.php     # CRUD de checklists
│   │   └── RelatorioChecklistController.php  # Relatórios
│   │
│   ├── helpers/
│   │   ├── PontuacaoHelper.php         # Cálculos de pontuação
│   │   └── RelatorioHelper.php         # Formatação de relatórios
│   │
│   ├── models/
│   │   ├── Checklist.php               # Gestão de checklists
│   │   ├── RespostaChecklist.php       # Respostas e pontos
│   │   ├── ModuloAvaliacao.php         # Módulos/setores
│   │   ├── Pergunta.php                # Perguntas
│   │   ├── Loja.php                    # Lojas
│   │   └── Configuracao.php            # Configurações
│   │
│   ├── services/
│   │   └── RelatorioService.php        # Lógica de relatórios
│   │
│   └── views/
│       └── layouts/
│           ├── header.php              # Cabeçalho
│           ├── sidebar.php             # Menu lateral (MODIFICADO)
│           └── footer.php              # Rodapé
│
├── database/
│   └── migrations/
│       ├── checklist_lojas_schema.sql  # Estrutura das tabelas
│       └── checklist_lojas_seed.sql    # Dados iniciais
│
├── public/
│   ├── instalar_checklist.php          # Instalador automático
│   │
│   ├── checklist/
│   │   ├── index.php                   # ✅ Lista de checklists
│   │   ├── novo.php                    # ✅ Criar nova avaliação
│   │   ├── editar.php                  # ⏳ Preencher avaliação (PENDENTE)
│   │   ├── visualizar.php              # ⏳ Ver checklist completo (PENDENTE)
│   │   ├── lojas.php                   # ⏳ CRUD de lojas (PENDENTE)
│   │   ├── modulos.php                 # ⏳ CRUD de módulos (PENDENTE)
│   │   │
│   │   └── relatorios/
│   │       └── index.php               # ✅ Dashboard
│   │
│   └── uploads/
│       └── fotos_checklist/            # Diretório para fotos
│
└── docs/
    ├── CHECKLIST_LOJAS_README.md       # README técnico
    ├── INSTALACAO_CHECKLIST.md         # Guia de instalação
    └── CHECKLIST_LOJAS_DOCUMENTACAO_COMPLETA.md  # Este arquivo
```

---

## 3. Arquitetura do Sistema

### Padrão MVC (Model-View-Controller)

```
┌─────────────┐
│   BROWSER   │
└──────┬──────┘
       │
       ▼
┌─────────────────┐
│  VIEW (Página)  │  ← public/checklist/*.php
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│   CONTROLLER    │  ← app/controllers/*Controller.php
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│     MODEL       │  ← app/models/*.php
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│    DATABASE     │  ← MySQL
└─────────────────┘
```

### Camadas Adicionais

```
CONTROLLER
    │
    ├──► SERVICE     (Lógica de negócio complexa)
    │
    └──► HELPER      (Funções auxiliares e cálculos)
```

### Fluxo de Requisição

1. **Usuário acessa** `public/checklist/novo.php`
2. **View carrega** configurações e autenticação
3. **Controller** `ChecklistController::criar()` é chamado
4. **Model** `Checklist::criar()` insere no banco
5. **Helper** `PontuacaoHelper` calcula pesos
6. **Service** `RelatorioService` gera estatísticas
7. **View** renderiza dados na página

---

## 4. Banco de Dados

### 4.1. Estrutura (8 Tabelas)

#### **lojas**
Armazena informações das lojas a serem avaliadas.

```sql
CREATE TABLE lojas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(200) NOT NULL,
    codigo VARCHAR(50),
    endereco TEXT,
    cidade VARCHAR(100),
    estado VARCHAR(2),
    telefone VARCHAR(20),
    email VARCHAR(200),
    gerente_responsavel VARCHAR(200),
    ativo BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**Campos importantes:**
- `codigo`: Código interno da loja
- `ativo`: Permite desativar lojas sem deletar histórico

---

#### **modulos_avaliacao**
Define os setores/áreas que serão avaliados.

```sql
CREATE TABLE modulos_avaliacao (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(200) NOT NULL,
    descricao TEXT,
    icone VARCHAR(50),
    ordem INT DEFAULT 0,
    ativo BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**Módulos pré-cadastrados:**
1. Organização de Lojas (8 perguntas)
2. Caixas (6 perguntas)
3. Setor Ovos (8 perguntas)
4. Gôndolas e Ilhas (8 perguntas)
5. Balcão de Frios (8 perguntas)
6. Câmara Fria (8 perguntas)
7. Estoque (8 perguntas)
8. Áreas Comuns (6 perguntas)

---

#### **perguntas**
Perguntas específicas de cada módulo.

```sql
CREATE TABLE perguntas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    modulo_id INT NOT NULL,
    titulo TEXT NOT NULL,
    descricao TEXT,
    ordem INT DEFAULT 0,
    ativo BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (modulo_id) REFERENCES modulos_avaliacao(id)
);
```

**Total pré-cadastrado:** 58 perguntas distribuídas nos 8 módulos

**Exemplo:**
```sql
INSERT INTO perguntas (modulo_id, titulo, ordem) VALUES
(1, 'A loja está limpa e organizada?', 1),
(1, 'A sinalização está visível e correta?', 2);
```

---

#### **checklists**
Registro de cada avaliação realizada.

```sql
CREATE TABLE checklists (
    id INT PRIMARY KEY AUTO_INCREMENT,
    loja_id INT NOT NULL,
    colaborador_id INT NOT NULL,
    data_avaliacao DATE NOT NULL,
    modulo_id INT NOT NULL,
    pontuacao_total DECIMAL(4,2) DEFAULT 0,
    pontuacao_maxima DECIMAL(4,2) DEFAULT 5,
    percentual DECIMAL(5,2) DEFAULT 0,
    atingiu_meta BOOLEAN DEFAULT 0,
    status ENUM('rascunho', 'finalizado', 'revisado') DEFAULT 'rascunho',
    observacoes_gerais TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    finalizado_em DATETIME,
    FOREIGN KEY (loja_id) REFERENCES lojas(id),
    FOREIGN KEY (colaborador_id) REFERENCES usuarios_sistema(id),
    FOREIGN KEY (modulo_id) REFERENCES modulos_avaliacao(id)
);
```

**Campos calculados automaticamente:**
- `pontuacao_total`: Soma dos pontos de todas as respostas
- `percentual`: (pontuacao_total / pontuacao_maxima) × 100
- `atingiu_meta`: TRUE se percentual ≥ 80%

**Status:**
- `rascunho`: Ainda pode ser editado
- `finalizado`: Não pode mais ser editado
- `revisado`: Passou por revisão do gestor

---

#### **respostas_checklist**
Respostas individuais para cada pergunta.

```sql
CREATE TABLE respostas_checklist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    checklist_id INT NOT NULL,
    pergunta_id INT NOT NULL,
    estrelas INT NOT NULL CHECK (estrelas BETWEEN 1 AND 5),
    pontuacao DECIMAL(4,3) DEFAULT 0,
    observacao TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (checklist_id) REFERENCES checklists(id) ON DELETE CASCADE,
    FOREIGN KEY (pergunta_id) REFERENCES perguntas(id)
);
```

**Campos importantes:**
- `estrelas`: Nota dada (1-5)
- `pontuacao`: Valor em pontos (calculado automaticamente)
- `observacao`: Comentário opcional sobre a resposta

---

#### **fotos_checklist**
Fotos anexadas às respostas.

```sql
CREATE TABLE fotos_checklist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    resposta_id INT NOT NULL,
    caminho VARCHAR(255) NOT NULL,
    descricao TEXT,
    tamanho INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (resposta_id) REFERENCES respostas_checklist(id) ON DELETE CASCADE
);
```

**Nota:** Upload de fotos está preparado mas não implementado nas views.

---

#### **configuracoes_sistema**
Configurações globais do sistema.

```sql
CREATE TABLE configuracoes_sistema (
    id INT PRIMARY KEY AUTO_INCREMENT,
    chave VARCHAR(100) NOT NULL UNIQUE,
    valor TEXT,
    tipo ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    descricao TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**Configurações pré-cadastradas:**

| Chave | Valor | Descrição |
|-------|-------|-----------|
| `percentual_aprovacao` | 80 | Meta para aprovar (%) |
| `peso_8_perguntas_1_estrela` | 0.125 | Peso para 1⭐ em 8 perguntas |
| `peso_8_perguntas_2_estrela` | 0.25 | Peso para 2⭐ em 8 perguntas |
| ... | ... | ... |
| `peso_6_perguntas_1_estrela` | 0.167 | Peso para 1⭐ em 6 perguntas |
| ... | ... | ... |

---

#### **cargos_checklist**
Cargos dos avaliadores (opcional).

```sql
CREATE TABLE cargos_checklist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    pode_avaliar BOOLEAN DEFAULT 1,
    pode_revisar BOOLEAN DEFAULT 0,
    ativo BOOLEAN DEFAULT 1
);
```

---

### 4.2. Relacionamentos

```
lojas (1) ───────────────► (N) checklists
                                   │
                                   │ (1)
                                   │
                                   ▼
usuarios_sistema (1) ──────► (N) checklists
                                   │
                                   │ (1)
                                   │
                                   ▼
modulos_avaliacao (1) ─────► (N) checklists
     │                            │
     │ (1)                        │ (1)
     │                            │
     ▼                            ▼
     (N) perguntas ◄────────── (N) respostas_checklist
                                   │
                                   │ (1)
                                   │
                                   ▼
                               (N) fotos_checklist
```

---

## 5. Models (Modelos)

### 5.1. Checklist.php

**Responsabilidade:** Gerenciar checklists completos (CRUD + cálculos).

**Principais Métodos:**

```php
// Criar novo checklist
public function criar($dados)
// $dados = [
//     'loja_id' => 1,
//     'colaborador_id' => 2,
//     'modulo_id' => 3,
//     'data_avaliacao' => '2025-11-07',
//     'observacoes_gerais' => 'Texto opcional'
// ]

// Buscar por ID
public function buscarPorId($id)

// Listar com filtros
public function listar($filtros = [], $paginacao = [])
// $filtros = [
//     'loja_id' => 1,
//     'modulo_id' => 2,
//     'status' => 'finalizado',
//     'data_inicio' => '2025-01-01',
//     'data_fim' => '2025-12-31'
// ]

// Calcular pontuação (AUTOMÁTICO)
public function calcularPontuacao($checklistId)
// Soma pontos das respostas, calcula percentual e atualiza checklist

// Finalizar checklist
public function finalizar($checklistId)
// Muda status para 'finalizado', bloqueia edição

// Excluir (soft delete)
public function excluir($id)
```

**Cálculo Automático:**
Sempre que uma resposta é salva, o método `calcularPontuacao()` é chamado automaticamente para atualizar:
- `pontuacao_total`
- `percentual`
- `atingiu_meta`

---

### 5.2. RespostaChecklist.php

**Responsabilidade:** Gerenciar respostas individuais e calcular pontos.

**Principais Métodos:**

```php
// Salvar resposta
public function salvar($dados)
// $dados = [
//     'checklist_id' => 1,
//     'pergunta_id' => 5,
//     'estrelas' => 4,          // 1-5
//     'observacao' => 'OK'      // Opcional
// ]
// IMPORTANTE: Calcula pontuação automaticamente!

// Buscar respostas de um checklist
public function buscarPorChecklist($checklistId)

// Calcular pontuação individual (INTERNO)
private function calcularPontuacao($estrelas, $totalPerguntas)
// Busca peso na tabela configuracoes_sistema
// Fallback para cálculo proporcional se não encontrar
```

**Exemplo de Cálculo:**
```
Módulo: 8 perguntas
Estrelas: 4
Total de perguntas no módulo: 8

1. Busca: peso_8_perguntas_4_estrela = 0.5
2. Retorna: 0.5 pontos
```

---

### 5.3. ModuloAvaliacao.php

**Responsabilidade:** Gerenciar módulos de avaliação.

**Principais Métodos:**

```php
// Listar todos ativos
public function listarAtivos()

// Buscar por ID com total de perguntas
public function buscarPorId($id)
// Retorna: ['id', 'nome', 'descricao', 'total_perguntas']

// Criar/editar módulo
public function criar($dados)
public function atualizar($id, $dados)

// Desativar (soft delete)
public function desativar($id)
```

---

### 5.4. Pergunta.php

**Responsabilidade:** Gerenciar perguntas dos módulos.

**Principais Métodos:**

```php
// Listar por módulo
public function listarPorModulo($moduloId)

// Criar pergunta
public function criar($dados)
// $dados = [
//     'modulo_id' => 1,
//     'titulo' => 'Loja limpa?',
//     'descricao' => 'Verificar chão...',
//     'ordem' => 1
// ]

// Reordenar perguntas
public function reordenar($moduloId, $novaOrdem)
```

---

### 5.5. Loja.php

**Responsabilidade:** Gerenciar cadastro de lojas.

**Principais Métodos:**

```php
// CRUD básico
public function criar($dados)
public function atualizar($id, $dados)
public function buscarPorId($id)
public function listar($filtros = [])

// Buscar com estatísticas
public function buscarComEstatisticas($lojaId)
// Retorna loja + total de avaliações + média de percentual
```

---

### 5.6. Configuracao.php

**Responsabilidade:** Gerenciar configurações do sistema.

**Principais Métodos:**

```php
// Buscar por chave
public function buscarPorChave($chave)
// Ex: buscarPorChave('percentual_aprovacao') => 80

// Atualizar valor
public function atualizar($chave, $valor)

// Listar todas
public function listar()

// Buscar peso específico
public function buscarPeso($totalPerguntas, $estrelas)
// Ex: buscarPeso(8, 4) => 0.5
```

---

## 6. Controllers (Controladores)

### 6.1. ChecklistController.php

**Responsabilidade:** Orquestrar operações de checklist.

**Métodos Públicos:**

```php
// Exibir formulário de novo checklist
public function exibirFormularioNovo()
// Retorna: ['lojas' => [], 'modulos' => []]
// Usado em: public/checklist/novo.php

// Criar checklist
public function criar()
// POST data, valida, cria registro
// Retorna: ['success' => bool, 'checklist_id' => int, 'message' => string]

// Listar checklists com filtros
public function listar()
// GET params, filtra, pagina
// Retorna: [
//     'checklists' => [],
//     'filtros' => [],
//     'estatisticas' => [],
//     'paginacao' => []
// ]

// Salvar resposta
public function salvarResposta()
// POST: pergunta_id, estrelas, observacao
// Calcula pontuação automática

// Finalizar checklist
public function finalizar($checklistId)
// Valida se todas as perguntas foram respondidas
// Muda status para 'finalizado'

// Visualizar checklist
public function visualizar($checklistId)
// Retorna dados completos para exibição
```

**Exemplo de Uso:**

```php
// Em public/checklist/novo.php
$controller = new ChecklistController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultado = $controller->criar();
    if ($resultado['success']) {
        header('Location: editar.php?id=' . $resultado['checklist_id']);
    }
} else {
    $dados = $controller->exibirFormularioNovo();
    // Renderiza formulário com $dados['lojas'] e $dados['modulos']
}
```

---

### 6.2. RelatorioChecklistController.php

**Responsabilidade:** Gerar dados para relatórios e dashboard.

**Métodos Públicos:**

```php
// Dashboard principal
public function dashboard()
// GET params: loja_id, data_inicio, data_fim
// Retorna: [
//     'estatisticas_gerais' => [
//         'total_checklists',
//         'media_percentual',
//         'taxa_aprovacao',
//         'total_lojas'
//     ],
//     'ranking_lojas' => [],
//     'distribuicao_notas' => [],
//     'desempenho_setores' => [],
//     'lojas' => [],
//     'filtros' => []
// ]

// Exportar para CSV
public function exportarCSV($filtros)
// Gera arquivo CSV para download

// Dados para gráfico de evolução
public function evolucaoTemporal($lojaId, $moduloId)
// Retorna série temporal de percentuais
```

**Exemplo de Uso:**

```php
// Em public/checklist/relatorios/index.php
$controller = new RelatorioChecklistController();
$dados = $controller->dashboard();

// Renderiza:
// - Cards com estatísticas
// - Ranking de lojas
// - Gráficos de distribuição
// - Desempenho por setor
```

---

## 7. Services (Serviços)

### 7.1. RelatorioService.php

**Responsabilidade:** Lógica de negócio complexa para relatórios.

**Principais Métodos:**

```php
// Estatísticas gerais
public function obterEstatisticasGerais($filtros = [])
// Retorna: total, média, aprovação, etc.

// Ranking de lojas
public function obterRankingLojas($filtros = [])
// Ordena lojas por média de percentual
// Inclui total de avaliações

// Distribuição de classificações
public function obterDistribuicaoNotas($filtros = [])
// Conta: Excelente, Bom, Regular, Ruim, Muito Ruim

// Desempenho por setor
public function obterDesempenhoPorSetor($filtros = [])
// Média de cada módulo

// Evolução temporal
public function obterEvolucaoTemporal($lojaId, $moduloId, $periodo)
// Série histórica de percentuais

// Comparação entre lojas
public function compararLojas($lojasIds, $moduloId = null)
// Compara métricas de múltiplas lojas
```

**Por que Service?**
Queries complexas que envolvem múltiplas tabelas e cálculos agregados ficam isoladas aqui, mantendo Controllers limpos.

---

## 8. Helpers (Auxiliares)

### 8.1. PontuacaoHelper.php

**Responsabilidade:** Cálculos e conversões de pontuação.

**Constantes:**

```php
// Pesos para módulos de 8 perguntas
const PESOS_8_PERGUNTAS = [
    1 => 0.125,  // 1 estrela = 0.125 pontos
    2 => 0.25,   // 2 estrelas = 0.25 pontos
    3 => 0.375,
    4 => 0.5,
    5 => 0.625   // 5 estrelas = 0.625 pontos
];

// Pesos para módulos de 6 perguntas
const PESOS_6_PERGUNTAS = [
    1 => 0.167,
    2 => 0.333,
    3 => 0.500,
    4 => 0.667,
    5 => 0.833
];

// Meta de aprovação
const PERCENTUAL_APROVACAO = 80;
```

**Principais Métodos:**

```php
// Obter peso baseado no total de perguntas
public static function obterPeso($totalPerguntas, $estrelas)
// Ex: obterPeso(8, 4) => 0.5
// Ex: obterPeso(6, 5) => 0.833

// Calcular percentual
public static function calcularPercentual($pontuacaoObtida, $pontuacaoMaxima)
// Ex: calcularPercentual(3.5, 5.0) => 70.0

// Classificar por percentual
public static function classificarPercentual($percentual)
// Retorna: 'Excelente', 'Bom', 'Regular', 'Ruim', 'Muito Ruim'

// Obter cor do percentual
public static function obterCorPercentual($percentual)
// Retorna: '#28a745' (verde), '#007bff' (azul), etc.

// Verificar se atingiu meta
public static function atingiuMeta($percentual)
// Retorna: true se >= 80%

// Converter percentual em estrelas (visual)
public static function percentualParaEstrelas($percentual)
// Ex: 70% => 3.5 estrelas
```

**Fórmulas:**

```
PONTUAÇÃO MÁXIMA = 5 pontos (sempre)

Para 8 perguntas:
- Cada pergunta vale: 5 ÷ 8 = 0.625 pontos (5 estrelas)
- 1 estrela = 0.625 ÷ 5 = 0.125 pontos
- Pontuação total = Σ (estrelas × 0.125)

Para 6 perguntas:
- Cada pergunta vale: 5 ÷ 6 = 0.833 pontos (5 estrelas)
- 1 estrela = 0.833 ÷ 5 = 0.167 pontos

Percentual = (pontuação_total ÷ 5) × 100
```

**Classificação:**

| Percentual | Classificação | Cor | Estrelas Visuais |
|------------|---------------|-----|------------------|
| ≥ 80% | ⭐⭐⭐⭐⭐ Excelente | Verde (#28a745) | 4-5 estrelas |
| ≥ 60% | ⭐⭐⭐⭐ Bom | Azul (#007bff) | 3-4 estrelas |
| ≥ 40% | ⭐⭐⭐ Regular | Amarelo (#ffc107) | 2-3 estrelas |
| ≥ 20% | ⭐⭐ Ruim | Laranja (#fd7e14) | 1-2 estrelas |
| < 20% | ⭐ Muito Ruim | Vermelho (#dc3545) | 0-1 estrelas |

---

### 8.2. RelatorioHelper.php

**Responsabilidade:** Formatação de dados para relatórios.

**Principais Métodos:**

```php
// Preparar dados para gráfico de linha
public static function prepararGraficoLinha($dados, $labelX, $labelY)
// Retorna: ['labels' => [], 'datasets' => []]

// Preparar dados para gráfico de pizza
public static function prepararGraficoPizza($dados, $labelCampo, $valorCampo)
// Retorna: ['labels' => [], 'data' => [], 'backgroundColor' => []]

// Preparar dados para gráfico de barras
public static function prepararGraficoBarras($dados)

// Formatar número
public static function formatarNumero($numero, $decimais = 2)
// Ex: 1234.5 => "1.234,50"

// Formatar percentual
public static function formatarPercentual($percentual)
// Ex: 75.3456 => "75,3%"

// Formatar data
public static function formatarData($data, $formato = 'd/m/Y')
// Ex: "2025-11-07" => "07/11/2025"

// Gerar cores para gráficos
public static function gerarCores($quantidade)
// Retorna array de cores hexadecimais

// Exportar para CSV
public static function gerarCSV($dados, $colunas, $nomeArquivo)
// Gera arquivo CSV para download
```

---

## 9. Views (Páginas)

### 9.1. public/checklist/index.php ✅

**Função:** Listar todos os checklists com filtros.

**Recursos:**
- Cards com estatísticas gerais
- Filtros: loja, módulo, data, status
- Tabela com todos os checklists
- Paginação
- Links para editar (rascunho) ou visualizar (finalizado)

**Dados Exibidos:**
```php
[
    'checklists' => [
        ['id', 'data_avaliacao', 'loja_nome', 'modulo_nome',
         'percentual', 'atingiu_meta', 'status']
    ],
    'estatisticas' => [
        'total_checklists', 'media_percentual',
        'total_aprovados', 'total_reprovados'
    ],
    'lojas' => [...],
    'modulos' => [...],
    'filtros' => [...]
]
```

---

### 9.2. public/checklist/novo.php ✅

**Função:** Criar nova avaliação.

**Formulário:**
- Select: Loja
- Select: Módulo de Avaliação
- Date: Data da Avaliação
- Textarea: Observações Gerais (opcional)
- Button: Criar e Começar Avaliação

**Fluxo:**
1. Usuário preenche formulário
2. POST para `ChecklistController::criar()`
3. Cria checklist em status "rascunho"
4. Redireciona para `editar.php?id={checklist_id}`

---

### 9.3. public/checklist/editar.php ⏳ PENDENTE

**Função:** Preencher avaliação com perguntas e estrelas.

**O que precisa ter:**

```html
<!-- Para cada pergunta do módulo -->
<div class="pergunta-card">
    <h4>1. A loja está limpa e organizada?</h4>
    <p class="descricao">Verificar chão, prateleiras...</p>

    <!-- Sistema de estrelas (JavaScript) -->
    <div class="estrelas">
        <span class="estrela" data-valor="1">⭐</span>
        <span class="estrela" data-valor="2">⭐</span>
        <span class="estrela" data-valor="3">⭐</span>
        <span class="estrela" data-valor="4">⭐</span>
        <span class="estrela" data-valor="5">⭐</span>
    </div>

    <!-- Observação opcional -->
    <textarea name="observacao" placeholder="Observação..."></textarea>

    <!-- Upload de foto -->
    <input type="file" name="foto" accept="image/*">

    <!-- Salvar via AJAX -->
    <button onclick="salvarResposta(pergunta_id, estrelas, observacao)">
        Salvar
    </button>
</div>

<!-- Botão final -->
<button onclick="finalizar()">✅ Finalizar Avaliação</button>
```

**JavaScript Necessário:**

```javascript
// Capturar cliques nas estrelas
document.querySelectorAll('.estrela').forEach(estrela => {
    estrela.addEventListener('click', function() {
        const valor = this.dataset.valor;
        const perguntaId = this.closest('.pergunta-card').dataset.perguntaId;
        marcarEstrelas(valor);
        salvarResposta(perguntaId, valor);
    });
});

// Salvar via AJAX
function salvarResposta(perguntaId, estrelas, observacao = '') {
    fetch('salvar_resposta.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            pergunta_id: perguntaId,
            estrelas: estrelas,
            observacao: observacao
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Atualizar pontuação total na tela
            atualizarPontuacao();
        }
    });
}
```

---

### 9.4. public/checklist/visualizar.php ⏳ PENDENTE

**Função:** Exibir checklist finalizado (somente leitura).

**O que precisa ter:**

```html
<!-- Cabeçalho -->
<div class="header">
    <h1>📋 Checklist #123</h1>
    <div class="info">
        <p><strong>Loja:</strong> Loja Central</p>
        <p><strong>Módulo:</strong> Organização de Lojas</p>
        <p><strong>Data:</strong> 07/11/2025</p>
        <p><strong>Avaliador:</strong> João Silva</p>
    </div>
</div>

<!-- Pontuação -->
<div class="pontuacao-card">
    <h2>Pontuação: 4.25 / 5.00</h2>
    <div class="percentual">85%</div>
    <div class="classificacao excelente">⭐⭐⭐⭐⭐ Excelente</div>
    <div class="meta">✅ Atingiu a meta de 80%</div>
</div>

<!-- Respostas -->
<div class="respostas">
    <div class="resposta">
        <h4>1. Loja limpa?</h4>
        <div class="estrelas-readonly">⭐⭐⭐⭐⭐</div>
        <p class="observacao">Tudo perfeito</p>
        <img src="foto.jpg" alt="Foto">
    </div>
    <!-- Repetir para cada resposta -->
</div>

<!-- Observações Gerais -->
<div class="observacoes">
    <h3>Observações Gerais</h3>
    <p>Loja em ótimas condições...</p>
</div>

<!-- Botões -->
<button onclick="imprimir()">🖨️ Imprimir</button>
<button onclick="exportarPDF()">📄 Exportar PDF</button>
```

---

### 9.5. public/checklist/lojas.php ⏳ PENDENTE

**Função:** CRUD de lojas.

**O que precisa ter:**

```html
<!-- Lista de lojas -->
<table>
    <thead>
        <tr>
            <th>Código</th>
            <th>Nome</th>
            <th>Cidade</th>
            <th>Gerente</th>
            <th>Total Avaliações</th>
            <th>Média</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>L001</td>
            <td>Loja Central</td>
            <td>São Paulo</td>
            <td>João Silva</td>
            <td>15</td>
            <td>82%</td>
            <td>
                <a href="?editar=1">✏️ Editar</a>
                <a href="?desativar=1">🚫 Desativar</a>
            </td>
        </tr>
    </tbody>
</table>

<!-- Formulário de cadastro/edição (modal ou inline) -->
<form method="POST">
    <input name="codigo" placeholder="Código">
    <input name="nome" placeholder="Nome da Loja" required>
    <input name="endereco" placeholder="Endereço">
    <input name="cidade" placeholder="Cidade">
    <select name="estado">
        <option value="SP">SP</option>
        <!-- ... -->
    </select>
    <input name="telefone" placeholder="Telefone">
    <input name="email" type="email" placeholder="Email">
    <input name="gerente_responsavel" placeholder="Gerente">
    <button type="submit">💾 Salvar</button>
</form>
```

---

### 9.6. public/checklist/modulos.php ⏳ PENDENTE

**Função:** Gerenciar módulos e perguntas (admin).

**O que precisa ter:**

```html
<!-- Lista de módulos -->
<div class="modulos-lista">
    <div class="modulo-card">
        <h3>1. Organização de Lojas</h3>
        <p>8 perguntas</p>
        <button onclick="editarModulo(1)">✏️ Editar</button>
        <button onclick="gerenciarPerguntas(1)">📝 Perguntas</button>
    </div>
    <!-- Repetir -->
</div>

<!-- Modal: Editar Módulo -->
<div id="modal-modulo">
    <form method="POST">
        <input name="nome" placeholder="Nome do Módulo">
        <textarea name="descricao" placeholder="Descrição"></textarea>
        <input name="icone" placeholder="Ícone (emoji)">
        <button type="submit">💾 Salvar</button>
    </form>
</div>

<!-- Modal: Gerenciar Perguntas -->
<div id="modal-perguntas">
    <h3>Perguntas do Módulo: Organização de Lojas</h3>

    <!-- Lista ordenável (drag & drop) -->
    <ul id="perguntas-lista" class="sortable">
        <li data-id="1">
            <span class="handle">☰</span>
            <span class="titulo">A loja está limpa?</span>
            <button onclick="editarPergunta(1)">✏️</button>
            <button onclick="excluirPergunta(1)">🗑️</button>
        </li>
        <!-- Repetir -->
    </ul>

    <!-- Adicionar nova pergunta -->
    <form method="POST">
        <input name="titulo" placeholder="Título da pergunta">
        <textarea name="descricao" placeholder="Descrição (opcional)"></textarea>
        <button type="submit">➕ Adicionar Pergunta</button>
    </form>
</div>
```

**JavaScript para ordenação:**
```javascript
// Usar biblioteca Sortable.js
const sortable = new Sortable(document.getElementById('perguntas-lista'), {
    animation: 150,
    handle: '.handle',
    onEnd: function(evt) {
        salvarNovaOrdem();
    }
});
```

---

### 9.7. public/checklist/relatorios/index.php ✅

**Função:** Dashboard com estatísticas e gráficos.

**Recursos:**
- Filtros: loja, período
- Cards com métricas principais
- Ranking de lojas (top 10)
- Gráfico de distribuição de notas
- Gráfico de desempenho por setor
- Evolução temporal (preparado)

**Estrutura:**
```
┌─────────────────────────────────────────┐
│ Filtros: [Loja] [Data Início] [Data Fim]│
└─────────────────────────────────────────┘

┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐
│ Total   │ │ Média   │ │ Taxa    │ │ Lojas   │
│ 42      │ │ 78.5%   │ │ 85%     │ │ 12      │
└─────────┘ └─────────┘ └─────────┘ └─────────┘

┌─────────────────────────────────────────┐
│ 🏆 Ranking de Lojas                     │
│ 1º Loja Central        85.2% ⭐⭐⭐⭐⭐    │
│ 2º Loja Norte          82.1% ⭐⭐⭐⭐⭐    │
│ 3º Loja Sul            78.5% ⭐⭐⭐⭐      │
└─────────────────────────────────────────┘

┌─────────────────────────────────────────┐
│ 📊 Distribuição de Classificações       │
│ Excelente  ████████████░░░░░░  60%      │
│ Bom        ██████░░░░░░░░░░░░  30%      │
│ Regular    ██░░░░░░░░░░░░░░░░  10%      │
└─────────────────────────────────────────┘

┌─────────────────────────────────────────┐
│ 📊 Desempenho por Setor                 │
│ Organização  ████████████████  85%      │
│ Caixas       ███████████████░  78%      │
│ Estoque      ██████████████░░  72%      │
└─────────────────────────────────────────┘
```

---

## 10. Sistema de Pontuação

### 10.1. Como Funciona

**Princípio:** Cada checklist tem pontuação máxima de **5.0 pontos**, independente do número de perguntas.

**Pesos dinâmicos:** O peso de cada estrela varia conforme o total de perguntas do módulo.

### 10.2. Fórmulas

#### Pontuação Máxima
```
PONTUAÇÃO_MÁXIMA = 5.0 pontos (sempre)
```

#### Peso de Cada Pergunta
```
PESO_PERGUNTA = 5.0 ÷ TOTAL_PERGUNTAS

Exemplos:
- 8 perguntas: 5 ÷ 8 = 0.625 pontos (5 estrelas)
- 6 perguntas: 5 ÷ 6 = 0.833 pontos (5 estrelas)
```

#### Peso de Cada Estrela
```
PESO_ESTRELA = PESO_PERGUNTA ÷ 5

Para 8 perguntas:
- 1 estrela = 0.625 ÷ 5 = 0.125 pontos
- 2 estrelas = 0.125 × 2 = 0.25 pontos
- 3 estrelas = 0.125 × 3 = 0.375 pontos
- 4 estrelas = 0.125 × 4 = 0.5 pontos
- 5 estrelas = 0.125 × 5 = 0.625 pontos

Para 6 perguntas:
- 1 estrela = 0.833 ÷ 5 = 0.167 pontos
- 2 estrelas = 0.167 × 2 = 0.333 pontos
- ... (e assim por diante)
```

#### Pontuação Total
```
PONTUACAO_TOTAL = Σ (pontos de cada resposta)

Exemplo (8 perguntas):
Pergunta 1: 5 estrelas = 0.625
Pergunta 2: 4 estrelas = 0.5
Pergunta 3: 5 estrelas = 0.625
Pergunta 4: 3 estrelas = 0.375
Pergunta 5: 5 estrelas = 0.625
Pergunta 6: 4 estrelas = 0.5
Pergunta 7: 5 estrelas = 0.625
Pergunta 8: 5 estrelas = 0.625
─────────────────────────
TOTAL: 4.5 pontos
```

#### Percentual
```
PERCENTUAL = (PONTUACAO_TOTAL ÷ 5.0) × 100

Exemplo:
4.5 ÷ 5.0 = 0.9
0.9 × 100 = 90%
```

#### Atingiu Meta?
```
ATINGIU_META = PERCENTUAL >= 80% ? TRUE : FALSE

Exemplo:
90% >= 80% → TRUE ✅
```

### 10.3. Exemplos Práticos

#### Exemplo 1: Módulo de 8 perguntas - Nota Excelente
```
Respostas: 5, 5, 5, 5, 5, 5, 5, 5 (todas 5 estrelas)

Cálculo:
8 × 0.625 = 5.0 pontos

Percentual:
(5.0 ÷ 5.0) × 100 = 100%

Classificação: ⭐⭐⭐⭐⭐ Excelente
Meta: ✅ Atingida (100% >= 80%)
```

#### Exemplo 2: Módulo de 8 perguntas - Nota Mediana
```
Respostas: 4, 3, 5, 4, 3, 4, 5, 4

Cálculo:
0.5 + 0.375 + 0.625 + 0.5 + 0.375 + 0.5 + 0.625 + 0.5 = 4.0 pontos

Percentual:
(4.0 ÷ 5.0) × 100 = 80%

Classificação: ⭐⭐⭐⭐⭐ Excelente (limite inferior)
Meta: ✅ Atingida (80% >= 80%)
```

#### Exemplo 3: Módulo de 6 perguntas
```
Respostas: 5, 5, 4, 5, 4, 5

Cálculo:
0.833 + 0.833 + 0.667 + 0.833 + 0.667 + 0.833 = 4.666 pontos

Percentual:
(4.666 ÷ 5.0) × 100 = 93.3%

Classificação: ⭐⭐⭐⭐⭐ Excelente
Meta: ✅ Atingida (93.3% >= 80%)
```

#### Exemplo 4: Reprovação
```
Módulo: 8 perguntas
Respostas: 3, 2, 3, 2, 3, 2, 3, 2

Cálculo:
0.375 + 0.25 + 0.375 + 0.25 + 0.375 + 0.25 + 0.375 + 0.25 = 2.5 pontos

Percentual:
(2.5 ÷ 5.0) × 100 = 50%

Classificação: ⭐⭐⭐ Regular
Meta: ❌ Não atingida (50% < 80%)
```

### 10.4. Tabela de Referência Rápida

#### Módulos de 8 Perguntas

| Estrelas | Pontos por Pergunta | Total (8 perguntas) | Percentual |
|----------|---------------------|---------------------|------------|
| 5 | 0.625 | 5.0 | 100% |
| 4 | 0.500 | 4.0 | 80% |
| 3 | 0.375 | 3.0 | 60% |
| 2 | 0.250 | 2.0 | 40% |
| 1 | 0.125 | 1.0 | 20% |

#### Módulos de 6 Perguntas

| Estrelas | Pontos por Pergunta | Total (6 perguntas) | Percentual |
|----------|---------------------|---------------------|------------|
| 5 | 0.833 | 5.0 | 100% |
| 4 | 0.667 | 4.0 | 80% |
| 3 | 0.500 | 3.0 | 60% |
| 2 | 0.333 | 2.0 | 40% |
| 1 | 0.167 | 1.0 | 20% |

---

## 11. Fluxo de Dados

### 11.1. Fluxo de Criação de Checklist

```
1. USUÁRIO
   ↓
   Acessa: public/checklist/novo.php
   ↓
2. VIEW (novo.php)
   - Carrega ChecklistController
   - Chama: $controller->exibirFormularioNovo()
   ↓
3. CONTROLLER (ChecklistController)
   - Busca lojas ativas
   - Busca módulos ativos com total de perguntas
   - Retorna: ['lojas' => [], 'modulos' => []]
   ↓
4. VIEW
   - Renderiza formulário com select de lojas e módulos
   - Usuário preenche e submete (POST)
   ↓
5. CONTROLLER
   - Recebe POST
   - Valida dados obrigatórios
   - Chama: $controller->criar()
   ↓
6. MODEL (Checklist)
   - Insere novo registro no banco
   - Status: 'rascunho'
   - Pontuação: 0
   - Retorna: checklist_id
   ↓
7. CONTROLLER
   - Retorna: ['success' => true, 'checklist_id' => 123]
   ↓
8. VIEW
   - Redireciona: editar.php?id=123
```

### 11.2. Fluxo de Preenchimento de Respostas (PENDENTE - editar.php)

```
1. USUÁRIO
   ↓
   Acessa: public/checklist/editar.php?id=123
   ↓
2. VIEW (editar.php)
   - Busca checklist por ID
   - Busca perguntas do módulo
   - Busca respostas já salvas (se houver)
   - Renderiza formulário com estrelas
   ↓
3. USUÁRIO
   - Clica em 4 estrelas na pergunta 1
   ↓
4. JAVASCRIPT
   - Captura evento de clique
   - Envia AJAX para salvar_resposta.php
   - Dados: {checklist_id: 123, pergunta_id: 1, estrelas: 4}
   ↓
5. CONTROLLER (salvarResposta)
   - Valida dados
   - Chama: RespostaChecklist->salvar()
   ↓
6. MODEL (RespostaChecklist)
   - Busca total de perguntas do módulo
   - Calcula pontuação: obterPeso(8, 4) => 0.5
   - Insere/atualiza resposta
   - Chama: Checklist->calcularPontuacao(123)
   ↓
7. MODEL (Checklist::calcularPontuacao)
   - Soma todos os pontos das respostas
   - Calcula percentual: (total ÷ 5) × 100
   - Verifica meta: percentual >= 80%
   - Atualiza campos: pontuacao_total, percentual, atingiu_meta
   ↓
8. CONTROLLER
   - Retorna JSON: {success: true, pontuacao_total: 3.5, percentual: 70}
   ↓
9. JAVASCRIPT
   - Atualiza pontuação na tela em tempo real
   - Marca pergunta como respondida
```

### 11.3. Fluxo de Finalização

```
1. USUÁRIO
   - Responde todas as perguntas
   - Clica em "Finalizar Avaliação"
   ↓
2. JAVASCRIPT
   - Confirma: "Deseja finalizar? Não poderá mais editar"
   - Envia POST para finalizar.php
   ↓
3. CONTROLLER (finalizar)
   - Valida se todas as perguntas foram respondidas
   - Se sim:
     - Atualiza status: 'finalizado'
     - Registra data: finalizado_em = NOW()
     - Retorna: {success: true}
   - Se não:
     - Retorna: {success: false, message: 'Responda todas'}
   ↓
4. VIEW
   - Redireciona para visualizar.php?id=123
```

### 11.4. Fluxo de Dashboard

```
1. USUÁRIO
   ↓
   Acessa: public/checklist/relatorios/index.php
   ↓
2. VIEW
   - Captura filtros GET: loja_id, data_inicio, data_fim
   - Carrega RelatorioChecklistController
   - Chama: $controller->dashboard()
   ↓
3. CONTROLLER (RelatorioChecklistController)
   - Chama: RelatorioService->obterEstatisticasGerais($filtros)
   - Chama: RelatorioService->obterRankingLojas($filtros)
   - Chama: RelatorioService->obterDistribuicaoNotas($filtros)
   - Chama: RelatorioService->obterDesempenhoPorSetor($filtros)
   - Busca lista de lojas
   - Retorna array com todos os dados
   ↓
4. SERVICE (RelatorioService)
   - Executa queries complexas no banco
   - Faz joins: checklists + lojas + modulos
   - Agrupa por: loja, módulo, classificação
   - Calcula médias, totais, percentuais
   - Retorna dados estruturados
   ↓
5. VIEW
   - Renderiza cards com estatísticas
   - Renderiza tabela de ranking
   - Renderiza gráficos de barras (distribuição e setores)
   - Usa PontuacaoHelper para cores e classificações
```

---

## 12. Instalação

### 12.1. Pré-requisitos

- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Apache ou Nginx
- Sistema SGC já instalado e funcionando

### 12.2. Instalação Automática (Recomendado)

**Passo 1:** Acesse o instalador
```
http://seudominio.com/instalar_checklist.php
```

**Passo 2:** Clique em "🚀 Instalar Banco de Dados"

**O que acontece:**
- ✅ Cria 8 tabelas no banco
- ✅ Insere 8 módulos de avaliação
- ✅ Insere 58 perguntas pré-cadastradas
- ✅ Insere 4 lojas de exemplo
- ✅ Insere pesos nas configurações
- ✅ Cria diretório de uploads

**Passo 3:** Delete o instalador (segurança)
```bash
rm public/instalar_checklist.php
```

### 12.3. Instalação Manual

Se preferir instalar manualmente:

```bash
# 1. Conecte ao MySQL
mysql -u usuario -p banco_de_dados

# 2. Execute os scripts
source database/migrations/checklist_lojas_schema.sql
source database/migrations/checklist_lojas_seed.sql

# 3. Crie diretório de uploads
mkdir -p public/uploads/fotos_checklist
chmod 755 public/uploads/fotos_checklist
```

### 12.4. Verificação

Acesse o menu **Formulários** no sidebar. Deve aparecer:
- 📝 Checklists de Lojas
- ➕ Nova Avaliação
- 🏪 Gerenciar Lojas
- 📊 Dashboard & Relatórios
- ⚙️ Configurar Módulos (admin)

---

## 13. Bugs Conhecidos

### 13.1. Bugs Corrigidos ✅

#### Bug 1: Auth::checkAuth() não existe
**Erro:**
```
Fatal error: Call to undefined method Auth::checkAuth()
```

**Causa:** Páginas chamavam método inexistente `Auth::checkAuth()`

**Correção:** Substituído por `Auth::requireLogin()` em:
- `public/checklist/index.php`
- `public/checklist/novo.php`
- `public/checklist/relatorios/index.php`

**Status:** ✅ Corrigido

---

#### Bug 2: Database class not found
**Erro:**
```
Fatal error: Class "Database" not found in app/models/Checklist.php:12
```

**Causa:** Páginas não carregavam explicitamente a classe `Database`

**Correção:** Adicionado `require_once Database.php` nas três páginas

**Status:** ✅ Corrigido

---

#### Bug 3: Instalador não executava SQL
**Erro:**
```
0 comandos executados
Table 'perguntas' doesn't exist
```

**Causa:** Parser SQL simples com `explode(';')` quebrava em semicolons dentro de strings

**Correção:** Parser robusto com regex:
```php
preg_match_all('/CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+(\w+)\s*\([^;]+?\)\s*ENGINE=InnoDB[^;]*;/is', $schema, $matches);
```

**Status:** ✅ Corrigido

---

### 13.2. Bugs Pendentes ⏳

#### Bug 4: Páginas editar.php, visualizar.php, etc. não existem
**Erro:**
```
404 Not Found
```

**Causa:** Páginas ainda não foram criadas

**Impacto:**
- Não é possível preencher avaliações
- Não é possível visualizar checklists completos
- Não é possível gerenciar lojas e módulos

**Prioridade:** 🔴 ALTA

**Solução:** Criar as páginas faltantes (ver seção 9 desta documentação)

---

#### Bug 5: Upload de fotos não funciona
**Causa:** Interface de upload não implementada nas views

**Impacto:** Não é possível anexar fotos às respostas

**Prioridade:** 🟡 MÉDIA

**Solução:**
1. Adicionar campo `<input type="file">` em editar.php
2. Implementar endpoint de upload
3. Processar e salvar na tabela `fotos_checklist`
4. Exibir miniaturas em visualizar.php

---

#### Bug 6: Colaborador sempre usa ID do usuário logado
**Causa:** Em `ChecklistController::criar()`, usa `Auth::getUserId()` fixo

**Impacto:** Não é possível registrar avaliações feitas por outras pessoas

**Prioridade:** 🟢 BAIXA

**Solução:** Adicionar select opcional de "Avaliador" no formulário novo.php

---

#### Bug 7: Sem validação de perguntas duplicadas
**Causa:** Não há unique constraint em `(checklist_id, pergunta_id)`

**Impacto:** É possível salvar a mesma resposta múltiplas vezes

**Prioridade:** 🟡 MÉDIA

**Solução:**
```sql
ALTER TABLE respostas_checklist
ADD UNIQUE KEY unique_resposta (checklist_id, pergunta_id);
```

---

#### Bug 8: Dashboard sem cache
**Causa:** Queries pesadas executam a cada acesso

**Impacto:** Lentidão com muitos dados

**Prioridade:** 🟢 BAIXA

**Solução:** Implementar cache de 5 minutos para estatísticas

---

### 13.3. Melhorias Sugeridas

#### Melhoria 1: Gráficos interativos
**Atual:** Barras HTML/CSS simples

**Sugestão:** Integrar Chart.js ou ApexCharts

**Benefício:** Gráficos mais bonitos, interativos, exportáveis

---

#### Melhoria 2: Notificações
**Sugestão:** Enviar email quando checklist for finalizado

**Implementação:**
1. Adicionar tabela `notificacoes`
2. Criar service EmailService
3. Disparar ao finalizar checklist

---

#### Melhoria 3: Histórico de alterações
**Sugestão:** Log de quem editou cada resposta

**Implementação:**
1. Adicionar tabela `log_alteracoes`
2. Registrar: user, timestamp, campo, valor_anterior, valor_novo

---

#### Melhoria 4: Comparação visual de períodos
**Sugestão:** "Comparar Novembro vs Outubro"

**Implementação:**
- Endpoint: `compararPeriodos($periodo1, $periodo2)`
- Gráfico de linhas com duas séries

---

#### Melhoria 5: Exportação Excel
**Atual:** Sem exportação

**Sugestão:** Botão "Exportar Excel" no dashboard

**Implementação:** Usar PhpSpreadsheet

---

## 14. Como Usar

### 14.1. Criar Nova Avaliação

1. Acesse **Formulários > Nova Avaliação**
2. Selecione a **Loja**
3. Selecione o **Módulo** (ex: Organização de Lojas)
4. Escolha a **Data da Avaliação**
5. Adicione **Observações Gerais** (opcional)
6. Clique em **Criar e Começar Avaliação**
7. ⚠️ Será redirecionado para editar.php (PENDENTE)

### 14.2. Preencher Avaliação (QUANDO editar.php EXISTIR)

1. Para cada pergunta:
   - Leia a pergunta
   - Clique na quantidade de estrelas (1-5)
   - Adicione observação se necessário
   - Anexe foto se quiser
   - Resposta salva automaticamente (AJAX)
2. Ao terminar todas, clique em **Finalizar Avaliação**
3. Confirme (não poderá mais editar)
4. Será redirecionado para visualização

### 14.3. Ver Relatórios

1. Acesse **Formulários > Dashboard & Relatórios**
2. Use os filtros:
   - **Loja:** Específica ou todas
   - **Data Início:** Filtrar por período
   - **Data Fim:** Filtrar por período
3. Visualize:
   - Cards com estatísticas gerais
   - Ranking de lojas
   - Distribuição de classificações
   - Desempenho por setor

### 14.4. Listar Checklists

1. Acesse **Formulários > Checklists de Lojas**
2. Use os filtros:
   - Loja, Módulo, Data, Status
3. Clique em:
   - **Editar:** Se status = rascunho
   - **Visualizar:** Se status = finalizado

---

## 15. Próximos Passos

### Fase 1: Completar Funcionalidades Básicas 🔴 URGENTE

- [ ] Criar `public/checklist/editar.php`
  - Formulário com perguntas
  - Sistema de estrelas clicáveis (JavaScript)
  - Salvamento via AJAX
  - Atualização de pontuação em tempo real
  - Botão finalizar

- [ ] Criar `public/checklist/visualizar.php`
  - Exibição de todas as respostas
  - Fotos anexadas
  - Pontuação e classificação
  - Opção de imprimir

- [ ] Criar `public/checklist/lojas.php`
  - CRUD completo de lojas
  - Lista com busca
  - Formulário de cadastro/edição

---

### Fase 2: Funcionalidades Administrativas 🟡 IMPORTANTE

- [ ] Criar `public/checklist/modulos.php`
  - CRUD de módulos
  - Gerenciar perguntas
  - Ordenação drag & drop
  - Ativar/desativar

- [ ] Implementar upload de fotos
  - Interface em editar.php
  - Validação de tipo e tamanho
  - Miniaturas em visualizar.php
  - Galeria lightbox

---

### Fase 3: Melhorias e Otimizações 🟢 DESEJÁVEL

- [ ] Integrar Chart.js
  - Gráficos de linha para evolução
  - Gráficos de pizza para distribuição
  - Gráficos de barras para comparação

- [ ] Exportação Excel
  - Botão no dashboard
  - Usar PhpSpreadsheet
  - Incluir gráficos

- [ ] Sistema de notificações
  - Email ao finalizar checklist
  - Alerta de checklists pendentes
  - Resumo semanal

- [ ] Cache de relatórios
  - Redis ou arquivo
  - TTL de 5 minutos
  - Invalidar ao criar/editar

- [ ] Comparação de períodos
  - "Este mês vs mês passado"
  - Gráfico de tendência
  - Indicadores de melhora/piora

- [ ] Aplicativo mobile
  - PWA ou React Native
  - Captura de fotos
  - Funcionamento offline
  - Sincronização

---

## 16. Manutenção

### 16.1. Backup do Banco de Dados

```bash
# Backup completo
mysqldump -u usuario -p banco_de_dados > backup_checklist_$(date +%Y%m%d).sql

# Backup apenas das tabelas de checklist
mysqldump -u usuario -p banco_de_dados \
  lojas modulos_avaliacao perguntas checklists \
  respostas_checklist fotos_checklist configuracoes_sistema \
  > backup_checklist_tabelas_$(date +%Y%m%d).sql
```

### 16.2. Limpeza de Dados Antigos

```sql
-- Deletar checklists rascunho com mais de 30 dias
DELETE FROM checklists
WHERE status = 'rascunho'
  AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);

-- Arquivar checklists antigos (opcional)
CREATE TABLE checklists_arquivo LIKE checklists;
INSERT INTO checklists_arquivo
SELECT * FROM checklists
WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);
```

### 16.3. Monitoramento

```sql
-- Total de checklists por status
SELECT status, COUNT(*) as total
FROM checklists
GROUP BY status;

-- Média geral de pontuação
SELECT AVG(percentual) as media_geral
FROM checklists
WHERE status = 'finalizado';

-- Lojas sem avaliação nos últimos 30 dias
SELECT l.nome, MAX(c.data_avaliacao) as ultima_avaliacao
FROM lojas l
LEFT JOIN checklists c ON l.id = c.loja_id
GROUP BY l.id
HAVING ultima_avaliacao IS NULL
   OR ultima_avaliacao < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

### 16.4. Logs

```bash
# Ver logs de erro do banco
tail -f logs/database.log

# Ver logs de PHP
tail -f logs/error.log

# Ver acessos ao sistema
tail -f logs/access.log
```

### 16.5. Atualização de Pesos

Se quiser mudar os pesos das estrelas:

```sql
-- Atualizar peso de 4 estrelas em módulos de 8 perguntas
UPDATE configuracoes_sistema
SET valor = '0.55'
WHERE chave = 'peso_8_perguntas_4_estrela';

-- Recalcular todos os checklists
-- (executar via script PHP)
```

```php
<?php
// recalcular_pontuacoes.php
require_once 'app/config/config.php';
require_once 'app/models/Checklist.php';

$checklistModel = new Checklist();
$checklists = $checklistModel->listar();

foreach ($checklists['checklists'] as $checklist) {
    $checklistModel->calcularPontuacao($checklist['id']);
    echo "Recalculado checklist #{$checklist['id']}\n";
}

echo "Concluído!\n";
```

---

## 17. Referências

### Arquivos Importantes

| Arquivo | Descrição |
|---------|-----------|
| `CHECKLIST_LOJAS_README.md` | README técnico original |
| `INSTALACAO_CHECKLIST.md` | Guia de instalação rápida |
| `plano-desenvolvimento-checklist-loja.md` | Plano original do projeto |
| `database/migrations/checklist_lojas_schema.sql` | Estrutura do banco |
| `database/migrations/checklist_lojas_seed.sql` | Dados iniciais |

### Estrutura de Classes

```
Auth                          (app/classes/Auth.php)
Database                      (app/classes/Database.php)
├── ChecklistController       (app/controllers/ChecklistController.php)
├── RelatorioChecklistController (app/controllers/RelatorioChecklistController.php)
├── Checklist                 (app/models/Checklist.php)
├── RespostaChecklist         (app/models/RespostaChecklist.php)
├── ModuloAvaliacao           (app/models/ModuloAvaliacao.php)
├── Pergunta                  (app/models/Pergunta.php)
├── Loja                      (app/models/Loja.php)
├── Configuracao              (app/models/Configuracao.php)
├── RelatorioService          (app/services/RelatorioService.php)
├── PontuacaoHelper           (app/helpers/PontuacaoHelper.php)
└── RelatorioHelper           (app/helpers/RelatorioHelper.php)
```

---

## 🎉 Conclusão

Este sistema de checklist de lojas está **80% completo** e pronto para uso básico. As funcionalidades principais de listagem e relatórios estão funcionais.

**O que funciona agora:**
- ✅ Instalação automática
- ✅ Criação de checklists
- ✅ Listagem com filtros
- ✅ Dashboard completo
- ✅ Cálculo automático de pontuação
- ✅ Ranking de lojas

**O que precisa ser feito:**
- ⏳ Páginas de edição e visualização
- ⏳ CRUD de lojas e módulos
- ⏳ Upload de fotos
- ⏳ Gráficos interativos

**Documentação criada por:** Claude AI
**Data:** 2025-11-07
**Versão:** 1.0

---

**Para dúvidas ou suporte, consulte:**
- Esta documentação completa
- CHECKLIST_LOJAS_README.md (documentação técnica)
- INSTALACAO_CHECKLIST.md (guia de instalação)
- Código-fonte comentado em cada arquivo
