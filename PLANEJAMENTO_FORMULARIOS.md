# 📋 Planejamento: Dois Tipos de Formulários

## 🎯 Objetivo
Criar dois tipos de formulários de avaliação com diferentes frequências:
1. **Formulário Quinzenal/Mensal** - Avaliação periódica (atual)
2. **Formulário Diário** - Avaliação diária (novo)

Ambos compartilham:
- Mesma estrutura de módulos e perguntas
- Seleção de unidade
- Seleção de responsável
- Sistema de estrelas
- Fotos e observações

**Única diferença:** Frequência de avaliação

---

## 📊 Estrutura Atual

### Menu Sidebar Atual:
```
📋 Formulários
   ├── 📝 Checklists de Unidades
   ├── ➕ Nova Avaliação
   ├── 📊 Dashboard & Relatórios
   └── ⚙️ Configurar Módulos
```

### Arquivos Principais:
- `/public/checklist/index.php` - Lista de checklists
- `/public/checklist/novo.php` - Criar nova avaliação
- `/public/checklist/editar.php` - Preencher avaliação
- `/public/checklist/visualizar.php` - Ver resultados
- `/app/models/Checklist.php` - Model principal

---

## 🏗️ Estrutura Proposta

### Novo Menu Sidebar:
```
📋 Formulários
   │
   ├── 📅 Avaliações Quinzenais/Mensais
   │    ├── 📝 Lista de Avaliações
   │    ├── ➕ Nova Avaliação Quinzenal
   │    └── 📊 Dashboard & Relatórios
   │
   ├── 📆 Avaliações Diárias
   │    ├── 📝 Lista de Avaliações
   │    ├── ➕ Nova Avaliação Diária
   │    └── 📊 Dashboard & Relatórios
   │
   └── ⚙️ Configurações
        ├── Configurar Módulos
        └── Configurar Perguntas
```

---

## 🗄️ Mudanças no Banco de Dados

### 1. Adicionar coluna `tipo` na tabela `checklists`

```sql
ALTER TABLE checklists
ADD COLUMN tipo ENUM('quinzenal_mensal', 'diario') NOT NULL DEFAULT 'quinzenal_mensal'
AFTER responsavel_id;

-- Adicionar índice para otimizar queries
CREATE INDEX idx_tipo ON checklists(tipo);
```

### 2. Campos da tabela `checklists` (atualizada):
- id
- unidade_id
- colaborador_id
- responsavel_id
- **tipo** ← NOVO
- data_avaliacao
- observacoes_gerais
- status
- pontuacao_maxima
- percentual
- atingiu_meta
- criado_em
- atualizado_em

---

## 📁 Estrutura de Arquivos Proposta

### Opção 1: Pastas Separadas (Recomendado)
```
/public/checklist/
├── quinzenal/
│   ├── index.php          (Lista avaliações quinzenais)
│   ├── novo.php           (Criar avaliação quinzenal)
│   ├── editar.php         (Preencher avaliação)
│   ├── visualizar.php     (Ver resultado)
│   └── relatorios/
│       └── index.php      (Dashboard quinzenal)
│
├── diario/
│   ├── index.php          (Lista avaliações diárias)
│   ├── novo.php           (Criar avaliação diária)
│   ├── editar.php         (Preencher avaliação)
│   ├── visualizar.php     (Ver resultado)
│   └── relatorios/
│       └── index.php      (Dashboard diário)
│
├── modulos.php            (Configurar módulos - compartilhado)
├── perguntas.php          (Configurar perguntas - compartilhado)
└── shared/
    ├── salvar_resposta.php    (Compartilhado)
    ├── finalizar.php          (Compartilhado)
    └── buscar_liderancas.php  (Compartilhado)
```

### Opção 2: Arquivos Únicos com Parâmetro (Alternativa)
- Adicionar parâmetro `?tipo=quinzenal` ou `?tipo=diario`
- Menos duplicação de código
- Mais complexo de manter

**Recomendação:** Usar Opção 1 (pastas separadas) para melhor organização

---

## 🔧 Implementação Técnica

### Fase 1: Preparação do Banco ✅
1. ✅ Adicionar coluna `tipo` na tabela `checklists`
2. ✅ Criar migration script
3. ✅ Executar migration
4. ✅ Verificar integridade dos dados

### Fase 2: Atualização dos Models 📝
1. Atualizar `Checklist.php`:
   - Adicionar campo `tipo` no método `criar()`
   - Adicionar filtro por tipo no método `listar()`
   - Ajustar queries para incluir tipo

2. Manter `ModuloAvaliacao.php` e `Pergunta.php` inalterados
   (mesmos módulos e perguntas para ambos os tipos)

### Fase 3: Criar Estrutura de Pastas 📁
1. Criar `/public/checklist/quinzenal/`
2. Criar `/public/checklist/diario/`
3. Criar `/public/checklist/shared/`
4. Mover arquivos compartilhados para `/shared/`

### Fase 4: Duplicar e Adaptar Arquivos 📄

#### Arquivos Quinzenais (copiar dos atuais):
- `quinzenal/index.php` ← De `checklist/index.php`
  - Adicionar filtro: `WHERE tipo = 'quinzenal_mensal'`

- `quinzenal/novo.php` ← De `checklist/novo.php`
  - Adicionar hidden input: `<input type="hidden" name="tipo" value="quinzenal_mensal">`

- `quinzenal/editar.php` ← De `checklist/editar.php`
  - Sem alterações necessárias

- `quinzenal/visualizar.php` ← De `checklist/visualizar.php`
  - Sem alterações necessárias

#### Arquivos Diários (copiar dos quinzenais):
- `diario/index.php`
  - Adicionar filtro: `WHERE tipo = 'diario'`
  - Mudar título: "Avaliações Diárias"

- `diario/novo.php`
  - Adicionar hidden input: `<input type="hidden" name="tipo" value="diario">`
  - Mudar título: "Nova Avaliação Diária"

- `diario/editar.php` - Igual ao quinzenal
- `diario/visualizar.php` - Igual ao quinzenal

### Fase 5: Atualizar Menu Sidebar 🎨
Atualizar `/app/views/layouts/sidebar.php`:

```php
<li>
    <a href="#" onclick="toggleSubmenu('formularios'); return false;">
        <span class="icon">📋</span>
        <span class="text">Formulários</span>
    </a>
    <ul class="submenu" id="submenu-formularios">
        <!-- Quinzenais/Mensais -->
        <li class="submenu-header">📅 Quinzenais/Mensais</li>
        <li><a href="<?php echo BASE_URL; ?>checklist/quinzenal/">📝 Lista de Avaliações</a></li>
        <li><a href="<?php echo BASE_URL; ?>checklist/quinzenal/novo.php">➕ Nova Avaliação</a></li>
        <li><a href="<?php echo BASE_URL; ?>checklist/quinzenal/relatorios/">📊 Relatórios</a></li>

        <!-- Diários -->
        <li class="submenu-header">📆 Avaliações Diárias</li>
        <li><a href="<?php echo BASE_URL; ?>checklist/diario/">📝 Lista de Avaliações</a></li>
        <li><a href="<?php echo BASE_URL; ?>checklist/diario/novo.php">➕ Nova Avaliação</a></li>
        <li><a href="<?php echo BASE_URL; ?>checklist/diario/relatorios/">📊 Relatórios</a></li>

        <!-- Configurações -->
        <?php if (Auth::hasLevel(['admin', 'gestor'])): ?>
        <li class="submenu-header">⚙️ Configurações</li>
        <li><a href="<?php echo BASE_URL; ?>checklist/modulos.php">📦 Módulos</a></li>
        <li><a href="<?php echo BASE_URL; ?>checklist/perguntas.php">❓ Perguntas</a></li>
        <?php endif; ?>
    </ul>
</li>
```

### Fase 6: Atualizar Controllers 🎮
Atualizar `ChecklistController.php`:

```php
public function listar($tipo = null) {
    // Adicionar filtro por tipo
    $params = $_GET;
    if ($tipo) {
        $params['tipo'] = $tipo;
    }
    // ... resto do código
}

public function criar($dados) {
    // Validar tipo
    if (!in_array($dados['tipo'], ['quinzenal_mensal', 'diario'])) {
        throw new Exception('Tipo de formulário inválido');
    }
    // ... resto do código
}
```

### Fase 7: Criar Scripts de Migration 🔄
Criar scripts para:
1. Adicionar coluna `tipo`
2. Migrar dados existentes para `quinzenal_mensal`
3. Rollback (se necessário)

### Fase 8: Testes 🧪
1. ✅ Criar avaliação quinzenal
2. ✅ Criar avaliação diária
3. ✅ Preencher e finalizar quinzenal
4. ✅ Preencher e finalizar diária
5. ✅ Verificar relatórios separados
6. ✅ Testar filtros e buscas

### Fase 9: Limpeza 🧹
1. Remover arquivos antigos de `/public/checklist/` raiz
2. Configurar redirects
3. Atualizar documentação
4. Remover scripts de debug

---

## 🎨 Diferenças Visuais Sugeridas

### Formulário Quinzenal/Mensal:
- Cor primária: **Azul** (#667eea)
- Ícone: 📅
- Badge: "Quinzenal"

### Formulário Diário:
- Cor primária: **Verde** (#28a745)
- Ícone: 📆
- Badge: "Diário"

---

## 📋 Checklist de Implementação

### Banco de Dados:
- [ ] Criar migration para adicionar coluna `tipo`
- [ ] Executar migration
- [ ] Verificar dados existentes
- [ ] Criar índices

### Backend:
- [ ] Atualizar Model `Checklist.php`
- [ ] Atualizar Controller `ChecklistController.php`
- [ ] Criar estrutura de pastas
- [ ] Duplicar arquivos necessários
- [ ] Ajustar queries com filtro de tipo

### Frontend:
- [ ] Atualizar sidebar com novo menu
- [ ] Criar páginas quinzenais
- [ ] Criar páginas diárias
- [ ] Adicionar badges visuais de identificação
- [ ] Atualizar breadcrumbs

### Testes:
- [ ] Testar criação quinzenal
- [ ] Testar criação diária
- [ ] Testar preenchimento
- [ ] Testar finalização
- [ ] Testar visualização
- [ ] Testar relatórios

### Documentação:
- [ ] Atualizar README
- [ ] Documentar diferenças entre tipos
- [ ] Criar guia para usuários

---

## 🚀 Ordem de Execução Recomendada

1. **Primeiro:** Migration do banco de dados
2. **Segundo:** Atualizar Models
3. **Terceiro:** Criar estrutura de pastas
4. **Quarto:** Duplicar arquivos quinzenais
5. **Quinto:** Criar arquivos diários
6. **Sexto:** Atualizar menu sidebar
7. **Sétimo:** Testar tudo
8. **Oitavo:** Limpeza e documentação

---

## ⚠️ Considerações Importantes

1. **Compatibilidade:** Dados existentes serão marcados como `quinzenal_mensal`
2. **Relatórios:** Dashboards precisam filtrar por tipo
3. **Permissões:** Manter mesmas permissões para ambos os tipos
4. **Performance:** Adicionar índices nas queries por tipo
5. **Backup:** Fazer backup antes da migration

---

## 💡 Melhorias Futuras (Opcional)

1. Adicionar campo `frequencia_dias` (14, 30, 1, etc)
2. Sistema de agendamento automático
3. Notificações para avaliações pendentes
4. Comparação entre avaliações diárias e quinzenais
5. Meta de frequência por unidade

---

**Documento criado em:** <?php echo date('Y-m-d H:i:s'); ?>
**Versão:** 1.0
**Status:** Aguardando aprovação
