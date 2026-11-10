<?php
/**
 * Consultas de relatórios de vendas (padrão Repository).
 */

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Venda;
use PDO;

final class RelatorioRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Monta o WHERE e os parâmetros a partir dos filtros informados.
     *
     * @param array<string, mixed> $filtros
     * @return array{where: string, params: array<string, mixed>}
     */
    private function filtro(array $filtros): array
    {
        $condicoes = [];
        $parametros = [];

        $dataInicio = trim((string) ($filtros['data_inicio'] ?? ''));
        if ($dataInicio !== '') {
            $condicoes[] = 'date(v.criado_em) >= :data_inicio';
            $parametros['data_inicio'] = $dataInicio;
        }

        $dataFim = trim((string) ($filtros['data_fim'] ?? ''));
        if ($dataFim !== '') {
            $condicoes[] = 'date(v.criado_em) <= :data_fim';
            $parametros['data_fim'] = $dataFim;
        }

        $formaPagamento = trim((string) ($filtros['forma_pagamento'] ?? ''));
        if ($formaPagamento !== '') {
            $condicoes[] = 'v.forma_pagamento = :forma_pagamento';
            $parametros['forma_pagamento'] = $formaPagamento;
        }

        $clienteId = (int) ($filtros['cliente_id'] ?? 0);
        if ($clienteId > 0) {
            $condicoes[] = 'v.cliente_id = :cliente_id';
            $parametros['cliente_id'] = $clienteId;
        }

        return [
            'where' => $condicoes === [] ? '' : ' WHERE ' . implode(' AND ', $condicoes),
            'params' => $parametros,
        ];
    }

    /**
     * @param array<string, mixed> $filtros
     * @return list<Venda>
     */
    public function vendas(array $filtros): array
    {
        $filtro = $this->filtro($filtros);

        $consulta = $this->pdo->prepare(
            'SELECT v.*, c.nome AS cliente_nome
             FROM vendas v
             LEFT JOIN clientes c ON c.id = v.cliente_id'
            . $filtro['where']
            . ' ORDER BY v.id DESC'
        );
        $consulta->execute($filtro['params']);

        return array_map(
            static fn (array $linha): Venda => Venda::deArray($linha),
            $consulta->fetchAll()
        );
    }

    /**
     * @param array<string, mixed> $filtros
     * @return array{total_vendas: int, faturamento: float, ticket_medio: float, total_itens: int}
     */
    public function resumo(array $filtros): array
    {
        $filtro = $this->filtro($filtros);

        $consulta = $this->pdo->prepare(
            'SELECT COUNT(*) AS total_vendas,
                    COALESCE(SUM(v.total), 0) AS faturamento,
                    COALESCE(AVG(v.total), 0) AS ticket_medio
             FROM vendas v'
            . $filtro['where']
        );
        $consulta->execute($filtro['params']);
        $resumo = $consulta->fetch() ?: [];

        $consultaItens = $this->pdo->prepare(
            'SELECT COALESCE(SUM(iv.quantidade), 0) AS total_itens
             FROM itens_venda iv
             JOIN vendas v ON v.id = iv.venda_id'
            . $filtro['where']
        );
        $consultaItens->execute($filtro['params']);

        return [
            'total_vendas' => (int) ($resumo['total_vendas'] ?? 0),
            'faturamento' => round((float) ($resumo['faturamento'] ?? 0), 2),
            'ticket_medio' => round((float) ($resumo['ticket_medio'] ?? 0), 2),
            'total_itens' => (int) $consultaItens->fetchColumn(),
        ];
    }

    /**
     * @param array<string, mixed> $filtros
     * @return list<array{forma_pagamento: string, quantidade: int, total: float}>
     */
    public function porFormaPagamento(array $filtros): array
    {
        $filtro = $this->filtro($filtros);

        $consulta = $this->pdo->prepare(
            'SELECT v.forma_pagamento,
                    COUNT(*) AS quantidade,
                    COALESCE(SUM(v.total), 0) AS total
             FROM vendas v'
            . $filtro['where']
            . ' GROUP BY v.forma_pagamento
                ORDER BY total DESC'
        );
        $consulta->execute($filtro['params']);

        return array_map(
            static fn (array $linha): array => [
                'forma_pagamento' => (string) $linha['forma_pagamento'],
                'quantidade' => (int) $linha['quantidade'],
                'total' => round((float) $linha['total'], 2),
            ],
            $consulta->fetchAll()
        );
    }

    /**
     * @param array<string, mixed> $filtros
     * @return list<array{nome_produto: string, quantidade: int, total: float}>
     */
    public function produtosMaisVendidos(array $filtros, int $limite = 10): array
    {
        $filtro = $this->filtro($filtros);

        $consulta = $this->pdo->prepare(
            'SELECT iv.nome_produto,
                    SUM(iv.quantidade) AS quantidade,
                    COALESCE(SUM(iv.subtotal), 0) AS total
             FROM itens_venda iv
             JOIN vendas v ON v.id = iv.venda_id'
            . $filtro['where']
            . ' GROUP BY iv.nome_produto
                ORDER BY quantidade DESC, total DESC
                LIMIT :limite'
        );

        foreach ($filtro['params'] as $chave => $valor) {
            $consulta->bindValue(':' . $chave, $valor);
        }
        $consulta->bindValue(':limite', $limite, PDO::PARAM_INT);
        $consulta->execute();

        return array_map(
            static fn (array $linha): array => [
                'nome_produto' => (string) $linha['nome_produto'],
                'quantidade' => (int) $linha['quantidade'],
                'total' => round((float) $linha['total'], 2),
            ],
            $consulta->fetchAll()
        );
    }
}
