<?php
/**
 * Controller de clientes: CRUD completo.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Cliente;
use App\Repositories\ClienteRepository;

final class ClienteController
{
    public function __construct(private ClienteRepository $clientes)
    {
    }

    /**
     * GET /api/clientes?busca=...
     */
    public function index(Request $requisicao): void
    {
        $busca = trim((string) $requisicao->query('busca', ''));

        $clientes = array_map(
            static fn (Cliente $cliente): array => $cliente->paraArray(),
            $this->clientes->listar($busca)
        );

        Response::sucesso(['clientes' => $clientes]);
    }

    /**
     * GET /api/clientes/{id}
     */
    public function show(Request $requisicao, string $id): void
    {
        $cliente = $this->clientes->buscar((int) $id);

        if ($cliente === null) {
            Response::erro('Cliente não encontrado.', 404);
        }

        Response::sucesso(['cliente' => $cliente->paraArray()]);
    }

    /**
     * POST /api/clientes
     */
    public function store(Request $requisicao): void
    {
        $cliente = Cliente::deArray($requisicao->corpo());

        $id = $this->clientes->criar($cliente);

        Response::sucesso([
            'mensagem' => 'Cliente cadastrado com sucesso!',
            'id' => $id,
        ], 201);
    }

    /**
     * PUT /api/clientes/{id}
     */
    public function update(Request $requisicao, string $id): void
    {
        if ($this->clientes->buscar((int) $id) === null) {
            Response::erro('Cliente não encontrado.', 404);
        }

        $dados = $requisicao->corpo();
        $dados['id'] = (int) $id;

        $this->clientes->atualizar(Cliente::deArray($dados));

        Response::sucesso(['mensagem' => 'Cliente atualizado com sucesso!']);
    }

    /**
     * DELETE /api/clientes/{id}
     */
    public function destroy(Request $requisicao, string $id): void
    {
        if ($this->clientes->buscar((int) $id) === null) {
            Response::erro('Cliente não encontrado.', 404);
        }

        if ($this->clientes->possuiVendas((int) $id)) {
            Response::erro('Não é possível excluir: este cliente possui vendas registradas.', 409);
        }

        $this->clientes->excluir((int) $id);

        Response::sucesso(['mensagem' => 'Cliente excluído com sucesso!']);
    }
}
