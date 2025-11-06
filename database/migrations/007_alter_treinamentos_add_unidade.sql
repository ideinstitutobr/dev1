-- =========================================================
-- MIGRATION: Adiciona campos de unidade aos treinamentos
-- Data: 2024-11-06
-- Descrição: Adiciona referência à unidade e setor de destino
--            do treinamento (onde será realizado)
-- =========================================================

-- Adicionar coluna unidade_destino_id
ALTER TABLE treinamentos
    ADD COLUMN IF NOT EXISTS unidade_destino_id INT DEFAULT NULL
    COMMENT 'Unidade onde o treinamento será realizado'
    AFTER local;

-- Adicionar coluna setor_destino
ALTER TABLE treinamentos
    ADD COLUMN IF NOT EXISTS setor_destino VARCHAR(100) DEFAULT NULL
    COMMENT 'Setor específico do treinamento (se aplicável)'
    AFTER unidade_destino_id;

-- Adicionar foreign key
ALTER TABLE treinamentos
    ADD CONSTRAINT fk_treinamentos_unidade_destino
    FOREIGN KEY (unidade_destino_id) REFERENCES unidades(id) ON DELETE SET NULL;

-- Adicionar índices
ALTER TABLE treinamentos
    ADD INDEX IF NOT EXISTS idx_unidade_destino (unidade_destino_id);

ALTER TABLE treinamentos
    ADD INDEX IF NOT EXISTS idx_setor_destino (setor_destino);
