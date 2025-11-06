-- =========================================================
-- MIGRATION: Adiciona campos de unidade aos colaboradores
-- Data: 2024-11-06
-- Descrição: Adiciona referência à unidade e setor principal
--            do colaborador (desnormalizado para performance)
-- =========================================================

-- Adicionar coluna unidade_principal_id (se não existir)
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'colaboradores'
    AND COLUMN_NAME = 'unidade_principal_id');

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE colaboradores ADD COLUMN unidade_principal_id INT DEFAULT NULL COMMENT "Unidade principal do colaborador" AFTER departamento',
    'SELECT "Coluna unidade_principal_id já existe"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar coluna setor_principal (se não existir)
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'colaboradores'
    AND COLUMN_NAME = 'setor_principal');

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE colaboradores ADD COLUMN setor_principal VARCHAR(100) DEFAULT NULL COMMENT "Setor principal (desnormalizado para performance)" AFTER unidade_principal_id',
    'SELECT "Coluna setor_principal já existe"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar foreign key (se não existir)
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'colaboradores'
    AND CONSTRAINT_NAME = 'fk_colaboradores_unidade_principal');

SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE colaboradores ADD CONSTRAINT fk_colaboradores_unidade_principal FOREIGN KEY (unidade_principal_id) REFERENCES unidades(id) ON DELETE SET NULL',
    'SELECT "Foreign key fk_colaboradores_unidade_principal já existe"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar índice unidade_principal (se não existir)
SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'colaboradores'
    AND INDEX_NAME = 'idx_unidade_principal');

SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE colaboradores ADD INDEX idx_unidade_principal (unidade_principal_id)',
    'SELECT "Índice idx_unidade_principal já existe"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar índice setor_principal (se não existir)
SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'colaboradores'
    AND INDEX_NAME = 'idx_setor_principal');

SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE colaboradores ADD INDEX idx_setor_principal (setor_principal)',
    'SELECT "Índice idx_setor_principal já existe"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
