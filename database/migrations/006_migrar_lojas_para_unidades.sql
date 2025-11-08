-- Migração: Alterar checklists para usar unidades ao invés de lojas
-- Data: 2025-11-08
-- Descrição: Remove a dependência de lojas e passa a usar unidades

-- 1. Adicionar coluna unidade_id (temporariamente permitindo NULL)
ALTER TABLE checklists ADD COLUMN unidade_id INT NULL AFTER id;

-- 2. Copiar dados de loja_id para unidade_id (se houver correspondência)
-- Nota: Esta migração assume que as lojas foram migradas para unidades ou que
-- será necessário fazer um mapeamento manual. Para novos registros, use unidade_id diretamente.

-- 3. Remover a constraint de chave estrangeira antiga (se existir)
ALTER TABLE checklists DROP FOREIGN KEY IF EXISTS checklists_ibfk_1;

-- 4. Remover a coluna loja_id
ALTER TABLE checklists DROP COLUMN loja_id;

-- 5. Tornar unidade_id obrigatório
ALTER TABLE checklists MODIFY COLUMN unidade_id INT NOT NULL;

-- 6. Adicionar chave estrangeira para unidades
ALTER TABLE checklists ADD CONSTRAINT fk_checklist_unidade
    FOREIGN KEY (unidade_id) REFERENCES unidades(id)
    ON DELETE RESTRICT ON UPDATE CASCADE;

-- 7. Adicionar índice para otimização
CREATE INDEX idx_unidade_id ON checklists(unidade_id);

-- 8. Remover a tabela lojas (opcional - descomente se desejar remover)
-- DROP TABLE IF EXISTS lojas;
