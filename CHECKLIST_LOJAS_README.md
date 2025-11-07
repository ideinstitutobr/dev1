# 📋 SISTEMA DE CHECKLIST DE LOJAS

## 🎯 Visão Geral

Sistema completo de avaliação de lojas através de checklists digitais com:
- Formulário digital para supervisão e avaliação
- Cálculo automático de pontuações por estrelas (1-5)
- Sistema de pesos diferenciados (8 perguntas ou 6 perguntas por módulo)
- Relatórios visuais e comparativos
- Dashboard com gráficos interativos
- Upload de fotos por pergunta
- 8 setores pré-configurados para avaliação

---

## 📊 Arquivos Criados

### 🗄️ **Banco de Dados**

#### Migrations:
- `database/migrations/checklist_lojas_schema.sql` - Schema completo com 8 tabelas
- `database/migrations/checklist_lojas_seed.sql` - Dados iniciais (8 módulos com perguntas)

#### Tabelas Criadas:
1. **lojas** - Cadastro de lojas/unidades
2. **cargos_checklist** - Cargos com permissões
3. **modulos_avaliacao** - Módulos/setores (8 pré-configurados)
4. **perguntas** - Perguntas de cada módulo
5. **checklists** - Registro de avaliações
6. **respostas_checklist** - Respostas por pergunta
7. **fotos_checklist** - Fotos anexadas
8. **configuracoes_sistema** - Pesos de pontuação

### 🔧 **Models** (`app/models/`)
- `Checklist.php` - Gerencia checklists
- `RespostaChecklist.php` - Gerencia respostas
- `ModuloAvaliacao.php` - Gerencia módulos/setores
- `Pergunta.php` - Gerencia perguntas
- `Loja.php` - Gerencia lojas
- `Configuracao.php` - Gerencia configurações

### 🎮 **Controllers** (`app/controllers/`)
- `ChecklistController.php` - CRUD de checklists
- `RelatorioChecklistController.php` - Relatórios e dashboards

### 🛠️ **Helpers** (`app/helpers/`)
- `PontuacaoHelper.php` - Cálculos de pontuação
- `RelatorioHelper.php` - Funções auxiliares para relatórios

### ⚙️ **Services** (`app/services/`)
- `RelatorioService.php` - Lógica de negócio para relatórios

---

## 🏗️ Sistema de Pontuação

### **Módulos de 8 Perguntas** (Pontuação máxima: 5 pontos)
- ⭐ 1 estrela = 0,125 pontos
- ⭐⭐ 2 estrelas = 0,25 pontos
- ⭐⭐⭐ 3 estrelas = 0,375 pontos
- ⭐⭐⭐⭐ 4 estrelas = 0,5 pontos
- ⭐⭐⭐⭐⭐ 5 estrelas = 0,625 pontos

### **Módulos de 6 Perguntas** (Pontuação máxima: 5 pontos)
- ⭐ 1 estrela = 0,167 pontos
- ⭐⭐ 2 estrelas = 0,333 pontos
- ⭐⭐⭐ 3 estrelas = 0,500 pontos
- ⭐⭐⭐⭐ 4 estrelas = 0,667 pontos
- ⭐⭐⭐⭐⭐ 5 estrelas = 0,833 pontos

### **Classificação por Percentual**
- **≥ 80%** - ⭐⭐⭐⭐⭐ Excelente (Verde)
- **≥ 60%** - ⭐⭐⭐⭐ Bom (Azul)
- **≥ 40%** - ⭐⭐⭐ Regular (Amarelo)
- **≥ 20%** - ⭐⭐ Ruim (Laranja)
- **< 20%** - ⭐ Muito Ruim (Vermelho)

### **Meta de Aprovação**
- Meta padrão: **4 estrelas de 5** (80% de aprovação)
- Configurável na tabela `configuracoes_sistema`

---

## 📝 Módulos Pré-Configurados

### 1. **Organização de Lojas** (8 perguntas)
Avaliação geral da loja: corredores, sinalização, prateleiras, iluminação, piso, vitrines, produtos e fachada.

### 2. **Caixas** (6 perguntas)
Limpeza, uniformização, atendimento, equipamentos, materiais e sinalização.

### 3. **Setor Ovos** (8 perguntas)
Temperatura, embalagens, validades, higiene, organização, FIFO, precificação e estoque.

### 4. **Gôndolas e Ilhas** (8 perguntas)
Limpeza, precificação, validades, reposição, ilhas promocionais, pontas de gôndola e layout.

### 5. **Balcão de Frios** (8 perguntas)
Limpeza, temperatura, acondicionamento, validades, EPIs, precificação, variedade e atendimento.

### 6. **Câmara Fria** (8 perguntas)
Limpeza, temperatura, identificação, FIFO, prateleiras, vedação, produtos e controle.

### 7. **Estoque** (8 perguntas)
Organização, armazenamento, controle, validades, pragas, separação, estrutura e acesso.

### 8. **Áreas Comuns de Colaboradores** (6 perguntas)
Vestiários, refeitório, água potável, armários, área de descanso e normas de segurança.

---

## 🚀 Instalação e Configuração

### **Passo 1: Executar o Schema**

```bash
mysql -u seu_usuario -p nome_do_banco < database/migrations/checklist_lojas_schema.sql
```

Ou execute diretamente no phpMyAdmin/MySQL Workbench.

### **Passo 2: Executar o Seed**

```bash
mysql -u seu_usuario -p nome_do_banco < database/migrations/checklist_lojas_seed.sql
```

Isso criará:
- 8 módulos de avaliação
- Todas as perguntas (58 perguntas no total)
- 4 lojas de exemplo
- 4 cargos padrão
- Configurações de pesos

### **Passo 3: Criar Diretório de Uploads**

```bash
mkdir -p public/uploads/fotos_checklist
chmod 755 public/uploads/fotos_checklist
```

### **Passo 4: Incluir Arquivos no Autoload**

Adicione no seu arquivo de inicialização (ex: `index.php` ou bootstrap):

```php
// Helpers
require_once __DIR__ . '/app/helpers/PontuacaoHelper.php';
require_once __DIR__ . '/app/helpers/RelatorioHelper.php';

// Services
require_once __DIR__ . '/app/services/RelatorioService.php';

// Models
require_once __DIR__ . '/app/models/Checklist.php';
require_once __DIR__ . '/app/models/RespostaChecklist.php';
require_once __DIR__ . '/app/models/ModuloAvaliacao.php';
require_once __DIR__ . '/app/models/Pergunta.php';
require_once __DIR__ . '/app/models/Loja.php';
require_once __DIR__ . '/app/models/Configuracao.php';

// Controllers
require_once __DIR__ . '/app/controllers/ChecklistController.php';
require_once __DIR__ . '/app/controllers/RelatorioChecklistController.php';
```

---

## 🎯 Como Usar os Controllers

### **ChecklistController - Operações Básicas**

#### **Listar Checklists**
```php
$controller = new ChecklistController();
$dados = $controller->listar();

// Retorna:
// - checklists: Array de checklists
// - paginacao: Informações de paginação
// - estatisticas: Total, média, aprovados, etc.
// - filtros: Filtros aplicados
// - lojas: Lista de lojas
// - modulos: Lista de módulos
```

#### **Criar Novo Checklist**
```php
// Formulário
$controller = new ChecklistController();
$dados = $controller->exibirFormularioNovo();

// POST - Criar
$_POST['loja_id'] = 1;
$_POST['modulo_id'] = 1;
$_POST['data_avaliacao'] = '2025-11-07';
$resultado = $controller->criar();
```

#### **Editar Checklist**
```php
$controller = new ChecklistController();
$dados = $controller->exibirFormularioEditar($checklistId);

// Retorna:
// - checklist: Dados do checklist
// - perguntas: Lista de perguntas do módulo
// - respostas: Respostas já cadastradas (indexadas por pergunta_id)
```

#### **Salvar Resposta (AJAX)**
```php
$_POST['checklist_id'] = 1;
$_POST['pergunta_id'] = 1;
$_POST['estrelas'] = 5;
$_POST['observacao'] = 'Excelente!';

$controller = new ChecklistController();
$controller->salvarResposta(); // Retorna JSON
```

#### **Finalizar Checklist**
```php
$controller = new ChecklistController();
$resultado = $controller->finalizar($checklistId);

// Valida se todas perguntas foram respondidas
// Calcula pontuação final
// Muda status para 'finalizado'
```

#### **Visualizar Checklist**
```php
$controller = new ChecklistController();
$dados = $controller->visualizar($checklistId);

// Retorna:
// - checklist: Dados completos
// - respostas: Todas as respostas
// - classificacao: Array com texto, classe, icone e cor
```

### **RelatorioChecklistController - Relatórios**

#### **Dashboard Principal**
```php
$controller = new RelatorioChecklistController();
$dados = $controller->dashboard();

// Retorna:
// - estatisticas_gerais: Total, média, aprovados, taxa
// - ranking_lojas: Ranking ordenado por performance
// - evolucao_temporal: Dados para gráfico de linha
// - distribuicao_notas: Dados para gráfico de pizza
// - desempenho_setores: Média por módulo
```

#### **Relatório por Setor**
```php
$controller = new RelatorioChecklistController();
$dados = $controller->porSetor($moduloId);

// Retorna análise detalhada de um setor específico
// - modulo: Dados do módulo
// - analise_perguntas: Média de estrelas por pergunta
// - evolucao: Evolução temporal do setor
```

#### **Comparativo entre Lojas**
```php
$_GET['lojas'] = [1, 2, 3];
$controller = new RelatorioChecklistController();
$dados = $controller->comparativo();
```

#### **Exportar CSV**
```php
$controller = new RelatorioChecklistController();
$controller->exportarCSV(); // Download automático
```

#### **API JSON para Gráficos**
```php
// URL: /api/relatorio/grafico?tipo=evolucao&loja_id=1
$_GET['tipo'] = 'evolucao'; // ou 'distribuicao', 'ranking', 'setores'
$controller = new RelatorioChecklistController();
$controller->dadosGrafico(); // Retorna JSON
```

---

## 🔧 Uso dos Helpers

### **PontuacaoHelper**

```php
// Obter peso de pontuação
$peso = PontuacaoHelper::obterPeso(5, 8); // 0.625

// Converter pontuação para estrelas
$estrelas = PontuacaoHelper::pontuacaoParaEstrelas(4.5, 5); // 4.5

// Obter classificação
$classificacao = PontuacaoHelper::obterClassificacao(85);
// Retorna: ['texto' => 'Excelente', 'classe' => 'success', 'icone' => '⭐⭐⭐⭐⭐', 'cor' => '#28a745']

// Verificar se atingiu meta
$atingiu = PontuacaoHelper::atingiuMeta(82, 80); // true

// Formatar percentual
$formatado = PontuacaoHelper::formatarPercentual(85.456); // "85,5%"

// Gerar HTML de estrelas (requer Font Awesome)
$html = PontuacaoHelper::gerarEstrelasHtml(4); // <i class="fas fa-star"></i> x4 + <i class="far fa-star"></i> x1

// Calcular média
$respostas = [['estrelas' => 5], ['estrelas' => 4], ['estrelas' => 5]];
$media = PontuacaoHelper::calcularMediaEstrelas($respostas); // 4.7
```

### **RelatorioHelper**

```php
// Formatar datas
$data = RelatorioHelper::formatarData('2025-11-07'); // "07/11/2025"
$periodo = RelatorioHelper::formatarPeriodo('2025-11-01', '2025-11-07'); // "01/11/2025 a 07/11/2025"

// Preparar dados para gráficos
$dadosLinha = RelatorioHelper::prepararDadosGraficoLinha($dados, 'data', 'valor');
$dadosPizza = RelatorioHelper::prepararDadosGraficoPizza($dados, 'categoria', 'total');
$dadosBarras = RelatorioHelper::prepararDadosGraficoBarras($dados, 'nome', 'pontuacao');

// Calcular variação
$variacao = RelatorioHelper::calcularVariacao(85, 78); // 8.97%
$formatado = RelatorioHelper::formatarVariacao(8.97);
// Retorna: ['valor' => '+9.0%', 'icone' => '↑', 'classe' => 'text-success']

// Agrupar por período
$agrupados = RelatorioHelper::agruparPorPeriodo($dados, 'data_avaliacao', 'mes');

// Estatísticas
$valores = [85, 90, 78, 92, 88];
$stats = RelatorioHelper::calcularEstatisticas($valores);
// Retorna: total, media, mediana, minimo, maximo, desvio_padrao

// Gerar resumo
$resumo = RelatorioHelper::gerarResumo([
    'total_checklists' => 50,
    'media_percentual' => 85.5,
    'taxa_aprovacao' => 92
]); // "50 avaliações realizadas, média de 85.5%, 92% de aprovação"
```

---

## 💡 Exemplos de Uso Completo

### **Exemplo 1: Criar e Preencher Checklist**

```php
// 1. Criar checklist
$controller = new ChecklistController();

$_POST['loja_id'] = 1;
$_POST['modulo_id'] = 1; // Organização de Lojas
$_POST['data_avaliacao'] = date('Y-m-d');

$resultado = $controller->criar();
$checklistId = $resultado['checklist_id'];

// 2. Responder perguntas
$perguntas = [1, 2, 3, 4, 5, 6, 7, 8]; // IDs das perguntas

foreach ($perguntas as $perguntaId) {
    $_POST['checklist_id'] = $checklistId;
    $_POST['pergunta_id'] = $perguntaId;
    $_POST['estrelas'] = rand(3, 5); // Avaliação aleatória
    $_POST['observacao'] = 'Observação da pergunta ' . $perguntaId;

    $controller->salvarResposta();
}

// 3. Finalizar
$controller->finalizar($checklistId);

// 4. Visualizar
$dados = $controller->visualizar($checklistId);
echo "Percentual: " . $dados['checklist']['percentual'] . "%\n";
echo "Classificação: " . $dados['classificacao']['texto'] . "\n";
```

### **Exemplo 2: Gerar Relatório Completo**

```php
$relatorioController = new RelatorioChecklistController();

// Dashboard geral
$_GET['data_inicio'] = '2025-11-01';
$_GET['data_fim'] = '2025-11-07';
$_GET['loja_id'] = 1;

$dashboard = $relatorioController->dashboard();

echo "📊 Estatísticas Gerais:\n";
echo "Total de avaliações: " . $dashboard['estatisticas_gerais']['total_checklists'] . "\n";
echo "Média geral: " . round($dashboard['estatisticas_gerais']['media_percentual'], 1) . "%\n";
echo "Taxa de aprovação: " . $dashboard['estatisticas_gerais']['taxa_aprovacao'] . "%\n\n";

echo "🏆 Ranking de Lojas:\n";
foreach ($dashboard['ranking_lojas'] as $index => $loja) {
    echo ($index + 1) . "º - " . $loja['nome'] . " - " . round($loja['media_percentual'], 1) . "%\n";
}

echo "\n📈 Evolução nos últimos 7 dias:\n";
foreach ($dashboard['evolucao_temporal'] as $dia) {
    echo RelatorioHelper::formatarData($dia['data']) . " - " . round($dia['media_percentual'], 1) . "%\n";
}
```

### **Exemplo 3: Análise por Setor**

```php
$relatorioController = new RelatorioChecklistController();

// Analisar setor de Caixas (módulo_id = 2)
$_GET['modulo_id'] = 2;
$_GET['loja_id'] = 1;
$analise = $relatorioController->porSetor(2);

echo "📝 Análise do Setor: " . $analise['modulo']['nome'] . "\n\n";

echo "Perguntas com pior desempenho:\n";
$perguntas = $analise['analise_perguntas'];
usort($perguntas, function($a, $b) {
    return $a['media_estrelas'] <=> $b['media_estrelas'];
});

foreach (array_slice($perguntas, 0, 3) as $pergunta) {
    echo "- " . $pergunta['pergunta'] . "\n";
    echo "  Média: " . round($pergunta['media_estrelas'], 1) . " estrelas\n";
    echo "  Distribuição: 5★(" . $pergunta['total_5_estrelas'] . ") ";
    echo "4★(" . $pergunta['total_4_estrelas'] . ") ";
    echo "3★(" . $pergunta['total_3_estrelas'] . ")\n\n";
}
```

---

## 🔐 Segurança e Boas Práticas

### **1. Validação de Permissões**

Adicione verificação de permissões nos controllers:

```php
// No início de cada método do controller
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

// Verificar se usuário tem permissão para editar
$cargo = $_SESSION['cargo'] ?? null;
if (!in_array($cargo, ['supervisor', 'gerente', 'administrador'])) {
    die('Acesso negado');
}
```

### **2. CSRF Protection**

Use tokens CSRF nos formulários:

```php
// Gerar token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Validar no controller
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('Token inválido');
}
```

### **3. Sanitização de Dados**

```php
// Sanitizar inputs
$lojaId = filter_var($_POST['loja_id'], FILTER_VALIDATE_INT);
$estrelas = filter_var($_POST['estrelas'], FILTER_VALIDATE_INT);
$observacao = htmlspecialchars($_POST['observacao'], ENT_QUOTES, 'UTF-8');
```

---

## 📱 Próximos Passos (Views e Frontend)

Para completar o sistema, você precisa criar as views em `app/views/checklist/`:

1. **index.php** - Lista de checklists com filtros
2. **novo.php** - Formulário de criação
3. **editar.php** - Formulário de avaliação com estrelas
4. **visualizar.php** - Exibição do checklist finalizado

E em `app/views/relatorio/`:

1. **dashboard.php** - Dashboard com gráficos (usar Chart.js)
2. **setor.php** - Análise detalhada por setor
3. **comparativo.php** - Comparação entre lojas

### **Bibliotecas Recomendadas:**
- **Chart.js** - Para gráficos interativos
- **Font Awesome** - Para ícones de estrelas
- **Bootstrap** - Para layout responsivo
- **jQuery** - Para AJAX e interatividade

---

## 🎨 Integração com Rotas

Adicione as rotas no seu sistema de roteamento:

```php
// Exemplo de rotas
$routes = [
    // Checklists
    'GET /checklist' => 'ChecklistController@listar',
    'GET /checklist/novo' => 'ChecklistController@exibirFormularioNovo',
    'POST /checklist/criar' => 'ChecklistController@criar',
    'GET /checklist/editar/:id' => 'ChecklistController@exibirFormularioEditar',
    'POST /checklist/salvar-resposta' => 'ChecklistController@salvarResposta',
    'POST /checklist/finalizar/:id' => 'ChecklistController@finalizar',
    'GET /checklist/visualizar/:id' => 'ChecklistController@visualizar',
    'DELETE /checklist/:id' => 'ChecklistController@deletar',

    // Relatórios
    'GET /relatorio/dashboard' => 'RelatorioChecklistController@dashboard',
    'GET /relatorio/setor/:id' => 'RelatorioChecklistController@porSetor',
    'GET /relatorio/comparativo' => 'RelatorioChecklistController@comparativo',
    'GET /relatorio/exportar-csv' => 'RelatorioChecklistController@exportarCSV',
    'GET /api/relatorio/grafico' => 'RelatorioChecklistController@dadosGrafico',
];
```

---

## 📊 Estrutura do Banco de Dados

### **Resumo das Tabelas:**

```
lojas (8 campos)
├── id, nome, codigo, endereco, cidade, estado, ativo, created_at, updated_at

cargos_checklist (3 campos)
├── id, nome, nivel_acesso, created_at

modulos_avaliacao (7 campos)
├── id, nome, descricao, total_perguntas, peso_por_pergunta, ordem, ativo, created_at

perguntas (8 campos)
├── id, modulo_id, texto, descricao, ordem, obrigatoria, permite_foto, ativo, created_at

checklists (11 campos)
├── id, loja_id, colaborador_id, data_avaliacao, modulo_id, pontuacao_total
├── pontuacao_maxima, percentual, atingiu_meta, observacoes_gerais, status
├── created_at, updated_at

respostas_checklist (6 campos)
├── id, checklist_id, pergunta_id, estrelas, pontuacao, observacao, created_at

fotos_checklist (4 campos)
├── id, resposta_id, caminho, legenda, created_at

configuracoes_sistema (5 campos)
├── id, chave, valor, descricao, tipo, updated_at
```

---

## 🐛 Troubleshooting

### **Erro: Tabela já existe**
```sql
-- Remover tabelas antes de recriar
DROP TABLE IF EXISTS fotos_checklist;
DROP TABLE IF EXISTS respostas_checklist;
DROP TABLE IF EXISTS checklists;
DROP TABLE IF EXISTS perguntas;
DROP TABLE IF EXISTS modulos_avaliacao;
DROP TABLE IF EXISTS cargos_checklist;
DROP TABLE IF EXISTS configuracoes_sistema;
DROP TABLE IF EXISTS lojas;
```

### **Erro: Permissão negada no upload**
```bash
chmod 755 public/uploads/fotos_checklist
chown www-data:www-data public/uploads/fotos_checklist
```

### **Erro: Class not found**
Verifique se todos os arquivos estão sendo incluídos no autoload ou bootstrap.

---

## ✅ Checklist de Implementação

- [x] Schema do banco de dados criado
- [x] Seeds com 8 módulos e 58 perguntas
- [x] 6 Models implementados
- [x] 2 Helpers implementados
- [x] 1 Service implementado
- [x] 2 Controllers implementados
- [ ] Views do checklist (a criar)
- [ ] Views de relatórios (a criar)
- [ ] JavaScript para interatividade (a criar)
- [ ] CSS personalizado (a criar)
- [ ] Integração com sistema de rotas
- [ ] Testes de funcionalidade

---

## 📞 Suporte

Sistema desenvolvido seguindo o plano: `plano-desenvolvimento-checklist-loja.md`

**Recursos Implementados:**
✅ Backend completo (Models, Controllers, Services, Helpers)
✅ Sistema de pontuação por estrelas
✅ Cálculo automático de percentuais
✅ 8 módulos pré-configurados
✅ Sistema de relatórios e dashboard
✅ Upload de fotos
✅ Exportação CSV

**Pendente:**
- Views (HTML/CSS/JS)
- Integração com sistema de autenticação
- Testes automatizados
- API REST completa

---

**Versão:** 1.0
**Data:** 2025-11-07
**Desenvolvido por:** IDE Digital - Claude AI
