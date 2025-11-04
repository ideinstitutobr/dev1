-- ============================================
-- Migration: Campos da Matriz de Capacitações
-- Descrição: Adiciona/corrige os 12 campos conforme plano original
-- Data: 04/01/2025
-- ============================================

-- IMPORTANTE: Executar esta migration ANTES de usar o formulário atualizado

-- ============================================
-- ETAPA 1: Corrigir ENUM do campo 'tipo'
-- ============================================

-- Alterar valores do ENUM 'tipo' de (Interno/Externo) para (Normativos/Comportamentais/Técnicos)
ALTER TABLE treinamentos
MODIFY COLUMN tipo ENUM('Normativos', 'Comportamentais', 'Técnicos') NOT NULL
COMMENT 'Campo 2: Tipo do treinamento';

-- ============================================
-- ETAPA 2: Adicionar campo 'modalidade' (NOVO)
-- ============================================

-- Campo 13: Modalidade do treinamento
ALTER TABLE treinamentos
ADD COLUMN modalidade ENUM('Presencial', 'Híbrido', 'Remoto') DEFAULT 'Presencial'
COMMENT 'Campo 13: Modalidade de realização'
AFTER tipo;

-- ============================================
-- ETAPA 3: Adicionar 'link_reuniao' na agenda
-- ============================================

-- Campo 14: Link para reunião remota (na tabela de agenda)
ALTER TABLE agenda_treinamentos
ADD COLUMN link_reuniao VARCHAR(500) NULL
COMMENT 'Campo 14: Link da reunião remota (Zoom, Teams, Google Meet, etc.)'
AFTER local;

-- ============================================
-- ETAPA 4: Verificar campos já existentes no schema
-- (Esses campos já existem no schema.sql mas não estão no formulário)
-- ============================================

-- Os campos abaixo JÁ EXISTEM no banco (não precisam ser criados):
-- ✅ componente_pe ENUM('Clientes', 'Financeiro', 'Processos Internos', 'Aprendizagem e Crescimento')
-- ✅ programa ENUM('PGR', 'Líderes em Transformação', 'Crescer', 'Gerais')
-- ✅ objetivo TEXT
-- ✅ resultados_esperados TEXT
-- ✅ justificativa TEXT
-- ✅ carga_horaria_total DECIMAL(5,2)
-- ✅ valor_investimento DECIMAL(10,2)
-- ✅ status ENUM('Programado', 'Executado', 'Pendente', 'Cancelado')

-- CASO esses campos NÃO EXISTAM (verificar primeiro), descomentar:
/*
ALTER TABLE treinamentos
ADD COLUMN IF NOT EXISTS componente_pe ENUM('Clientes', 'Financeiro', 'Processos Internos', 'Aprendizagem e Crescimento') NULL
COMMENT 'Campo 3: Componente do Planejamento Estratégico'
AFTER modalidade;

ALTER TABLE treinamentos
ADD COLUMN IF NOT EXISTS programa ENUM('PGR', 'Líderes em Transformação', 'Crescer', 'Gerais') NULL
COMMENT 'Campo 4: Programa ao qual pertence'
AFTER componente_pe;

ALTER TABLE treinamentos
ADD COLUMN IF NOT EXISTS objetivo TEXT NULL
COMMENT 'Campo 5: O Que (Objetivo do treinamento)'
AFTER programa;

ALTER TABLE treinamentos
ADD COLUMN IF NOT EXISTS resultados_esperados TEXT NULL
COMMENT 'Campo 6: Resultados esperados'
AFTER objetivo;

ALTER TABLE treinamentos
ADD COLUMN IF NOT EXISTS justificativa TEXT NULL
COMMENT 'Campo 7: Por Que (Justificativa)'
AFTER resultados_esperados;
*/

-- ============================================
-- ETAPA 5: Atualizar registros existentes
-- ============================================

-- Converter tipos antigos para novos (se houver dados)
-- Interno → Técnicos (assumindo que treinamentos internos são técnicos)
-- Externo → Normativos (assumindo que treinamentos externos são normativos)
-- ATENÇÃO: Ajuste conforme a realidade dos seus dados!

UPDATE treinamentos
SET tipo = 'Técnicos'
WHERE tipo = 'Interno';

UPDATE treinamentos
SET tipo = 'Normativos'
WHERE tipo = 'Externo';

-- ============================================
-- ETAPA 6: Atualizar índices e comentários
-- ============================================

ALTER TABLE treinamentos
COMMENT = 'Matriz de Capacitações com 14 campos completos';

-- Adicionar índice para modalidade
CREATE INDEX idx_modalidade ON treinamentos(modalidade);

-- ============================================
-- VERIFICAÇÃO FINAL
-- ============================================

-- Executar para verificar estrutura final:
-- SHOW COLUMNS FROM treinamentos;
-- SHOW COLUMNS FROM agenda_treinamentos;

-- ============================================
-- CAMPOS DA MATRIZ - CHECKLIST FINAL
-- ============================================
/*
 ✅ 1. nome VARCHAR(250) - Nome do Treinamento
 ✅ 2. tipo ENUM - Normativos, Comportamentais, Técnicos
 ✅ 3. componente_pe ENUM - Clientes, Financeiro, Processos Internos, Aprendizagem
 ✅ 4. programa ENUM - PGR, Líderes em Transformação, Crescer, Gerais
 ✅ 5. objetivo TEXT - O Que (Objetivo)
 ✅ 6. resultados_esperados TEXT - Resultados
 ✅ 7. justificativa TEXT - Por Que (Justificativa)
 ✅ 8. data_inicio/data_fim + agenda - Quando
 ✅ 9. treinamento_participantes - Quem (Participantes)
 ✅ 10. notificacoes - Frequência de Participantes (check-in)
 ✅ 11. valor_investimento DECIMAL - Quanto (Valor)
 ✅ 12. status ENUM - Status
 ✅ 13. modalidade ENUM - Presencial, Híbrido, Remoto
 ✅ 14. link_reuniao VARCHAR - Local da Reunião (link remoto)
*/
