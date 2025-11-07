<?php
/**
 * Helper: PontuacaoHelper
 * Funções auxiliares para cálculos de pontuação
 */

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
        if ($pontuacaoMaxima == 0) {
            return 0;
        }
        return round(($pontuacao / $pontuacaoMaxima) * 5, 1);
    }

    /**
     * Converte percentual para classificação
     */
    public static function obterClassificacao($percentual) {
        if ($percentual >= 80) {
            return [
                'texto' => 'Excelente',
                'classe' => 'success',
                'icone' => '⭐⭐⭐⭐⭐',
                'cor' => '#28a745'
            ];
        } elseif ($percentual >= 60) {
            return [
                'texto' => 'Bom',
                'classe' => 'primary',
                'icone' => '⭐⭐⭐⭐',
                'cor' => '#007bff'
            ];
        } elseif ($percentual >= 40) {
            return [
                'texto' => 'Regular',
                'classe' => 'warning',
                'icone' => '⭐⭐⭐',
                'cor' => '#ffc107'
            ];
        } elseif ($percentual >= 20) {
            return [
                'texto' => 'Ruim',
                'classe' => 'danger',
                'icone' => '⭐⭐',
                'cor' => '#fd7e14'
            ];
        } else {
            return [
                'texto' => 'Muito Ruim',
                'classe' => 'dark',
                'icone' => '⭐',
                'cor' => '#dc3545'
            ];
        }
    }

    /**
     * Verifica se atingiu a meta
     */
    public static function atingiuMeta($percentual, $metaMinima = 80) {
        return $percentual >= $metaMinima;
    }

    /**
     * Formata percentual para exibição
     */
    public static function formatarPercentual($percentual, $decimais = 1) {
        return number_format($percentual, $decimais, ',', '.') . '%';
    }

    /**
     * Formata pontuação para exibição
     */
    public static function formatarPontuacao($pontuacao, $decimais = 2) {
        return number_format($pontuacao, $decimais, ',', '.');
    }

    /**
     * Gera HTML de estrelas
     */
    public static function gerarEstrelasHtml($estrelas, $total = 5) {
        $html = '';
        for ($i = 1; $i <= $total; $i++) {
            if ($i <= $estrelas) {
                $html .= '<i class="fas fa-star text-warning"></i>';
            } else {
                $html .= '<i class="far fa-star text-muted"></i>';
            }
        }
        return $html;
    }

    /**
     * Calcula média de estrelas de um array de respostas
     */
    public static function calcularMediaEstrelas($respostas) {
        if (empty($respostas)) {
            return 0;
        }

        $total = 0;
        foreach ($respostas as $resposta) {
            $total += $resposta['estrelas'] ?? 0;
        }

        return round($total / count($respostas), 1);
    }

    /**
     * Obtém cor baseada no percentual
     */
    public static function obterCorPercentual($percentual) {
        if ($percentual >= 80) return '#28a745'; // Verde
        if ($percentual >= 60) return '#007bff'; // Azul
        if ($percentual >= 40) return '#ffc107'; // Amarelo
        if ($percentual >= 20) return '#fd7e14'; // Laranja
        return '#dc3545'; // Vermelho
    }
}
