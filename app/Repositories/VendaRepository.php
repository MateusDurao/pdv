<?php
/**
 * Acesso aos dados de vendas (padrão Repository).
 */

declare(strict_types=1);

namespace App\Repositories;

use App\Models\ItemVenda;
use App\Models\Venda;
use PDO;
use Throwable;

final class VendaRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @return list<Venda>
     */
    public function ultimas(int $limite = 100): array
    {
        $consulta = $this->pdo->prepare(
            'SELECT v.*, c.nome AS cliente_nome
             FROM vendas v
             LEFT JOIN clientes c ON c.id = v.cliente_id
             ORDER BY v.id DESC
             LIMIT ?'
        );
        $consulta->bindValue(1, $limite, PDO::PARAM_INT);
        $consulta->execute();

        return array_map(
            static fn (array $linha): Venda => Venda::deArray($linha),
            $consulta->fetchAll()
        );
    }

    public function buscar(int $id): ?Venda
    {
        $consulta = $this->pdo->prepare(
            'SELECT v.*, c.nome AS cliente_nome
             FROM vendas v
             LEFT JOIN clientes c ON c.id = v.cliente_id
             WHERE v.id = ?'
        );
        $consulta->execute([$id]);

        $linha = $consulta->fetch();

        if ($linha === false) {
            return null;
        }

        $consultaItens = $this->pdo->prepare(
            'SELECT * FROM itens_venda WHERE venda_id = ? ORDER BY id'
        );
        $consultaItens->execute([$id]);

        $itens = array_map(
            static fn (array $item): ItemVenda => ItemVenda::deArray($item),
            $consultaItens->fetchAll()
        );

        return Venda::deArray($linha, $itens);
    }

    /**
     * Grava a venda e seus itens numa transação, baixando o estoque.
     */
    public function registrar(Venda $venda): int
    {
        $this->pdo->beginTransaction();

        try {
            $gravarVenda = $this->pdo->prepare(
                'INSERT INTO vendas (cliente_id, total, desconto, forma_pagamento) VALUES (?, ?, ?, ?)'
            );
            $gravarVenda->execute([
                $venda->clienteId,
                $venda->total,
                $venda->desconto,
                $venda->formaPagamento->value,
            ]);

            $vendaId = (int) $this->pdo->lastInsertId();

            $gravarItem = $this->pdo->prepare(
                'INSERT INTO itens_venda (venda_id, produto_id, nome_produto, preco, quantidade, subtotal)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $baixarEstoque = $this->pdo->prepare(
                'UPDATE produtos SET estoque = MAX(0, estoque - ?) WHERE id = ?'
            );

            foreach ($venda->itens as $item) {
                $gravarItem->execute([
                    $vendaId,
                    $item->produtoId,
                    $item->nomeProduto,
                    $item->preco,
                    $item->quantidade,
                    $item->subtotal,
                ]);

                if ($item->produtoId !== null) {
                    $baixarEstoque->execute([$item->quantidade, $item->produtoId]);
                }
            }

            $this->pdo->commit();

            return $vendaId;
        } catch (Throwable $erro) {
            $this->pdo->rollBack();
            throw $erro;
        }
    }
}
