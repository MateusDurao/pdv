<?php
/**
 * Acesso aos dados de produtos (padrão Repository).
 */

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Produto;
use InvalidArgumentException;
use PDO;

final class ProdutoRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @return list<Produto>
     */
    public function listar(string $busca = ''): array
    {
        $consulta = $this->pdo->prepare("
            SELECT id, nome, codigo_barras, preco, estoque, emoji, criado_em
            FROM produtos
            WHERE nome LIKE :busca OR codigo_barras LIKE :busca
            ORDER BY nome
        ");
        $consulta->execute(['busca' => '%' . $busca . '%']);

        return array_map(
            static fn (array $linha): Produto => Produto::deArray($linha),
            $consulta->fetchAll()
        );
    }

    public function buscar(int $id): ?Produto
    {
        $consulta = $this->pdo->prepare(
            'SELECT id, nome, codigo_barras, preco, estoque, emoji, criado_em FROM produtos WHERE id = ?'
        );
        $consulta->execute([$id]);

        $linha = $consulta->fetch();

        return $linha === false ? null : Produto::deArray($linha);
    }

    public function criar(Produto $produto): int
    {
        $inserir = $this->pdo->prepare(
            'INSERT INTO produtos (nome, codigo_barras, preco, estoque, emoji) VALUES (?, ?, ?, ?, ?)'
        );
        $inserir->execute([
            $produto->nome,
            $produto->codigoBarras,
            $produto->preco,
            $produto->estoque,
            $produto->emoji,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function atualizar(Produto $produto): void
    {
        if ($produto->id === null) {
            throw new InvalidArgumentException('Não é possível atualizar um produto sem ID.');
        }

        $atualizar = $this->pdo->prepare(
            'UPDATE produtos SET nome = ?, codigo_barras = ?, preco = ?, estoque = ?, emoji = ? WHERE id = ?'
        );
        $atualizar->execute([
            $produto->nome,
            $produto->codigoBarras,
            $produto->preco,
            $produto->estoque,
            $produto->emoji,
            $produto->id,
        ]);
    }

    public function excluir(int $id): void
    {
        $excluir = $this->pdo->prepare('DELETE FROM produtos WHERE id = ?');
        $excluir->execute([$id]);
    }

    public function baixarEstoque(int $id, int $quantidade): void
    {
        $baixar = $this->pdo->prepare(
            'UPDATE produtos SET estoque = MAX(0, estoque - ?) WHERE id = ?'
        );
        $baixar->execute([$quantidade, $id]);
    }
}
