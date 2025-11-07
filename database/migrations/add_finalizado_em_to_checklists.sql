-- =====================================================
-- Migration: Adicionar campo finalizado_em
-- Data: 2025-11-07
-- Descrição: Adiciona coluna para registrar data/hora de finalização
-- =====================================================

ALTER TABLE checklists
ADD COLUMN finalizado_em DATETIME NULL AFTER updated_at;

-- Índice para facilitar busca por data de finalização
CREATE INDEX idx_finalizado_em ON checklists(finalizado_em);
