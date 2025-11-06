-- =========================================================
-- MIGRATION: Adiciona campos de unidade aos treinamentos
-- Data: 2024-11-06
-- Descrição: Adiciona referência à unidade e setor de destino
--            do treinamento (onde será realizado)
-- =========================================================

-- Adicionar coluna unidade_destino_id (se não existir)
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'treinamentos'
    AND COLUMN_NAME = 'unidade_destino_id');

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE treinamentos ADD COLUMN unidade_destino_id INT DEFAULT NULL COMMENT "Unidade onde o treinamento será realizado" AFTER local',
    'SELECT "Coluna unidade_destino_id já existe"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar coluna setor_destino (se não existir)
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'treinamentos'
    AND COLUMN_NAME = 'setor_destino');

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE treinamentos ADD COLUMN setor_destino VARCHAR(100) DEFAULT NULL COMMENT "Setor específico do treinamento (se aplicável)" AFTER unidade_destino_id',
    'SELECT "Coluna setor_destino já existe"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar foreign key (se não existir)
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'treinamentos'
    AND CONSTRAINT_NAME = 'fk_treinamentos_unidade_destino');

SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE treinamentos ADD CONSTRAINT fk_treinamentos_unidade_destino FOREIGN KEY (unidade_destino_id) REFERENCES unidades(id) ON DELETE SET NULL',
    'SELECT "Foreign key fk_treinamentos_unidade_destino já existe"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar índice unidade_destino (se não existir)
SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'treinamentos'
    AND INDEX_NAME = 'idx_unidade_destino');

SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE treinamentos ADD INDEX idx_unidade_destino (unidade_destino_id)',
    'SELECT "Índice idx_unidade_destino já existe"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar índice setor_destino (se não existir)
SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'treinamentos'
    AND INDEX_NAME = 'idx_setor_destino');

SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE treinamentos ADD INDEX idx_setor_destino (setor_destino)',
    'SELECT "Índice idx_setor_destino já existe"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
