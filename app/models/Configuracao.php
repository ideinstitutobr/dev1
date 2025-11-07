<?php
/**
 * Model: Configuracao
 * Gerencia configurações do sistema
 */

class Configuracao {
    private $db;
    private $pdo;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    /**
     * Obtém valor de configuração
     */
    public function obter($chave) {
        $sql = "SELECT valor, tipo FROM configuracoes_sistema WHERE chave = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$chave]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$resultado) {
            return null;
        }

        // Converte o valor de acordo com o tipo
        switch ($resultado['tipo']) {
            case 'int':
                return (int) $resultado['valor'];
            case 'decimal':
                return (float) $resultado['valor'];
            case 'boolean':
                return (bool) $resultado['valor'];
            default:
                return $resultado['valor'];
        }
    }

    /**
     * Define valor de configuração
     */
    public function definir($chave, $valor, $descricao = null, $tipo = 'string') {
        $sql = "INSERT INTO configuracoes_sistema (chave, valor, descricao, tipo)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE valor = ?, descricao = COALESCE(?, descricao)";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$chave, $valor, $descricao, $tipo, $valor, $descricao]);
    }

    /**
     * Lista todas as configurações
     */
    public function listar($grupo = null) {
        if ($grupo) {
            $sql = "SELECT * FROM configuracoes_sistema WHERE chave LIKE ? ORDER BY chave";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(["{$grupo}%"]);
        } else {
            $sql = "SELECT * FROM configuracoes_sistema ORDER BY chave";
            $stmt = $this->pdo->query($sql);
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtém todos os pesos de pontuação
     */
    public function obterPesosPontuacao() {
        $sql = "SELECT * FROM configuracoes_sistema WHERE chave LIKE 'peso_%' ORDER BY chave";
        $stmt = $this->pdo->query($sql);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $pesos = [
            '8_perguntas' => [],
            '6_perguntas' => []
        ];

        foreach ($resultados as $resultado) {
            if (strpos($resultado['chave'], 'peso_8_perguntas_') === 0) {
                $estrelas = (int) str_replace(['peso_8_perguntas_', '_estrela', '_estrelas'], '', $resultado['chave']);
                $pesos['8_perguntas'][$estrelas] = (float) $resultado['valor'];
            } elseif (strpos($resultado['chave'], 'peso_6_perguntas_') === 0) {
                $estrelas = (int) str_replace(['peso_6_perguntas_', '_estrela', '_estrelas'], '', $resultado['chave']);
                $pesos['6_perguntas'][$estrelas] = (float) $resultado['valor'];
            }
        }

        return $pesos;
    }

    /**
     * Atualiza configuração
     */
    public function atualizar($chave, $valor) {
        $sql = "UPDATE configuracoes_sistema SET valor = ? WHERE chave = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$valor, $chave]);
    }

    /**
     * Deleta configuração
     */
    public function deletar($chave) {
        $sql = "DELETE FROM configuracoes_sistema WHERE chave = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$chave]);
    }
}
