# Sistema de Gestão de Capacitações (SGC)

## 📋 Índice

1. [Visão Geral do Projeto](#visão-geral-do-projeto)
2. [Requisitos Técnicos](#requisitos-técnicos)
3. [Arquitetura do Sistema](#arquitetura-do-sistema)
4. [Estrutura do Banco de Dados](#estrutura-do-banco-de-dados)
5. [Módulos do Sistema](#módulos-do-sistema)
6. [Implementações de Código](#implementações-de-código)
7. [Estrutura de Diretórios](#estrutura-de-diretórios)
8. [Cronograma de Desenvolvimento](#cronograma-de-desenvolvimento)
9. [Guia de Implementação](#guia-de-implementação)

---

## Visão Geral do Projeto

### Objetivo
Desenvolver um sistema web em PHP para gerenciar matriz de treinamentos corporativos, controlar participantes, calcular indicadores de RH e integrar com usuários do WordPress existente.

### Funcionalidades Principais
- ✅ Gestão completa de colaboradores
- ✅ Integração com WordPress (importação de usuários)
- ✅ Matriz de capacitações com 12 campos específicos
- ✅ Sistema de notificações e check-in
- ✅ Controle de frequência
- ✅ Relatórios gerenciais com indicadores de RH
- ✅ Dashboard visual

### Campos da Matriz de Capacitações

1. **Nome do Treinamento** - Identificação do curso
2. **Tipo** - Categorias: Normativos, Comportamentais, Técnicos
3. **Componente do P.E.** - Clientes, Financeiro, Processos Internos, Aprendizagem e Crescimento
4. **Programa** - PGR, Líderes em Transformação, Crescer, Gerais
5. **O Que (Objetivo)** - Campo texto com objetivo
6. **Resultados** - Campo texto com resultados esperados
7. **Por Que (Justificativa)** - Campo texto com justificativa
8. **Quando** - Data/período com horários de início e fim
9. **Quem (Participantes)** - Vinculação de colaboradores
10. **Frequência de Participantes** - Controle de presença e notificações
11. **Quanto (Valor)** - Custo em reais com previsão por período
12. **Status** - Programado, Executado, Pendente

---

## Requisitos Técnicos

### Stack Tecnológico

```yaml
Backend:
  - PHP: "8.1+"
  - PDO: "Para conexão com banco de dados"
  - Composer: "Gerenciador de dependências"

Banco de Dados:
  - MySQL: "8.0+"
  - InnoDB: "Engine para transações"

Frontend:
  - HTML5: "Estrutura"
  - CSS3: "Estilização"
  - JavaScript: "Interatividade (Vanilla ou Vue.js)"
  - Bootstrap: "5.3+ (opcional para UI responsiva)"

Bibliotecas PHP:
  - PHPMailer: "Envio de e-mails"
  - PhpSpreadsheet: "Geração de relatórios Excel"
  - TCPDF ou FPDF: "Geração de PDFs"
  - Guzzle: "HTTP Client para API WordPress (opcional)"

Integração:
  - WordPress REST API: "Importação de usuários"

Servidor Web:
  - Apache: "2.4+ com mod_rewrite"
  - Nginx: "1.18+ (alternativa)"

Ambiente de Desenvolvimento:
  - XAMPP, WAMP ou Docker
```

### Dependências (composer.json)

```json
{
    "require": {
        "php": ">=8.1",
        "phpmailer/phpmailer": "^6.8",
        "phpoffice/phpspreadsheet": "^1.29",
        "tecnickcom/tcpdf": "^6.6",
        "guzzlehttp/guzzle": "^7.8"
    }
}
```

---

## Arquitetura do Sistema

### Padrão de Desenvolvimento

```
Arquitetura: MVC (Model-View-Controller)
Padrão de Projeto: Factory, Singleton, Repository
Organização: PSR-4 (Autoloading)
```

### Camadas da Aplicação

```
┌─────────────────────────────────────┐
│         CAMADA DE VISÃO             │
│  (Views - HTML/CSS/JavaScript)      │
└─────────────────────────────────────┘
              ↓ ↑
┌─────────────────────────────────────┐
│      CAMADA DE CONTROLE             │
│  (Controllers - Lógica de Negócio)  │
└─────────────────────────────────────┘
              ↓ ↑
┌─────────────────────────────────────┐
│       CAMADA DE MODELO              │
│  (Models - Acesso a Dados)          │
└─────────────────────────────────────┘
              ↓ ↑
┌─────────────────────────────────────┐
│      BANCO DE DADOS (MySQL)         │
└─────────────────────────────────────┘
```

---

## Estrutura do Banco de Dados

### Diagrama ER (Entidade-Relacionamento)

```
colaboradores (1) ──────── (N) treinamento_participantes
                                        │
                                        │ (N)
                                        │
                                        ↓
treinamentos (1) ────────── (N) treinamento_participantes
       │                              │
       │ (1)                          │ (1)
       │                              │
       ↓ (N)                          ↓ (N)
agenda_treinamentos          frequencia_treinamento
                                        │
                                        │ (N)
                                        ↓ (1)
                              agenda_treinamentos
```

### Script SQL Completo

```sql
-- =====================================================
-- SISTEMA DE GESTÃO DE CAPACITAÇÕES (SGC)
-- Versão: 1.0
-- Data: 2025-11-03
-- =====================================================

-- Criação do Database
CREATE DATABASE IF NOT EXISTS sgc_treinamentos 
    DEFAULT CHARACTER SET utf8mb4 
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE sgc_treinamentos;

-- =====================================================
-- TABELA: colaboradores
-- Descrição: Armazena dados dos colaboradores/funcionários
-- =====================================================
CREATE TABLE colaboradores (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(200) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    cpf VARCHAR(14) UNIQUE,
    nivel_hierarquico ENUM('Estratégico', 'Tático', 'Operacional') NOT NULL,
    cargo VARCHAR(100),
    departamento VARCHAR(100),
    salario DECIMAL(10,2) COMMENT 'Salário mensal para cálculo de % sobre folha',
    data_admissao DATE,
    telefone VARCHAR(20),
    ativo BOOLEAN DEFAULT 1,
    origem ENUM('local', 'wordpress') DEFAULT 'local' COMMENT 'Origem do cadastro',
    wordpress_id INT NULL COMMENT 'ID do usuário no WordPress',
    foto_perfil VARCHAR(255),
    observacoes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_nivel (nivel_hierarquico),
    INDEX idx_ativo (ativo),
    INDEX idx_origem (origem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: treinamentos
-- Descrição: Cadastro dos treinamentos/capacitações
-- =====================================================
CREATE TABLE treinamentos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(250) NOT NULL COMMENT 'Campo 1: Nome do Treinamento',
    tipo ENUM('Normativos', 'Comportamentais', 'Técnicos') NOT NULL COMMENT 'Campo 2: Tipo',
    componente_pe ENUM('Clientes', 'Financeiro', 'Processos Internos', 'Aprendizagem e Crescimento') NOT NULL COMMENT 'Campo 3: Componente do P.E.',
    programa ENUM('PGR', 'Líderes em Transformação', 'Crescer', 'Gerais') NOT NULL COMMENT 'Campo 4: Programa',
    objetivo TEXT COMMENT 'Campo 5: O Que (Objetivo)',
    resultados_esperados TEXT COMMENT 'Campo 6: Resultados',
    justificativa TEXT COMMENT 'Campo 7: Por Que (Justificativa)',
    carga_horaria_total DECIMAL(5,2) COMMENT 'Carga horária total em horas',
    valor_investimento DECIMAL(10,2) DEFAULT 0 COMMENT 'Campo 11: Quanto (Valor)',
    status ENUM('Programado', 'Executado', 'Pendente', 'Cancelado') DEFAULT 'Programado' COMMENT 'Campo 12: Status',
    instrutor VARCHAR(150),
    local_padrao VARCHAR(200),
    material_didatico TEXT,
    observacoes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_nome (nome),
    INDEX idx_tipo (tipo),
    INDEX idx_programa (programa),
    INDEX idx_status (status),
    FULLTEXT idx_busca (nome, objetivo, resultados_esperados)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: agenda_treinamentos
-- Descrição: Agendamento de datas e horários dos treinamentos
-- Relacionamento: Um treinamento pode ter múltiplas datas/turmas
-- =====================================================
CREATE TABLE agenda_treinamentos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    treinamento_id INT NOT NULL COMMENT 'FK para treinamentos',
    data_inicio DATE NOT NULL COMMENT 'Campo 8: Quando (início)',
    data_fim DATE NOT NULL COMMENT 'Campo 8: Quando (fim)',
    hora_inicio TIME COMMENT 'Horário de início',
    hora_fim TIME COMMENT 'Horário de término',
    carga_horaria_dia DECIMAL(4,2) COMMENT 'Horas deste dia específico',
    local VARCHAR(200),
    instrutor VARCHAR(150),
    vagas_disponiveis INT,
    observacoes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (treinamento_id) REFERENCES treinamentos(id) ON DELETE CASCADE,
    INDEX idx_data (data_inicio, data_fim),
    INDEX idx_treinamento (treinamento_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: treinamento_participantes
-- Descrição: Vinculação de colaboradores aos treinamentos
-- Campo 9: Quem (Participantes)
-- =====================================================
CREATE TABLE treinamento_participantes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    treinamento_id INT NOT NULL COMMENT 'FK para treinamentos',
    colaborador_id INT NOT NULL COMMENT 'FK para colaboradores',
    status_participacao ENUM('Confirmado', 'Pendente', 'Ausente', 'Presente', 'Cancelado') DEFAULT 'Pendente',
    check_in_realizado BOOLEAN DEFAULT 0 COMMENT 'Campo 10: Check-in',
    data_check_in TIMESTAMP NULL,
    nota_avaliacao_reacao DECIMAL(3,1) COMMENT 'Avaliação de reação (0-10)',
    nota_avaliacao_aprendizado DECIMAL(3,1) COMMENT 'Avaliação de aprendizado (0-10)',
    nota_avaliacao_comportamento DECIMAL(3,1) COMMENT 'Avaliação de mudança de comportamento (0-10)',
    comentario_avaliacao TEXT,
    certificado_emitido BOOLEAN DEFAULT 0,
    data_emissao_certificado TIMESTAMP NULL,
    observacoes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (treinamento_id) REFERENCES treinamentos(id) ON DELETE CASCADE,
    FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id) ON DELETE CASCADE,
    UNIQUE KEY unique_participacao (treinamento_id, colaborador_id),
    INDEX idx_status (status_participacao),
    INDEX idx_checkin (check_in_realizado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: frequencia_treinamento
-- Descrição: Controle detalhado de presença por dia/período
-- Campo 10: Frequência de Participantes (detalhamento)
-- =====================================================
CREATE TABLE frequencia_treinamento (
    id INT PRIMARY KEY AUTO_INCREMENT,
    participante_id INT NOT NULL COMMENT 'FK para treinamento_participantes',
    agenda_id INT NOT NULL COMMENT 'FK para agenda_treinamentos (dia específico)',
    presente BOOLEAN DEFAULT 0,
    horas_participadas DECIMAL(5,2) COMMENT 'Horas efetivas de participação',
    justificativa_ausencia TEXT,
    observacoes TEXT,
    registrado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    registrado_por VARCHAR(100) COMMENT 'Usuário que registrou a frequência',
    
    FOREIGN KEY (participante_id) REFERENCES treinamento_participantes(id) ON DELETE CASCADE,
    FOREIGN KEY (agenda_id) REFERENCES agenda_treinamentos(id) ON DELETE CASCADE,
    UNIQUE KEY unique_frequencia (participante_id, agenda_id),
    INDEX idx_presente (presente)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: notificacoes
-- Descrição: Controle de notificações enviadas aos participantes
-- Campo 10: Sistema de notificações
-- =====================================================
CREATE TABLE notificacoes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    participante_id INT NOT NULL COMMENT 'FK para treinamento_participantes',
    tipo ENUM('convite', 'lembrete', 'confirmacao', 'certificado', 'avaliacao') NOT NULL,
    email_enviado BOOLEAN DEFAULT 0,
    data_envio TIMESTAMP NULL,
    token_check_in VARCHAR(100) UNIQUE COMMENT 'Token único para check-in',
    expiracao_token TIMESTAMP NULL,
    assunto VARCHAR(200),
    corpo_email TEXT,
    tentativas_envio INT DEFAULT 0,
    erro_envio TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (participante_id) REFERENCES treinamento_participantes(id) ON DELETE CASCADE,
    INDEX idx_tipo (tipo),
    INDEX idx_enviado (email_enviado),
    INDEX idx_token (token_check_in)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: wp_sync_log
-- Descrição: Log de sincronizações com WordPress
-- =====================================================
CREATE TABLE wp_sync_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    total_usuarios_wp INT COMMENT 'Total de usuários no WordPress',
    novos_importados INT COMMENT 'Novos colaboradores importados',
    atualizados INT COMMENT 'Colaboradores atualizados',
    erros INT COMMENT 'Quantidade de erros',
    detalhes_erros TEXT COMMENT 'Detalhes dos erros ocorridos',
    tempo_execucao DECIMAL(6,2) COMMENT 'Tempo de execução em segundos',
    executado_por VARCHAR(100),
    data_sync TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_data (data_sync)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: configuracoes
-- Descrição: Configurações do sistema
-- =====================================================
CREATE TABLE configuracoes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    chave VARCHAR(100) UNIQUE NOT NULL,
    valor TEXT,
    descricao VARCHAR(255),
    tipo ENUM('texto', 'numero', 'boolean', 'json') DEFAULT 'texto',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: usuarios_sistema
-- Descrição: Usuários do sistema SGC (administradores/gestores RH)
-- =====================================================
CREATE TABLE usuarios_sistema (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL COMMENT 'Hash da senha',
    nivel_acesso ENUM('admin', 'gestor', 'instrutor', 'visualizador') DEFAULT 'visualizador',
    ativo BOOLEAN DEFAULT 1,
    ultimo_acesso TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_nivel (nivel_acesso)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSERIR CONFIGURAÇÕES PADRÃO
-- =====================================================
INSERT INTO configuracoes (chave, valor, descricao, tipo) VALUES
    ('wp_api_url', '', 'URL da API do WordPress', 'texto'),
    ('wp_api_user', '', 'Usuário da API WordPress', 'texto'),
    ('wp_api_password', '', 'Senha de Aplicação WordPress', 'texto'),
    ('smtp_host', '', 'Servidor SMTP para envio de e-mails', 'texto'),
    ('smtp_port', '587', 'Porta SMTP', 'numero'),
    ('smtp_user', '', 'Usuário SMTP', 'texto'),
    ('smtp_password', '', 'Senha SMTP', 'texto'),
    ('email_remetente', 'noreply@empresa.com', 'E-mail remetente do sistema', 'texto'),
    ('nome_remetente', 'Sistema de Capacitações', 'Nome do remetente', 'texto'),
    ('sincronizacao_auto', 'false', 'Sincronização automática com WordPress', 'boolean'),
    ('horas_meta_anual', '40', 'Meta de horas de treinamento por colaborador/ano', 'numero'),
    ('percentual_meta_folha', '2.0', 'Meta de % investimento sobre folha salarial', 'numero');

-- =====================================================
-- INSERIR USUÁRIO ADMINISTRADOR PADRÃO
-- Senha: admin123 (hash gerado com password_hash)
-- =====================================================
INSERT INTO usuarios_sistema (nome, email, senha, nivel_acesso) VALUES
    ('Administrador', 'admin@sgc.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- =====================================================
-- VIEWS PARA RELATÓRIOS
-- =====================================================

-- View: Resumo de Treinamentos por Status
CREATE VIEW vw_treinamentos_status AS
SELECT 
    status,
    COUNT(*) as total,
    SUM(valor_investimento) as investimento_total,
    SUM(carga_horaria_total) as horas_totais
FROM treinamentos
GROUP BY status;

-- View: Participações por Colaborador
CREATE VIEW vw_participacoes_colaborador AS
SELECT 
    c.id,
    c.nome,
    c.nivel_hierarquico,
    COUNT(tp.id) as total_treinamentos,
    SUM(CASE WHEN tp.status_participacao = 'Presente' THEN 1 ELSE 0 END) as treinamentos_concluidos,
    SUM(f.horas_participadas) as horas_totais_treinamento
FROM colaboradores c
LEFT JOIN treinamento_participantes tp ON c.id = tp.colaborador_id
LEFT JOIN frequencia_treinamento f ON tp.id = f.participante_id
WHERE c.ativo = 1
GROUP BY c.id, c.nome, c.nivel_hierarquico;

-- View: Indicadores Mensais
CREATE VIEW vw_indicadores_mensais AS
SELECT 
    YEAR(at.data_inicio) as ano,
    MONTH(at.data_inicio) as mes,
    COUNT(DISTINCT t.id) as total_treinamentos,
    COUNT(DISTINCT tp.colaborador_id) as total_participantes,
    SUM(t.valor_investimento) as investimento_total,
    SUM(f.horas_participadas) as horas_totais,
    AVG(tp.nota_avaliacao_reacao) as media_avaliacao
FROM agenda_treinamentos at
JOIN treinamentos t ON at.treinamento_id = t.id
JOIN treinamento_participantes tp ON t.id = tp.treinamento_id
LEFT JOIN frequencia_treinamento f ON tp.id = f.participante_id
WHERE t.status = 'Executado'
GROUP BY YEAR(at.data_inicio), MONTH(at.data_inicio);

-- =====================================================
-- TRIGGERS
-- =====================================================

-- Trigger: Atualizar status do treinamento quando todas agendas passarem
DELIMITER $$
CREATE TRIGGER trg_atualizar_status_treinamento
AFTER UPDATE ON agenda_treinamentos
FOR EACH ROW
BEGIN
    DECLARE ultima_data DATE;
    
    SELECT MAX(data_fim) INTO ultima_data
    FROM agenda_treinamentos
    WHERE treinamento_id = NEW.treinamento_id;
    
    IF ultima_data < CURDATE() THEN
        UPDATE treinamentos
        SET status = 'Executado'
        WHERE id = NEW.treinamento_id AND status = 'Programado';
    END IF;
END$$
DELIMITER ;

-- Trigger: Atualizar check-in quando frequência for marcada como presente
DELIMITER $$
CREATE TRIGGER trg_atualizar_checkin
AFTER UPDATE ON frequencia_treinamento
FOR EACH ROW
BEGIN
    IF NEW.presente = 1 AND OLD.presente = 0 THEN
        UPDATE treinamento_participantes
        SET check_in_realizado = 1,
            data_check_in = NOW(),
            status_participacao = 'Presente'
        WHERE id = NEW.participante_id;
    END IF;
END$$
DELIMITER ;

-- =====================================================
-- PROCEDURES ÚTEIS
-- =====================================================

-- Procedure: Calcular HTC (Horas de Treinamento por Colaborador)
DELIMITER $$
CREATE PROCEDURE sp_calcular_htc(
    IN p_data_inicio DATE,
    IN p_data_fim DATE
)
BEGIN
    SELECT 
        COALESCE(SUM(f.horas_participadas), 0) / NULLIF(COUNT(DISTINCT tp.colaborador_id), 0) as htc,
        SUM(f.horas_participadas) as total_horas,
        COUNT(DISTINCT tp.colaborador_id) as total_colaboradores
    FROM frequencia_treinamento f
    JOIN treinamento_participantes tp ON f.participante_id = tp.id
    JOIN agenda_treinamentos at ON f.agenda_id = at.id
    WHERE at.data_inicio BETWEEN p_data_inicio AND p_data_fim
    AND f.presente = 1;
END$$
DELIMITER ;

-- Procedure: Calcular HTC por Nível Hierárquico
DELIMITER $$
CREATE PROCEDURE sp_calcular_htc_nivel(
    IN p_data_inicio DATE,
    IN p_data_fim DATE
)
BEGIN
    SELECT 
        c.nivel_hierarquico,
        COUNT(DISTINCT c.id) as total_colaboradores,
        SUM(f.horas_participadas) as total_horas,
        SUM(f.horas_participadas) / NULLIF(COUNT(DISTINCT c.id), 0) as htc_nivel
    FROM colaboradores c
    JOIN treinamento_participantes tp ON c.id = tp.colaborador_id
    JOIN frequencia_treinamento f ON tp.id = f.participante_id
    JOIN agenda_treinamentos at ON f.agenda_id = at.id
    WHERE at.data_inicio BETWEEN p_data_inicio AND p_data_fim
    AND f.presente = 1
    AND c.ativo = 1
    GROUP BY c.nivel_hierarquico;
END$$
DELIMITER ;

-- Procedure: Calcular Percentual sobre Folha
DELIMITER $$
CREATE PROCEDURE sp_calcular_percentual_folha(
    IN p_data_inicio DATE,
    IN p_data_fim DATE
)
BEGIN
    DECLARE v_folha_total DECIMAL(15,2);
    DECLARE v_investimento_total DECIMAL(15,2);
    
    SELECT SUM(salario) INTO v_folha_total
    FROM colaboradores
    WHERE ativo = 1;
    
    SELECT SUM(t.valor_investimento) INTO v_investimento_total
    FROM treinamentos t
    JOIN agenda_treinamentos at ON t.id = at.treinamento_id
    WHERE at.data_inicio BETWEEN p_data_inicio AND p_data_fim;
    
    SELECT 
        v_investimento_total as investimento_total,
        v_folha_total as folha_salarial_total,
        (v_investimento_total / NULLIF(v_folha_total, 0)) * 100 as percentual_sobre_folha;
END$$
DELIMITER ;

-- =====================================================
-- INDICES ADICIONAIS PARA PERFORMANCE
-- =====================================================

-- Índices compostos para queries frequentes
CREATE INDEX idx_treinamento_status_data ON treinamentos(status, created_at);
CREATE INDEX idx_participante_status ON treinamento_participantes(status_participacao, colaborador_id);
CREATE INDEX idx_frequencia_agenda_presente ON frequencia_treinamento(agenda_id, presente);
CREATE INDEX idx_agenda_periodo ON agenda_treinamentos(data_inicio, data_fim, treinamento_id);

-- =====================================================
-- FIM DO SCRIPT
-- =====================================================
```

---

## Módulos do Sistema

### Módulo 1: Gestão de Colaboradores

**Objetivo:** Gerenciar cadastro completo de colaboradores/funcionários.

**Funcionalidades:**
- ✅ Listagem com paginação e filtros avançados
- ✅ Cadastro manual com validação de campos
- ✅ Edição e atualização de dados
- ✅ Inativação (soft delete)
- ✅ Importação via planilha Excel/CSV
- ✅ Histórico de treinamentos do colaborador
- ✅ Exportação de relatórios

**Arquivos:**
```
/modules/colaboradores/
  ├── listar.php          # Lista todos colaboradores
  ├── cadastrar.php       # Formulário de cadastro
  ├── editar.php          # Formulário de edição
  ├── visualizar.php      # Detalhes do colaborador
  ├── importar.php        # Upload de planilha
  └── ajax/
      ├── buscar.php      # Busca dinâmica
      ├── salvar.php      # Salvar via AJAX
      └── deletar.php     # Inativação
```

**Regras de Negócio:**
- CPF e E-mail devem ser únicos
- Nível hierárquico é obrigatório
- Ao inativar, não excluir do banco (apenas marcar como inativo)
- Não permitir exclusão se houver treinamentos vinculados

---

### Módulo 2: Integração WordPress

**Objetivo:** Sincronizar usuários do WordPress com o sistema SGC.

**Funcionalidades:**
- ✅ Configuração de credenciais (URL, usuário, senha de aplicação)
- ✅ Sincronização manual (botão)
- ✅ Sincronização automática (cron job)
- ✅ Mapeamento de campos WordPress → SGC
- ✅ Log detalhado de sincronizações
- ✅ Tratamento de erros e retry

**Arquivos:**
```
/modules/integracao/
  ├── configurar.php      # Tela de configuração
  ├── sincronizar.php     # Executar sincronização
  ├── historico.php       # Log de sincronizações
  └── classes/
      └── WordPressSync.php  # Classe de integração
```

**Fluxo de Sincronização:**
```
1. Buscar usuários do WordPress via REST API
2. Para cada usuário:
   a. Verificar se já existe (por wordpress_id)
   b. Se existe: atualizar nome e email
   c. Se não existe: criar novo colaborador
3. Registrar log da sincronização
4. Retornar estatísticas (novos, atualizados, erros)
```

**Endpoint WordPress:**
```
GET https://seusite.com/wp-json/wp/v2/users
Authorization: Basic [base64(usuario:senha_aplicacao)]
```

---

### Módulo 3: Matriz de Capacitações

**Objetivo:** Gerenciar treinamentos com todos os 12 campos especificados.

**Funcionalidades:**
- ✅ Cadastro completo de treinamentos
- ✅ Wizard multi-etapas para facilitar preenchimento
- ✅ Vinculação de participantes
- ✅ Agendamento de múltiplas datas/turmas
- ✅ Controle de custos por treinamento
- ✅ Alteração de status (Programado → Executado)
- ✅ Busca avançada com filtros múltiplos

**Arquivos:**
```
/modules/treinamentos/
  ├── listar.php          # Grid de treinamentos
  ├── cadastrar.php       # Wizard de cadastro
  ├── editar.php          # Edição completa
  ├── visualizar.php      # Detalhes + participantes
  ├── agenda.php          # Calendário visual
  ├── participantes.php   # Vincular colaboradores
  └── ajax/
      ├── buscar.php
      ├── salvar.php
      ├── deletar.php
      └── vincular_participante.php
```

**Wizard de Cadastro (4 Etapas):**

```
Etapa 1: Dados Básicos
- Nome do Treinamento
- Tipo (Normativos/Comportamentais/Técnicos)
- Componente do P.E.
- Programa

Etapa 2: Descritivos
- Objetivo (O Que?)
- Resultados Esperados
- Justificativa (Por Quê?)

Etapa 3: Agendamento
- Data(s) e horários (Quando?)
- Carga horária
- Local
- Instrutor

Etapa 4: Participantes e Investimento
- Vincular colaboradores (Quem?)
- Valor do investimento (Quanto?)
- Status inicial
```

**Regras de Negócio:**
- Treinamento só pode ser excluído se status = "Programado"
- Ao vincular participantes, criar notificação automática
- Calcular custo por colaborador automaticamente
- Validar datas (data_fim >= data_inicio)

---

### Módulo 4: Gestão de Participantes e Notificações

**Objetivo:** Controlar presença, enviar notificações e fazer check-in.

**Funcionalidades:**
- ✅ Vinculação em massa de colaboradores
- ✅ Envio automático de convites por e-mail
- ✅ Geração de ticket de participação (HTML/PDF)
- ✅ Sistema de check-in via token único
- ✅ Registro de frequência por dia
- ✅ Controle de presença/ausência
- ✅ Avaliações de reação e aprendizado

**Arquivos:**
```
/modules/participantes/
  ├── vincular.php        # Seleção múltipla de colaboradores
  ├── frequencia.php      # Registro de presença
  ├── avaliar.php         # Formulário de avaliação
  └── ajax/
      ├── vincular.php
      ├── registrar_frequencia.php
      └── enviar_notificacao.php

/public/
  └── checkin.php         # Página pública de check-in
```

**Fluxo de Notificações:**

```
1. Colaborador é vinculado ao treinamento
   ↓
2. Sistema gera token único de check-in
   ↓
3. E-mail de convite é enviado com:
   - Detalhes do treinamento
   - Data, horário e local
   - Link de check-in
   ↓
4. Colaborador clica no link e faz check-in
   ↓
5. Status muda para "Confirmado"
   ↓
6. No dia do treinamento, instrutor registra frequência
   ↓
7. Ao final, colaborador recebe link de avaliação
```

**Template de E-mail (Convite):**
```html
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; }
        .ticket { max-width: 600px; margin: 0 auto; border: 2px solid #0066cc; }
        .header { background: #0066cc; color: white; padding: 20px; text-align: center; }
        .content { padding: 30px; }
        .button { background: #0066cc; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; display: inline-block; }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="header">
            <h1>🎓 Ticket de Participação</h1>
        </div>
        <div class="content">
            <p>Olá, <strong>{NOME_COLABORADOR}</strong>!</p>
            <p>Você foi inscrito no treinamento:</p>
            <h2>{NOME_TREINAMENTO}</h2>
            <p><strong>📅 Data:</strong> {DATA_INICIO}</p>
            <p><strong>🕒 Horário:</strong> {HORA_INICIO}</p>
            <p><strong>📍 Local:</strong> {LOCAL}</p>
            <p><strong>🎯 Objetivo:</strong> {OBJETIVO}</p>
            <div style="text-align: center; margin: 30px 0;">
                <a href="{LINK_CHECKIN}" class="button">✅ CONFIRMAR PRESENÇA</a>
            </div>
        </div>
    </div>
</body>
</html>
```

---

### Módulo 5: Relatórios e Indicadores

**Objetivo:** Gerar relatórios gerenciais e calcular indicadores de RH conforme documento técnico.

**Funcionalidades:**
- ✅ Dashboard visual com gráficos
- ✅ Indicadores calculados automaticamente:
  - HTC (Horas de Treinamento por Colaborador)
  - HTC por Nível Hierárquico
  - CTC (Custo de Treinamento por Colaborador)
  - % de Investimento sobre Folha Salarial
  - % de Treinamentos Realizados vs Planejados
  - % de Colaboradores Capacitados
- ✅ Relatórios mensais, trimestrais e anuais
- ✅ Comparativos entre períodos
- ✅ Exportação para Excel e PDF
- ✅ Gráficos interativos (Chart.js ou similar)

**Arquivos:**
```
/modules/relatorios/
  ├── dashboard.php       # Dashboard principal
  ├── mensal.php          # Relatório mensal
  ├── trimestral.php      # Relatório trimestral
  ├── anual.php           # Consolidado anual
  ├── colaborador.php     # Histórico individual
  ├── comparativo.php     # Comparação entre períodos
  ├── exportar_excel.php  # Geração de Excel
  └── exportar_pdf.php    # Geração de PDF
```

**Indicadores Implementados:**

```php
// 1. HTC - Horas de Treinamento por Colaborador
HTC = Total de Horas de Treinamento / Número de Colaboradores Treinados

// 2. HTC por Nível Hierárquico
HTC_nivel = Total de Horas do Nível / Número de Colaboradores do Nível

// 3. CTC - Custo de Treinamento por Colaborador
CTC = Custo Total de Treinamentos / Número de Colaboradores Treinados

// 4. % de Investimento sobre Folha
% = (Custo Total de Treinamentos / Folha Salarial Total) × 100

// 5. % de Treinamentos Realizados vs Planejados
% = (Horas Realizadas / Horas Planejadas) × 100

// 6. % de Colaboradores Capacitados
% = (Colaboradores Treinados / Colaboradores Totais) × 100
```

**Tipos de Gráficos:**
- Pizza: Proporção de horas por nível hierárquico
- Colunas: Horas de treinamento por mês
- Linhas: Evolução de investimento ao longo do ano
- Barras horizontais: Top 10 treinamentos mais realizados
- Radar: Avaliação média por tipo de treinamento

---

## Implementações de Código

### Classe: Database (Conexão PDO)

**Arquivo:** `/classes/Database.php`

```php
<?php
/**
 * Classe Database
 * Gerencia conexão com banco de dados usando PDO e Singleton
 */
class Database {
    private static $instance = null;
    private $connection;
    
    // Configurações do banco
    private $host = 'localhost';
    private $dbname = 'sgc_treinamentos';
    private $username = 'root';
    private $password = '';
    private $charset = 'utf8mb4';
    
    /**
     * Construtor privado (Singleton)
     */
    private function __construct() {
        $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}"
        ];
        
        try {
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            throw new Exception("Erro de conexão: " . $e->getMessage());
        }
    }
    
    /**
     * Retorna instância única da classe (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Retorna conexão PDO
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Previne clonagem
     */
    private function __clone() {}
    
    /**
     * Previne deserialização
     */
    public function __wakeup() {
        throw new Exception("Não é possível deserializar Singleton");
    }
}
```

---

### Classe: WordPressSync (Integração com WordPress)

**Arquivo:** `/classes/WordPressSync.php`

```php
<?php
/**
 * Classe WordPressSync
 * Integração com WordPress REST API para importação de usuários
 */
class WordPressSync {
    private $pdo;
    private $wp_url;
    private $wp_user;
    private $wp_password;
    
    /**
     * Construtor
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->loadConfig();
    }
    
    /**
     * Carrega configurações do WordPress do banco
     */
    private function loadConfig() {
        $stmt = $this->pdo->query("
            SELECT chave, valor 
            FROM configuracoes 
            WHERE chave IN ('wp_api_url', 'wp_api_user', 'wp_api_password')
        ");
        
        $config = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $this->wp_url = $config['wp_api_url'] ?? '';
        $this->wp_user = $config['wp_api_user'] ?? '';
        $this->wp_password = $config['wp_api_password'] ?? '';
    }
    
    /**
     * Valida se as configurações estão preenchidas
     */
    public function isConfigured() {
        return !empty($this->wp_url) && !empty($this->wp_user) && !empty($this->wp_password);
    }
    
    /**
     * Busca usuários do WordPress via REST API
     */
    public function fetchUsers($per_page = 100, $page = 1) {
        if (!$this->isConfigured()) {
            throw new Exception("WordPress não configurado. Configure em Integrações > Configurar.");
        }
        
        $url = rtrim($this->wp_url, '/') . "/wp-json/wp/v2/users?per_page={$per_page}&page={$page}";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => $this->wp_user . ':' . $this->wp_password,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_SSL_VERIFYPEER => false, // Em produção, configure SSL corretamente
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($http_code !== 200) {
            throw new Exception("Erro na API WordPress (HTTP {$http_code}): {$error}");
        }
        
        $users = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Erro ao decodificar resposta JSON: " . json_last_error_msg());
        }
        
        return $users;
    }
    
    /**
     * Sincroniza usuários do WordPress com colaboradores do SGC
     */
    public function syncUsers() {
        $inicio = microtime(true);
        $imported = 0;
        $updated = 0;
        $errors = 0;
        $error_details = [];
        
        try {
            $this->pdo->beginTransaction();
            
            // Busca usuários do WordPress (pode paginar se necessário)
            $users = $this->fetchUsers(100, 1);
            $total_wp = count($users);
            
            foreach ($users as $user) {
                try {
                    // Verifica se colaborador já existe pelo wordpress_id
                    $stmt = $this->pdo->prepare("
                        SELECT id FROM colaboradores WHERE wordpress_id = ?
                    ");
                    $stmt->execute([$user['id']]);
                    $exists = $stmt->fetch();
                    
                    if ($exists) {
                        // Atualiza dados existentes
                        $stmt = $this->pdo->prepare("
                            UPDATE colaboradores 
                            SET nome = ?, 
                                email = ?, 
                                updated_at = NOW() 
                            WHERE wordpress_id = ?
                        ");
                        $stmt->execute([
                            $user['name'],
                            $user['email'],
                            $user['id']
                        ]);
                        $updated++;
                    } else {
                        // Verifica se e-mail já existe (evitar duplicação)
                        $stmt = $this->pdo->prepare("
                            SELECT id FROM colaboradores WHERE email = ?
                        ");
                        $stmt->execute([$user['email']]);
                        
                        if ($stmt->fetch()) {
                            // E-mail já existe, atualiza vinculando o wordpress_id
                            $stmt = $this->pdo->prepare("
                                UPDATE colaboradores 
                                SET wordpress_id = ?,
                                    origem = 'wordpress',
                                    updated_at = NOW()
                                WHERE email = ?
                            ");
                            $stmt->execute([$user['id'], $user['email']]);
                            $updated++;
                        } else {
                            // Insere novo colaborador
                            $stmt = $this->pdo->prepare("
                                INSERT INTO colaboradores 
                                (nome, email, origem, wordpress_id, nivel_hierarquico, created_at) 
                                VALUES (?, ?, 'wordpress', ?, 'Operacional', NOW())
                            ");
                            $stmt->execute([
                                $user['name'],
                                $user['email'],
                                $user['id']
                            ]);
                            $imported++;
                        }
                    }
                } catch (Exception $e) {
                    $errors++;
                    $error_details[] = "Usuário {$user['email']}: " . $e->getMessage();
                }
            }
            
            // Registra log da sincronização
            $tempo_execucao = microtime(true) - $inicio;
            $stmt = $this->pdo->prepare("
                INSERT INTO wp_sync_log 
                (total_usuarios_wp, novos_importados, atualizados, erros, detalhes_erros, tempo_execucao) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $total_wp,
                $imported,
                $updated,
                $errors,
                implode("\n", $error_details),
                round($tempo_execucao, 2)
            ]);
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'total_wp' => $total_wp,
                'imported' => $imported,
                'updated' => $updated,
                'errors' => $errors,
                'error_details' => $error_details,
                'tempo_execucao' => round($tempo_execucao, 2)
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
    
    /**
     * Testa conexão com WordPress
     */
    public function testConnection() {
        try {
            $users = $this->fetchUsers(1, 1);
            return [
                'success' => true,
                'message' => 'Conexão estabelecida com sucesso!'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
```

---

### Classe: NotificationManager (Envio de E-mails)

**Arquivo:** `/classes/NotificationManager.php`

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Classe NotificationManager
 * Gerencia envio de notificações por e-mail aos participantes
 */
class NotificationManager {
    private $pdo;
    private $mailer;
    
    /**
     * Construtor
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->setupMailer();
    }
    
    /**
     * Configura PHPMailer com dados do banco
     */
    private function setupMailer() {
        // Busca configurações SMTP do banco
        $stmt = $this->pdo->query("
            SELECT chave, valor 
            FROM configuracoes 
            WHERE chave IN ('smtp_host', 'smtp_port', 'smtp_user', 'smtp_password', 'email_remetente', 'nome_remetente')
        ");
        $config = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $this->mailer = new PHPMailer(true);
        
        // Configurações SMTP
        $this->mailer->isSMTP();
        $this->mailer->Host = $config['smtp_host'] ?? 'localhost';
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = $config['smtp_user'] ?? '';
        $this->mailer->Password = $config['smtp_password'] ?? '';
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = $config['smtp_port'] ?? 587;
        $this->mailer->CharSet = 'UTF-8';
        
        // Remetente padrão
        $this->mailer->setFrom(
            $config['email_remetente'] ?? 'noreply@empresa.com',
            $config['nome_remetente'] ?? 'Sistema de Capacitações'
        );
    }
    
    /**
     * Envia convite para participante
     */
    public function enviarConvite($participante_id) {
        try {
            // Busca dados do participante e treinamento
            $stmt = $this->pdo->prepare("
                SELECT 
                    tp.id as participante_id,
                    c.nome as colaborador_nome,
                    c.email as colaborador_email,
                    t.nome as treinamento_nome,
                    t.objetivo,
                    t.carga_horaria_total,
                    at.data_inicio,
                    at.data_fim,
                    at.hora_inicio,
                    at.hora_fim,
                    at.local
                FROM treinamento_participantes tp
                JOIN colaboradores c ON tp.colaborador_id = c.id
                JOIN treinamentos t ON tp.treinamento_id = t.id
                LEFT JOIN agenda_treinamentos at ON t.id = at.treinamento_id
                WHERE tp.id = ?
                ORDER BY at.data_inicio ASC
                LIMIT 1
            ");
            $stmt->execute([$participante_id]);
            $dados = $stmt->fetch();
            
            if (!$dados) {
                throw new Exception("Participante não encontrado");
            }
            
            // Gera token único para check-in
            $token = bin2hex(random_bytes(32));
            $expiracao = date('Y-m-d H:i:s', strtotime('+30 days'));
            
            // Salva notificação no banco
            $stmt = $this->pdo->prepare("
                INSERT INTO notificacoes 
                (participante_id, tipo, token_check_in, expiracao_token, assunto) 
                VALUES (?, 'convite', ?, ?, ?)
            ");
            $assunto = "Convite: {$dados['treinamento_nome']}";
            $stmt->execute([$participante_id, $token, $expiracao, $assunto]);
            $notificacao_id = $this->pdo->lastInsertId();
            
            // Monta corpo do e-mail
            $link_checkin = "http://localhost/sgc/public/checkin.php?token={$token}";
            $corpo = $this->montarTemplateConvite($dados, $link_checkin);
            
            // Envia e-mail
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($dados['colaborador_email'], $dados['colaborador_nome']);
            $this->mailer->Subject = $assunto;
            $this->mailer->isHTML(true);
            $this->mailer->Body = $corpo;
            
            $this->mailer->send();
            
            // Atualiza notificação como enviada
            $stmt = $this->pdo->prepare("
                UPDATE notificacoes 
                SET email_enviado = 1, 
                    data_envio = NOW(),
                    corpo_email = ?
                WHERE id = ?
            ");
            $stmt->execute([$corpo, $notificacao_id]);
            
            return [
                'success' => true,
                'message' => 'Convite enviado com sucesso!',
                'token' => $token
            ];
            
        } catch (Exception $e) {
            // Registra erro no banco
            if (isset($notificacao_id)) {
                $stmt = $this->pdo->prepare("
                    UPDATE notificacoes 
                    SET tentativas_envio = tentativas_envio + 1,
                        erro_envio = ?
                    WHERE id = ?
                ");
                $stmt->execute([$e->getMessage(), $notificacao_id]);
            }
            
            return [
                'success' => false,
                'message' => 'Erro ao enviar convite: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Monta template HTML do convite
     */
    private function montarTemplateConvite($dados, $link_checkin) {
        $data_formatada = date('d/m/Y', strtotime($dados['data_inicio']));
        $hora_formatada = date('H:i', strtotime($dados['hora_inicio']));
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    margin: 0;
                    padding: 0;
                }
                .ticket {
                    max-width: 600px;
                    margin: 20px auto;
                    border: 3px solid #0066cc;
                    border-radius: 10px;
                    overflow: hidden;
                }
                .header {
                    background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%);
                    color: white;
                    padding: 30px;
                    text-align: center;
                }
                .header h1 {
                    margin: 0;
                    font-size: 28px;
                }
                .content {
                    padding: 40px 30px;
                    background: #f9f9f9;
                }
                .treinamento-nome {
                    color: #0066cc;
                    font-size: 22px;
                    margin: 20px 0;
                    font-weight: bold;
                }
                .info-item {
                    margin: 15px 0;
                    padding: 10px;
                    background: white;
                    border-left: 4px solid #0066cc;
                }
                .button-container {
                    text-align: center;
                    margin: 40px 0 20px;
                }
                .button {
                    background: #0066cc;
                    color: white !important;
                    padding: 15px 40px;
                    text-decoration: none;
                    border-radius: 5px;
                    display: inline-block;
                    font-weight: bold;
                    font-size: 16px;
                    transition: background 0.3s;
                }
                .button:hover {
                    background: #0052a3;
                }
                .footer {
                    padding: 20px;
                    text-align: center;
                    font-size: 12px;
                    color: #666;
                    background: #e9e9e9;
                }
            </style>
        </head>
        <body>
            <div class='ticket'>
                <div class='header'>
                    <h1>🎓 Ticket de Participação</h1>
                    <p style='margin: 10px 0 0 0; font-size: 16px;'>Sistema de Gestão de Capacitações</p>
                </div>
                
                <div class='content'>
                    <p>Olá, <strong>{$dados['colaborador_nome']}</strong>!</p>
                    
                    <p>Você foi inscrito(a) no seguinte treinamento:</p>
                    
                    <div class='treinamento-nome'>
                        {$dados['treinamento_nome']}
                    </div>
                    
                    <div class='info-item'>
                        <strong>📅 Data:</strong> {$data_formatada}
                    </div>
                    
                    <div class='info-item'>
                        <strong>🕒 Horário:</strong> {$hora_formatada}
                    </div>
                    
                    <div class='info-item'>
                        <strong>📍 Local:</strong> {$dados['local']}
                    </div>
                    
                    <div class='info-item'>
                        <strong>⏱️ Carga Horária:</strong> {$dados['carga_horaria_total']} horas
                    </div>
                    
                    <div class='info-item'>
                        <strong>🎯 Objetivo:</strong><br>
                        {$dados['objetivo']}
                    </div>
                    
                    <div class='button-container'>
                        <a href='{$link_checkin}' class='button'>
                            ✅ CONFIRMAR PRESENÇA
                        </a>
                    </div>
                    
                    <p style='font-size: 14px; color: #666; text-align: center;'>
                        É importante confirmar sua presença clicando no botão acima.<br>
                        Este link é válido por 30 dias.
                    </p>
                </div>
                
                <div class='footer'>
                    <p>Este é um e-mail automático. Por favor, não responda.</p>
                    <p>Para dúvidas, entre em contato com o RH.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Envia lembrete para participante (1 dia antes do treinamento)
     */
    public function enviarLembrete($participante_id) {
        // Implementação similar ao enviarConvite
        // Apenas muda o template e o tipo da notificação
    }
    
    /**
     * Envia link para avaliação pós-treinamento
     */
    public function enviarAvaliacao($participante_id) {
        // Implementação para enviar formulário de avaliação
    }
    
    /**
     * Envia certificado de conclusão
     */
    public function enviarCertificado($participante_id) {
        // Implementação para enviar certificado em PDF
    }
}
```

---

### Classe: IndicadoresRH (Cálculo de Indicadores)

**Arquivo:** `/classes/IndicadoresRH.php`

```php
<?php
/**
 * Classe IndicadoresRH
 * Calcula indicadores de treinamento baseados no documento técnico
 */
class IndicadoresRH {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * 1. HTC - Horas de Treinamento por Colaborador
     * Fórmula: Total de Horas / Número de Colaboradores Treinados
     */
    public function calcularHTC($data_inicio, $data_fim) {
        $stmt = $this->pdo->prepare("
            SELECT 
                COALESCE(SUM(f.horas_participadas), 0) as total_horas,
                COUNT(DISTINCT tp.colaborador_id) as total_colaboradores,
                CASE 
                    WHEN COUNT(DISTINCT tp.colaborador_id) > 0 
                    THEN COALESCE(SUM(f.horas_participadas), 0) / COUNT(DISTINCT tp.colaborador_id)
                    ELSE 0 
                END as htc
            FROM frequencia_treinamento f
            JOIN treinamento_participantes tp ON f.participante_id = tp.id
            JOIN agenda_treinamentos at ON f.agenda_id = at.id
            WHERE at.data_inicio BETWEEN ? AND ?
            AND f.presente = 1
        ");
        
        $stmt->execute([$data_inicio, $data_fim]);
        return $stmt->fetch();
    }
    
    /**
     * 2. HTC por Nível Hierárquico
     * Fórmula: Total de Horas do Nível / Número de Colaboradores do Nível
     */
    public function calcularHTCPorNivel($data_inicio, $data_fim) {
        $stmt = $this->pdo->prepare("
            SELECT 
                c.nivel_hierarquico,
                COUNT(DISTINCT c.id) as total_colaboradores,
                COALESCE(SUM(f.horas_participadas), 0) as total_horas,
                CASE 
                    WHEN COUNT(DISTINCT c.id) > 0 
                    THEN COALESCE(SUM(f.horas_participadas), 0) / COUNT(DISTINCT c.id)
                    ELSE 0 
                END as htc_nivel
            FROM colaboradores c
            LEFT JOIN treinamento_participantes tp ON c.id = tp.colaborador_id
            LEFT JOIN frequencia_treinamento f ON tp.id = f.participante_id AND f.presente = 1
            LEFT JOIN agenda_treinamentos at ON f.agenda_id = at.id
            WHERE c.ativo = 1
            AND (at.data_inicio BETWEEN ? AND ? OR at.data_inicio IS NULL)
            GROUP BY c.nivel_hierarquico
            ORDER BY c.nivel_hierarquico
        ");
        
        $stmt->execute([$data_inicio, $data_fim]);
        return $stmt->fetchAll();
    }
    
    /**
     * 3. CTC - Custo de Treinamento por Colaborador
     * Fórmula: Custo Total / Número de Colaboradores Treinados
     */
    public function calcularCTC($data_inicio, $data_fim) {
        $stmt = $this->pdo->prepare("
            SELECT 
                COALESCE(SUM(t.valor_investimento), 0) as custo_total,
                COUNT(DISTINCT tp.colaborador_id) as total_colaboradores,
                CASE 
                    WHEN COUNT(DISTINCT tp.colaborador_id) > 0 
                    THEN COALESCE(SUM(t.valor_investimento), 0) / COUNT(DISTINCT tp.colaborador_id)
                    ELSE 0 
                END as ctc
            FROM treinamentos t
            JOIN treinamento_participantes tp ON t.id = tp.treinamento_id
            JOIN agenda_treinamentos at ON t.id = at.treinamento_id
            WHERE at.data_inicio BETWEEN ? AND ?
        ");
        
        $stmt->execute([$data_inicio, $data_fim]);
        return $stmt->fetch();
    }
    
    /**
     * 4. Percentual de Investimento sobre Folha Salarial
     * Fórmula: (Custo Total de Treinamentos / Folha Salarial Total) × 100
     */
    public function calcularPercentualFolha($data_inicio, $data_fim) {
        // Busca folha salarial total
        $stmt_folha = $this->pdo->query("
            SELECT COALESCE(SUM(salario), 0) as folha_total
            FROM colaboradores
            WHERE ativo = 1
        ");
        $folha = $stmt_folha->fetch();
        
        // Busca custo total de treinamentos
        $stmt_custo = $this->pdo->prepare("
            SELECT COALESCE(SUM(t.valor_investimento), 0) as investimento_total
            FROM treinamentos t
            JOIN agenda_treinamentos at ON t.id = at.treinamento_id
            WHERE at.data_inicio BETWEEN ? AND ?
        ");
        $stmt_custo->execute([$data_inicio, $data_fim]);
        $custo = $stmt_custo->fetch();
        
        $percentual = 0;
        if ($folha['folha_total'] > 0) {
            $percentual = ($custo['investimento_total'] / $folha['folha_total']) * 100;
        }
        
        return [
            'investimento_total' => $custo['investimento_total'],
            'folha_total' => $folha['folha_total'],
            'percentual' => round($percentual, 2)
        ];
    }
    
    /**
     * 5. % de Treinamentos Realizados vs Planejados
     * Fórmula: (Horas Realizadas / Horas Planejadas) × 100
     */
    public function calcularPercentualRealizados($data_inicio, $data_fim) {
        $stmt = $this->pdo->prepare("
            SELECT 
                SUM(CASE WHEN t.status = 'Executado' THEN t.carga_horaria_total ELSE 0 END) as horas_realizadas,
                SUM(t.carga_horaria_total) as horas_planejadas,
                CASE 
                    WHEN SUM(t.carga_horaria_total) > 0 
                    THEN (SUM(CASE WHEN t.status = 'Executado' THEN t.carga_horaria_total ELSE 0 END) / SUM(t.carga_horaria_total)) * 100
                    ELSE 0 
                END as percentual
            FROM treinamentos t
            JOIN agenda_treinamentos at ON t.id = at.treinamento_id
            WHERE at.data_inicio BETWEEN ? AND ?
        ");
        
        $stmt->execute([$data_inicio, $data_fim]);
        $resultado = $stmt->fetch();
        
        return [
            'horas_realizadas' => $resultado['horas_realizadas'] ?? 0,
            'horas_planejadas' => $resultado['horas_planejadas'] ?? 0,
            'percentual' => round($resultado['percentual'] ?? 0, 2)
        ];
    }
    
    /**
     * 6. % de Colaboradores Capacitados
     * Fórmula: (Colaboradores Treinados / Colaboradores Totais) × 100
     */
    public function calcularPercentualCapacitados($data_inicio, $data_fim) {
        // Total de colaboradores ativos
        $stmt_total = $this->pdo->query("
            SELECT COUNT(*) as total
            FROM colaboradores
            WHERE ativo = 1
        ");
        $total = $stmt_total->fetch()['total'];
        
        // Colaboradores que participaram de treinamentos
        $stmt_treinados = $this->pdo->prepare("
            SELECT COUNT(DISTINCT tp.colaborador_id) as treinados
            FROM treinamento_participantes tp
            JOIN agenda_treinamentos at ON tp.treinamento_id = at.treinamento_id
            WHERE at.data_inicio BETWEEN ? AND ?
            AND tp.status_participacao IN ('Presente', 'Confirmado')
        ");
        $stmt_treinados->execute([$data_inicio, $data_fim]);
        $treinados = $stmt_treinados->fetch()['treinados'];
        
        $percentual = 0;
        if ($total > 0) {
            $percentual = ($treinados / $total) * 100;
        }
        
        return [
            'colaboradores_totais' => $total,
            'colaboradores_treinados' => $treinados,
            'percentual' => round($percentual, 2)
        ];
    }
    
    /**
     * Dashboard Resumido
     * Retorna todos os indicadores principais
     */
    public function getDashboard($data_inicio, $data_fim) {
        return [
            'htc' => $this->calcularHTC($data_inicio, $data_fim),
            'htc_nivel' => $this->calcularHTCPorNivel($data_inicio, $data_fim),
            'ctc' => $this->calcularCTC($data_inicio, $data_fim),
            'percentual_folha' => $this->calcularPercentualFolha($data_inicio, $data_fim),
            'percentual_realizados' => $this->calcularPercentualRealizados($data_inicio, $data_fim),
            'percentual_capacitados' => $this->calcularPercentualCapacitados($data_inicio, $data_fim)
        ];
    }
    
    /**
     * Relatório Mensal Completo
     */
    public function getRelatorioMensal($ano, $mes) {
        $data_inicio = "{$ano}-{$mes}-01";
        $data_fim = date("Y-m-t", strtotime($data_inicio));
        
        return $this->getDashboard($data_inicio, $data_fim);
    }
    
    /**
     * Relatório Anual Completo
     */
    public function getRelatorioAnual($ano) {
        $data_inicio = "{$ano}-01-01";
        $data_fim = "{$ano}-12-31";
        
        return $this->getDashboard($data_inicio, $data_fim);
    }
}
```

---

## Estrutura de Diretórios

```
sgc-treinamentos/
│
├── public/                         # Arquivos públicos (raiz web)
│   ├── index.php                   # Página inicial/login
│   ├── checkin.php                 # Check-in público (via token)
│   ├── .htaccess                   # Regras Apache
│   │
│   ├── assets/                     # Assets frontend
│   │   ├── css/
│   │   │   ├── main.css
│   │   │   ├── dashboard.css
│   │   │   └── print.css
│   │   ├── js/
│   │   │   ├── main.js
│   │   │   ├── charts.js
│   │   │   ├── datatables.js
│   │   │   └── validators.js
│   │   ├── img/
│   │   │   ├── logo.png
│   │   │   └── icons/
│   │   └── vendor/                # Bibliotecas frontend
│   │       ├── bootstrap/
│   │       ├── jquery/
│   │       ├── chart.js/
│   │       └── datatables/
│   │
│   └── uploads/                    # Uploads de usuários
│       ├── colaboradores/
│       ├── certificados/
│       └── temp/
│
├── app/                            # Aplicação PHP
│   │
│   ├── config/                     # Configurações
│   │   ├── config.php              # Config geral
│   │   ├── database.php            # Config DB
│   │   └── constants.php           # Constantes
│   │
│   ├── classes/                    # Classes principais
│   │   ├── Database.php
│   │   ├── WordPressSync.php
│   │   ├── NotificationManager.php
│   │   ├── IndicadoresRH.php
│   │   ├── Auth.php                # Autenticação
│   │   └── Utils.php               # Funções auxiliares
│   │
│   ├── models/                     # Models (acesso a dados)
│   │   ├── Colaborador.php
│   │   ├── Treinamento.php
│   │   ├── Participante.php
│   │   ├── Frequencia.php
│   │   └── Relatorio.php
│   │
│   ├── controllers/                # Controllers (lógica)
│   │   ├── ColaboradorController.php
│   │   ├── TreinamentoController.php
│   │   ├── ParticipanteController.php
│   │   ├── RelatorioController.php
│   │   └── IntegracaoController.php
│   │
│   ├── views/                      # Views (interface)
│   │   ├── layouts/
│   │   │   ├── header.php
│   │   │   ├── footer.php
│   │   │   ├── sidebar.php
│   │   │   └── navbar.php
│   │   │
│   │   ├── auth/
│   │   │   ├── login.php
│   │   │   └── logout.php
│   │   │
│   │   ├── colaboradores/
│   │   │   ├── listar.php
│   │   │   ├── cadastrar.php
│   │   │   ├── editar.php
│   │   │   ├── visualizar.php
│   │   │   └── importar.php
│   │   │
│   │   ├── treinamentos/
│   │   │   ├── listar.php
│   │   │   ├── cadastrar.php
│   │   │   ├── editar.php
│   │   │   ├── visualizar.php
│   │   │   └── agenda.php
│   │   │
│   │   ├── participantes/
│   │   │   ├── vincular.php
│   │   │   ├── frequencia.php
│   │   │   └── avaliar.php
│   │   │
│   │   ├── integracao/
│   │   │   ├── configurar.php
│   │   │   ├── sincronizar.php
│   │   │   └── historico.php
│   │   │
│   │   ├── relatorios/
│   │   │   ├── dashboard.php
│   │   │   ├── mensal.php
│   │   │   ├── trimestral.php
│   │   │   ├── anual.php
│   │   │   ├── colaborador.php
│   │   │   └── comparativo.php
│   │   │
│   │   └── dashboard.php           # Dashboard principal
│   │
│   └── helpers/                    # Funções auxiliares
│       ├── functions.php
│       ├── validators.php
│       └── formatters.php
│
├── database/                       # Scripts de banco
│   ├── schema.sql                  # Estrutura completa
│   ├── seeds.sql                   # Dados iniciais
│   └── migrations/                 # Migrações
│       ├── 001_create_tables.sql
│       ├── 002_create_views.sql
│       └── 003_create_procedures.sql
│
├── vendor/                         # Dependências Composer
│   └── autoload.php
│
├── logs/                           # Logs do sistema
│   ├── app.log
│   ├── error.log
│   └── sync.log
│
├── temp/                           # Arquivos temporários
│   ├── exports/
│   └── cache/
│
├── docs/                           # Documentação
│   ├── README.md
│   ├── API.md
│   ├── DATABASE.md
│   └── DEPLOYMENT.md
│
├── tests/                          # Testes (opcional)
│   ├── unit/
│   └── integration/
│
├── .env.example                    # Exemplo de variáveis de ambiente
├── .gitignore
├── composer.json                   # Dependências PHP
├── composer.lock
└── README.md
```

---

## Cronograma de Desenvolvimento

### FASE 1: Estrutura Base (2 semanas)

**Semana 1:**
- ✅ Criar estrutura de diretórios
- ✅ Configurar banco de dados (executar schema.sql)
- ✅ Implementar classe Database (PDO + Singleton)
- ✅ Criar sistema de autenticação básico
- ✅ Desenvolver layout base (header, sidebar, footer)
- ✅ Implementar Model e Controller de Colaboradores

**Semana 2:**
- ✅ Desenvolver CRUD completo de Colaboradores
- ✅ Implementar validações de formulário
- ✅ Criar listagem com paginação e filtros
- ✅ Desenvolver funcionalidade de importação de planilha
- ✅ Testes iniciais do módulo

**Entregáveis:**
- Sistema de login funcional
- CRUD de colaboradores completo
- Layout base implementado

---

### FASE 2: Integração WordPress (1 semana)

**Semana 3:**
- ✅ Implementar classe WordPressSync
- ✅ Criar tela de configuração (salvar credenciais)
- ✅ Desenvolver botão de sincronização manual
- ✅ Implementar log de sincronizações
- ✅ Criar cron job para sincronização automática
- ✅ Testar com WordPress real
- ✅ Tratamento de erros e validações

**Entregáveis:**
- Integração WordPress funcional
- Importação de usuários testada
- Logs de sincronização salvos

---

### FASE 3: Matriz de Capacitações (2 semanas)

**Semana 4:**
- ✅ Implementar Models (Treinamento, Agenda)
- ✅ Desenvolver Controller de Treinamentos
- ✅ Criar wizard de cadastro (4 etapas)
- ✅ Implementar validações específicas
- ✅ Desenvolver listagem com filtros avançados

**Semana 5:**
- ✅ Criar tela de agendamento (múltiplas datas)
- ✅ Implementar vinculação de participantes
- ✅ Desenvolver controle de status
- ✅ Criar visualização detalhada de treinamento
- ✅ Implementar cálculos automáticos (custo/colaborador)
- ✅ Testes do módulo completo

**Entregáveis:**
- CRUD de treinamentos com 12 campos
- Sistema de agendamento funcional
- Vinculação de participantes

---

### FASE 4: Notificações e Check-in (1 semana)

**Semana 6:**
- ✅ Configurar PHPMailer
- ✅ Implementar classe NotificationManager
- ✅ Criar template HTML de convite
- ✅ Desenvolver sistema de tokens únicos
- ✅ Criar página pública de check-in
- ✅ Implementar envio automático ao vincular participante
- ✅ Testar envio de e-mails

**Entregáveis:**
- Sistema de notificações por e-mail
- Check-in via token funcional
- Templates de e-mail responsivos

---

### FASE 5: Frequência e Avaliações (1 semana)

**Semana 7:**
- ✅ Implementar Model de Frequência
- ✅ Criar tela de registro de presença
- ✅ Desenvolver controle por dia/período
- ✅ Implementar formulário de avaliação
- ✅ Criar armazenamento de notas (reação, aprendizado)
- ✅ Desenvolver visualização de frequência por treinamento
- ✅ Testes de integração

**Entregáveis:**
- Controle de frequência detalhado
- Sistema de avaliações implementado

---

### FASE 6: Relatórios e Indicadores (2 semanas)

**Semana 8:**
- ✅ Implementar classe IndicadoresRH
- ✅ Desenvolver cálculo de todos os 6 indicadores
- ✅ Criar procedures SQL para performance
- ✅ Implementar dashboard visual
- ✅ Integrar biblioteca de gráficos (Chart.js)

**Semana 9:**
- ✅ Desenvolver relatórios mensais, trimestrais e anuais
- ✅ Implementar exportação para Excel (PHPSpreadsheet)
- ✅ Implementar exportação para PDF (TCPDF)
- ✅ Criar relatório comparativo entre períodos
- ✅ Desenvolver filtros avançados de relatórios
- ✅ Testes de cálculos e validação de fórmulas

**Entregáveis:**
- Dashboard com 6 indicadores
- Relatórios mensais/anuais
- Exportação Excel e PDF

---

### FASE 7: Testes e Refinamentos (1 semana)

**Semana 10:**
- ✅ Testes de integração completos
- ✅ Testes de carga e performance
- ✅ Correção de bugs identificados
- ✅ Ajustes de UX/UI
- ✅ Otimização de queries SQL
- ✅ Documentação final
- ✅ Deploy em ambiente de homologação

**Entregáveis:**
- Sistema 100% funcional
- Documentação completa
- Manual do usuário

---

### Timeline Visual

```
Mês 1         |  Mês 2         |  Mês 3
--------------|----------------|----------------
S1 S2 | S3    | S4 S5 | S6 S7  | S8 S9 | S10
──────┼───────┼───────┼────────┼───────┼────────
F1 F1 | F2    | F3 F3 | F4 F5  | F6 F6 | F7
Colabs| WP    | Treina| Notif  | Relat | Testes
      |       |       | Freq   |       |
```

**Total: 10 semanas (~2,5 meses)**

---

## Guia de Implementação

### Passo 1: Configuração Inicial

```bash
# 1. Criar estrutura de diretórios
mkdir -p sgc-treinamentos/{public,app/{config,classes,models,controllers,views,helpers},database,vendor,logs,temp,docs}

# 2. Navegar para o diretório
cd sgc-treinamentos

# 3. Inicializar Composer
composer init

# 4. Instalar dependências
composer require phpmailer/phpmailer
composer require phpoffice/phpspreadsheet
composer require tecnickcom/tcpdf
```

### Passo 2: Configurar Banco de Dados

```bash
# 1. Criar banco no MySQL
mysql -u root -p -e "CREATE DATABASE sgc_treinamentos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. Executar script SQL
mysql -u root -p sgc_treinamentos < database/schema.sql

# 3. Verificar tabelas criadas
mysql -u root -p sgc_treinamentos -e "SHOW TABLES;"
```

### Passo 3: Configurar Conexão

Criar arquivo `app/config/config.php`:

```php
<?php
// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'sgc_treinamentos');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Configurações da aplicação
define('BASE_URL', 'http://localhost/sgc-treinamentos/public/');
define('BASE_PATH', __DIR__ . '/../../');

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Autoload do Composer
require_once BASE_PATH . 'vendor/autoload.php';

// Sessão
session_start();
```

### Passo 4: Testar Conexão

Criar `public/test_connection.php`:

```php
<?php
require_once '../app/config/config.php';
require_once '../app/classes/Database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "✅ Conexão estabelecida com sucesso!<br>";
    
    // Testa query
    $stmt = $conn->query("SELECT COUNT(*) as total FROM colaboradores");
    $result = $stmt->fetch();
    
    echo "Total de colaboradores: " . $result['total'];
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage();
}
```

### Passo 5: Iniciar Desenvolvimento

```bash
# Acessar no navegador:
http://localhost/sgc-treinamentos/public/

# Primeiro login (usuário padrão):
# E-mail: admin@sgc.com
# Senha: admin123
```

---

## Comandos Úteis

### Git

```bash
# Inicializar repositório
git init

# Criar .gitignore
cat > .gitignore << EOL
/vendor/
/logs/*.log
/temp/*
.env
.DS_Store
Thumbs.db
EOL

# Primeiro commit
git add .
git commit -m "Initial commit - SGC Treinamentos"
```

### Composer

```bash
# Atualizar dependências
composer update

# Verificar autoload
composer dump-autoload
```

### MySQL

```bash
# Backup do banco
mysqldump -u root -p sgc_treinamentos > backup_$(date +%Y%m%d).sql

# Restaurar backup
mysql -u root -p sgc_treinamentos < backup_20250103.sql
```

---

## Checklist de Implementação

### ✅ Estrutura Base
- [ ] Estrutura de diretórios criada
- [ ] Banco de dados configurado
- [ ] Composer instalado e configurado
- [ ] Classe Database implementada
- [ ] Sistema de autenticação funcionando
- [ ] Layout base desenvolvido

### ✅ Módulo Colaboradores
- [ ] Model Colaborador criado
- [ ] Controller Colaborador criado
- [ ] CRUD completo implementado
- [ ] Validações funcionando
- [ ] Importação de planilha operacional

### ✅ Integração WordPress
- [ ] Classe WordPressSync implementada
- [ ] Configuração de credenciais funcional
- [ ] Sincronização manual testada
- [ ] Log de sincronização gravando
- [ ] Cron job configurado

### ✅ Módulo Treinamentos
- [ ] Model Treinamento criado
- [ ] Wizard de cadastro funcional
- [ ] 12 campos implementados
- [ ] Agendamento de datas operacional
- [ ] Vinculação de participantes testada

### ✅ Notificações
- [ ] PHPMailer configurado
- [ ] Classe NotificationManager implementada
- [ ] Template de convite criado
- [ ] Sistema de tokens funcional
- [ ] Check-in público testado

### ✅ Frequência
- [ ] Model Frequência criado
- [ ] Registro de presença operacional
- [ ] Avaliações implementadas
- [ ] Integração com participantes OK

### ✅ Relatórios
- [ ] Classe IndicadoresRH implementada
- [ ] 6 indicadores calculando corretamente
- [ ] Dashboard visual funcionando
- [ ] Gráficos renderizando
- [ ] Exportação Excel/PDF testada

### ✅ Testes Finais
- [ ] Todos os módulos integrados
- [ ] Performance otimizada
- [ ] Bugs corrigidos
- [ ] Documentação completa
- [ ] Deploy realizado

---

## Observações Importantes

### Segurança

- **SEMPRE** use prepared statements (PDO)
- **NUNCA** armazene senhas em texto plano (use `password_hash()`)
- **SEMPRE** valide e sanitize inputs do usuário
- **SEMPRE** use HTTPS em produção
- Implemente proteção contra SQL Injection, XSS e CSRF

### Performance

- Use índices no banco de dados
- Implemente paginação nas listagens
- Use cache quando apropriado
- Otimize queries N+1
- Minimize queries dentro de loops

### Manutenibilidade

- Siga padrões PSR (PSR-4, PSR-12)
- Documente código com PHPDoc
- Use nomes descritivos para variáveis e funções
- Mantenha funções pequenas e focadas
- Escreva código testável

---

## Próximos Passos Recomendados

1. **Validar** este documento com a equipe
2. **Configurar** ambiente de desenvolvimento
3. **Executar** script SQL do banco de dados
4. **Implementar** Fase 1 (Estrutura Base)
5. **Testar** cada módulo antes de avançar
6. **Documentar** decisões importantes
7. **Realizar** code review regular

---

## Contato e Suporte

Para dúvidas sobre implementação:
- Consultar documentação técnica fornecida
- Revisar este documento completo
- Verificar comentários no código

---

**Versão:** 1.0  
**Data:** 2025-11-03  
**Status:** Pronto para Implementação
