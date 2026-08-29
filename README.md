# Sistema de Gestão

Sistema web de gestão para tornearia, com **dois lados** no mesmo projeto:

- **Site público** — vitrine de serviços, produtos e clientes
- **Admin** — cadastro e indicadores (dashboard, empresas, serviços, estoque)

Os dois usam o mesmo banco MySQL, a mesma conexão PHP e os mesmos helpers.

## Tecnologias

- PHP 8+
- MySQL / MariaDB
- Bootstrap 5 + Bootstrap Icons
- SweetAlert2 + IMask (apenas no admin)
- TypeScript (compilado para `assets/js/`)

## Requisitos

- [XAMPP](https://www.apachefriends.org/) (Apache + MySQL + PHP)
- Node.js (opcional, apenas se for alterar arquivos `.ts`)

## Instalação local

1. Coloque o repositório na pasta do Apache:

```
C:\xampp\htdocs\SistemaDeGestao
```

2. No phpMyAdmin, importe `database/schema.sql`.

3. Configure a conexão com o MySQL:

```bash
copy config\pdo.example.php config\pdo.php
```

Edite `config/pdo.php` com usuário e senha do seu MySQL.

4. (Opcional) Instale dependências TypeScript:

```bash
npm install
npm run build
```

5. Abrir no navegador:

| Área | URL |
|---|---|
| Site público | http://localhost/SistemaDeGestao/ |
| Admin | http://localhost/SistemaDeGestao/admin/ |

## O que é compartilhado e o que é de cada lado

**Usar nos dois**

- `config/` — conexão com o banco (`pdo.php`), caminhos (`bootstrap.php`) e helpers (`formatar_moeda`)
- `database/` — tabelas, views e dados iniciais
- `api/catalogo.php` — leitura pública (empresas ativas, estoque, serviços)
- `assets/css/shared.css` — fonte e reset
- `src/api/client.ts`, `src/utils/formatters.ts`, `src/types/interfaces.ts` — cliente HTTP, moeda e tipos

**Só admin**

- `admin/` — dashboard, CRUD, sidebar
- `api/dashboard.php`, `api/empresas.php` — indicadores e escrita
- `assets/css/admin.css` e `src/pages/` — layout e scripts do painel
- SweetAlert2 e IMask

**Só site público**

- `index.php` + `site/templates/` — vitrine
- `assets/css/public.css` — hero, cards, navbar

## Estrutura do projeto

```
SistemaDeGestao/
├── index.php            # Site público
├── admin/               # Painel administrativo
│   ├── index.php        # Dashboard
│   ├── pages/           # Empresas, serviços, estoque
│   └── templates/
├── site/templates/      # Header e footer da vitrine
├── api/                 # Endpoints JSON
├── assets/              # CSS e JavaScript compilado
├── config/              # PDO, bootstrap e helpers
├── database/            # Scripts SQL
└── src/                 # TypeScript fonte
```

## Licença

Projeto acadêmico / portfólio.
