-- =====================================================
-- Script: Criar Dados Iniciais - Módulos e Perguntas
-- Data: 2025-11-08
-- Descrição: Cria estrutura completa de módulos e perguntas
--            separados por tipo de formulário
-- =====================================================

-- =====================================================
-- FORMULÁRIOS DIÁRIOS
-- =====================================================

-- Módulo 1: Limpeza e Organização (DIÁRIO)
INSERT INTO modulos_avaliacao (nome, tipo, descricao, total_perguntas, peso_por_pergunta, ordem, ativo)
VALUES ('Limpeza e Organização', 'diario', 'Avaliação rápida de limpeza e organização da loja', 5, 20.0, 1, 1);

SET @modulo_limpeza_diario = LAST_INSERT_ID();

INSERT INTO perguntas (modulo_id, tipo, texto, descricao, ordem, obrigatoria, permite_foto, ativo) VALUES
(@modulo_limpeza_diario, 'diario', 'A loja está limpa e organizada?', 'Verificar piso, prateleiras e áreas comuns', 1, 1, 1, 1),
(@modulo_limpeza_diario, 'diario', 'Os produtos estão bem organizados nas prateleiras?', 'Verificar alinhamento e arrumação', 2, 1, 1, 1),
(@modulo_limpeza_diario, 'diario', 'As áreas de circulação estão desobstruídas?', 'Verificar corredores e passagens', 3, 1, 1, 1),
(@modulo_limpeza_diario, 'diario', 'O banheiro está limpo e abastecido?', 'Verificar limpeza e materiais de higiene', 4, 1, 1, 1),
(@modulo_limpeza_diario, 'diario', 'A fachada e entrada estão apresentáveis?', 'Verificar calçada, vitrine e porta de entrada', 5, 1, 1, 1);

-- Módulo 2: Atendimento (DIÁRIO)
INSERT INTO modulos_avaliacao (nome, tipo, descricao, total_perguntas, peso_por_pergunta, ordem, ativo)
VALUES ('Atendimento', 'diario', 'Avaliação da qualidade do atendimento ao cliente', 5, 20.0, 2, 1);

SET @modulo_atendimento_diario = LAST_INSERT_ID();

INSERT INTO perguntas (modulo_id, tipo, texto, descricao, ordem, obrigatoria, permite_foto, ativo) VALUES
(@modulo_atendimento_diario, 'diario', 'A equipe está uniformizada adequadamente?', 'Verificar uniforme limpo e completo', 1, 1, 1, 1),
(@modulo_atendimento_diario, 'diario', 'Os colaboradores estão receptivos e cordiais?', 'Observar postura e atendimento', 2, 1, 1, 1),
(@modulo_atendimento_diario, 'diario', 'O atendimento está sendo ágil?', 'Verificar tempo de espera e fluxo', 3, 1, 1, 1),
(@modulo_atendimento_diario, 'diario', 'A equipe demonstra conhecimento dos produtos?', 'Avaliar capacidade de orientação', 4, 1, 1, 1),
(@modulo_atendimento_diario, 'diario', 'O ambiente de trabalho está harmonioso?', 'Observar clima organizacional', 5, 1, 1, 1);

-- =====================================================
-- FORMULÁRIOS QUINZENAIS/MENSAIS
-- =====================================================

-- Módulo 1: Infraestrutura (QUINZENAL/MENSAL)
INSERT INTO modulos_avaliacao (nome, tipo, descricao, total_perguntas, peso_por_pergunta, ordem, ativo)
VALUES ('Infraestrutura', 'quinzenal_mensal', 'Avaliação completa da infraestrutura física da loja', 8, 12.5, 1, 1);

SET @modulo_infra_quinzenal = LAST_INSERT_ID();

INSERT INTO perguntas (modulo_id, tipo, texto, descricao, ordem, obrigatoria, permite_foto, ativo) VALUES
(@modulo_infra_quinzenal, 'quinzenal_mensal', 'A iluminação está adequada em todos os ambientes?', 'Verificar funcionamento e intensidade', 1, 1, 1, 1),
(@modulo_infra_quinzenal, 'quinzenal_mensal', 'O sistema de climatização está funcionando?', 'Verificar ar condicionado/ventilação', 2, 1, 1, 1),
(@modulo_infra_quinzenal, 'quinzenal_mensal', 'As instalações elétricas estão em bom estado?', 'Verificar tomadas, interruptores e fiação', 3, 1, 1, 1),
(@modulo_infra_quinzenal, 'quinzenal_mensal', 'As portas e janelas estão funcionando corretamente?', 'Verificar fechaduras e mecanismos', 4, 1, 1, 1),
(@modulo_infra_quinzenal, 'quinzenal_mensal', 'A pintura e acabamentos estão conservados?', 'Verificar paredes, teto e pisos', 5, 1, 1, 1),
(@modulo_infra_quinzenal, 'quinzenal_mensal', 'Os equipamentos de segurança estão funcionais?', 'Verificar extintores, alarmes e câmeras', 6, 1, 1, 1),
(@modulo_infra_quinzenal, 'quinzenal_mensal', 'A sinalização interna está adequada?', 'Verificar placas e indicações', 7, 1, 1, 1),
(@modulo_infra_quinzenal, 'quinzenal_mensal', 'O depósito/estoque está organizado?', 'Verificar disposição e acesso aos produtos', 8, 1, 1, 1);

-- Módulo 2: Gestão de Pessoas (QUINZENAL/MENSAL)
INSERT INTO modulos_avaliacao (nome, tipo, descricao, total_perguntas, peso_por_pergunta, ordem, ativo)
VALUES ('Gestão de Pessoas', 'quinzenal_mensal', 'Avaliação de recursos humanos e desenvolvimento da equipe', 7, 14.28, 2, 1);

SET @modulo_pessoas_quinzenal = LAST_INSERT_ID();

INSERT INTO perguntas (modulo_id, tipo, texto, descricao, ordem, obrigatoria, permite_foto, ativo) VALUES
(@modulo_pessoas_quinzenal, 'quinzenal_mensal', 'A escala de trabalho está sendo cumprida?', 'Verificar horários e folgas', 1, 1, 1, 1),
(@modulo_pessoas_quinzenal, 'quinzenal_mensal', 'Os treinamentos estão sendo realizados?', 'Verificar frequência e conteúdo', 2, 1, 1, 1),
(@modulo_pessoas_quinzenal, 'quinzenal_mensal', 'As metas individuais estão sendo acompanhadas?', 'Verificar sistema de avaliação', 3, 1, 1, 1),
(@modulo_pessoas_quinzenal, 'quinzenal_mensal', 'Há comunicação efetiva entre liderança e equipe?', 'Avaliar canais e frequência', 4, 1, 1, 1),
(@modulo_pessoas_quinzenal, 'quinzenal_mensal', 'Os colaboradores conhecem suas responsabilidades?', 'Verificar clareza de funções', 5, 1, 1, 1),
(@modulo_pessoas_quinzenal, 'quinzenal_mensal', 'Há plano de desenvolvimento para a equipe?', 'Verificar iniciativas de crescimento', 6, 1, 1, 1),
(@modulo_pessoas_quinzenal, 'quinzenal_mensal', 'O clima organizacional está satisfatório?', 'Avaliar ambiente e motivação', 7, 1, 1, 1);

-- Módulo 3: Gestão Comercial (QUINZENAL/MENSAL)
INSERT INTO modulos_avaliacao (nome, tipo, descricao, total_perguntas, peso_por_pergunta, ordem, ativo)
VALUES ('Gestão Comercial', 'quinzenal_mensal', 'Avaliação de processos comerciais e resultados', 10, 10.0, 3, 1);

SET @modulo_comercial_quinzenal = LAST_INSERT_ID();

INSERT INTO perguntas (modulo_id, tipo, texto, descricao, ordem, obrigatoria, permite_foto, ativo) VALUES
(@modulo_comercial_quinzenal, 'quinzenal_mensal', 'As metas de vendas estão sendo acompanhadas?', 'Verificar controles e indicadores', 1, 1, 1, 1),
(@modulo_comercial_quinzenal, 'quinzenal_mensal', 'O estoque está adequado à demanda?', 'Verificar disponibilidade de produtos', 2, 1, 1, 1),
(@modulo_comercial_quinzenal, 'quinzenal_mensal', 'Os precificação está correta e atualizada?', 'Verificar etiquetas e sistema', 3, 1, 1, 1),
(@modulo_comercial_quinzenal, 'quinzenal_mensal', 'As promoções estão sendo divulgadas adequadamente?', 'Verificar materiais e comunicação', 4, 1, 1, 1),
(@modulo_comercial_quinzenal, 'quinzenal_mensal', 'O caixa está sendo operado corretamente?', 'Verificar processos e registros', 5, 1, 1, 1),
(@modulo_comercial_quinzenal, 'quinzenal_mensal', 'Os produtos de maior margem estão em destaque?', 'Verificar exposição estratégica', 6, 1, 1, 1),
(@modulo_comercial_quinzenal, 'quinzenal_mensal', 'Há controle de perdas e quebras?', 'Verificar registros e ações preventivas', 7, 1, 1, 1),
(@modulo_comercial_quinzenal, 'quinzenal_mensal', 'As devoluções estão sendo tratadas adequadamente?', 'Verificar processo e registros', 8, 1, 1, 1),
(@modulo_comercial_quinzenal, 'quinzenal_mensal', 'O atendimento pós-venda está funcionando?', 'Verificar follow-up e satisfação', 9, 1, 1, 1),
(@modulo_comercial_quinzenal, 'quinzenal_mensal', 'Os relatórios gerenciais estão atualizados?', 'Verificar dashboards e controles', 10, 1, 1, 1);

-- =====================================================
-- CONFIRMAÇÃO DOS DADOS CRIADOS
-- =====================================================
SELECT
    'Dados iniciais criados com sucesso!' AS mensagem,
    (SELECT COUNT(*) FROM modulos_avaliacao WHERE tipo = 'diario') AS modulos_diarios,
    (SELECT COUNT(*) FROM perguntas WHERE tipo = 'diario') AS perguntas_diarias,
    (SELECT COUNT(*) FROM modulos_avaliacao WHERE tipo = 'quinzenal_mensal') AS modulos_quinzenais,
    (SELECT COUNT(*) FROM perguntas WHERE tipo = 'quinzenal_mensal') AS perguntas_quinzenais;
