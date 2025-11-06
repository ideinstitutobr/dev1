-- =====================================================
-- MIGRATION: Portal do Colaborador + Sistema de Certificados
-- Data: 2025-11-04
-- Descrição: Cria estrutura completa para:
--   1. Login de colaboradores no portal
--   2. Templates de certificados
--   3. Controle de certificados emitidos
-- =====================================================

-- =====================================================
-- TABELA 1: Senhas de Colaboradores para Portal
-- =====================================================

CREATE TABLE IF NOT EXISTS colaboradores_senhas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    colaborador_id INT NOT NULL UNIQUE COMMENT 'FK para colaboradores',
    senha_hash VARCHAR(255) NOT NULL COMMENT 'Hash da senha (Argon2id)',
    senha_temporaria BOOLEAN DEFAULT 1 COMMENT 'Se 1, deve trocar no primeiro login',
    ultimo_acesso TIMESTAMP NULL COMMENT 'Data/hora do último acesso',

    -- Reset de Senha
    token_reset VARCHAR(100) UNIQUE NULL COMMENT 'Token para reset de senha',
    expiracao_token TIMESTAMP NULL COMMENT 'Expiração do token de reset',

    -- Segurança
    tentativas_login INT DEFAULT 0 COMMENT 'Contador de tentativas falhas',
    bloqueado_ate TIMESTAMP NULL COMMENT 'Bloqueio temporário por tentativas',

    -- Auditoria
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id) ON DELETE CASCADE,
    INDEX idx_token (token_reset),
    INDEX idx_bloqueio (bloqueado_ate),
    INDEX idx_colaborador (colaborador_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Senhas e autenticação de colaboradores no portal';

-- Adicionar campo de controle de acesso ao portal na tabela colaboradores
ALTER TABLE colaboradores
ADD COLUMN IF NOT EXISTS portal_ativo BOOLEAN DEFAULT 1
COMMENT 'Se 1, permite acesso ao portal';

-- =====================================================
-- TABELA 2: Templates de Certificados
-- =====================================================

CREATE TABLE IF NOT EXISTS certificado_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(150) NOT NULL COMMENT 'Nome identificador do template',
    descricao TEXT COMMENT 'Descrição do uso do template',

    -- Configurações de Página
    orientacao ENUM('portrait', 'landscape') DEFAULT 'landscape' COMMENT 'Orientação do papel',
    tamanho_papel ENUM('A4', 'Letter') DEFAULT 'A4' COMMENT 'Tamanho do papel',

    -- Logo da Empresa
    logo_path VARCHAR(255) COMMENT 'Caminho do arquivo de logo',
    logo_largura INT DEFAULT 50 COMMENT 'Largura da logo em mm',
    logo_posicao_x INT DEFAULT 20 COMMENT 'Posição X da logo em mm',
    logo_posicao_y INT DEFAULT 20 COMMENT 'Posição Y da logo em mm',

    -- Configurações Visuais
    cor_fundo VARCHAR(7) DEFAULT '#FFFFFF' COMMENT 'Cor de fundo (hex)',
    cor_borda VARCHAR(7) DEFAULT '#667eea' COMMENT 'Cor da borda (hex)',
    largura_borda INT DEFAULT 2 COMMENT 'Largura da borda em mm',
    cor_texto_principal VARCHAR(7) DEFAULT '#000000' COMMENT 'Cor do texto principal (hex)',
    cor_texto_secundario VARCHAR(7) DEFAULT '#666666' COMMENT 'Cor do texto secundário (hex)',

    -- Template HTML/CSS
    template_html TEXT COMMENT 'Template HTML do certificado',
    template_css TEXT COMMENT 'CSS customizado adicional',

    -- Assinatura
    assinatura_path VARCHAR(255) COMMENT 'Caminho da imagem de assinatura',
    assinatura_cargo VARCHAR(100) COMMENT 'Cargo do assinante',
    assinatura_nome VARCHAR(150) COMMENT 'Nome do assinante',

    -- Campos Dinâmicos Disponíveis
    campos_disponiveis JSON COMMENT 'Array de campos que podem ser usados: {NOME}, {TREINAMENTO}, etc.',

    -- Controle
    ativo BOOLEAN DEFAULT 1 COMMENT 'Se o template está ativo',
    padrao BOOLEAN DEFAULT 0 COMMENT 'Se é o template padrão do sistema',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_ativo (ativo),
    INDEX idx_padrao (padrao),
    INDEX idx_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Templates personalizáveis de certificados';

-- =====================================================
-- TABELA 3: Certificados Emitidos
-- =====================================================

CREATE TABLE IF NOT EXISTS certificados_emitidos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    participante_id INT NOT NULL COMMENT 'FK para treinamento_participantes',
    template_id INT NOT NULL COMMENT 'FK para certificado_templates usado',

    -- Identificação do Certificado
    numero_certificado VARCHAR(50) UNIQUE NOT NULL COMMENT 'Número único do certificado (ex: CERT-2025-00001)',
    hash_validacao VARCHAR(64) UNIQUE NOT NULL COMMENT 'Hash SHA256 para validação pública',

    -- Arquivo PDF Gerado
    arquivo_path VARCHAR(255) NOT NULL COMMENT 'Caminho do PDF no servidor',
    arquivo_tamanho INT COMMENT 'Tamanho do arquivo em bytes',

    -- Snapshot dos Dados
    dados_certificado JSON COMMENT 'Snapshot dos dados usados na geração (colaborador, treinamento, etc.)',

    -- Envio por Email
    enviado_email BOOLEAN DEFAULT 0 COMMENT 'Se foi enviado por email',
    data_envio_email TIMESTAMP NULL COMMENT 'Data do envio por email',

    -- Controle de Acesso
    downloads INT DEFAULT 0 COMMENT 'Contador de downloads',
    ultimo_download TIMESTAMP NULL COMMENT 'Data do último download',

    -- Revogação
    revogado BOOLEAN DEFAULT 0 COMMENT 'Se o certificado foi revogado',
    data_revogacao TIMESTAMP NULL COMMENT 'Data da revogação',
    motivo_revogacao TEXT COMMENT 'Motivo da revogação',
    revogado_por INT NULL COMMENT 'ID do usuário RH que revogou',

    -- Auditoria
    gerado_por INT COMMENT 'ID do usuário RH que gerou',
    data_emissao TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Data de emissão do certificado',

    FOREIGN KEY (participante_id) REFERENCES treinamento_participantes(id) ON DELETE CASCADE,
    FOREIGN KEY (template_id) REFERENCES certificado_templates(id),
    FOREIGN KEY (gerado_por) REFERENCES usuarios_sistema(id),
    FOREIGN KEY (revogado_por) REFERENCES usuarios_sistema(id),

    INDEX idx_numero (numero_certificado),
    INDEX idx_hash (hash_validacao),
    INDEX idx_participante (participante_id),
    INDEX idx_revogado (revogado),
    INDEX idx_data_emissao (data_emissao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Controle de certificados emitidos e sua validação';

-- =====================================================
-- ATUALIZAÇÃO: Garantir campos de certificado em treinamento_participantes
-- =====================================================

-- Garantir que os campos existem (podem já existir)
ALTER TABLE treinamento_participantes
MODIFY COLUMN certificado_emitido BOOLEAN DEFAULT 0
COMMENT 'Se o certificado foi emitido para este participante';

ALTER TABLE treinamento_participantes
MODIFY COLUMN data_emissao_certificado TIMESTAMP NULL
COMMENT 'Data de emissão do certificado';

-- =====================================================
-- NOTA: Template padrão será inserido via PHP no instalador
-- devido a conflitos de aspas no SQL
-- =====================================================

-- =====================================================
-- ÍNDICES ADICIONAIS PARA PERFORMANCE
-- =====================================================

-- Índice para buscas de certificados por colaborador
CREATE INDEX idx_certificados_colaborador
ON certificados_emitidos(participante_id, data_emissao DESC);

-- Índice para buscas de senhas ativas
CREATE INDEX idx_senhas_ativas
ON colaboradores_senhas(colaborador_id, bloqueado_ate);

-- =====================================================
-- FIM DA MIGRATION
-- =====================================================
