# 🐛 BUGS CONHECIDOS E PENDÊNCIAS - SISTEMA DE CHECKLIST

**Data:** 2025-11-07
**Status:** Em desenvolvimento

---

## 📊 Resumo Executivo

| Status | Quantidade | Prioridade |
|--------|-----------|------------|
| ✅ Corrigidos | 3 | - |
| ⏳ Pendentes | 5 | 🔴 Alta: 1, 🟡 Média: 3, 🟢 Baixa: 1 |
| 💡 Melhorias | 5 | Futuras |

---

## ✅ BUGS CORRIGIDOS

### Bug 1: Auth::checkAuth() não existe ✅
**Erro:**
```
Fatal error: Call to undefined method Auth::checkAuth()
```

**Arquivos Afetados:**
- `public/checklist/index.php:12`
- `public/checklist/novo.php:11`
- `public/checklist/relatorios/index.php:11`

**Causa:**
O método `Auth::checkAuth()` não existe na classe Auth. O método correto é `Auth::requireLogin()`.

**Solução Aplicada:**
Substituído `Auth::checkAuth()` por `Auth::requireLogin()` nas três páginas.

**Commit:** `73a0128`

---

### Bug 2: Database class not found ✅
**Erro:**
```
Fatal error: Class "Database" not found in app/models/Checklist.php:12
```

**Arquivos Afetados:**
- `public/checklist/index.php`
- `public/checklist/novo.php`
- `public/checklist/relatorios/index.php`

**Causa:**
As páginas carregavam `database.php` (apenas constantes) mas não carregavam a classe `Database.php`.

**Solução Aplicada:**
Adicionado `require_once __DIR__ . '/../../app/classes/Database.php'` nas três páginas.

**Commit:** `afa6a5f`

---

### Bug 3: Instalador não executava SQL ✅
**Erro:**
```
[OK] Tabelas criadas com sucesso! (0 comandos executados)
Fatal error: Table 'perguntas' doesn't exist
```

**Arquivo Afetado:**
- `public/instalar_checklist.php`

**Causa:**
Parser SQL simples usando `explode(';', $sql)` quebrava quando encontrava ponto e vírgula dentro de strings ou comentários SQL.

**Solução Aplicada:**
Implementado parser robusto usando regex:
```php
preg_match_all('/CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+(\w+)\s*\([^;]+?\)\s*ENGINE=InnoDB[^;]*;/is', $schema, $matches);
```

**Resultado:**
Instalador agora cria com sucesso:
- 8 tabelas
- 8 módulos
- 58 perguntas
- 4 lojas de exemplo
- Configurações de peso

---

## ⏳ BUGS PENDENTES

### 🔴 Bug 4: Páginas principais não existem (CRÍTICO)

**Status:** ⏳ Não implementado

**Arquivos Faltando:**
1. `public/checklist/editar.php` - Preencher avaliação
2. `public/checklist/visualizar.php` - Ver checklist completo
3. `public/checklist/lojas.php` - CRUD de lojas
4. `public/checklist/modulos.php` - CRUD de módulos

**Impacto:**
- ❌ **CRÍTICO:** Não é possível preencher avaliações
- ❌ Não é possível visualizar checklists finalizados
- ❌ Não é possível gerenciar lojas
- ❌ Não é possível gerenciar módulos

**Prioridade:** 🔴 ALTA - Sistema 80% funcional sem essas páginas

**Como Corrigir:**

#### 4.1. Criar editar.php

**Template Básico:**

```php
<?php
// public/checklist/editar.php

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';

Auth::requireLogin();

require_once APP_PATH . 'models/Checklist.php';
require_once APP_PATH . 'models/Pergunta.php';
require_once APP_PATH . 'models/RespostaChecklist.php';

// Buscar checklist
$checklistId = $_GET['id'] ?? null;
if (!$checklistId) {
    header('Location: index.php');
    exit;
}

$checklistModel = new Checklist();
$checklist = $checklistModel->buscarPorId($checklistId);

if (!$checklist || $checklist['status'] != 'rascunho') {
    $_SESSION['error'] = 'Checklist não encontrado ou já finalizado';
    header('Location: index.php');
    exit;
}

// Buscar perguntas do módulo
$perguntaModel = new Pergunta();
$perguntas = $perguntaModel->listarPorModulo($checklist['modulo_id']);

// Buscar respostas já salvas
$respostaModel = new RespostaChecklist();
$respostas = $respostaModel->buscarPorChecklist($checklistId);

// Indexar respostas por pergunta_id
$respostasMap = [];
foreach ($respostas as $resposta) {
    $respostasMap[$resposta['pergunta_id']] = $resposta;
}

$pageTitle = 'Preencher Avaliação';
include APP_PATH . 'views/layouts/header.php';
include APP_PATH . 'views/layouts/sidebar.php';
?>

<style>
    /* CSS para estrelas, cards de perguntas, etc. */
    .main-content { margin-left: 260px; padding: 30px; }
    .pergunta-card {
        background: white;
        padding: 20px;
        margin-bottom: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }
    .estrelas {
        display: flex;
        gap: 10px;
        margin: 15px 0;
    }
    .estrela {
        font-size: 32px;
        cursor: pointer;
        opacity: 0.3;
        transition: opacity 0.2s;
    }
    .estrela.ativa {
        opacity: 1;
    }
    .observacao-input {
        width: 100%;
        padding: 10px;
        margin-top: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
    }
</style>

<div class="main-content" id="mainContent">
    <div class="page-header">
        <h1>📝 Preencher Avaliação</h1>
        <p>
            <strong>Loja:</strong> <?php echo htmlspecialchars($checklist['loja_nome']); ?> |
            <strong>Módulo:</strong> <?php echo htmlspecialchars($checklist['modulo_nome']); ?>
        </p>
    </div>

    <!-- Indicador de progresso -->
    <div class="progresso-card" style="background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
        <h3>Progresso: <span id="progresso">0/<?php echo count($perguntas); ?></span></h3>
        <div style="background: #f0f0f0; height: 20px; border-radius: 10px;">
            <div id="barra-progresso" style="width: 0%; height: 100%; background: #667eea; border-radius: 10px; transition: width 0.3s;"></div>
        </div>
        <p style="margin-top: 10px;">
            <strong>Pontuação:</strong>
            <span id="pontuacao-atual">0.00</span> / 5.00
            (<span id="percentual-atual">0</span>%)
        </p>
    </div>

    <!-- Perguntas -->
    <form id="form-checklist">
        <?php foreach ($perguntas as $index => $pergunta): ?>
            <?php
            $respostaSalva = $respostasMap[$pergunta['id']] ?? null;
            $estrelasSalvas = $respostaSalva['estrelas'] ?? 0;
            $observacaoSalva = $respostaSalva['observacao'] ?? '';
            ?>

            <div class="pergunta-card" data-pergunta-id="<?php echo $pergunta['id']; ?>">
                <h4><?php echo ($index + 1); ?>. <?php echo htmlspecialchars($pergunta['titulo']); ?></h4>

                <?php if ($pergunta['descricao']): ?>
                    <p class="descricao" style="color: #666; font-size: 14px;">
                        <?php echo htmlspecialchars($pergunta['descricao']); ?>
                    </p>
                <?php endif; ?>

                <!-- Sistema de estrelas -->
                <div class="estrelas" data-pergunta-id="<?php echo $pergunta['id']; ?>">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="estrela <?php echo ($i <= $estrelasSalvas) ? 'ativa' : ''; ?>"
                              data-valor="<?php echo $i; ?>">⭐</span>
                    <?php endfor; ?>
                </div>

                <!-- Observação -->
                <textarea class="observacao-input"
                          data-pergunta-id="<?php echo $pergunta['id']; ?>"
                          placeholder="Observações sobre esta pergunta (opcional)"><?php echo htmlspecialchars($observacaoSalva); ?></textarea>

                <!-- Indicador de salvamento -->
                <div class="status-salvo" style="margin-top: 10px; color: #28a745; display: none;">
                    ✓ Salvo
                </div>
            </div>
        <?php endforeach; ?>
    </form>

    <!-- Botões finais -->
    <div style="text-align: center; margin-top: 30px;">
        <button onclick="finalizar()" class="btn-finalizar"
                style="padding: 15px 40px; background: #28a745; color: white; border: none; border-radius: 5px; font-size: 18px; cursor: pointer;">
            ✅ Finalizar Avaliação
        </button>
    </div>
</div>

<script>
// Dados do checklist
const CHECKLIST_ID = <?php echo $checklistId; ?>;
const TOTAL_PERGUNTAS = <?php echo count($perguntas); ?>;

// Contador de respostas
let respostasPreenchidas = <?php echo count($respostas); ?>;

// Atualizar progresso inicial
atualizarProgresso();

// Event listener para estrelas
document.querySelectorAll('.estrelas').forEach(container => {
    const perguntaId = container.dataset.perguntaId;
    const estrelas = container.querySelectorAll('.estrela');

    estrelas.forEach(estrela => {
        estrela.addEventListener('click', function() {
            const valor = parseInt(this.dataset.valor);

            // Marcar estrelas
            estrelas.forEach((e, index) => {
                if (index < valor) {
                    e.classList.add('ativa');
                } else {
                    e.classList.remove('ativa');
                }
            });

            // Salvar resposta
            salvarResposta(perguntaId, valor);
        });
    });
});

// Event listener para observações (debounce)
let timeoutObservacao = {};
document.querySelectorAll('.observacao-input').forEach(textarea => {
    textarea.addEventListener('input', function() {
        const perguntaId = this.dataset.perguntaId;
        const observacao = this.value;

        clearTimeout(timeoutObservacao[perguntaId]);
        timeoutObservacao[perguntaId] = setTimeout(() => {
            salvarObservacao(perguntaId, observacao);
        }, 1000);
    });
});

// Salvar resposta via AJAX
function salvarResposta(perguntaId, estrelas) {
    const observacao = document.querySelector(`.observacao-input[data-pergunta-id="${perguntaId}"]`).value;

    fetch('salvar_resposta.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            checklist_id: CHECKLIST_ID,
            pergunta_id: perguntaId,
            estrelas: estrelas,
            observacao: observacao
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mostrar status de salvo
            const card = document.querySelector(`.pergunta-card[data-pergunta-id="${perguntaId}"]`);
            const statusSalvo = card.querySelector('.status-salvo');
            statusSalvo.style.display = 'block';
            setTimeout(() => {
                statusSalvo.style.display = 'none';
            }, 2000);

            // Atualizar progresso
            respostasPreenchidas = data.total_respostas;
            atualizarProgresso();

            // Atualizar pontuação
            document.getElementById('pontuacao-atual').textContent = data.pontuacao_total.toFixed(2);
            document.getElementById('percentual-atual').textContent = Math.round(data.percentual);
        }
    });
}

// Atualizar progresso
function atualizarProgresso() {
    const percentual = (respostasPreenchidas / TOTAL_PERGUNTAS) * 100;
    document.getElementById('progresso').textContent = `${respostasPreenchidas}/${TOTAL_PERGUNTAS}`;
    document.getElementById('barra-progresso').style.width = percentual + '%';
}

// Finalizar avaliação
function finalizar() {
    if (respostasPreenchidas < TOTAL_PERGUNTAS) {
        alert(`Você respondeu apenas ${respostasPreenchidas} de ${TOTAL_PERGUNTAS} perguntas.\nPor favor, responda todas antes de finalizar.`);
        return;
    }

    if (!confirm('Deseja finalizar esta avaliação?\n\nApós finalizar, não será mais possível editar as respostas.')) {
        return;
    }

    fetch('finalizar.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            checklist_id: CHECKLIST_ID
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Avaliação finalizada com sucesso!');
            window.location.href = 'visualizar.php?id=' + CHECKLIST_ID;
        } else {
            alert('Erro: ' + data.message);
        }
    });
}
</script>

<?php include APP_PATH . 'views/layouts/footer.php'; ?>
```

**Endpoint: salvar_resposta.php**

```php
<?php
// public/checklist/salvar_resposta.php

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';

Auth::requireLogin();

require_once APP_PATH . 'models/RespostaChecklist.php';
require_once APP_PATH . 'models/Checklist.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);

    $respostaModel = new RespostaChecklist();
    $resultado = $respostaModel->salvar([
        'checklist_id' => $input['checklist_id'],
        'pergunta_id' => $input['pergunta_id'],
        'estrelas' => $input['estrelas'],
        'observacao' => $input['observacao'] ?? ''
    ]);

    if ($resultado['success']) {
        // Buscar pontuação atualizada
        $checklistModel = new Checklist();
        $checklist = $checklistModel->buscarPorId($input['checklist_id']);

        // Contar respostas
        $totalRespostas = $respostaModel->contarPorChecklist($input['checklist_id']);

        echo json_encode([
            'success' => true,
            'pontuacao_total' => floatval($checklist['pontuacao_total']),
            'percentual' => floatval($checklist['percentual']),
            'total_respostas' => $totalRespostas
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => $resultado['message']]);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
```

**Endpoint: finalizar.php**

```php
<?php
// public/checklist/finalizar.php

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';

Auth::requireLogin();

require_once APP_PATH . 'models/Checklist.php';
require_once APP_PATH . 'controllers/ChecklistController.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);

    $controller = new ChecklistController();
    $resultado = $controller->finalizar($input['checklist_id']);

    echo json_encode($resultado);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
```

#### 4.2. Criar visualizar.php

**Template mais simples - apenas exibir dados.**

#### 4.3. Criar lojas.php

**CRUD básico com formulário e lista.**

#### 4.4. Criar modulos.php

**Gerenciar módulos e perguntas (admin apenas).**

---

### 🟡 Bug 5: Upload de fotos não implementado

**Status:** ⏳ Interface não implementada

**Impacto:** Não é possível anexar fotos às respostas

**Prioridade:** 🟡 MÉDIA

**Preparação:**
- ✅ Tabela `fotos_checklist` existe
- ✅ Diretório `public/uploads/fotos_checklist/` criado
- ❌ Interface HTML não implementada
- ❌ Endpoint de upload não criado

**Como Corrigir:**

1. Adicionar campo de upload em `editar.php`:
```html
<input type="file" accept="image/*" data-pergunta-id="<?php echo $pergunta['id']; ?>" onchange="uploadFoto(this)">
```

2. Criar endpoint `upload_foto.php`:
```php
<?php
// Validar imagem
// Salvar em uploads/fotos_checklist/
// Inserir registro em tabela fotos_checklist
// Retornar URL da foto
```

3. Exibir fotos em `visualizar.php`:
```html
<div class="fotos">
    <img src="/uploads/fotos_checklist/foto123.jpg">
</div>
```

---

### 🟡 Bug 6: Sem validação de respostas duplicadas

**Status:** ⏳ Não implementado

**Impacto:** É possível salvar a mesma resposta múltiplas vezes

**Prioridade:** 🟡 MÉDIA

**Como Corrigir:**

```sql
ALTER TABLE respostas_checklist
ADD UNIQUE KEY unique_resposta (checklist_id, pergunta_id);
```

Ou no código PHP:
```php
// Em RespostaChecklist::salvar()
// Verificar se já existe antes de inserir
// Se existir, fazer UPDATE ao invés de INSERT
```

---

### 🟡 Bug 7: Colaborador sempre usa ID do usuário logado

**Status:** ⏳ Hard-coded

**Impacto:** Não é possível registrar avaliações feitas por outras pessoas

**Prioridade:** 🟡 MÉDIA

**Como Corrigir:**

1. Adicionar select em `novo.php`:
```html
<select name="colaborador_id">
    <option value="<?php echo Auth::getUserId(); ?>">Eu mesmo</option>
    <!-- Listar outros colaboradores -->
</select>
```

2. Atualizar `ChecklistController::criar()`:
```php
$colaboradorId = $dados['colaborador_id'] ?? Auth::getUserId();
```

---

### 🟢 Bug 8: Dashboard sem cache

**Status:** ⏳ Sem otimização

**Impacto:** Queries pesadas executam a cada acesso, causando lentidão

**Prioridade:** 🟢 BAIXA (apenas com muitos dados)

**Como Corrigir:**

Implementar cache simples:

```php
// Em RelatorioService.php
public function obterEstatisticasGerais($filtros = []) {
    $cacheKey = 'estatisticas_' . md5(serialize($filtros));
    $cacheFile = TEMP_PATH . 'cache/' . $cacheKey . '.json';

    // Verificar cache (5 minutos)
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 300) {
        return json_decode(file_get_contents($cacheFile), true);
    }

    // Executar query
    $resultado = /* query pesada */;

    // Salvar cache
    file_put_contents($cacheFile, json_encode($resultado));

    return $resultado;
}
```

---

## 💡 MELHORIAS SUGERIDAS (Futuro)

### Melhoria 1: Gráficos interativos com Chart.js

**Status:** Não implementado

**Benefício:** Gráficos mais bonitos e interativos

**Como Implementar:**
```html
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<canvas id="grafico-ranking"></canvas>
<script>
new Chart(document.getElementById('grafico-ranking'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($labels); ?>,
        datasets: [{
            label: 'Média Geral',
            data: <?php echo json_encode($valores); ?>,
            backgroundColor: '#667eea'
        }]
    }
});
</script>
```

---

### Melhoria 2: Notificações por email

**Status:** Não implementado

**Benefício:** Avisar gestores sobre avaliações finalizadas

**Como Implementar:**
1. Criar `EmailService.php`
2. Enviar email ao finalizar checklist
3. Resumo semanal via cron

---

### Melhoria 3: Histórico de alterações

**Status:** Não implementado

**Benefício:** Auditoria de quem alterou cada resposta

**Como Implementar:**
```sql
CREATE TABLE log_alteracoes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT,
    tabela VARCHAR(50),
    registro_id INT,
    acao ENUM('insert', 'update', 'delete'),
    campo VARCHAR(100),
    valor_anterior TEXT,
    valor_novo TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

### Melhoria 4: Comparação de períodos

**Status:** Não implementado

**Benefício:** "Este mês vs mês passado"

**Como Implementar:**
```php
// RelatorioService::compararPeriodos($periodo1, $periodo2)
// Retorna: [
//     'periodo1' => ['media' => 85%, 'total' => 10],
//     'periodo2' => ['media' => 78%, 'total' => 12],
//     'variacao' => +7%
// ]
```

---

### Melhoria 5: Exportação Excel

**Status:** Não implementado

**Benefício:** Relatórios em formato Excel

**Como Implementar:**
```bash
composer require phpoffice/phpspreadsheet
```

```php
use PhpOffice\PhpSpreadsheet\Spreadsheet;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1', 'Loja');
$sheet->setCellValue('B1', 'Média');
// ... preencher dados

$writer = new Xlsx($spreadsheet);
$writer->save('relatorio.xlsx');
```

---

## 🎯 PRIORIDADES DE CORREÇÃO

### Urgente (Esta Semana) 🔴

1. Criar `editar.php` - SEM ISSO O SISTEMA NÃO FUNCIONA
2. Criar `visualizar.php` - Necessário para ver resultados
3. Criar `salvar_resposta.php` e `finalizar.php` (endpoints)

### Importante (Este Mês) 🟡

4. Criar `lojas.php` - CRUD de lojas
5. Implementar upload de fotos
6. Adicionar validação de duplicatas
7. Criar `modulos.php` - Gerenciar módulos (admin)

### Desejável (Próximos Meses) 🟢

8. Implementar cache de relatórios
9. Integrar Chart.js
10. Sistema de notificações
11. Exportação Excel
12. Histórico de alterações

---

## 📝 CHECKLIST DE IMPLEMENTAÇÃO

### Para `editar.php`
- [ ] Criar arquivo base
- [ ] Buscar checklist e perguntas
- [ ] Renderizar perguntas com estrelas
- [ ] Implementar JavaScript para estrelas
- [ ] Criar endpoint `salvar_resposta.php`
- [ ] Criar endpoint `finalizar.php`
- [ ] Adicionar indicador de progresso
- [ ] Atualizar pontuação em tempo real
- [ ] Validar antes de finalizar
- [ ] Testar fluxo completo

### Para `visualizar.php`
- [ ] Criar arquivo base
- [ ] Buscar checklist e respostas
- [ ] Exibir header com dados da loja
- [ ] Exibir pontuação e classificação
- [ ] Listar todas as respostas
- [ ] Exibir fotos (se houver)
- [ ] Botão de imprimir
- [ ] Testar exibição

### Para `lojas.php`
- [ ] Criar arquivo base
- [ ] Listar lojas em tabela
- [ ] Formulário de cadastro
- [ ] Validação de dados
- [ ] Editar loja
- [ ] Desativar loja (soft delete)
- [ ] Busca e filtros
- [ ] Testar CRUD completo

---

## 🆘 SUPORTE

**Se encontrar um bug novo:**
1. Anotar mensagem de erro completa
2. Identificar arquivo e linha
3. Verificar se está nesta lista
4. Adicionar aqui se for novo
5. Priorizar conforme impacto

**Arquivos de Log:**
- `logs/error.log` - Erros do PHP
- `logs/database.log` - Erros do banco
- `logs/access.log` - Acessos às páginas

**Comandos Úteis:**
```bash
# Ver últimos erros
tail -f logs/error.log

# Verificar tabelas
mysql -u usuario -p -e "SHOW TABLES LIKE 'checklist%'"

# Teste de conexão
php public/test_connection.php
```

---

**Documentação atualizada em:** 2025-11-07
**Próxima revisão:** Após implementar editar.php
