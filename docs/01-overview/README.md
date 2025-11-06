# Overview

- Introdução ao projeto e objetivos
- Contexto do negócio
- Escopo geral

---

## Atualizações de Recursos (2025-11)

- Página **Configurar Campos** reestruturada em abas: Nível Hierárquico, Cargo, Departamento e Setor.
  - Cabeçalho com meta "Itens • Vínculos" e adição direta.
  - Linhas com colunas padronizadas: Nome | Vinculados | Ações.
  - Ações por ícones: Renomear (inline) e Remover (com confirmação).
  - Indicador centralizado "N vínculo(s)" e escrita do catálogo atômica (LOCK_EX).

- **Nível Hierárquico** dinâmico (ENUM):
  - Adição, renomeação e remoção controladas com atualização da definição ENUM e dos registros.
  - Remoção bloqueada quando há vínculos.

- **Formulários de Colaboradores** (Cadastrar/Editar):
  - Nível como select dinâmico (valores do ENUM).
  - Cargo/Departamento/Setor como select dinâmico (banco + catálogo), sem duplicados.
  - Setor condicional: aparece como select quando a coluna existe; caso contrário, campo desabilitado com instrução de instalação.

- **Listagem de Colaboradores**:
  - Filtros dinâmicos para Nível, Cargo, Departamento e Setor.
  - Colunas estáveis (inclui Setor) e fallback visual para valores ausentes.
  - CSS defensivo garantindo exibição de cabeçalhos `<th>`.

- **Visualização do Colaborador**:
  - Badge de Nível e exibição de Setor quando disponível.

- **Assets**:
  - Placeholders básicos criados em `public/assets/css/` e `public/assets/js/` para evitar 404 e prover estilos mínimos.
