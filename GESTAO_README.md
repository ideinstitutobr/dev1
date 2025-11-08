# 📋 Sistema de Gestão de Avaliações - README

## 🎯 Objetivo

Este sistema permite gerenciar módulos e perguntas de avaliação separados por tipo de formulário:
- **Formulários Diários**: Avaliações rápidas do dia a dia
- **Formulários Quinzenais/Mensais**: Avaliações periódicas completas

## 🗂️ Estrutura de Diretórios

```
public/gestao/
├── index.php                      # Painel principal de gestão
├── modulos/
│   ├── diario/
│   │   ├── index.php             # Listagem de módulos diários
│   │   ├── criar.php             # Criar módulo diário
│   │   ├── editar.php            # Editar módulo diário
│   │   └── excluir.php           # Excluir módulo diário
│   └── quinzenal/
│       ├── index.php             # Listagem de módulos quinzenais/mensais
│       ├── criar.php             # Criar módulo quinzenal/mensal
│       ├── editar.php            # Editar módulo quinzenal/mensal
│       └── excluir.php           # Excluir módulo quinzenal/mensal
└── perguntas/
    ├── diario/                   # (A implementar)
    └── quinzenal/                # (A implementar)
```

## 🚀 Passo a Passo - Configuração Inicial

### 1. Limpar Banco de Dados (OPCIONAL - CUIDADO!)

Se quiser começar do zero, execute o script SQL:

```bash
# Conecte ao MySQL
mysql -u seu_usuario -p seu_banco

# Execute o script
source /home/user/dev1/database/migrations/008_limpar_e_recriar_estrutura.sql
```

**⚠️ ATENÇÃO:** Este script deleta TODOS os dados de:
- Respostas de checklist
- Checklists
- Perguntas
- Módulos de avaliação

### 2. Popular com Dados Iniciais

Após limpar (ou em banco vazio), popule com dados iniciais:

```bash
mysql -u seu_usuario -p seu_banco
source /home/user/dev1/database/migrations/009_criar_dados_iniciais.sql
```

Este script cria:

**Formulários Diários:**
- 2 módulos: "Limpeza e Organização" e "Atendimento"
- 10 perguntas no total (5 por módulo)

**Formulários Quinzenais/Mensais:**
- 3 módulos: "Infraestrutura", "Gestão de Pessoas" e "Gestão Comercial"
- 25 perguntas no total (8 + 7 + 10)

## 📊 Como Usar

### Acessar Painel de Gestão

1. Acesse: `http://seu-dominio/gestao/`
2. Faça login (requer autenticação)
3. Escolha o tipo de formulário que deseja gerenciar

### Gerenciar Módulos

**Módulos Diários:**
- Acesse: `Gestão → Formulários Diários → Gerenciar Módulos`
- Ou diretamente: `/gestao/modulos/diario/`

**Módulos Quinzenais/Mensais:**
- Acesse: `Gestão → Formulários Quinzenais/Mensais → Gerenciar Módulos`
- Ou diretamente: `/gestao/modulos/quinzenal/`

**Ações disponíveis:**
- ➕ Criar novo módulo
- ✏️ Editar módulo existente
- 🗑️ Excluir módulo
- ❓ Gerenciar perguntas do módulo

### Campos do Módulo

| Campo | Descrição | Exemplo |
|-------|-----------|---------|
| Nome | Nome identificador do módulo | "Limpeza e Organização" |
| Descrição | Orientação para avaliadores | "Avaliação da limpeza geral..." |
| Total Perguntas | Quantidade de perguntas | 5 |
| Peso por Pergunta | Peso no cálculo (%) | 20.00 |
| Ordem | Ordem de exibição | 1, 2, 3... |
| Ativo | Se aparece nos formulários | ✓ Sim / ✗ Não |

## 🔒 Isolamento por Tipo

**IMPORTANTE:** Os formulários são completamente isolados:

✅ **Correto:**
- Módulos diários aparecem APENAS em formulários diários
- Módulos quinzenais aparecem APENAS em formulários quinzenais/mensais
- Perguntas diárias vinculadas a módulos diários
- Perguntas quinzenais vinculadas a módulos quinzenais

❌ **Não permitido:**
- Misturar perguntas de tipos diferentes no mesmo formulário
- Usar módulos diários em formulários quinzenais
- Criar perguntas sem definir o tipo

## 🗄️ Estrutura do Banco de Dados

### Tabela: modulos_avaliacao

```sql
- id (INT)
- nome (VARCHAR)
- tipo (ENUM: 'diario', 'quinzenal_mensal')  ← OBRIGATÓRIO
- descricao (TEXT)
- total_perguntas (INT)
- peso_por_pergunta (DECIMAL)
- ordem (INT)
- ativo (BOOLEAN)
```

### Tabela: perguntas

```sql
- id (INT)
- modulo_id (INT)
- tipo (ENUM: 'diario', 'quinzenal_mensal')  ← OBRIGATÓRIO
- texto (TEXT)
- descricao (TEXT)
- ordem (INT)
- obrigatoria (BOOLEAN)
- permite_foto (BOOLEAN)
- ativo (BOOLEAN)
```

## 🧪 Testando a Estrutura

### 1. Verificar Módulos Criados

```sql
-- Módulos diários
SELECT * FROM modulos_avaliacao WHERE tipo = 'diario';

-- Módulos quinzenais/mensais
SELECT * FROM modulos_avaliacao WHERE tipo = 'quinzenal_mensal';
```

### 2. Verificar Perguntas

```sql
-- Perguntas diárias
SELECT COUNT(*) as total FROM perguntas WHERE tipo = 'diario';

-- Perguntas quinzenais/mensais
SELECT COUNT(*) as total FROM perguntas WHERE tipo = 'quinzenal_mensal';
```

### 3. Testar Formulário

1. Crie uma avaliação diária: `/checklist/diario/novo.php`
2. Deve carregar apenas os 2 módulos diários (10 perguntas)
3. Preencha e finalize - não deve dar erro 500

4. Crie uma avaliação quinzenal: `/checklist/quinzenal/novo.php`
5. Deve carregar apenas os 3 módulos quinzenais (25 perguntas)
6. Preencha e finalize - não deve dar erro 500

## 🐛 Troubleshooting

### Erro: "Nenhum módulo cadastrado"
**Solução:** Execute o script `009_criar_dados_iniciais.sql`

### Erro 500 ao finalizar avaliação
**Causa:** Mismatch entre perguntas carregadas e esperadas
**Solução:**
1. Verifique se todos os módulos têm tipo definido
2. Verifique se todas as perguntas têm tipo definido
3. Execute: `UPDATE modulos_avaliacao SET tipo='diario' WHERE tipo IS NULL;`
4. Execute: `UPDATE perguntas SET tipo='diario' WHERE tipo IS NULL;`

### Módulos aparecem em formulários errados
**Solução:** Verifique o campo `tipo` do módulo:
```sql
SELECT id, nome, tipo FROM modulos_avaliacao;
```

## 📝 Próximos Passos

1. ✅ Estrutura de módulos criada
2. ✅ Scripts SQL de limpeza e população
3. 🔲 Implementar gestão de perguntas (similar aos módulos)
4. 🔲 Adicionar relatórios e estatísticas
5. 🔲 Implementar importação/exportação de módulos

## 💡 Dicas

- **Sempre defina o tipo**: Ao criar módulos/perguntas via código, sempre passe o campo `tipo`
- **Use a gestão web**: Prefira usar as páginas de gestão em vez de SQL direto
- **Faça backup**: Antes de executar scripts de limpeza, faça backup do banco
- **Teste isoladamente**: Teste formulários diários e quinzenais separadamente

## 🆘 Suporte

Se encontrar problemas:
1. Verifique os logs do PHP/Apache
2. Verifique o console do navegador (F12)
3. Confirme que as migrations foram executadas
4. Confirme que os módulos têm tipo correto no banco

---

**Data de criação:** 2025-11-08
**Versão:** 1.0
**Autor:** Sistema automatizado
