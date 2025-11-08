-- =====================================================
-- Script: Limpar e Recriar Estrutura de Avaliações
-- Data: 2025-11-08
-- Descrição: Remove todos os dados de avaliações, módulos e perguntas
--            para permitir recriação da estrutura do zero
-- =====================================================

-- ATENÇÃO: Este script irá DELETAR PERMANENTEMENTE todos os dados de:
-- - Respostas de checklist
-- - Checklists
-- - Perguntas
-- - Módulos de avaliação

-- Execute este script APENAS se tiver certeza que deseja limpar os dados!

-- =====================================================
-- PASSO 1: Deletar respostas (dependências primeiro)
-- =====================================================
DELETE FROM respostas_checklist;
ALTER TABLE respostas_checklist AUTO_INCREMENT = 1;
SELECT 'Respostas de checklist deletadas' AS status;

-- =====================================================
-- PASSO 2: Deletar checklists
-- =====================================================
DELETE FROM checklists;
ALTER TABLE checklists AUTO_INCREMENT = 1;
SELECT 'Checklists deletados' AS status;

-- =====================================================
-- PASSO 3: Deletar perguntas
-- =====================================================
DELETE FROM perguntas;
ALTER TABLE perguntas AUTO_INCREMENT = 1;
SELECT 'Perguntas deletadas' AS status;

-- =====================================================
-- PASSO 4: Deletar módulos
-- =====================================================
DELETE FROM modulos_avaliacao;
ALTER TABLE modulos_avaliacao AUTO_INCREMENT = 1;
SELECT 'Módulos de avaliação deletados' AS status;

-- =====================================================
-- CONFIRMAÇÃO
-- =====================================================
SELECT
    'Limpeza concluída! Banco de dados pronto para receber nova estrutura.' AS mensagem,
    (SELECT COUNT(*) FROM modulos_avaliacao) AS total_modulos,
    (SELECT COUNT(*) FROM perguntas) AS total_perguntas,
    (SELECT COUNT(*) FROM checklists) AS total_checklists,
    (SELECT COUNT(*) FROM respostas_checklist) AS total_respostas;
