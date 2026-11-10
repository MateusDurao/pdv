<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo ?? 'PDV Simples', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/estilos.css">
</head>
<body>

<header class="cabecalho">
    <div class="marca">
        <div class="logo">🛒</div>
        PDV Simples
    </div>

    <nav class="navegacao">
        <button type="button" data-tela="caixa" class="ativo">Caixa</button>
        <button type="button" data-tela="produtos">Produtos</button>
        <button type="button" data-tela="clientes">Clientes</button>
        <button type="button" data-tela="vendas">Relatórios</button>
    </nav>

    <div class="relogio" id="relogio"></div>
</header>

<main>

    <!-- ================= TELA CAIXA ================= -->
    <section class="tela ativa" id="tela-caixa">
        <div class="gaveta-venda">

            <!-- Produtos -->
            <div class="painel">
                <div class="titulo-painel">
                    <span>Produtos</span>
                    <span class="dica">Clique para adicionar ou digite o código de barras</span>
                </div>

                <div class="campo-busca">
                    <input type="text" id="busca" placeholder="🔍  Buscar por nome ou código de barras..." autocomplete="off">
                </div>

                <div class="grade-produtos" id="gradeProdutos"></div>
            </div>

            <!-- Carrinho -->
            <div class="painel">
                <div class="titulo-painel">
                    <span>🧾 Carrinho</span>
                    <button type="button" class="botao botao-cinza botao-pequeno" id="botaoLimpar">Limpar</button>
                </div>

                <ul class="lista-carrinho" id="listaCarrinho"></ul>

                <div class="area-total">
                    <div class="linha">
                        <span>Itens</span>
                        <span id="quantidadeItens">0</span>
                    </div>
                    <div class="linha">
                        <span>Cliente</span>
                        <select id="clienteVenda" class="campo-cliente">
                            <option value="">Consumidor final</option>
                        </select>
                    </div>
                    <div class="linha">
                        <span>Desconto (R$)</span>
                        <input type="number" id="desconto" value="0" min="0" step="0.01" class="campo-desconto">
                    </div>
                    <div class="valor-final">
                        <span>Total</span>
                        <span class="realce" id="totalVenda">R$ 0,00</span>
                    </div>

                    <div class="formas-pagamento" id="formasPagamento">
                        <button type="button" data-forma="dinheiro" class="escolhida">💵 Dinheiro</button>
                        <button type="button" data-forma="pix">🔵 Pix</button>
                        <button type="button" data-forma="cartao">💳 Cartão</button>
                        <button type="button" data-forma="fiado">📝 Fiado</button>
                    </div>

                    <button type="button" class="botao botao-verde" id="botaoFinalizar">✔ Finalizar Venda</button>
                </div>
            </div>

        </div>
    </section>

    <!-- ================= TELA PRODUTOS ================= -->
    <section class="tela" id="tela-produtos">
        <div class="area-tabela">

            <div class="painel" style="margin-bottom:18px">
                <div class="titulo-painel">
                    <span id="tituloFormulario">➕ Novo produto</span>
                    <button type="button" class="botao botao-cinza botao-pequeno escondido" id="botaoCancelarEdicao">Cancelar edição</button>
                </div>

                <form class="formulario" id="formularioProduto">
                    <input type="hidden" id="produtoId" value="">

                    <div class="campo">
                        <label for="campoEmoji">Emoji</label>
                        <input type="text" id="campoEmoji" value="📦" maxlength="4">
                    </div>

                    <div class="campo campo-grande">
                        <label for="campoNome">Nome</label>
                        <input type="text" id="campoNome" placeholder="Ex.: Café 500g" required>
                    </div>

                    <div class="campo">
                        <label for="campoCodigo">Código de barras</label>
                        <input type="text" id="campoCodigo" placeholder="Opcional">
                    </div>

                    <div class="campo">
                        <label for="campoPreco">Preço (R$)</label>
                        <input type="text" id="campoPreco" inputmode="decimal" placeholder="0,00" required>
                    </div>

                    <div class="campo">
                        <label for="campoEstoque">Estoque</label>
                        <input type="number" id="campoEstoque" value="0" min="0" required>
                    </div>

                    <button type="submit" class="botao botao-primario" id="botaoSalvarProduto">Cadastrar</button>
                </form>
            </div>

            <div class="painel">
                <div class="barra-acoes">
                    <div class="busca-tabela">
                        <input type="text" id="buscaProdutos" placeholder="🔍 Buscar produto...">
                    </div>
                    <span class="contador-tabela" id="contadorProdutos"></span>
                </div>

                <table class="tabela">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Nome</th>
                            <th>Código de barras</th>
                            <th>Preço</th>
                            <th>Estoque</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="corpoTabelaProdutos"></tbody>
                </table>
            </div>

        </div>
    </section>

    <!-- ================= TELA CLIENTES ================= -->
    <section class="tela" id="tela-clientes">
        <div class="area-tabela">

            <div class="painel" style="margin-bottom:18px">
                <div class="titulo-painel">
                    <span id="tituloFormularioCliente">➕ Novo cliente</span>
                    <button type="button" class="botao botao-cinza botao-pequeno escondido" id="botaoCancelarEdicaoCliente">Cancelar edição</button>
                </div>

                <form class="formulario formulario-cliente" id="formularioCliente">
                    <input type="hidden" id="clienteId" value="">

                    <div class="campo campo-grande">
                        <label for="campoClienteNome">Nome</label>
                        <input type="text" id="campoClienteNome" placeholder="Ex.: Maria Silva" required>
                    </div>

                    <div class="campo">
                        <label for="campoClienteCpf">CPF</label>
                        <input type="text" id="campoClienteCpf" placeholder="000.000.000-00">
                    </div>

                    <div class="campo">
                        <label for="campoClienteTelefone">Telefone</label>
                        <input type="text" id="campoClienteTelefone" placeholder="(11) 99999-9999">
                    </div>

                    <div class="campo">
                        <label for="campoClienteEmail">E-mail</label>
                        <input type="email" id="campoClienteEmail" placeholder="cliente@email.com">
                    </div>

                    <div class="campo campo-grande">
                        <label for="campoClienteEndereco">Endereço</label>
                        <input type="text" id="campoClienteEndereco" placeholder="Rua, número, bairro">
                    </div>

                    <button type="submit" class="botao botao-primario" id="botaoSalvarCliente">Cadastrar</button>
                </form>
            </div>

            <div class="painel">
                <div class="barra-acoes">
                    <div class="busca-tabela">
                        <input type="text" id="buscaClientes" placeholder="🔍 Buscar por nome, CPF, telefone ou e-mail...">
                    </div>
                    <span class="contador-tabela" id="contadorClientes"></span>
                </div>

                <table class="tabela">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>CPF</th>
                            <th>Telefone</th>
                            <th>E-mail</th>
                            <th>Endereço</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="corpoTabelaClientes"></tbody>
                </table>
            </div>

        </div>
    </section>

    <!-- ================= TELA RELATÓRIOS DE VENDAS ================= -->
    <section class="tela" id="tela-vendas">
        <div class="area-tabela">

            <div class="painel relatorio-filtros">
                <div class="campo">
                    <label for="filtroDataInicio">Data inicial</label>
                    <input type="date" id="filtroDataInicio">
                </div>

                <div class="campo">
                    <label for="filtroDataFim">Data final</label>
                    <input type="date" id="filtroDataFim">
                </div>

                <div class="campo">
                    <label for="filtroPagamento">Pagamento</label>
                    <select id="filtroPagamento" class="campo-cliente">
                        <option value="">Todos</option>
                        <option value="dinheiro">💵 Dinheiro</option>
                        <option value="pix">🔵 Pix</option>
                        <option value="cartao">💳 Cartão</option>
                        <option value="fiado">📝 Fiado</option>
                    </select>
                </div>

                <div class="campo">
                    <label for="filtroCliente">Cliente</label>
                    <select id="filtroCliente" class="campo-cliente">
                        <option value="">Todos</option>
                    </select>
                </div>

                <div class="relatorio-acoes">
                    <button type="button" class="botao botao-primario botao-pequeno" id="botaoFiltrar">🔎 Filtrar</button>
                    <button type="button" class="botao botao-cinza botao-pequeno" id="botaoLimparFiltros">Limpar</button>
                    <button type="button" class="botao botao-cinza botao-pequeno" id="botaoExportarCsv">⬇ CSV</button>
                </div>
            </div>

            <div class="resumo-dia">
                <div class="cartao-resumo">
                    <div class="titulo">Vendas</div>
                    <div class="valor roxo" id="resumoQuantidade">0</div>
                </div>
                <div class="cartao-resumo">
                    <div class="titulo">Faturamento</div>
                    <div class="valor verde" id="resumoTotal">R$ 0,00</div>
                </div>
                <div class="cartao-resumo">
                    <div class="titulo">Ticket médio</div>
                    <div class="valor" id="resumoTicket">R$ 0,00</div>
                </div>
                <div class="cartao-resumo">
                    <div class="titulo">Itens vendidos</div>
                    <div class="valor" id="resumoItens">0</div>
                </div>
            </div>

            <div class="relatorio-grade">
                <div class="painel">
                    <div class="titulo-painel">
                        <span>🏆 Produtos mais vendidos</span>
                    </div>
                    <div class="lista-ranking" id="rankingProdutos"></div>
                </div>

                <div class="painel">
                    <div class="titulo-painel">
                        <span>💳 Por forma de pagamento</span>
                    </div>
                    <div class="lista-ranking" id="rankingPagamentos"></div>
                </div>
            </div>

            <div class="painel">
                <div class="titulo-painel">
                    <span>📊 Vendas do período</span>
                    <span class="dica" id="periodoRelatorio"></span>
                </div>

                <table class="tabela">
                    <thead>
                        <tr>
                            <th>Nº</th>
                            <th>Data e hora</th>
                            <th>Cliente</th>
                            <th>Pagamento</th>
                            <th>Desconto</th>
                            <th>Total</th>
                            <th>Detalhes</th>
                        </tr>
                    </thead>
                    <tbody id="corpoTabelaVendas"></tbody>
                </table>
            </div>

        </div>
    </section>

</main>

<!-- Janela de detalhes da venda -->
<div class="fundo-janela escondido" id="janelaDetalhes">
    <div class="janela">
        <div class="titulo-painel">
            <span id="tituloDetalhes">🧾 Detalhes da venda</span>
            <button type="button" class="botao botao-cinza botao-pequeno" id="botaoFecharDetalhes">Fechar</button>
        </div>
        <div id="conteudoDetalhes"></div>
    </div>
</div>

<!-- Comemoração da venda -->
<div class="fundo-comemoracao" id="fundoComemoracao">
    <span class="confete"></span>
    <span class="confete"></span>
    <span class="confete"></span>
    <span class="confete"></span>
    <span class="confete"></span>
    <span class="confete"></span>
    <span class="confete"></span>
    <span class="confete"></span>
    <span class="confete"></span>

    <div class="comemoracao">
        <img src="/images/ai-que-delicia.jpg" alt="Ai que delícia, cara" class="imagem-comemoracao">

        <div class="texto-comemoracao">
            <div class="grito">AI QUE DELÍCIA, CARA! 😂</div>
            <p>Venda concluída com sucesso!</p>
            <p class="detalhe-venda-comemoracao" id="detalheComemoracao"></p>
        </div>
    </div>
</div>

<div class="aviso" id="aviso"></div>

<script src="/app.js"></script>
</body>
</html>
