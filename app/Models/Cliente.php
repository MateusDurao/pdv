<?php
/**
 * Modelo de Cliente.
 */

declare(strict_types=1);

namespace App\Models;

use InvalidArgumentException;

final class Cliente
{
    public function __construct(
        public ?int $id,
        public string $nome,
        public string $cpf,
        public string $telefone,
        public string $email,
        public string $endereco,
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
            trim((string) ($dados['cpf'] ?? '')),
            trim((string) ($dados['telefone'] ?? '')),
            trim((string) ($dados['email'] ?? '')),
            trim((string) ($dados['endereco'] ?? '')),
            isset($dados['criado_em']) ? (string) $dados['criado_em'] : null,
        );
    }

    public function validar(): void
    {
        if ($this->nome === '') {
            throw new InvalidArgumentException('Informe o nome do cliente.');
        }

        if ($this->email !== '' && filter_var($this->email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Informe um e-mail válido.');
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
            'cpf' => $this->cpf,
            'telefone' => $this->telefone,
            'email' => $this->email,
            'endereco' => $this->endereco,
            'criado_em' => $this->criadoEm,
        ];
    }
}
