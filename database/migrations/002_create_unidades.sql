-- =========================================================
-- MIGRATION: Cria tabela unidades
-- Data: 2024-11-06
-- Descrição: Cadastro principal de unidades/lojas da empresa
-- =========================================================

CREATE TABLE IF NOT EXISTS unidades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(200) NOT NULL,
    codigo VARCHAR(50) UNIQUE COMMENT 'Código identificador da unidade',
    categoria_local_id INT NOT NULL,

    -- Dados de endereço
    endereco VARCHAR(255),
    numero VARCHAR(20),
    complemento VARCHAR(100),
    bairro VARCHAR(100),
    cidade VARCHAR(100),
    estado CHAR(2),
    cep VARCHAR(10),

    -- Dados de contato
    telefone VARCHAR(20),
    email VARCHAR(100),

    -- Dados operacionais
    data_inauguracao DATE,
    area_m2 DECIMAL(10,2) COMMENT 'Área em metros quadrados',
    capacidade_pessoas INT COMMENT 'Capacidade de pessoas',
    observacoes TEXT,

    -- Controle
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (categoria_local_id) REFERENCES categorias_local_unidade(id),
    INDEX idx_nome (nome),
    INDEX idx_codigo (codigo),
    INDEX idx_cidade (cidade),
    INDEX idx_estado (estado),
    INDEX idx_ativo (ativo),
    INDEX idx_categoria (categoria_local_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Unidades/Lojas da empresa';
