<?php
/**
 * SystemConfig
 * Leitura/escrita de configurações do sistema (cores, logo, textos, etc.)
 */

class SystemConfig {
    private static $pdo;

    private static function ensureConnection() {
        if (!self::$pdo) {
            $db = Database::getInstance();
            self::$pdo = $db->getConnection();
            self::ensureTable();
        }
    }

    private static function ensureTable() {
        $sql = "CREATE TABLE IF NOT EXISTS configuracoes_sistema (
            chave VARCHAR(100) PRIMARY KEY,
            valor TEXT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        self::$pdo->exec($sql);
    }

    public static function get($key, $default = null) {
        self::ensureConnection();
        $stmt = self::$pdo->prepare('SELECT valor FROM configuracoes_sistema WHERE chave = ?');
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row && $row['valor'] !== null && $row['valor'] !== '' ? $row['valor'] : $default;
    }

    public static function set($key, $value) {
        self::ensureConnection();
        $stmt = self::$pdo->prepare('REPLACE INTO configuracoes_sistema (chave, valor) VALUES (?, ?)');
        return $stmt->execute([$key, $value]);
    }

    public static function all() {
        self::ensureConnection();
        $stmt = self::$pdo->query('SELECT chave, valor FROM configuracoes_sistema');
        $rows = $stmt ? $stmt->fetchAll() : [];
        $out = [];
        foreach ($rows as $r) { $out[$r['chave']] = $r['valor']; }
        return $out;
    }
}

