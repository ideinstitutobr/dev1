# 📧 Como Instalar o PHPMailer

O sistema de notificações precisa do **PHPMailer** para enviar e-mails. Existem 2 formas de instalar:

---

## ✅ **Opção 1: Via Composer (Recomendado)**

Se você tem acesso SSH ao servidor:

```bash
cd /home/u411458227/domains/ideinstituto.com.br/public_html/comercial
composer require phpmailer/phpmailer
```

---

## ✅ **Opção 2: Download Manual (Mais Fácil)**

Se não tem Composer, baixe manualmente:

### **Passo 1: Baixar PHPMailer**
- Acesse: https://github.com/PHPMailer/PHPMailer/archive/refs/tags/v6.9.1.zip
- Baixe o arquivo ZIP

### **Passo 2: Extrair Arquivos**
Extraia o ZIP e copie APENAS os seguintes arquivos para a pasta do projeto:

```
comercial/
└── vendor/
    └── phpmailer/
        └── phpmailer/
            └── src/
                ├── PHPMailer.php
                ├── SMTP.php
                ├── Exception.php
                └── POP3.php (opcional)
```

### **Passo 3: Criar Estrutura de Pastas**
Crie as pastas via FTP/cPanel:
1. `vendor/phpmailer/phpmailer/src/`
2. Coloque os 3 arquivos principais dentro de `src/`

### **Passo 4: Testar**
Acesse: `https://comercial.ideinstituto.com.br/public/configuracoes/email.php`
E teste o envio!

---

## 📂 **Estrutura Final:**

```
comercial do norte/
├── app/
├── database/
├── public/
└── vendor/
    └── phpmailer/
        └── phpmailer/
            └── src/
                ├── PHPMailer.php      (obrigatório)
                ├── SMTP.php           (obrigatório)
                ├── Exception.php      (obrigatório)
                └── POP3.php           (opcional)
```

---

## 🔧 **Configuração Gmail (Exemplo)**

Depois de instalar o PHPMailer:

1. **Criar Senha de Aplicativo no Gmail:**
   - Acesse: https://myaccount.google.com/apppasswords
   - Crie uma senha para "Outro (nome personalizado)"
   - Use essa senha no campo "Senha SMTP"

2. **Configurações:**
   - **Servidor SMTP:** smtp.gmail.com
   - **Porta:** 587
   - **Segurança:** TLS
   - **Usuário:** seu-email@gmail.com
   - **Senha:** senha-de-aplicativo-de-16-digitos
   - **E-mail Remetente:** seu-email@gmail.com
   - **Nome Remetente:** SGC - Sistema de Capacitações

3. **Marcar:** ☑️ Habilitar sistema de e-mail

4. **Clicar:** Testar Conexão

---

## ❓ **Problemas Comuns:**

### Erro: "PHPMailer não está instalado"
**Solução:** Instale via Composer ou manualmente (Opção 2)

### Erro: "SMTP Error: Could not authenticate"
**Solução:**
- Verifique usuário e senha
- No Gmail, use senha de aplicativo (não a senha normal)

### Erro: "SMTP connect() failed"
**Solução:**
- Verifique se o servidor permite conexões SMTP
- Tente porta 465 com SSL

### Erro: "Connection timed out"
**Solução:**
- Firewall do servidor pode estar bloqueando
- Contate o provedor de hospedagem

---

## 📧 **Outros Provedores SMTP:**

### **Office 365 / Outlook.com:**
```
Servidor: smtp.office365.com
Porta: 587
Segurança: TLS
```

### **Yahoo:**
```
Servidor: smtp.mail.yahoo.com
Porta: 465 ou 587
Segurança: SSL ou TLS
```

### **Hostinger:**
```
Servidor: smtp.hostinger.com
Porta: 587
Segurança: TLS
```

### **Mailtrap (para testes):**
```
Servidor: smtp.mailtrap.io
Porta: 587 ou 2525
Usuário: [obtido no painel Mailtrap]
Senha: [obtido no painel Mailtrap]
```

---

## ✅ **Verificar se Está Instalado:**

Execute este script para verificar:

```php
<?php
if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    echo "✅ PHPMailer instalado!";
} else {
    echo "❌ PHPMailer NÃO instalado";
}
?>
```

---

**Precisa de ajuda?** Entre em contato com o suporte técnico! 🚀
