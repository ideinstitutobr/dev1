# 🧪 Instruções de Teste - Formulários Quinzenais e Diários

## ✅ Implementação Concluída!

A implementação dos dois tipos de formulários foi **concluída com sucesso**!

---

## 📋 O que foi implementado:

### 1. ✅ Banco de Dados
- Coluna `tipo` adicionada à tabela `checklists`
- Valores: `'quinzenal_mensal'` ou `'diario'`
- Índices criados para otimização

### 2. ✅ Models
- `Checklist.php` atualizado para suportar o campo `tipo`
- Validação de tipo implementada
- Filtros por tipo adicionados

### 3. ✅ Estrutura de Arquivos
```
/public/checklist/
├── quinzenal/               (Avaliações Quinzenais/Mensais)
│   ├── index.php
│   ├── novo.php
│   ├── editar.php
│   └── visualizar.php
│
├── diario/                  (Avaliações Diárias)
│   ├── index.php
│   ├── novo.php
│   ├── editar.php
│   └── visualizar.php
│
└── shared/                  (Arquivos Compartilhados)
    ├── salvar_resposta.php
    ├── finalizar.php
    └── buscar_liderancas.php
```

### 4. ✅ Menu Sidebar
Novo menu organizado com:
- 📅 **Quinzenais/Mensais**
  - Lista de Avaliações
  - Nova Avaliação
- 📆 **Avaliações Diárias**
  - Lista de Avaliações
  - Nova Avaliação
- ⚙️ **Configurações**
  - Módulos

---

## 🧪 Testes Necessários

### Teste 1: Criar Avaliação Quinzenal/Mensal
1. Acesse o menu: **Formulários → Quinzenais/Mensais → Nova Avaliação**
2. URL: `http://dev1.ideinstituto.com.br/public/checklist/quinzenal/novo.php`
3. Preencha:
   - Selecione uma unidade
   - Selecione um responsável
   - Data da avaliação
   - Observações (opcional)
4. Clique em "Criar e Começar Avaliação"
5. **Esperado:** Deve redirecionar para a página de edição com todos os módulos

### Teste 2: Criar Avaliação Diária
1. Acesse o menu: **Formulários → Avaliações Diárias → Nova Avaliação**
2. URL: `http://dev1.ideinstituto.com.br/public/checklist/diario/novo.php`
3. Preencha os mesmos campos
4. Clique em "Criar e Começar Avaliação"
5. **Esperado:** Deve redirecionar para a página de edição com todos os módulos

### Teste 3: Preencher e Finalizar Avaliação Quinzenal
1. Acesse uma avaliação quinzenal criada
2. Responda todas as perguntas (estrelas)
3. Adicione observações e fotos (opcional)
4. Clique em "Finalizar Avaliação"
5. **Esperado:** Redirecionar para visualizar resultado

### Teste 4: Preencher e Finalizar Avaliação Diária
1. Acesse uma avaliação diária criada
2. Responda todas as perguntas
3. Finalize a avaliação
4. **Esperado:** Redirecionar para visualizar resultado

### Teste 5: Verificar Listas Separadas
1. Acesse: **Formulários → Quinzenais/Mensais → Lista de Avaliações**
2. URL: `http://dev1.ideinstituto.com.br/public/checklist/quinzenal/`
3. **Esperado:** Deve mostrar APENAS avaliações quinzenais/mensais

4. Acesse: **Formulários → Avaliações Diárias → Lista de Avaliações**
5. URL: `http://dev1.ideinstituto.com.br/public/checklist/diario/`
6. **Esperado:** Deve mostrar APENAS avaliações diárias

---

## ⚠️ Possíveis Problemas e Soluções

### Problema 1: "Column 'tipo' not found"
**Solução:** Execute a migration:
```
http://dev1.ideinstituto.com.br/public/migration_adicionar_tipo_formulario.php
```

### Problema 2: Lista vazia nas páginas de quinzenal/diario
**Causa:** Os arquivos index.php ainda não têm o filtro por tipo
**Solução:** Será necessário adicionar o filtro (próxima etapa)

### Problema 3: Erro ao criar avaliação
**Verificar:**
1. Se a migration foi executada
2. Se o campo `tipo` está no formulário
3. Logs de erro do PHP

---

## 📊 Verificar Dados no Banco

Execute este script para verificar os dados:
```
http://dev1.ideinstituto.com.br/public/verificar_migration_tipo.php
```

Deve mostrar:
- ✅ Coluna 'tipo' existe
- ✅ Registros existentes marcados como 'quinzenal_mensal'
- ✅ Índices criados

---

## 🔍 Próximos Ajustes Necessários

### Ajuste Pendente 1: Filtrar listas por tipo
Os arquivos `quinzenal/index.php` e `diario/index.php` precisam adicionar filtro:

**No arquivo quinzenal/index.php:**
```php
// Adicionar filtro de tipo
$_GET['tipo'] = 'quinzenal_mensal';
$dados = $controller->listar();
```

**No arquivo diario/index.php:**
```php
// Adicionar filtro de tipo
$_GET['tipo'] = 'diario';
$dados = $controller->listar();
```

### Ajuste Pendente 2: Atualizar Controllers
O `ChecklistController.php` precisa passar o filtro de tipo para o Model:

```php
public function listar() {
    $filtros = [
        'tipo' => $_GET['tipo'] ?? null,
        'unidade_id' => $_GET['unidade_id'] ?? null,
        // ... outros filtros
    ];

    return $this->checklistModel->listarComFiltros($filtros);
}
```

---

## ✅ Checklist de Testes

- [ ] Executar migration (se ainda não executou)
- [ ] Criar avaliação quinzenal
- [ ] Criar avaliação diária
- [ ] Preencher e finalizar avaliação quinzenal
- [ ] Preencher e finalizar avaliação diária
- [ ] Verificar listas separadas
- [ ] Verificar que não há cruzamento de dados
- [ ] Testar busca de lideranças
- [ ] Testar upload de fotos
- [ ] Testar observações

---

## 📝 Relatório de Teste

Após testar, anote:

**Testes Bem-Sucedidos:**
- [ ] Criação quinzenal: ✅ / ❌
- [ ] Criação diária: ✅ / ❌
- [ ] Preenchimento quinzenal: ✅ / ❌
- [ ] Preenchimento diário: ✅ / ❌
- [ ] Finalização quinzenal: ✅ / ❌
- [ ] Finalização diária: ✅ / ❌
- [ ] Listas separadas: ✅ / ❌

**Problemas Encontrados:**
- (Liste aqui qualquer problema)

---

## 🚀 Após os Testes

Se tudo estiver funcionando:
1. Podemos adicionar os filtros nas listas
2. Implementar dashboards separados (opcional)
3. Adicionar badges visuais para diferenciar os tipos
4. Limpar arquivos de debug/migration

**Documento criado em:** <?php echo date('Y-m-d H:i:s'); ?>
**Status:** Pronto para testes
