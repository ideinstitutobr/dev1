-- =========================================================
-- MIGRATION: Popula setores iniciais no field_categories
-- Data: 2024-11-06
-- Descrição: Adiciona setores padrão que serão usados
--            nas unidades (se não existirem)
-- =========================================================

-- Inserir setores padrão (ON DUPLICATE KEY UPDATE para não duplicar)
INSERT INTO field_categories (tipo, valor, ativo) VALUES
('setor', 'Vendas', 1),
('setor', 'Estoque', 1),
('setor', 'Administrativo', 1),
('setor', 'Financeiro', 1),
('setor', 'Recursos Humanos', 1),
('setor', 'TI', 1),
('setor', 'Marketing', 1),
('setor', 'Atendimento ao Cliente', 1),
('setor', 'Logística', 1),
('setor', 'Compras', 1),
('setor', 'Comercial', 1),
('setor', 'Operações', 1)
ON DUPLICATE KEY UPDATE valor = valor;
