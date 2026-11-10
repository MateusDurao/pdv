<?php
/**
 * Modelo de Venda.
 */

declare(strict_types=1);

namespace App\Models;

use App\Enums\FormaPagamento;
use InvalidArgumentException;

final class Venda
{
    /**
     * @param list<ItemVenda> $itens
     */
    public function __construct(
        public ?int $id,
        public float $total,
        public float $desconto,
        public FormaPagamento $formaPagamento,
        public ?int $clienteId = null,
        public ?string $clienteNome = null,
        public ?string $criadoEm = null,
        public array $itens = [],
    ) {
    }

    /**
     * Monta uma venda nova, calculando o total a partir dos itens.
     *
     * @param list<ItemVenda> $itens
     */
    public static function nova(
        array $itens,
        float $desconto,
        string $formaPagamento,
        ?int $clienteId = null,
    ): self {
        $desconto = max(0, $desconto);

        $bruto = array_reduce(
            $itens,
            static fn (float $soma, ItemVenda $item): float => $soma + $item->subtotal,
            0.0
        );

        return new self(
            null,
            max(0, $bruto - $desconto),
            $desconto,
            FormaPagamento::de($formaPagamento),
            $clienteId,
            null,
            null,
            $itens,
        );
    }

    /**
     * @param array<string, mixed> $dados
     * @param list<ItemVenda> $itens
     */
    public static function deArray(array $dados, array $itens = []): self
    {
        return new self(
            isset($dados['id']) ? (int) $dados['id'] : null,
            (float) ($dados['total'] ?? 0),
            (float) ($dados['desconto'] ?? 0),
            FormaPagamento::de((string) ($dados['forma_pagamento'] ?? 'dinheiro')),
            isset($dados['cliente_id']) && $dados['cliente_id'] !== null
                ? (int) $dados['cliente_id']
                : null,
            isset($dados['cliente_nome']) ? (string) $dados['cliente_nome'] : null,
            isset($dados['criado_em']) ? (string) $dados['criado_em'] : null,
            $itens,
        );
    }

    public function validar(): void
    {
        if ($this->itens === []) {
            throw new InvalidArgumentException('O carrinho está vazio.');
        }

        if ($this->desconto < 0) {
            throw new InvalidArgumentException('O desconto não pode ser negativo.');
        }

        foreach ($this->itens as $item) {
            $item->validar();
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function paraArray(bool $comItens = false): array
    {
        $dados = [
            'id' => $this->id,
            'cliente_id' => $this->clienteId,
            'cliente_nome' => $this->clienteNome,
            'total' => round($this->total, 2),
            'desconto' => round($this->desconto, 2),
            'forma_pagamento' => $this->formaPagamento->value,
            'criado_em' => $this->criadoEm,
        ];

        if ($comItens) {
            $dados['itens'] = array_map(
                static fn (ItemVenda $item): array => $item->paraArray(),
                $this->itens
            );
        }

        return $dados;
    }
}
