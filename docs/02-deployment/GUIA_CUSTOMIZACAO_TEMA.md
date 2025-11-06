# Guia de Implementação - Painel de Customização Completa

Este guia documenta o novo sistema de customização de cores e tipografia do SGC.

## 📋 O que foi implementado

### 1. Sistema de CSS Variables Dinâmicas
Arquivo: `public/assets/css/theme-variables.php`

**Variáveis disponíveis:**
- Cores primárias (primary, primary-dark, primary-light)
- Cores secundárias (secondary, secondary-dark, secondary-light)
- Cores de status (success, danger, warning, info)
- Cores de texto (text-primary, text-secondary, text-muted)
- Cores de links (link-color, link-hover)
- Cores de fundo (bg-body, bg-content, bg-sidebar)
- Cores do menu/sidebar
- Gradientes customizáveis
- Tipografia (font-family, tamanhos)
- Bordas e sombras

### 2. Aplicação Automática
O arquivo `theme-variables.php` é carregado como CSS e gera variáveis CSS dinamicamente a partir das configurações salvas no banco de dados.

### 3. Classes CSS Prontas
Todas as classes comuns do sistema já aplicam as variáveis:
- `.btn-primary`, `.btn-secondary`, `.btn-success`, `.btn-danger`, `.btn-warning`, `.btn-info`
- `.alert-success`, `.alert-danger`, `.alert-warning`, `.alert-info`
- `.badge-*`
- `.card`, `.config-card`
- Links e hover states

## 🎨 Como Usar

### Para Desenvolvedores

**Usar variáveis CSS em seus estilos:**
```css
.meu-elemento {
    background: var(--primary-color);
    color: var(--text-primary);
    border-radius: var(--border-radius);
    font-family: var(--font-family);
}
```

**Cores disponíveis:**
```css
--primary-color
--primary-dark
--primary-light
--secondary-color
--success-color
--danger-color
--warning-color
--info-color
--text-primary
--text-secondary
--text-muted
--link-color
--link-hover
--bg-body
--bg-content
--sidebar-bg
--sidebar-text
--sidebar-hover
--sidebar-active
--gradient-start
--gradient-end
--gradient-primary
```

**Tipografia:**
```css
--font-family
--font-family-headings
--font-size-base
--font-size-large
--font-size-small
```

### Para Administradores

1. Acesse **Configurações > Sistema**
2. Na seção expandida, configure:
   - Cores primárias e secundárias
   - Cores de status (sucesso, erro, aviso, info)
   - Cores de texto e links
   - Cores do menu/sidebar
   - Tipografia (fontes e tamanhos)
3. Clique em "Salvar Configurações"
4. As mudanças são aplicadas imediatamente em todo o sistema

## 🔄 Como Expandir

Para adicionar novas variáveis:

1. **Adicione no theme-variables.php:**
```php
$config = [
    // ... existentes
    'nova_variavel' => SystemConfig::get('nova_variavel', 'valor_padrao'),
];
```

2. **Adicione a variável CSS:**
```css
:root {
    --nova-variavel: <?php echo $config['nova_variavel']; ?>;
}
```

3. **Adicione no painel de configurações:**
```html
<div class="form-group">
    <label>Nova Variável</label>
    <input type="color" name="nova_variavel" value="<?php echo e($nova_variavel); ?>">
</div>
```

4. **Salve no actions.php:**
```php
$novaVariavel = trim($_POST['nova_variavel'] ?? '#000000');
SystemConfig::set('nova_variavel', $novaVariavel);
```

## 📝 Chaves de Configuração

Todas as configurações são salvas na tabela `configuracoes_sistema` com as seguintes chaves:

**Cores:**
- `primary_color`, `primary_dark`, `primary_light`
- `secondary_color`, `secondary_dark`, `secondary_light`
- `success_color`, `danger_color`, `warning_color`, `info_color`
- `text_primary`, `text_secondary`, `text_muted`
- `link_color`, `link_hover`
- `bg_body`, `bg_content`, `bg_sidebar`
- `sidebar_bg`, `sidebar_text`, `sidebar_hover`, `sidebar_active`
- `gradient_start`, `gradient_end`

**Tipografia:**
- `font_family`
- `font_family_headings`
- `font_size_base`
- `font_size_large`
- `font_size_small`

**Outros:**
- `border_radius`
- `box_shadow`

## 🎯 Benefícios

1. **Centralização:** Todas as cores em um só lugar
2. **Consistência:** Garante visual uniforme
3. **Facilidade:** Mudanças aplicadas instantaneamente
4. **Flexibilidade:** Cada cliente pode ter seu próprio tema
5. **Performance:** CSS gerado dinamicamente e cacheável
6. **Manutenibilidade:** Fácil de expandir e modificar

## 🚀 Próximas Melhorias (Futuro)

- [ ] Temas pré-definidos (claro, escuro, azul, verde, etc.)
- [ ] Exportar/importar temas
- [ ] Preview em tempo real sem salvar
- [ ] Reset para tema padrão
- [ ] Modo escuro automático
- [ ] Suporte a  múltiplos temas por usuário
