<?php
/**
 * Controller: ChecklistController
 * Gerencia operações de checklists de lojas
 */

require_once __DIR__ . '/../models/Checklist.php';
require_once __DIR__ . '/../models/RespostaChecklist.php';
require_once __DIR__ . '/../models/ModuloAvaliacao.php';
require_once __DIR__ . '/../models/Pergunta.php';
require_once __DIR__ . '/../models/Loja.php';

class ChecklistController {
    private $checklistModel;
    private $respostaModel;
    private $moduloModel;
    private $perguntaModel;
    private $lojaModel;

    public function __construct() {
        $this->checklistModel = new Checklist();
        $this->respostaModel = new RespostaChecklist();
        $this->moduloModel = new ModuloAvaliacao();
        $this->perguntaModel = new Pergunta();
        $this->lojaModel = new Loja();
    }

    /**
     * Lista todos os checklists
     */
    public function listar() {
        $filtros = [
            'loja_id' => $_GET['loja_id'] ?? null,
            'data_inicio' => $_GET['data_inicio'] ?? null,
            'data_fim' => $_GET['data_fim'] ?? null,
            'status' => $_GET['status'] ?? null,
            'modulo_id' => $_GET['modulo_id'] ?? null
        ];

        $params = [
            'page' => $_GET['page'] ?? 1,
            'per_page' => ITEMS_PER_PAGE ?? 20
        ];

        $resultado = $this->checklistModel->listarComFiltros($filtros, $params);
        $estatisticas = $this->checklistModel->obterEstatisticas($filtros);
        $lojas = $this->lojaModel->listarAtivas();
        $modulos = $this->moduloModel->listarAtivos();

        return [
            'checklists' => $resultado['registros'],
            'paginacao' => [
                'total' => $resultado['total'],
                'pagina_atual' => $resultado['pagina_atual'],
                'total_paginas' => $resultado['total_paginas']
            ],
            'estatisticas' => $estatisticas,
            'filtros' => $filtros,
            'lojas' => $lojas,
            'modulos' => $modulos
        ];
    }

    /**
     * Exibe formulário para novo checklist
     */
    public function exibirFormularioNovo() {
        $lojas = $this->lojaModel->listarAtivas();
        $modulos = $this->moduloModel->listarAtivos();

        return [
            'lojas' => $lojas,
            'modulos' => $modulos
        ];
    }

    /**
     * Cria um novo checklist
     */
    public function criar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'message' => 'Método inválido'];
        }

        try {
            $dados = [
                'loja_id' => $_POST['loja_id'],
                'colaborador_id' => $_SESSION['user_id'] ?? 1, // TODO: pegar do usuário logado
                'modulo_id' => $_POST['modulo_id'],
                'data_avaliacao' => $_POST['data_avaliacao'] ?? date('Y-m-d'),
                'observacoes_gerais' => $_POST['observacoes_gerais'] ?? null
            ];

            $checklistId = $this->checklistModel->criar($dados);

            return [
                'success' => true,
                'message' => 'Checklist criado com sucesso!',
                'checklist_id' => $checklistId
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao criar checklist: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Exibe formulário de edição
     */
    public function exibirFormularioEditar($id) {
        $checklist = $this->checklistModel->buscarPorId($id);

        if (!$checklist) {
            return ['success' => false, 'message' => 'Checklist não encontrado'];
        }

        // Buscar perguntas do módulo
        $perguntas = $this->perguntaModel->listarPorModulo($checklist['modulo_id'], true);

        // Buscar respostas já existentes
        $respostas = $this->respostaModel->obterRespostasCompletas($id);
        $respostasIndexadas = [];
        foreach ($respostas as $resposta) {
            $respostasIndexadas[$resposta['pergunta_id']] = $resposta;
        }

        return [
            'checklist' => $checklist,
            'perguntas' => $perguntas,
            'respostas' => $respostasIndexadas
        ];
    }

    /**
     * Salva resposta de uma pergunta via AJAX
     */
    public function salvarResposta() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Método inválido']);
            exit;
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
                throw new Exception('Número de estrelas inválido');
            }

            $respostaId = $this->respostaModel->salvarResposta($dados);

            // Upload de foto (se houver)
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $caminhoFoto = $this->uploadFoto($_FILES['foto']);
                $this->respostaModel->adicionarFoto($respostaId, $caminhoFoto, $_POST['legenda_foto'] ?? null);
            }

            // Recalcular pontuação total do checklist
            $pontuacao = $this->checklistModel->calcularPontuacao($dados['checklist_id']);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Resposta salva com sucesso',
                'pontuacao' => $pontuacao
            ]);
            exit;

        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao salvar resposta: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    /**
     * Finaliza o checklist
     */
    public function finalizar($id) {
        try {
            // Verificar se todas as perguntas obrigatórias foram respondidas
            $checklist = $this->checklistModel->buscarPorId($id);

            if (!$checklist) {
                return ['success' => false, 'message' => 'Checklist não encontrado'];
            }

            $perguntasObrigatorias = $this->perguntaModel->listarPorModulo($checklist['modulo_id'], true);
            $totalPerguntas = count($perguntasObrigatorias);

            $respostas = $this->respostaModel->obterRespostasCompletas($id);
            $totalRespostas = count($respostas);

            if ($totalRespostas < $totalPerguntas) {
                return [
                    'success' => false,
                    'message' => 'Por favor, responda todas as perguntas obrigatórias antes de finalizar'
                ];
            }

            $this->checklistModel->finalizar($id);

            return [
                'success' => true,
                'message' => 'Checklist finalizado com sucesso!'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao finalizar checklist: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Visualiza checklist finalizado
     */
    public function visualizar($id) {
        $checklist = $this->checklistModel->buscarPorId($id);

        if (!$checklist) {
            return ['success' => false, 'message' => 'Checklist não encontrado'];
        }

        $respostas = $this->respostaModel->obterRespostasCompletas($id);
        $classificacao = PontuacaoHelper::obterClassificacao($checklist['percentual']);

        return [
            'checklist' => $checklist,
            'respostas' => $respostas,
            'classificacao' => $classificacao
        ];
    }

    /**
     * Deleta checklist
     */
    public function deletar($id) {
        try {
            $this->checklistModel->deletar($id);

            return [
                'success' => true,
                'message' => 'Checklist deletado com sucesso!'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao deletar checklist: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Upload de foto
     */
    private function uploadFoto($arquivo) {
        $uploadDir = __DIR__ . '/../../public/uploads/fotos_checklist/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $extensao = pathinfo($arquivo['name'], PATHINFO_EXTENSION);
        $nomeArquivo = uniqid() . '_' . time() . '.' . $extensao;
        $caminhoCompleto = $uploadDir . $nomeArquivo;

        if (!move_uploaded_file($arquivo['tmp_name'], $caminhoCompleto)) {
            throw new Exception('Erro ao fazer upload da foto');
        }

        return '/uploads/fotos_checklist/' . $nomeArquivo;
    }
}
