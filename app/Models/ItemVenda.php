<?php
/**
 * Modelo de item de venda.
 */

declare(strict_types=1);

namespace App\Models;

use InvalidArgumentException;

final class ItemVenda
{
    public function __construct(
        public ?int $id,
        public ?int $vendaId,
        public ?int $produtoId,
        public string $nomeProduto,
        public float $preco,
        public int $quantidade,
        public float $subtotal,
    ) {
        $this->validar();
    }

    /**
     * Aceita tanto os dados vindos do frontend (nome) quanto do banco (nome_produto).
     *
     * @param array<string, mixed> $dados
     */
    public static function deArray(array $dados): self
    {
        $quantidade = max(1, (int) ($dados['quantidade'] ?? 1));
        $preco = (float) ($dados['preco'] ?? 0);
        $subtotal = isset($dados['subtotal'])
            ? (float) $dados['subtotal']
            : $preco * $quantidade;

        return new self(
            isset($dados['id']) ? (int) $dados['id'] : null,
            isset($dados['venda_id']) ? (int) $dados['venda_id'] : null,
            isset($dados['produto_id']) && $dados['produto_id'] !== null
                ? (int) $dados['produto_id']
                : null,
            trim((string) ($dados['nome_produto'] ?? $dados['nome'] ?? '')),
            $preco,
            $quantidade,
            $subtotal,
        );
    }

    public function validar(): void
    {
        if ($this->nomeProduto === '') {
            throw new InvalidArgumentException('Item de venda sem nome de produto.');
        }

        if ($this->preco < 0) {
            throw new InvalidArgumentException('O preço do item não pode ser negativo.');
        }

        if ($this->quantidade <= 0) {
            throw new InvalidArgumentException('A quantidade do item deve ser maior que zero.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function paraArray(): array
    {
        return [
            'id' => $this->id,
            'venda_id' => $this->vendaId,
            'produto_id' => $this->produtoId,
            'nome_produto' => $this->nomeProduto,
            'preco' => $this->preco,
            'quantidade' => $this->quantidade,
            'subtotal' => $this->subtotal,
        ];
    }
}
