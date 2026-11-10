/* ============================================================
   PDV Simples - Lógica do frontend
   ============================================================ */

// ---------- Estado geral ----------
let produtosCaixa = [];      // produtos exibidos na grade do caixa
let produtosLista = [];      // produtos exibidos na tabela
let clientesLista = [];      // clientes exibidos na tabela
let relatorioAtual = null;   // último resultado do relatório de vendas
let carrinho = [];
let formaPagamento = 'dinheiro';

const CHAVE_CARRINHO = 'pdv.carrinho';

// ---------- Utilidades ----------

function formatarDinheiro(valor) {
    return 'R$ ' + Number(valor).toFixed(2).replace('.', ',');
}

function escapar(texto) {
    return String(texto)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');
}

let tempoAviso = null;
function mostrarAviso(mensagem, erro = false) {
    const aviso = document.getElementById('aviso');
    aviso.textContent = mensagem;
    aviso.className = 'aviso aparecido' + (erro ? ' erro' : '');
    clearTimeout(tempoAviso);
    tempoAviso = setTimeout(() => aviso.className = 'aviso', 3200);
}

function formatarData(dataSql) {
    const [data, hora] = String(dataSql).split(' ');
    const [ano, mes, dia] = data.split('-');
    return `${dia}/${mes}/${ano} ${(hora || '').slice(0, 5)}`;
}

function dataDeHoje() {
    const agora = new Date();
    const mes = String(agora.getMonth() + 1).padStart(2, '0');
    const dia = String(agora.getDate()).padStart(2, '0');
    return `${agora.getFullYear()}-${mes}-${dia}`;
}

async function pedirJson(url, opcoes = {}) {
    const resposta = await fetch(url, opcoes);

    try {
        return await resposta.json();
    } catch (erro) {
        return { ok: false, erro: 'Resposta inválida do servidor.' };
    }
}

function enviarJson(url, dados = {}, metodo = 'POST') {
    return pedirJson(url, {
        method: metodo,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(dados),
    });
}

// ---------- Persistência do carrinho ----------

function salvarCarrinho() {
    try {
        localStorage.setItem(CHAVE_CARRINHO, JSON.stringify(carrinho));
    } catch (erro) {
        // localStorage indisponível (modo privado, por exemplo): segue sem salvar
    }
}

function restaurarCarrinho() {
    try {
        const salvo = localStorage.getItem(CHAVE_CARRINHO);

        if (!salvo) return;

        const dados = JSON.parse(salvo);

        if (Array.isArray(dados)) {
            carrinho = dados.filter(item =>
                item && Number(item.produto_id) > 0 && Number(item.quantidade) > 0
            );
        }
    } catch (erro) {
        carrinho = [];
    }
}

// ---------- Relógio ----------

function atualizarRelogio() {
    const agora = new Date();
    const hora = agora.toLocaleTimeString('pt-BR');
    const data = agora.toLocaleDateString('pt-BR', { weekday: 'short', day: '2-digit', month: 'short' });
    document.getElementById('relogio').textContent = `${data} · ${hora}`;
}

// ---------- Navegação entre telas ----------

document.querySelectorAll('.navegacao button').forEach(botao => {
    botao.addEventListener('click', () => {
        document.querySelectorAll('.navegacao button').forEach(b => b.classList.remove('ativo'));
        document.querySelectorAll('.tela').forEach(t => t.classList.remove('ativa'));

        botao.classList.add('ativo');
        document.getElementById('tela-' + botao.dataset.tela).classList.add('ativa');

        if (botao.dataset.tela === 'caixa') carregarProdutosCaixa();
        if (botao.dataset.tela === 'produtos') carregarProdutosTabela();
        if (botao.dataset.tela === 'clientes') carregarClientesTabela();
        if (botao.dataset.tela === 'vendas') carregarRelatorio();
    });
});

/* ============================================================
   TELA CAIXA
   ============================================================ */

async function carregarProdutosCaixa(busca = '') {
    const dados = await pedirJson('/api/produtos?busca=' + encodeURIComponent(busca));
    produtosCaixa = dados.produtos || [];
    desenharGrade();
}

function desenharGrade() {
    const grade = document.getElementById('gradeProdutos');
    grade.innerHTML = '';

    if (produtosCaixa.length === 0) {
        grade.innerHTML = '<div class="vazio">Nenhum produto encontrado.</div>';
        return;
    }

    for (const produto of produtosCaixa) {
        const semEstoque = Number(produto.estoque) <= 0;
        const cartao = document.createElement('button');
        cartao.type = 'button';
        cartao.className = 'cartao-produto' + (semEstoque ? ' sem-estoque' : '');
        cartao.disabled = semEstoque;
        cartao.innerHTML = `
            <span class="emoji-produto">${escapar(produto.emoji)}</span>
            <span class="nome">${escapar(produto.nome)}</span>
            <span class="rodape-cartao">
                <span class="preco">${formatarDinheiro(produto.preco)}</span>
                <span class="estoque">${semEstoque ? 'esgotado' : produto.estoque + ' un'}</span>
            </span>`;
        cartao.addEventListener('click', () => adicionarAoCarrinho(produto));
        grade.appendChild(cartao);
    }
}

function adicionarAoCarrinho(produto) {
    const existente = carrinho.find(item => item.produto_id === Number(produto.id));

    if (existente) {
        if (existente.quantidade + 1 > Number(produto.estoque)) {
            mostrarAviso('Estoque insuficiente de ' + produto.nome, true);
            return;
        }
        existente.quantidade++;
        existente.subtotal = existente.quantidade * existente.preco;
    } else {
        carrinho.push({
            produto_id: Number(produto.id),
            nome: produto.nome,
            preco: Number(produto.preco),
            quantidade: 1,
            subtotal: Number(produto.preco),
            estoque: Number(produto.estoque),
        });
    }

    desenharCarrinho();
}

function mudarQuantidade(indice, mudanca) {
    const item = carrinho[indice];

    if (item.quantidade + mudanca > item.estoque) {
        mostrarAviso('Estoque insuficiente de ' + item.nome, true);
        return;
    }

    item.quantidade += mudanca;
    item.subtotal = item.quantidade * item.preco;

    if (item.quantidade <= 0) {
        carrinho.splice(indice, 1);
    }

    desenharCarrinho();
}

function removerItem(indice) {
    carrinho.splice(indice, 1);
    desenharCarrinho();
}

function limparCarrinho() {
    carrinho = [];
    desenharCarrinho();
}

function desenharCarrinho() {
    const lista = document.getElementById('listaCarrinho');
    lista.innerHTML = '';

    if (carrinho.length === 0) {
        lista.innerHTML = '<li class="vazio">Carrinho vazio.<br>Adicione produtos para começar.</li>';
    }

    carrinho.forEach((item, indice) => {
        const li = document.createElement('li');
        li.innerHTML = `
            <span class="item-nome">${escapar(item.nome)}</span>
            <span class="item-subtotal">${formatarDinheiro(item.subtotal)}</span>
            <div class="item-controles">
                <button type="button" onclick="mudarQuantidade(${indice}, -1)">−</button>
                <span class="qtde">${item.quantidade}</span>
                <button type="button" onclick="mudarQuantidade(${indice}, 1)">+</button>
                <button type="button" class="remover" onclick="removerItem(${indice})" title="Remover item">×</button>
            </div>`;
        lista.appendChild(li);
    });

    const quantidade = carrinho.reduce((soma, item) => soma + item.quantidade, 0);
    const bruto = carrinho.reduce((soma, item) => soma + item.subtotal, 0);
    const desconto = Math.max(0, Number(document.getElementById('desconto').value) || 0);
    const total = Math.max(0, bruto - desconto);

    document.getElementById('quantidadeItens').textContent = quantidade;
    document.getElementById('totalVenda').textContent = formatarDinheiro(total);

    salvarCarrinho();
}

// Pagamento
document.querySelectorAll('#formasPagamento button').forEach(botao => {
    botao.addEventListener('click', () => {
        document.querySelectorAll('#formasPagamento button').forEach(b => b.classList.remove('escolhida'));
        botao.classList.add('escolhida');
        formaPagamento = botao.dataset.forma;
    });
});

document.getElementById('desconto').addEventListener('input', desenharCarrinho);
document.getElementById('botaoLimpar').addEventListener('click', limparCarrinho);

async function finalizarVenda() {
    if (carrinho.length === 0) {
        mostrarAviso('O carrinho está vazio!', true);
        return;
    }

    const desconto = Math.max(0, Number(document.getElementById('desconto').value) || 0);

    const corpo = {
        itens: carrinho,
        desconto: desconto,
        forma_pagamento: formaPagamento,
        cliente_id: document.getElementById('clienteVenda').value || null,
    };

    const botao = document.getElementById('botaoFinalizar');
    botao.disabled = true;
    botao.textContent = 'Registrando...';

    try {
        const dados = await enviarJson('/api/vendas', corpo);

        if (dados.ok) {
            mostrarAviso(dados.mensagem + '  Total: ' + dados.total);
            mostrarComemoracao(dados);
            limparCarrinho();
            document.getElementById('desconto').value = 0;
            document.getElementById('clienteVenda').value = '';
            document.getElementById('busca').value = '';
            carregarProdutosCaixa();
        } else {
            mostrarAviso(dados.erro, true);
        }
    } catch (erro) {
        mostrarAviso('Falha ao finalizar a venda.', true);
    } finally {
        botao.disabled = false;
        botao.textContent = 'Finalizar Venda';
    }
}

document.getElementById('botaoFinalizar').addEventListener('click', finalizarVenda);

// Busca do caixa
let esperaBusca = null;
document.getElementById('busca').addEventListener('input', function () {
    clearTimeout(esperaBusca);
    const termo = this.value.trim();
    esperaBusca = setTimeout(() => carregarProdutosCaixa(termo), 250);
});

// Enter na busca: se der exatamente 1 resultado, adiciona
document.getElementById('busca').addEventListener('keydown', async function (evento) {
    if (evento.key !== 'Enter') return;

    const dados = await pedirJson('/api/produtos?busca=' + encodeURIComponent(this.value.trim()));

    if (dados.produtos && dados.produtos.length === 1) {
        adicionarAoCarrinho(dados.produtos[0]);
        this.value = '';
        carregarProdutosCaixa();
    }
});

/* ============================================================
   TELA PRODUTOS
   ============================================================ */

async function carregarProdutosTabela(busca = '') {
    const dados = await pedirJson('/api/produtos?busca=' + encodeURIComponent(busca));
    produtosLista = dados.produtos || [];
    desenharTabelaProdutos();
}

function desenharTabelaProdutos() {
    const corpo = document.getElementById('corpoTabelaProdutos');
    corpo.innerHTML = '';

    document.getElementById('contadorProdutos').textContent = produtosLista.length + ' produto(s)';

    if (produtosLista.length === 0) {
        corpo.innerHTML = '<tr><td colspan="6" class="vazio">Nenhum produto encontrado.</td></tr>';
        return;
    }

    for (const produto of produtosLista) {
        const linha = document.createElement('tr');
        linha.innerHTML = `
            <td style="font-size:20px">${escapar(produto.emoji)}</td>
            <td><strong>${escapar(produto.nome)}</strong></td>
            <td>${escapar(produto.codigo_barras || '—')}</td>
            <td><span class="etiqueta etiqueta-verde">${formatarDinheiro(produto.preco)}</span></td>
            <td>
                <span class="etiqueta ${Number(produto.estoque) > 0 ? 'etiqueta-azul' : 'etiqueta-vermelha'}">
                    ${produto.estoque} un
                </span>
            </td>
            <td>
                <button type="button" class="botao botao-cinza botao-pequeno" data-acao="editar">Editar</button>
                <button type="button" class="botao botao-vermelho botao-pequeno" data-acao="excluir">Excluir</button>
            </td>`;

        linha.querySelector('[data-acao="editar"]').addEventListener('click', () => editarProduto(produto.id));
        linha.querySelector('[data-acao="excluir"]').addEventListener('click', () => excluirProduto(produto));

        corpo.appendChild(linha);
    }
}

async function editarProduto(id) {
    const dados = await pedirJson('/api/produtos/' + id);

    if (!dados.ok) {
        mostrarAviso(dados.erro, true);
        return;
    }

    const produto = dados.produto;

    document.getElementById('produtoId').value = produto.id;
    document.getElementById('campoEmoji').value = produto.emoji || '📦';
    document.getElementById('campoNome').value = produto.nome;
    document.getElementById('campoCodigo').value = produto.codigo_barras || '';
    document.getElementById('campoPreco').value = String(produto.preco).replace('.', ',');
    document.getElementById('campoEstoque').value = produto.estoque;

    document.getElementById('tituloFormulario').textContent = 'Editar produto';
    document.getElementById('botaoSalvarProduto').textContent = 'Salvar alterações';
    document.getElementById('botaoCancelarEdicao').classList.remove('escondido');

    document.getElementById('tela-produtos').scrollIntoView({ behavior: 'smooth' });
    document.getElementById('campoNome').focus();
}

function limparFormularioProduto() {
    document.getElementById('produtoId').value = '';
    document.getElementById('campoEmoji').value = '📦';
    document.getElementById('campoNome').value = '';
    document.getElementById('campoCodigo').value = '';
    document.getElementById('campoPreco').value = '';
    document.getElementById('campoEstoque').value = 0;

    document.getElementById('tituloFormulario').textContent = 'Novo produto';
    document.getElementById('botaoSalvarProduto').textContent = 'Cadastrar';
    document.getElementById('botaoCancelarEdicao').classList.add('escondido');
}

document.getElementById('botaoCancelarEdicao').addEventListener('click', limparFormularioProduto);

document.getElementById('formularioProduto').addEventListener('submit', async (evento) => {
    evento.preventDefault();

    const preco = parseFloat(String(document.getElementById('campoPreco').value).replace(',', '.'));

    if (!(preco > 0)) {
        mostrarAviso('Informe um preço maior que zero.', true);
        return;
    }

    const id = Number(document.getElementById('produtoId').value);

    const dadosProduto = {
        nome: document.getElementById('campoNome').value.trim(),
        codigo_barras: document.getElementById('campoCodigo').value.trim(),
        preco: preco,
        estoque: parseInt(document.getElementById('campoEstoque').value, 10) || 0,
        emoji: document.getElementById('campoEmoji').value.trim() || '📦',
    };

    if (id) {
        dadosProduto.id = id;
    }

    const url = id ? '/api/produtos/' + id : '/api/produtos';
    const metodo = id ? 'PUT' : 'POST';
    const dados = await enviarJson(url, dadosProduto, metodo);

    if (!dados.ok) {
        mostrarAviso(dados.erro, true);
        return;
    }

    mostrarAviso(dados.mensagem);
    limparFormularioProduto();
    carregarProdutosTabela(document.getElementById('buscaProdutos').value.trim());
});

async function excluirProduto(produto) {
    if (!confirm(`Excluir o produto "${produto.nome}"?`)) return;

    const dados = await enviarJson('/api/produtos/' + produto.id, {}, 'DELETE');

    if (!dados.ok) {
        mostrarAviso(dados.erro, true);
        return;
    }

    mostrarAviso(dados.mensagem);
    carregarProdutosTabela(document.getElementById('buscaProdutos').value.trim());
}

let esperaBuscaProdutos = null;
document.getElementById('buscaProdutos').addEventListener('input', function () {
    clearTimeout(esperaBuscaProdutos);
    const termo = this.value.trim();
    esperaBuscaProdutos = setTimeout(() => carregarProdutosTabela(termo), 250);
});

/* ============================================================
   TELA CLIENTES
   ============================================================ */

async function carregarClientesTabela(busca = '') {
    const dados = await pedirJson('/api/clientes?busca=' + encodeURIComponent(busca));

    if (!dados.ok) {
        mostrarAviso(dados.erro, true);
        return;
    }

    clientesLista = dados.clientes || [];
    desenharTabelaClientes();
}

function desenharTabelaClientes() {
    const corpo = document.getElementById('corpoTabelaClientes');
    corpo.innerHTML = '';

    document.getElementById('contadorClientes').textContent = clientesLista.length + ' cliente(s)';

    if (clientesLista.length === 0) {
        corpo.innerHTML = '<tr><td colspan="6" class="vazio">Nenhum cliente encontrado.</td></tr>';
        return;
    }

    for (const cliente of clientesLista) {
        const linha = document.createElement('tr');
        linha.innerHTML = `
            <td><strong>${escapar(cliente.nome)}</strong></td>
            <td>${escapar(cliente.cpf || '—')}</td>
            <td>${escapar(cliente.telefone || '—')}</td>
            <td>${escapar(cliente.email || '—')}</td>
            <td>${escapar(cliente.endereco || '—')}</td>
            <td>
                <button type="button" class="botao botao-cinza botao-pequeno" data-acao="editar">Editar</button>
                <button type="button" class="botao botao-vermelho botao-pequeno" data-acao="excluir">Excluir</button>
            </td>`;

        linha.querySelector('[data-acao="editar"]').addEventListener('click', () => editarCliente(cliente.id));
        linha.querySelector('[data-acao="excluir"]').addEventListener('click', () => excluirCliente(cliente));

        corpo.appendChild(linha);
    }
}

async function editarCliente(id) {
    const dados = await pedirJson('/api/clientes/' + id);

    if (!dados.ok) {
        mostrarAviso(dados.erro, true);
        return;
    }

    const cliente = dados.cliente;

    document.getElementById('clienteId').value = cliente.id;
    document.getElementById('campoClienteNome').value = cliente.nome;
    document.getElementById('campoClienteCpf').value = cliente.cpf || '';
    document.getElementById('campoClienteTelefone').value = cliente.telefone || '';
    document.getElementById('campoClienteEmail').value = cliente.email || '';
    document.getElementById('campoClienteEndereco').value = cliente.endereco || '';

    document.getElementById('tituloFormularioCliente').textContent = 'Editar cliente';
    document.getElementById('botaoSalvarCliente').textContent = 'Salvar alterações';
    document.getElementById('botaoCancelarEdicaoCliente').classList.remove('escondido');

    document.getElementById('tela-clientes').scrollIntoView({ behavior: 'smooth' });
    document.getElementById('campoClienteNome').focus();
}

function limparFormularioCliente() {
    document.getElementById('clienteId').value = '';
    document.getElementById('campoClienteNome').value = '';
    document.getElementById('campoClienteCpf').value = '';
    document.getElementById('campoClienteTelefone').value = '';
    document.getElementById('campoClienteEmail').value = '';
    document.getElementById('campoClienteEndereco').value = '';

    document.getElementById('tituloFormularioCliente').textContent = 'Novo cliente';
    document.getElementById('botaoSalvarCliente').textContent = 'Cadastrar';
    document.getElementById('botaoCancelarEdicaoCliente').classList.add('escondido');
}

document.getElementById('botaoCancelarEdicaoCliente').addEventListener('click', limparFormularioCliente);

document.getElementById('formularioCliente').addEventListener('submit', async (evento) => {
    evento.preventDefault();

    const id = Number(document.getElementById('clienteId').value);

    const dadosCliente = {
        nome: document.getElementById('campoClienteNome').value.trim(),
        cpf: document.getElementById('campoClienteCpf').value.trim(),
        telefone: document.getElementById('campoClienteTelefone').value.trim(),
        email: document.getElementById('campoClienteEmail').value.trim(),
        endereco: document.getElementById('campoClienteEndereco').value.trim(),
    };

    if (id) {
        dadosCliente.id = id;
    }

    const url = id ? '/api/clientes/' + id : '/api/clientes';
    const metodo = id ? 'PUT' : 'POST';
    const dados = await enviarJson(url, dadosCliente, metodo);

    if (!dados.ok) {
        mostrarAviso(dados.erro, true);
        return;
    }

    mostrarAviso(dados.mensagem);
    limparFormularioCliente();
    carregarClientesTabela(document.getElementById('buscaClientes').value.trim());
    carregarClientesParaVenda();
});

async function excluirCliente(cliente) {
    if (!confirm(`Excluir o cliente "${cliente.nome}"?`)) return;

    const dados = await enviarJson('/api/clientes/' + cliente.id, {}, 'DELETE');

    if (!dados.ok) {
        mostrarAviso(dados.erro, true);
        return;
    }

    mostrarAviso(dados.mensagem);
    carregarClientesTabela(document.getElementById('buscaClientes').value.trim());
    carregarClientesParaVenda();
}

let esperaBuscaClientes = null;
document.getElementById('buscaClientes').addEventListener('input', function () {
    clearTimeout(esperaBuscaClientes);
    const termo = this.value.trim();
    esperaBuscaClientes = setTimeout(() => carregarClientesTabela(termo), 250);
});

function preencherSelectClientes(idSelect, clientes, rotuloVazio) {
    const select = document.getElementById(idSelect);

    if (!select) return;

    const valorAtual = select.value;

    select.innerHTML = `<option value="">${rotuloVazio}</option>` + clientes
        .map(cliente => `<option value="${cliente.id}">${escapar(cliente.nome)}</option>`)
        .join('');

    if ([...select.options].some(opcao => opcao.value === valorAtual)) {
        select.value = valorAtual;
    }
}

async function carregarClientesParaVenda() {
    const dados = await pedirJson('/api/clientes');

    if (!dados.ok) return;

    const clientes = dados.clientes || [];

    preencherSelectClientes('clienteVenda', clientes, 'Consumidor final');
    preencherSelectClientes('filtroCliente', clientes, 'Todos');
}

/* ============================================================
   TELA RELATÓRIOS DE VENDAS
   ============================================================ */

const nomesFormas = {
    dinheiro: 'Dinheiro',
    pix: 'Pix',
    cartao: 'Cartão',
    fiado: 'Fiado',
};

function filtrosRelatorio() {
    return {
        data_inicio: document.getElementById('filtroDataInicio').value,
        data_fim: document.getElementById('filtroDataFim').value,
        forma_pagamento: document.getElementById('filtroPagamento').value,
        cliente_id: document.getElementById('filtroCliente').value,
    };
}

async function carregarRelatorio() {
    const parametros = new URLSearchParams();
    const filtros = filtrosRelatorio();

    for (const [chave, valor] of Object.entries(filtros)) {
        if (valor) parametros.set(chave, valor);
    }

    const dados = await pedirJson('/api/relatorios/vendas?' + parametros.toString());

    if (!dados.ok) {
        mostrarAviso(dados.erro, true);
        return;
    }

    relatorioAtual = dados;
    desenharRelatorio();
}

function formatarDataCurta(iso) {
    const [ano, mes, dia] = String(iso).split('-');
    return `${dia}/${mes}/${ano}`;
}

function desenharRanking(id, itens, montar) {
    const lista = document.getElementById(id);
    lista.innerHTML = '';

    if (!itens || itens.length === 0) {
        lista.innerHTML = '<div class="vazio">Sem dados no período.</div>';
        return;
    }

    itens.forEach((item, indice) => {
        const { nome, valor } = montar(item);
        const div = document.createElement('div');
        div.className = 'item-ranking';
        div.innerHTML = `
            <span class="ranking-nome">${indice + 1}. ${escapar(nome)}</span>
            <span class="ranking-valor">${valor}</span>`;
        lista.appendChild(div);
    });
}

function desenharRelatorio() {
    if (!relatorioAtual) return;

    const resumo = relatorioAtual.resumo || {};
    const porForma = relatorioAtual.por_forma_pagamento || [];
    const produtos = relatorioAtual.produtos_mais_vendidos || [];
    const vendas = relatorioAtual.vendas || [];

    document.getElementById('resumoQuantidade').textContent = resumo.total_vendas ?? 0;
    document.getElementById('resumoTotal').textContent = formatarDinheiro(resumo.faturamento ?? 0);
    document.getElementById('resumoTicket').textContent = formatarDinheiro(resumo.ticket_medio ?? 0);
    document.getElementById('resumoItens').textContent = resumo.total_itens ?? 0;

    desenharRanking('rankingProdutos', produtos, item => ({
        nome: item.nome_produto,
        valor: `${item.quantidade} un · ${formatarDinheiro(item.total)}`,
    }));

    desenharRanking('rankingPagamentos', porForma, item => ({
        nome: nomesFormas[item.forma_pagamento] || item.forma_pagamento,
        valor: `${item.quantidade} venda(s) · ${formatarDinheiro(item.total)}`,
    }));

    const filtros = filtrosRelatorio();
    let periodo = 'Todo o período';

    if (filtros.data_inicio && filtros.data_fim) {
        periodo = `${formatarDataCurta(filtros.data_inicio)} a ${formatarDataCurta(filtros.data_fim)}`;
    } else if (filtros.data_inicio) {
        periodo = `A partir de ${formatarDataCurta(filtros.data_inicio)}`;
    } else if (filtros.data_fim) {
        periodo = `Até ${formatarDataCurta(filtros.data_fim)}`;
    }

    document.getElementById('periodoRelatorio').textContent = periodo;

    desenharTabelaVendas(vendas);
}

function desenharTabelaVendas(vendas) {
    const corpo = document.getElementById('corpoTabelaVendas');
    corpo.innerHTML = '';

    if (!vendas || vendas.length === 0) {
        corpo.innerHTML = '<tr><td colspan="7" class="vazio">Nenhuma venda encontrada no período.</td></tr>';
        return;
    }

    for (const venda of vendas) {
        const linha = document.createElement('tr');
        linha.innerHTML = `
            <td><strong>#${venda.id}</strong></td>
            <td>${formatarData(venda.criado_em)}</td>
            <td>${escapar(venda.cliente_nome || 'Consumidor final')}</td>
            <td>${nomesFormas[venda.forma_pagamento] || escapar(venda.forma_pagamento)}</td>
            <td>${formatarDinheiro(venda.desconto)}</td>
            <td><span class="etiqueta etiqueta-verde">${formatarDinheiro(venda.total)}</span></td>
            <td><button type="button" class="botao botao-cinza botao-pequeno">Ver</button></td>`;

        linha.querySelector('button').addEventListener('click', () => abrirDetalhesVenda(venda.id));
        corpo.appendChild(linha);
    }
}

document.getElementById('botaoFiltrar').addEventListener('click', carregarRelatorio);

document.getElementById('botaoLimparFiltros').addEventListener('click', () => {
    document.getElementById('filtroDataInicio').value = '';
    document.getElementById('filtroDataFim').value = '';
    document.getElementById('filtroPagamento').value = '';
    document.getElementById('filtroCliente').value = '';
    carregarRelatorio();
});

document.getElementById('botaoExportarCsv').addEventListener('click', exportarRelatorioCsv);

function exportarRelatorioCsv() {
    if (!relatorioAtual || !relatorioAtual.vendas || relatorioAtual.vendas.length === 0) {
        mostrarAviso('Não há vendas para exportar.', true);
        return;
    }

    const linhas = [['Nº', 'Data e hora', 'Cliente', 'Pagamento', 'Desconto', 'Total']];

    for (const venda of relatorioAtual.vendas) {
        linhas.push([
            venda.id,
            formatarData(venda.criado_em),
            venda.cliente_nome || 'Consumidor final',
            nomesFormas[venda.forma_pagamento] || venda.forma_pagamento,
            Number(venda.desconto).toFixed(2).replace('.', ','),
            Number(venda.total).toFixed(2).replace('.', ','),
        ]);
    }

    const csv = '\ufeff' + linhas
        .map(linha => linha.map(campo => `"${String(campo).replaceAll('"', '""')}"`).join(';'))
        .join('\r\n');

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');

    link.href = url;
    link.download = `relatorio-vendas-${dataDeHoje()}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}

async function abrirDetalhesVenda(id) {
    const dados = await pedirJson('/api/vendas/' + id);

    if (!dados.ok) {
        mostrarAviso(dados.erro, true);
        return;
    }

    const venda = dados.venda;

    const linhasItens = venda.itens.map(item => `
        <tr>
            <td>${escapar(item.nome_produto)}</td>
            <td>${formatarDinheiro(item.preco)}</td>
            <td>${item.quantidade}</td>
            <td>${formatarDinheiro(item.subtotal)}</td>
        </tr>`).join('');

    document.getElementById('tituloDetalhes').textContent =
        `Venda #${venda.id} — ${formatarData(venda.criado_em)}`;

    document.getElementById('conteudoDetalhes').innerHTML = `
        <table class="tabela">
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Preço unit.</th>
                    <th>Qtde</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                ${linhasItens}
                <tr>
                    <td colspan="3" style="text-align:right"><strong>Desconto</strong></td>
                    <td>${formatarDinheiro(venda.desconto)}</td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align:right"><strong>Total</strong></td>
                    <td><span class="etiqueta etiqueta-verde">${formatarDinheiro(venda.total)}</span></td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align:right"><strong>Cliente</strong></td>
                    <td>${escapar(venda.cliente_nome || 'Consumidor final')}</td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align:right"><strong>Pagamento</strong></td>
                    <td>${nomesFormas[venda.forma_pagamento] || escapar(venda.forma_pagamento)}</td>
                </tr>
            </tbody>
        </table>`;

    document.getElementById('janelaDetalhes').classList.remove('escondido');
}

document.getElementById('botaoFecharDetalhes').addEventListener('click', () => {
    document.getElementById('janelaDetalhes').classList.add('escondido');
});

document.getElementById('janelaDetalhes').addEventListener('click', (evento) => {
    if (evento.target.id === 'janelaDetalhes') {
        document.getElementById('janelaDetalhes').classList.add('escondido');
    }
});

document.addEventListener('keydown', (evento) => {
    if (evento.key === 'Escape') {
        document.getElementById('janelaDetalhes').classList.add('escondido');
        esconderComemoracao();
    }
});

/* ============================================================
   Comemoração da venda
   ============================================================ */

let tempoComemoracao = null;

function mostrarComemoracao(venda) {
    const fundo = document.getElementById('fundoComemoracao');

    document.getElementById('detalheComemoracao').textContent =
        `Venda #${venda.venda_id} • Total R$ ${venda.total}`;

    fundo.classList.add('ativo');

    clearTimeout(tempoComemoracao);
    tempoComemoracao = setTimeout(esconderComemoracao, 4500);
}

function esconderComemoracao() {
    document.getElementById('fundoComemoracao').classList.remove('ativo');
    clearTimeout(tempoComemoracao);
}

document.getElementById('fundoComemoracao').addEventListener('click', esconderComemoracao);

/* ============================================================
   Início
   ============================================================ */

atualizarRelogio();
setInterval(atualizarRelogio, 1000);

restaurarCarrinho();
desenharCarrinho();
carregarProdutosCaixa();
carregarClientesParaVenda();
document.getElementById('busca').focus();
