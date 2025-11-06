-- =========================================================
-- MIGRATION: Cria tabela unidade_setores
-- Data: 2024-11-06
-- Descrição: Vincular setores disponíveis em cada unidade
--            Integra com field_categories tipo='setor'
-- =========================================================

CREATE TABLE IF NOT EXISTS unidade_setores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unidade_id INT NOT NULL,
    setor VARCHAR(100) NOT NULL COMMENT 'Nome do setor (referencia field_categories.valor)',
    descricao TEXT COMMENT 'Descrição específica do setor nesta unidade',
    responsavel_colaborador_id INT DEFAULT NULL COMMENT 'Responsável pelo setor',
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (unidade_id) REFERENCES unidades(id) ON DELETE CASCADE,
    FOREIGN KEY (responsavel_colaborador_id) REFERENCES colaboradores(id) ON DELETE SET NULL,
    UNIQUE KEY unique_unidade_setor (unidade_id, setor),
    INDEX idx_unidade (unidade_id),
    INDEX idx_setor (setor),
    INDEX idx_responsavel (responsavel_colaborador_id),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Setores ativos em cada unidade';
