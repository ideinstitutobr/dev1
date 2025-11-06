# 📚 Como Usar o Sistema de Setores

## 📋 Índice
1. [Visão Geral](#visão-geral)
2. [Fluxo de Trabalho](#fluxo-de-trabalho)
3. [Passo a Passo](#passo-a-passo)
4. [Resolução de Problemas](#resolução-de-problemas)
5. [Perguntas Frequentes](#perguntas-frequentes)

---

## 🎯 Visão Geral

O Sistema de Setores funciona em **3 camadas**:

```
┌─────────────────────────────────────────────────┐
│  1. SETORES GLOBAIS (Catálogo)                 │
│  └─ Vendas, Caixa, Estoque, Administrativo...  │
└─────────────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────────────┐
│  2. SETORES POR UNIDADE (Ativação seletiva)    │
│  └─ Unidade A: Vendas + Caixa                   │
│  └─ Unidade B: Vendas + Estoque + Admin         │
└─────────────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────────────┐
│  3. COLABORADORES VINCULADOS AOS SETORES        │
│  └─ João → Unidade A → Setor Vendas             │
│  └─ Maria → Unidade B → Setor Caixa             │
└─────────────────────────────────────────────────┘
```

---

## 🔄 Fluxo de Trabalho

### Ordem Recomendada:

1. **Criar Setores Globais** (uma vez)
   - Exemplo: Vendas, Caixa, Estoque, Administrativo, etc.

2. **Cadastrar Unidades** (se ainda não tiver)
   - Exemplo: Matriz, Filial São Paulo, Filial Rio, etc.

3. **Ativar Setores nas Unidades**
   - Escolher quais setores existem em cada unidade

4. **Definir Responsáveis** (opcional)
   - Atribuir um colaborador como responsável de cada setor

5. **Vincular Colaboradores aos Setores**
   - Alocar cada colaborador a um setor específico da unidade

---

## 📝 Passo a Passo

### 1️⃣ Criar Setores Globais

#### Acesso:
```
Dashboard → Unidades → Setores Globais → Novo Setor
```

#### URL Direta:
```
/public/unidades/setores_globais/cadastrar.php
```

#### Como fazer:
1. Clique em **"Novo Setor"**
2. Preencha:
   - **Nome**: Nome do setor (ex: "Vendas")
   - **Descrição** (opcional): Detalhes sobre o setor
3. Clique em **"Criar Setor"**

#### Exemplos de Setores Comuns:
- ✅ Vendas
- ✅ Caixa
- ✅ Estoque
- ✅ Administrativo
- ✅ Financeiro
- ✅ Recursos Humanos
- ✅ TI (Tecnologia da Informação)
- ✅ Marketing
- ✅ Atendimento ao Cliente
- ✅ Logística
- ✅ Compras
- ✅ Comercial
- ✅ Operações

> **💡 Dica**: O sistema já vem com 12 setores pré-cadastrados após executar as migrations.

---

### 2️⃣ Ativar Setores em uma Unidade

#### Acesso:
```
Dashboard → Unidades → [Selecionar Unidade] → Aba "Setores" → Adicionar Setor
```

#### URL Direta:
```
/public/unidades/setores/gerenciar.php?unidade_id=X
```
(Substitua X pelo ID da unidade)

#### Como fazer:
1. Acesse a página de **Gerenciar Setores** da unidade
2. Clique em **"Adicionar Setor"**
3. No modal que abrir:
   - Selecione o setor no dropdown
   - Adicione uma descrição específica (opcional)
4. Clique em **"Adicionar"**

#### O que acontece:
- O setor ficará disponível para vincular colaboradores
- Você poderá definir um responsável para o setor
- O setor aparecerá nos formulários de cadastro de colaboradores

---

### 3️⃣ Definir Responsável de um Setor

#### Acesso:
```
[Na página de Gerenciar Setores da unidade]
→ Clique no botão "👤 Responsável" do setor
```

#### Como fazer:
1. Clique em **"👤 Responsável"** no card do setor
2. Selecione um colaborador no dropdown
   - Somente colaboradores já vinculados à unidade aparecem
3. Clique em **"Salvar"**

> **⚠️ Importante**: O colaborador precisa estar vinculado à unidade primeiro!

---

### 4️⃣ Vincular Colaborador a um Setor

#### Acesso:
```
Dashboard → Unidades → [Selecionar Unidade] → Colaboradores → Vincular Colaborador
```

#### URL Direta:
```
/public/unidades/colaboradores/vincular.php?unidade_id=X
```

#### Como fazer:
1. Selecione a **Unidade**
2. Busque o **Colaborador** (autocomplete)
3. Selecione o **Setor** (dropdown com setores ativos da unidade)
4. Preencha:
   - **Cargo Específico** (opcional)
   - **Data de Vinculação**
   - Marque **"Vínculo Principal"** se for a unidade principal do colaborador
5. Clique em **"Vincular"**

#### Resultado:
- Colaborador ficará vinculado ao setor
- Se marcado como principal, os dados serão atualizados na tabela `colaboradores`
- Histórico de vinculação será mantido

---

### 5️⃣ Cadastrar Novo Colaborador com Setor

#### Acesso:
```
Dashboard → Colaboradores → Novo Colaborador
```

#### Como funciona:
1. Preencha os dados do colaborador normalmente
2. Ao selecionar **"Unidade Principal"**, o sistema carrega automaticamente os setores disponíveis
3. Selecione o **"Setor"** no dropdown
4. Salve o colaborador

> **✨ Automação**: O sistema carrega os setores dinamicamente via AJAX quando você seleciona a unidade!

---

## 🔧 Resolução de Problemas

### ❌ Não consigo criar setores

**Possíveis causas:**

1. **As migrations não foram executadas**
   - **Solução**: Execute o script de migrations:
     ```bash
     php /home/user/dev1/database/migrations/executar_migrations_unidades.php
     ```

2. **Tabela `unidade_setores` não existe**
   - **Solução**: Execute a migration específica:
     ```bash
     mysql -u root -p sgc_db < /home/user/dev1/database/migrations/003_create_unidade_setores.sql
     ```

3. **Nenhum setor global cadastrado**
   - **Solução**: Execute a migration de população:
     ```bash
     mysql -u root -p sgc_db < /home/user/dev1/database/migrations/008_populate_setores_iniciais.sql
     ```

### 🔍 Como verificar se está tudo funcionando?

**Use o script de verificação:**
```
URL: /public/verificar_setores.php
```

Este script irá:
- ✅ Verificar se as tabelas existem
- ✅ Verificar se há setores globais cadastrados
- ✅ Verificar se há unidades cadastradas
- ✅ Listar todos os arquivos necessários
- ✅ Mostrar soluções para problemas encontrados

---

### ⚠️ Não consigo adicionar setor em uma unidade

**Verificações:**

1. **Há setores globais cadastrados?**
   - Vá em: `/public/unidades/setores_globais/listar.php`
   - Se não houver, crie pelo menos um

2. **A unidade existe?**
   - Verifique se a unidade está ativa

3. **Você tem permissão de admin?**
   - Somente administradores podem gerenciar setores

---

### ⚠️ Não consigo vincular colaborador a um setor

**Verificações:**

1. **O setor está ativo na unidade?**
   - Vá em: Gerenciar Setores da Unidade
   - Verifique se o setor está listado como "Ativo"

2. **O colaborador existe?**
   - Verifique se o colaborador está cadastrado e ativo

3. **A unidade está ativa?**
   - Verifique o status da unidade

---

### ⚠️ Não consigo inativar um setor

**Motivo:**
- Existem colaboradores vinculados a esse setor

**Solução:**
1. Remova ou transfira os colaboradores do setor primeiro
2. Depois inative o setor

**Como transferir colaboradores:**
- Edite o vínculo do colaborador
- Selecione outro setor
- Salve

---

## ❓ Perguntas Frequentes

### 1. Qual a diferença entre Setores Globais e Setores por Unidade?

**Setores Globais** são o **catálogo** de todos os setores disponíveis no sistema (ex: Vendas, Caixa, Estoque).

**Setores por Unidade** são quais setores estão **ativos** em cada unidade específica. Uma unidade pode ter apenas "Vendas" e "Caixa", enquanto outra tem "Vendas", "Estoque" e "Administrativo".

---

### 2. Posso ter setores com nomes diferentes em unidades diferentes?

Não. Os setores são globais. Se você quiser um setor personalizado, crie-o como Setor Global primeiro.

**Por exemplo:**
- Se a Matriz tem um setor "Logística" e a Filial tem um setor "Distribuição", crie ambos como Setores Globais separados.

---

### 3. Um colaborador pode estar em mais de um setor?

Sim! Um colaborador pode ter múltiplos vínculos:
- Em diferentes unidades
- Em diferentes setores da mesma unidade (se necessário)

Mas ele terá apenas **um vínculo principal** (marcado como tal).

---

### 4. Posso editar o nome de um Setor Global?

Sim, mas **com cuidado**!

Quando você edita o nome de um Setor Global, o sistema atualiza **em cascata**:
- Todos os vínculos nas unidades
- Todos os registros de colaboradores

**Acesso:**
```
Setores Globais → Editar (ícone de lápis)
```

---

### 5. Posso excluir um Setor Global?

Somente se ele **não estiver em uso**.

O sistema verifica:
- ✅ Se nenhuma unidade está usando
- ✅ Se nenhum colaborador está vinculado

Se estiver em uso, você receberá uma mensagem de erro indicando quantas unidades/colaboradores estão usando.

---

### 6. Como saber quais colaboradores estão em um setor?

**Na página de Gerenciar Setores:**
- Cada card de setor mostra o número de colaboradores vinculados

**Para ver a lista completa:**
```
Unidades → [Selecionar Unidade] → Aba "Colaboradores"
→ Filtrar por setor
```

---

### 7. Posso definir mais de um responsável por setor?

Não. O sistema permite apenas **um responsável** por setor.

Se precisar de múltiplos responsáveis, considere:
- Criar sub-setores (ex: "Vendas - Time A", "Vendas - Time B")
- Usar o campo "cargo_especifico" nos colaboradores

---

### 8. O que é "Vínculo Principal"?

É a **unidade e setor** onde o colaborador está **alocado principalmente**.

**Comportamento:**
- Apenas um vínculo pode ser principal
- Os dados são replicados na tabela `colaboradores` para acesso rápido
- Usado em relatórios e dashboards

---

### 9. Posso transferir um colaborador entre setores?

Sim!

**Método 1 - Editar vínculo:**
- Edite o vínculo existente
- Altere o setor
- Salve

**Método 2 - Criar novo vínculo:**
- Desvincule do setor antigo (data de desvinculação)
- Crie novo vínculo no novo setor
- O histórico será mantido

---

### 10. Como ver o histórico de um colaborador?

O sistema mantém **histórico completo** com:
- Data de vinculação
- Data de desvinculação
- Setor anterior/atual
- Cargo específico

**Acesso:**
```
Colaboradores → [Selecionar Colaborador] → Ver Vínculos
```

---

## 🚀 Dicas de Uso

### ✅ Boas Práticas

1. **Crie todos os Setores Globais primeiro**
   - Planeje quais setores sua empresa tem
   - Crie todos de uma vez para evitar inconsistências

2. **Use nomes padronizados**
   - "Vendas" ao invés de "Vendas/Comercial/Atendimento"
   - Mantenha os nomes simples e claros

3. **Defina responsáveis**
   - Ajuda na gestão e organização
   - Facilita contato e responsabilização

4. **Revise periodicamente**
   - Verifique se colaboradores ainda estão nos setores corretos
   - Atualize responsáveis quando necessário

5. **Documente setores personalizados**
   - Se criar setores específicos da sua empresa, documente suas responsabilidades

---

### ⚡ Atalhos Úteis

- **Criar Setor Global**: `/public/unidades/setores_globais/cadastrar.php`
- **Listar Setores Globais**: `/public/unidades/setores_globais/listar.php`
- **Gerenciar Setores de Unidade**: `/public/unidades/setores/gerenciar.php?unidade_id=X`
- **Vincular Colaborador**: `/public/unidades/colaboradores/vincular.php?unidade_id=X`
- **Verificar Sistema**: `/public/verificar_setores.php`

---

## 📞 Suporte

Se ainda tiver dúvidas:

1. **Execute o verificador**: `/public/verificar_setores.php`
2. **Consulte a documentação técnica**: `/docs/SISTEMA_UNIDADES.md`
3. **Verifique os logs de erro**: `/logs/error.log`

---

**Última atualização**: 2025-11-06
**Versão**: 1.0.0
