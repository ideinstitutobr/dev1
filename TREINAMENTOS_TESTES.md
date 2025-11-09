# 🧪 TESTES DO MÓDULO TREINAMENTOS

## 📋 Documento de Testes - Sprint 3 (Fase 3)

**Módulo:** Treinamentos
**Versão:** 2.0 (Nova Arquitetura Core)
**Data:** 2025-11-09
**Responsável:** Equipe de Desenvolvimento

---

## 🎯 OBJETIVO

Validar completamente a migração do módulo de Treinamentos para a nova arquitetura Core, garantindo que todas as funcionalidades estejam operacionais, seguras e com boa experiência de usuário.

---

## 📊 STATUS DOS TESTES

| Categoria | Total | Executados | Aprovados | Falhas | Pendentes |
|-----------|-------|------------|-----------|---------|-----------|
| Funcionalidades CRUD | 12 | 0 | 0 | 0 | 12 |
| Validações | 8 | 0 | 0 | 0 | 8 |
| Interface/UX | 10 | 0 | 0 | 0 | 10 |
| Segurança | 6 | 0 | 0 | 0 | 6 |
| Performance | 4 | 0 | 0 | 0 | 4 |
| API/Integração | 5 | 0 | 0 | 0 | 5 |
| **TOTAL** | **45** | **0** | **0** | **0** | **45** |

---

## 🧪 CASOS DE TESTE

### 1. FUNCIONALIDADES CRUD

#### TC-001: Listar Treinamentos
**Prioridade:** Alta
**Pré-condições:** Sistema com banco de dados populado

**Passos:**
1. Acessar `/treinamentos`
2. Verificar se a lista de treinamentos é exibida
3. Verificar se todos os campos estão visíveis (ID, Nome, Tipo, Modalidade, Data Início, etc.)
4. Verificar se os badges de status têm as cores corretas
5. Verificar se o contador de participantes está visível

**Resultado Esperado:**
- ✅ Tabela exibida com todos os treinamentos
- ✅ Informações completas e formatadas corretamente
- ✅ Badges coloridos por status (Programado=azul, Em Andamento=amarelo, Executado=verde, Cancelado=vermelho)
- ✅ Ações (ver, editar, deletar) visíveis

**Status:** ⏳ Pendente

---

#### TC-002: Filtrar Treinamentos por Nome
**Prioridade:** Alta
**Pré-condições:** Sistema com múltiplos treinamentos cadastrados

**Passos:**
1. Acessar `/treinamentos`
2. No campo "Buscar", digitar parte do nome de um treinamento existente
3. Clicar em "Filtrar"
4. Verificar resultados

**Resultado Esperado:**
- ✅ Apenas treinamentos que contêm o texto buscado são exibidos
- ✅ A busca é case-insensitive
- ✅ O filtro é preservado na URL
- ✅ Mensagem "Nenhum treinamento encontrado" se não houver resultados

**Status:** ⏳ Pendente

---

#### TC-003: Filtrar por Tipo, Status e Ano
**Prioridade:** Alta
**Pré-condições:** Sistema com treinamentos de diferentes tipos, status e anos

**Passos:**
1. Acessar `/treinamentos`
2. Selecionar um tipo específico (ex: "Técnico")
3. Selecionar um status específico (ex: "Programado")
4. Selecionar um ano específico
5. Clicar em "Filtrar"

**Resultado Esperado:**
- ✅ Apenas treinamentos que atendem TODOS os filtros são exibidos
- ✅ Filtros são preservados na paginação
- ✅ URL reflete todos os filtros aplicados
- ✅ Botão "Limpar Filtros" remove todos os filtros

**Status:** ⏳ Pendente

---

#### TC-004: Paginação
**Prioridade:** Alta
**Pré-condições:** Sistema com mais de 20 treinamentos

**Passos:**
1. Acessar `/treinamentos`
2. Verificar se a paginação aparece
3. Clicar na página 2
4. Verificar se os próximos registros são exibidos
5. Clicar em "Primeira" e "Última" página
6. Aplicar filtro e verificar se paginação mantém filtros

**Resultado Esperado:**
- ✅ Paginação exibida corretamente
- ✅ Botões "Primeira", "Anterior", "Próxima", "Última" funcionam
- ✅ Indicador "Página X de Y" correto
- ✅ Filtros preservados entre páginas
- ✅ Total de registros exibido corretamente

**Status:** ⏳ Pendente

---

#### TC-005: Criar Novo Treinamento
**Prioridade:** Crítica
**Pré-condições:** Usuário autenticado

**Passos:**
1. Acessar `/treinamentos`
2. Clicar em "Novo Treinamento"
3. Preencher todos os campos obrigatórios:
   - Nome: "Treinamento de Teste Automatizado"
   - Tipo: "Técnico"
   - Modalidade: "Presencial"
   - Status: "Programado"
   - Data Início: data futura
4. Preencher campos opcionais (Fornecedor, Instrutor, Carga Horária, etc.)
5. Clicar em "Cadastrar Treinamento"

**Resultado Esperado:**
- ✅ Formulário carregado corretamente
- ✅ Campos organizados em seções temáticas
- ✅ Auto-focus no campo "Nome"
- ✅ Após submissão, redirecionamento para `/treinamentos`
- ✅ Flash message de sucesso exibida
- ✅ Treinamento aparece na lista
- ✅ Evento `treinamento.created` disparado

**Status:** ⏳ Pendente

---

#### TC-006: Editar Treinamento Existente
**Prioridade:** Crítica
**Pré-condições:** Sistema com treinamento cadastrado

**Passos:**
1. Acessar `/treinamentos`
2. Clicar no botão "Editar" de um treinamento
3. Verificar se o formulário é carregado com dados existentes
4. Alterar o campo "Nome" para "Treinamento Editado - Teste"
5. Alterar o status de "Programado" para "Em Andamento"
6. Clicar em "Atualizar Treinamento"

**Resultado Esperado:**
- ✅ Formulário pré-preenchido com dados atuais
- ✅ Título da página: "Editar Treinamento"
- ✅ Botão de submissão: "Atualizar"
- ✅ Method override para PUT incluído
- ✅ Após submissão, redirecionamento com sucesso
- ✅ Alterações refletidas no banco de dados
- ✅ Evento `treinamento.updated` disparado

**Status:** ⏳ Pendente

---

#### TC-007: Visualizar Detalhes do Treinamento
**Prioridade:** Alta
**Pré-condições:** Sistema com treinamento cadastrado com participantes

**Passos:**
1. Acessar `/treinamentos`
2. Clicar no botão "Ver Detalhes" (ícone de olho)
3. Verificar se todas as informações são exibidas
4. Verificar estatísticas (participantes, presentes, check-ins, % presença)

**Resultado Esperado:**
- ✅ Página de detalhes carregada
- ✅ 4 cards de estatísticas coloridos exibidos
- ✅ Informações do treinamento organizadas em seções
- ✅ Tabela de participantes (se houver)
- ✅ Ações contextuais baseadas no status
- ✅ Links úteis (Agenda, Frequência, Avaliações)
- ✅ Informações do sistema (ID, datas de criação/atualização)

**Status:** ⏳ Pendente

---

#### TC-008: Deletar Treinamento (como Admin)
**Prioridade:** Média
**Pré-condições:** Usuário autenticado como admin

**Passos:**
1. Fazer login como administrador
2. Acessar `/treinamentos`
3. Verificar se o botão "Deletar" está visível
4. Clicar no botão "Deletar" de um treinamento
5. Verificar se confirmação JavaScript é exibida
6. Confirmar deleção

**Resultado Esperado:**
- ✅ Botão de deletar visível apenas para admin
- ✅ Confirmação JavaScript: "Tem certeza que deseja deletar..."
- ✅ Após confirmação, treinamento é removido
- ✅ Flash message de sucesso exibida
- ✅ Treinamento não aparece mais na lista
- ✅ Evento `treinamento.deleted` disparado

**Status:** ⏳ Pendente

---

#### TC-009: Deletar Treinamento (como Usuário Regular)
**Prioridade:** Média
**Pré-condições:** Usuário autenticado sem perfil admin

**Passos:**
1. Fazer login como usuário regular
2. Acessar `/treinamentos`
3. Verificar botões de ação disponíveis

**Resultado Esperado:**
- ✅ Botão "Deletar" NÃO visível
- ✅ Apenas botões "Ver" e "Editar" visíveis
- ✅ Tentativa direta de acessar rota DELETE retorna erro 403

**Status:** ⏳ Pendente

---

#### TC-010: Iniciar Treinamento Programado
**Prioridade:** Alta
**Pré-condições:** Treinamento com status "Programado"

**Passos:**
1. Acessar detalhes de um treinamento programado
2. Verificar se botão "Iniciar Treinamento" está visível
3. Clicar no botão
4. Verificar se status muda para "Em Andamento"

**Resultado Esperado:**
- ✅ Botão "Iniciar" visível apenas em treinamentos programados
- ✅ Após clicar, status atualizado
- ✅ Flash message de sucesso
- ✅ Botão desaparece e novo botão contextual aparece

**Status:** ⏳ Pendente

---

#### TC-011: Executar Treinamento em Andamento
**Prioridade:** Alta
**Pré-condições:** Treinamento com status "Em Andamento"

**Passos:**
1. Acessar detalhes de um treinamento em andamento
2. Verificar se botão "Marcar como Executado" está visível
3. Clicar no botão
4. Verificar se status muda para "Executado"

**Resultado Esperado:**
- ✅ Botão "Marcar como Executado" visível
- ✅ Status atualizado para "Executado"
- ✅ Badge muda para verde
- ✅ Botão desaparece após execução

**Status:** ⏳ Pendente

---

#### TC-012: Cancelar Treinamento
**Prioridade:** Média
**Pré-condições:** Treinamento com status diferente de "Cancelado"

**Passos:**
1. Acessar detalhes de um treinamento
2. Clicar no botão "Cancelar Treinamento"
3. Confirmar cancelamento
4. Verificar se status muda para "Cancelado"

**Resultado Esperado:**
- ✅ Botão "Cancelar" visível
- ✅ Confirmação solicitada
- ✅ Status atualizado para "Cancelado"
- ✅ Badge muda para vermelho
- ✅ Ações contextuais desaparecem

**Status:** ⏳ Pendente

---

### 2. VALIDAÇÕES

#### TC-013: Validação de Campos Obrigatórios
**Prioridade:** Crítica
**Pré-condições:** Formulário de criação aberto

**Passos:**
1. Acessar `/treinamentos/criar`
2. Deixar campos obrigatórios vazios
3. Tentar submeter formulário
4. Verificar mensagens de erro

**Resultado Esperado:**
- ✅ Validação HTML5 impede submissão
- ✅ Mensagens de erro específicas para cada campo
- ✅ Campos obrigatórios: Nome, Tipo, Modalidade, Status, Data Início
- ✅ Campos marcados com borda vermelha
- ✅ Mensagens de erro em português

**Status:** ⏳ Pendente

---

#### TC-014: Validação de Data de Fim Anterior à Data de Início
**Prioridade:** Alta
**Pré-condições:** Formulário de criação aberto

**Passos:**
1. Preencher Data Início: "2025-12-31"
2. Preencher Data Fim: "2025-12-01"
3. Tentar submeter formulário

**Resultado Esperado:**
- ✅ Validação JavaScript impede submissão
- ✅ Alert exibido: "A data de fim não pode ser anterior à data de início!"
- ✅ Formulário não é submetido

**Status:** ⏳ Pendente

---

#### TC-015: Validação de Tamanho Máximo de Campos
**Prioridade:** Média
**Pré-condições:** Formulário de criação aberto

**Passos:**
1. Tentar inserir mais de 255 caracteres no campo "Nome"
2. Tentar inserir mais de 200 caracteres em "Fornecedor"
3. Verificar se limitação é aplicada

**Resultado Esperado:**
- ✅ Input HTML impede entrada além do limite (maxlength)
- ✅ Se submissão forçada, validação backend rejeita
- ✅ Mensagem de erro específica exibida

**Status:** ⏳ Pendente

---

#### TC-016: Validação de Campos Numéricos
**Prioridade:** Média
**Pré-condições:** Formulário de criação aberto

**Passos:**
1. Tentar inserir valor negativo em "Carga Horária"
2. Tentar inserir valor negativo em "Custo Total"
3. Tentar inserir texto em campos numéricos
4. Submeter formulário

**Resultado Esperado:**
- ✅ Validação HTML5 impede valores negativos (min="0")
- ✅ Validação impede entrada de texto
- ✅ Step de 0.5 para carga horária funciona
- ✅ Step de 0.01 para custo funciona

**Status:** ⏳ Pendente

---

#### TC-017: Validação de Tipo Inválido
**Prioridade:** Média
**Pré-condições:** Formulário de criação

**Passos:**
1. Tentar submeter com tipo não listado (via manipulação direta)
2. Verificar validação backend

**Resultado Esperado:**
- ✅ Backend rejeita valores não permitidos
- ✅ Mensagem de erro: "Tipo inválido"
- ✅ Old input preservado

**Status:** ⏳ Pendente

---

#### TC-018: Validação de Status Inválido
**Prioridade:** Média
**Pré-condições:** Formulário de criação

**Passos:**
1. Tentar submeter com status não listado (via manipulação)
2. Verificar validação backend

**Resultado Esperado:**
- ✅ Backend rejeita valores não permitidos
- ✅ Mensagem de erro: "Status inválido"
- ✅ Old input preservado

**Status:** ⏳ Pendente

---

#### TC-019: Preservação de Old Input Após Erro
**Prioridade:** Alta
**Pré-condições:** Formulário de criação

**Passos:**
1. Preencher 10 campos do formulário
2. Deixar 1 campo obrigatório vazio
3. Submeter formulário
4. Verificar se dados preenchidos são preservados

**Resultado Esperado:**
- ✅ Todos os campos previamente preenchidos mantêm seus valores
- ✅ Apenas campo com erro está vazio ou incorreto
- ✅ Usuário não precisa reescrever tudo

**Status:** ⏳ Pendente

---

#### TC-020: Validação CSRF Token
**Prioridade:** Crítica
**Pré-condições:** Formulário de criação

**Passos:**
1. Acessar formulário
2. Remover ou alterar csrf_token via DevTools
3. Tentar submeter formulário

**Resultado Esperado:**
- ✅ Submissão rejeitada
- ✅ Erro 403 ou mensagem de token inválido
- ✅ Log de tentativa suspeita

**Status:** ⏳ Pendente

---

### 3. INTERFACE E EXPERIÊNCIA DO USUÁRIO

#### TC-021: Responsividade Mobile
**Prioridade:** Alta
**Pré-condições:** Acesso via dispositivo móvel ou DevTools

**Passos:**
1. Acessar `/treinamentos` em tela de 375px (mobile)
2. Verificar se tabela é responsiva
3. Verificar se formulários são utilizáveis
4. Testar em tablet (768px)

**Resultado Esperado:**
- ✅ Tabela usa scroll horizontal ou cards em mobile
- ✅ Formulário ocupa largura completa e é legível
- ✅ Botões e campos são clicáveis/tocáveis
- ✅ Menu collapse funciona
- ✅ Filtros empilham verticalmente

**Status:** ⏳ Pendente

---

#### TC-022: Flash Messages
**Prioridade:** Alta
**Pré-condições:** Sistema configurado

**Passos:**
1. Criar um treinamento (sucesso)
2. Verificar flash message verde
3. Tentar criar treinamento com erro (erro de validação)
4. Verificar flash message vermelha
5. Verificar se mensagens são dismissíveis

**Resultado Esperado:**
- ✅ Flash message de sucesso verde com ícone de check
- ✅ Flash message de erro vermelha com ícone de exclamação
- ✅ Botão X fecha a mensagem
- ✅ Mensagens desaparecem após alguns segundos (opcional)

**Status:** ⏳ Pendente

---

#### TC-023: Auto-focus em Formulário
**Prioridade:** Baixa
**Pré-condições:** Formulário de criação

**Passos:**
1. Acessar `/treinamentos/criar`
2. Verificar se cursor está automaticamente no campo "Nome"

**Resultado Esperado:**
- ✅ Campo "Nome" tem foco automático
- ✅ Usuário pode começar a digitar imediatamente

**Status:** ⏳ Pendente

---

#### TC-024: Ícones e Visual
**Prioridade:** Média
**Pré-condições:** Sistema carregado

**Passos:**
1. Acessar todas as páginas do módulo
2. Verificar se ícones Font Awesome são exibidos
3. Verificar se cores correspondem ao status
4. Verificar se gradientes são aplicados

**Resultado Esperado:**
- ✅ Todos os ícones carregam corretamente
- ✅ Cores consistentes (Programado=azul, Em Andamento=amarelo, Executado=verde, Cancelado=vermelho)
- ✅ Gradientes nos cards e headers
- ✅ Design moderno e profissional

**Status:** ⏳ Pendente

---

#### TC-025: Links e Navegação
**Prioridade:** Alta
**Pré-condições:** Sistema com dados

**Passos:**
1. Testar link "Novo Treinamento"
2. Testar link "Voltar" no formulário
3. Testar links de ação (Ver, Editar, Deletar)
4. Testar links de breadcrumb
5. Testar links úteis na página de detalhes

**Resultado Esperado:**
- ✅ Todos os links funcionam corretamente
- ✅ Navegação intuitiva
- ✅ Botão "Voltar" retorna à lista
- ✅ Links abrem páginas corretas

**Status:** ⏳ Pendente

---

#### TC-026: Estados Vazios
**Prioridade:** Média
**Pré-condições:** Banco de dados vazio ou filtros que não retornam resultados

**Passos:**
1. Acessar `/treinamentos` com banco vazio
2. Aplicar filtro que não retorna resultados
3. Verificar mensagens exibidas

**Resultado Esperado:**
- ✅ Mensagem amigável: "Nenhum treinamento encontrado"
- ✅ Ícone de informação azul
- ✅ Sugestão para criar novo treinamento ou limpar filtros

**Status:** ⏳ Pendente

---

#### TC-027: Loading e Performance Visual
**Prioridade:** Baixa
**Pré-condições:** Sistema com muitos dados

**Passos:**
1. Acessar página com 100+ treinamentos
2. Verificar tempo de carregamento
3. Verificar se há flickering ou layout shift

**Resultado Esperado:**
- ✅ Página carrega em menos de 2 segundos
- ✅ Sem layout shift (CLS baixo)
- ✅ Tabela renderiza suavemente

**Status:** ⏳ Pendente

---

#### TC-028: Acessibilidade
**Prioridade:** Média
**Pré-condições:** Sistema carregado

**Passos:**
1. Navegar usando apenas teclado (Tab, Enter)
2. Verificar se labels estão associados aos inputs
3. Verificar se há alt text em ícones importantes
4. Testar com leitor de tela (opcional)

**Resultado Esperado:**
- ✅ Navegação por teclado funciona
- ✅ Todos os inputs têm labels
- ✅ Focus visível em elementos
- ✅ Estrutura semântica correta (h1, h2, etc.)

**Status:** ⏳ Pendente

---

#### TC-029: Botão de Limpar Formulário
**Prioridade:** Baixa
**Pré-condições:** Formulário preenchido

**Passos:**
1. Preencher vários campos do formulário
2. Clicar no botão "Limpar"
3. Verificar se todos os campos são resetados

**Resultado Esperado:**
- ✅ Todos os campos voltam aos valores padrão
- ✅ Campos obrigatórios ficam vazios
- ✅ Selects voltam à primeira opção

**Status:** ⏳ Pendente

---

#### TC-030: Tooltips e Títulos
**Prioridade:** Baixa
**Pré-condições:** Sistema carregado

**Passos:**
1. Passar mouse sobre botões de ação
2. Verificar se tooltips aparecem

**Resultado Esperado:**
- ✅ Tooltip "Ver Detalhes" no botão de olho
- ✅ Tooltip "Editar" no botão de edição
- ✅ Tooltip "Deletar" no botão de deletar

**Status:** ⏳ Pendente

---

### 4. SEGURANÇA

#### TC-031: Proteção CSRF
**Prioridade:** Crítica
**Pré-condições:** Sistema configurado

**Passos:**
1. Tentar submeter formulário sem CSRF token
2. Tentar submeter com token expirado
3. Tentar submeter com token inválido

**Resultado Esperado:**
- ✅ Todas as tentativas são rejeitadas
- ✅ Erro 403 ou mensagem clara
- ✅ Logs de segurança registram tentativa

**Status:** ⏳ Pendente

---

#### TC-032: Proteção XSS
**Prioridade:** Crítica
**Pré-condições:** Formulário de criação

**Passos:**
1. Tentar inserir script malicioso no campo "Nome": `<script>alert('XSS')</script>`
2. Submeter formulário
3. Visualizar detalhes do treinamento criado
4. Verificar código fonte da página

**Resultado Esperado:**
- ✅ Script não é executado
- ✅ Conteúdo é escapado: `&lt;script&gt;...`
- ✅ Proteção via `$this->e()` funciona

**Status:** ⏳ Pendente

---

#### TC-033: Injeção SQL
**Prioridade:** Crítica
**Pré-condições:** Formulário de busca

**Passos:**
1. Tentar buscar por: `'; DROP TABLE treinamentos; --`
2. Verificar se query é sanitizada
3. Verificar logs de erro

**Resultado Esperado:**
- ✅ Query não executa comandos maliciosos
- ✅ Prepared statements protegem contra SQL injection
- ✅ Dados permanecem intactos
- ✅ Busca trata input como string literal

**Status:** ⏳ Pendente

---

#### TC-034: Controle de Acesso
**Prioridade:** Alta
**Pré-condições:** Usuário não autenticado

**Passos:**
1. Tentar acessar `/treinamentos` sem login
2. Tentar acessar `/treinamentos/criar` sem login
3. Verificar redirecionamento

**Resultado Esperado:**
- ✅ Usuário redirecionado para `/login`
- ✅ Flash message: "Você precisa estar autenticado"
- ✅ URL original salva para redirect após login

**Status:** ⏳ Pendente

---

#### TC-035: Autorização de Deleção
**Prioridade:** Alta
**Pré-condições:** Usuário regular autenticado

**Passos:**
1. Login como usuário regular
2. Tentar acessar diretamente `/treinamentos/1/deletar` via POST

**Resultado Esperado:**
- ✅ Erro 403 Forbidden
- ✅ Mensagem: "Acesso negado. Apenas administradores podem deletar."
- ✅ Treinamento não é deletado

**Status:** ⏳ Pendente

---

#### TC-036: Sanitização de Upload (se aplicável)
**Prioridade:** Média
**Pré-condições:** Funcionalidade de upload implementada

**Passos:**
1. Tentar fazer upload de arquivo não permitido
2. Verificar validação de tipo de arquivo
3. Verificar validação de tamanho

**Resultado Esperado:**
- ✅ Apenas tipos permitidos aceitos
- ✅ Limite de tamanho respeitado
- ✅ Nomes de arquivo sanitizados
- ✅ Arquivos armazenados fora de public_html

**Status:** ⏳ Pendente (se aplicável)

---

### 5. PERFORMANCE

#### TC-037: Tempo de Resposta da Listagem
**Prioridade:** Alta
**Pré-condições:** Banco com 1000+ treinamentos

**Passos:**
1. Acessar `/treinamentos`
2. Medir tempo de resposta
3. Verificar query no log

**Resultado Esperado:**
- ✅ Tempo de resposta < 500ms
- ✅ Paginação limita resultados (20 por página)
- ✅ Query otimizada (sem N+1)
- ✅ Índices no banco utilizados

**Status:** ⏳ Pendente

---

#### TC-038: Otimização de Queries
**Prioridade:** Alta
**Pré-condições:** Sistema com dados relacionados

**Passos:**
1. Acessar página de detalhes
2. Verificar queries executadas (log ou profiler)
3. Contar número de queries

**Resultado Esperado:**
- ✅ Máximo 5 queries por página
- ✅ Eager loading de participantes
- ✅ Sem problema N+1
- ✅ Queries otimizadas com índices

**Status:** ⏳ Pendente

---

#### TC-039: Cache (se implementado)
**Prioridade:** Média
**Pré-condições:** Sistema de cache configurado

**Passos:**
1. Acessar lista de treinamentos (primeira vez)
2. Acessar novamente
3. Verificar se segunda requisição usa cache
4. Criar novo treinamento
5. Verificar se cache é invalidado

**Resultado Esperado:**
- ✅ Segunda requisição mais rápida
- ✅ Cache invalidado após create/update/delete
- ✅ Headers de cache corretos

**Status:** ⏳ Pendente (se aplicável)

---

#### TC-040: Tamanho da Página
**Prioridade:** Baixa
**Pré-condições:** Sistema carregado

**Passos:**
1. Acessar página de listagem
2. Verificar tamanho total transferido (DevTools Network)
3. Verificar se CSS/JS estão minificados

**Resultado Esperado:**
- ✅ Página < 500KB total
- ✅ Recursos externos (CDN) carregam rápido
- ✅ Sem recursos desnecessários

**Status:** ⏳ Pendente

---

### 6. API E INTEGRAÇÃO

#### TC-041: Endpoint API de Listagem
**Prioridade:** Alta
**Pré-condições:** API configurada

**Passos:**
1. Fazer requisição GET para `/api/treinamentos`
2. Verificar resposta JSON
3. Verificar estrutura de dados

**Resultado Esperado:**
- ✅ Status 200 OK
- ✅ JSON válido retornado
- ✅ Estrutura: `{"success": true, "data": [...], "pagination": {...}}`
- ✅ Headers corretos (Content-Type: application/json)

**Status:** ⏳ Pendente

---

#### TC-042: Endpoint API de Criação
**Prioridade:** Alta
**Pré-condições:** API configurada

**Passos:**
1. Fazer requisição POST para `/api/treinamentos`
2. Enviar JSON com dados válidos
3. Verificar resposta

**Resultado Esperado:**
- ✅ Status 201 Created
- ✅ JSON retorna treinamento criado com ID
- ✅ Location header com URL do recurso
- ✅ Evento disparado

**Status:** ⏳ Pendente

---

#### TC-043: Endpoint API com Erro
**Prioridade:** Alta
**Pré-condições:** API configurada

**Passos:**
1. Fazer requisição POST com dados inválidos
2. Verificar resposta de erro

**Resultado Esperado:**
- ✅ Status 422 Unprocessable Entity
- ✅ JSON com erros de validação: `{"success": false, "errors": {...}}`
- ✅ Mensagens de erro claras

**Status:** ⏳ Pendente

---

#### TC-044: Eventos Disparados
**Prioridade:** Alta
**Pré-condições:** Sistema com listeners configurados

**Passos:**
1. Criar treinamento
2. Verificar log se evento `treinamento.created` foi disparado
3. Editar treinamento
4. Verificar evento `treinamento.updated`
5. Deletar treinamento
6. Verificar evento `treinamento.deleted`

**Resultado Esperado:**
- ✅ Todos os 3 eventos são disparados
- ✅ Dados corretos passados no evento
- ✅ Listeners executam ações (se configurados)

**Status:** ⏳ Pendente

---

#### TC-045: Integração com Outros Módulos
**Prioridade:** Média
**Pré-condições:** Módulos de Participantes/Agenda implementados

**Passos:**
1. Criar treinamento
2. Adicionar participantes
3. Criar agenda
4. Verificar se detalhes mostram informações relacionadas

**Resultado Esperado:**
- ✅ Participantes aparecem na lista
- ✅ Agenda é exibida
- ✅ Estatísticas calculadas corretamente
- ✅ Relacionamentos funcionam

**Status:** ⏳ Pendente (dependente de outros módulos)

---

## 📝 CHECKLIST DE PRÉ-PRODUÇÃO

Antes de colocar o módulo em produção, verificar:

### Código
- [ ] Nenhum `var_dump`, `print_r`, `dd()` esquecido
- [ ] Nenhum comentário TODO sem resolução
- [ ] Code review realizado
- [ ] PSR-12 aplicado (formatação)

### Segurança
- [ ] CSRF ativo em todos os formulários
- [ ] XSS protection via escaping
- [ ] SQL injection prevenida (prepared statements)
- [ ] Validação backend para todos os inputs
- [ ] Controle de acesso implementado

### Performance
- [ ] Queries otimizadas
- [ ] Índices criados no banco
- [ ] Sem problema N+1
- [ ] Paginação implementada

### UX/UI
- [ ] Responsivo em mobile/tablet/desktop
- [ ] Flash messages funcionando
- [ ] Validações com mensagens claras
- [ ] Loading states (se aplicável)

### Dados
- [ ] Migrations executadas
- [ ] Seeders testados (opcional)
- [ ] Backup antes de deploy

### Documentação
- [ ] README atualizado
- [ ] Comentários no código
- [ ] Changelog atualizado
- [ ] Este documento de testes completo

---

## 🐛 BUGS ENCONTRADOS

| ID | Descrição | Severidade | Status | Responsável | Data |
|----|-----------|------------|--------|-------------|------|
| - | Nenhum bug encontrado ainda | - | - | - | - |

---

## 📈 MÉTRICAS

| Métrica | Valor Esperado | Valor Obtido | Status |
|---------|----------------|--------------|--------|
| Tempo médio de listagem | < 500ms | - | ⏳ |
| Tempo médio de criação | < 1s | - | ⏳ |
| Taxa de sucesso de testes | 100% | 0% | ⏳ |
| Cobertura de código | > 80% | - | ⏳ |

---

## ✅ APROVAÇÃO

### Critérios de Aceitação
- [ ] Todos os testes críticos passaram
- [ ] Nenhum bug de severidade alta/crítica
- [ ] Performance dentro do esperado
- [ ] Segurança validada
- [ ] UX aprovada
- [ ] Code review aprovado

### Assinaturas
- **Desenvolvedor:** ________________ Data: ____/____/____
- **QA/Tester:** ________________ Data: ____/____/____
- **Líder Técnico:** ________________ Data: ____/____/____

---

## 📚 REFERÊNCIAS

- [Documentação do Projeto](./README.md)
- [Status da Migração](./MIGRACAO_TREINAMENTOS_STATUS.md)
- [Arquitetura Core](./core/README.md)
- [Guia de Testes](./docs/TESTING.md)

---

**Última atualização:** 2025-11-09
**Versão do documento:** 1.0
**Próxima revisão:** Após execução dos testes
