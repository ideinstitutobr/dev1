-- =========================================================
-- MIGRATION: Cria tabela unidade_colaboradores
-- Data: 2024-11-06
-- Descrição: Vinculação de colaboradores aos setores das unidades
-- =========================================================

CREATE TABLE IF NOT EXISTS unidade_colaboradores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unidade_id INT NOT NULL,
    colaborador_id INT NOT NULL,
    unidade_setor_id INT NOT NULL COMMENT 'Setor específico da unidade',
    cargo_especifico VARCHAR(100) COMMENT 'Cargo específico nesta unidade/setor',
    data_vinculacao DATE NOT NULL,
    data_desvinculacao DATE DEFAULT NULL,
    is_vinculo_principal TINYINT(1) DEFAULT 0 COMMENT 'Se é a unidade principal do colaborador',
    observacoes TEXT,
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (unidade_id) REFERENCES unidades(id) ON DELETE CASCADE,
    FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id) ON DELETE CASCADE,
    FOREIGN KEY (unidade_setor_id) REFERENCES unidade_setores(id) ON DELETE RESTRICT,
    UNIQUE KEY unique_vinculo_ativo (unidade_id, colaborador_id, unidade_setor_id, ativo),
    INDEX idx_unidade (unidade_id),
    INDEX idx_colaborador (colaborador_id),
    INDEX idx_setor (unidade_setor_id),
    INDEX idx_principal (is_vinculo_principal),
    INDEX idx_ativo (ativo),
    INDEX idx_data_vinculacao (data_vinculacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Colaboradores vinculados aos setores das unidades';
