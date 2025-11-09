# EXEMPLOS DE USO - SISTEMA DE EVENTOS

**Sistema:** SGC - Sistema de Gestão de Capacitações
**Componente:** EventManager
**Objetivo:** Facilitar a extensibilidade do sistema através de eventos e hooks

---

## 📋 ÍNDICE

1. [Conceitos Básicos](#conceitos-básicos)
2. [Sintaxe Laravel](#sintaxe-laravel)
3. [Sintaxe WordPress](#sintaxe-wordpress)
4. [Casos de Uso Reais](#casos-de-uso-reais)
5. [Eventos do Sistema](#eventos-do-sistema)
6. [Boas Práticas](#boas-práticas)

---

## CONCEITOS BÁSICOS

### O que são Eventos?

Eventos permitem que diferentes partes do sistema se comuniquem sem dependência direta:

```php
// Sem eventos (acoplado)
class TreinamentoController {
    public function criar($dados) {
        $treinamento = $this->model->criar($dados);

        // Código acoplado - difícil de estender
        $this->enviarEmail($treinamento);
        $this->atualizarEstatisticas($treinamento);
        $this->notificarGestor($treinamento);
    }
}

// Com eventos (desacoplado)
class TreinamentoController {
    public function criar($dados) {
        $treinamento = $this->model->criar($dados);

        // Dispara evento - módulos externos podem reagir
        event('treinamento.criado', $treinamento);

        return $treinamento;
    }
}
```

### Vantagens

✅ **Desacoplamento** - Componentes não dependem uns dos outros
✅ **Extensibilidade** - Adicionar funcionalidades sem modificar código
✅ **Testabilidade** - Fácil criar mocks e testar isoladamente
✅ **Modularidade** - Módulos podem adicionar comportamentos
✅ **Manutenibilidade** - Mudanças isoladas não quebram sistema

---

## SINTAXE LARAVEL

### 1. Registrar Listener

```php
// Registrar um listener simples
listen('treinamento.criado', function($treinamento) {
    // Enviar e-mail de boas-vindas
    $mailer = app('MailService');
    $mailer->send([
        'to' => $treinamento['email_instrutor'],
        'subject' => 'Novo Treinamento Criado',
        'body' => "Treinamento: {$treinamento['nome']}"
    ]);
});

// Listener com prioridade (maior = executa primeiro)
listen('treinamento.criado', function($treinamento) {
    // Log antes de tudo
    logger("Treinamento criado: {$treinamento['id']}");
}, 100);  // Prioridade 100 (executa primeiro)
```

### 2. Disparar Evento

```php
// Sintaxe simples
event('treinamento.criado', $treinamento);

// ou

dispatch('treinamento.criado', $treinamento);

// Com múltiplos parâmetros
dispatch('participante.inscrito', [$treinamento, $colaborador]);
```

### 3. Wildcard Events

```php
// Ouvir TODOS os eventos de treinamento
listen('treinamento.*', function($data) {
    logger("Evento de treinamento disparado");
});

// Ouvir todos os eventos
listen('*', function($data) {
    logger("Qualquer evento disparado");
});
```

### 4. Listener que Executa Apenas Uma Vez

```php
event()->once('sistema.iniciado', function() {
    // Executar migração inicial (apenas uma vez)
    $this->executarMigracao();
});
```

### 5. Listener de Classe

```php
// Registrar método de uma classe
listen('treinamento.criado', 'NotificacaoService@enviarEmail');

// A classe será resolvida automaticamente pelo container
class NotificacaoService {
    public function enviarEmail($treinamento) {
        // Enviar e-mail
    }
}
```

---

## SINTAXE WORDPRESS

O EventManager também suporta a sintaxe familiar do WordPress:

### 1. Actions (Hooks de Ação)

```php
// Adicionar action
add_action('treinamento.criado', function($treinamento) {
    // Fazer algo quando treinamento é criado
    logger("Treinamento criado: {$treinamento['nome']}");
}, 10);  // Prioridade 10 (padrão)

// Disparar action
do_action('treinamento.criado', $treinamento);

// Verificar se tem actions
if (has_action('treinamento.criado')) {
    // Tem listeners registrados
}

// Remover action
remove_action('treinamento.criado', $callback);
```

### 2. Filters (Hooks de Filtro)

```php
// Adicionar filtro
add_action('treinamento.titulo', function($titulo, $treinamento) {
    // Modificar título antes de salvar
    return strtoupper($titulo);
}, 10);

// Aplicar filtros
$titulo = 'Treinamento de PHP';
$titulo = apply_filters('treinamento.titulo', $titulo, $treinamento);
// Resultado: "TREINAMENTO DE PHP"

// Múltiplos filtros em cadeia
add_action('treinamento.titulo', function($titulo) {
    return trim($titulo);
}, 5);  // Executa primeiro (prioridade 5)

add_action('treinamento.titulo', function($titulo) {
    return ucwords($titulo);
}, 10);  // Executa depois (prioridade 10)

$titulo = apply_filters('treinamento.titulo', '  treinamento de php  ');
// Resultado: "Treinamento De Php"
```

---

## CASOS DE USO REAIS

### Caso 1: Notificações ao Criar Treinamento

**Problema:** Quando um treinamento é criado, precisamos:
- Enviar e-mail ao instrutor
- Notificar gestor
- Atualizar estatísticas
- Logar auditoria

**Solução com Eventos:**

```php
// app/controllers/TreinamentoController.php
public function criar($dados) {
    $treinamento = $this->service->criar($dados);

    // Disparar evento - simples!
    event('treinamento.criado', $treinamento);

    return $treinamento;
}

// Módulo de Notificações (pode estar em arquivo separado)
// app/Modules/Notificacao/listeners.php
add_action('treinamento.criado', function($treinamento) {
    $mailer = app('MailService');

    // E-mail para instrutor
    $mailer->send([
        'to' => $treinamento['email_instrutor'],
        'subject' => 'Novo Treinamento Criado',
        'template' => 'treinamento-criado',
        'data' => $treinamento
    ]);
}, 10);

// Módulo de Estatísticas
// app/Modules/Estatisticas/listeners.php
add_action('treinamento.criado', function($treinamento) {
    $stats = app('EstatisticasService');
    $stats->incrementar('total_treinamentos');
    $stats->registrar('treinamento_criado', [
        'id' => $treinamento['id'],
        'tipo' => $treinamento['tipo']
    ]);
}, 5);

// Módulo de Auditoria
// app/Modules/Auditoria/listeners.php
add_action('treinamento.criado', function($treinamento) {
    logger("Treinamento criado: {$treinamento['nome']}", 'audit');
}, 15);
```

**Benefício:** Cada módulo é independente. Posso adicionar/remover funcionalidades sem tocar no controller!

---

### Caso 2: Validação Customizada com Filtros

**Problema:** Validar dados do treinamento antes de salvar

```php
// Adicionar validação customizada
add_action('treinamento.validar', function($erros, $dados) {
    // Validação do módulo de orçamento
    if ($dados['custo_total'] > 100000) {
        $erros[] = 'Custo muito alto! Necessita aprovação da diretoria';
    }

    return $erros;
}, 10);

// No controller
public function criar($dados) {
    $erros = [];

    // Aplicar filtros de validação
    $erros = apply_filters('treinamento.validar', $erros, $dados);

    if (!empty($erros)) {
        return ['success' => false, 'errors' => $erros];
    }

    // Continuar...
}
```

---

### Caso 3: Modificar Dados Antes de Salvar

```php
// Normalizar título
add_action('treinamento.antes_salvar', function($dados) {
    // Sempre salvar título em uppercase
    $dados['nome'] = strtoupper($dados['nome']);

    // Remover espaços extras
    $dados['nome'] = trim($dados['nome']);

    return $dados;
}, 10);

// Adicionar tags automáticas
add_action('treinamento.antes_salvar', function($dados) {
    if (empty($dados['tags'])) {
        // Gerar tags baseado no tipo
        $dados['tags'] = $this->gerarTags($dados['tipo']);
    }

    return $dados;
}, 20);

// No model
public function criar($dados) {
    // Aplicar filtros
    $dados = apply_filters('treinamento.antes_salvar', $dados);

    // Salvar
    return $this->inserir($dados);
}
```

---

### Caso 4: Plugin/Módulo de Certificados

**Problema:** Adicionar sistema de certificados SEM modificar código existente

```php
// app/Modules/Certificado/CertificadoServiceProvider.php
public function boot() {
    $events = app('EventManager');

    // Quando treinamento é concluído, gerar certificado
    $events->listen('treinamento.concluido', function($treinamento) {
        $service = app('CertificadoService');

        // Buscar participantes que completaram
        $participantes = app('Participante')->buscarPorTreinamento($treinamento['id']);

        foreach ($participantes as $participante) {
            if ($participante['concluiu']) {
                // Gerar certificado
                $service->gerar([
                    'treinamento_id' => $treinamento['id'],
                    'colaborador_id' => $participante['colaborador_id'],
                    'data_conclusao' => now()
                ]);
            }
        }
    });

    // Quando certificado é gerado, enviar por e-mail
    $events->listen('certificado.gerado', function($certificado) {
        $mailer = app('MailService');
        $mailer->enviarCertificado($certificado);
    });
}
```

**Benefício:** Módulo completamente independente! Pode ser ativado/desativado sem afetar o resto do sistema.

---

## EVENTOS DO SISTEMA

### Eventos de Treinamento

```php
'treinamento.antes.criar'      // Antes de criar
'treinamento.criado'           // Após criar
'treinamento.antes.atualizar'  // Antes de atualizar
'treinamento.atualizado'       // Após atualizar
'treinamento.antes.deletar'    // Antes de deletar
'treinamento.deletado'         // Após deletar
'treinamento.cancelado'        // Quando cancelado
'treinamento.executado'        // Quando marcado como executado
'treinamento.concluido'        // Quando concluído
```

### Eventos de Colaborador

```php
'colaborador.criado'
'colaborador.atualizado'
'colaborador.inativado'
'colaborador.ativado'
```

### Eventos de Participante

```php
'participante.inscrito'        // Nova inscrição
'participante.confirmado'      // Inscrição confirmada
'participante.removido'        // Removido do treinamento
'participante.avaliado'        // Avaliação registrada
```

### Eventos de Frequência

```php
'frequencia.checkin'           // Check-in realizado
'frequencia.atualizada'        // Frequência modificada
```

### Eventos de Sistema

```php
'sistema.iniciado'             // Sistema inicializado
'usuario.logado'               // Login realizado
'usuario.deslogado'            // Logout realizado
'email.enviado'                // E-mail enviado
'email.falhou'                 // Falha no envio
```

---

## BOAS PRÁTICAS

### 1. Nomenclatura de Eventos

✅ **BOM:**
```php
'treinamento.criado'
'colaborador.atualizado'
'participante.inscrito'
```

❌ **RUIM:**
```php
'TreinamentoCriado'
'update_colaborador'
'newParticipant'
```

**Padrão:** `entidade.acao` (lowercase, separado por ponto)

---

### 2. Documentar Eventos

```php
/**
 * Disparado quando um treinamento é criado
 *
 * @event treinamento.criado
 * @param array $treinamento Dados do treinamento
 * @param array $usuario Usuário que criou
 */
event('treinamento.criado', [$treinamento, $usuario]);
```

---

### 3. Não Fazer Demais nos Listeners

❌ **RUIM:**
```php
add_action('treinamento.criado', function($treinamento) {
    // 100 linhas de código
    // Múltiplas operações de banco
    // Processamento pesado
});
```

✅ **BOM:**
```php
add_action('treinamento.criado', function($treinamento) {
    // Delegar para service
    $service = app('NotificacaoService');
    $service->processar($treinamento);
});
```

---

### 4. Usar Prioridades Corretamente

```php
// Prioridades recomendadas:
// 0-5:   Validações e preparação
// 5-10:  Lógica principal
// 10-15: Notificações
// 15-20: Logging e auditoria
// 20+:   Tarefas finais

add_action('treinamento.criado', $validarOrcamento, 5);    // Primeiro
add_action('treinamento.criado', $enviarEmail, 10);        // Meio
add_action('treinamento.criado', $logarAuditoria, 15);     // Último
```

---

### 5. Tratar Erros nos Listeners

```php
add_action('treinamento.criado', function($treinamento) {
    try {
        // Código que pode falhar
        $mailer->send(...);
    } catch (Exception $e) {
        // Log do erro - não quebrar o sistema
        logger("Erro ao enviar e-mail: " . $e->getMessage(), 'error');

        // Opcionalmente disparar evento de erro
        event('email.falhou', [
            'treinamento' => $treinamento,
            'erro' => $e->getMessage()
        ]);
    }
});
```

---

## EXEMPLO COMPLETO: MÓDULO DE NOTIFICAÇÕES

```php
<?php
// app/Modules/Notificacao/NotificacaoServiceProvider.php

namespace App\Modules\Notificacao;

use App\Core\ServiceProvider;
use App\Core\Container;

class NotificacaoServiceProvider extends ServiceProvider
{
    public function register(Container $container)
    {
        $container->singleton('NotificacaoService', function($c) {
            return new NotificacaoService(
                $c->get('MailService'),
                $c->get('EventManager')
            );
        });
    }

    public function boot(Container $container)
    {
        $events = $container->get('EventManager');
        $service = $container->get('NotificacaoService');

        // Treinamento criado
        $events->listen('treinamento.criado', function($treinamento) use ($service) {
            $service->notificarTreinamentoCriado($treinamento);
        }, 10);

        // Participante inscrito
        $events->listen('participante.inscrito', function($participante, $treinamento) use ($service) {
            $service->notificarInscricao($participante, $treinamento);
        }, 10);

        // Treinamento cancelado
        $events->listen('treinamento.cancelado', function($treinamento) use ($service) {
            $service->notificarCancelamento($treinamento);
        }, 10);

        // Check-in realizado
        $events->listen('frequencia.checkin', function($frequencia) use ($service) {
            $service->confirmarCheckin($frequencia);
        }, 10);
    }
}

// app/Modules/Notificacao/NotificacaoService.php
class NotificacaoService
{
    private $mailer;
    private $events;

    public function __construct($mailer, $events)
    {
        $this->mailer = $mailer;
        $this->events = $events;
    }

    public function notificarTreinamentoCriado($treinamento)
    {
        try {
            $this->mailer->send([
                'to' => $treinamento['email_instrutor'],
                'subject' => 'Novo Treinamento Criado',
                'template' => 'emails/treinamento-criado',
                'data' => $treinamento
            ]);

            // Disparar evento de sucesso
            $this->events->dispatch('email.enviado', [
                'tipo' => 'treinamento_criado',
                'destinatario' => $treinamento['email_instrutor']
            ]);

        } catch (Exception $e) {
            logger("Erro ao enviar email: " . $e->getMessage(), 'error');

            $this->events->dispatch('email.falhou', [
                'erro' => $e->getMessage(),
                'tipo' => 'treinamento_criado'
            ]);
        }
    }
}
```

---

## RESUMO

### Quando Usar Eventos?

✅ **USE quando:**
- Múltiplos módulos precisam reagir a uma ação
- Quer adicionar funcionalidades sem modificar código existente
- Precisa de extensibilidade para plugins/módulos
- Quer desacoplar componentes

❌ **NÃO USE quando:**
- Lógica é simples e direta
- Apenas um componente precisa da informação
- Performance é crítica (eventos têm overhead mínimo, mas existe)

### Resumo de Sintaxe

```php
// Laravel Style
listen('evento', $callback, $priority);
dispatch('evento', $dados);

// WordPress Style
add_action('hook', $callback, $priority);
do_action('hook', $dados);
apply_filters('filtro', $valor);

// Helpers
event('evento', $dados);              // Disparar
event()->listen('evento', $callback);  // Registrar
has_action('hook');                    // Verificar
remove_action('hook', $callback);      // Remover
```

---

**FIM DOS EXEMPLOS**

Para mais informações, consulte:
- `app/Core/EventManager.php` - Código fonte
- `PLANO_REFATORACAO_ARQUITETURA_MODULAR.md` - Arquitetura completa
- `GUIA_IMPLEMENTACAO_NOVOS_RECURSOS.md` - Como criar módulos
