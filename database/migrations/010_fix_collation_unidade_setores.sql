-- =========================================================
-- MIGRATION: Corrige collation da tabela unidade_setores
-- Data: 2025-11-06
-- Descrição: Altera collation da coluna 'setor' para utf8mb4_unicode_ci
--            para compatibilidade com field_categories
-- =========================================================

-- Altera collation da coluna setor em unidade_setores
ALTER TABLE unidade_setores
MODIFY COLUMN setor VARCHAR(100)
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci
NOT NULL
COMMENT 'Nome do setor (referencia field_categories.valor)';

-- Altera collation da coluna setor_principal em colaboradores (se existir)
-- Usa procedimento condicional pois a coluna pode não existir em todas as instalações
SET @preparedStatement = (
    SELECT IF(
        COUNT(*) > 0,
        'ALTER TABLE colaboradores
         MODIFY COLUMN setor_principal VARCHAR(100)
         CHARACTER SET utf8mb4
         COLLATE utf8mb4_unicode_ci',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'colaboradores'
    AND COLUMN_NAME = 'setor_principal'
);

PREPARE alterStatement FROM @preparedStatement;
EXECUTE alterStatement;
DEALLOCATE PREPARE alterStatement;
