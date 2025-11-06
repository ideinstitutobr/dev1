# Operacional

- Materiais de teste
- Instruções pontuais

---

## Configuração de Cores — Preview e Troubleshooting

Página: `Configurações > Sistema` (`public/configuracoes/sistema.php`)

### O que foi ajustado
- `input[type="color"]` sem `padding` para exibir corretamente a amostra nativa.
- Dimensões definidas (`64x36px`) para melhor legibilidade.
- Pré-visualização ao lado do colorpicker: caixinha da cor + código HEX, atualizando em tempo real.

### Como validar
1. Acesse `Configurações > Sistema`.
2. Altere `Cor Primária`, `Gradiente (Início)` e `Gradiente (Fim)`.
3. Verifique se a pré-visualização e o código HEX ao lado mudam instantaneamente.
4. Salve e confirme se o gradiente da sidebar reflete as novas cores.

### Onde as cores são aplicadas
- `app/views/layouts/header.php`: define `--primary-color`, `--gradient-start`, `--gradient-end`.
- `app/views/layouts/sidebar.php`: usa `--gradient-start` e `--gradient-end` no fundo do menu lateral.

### Problemas comuns
- Colorpicker mostra `—` ou sem cor:
  - Verifique se há CSS global com `padding`, `appearance`, `filter`, `opacity` ou `background` aplicados em `input`.
  - Remova `padding` do `input[type="color"]` ou isole estilos com maior especificidade.
- Preview não atualiza:
  - Confirme que os eventos `input/change` estão ativos no formulário de configuração.
  - Verifique se não há erros no console do navegador.

### Dica
- Se desejar, é possível incluir um preview do gradiente atual (início→fim) no topo da seção para validar rapidamente a combinação.
