<?php
/**
 * Classe RateLimiter - Proteção contra Brute Force
 * Sistema de Gestão de Capacitações (SGC)
 *
 * Limita o número de tentativas de login por IP/usuário
 */

class RateLimiter
{
    /**
     * Prefixo para chaves de sessão
     */
    const SESSION_PREFIX = 'rate_limit_';

    /**
     * Número máximo de tentativas permitidas
     */
    private $maxAttempts;

    /**
     * Tempo de decay em minutos
     */
    private $decayMinutes;

    /**
     * Construtor
     *
     * @param int|null $maxAttempts Número máximo de tentativas
     * @param int|null $decayMinutes Minutos para resetar
     */
    public function __construct(?int $maxAttempts = null, ?int $decayMinutes = null)
    {
        $this->maxAttempts = $maxAttempts ?? (int) env('RATE_LIMIT_MAX_ATTEMPTS', 5);
        $this->decayMinutes = $decayMinutes ?? (int) env('RATE_LIMIT_DECAY_MINUTES', 15);
    }

    /**
     * Verificar se atingiu o limite de tentativas
     *
     * @param string $key Chave única (IP, email, etc)
     * @return bool True se atingiu o limite
     */
    public function tooManyAttempts(string $key): bool
    {
        $attempts = $this->getAttempts($key);
        return $attempts >= $this->maxAttempts;
    }

    /**
     * Incrementar tentativas
     *
     * @param string $key Chave única
     */
    public function hit(string $key): void
    {
        $sessionKey = $this->getSessionKey($key);

        if (!isset($_SESSION[$sessionKey])) {
            $_SESSION[$sessionKey] = [
                'attempts' => 0,
                'expires_at' => $this->getExpiryTime()
            ];
        }

        // Verificar se expirou
        if ($this->hasExpired($sessionKey)) {
            $this->clear($key);
            $_SESSION[$sessionKey] = [
                'attempts' => 0,
                'expires_at' => $this->getExpiryTime()
            ];
        }

        $_SESSION[$sessionKey]['attempts']++;
    }

    /**
     * Obter número de tentativas
     *
     * @param string $key Chave única
     * @return int Número de tentativas
     */
    public function getAttempts(string $key): int
    {
        $sessionKey = $this->getSessionKey($key);

        if (!isset($_SESSION[$sessionKey])) {
            return 0;
        }

        // Se expirou, retornar 0
        if ($this->hasExpired($sessionKey)) {
            $this->clear($key);
            return 0;
        }

        return $_SESSION[$sessionKey]['attempts'] ?? 0;
    }

    /**
     * Limpar tentativas
     *
     * @param string $key Chave única
     */
    public function clear(string $key): void
    {
        $sessionKey = $this->getSessionKey($key);
        unset($_SESSION[$sessionKey]);
    }

    /**
     * Obter tempo restante até resetar
     *
     * @param string $key Chave única
     * @return int Segundos restantes
     */
    public function availableIn(string $key): int
    {
        $sessionKey = $this->getSessionKey($key);

        if (!isset($_SESSION[$sessionKey]['expires_at'])) {
            return 0;
        }

        $remaining = $_SESSION[$sessionKey]['expires_at'] - time();
        return max(0, $remaining);
    }

    /**
     * Obter tentativas restantes
     *
     * @param string $key Chave única
     * @return int Tentativas restantes
     */
    public function retriesLeft(string $key): int
    {
        $attempts = $this->getAttempts($key);
        return max(0, $this->maxAttempts - $attempts);
    }

    /**
     * Verificar se expirou
     *
     * @param string $sessionKey Chave de sessão
     * @return bool
     */
    private function hasExpired(string $sessionKey): bool
    {
        if (!isset($_SESSION[$sessionKey]['expires_at'])) {
            return true;
        }

        return time() >= $_SESSION[$sessionKey]['expires_at'];
    }

    /**
     * Obter tempo de expiração
     *
     * @return int Timestamp de expiração
     */
    private function getExpiryTime(): int
    {
        return time() + ($this->decayMinutes * 60);
    }

    /**
     * Obter chave de sessão
     *
     * @param string $key Chave original
     * @return string Chave de sessão
     */
    private function getSessionKey(string $key): string
    {
        return self::SESSION_PREFIX . sha1($key);
    }

    /**
     * Obter IP do cliente
     *
     * @return string IP do cliente
     */
    public static function getClientIp(): string
    {
        // Verificar headers de proxy
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Pegar o primeiro IP da lista
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }

        // Validar IP
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $ip = '0.0.0.0';
        }

        return $ip;
    }

    /**
     * Criar chave baseada em IP
     *
     * @param string $prefix Prefixo da chave
     * @return string Chave única
     */
    public static function keyByIp(string $prefix = 'login'): string
    {
        return $prefix . ':' . self::getClientIp();
    }

    /**
     * Criar chave baseada em email e IP
     *
     * @param string $email E-mail do usuário
     * @param string $prefix Prefixo da chave
     * @return string Chave única
     */
    public static function keyByEmailAndIp(string $email, string $prefix = 'login'): string
    {
        return $prefix . ':' . $email . ':' . self::getClientIp();
    }

    /**
     * Criar instância para login
     *
     * @return self
     */
    public static function forLogin(): self
    {
        return new self();
    }

    /**
     * Wrapper para verificar login
     *
     * @param string $email E-mail do usuário
     * @return array ['allowed' => bool, 'retries' => int, 'wait' => int]
     */
    public function checkLogin(string $email): array
    {
        $key = self::keyByEmailAndIp($email);

        if ($this->tooManyAttempts($key)) {
            return [
                'allowed' => false,
                'retries' => 0,
                'wait' => $this->availableIn($key),
                'message' => sprintf(
                    'Muitas tentativas de login. Tente novamente em %d minutos.',
                    ceil($this->availableIn($key) / 60)
                )
            ];
        }

        return [
            'allowed' => true,
            'retries' => $this->retriesLeft($key),
            'wait' => 0,
            'message' => ''
        ];
    }

    /**
     * Registrar tentativa de login falhada
     *
     * @param string $email E-mail do usuário
     */
    public function recordLoginAttempt(string $email): void
    {
        $key = self::keyByEmailAndIp($email);
        $this->hit($key);
    }

    /**
     * Limpar após login bem-sucedido
     *
     * @param string $email E-mail do usuário
     */
    public function clearLoginAttempts(string $email): void
    {
        $key = self::keyByEmailAndIp($email);
        $this->clear($key);
    }
}
