-- ============================================
-- Migration: Tabela de Frequência
-- Descrição: Registro de presença por sessão
-- Data: 2024
-- ============================================

-- Criar tabela de sessões de treinamento
CREATE TABLE IF NOT EXISTS treinamento_sessoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    treinamento_id INT NOT NULL,
    nome VARCHAR(200) NOT NULL,
    data_sessao DATE NOT NULL,
    hora_inicio TIME,
    hora_fim TIME,
    local VARCHAR(255),
    observacoes TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (treinamento_id) REFERENCES treinamentos(id) ON DELETE CASCADE,
    INDEX idx_treinamento (treinamento_id),
    INDEX idx_data (data_sessao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Criar tabela de frequência
CREATE TABLE IF NOT EXISTS frequencia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sessao_id INT NOT NULL,
    participante_id INT NOT NULL,
    status ENUM('Presente', 'Ausente', 'Justificado', 'Atrasado') DEFAULT 'Ausente',
    hora_checkin TIME,
    justificativa TEXT,
    observacoes TEXT,
    registrado_por INT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (sessao_id) REFERENCES treinamento_sessoes(id) ON DELETE CASCADE,
    FOREIGN KEY (participante_id) REFERENCES treinamento_participantes(id) ON DELETE CASCADE,

    UNIQUE KEY unique_sessao_participante (sessao_id, participante_id),
    INDEX idx_sessao (sessao_id),
    INDEX idx_participante (participante_id),
    INDEX idx_status (status),
    INDEX idx_registrado_por (registrado_por)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adicionar coluna de QR Code token para check-in rápido (opcional)
ALTER TABLE treinamento_sessoes
ADD COLUMN qr_token VARCHAR(100) UNIQUE AFTER observacoes;

-- Comentários das tabelas
ALTER TABLE treinamento_sessoes COMMENT = 'Sessões individuais de cada treinamento';
ALTER TABLE frequencia COMMENT = 'Registro de frequência por sessão e participante';
