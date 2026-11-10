<?php
/**
 * Conexão com o SQLite.
 *
 * Padrão Singleton: existe apenas uma instância da conexão durante a requisição.
 * Também é responsável por criar as tabelas e semear os produtos de exemplo.
 */

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?self $instancia = null;

    private PDO $pdo;

    private function __construct()
    {
        $caminho = getenv('PDV_DB')
            ?: BASE_PATH . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'pdv.sqlite';

        try {
            $this->pdo = new PDO('sqlite:' . $caminho);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->pdo->exec('PRAGMA foreign_keys = ON');
        } catch (PDOException $erro) {
            throw new RuntimeException('Erro ao abrir o banco de dados: ' . $erro->getMessage(), 0, $erro);
        }

        $this->criarEsquema();
        $this->semearProdutos();
        $this->semearClientes();
    }

    public static function instancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }

        return self::$instancia;
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    private function criarEsquema(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS produtos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nome TEXT NOT NULL,
                codigo_barras TEXT DEFAULT '',
                preco REAL NOT NULL DEFAULT 0,
                estoque INTEGER NOT NULL DEFAULT 0,
                emoji TEXT NOT NULL DEFAULT '📦',
                criado_em TEXT DEFAULT (datetime('now', 'localtime'))
            )
        ");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS clientes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nome TEXT NOT NULL,
                cpf TEXT DEFAULT '',
                telefone TEXT DEFAULT '',
                email TEXT DEFAULT '',
                endereco TEXT DEFAULT '',
                criado_em TEXT DEFAULT (datetime('now', 'localtime'))
            )
        ");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS vendas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                cliente_id INTEGER,
                total REAL NOT NULL DEFAULT 0,
                desconto REAL NOT NULL DEFAULT 0,
                forma_pagamento TEXT NOT NULL DEFAULT 'dinheiro',
                criado_em TEXT DEFAULT (datetime('now', 'localtime'))
            )
        ");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS itens_venda (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                venda_id INTEGER NOT NULL,
                produto_id INTEGER,
                nome_produto TEXT NOT NULL,
                preco REAL NOT NULL,
                quantidade INTEGER NOT NULL,
                subtotal REAL NOT NULL
            )
        ");

        $this->garantirColunaClienteVenda();

        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_produtos_nome ON produtos (nome)');
        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_clientes_nome ON clientes (nome)');
        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_itens_venda_venda ON itens_venda (venda_id)');
        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_vendas_cliente ON vendas (cliente_id)');
    }

    /**
     * Bancos criados antes da tela de clientes não têm a coluna cliente_id.
     * Este método faz a migração sem apagar os dados existentes.
     */
    private function garantirColunaClienteVenda(): void
    {
        $colunas = $this->pdo->query('PRAGMA table_info(vendas)')->fetchAll();

        foreach ($colunas as $coluna) {
            if (($coluna['name'] ?? '') === 'cliente_id') {
                return;
            }
        }

        $this->pdo->exec('ALTER TABLE vendas ADD COLUMN cliente_id INTEGER');
    }

    private function semearProdutos(): void
    {
        $total = (int) $this->pdo->query('SELECT COUNT(*) FROM produtos')->fetchColumn();

        if ($total > 0) {
            return;
        }

        $exemplos = [
            ['Café 500g', '7891234567890', 18.90, 50, '☕'],
            ['Pão Francês (un)', '7891234567891', 0.75, 200, '🥖'],
            ['Leite Integral 1L', '7891234567892', 5.49, 80, '🥛'],
            ['Refrigerante 2L', '7891234567893', 8.99, 40, '🥤'],
            ['Arroz 5kg', '7891234567894', 27.50, 30, '🍚'],
            ['Feijão 1kg', '7891234567895', 8.90, 35, '🫘'],
            ['Açúcar 1kg', '7891234567896', 4.79, 45, '🧂'],
            ['Óleo de Soja 900ml', '7891234567897', 7.29, 25, '🫗'],
        ];

        $inserir = $this->pdo->prepare(
            'INSERT INTO produtos (nome, codigo_barras, preco, estoque, emoji) VALUES (?, ?, ?, ?, ?)'
        );

        foreach ($exemplos as $produto) {
            $inserir->execute($produto);
        }
    }

    private function semearClientes(): void
    {
        $total = (int) $this->pdo->query('SELECT COUNT(*) FROM clientes')->fetchColumn();

        if ($total > 0) {
            return;
        }

        $exemplos = [
            ['Maria Silva', '123.456.789-00', '(11) 98765-4321', 'maria@email.com', 'Rua das Flores, 123'],
            ['João Souza', '987.654.321-00', '(11) 91234-5678', 'joao@email.com', 'Av. Brasil, 456'],
            ['Ana Costa', '', '(11) 99999-0000', 'ana@email.com', 'Rua do Comércio, 789'],
        ];

        $inserir = $this->pdo->prepare(
            'INSERT INTO clientes (nome, cpf, telefone, email, endereco) VALUES (?, ?, ?, ?, ?)'
        );

        foreach ($exemplos as $cliente) {
            $inserir->execute($cliente);
        }
    }
}
