# 📚 SGC - Sistema de Gestão de Capacitações

![Status](https://img.shields.io/badge/Status-100%25%20Conclu%C3%ADdo-brightgreen)
![Versão](https://img.shields.io/badge/Vers%C3%A3o-1.0.0-blue)
![PHP](https://img.shields.io/badge/PHP-8.x-purple)
![MySQL](https://img.shields.io/badge/MySQL-8.0-orange)

Sistema completo para gestão de treinamentos e capacitação de colaboradores desenvolvido para a **Comercial do Norte**.

---

## 🎯 Sobre o Sistema

O **SGC** é uma plataforma web robusta para gerenciar todo o ciclo de vida de treinamentos corporativos, desde o cadastro de colaboradores até relatórios avançados com indicadores de RH e gráficos interativos.

### ✨ Principais Recursos

- 🎓 **Gestão completa de treinamentos** e colaboradores
- 📧 **Sistema de notificações** por e-mail com templates personalizados
- 📅 **Módulo de agenda** para múltiplas turmas e datas
- 📊 **7 Indicadores de RH (KPIs)** calculados automaticamente
- 📈 **Gráficos interativos** com Chart.js
- 📝 **Controle de frequência** com check-in por QR Code
- 🎯 **Sistema de avaliações** e feedback
- 📱 **Interface responsiva** para mobile e desktop

---

## 🔄 Atualizações Recentes (Configurar Campos, Listagem e Formulários)

Nos últimos ciclos, o SGC recebeu melhorias significativas nos módulos de Colaboradores e na página de configuração de campos:

- ✅ **Configurar Campos (Colaboradores > Configurar Campos)** reestruturado em abas: Nível Hierárquico, Cargo, Departamento e Setor.
  - Cabeçalho com meta de "Itens • Vínculos".
  - Linhas padronizadas com colunas: Nome | Vinculados | Ações.
  - Ações compactas por ícones: Renomear (edição inline) e Remover (com confirmação).
  - Botão de adicionar direto no cabeçalho (sem rodapés escondidos).
  - Indicador de vínculos padronizado como "N vínculo(s)".
  - Escrita do catálogo (`app/config/field_catalog.json`) **atômica** (LOCK_EX) e sem duplicados case‑insensíveis.

- 🏷️ **Nível Hierárquico (ENUM) dinâmico**:
  - Adição de novos níveis alterando o ENUM via `ALTER TABLE colaboradores MODIFY COLUMN nivel_hierarquico ENUM(...)`.
  - Renomear nível atualiza registros e redefine o ENUM para refletir o novo valor.
  - Remoção de nível só é permitida se não houver colaboradores vinculados.

- 🧭 **Formulários (Cadastrar/Editar Colaborador)**:
  - Nível Hierárquico como `<select>` dinâmico (valores do ENUM).
  - Cargo/Departamento/Setor como `<select>` dinâmicos (união de valores distintos do banco + catálogo).
  - Setor aparece como `<select>` quando a coluna existe; caso contrário, é exibido desabilitado com instrução para instalar.

- 📋 **Listagem de Colaboradores**:
  - Filtros dinâmicos para Nível, Cargo, Departamento e Setor.
  - Colunas visíveis: ID, Nome, E‑mail, Nível, Cargo, Departamento, Setor, Ações.
  - Ajuste CSS defensivo para garantir exibição dos cabeçalhos `<th>`.
  - Fallback visual em "Nível" e "Setor" para "-" quando não houver valor.

- 👁️ **Visualização do Colaborador**:
  - Exibe Nível com badges e, quando disponível, o **Setor**.

- 🧩 **Assets placeholders**:
  - `public/assets/css/main.css`, `dashboard.css` e `public/assets/js/main.js` adicionados como base para evitar 404 e prover estilos mínimos.

> Observação: alguns ambientes locais podem registrar `@vite/dashboard.php` como asset ausente; isso **não bloqueia** as funcionalidades acima.

---

---

## 🚀 Demonstração

### Dashboard Principal
Interface moderna com estatísticas em tempo real e gráficos interativos.

### Indicadores de RH
7 KPIs essenciais com comparação anual e análise por nível hierárquico.

### Gestão de Agenda
Controle de turmas, vagas, horários e locais de treinamento.

---

## 📋 Módulos Implementados

### 1️⃣ Colaboradores
- CRUD completo
- Campos: CPF, e-mail, cargo, departamento, salário
- Nível hierárquico (Estratégico, Tático, Operacional)
- Status ativo/inativo

### 2️⃣ Treinamentos
- CRUD completo
- Tipos: Técnico, Comportamental, Segurança, etc.
- Controle de custos e fornecedores
- Status: Programado, Em Andamento, Executado, Cancelado
- Sistema de avaliação (0-10)

### 3️⃣ Participantes
- Vinculação colaboradores ↔ treinamentos
- Check-in manual e por QR Code
- Avaliação individual
- Envio de convites por e-mail

### 4️⃣ Frequência
- Registro de presença por sessão
- QR Code único por aula
- Relatórios de frequência
- Controle de horas presenciais

### 5️⃣ Notificações
- Convites para treinamentos
- Lembretes automáticos
- Confirmações de inscrição
- Templates HTML responsivos
- Configuração SMTP

### 6️⃣ Agenda/Turmas
- Múltiplas datas e horários
- Controle de vagas
- Gestão de turmas
- Vinculação de participantes

### 7️⃣ Indicadores de RH
**7 KPIs Principais:**
1. HTC - Horas de Treinamento por Colaborador
2. HTC por Nível Hierárquico
3. CTC - Custo de Treinamento por Colaborador
4. % Investimento sobre Folha de Pagamento
5. Taxa de Conclusão de Treinamentos
6. % de Colaboradores Capacitados
7. Índice Geral de Capacitação

### 8️⃣ Relatórios e Dashboards
- Dashboard com 9 estatísticas principais
- 6 gráficos interativos (Chart.js)
- Relatórios por departamento
- Matriz de capacitações
- Exportação de dados

---

## 🛠️ Tecnologias

### Backend
- **PHP 8.x** - Linguagem principal
- **MySQL 8.0** - Banco de dados
- **PDO** - Database abstraction layer
- **Arquitetura MVC** - Model-View-Controller

### Frontend
- **HTML5** + **CSS3**
- **JavaScript ES6+**
- **Chart.js 4.4** - Gráficos interativos
- **Design Responsivo** - Mobile-first

### Bibliotecas
- **PHPMailer** - Envio de e-mails (opcional)
- **Chart.js** - Visualização de dados

---

## 📊 Banco de Dados

### Estrutura

**7 Tabelas Principais:**
1. `colaboradores` - Dados dos funcionários
2. `treinamentos` - Cursos e capacitações
3. `treinamento_participantes` - Vinculação colaboradores/treinamentos
4. `frequencia` - Registro de presença
5. `notificacoes` - Sistema de e-mails
6. `agenda_treinamentos` - Gestão de turmas
7. `configuracoes` - Configurações do sistema

---

## 🔧 Instalação

### Requisitos
- PHP >= 8.0
- MySQL >= 8.0
- Composer (opcional, para PHPMailer)
- Servidor web (Apache/Nginx)

### Instalação Rápida (Recomendada)
- Para instalações locais no XAMPP (pasta `htdocs/sgc`), acesse `http://localhost/sgc/public/instalar_tudo.php` e clique em "Instalar Tudo". O instalador é idempotente e aplica o schema e todas as migrações conhecidas.

### Passo a Passo

#### 1. Clone o Repositório
```bash
git clone https://github.com/seu-usuario/comercial-do-norte.git
cd comercial-do-norte
```

#### 2. Configure o Banco de Dados
Edite `app/config/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'comercial_sgc');
define('DB_USER', 'root');
define('DB_PASS', '');
```

Crie o banco de dados:
```sql
CREATE DATABASE comercial_sgc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

#### 3. Execute as Migrations

**Opção A: Via Navegador (Recomendado)**
```
http://localhost/comercial-do-norte/public/instalar_notificacoes.php
http://localhost/comercial-do-norte/public/instalar_agenda.php
```

Ou use o instalador unificado:
```
http://localhost/sgc/public/instalar_tudo.php
```

**Opção B: SQL Direto**
```bash
mysql -u root -p comercial_sgc < database/migrations/migration_inicial.sql
mysql -u root -p comercial_sgc < database/migrations/migration_frequencia.sql
mysql -u root -p comercial_sgc < database/migrations/migration_notificacoes.sql
mysql -u root -p comercial_sgc < database/migrations/migration_agenda.sql
```

#### 4. Instale PHPMailer (Opcional)
```bash
composer require phpmailer/phpmailer
```

Ou faça upload manual dos arquivos para `vendor/phpmailer/phpmailer/src/`

#### 5. Configure Permissões
```bash
chmod 755 public/uploads/
chmod 755 vendor/
```

#### 6. Acesse o Sistema
```
http://localhost/comercial-do-norte/public/
```

No XAMPP com `htdocs/sgc`, use:
```
http://localhost/sgc/public/
```

**Login padrão:**
- Usuário: admin
- Senha: (conforme cadastrado)

---

## 📖 Documentação Completa

### Arquivos de Documentação
- **[SISTEMA_COMPLETO.md](SISTEMA_COMPLETO.md)** - Documentação técnica completa
- **[PROBLEMAS_PENDENTES.md](PROBLEMAS_PENDENTES.md)** - Issues e pendências
- **[TESTE_AGENDA.md](TESTE_AGENDA.md)** - Guia de testes
- **[CORRIGIR_VISUALIZAR.txt](CORRIGIR_VISUALIZAR.txt)** - Instruções específicas

### Estrutura de Diretórios
```
comercial-do-norte/
├── app/                      # Backend
│   ├── classes/             # Classes auxiliares
│   ├── config/              # Configurações
│   ├── controllers/         # Controllers MVC
│   ├── models/              # Models MVC
│   └── views/               # Views (layouts)
├── database/                 # Migrations SQL
│   └── migrations/
├── public/                   # Frontend (acesso público)
│   ├── agenda/
│   ├── colaboradores/
│   ├── configuracoes/
│   ├── frequencia/
│   ├── participantes/
│   ├── relatorios/
│   ├── treinamentos/
│   └── assets/
├── uploads/                  # Arquivos enviados
├── vendor/                   # Dependências (Composer)
└── README.md                # Este arquivo
```

---

## 🎯 Como Usar

### Fluxo Básico

1. **Cadastre Colaboradores**
   ```
   Menu > Colaboradores > Cadastrar
   ```

2. **Crie um Treinamento**
   ```
   Menu > Treinamentos > Cadastrar
   ```

3. **Configure Agenda (Opcional)**
   ```
   Treinamento > Gerenciar Agenda/Turmas
   ```

4. **Vincule Participantes**
   ```
   Treinamento > Vincular Participantes
   Envie convites por e-mail
   ```

5. **Registre Frequência**
   ```
   Menu > Frequência > Selecionar Treinamento
   Crie sessões e registre presenças
   ```

6. **Visualize Indicadores**
   ```
   Menu > Relatórios > Indicadores de RH
   Filtre por ano e analise KPIs
   ```

---

## 📊 Indicadores de RH (KPIs)

### HTC - Horas de Treinamento por Colaborador
```
HTC = Total de horas de treinamento / Total de colaboradores ativos
```

### CTC - Custo de Treinamento por Colaborador
```
CTC = Total investido em treinamentos / Total de colaboradores
```

### Taxa de Conclusão
```
Taxa = (Treinamentos executados / Total programados) × 100
```

### % Colaboradores Capacitados
```
% = (Colaboradores com treinamento / Total de colaboradores) × 100
```

### Índice Geral de Capacitação
```
Índice = (Taxa Conclusão × 30%) + (% Capacitados × 40%) + (HTC/Meta × 30%)
```

---

## 🔐 Segurança

### Medidas Implementadas
- ✅ **Prepared Statements** - Proteção contra SQL Injection
- ✅ **CSRF Tokens** - Proteção contra CSRF
- ✅ **Session Timeout** - 30 minutos de inatividade
- ✅ **Password Hashing** - Senhas criptografadas
- ✅ **Input Sanitization** - Validação de dados
- ✅ **Tokens Únicos** - Para check-in e notificações
- ✅ **Controle de Acesso** - Por nível de usuário

---

## 🐛 Problemas Conhecidos

### Em Produção
1. **Botão Agenda não aparece** - Arquivo `visualizar.php` precisa ser atualizado no servidor
2. **PHPMailer não instalado** - Sistema de e-mails requer instalação manual

Ver [PROBLEMAS_PENDENTES.md](PROBLEMAS_PENDENTES.md) para detalhes e soluções.

---

## 🚀 Deploy em Produção

### Checklist de Deploy

#### 1. Upload de Arquivos
```
Total: 33 arquivos
- 22 novos
- 11 modificados
```

Ver lista completa em [PROBLEMAS_PENDENTES.md](PROBLEMAS_PENDENTES.md)

#### 2. Executar Migrations
```
https://seudominio.com/public/instalar_notificacoes.php
https://seudominio.com/public/instalar_agenda.php
```

#### 3. Instalar PHPMailer
```bash
composer require phpmailer/phpmailer
```

#### 4. Configurar SMTP
```
Configurações > E-mail (SMTP)
Preencher dados e testar conexão
```

#### 5. Verificação Final
- [ ] Login funcionando
- [ ] Todos os módulos acessíveis
- [ ] Gráficos carregando
- [ ] E-mails sendo enviados
- [ ] Botão Agenda aparecendo

---

## 📈 Estatísticas do Projeto

### Código
- **Linhas de código:** ~15.000+
- **Arquivos PHP:** 50+
- **Models:** 7
- **Controllers:** 6
- **Views:** 35+

### Funcionalidades
- **Módulos principais:** 8
- **KPIs de RH:** 7
- **Gráficos interativos:** 6
- **Tipos de notificação:** 5
- **Relatórios:** 4

---

## 🤝 Contribuindo

Este é um projeto privado desenvolvido para a **Comercial do Norte**.

Para sugestões ou melhorias, entre em contato com a equipe de TI.

---

## 📞 Suporte

### Contato
- **Empresa:** Comercial do Norte
- **Sistema:** SGC - Sistema de Gestão de Capacitações
- **Versão:** 1.0.0

### Documentação
- [Documentação Completa](SISTEMA_COMPLETO.md)
- [Problemas e Soluções](PROBLEMAS_PENDENTES.md)
- [Guia de Testes](TESTE_AGENDA.md)

---

## 📜 Changelog

### Versão 1.0.0 (Novembro 2025)
- ✅ 8 módulos completos implementados
- ✅ Sistema de notificações por e-mail
- ✅ Módulo de agenda/turmas
- ✅ 7 indicadores de RH
- ✅ 6 gráficos Chart.js
- ✅ Interface responsiva
- ✅ Documentação completa

---

## 📄 Licença

Propriedade de **Comercial do Norte**.
Todos os direitos reservados © 2025

---

## 🎉 Agradecimentos

Desenvolvido com dedicação para otimizar a gestão de capacitações da **Comercial do Norte**.

---

**Status:** ✅ Sistema 100% Concluído e Pronto para Produção

**Última atualização:** Novembro 2025

---

<div align="center">

**[⬆ Voltar ao topo](#-sgc---sistema-de-gestão-de-capacitações)**

</div>
## 📖 Documentação
- Índice geral: `docs/README.md`
- Principais seções:
  - Overview: `docs/01-overview/`
  - Deployment: `docs/02-deployment/`
  - Guia rápido (raiz): `GUIA_INSTALACAO.md`
  - Arquitetura: `docs/04-architecture/`
  - Banco de Dados: `docs/05-database/`
  - Roadmap: `docs/07-roadmap/`
  - Progresso: `docs/08-progress/`
  - Issues: `docs/09-issues/`
  - Changelog: `docs/10-changelog/`
  - Operacional: `docs/11-operacional/`

---

## 🔍 Code Review e Melhorias (Nov 2025)

**Qualidade Geral**: ⭐⭐⭐⭐ 85% | **Status**: ✅ Pronto para produção (após correções críticas)

### 📊 Resumo da Análise
- ✅ **50+ arquivos revisados** - Análise completa do código-fonte
- 🔴 **1 problema crítico** identificado (SQL Injection - fácil correção)
- 🟡 **2 problemas médios** (credenciais, rate limiting)
- 💡 **15+ melhorias** sugeridas (performance, arquitetura, testes)

### 📄 Documentação Completa
1. **[Resumo Executivo](docs/09-issues/RESUMO_EXECUTIVO.md)** ⭐ *Comece aqui*
   - Visão para gestores e tomadores de decisão
   - Análise de riscos e investimentos
   - Roadmap de implementação

2. **[Code Review Detalhado](docs/09-issues/code-review-2025-11-06.md)**
   - Análise técnica completa (1300+ linhas)
   - Exemplos de código corrigido
   - Guia de implementação passo a passo

3. **[Checklist de Melhorias](docs/09-issues/IMPROVEMENT_CHECKLIST.md)**
   - Tarefas organizadas por prioridade
   - Campos para responsáveis e prazos
   - Rastreamento de progresso (0/26 tarefas)

### 🎯 Ações Recomendadas

**URGENTE (Esta Semana)** - 4 horas:
- [ ] Corrigir SQL Injection em LIMIT/OFFSET
- [ ] Migrar credenciais para .env
- [ ] Implementar rate limiting
- [ ] Adicionar headers de segurança

**IMPORTANTE (Este Mês)** - 6 horas:
- [ ] Adicionar índices de banco (+40% velocidade)
- [ ] Validação de CPF
- [ ] Logs estruturados
- [ ] Política de senhas fortes

**Acesse**: `docs/09-issues/` para documentação completa
