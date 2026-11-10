<?php
/**
 * Respostas HTTP em JSON.
 */

declare(strict_types=1);

namespace App\Core;

final class Response
{
    /**
     * @param array<string, mixed> $dados
     */
    public static function json(array $dados, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode($dados, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * @param array<string, mixed> $dados
     */
    public static function sucesso(array $dados = [], int $status = 200): never
    {
        self::json(array_merge(['ok' => true], $dados), $status);
    }

    public static function erro(string $mensagem, int $status = 400): never
    {
        self::json(['ok' => false, 'erro' => $mensagem], $status);
    }
}
