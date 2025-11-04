<?php
/**
 * Controller: Agenda
 * Gerencia operações de agenda de treinamentos
 */

class AgendaController {
    private $model;

    public function __construct() {
        require_once __DIR__ . '/../models/Agenda.php';
        $this->model = new Agenda();
    }

    /**
     * Processar criação de agenda
     */
    public function criarAgenda($dados) {
        // Validações
        $erros = [];

        if (empty($dados['treinamento_id'])) {
            $erros[] = 'Treinamento é obrigatório';
        }

        if (empty($dados['data_inicio'])) {
            $erros[] = 'Data de início é obrigatória';
        }

        // Valida datas
        if (!empty($dados['data_inicio']) && !empty($dados['data_fim'])) {
            if (strtotime($dados['data_fim']) < strtotime($dados['data_inicio'])) {
                $erros[] = 'Data de fim não pode ser anterior à data de início';
            }
        }

        // Valida horários
        if (!empty($dados['hora_inicio']) && !empty($dados['hora_fim'])) {
            if (strtotime($dados['hora_fim']) <= strtotime($dados['hora_inicio'])) {
                $erros[] = 'Hora de fim deve ser posterior à hora de início';
            }
        }

        // Valida vagas
        if (isset($dados['vagas_total']) && $dados['vagas_total'] < 0) {
            $erros[] = 'Número de vagas não pode ser negativo';
        }

        if (!empty($erros)) {
            return [
                'success' => false,
                'message' => implode('<br>', $erros)
            ];
        }

        return $this->model->criar($dados);
    }

    /**
     * Processar atualização de agenda
     */
    public function atualizarAgenda($id, $dados) {
        // Mesmas validações do criar
        $erros = [];

        if (empty($dados['data_inicio'])) {
            $erros[] = 'Data de início é obrigatória';
        }

        if (!empty($dados['data_inicio']) && !empty($dados['data_fim'])) {
            if (strtotime($dados['data_fim']) < strtotime($dados['data_inicio'])) {
                $erros[] = 'Data de fim não pode ser anterior à data de início';
            }
        }

        if (!empty($erros)) {
            return [
                'success' => false,
                'message' => implode('<br>', $erros)
            ];
        }

        return $this->model->atualizar($id, $dados);
    }

    /**
     * Processar exclusão de agenda
     */
    public function deletarAgenda($id) {
        return $this->model->deletar($id);
    }

    /**
     * Buscar agenda
     */
    public function buscarAgenda($id) {
        return $this->model->buscarPorId($id);
    }

    /**
     * Listar agendas de um treinamento
     */
    public function listarAgendas($treinamentoId) {
        return $this->model->listarPorTreinamento($treinamentoId);
    }

    /**
     * Verificar disponibilidade
     */
    public function verificarDisponibilidade($agendaId) {
        return $this->model->temVagasDisponiveis($agendaId);
    }
}
