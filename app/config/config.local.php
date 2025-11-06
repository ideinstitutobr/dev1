<?php
// Configurações locais para desenvolvimento
// Ajusta BASE_URL para o servidor embutido do PHP
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://127.0.0.1:8090/');
}
// Ambiente local
if (!defined('APP_ENV')) {
    define('APP_ENV', 'development');
}
