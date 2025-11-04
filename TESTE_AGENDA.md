# 🧪 Teste do Módulo de Agenda

## ✅ Checklist de Verificação

### **1. Instalação do Banco de Dados**
- [ ] Acessar: `https://comercial.ideinstituto.com.br/public/instalar_agenda.php`
- [ ] Clicar em "Iniciar Instalação"
- [ ] Verificar se apareceu "✅ Instalação concluída!"
- [ ] Confirmar que a tabela `agenda_treinamentos` foi criada

### **2. Verificar Botão na Visualização de Treinamento**
- [ ] Ir em: Treinamentos > Listar
- [ ] Clicar em qualquer treinamento para visualizar
- [ ] **Verificar se o botão "📅 Gerenciar Agenda/Turmas" está aparecendo**
- [ ] O botão deve estar entre "Marcar como Executado" e "Vincular Participantes"
- [ ] O botão deve ser AZUL CLARO (cor: #17a2b8)

### **3. Testar Criação de Agenda**
- [ ] Clicar no botão "📅 Gerenciar Agenda/Turmas"
- [ ] Deve abrir a página de gerenciamento de agenda
- [ ] Clicar em "➕ Nova Turma/Data"
- [ ] Preencher o formulário:
  - **Turma:** Turma A
  - **Data Início:** (data de hoje)
  - **Hora Início:** 09:00
  - **Hora Fim:** 12:00
  - **Local:** Sala 1
  - **Vagas Total:** 20
- [ ] Clicar em "Criar Agenda"
- [ ] Verificar se voltou para a listagem e a agenda foi criada

### **4. Testar Edição de Agenda**
- [ ] Na listagem de agendas, clicar em "✏️ Editar"
- [ ] Modificar algum campo (ex: mudar vagas para 25)
- [ ] Clicar em "Salvar Alterações"
- [ ] Verificar se a mudança foi salva

### **5. Testar Visualização**
- [ ] Verificar se a tabela mostra:
  - ✅ Turma
  - ✅ Período (data início e fim)
  - ✅ Horário
  - ✅ Local
  - ✅ Vagas (0/20 por exemplo)
  - ✅ Status com badge colorido

---

## 🐛 Problemas Comuns e Soluções

### **Problema: Botão não aparece na visualização**

**Possíveis causas:**
1. Cache do navegador
2. Arquivo não foi atualizado no servidor

**Soluções:**
1. **Limpar cache:**
   - Pressionar `Ctrl + Shift + R` (Windows/Linux)
   - Ou `Cmd + Shift + R` (Mac)

2. **Verificar se o arquivo foi enviado:**
   - Verificar data de modificação do arquivo `visualizar.php` no servidor
   - Deve ser a data/hora de hoje

3. **Inspecionar elemento:**
   - Clicar com botão direito na página
   - "Inspecionar elemento"
   - Procurar por "Gerenciar Agenda"
   - Se encontrar, significa que o botão está lá mas pode estar escondido

4. **Verificar permissões:**
   - O botão aparece para todos os níveis de usuário
   - Não há restrição de permissão

---

### **Problema: Erro ao acessar gerenciar.php**

**Erro possível:** "Tabela agenda_treinamentos não existe"

**Solução:**
- Executar a instalação: `public/instalar_agenda.php`

---

### **Problema: Erro ao criar agenda**

**Erro possível:** "Token inválido"

**Solução:**
- Verificar se a sessão está ativa
- Fazer logout e login novamente

---

## 📸 Como Deve Parecer

### **Visualização de Treinamento - Botões de Ação:**
```
[ ✏️ Editar Treinamento ]  [ ❌ Cancelar ]  [ ✅ Marcar como Executado ]

[ 📅 Gerenciar Agenda/Turmas ]  [ ➕ Vincular Participantes ]  [ ← Voltar ]
       ↑ ESTE BOTÃO DEVE APARECER
      COR AZUL CLARO (#17a2b8)
```

### **Página de Gerenciar Agenda:**
```
📅 Agenda do Treinamento
Nome do Treinamento Aqui

[ ➕ Nova Turma/Data ]                    [ ← Voltar ao Treinamento ]

┌─────────────────────────────────────────────────────────────┐
│ Turma │ Período │ Horário │ Local │ Vagas │ Status │ Ações │
├─────────────────────────────────────────────────────────────┤
│ (vazio se não houver agendas criadas)                       │
└─────────────────────────────────────────────────────────────┘
```

---

## 🔍 Inspeção Manual

Se o botão não aparecer, verifique manualmente no código-fonte da página:

1. Abra a página de visualização do treinamento
2. Pressione `Ctrl + U` para ver o código-fonte
3. Pressione `Ctrl + F` e procure por: `Gerenciar Agenda`
4. Deve encontrar algo como:
   ```html
   <a href="../agenda/gerenciar.php?treinamento_id=123" class="btn btn-info">
       📅 Gerenciar Agenda/Turmas
   </a>
   ```

Se encontrar isso, significa que o código está correto e o problema é de CSS ou cache.

---

## ✅ Após os Testes

Se tudo funcionar:
- ✅ Módulo de Agenda está 100% operacional
- ✅ Pode criar múltiplas turmas/datas
- ✅ Pode controlar vagas
- ✅ Está integrado com o sistema

Próximo passo: Implementar Indicadores de RH! 📊

---

**Qualquer problema, me avise com print da tela!** 📸
