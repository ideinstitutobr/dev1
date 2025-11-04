-- =====================================================
-- MIGRATION: Módulo de Agenda de Treinamentos
-- Data: 2025-01-04
-- Descrição: Melhora a tabela agenda_treinamentos
-- =====================================================

-- Verificar se a tabela já existe e ajustar se necessário
-- DROP TABLE IF EXISTS agenda_treinamentos;

CREATE TABLE IF NOT EXISTS agenda_treinamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    treinamento_id INT NOT NULL,
    turma VARCHAR(100) COMMENT 'Identificação da turma (Turma A, Turma B, etc)',
    data_inicio DATE NOT NULL,
    data_fim DATE,
    hora_inicio TIME,
    hora_fim TIME,
    dias_semana VARCHAR(50) COMMENT 'Dias da semana (seg,ter,qua,qui,sex,sab,dom)',
    local VARCHAR(255),
    vagas_total INT DEFAULT 0,
    vagas_ocupadas INT DEFAULT 0,
    instrutor VARCHAR(200),
    observacoes TEXT,
    status ENUM('Programado', 'Em Andamento', 'Concluído', 'Cancelado') DEFAULT 'Programado',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (treinamento_id) REFERENCES treinamentos(id) ON DELETE CASCADE,
    INDEX idx_treinamento (treinamento_id),
    INDEX idx_data_inicio (data_inicio),
    INDEX idx_status (status),
    INDEX idx_turma (turma)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adicionar coluna agenda_id na tabela treinamento_participantes se não existir
ALTER TABLE treinamento_participantes
ADD COLUMN agenda_id INT AFTER treinamento_id,
ADD FOREIGN KEY (agenda_id) REFERENCES agenda_treinamentos(id) ON DELETE SET NULL;

-- Criar índice
CREATE INDEX idx_agenda ON treinamento_participantes(agenda_id);
