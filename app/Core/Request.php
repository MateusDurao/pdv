<?php
/**
 * Representa a requisição HTTP recebida.
 */

declare(strict_types=1);

namespace App\Core;

final class Request
{
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $corpo
     */
    public function __construct(
        private string $metodo,
        private string $caminho,
        private array $query,
        private array $corpo,
    ) {
    }

    public static function capturar(): self
    {
        $metodo = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        $caminho = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $caminho = rtrim($caminho, '/') ?: '/';

        $corpo = [];
        $conteudo = file_get_contents('php://input');

        if (is_string($conteudo) && $conteudo !== '') {
            $decodificado = json_decode($conteudo, true);

            if (is_array($decodificado)) {
                $corpo = $decodificado;
            }
        }

        return new self($metodo, $caminho, $_GET, $corpo);
    }

    public function metodo(): string
    {
        return $this->metodo;
    }

    public function caminho(): string
    {
        return $this->caminho;
    }

    public function query(string $chave, mixed $padrao = null): mixed
    {
        return $this->query[$chave] ?? $padrao;
    }

    /**
     * Sem argumentos devolve o corpo inteiro; com argumento devolve um campo.
     *
     * @return mixed
     */
    public function corpo(?string $chave = null, mixed $padrao = null): mixed
    {
        if ($chave === null) {
            return $this->corpo;
        }

        return $this->corpo[$chave] ?? $padrao;
    }
}
