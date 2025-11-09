<?php
/**
 * Classe DotEnv - Carrega variáveis de ambiente do arquivo .env
 * Sistema de Gestão de Capacitações (SGC)
 *
 * Inspirado em vlucas/phpdotenv, mas simplificado para as necessidades do SGC
 */

class DotEnv
{
    /**
     * Caminho do arquivo .env
     */
    protected $path;

    /**
     * Construtor
     *
     * @param string $path Caminho do diretório onde está o .env
     */
    public function __construct(string $path)
    {
        if (!file_exists($path)) {
            throw new \Exception("Diretório .env não existe: {$path}");
        }

        $this->path = $path;
    }

    /**
     * Carregar arquivo .env
     *
     * @throws \Exception Se o arquivo .env não existir
     */
    public function load(): void
    {
        $filePath = rtrim($this->path, '/') . '/.env';

        if (!is_readable($filePath)) {
            throw new \Exception(
                "Arquivo .env não encontrado ou não legível. " .
                "Copie .env.example para .env e configure suas credenciais."
            );
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            // Ignorar comentários
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parse da linha
            $this->parseLine($line);
        }
    }

    /**
     * Fazer parse de uma linha do .env
     *
     * @param string $line Linha do arquivo
     */
    protected function parseLine(string $line): void
    {
        // Remover espaços em branco
        $line = trim($line);

        // Verificar se tem =
        if (strpos($line, '=') === false) {
            return;
        }

        // Separar chave e valor
        list($name, $value) = explode('=', $line, 2);

        $name = trim($name);
        $value = trim($value);

        // Remover aspas do valor se existirem
        $value = $this->removeQuotes($value);

        // Expandir variáveis ${VAR}
        $value = $this->expandVariables($value);

        // Definir variável de ambiente
        $this->setEnvironmentVariable($name, $value);
    }

    /**
     * Remover aspas do valor
     *
     * @param string $value Valor com possíveis aspas
     * @return string Valor sem aspas
     */
    protected function removeQuotes(string $value): string
    {
        // Remover aspas duplas
        if (strlen($value) > 1 && $value[0] === '"' && $value[strlen($value) - 1] === '"') {
            return substr($value, 1, -1);
        }

        // Remover aspas simples
        if (strlen($value) > 1 && $value[0] === "'" && $value[strlen($value) - 1] === "'") {
            return substr($value, 1, -1);
        }

        return $value;
    }

    /**
     * Expandir variáveis no formato ${VAR}
     *
     * @param string $value Valor com possíveis variáveis
     * @return string Valor com variáveis expandidas
     */
    protected function expandVariables(string $value): string
    {
        if (strpos($value, '${') === false) {
            return $value;
        }

        return preg_replace_callback(
            '/\$\{([a-zA-Z0-9_]+)\}/',
            function ($matches) {
                return $this->getEnvironmentVariable($matches[1]) ?? $matches[0];
            },
            $value
        );
    }

    /**
     * Definir variável de ambiente
     *
     * @param string $name Nome da variável
     * @param string $value Valor da variável
     */
    protected function setEnvironmentVariable(string $name, ?string $value = null): void
    {
        // Não sobrescrever se já existe em $_ENV ou $_SERVER
        if (isset($_ENV[$name]) || isset($_SERVER[$name])) {
            return;
        }

        // Definir em putenv, $_ENV e $_SERVER
        putenv("{$name}={$value}");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }

    /**
     * Obter variável de ambiente
     *
     * @param string $name Nome da variável
     * @param mixed $default Valor padrão se não existir
     * @return mixed Valor da variável ou padrão
     */
    public function getEnvironmentVariable(string $name, $default = null)
    {
        // Tentar getenv primeiro
        $value = getenv($name);
        if ($value !== false) {
            return $value;
        }

        // Tentar $_ENV
        if (isset($_ENV[$name])) {
            return $_ENV[$name];
        }

        // Tentar $_SERVER
        if (isset($_SERVER[$name])) {
            return $_SERVER[$name];
        }

        return $default;
    }

    /**
     * Verificar se variáveis obrigatórias estão definidas
     *
     * @param array $required Array de variáveis obrigatórias
     * @throws \Exception Se alguma variável obrigatória não estiver definida
     */
    public function required(array $required): void
    {
        $missing = [];

        foreach ($required as $var) {
            if ($this->getEnvironmentVariable($var) === null) {
                $missing[] = $var;
            }
        }

        if (!empty($missing)) {
            throw new \Exception(
                "Variáveis de ambiente obrigatórias não definidas: " .
                implode(', ', $missing)
            );
        }
    }
}

/**
 * Helper global para obter variável de ambiente
 *
 * @param string $key Nome da variável
 * @param mixed $default Valor padrão
 * @return mixed Valor da variável
 */
function env(string $key, $default = null)
{
    // Tentar getenv
    $value = getenv($key);
    if ($value !== false) {
        return $value;
    }

    // Tentar $_ENV
    if (isset($_ENV[$key])) {
        return $_ENV[$key];
    }

    // Tentar $_SERVER
    if (isset($_SERVER[$key])) {
        return $_SERVER[$key];
    }

    return $default;
}
