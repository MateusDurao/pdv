<?php
/**
 * Inicialização da aplicação.
 *
 * - Define o caminho base do projeto
 * - Registra um autoloader simples no padrão PSR-4 (namespace App\)
 * - Ajusta o fuso horário
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

spl_autoload_register(static function (string $classe): void {
    $prefixo = 'App\\';

    if (!str_starts_with($classe, $prefixo)) {
        return;
    }

    $relativo = substr($classe, strlen($prefixo));
    $arquivo = BASE_PATH . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR
        . str_replace('\\', DIRECTORY_SEPARATOR, $relativo) . '.php';

    if (is_file($arquivo)) {
        require_once $arquivo;
    }
});

date_default_timezone_set('America/Sao_Paulo');
