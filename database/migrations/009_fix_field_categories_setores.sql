-- =========================================================
-- MIGRATION: Corrige tabela field_categories para setores
-- Data: 2025-11-06
-- Descrição: Adiciona coluna descricao e atualiza ENUM tipo
--            para incluir 'setor'
-- =========================================================

-- 1. Adicionar coluna descricao se não existir
ALTER TABLE field_categories
ADD COLUMN IF NOT EXISTS descricao TEXT COMMENT 'Descrição detalhada da categoria';

-- 2. Modificar ENUM para incluir 'setor'
ALTER TABLE field_categories
MODIFY COLUMN tipo ENUM('cargo', 'departamento', 'setor') NOT NULL COMMENT 'Tipo de categoria';

-- 3. Atualizar índice único para permitir setores
-- (O índice UNIQUE KEY unique_tipo_valor já existe e funciona)
