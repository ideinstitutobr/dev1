# 📋 PLANO DE DESENVOLVIMENTO - SISTEMA DE CHECKLIST DE LOJA
## Implementação em PHP MVC

---

## 🎯 Objetivo do Projeto

Desenvolver um sistema completo de checklist diário de lojas com:
- Formulário digital para supervisão
- Cálculo automático de pontuações
- Relatórios visuais e comparativos
- Dashboard BI integrado

---

## 📊 Análise dos Requisitos

### Sistema de Pontuação

**Módulo 1 - 8 Perguntas:**
- Pontuação máxima: 5 pontos (0,625 por pergunta)
- Escala: 1 a 5 estrelas
- Pesos: ⭐(0,125) | ⭐⭐(0,25) | ⭐⭐⭐(0,375) | ⭐⭐⭐⭐(0,5) | ⭐⭐⭐⭐⭐(0,625)

**Módulo 2 - 6 Perguntas:**
- Pontuação máxima: 5 pontos (0,833 por pergunta)
- Escala: 1 a 5 estrelas
- Pesos: ⭐(0,167) | ⭐⭐(0,333) | ⭐⭐⭐(0,500) | ⭐⭐⭐⭐(0,667) | ⭐⭐⭐⭐⭐(0,833)

### Setores a Avaliar
1. Organização de Lojas
2. Caixas
3. Setor Ovos
4. Gôndolas e Ilhas
5. Balcão de Frios
6. Câmara Fria
7. Estoque
8. Áreas Comuns de Colaboradores

---

## 🗄️ FASE 1: MODELAGEM DO BANCO DE DADOS

### 1.1. Estrutura de Tabelas

#### Tabela: `lojas`
```sql
CREATE TABLE lojas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    codigo VARCHAR(20) UNIQUE,
    endereco TEXT,
    cidade VARCHAR(100),
    estado VARCHAR(2),
    ativo BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### Tabela: `colaboradores`
```sql
CREATE TABLE colaboradores (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    cpf VARCHAR(14) UNIQUE,
    cargo_id INT,
    loja_id INT,
    email VARCHAR(100),
    telefone VARCHAR(20),
    ativo BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cargo_id) REFERENCES cargos(id),
    FOREIGN KEY (loja_id) REFERENCES lojas(id)
);
```

#### Tabela: `cargos`
```sql
CREATE TABLE cargos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(50) NOT NULL,
    nivel_acesso ENUM('supervisor', 'gerente', 'administrador') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### Tabela: `modulos_avaliacao`
```sql
CREATE TABLE modulos_avaliacao (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    total_perguntas INT NOT NULL,
    peso_por_pergunta DECIMAL(5,3) NOT NULL,
    ordem INT,
    ativo BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### Tabela: `perguntas`
```sql
CREATE TABLE perguntas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    modulo_id INT NOT NULL,
    texto TEXT NOT NULL,
    descricao TEXT,
    ordem INT,
    obrigatoria BOOLEAN DEFAULT 1,
    permite_foto BOOLEAN DEFAULT 1,
    ativo BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (modulo_id) REFERENCES modulos_avaliacao(id)
);
```

#### Tabela: `checklists`
```sql
CREATE TABLE checklists (
    id INT PRIMARY KEY AUTO_INCREMENT,
    loja_id INT NOT NULL,
    colaborador_id INT NOT NULL,
    data_avaliacao DATE NOT NULL,
    modulo_id INT NOT NULL,
    pontuacao_total DECIMAL(4,2) DEFAULT 0,
    pontuacao_maxima DECIMAL(4,2) DEFAULT 5,
    percentual DECIMAL(5,2) DEFAULT 0,
    atingiu_meta BOOLEAN DEFAULT 0,
    observacoes_gerais TEXT,
    status ENUM('rascunho', 'finalizado', 'revisado') DEFAULT 'rascunho',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (loja_id) REFERENCES lojas(id),
    FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id),
    FOREIGN KEY (modulo_id) REFERENCES modulos_avaliacao(id),
    UNIQUE KEY unique_checklist (loja_id, data_avaliacao, modulo_id)
);
```

#### Tabela: `respostas_checklist`
```sql
CREATE TABLE respostas_checklist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    checklist_id INT NOT NULL,
    pergunta_id INT NOT NULL,
    estrelas INT NOT NULL CHECK (estrelas BETWEEN 1 AND 5),
    pontuacao DECIMAL(5,3) NOT NULL,
    observacao TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (checklist_id) REFERENCES checklists(id) ON DELETE CASCADE,
    FOREIGN KEY (pergunta_id) REFERENCES perguntas(id)
);
```

#### Tabela: `fotos_checklist`
```sql
CREATE TABLE fotos_checklist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    resposta_id INT NOT NULL,
    caminho VARCHAR(255) NOT NULL,
    legenda TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (resposta_id) REFERENCES respostas_checklist(id) ON DELETE CASCADE
);
```

#### Tabela: `configuracoes_sistema`
```sql
CREATE TABLE configuracoes_sistema (
    id INT PRIMARY KEY AUTO_INCREMENT,
    chave VARCHAR(50) UNIQUE NOT NULL,
    valor TEXT,
    descricao TEXT,
    tipo ENUM('int', 'decimal', 'string', 'boolean') DEFAULT 'string',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Inserir configurações padrão
INSERT INTO configuracoes_sistema (chave, valor, descricao, tipo) VALUES
('meta_minima_estrelas', '4', 'Meta mínima de estrelas para aprovação', 'int'),
('peso_8_perguntas_1_estrela', '0.125', 'Peso para 1 estrela (8 perguntas)', 'decimal'),
('peso_8_perguntas_2_estrelas', '0.25', 'Peso para 2 estrelas (8 perguntas)', 'decimal'),
('peso_8_perguntas_3_estrelas', '0.375', 'Peso para 3 estrelas (8 perguntas)', 'decimal'),
('peso_8_perguntas_4_estrelas', '0.5', 'Peso para 4 estrelas (8 perguntas)', 'decimal'),
('peso_8_perguntas_5_estrelas', '0.625', 'Peso para 5 estrelas (8 perguntas)', 'decimal'),
('peso_6_perguntas_1_estrela', '0.167', 'Peso para 1 estrela (6 perguntas)', 'decimal'),
('peso_6_perguntas_2_estrelas', '0.333', 'Peso para 2 estrelas (6 perguntas)', 'decimal'),
('peso_6_perguntas_3_estrelas', '0.500', 'Peso para 3 estrelas (6 perguntas)', 'decimal'),
('peso_6_perguntas_4_estrelas', '0.667', 'Peso para 4 estrelas (6 perguntas)', 'decimal'),
('peso_6_perguntas_5_estrelas', '0.833', 'Peso para 5 estrelas (6 perguntas)', 'decimal');
```

---

## 🏗️ FASE 2: ESTRUTURA MVC

### 2.1. Estrutura de Diretórios

```
/app
  /controllers
    ChecklistController.php
    RelatorioController.php
    LojaController.php
    ColaboradorController.php
    ModuloController.php
    PerguntaController.php
    ConfiguracaoController.php
  
  /models
    Checklist.php
    RespostaChecklist.php
    Loja.php
    Colaborador.php
    Cargo.php
    ModuloAvaliacao.php
    Pergunta.php
    FotoChecklist.php
    Configuracao.php
  
  /views
    /checklist
      index.php
      novo.php
      editar.php
      visualizar.php
      listar.php
    /relatorio
      dashboard.php
      comparativo.php
      detalhado.php
      setor.php
      exportar.php
    /loja
      index.php
      cadastrar.php
      editar.php
    /configuracao
      sistema.php
      modulos.php
      perguntas.php
  
  /helpers
    PontuacaoHelper.php
    RelatorioHelper.php
    ValidacaoHelper.php
    DateHelper.php
  
  /services
    ChecklistService.php
    RelatorioService.php
    ExportacaoService.php
    NotificacaoService.php

/public
  /css
    checklist.css
    relatorio.css
  /js
    checklist.js
    relatorio.js
    charts.js
  /uploads
    /fotos_checklist
```

---

## 💻 FASE 3: IMPLEMENTAÇÃO DOS MODELS

### 3.1. Model: Checklist.php

```php
<?php
namespace App\Models;

class Checklist extends Model {
    
    protected $table = 'checklists';
    
    protected $fillable = [
        'loja_id',
        'colaborador_id',
        'data_avaliacao',
        'modulo_id',
        'pontuacao_total',
        'pontuacao_maxima',
        'percentual',
        'atingiu_meta',
        'observacoes_gerais',
        'status'
    ];
    
    /**
     * Cria um novo checklist
     */
    public function criar($dados) {
        $dados['data_avaliacao'] = date('Y-m-d');
        $dados['status'] = 'rascunho';
        return $this->insert($dados);
    }
    
    /**
     * Calcula a pontuação total do checklist
     */
    public function calcularPontuacao($checklistId) {
        $respostas = $this->db->query(
            "SELECT SUM(pontuacao) as total 
             FROM respostas_checklist 
             WHERE checklist_id = ?",
            [$checklistId]
        )->fetch();
        
        $total = $respostas['total'] ?? 0;
        
        // Buscar pontuação máxima do módulo
        $checklist = $this->find($checklistId);
        $modulo = $this->db->query(
            "SELECT total_perguntas, peso_por_pergunta 
             FROM modulos_avaliacao 
             WHERE id = ?",
            [$checklist['modulo_id']]
        )->fetch();
        
        $pontuacaoMaxima = $modulo['total_perguntas'] * $modulo['peso_por_pergunta'];
        $percentual = ($total / $pontuacaoMaxima) * 100;
        
        // Verificar se atingiu meta (4 de 5 = 80%)
        $metaMinima = $this->getConfiguracao('meta_minima_estrelas');
        $atingiuMeta = $percentual >= (($metaMinima / 5) * 100);
        
        // Atualizar checklist
        $this->update($checklistId, [
            'pontuacao_total' => $total,
            'pontuacao_maxima' => $pontuacaoMaxima,
            'percentual' => round($percentual, 2),
            'atingiu_meta' => $atingiuMeta
        ]);
        
        return [
            'total' => $total,
            'maximo' => $pontuacaoMaxima,
            'percentual' => $percentual,
            'atingiu_meta' => $atingiuMeta
        ];
    }
    
    /**
     * Finaliza o checklist
     */
    public function finalizar($checklistId) {
        $this->calcularPontuacao($checklistId);
        return $this->update($checklistId, ['status' => 'finalizado']);
    }
    
    /**
     * Lista checklists com filtros
     */
    public function listarComFiltros($filtros = []) {
        $sql = "SELECT 
                    c.*,
                    l.nome as loja_nome,
                    col.nome as colaborador_nome,
                    m.nome as modulo_nome
                FROM checklists c
                INNER JOIN lojas l ON c.loja_id = l.id
                INNER JOIN colaboradores col ON c.colaborador_id = col.id
                INNER JOIN modulos_avaliacao m ON c.modulo_id = m.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filtros['loja_id'])) {
            $sql .= " AND c.loja_id = ?";
            $params[] = $filtros['loja_id'];
        }
        
        if (!empty($filtros['data_inicio'])) {
            $sql .= " AND c.data_avaliacao >= ?";
            $params[] = $filtros['data_inicio'];
        }
        
        if (!empty($filtros['data_fim'])) {
            $sql .= " AND c.data_avaliacao <= ?";
            $params[] = $filtros['data_fim'];
        }
        
        if (!empty($filtros['status'])) {
            $sql .= " AND c.status = ?";
            $params[] = $filtros['status'];
        }
        
        $sql .= " ORDER BY c.data_avaliacao DESC, c.created_at DESC";
        
        return $this->db->query($sql, $params)->fetchAll();
    }
    
    /**
     * Obtém estatísticas gerais
     */
    public function obterEstatisticas($filtros = []) {
        $where = "WHERE c.status = 'finalizado'";
        $params = [];
        
        if (!empty($filtros['loja_id'])) {
            $where .= " AND c.loja_id = ?";
            $params[] = $filtros['loja_id'];
        }
        
        if (!empty($filtros['data_inicio'])) {
            $where .= " AND c.data_avaliacao >= ?";
            $params[] = $filtros['data_inicio'];
        }
        
        if (!empty($filtros['data_fim'])) {
            $where .= " AND c.data_avaliacao <= ?";
            $params[] = $filtros['data_fim'];
        }
        
        $sql = "SELECT 
                    COUNT(*) as total_checklists,
                    AVG(percentual) as media_percentual,
                    SUM(CASE WHEN atingiu_meta = 1 THEN 1 ELSE 0 END) as total_aprovados,
                    SUM(CASE WHEN atingiu_meta = 0 THEN 1 ELSE 0 END) as total_reprovados
                FROM checklists c
                {$where}";
        
        return $this->db->query($sql, $params)->fetch();
    }
    
    /**
     * Relacionamento com respostas
     */
    public function respostas() {
        return $this->hasMany('RespostaChecklist', 'checklist_id');
    }
    
    /**
     * Relacionamento com loja
     */
    public function loja() {
        return $this->belongsTo('Loja', 'loja_id');
    }
    
    /**
     * Relacionamento com colaborador
     */
    public function colaborador() {
        return $this->belongsTo('Colaborador', 'colaborador_id');
    }
    
    private function getConfiguracao($chave) {
        $config = $this->db->query(
            "SELECT valor FROM configuracoes_sistema WHERE chave = ?",
            [$chave]
        )->fetch();
        return $config['valor'] ?? 4;
    }
}
```

### 3.2. Model: RespostaChecklist.php

```php
<?php
namespace App\Models;

class RespostaChecklist extends Model {
    
    protected $table = 'respostas_checklist';
    
    protected $fillable = [
        'checklist_id',
        'pergunta_id',
        'estrelas',
        'pontuacao',
        'observacao'
    ];
    
    /**
     * Salva resposta e calcula pontuação automaticamente
     */
    public function salvarResposta($dados) {
        // Buscar informações do módulo para calcular peso correto
        $pergunta = $this->db->query(
            "SELECT p.*, m.total_perguntas 
             FROM perguntas p
             INNER JOIN modulos_avaliacao m ON p.modulo_id = m.id
             WHERE p.id = ?",
            [$dados['pergunta_id']]
        )->fetch();
        
        $totalPerguntas = $pergunta['total_perguntas'];
        $estrelas = $dados['estrelas'];
        
        // Calcular pontuação baseado no número de perguntas do módulo
        $pontuacao = $this->calcularPontuacao($estrelas, $totalPerguntas);
        
        $dados['pontuacao'] = $pontuacao;
        
        // Verificar se já existe resposta
        $existente = $this->db->query(
            "SELECT id FROM respostas_checklist 
             WHERE checklist_id = ? AND pergunta_id = ?",
            [$dados['checklist_id'], $dados['pergunta_id']]
        )->fetch();
        
        if ($existente) {
            // Atualizar resposta existente
            return $this->update($existente['id'], $dados);
        } else {
            // Criar nova resposta
            return $this->insert($dados);
        }
    }
    
    /**
     * Calcula pontuação baseado no número de estrelas e total de perguntas
     */
    private function calcularPontuacao($estrelas, $totalPerguntas) {
        $pesosKey = "peso_{$totalPerguntas}_perguntas_{$estrelas}_estrela" . ($estrelas > 1 ? 's' : '');
        
        $config = $this->db->query(
            "SELECT valor FROM configuracoes_sistema WHERE chave = ?",
            [$pesosKey]
        )->fetch();
        
        if ($config) {
            return (float) $config['valor'];
        }
        
        // Fallback: cálculo proporcional
        $pontuacaoMaxima = 5 / $totalPerguntas;
        return ($estrelas / 5) * $pontuacaoMaxima;
    }
    
    /**
     * Obtém respostas de um checklist com informações das perguntas
     */
    public function obterRespostasCompletas($checklistId) {
        $sql = "SELECT 
                    r.*,
                    p.texto as pergunta_texto,
                    p.descricao as pergunta_descricao,
                    m.nome as modulo_nome
                FROM respostas_checklist r
                INNER JOIN perguntas p ON r.pergunta_id = p.id
                INNER JOIN modulos_avaliacao m ON p.modulo_id = m.id
                WHERE r.checklist_id = ?
                ORDER BY p.ordem";
        
        return $this->db->query($sql, [$checklistId])->fetchAll();
    }
    
    /**
     * Adiciona foto à resposta
     */
    public function adicionarFoto($respostaId, $caminhoFoto, $legenda = null) {
        return $this->db->query(
            "INSERT INTO fotos_checklist (resposta_id, caminho, legenda) VALUES (?, ?, ?)",
            [$respostaId, $caminhoFoto, $legenda]
        );
    }
    
    /**
     * Relacionamento com fotos
     */
    public function fotos() {
        return $this->hasMany('FotoChecklist', 'resposta_id');
    }
}
```

### 3.3. Helper: PontuacaoHelper.php

```php
<?php
namespace App\Helpers;

class PontuacaoHelper {
    
    /**
     * Tabela de pesos para 8 perguntas
     */
    const PESOS_8_PERGUNTAS = [
        1 => 0.125,
        2 => 0.25,
        3 => 0.375,
        4 => 0.5,
        5 => 0.625
    ];
    
    /**
     * Tabela de pesos para 6 perguntas
     */
    const PESOS_6_PERGUNTAS = [
        1 => 0.167,
        2 => 0.333,
        3 => 0.500,
        4 => 0.667,
        5 => 0.833
    ];
    
    /**
     * Obtém o peso baseado no número de estrelas e total de perguntas
     */
    public static function obterPeso($estrelas, $totalPerguntas) {
        if ($totalPerguntas == 8) {
            return self::PESOS_8_PERGUNTAS[$estrelas] ?? 0;
        } elseif ($totalPerguntas == 6) {
            return self::PESOS_6_PERGUNTAS[$estrelas] ?? 0;
        }
        
        // Cálculo genérico para outros números de perguntas
        $pontuacaoMaxima = 5 / $totalPerguntas;
        return ($estrelas / 5) * $pontuacaoMaxima;
    }
    
    /**
     * Converte pontuação para estrelas
     */
    public static function pontuacaoParaEstrelas($pontuacao, $pontuacaoMaxima = 5) {
        return round(($pontuacao / $pontuacaoMaxima) * 5, 1);
    }
    
    /**
     * Converte percentual para classificação
     */
    public static function obterClassificacao($percentual) {
        if ($percentual >= 80) {
            return ['texto' => 'Excelente', 'classe' => 'success', 'icone' => '⭐⭐⭐⭐⭐'];
        } elseif ($percentual >= 60) {
            return ['texto' => 'Bom', 'classe' => 'primary', 'icone' => '⭐⭐⭐⭐'];
        } elseif ($percentual >= 40) {
            return ['texto' => 'Regular', 'classe' => 'warning', 'icone' => '⭐⭐⭐'];
        } elseif ($percentual >= 20) {
            return ['texto' => 'Ruim', 'classe' => 'danger', 'icone' => '⭐⭐'];
        } else {
            return ['texto' => 'Muito Ruim', 'classe' => 'dark', 'icone' => '⭐'];
        }
    }
    
    /**
     * Verifica se atingiu a meta
     */
    public static function atingiuMeta($percentual, $metaMinima = 80) {
        return $percentual >= $metaMinima;
    }
}
```

---

## 🎮 FASE 4: IMPLEMENTAÇÃO DOS CONTROLLERS

### 4.1. Controller: ChecklistController.php

```php
<?php
namespace App\Controllers;

use App\Models\Checklist;
use App\Models\RespostaChecklist;
use App\Models\ModuloAvaliacao;
use App\Models\Pergunta;
use App\Helpers\PontuacaoHelper;

class ChecklistController extends Controller {
    
    private $checklistModel;
    private $respostaModel;
    private $moduloModel;
    private $perguntaModel;
    
    public function __construct() {
        parent::__construct();
        $this->checklistModel = new Checklist();
        $this->respostaModel = new RespostaChecklist();
        $this->moduloModel = new ModuloAvaliacao();
        $this->perguntaModel = new Pergunta();
    }
    
    /**
     * Lista todos os checklists
     */
    public function index() {
        $filtros = [
            'loja_id' => $_GET['loja_id'] ?? null,
            'data_inicio' => $_GET['data_inicio'] ?? null,
            'data_fim' => $_GET['data_fim'] ?? null,
            'status' => $_GET['status'] ?? null
        ];
        
        $checklists = $this->checklistModel->listarComFiltros($filtros);
        $estatisticas = $this->checklistModel->obterEstatisticas($filtros);
        
        $this->view('checklist/index', [
            'checklists' => $checklists,
            'estatisticas' => $estatisticas,
            'filtros' => $filtros
        ]);
    }
    
    /**
     * Exibe formulário para novo checklist
     */
    public function novo() {
        $lojas = $this->db->query("SELECT * FROM lojas WHERE ativo = 1 ORDER BY nome")->fetchAll();
        $modulos = $this->moduloModel->findAll(['ativo' => 1], ['ordem' => 'ASC']);
        
        $this->view('checklist/novo', [
            'lojas' => $lojas,
            'modulos' => $modulos
        ]);
    }
    
    /**
     * Cria um novo checklist
     */
    public function criar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->redirect('/checklist/novo');
        }
        
        try {
            $dados = [
                'loja_id' => $_POST['loja_id'],
                'colaborador_id' => $_SESSION['user_id'],
                'modulo_id' => $_POST['modulo_id'],
                'data_avaliacao' => $_POST['data_avaliacao'] ?? date('Y-m-d'),
                'observacoes_gerais' => $_POST['observacoes_gerais'] ?? null
            ];
            
            $checklistId = $this->checklistModel->criar($dados);
            
            $this->setFlash('success', 'Checklist criado com sucesso!');
            return $this->redirect("/checklist/editar/{$checklistId}");
            
        } catch (\Exception $e) {
            $this->setFlash('error', 'Erro ao criar checklist: ' . $e->getMessage());
            return $this->redirect('/checklist/novo');
        }
    }
    
    /**
     * Edita um checklist existente
     */
    public function editar($id) {
        $checklist = $this->checklistModel->find($id);
        
        if (!$checklist) {
            $this->setFlash('error', 'Checklist não encontrado');
            return $this->redirect('/checklist');
        }
        
        // Buscar perguntas do módulo
        $perguntas = $this->perguntaModel->findAll(
            ['modulo_id' => $checklist['modulo_id'], 'ativo' => 1],
            ['ordem' => 'ASC']
        );
        
        // Buscar respostas já existentes
        $respostas = $this->respostaModel->obterRespostasCompletas($id);
        $respostasIndexadas = [];
        foreach ($respostas as $resposta) {
            $respostasIndexadas[$resposta['pergunta_id']] = $resposta;
        }
        
        $this->view('checklist/editar', [
            'checklist' => $checklist,
            'perguntas' => $perguntas,
            'respostas' => $respostasIndexadas
        ]);
    }
    
    /**
     * Salva resposta de uma pergunta via AJAX
     */
    public function salvarResposta() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->jsonResponse(['success' => false, 'message' => 'Método inválido'], 405);
        }
        
        try {
            $dados = [
                'checklist_id' => $_POST['checklist_id'],
                'pergunta_id' => $_POST['pergunta_id'],
                'estrelas' => (int) $_POST['estrelas'],
                'observacao' => $_POST['observacao'] ?? null
            ];
            
            // Validar estrelas (1-5)
            if ($dados['estrelas'] < 1 || $dados['estrelas'] > 5) {
                throw new \Exception('Número de estrelas inválido');
            }
            
            $respostaId = $this->respostaModel->salvarResposta($dados);
            
            // Upload de foto (se houver)
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $caminhoFoto = $this->uploadFoto($_FILES['foto']);
                $this->respostaModel->adicionarFoto($respostaId, $caminhoFoto, $_POST['legenda_foto'] ?? null);
            }
            
            // Recalcular pontuação total do checklist
            $pontuacao = $this->checklistModel->calcularPontuacao($dados['checklist_id']);
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Resposta salva com sucesso',
                'pontuacao' => $pontuacao
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Erro ao salvar resposta: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Finaliza o checklist
     */
    public function finalizar($id) {
        try {
            // Verificar se todas as perguntas obrigatórias foram respondidas
            $checklist = $this->checklistModel->find($id);
            $totalPerguntas = $this->db->query(
                "SELECT COUNT(*) as total FROM perguntas 
                 WHERE modulo_id = ? AND obrigatoria = 1 AND ativo = 1",
                [$checklist['modulo_id']]
            )->fetch()['total'];
            
            $totalRespostas = $this->db->query(
                "SELECT COUNT(*) as total FROM respostas_checklist WHERE checklist_id = ?",
                [$id]
            )->fetch()['total'];
            
            if ($totalRespostas < $totalPerguntas) {
                $this->setFlash('warning', 'Por favor, responda todas as perguntas obrigatórias antes de finalizar');
                return $this->redirect("/checklist/editar/{$id}");
            }
            
            $this->checklistModel->finalizar($id);
            
            $this->setFlash('success', 'Checklist finalizado com sucesso!');
            return $this->redirect("/checklist/visualizar/{$id}");
            
        } catch (\Exception $e) {
            $this->setFlash('error', 'Erro ao finalizar checklist: ' . $e->getMessage());
            return $this->redirect("/checklist/editar/{$id}");
        }
    }
    
    /**
     * Visualiza checklist finalizado
     */
    public function visualizar($id) {
        $checklist = $this->checklistModel->find($id);
        
        if (!$checklist) {
            $this->setFlash('error', 'Checklist não encontrado');
            return $this->redirect('/checklist');
        }
        
        $respostas = $this->respostaModel->obterRespostasCompletas($id);
        $classificacao = PontuacaoHelper::obterClassificacao($checklist['percentual']);
        
        $this->view('checklist/visualizar', [
            'checklist' => $checklist,
            'respostas' => $respostas,
            'classificacao' => $classificacao
        ]);
    }
    
    /**
     * Upload de foto
     */
    private function uploadFoto($arquivo) {
        $uploadDir = PUBLIC_PATH . '/uploads/fotos_checklist/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $extensao = pathinfo($arquivo['name'], PATHINFO_EXTENSION);
        $nomeArquivo = uniqid() . '_' . time() . '.' . $extensao;
        $caminhoCompleto = $uploadDir . $nomeArquivo;
        
        if (!move_uploaded_file($arquivo['tmp_name'], $caminhoCompleto)) {
            throw new \Exception('Erro ao fazer upload da foto');
        }
        
        return '/uploads/fotos_checklist/' . $nomeArquivo;
    }
}
```

---

## 📈 FASE 5: SISTEMA DE RELATÓRIOS

### 5.1. Controller: RelatorioController.php

```php
<?php
namespace App\Controllers;

use App\Models\Checklist;
use App\Helpers\PontuacaoHelper;
use App\Services\RelatorioService;

class RelatorioController extends Controller {
    
    private $checklistModel;
    private $relatorioService;
    
    public function __construct() {
        parent::__construct();
        $this->checklistModel = new Checklist();
        $this->relatorioService = new RelatorioService();
    }
    
    /**
     * Dashboard principal de relatórios
     */
    public function dashboard() {
        $filtros = [
            'loja_id' => $_GET['loja_id'] ?? null,
            'data_inicio' => $_GET['data_inicio'] ?? date('Y-m-d', strtotime('-30 days')),
            'data_fim' => $_GET['data_fim'] ?? date('Y-m-d')
        ];
        
        $dados = [
            'estatisticas_gerais' => $this->relatorioService->obterEstatisticasGerais($filtros),
            'ranking_lojas' => $this->relatorioService->obterRankingLojas($filtros),
            'evolucao_temporal' => $this->relatorioService->obterEvolucaoTemporal($filtros),
            'distribuicao_notas' => $this->relatorioService->obterDistribuicaoNotas($filtros),
            'desempenho_setores' => $this->relatorioService->obterDesempenhoSetores($filtros),
            'filtros' => $filtros
        ];
        
        $this->view('relatorio/dashboard', $dados);
    }
    
    /**
     * Relatório comparativo entre lojas
     */
    public function comparativo() {
        $filtros = [
            'lojas_ids' => $_GET['lojas'] ?? [],
            'data_inicio' => $_GET['data_inicio'] ?? date('Y-m-d', strtotime('-30 days')),
            'data_fim' => $_GET['data_fim'] ?? date('Y-m-d')
        ];
        
        $dados = [
            'comparacao' => $this->relatorioService->compararLojas($filtros),
            'filtros' => $filtros
        ];
        
        $this->view('relatorio/comparativo', $dados);
    }
    
    /**
     * Relatório detalhado por setor
     */
    public function setor($moduloId) {
        $filtros = [
            'modulo_id' => $moduloId,
            'loja_id' => $_GET['loja_id'] ?? null,
            'data_inicio' => $_GET['data_inicio'] ?? date('Y-m-d', strtotime('-30 days')),
            'data_fim' => $_GET['data_fim'] ?? date('Y-m-d')
        ];
        
        $dados = [
            'modulo' => $this->db->query("SELECT * FROM modulos_avaliacao WHERE id = ?", [$moduloId])->fetch(),
            'analise_perguntas' => $this->relatorioService->analisarPerguntasSetor($filtros),
            'evolucao' => $this->relatorioService->obterEvolucaoSetor($filtros),
            'filtros' => $filtros
        ];
        
        $this->view('relatorio/setor', $dados);
    }
    
    /**
     * Exporta relatório em PDF
     */
    public function exportarPdf($tipo) {
        // Implementar exportação PDF
    }
    
    /**
     * Exporta relatório em Excel
     */
    public function exportarExcel($tipo) {
        // Implementar exportação Excel
    }
    
    /**
     * API para gráficos (retorna JSON)
     */
    public function dadosGrafico() {
        $tipo = $_GET['tipo'] ?? null;
        $filtros = $_GET['filtros'] ?? [];
        
        switch ($tipo) {
            case 'evolucao':
                $dados = $this->relatorioService->obterEvolucaoTemporal($filtros);
                break;
            case 'distribuicao':
                $dados = $this->relatorioService->obterDistribuicaoNotas($filtros);
                break;
            case 'ranking':
                $dados = $this->relatorioService->obterRankingLojas($filtros);
                break;
            default:
                $dados = [];
        }
        
        return $this->jsonResponse($dados);
    }
}
```

### 5.2. Service: RelatorioService.php

```php
<?php
namespace App\Services;

class RelatorioService {
    
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obtém estatísticas gerais
     */
    public function obterEstatisticasGerais($filtros) {
        $where = "WHERE status = 'finalizado'";
        $params = [];
        
        if (!empty($filtros['loja_id'])) {
            $where .= " AND loja_id = ?";
            $params[] = $filtros['loja_id'];
        }
        
        if (!empty($filtros['data_inicio'])) {
            $where .= " AND data_avaliacao >= ?";
            $params[] = $filtros['data_inicio'];
        }
        
        if (!empty($filtros['data_fim'])) {
            $where .= " AND data_avaliacao <= ?";
            $params[] = $filtros['data_fim'];
        }
        
        $sql = "SELECT 
                    COUNT(*) as total_checklists,
                    AVG(percentual) as media_percentual,
                    AVG(pontuacao_total) as media_pontuacao,
                    SUM(CASE WHEN atingiu_meta = 1 THEN 1 ELSE 0 END) as total_aprovados,
                    SUM(CASE WHEN atingiu_meta = 0 THEN 1 ELSE 0 END) as total_reprovados,
                    COUNT(DISTINCT loja_id) as total_lojas,
                    COUNT(DISTINCT colaborador_id) as total_avaliadores
                FROM checklists
                {$where}";
        
        $stats = $this->db->query($sql, $params)->fetch();
        
        // Adicionar taxa de aprovação
        $stats['taxa_aprovacao'] = $stats['total_checklists'] > 0 
            ? round(($stats['total_aprovados'] / $stats['total_checklists']) * 100, 2)
            : 0;
        
        return $stats;
    }
    
    /**
     * Obtém ranking de lojas
     */
    public function obterRankingLojas($filtros) {
        $where = "WHERE c.status = 'finalizado'";
        $params = [];
        
        if (!empty($filtros['data_inicio'])) {
            $where .= " AND c.data_avaliacao >= ?";
            $params[] = $filtros['data_inicio'];
        }
        
        if (!empty($filtros['data_fim'])) {
            $where .= " AND c.data_avaliacao <= ?";
            $params[] = $filtros['data_fim'];
        }
        
        $sql = "SELECT 
                    l.id,
                    l.nome,
                    l.cidade,
                    COUNT(c.id) as total_avaliacoes,
                    AVG(c.percentual) as media_percentual,
                    AVG(c.pontuacao_total) as media_pontuacao,
                    SUM(CASE WHEN c.atingiu_meta = 1 THEN 1 ELSE 0 END) as total_aprovados,
                    MAX(c.percentual) as melhor_nota,
                    MIN(c.percentual) as pior_nota
                FROM lojas l
                LEFT JOIN checklists c ON l.id = c.loja_id
                {$where}
                GROUP BY l.id, l.nome, l.cidade
                HAVING total_avaliacoes > 0
                ORDER BY media_percentual DESC";
        
        return $this->db->query($sql, $params)->fetchAll();
    }
    
    /**
     * Obtém evolução temporal (gráfico de linha)
     */
    public function obterEvolucaoTemporal($filtros) {
        $where = "WHERE status = 'finalizado'";
        $params = [];
        
        if (!empty($filtros['loja_id'])) {
            $where .= " AND loja_id = ?";
            $params[] = $filtros['loja_id'];
        }
        
        if (!empty($filtros['data_inicio'])) {
            $where .= " AND data_avaliacao >= ?";
            $params[] = $filtros['data_inicio'];
        }
        
        if (!empty($filtros['data_fim'])) {
            $where .= " AND data_avaliacao <= ?";
            $params[] = $filtros['data_fim'];
        }
        
        $sql = "SELECT 
                    data_avaliacao as data,
                    AVG(percentual) as media_percentual,
                    COUNT(*) as total_avaliacoes
                FROM checklists
                {$where}
                GROUP BY data_avaliacao
                ORDER BY data_avaliacao ASC";
        
        return $this->db->query($sql, $params)->fetchAll();
    }
    
    /**
     * Obtém distribuição de notas (gráfico de pizza/rosca)
     */
    public function obterDistribuicaoNotas($filtros) {
        $where = "WHERE status = 'finalizado'";
        $params = [];
        
        if (!empty($filtros['loja_id'])) {
            $where .= " AND loja_id = ?";
            $params[] = $filtros['loja_id'];
        }
        
        if (!empty($filtros['data_inicio'])) {
            $where .= " AND data_avaliacao >= ?";
            $params[] = $filtros['data_inicio'];
        }
        
        if (!empty($filtros['data_fim'])) {
            $where .= " AND data_avaliacao <= ?";
            $params[] = $filtros['data_fim'];
        }
        
        $sql = "SELECT 
                    CASE 
                        WHEN percentual >= 80 THEN 'Excelente'
                        WHEN percentual >= 60 THEN 'Bom'
                        WHEN percentual >= 40 THEN 'Regular'
                        WHEN percentual >= 20 THEN 'Ruim'
                        ELSE 'Muito Ruim'
                    END as categoria,
                    COUNT(*) as total,
                    AVG(percentual) as media
                FROM checklists
                {$where}
                GROUP BY categoria
                ORDER BY 
                    CASE categoria
                        WHEN 'Excelente' THEN 1
                        WHEN 'Bom' THEN 2
                        WHEN 'Regular' THEN 3
                        WHEN 'Ruim' THEN 4
                        WHEN 'Muito Ruim' THEN 5
                    END";
        
        return $this->db->query($sql, $params)->fetchAll();
    }
    
    /**
     * Obtém desempenho por setor/módulo
     */
    public function obterDesempenhoSetores($filtros) {
        $where = "WHERE c.status = 'finalizado'";
        $params = [];
        
        if (!empty($filtros['loja_id'])) {
            $where .= " AND c.loja_id = ?";
            $params[] = $filtros['loja_id'];
        }
        
        if (!empty($filtros['data_inicio'])) {
            $where .= " AND c.data_avaliacao >= ?";
            $params[] = $filtros['data_inicio'];
        }
        
        if (!empty($filtros['data_fim'])) {
            $where .= " AND c.data_avaliacao <= ?";
            $params[] = $filtros['data_fim'];
        }
        
        $sql = "SELECT 
                    m.id,
                    m.nome as setor,
                    COUNT(c.id) as total_avaliacoes,
                    AVG(c.percentual) as media_percentual,
                    AVG(c.pontuacao_total) as media_pontuacao,
                    SUM(CASE WHEN c.atingiu_meta = 1 THEN 1 ELSE 0 END) as total_aprovados
                FROM modulos_avaliacao m
                LEFT JOIN checklists c ON m.id = c.modulo_id
                {$where}
                GROUP BY m.id, m.nome
                HAVING total_avaliacoes > 0
                ORDER BY media_percentual DESC";
        
        return $this->db->query($sql, $params)->fetchAll();
    }
    
    /**
     * Analisa perguntas de um setor específico
     */
    public function analisarPerguntasSetor($filtros) {
        $where = "WHERE c.status = 'finalizado' AND p.modulo_id = ?";
        $params = [$filtros['modulo_id']];
        
        if (!empty($filtros['loja_id'])) {
            $where .= " AND c.loja_id = ?";
            $params[] = $filtros['loja_id'];
        }
        
        if (!empty($filtros['data_inicio'])) {
            $where .= " AND c.data_avaliacao >= ?";
            $params[] = $filtros['data_inicio'];
        }
        
        if (!empty($filtros['data_fim'])) {
            $where .= " AND c.data_avaliacao <= ?";
            $params[] = $filtros['data_fim'];
        }
        
        $sql = "SELECT 
                    p.id,
                    p.texto as pergunta,
                    p.ordem,
                    COUNT(r.id) as total_respostas,
                    AVG(r.estrelas) as media_estrelas,
                    AVG(r.pontuacao) as media_pontuacao,
                    SUM(CASE WHEN r.estrelas = 5 THEN 1 ELSE 0 END) as total_5_estrelas,
                    SUM(CASE WHEN r.estrelas = 4 THEN 1 ELSE 0 END) as total_4_estrelas,
                    SUM(CASE WHEN r.estrelas = 3 THEN 1 ELSE 0 END) as total_3_estrelas,
                    SUM(CASE WHEN r.estrelas = 2 THEN 1 ELSE 0 END) as total_2_estrelas,
                    SUM(CASE WHEN r.estrelas = 1 THEN 1 ELSE 0 END) as total_1_estrela
                FROM perguntas p
                LEFT JOIN respostas_checklist r ON p.id = r.pergunta_id
                LEFT JOIN checklists c ON r.checklist_id = c.id
                {$where}
                GROUP BY p.id, p.texto, p.ordem
                HAVING total_respostas > 0
                ORDER BY p.ordem";
        
        return $this->db->query($sql, $params)->fetchAll();
    }
    
    /**
     * Compara múltiplas lojas
     */
    public function compararLojas($filtros) {
        if (empty($filtros['lojas_ids'])) {
            return [];
        }
        
        $placeholders = implode(',', array_fill(0, count($filtros['lojas_ids']), '?'));
        $params = $filtros['lojas_ids'];
        
        $where = "WHERE c.status = 'finalizado' AND l.id IN ({$placeholders})";
        
        if (!empty($filtros['data_inicio'])) {
            $where .= " AND c.data_avaliacao >= ?";
            $params[] = $filtros['data_inicio'];
        }
        
        if (!empty($filtros['data_fim'])) {
            $where .= " AND c.data_avaliacao <= ?";
            $params[] = $filtros['data_fim'];
        }
        
        $sql = "SELECT 
                    l.id,
                    l.nome as loja,
                    m.nome as setor,
                    COUNT(c.id) as total_avaliacoes,
                    AVG(c.percentual) as media_percentual,
                    AVG(c.pontuacao_total) as media_pontuacao
                FROM lojas l
                LEFT JOIN checklists c ON l.id = c.loja_id
                LEFT JOIN modulos_avaliacao m ON c.modulo_id = m.id
                {$where}
                GROUP BY l.id, l.nome, m.id, m.nome
                ORDER BY l.nome, m.nome";
        
        return $this->db->query($sql, $params)->fetchAll();
    }
}
```

---

## 🎨 FASE 6: VIEWS E INTERFACE

### 6.1. View: Dashboard de Relatórios

```php
<!-- views/relatorio/dashboard.php -->
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Checklist de Lojas</title>
    <link rel="stylesheet" href="/css/relatorio.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <h1 class="page-title">📊 Dashboard de Relatórios</h1>
                
                <!-- Filtros -->
                <div class="filters-card">
                    <form method="GET" class="filters-form">
                        <div class="row">
                            <div class="col-md-3">
                                <label>Loja</label>
                                <select name="loja_id" class="form-control">
                                    <option value="">Todas as lojas</option>
                                    <?php foreach ($lojas as $loja): ?>
                                        <option value="<?= $loja['id'] ?>" <?= $filtros['loja_id'] == $loja['id'] ? 'selected' : '' ?>>
                                            <?= $loja['nome'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>Data Início</label>
                                <input type="date" name="data_inicio" class="form-control" 
                                       value="<?= $filtros['data_inicio'] ?>">
                            </div>
                            <div class="col-md-3">
                                <label>Data Fim</label>
                                <input type="date" name="data_fim" class="form-control" 
                                       value="<?= $filtros['data_fim'] ?>">
                            </div>
                            <div class="col-md-3">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary btn-block">
                                    🔍 Filtrar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Cards de Estatísticas -->
                <div class="row stats-cards">
                    <div class="col-md-3">
                        <div class="stat-card bg-primary">
                            <div class="stat-icon">📋</div>
                            <div class="stat-value"><?= $estatisticas_gerais['total_checklists'] ?></div>
                            <div class="stat-label">Total de Avaliações</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-success">
                            <div class="stat-icon">⭐</div>
                            <div class="stat-value"><?= number_format($estatisticas_gerais['media_percentual'], 1) ?>%</div>
                            <div class="stat-label">Média Geral</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-info">
                            <div class="stat-icon">✅</div>
                            <div class="stat-value"><?= $estatisticas_gerais['taxa_aprovacao'] ?>%</div>
                            <div class="stat-label">Taxa de Aprovação</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-warning">
                            <div class="stat-icon">🏪</div>
                            <div class="stat-value"><?= $estatisticas_gerais['total_lojas'] ?></div>
                            <div class="stat-label">Lojas Avaliadas</div>
                        </div>
                    </div>
                </div>
                
                <!-- Gráficos -->
                <div class="row mt-4">
                    <!-- Gráfico de Evolução Temporal -->
                    <div class="col-md-8">
                        <div class="chart-card">
                            <h3 class="chart-title">📈 Evolução Temporal</h3>
                            <canvas id="evolucaoChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Gráfico de Distribuição de Notas -->
                    <div class="col-md-4">
                        <div class="chart-card">
                            <h3 class="chart-title">🎯 Distribuição de Notas</h3>
                            <canvas id="distribuicaoChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <!-- Ranking de Lojas -->
                    <div class="col-md-6">
                        <div class="chart-card">
                            <h3 class="chart-title">🏆 Ranking de Lojas</h3>
                            <div class="ranking-list">
                                <?php foreach ($ranking_lojas as $index => $loja): ?>
                                    <div class="ranking-item">
                                        <div class="ranking-position"><?= $index + 1 ?>º</div>
                                        <div class="ranking-info">
                                            <div class="ranking-name"><?= $loja['nome'] ?></div>
                                            <div class="ranking-city"><?= $loja['cidade'] ?></div>
                                        </div>
                                        <div class="ranking-score">
                                            <div class="score-value"><?= number_format($loja['media_percentual'], 1) ?>%</div>
                                            <div class="score-stars">
                                                <?php
                                                $estrelas = round($loja['media_percentual'] / 20);
                                                echo str_repeat('⭐', $estrelas);
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Desempenho por Setor -->
                    <div class="col-md-6">
                        <div class="chart-card">
                            <h3 class="chart-title">📊 Desempenho por Setor</h3>
                            <canvas id="setoresChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Dados para os gráficos
        const evolucaoData = <?= json_encode($evolucao_temporal) ?>;
        const distribuicaoData = <?= json_encode($distribuicao_notas) ?>;
        const setoresData = <?= json_encode($desempenho_setores) ?>;
        
        // Gráfico de Evolução Temporal (Linha)
        const evolucaoChart = new Chart(document.getElementById('evolucaoChart'), {
            type: 'line',
            data: {
                labels: evolucaoData.map(d => d.data),
                datasets: [{
                    label: 'Média Percentual',
                    data: evolucaoData.map(d => d.media_percentual),
                    borderColor: '#4A90E2',
                    backgroundColor: 'rgba(74, 144, 226, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });
        
        // Gráfico de Distribuição (Rosca)
        const distribuicaoChart = new Chart(document.getElementById('distribuicaoChart'), {
            type: 'doughnut',
            data: {
                labels: distribuicaoData.map(d => d.categoria),
                datasets: [{
                    data: distribuicaoData.map(d => d.total),
                    backgroundColor: [
                        '#28a745', // Excelente - Verde
                        '#007bff', // Bom - Azul
                        '#ffc107', // Regular - Amarelo
                        '#fd7e14', // Ruim - Laranja
                        '#dc3545'  // Muito Ruim - Vermelho
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        
        // Gráfico de Setores (Barra Horizontal)
        const setoresChart = new Chart(document.getElementById('setoresChart'), {
            type: 'bar',
            data: {
                labels: setoresData.map(d => d.setor),
                datasets: [{
                    label: 'Média Percentual',
                    data: setoresData.map(d => d.media_percentual),
                    backgroundColor: '#6f42c1'
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
```

---

## ⚙️ FASE 7: CONFIGURAÇÕES E SEEDS

### 7.1. Script de Seed para Módulos e Perguntas

```sql
-- seed_modulos_perguntas.sql

-- Módulo 1: Organização de Lojas (8 perguntas)
INSERT INTO modulos_avaliacao (nome, descricao, total_perguntas, peso_por_pergunta, ordem) VALUES
('Organização de Lojas', 'Avaliação da limpeza, organização e disposição geral da loja', 8, 0.625, 1);

SET @modulo1_id = LAST_INSERT_ID();

INSERT INTO perguntas (modulo_id, texto, descricao, ordem, obrigatoria, permite_foto) VALUES
(@modulo1_id, 'Os corredores estão limpos e organizados?', 'Verificar se os corredores estão livres de obstáculos e limpos', 1, 1, 1),
(@modulo1_id, 'A sinalização está visível e adequada?', 'Placas indicativas e de preços bem posicionadas', 2, 1, 1),
(@modulo1_id, 'As prateleiras estão bem abastecidas?', 'Verificar se não há produtos faltando nas gôndolas', 3, 1, 1),
(@modulo1_id, 'A iluminação está adequada em toda a loja?', 'Todas as luzes funcionando e ambiente bem iluminado', 4, 1, 0),
(@modulo1_id, 'O piso está limpo e sem riscos?', 'Verificar condições do piso e segurança', 5, 1, 1),
(@modulo1_id, 'As vitrines e displays estão organizados?', 'Produtos destacados de forma atrativa', 6, 1, 1),
(@modulo1_id, 'Há produtos vencidos ou danificados expostos?', 'Verificar validades e condições dos produtos', 7, 1, 1),
(@modulo1_id, 'A entrada e fachada estão em bom estado?', 'Limpeza e conservação da parte externa', 8, 1, 1);

-- Módulo 2: Caixas (6 perguntas)
INSERT INTO modulos_avaliacao (nome, descricao, total_perguntas, peso_por_pergunta, ordem) VALUES
('Caixas', 'Avaliação do atendimento nos caixas e funcionamento dos equipamentos', 6, 0.833, 2);

SET @modulo2_id = LAST_INSERT_ID();

INSERT INTO perguntas (modulo_id, texto, descricao, ordem, obrigatoria, permite_foto) VALUES
(@modulo2_id, 'Os caixas estão limpos e organizados?', 'Verificar limpeza da área de atendimento', 1, 1, 1),
(@modulo2_id, 'Os operadores estão uniformizados?', 'Uniforme completo e identificação visível', 2, 1, 0),
(@modulo2_id, 'O atendimento está sendo ágil?', 'Tempo de espera adequado nas filas', 3, 1, 0),
(@modulo2_id, 'Os equipamentos estão funcionando?', 'Leitores, impressoras e balanças operacionais', 4, 1, 1),
(@modulo2_id, 'Há sacolas e materiais de embalagem suficientes?', 'Disponibilidade de sacolas plásticas e papel', 5, 1, 0),
(@modulo2_id, 'A área de caixas está sinalizada?', 'Placas de "Aberto" e "Fechado" visíveis', 6, 1, 1);

-- Módulo 3: Setor Ovos (8 perguntas)
INSERT INTO modulos_avaliacao (nome, descricao, total_perguntas, peso_por_pergunta, ordem) VALUES
('Setor Ovos', 'Avaliação específica do setor de ovos e produtos sensíveis', 8, 0.625, 3);

SET @modulo3_id = LAST_INSERT_ID();

INSERT INTO perguntas (modulo_id, texto, descricao, ordem, obrigatoria, permite_foto) VALUES
(@modulo3_id, 'Os ovos estão armazenados na temperatura adequada?', 'Verificar refrigeração se necessário', 1, 1, 1),
(@modulo3_id, 'Todas as embalagens estão íntegras?', 'Sem ovos quebrados ou rachados expostos', 2, 1, 1),
(@modulo3_id, 'As datas de validade estão visíveis?', 'Etiquetas legíveis e atualizadas', 3, 1, 1),
(@modulo3_id, 'A área está limpa e higienizada?', 'Sem sujeira ou resíduos', 4, 1, 1),
(@modulo3_id, 'Os produtos estão organizados por tipo?', 'Separação clara entre tipos e tamanhos', 5, 1, 0),
(@modulo3_id, 'Há produtos vencidos ou próximos do vencimento?', 'Sistema FIFO implementado', 6, 1, 1),
(@modulo3_id, 'A precificação está correta e visível?', 'Etiquetas de preço corretas', 7, 1, 1),
(@modulo3_id, 'O estoque está adequado à demanda?', 'Quantidade suficiente sem excesso', 8, 1, 0);

-- Módulo 4: Gôndolas e Ilhas (8 perguntas)
INSERT INTO modulos_avaliacao (nome, descricao, total_perguntas, peso_por_pergunta, ordem) VALUES
('Gôndolas e Ilhas', 'Avaliação da exposição de produtos nas gôndolas e ilhas promocionais', 8, 0.625, 4);

-- ... (continuar com as perguntas dos demais módulos)

-- Módulo 5: Balcão de Frios
-- Módulo 6: Câmara Fria
-- Módulo 7: Estoque
-- Módulo 8: Áreas Comuns
```

---

## 📱 FASE 8: RECURSOS AVANÇADOS

### 8.1. Sistema de Notificações

```php
<?php
namespace App\Services;

class NotificacaoService {
    
    /**
     * Envia notificação quando checklist não atinge meta
     */
    public function notificarMetaNaoAtingida($checklistId) {
        $checklist = $this->db->query(
            "SELECT c.*, l.nome as loja_nome, col.nome as colaborador_nome, col.email
             FROM checklists c
             INNER JOIN lojas l ON c.loja_id = l.id
             INNER JOIN colaboradores col ON c.colaborador_id = col.id
             WHERE c.id = ?",
            [$checklistId]
        )->fetch();
        
        if (!$checklist['atingiu_meta']) {
            // Enviar e-mail
            $assunto = "⚠️ Meta não atingida - {$checklist['loja_nome']}";
            $mensagem = "O checklist realizado em {$checklist['data_avaliacao']} não atingiu a meta mínima.\n\n";
            $mensagem .= "Pontuação: {$checklist['percentual']}%\n";
            $mensagem .= "Meta: 80%\n\n";
            $mensagem .= "Por favor, verifique os pontos de melhoria e tome as ações necessárias.";
            
            // Implementar envio de e-mail
            $this->enviarEmail($checklist['email'], $assunto, $mensagem);
            
            // Registrar notificação no banco
            $this->db->query(
                "INSERT INTO notificacoes (tipo, referencia_id, destinatario_id, titulo, mensagem, enviado_em) 
                 VALUES ('checklist_meta', ?, ?, ?, ?, NOW())",
                [$checklistId, $checklist['colaborador_id'], $assunto, $mensagem]
            );
        }
    }
    
    /**
     * Lembrete diário para preencher checklist
     */
    public function enviarLembretesDiarios() {
        // Buscar lojas que não preencheram checklist hoje
        $hoje = date('Y-m-d');
        
        $lojasSemChecklist = $this->db->query(
            "SELECT DISTINCT l.id, l.nome, c.email, c.nome as colaborador_nome
             FROM lojas l
             INNER JOIN colaboradores c ON l.id = c.loja_id
             WHERE c.cargo_id IN (SELECT id FROM cargos WHERE nivel_acesso IN ('supervisor', 'gerente'))
             AND l.id NOT IN (
                 SELECT loja_id FROM checklists WHERE data_avaliacao = ?
             )",
            [$hoje]
        )->fetchAll();
        
        foreach ($lojasSemChecklist as $loja) {
            $assunto = "📋 Lembrete: Checklist diário pendente - {$loja['nome']}";
            $mensagem = "Olá {$loja['colaborador_nome']},\n\n";
            $mensagem .= "O checklist diário da loja {$loja['nome']} ainda não foi preenchido hoje.\n";
            $mensagem .= "Por favor, acesse o sistema e realize a avaliação.\n\n";
            $mensagem .= "Link: " . BASE_URL . "/checklist/novo";
            
            $this->enviarEmail($loja['email'], $assunto, $mensagem);
        }
    }
    
    private function enviarEmail($destinatario, $assunto, $mensagem) {
        // Implementar envio real com PHPMailer, SwiftMailer, etc.
        mail($destinatario, $assunto, $mensagem);
    }
}
```

### 8.2. API REST para Integração Mobile

```php
<?php
namespace App\Controllers\Api;

class ChecklistApiController extends ApiController {
    
    /**
     * Lista checklists (GET /api/checklists)
     */
    public function index() {
        $this->requireAuth();
        
        $filtros = [
            'loja_id' => $_GET['loja_id'] ?? null,
            'data_inicio' => $_GET['data_inicio'] ?? null,
            'data_fim' => $_GET['data_fim'] ?? null
        ];
        
        $checklists = $this->checklistModel->listarComFiltros($filtros);
        
        return $this->jsonResponse([
            'success' => true,
            'data' => $checklists
        ]);
    }
    
    /**
     * Detalhes de um checklist (GET /api/checklists/:id)
     */
    public function show($id) {
        $this->requireAuth();
        
        $checklist = $this->checklistModel->find($id);
        
        if (!$checklist) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Checklist não encontrado'
            ], 404);
        }
        
        $respostas = $this->respostaModel->obterRespostasCompletas($id);
        
        return $this->jsonResponse([
            'success' => true,
            'data' => [
                'checklist' => $checklist,
                'respostas' => $respostas
            ]
        ]);
    }
    
    /**
     * Cria novo checklist (POST /api/checklists)
     */
    public function store() {
        $this->requireAuth();
        
        $dados = $this->getJsonInput();
        
        try {
            $checklistId = $this->checklistModel->criar($dados);
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Checklist criado com sucesso',
                'data' => ['id' => $checklistId]
            ], 201);
            
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * Salva resposta (POST /api/checklists/:id/respostas)
     */
    public function salvarResposta($checklistId) {
        $this->requireAuth();
        
        $dados = $this->getJsonInput();
        $dados['checklist_id'] = $checklistId;
        
        try {
            $respostaId = $this->respostaModel->salvarResposta($dados);
            $pontuacao = $this->checklistModel->calcularPontuacao($checklistId);
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Resposta salva com sucesso',
                'data' => [
                    'resposta_id' => $respostaId,
                    'pontuacao' => $pontuacao
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * Finaliza checklist (POST /api/checklists/:id/finalizar)
     */
    public function finalizar($id) {
        $this->requireAuth();
        
        try {
            $this->checklistModel->finalizar($id);
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Checklist finalizado com sucesso'
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
```

---

## 🧪 FASE 9: TESTES

### 9.1. Testes Unitários

```php
<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Helpers\PontuacaoHelper;

class PontuacaoHelperTest extends TestCase {
    
    /**
     * Testa cálculo de peso para 8 perguntas
     */
    public function testPeso8Perguntas() {
        $this->assertEquals(0.125, PontuacaoHelper::obterPeso(1, 8));
        $this->assertEquals(0.25, PontuacaoHelper::obterPeso(2, 8));
        $this->assertEquals(0.375, PontuacaoHelper::obterPeso(3, 8));
        $this->assertEquals(0.5, PontuacaoHelper::obterPeso(4, 8));
        $this->assertEquals(0.625, PontuacaoHelper::obterPeso(5, 8));
    }
    
    /**
     * Testa cálculo de peso para 6 perguntas
     */
    public function testPeso6Perguntas() {
        $this->assertEquals(0.167, PontuacaoHelper::obterPeso(1, 6));
        $this->assertEquals(0.333, PontuacaoHelper::obterPeso(2, 6));
        $this->assertEquals(0.500, PontuacaoHelper::obterPeso(3, 6));
        $this->assertEquals(0.667, PontuacaoHelper::obterPeso(4, 6));
        $this->assertEquals(0.833, PontuacaoHelper::obterPeso(5, 6));
    }
    
    /**
     * Testa conversão de pontuação para estrelas
     */
    public function testPontuacaoParaEstrelas() {
        $this->assertEquals(5.0, PontuacaoHelper::pontuacaoParaEstrelas(5, 5));
        $this->assertEquals(4.0, PontuacaoHelper::pontuacaoParaEstrelas(4, 5));
        $this->assertEquals(3.0, PontuacaoHelper::pontuacaoParaEstrelas(3, 5));
        $this->assertEquals(2.5, PontuacaoHelper::pontuacaoParaEstrelas(2.5, 5));
    }
    
    /**
     * Testa verificação de meta
     */
    public function testAtingiuMeta() {
        $this->assertTrue(PontuacaoHelper::atingiuMeta(85, 80));
        $this->assertTrue(PontuacaoHelper::atingiuMeta(80, 80));
        $this->assertFalse(PontuacaoHelper::atingiuMeta(75, 80));
    }
}
```

---

## 📋 FASE 10: CRONOGRAMA DE IMPLEMENTAÇÃO

### Semana 1-2: Setup e Base
- [ ] Criar estrutura de banco de dados
- [ ] Implementar models básicos
- [ ] Configurar MVC
- [ ] Criar seeds de módulos e perguntas

### Semana 3-4: Formulários
- [ ] Implementar ChecklistController
- [ ] Criar views de formulário
- [ ] Sistema de upload de fotos
- [ ] AJAX para salvamento de respostas

### Semana 5-6: Relatórios
- [ ] Implementar RelatorioController
- [ ] Criar RelatorioService
- [ ] Desenvolver dashboard com gráficos
- [ ] Implementar filtros e comparativos

### Semana 7-8: Recursos Avançados
- [ ] Sistema de notificações
- [ ] API REST
- [ ] Exportação PDF/Excel
- [ ] Testes unitários

### Semana 9-10: Refinamento
- [ ] Otimização de performance
- [ ] Testes de integração
- [ ] Documentação final
- [ ] Deploy e treinamento

---

## ✅ CHECKLIST DE ENTREGA

### Banco de Dados
- [ ] Todas as tabelas criadas
- [ ] Relacionamentos configurados
- [ ] Índices otimizados
- [ ] Seeds de dados iniciais

### Backend
- [ ] Models implementados
- [ ] Controllers funcionais
- [ ] Helpers criados
- [ ] Services desenvolvidos
- [ ] API REST completa

### Frontend
- [ ] Views responsivas
- [ ] Formulários funcionais
- [ ] Gráficos interativos
- [ ] Filtros dinâmicos
- [ ] Upload de imagens

### Testes
- [ ] Testes unitários
- [ ] Testes de integração
- [ ] Testes de performance
- [ ] Testes de usabilidade

### Documentação
- [ ] README completo
- [ ] Documentação de API
- [ ] Manual do usuário
- [ ] Guia de instalação

---

## 🔧 REQUISITOS TÉCNICOS

### Servidor
- PHP 7.4+
- MySQL 5.7+ ou MariaDB 10.3+
- Apache/Nginx
- Extensões: PDO, GD, mbstring

### Frontend
- HTML5
- CSS3 (Bootstrap 4+)
- JavaScript ES6+
- Chart.js para gráficos

### Desenvolvimento
- Composer para dependências
- Git para versionamento
- PHPUnit para testes

---

## 📞 SUPORTE E MANUTENÇÃO

### Monitoramento
- Logs de erros
- Métricas de uso
- Performance do banco

### Backups
- Backup diário automático
- Retenção de 30 dias
- Testes de restauração

### Atualizações
- Versionamento semântico
- Changelog documentado
- Testes antes de deploy

---

**Desenvolvido por:** IDE Digital  
**Data:** Novembro 2025  
**Versão:** 1.0
