# 📋 Checklist de Melhorias - SGC
**Baseado no Code Review de 06/11/2025**

Use este checklist para acompanhar a implementação das melhorias sugeridas.

---

## 🔴 CRÍTICO - Implementar URGENTEMENTE

- [ ] **SQL Injection em LIMIT/OFFSET**
  - [ ] Corrigir `app/models/Colaborador.php:81`
  - [ ] Corrigir `app/models/Treinamento.php:70`
  - [ ] Testar queries modificadas
  - [ ] Verificar outros models com mesmo padrão

**Responsável**: _____________
**Prazo**: _____________
**Status**: ⏳ Pendente

---

## 🟡 ALTA PRIORIDADE - Implementar esta semana

### Segurança

- [ ] **Migrar credenciais para variáveis de ambiente**
  - [ ] Instalar `vlucas/phpdotenv`
  - [ ] Criar arquivo `.env`
  - [ ] Adicionar `.env` ao `.gitignore`
  - [ ] Atualizar `app/config/database.php`
  - [ ] Atualizar documentação de instalação
  - [ ] Remover credenciais do repositório (reescrever histórico Git se necessário)

**Responsável**: _____________
**Prazo**: _____________
**Status**: ⏳ Pendente

---

- [ ] **Implementar Rate Limiting no Login**
  - [ ] Criar tabela `login_attempts`
  - [ ] Implementar método `checkLoginAttempts()` em Auth.php
  - [ ] Registrar tentativas falhadas
  - [ ] Adicionar mensagem de bloqueio temporário
  - [ ] Testar bloqueio após 5 tentativas
  - [ ] Implementar limpeza automática de registros antigos

**Responsável**: _____________
**Prazo**: _____________
**Status**: ⏳ Pendente

---

- [ ] **Adicionar Headers de Segurança HTTP**
  - [ ] Implementar headers em `config.php`
  - [ ] Ou adicionar em `.htaccess`
  - [ ] Testar com https://securityheaders.com
  - [ ] Ajustar CSP conforme necessário

**Responsável**: _____________
**Prazo**: _____________
**Status**: ⏳ Pendente

---

### Performance

- [ ] **Adicionar Índices de Banco de Dados**
  - [ ] Criar script SQL com todos os índices
  - [ ] Executar em ambiente de desenvolvimento
  - [ ] Medir performance (antes/depois)
  - [ ] Executar em produção (horário de baixo tráfego)
  - [ ] Monitorar impacto

**Responsável**: _____________
**Prazo**: _____________
**Status**: ⏳ Pendente

---

## 🟢 MÉDIA PRIORIDADE - Implementar este mês

### Qualidade de Código

- [ ] **Implementar Validação de CPF**
  - [ ] Criar função `validarCPF()` em helpers
  - [ ] Adicionar validação em Colaborador::criar()
  - [ ] Adicionar validação em Colaborador::atualizar()
  - [ ] Adicionar validação no frontend
  - [ ] Testar com CPFs válidos e inválidos

**Responsável**: _____________
**Prazo**: _____________
**Status**: ⏳ Pendente

---

- [ ] **Refatorar Código Duplicado**
  - [ ] Criar classe `DatabaseHelper`
  - [ ] Mover método `hasColumn()`
  - [ ] Mover método `tableExists()`
  - [ ] Atualizar todos os arquivos que usam essas funções
  - [ ] Testar todas as funcionalidades afetadas

**Responsável**: _____________
**Prazo**: _____________
**Status**: ⏳ Pendente

---

- [ ] **Implementar Logs Estruturados**
  - [ ] Criar classe `Logger`
  - [ ] Implementar níveis (DEBUG, INFO, WARNING, ERROR, CRITICAL)
  - [ ] Substituir `error_log()` por `Logger::error()`
  - [ ] Adicionar logs em operações críticas
  - [ ] Configurar rotação de logs

**Responsável**: _____________
**Prazo**: _____________
**Status**: ⏳ Pendente

---

- [ ] **Política de Senhas Fortes**
  - [ ] Implementar `validatePasswordStrength()`
  - [ ] Adicionar validação em Auth::register()
  - [ ] Adicionar validação em Auth::changePassword()
  - [ ] Criar lista de senhas comuns
  - [ ] Adicionar feedback visual no frontend
  - [ ] Documentar política de senhas

**Responsável**: _____________
**Prazo**: _____________
**Status**: ⏳ Pendente

---

### Performance

- [ ] **Otimizar Queries N+1**
  - [ ] Identificar todas as subconsultas em loops
  - [ ] Refatorar para usar JOINs
  - [ ] Testar queries otimizadas
  - [ ] Medir ganho de performance
  - [ ] Documentar mudanças

**Responsável**: _____________
**Prazo**: _____________
**Status**: ⏳ Pendente

---

- [ ] **Implementar Cache de Configurações**
  - [ ] Criar função `getCatalog()` com cache estático
  - [ ] Substituir leituras de `field_catalog.json`
  - [ ] Testar cache em diferentes cenários
  - [ ] Implementar invalidação quando necessário

**Responsável**: _____________
**Prazo**: _____________
**Status**: ⏳ Pendente

---

### Segurança

- [ ] **Implementar Auditoria de Ações**
  - [ ] Criar tabela `audit_log`
  - [ ] Criar classe `AuditLog`
  - [ ] Adicionar logs em operações CRUD
  - [ ] Criar interface para visualizar logs
  - [ ] Implementar filtros e busca
  - [ ] Configurar retenção de logs (LGPD)

**Responsável**: _____________
**Prazo**: _____________
**Status**: ⏳ Pendente

---

## 🔵 BAIXA PRIORIDADE - Backlog

### Arquitetura

- [ ] **Implementar Namespaces PSR-4**
  - [ ] Atualizar `composer.json`
  - [ ] Adicionar namespaces em Models
  - [ ] Adicionar namespaces em Controllers
  - [ ] Adicionar namespaces em Classes
  - [ ] Atualizar todos os `require` para `use`
  - [ ] Executar `composer dump-autoload`
  - [ ] Testar todo o sistema

**Responsável**: _____________
**Prazo**: _____________
**Status**: ⏳ Pendente

**Esforço estimado**: 6 horas

---

- [ ] **Adicionar Type Hints e Return Types**
  - [ ] Adicionar em todos os Models
  - [ ] Adicionar em todos os Controllers
  - [ ] Adicionar em todas as Classes
  - [ ] Testar com `declare(strict_types=1)`
  - [ ] Corrigir warnings do PHP

**Responsável**: _____________
**Prazo**: _____________
**Status**: ⏳ Pendente

**Esforço estimado**: 8 horas

---

- [ ] **Documentação PHPDoc Completa**
  - [ ] Adicionar docblocks em Models
  - [ ] Adicionar docblocks em Controllers
  - [ ] Adicionar docblocks em Classes
  - [ ] Gerar documentação com phpDocumentor
  - [ ] Publicar documentação

**Responsável**: _____________
**Prazo**: _____________
**Status**: ⏳ Pendente

**Esforço estimado**: 6 horas

---

- [ ] **Implementar Repository Pattern (Opcional)**
  - [ ] Criar pasta `app/repositories/`
  - [ ] Implementar `ColaboradorRepository`
  - [ ] Implementar `TreinamentoRepository`
  - [ ] Refatorar Models para usar Repositories
  - [ ] Atualizar Controllers
  - [ ] Testar todas as funcionalidades

**Responsável**: _____________
**Prazo**: _____________
**Status**: ⏳ Pendente

**Esforço estimado**: 12 horas

---

### Testes

- [ ] **Configurar PHPUnit**
  - [ ] Instalar PHPUnit via Composer
  - [ ] Criar estrutura de testes (`tests/Unit`, `tests/Feature`)
  - [ ] Configurar `phpunit.xml`
  - [ ] Criar banco de dados de testes
  - [ ] Configurar fixtures/factories

**Responsável**: _____________
**Prazo**: _____________
**Status**: ⏳ Pendente

**Esforço estimado**: 4 horas

---

- [ ] **Implementar Testes Unitários**
  - [ ] Testes para Models (mínimo 50% coverage)
  - [ ] Testes para Controllers (mínimo 50% coverage)
  - [ ] Testes para Classes (mínimo 70% coverage)
  - [ ] Medir code coverage
  - [ ] Meta: 70% coverage geral

**Responsável**: _____________
**Prazo**: _____________
**Status**: ⏳ Pendente

**Esforço estimado**: 20 horas

---

- [ ] **Implementar Testes de Integração**
  - [ ] Testar fluxo de login
  - [ ] Testar CRUD de colaboradores
  - [ ] Testar CRUD de treinamentos
  - [ ] Testar vinculação de participantes
  - [ ] Testar registro de frequência

**Responsável**: _____________
**Prazo**: _____________
**Status**: ⏳ Pendente

**Esforço estimado**: 10 horas

---

### Monitoramento

- [ ] **Implementar APM (Application Performance Monitoring)**
  - [ ] Escolher ferramenta (Sentry, New Relic, etc)
  - [ ] Configurar SDK
  - [ ] Integrar com sistema
  - [ ] Configurar alertas
  - [ ] Treinar equipe

**Responsável**: _____________
**Prazo**: _____________
**Status**: ⏳ Pendente

**Esforço estimado**: 3 horas

---

- [ ] **Configurar CI/CD**
  - [ ] Criar workflow GitHub Actions
  - [ ] Executar testes automaticamente
  - [ ] Verificar code style (PHP-CS-Fixer)
  - [ ] Análise estática (PHPStan)
  - [ ] Deploy automático (opcional)

**Responsável**: _____________
**Prazo**: _____________
**Status**: ⏳ Pendente

**Esforço estimado**: 6 horas

---

## 📊 PROGRESSO GERAL

### Por Prioridade
- 🔴 **Crítico**: ⬜⬜⬜⬜ 0/4 (0%)
- 🟡 **Alta**: ⬜⬜⬜⬜⬜⬜⬜⬜ 0/8 (0%)
- 🟢 **Média**: ⬜⬜⬜⬜⬜⬜ 0/6 (0%)
- 🔵 **Baixa**: ⬜⬜⬜⬜⬜⬜⬜⬜ 0/8 (0%)

### Por Categoria
- 🔒 **Segurança**: ⬜⬜⬜⬜⬜ 0/5 (0%)
- 🚀 **Performance**: ⬜⬜⬜ 0/3 (0%)
- 🛠️ **Qualidade**: ⬜⬜⬜⬜ 0/4 (0%)
- 📦 **Arquitetura**: ⬜⬜⬜⬜ 0/4 (0%)
- 🧪 **Testes**: ⬜⬜⬜ 0/3 (0%)
- 📊 **Monitoramento**: ⬜⬜ 0/2 (0%)

### Total Geral
**0/26 tarefas concluídas (0%)**

---

## 📝 NOTAS

### Como usar este checklist:
1. Atribua responsáveis para cada tarefa
2. Defina prazos realistas
3. Marque ✅ conforme completa os itens
4. Atualize o status: ⏳ Pendente | 🔄 Em Progresso | ✅ Concluído | ❌ Bloqueado
5. Documente problemas encontrados
6. Revise semanalmente

### Dicas:
- Comece pelos itens críticos (🔴)
- Implemente mudanças em pequenos PRs
- Teste cada mudança isoladamente
- Mantenha este documento atualizado
- Comemore pequenas vitórias! 🎉

---

*Última atualização: 06/11/2025*
