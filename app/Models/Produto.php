<?php
/**
 * Modelo de Produto.
 */

declare(strict_types=1);

namespace App\Models;

use InvalidArgumentException;

final class Produto
{
    public function __construct(
        public ?int $id,
        public string $nome,
        public string $codigoBarras,
        public float $preco,
        public int $estoque,
        public string $emoji,
        public ?string $criadoEm = null,
    ) {
        $this->validar();
    }

    /**
     * @param array<string, mixed> $dados
     */
    public static function deArray(array $dados): self
    {
        return new self(
            isset($dados['id']) ? (int) $dados['id'] : null,
            trim((string) ($dados['nome'] ?? '')),
            trim((string) ($dados['codigo_barras'] ?? '')),
            (float) ($dados['preco'] ?? 0),
            (int) ($dados['estoque'] ?? 0),
            trim((string) ($dados['emoji'] ?? '')) ?: '📦',
            isset($dados['criado_em']) ? (string) $dados['criado_em'] : null,
        );
    }

    public function validar(): void
    {
        if ($this->nome === '') {
            throw new InvalidArgumentException('Informe o nome do produto.');
        }

        if ($this->preco <= 0) {
            throw new InvalidArgumentException('Informe um preço maior que zero.');
        }

        if ($this->estoque < 0) {
            throw new InvalidArgumentException('O estoque não pode ser negativo.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function paraArray(): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'codigo_barras' => $this->codigoBarras,
            'preco' => $this->preco,
            'estoque' => $this->estoque,
            'emoji' => $this->emoji,
            'criado_em' => $this->criadoEm,
        ];
    }
}
