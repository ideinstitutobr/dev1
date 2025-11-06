# 🚀 Guia de Instalação e Deploy — SGC (Sistema de Gestão de Capacitações)

Este guia explica como instalar e publicar o SGC em qualquer hospedagem (cPanel/Plesk/Apache/Nginx/Windows), usando a página de auto-instalação para criar e configurar tudo.

## ✅ Visão Geral
- Linguagem: PHP 8.1+ com MySQL/MariaDB
- Front público em `public/`
- Instalação rápida via `public/instalador.php`
- Dependências opcionais via Composer (PHPMailer, TCPDF, PhpSpreadsheet)

## 📋 Pré‑requisitos
- PHP: `>= 8.1` com extensões `pdo_mysql`, `openssl`, `mbstring`, `json`, `fileinfo`
- Opcional para exportações:
  - XLSX: `extension=zip` e `phpoffice/phpspreadsheet`
  - PDF: `tecnickcom/tcpdf`
- Banco de dados MySQL/MariaDB acessível
- Acesso ao servidor: FTP/SFTP/cPanel/SSH
- Pastas com escrita: `public/uploads/`, `logs/`, `temp/`

## 🗂️ Estrutura do Projeto
- Raiz do projeto: `.../sgc/`
- Público (DocumentRoot recomendado): `.../sgc/public/`
- Configuração: `.../sgc/app/config/`
- Banco (schema/migrations): `.../sgc/database/`
- Dependências: `.../sgc/vendor/` (se não usar Composer no servidor)

## 🌍 Modos de Publicação
- DocumentRoot apontando para `public/` (ideal):
  - URL: `https://seu-dominio/`
  - `BASE_URL` em `app/config/config.php`: `https://seu-dominio/`
- Em subpasta (ex.: `public_html/sgc/`):
  - URL: `https://seu-dominio/sgc/public/`
  - `BASE_URL`: `https://seu-dominio/sgc/public/`

## 📦 Passo a Passo (FTP/cPanel)
1) Copie todo o projeto para o servidor (ex.: `public_html/sgc/`).  
2) Se não tiver Composer no servidor, inclua a pasta `vendor/`.  
3) Ajuste os arquivos de configuração (sem usar inputs do instalador):
- `app/config/database.php` → host, database, user, senha, charset
- `app/config/config.local.php` (opcional) → `BASE_URL`, `APP_ENV` e `COOKIE_SECURE`
4) Acesse o instalador unificado: `https://seu-dominio/sgc/public/install.php`
- O instalador lê as credenciais/URL dos arquivos de configuração
- Aplica o schema e todas as migrations idempotentes
- Garante usuário admin padrão (login: `admin@localhost`, senha: `admin`)
5) Garanta escrita em `public/uploads/`, `logs/`, `temp/`

## 🖥️ Passo a Passo (SSH + Composer)
- Suba o projeto (Git/rsync/SSH)  
- No servidor, dentro do projeto:
```
composer install --no-dev --prefer-dist
```
- Ative `extension=zip` no `php.ini` (para `.xlsx`)  
- Ajuste `BASE_URL`/`APP_ENV` via `app/config/config.local.php`  
- Acesse `.../public/install.php` e conclua a instalação única

## ⚙️ Configuração Importante
- `app/config/config.php`:
  - `BASE_URL` (ex.: `https://seu-dominio/sgc/public/`)
  - `APP_ENV = production` (desativa `display_errors` e ativa logs)
- `app/config/database.php`: gravado automaticamente pelo instalador

## ✉️ SMTP (Exemplos)
- Gmail: `smtp.gmail.com`, porta `587`, `tls`, senha de aplicativo (2FA).  
- Office365: `smtp.office365.com`, porta `587`, `tls`.  
- Mailtrap: credenciais do sandbox (ideal para testes).  
- Configure em `public/configuracoes/email.php` e use “📧 Testar Conexão”.

## 🔍 Validações Pós‑Instalação
- Login: `.../public/index.php` e redirecionamento para `dashboard`.  
- Relatórios: exportar Excel/PDF.  
  - XLSX: requer `extension=zip` e PhpSpreadsheet; sem a lib, o sistema gera `.xls` via fallback.  
  - PDF: requer TCPDF (Composer ou manual em `vendor/tecnickcom/tcpdf/`).  
- E-mail: reset/reenvio de senha via SMTP com logs em `email_logs`.

## 🧯 Troubleshooting
- Página em branco: veja `logs/error.log` (produção) e `display_errors` (dev).  
- Banco não conecta: confirme host/porta/usuário/senha no instalador.  
- XLSX não baixa `.xlsx`: ative `extension=zip`; sem isso, baixa `.xls`.  
- PDF não baixa: instale TCPDF ou copie para `vendor/tecnickcom/tcpdf/`.  
- E-mail falha: teste SMTP na UI e verifique `email_logs` com a mensagem detalhada do erro.

## 🔐 Segurança e Boas Práticas
- `APP_ENV=production` em produção (logs ativados, erro oculto).  
- Use HTTPS e `session.cookie_secure = 1`.  
- Evite deixar endpoints de teste (ex.: `relatorios/test_tcpdf.php`) em produção.  
- Mantenha permissões restritas e apenas onde necessário (uploads/logs/temp).

## 🔁 Atualizações Futuras
- Suba os arquivos atualizados.  
- Acesse novamente `instalador.php` para aplicar migrations (wizard idempotente).  
- Revalide SMTP e relatórios.

## 📎 Observações por Hospedagem
- cPanel:
  - MultiPHP Manager → PHP 8.1  
  - Git Version Control (opcional) para publicar direto do GitHub  
- Plesk:
  - Configure o DocumentRoot do site/subdomínio para `public/`  
- Apache/Nginx:
  - Apache: VirtualHost com `DocumentRoot /caminho/sgc/public`  
  - Nginx: raíz em `public/` + `try_files` e PHP-FPM habilitado

---

## ✅ Checklist Rápido
- [ ] Copiar projeto para o servidor  
- [ ] Ajustar `BASE_URL` e `APP_ENV`  
- [ ] Composer install (se disponível) ou enviar `vendor/`  
- [ ] Acessar `public/instalador.php` e concluir passos  
- [ ] Configurar SMTP e testar  
- [ ] Validar exportações de relatórios (Excel/PDF)  
- [ ] Remover endpoints de teste e revisar logs

---

Para dúvidas ou personalizações de deploy (GitHub Actions/FTP/SFTP), integro com você um fluxo automatizado que publique e execute a instalação sem intervenção manual.
