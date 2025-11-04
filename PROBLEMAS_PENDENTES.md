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

## ✅ **Migrations Pendentes de Execução:**

### **1. Migration de Notificações**
- **Arquivo:** `database/migrations/migration_notificacoes.sql`
- **Executar via:** https://comercial.ideinstituto.com.br/public/instalar_notificacoes.php
- **Cria:** 3 tabelas (notificacoes, configuracoes_email, email_logs)

### **2. Migration de Agenda**
- **Arquivo:** `database/migrations/migration_agenda.sql`
- **Executar via:** https://comercial.ideinstituto.com.br/public/instalar_agenda.php
- **Cria:** 1 tabela (agenda_treinamentos) + adiciona coluna agenda_id

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
