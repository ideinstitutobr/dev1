# PLANO DE DESENVOLVIMENTO - FORMULÁRIOS DINÂMICOS (AJUSTADO)
## Sistema Avançado de Criação de Formulários - Módulo Paralelo

**Versão:** 1.1 (Ajustado para Integração)
**Data:** 09/11/2025
**Arquitetura:** PHP MVC
**Estratégia:** Módulo Paralelo (sem conflitos com sistema de checklists)

---

## 📋 MUDANÇAS EM RELAÇÃO AO PLANO ORIGINAL

### 🔄 Nomenclaturas Ajustadas

Para evitar conflitos com o sistema de checklists existente:

| Original | Ajustado | Motivo |
|----------|----------|--------|
| `formularios` | `formularios_dinamicos` | Clareza e separação |
| `secoes` | `form_secoes` | Prefixo padronizado |
| `perguntas` | `form_perguntas` | **CRÍTICO**: Conflito com tabela existente |
| `opcoes_resposta` | `form_opcoes_resposta` | Prefixo padronizado |
| `respostas` | `form_respostas` | Evitar confusão com `respostas_checklist` |
| `respostas_detalhes` | `form_respostas_detalhes` | Prefixo padronizado |
| `faixas_pontuacao` | `form_faixas_pontuacao` | Prefixo padronizado |
| `compartilhamentos` | `form_compartilhamentos` | Prefixo padronizado |
| `usuarios` | `usuarios_sistema` | **JÁ EXISTE** no sistema |

### 📂 Estrutura de Pastas Ajustada

```
public/
├── checklist/                    # SISTEMA ANTIGO (não mexer)
│   ├── diario/
│   └── quinzenal/
│
├── formularios-dinamicos/        # SISTEMA NOVO ⭐
│   ├── index.php                 # Listar formulários
│   ├── criar.php                 # Criar novo
│   ├── editar.php                # Editar formulário
│   ├── builder/                  # Builder visual
│   │   ├── canvas.php
│   │   └── componentes.php
│   ├── responder/                # Frontend público
│   │   └── index.php
│   ├── relatorios/               # Relatórios e analytics
│   │   ├── dashboard.php
│   │   └── graficos.php
│   └── api/                      # Endpoints AJAX
│       ├── secoes.php
│       ├── perguntas.php
│       └── opcoes.php

app/
├── controllers/
│   ├── ChecklistController.php           # Mantém (antigo)
│   ├── FormularioDinamicoController.php  # NOVO ⭐
│   ├── FormSecaoController.php           # NOVO ⭐
│   ├── FormPerguntaController.php        # NOVO ⭐
│   ├── FormRespostaController.php        # NOVO ⭐
│   ├── FormRelatorioController.php       # NOVO ⭐
│   └── FormExportacaoController.php      # NOVO ⭐
│
├── models/
│   ├── Checklist.php                     # Mantém (antigo)
│   ├── Pergunta.php                      # Mantém (antigo)
│   ├── FormularioDinamico.php            # NOVO ⭐
│   ├── FormSecao.php                     # NOVO ⭐
│   ├── FormPergunta.php                  # NOVO ⭐
│   ├── FormOpcaoResposta.php             # NOVO ⭐
│   ├── FormResposta.php                  # NOVO ⭐
│   └── FormFaixaPontuacao.php            # NOVO ⭐
│
└── helpers/
    ├── PontuacaoHelper.php               # Mantém (será estendido)
    ├── FormularioHelper.php              # NOVO ⭐
    ├── ValidationHelper.php              # NOVO ⭐
    └── ChartHelper.php                   # NOVO ⭐
```

---

## 🗄️ MODELAGEM DO BANCO DE DADOS AJUSTADA

### Tabelas do Novo Sistema (Prefixo `form_`)

#### 1. formularios_dinamicos
```sql
CREATE TABLE formularios_dinamicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    slug VARCHAR(255) UNIQUE NOT NULL,
    usuario_id INT NOT NULL,
    status ENUM('rascunho', 'ativo', 'inativo', 'arquivado') DEFAULT 'rascunho',
    tipo_pontuacao ENUM('soma_simples', 'media_ponderada', 'percentual') DEFAULT 'soma_simples',
    pontuacao_maxima DECIMAL(10,2) DEFAULT 0,
    exibir_pontuacao BOOLEAN DEFAULT TRUE,
    permite_multiplas_respostas BOOLEAN DEFAULT FALSE,
    data_inicio DATETIME,
    data_fim DATETIME,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios_sistema(id) ON DELETE CASCADE,
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 2. form_secoes
```sql
CREATE TABLE form_secoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formulario_id INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    ordem INT NOT NULL DEFAULT 0,
    peso DECIMAL(5,2) DEFAULT 1.00,
    cor VARCHAR(7) DEFAULT '#007bff',
    icone VARCHAR(50),
    visivel BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (formulario_id) REFERENCES formularios_dinamicos(id) ON DELETE CASCADE,
    INDEX idx_formulario_ordem (formulario_id, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 3. form_perguntas (⭐ NOME AJUSTADO)
```sql
CREATE TABLE form_perguntas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    secao_id INT NOT NULL,
    tipo_pergunta ENUM(
        'texto_curto', 'texto_longo', 'multipla_escolha',
        'caixas_selecao', 'lista_suspensa', 'escala_linear',
        'grade_multipla', 'data', 'hora', 'arquivo'
    ) NOT NULL,
    pergunta TEXT NOT NULL,
    descricao TEXT,
    ordem INT NOT NULL DEFAULT 0,
    obrigatoria BOOLEAN DEFAULT FALSE,
    peso DECIMAL(5,2) DEFAULT 1.00,
    pontuacao_maxima DECIMAL(10,2) DEFAULT 0,
    tem_pontuacao BOOLEAN DEFAULT FALSE,
    config_adicional JSON,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (secao_id) REFERENCES form_secoes(id) ON DELETE CASCADE,
    INDEX idx_secao_ordem (secao_id, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 4. form_opcoes_resposta
```sql
CREATE TABLE form_opcoes_resposta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pergunta_id INT NOT NULL,
    texto_opcao VARCHAR(500) NOT NULL,
    ordem INT NOT NULL DEFAULT 0,
    pontuacao DECIMAL(10,2) DEFAULT 0,
    vai_para_secao INT NULL,
    vai_para_pergunta INT NULL,
    cor VARCHAR(7),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pergunta_id) REFERENCES form_perguntas(id) ON DELETE CASCADE,
    FOREIGN KEY (vai_para_secao) REFERENCES form_secoes(id) ON DELETE SET NULL,
    FOREIGN KEY (vai_para_pergunta) REFERENCES form_perguntas(id) ON DELETE SET NULL,
    INDEX idx_pergunta_ordem (pergunta_id, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 5. form_respostas
```sql
CREATE TABLE form_respostas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formulario_id INT NOT NULL,
    respondente_email VARCHAR(255),
    respondente_nome VARCHAR(255),
    respondente_ip VARCHAR(45),
    pontuacao_total DECIMAL(10,2) DEFAULT 0,
    percentual_acerto DECIMAL(5,2) DEFAULT 0,
    status_resposta ENUM('em_andamento', 'concluida', 'incompleta') DEFAULT 'em_andamento',
    tempo_resposta INT COMMENT 'em segundos',
    iniciado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    concluido_em TIMESTAMP NULL,
    FOREIGN KEY (formulario_id) REFERENCES formularios_dinamicos(id) ON DELETE CASCADE,
    INDEX idx_formulario (formulario_id),
    INDEX idx_email (respondente_email),
    INDEX idx_status (status_resposta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 6. form_respostas_detalhes
```sql
CREATE TABLE form_respostas_detalhes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resposta_id INT NOT NULL,
    pergunta_id INT NOT NULL,
    opcao_id INT NULL,
    valor_texto TEXT,
    valor_numero DECIMAL(10,2),
    valor_data DATE,
    arquivo_path VARCHAR(500),
    pontuacao_obtida DECIMAL(10,2) DEFAULT 0,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (resposta_id) REFERENCES form_respostas(id) ON DELETE CASCADE,
    FOREIGN KEY (pergunta_id) REFERENCES form_perguntas(id) ON DELETE CASCADE,
    FOREIGN KEY (opcao_id) REFERENCES form_opcoes_resposta(id) ON DELETE SET NULL,
    INDEX idx_resposta (resposta_id),
    INDEX idx_pergunta (pergunta_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 7. form_faixas_pontuacao
```sql
CREATE TABLE form_faixas_pontuacao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formulario_id INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    pontuacao_minima DECIMAL(10,2) NOT NULL,
    pontuacao_maxima DECIMAL(10,2) NOT NULL,
    percentual_minimo DECIMAL(5,2),
    percentual_maximo DECIMAL(5,2),
    mensagem TEXT,
    recomendacoes TEXT,
    cor VARCHAR(7) DEFAULT '#28a745',
    ordem INT DEFAULT 0,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (formulario_id) REFERENCES formularios_dinamicos(id) ON DELETE CASCADE,
    INDEX idx_formulario (formulario_id),
    INDEX idx_pontuacao (pontuacao_minima, pontuacao_maxima)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 8. form_compartilhamentos
```sql
CREATE TABLE form_compartilhamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formulario_id INT NOT NULL,
    usuario_id INT NOT NULL,
    nivel_permissao ENUM('visualizar', 'editar', 'gerenciar') DEFAULT 'visualizar',
    compartilhado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (formulario_id) REFERENCES formularios_dinamicos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios_sistema(id) ON DELETE CASCADE,
    UNIQUE KEY uk_form_user (formulario_id, usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 🚀 CRONOGRAMA DE DESENVOLVIMENTO (15 SEMANAS)

### SPRINT 1: Fundação (2 semanas)
```
Semana 1:
☐ Criar branch Git: feature/formularios-dinamicos
☐ Criar estrutura de pastas /public/formularios-dinamicos/
☐ Executar script SQL de criação das tabelas form_*
☐ Atualizar composer.json (adicionar mPDF)
☐ Criar BaseController e FormularioDinamicoController

Semana 2:
☐ Criar Models: FormularioDinamico, FormSecao, FormPergunta
☐ Criar layout master específico para formulários
☐ Criar página de listagem (/formularios-dinamicos/index.php)
☐ Implementar CRUD básico de formulários
☐ Testes unitários dos Models
```

### SPRINT 2: Builder de Formulários (3 semanas)
```
Semana 3:
☐ Interface HTML/CSS do builder
☐ Criar FormSecaoController
☐ CRUD de seções com AJAX
☐ Implementar drag-and-drop (SortableJS)

Semana 4:
☐ Criar FormPerguntaController
☐ Implementar 5 tipos básicos de pergunta:
  - texto_curto
  - texto_longo
  - multipla_escolha
  - caixas_selecao
  - lista_suspensa
☐ Sistema de preview em tempo real

Semana 5:
☐ Implementar 5 tipos avançados:
  - escala_linear
  - grade_multipla
  - data
  - hora
  - arquivo
☐ Sistema de opções de resposta (FormOpcaoResposta)
☐ Validações frontend e backend
```

### SPRINT 3: Sistema de Pontuação (2 semanas)
```
Semana 6:
☐ Criar FormFaixaPontuacao Model
☐ Interface de configuração de pesos (seção/pergunta)
☐ Pontuação por opção de resposta
☐ Lógica de cálculo (3 tipos)

Semana 7:
☐ CRUD de faixas de pontuação
☐ Mensagens e recomendações por faixa
☐ Testes de cálculo de pontuação
☐ Estender PontuacaoHelper para formulários dinâmicos
```

### SPRINT 4: Frontend Público (2 semanas)
```
Semana 8:
☐ Layout público (/formularios-dinamicos/responder/)
☐ Carregamento dinâmico de seções via AJAX
☐ Validação em tempo real
☐ Sistema de salvamento de progresso

Semana 9:
☐ Upload de arquivos seguro
☐ Lógica condicional (ir para seção X)
☐ Página de resultado com pontuação e faixa
☐ Email de confirmação (PHPMailer)
```

### SPRINT 5: Relatórios e Gráficos (3 semanas)
```
Semana 10:
☐ Criar FormRelatorioController
☐ Listagem de respostas com filtros avançados
☐ Visualização individual de resposta
☐ Dashboard com métricas principais

Semana 11:
☐ Integrar Chart.js
☐ Criar ChartHelper
☐ Implementar 6 tipos de gráficos:
  - Pizza (distribuição)
  - Barras (comparação)
  - Linha (evolução)
  - Radar (performance)
  - Histograma (frequência)
  - Funil (taxa de conclusão)

Semana 12:
☐ Análise por seção
☐ Análise por pergunta
☐ Relatórios pré-configurados
☐ Filtros de data/faixa/status
```

### SPRINT 6: Exportação e Finalizações (2 semanas)
```
Semana 13:
☐ Criar FormExportacaoController
☐ Exportação Excel (PHPSpreadsheet)
☐ Exportação PDF (mPDF - novo)
☐ Exportação CSV
☐ Exportação JSON

Semana 14:
☐ Sistema de compartilhamento
☐ Ajustes de UI/UX
☐ Otimizações de performance
☐ Documentação de usuário
☐ Documentação técnica
```

### SPRINT 7: Testes e Deploy (1 semana)
```
Semana 15:
☐ Testes de integração
☐ Testes de carga (100 formulários simultâneos)
☐ Testes de segurança (XSS, SQL Injection, CSRF)
☐ Correção de bugs críticos
☐ Deploy em staging
☐ Deploy em produção
```

---

## 📦 DEPENDÊNCIAS COMPOSER ATUALIZADAS

Arquivo: `/home/user/dev1/composer.json`

```json
{
  "require": {
    "phpmailer/phpmailer": "^6.8",
    "phpoffice/phpspreadsheet": "^1.29",
    "tecnickcom/tcpdf": "^6.6",
    "mpdf/mpdf": "^8.2",
    "guzzlehttp/guzzle": "^7.8",
    "vlucas/phpdotenv": "^5.5"
  },
  "require-dev": {
    "phpunit/phpunit": "^10.0"
  }
}
```

**Executar após edição:**
```bash
composer update
```

---

## 🔐 SEGURANÇA MANTIDA

Todas as medidas de segurança do sistema atual serão mantidas:

- ✅ PDO com Prepared Statements
- ✅ Hash bcrypt (cost 12)
- ✅ CSRF tokens em todos os formulários
- ✅ Sessões HttpOnly e Secure
- ✅ Validação de MIME types em uploads
- ✅ Sanitização de HTML (htmlspecialchars)
- ✅ Rate limiting

**Novas adições:**
- ✅ Validação de JSON schema para config_adicional
- ✅ Proteção contra IDOR (verificar propriedade do formulário)
- ✅ Logs de auditoria para ações críticas

---

## 🎯 ROTAS DO SISTEMA

### Formulários Dinâmicos
```
/formularios-dinamicos                           [GET]      Listar
/formularios-dinamicos/criar                     [GET, POST] Criar
/formularios-dinamicos/{id}/editar               [GET, POST] Editar
/formularios-dinamicos/{id}/duplicar             [POST]      Duplicar
/formularios-dinamicos/{id}/excluir              [POST]      Excluir
/formularios-dinamicos/{id}/builder              [GET]       Builder visual

// Responder
/formularios-dinamicos/responder/{slug}          [GET]       Iniciar
/formularios-dinamicos/responder/{slug}/secao/{n}[GET]       Navegar
/formularios-dinamicos/responder/{slug}/salvar   [POST]      Salvar progresso
/formularios-dinamicos/responder/{slug}/enviar   [POST]      Finalizar
/formularios-dinamicos/responder/{slug}/resultado[GET]       Ver resultado

// Relatórios
/formularios-dinamicos/{id}/relatorios           [GET]       Dashboard
/formularios-dinamicos/{id}/respostas            [GET]       Listar respostas
/formularios-dinamicos/{id}/respostas/{rid}      [GET]       Ver resposta
/formularios-dinamicos/{id}/graficos             [GET]       Gráficos

// Exportação
/formularios-dinamicos/{id}/exportar/excel       [POST]      Excel
/formularios-dinamicos/{id}/exportar/pdf         [POST]      PDF
/formularios-dinamicos/{id}/exportar/csv         [POST]      CSV
/formularios-dinamicos/{id}/exportar/json        [POST]      JSON

// API AJAX
/formularios-dinamicos/api/secoes/criar          [POST]
/formularios-dinamicos/api/secoes/{id}/atualizar [PUT]
/formularios-dinamicos/api/secoes/reordenar      [POST]
/formularios-dinamicos/api/perguntas/criar       [POST]
/formularios-dinamicos/api/perguntas/{id}/atualizar[PUT]
/formularios-dinamicos/api/perguntas/reordenar   [POST]
/formularios-dinamicos/api/opcoes/criar          [POST]
/formularios-dinamicos/api/opcoes/{id}/atualizar [PUT]
```

### Sistema Antigo (MANTER INTOCADO)
```
/checklist/diario/                               Sistema existente
/checklist/quinzenal/                            Sistema existente
/gestao/modulos/                                 Sistema existente
/gestao/perguntas/                               Sistema existente
```

---

## 📊 ESTIMATIVA ATUALIZADA

### Horas de Desenvolvimento
```
Fundação:                     80 horas
Builder:                      80 horas
Pontuação:                    40 horas
Frontend Público:             50 horas
Relatórios e Gráficos:        60 horas
Exportação:                   40 horas
UI/UX:                        40 horas
Testes:                       40 horas
Documentação:                 20 horas
Buffer (ajustes nomenclatura): 10 horas
──────────────────────────────────────
TOTAL:                        460 horas
```

### Custo Estimado
```
Dev Backend (R$ 100/h):    R$ 46.000
Dev Frontend (R$ 80/h):    R$ 36.800
Designer (R$ 70/h):        R$ 14.000
QA (R$ 60/h):              R$ 12.000
Infraestrutura:            R$  2.000
Ferramentas:               R$  3.000
Buffer (10%):              R$ 11.400
──────────────────────────────────────
TOTAL:                     R$ 125.200
```

---

## ✅ CHECKLIST DE PREPARAÇÃO (ANTES DE COMEÇAR)

### Ambiente
- [ ] Git branch criada: `feature/formularios-dinamicos`
- [ ] Backup completo do banco de dados atual
- [ ] Ambiente de staging configurado
- [ ] composer.json atualizado com mPDF

### Banco de Dados
- [ ] Script SQL criado: `/database/migrations/020_criar_formularios_dinamicos.sql`
- [ ] Script executado em staging
- [ ] Verificar que tabelas `form_*` foram criadas
- [ ] Verificar que não afetou tabelas existentes

### Código
- [ ] Pasta `/public/formularios-dinamicos/` criada
- [ ] Pasta `/app/controllers/Form*` preparada
- [ ] Pasta `/app/models/Form*` preparada
- [ ] Pasta `/app/helpers/` com novos helpers

### Documentação
- [ ] README atualizado com novo módulo
- [ ] Documentação técnica iniciada
- [ ] Guia de instalação atualizado

---

## 🎯 PRÓXIMO PASSO IMEDIATO

**EXECUTAR AGORA:**

1. Criar branch Git
2. Executar script SQL de criação das tabelas
3. Criar estrutura de pastas
4. Criar Models base
5. Criar Controllers base
6. Testar que sistema antigo continua funcionando

---

## 📝 OBSERVAÇÕES IMPORTANTES

### ⚠️ Regras de Ouro

1. **NUNCA** alterar arquivos do sistema de checklists
2. **SEMPRE** usar prefixo `form_` nas novas tabelas
3. **SEMPRE** usar namespace `FormularioDinamico*` nos Controllers/Models
4. **SEMPRE** testar que sistema antigo não foi afetado
5. **SEMPRE** fazer backup antes de migrações SQL

### 🔄 Convivência dos Sistemas

Durante o desenvolvimento e período inicial, **dois sistemas** conviverão:

**Menu Principal:**
```
Dashboard
├── Checklists (sistema antigo)
│   ├── Diários
│   └── Quinzenais/Mensais
├── Formulários Dinâmicos (sistema novo) ⭐
│   ├── Meus Formulários
│   ├── Criar Novo
│   ├── Respostas
│   └── Relatórios
└── Outros módulos...
```

---

## 📞 SUPORTE

Se encontrar qualquer conflito ou dúvida durante a implementação:

1. Verificar que está usando nomenclatura ajustada
2. Confirmar que tabelas têm prefixo `form_`
3. Testar sistema antigo antes de prosseguir
4. Consultar esta documentação

---

**Status:** ✅ Aprovado para implementação
**Próxima Ação:** Executar scripts de preparação (criar tabelas, pastas, arquivos base)
**Responsável:** Equipe de desenvolvimento

---

*Fim do Plano Ajustado*
