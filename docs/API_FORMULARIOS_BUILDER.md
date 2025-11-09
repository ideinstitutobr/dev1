# API - Formulários Dinâmicos (Builder)

Documentação das APIs REST para o Builder Visual de Formulários Dinâmicos.

## Autenticação

Todas as APIs requerem autenticação via sessão PHP. O usuário deve estar logado no sistema.

**Verificação:**
- `Auth::isLogged()` - Verifica se usuário está autenticado
- `Auth::getUserId()` - Obtém ID do usuário logado
- `Auth::isAdmin()` - Verifica se usuário é administrador

**Permissões:**
- Apenas o proprietário do formulário ou administradores podem editar
- Validação feita em cada endpoint

## Endpoints

### 1. Salvar Formulário

**URL:** `/public/formularios-dinamicos/api/salvar_formulario.php`
**Método:** `POST`
**Content-Type:** `application/json`

**Descrição:** Atualiza dados gerais de um formulário existente.

**Body (JSON):**
```json
{
  "id": 1,
  "titulo": "Novo Título",
  "descricao": "Nova descrição",
  "status": "ativo",
  "permite_multiplas_respostas": 1,
  "requer_autenticacao": 0,
  "mostrar_pontuacao": 1,
  "tipo_pontuacao": "soma",
  "data_inicio": "2025-01-01",
  "data_fim": "2025-12-31",
  "mensagem_boas_vindas": "Bem-vindo!",
  "mensagem_conclusao": "Obrigado!",
  "config_adicional": {
    "cor_tema": "#667eea",
    "permitir_voltar": true
  }
}
```

**Campos Permitidos:**
- `titulo` (string) - Título do formulário
- `descricao` (string) - Descrição
- `slug` (string) - URL amigável
- `status` (enum) - `rascunho`, `ativo`, `inativo`, `arquivado`
- `permite_multiplas_respostas` (boolean) - 0 ou 1
- `requer_autenticacao` (boolean) - 0 ou 1
- `mostrar_pontuacao` (boolean) - 0 ou 1
- `tipo_pontuacao` (enum) - `nenhum`, `soma`, `media`, `percentual`
- `data_inicio` (date) - Data de início
- `data_fim` (date) - Data de término
- `mensagem_boas_vindas` (text) - Mensagem inicial
- `mensagem_conclusao` (text) - Mensagem final
- `config_adicional` (object) - Configurações adicionais em JSON

**Resposta Sucesso (200):**
```json
{
  "success": true,
  "message": "Formulário atualizado com sucesso"
}
```

**Resposta Erro (400):**
```json
{
  "success": false,
  "message": "Descrição do erro"
}
```

---

### 2. Salvar Seção

**URL:** `/public/formularios-dinamicos/api/salvar_secao.php`
**Método:** `POST`
**Content-Type:** `application/json`

**Descrição:** Cria uma nova seção ou atualiza uma existente.

**Body para Criar (JSON):**
```json
{
  "formulario_id": 1,
  "titulo": "Nova Seção",
  "descricao": "Descrição da seção",
  "ordem": 1,
  "obrigatoria": 0
}
```

**Body para Atualizar (JSON):**
```json
{
  "id": 5,
  "titulo": "Seção Atualizada",
  "descricao": "Nova descrição",
  "ordem": 2,
  "obrigatoria": 1
}
```

**Campos:**
- `formulario_id` (int) - ID do formulário (apenas criação)
- `id` (int) - ID da seção (apenas atualização)
- `titulo` (string) - Título da seção
- `descricao` (string) - Descrição
- `ordem` (int) - Ordem de exibição (auto se não informado)
- `obrigatoria` (boolean) - 0 ou 1

**Resposta Sucesso (200):**
```json
{
  "success": true,
  "message": "Seção criada com sucesso",
  "secao_id": 5
}
```

---

### 3. Salvar Pergunta

**URL:** `/public/formularios-dinamicos/api/salvar_pergunta.php`
**Método:** `POST`
**Content-Type:** `application/json`

**Descrição:** Cria uma nova pergunta ou atualiza uma existente.

**Body para Criar (JSON):**
```json
{
  "secao_id": 5,
  "tipo_pergunta": "multipla_escolha",
  "pergunta": "Qual é a sua resposta?",
  "descricao": "Escolha uma opção",
  "ordem": 1,
  "obrigatoria": 1,
  "tem_pontuacao": 1,
  "pontuacao_maxima": 10,
  "config_adicional": {
    "permitir_outro": true
  }
}
```

**Body para Atualizar (JSON):**
```json
{
  "id": 15,
  "pergunta": "Pergunta Atualizada",
  "descricao": "Nova descrição",
  "obrigatoria": 0
}
```

**Tipos de Pergunta:**
- `texto_curto` - Resposta curta
- `texto_longo` - Parágrafo
- `multipla_escolha` - Múltipla escolha (uma opção)
- `caixas_selecao` - Caixas de seleção (várias opções)
- `lista_suspensa` - Dropdown
- `escala_linear` - Escala numérica
- `grade_multipla` - Grade de perguntas
- `data` - Campo de data
- `hora` - Campo de hora
- `arquivo` - Upload de arquivo

**Campos:**
- `secao_id` (int) - ID da seção (apenas criação)
- `id` (int) - ID da pergunta (apenas atualização)
- `tipo_pergunta` (enum) - Tipo da pergunta
- `pergunta` (string) - Texto da pergunta
- `descricao` (string) - Texto de ajuda
- `ordem` (int) - Ordem de exibição
- `obrigatoria` (boolean) - 0 ou 1
- `tem_pontuacao` (boolean) - 0 ou 1
- `pontuacao_maxima` (decimal) - Pontuação máxima
- `config_adicional` (object) - Configurações específicas do tipo

**Resposta Sucesso (200):**
```json
{
  "success": true,
  "message": "Pergunta criada com sucesso",
  "pergunta_id": 15
}
```

---

### 4. Deletar

**URL:** `/public/formularios-dinamicos/api/deletar.php`
**Método:** `POST`
**Content-Type:** `application/json`

**Descrição:** Deleta uma seção ou pergunta.

**Body para Deletar Seção (JSON):**
```json
{
  "tipo": "secao",
  "id": 5
}
```

**Body para Deletar Pergunta (JSON):**
```json
{
  "tipo": "pergunta",
  "id": 15
}
```

**Campos:**
- `tipo` (enum) - `secao` ou `pergunta`
- `id` (int) - ID do item a deletar

**Comportamento:**
- Deletar seção: Remove a seção e TODAS as perguntas dela (CASCADE)
- Deletar pergunta: Remove a pergunta e todas as opções dela (CASCADE)

**Resposta Sucesso (200):**
```json
{
  "success": true,
  "message": "Seção deletada com sucesso"
}
```

---

### 5. Reordenar

**URL:** `/public/formularios-dinamicos/api/reordenar.php`
**Método:** `POST`
**Content-Type:** `application/json`

**Descrição:** Atualiza a ordem de perguntas ou seções após drag-and-drop.

**Body para Reordenar Perguntas (JSON):**
```json
{
  "tipo": "perguntas",
  "ordens": [
    {"id": 15, "ordem": 1},
    {"id": 16, "ordem": 2},
    {"id": 17, "ordem": 3}
  ]
}
```

**Body para Reordenar Seções (JSON):**
```json
{
  "tipo": "secoes",
  "ordens": [
    {"id": 5, "ordem": 1},
    {"id": 6, "ordem": 2}
  ]
}
```

**Campos:**
- `tipo` (enum) - `perguntas` ou `secoes`
- `ordens` (array) - Array de objetos com `id` e `ordem`

**Resposta Sucesso (200):**
```json
{
  "success": true,
  "message": "Perguntas reordenadas com sucesso",
  "total_atualizadas": 3
}
```

---

## Códigos de Status HTTP

- `200` - Sucesso
- `400` - Erro de validação ou lógica de negócio
- `401` - Não autenticado (não implementado, usa redirecionamento)
- `403` - Sem permissão (retorna 400 com mensagem)
- `500` - Erro interno do servidor

## Tratamento de Erros

Todas as APIs retornam erros no formato:

```json
{
  "success": false,
  "message": "Descrição do erro"
}
```

**Erros Comuns:**
- "Usuário não autenticado"
- "Dados inválidos"
- "ID do formulário não informado"
- "Formulário não encontrado"
- "Sem permissão para editar este formulário"
- "Status inválido"
- "Tipo de pergunta inválido"

## Exemplos de Uso com JavaScript

### Atualizar Título do Formulário

```javascript
$.ajax({
    url: BASE_URL + 'formularios-dinamicos/api/salvar_formulario.php',
    method: 'POST',
    data: JSON.stringify({
        id: 1,
        titulo: 'Novo Título'
    }),
    contentType: 'application/json',
    success: function(response) {
        if (response.success) {
            console.log('Título atualizado!');
        }
    },
    error: function(xhr) {
        const error = JSON.parse(xhr.responseText);
        alert('Erro: ' + error.message);
    }
});
```

### Adicionar Nova Seção

```javascript
$.ajax({
    url: BASE_URL + 'formularios-dinamicos/api/salvar_secao.php',
    method: 'POST',
    data: JSON.stringify({
        formulario_id: 1,
        titulo: 'Nova Seção',
        descricao: 'Descrição da seção'
    }),
    contentType: 'application/json',
    success: function(response) {
        if (response.success) {
            console.log('Seção criada com ID:', response.secao_id);
            location.reload();
        }
    }
});
```

### Adicionar Nova Pergunta

```javascript
$.ajax({
    url: BASE_URL + 'formularios-dinamicos/api/salvar_pergunta.php',
    method: 'POST',
    data: JSON.stringify({
        secao_id: 5,
        tipo_pergunta: 'texto_curto',
        pergunta: 'Qual é o seu nome?',
        obrigatoria: 1
    }),
    contentType: 'application/json',
    success: function(response) {
        if (response.success) {
            console.log('Pergunta criada com ID:', response.pergunta_id);
            location.reload();
        }
    }
});
```

### Deletar Pergunta

```javascript
if (confirm('Tem certeza que deseja deletar esta pergunta?')) {
    $.ajax({
        url: BASE_URL + 'formularios-dinamicos/api/deletar.php',
        method: 'POST',
        data: JSON.stringify({
            tipo: 'pergunta',
            id: 15
        }),
        contentType: 'application/json',
        success: function(response) {
            if (response.success) {
                $('#pergunta-15').fadeOut();
            }
        }
    });
}
```

### Reordenar Perguntas

```javascript
// Após drag-and-drop com SortableJS
function reordenarPerguntas(evt) {
    const ordens = [];
    $(evt.to).find('.pergunta-card').each(function(index) {
        ordens.push({
            id: $(this).data('pergunta-id'),
            ordem: index + 1
        });
    });

    $.ajax({
        url: BASE_URL + 'formularios-dinamicos/api/reordenar.php',
        method: 'POST',
        data: JSON.stringify({
            tipo: 'perguntas',
            ordens: ordens
        }),
        contentType: 'application/json',
        success: function(response) {
            console.log('Reordenado:', response.total_atualizadas, 'perguntas');
        }
    });
}
```

## Segurança

**Validações Implementadas:**
1. ✅ Autenticação obrigatória (sessão PHP)
2. ✅ Verificação de propriedade (owner ou admin)
3. ✅ Validação de tipos de dados
4. ✅ Prepared statements (PDO) para SQL injection
5. ✅ JSON encoding para XSS
6. ✅ Validação de enums (status, tipos, etc.)

**Não Implementado:**
- CSRF tokens (recomendado adicionar)
- Rate limiting
- Logging de auditoria

## Próximos Passos

Para completar o Builder, faltam implementar:

1. **API para Opções de Resposta** (para múltipla escolha, caixas de seleção, etc.)
2. **Painel de Propriedades Dinâmico** (frontend)
3. **Preview Modal** (frontend)
4. **Validações de Frontend**
5. **Auto-save Real** (atualmente apenas simula)

---

**Versão:** Sprint 2 - Fase 2
**Data:** 2025-11-09
**Autor:** Claude Code
