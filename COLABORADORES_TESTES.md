# TESTES - MÓDULO COLABORADORES

**Sistema de Gestão de Capacitações (SGC)**
**Sprint:** 4
**Data:** 10 de Novembro de 2025
**Módulo:** Colaboradores (Funcionários)

---

## 📋 ÍNDICE

1. [Resumo](#resumo)
2. [Ambiente de Teste](#ambiente-de-teste)
3. [Casos de Teste CRUD](#casos-de-teste-crud)
4. [Casos de Teste de Validação](#casos-de-teste-de-validação)
5. [Casos de Teste UI/UX](#casos-de-teste-uiux)
6. [Casos de Teste de Segurança](#casos-de-teste-de-segurança)
7. [Casos de Teste de Performance](#casos-de-teste-de-performance)
8. [Casos de Teste de API](#casos-de-teste-de-api)
9. [Resultados dos Testes](#resultados-dos-testes)

---

## 🎯 RESUMO

### Objetivos dos Testes

✅ Validar funcionalidades CRUD completas
✅ Testar validações (CPF, email, campos obrigatórios)
✅ Verificar segurança (CSRF, permissões)
✅ Testar performance (paginação, filtros)
✅ Validar API JSON
✅ Testar relacionamentos (histórico de treinamentos)

### Cobertura de Testes

| Categoria | Testes | Prioridade |
|-----------|--------|------------|
| **CRUD** | 10 testes | Crítica |
| **Validação** | 8 testes | Crítica |
| **UI/UX** | 8 testes | Alta |
| **Segurança** | 5 testes | Crítica |
| **Performance** | 3 testes | Média |
| **API** | 2 testes | Alta |
| **TOTAL** | **36 testes** | - |

---

## 🛠️ AMBIENTE DE TESTE

### Pré-Requisitos

- [ ] Banco de dados com tabela `colaboradores`
- [ ] Pelo menos 10 colaboradores cadastrados para testes de listagem
- [ ] Usuário de teste com nível "Estratégico" (admin)
- [ ] Usuário de teste com nível "Operacional" (não-admin)
- [ ] Dados de teste com CPFs válidos e inválidos

### Dados de Teste

```sql
-- Inserir colaboradores de teste
INSERT INTO colaboradores (nome, email, cpf, nivel_hierarquico, cargo, departamento, ativo) VALUES
('Teste Admin', 'admin@teste.com', '12345678901', 'Estratégico', 'Diretor', 'Administração', 1),
('Teste Operacional', 'operacional@teste.com', '98765432100', 'Operacional', 'Assistente', 'Operações', 1),
('Teste Inativo', 'inativo@teste.com', '11111111111', 'Tático', 'Gerente', 'RH', 0);
```

---

## ✅ CASOS DE TESTE CRUD

### TC-COL-001: Listar Colaboradores (Sem Filtros)
**Prioridade:** Crítica

**Passos:**
1. Acessar `/colaboradores`
2. Verificar renderização da página

**Resultado Esperado:**
- ✅ Página carrega sem erros
- ✅ Título "Colaboradores" visível
- ✅ Tabela com lista de colaboradores
- ✅ Colunas: Nome, Email, CPF, Nível, Cargo, Departamento, Status, Ações
- ✅ Botão "Novo Colaborador" visível
- ✅ Paginação funcional (se total > 20)
- ✅ Filtros disponíveis

**Status:** ⏳ Pendente

---

### TC-COL-002: Listar Colaboradores (Com Filtros)
**Prioridade:** Alta

**Passos:**
1. Acessar `/colaboradores`
2. Preencher filtro "Buscar" com termo (ex: "João")
3. Selecionar "Nível Hierárquico" (ex: "Operacional")
4. Selecionar "Status" (ex: "Ativo")
5. Clicar em "Buscar"

**Resultado Esperado:**
- ✅ Resultados filtrados corretamente
- ✅ Apenas colaboradores que atendem aos filtros aparecem
- ✅ URL contém parâmetros de filtro
- ✅ Campos de filtro mantêm valores após busca

**Status:** ⏳ Pendente

---

### TC-COL-003: Acessar Formulário de Criação
**Prioridade:** Crítica

**Passos:**
1. Acessar `/colaboradores`
2. Clicar em "Novo Colaborador"

**Resultado Esperado:**
- ✅ Redirecionamento para `/colaboradores/criar`
- ✅ Formulário vazio exibido
- ✅ Todos os campos disponíveis:
  - Identificação: nome, email, cpf, telefone, foto
  - Profissional: nível, cargo, departamento, salário, data_admissao
  - Sistema: origem, wordpress_id, ativo, observações
- ✅ Máscaras aplicadas (CPF, telefone, salário)
- ✅ CSRF token presente

**Status:** ⏳ Pendente

---

### TC-COL-004: Criar Novo Colaborador (Dados Válidos)
**Prioridade:** Crítica

**Passos:**
1. Acessar `/colaboradores/criar`
2. Preencher campos obrigatórios:
   - Nome: "Maria Silva"
   - Email: "maria.silva@teste.com"
   - Nível: "Tático"
3. Preencher campos opcionais:
   - CPF: "123.456.789-01"
   - Cargo: "Analista"
   - Departamento: "TI"
4. Clicar em "Cadastrar Colaborador"

**Resultado Esperado:**
- ✅ Redirecionamento para `/colaboradores`
- ✅ Flash message de sucesso exibida
- ✅ Colaborador aparece na lista
- ✅ Email normalizado para lowercase
- ✅ CPF salvo sem formatação (somente números)
- ✅ Evento `colaborador.created` disparado

**Status:** ⏳ Pendente

---

### TC-COL-005: Criar Colaborador (Email Duplicado)
**Prioridade:** Alta

**Passos:**
1. Acessar `/colaboradores/criar`
2. Preencher com email já existente no banco
3. Submeter formulário

**Resultado Esperado:**
- ✅ Formulário não é submetido
- ✅ Mensagem de erro: "E-mail já cadastrado"
- ✅ Dados do formulário preservados (except email)
- ✅ Redirecionamento de volta para `/colaboradores/criar`

**Status:** ⏳ Pendente

---

### TC-COL-006: Visualizar Detalhes do Colaborador
**Prioridade:** Crítica

**Passos:**
1. Acessar `/colaboradores`
2. Clicar no botão "Visualizar" (ícone olho) de um colaborador

**Resultado Esperado:**
- ✅ Redirecionamento para `/colaboradores/{id}`
- ✅ Informações do colaborador exibidas:
  - Foto de perfil (ou placeholder)
  - Nome, email, CPF
  - Cargo, departamento, nível
  - Data de admissão, salário
- ✅ Cards de estatísticas:
  - Total de treinamentos
  - Concluídos
  - Horas totais
  - Média de avaliação
- ✅ Tabela de histórico de treinamentos
- ✅ Botão "Editar" visível
- ✅ Botão "Ativar/Inativar" visível (admin only)

**Status:** ⏳ Pendente

---

### TC-COL-007: Acessar Formulário de Edição
**Prioridade:** Crítica

**Passos:**
1. Acessar `/colaboradores/{id}`
2. Clicar em "Editar"

**Resultado Esperado:**
- ✅ Redirecionamento para `/colaboradores/{id}/editar`
- ✅ Formulário preenchido com dados atuais
- ✅ Campos editáveis
- ✅ Botão "Atualizar Colaborador" visível

**Status:** ⏳ Pendente

---

### TC-COL-008: Atualizar Colaborador (Dados Válidos)
**Prioridade:** Crítica

**Passos:**
1. Acessar `/colaboradores/{id}/editar`
2. Alterar campo "Cargo" para "Coordenador"
3. Alterar campo "Salário" para "5.500,00"
4. Clicar em "Atualizar Colaborador"

**Resultado Esperado:**
- ✅ Redirecionamento para `/colaboradores`
- ✅ Flash message de sucesso
- ✅ Colaborador atualizado na lista
- ✅ Salário salvo corretamente (5500.00)
- ✅ Campo `updated_at` atualizado
- ✅ Evento `colaborador.updated` disparado

**Status:** ⏳ Pendente

---

### TC-COL-009: Inativar Colaborador (Admin)
**Prioridade:** Alta

**Passos:**
1. Login como usuário admin (Estratégico)
2. Acessar `/colaboradores/{id}` de um colaborador ativo
3. Clicar em "Inativar Colaborador"
4. Confirmar ação no alerta

**Resultado Esperado:**
- ✅ Redirecionamento para `/colaboradores`
- ✅ Flash message de sucesso
- ✅ Status alterado para "Inativo"
- ✅ Badge de status cinza na listagem
- ✅ Campo `ativo` = 0 no banco
- ✅ Evento `colaborador.inativado` disparado

**Status:** ⏳ Pendente

---

### TC-COL-010: Ativar Colaborador (Admin)
**Prioridade:** Alta

**Passos:**
1. Login como usuário admin (Estratégico)
2. Acessar `/colaboradores/{id}` de um colaborador inativo
3. Clicar em "Ativar Colaborador"
4. Confirmar ação no alerta

**Resultado Esperado:**
- ✅ Redirecionamento para `/colaboradores`
- ✅ Flash message de sucesso
- ✅ Status alterado para "Ativo"
- ✅ Badge de status verde na listagem
- ✅ Campo `ativo` = 1 no banco
- ✅ Evento `colaborador.ativado` disparado

**Status:** ⏳ Pendente

---

## 🔍 CASOS DE TESTE DE VALIDAÇÃO

### TC-COL-V001: Validação de Campos Obrigatórios
**Prioridade:** Crítica

**Passos:**
1. Acessar `/colaboradores/criar`
2. Deixar campos obrigatórios vazios (nome, email, nível)
3. Tentar submeter formulário

**Resultado Esperado:**
- ✅ Formulário bloqueado por validação HTML5
- ✅ Mensagens de erro exibidas
- ✅ Campos destacados em vermelho
- ✅ Não há submissão para o servidor

**Status:** ⏳ Pendente

---

### TC-COL-V002: Validação de CPF Inválido (Formato)
**Prioridade:** Alta

**Passos:**
1. Acessar `/colaboradores/criar`
2. Preencher CPF com formato inválido: "111.111.111-11" (dígitos repetidos)
3. Sair do campo (blur)

**Resultado Esperado:**
- ✅ Campo marcado como inválido (borda vermelha)
- ✅ Mensagem de erro: "CPF inválido"
- ✅ Validação JavaScript em tempo real

**Status:** ⏳ Pendente

---

### TC-COL-V003: Validação de CPF Inválido (Dígitos Verificadores)
**Prioridade:** Alta

**Passos:**
1. Acessar `/colaboradores/criar`
2. Preencher CPF com dígitos verificadores incorretos: "123.456.789-00"
3. Tentar submeter

**Resultado Esperado:**
- ✅ Validação JavaScript marca como inválido
- ✅ Se passar JS, backend retorna erro
- ✅ Mensagem: "CPF inválido"

**Status:** ⏳ Pendente

---

### TC-COL-V004: Validação de CPF Duplicado
**Prioridade:** Alta

**Passos:**
1. Acessar `/colaboradores/criar`
2. Preencher CPF já existente no banco
3. Submeter formulário

**Resultado Esperado:**
- ✅ Redirecionamento para `/colaboradores/criar`
- ✅ Mensagem de erro: "CPF já cadastrado"
- ✅ Dados preservados (exceto CPF)

**Status:** ⏳ Pendente

---

### TC-COL-V005: Validação de Email Inválido
**Prioridade:** Alta

**Passos:**
1. Acessar `/colaboradores/criar`
2. Preencher email inválido: "emailinvalido"
3. Tentar submeter

**Resultado Esperado:**
- ✅ Validação HTML5 bloqueia
- ✅ Mensagem de erro do browser
- ✅ Campo marcado como inválido

**Status:** ⏳ Pendente

---

### TC-COL-V006: Validação de Salário (Formato Brasileiro)
**Prioridade:** Média

**Passos:**
1. Acessar `/colaboradores/criar`
2. Preencher salário: "3.500,50"
3. Submeter formulário

**Resultado Esperado:**
- ✅ Valor aceito
- ✅ Salvo no banco como 3500.50 (decimal)
- ✅ Máscara JavaScript funciona corretamente

**Status:** ⏳ Pendente

---

### TC-COL-V007: Validação de Limites de Caracteres
**Prioridade:** Média

**Passos:**
1. Acessar `/colaboradores/criar`
2. Preencher campos excedendo limites:
   - Nome: 250 caracteres (limite: 200)
   - Email: 200 caracteres (limite: 150)
   - Cargo: 150 caracteres (limite: 100)

**Resultado Esperado:**
- ✅ Input HTML bloqueia digitação após limite (maxlength)
- ✅ Se passar, validação backend rejeita
- ✅ Mensagens de erro apropriadas

**Status:** ⏳ Pendente

---

### TC-COL-V008: Atualizar com Email do Próprio Colaborador
**Prioridade:** Média

**Passos:**
1. Acessar `/colaboradores/{id}/editar`
2. Manter o email atual (não alterar)
3. Alterar outro campo (ex: cargo)
4. Submeter

**Resultado Esperado:**
- ✅ Atualização bem-sucedida
- ✅ Validação de email único não acusa duplicata
- ✅ Email preservado

**Status:** ⏳ Pendente

---

## 🎨 CASOS DE TESTE UI/UX

### TC-COL-UI001: Máscaras de Entrada (CPF)
**Prioridade:** Alta

**Passos:**
1. Acessar `/colaboradores/criar`
2. Digitar CPF sem formatação: "12345678901"

**Resultado Esperado:**
- ✅ Máscara aplicada automaticamente: "123.456.789-01"
- ✅ Formatação em tempo real (ao digitar)

**Status:** ⏳ Pendente

---

### TC-COL-UI002: Máscaras de Entrada (Telefone)
**Prioridade:** Média

**Passos:**
1. Acessar `/colaboradores/criar`
2. Digitar telefone: "11987654321"

**Resultado Esperado:**
- ✅ Máscara aplicada: "(11) 98765-4321"
- ✅ Suporta celular (9 dígitos) e fixo (8 dígitos)

**Status:** ⏳ Pendente

---

### TC-COL-UI003: Máscaras de Entrada (Salário)
**Prioridade:** Média

**Passos:**
1. Acessar `/colaboradores/criar`
2. Digitar no campo salário: "5000"

**Resultado Esperado:**
- ✅ Máscara aplicada: "50,00" (ao sair do campo)
- ✅ Formato brasileiro: ponto para milhares, vírgula para decimal
- ✅ Exemplo: "15000" → "150,00" → corrigir para "1.500,00" se usuário digitou incorretamente

**Status:** ⏳ Pendente

---

### TC-COL-UI004: Exibição de Avatar/Foto
**Prioridade:** Baixa

**Passos:**
1. Visualizar colaborador COM foto_perfil
2. Visualizar colaborador SEM foto_perfil

**Resultado Esperado:**
- ✅ Com foto: imagem exibida (32x32 na lista, 120x120 nos detalhes)
- ✅ Sem foto: placeholder com ícone de usuário
- ✅ Imagens arredondadas (rounded-circle)

**Status:** ⏳ Pendente

---

### TC-COL-UI005: Badges de Status
**Prioridade:** Média

**Passos:**
1. Visualizar lista de colaboradores
2. Observar badges de nível e status

**Resultado Esperado:**
- ✅ Nível "Estratégico": badge vermelho (danger)
- ✅ Nível "Tático": badge amarelo (warning)
- ✅ Nível "Operacional": badge azul (info)
- ✅ Status "Ativo": badge verde (success)
- ✅ Status "Inativo": badge cinza (secondary)

**Status:** ⏳ Pendente

---

### TC-COL-UI006: Responsividade Mobile
**Prioridade:** Alta

**Passos:**
1. Acessar `/colaboradores` em dispositivo mobile ou reduzir janela
2. Testar formulário em mobile

**Resultado Esperado:**
- ✅ Tabela com scroll horizontal ou adaptada
- ✅ Filtros empilhados verticalmente
- ✅ Botões acessíveis e clicáveis
- ✅ Formulário com campos em 100% width

**Status:** ⏳ Pendente

---

### TC-COL-UI007: Paginação (Navegação)
**Prioridade:** Alta

**Passos:**
1. Acessar `/colaboradores` com mais de 20 registros
2. Clicar em "Próxima página"
3. Clicar em página específica
4. Clicar em "Primeira" e "Última"

**Resultado Esperado:**
- ✅ Navegação funciona corretamente
- ✅ Filtros preservados ao mudar de página
- ✅ Indicador de página atual destacado
- ✅ Total de páginas e registros exibidos

**Status:** ⏳ Pendente

---

### TC-COL-UI008: Confirmação de Ações Destrutivas
**Prioridade:** Alta

**Passos:**
1. Tentar inativar colaborador
2. Clicar em "Cancelar" no alerta

**Resultado Esperado:**
- ✅ Alerta de confirmação exibido
- ✅ Ao cancelar, nenhuma ação é executada
- ✅ Ao confirmar, ação é executada

**Status:** ⏳ Pendente

---

## 🔒 CASOS DE TESTE DE SEGURANÇA

### TC-COL-SEC001: CSRF Protection (Criar)
**Prioridade:** Crítica

**Passos:**
1. Acessar `/colaboradores/criar`
2. Remover campo `csrf_token` do formulário (via DevTools)
3. Tentar submeter

**Resultado Esperado:**
- ✅ Requisição bloqueada
- ✅ Erro 403 ou mensagem de erro
- ✅ Colaborador NÃO criado

**Status:** ⏳ Pendente

---

### TC-COL-SEC002: CSRF Protection (Atualizar/Deletar)
**Prioridade:** Crítica

**Passos:**
1. Tentar fazer requisição PUT/DELETE sem csrf_token

**Resultado Esperado:**
- ✅ Requisição bloqueada
- ✅ Ação NÃO executada

**Status:** ⏳ Pendente

---

### TC-COL-SEC003: Permissões de Inativação (Não-Admin)
**Prioridade:** Crítica

**Passos:**
1. Login como usuário Operacional (não-admin)
2. Tentar acessar `/colaboradores/{id}` e clicar "Inativar"

**Resultado Esperado:**
- ✅ Mensagem de erro: "Acesso negado"
- ✅ Colaborador NÃO inativado
- ✅ Redirecionamento para `/colaboradores`

**Status:** ⏳ Pendente

---

### TC-COL-SEC004: SQL Injection (Filtros)
**Prioridade:** Alta

**Passos:**
1. Acessar `/colaboradores?search=' OR '1'='1`
2. Verificar resultados

**Resultado Esperado:**
- ✅ Query escapada corretamente (prepared statements)
- ✅ Sem erro de SQL
- ✅ Sem dados sensíveis vazados

**Status:** ⏳ Pendente

---

### TC-COL-SEC005: XSS Protection
**Prioridade:** Alta

**Passos:**
1. Criar colaborador com nome: `<script>alert('XSS')</script>`
2. Visualizar lista e detalhes

**Resultado Esperado:**
- ✅ Script não executado
- ✅ Texto exibido como string literal
- ✅ Função `$this->e()` escapando HTML

**Status:** ⏳ Pendente

---

## ⚡ CASOS DE TESTE DE PERFORMANCE

### TC-COL-PERF001: Listagem com 100+ Registros
**Prioridade:** Média

**Passos:**
1. Inserir 100 colaboradores no banco
2. Acessar `/colaboradores`
3. Medir tempo de carregamento

**Resultado Esperado:**
- ✅ Página carrega em < 2 segundos
- ✅ Paginação limita a 20 registros por página
- ✅ Query otimizada (sem N+1)

**Status:** ⏳ Pendente

---

### TC-COL-PERF002: Filtros com Wildcards
**Prioridade:** Baixa

**Passos:**
1. Buscar por termo genérico: "a"
2. Verificar performance

**Resultado Esperado:**
- ✅ Busca completa em < 1 segundo
- ✅ Índices de banco utilizados
- ✅ LIKE otimizado

**Status:** ⏳ Pendente

---

### TC-COL-PERF003: Exportação CSV (1000+ Registros)
**Prioridade:** Baixa

**Passos:**
1. Acessar `/colaboradores/exportar` com 1000+ registros

**Resultado Esperado:**
- ✅ Download inicia rapidamente
- ✅ Arquivo gerado corretamente
- ✅ Sem timeout de servidor
- ✅ Formato CSV válido

**Status:** ⏳ Pendente

---

## 🔌 CASOS DE TESTE DE API

### TC-COL-API001: Endpoint JSON (Listagem)
**Prioridade:** Alta

**Passos:**
1. Fazer requisição GET para `/api/colaboradores`
2. Verificar resposta

**Resultado Esperado:**
- ✅ Status HTTP 200
- ✅ Content-Type: application/json
- ✅ Estrutura JSON válida:
```json
{
  "success": true,
  "data": [...],
  "pagination": {
    "total": 50,
    "page": 1,
    "per_page": 20,
    "total_pages": 3
  }
}
```

**Status:** ⏳ Pendente

---

### TC-COL-API002: Endpoint JSON (Filtros e Paginação)
**Prioridade:** Alta

**Passos:**
1. Fazer requisição GET para `/api/colaboradores?search=joão&nivel=Operacional&page=2&per_page=10`

**Resultado Esperado:**
- ✅ Filtros aplicados corretamente
- ✅ Paginação funcionando
- ✅ `per_page` respeitado (máx 100)
- ✅ Resultados corretos

**Status:** ⏳ Pendente

---

## 📊 RESULTADOS DOS TESTES

### Resumo

| Categoria | Total | Passou | Falhou | Pendente |
|-----------|-------|--------|--------|----------|
| CRUD | 10 | 0 | 0 | 10 |
| Validação | 8 | 0 | 0 | 8 |
| UI/UX | 8 | 0 | 0 | 8 |
| Segurança | 5 | 0 | 0 | 5 |
| Performance | 3 | 0 | 0 | 3 |
| API | 2 | 0 | 0 | 2 |
| **TOTAL** | **36** | **0** | **0** | **36** |

### Cobertura: 0%

---

## 🐛 BUGS ENCONTRADOS

### Críticos
*Nenhum bug crítico encontrado ainda*

### Altos
*Nenhum bug alto encontrado ainda*

### Médios
*Nenhum bug médio encontrado ainda*

### Baixos
*Nenhum bug baixo encontrado ainda*

---

## ✅ CHECKLIST DE TESTE

### Preparação
- [ ] Ambiente de teste configurado
- [ ] Banco de dados com dados de teste
- [ ] Usuários de teste criados (admin e não-admin)

### Execução
- [ ] Executar todos os testes CRUD (TC-COL-001 a TC-COL-010)
- [ ] Executar todos os testes de Validação (TC-COL-V001 a TC-COL-V008)
- [ ] Executar todos os testes de UI/UX (TC-COL-UI001 a TC-COL-UI008)
- [ ] Executar todos os testes de Segurança (TC-COL-SEC001 a TC-COL-SEC005)
- [ ] Executar todos os testes de Performance (TC-COL-PERF001 a TC-COL-PERF003)
- [ ] Executar todos os testes de API (TC-COL-API001 a TC-COL-API002)

### Documentação
- [ ] Atualizar status de cada teste
- [ ] Documentar bugs encontrados
- [ ] Criar issues para correções
- [ ] Atualizar cobertura de testes

### Aprovação
- [ ] Todos os testes críticos passaram
- [ ] Bugs críticos corrigidos
- [ ] Relatório de testes aprovado
- [ ] Sprint 4 pronta para produção

---

**STATUS GERAL:** ⏳ Testes Pendentes
**PRÓXIMO PASSO:** Executar testes e documentar resultados

---

**FIM DO DOCUMENTO DE TESTES**
