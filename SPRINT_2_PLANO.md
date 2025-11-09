# Sprint 2 - Builder Visual de Formulários
## Semanas 3-5 | Desenvolvimento do Editor Drag-and-Drop

**Status:** 🔄 Em Desenvolvimento
**Início:** Após conclusão Sprint 1
**Duração:** 3 semanas

---

## 📋 Objetivos do Sprint 2

Criar interface visual intuitiva para construção de formulários, permitindo que usuários criem e editem formulários sem conhecimento técnico.

### Metas Principais:
1. ✅ Interface drag-and-drop para adicionar perguntas
2. ✅ Editor visual de seções
3. ✅ Configuração de tipos de pergunta
4. ✅ Preview em tempo real
5. ✅ Salvamento automático
6. ✅ Validações frontend

---

## 🎯 Funcionalidades a Implementar

### 1. Builder de Formulário (Página Principal)
**Arquivo:** `public/formularios-dinamicos/builder.php`

**Componentes:**
- Barra superior: Título do formulário, status, botões de ação
- Painel lateral esquerdo: Paleta de tipos de pergunta
- Área central: Canvas de construção (drag-and-drop)
- Painel lateral direito: Configurações da pergunta selecionada
- Rodapé: Salvamento automático, preview, publicar

**Tipos de pergunta disponíveis:**
1. 📝 Texto Curto (max 255 caracteres)
2. 📄 Texto Longo (textarea)
3. ⭕ Múltipla Escolha (radio buttons)
4. ☑️ Caixas de Seleção (checkboxes)
5. 📋 Lista Suspensa (select dropdown)
6. 📊 Escala Linear (0-10 com labels)
7. 📊 Grade Múltipla (matriz de opções)
8. 📅 Data (date picker)
9. ⏰ Hora (time picker)
10. 📎 Arquivo (upload)

---

### 2. Estrutura de Seções
**Funcionalidades:**
- Adicionar/remover seções
- Reordenar seções (drag-and-drop)
- Configurar seção:
  - Título
  - Descrição
  - Cor personalizada
  - Ícone
  - Peso para pontuação
- Expandir/colapsar seções
- Duplicar seção

---

### 3. Configuração de Perguntas
**Painel de propriedades para cada tipo:**

**Texto Curto/Longo:**
- Texto da pergunta
- Texto de ajuda (opcional)
- Obrigatória (sim/não)
- Validação (email, URL, número, etc.)
- Caracteres mínimo/máximo

**Múltipla Escolha/Lista:**
- Texto da pergunta
- Adicionar/remover/reordenar opções
- Permitir "Outro" com campo de texto
- Pontuação por opção
- Lógica condicional (ir para seção X)

**Escala Linear:**
- Texto da pergunta
- Valor mínimo/máximo
- Labels (ex: "Péssimo" a "Excelente")
- Pontuação proporcional

**Arquivo:**
- Tipos permitidos (PDF, imagens, etc.)
- Tamanho máximo
- Múltiplos arquivos

---

### 4. Sistema de Pontuação
**Configurações:**
- Tipo de cálculo:
  - Soma simples
  - Média ponderada
  - Percentual
- Peso por pergunta
- Peso por seção
- Pontuação máxima (calculada automaticamente)

---

### 5. Preview em Tempo Real
**Funcionalidades:**
- Modal fullscreen com preview do formulário
- Exibe exatamente como respondente verá
- Permite testar validações
- Não salva respostas
- Botão "Fechar Preview"

---

### 6. Salvamento Automático
**Comportamento:**
- Salva a cada 30 segundos
- Salva ao mudar de campo
- Indicador visual de status:
  - "Salvando..."
  - "Salvo"
  - "Erro ao salvar"
- Possibilidade de desfazer/refazer

---

## 🛠️ Tecnologias Utilizadas

### Frontend:
- **SortableJS** - Drag and drop
- **jQuery** - Manipulação DOM
- **Bootstrap 5** - Layout e componentes
- **FontAwesome 6** - Ícones
- **Flatpickr** - Date/time picker
- **Toastr** - Notificações

### Backend:
- **PHP 8.1+** - Lógica de servidor
- **PDO** - Banco de dados
- **JSON** - Comunicação AJAX

---

## 📁 Estrutura de Arquivos a Criar

```
public/formularios-dinamicos/
├── builder.php                 # Builder principal
├── api/
│   ├── salvar_formulario.php  # Salva formulário completo
│   ├── salvar_secao.php       # Salva seção
│   ├── salvar_pergunta.php    # Salva pergunta
│   ├── reordenar.php          # Reordena elementos
│   └── deletar.php            # Deleta elementos
├── assets/
│   ├── js/
│   │   ├── builder.js         # Lógica principal
│   │   ├── drag-drop.js       # Drag and drop
│   │   ├── auto-save.js       # Salvamento automático
│   │   └── preview.js         # Preview
│   └── css/
│       └── builder.css        # Estilos do builder

app/controllers/
├── FormularioDinamicoController.php  # ✅ Já existe
├── FormSecaoController.php           # Novo
└── FormPerguntaController.php        # Novo
```

---

## 🎨 Layout do Builder

```
┌─────────────────────────────────────────────────────────────┐
│  [<- Voltar]  Formulário: "Nome"  [Rascunho ▼]  [Preview]  │
├─────────────┬───────────────────────────┬───────────────────┤
│             │                           │                   │
│  PALETA     │      CANVAS               │   PROPRIEDADES    │
│             │                           │                   │
│ 📝 Texto    │  ┌─ Seção 1: Dados ─┐    │  Pergunta         │
│    Curto    │  │                    │   │  Selecionada      │
│             │  │ [1] Qual seu nome? │   │                   │
│ 📄 Texto    │  │     [Texto Curto]  │   │  Texto:           │
│    Longo    │  │                    │   │  ┌──────────────┐ │
│             │  │ [2] Sua idade?     │   │  │ Qual seu     │ │
│ ⭕ Múltipla │  │     [Número]       │   │  │ nome?        │ │
│    Escolha  │  │                    │   │  └──────────────┘ │
│             │  └────────────────────┘   │                   │
│ ☑️ Caixas   │                           │  ☑ Obrigatória    │
│            │  [+ Adicionar Seção]      │                   │
│ 📋 Lista    │                           │  Validação:       │
│    Suspensa │                           │  └─ Nenhuma ──┘  │
│             │                           │                   │
│ 📊 Escala   │                           │  [Deletar]        │
│    Linear   │                           │                   │
│             │                           │                   │
│ ...mais     │                           │                   │
│             │                           │                   │
└─────────────┴───────────────────────────┴───────────────────┘
│  💾 Salvo às 14:35  |  [Configurações Gerais]  |  [Publicar]│
└─────────────────────────────────────────────────────────────┘
```

---

## 🔄 Fluxo de Trabalho

### 1. Criar Novo Formulário
```
Usuário clica "Criar Novo"
  → Preenche título e descrição
  → Sistema cria formulário em status "rascunho"
  → Redireciona para builder.php?id=X
```

### 2. Editar Formulário
```
Usuário clica "Editar" em formulário existente
  → Sistema carrega formulário, seções e perguntas
  → Monta interface do builder
  → Habilita salvamento automático
```

### 3. Adicionar Pergunta
```
Usuário arrasta tipo de pergunta do painel
  → Solta na seção desejada
  → Sistema cria pergunta via AJAX
  → Abre painel de propriedades
  → Auto-save em segundo plano
```

### 4. Publicar Formulário
```
Usuário clica "Publicar"
  → Sistema valida:
    - Pelo menos 1 seção
    - Pelo menos 1 pergunta
    - Todas as perguntas configuradas
  → Muda status para "ativo"
  → Gera link público
  → Mostra confirmação
```

---

## 🧪 Critérios de Aceitação

- [ ] Usuário consegue criar formulário do zero
- [ ] Drag-and-drop funciona suavemente
- [ ] Todas as configurações são salvas
- [ ] Preview mostra formulário corretamente
- [ ] Salvamento automático funciona
- [ ] Validações impedem publicação incompleta
- [ ] Interface é responsiva (mobile)
- [ ] Performance: carrega em < 2s
- [ ] Sem erros no console
- [ ] Funciona nos browsers: Chrome, Firefox, Safari, Edge

---

## 📊 Estimativa de Tempo

| Tarefa | Tempo Estimado |
|--------|---------------|
| Layout HTML/CSS do builder | 8h |
| Integração SortableJS | 4h |
| Paleta de tipos de pergunta | 4h |
| CRUD de seções | 6h |
| CRUD de perguntas | 10h |
| Painel de propriedades | 8h |
| Configuração por tipo | 12h |
| Preview modal | 6h |
| Salvamento automático | 6h |
| Validações frontend | 4h |
| APIs backend | 8h |
| Testes e ajustes | 8h |
| **TOTAL** | **84h ≈ 2-3 semanas** |

---

## 🚀 Próximas Etapas (Sprint 3)

Após conclusão do Sprint 2:
- Interface pública para responder formulários
- Sistema de submissão de respostas
- Cálculo de pontuação
- Exibição de resultado com faixa

---

**Atualizado:** 2025-11-09
**Responsável:** Equipe de Desenvolvimento
