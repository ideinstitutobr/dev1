-- =====================================================
-- MIGRATION: Adiciona campo SETOR aos colaboradores
-- Data: 2025-11-05
-- Descrição: Cria coluna 'setor' e índice para consultas
-- =====================================================

ALTER TABLE colaboradores
    ADD COLUMN IF NOT EXISTS setor VARCHAR(100) NULL
    COMMENT 'Setor organizacional do colaborador'
    AFTER departamento;

-- Índice opcional para buscas por setor
CREATE INDEX IF NOT EXISTS idx_colaboradores_setor
    ON colaboradores (setor);

-- Fim da migration
