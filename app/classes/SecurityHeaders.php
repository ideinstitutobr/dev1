<?php
/**
 * Classe SecurityHeaders - Gerencia Headers HTTP de Segurança
 * Sistema de Gestão de Capacitações (SGC)
 *
 * Implementa headers de segurança recomendados pelo OWASP
 */

class SecurityHeaders
{
    /**
     * Aplicar todos os headers de segurança
     */
    public static function apply(): void
    {
        self::setXFrameOptions();
        self::setXContentTypeOptions();
        self::setXXSSProtection();
        self::setContentSecurityPolicy();
        self::setStrictTransportSecurity();
        self::setReferrerPolicy();
        self::setPermissionsPolicy();
        self::removeServerHeader();
    }

    /**
     * X-Frame-Options: Previne clickjacking
     * Impede que o site seja carregado em iframes
     */
    private static function setXFrameOptions(): void
    {
        if (!headers_sent()) {
            header('X-Frame-Options: DENY');
        }
    }

    /**
     * X-Content-Type-Options: Previne MIME sniffing
     * Força o browser a respeitar o Content-Type declarado
     */
    private static function setXContentTypeOptions(): void
    {
        if (!headers_sent()) {
            header('X-Content-Type-Options: nosniff');
        }
    }

    /**
     * X-XSS-Protection: Habilita proteção XSS do browser
     * Nota: Descontinuado em navegadores modernos, mas ainda útil para legados
     */
    private static function setXXSSProtection(): void
    {
        if (!headers_sent()) {
            header('X-XSS-Protection: 1; mode=block');
        }
    }

    /**
     * Content-Security-Policy: Define fontes permitidas de conteúdo
     * Previne XSS e injeção de código malicioso
     */
    private static function setContentSecurityPolicy(): void
    {
        if (!headers_sent()) {
            // CSP básico - você pode customizar conforme necessário
            $csp = [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
                "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com",
                "font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com",
                "img-src 'self' data: https:",
                "connect-src 'self'",
                "frame-ancestors 'none'",
                "base-uri 'self'",
                "form-action 'self'"
            ];

            header('Content-Security-Policy: ' . implode('; ', $csp));
        }
    }

    /**
     * Strict-Transport-Security: Força HTTPS
     * Apenas aplicado se estiver em HTTPS
     */
    private static function setStrictTransportSecurity(): void
    {
        // Só aplicar se estiver em HTTPS
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                   || $_SERVER['SERVER_PORT'] == 443
                   || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

        if ($isHttps && !headers_sent()) {
            // max-age=31536000 (1 ano)
            // includeSubDomains: aplicar a todos os subdomínios
            // preload: permite inclusão na lista de preload do browser
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
    }

    /**
     * Referrer-Policy: Controla informações de referrer
     * Previne vazamento de informações sensíveis via referrer
     */
    private static function setReferrerPolicy(): void
    {
        if (!headers_sent()) {
            // strict-origin-when-cross-origin: Envia origin completa apenas para same-origin
            header('Referrer-Policy: strict-origin-when-cross-origin');
        }
    }

    /**
     * Permissions-Policy: Controla APIs do browser
     * Desabilita recursos não utilizados
     */
    private static function setPermissionsPolicy(): void
    {
        if (!headers_sent()) {
            $policies = [
                'geolocation=()',
                'microphone=()',
                'camera=()',
                'payment=()',
                'usb=()',
                'magnetometer=()',
                'gyroscope=()',
                'accelerometer=()'
            ];

            header('Permissions-Policy: ' . implode(', ', $policies));
        }
    }

    /**
     * Remove header Server para não expor tecnologia
     */
    private static function removeServerHeader(): void
    {
        if (!headers_sent()) {
            header_remove('X-Powered-By');
            // Nota: Server header só pode ser removido via configuração do servidor (Apache/Nginx)
        }
    }

    /**
     * Aplicar headers específicos para API JSON
     */
    public static function applyForApi(): void
    {
        self::apply();

        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            header('X-Content-Type-Options: nosniff');
        }
    }

    /**
     * Aplicar headers específicos para download de arquivos
     *
     * @param string $filename Nome do arquivo
     * @param string $contentType Tipo de conteúdo
     */
    public static function applyForDownload(string $filename, string $contentType = 'application/octet-stream'): void
    {
        if (!headers_sent()) {
            header("Content-Type: {$contentType}");
            header("Content-Disposition: attachment; filename=\"{$filename}\"");
            header('Content-Transfer-Encoding: binary');
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
        }
    }

    /**
     * Configurar cache para assets estáticos
     *
     * @param int $maxAge Tempo em segundos (padrão: 1 ano)
     */
    public static function applyForStaticAssets(int $maxAge = 31536000): void
    {
        if (!headers_sent()) {
            header("Cache-Control: public, max-age={$maxAge}, immutable");
            header('Vary: Accept-Encoding');
        }
    }

    /**
     * Desabilitar cache para páginas dinâmicas/sensíveis
     */
    public static function disableCache(): void
    {
        if (!headers_sent()) {
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Cache-Control: post-check=0, pre-check=0', false);
            header('Pragma: no-cache');
            header('Expires: 0');
        }
    }

    /**
     * Verificar se headers já foram enviados
     *
     * @return bool
     */
    public static function headersSent(): bool
    {
        return headers_sent();
    }

    /**
     * Obter lista de headers definidos
     *
     * @return array
     */
    public static function getHeaders(): array
    {
        return headers_list();
    }
}
