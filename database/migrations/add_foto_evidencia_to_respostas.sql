-- =====================================================
-- Migration: Adicionar campo foto_evidencia
-- Data: 2025-11-07
-- Descrição: Adiciona coluna para armazenar foto de evidência nas respostas
-- =====================================================

ALTER TABLE respostas_checklist
ADD COLUMN foto_evidencia VARCHAR(255) NULL AFTER observacao;

-- Índice para facilitar busca por respostas com foto
CREATE INDEX idx_foto_evidencia ON respostas_checklist(foto_evidencia);
