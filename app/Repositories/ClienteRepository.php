<?php
/**
 * Acesso aos dados de clientes (padrão Repository).
 */

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Cliente;
use InvalidArgumentException;
use PDO;

final class ClienteRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @return list<Cliente>
     */
    public function listar(string $busca = ''): array
    {
        $consulta = $this->pdo->prepare("
            SELECT id, nome, cpf, telefone, email, endereco, criado_em
            FROM clientes
            WHERE nome LIKE :busca
               OR cpf LIKE :busca
               OR telefone LIKE :busca
               OR email LIKE :busca
            ORDER BY nome
        ");
        $consulta->execute(['busca' => '%' . $busca . '%']);

        return array_map(
            static fn (array $linha): Cliente => Cliente::deArray($linha),
            $consulta->fetchAll()
        );
    }

    public function buscar(int $id): ?Cliente
    {
        $consulta = $this->pdo->prepare(
            'SELECT id, nome, cpf, telefone, email, endereco, criado_em FROM clientes WHERE id = ?'
        );
        $consulta->execute([$id]);

        $linha = $consulta->fetch();

        return $linha === false ? null : Cliente::deArray($linha);
    }

    public function criar(Cliente $cliente): int
    {
        $inserir = $this->pdo->prepare(
            'INSERT INTO clientes (nome, cpf, telefone, email, endereco) VALUES (?, ?, ?, ?, ?)'
        );
        $inserir->execute([
            $cliente->nome,
            $cliente->cpf,
            $cliente->telefone,
            $cliente->email,
            $cliente->endereco,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function atualizar(Cliente $cliente): void
    {
        if ($cliente->id === null) {
            throw new InvalidArgumentException('Não é possível atualizar um cliente sem ID.');
        }

        $atualizar = $this->pdo->prepare(
            'UPDATE clientes SET nome = ?, cpf = ?, telefone = ?, email = ?, endereco = ? WHERE id = ?'
        );
        $atualizar->execute([
            $cliente->nome,
            $cliente->cpf,
            $cliente->telefone,
            $cliente->email,
            $cliente->endereco,
            $cliente->id,
        ]);
    }

    public function excluir(int $id): void
    {
        $excluir = $this->pdo->prepare('DELETE FROM clientes WHERE id = ?');
        $excluir->execute([$id]);
    }

    /**
     * Indica se o cliente já possui vendas registradas.
     * Usado para impedir exclusões que quebrariam o histórico.
     */
    public function possuiVendas(int $id): bool
    {
        $consulta = $this->pdo->prepare('SELECT COUNT(*) FROM vendas WHERE cliente_id = ?');
        $consulta->execute([$id]);

        return (int) $consulta->fetchColumn() > 0;
    }
}
