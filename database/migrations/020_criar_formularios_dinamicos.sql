-- =====================================================
-- MIGRAÇÃO: Criar Sistema de Formulários Dinâmicos
-- Descrição: Tabelas completas do novo módulo
-- Versão: 020
-- Data: 2025-11-09
-- Estratégia: Módulo paralelo (sem conflitos)
-- =====================================================

-- =====================================================
-- TABELA 1: formularios_dinamicos
-- Descrição: Formulários criados no sistema
-- =====================================================
CREATE TABLE IF NOT EXISTS formularios_dinamicos (
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
    INDEX idx_usuario (usuario_id),
    INDEX idx_datas (data_inicio, data_fim)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Formulários dinâmicos criados no sistema';

-- =====================================================
-- TABELA 2: form_secoes
-- Descrição: Seções/agrupamentos de perguntas
-- =====================================================
CREATE TABLE IF NOT EXISTS form_secoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formulario_id INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    ordem INT NOT NULL DEFAULT 0,
    peso DECIMAL(5,2) DEFAULT 1.00 COMMENT 'Peso para cálculo de pontuação',
    cor VARCHAR(7) DEFAULT '#007bff' COMMENT 'Cor hexadecimal para UI',
    icone VARCHAR(50) COMMENT 'Ícone FontAwesome',
    visivel BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (formulario_id) REFERENCES formularios_dinamicos(id) ON DELETE CASCADE,

    INDEX idx_formulario_ordem (formulario_id, ordem),
    INDEX idx_visivel (visivel)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Seções dos formulários dinâmicos';

-- =====================================================
-- TABELA 3: form_perguntas
-- Descrição: Perguntas de cada seção
-- IMPORTANTE: Nome diferente de "perguntas" para evitar conflito
-- =====================================================
CREATE TABLE IF NOT EXISTS form_perguntas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    secao_id INT NOT NULL,
    tipo_pergunta ENUM(
        'texto_curto',      -- Input de texto
        'texto_longo',      -- Textarea
        'multipla_escolha', -- Radio buttons
        'caixas_selecao',   -- Checkboxes
        'lista_suspensa',   -- Select dropdown
        'escala_linear',    -- Escala de 0-10
        'grade_multipla',   -- Grid de opções
        'data',             -- Date picker
        'hora',             -- Time picker
        'arquivo'           -- Upload de arquivo
    ) NOT NULL,
    pergunta TEXT NOT NULL,
    descricao TEXT COMMENT 'Texto de ajuda/instrução',
    ordem INT NOT NULL DEFAULT 0,
    obrigatoria BOOLEAN DEFAULT FALSE,
    peso DECIMAL(5,2) DEFAULT 1.00 COMMENT 'Peso para cálculo de pontuação',
    pontuacao_maxima DECIMAL(10,2) DEFAULT 0,
    tem_pontuacao BOOLEAN DEFAULT FALSE,
    config_adicional JSON COMMENT 'Configurações específicas por tipo',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (secao_id) REFERENCES form_secoes(id) ON DELETE CASCADE,

    INDEX idx_secao_ordem (secao_id, ordem),
    INDEX idx_tipo (tipo_pergunta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Perguntas dos formulários dinâmicos';

-- =====================================================
-- TABELA 4: form_opcoes_resposta
-- Descrição: Opções para perguntas de múltipla escolha
-- =====================================================
CREATE TABLE IF NOT EXISTS form_opcoes_resposta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pergunta_id INT NOT NULL,
    texto_opcao VARCHAR(500) NOT NULL,
    ordem INT NOT NULL DEFAULT 0,
    pontuacao DECIMAL(10,2) DEFAULT 0 COMMENT 'Pontos se essa opção for selecionada',
    vai_para_secao INT NULL COMMENT 'Lógica condicional: ir para seção X',
    vai_para_pergunta INT NULL COMMENT 'Lógica condicional: ir para pergunta Y',
    cor VARCHAR(7) COMMENT 'Cor da opção (opcional)',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (pergunta_id) REFERENCES form_perguntas(id) ON DELETE CASCADE,
    FOREIGN KEY (vai_para_secao) REFERENCES form_secoes(id) ON DELETE SET NULL,
    FOREIGN KEY (vai_para_pergunta) REFERENCES form_perguntas(id) ON DELETE SET NULL,

    INDEX idx_pergunta_ordem (pergunta_id, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Opções de resposta para múltipla escolha';

-- =====================================================
-- TABELA 5: form_respostas
-- Descrição: Respostas de formulários preenchidos
-- =====================================================
CREATE TABLE IF NOT EXISTS form_respostas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formulario_id INT NOT NULL,
    respondente_email VARCHAR(255) COMMENT 'Email do respondente (opcional)',
    respondente_nome VARCHAR(255) COMMENT 'Nome do respondente (opcional)',
    respondente_ip VARCHAR(45) COMMENT 'IP para auditoria',
    pontuacao_total DECIMAL(10,2) DEFAULT 0,
    percentual_acerto DECIMAL(5,2) DEFAULT 0,
    status_resposta ENUM('em_andamento', 'concluida', 'incompleta') DEFAULT 'em_andamento',
    tempo_resposta INT COMMENT 'Tempo total em segundos',
    iniciado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    concluido_em TIMESTAMP NULL,

    FOREIGN KEY (formulario_id) REFERENCES formularios_dinamicos(id) ON DELETE CASCADE,

    INDEX idx_formulario (formulario_id),
    INDEX idx_email (respondente_email),
    INDEX idx_status (status_resposta),
    INDEX idx_datas (iniciado_em, concluido_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Respostas enviadas aos formulários';

-- =====================================================
-- TABELA 6: form_respostas_detalhes
-- Descrição: Respostas individuais de cada pergunta
-- =====================================================
CREATE TABLE IF NOT EXISTS form_respostas_detalhes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resposta_id INT NOT NULL,
    pergunta_id INT NOT NULL,
    opcao_id INT NULL COMMENT 'ID da opção selecionada (múltipla escolha)',
    valor_texto TEXT COMMENT 'Resposta de texto',
    valor_numero DECIMAL(10,2) COMMENT 'Resposta numérica (escala)',
    valor_data DATE COMMENT 'Resposta de data',
    arquivo_path VARCHAR(500) COMMENT 'Caminho do arquivo enviado',
    pontuacao_obtida DECIMAL(10,2) DEFAULT 0,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (resposta_id) REFERENCES form_respostas(id) ON DELETE CASCADE,
    FOREIGN KEY (pergunta_id) REFERENCES form_perguntas(id) ON DELETE CASCADE,
    FOREIGN KEY (opcao_id) REFERENCES form_opcoes_resposta(id) ON DELETE SET NULL,

    INDEX idx_resposta (resposta_id),
    INDEX idx_pergunta (pergunta_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Detalhes das respostas (uma linha por pergunta)';

-- =====================================================
-- TABELA 7: form_faixas_pontuacao
-- Descrição: Faixas de classificação por pontuação
-- =====================================================
CREATE TABLE IF NOT EXISTS form_faixas_pontuacao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formulario_id INT NOT NULL,
    titulo VARCHAR(255) NOT NULL COMMENT 'Ex: Crítico, Regular, Bom, Excelente',
    pontuacao_minima DECIMAL(10,2) NOT NULL,
    pontuacao_maxima DECIMAL(10,2) NOT NULL,
    percentual_minimo DECIMAL(5,2) COMMENT 'Percentual mínimo (0-100)',
    percentual_maximo DECIMAL(5,2) COMMENT 'Percentual máximo (0-100)',
    mensagem TEXT COMMENT 'Mensagem exibida ao atingir essa faixa',
    recomendacoes TEXT COMMENT 'Recomendações de melhoria',
    cor VARCHAR(7) DEFAULT '#28a745' COMMENT 'Cor da faixa',
    ordem INT DEFAULT 0,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (formulario_id) REFERENCES formularios_dinamicos(id) ON DELETE CASCADE,

    INDEX idx_formulario (formulario_id),
    INDEX idx_pontuacao (pontuacao_minima, pontuacao_maxima),
    INDEX idx_percentual (percentual_minimo, percentual_maximo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Faixas de pontuação e classificação';

-- =====================================================
-- TABELA 8: form_compartilhamentos
-- Descrição: Compartilhamento de formulários entre usuários
-- =====================================================
CREATE TABLE IF NOT EXISTS form_compartilhamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formulario_id INT NOT NULL,
    usuario_id INT NOT NULL,
    nivel_permissao ENUM('visualizar', 'editar', 'gerenciar') DEFAULT 'visualizar',
    compartilhado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (formulario_id) REFERENCES formularios_dinamicos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios_sistema(id) ON DELETE CASCADE,

    UNIQUE KEY uk_form_user (formulario_id, usuario_id),
    INDEX idx_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Compartilhamento de formulários';

-- =====================================================
-- INSERIR DADOS DE EXEMPLO (OPCIONAL)
-- =====================================================

-- Inserir formulário de exemplo
INSERT INTO formularios_dinamicos (
    titulo, descricao, slug, usuario_id, status, tipo_pontuacao
) VALUES (
    'Formulário de Exemplo',
    'Este é um formulário de exemplo para demonstração do sistema',
    'formulario-exemplo',
    1,  -- Assumindo que existe usuário com ID 1
    'rascunho',
    'soma_simples'
);

SET @form_id = LAST_INSERT_ID();

-- Inserir seção de exemplo
INSERT INTO form_secoes (formulario_id, titulo, descricao, ordem, peso) VALUES
(@form_id, 'Dados Gerais', 'Informações básicas', 1, 1.00);

SET @secao_id = LAST_INSERT_ID();

-- Inserir perguntas de exemplo
INSERT INTO form_perguntas (secao_id, tipo_pergunta, pergunta, ordem, obrigatoria) VALUES
(@secao_id, 'texto_curto', 'Qual é o seu nome?', 1, TRUE),
(@secao_id, 'texto_longo', 'Conte-nos sobre sua experiência', 2, FALSE),
(@secao_id, 'multipla_escolha', 'Como você avalia nosso serviço?', 3, TRUE);

SET @pergunta_mc = LAST_INSERT_ID();

-- Inserir opções para múltipla escolha
INSERT INTO form_opcoes_resposta (pergunta_id, texto_opcao, ordem, pontuacao) VALUES
(@pergunta_mc, 'Excelente', 1, 10),
(@pergunta_mc, 'Bom', 2, 7),
(@pergunta_mc, 'Regular', 3, 4),
(@pergunta_mc, 'Ruim', 4, 0);

-- Inserir faixas de pontuação
INSERT INTO form_faixas_pontuacao (formulario_id, titulo, pontuacao_minima, pontuacao_maxima, cor, ordem) VALUES
(@form_id, 'Crítico', 0, 25, '#dc3545', 1),
(@form_id, 'Regular', 26, 50, '#ffc107', 2),
(@form_id, 'Bom', 51, 75, '#17a2b8', 3),
(@form_id, 'Excelente', 76, 100, '#28a745', 4);

-- =====================================================
-- VERIFICAÇÕES PÓS-CRIAÇÃO
-- =====================================================

-- Contar tabelas criadas
SELECT COUNT(*) as 'Tabelas form_* criadas'
FROM information_schema.tables
WHERE table_schema = DATABASE()
AND table_name LIKE 'form_%';

-- Listar todas as tabelas do sistema
SELECT
    table_name as 'Tabela',
    table_rows as 'Linhas',
    ROUND((data_length + index_length) / 1024 / 1024, 2) as 'Tamanho (MB)'
FROM information_schema.tables
WHERE table_schema = DATABASE()
AND (
    table_name LIKE 'form_%'
    OR table_name = 'formularios_dinamicos'
)
ORDER BY table_name;

-- =====================================================
-- OBSERVAÇÕES IMPORTANTES
-- =====================================================

-- 1. Este script cria 8 tabelas novas com prefixo "form_"
-- 2. A tabela principal é "formularios_dinamicos" (sem prefixo)
-- 3. Não afeta NENHUMA tabela existente do sistema de checklists
-- 4. Todas as foreign keys usam ON DELETE CASCADE para integridade
-- 5. Índices otimizados para queries comuns
-- 6. Charset UTF-8 para suportar caracteres especiais
-- 7. Engine InnoDB para suporte a transações

-- =====================================================
-- ROLLBACK (SE NECESSÁRIO)
-- =====================================================

-- Para reverter esta migração, execute:
-- DROP TABLE IF EXISTS form_compartilhamentos;
-- DROP TABLE IF EXISTS form_faixas_pontuacao;
-- DROP TABLE IF EXISTS form_respostas_detalhes;
-- DROP TABLE IF EXISTS form_respostas;
-- DROP TABLE IF EXISTS form_opcoes_resposta;
-- DROP TABLE IF EXISTS form_perguntas;
-- DROP TABLE IF EXISTS form_secoes;
-- DROP TABLE IF EXISTS formularios_dinamicos;

-- =====================================================
-- FIM DA MIGRAÇÃO
-- =====================================================

SELECT '✓ Migração 020 executada com sucesso!' as 'Status';
SELECT '✓ Sistema de Formulários Dinâmicos criado' as 'Módulo';
SELECT '✓ Sistema de Checklists não foi afetado' as 'Compatibilidade';
