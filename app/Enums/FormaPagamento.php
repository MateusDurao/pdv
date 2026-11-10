<?php
/**
 * Formas de pagamento aceitas pelo PDV.
 */

declare(strict_types=1);

namespace App\Enums;

enum FormaPagamento: string
{
    case Dinheiro = 'dinheiro';
    case Pix = 'pix';
    case Cartao = 'cartao';
    case Fiado = 'fiado';

    public static function de(string $valor): self
    {
        return self::tryFrom($valor) ?? self::Dinheiro;
    }

    /**
     * @return list<string>
     */
    public static function valores(): array
    {
        return array_map(
            static fn (self $forma): string => $forma->value,
            self::cases()
        );
    }
}
