<?php
/**
 * Controller de relatórios de vendas.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Venda;
use App\Repositories\RelatorioRepository;

final class RelatorioController
{
    public function __construct(private RelatorioRepository $relatorios)
    {
    }

    /**
     * GET /api/relatorios/vendas?data_inicio=&data_fim=&forma_pagamento=&cliente_id=
     */
    public function vendas(Request $requisicao): void
    {
        $filtros = [
            'data_inicio' => (string) $requisicao->query('data_inicio', ''),
            'data_fim' => (string) $requisicao->query('data_fim', ''),
            'forma_pagamento' => (string) $requisicao->query('forma_pagamento', ''),
            'cliente_id' => (string) $requisicao->query('cliente_id', ''),
        ];

        $vendas = array_map(
            static fn (Venda $venda): array => $venda->paraArray(),
            $this->relatorios->vendas($filtros)
        );

        Response::sucesso([
            'resumo' => $this->relatorios->resumo($filtros),
            'por_forma_pagamento' => $this->relatorios->porFormaPagamento($filtros),
            'produtos_mais_vendidos' => $this->relatorios->produtosMaisVendidos($filtros),
            'vendas' => $vendas,
        ]);
    }
}
