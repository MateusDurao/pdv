# PDV Simples — MVC + POO + Design Patterns

Sistema de Ponto de Venda (PDV) desenvolvido em **PHP 8 + SQLite**, com interface moderna, código todo em português e os três CRUDs pedidos no trabalho:

1. **Cadastro de Clientes**
2. **Carrinho de Vendas**
3. **Relatórios de Vendas**

O projeto aplica **arquitetura MVC**, **Programação Orientada a Objetos (POO)** e **Design Patterns** clássicos, sem frameworks externos.

---

## 1. Requisitos

- **PHP 8.1 ou superior** com a extensão `pdo_sqlite`
- Navegador moderno
- (Opcional) Apache com `mod_rewrite` para deploy em produção

---

## 2. Como rodar

Na pasta do projeto:

```powershell
php -S localhost:8090 -t public public/router.php
```

Abra: **http://localhost:8090**

> Também existe o `iniciar.bat`: basta dar dois cliques.

Na primeira execução o banco `database/pdv.sqlite` é criado automaticamente com as tabelas, 8 produtos e 3 clientes de exemplo.

Para usar outro banco (útil em testes), defina a variável de ambiente `PDV_DB`:

```powershell
$env:PDV_DB = "C:\temp\teste.sqlite"
php -S localhost:8091 -t public public/router.php
```

---

## 3. Arquitetura MVC

O fluxo de uma requisição é sempre o mesmo:

```
Navegador
   │
   ▼
public/index.php          ← Front Controller (ponto único de entrada)
   │
   ▼
app/Core/Router.php       ← escolhe a rota e chama o Controller
   │
   ▼
app/Controllers/*.php     ← recebe a requisição, valida e orquestra
   │
   ├──► app/Models/*.php        ← regras de negócio e validação (POO)
   │
   └──► app/Repositories/*.php  ← acesso ao banco (SQL isolado)
             │
             ▼
        SQLite (database/pdv.sqlite)
   │
   ▼
app/Views/home.php        ← camada de visualização (HTML)
public/app.js             ← consome a API REST e atualiza a tela
```

### Camadas

| Camada | Pasta | Responsabilidade |
|---|---|---|
| **Model** | `app/Models` | Representa os dados e valida as regras de negócio (`Produto`, `Cliente`, `Venda`, `ItemVenda`) |
| **View** | `app/Views` | Somente apresentação. A tela única `home.php` contém Caixa, Produtos, Clientes e Relatórios |
| **Controller** | `app/Controllers` | Recebe a requisição, chama Models/Repositories e devolve JSON |
| **Repository** | `app/Repositories` | Todo o SQL fica aqui; o Controller nunca fala com o PDO diretamente |
| **Core** | `app/Core` | Infraestrutura: banco, requisição, resposta, rotas e renderização |

### Separação frontend/backend

O backend expõe uma **API REST em JSON**. O frontend (`public/app.js`) consome essa API com `fetch`, mantendo a View independente da persistência. Isso caracteriza uma SPA (Single Page Application) servida por um único Front Controller.

---

## 4. Programação Orientada a Objetos (POO)

O projeto usa POO de ponta a ponta:

- **Classes e objetos**: `Produto`, `Cliente`, `Venda`, `ItemVenda`, `ProdutoRepository`, `ClienteController`, etc.
- **Encapsulamento**: atributos e regras ficam dentro das classes; o acesso ao banco é privado nos Repositories.
- **Abstração**: cada classe expõe apenas o necessário (`paraArray()`, `validar()`, `listar()`, `criar()`...).
- **Construtores e tipagem forte**: `declare(strict_types=1)` e tipos em parâmetros/retornos.
- **Enumeração (PHP 8.1+)**: `App\Enums\FormaPagamento` representa as formas de pagamento válidas.
- **Composição**: uma `Venda` é composta por vários `ItemVenda`.
- **Tratamento de exceções**: `InvalidArgumentException` para dados inválidos e `Throwable` para erros internos.

> **POO x POP:** o trabalho cita “POP de implantação”. Aqui a implementação é **POO** (Programação Orientada a Objetos), e o **POP** é atendido pelo **processo de implantação** descrito na seção 9 (instalação, configuração do servidor e publicação no GitHub).

---

## 5. Design Patterns adotados

| Padrão | Onde está | Para que serve |
|---|---|---|
| **Front Controller** | `public/index.php` | Centraliza todas as requisições em um único ponto de entrada |
| **MVC** | `app/Models`, `app/Views`, `app/Controllers` | Separa dados, apresentação e controle |
| **Repository** | `app/Repositories/*.php` | Isola o SQL do restante da aplicação e facilita trocar o banco |
| **Singleton** | `app/Core/Database.php` | Garante uma única conexão PDO por requisição |
| **Dependency Injection** | `public/index.php` | Cria os Repositories e injeta nos Controllers via construtor |
| **Router** | `app/Core/Router.php` | Mapeia método + URL para um Controller, inclusive com parâmetros (`{id}`) |
| **Template View** | `app/Core/View.php` | Renderiza arquivos PHP de `app/Views` com dados extraídos |
| **Data Mapper (simplificado)** | `Model::deArray()` / `Model::paraArray()` | Converte linhas do banco em objetos e objetos em arrays JSON |
| **Transaction Script** | `VendaRepository::registrar()` | Grava venda + itens + baixa de estoque dentro de uma transação |

---

## 6. CRUDs implementados

### 6.1 Cadastro de Clientes — CRUD completo

Tela **Clientes**, com formulário de cadastro/edição e tabela com busca.

| Operação | Como funciona |
|---|---|
| **Create** | Formulário → `POST /api/clientes` → `Cliente` valida nome/e-mail → `ClienteRepository::criar()` |
| **Read** | Tabela → `GET /api/clientes?busca=...` e `GET /api/clientes/{id}` |
| **Update** | Botão **Editar** preenche o formulário → `PUT /api/clientes/{id}` |
| **Delete** | Botão **Excluir** → `DELETE /api/clientes/{id}` |

**Regra de negócio:** um cliente com vendas registradas **não pode ser excluído** (HTTP `409`), preservando o histórico. A verificação fica em `ClienteRepository::possuiVendas()`.

### 6.2 Carrinho de Vendas — CRUD dos itens + finalização

O carrinho é o núcleo operacional do PDV. O CRUD é aplicado aos **itens do carrinho**:

| Operação | Como funciona |
|---|---|
| **Create** | Clicar no produto (ou digitar o código e apertar Enter) adiciona o item |
| **Read** | Lista de itens, quantidades, subtotais e total em tempo real |
| **Update** | Botões **+ / −** alteram a quantidade; campo de desconto recalcula o total |
| **Delete** | Botão 🗑 remove um item; botão **Limpar** esvazia o carrinho |

Recursos adicionais:

- **Persistência em `localStorage`**: o carrinho sobrevive a recarregamentos da página.
- **Seleção de cliente** no carrinho (ou “Consumidor final”).
- **Formas de pagamento**: Dinheiro, Pix, Cartão e Fiado.
- **Finalização**: `POST /api/vendas` grava a venda, os itens e baixa o estoque em uma transação.
- **Validação de estoque** antes de adicionar/aumentar itens.

### 6.3 Relatórios de Vendas — consulta, filtros e exportação

Tela **Relatórios**, com filtros e indicadores. Por natureza é uma tela de leitura (não altera dados), então o CRUD aqui é o **Read** completo com recursos de relatório:

| Recurso | Detalhe |
|---|---|
| **Filtro por período** | Data inicial e data final |
| **Filtro por pagamento** | Dinheiro, Pix, Cartão ou Fiado |
| **Filtro por cliente** | Um cliente específico ou todos |
| **Resumo** | Quantidade de vendas, faturamento, ticket médio e itens vendidos |
| **Ranking de produtos** | Produtos mais vendidos no período |
| **Ranking por pagamento** | Distribuição por forma de pagamento |
| **Tabela de vendas** | Vendas do período com cliente e detalhes |
| **Exportação CSV** | Botão **⬇ CSV** gera o arquivo com separador `;` e acentuação correta |

Endpoint: `GET /api/relatorios/vendas?data_inicio=&data_fim=&forma_pagamento=&cliente_id=`

---

## 7. Estrutura do projeto

```
pdv/
├── app/
│   ├── bootstrap.php              # autoload PSR-4 + fuso horário
│   ├── Core/
│   │   ├── Database.php           # Singleton da conexão + schema + seed
│   │   ├── Request.php            # requisição HTTP
│   │   ├── Response.php           # respostas JSON
│   │   ├── Router.php             # rotas com parâmetros
│   │   └── View.php               # renderização das views
│   ├── Controllers/
│   │   ├── HomeController.php
│   │   ├── ProdutoController.php
│   │   ├── ClienteController.php
│   │   ├── VendaController.php
│   │   └── RelatorioController.php
│   ├── Models/
│   │   ├── Produto.php
│   │   ├── Cliente.php
│   │   ├── Venda.php
│   │   └── ItemVenda.php
│   ├── Enums/
│   │   └── FormaPagamento.php
│   ├── Repositories/
│   │   ├── ProdutoRepository.php
│   │   ├── ClienteRepository.php
│   │   ├── VendaRepository.php
│   │   └── RelatorioRepository.php
│   └── Views/
│       └── home.php               # tela única (Caixa, Produtos, Clientes, Relatórios)
├── database/
│   └── pdv.sqlite                 # criado automaticamente (não versionar)
├── public/
│   ├── index.php                  # Front Controller
│   ├── router.php                 # router do servidor embutido
│   ├── .htaccess                  # reescrita para Apache
│   ├── app.js                     # lógica do frontend
│   ├── estilos.css                # tema escuro
│   └── images/
│       └── ai-que-delicia.jpg     # comemoração da venda
├── iniciar.bat
├── .gitignore
└── README.md
```

---

## 8. API REST

Todas as respostas são JSON no formato `{ "ok": true, ... }` ou `{ "ok": false, "erro": "..." }`.

### Produtos

| Método | Rota | Ação |
|---|---|---|
| GET | `/api/produtos?busca=...` | Lista/busca produtos |
| GET | `/api/produtos/{id}` | Busca um produto |
| POST | `/api/produtos` | Cadastra produto |
| PUT | `/api/produtos/{id}` | Atualiza produto |
| DELETE | `/api/produtos/{id}` | Exclui produto |

### Clientes

| Método | Rota | Ação |
|---|---|---|
| GET | `/api/clientes?busca=...` | Lista/busca clientes |
| GET | `/api/clientes/{id}` | Busca um cliente |
| POST | `/api/clientes` | Cadastra cliente |
| PUT | `/api/clientes/{id}` | Atualiza cliente |
| DELETE | `/api/clientes/{id}` | Exclui cliente (bloqueado se tiver vendas) |

### Vendas / Carrinho

| Método | Rota | Ação |
|---|---|---|
| GET | `/api/vendas` | Lista as últimas vendas |
| GET | `/api/vendas/{id}` | Detalhes da venda + itens + cliente |
| POST | `/api/vendas` | Finaliza a venda (grava itens e baixa estoque) |

### Relatórios

| Método | Rota | Ação |
|---|---|---|
| GET | `/api/relatorios/vendas` | Relatório com resumo, rankings e vendas filtradas |

Exemplo de corpo para finalizar uma venda:

```json
{
  "cliente_id": 1,
  "forma_pagamento": "pix",
  "desconto": 2.5,
  "itens": [
    { "produto_id": 1, "nome": "Café 500g", "preco": 18.9, "quantidade": 2, "subtotal": 37.8 }
  ]
}
```

---

## 9. Implantação (POP)

### 9.1 Ambiente de desenvolvimento (PHP embutido)

```powershell
php -S localhost:8090 -t public public/router.php
```

O document root é `public/`, e o `router.php` entrega arquivos estáticos e encaminha o restante ao Front Controller.

### 9.2 Apache / XAMPP

1. Copie o projeto para `htdocs/pdv`.
2. Configure o **DocumentRoot** (ou VirtualHost) para a pasta `public/`.
3. Garanta `mod_rewrite` habilitado.
4. O `.htaccess` já redireciona rotas inexistentes para `index.php`.
5. Dê permissão de escrita na pasta `database/` para o SQLite.

### 9.3 Publicação no GitHub

```powershell
cd C:\Users\00508036\pdv
git init
git add .
git commit -m "PDV com MVC, POO, design patterns e CRUDs de clientes, carrinho e relatorios"
git branch -M main
git remote add origin https://github.com/SEU-USUARIO/pdv.git
git push -u origin main
```

> O banco `database/pdv.sqlite` é ignorado pelo `.gitignore`; ele é recriado automaticamente na primeira execução.

---

## 10. Testes realizados

- `php -l` em todos os arquivos PHP: **0 erros**
- `node --check` no `app.js`: **OK**
- CRUD de produtos: criar → ler → atualizar → excluir → `404`
- CRUD de clientes: criar → ler → atualizar → excluir → `404`
- Validação de cliente inválido: HTTP `422`
- Exclusão de cliente com venda: HTTP `409`
- Venda com cliente: total, itens, cliente e baixa de estoque corretos
- Relatórios: filtros por cliente, pagamento e data funcionando
- Carrinho vazio: HTTP `400`

---

## 11. Resetar os dados

Apague `database/pdv.sqlite` e recarregue a página. O banco será recriado com os produtos e clientes de exemplo.

---

## 12. Funcionalidades da interface

- **Caixa**: grade de produtos, busca por nome/código, carrinho com quantidades, desconto, cliente, formas de pagamento e finalização.
- **Produtos**: CRUD completo com busca.
- **Clientes**: CRUD completo com busca e regra de integridade.
- **Relatórios**: filtros, cartões de resumo, rankings, tabela detalhada e exportação CSV.
- **Comemoração**: ao finalizar a venda, a imagem “AI QUE DELÍCIA, CARA!” é exibida.
