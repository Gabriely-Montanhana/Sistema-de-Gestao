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

1. Clone o repositório na pasta do Apache:

```bash
git clone https://github.com/SEU-USUARIO/Sistema-de-Gestao.git
cd SistemaDeGestao
```

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

5. Acesse no navegador:

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

## Publicar no GitHub

Na pasta do projeto:

```bash
git init
git add .
git commit -m "Initial commit: Sistema de Gestão"
git branch -M main
git remote add origin https://github.com/SEU-USUARIO/Sistema-de-Gestao.git
git push -u origin main
```

> **Importante:** o arquivo `config/pdo.php` não vai para o GitHub (está no `.gitignore`). Cada pessoa copia o `pdo.example.php` na própria máquina.

## Licença

Projeto acadêmico / portfólio.
