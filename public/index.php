<?php
/**
 * Front Controller — ponto único de entrada da aplicação.
 *
 * Padrões aplicados:
 *  - Front Controller: toda requisição passa por aqui
 *  - MVC: Controllers -> Models/Repositories -> Views
 *  - Dependency Injection: dependências criadas e injetadas explicitamente
 *  - Repository: acesso ao banco isolado em App\Repositories
 *  - Singleton: conexão única em App\Core\Database
 */

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Controllers\ClienteController;
use App\Controllers\HomeController;
use App\Controllers\ProdutoController;
use App\Controllers\RelatorioController;
use App\Controllers\VendaController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Repositories\ClienteRepository;
use App\Repositories\ProdutoRepository;
use App\Repositories\RelatorioRepository;
use App\Repositories\VendaRepository;

// ----- Injeção de dependências -----
$pdo = Database::instancia()->pdo();

$produtoRepositorio = new ProdutoRepository($pdo);
$clienteRepositorio = new ClienteRepository($pdo);
$vendaRepositorio = new VendaRepository($pdo);
$relatorioRepositorio = new RelatorioRepository($pdo);

$homeController = new HomeController();
$produtoController = new ProdutoController($produtoRepositorio);
$clienteController = new ClienteController($clienteRepositorio);
$vendaController = new VendaController($vendaRepositorio);
$relatorioController = new RelatorioController($relatorioRepositorio);

// ----- Rotas -----
$router = new Router();

$router->get('/', [$homeController, 'index']);

$router->get('/api/produtos', [$produtoController, 'index']);
$router->post('/api/produtos', [$produtoController, 'store']);
$router->get('/api/produtos/{id}', [$produtoController, 'show']);
$router->put('/api/produtos/{id}', [$produtoController, 'update']);
$router->delete('/api/produtos/{id}', [$produtoController, 'destroy']);

$router->get('/api/clientes', [$clienteController, 'index']);
$router->post('/api/clientes', [$clienteController, 'store']);
$router->get('/api/clientes/{id}', [$clienteController, 'show']);
$router->put('/api/clientes/{id}', [$clienteController, 'update']);
$router->delete('/api/clientes/{id}', [$clienteController, 'destroy']);

$router->get('/api/vendas', [$vendaController, 'index']);
$router->post('/api/vendas', [$vendaController, 'store']);
$router->get('/api/vendas/{id}', [$vendaController, 'show']);

$router->get('/api/relatorios/vendas', [$relatorioController, 'vendas']);

// ----- Execução -----
try {
    $router->despachar(Request::capturar());
} catch (InvalidArgumentException $erro) {
    Response::erro($erro->getMessage(), 422);
} catch (Throwable $erro) {
    Response::erro('Erro interno: ' . $erro->getMessage(), 500);
}
