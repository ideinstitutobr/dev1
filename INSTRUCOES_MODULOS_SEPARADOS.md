# 📦 Módulos Separados por Tipo de Formulário

## ✅ Correções Implementadas

### 1. **Erro 500 Corrigido** ✅
- Adicionado filtro de tipo no `ChecklistController`
- Corrigido paths nos arquivos `index.php` de quinzenal e diário
- Agora as listas carregam corretamente

### 2. **Módulos Separados** ✅
- Cada tipo de formulário agora tem seus próprios módulos
- Cada tipo de formulário tem suas próprias perguntas
- Módulos quinzenais não aparecem em avaliações diárias e vice-versa

---

## 🔄 Migration Necessária

### Execute a Migration de Módulos:

**URL:**
```
http://dev1.ideinstituto.com.br/public/migration_adicionar_tipo_modulos.php
```

**O que essa migration faz:**
1. Adiciona coluna `tipo` na tabela `modulos_avaliacao`
2. Adiciona coluna `tipo` na tabela `perguntas`
3. Marca todos os módulos existentes como `'quinzenal_mensal'`
4. Marca todas as perguntas existentes como `'quinzenal_mensal'`
5. Cria índices para otimização

---

## 📊 Estrutura Atual

### Tabela: modulos_avaliacao
```
| Campo            | Tipo                                    | Default           |
|------------------|-----------------------------------------|-------------------|
| id               | int(11)                                 |                   |
| nome             | varchar(100)                            |                   |
| tipo             | enum('quinzenal_mensal','diario')       | quinzenal_mensal  | ← NOVO
| descricao        | text                                    |                   |
| total_perguntas  | int(11)                                 |                   |
| ordem            | int(11)                                 | 0                 |
| ativo            | tinyint(1)                              | 1                 |
```

### Tabela: perguntas
```
| Campo            | Tipo                                    | Default           |
|------------------|-----------------------------------------|-------------------|
| id               | int(11)                                 |                   |
| modulo_id        | int(11)                                 |                   |
| tipo             | enum('quinzenal_mensal','diario')       | quinzenal_mensal  | ← NOVO
| texto            | text                                    |                   |
| descricao        | text                                    |                   |
| ordem            | int(11)                                 | 0                 |
| ativo            | tinyint(1)                              | 1                 |
```

---

## 🎯 Como Funcionam os Módulos Agora

### Formulário Quinzenal/Mensal:
- Tipo: `'quinzenal_mensal'`
- Usa APENAS módulos marcados como `tipo = 'quinzenal_mensal'`
- Usa APENAS perguntas marcadas como `tipo = 'quinzenal_mensal'`

### Formulário Diário:
- Tipo: `'diario'`
- Usa APENAS módulos marcados como `tipo = 'diario'`
- Usa APENAS perguntas marcadas como `tipo = 'diario'`

---

## 📝 Como Criar Módulos para Cada Tipo

### Opção 1: Via Interface (Recomendado)

1. Acesse: **Formulários → Configurações → Módulos**
2. Ao criar/editar um módulo, selecione o **Tipo**:
   - `Quinzenal/Mensal` - Para avaliações periódicas
   - `Diário` - Para avaliações diárias

### Opção 2: Via SQL Direto

**Criar módulo para Quinzenal/Mensal:**
```sql
INSERT INTO modulos_avaliacao (nome, tipo, descricao, ordem, ativo)
VALUES ('Nome do Módulo', 'quinzenal_mensal', 'Descrição...', 1, 1);
```

**Criar módulo para Diário:**
```sql
INSERT INTO modulos_avaliacao (nome, tipo, descricao, ordem, ativo)
VALUES ('Nome do Módulo', 'diario', 'Descrição...', 1, 1);
```

**Criar perguntas para o módulo:**
```sql
INSERT INTO perguntas (modulo_id, tipo, texto, descricao, ordem, ativo)
VALUES (ID_DO_MODULO, 'diario', 'Texto da pergunta?', 'Descrição opcional', 1, 1);
```

---

## 🔧 Converter Módulos Existentes

### Duplicar módulos existentes para Diário:

**Passo 1: Duplicar módulos**
```sql
INSERT INTO modulos_avaliacao (nome, tipo, descricao, total_perguntas, peso_por_pergunta, ordem, ativo)
SELECT
    CONCAT(nome, ' (Diário)'),
    'diario',
    descricao,
    total_perguntas,
    peso_por_pergunta,
    ordem,
    ativo
FROM modulos_avaliacao
WHERE tipo = 'quinzenal_mensal';
```

**Passo 2: Duplicar perguntas**

Você precisará duplicar as perguntas manualmente ou criar um script PHP para isso.

Exemplo de script PHP:
```php
<?php
require_once 'app/config/config.php';
require_once 'app/classes/Database.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

// Buscar todos os módulos quinzenais
$modulosQuinzenais = $pdo->query("
    SELECT id, nome FROM modulos_avaliacao
    WHERE tipo = 'quinzenal_mensal'
")->fetchAll();

foreach ($modulosQuinzenais as $moduloQuinzenal) {
    // Criar módulo diário correspondente
    $stmt = $pdo->prepare("
        INSERT INTO modulos_avaliacao (nome, tipo, descricao, ordem, ativo)
        SELECT CONCAT(nome, ' (Diário)'), 'diario', descricao, ordem, ativo
        FROM modulos_avaliacao
        WHERE id = ?
    ");
    $stmt->execute([$moduloQuinzenal['id']]);
    $novoModuloId = $pdo->lastInsertId();

    // Duplicar perguntas
    $stmt = $pdo->prepare("
        INSERT INTO perguntas (modulo_id, tipo, texto, descricao, ordem, ativo)
        SELECT ?, 'diario', texto, descricao, ordem, ativo
        FROM perguntas
        WHERE modulo_id = ? AND tipo = 'quinzenal_mensal'
    ");
    $stmt->execute([$novoModuloId, $moduloQuinzenal['id']]);
}

echo "Módulos e perguntas duplicados com sucesso!";
?>
```

---

## 🧪 Teste Depois da Migration

### Teste 1: Criar Avaliação Quinzenal
1. Acesse: **Formulários → Quinzenais/Mensais → Nova Avaliação**
2. Preencha e crie
3. **Esperado:** Deve mostrar apenas módulos tipo `quinzenal_mensal`

### Teste 2: Criar Avaliação Diária
1. Acesse: **Formulários → Avaliações Diárias → Nova Avaliação**
2. Preencha e crie
3. **Esperado:** Deve mostrar apenas módulos tipo `diario`
4. **Se não houver módulos diários criados, aparecerá vazio!**

### Teste 3: Listar Avaliações
1. Acesse: **Formulários → Quinzenais/Mensais → Lista de Avaliações**
2. **Esperado:** Deve mostrar apenas avaliações quinzenais

3. Acesse: **Formulários → Avaliações Diárias → Lista de Avaliações**
4. **Esperado:** Deve mostrar apenas avaliações diárias

---

## ⚠️ Importante

### Você PRECISA criar módulos para formulários diários!

Atualmente, todos os módulos existentes são do tipo `quinzenal_mensal`. Para que as avaliações diárias funcionem, você precisa:

1. **Opção A:** Duplicar os módulos existentes e marcar como `diario`
2. **Opção B:** Criar novos módulos específicos para avaliações diárias
3. **Opção C:** Usar o script de duplicação acima

**Sem módulos do tipo `diario`, as avaliações diárias ficarão vazias!**

---

## 📋 Checklist de Implementação

- [x] Migration executada (checklists com tipo)
- [ ] Migration de módulos executada (modulos_avaliacao e perguntas com tipo)
- [ ] Módulos para `quinzenal_mensal` verificados
- [ ] Módulos para `diario` criados
- [ ] Perguntas para módulos diários criadas
- [ ] Teste de criação de avaliação quinzenal
- [ ] Teste de criação de avaliação diária
- [ ] Teste de listagem separada
- [ ] Teste de finalização

---

## 🚀 Próximos Passos

1. **Execute a migration de módulos**
2. **Crie ou duplique módulos para formulários diários**
3. **Teste ambos os tipos de formulário**
4. **Ajuste conforme necessário**

---

**Documento criado em:** <?php echo date('Y-m-d H:i:s'); ?>
**Status:** Pronto para migration
