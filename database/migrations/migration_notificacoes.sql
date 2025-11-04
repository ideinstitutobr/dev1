-- =====================================================
-- MIGRATION: Sistema de Notificações
-- Data: 2025-01-04
-- Descrição: Cria tabela para controle de notificações
-- =====================================================

-- Criar tabela de notificações
CREATE TABLE IF NOT EXISTS notificacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    participante_id INT NOT NULL COMMENT 'FK para treinamento_participantes',
    tipo ENUM('convite', 'lembrete', 'confirmacao', 'certificado', 'avaliacao') NOT NULL,
    email_destinatario VARCHAR(150) NOT NULL,
    email_enviado BOOLEAN DEFAULT 0,
    data_envio TIMESTAMP NULL,
    token_check_in VARCHAR(100) UNIQUE COMMENT 'Token único para check-in',
    expiracao_token TIMESTAMP NULL,
    assunto VARCHAR(200),
    corpo_email TEXT,
    tentativas_envio INT DEFAULT 0,
    erro_envio TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (participante_id) REFERENCES treinamento_participantes(id) ON DELETE CASCADE,
    INDEX idx_participante (participante_id),
    INDEX idx_tipo (tipo),
    INDEX idx_enviado (email_enviado),
    INDEX idx_token (token_check_in),
    INDEX idx_data_envio (data_envio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Criar tabela de configurações SMTP
CREATE TABLE IF NOT EXISTS configuracoes_email (
    id INT AUTO_INCREMENT PRIMARY KEY,
    smtp_host VARCHAR(255) DEFAULT 'smtp.gmail.com',
    smtp_port INT DEFAULT 587,
    smtp_user VARCHAR(150),
    smtp_password VARCHAR(255),
    smtp_secure VARCHAR(10) DEFAULT 'tls' COMMENT 'tls ou ssl',
    email_remetente VARCHAR(150),
    nome_remetente VARCHAR(150) DEFAULT 'SGC - Sistema de Capacitações',
    habilitado BOOLEAN DEFAULT 0,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir configuração padrão
INSERT INTO configuracoes_email (smtp_host, smtp_port, smtp_secure, nome_remetente, habilitado)
VALUES ('smtp.gmail.com', 587, 'tls', 'SGC - Sistema de Capacitações', 0);

-- Criar tabela de logs de e-mail
CREATE TABLE IF NOT EXISTS email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notificacao_id INT,
    destinatario VARCHAR(150),
    assunto VARCHAR(200),
    status ENUM('sucesso', 'erro', 'pendente') DEFAULT 'pendente',
    mensagem_erro TEXT,
    enviado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (notificacao_id) REFERENCES notificacoes(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_enviado_em (enviado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
