# Sistema de Gestão

Sistema web de gestão empresarial, desenvolvido em **PHP**, **MySQL**, **Bootstrap 5** e **TypeScript**.

## Funcionalidades

- Dashboard com indicadores analíticos
- Cadastro de empresas/clientes (CRUD)
- Listagem com editar, ativar e inativar
- Módulos de serviços e estoque (em desenvolvimento)

## Tecnologias

- PHP 8+
- MySQL / MariaDB
- Bootstrap 5 + Bootstrap Icons
- SweetAlert2 + IMask
- TypeScript (compilado para `assets/js/`)

## Requisitos

- [XAMPP](https://www.apachefriends.org/) (Apache + MySQL + PHP)
- Node.js (opcional, apenas se for alterar arquivos `.ts`)

## Instalação local

1. Abaixa a pasta do repositório na pasta do Apache:

Coloque na pasta `htdocs` do XAMPP, se preferir:

```
C:\xampp\htdocs\SistemaDeGestao
```

2. Configure o banco de dados no phpMyAdmin:

- Importe `database/schema.sql`

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

```
http://localhost/SistemaDeGestao/
```

## Estrutura do projeto

```
SistemaDeGestao/
├── api/              # Endpoints JSON (dashboard, etc.)
├── assets/           # CSS e JavaScript compilado
├── config/           # Conexão com banco (pdo.php local)
├── database/         # Scripts SQL
├── pages/            # Páginas do sistema
├── src/              # Código TypeScript fonte
├── templates/        # Header e footer
└── index.php         # Dashboard
```

## Licença

Projeto acadêmico / portfólio.
