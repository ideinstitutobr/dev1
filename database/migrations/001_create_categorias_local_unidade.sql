-- =========================================================
-- MIGRATION: Cria tabela categorias_local_unidade
-- Data: 2024-11-06
-- Descrição: Gerenciar categorias de locais para unidades
--            (Matriz, Filial, Shopping, etc)
-- =========================================================

CREATE TABLE IF NOT EXISTS categorias_local_unidade (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    descricao TEXT,
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ativo (ativo),
    INDEX idx_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Categorias de locais para unidades';

-- Dados iniciais
INSERT INTO categorias_local_unidade (nome, descricao) VALUES
('Matriz', 'Sede principal da empresa'),
('Filial', 'Unidade filial'),
('Franquia', 'Unidade franqueada'),
('Shopping', 'Loja em shopping center'),
('Centro Comercial', 'Loja em centro comercial'),
('Rua', 'Loja de rua'),
('Outlet', 'Loja outlet')
ON DUPLICATE KEY UPDATE nome = nome;
