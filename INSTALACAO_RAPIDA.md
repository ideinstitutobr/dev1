# 🚀 INSTALAÇÃO RÁPIDA - FORMULÁRIOS DINÂMICOS

**Sistema:** SGC - Sistema de Gestão de Capacitações
**Módulo:** Formulários Dinâmicos
**Domínio:** https://dev1.ideinstituto.com.br/
**Tempo estimado:** 5 minutos

---

## ✅ PRÉ-REQUISITOS VERIFICADOS

- [x] Arquivos criados e commitados
- [x] Branch enviada para o GitHub
- [x] Menu atualizado com novo módulo
- [x] Instalador web pronto
- [x] Models e Controllers funcionais
- [x] Sistema de checklists preservado

---

## 🎯 COMO INSTALAR (3 PASSOS SIMPLES)

### PASSO 1: Acessar o Instalador

Acesse via navegador (como **administrador**):

```
https://dev1.ideinstituto.com.br/public/formularios-dinamicos/instalar.php
```

ou clique no menu lateral:

```
Menu → Formulários Dinâmicos → Instalar/Atualizar
```

---

### PASSO 2: Confirmar Instalação

Na tela do instalador:

1. ✅ Verifique as informações exibidas:
   - 8 tabelas serão criadas
   - Formulário de exemplo será adicionado
   - Sistema de checklists não será afetado

2. 🔘 Clique no botão: **"Instalar Agora"**

3. ⏳ Aguarde a confirmação (5-10 segundos)

---

### PASSO 3: Verificar Instalação

Após a instalação bem-sucedida, você verá:

```
✅ Instalação concluída com sucesso!
📊 Total de comandos SQL executados: X
🗄️ Total de tabelas criadas/verificadas: 8
📋 Formulários de exemplo: 1
```

**Pronto!** O módulo está instalado e funcionando.

---

## 🎨 ACESSANDO O MÓDULO

### Via Menu Lateral

No menu lateral, procure por:

```
📝 Formulários Dinâmicos [NOVO]
  ├─ 📋 Meus Formulários
  ├─ ➕ Criar Novo
  ├─ 📊 Relatórios (admin/gestor)
  └─ ⚙️ Instalar/Atualizar (admin)
```

### Via URL Direta

```
Listar:    https://dev1.ideinstituto.com.br/public/formularios-dinamicos/
Criar:     https://dev1.ideinstituto.com.br/public/formularios-dinamicos/criar.php
Instalar:  https://dev1.ideinstituto.com.br/public/formularios-dinamicos/instalar.php
```

---

## 📋 O QUE VOCÊ PODE FAZER AGORA

### ✅ Disponível Imediatamente

- [x] Ver lista de formulários
- [x] Ver formulário de exemplo
- [x] Ver informações de cada formulário
- [x] Duplicar formulário
- [x] Arquivar formulário
- [x] Excluir formulário (se sem respostas)

### 🚧 Em Desenvolvimento (Sprint 2-7)

- [ ] Builder visual drag-and-drop (Sprint 2 - 3 semanas)
- [ ] Editar formulários (Sprint 2 - 3 semanas)
- [ ] Sistema de pontuação (Sprint 3 - 2 semanas)
- [ ] Responder formulários (Sprint 4 - 2 semanas)
- [ ] Relatórios e gráficos (Sprint 5 - 3 semanas)
- [ ] Exportação PDF/Excel (Sprint 6 - 2 semanas)

**Cronograma completo:** 15 semanas (3,5 meses)

---

## 🔍 VERIFICAÇÕES PÓS-INSTALAÇÃO

Execute estas verificações para garantir que tudo está OK:

### 1. Sistema Antigo (CRÍTICO)

Acesse e teste:

```
✓ https://dev1.ideinstituto.com.br/public/checklist/diario/
✓ https://dev1.ideinstituto.com.br/public/checklist/quinzenal/
✓ https://dev1.ideinstituto.com.br/public/gestao/modulos/
```

**Resultado esperado:** Tudo funcionando normalmente.

### 2. Sistema Novo

Acesse e teste:

```
✓ https://dev1.ideinstituto.com.br/public/formularios-dinamicos/
```

**Resultado esperado:** Página carrega, mostra "Formulário de Exemplo".

### 3. Menu Lateral

Verifique:

```
✓ Item "Formulários Dinâmicos" aparece
✓ Badge "NOVO" está visível
✓ Submenu expande ao clicar
✓ Links funcionam corretamente
```

---

## 🗄️ ESTRUTURA DO BANCO DE DADOS

As seguintes tabelas foram criadas:

```sql
1. formularios_dinamicos        -- Formulários criados
2. form_secoes                   -- Seções dos formulários
3. form_perguntas                -- Perguntas (10 tipos)
4. form_opcoes_resposta          -- Opções de múltipla escolha
5. form_respostas                -- Respostas enviadas
6. form_respostas_detalhes       -- Detalhes de cada resposta
7. form_faixas_pontuacao         -- Faixas de classificação
8. form_compartilhamentos        -- Compartilhamento entre usuários
```

**Verificar no banco:**

```sql
-- Verificar tabelas criadas
SHOW TABLES LIKE 'form%';
SHOW TABLES LIKE 'formularios_dinamicos';

-- Ver formulário de exemplo
SELECT * FROM formularios_dinamicos;

-- Ver seções do exemplo
SELECT * FROM form_secoes;

-- Ver perguntas do exemplo
SELECT * FROM form_perguntas;
```

---

## ❓ TROUBLESHOOTING

### Erro: "Acesso Negado"

**Causa:** Usuário não é administrador
**Solução:** Faça login como admin ou peça para um admin executar

### Erro: "Arquivo SQL não encontrado"

**Causa:** Arquivo de migração não está no servidor
**Solução:** Verificar se existe: `/home/user/dev1/database/migrations/020_criar_formularios_dinamicos.sql`

### Erro: "Table already exists"

**Causa:** Instalação já foi executada anteriormente
**Solução:** Normal! O instalador detecta e mostra mensagem apropriada

### Menu não aparece

**Causa:** Cache do navegador
**Solução:** Pressione Ctrl+F5 para forçar atualização

### Sistema de checklists parou

**CRÍTICO!**

1. Restaurar backup imediatamente
2. Verificar logs de erro
3. Reportar problema
4. NÃO prosseguir até resolver

---

## 📊 DADOS DE EXEMPLO

O instalador cria automaticamente:

**1 Formulário:**
- Título: "Formulário de Exemplo"
- Slug: formulario-exemplo
- Status: Rascunho
- Tipo de pontuação: Soma simples

**1 Seção:**
- Título: "Dados Gerais"
- Ordem: 1
- Peso: 1.00

**3 Perguntas:**
1. "Qual é o seu nome?" (texto curto, obrigatória)
2. "Conte-nos sobre sua experiência" (texto longo)
3. "Como você avalia nosso serviço?" (múltipla escolha, obrigatória)

**4 Opções de Resposta:**
- Excelente (10 pontos)
- Bom (7 pontos)
- Regular (4 pontos)
- Ruim (0 pontos)

**4 Faixas de Pontuação:**
- 🔴 Crítico (0-25 pts)
- 🟡 Regular (26-50 pts)
- 🟢 Bom (51-75 pts)
- 🔵 Excelente (76-100 pts)

---

## 🎯 PRÓXIMOS PASSOS

### Curto Prazo (Esta Semana)

1. ✅ **Instalar o módulo** (você está aqui!)
2. ✅ Explorar o formulário de exemplo
3. ✅ Verificar que sistema antigo funciona
4. ✅ Comunicar à equipe que módulo está disponível

### Médio Prazo (Próximas Semanas)

5. 📋 Aguardar Sprint 2 (Builder Visual)
6. 🎨 Criar primeiros formulários reais
7. 🧪 Testar com grupo piloto
8. 📊 Coletar feedback

### Longo Prazo (3-6 Meses)

9. 🚀 Lançar em produção
10. 📈 Migrar usuários gradualmente
11. 🔄 Decidir sobre deprecação do sistema antigo
12. 🎉 Comemorar o sucesso!

---

## 📞 SUPORTE

### Documentação

- `PLANO_FORMULARIOS_DINAMICOS_AJUSTADO.md` - Plano completo
- `GUIA_IMPLEMENTACAO_FORMULARIOS_DINAMICOS.md` - Guia técnico detalhado
- `INSTALACAO_RAPIDA.md` - Este documento

### Problemas?

Se encontrar qualquer problema:

1. Verifique o `GUIA_IMPLEMENTACAO_FORMULARIOS_DINAMICOS.md`
2. Consulte a seção "TROUBLESHOOTING" acima
3. Verifique logs do PHP (`storage/logs/`)
4. Consulte a equipe de desenvolvimento

---

## ✅ CHECKLIST FINAL

Antes de considerar a instalação concluída:

```
Instalação:
☐ Acessei o instalador como admin
☐ Cliquei em "Instalar Agora"
☐ Recebi confirmação de sucesso
☐ 8 tabelas foram criadas

Verificação do Sistema Novo:
☐ Consigo acessar /formularios-dinamicos/
☐ Vejo o formulário de exemplo
☐ Menu lateral mostra "Formulários Dinâmicos"
☐ Badge "NOVO" aparece
☐ Submenu expande corretamente

Verificação do Sistema Antigo (CRÍTICO):
☐ Checklists diários funcionam
☐ Checklists quinzenais funcionam
☐ Gestão de módulos funciona
☐ Gestão de perguntas funciona
☐ Nenhum erro aparece

Comunicação:
☐ Equipe foi informada
☐ Documentação foi compartilhada
☐ Próximos passos foram definidos
```

---

## 🎉 PARABÉNS!

Se você chegou até aqui e todos os itens acima estão ✅, a instalação foi um sucesso!

Você agora tem:

- ✅ Base de dados estruturada
- ✅ Módulo novo isolado e seguro
- ✅ Sistema antigo preservado
- ✅ Menu atualizado
- ✅ Fundação pronta para desenvolvimento

**Próximo marco:** Sprint 2 - Builder Visual (3 semanas)

---

**Data de criação:** 09/11/2025
**Versão:** 1.0
**Status:** Pronto para instalação
**Autor:** Claude (Anthropic)

---

*Fim do Guia de Instalação Rápida*
