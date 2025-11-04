<?php
/**
 * Controller: Frequencia
 * Gerencia lógica de negócio para frequência
 */

class FrequenciaController {
    private $model;

    public function __construct() {
        $this->model = new Frequencia();
    }

    /**
     * ==========================================
     * SESSÕES
     * ==========================================
     */

    /**
     * Criar sessão
     */
    public function criarSessao($dados) {
        // Validações
        $erros = [];

        if (empty($dados['treinamento_id'])) {
            $erros[] = 'Treinamento é obrigatório';
        }

        if (empty($dados['nome'])) {
            $erros[] = 'Nome da sessão é obrigatório';
        }

        if (empty($dados['data_sessao'])) {
            $erros[] = 'Data da sessão é obrigatória';
        }

        if (!empty($erros)) {
            return ['success' => false, 'errors' => $erros];
        }

        // Criar sessão
        $sessaoId = $this->model->criarSessao($dados);

        if ($sessaoId) {
            return [
                'success' => true,
                'message' => 'Sessão criada com sucesso!',
                'sessao_id' => $sessaoId
            ];
        }

        return ['success' => false, 'message' => 'Erro ao criar sessão'];
    }

    /**
     * Listar sessões
     */
    public function listarSessoes($treinamentoId) {
        return $this->model->listarSessoesPorTreinamento($treinamentoId);
    }

    /**
     * Buscar sessão
     */
    public function buscarSessao($id) {
        return $this->model->buscarSessao($id);
    }

    /**
     * Atualizar sessão
     */
    public function atualizarSessao($id, $dados) {
        // Validações
        $erros = [];

        if (empty($dados['nome'])) {
            $erros[] = 'Nome da sessão é obrigatório';
        }

        if (empty($dados['data_sessao'])) {
            $erros[] = 'Data da sessão é obrigatória';
        }

        if (!empty($erros)) {
            return ['success' => false, 'errors' => $erros];
        }

        $result = $this->model->atualizarSessao($id, $dados);

        if ($result) {
            return ['success' => true, 'message' => 'Sessão atualizada com sucesso!'];
        }

        return ['success' => false, 'message' => 'Erro ao atualizar sessão'];
    }

    /**
     * Deletar sessão
     */
    public function deletarSessao($id) {
        $result = $this->model->deletarSessao($id);

        if ($result) {
            return ['success' => true, 'message' => 'Sessão deletada com sucesso!'];
        }

        return ['success' => false, 'message' => 'Erro ao deletar sessão'];
    }

    /**
     * ==========================================
     * FREQUÊNCIA
     * ==========================================
     */

    /**
     * Listar frequência
     */
    public function listarFrequencia($sessaoId) {
        return $this->model->listarFrequenciaPorSessao($sessaoId);
    }

    /**
     * Registrar presença
     */
    public function registrarPresenca($frequenciaId, $status, $usuarioId, $observacoes = null, $justificativa = null) {
        $statusValidos = ['Presente', 'Ausente', 'Justificado', 'Atrasado'];

        if (!in_array($status, $statusValidos)) {
            return ['success' => false, 'message' => 'Status inválido'];
        }

        if ($status === 'Justificado' && empty($justificativa)) {
            return ['success' => false, 'message' => 'Justificativa é obrigatória para ausências justificadas'];
        }

        $result = $this->model->registrarPresenca($frequenciaId, $status, $usuarioId, $observacoes, $justificativa);

        if ($result) {
            return ['success' => true, 'message' => 'Presença registrada com sucesso!'];
        }

        return ['success' => false, 'message' => 'Erro ao registrar presença'];
    }

    /**
     * Registrar presença múltipla
     */
    public function registrarPresencaMultipla($sessaoId, $presencas, $usuarioId) {
        if (empty($presencas)) {
            return ['success' => false, 'message' => 'Nenhuma presença selecionada'];
        }

        $result = $this->model->registrarPresencaMultipla($sessaoId, $presencas, $usuarioId);

        if ($result) {
            return [
                'success' => true,
                'message' => count($presencas) . ' presença(s) registrada(s) com sucesso!'
            ];
        }

        return ['success' => false, 'message' => 'Erro ao registrar presenças'];
    }

    /**
     * Check-in por QR Code
     */
    public function checkinPorQR($qrToken, $colaboradorId) {
        if (empty($qrToken)) {
            return ['success' => false, 'message' => 'Token QR inválido'];
        }

        if (empty($colaboradorId)) {
            return ['success' => false, 'message' => 'Colaborador não identificado'];
        }

        return $this->model->checkinPorQRCode($qrToken, $colaboradorId);
    }

    /**
     * ==========================================
     * RELATÓRIOS
     * ==========================================
     */

    /**
     * Relatório de frequência
     */
    public function relatorioFrequencia($treinamentoId) {
        $dados = $this->model->relatorioFrequenciaPorTreinamento($treinamentoId);
        $estatisticas = $this->model->estatisticasGerais($treinamentoId);

        return [
            'dados' => $dados,
            'estatisticas' => $estatisticas
        ];
    }

    /**
     * Estatísticas gerais
     */
    public function estatisticasGerais($treinamentoId = null) {
        return $this->model->estatisticasGerais($treinamentoId);
    }

    /**
     * Exportar frequência para CSV
     */
    public function exportarCSV($sessaoId) {
        $sessao = $this->model->buscarSessao($sessaoId);
        $frequencias = $this->model->listarFrequenciaPorSessao($sessaoId);

        if (!$sessao) {
            return ['success' => false, 'message' => 'Sessão não encontrada'];
        }

        // Nome do arquivo
        $nomeArquivo = 'frequencia_' . date('Ymd_His');

        // Headers para download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $nomeArquivo . '.csv');

        // Criar arquivo
        $output = fopen('php://output', 'w');

        // UTF-8 BOM para Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Cabeçalho do relatório
        fputcsv($output, ['RELATÓRIO DE FREQUÊNCIA'], ';');
        fputcsv($output, ['Sessão: ' . $sessao['nome']], ';');
        fputcsv($output, ['Treinamento: ' . $sessao['treinamento_nome']], ';');
        fputcsv($output, ['Data: ' . date('d/m/Y', strtotime($sessao['data_sessao']))], ';');
        fputcsv($output, ['Gerado em: ' . date('d/m/Y H:i:s')], ';');
        fputcsv($output, [''], ';'); // Linha vazia

        // Cabeçalho das colunas
        fputcsv($output, [
            'Colaborador',
            'Cargo',
            'Departamento',
            'Status',
            'Hora Check-in',
            'Justificativa',
            'Observações',
            'Registrado por'
        ], ';');

        // Dados
        foreach ($frequencias as $freq) {
            fputcsv($output, [
                $freq['colaborador_nome'],
                $freq['cargo'] ?? '-',
                $freq['departamento'] ?? '-',
                $freq['status'],
                $freq['hora_checkin'] ?? '-',
                $freq['justificativa'] ?? '-',
                $freq['observacoes'] ?? '-',
                $freq['registrado_por_nome'] ?? 'Sistema'
            ], ';');
        }

        // Estatísticas
        fputcsv($output, [''], ';'); // Linha vazia
        fputcsv($output, ['ESTATÍSTICAS'], ';');
        fputcsv($output, ['Total de participantes', count($frequencias)], ';');
        fputcsv($output, [
            'Presentes',
            count(array_filter($frequencias, fn($f) => $f['status'] === 'Presente'))
        ], ';');
        fputcsv($output, [
            'Ausentes',
            count(array_filter($frequencias, fn($f) => $f['status'] === 'Ausente'))
        ], ';');
        fputcsv($output, [
            'Taxa de Presença',
            $sessao['total_participantes'] > 0
                ? round(($sessao['total_presentes'] / $sessao['total_participantes']) * 100, 1) . '%'
                : '0%'
        ], ';');

        fclose($output);
        exit;
    }

    /**
     * ==========================================
     * UTILITÁRIOS
     * ==========================================
     */

    /**
     * Buscar sessão por QR
     */
    public function buscarSessaoPorQR($qrToken) {
        return $this->model->buscarSessaoPorQR($qrToken);
    }

    /**
     * Verificar vínculo de colaborador
     */
    public function verificarVinculo($treinamentoId, $colaboradorId) {
        return $this->model->verificarVinculoColaborador($treinamentoId, $colaboradorId);
    }
}
