-- =====================================================
-- SEED DE MÓDULOS E PERGUNTAS - CHECKLIST DE LOJAS
-- Descrição: Dados iniciais dos 8 setores de avaliação
-- Versão: 1.0
-- Data: 2025-11-07
-- =====================================================

-- =====================================================
-- MÓDULO 1: Organização de Lojas (8 perguntas)
-- =====================================================
INSERT INTO modulos_avaliacao (nome, descricao, total_perguntas, peso_por_pergunta, ordem, ativo) VALUES
('Organização de Lojas', 'Avaliação da limpeza, organização e disposição geral da loja', 8, 0.625, 1, 1);

SET @modulo1_id = LAST_INSERT_ID();

INSERT INTO perguntas (modulo_id, texto, descricao, ordem, obrigatoria, permite_foto, ativo) VALUES
(@modulo1_id, 'Os corredores estão limpos e organizados?', 'Verificar se os corredores estão livres de obstáculos e limpos', 1, 1, 1, 1),
(@modulo1_id, 'A sinalização está visível e adequada?', 'Placas indicativas e de preços bem posicionadas', 2, 1, 1, 1),
(@modulo1_id, 'As prateleiras estão bem abastecidas?', 'Verificar se não há produtos faltando nas gôndolas', 3, 1, 1, 1),
(@modulo1_id, 'A iluminação está adequada em toda a loja?', 'Todas as luzes funcionando e ambiente bem iluminado', 4, 1, 0, 1),
(@modulo1_id, 'O piso está limpo e sem riscos?', 'Verificar condições do piso e segurança', 5, 1, 1, 1),
(@modulo1_id, 'As vitrines e displays estão organizados?', 'Produtos destacados de forma atrativa', 6, 1, 1, 1),
(@modulo1_id, 'Há produtos vencidos ou danificados expostos?', 'Verificar validades e condições dos produtos', 7, 1, 1, 1),
(@modulo1_id, 'A entrada e fachada estão em bom estado?', 'Limpeza e conservação da parte externa', 8, 1, 1, 1);

-- =====================================================
-- MÓDULO 2: Caixas (6 perguntas)
-- =====================================================
INSERT INTO modulos_avaliacao (nome, descricao, total_perguntas, peso_por_pergunta, ordem, ativo) VALUES
('Caixas', 'Avaliação do atendimento nos caixas e funcionamento dos equipamentos', 6, 0.833, 2, 1);

SET @modulo2_id = LAST_INSERT_ID();

INSERT INTO perguntas (modulo_id, texto, descricao, ordem, obrigatoria, permite_foto, ativo) VALUES
(@modulo2_id, 'Os caixas estão limpos e organizados?', 'Verificar limpeza da área de atendimento', 1, 1, 1, 1),
(@modulo2_id, 'Os operadores estão uniformizados?', 'Uniforme completo e identificação visível', 2, 1, 0, 1),
(@modulo2_id, 'O atendimento está sendo ágil?', 'Tempo de espera adequado nas filas', 3, 1, 0, 1),
(@modulo2_id, 'Os equipamentos estão funcionando?', 'Leitores, impressoras e balanças operacionais', 4, 1, 1, 1),
(@modulo2_id, 'Há sacolas e materiais de embalagem suficientes?', 'Disponibilidade de sacolas plásticas e papel', 5, 1, 0, 1),
(@modulo2_id, 'A área de caixas está sinalizada?', 'Placas de "Aberto" e "Fechado" visíveis', 6, 1, 1, 1);

-- =====================================================
-- MÓDULO 3: Setor Ovos (8 perguntas)
-- =====================================================
INSERT INTO modulos_avaliacao (nome, descricao, total_perguntas, peso_por_pergunta, ordem, ativo) VALUES
('Setor Ovos', 'Avaliação específica do setor de ovos e produtos sensíveis', 8, 0.625, 3, 1);

SET @modulo3_id = LAST_INSERT_ID();

INSERT INTO perguntas (modulo_id, texto, descricao, ordem, obrigatoria, permite_foto, ativo) VALUES
(@modulo3_id, 'Os ovos estão armazenados na temperatura adequada?', 'Verificar refrigeração se necessário', 1, 1, 1, 1),
(@modulo3_id, 'Todas as embalagens estão íntegras?', 'Sem ovos quebrados ou rachados expostos', 2, 1, 1, 1),
(@modulo3_id, 'As datas de validade estão visíveis?', 'Etiquetas legíveis e atualizadas', 3, 1, 1, 1),
(@modulo3_id, 'A área está limpa e higienizada?', 'Sem sujeira ou resíduos', 4, 1, 1, 1),
(@modulo3_id, 'Os produtos estão organizados por tipo?', 'Separação clara entre tipos e tamanhos', 5, 1, 0, 1),
(@modulo3_id, 'Há produtos vencidos ou próximos do vencimento?', 'Sistema FIFO implementado', 6, 1, 1, 1),
(@modulo3_id, 'A precificação está correta e visível?', 'Etiquetas de preço corretas', 7, 1, 1, 1),
(@modulo3_id, 'O estoque está adequado à demanda?', 'Quantidade suficiente sem excesso', 8, 1, 0, 1);

-- =====================================================
-- MÓDULO 4: Gôndolas e Ilhas (8 perguntas)
-- =====================================================
INSERT INTO modulos_avaliacao (nome, descricao, total_perguntas, peso_por_pergunta, ordem, ativo) VALUES
('Gôndolas e Ilhas', 'Avaliação da exposição de produtos nas gôndolas e ilhas promocionais', 8, 0.625, 4, 1);

SET @modulo4_id = LAST_INSERT_ID();

INSERT INTO perguntas (modulo_id, texto, descricao, ordem, obrigatoria, permite_foto, ativo) VALUES
(@modulo4_id, 'As gôndolas estão limpas e organizadas?', 'Produtos alinhados e prateleiras limpas', 1, 1, 1, 1),
(@modulo4_id, 'Os produtos estão com precificação visível?', 'Etiquetas de preço corretas e legíveis', 2, 1, 1, 1),
(@modulo4_id, 'Há produtos vencidos ou danificados?', 'Verificar validades e condições', 3, 1, 1, 1),
(@modulo4_id, 'A reposição está em dia?', 'Não há faltas ou rupturas nas gôndolas', 4, 1, 1, 1),
(@modulo4_id, 'As ilhas promocionais estão bem sinalizadas?', 'Material promocional visível e atrativo', 5, 1, 1, 1),
(@modulo4_id, 'Os produtos das ilhas correspondem à promoção?', 'Conferir se produtos e preços estão corretos', 6, 1, 1, 1),
(@modulo4_id, 'As pontas de gôndola estão bem exploradas?', 'Produtos destacados de forma estratégica', 7, 1, 1, 1),
(@modulo4_id, 'O layout facilita a circulação dos clientes?', 'Corredores desobstruídos e bem organizados', 8, 1, 0, 1);

-- =====================================================
-- MÓDULO 5: Balcão de Frios (8 perguntas)
-- =====================================================
INSERT INTO modulos_avaliacao (nome, descricao, total_perguntas, peso_por_pergunta, ordem, ativo) VALUES
('Balcão de Frios', 'Avaliação do balcão de frios, queijos e embutidos', 8, 0.625, 5, 1);

SET @modulo5_id = LAST_INSERT_ID();

INSERT INTO perguntas (modulo_id, texto, descricao, ordem, obrigatoria, permite_foto, ativo) VALUES
(@modulo5_id, 'O balcão está limpo e higienizado?', 'Verificar limpeza de balcões e utensílios', 1, 1, 1, 1),
(@modulo5_id, 'A temperatura está adequada?', 'Refrigeração funcionando corretamente', 2, 1, 1, 1),
(@modulo5_id, 'Os produtos estão bem acondicionados?', 'Embalagens adequadas e identificadas', 3, 1, 1, 1),
(@modulo5_id, 'As validades estão dentro do prazo?', 'Sem produtos vencidos ou próximos ao vencimento', 4, 1, 1, 1),
(@modulo5_id, 'Os funcionários usam EPIs adequados?', 'Luvas, toucas e aventais limpos', 5, 1, 1, 1),
(@modulo5_id, 'A precificação está correta e visível?', 'Etiquetas de preço por kg ou unidade corretas', 6, 1, 1, 1),
(@modulo5_id, 'Há variedade de produtos expostos?', 'Mix adequado de produtos disponíveis', 7, 1, 0, 1),
(@modulo5_id, 'O atendimento está sendo adequado?', 'Funcionários atendendo com agilidade e cordialidade', 8, 1, 0, 1);

-- =====================================================
-- MÓDULO 6: Câmara Fria (8 perguntas)
-- =====================================================
INSERT INTO modulos_avaliacao (nome, descricao, total_perguntas, peso_por_pergunta, ordem, ativo) VALUES
('Câmara Fria', 'Avaliação das câmaras frias e refrigeração', 8, 0.625, 6, 1);

SET @modulo6_id = LAST_INSERT_ID();

INSERT INTO perguntas (modulo_id, texto, descricao, ordem, obrigatoria, permite_foto, ativo) VALUES
(@modulo6_id, 'A câmara está limpa e organizada?', 'Sem acúmulo de gelo e produtos organizados', 1, 1, 1, 1),
(@modulo6_id, 'A temperatura está dentro do padrão?', 'Verificar termômetro e funcionamento', 2, 1, 1, 1),
(@modulo6_id, 'Os produtos estão identificados corretamente?', 'Etiquetas com data de entrada e validade', 3, 1, 1, 1),
(@modulo6_id, 'O sistema FIFO está sendo seguido?', 'Primeiro que entra, primeiro que sai', 4, 1, 1, 1),
(@modulo6_id, 'As prateleiras e estrados estão limpos?', 'Sem sujeira ou materiais inadequados', 5, 1, 1, 1),
(@modulo6_id, 'A porta veda corretamente?', 'Sem entrada de ar quente ou umidade', 6, 1, 1, 1),
(@modulo6_id, 'Há produtos vencidos ou danificados?', 'Verificar condições e validades', 7, 1, 1, 1),
(@modulo6_id, 'O controle de temperatura é registrado?', 'Planilha de controle atualizada', 8, 1, 0, 1);

-- =====================================================
-- MÓDULO 7: Estoque (8 perguntas)
-- =====================================================
INSERT INTO modulos_avaliacao (nome, descricao, total_perguntas, peso_por_pergunta, ordem, ativo) VALUES
('Estoque', 'Avaliação do estoque e armazenamento de produtos', 8, 0.625, 7, 1);

SET @modulo7_id = LAST_INSERT_ID();

INSERT INTO perguntas (modulo_id, texto, descricao, ordem, obrigatoria, permite_foto, ativo) VALUES
(@modulo7_id, 'O estoque está limpo e organizado?', 'Produtos separados e identificados', 1, 1, 1, 1),
(@modulo7_id, 'Os produtos estão armazenados corretamente?', 'Sobre estrados e afastados da parede', 2, 1, 1, 1),
(@modulo7_id, 'Há controle de entrada e saída?', 'Sistema de controle implementado', 3, 1, 0, 1),
(@modulo7_id, 'As validades são verificadas regularmente?', 'Sem produtos vencidos no estoque', 4, 1, 1, 1),
(@modulo7_id, 'A área está livre de pragas?', 'Sem sinais de roedores ou insetos', 5, 1, 1, 1),
(@modulo7_id, 'Os produtos de limpeza estão separados?', 'Área específica para produtos de limpeza', 6, 1, 1, 1),
(@modulo7_id, 'As prateleiras estão em bom estado?', 'Sem estruturas danificadas ou inseguras', 7, 1, 1, 1),
(@modulo7_id, 'O acesso é restrito e controlado?', 'Apenas pessoal autorizado tem acesso', 8, 1, 0, 1);

-- =====================================================
-- MÓDULO 8: Áreas Comuns de Colaboradores (6 perguntas)
-- =====================================================
INSERT INTO modulos_avaliacao (nome, descricao, total_perguntas, peso_por_pergunta, ordem, ativo) VALUES
('Áreas Comuns de Colaboradores', 'Avaliação de vestiários, refeitório e áreas de descanso', 6, 0.833, 8, 1);

SET @modulo8_id = LAST_INSERT_ID();

INSERT INTO perguntas (modulo_id, texto, descricao, ordem, obrigatoria, permite_foto, ativo) VALUES
(@modulo8_id, 'Os vestiários estão limpos?', 'Banheiros e vestiários higienizados', 1, 1, 1, 1),
(@modulo8_id, 'O refeitório está organizado e limpo?', 'Mesas, cadeiras e utensílios limpos', 2, 1, 1, 1),
(@modulo8_id, 'Há água potável disponível?', 'Bebedouros ou garrafas disponíveis', 3, 1, 0, 1),
(@modulo8_id, 'Os armários estão em bom estado?', 'Armários funcionando com fechaduras', 4, 1, 1, 1),
(@modulo8_id, 'A área de descanso é adequada?', 'Espaço confortável para pausas', 5, 1, 0, 1),
(@modulo8_id, 'As normas de segurança estão expostas?', 'Quadros informativos visíveis', 6, 1, 1, 1);

-- =====================================================
-- EXEMPLO DE LOJAS (OPCIONAL)
-- =====================================================
INSERT INTO lojas (nome, codigo, cidade, estado, ativo) VALUES
('Loja Centro', 'L001', 'São Paulo', 'SP', 1),
('Loja Norte', 'L002', 'São Paulo', 'SP', 1),
('Loja Sul', 'L003', 'Rio de Janeiro', 'RJ', 1),
('Loja Oeste', 'L004', 'Belo Horizonte', 'MG', 1)
ON DUPLICATE KEY UPDATE nome=VALUES(nome);

-- =====================================================
-- FIM DO SCRIPT DE SEED
-- =====================================================
