-- =====================================================
-- SISTEMA DE CHECKLIST DE LOJAS
-- Descrição: Sistema completo de avaliação de lojas
-- Versão: 1.0
-- Data: 2025-11-07
-- =====================================================

-- =====================================================
-- TABELA: lojas
-- Descrição: Cadastro de lojas/unidades
-- =====================================================
CREATE TABLE IF NOT EXISTS lojas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    codigo VARCHAR(20) UNIQUE,
    endereco TEXT,
    cidade VARCHAR(100),
    estado VARCHAR(2),
    ativo BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_nome (nome),
    INDEX idx_codigo (codigo),
    INDEX idx_cidade (cidade),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: cargos_checklist
-- Descrição: Cargos com permissões para checklist
-- =====================================================
CREATE TABLE IF NOT EXISTS cargos_checklist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(50) NOT NULL,
    nivel_acesso ENUM('supervisor', 'gerente', 'administrador') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_nivel (nivel_acesso)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: modulos_avaliacao
-- Descrição: Módulos/setores a serem avaliados
-- =====================================================
CREATE TABLE IF NOT EXISTS modulos_avaliacao (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    total_perguntas INT NOT NULL,
    peso_por_pergunta DECIMAL(5,3) NOT NULL,
    ordem INT,
    ativo BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_ordem (ordem),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: perguntas
-- Descrição: Perguntas de cada módulo de avaliação
-- =====================================================
CREATE TABLE IF NOT EXISTS perguntas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    modulo_id INT NOT NULL,
    texto TEXT NOT NULL,
    descricao TEXT,
    ordem INT,
    obrigatoria BOOLEAN DEFAULT 1,
    permite_foto BOOLEAN DEFAULT 1,
    ativo BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (modulo_id) REFERENCES modulos_avaliacao(id) ON DELETE CASCADE,
    INDEX idx_modulo (modulo_id),
    INDEX idx_ordem (ordem),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: checklists
-- Descrição: Registro de checklists realizados
-- =====================================================
CREATE TABLE IF NOT EXISTS checklists (
    id INT PRIMARY KEY AUTO_INCREMENT,
    loja_id INT NOT NULL,
    colaborador_id INT NOT NULL,
    data_avaliacao DATE NOT NULL,
    modulo_id INT NOT NULL,
    pontuacao_total DECIMAL(4,2) DEFAULT 0,
    pontuacao_maxima DECIMAL(4,2) DEFAULT 5,
    percentual DECIMAL(5,2) DEFAULT 0,
    atingiu_meta BOOLEAN DEFAULT 0,
    observacoes_gerais TEXT,
    status ENUM('rascunho', 'finalizado', 'revisado') DEFAULT 'rascunho',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (loja_id) REFERENCES lojas(id),
    FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id),
    FOREIGN KEY (modulo_id) REFERENCES modulos_avaliacao(id),
    UNIQUE KEY unique_checklist (loja_id, data_avaliacao, modulo_id),
    INDEX idx_loja (loja_id),
    INDEX idx_colaborador (colaborador_id),
    INDEX idx_data (data_avaliacao),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: respostas_checklist
-- Descrição: Respostas de cada pergunta do checklist
-- =====================================================
CREATE TABLE IF NOT EXISTS respostas_checklist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    checklist_id INT NOT NULL,
    pergunta_id INT NOT NULL,
    estrelas INT NOT NULL CHECK (estrelas BETWEEN 1 AND 5),
    pontuacao DECIMAL(5,3) NOT NULL,
    observacao TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (checklist_id) REFERENCES checklists(id) ON DELETE CASCADE,
    FOREIGN KEY (pergunta_id) REFERENCES perguntas(id),
    INDEX idx_checklist (checklist_id),
    INDEX idx_pergunta (pergunta_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: fotos_checklist
-- Descrição: Fotos anexadas às respostas
-- =====================================================
CREATE TABLE IF NOT EXISTS fotos_checklist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    resposta_id INT NOT NULL,
    caminho VARCHAR(255) NOT NULL,
    legenda TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (resposta_id) REFERENCES respostas_checklist(id) ON DELETE CASCADE,
    INDEX idx_resposta (resposta_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: configuracoes_sistema
-- Descrição: Configurações do sistema de pontuação
-- =====================================================
CREATE TABLE IF NOT EXISTS configuracoes_sistema (
    id INT PRIMARY KEY AUTO_INCREMENT,
    chave VARCHAR(50) UNIQUE NOT NULL,
    valor TEXT,
    descricao TEXT,
    tipo ENUM('int', 'decimal', 'string', 'boolean') DEFAULT 'string',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_chave (chave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSERIR CONFIGURAÇÕES PADRÃO
-- =====================================================
INSERT INTO configuracoes_sistema (chave, valor, descricao, tipo) VALUES
('meta_minima_estrelas', '4', 'Meta mínima de estrelas para aprovação', 'int'),
('peso_8_perguntas_1_estrela', '0.125', 'Peso para 1 estrela (8 perguntas)', 'decimal'),
('peso_8_perguntas_2_estrelas', '0.25', 'Peso para 2 estrelas (8 perguntas)', 'decimal'),
('peso_8_perguntas_3_estrelas', '0.375', 'Peso para 3 estrelas (8 perguntas)', 'decimal'),
('peso_8_perguntas_4_estrelas', '0.5', 'Peso para 4 estrelas (8 perguntas)', 'decimal'),
('peso_8_perguntas_5_estrelas', '0.625', 'Peso para 5 estrelas (8 perguntas)', 'decimal'),
('peso_6_perguntas_1_estrela', '0.167', 'Peso para 1 estrela (6 perguntas)', 'decimal'),
('peso_6_perguntas_2_estrelas', '0.333', 'Peso para 2 estrelas (6 perguntas)', 'decimal'),
('peso_6_perguntas_3_estrelas', '0.500', 'Peso para 3 estrelas (6 perguntas)', 'decimal'),
('peso_6_perguntas_4_estrelas', '0.667', 'Peso para 4 estrelas (6 perguntas)', 'decimal'),
('peso_6_perguntas_5_estrelas', '0.833', 'Peso para 5 estrelas (6 perguntas)', 'decimal')
ON DUPLICATE KEY UPDATE valor=VALUES(valor);

-- =====================================================
-- INSERIR CARGOS PADRÃO
-- =====================================================
INSERT INTO cargos_checklist (nome, nivel_acesso) VALUES
('Supervisor', 'supervisor'),
('Gerente de Loja', 'gerente'),
('Gerente Regional', 'gerente'),
('Administrador', 'administrador')
ON DUPLICATE KEY UPDATE nivel_acesso=VALUES(nivel_acesso);

-- =====================================================
-- FIM DO SCRIPT
-- =====================================================
