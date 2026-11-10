<?php
/**
 * Controller da página inicial (SPA).
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\View;

final class HomeController
{
    public function index(Request $requisicao): void
    {
        View::render('home', ['titulo' => 'PDV Simples']);
    }
}
