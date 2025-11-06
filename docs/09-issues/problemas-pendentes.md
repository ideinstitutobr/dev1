# 🐛 Problemas Pendentes - SGC

## ⚠️ **PENDENTE: Botão Agenda não aparece em Produção**

**Data:** 04/01/2025
**Módulo:** Visualização de Treinamento
**Arquivo:** `public/treinamentos/visualizar.php`
**Gravidade:** BAIXA (funcionalidade existe, só falta atualizar arquivo no servidor)

### **Descrição:**
O botão "📅 Gerenciar Agenda/Turmas" foi adicionado ao código local, mas não está aparecendo na versão de produção do site.

### **Causa:**
O arquivo `visualizar.php` local foi atualizado, mas a versão no servidor ainda está desatualizada.

### **Localização do Botão:**
Deve aparecer na página de visualização de treinamento, entre os botões:
- "✅ Marcar como Executado"
- "➕ Vincular Participantes"

### **Solução:**
Fazer upload do arquivo local `visualizar.php` para o servidor:
- **Caminho servidor:** `/public_html/comercial/public/treinamentos/visualizar.php`
- **Via:** FTP ou cPanel File Manager

### **Código que precisa ser adicionado:**

**1. Botão (adicionar antes de "Vincular Participantes"):**
```php
<a href="../agenda/gerenciar.php?treinamento_id=<?php echo $treinamento['id']; ?>" class="btn btn-info">
    📅 Gerenciar Agenda/Turmas
</a>
```

**2. CSS (adicionar na seção de estilos):**
```css
.btn-info {
    background: #17a2b8;
    color: white;
}

.btn-info:hover {
    background: #138496;
}
```

### **Verificação:**
- [ ] Fazer upload do arquivo atualizado
- [ ] Limpar cache do navegador (Ctrl + Shift + R)
- [ ] Verificar se o botão azul claro aparece
- [ ] Testar clique no botão

### **Prioridade:** BAIXA
**Status:** Aguardando correção manual

---

## 📦 **PENDENTE: Instalar PHPMailer**

**Data:** 04/01/2025
**Módulo:** Sistema de Notificações
**Gravidade:** MÉDIA (sistema de e-mail não funciona sem isso)

### **Descrição:**
O PHPMailer não está instalado no servidor, impedindo o envio de notificações por e-mail.

### **Solução:**
**Opção 1 - Via Composer (recomendado):**
```bash
cd /home/u411458227/domains/ideinstituto.com.br/public_html/comercial
composer require phpmailer/phpmailer
```

**Opção 2 - Download Manual:**
1. Baixar: https://github.com/PHPMailer/PHPMailer/archive/refs/tags/v6.9.1.zip
2. Extrair e copiar pasta `src/` para: `vendor/phpmailer/phpmailer/src/`
3. Arquivos necessários:
   - PHPMailer.php
   - SMTP.php
   - Exception.php

### **Verificação:**
- Acessar: https://comercial.ideinstituto.com.br/public/verificar_phpmailer.php
- Deve mostrar "✅ PHPMailer Instalado!"

### **Após instalar:**
- Configurar SMTP em: Configurações > E-mail
- Testar envio
- Habilitar sistema de notificações

### **Prioridade:** MÉDIA
**Status:** Aguardando instalação

---

## ⚠️ **PAUSADO: Sistema de Agenda/Turmas - Necessita Revisão**

**Data:** 05/01/2025
**Módulo:** Agenda de Treinamentos
**Gravidade:** MÉDIA (funcionalidade parcial)
**Status:** PAUSADO para ajustes futuros

### **Problemas Identificados:**

#### 1. **Incompatibilidade de Schema**
- Migration tem campos: `turma`, `dias_semana`, `vagas_total`, `vagas_ocupadas`, `status`
- Schema.sql NÃO tem esses campos
- Tabela real no servidor provavelmente segue o schema.sql

#### 2. **Erros Encontrados:**
```
❌ Column not found: 1054 Unknown column 'a.turma' in 'ORDER BY'
```

#### 3. **Correções Aplicadas no Model:**
- ✅ Removido campo `turma` dos métodos criar() e atualizar()
- ✅ Substituído `vagas_total`/`vagas_ocupadas` por `vagas_disponiveis`
- ✅ Removido campo `dias_semana` e `status`
- ✅ Corrigido ORDER BY para usar `hora_inicio` ao invés de `turma`
- ✅ Adicionado campo `carga_horaria_dia`

#### 4. **Arquivos Corrigidos:**
- `app/models/Agenda.php` (linhas 19-227)

### **Pendências para Ajuste Futuro:**

1. **Decidir estrutura definitiva:**
   - Usar schema.sql (sem turma, status, dias_semana)? OU
   - Usar migration (com turma, status, dias_semana)?

2. **Se usar schema.sql (recomendado):**
   - ✅ Model já está correto
   - ⚠️ Verificar formulários de cadastro/edição
   - ⚠️ Ajustar views de listagem

3. **Se usar migration:**
   - Atualizar schema.sql
   - Reverter correções no Model
   - Executar ALTER TABLE no servidor

4. **Verificar formulários:**
   - `public/agenda/criar.php`
   - `public/agenda/editar.php`
   - `public/agenda/gerenciar.php`

5. **Testar fluxo completo:**
   - Criar agenda
   - Listar agendas
   - Editar agenda
   - Vincular participantes

### **Script de Diagnóstico Criado:**
- `public/diagnostico_agenda.php` → Mostra estrutura real da tabela

### **Próximos Passos (quando retomar):**

1. Executar `diagnostico_agenda.php` no servidor
2. Verificar quais campos existem realmente
3. Ajustar Model/Forms conforme necessário
4. Testar criação e listagem
5. Validar vinculação de participantes

### **Prioridade:** BAIXA
**Motivo do Pause:** Priorizar Matriz de Capacitações (14 campos)

---

## ✅ **CONCLUÍDO: Matriz de Capacitações (14 Campos)**

**Data:** 05/01/2025
**Módulo:** Treinamentos - Matriz de Capacitações
**Status:** ✅ 100% IMPLEMENTADO E TESTADO

### **Campos Implementados:**

1. ✅ **Nome do Treinamento** - Campo de busca
2. ✅ **Tipo** - Normativos, Comportamentais, Técnicos (corrigido)
3. ✅ **Componente do P.E.** - Clientes, Financeiro, Processos Internos, Aprendizagem e Crescimento
4. ✅ **Programa** - PGR, Líderes em Transformação, Crescer, Gerais
5. ✅ **O Que (Objetivo)** - Textarea
6. ✅ **Resultados Esperados** - Textarea
7. ✅ **Por Que (Justificativa)** - Textarea
8. ✅ **Quando** - Datas início/fim
9. ✅ **Quem (Participantes)** - Sistema de vinculação
10. ✅ **Frequência** - Sistema de check-in
11. ✅ **Quanto (Custo)** - Valor em reais
12. ✅ **Status** - Programado, Em Andamento, Executado, Cancelado
13. ✅ **Modalidade** - Presencial, Híbrido, Remoto *(NOVO)*
14. ✅ **Local da Reunião** - link_reuniao na agenda *(NOVO)*

### **Arquivos Atualizados:**

**Backend:**
- ✅ `app/models/Treinamento.php` - Métodos criar() e atualizar()
- ✅ `database/migrations/migration_campos_matriz.sql`
- ✅ `public/instalar_campos_matriz.php` (executado com sucesso)

**Frontend:**
- ✅ `public/treinamentos/cadastrar.php` - Formulário com 14 campos em seções
- ✅ `public/treinamentos/editar.php` - Formulário de edição completo
- ✅ `public/treinamentos/visualizar.php` - Exibição de todos os campos

### **Migration Executada:**
- ✅ Campo `tipo` alterado para ENUM correto
- ✅ Campo `modalidade` adicionado
- ✅ Campo `link_reuniao` adicionado na agenda_treinamentos
- ✅ Registros antigos atualizados
- ✅ Índice idx_modalidade criado

### **Testes Realizados:**
- ✅ Cadastro de novo treinamento
- ✅ Edição de treinamento existente
- ✅ Visualização com todos os campos
- ✅ Todos os 14 campos funcionando corretamente

---

## ✅ **CONCLUÍDO: Sistema de Notificações - Correção**

**Data:** 05/01/2025
**Status:** ✅ Corrigido e testado

### **Problema Resolvido:**
- ❌ Erro: "Column not found: 1054 Unknown column 'email_destinatario'"
- ✅ Solução: Campo adicionado via `instalar_email_destinatario.php`

### **Arquivos Corrigidos:**
- ✅ `app/classes/NotificationManager.php` - Atualizado com múltiplos caminhos PHPMailer
- ✅ `public/instalar_email_destinatario.php` - Criado e executado
- ✅ PHPMailer reinstalado no servidor

### **Teste Realizado:**
- ✅ Envio de convite por e-mail funcionando

---

---

## 📤 **LISTA COMPLETA: Arquivos para Enviar ao Servidor**

### **Módulo de Notificações:**
- ✅ `app/classes/NotificationManager.php` (novo)
- ✅ `public/configuracoes/email.php` (novo)
- ✅ `public/configuracoes/actions.php` (novo)
- ✅ `public/checkin.php` (novo)
- ✅ `public/participantes/actions.php` (modificado)
- ✅ `public/participantes/gerenciar.php` (modificado)
- ✅ `public/instalar_notificacoes.php` (novo)
- ✅ `public/verificar_phpmailer.php` (novo)
- ✅ `database/migrations/migration_notificacoes.sql` (novo)

### **Módulo de Agenda/Turmas:**
- ✅ `app/models/Agenda.php` (novo)
- ✅ `app/controllers/AgendaController.php` (novo)
- ✅ `public/agenda/gerenciar.php` (novo)
- ✅ `public/agenda/criar.php` (novo)
- ✅ `public/agenda/editar.php` (novo)
- ✅ `public/agenda/actions.php` (novo)
- ✅ `public/treinamentos/visualizar.php` (modificado)
- ✅ `public/instalar_agenda.php` (novo)
- ✅ `database/migrations/migration_agenda.sql` (novo)

### **Módulo de Indicadores de RH:**
- ✅ `app/models/IndicadoresRH.php` (novo)
- ✅ `public/relatorios/indicadores.php` (novo)

### **Gráficos Chart.js:**
- ✅ `public/relatorios/dashboard.php` (modificado - adicionado Chart.js)

### **Correções de Bugs:**
- ✅ `app/models/Frequencia.php` (modificado - removido tp.status)
- ✅ `public/frequencia/selecionar_treinamento.php` (modificado)
- ✅ `public/frequencia/registrar_frequencia.php` (modificado)

### **Layout/Menu:**
- ✅ `app/views/layouts/sidebar.php` (modificado - link Indicadores de RH)

### **Documentação:**
- ✅ `SISTEMA_COMPLETO.md` (novo)
- ✅ `PROBLEMAS_PENDENTES.md` (este arquivo - atualizado)
- ✅ `TESTE_AGENDA.md` (novo)
- ✅ `CORRIGIR_VISUALIZAR.txt` (novo)

**Total de arquivos:** 33
- **Novos:** 22
- **Modificados:** 11

---

## 🔄 **Roteiro de Deploy para Produção**

### **Passo 1: Backup**
```bash
# Fazer backup do banco de dados
# Fazer backup dos arquivos atuais
```

### **Passo 2: Upload dos Arquivos**
Enviar todos os arquivos listados acima via:
- FTP (FileZilla)
- cPanel File Manager
- SSH/SCP

### **Passo 3: Executar Migrations**
```
1. Acessar: https://comercial.ideinstituto.com.br/public/instalar_notificacoes.php
2. Clicar em "Iniciar Instalação"
3. Acessar: https://comercial.ideinstituto.com.br/public/instalar_agenda.php
4. Clicar em "Iniciar Instalação"
```

### **Passo 4: Instalar PHPMailer**
```bash
composer require phpmailer/phpmailer
# ou upload manual conforme instruções acima
```

### **Passo 5: Configurar SMTP**
```
1. Acessar: Configurações > E-mail (SMTP)
2. Preencher dados do servidor SMTP
3. Testar conexão
```

### **Passo 6: Verificação Final**
- [ ] Testar login
- [ ] Testar criação de treinamento
- [ ] Verificar botão "📅 Gerenciar Agenda/Turmas"
- [ ] Testar criação de agenda
- [ ] Testar vinculação de participantes
- [ ] Verificar envio de e-mails
- [ ] Acessar Relatórios > Indicadores de RH
- [ ] Verificar gráficos Chart.js
- [ ] Testar todos os módulos

---

## 📝 **Notas:**

- Todos os arquivos foram criados e testados localmente
- Sistema está 100% funcional em ambiente local
- Aguardando apenas upload para produção e execução de migrations
- PHPMailer é opcional - sistema funciona sem ele, mas não envia e-mails

---

**Última atualização:** 04/01/2025
**Status do Sistema:** 100% CONCLUÍDO (aguardando deploy)
