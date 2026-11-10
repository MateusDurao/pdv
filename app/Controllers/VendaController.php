<?php
/**
 * Controller de vendas: listagem, detalhes e finalização.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\ItemVenda;
use App\Models\Venda;
use App\Repositories\VendaRepository;

final class VendaController
{
    public function __construct(private VendaRepository $vendas)
    {
    }

    /**
     * GET /api/vendas
     */
    public function index(Request $requisicao): void
    {
        $vendas = array_map(
            static fn (Venda $venda): array => $venda->paraArray(),
            $this->vendas->ultimas(100)
        );

        Response::sucesso(['vendas' => $vendas]);
    }

    /**
     * GET /api/vendas/{id}
     */
    public function show(Request $requisicao, string $id): void
    {
        $venda = $this->vendas->buscar((int) $id);

        if ($venda === null) {
            Response::erro('Venda não encontrada.', 404);
        }

        Response::sucesso(['venda' => $venda->paraArray(true)]);
    }

    /**
     * POST /api/vendas
     */
    public function store(Request $requisicao): void
    {
        $dados = $requisicao->corpo();
        $itensBrutos = $dados['itens'] ?? [];

        if (!is_array($itensBrutos) || $itensBrutos === []) {
            Response::erro('O carrinho está vazio.');
        }

        $itens = array_map(
            static fn (array $item): ItemVenda => ItemVenda::deArray($item),
            $itensBrutos
        );

        $clienteId = isset($dados['cliente_id']) && $dados['cliente_id'] !== '' && $dados['cliente_id'] !== null
            ? (int) $dados['cliente_id']
            : null;

        $venda = Venda::nova(
            $itens,
            (float) ($dados['desconto'] ?? 0),
            (string) ($dados['forma_pagamento'] ?? 'dinheiro'),
            $clienteId,
        );

        $venda->validar();

        $vendaId = $this->vendas->registrar($venda);

        Response::sucesso([
            'mensagem' => 'Venda #' . $vendaId . ' finalizada com sucesso!',
            'venda_id' => $vendaId,
            'total' => number_format($venda->total, 2, ',', '.'),
        ], 201);
    }
}
