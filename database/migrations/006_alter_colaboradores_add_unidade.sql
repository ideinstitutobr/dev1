-- =========================================================
-- MIGRATION: Adiciona campos de unidade aos colaboradores
-- Data: 2024-11-06
-- Descrição: Adiciona referência à unidade e setor principal
--            do colaborador (desnormalizado para performance)
-- =========================================================

-- Adicionar coluna unidade_principal_id
ALTER TABLE colaboradores
    ADD COLUMN IF NOT EXISTS unidade_principal_id INT DEFAULT NULL
    COMMENT 'Unidade principal do colaborador'
    AFTER departamento;

-- Adicionar coluna setor_principal
ALTER TABLE colaboradores
    ADD COLUMN IF NOT EXISTS setor_principal VARCHAR(100) DEFAULT NULL
    COMMENT 'Setor principal (desnormalizado para performance)'
    AFTER unidade_principal_id;

-- Adicionar foreign key
ALTER TABLE colaboradores
    ADD CONSTRAINT fk_colaboradores_unidade_principal
    FOREIGN KEY (unidade_principal_id) REFERENCES unidades(id) ON DELETE SET NULL;

-- Adicionar índices
ALTER TABLE colaboradores
    ADD INDEX IF NOT EXISTS idx_unidade_principal (unidade_principal_id);

ALTER TABLE colaboradores
    ADD INDEX IF NOT EXISTS idx_setor_principal (setor_principal);
