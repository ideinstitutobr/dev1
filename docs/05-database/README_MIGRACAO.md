# 🔄 Migração da Tabela Treinamentos

## ⚠️ IMPORTANTE: Leia antes de executar

Esta migração adiciona colunas necessárias à tabela `treinamentos` para suportar o novo módulo de Treinamentos.

## 📋 O que será alterado

A migração irá:

1. **Adicionar novas colunas:**
   - `fornecedor` - Para treinamentos externos
   - `carga_horaria` - Carga horária principal
   - `carga_horaria_complementar` - Horas complementares
   - `data_inicio` - Data de início
   - `data_fim` - Data de término
   - `custo_total` - Custo total do treinamento
   - `origem` - Origem do cadastro (local/wordpress)

2. **Atualizar valores ENUM:**
   - `tipo` - Adiciona 'Interno' e 'Externo'
   - `status` - Adiciona 'Em Andamento'

3. **Tornar opcionais:**
   - `componente_pe` - Pode ser NULL
   - `programa` - Pode ser NULL

## 🚀 Como Executar

### Opção 1: Via linha de comando PHP (Recomendado)

```bash
cd database
php executar_migracao.php
```

### Opção 2: Via navegador

1. Acesse pelo navegador:
   ```
   https://comercial.ideinstituto.com.br/database/executar_migracao.php
   ```

2. Aguarde a execução e veja as mensagens de sucesso

### Opção 3: Executar SQL manualmente

Se preferir executar manualmente no phpMyAdmin ou outro cliente MySQL:

1. Abra o arquivo `migration_treinamentos_update.sql`
2. Copie todo o conteúdo
3. Execute no seu cliente MySQL/phpMyAdmin
4. Verifique se não houve erros

## ✅ Verificação

Após executar a migração, você pode verificar se funcionou com este SQL:

```sql
DESCRIBE treinamentos;
```

Você deve ver as novas colunas:
- fornecedor
- carga_horaria
- carga_horaria_complementar
- data_inicio
- data_fim
- custo_total
- origem

## 🔒 Segurança

- ✅ A migração usa transações (rollback em caso de erro)
- ✅ Verifica se a coluna já existe antes de criar
- ✅ Não deleta dados existentes
- ✅ Mantém compatibilidade com dados antigos

## 📝 Notas

- Execute esta migração **apenas UMA VEZ**
- Se a coluna já existir, será ignorada (sem erro)
- Os dados existentes não serão afetados
- Campos antigos continuam funcionando normalmente

## ⚠️ Problemas Comuns

### Erro: "Column 'fornecedor' already exists"
**Solução:** A migração já foi executada. Não precisa executar novamente.

### Erro: "Access denied"
**Solução:** Verifique se o usuário do banco tem permissão ALTER TABLE.

### Erro: "Table doesn't exist"
**Solução:** Execute primeiro o schema.sql para criar as tabelas.

## 🆘 Suporte

Se encontrar problemas, verifique:
1. Conexão com banco de dados está funcionando
2. Usuário tem permissão ALTER TABLE
3. Tabela 'treinamentos' existe no banco
4. Arquivo config.php está com credenciais corretas

## 📅 Histórico

- **2025-01-XX** - Migração inicial para suportar novo módulo de treinamentos
