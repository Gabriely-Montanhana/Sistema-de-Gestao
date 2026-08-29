<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/bootstrap.php';

$paths = app_paths();
$rootPath = $paths['url'];
$adminPath = $paths['admin_url'];
$assetsPath = $paths['assets_url'];

$pageTitle = $pageTitle ?? 'Tornearia';
$currentPage = $currentPage ?? 'home';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="app-root" content="<?= htmlspecialchars($rootPath) ?>">
    <title><?= htmlspecialchars($pageTitle) ?> | Sistema de Gestão</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= $assetsPath ?>css/shared.css?v=2" rel="stylesheet">
    <link href="<?= $assetsPath ?>css/public.css?v=2" rel="stylesheet">
</head>
<body class="bg-white">
<nav class="navbar navbar-expand-lg navbar-dark public-navbar">
    <div class="container">
        <a class="navbar-brand fw-semibold d-flex align-items-center gap-2" href="<?= $rootPath ?>">
            <i class="bi bi-gear-wide-connected"></i>
            Sistema De Gestão
        </a>
        <button class="navbar-toggler border-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#menuPublico">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="menuPublico">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'home' ? 'active' : '' ?>" href="<?= $rootPath ?>">Início</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'servicos' ? 'active' : '' ?>" href="<?= $rootPath ?>site/pages/servicos.php">Serviços</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'produtos' ? 'active' : '' ?>" href="<?= $rootPath ?>site/pages/produtos.php">Produtos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'clientes' ? 'active' : '' ?>" href="<?= $rootPath ?>site/pages/clientes.php">Clientes</a>
                </li>
                <li class="nav-item ms-lg-2">
                    <a class="btn btn-primary btn-sm" href="<?= $adminPath ?>login.php">Área administrativa</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
