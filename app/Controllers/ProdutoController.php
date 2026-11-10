<?php
/**
 * Controller de produtos: CRUD completo.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Produto;
use App\Repositories\ProdutoRepository;

final class ProdutoController
{
    public function __construct(private ProdutoRepository $produtos)
    {
    }

    /**
     * GET /api/produtos?busca=...
     */
    public function index(Request $requisicao): void
    {
        $busca = trim((string) $requisicao->query('busca', ''));

        $produtos = array_map(
            static fn (Produto $produto): array => $produto->paraArray(),
            $this->produtos->listar($busca)
        );

        Response::sucesso(['produtos' => $produtos]);
    }

    /**
     * GET /api/produtos/{id}
     */
    public function show(Request $requisicao, string $id): void
    {
        $produto = $this->produtos->buscar((int) $id);

        if ($produto === null) {
            Response::erro('Produto não encontrado.', 404);
        }

        Response::sucesso(['produto' => $produto->paraArray()]);
    }

    /**
     * POST /api/produtos
     */
    public function store(Request $requisicao): void
    {
        $produto = Produto::deArray($requisicao->corpo());

        $id = $this->produtos->criar($produto);

        Response::sucesso([
            'mensagem' => 'Produto cadastrado com sucesso!',
            'id' => $id,
        ], 201);
    }

    /**
     * PUT /api/produtos/{id}
     */
    public function update(Request $requisicao, string $id): void
    {
        if ($this->produtos->buscar((int) $id) === null) {
            Response::erro('Produto não encontrado.', 404);
        }

        $dados = $requisicao->corpo();
        $dados['id'] = (int) $id;

        $this->produtos->atualizar(Produto::deArray($dados));

        Response::sucesso(['mensagem' => 'Produto atualizado com sucesso!']);
    }

    /**
     * DELETE /api/produtos/{id}
     */
    public function destroy(Request $requisicao, string $id): void
    {
        if ($this->produtos->buscar((int) $id) === null) {
            Response::erro('Produto não encontrado.', 404);
        }

        $this->produtos->excluir((int) $id);

        Response::sucesso(['mensagem' => 'Produto excluído com sucesso!']);
    }
}
