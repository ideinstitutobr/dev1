# GUIA DE IMPLEMENTAÇÃO - FORMULÁRIOS DINÂMICOS
## Passo a Passo para Executar a Preparação

**Data:** 09/11/2025
**Sistema:** SGC - Sistema de Gestão de Capacitações
**Módulo:** Formulários Dinâmicos
**Estratégia:** Módulo Paralelo (zero impacto no sistema atual)

---

## ✅ CHECKLIST DE PREPARAÇÃO CONCLUÍDA

Os seguintes itens já foram preparados e estão prontos:

- [x] Plano de desenvolvimento ajustado
- [x] Scripts SQL de criação das tabelas `form_*`
- [x] Estrutura de diretórios criada
- [x] Models base criados (FormularioDinamico, FormSecao, FormPergunta, FormOpcaoResposta)
- [x] Controller base criado (FormularioDinamicoController)
- [x] Arquivo `index.php` do módulo criado
- [x] Composer.json atualizado com mPDF

---

## 🚀 PRÓXIMOS PASSOS - AÇÕES NECESSÁRIAS

### PASSO 1: Criar Branch Git

```bash
cd /home/user/dev1
git checkout -b feature/formularios-dinamicos
```

**Objetivo**: Isolar o desenvolvimento em uma branch separada para facilitar rollback se necessário.

---

### PASSO 2: Backup do Banco de Dados

**CRÍTICO**: Faça backup antes de executar qualquer migração SQL.

```bash
# Criar diretório de backups se não existir
mkdir -p /home/user/dev1/database/backups

# Fazer backup (ajuste as credenciais conforme app/config/database.php)
mysqldump -h localhost -u u411458227_comercial255 -p u411458227_comercial255 > /home/user/dev1/database/backups/backup_pre_formularios_$(date +%Y%m%d_%H%M%S).sql
```

---

### PASSO 3: Executar Migração SQL

Este script criará todas as 8 tabelas do novo sistema sem afetar as existentes.

**Opção A: Via MySQL Client**
```bash
mysql -h localhost -u u411458227_comercial255 -p u411458227_comercial255 < /home/user/dev1/database/migrations/020_criar_formularios_dinamicos.sql
```

**Opção B: Via PHPMyAdmin**
1. Acesse PHPMyAdmin
2. Selecione database `u411458227_comercial255`
3. Vá em "SQL"
4. Cole o conteúdo de `/database/migrations/020_criar_formularios_dinamicos.sql`
5. Execute

**Opção C: Via script PHP executar_migracao.php**
```bash
php /home/user/dev1/database/executar_migracao.php 020_criar_formularios_dinamicos.sql
```

**Verificação Pós-Execução:**
```sql
-- Verificar tabelas criadas
SHOW TABLES LIKE 'form%';
SHOW TABLES LIKE 'formularios_dinamicos';

-- Deve retornar 8 tabelas:
-- 1. formularios_dinamicos
-- 2. form_secoes
-- 3. form_perguntas
-- 4. form_opcoes_resposta
-- 5. form_respostas
-- 6. form_respostas_detalhes
-- 7. form_faixas_pontuacao
-- 8. form_compartilhamentos

-- Verificar se formulário de exemplo foi criado
SELECT * FROM formularios_dinamicos;
```

---

### PASSO 4: Atualizar Dependências Composer

```bash
cd /home/user/dev1
composer update
```

Isso instalará o mPDF (nova dependência para exportação de PDF).

**Verificação:**
```bash
composer show mpdf/mpdf
# Deve mostrar a versão instalada
```

---

### PASSO 5: Verificar Sistema Antigo (CRÍTICO)

**Objetivo**: Garantir que o sistema de checklists não foi afetado.

```bash
# Acessar via navegador:
https://dev1.ideinstituto.com.br/public/checklist/diario/
https://dev1.ideinstituto.com.br/public/checklist/quinzenal/
https://dev1.ideinstituto.com.br/public/gestao/modulos/
```

**Testes:**
- [ ] Listar checklists diários funciona
- [ ] Listar checklists quinzenais funciona
- [ ] Criar novo checklist funciona
- [ ] Gerenciar módulos funciona
- [ ] Gerenciar perguntas funciona

Se QUALQUER um desses testes falhar, **PARE IMEDIATAMENTE** e:
1. Restaure o backup do banco de dados
2. Revise o script SQL
3. Investigue o problema antes de prosseguir

---

### PASSO 6: Atualizar Menu de Navegação

Adicionar link para o novo módulo no menu principal.

**Arquivo**: `/home/user/dev1/app/views/layouts/sidebar.php`

Adicionar após a seção de Checklists:

```php
<!-- Formulários Dinâmicos (NOVO) -->
<li class="nav-item">
    <a class="nav-link" href="#formulariosDinamicosSubmenu" data-bs-toggle="collapse">
        <i class="fas fa-file-alt"></i>
        Formulários Dinâmicos
        <span class="badge bg-success">NOVO</span>
    </a>
    <ul class="collapse list-unstyled" id="formulariosDinamicosSubmenu">
        <li>
            <a href="/public/formularios-dinamicos/index.php">
                <i class="fas fa-list"></i> Meus Formulários
            </a>
        </li>
        <li>
            <a href="/public/formularios-dinamicos/criar.php">
                <i class="fas fa-plus"></i> Criar Novo
            </a>
        </li>
    </ul>
</li>
```

---

### PASSO 7: Criar Arquivos Faltantes

Alguns arquivos ainda precisam ser criados para completar o CRUD básico:

#### 7.1 Criar `/public/formularios-dinamicos/criar.php`

```php
<?php
/**
 * Formulários Dinâmicos - Criar Novo
 */
session_start();
require_once __DIR__ . '/../../app/classes/Auth.php';

$auth = new Auth();
if (!$auth->verificarAutenticacao()) {
    header('Location: /public/index.php?erro=acesso_negado');
    exit;
}

// Por enquanto, redirecionar para placeholder
echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <title>Criar Formulário - Em Desenvolvimento</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
    <div class='container mt-5'>
        <div class='alert alert-info text-center'>
            <h1><i class='fas fa-construction'></i> Em Desenvolvimento</h1>
            <p>O builder de formulários será implementado no Sprint 2 (Semanas 3-5)</p>
            <a href='/public/formularios-dinamicos/index.php' class='btn btn-primary'>Voltar</a>
        </div>
    </div>
</body>
</html>";
```

#### 7.2 Criar `/public/formularios-dinamicos/editar.php`

```php
<?php
/**
 * Formulários Dinâmicos - Editar
 */
session_start();
require_once __DIR__ . '/../../app/classes/Auth.php';

$auth = new Auth();
if (!$auth->verificarAutenticacao()) {
    header('Location: /public/index.php?erro=acesso_negado');
    exit;
}

$id = $_GET['id'] ?? null;

echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <title>Editar Formulário - Em Desenvolvimento</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
    <div class='container mt-5'>
        <div class='alert alert-info text-center'>
            <h1><i class='fas fa-construction'></i> Em Desenvolvimento</h1>
            <p>Editor de formulário ID: {$id}</p>
            <p>O editor será implementado no Sprint 2 (Semanas 3-5)</p>
            <a href='/public/formularios-dinamicos/index.php' class='btn btn-primary'>Voltar</a>
        </div>
    </div>
</body>
</html>";
```

---

### PASSO 8: Testar Acesso ao Novo Módulo

Acessar via navegador:

```
https://dev1.ideinstituto.com.br/public/formularios-dinamicos/
```

**Resultado Esperado:**
- [x] Página carrega sem erros
- [x] Lista o formulário de exemplo ("Formulário de Exemplo")
- [x] Botão "Criar Formulário" aparece
- [x] Filtros funcionam
- [x] Ao clicar em "Editar", mostra página "Em Desenvolvimento"

---

### PASSO 9: Executar Testes de Integração

```bash
cd /home/user/dev1

# Testar que Models funcionam
php -r "
require_once 'app/models/FormularioDinamico.php';
\$model = new FormularioDinamico();
\$formulario = \$model->buscarPorId(1);
echo 'Formulário de exemplo: ' . \$formulario['titulo'] . PHP_EOL;
"

# Deve retornar: Formulário de exemplo: Formulário de Exemplo
```

---

### PASSO 10: Commit das Mudanças

```bash
git add .
git commit -m "feat: preparação inicial do módulo de Formulários Dinâmicos

- Criadas tabelas do banco de dados (form_*)
- Criados Models base (FormularioDinamico, FormSecao, FormPergunta, FormOpcaoResposta)
- Criado Controller base (FormularioDinamicoController)
- Criada estrutura de diretórios
- Atualizado composer.json com mPDF
- Sistema de checklists não foi afetado

Referência: Sprint 1 do Plano de Formulários Dinâmicos"

git log -1 --stat
```

---

## 📊 STATUS APÓS CONCLUSÃO DOS 10 PASSOS

### ✅ Pronto para Uso

- [x] Banco de dados estruturado
- [x] Models funcionais
- [x] Controller funcional
- [x] Listagem de formulários
- [x] Sistema antigo preservado
- [x] Branch Git criada
- [x] Dependências instaladas

### 🚧 Pendente (Sprints Futuros)

- [ ] Builder visual (Sprint 2)
- [ ] Editor de formulários (Sprint 2)
- [ ] Sistema de pontuação (Sprint 3)
- [ ] Frontend público (Sprint 4)
- [ ] Relatórios e gráficos (Sprint 5)
- [ ] Exportação (Sprint 6)

---

## 🎯 PRÓXIMO SPRINT (Semanas 3-5)

Após concluir esta preparação, você estará pronto para:

**Sprint 2: Builder de Formulários**
1. Interface HTML/CSS do builder
2. CRUD de seções com drag-and-drop (SortableJS)
3. Implementar 10 tipos de perguntas
4. Sistema de preview em tempo real
5. Validações frontend e backend

---

## 🆘 TROUBLESHOOTING

### Erro: "Table 'formularios_dinamicos' doesn't exist"

**Causa**: Script SQL não foi executado
**Solução**: Executar PASSO 3 novamente

### Erro: "Class 'FormularioDinamico' not found"

**Causa**: Autoload do Composer não atualizado
**Solução**:
```bash
composer dump-autoload
```

### Erro: "Class 'Mpdf\Mpdf' not found"

**Causa**: Composer update não foi executado
**Solução**: Executar PASSO 4 novamente

### Sistema de checklists parou de funcionar

**CRÍTICO - AÇÃO IMEDIATA:**
```bash
# Restaurar backup
mysql -h localhost -u u411458227_comercial255 -p u411458227_comercial255 < /home/user/dev1/database/backups/backup_pre_formularios_*.sql

# Investigar o que deu errado
# Reportar problema antes de prosseguir
```

---

## 📝 VALIDAÇÃO FINAL

Antes de considerar a preparação concluída, execute esta checklist:

```
Sistema Antigo:
[ ] Checklists diários funcionam normalmente
[ ] Checklists quinzenais funcionam normalmente
[ ] Gestão de módulos funciona
[ ] Gestão de perguntas funciona
[ ] Nenhum erro no console do navegador
[ ] Nenhum erro nos logs PHP

Sistema Novo:
[ ] 8 tabelas form_* existem no banco
[ ] Formulário de exemplo existe (ID 1)
[ ] Página /formularios-dinamicos/ carrega
[ ] Lista mostra o formulário de exemplo
[ ] Botões de ação aparecem
[ ] Sem erros 404 ou 500

Ambiente:
[ ] Branch Git criada
[ ] Backup do banco foi feito
[ ] Composer atualizado (mPDF instalado)
[ ] Models carregam sem erro
[ ] Controller carrega sem erro
```

---

## ✅ CONCLUSÃO

Após executar todos os 10 passos e validar o checklist final, você terá:

1. ✅ **Base sólida** para desenvolvimento dos próximos sprints
2. ✅ **Sistema antigo preservado** e funcionando
3. ✅ **Módulo novo isolado** e sem riscos
4. ✅ **Infraestrutura pronta** para builder visual
5. ✅ **Código versionado** no Git para rollback fácil

**Tempo estimado para executar todos os passos:** 2-3 horas

**Próximo documento a consultar:** `PLANO_FORMULARIOS_DINAMICOS_AJUSTADO.md` (Sprint 2)

---

**Status**: ⏳ Aguardando execução
**Responsável**: Equipe de desenvolvimento
**Validador**: Tech Lead / Gerente de Projeto

---

*Fim do Guia de Implementação*
