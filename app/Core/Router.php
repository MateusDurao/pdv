<?php
/**
 * Roteador simples.
 *
 * Suporta parâmetros dinâmicos no caminho, por exemplo: /api/produtos/{id}
 */

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /**
     * @var array<string, list<array{padrao: string, acao: callable}>>
     */
    private array $rotas = [];

    public function get(string $caminho, callable $acao): void
    {
        $this->registrar('GET', $caminho, $acao);
    }

    public function post(string $caminho, callable $acao): void
    {
        $this->registrar('POST', $caminho, $acao);
    }

    public function put(string $caminho, callable $acao): void
    {
        $this->registrar('PUT', $caminho, $acao);
    }

    public function delete(string $caminho, callable $acao): void
    {
        $this->registrar('DELETE', $caminho, $acao);
    }

    public function despachar(Request $requisicao): void
    {
        $metodo = $requisicao->metodo();
        $caminho = $requisicao->caminho();

        foreach ($this->rotas[$metodo] ?? [] as $rota) {
            if (preg_match($rota['padrao'], $caminho, $encontrados) !== 1) {
                continue;
            }

            $parametros = array_filter(
                $encontrados,
                static fn (int|string $chave): bool => is_string($chave),
                ARRAY_FILTER_USE_KEY
            );

            ($rota['acao'])($requisicao, ...array_values($parametros));

            return;
        }

        Response::erro('Rota não encontrada: ' . $caminho, 404);
    }

    private function registrar(string $metodo, string $caminho, callable $acao): void
    {
        $this->rotas[$metodo][] = [
            'padrao' => $this->compilar($caminho),
            'acao' => $acao,
        ];
    }

    private function compilar(string $caminho): string
    {
        $caminho = rtrim($caminho, '/') ?: '/';

        $padrao = preg_replace(
            '#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#',
            '(?P<$1>[^/]+)',
            $caminho
        );

        return '#^' . $padrao . '$#';
    }
}
