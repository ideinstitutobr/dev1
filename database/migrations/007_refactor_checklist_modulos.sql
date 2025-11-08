-- =====================================================
-- MIGRAÇÃO: Refatorar Checklists para Avaliar Todos os Módulos
-- Descrição: Remove modulo_id e adiciona responsavel_id
-- Versão: 007
-- Data: 2025-11-08
-- =====================================================

-- =====================================================
-- 1. Adicionar coluna responsavel_id
-- =====================================================
ALTER TABLE checklists
ADD COLUMN responsavel_id INT NULL
COMMENT 'Liderança responsável pela unidade (gerente/supervisor)'
AFTER colaborador_id;

-- =====================================================
-- 2. Adicionar foreign key para responsavel_id
-- =====================================================
ALTER TABLE checklists
ADD CONSTRAINT fk_checklist_responsavel
FOREIGN KEY (responsavel_id) REFERENCES colaboradores(id)
ON DELETE RESTRICT ON UPDATE CASCADE;

-- =====================================================
-- 3. Criar índice para responsavel_id
-- =====================================================
CREATE INDEX idx_responsavel_id ON checklists(responsavel_id);

-- =====================================================
-- 4. Remover foreign key antiga do modulo_id
-- =====================================================
-- Primeiro descobrir o nome da constraint
SET @constraint_name = (
    SELECT CONSTRAINT_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'checklists'
    AND COLUMN_NAME = 'modulo_id'
    AND REFERENCED_TABLE_NAME IS NOT NULL
    LIMIT 1
);

-- Remover a constraint se existir
SET @sql = IF(@constraint_name IS NOT NULL,
    CONCAT('ALTER TABLE checklists DROP FOREIGN KEY ', @constraint_name),
    'SELECT "No FK constraint found for modulo_id"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- 5. Remover coluna modulo_id
-- =====================================================
-- A coluna modulo_id não é mais necessária porque agora
-- um checklist avalia TODOS os módulos ativos de uma vez
ALTER TABLE checklists DROP COLUMN modulo_id;

-- =====================================================
-- OBSERVAÇÕES:
-- =====================================================
-- 1. Checklists antigos que tinham modulo_id serão mantidos
--    mas sem essa referência (compatibilidade retroativa)
--
-- 2. Novos checklists precisarão ter responsavel_id preenchido
--    (pode ser NULL para manter compatibilidade)
--
-- 3. A pontuação agora será calculada com base em TODAS as
--    perguntas de TODOS os módulos ativos
-- =====================================================
