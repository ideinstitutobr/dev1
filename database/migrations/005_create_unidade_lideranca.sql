-- =========================================================
-- MIGRATION: Cria tabela unidade_lideranca
-- Data: 2024-11-06
-- Descrição: Cargos de liderança por unidade
--            (Diretor de Varejo, Gerente, Supervisor)
-- =========================================================

CREATE TABLE IF NOT EXISTS unidade_lideranca (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unidade_id INT NOT NULL,
    colaborador_id INT NOT NULL,
    cargo_lideranca ENUM('diretor_varejo', 'gerente_loja', 'supervisor_loja') NOT NULL,
    unidade_setor_id INT DEFAULT NULL COMMENT 'Setor supervisionado (NULL = toda unidade)',
    data_inicio DATE NOT NULL,
    data_fim DATE DEFAULT NULL,
    observacoes TEXT,
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (unidade_id) REFERENCES unidades(id) ON DELETE CASCADE,
    FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id) ON DELETE CASCADE,
    FOREIGN KEY (unidade_setor_id) REFERENCES unidade_setores(id) ON DELETE SET NULL,
    UNIQUE KEY unique_diretor_ativo (unidade_id, cargo_lideranca, ativo) COMMENT 'Apenas 1 diretor/gerente ativo por unidade',
    INDEX idx_unidade (unidade_id),
    INDEX idx_colaborador (colaborador_id),
    INDEX idx_cargo (cargo_lideranca),
    INDEX idx_setor (unidade_setor_id),
    INDEX idx_ativo (ativo),
    INDEX idx_data_inicio (data_inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Cargos de liderança por unidade';
