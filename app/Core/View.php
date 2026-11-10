<?php
/**
 * Renderização das views (arquivos PHP em app/Views).
 */

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class View
{
    /**
     * @param array<string, mixed> $dados
     */
    public static function render(string $nome, array $dados = []): void
    {
        $arquivo = BASE_PATH . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR
            . 'Views' . DIRECTORY_SEPARATOR . $nome . '.php';

        if (!is_file($arquivo)) {
            throw new RuntimeException('View não encontrada: ' . $nome);
        }

        extract($dados, EXTR_SKIP);

        require $arquivo;
    }
}
