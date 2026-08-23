<?php
declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Sistema de Gestão';
$currentPage = $currentPage ?? 'dashboard';

$scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

if (!isset($rootPath)) {
    if (str_contains($scriptPath, '/pages/')) {
        $rootPath = substr($scriptPath, 0, strpos($scriptPath, '/pages/')) . '/';
    } else {
        $rootPath = rtrim(dirname($scriptPath), '/') . '/';
    }
}

$basePath = $rootPath;
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
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="<?= $rootPath ?>assets/css/style.css?v=2" rel="stylesheet">
</head>
<body class="bg-light overflow-x-hidden">
<div class="container-fluid g-0 overflow-hidden">
    <div class="row g-0 flex-nowrap min-vh-100">

        <!-- Sidebar Desktop -->
        <aside class="col-auto sidebar d-none d-lg-flex flex-column">
            <div class="sidebar-brand">
                <i class="bi bi-gear-wide-connected"></i>
                <div class="sidebar-brand-text">
                    <strong>Sistema de Gestão</strong>
                </div>
            </div>
            <ul class="nav nav-pills flex-column sidebar-nav">
                <li class="nav-item">
                    <a href="<?= $rootPath ?>index.php" class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= $rootPath ?>pages/empresas.php" class="nav-link <?= $currentPage === 'empresas' ? 'active' : '' ?>">
                        <i class="bi bi-building"></i> Empresas
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= $rootPath ?>pages/servicos.php" class="nav-link <?= $currentPage === 'servicos' ? 'active' : '' ?>">
                        <i class="bi bi-tools"></i> Serviços
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= $rootPath ?>pages/estoque.php" class="nav-link <?= $currentPage === 'estoque' ? 'active' : '' ?>">
                        <i class="bi bi-box-seam"></i> Estoque
                    </a>
                </li>
            </ul>
            <div class="sidebar-footer mt-auto">
                <i class="bi bi-gear"></i> Sistema de Gestão<br>
                v1.0.0
            </div>
        </aside>

        <!--  Sidebar Mobile -->
        <div class="offcanvas offcanvas-start sidebar-offcanvas d-lg-none" tabindex="-1" id="sidebarMobile">
            <div class="offcanvas-header border-bottom border-secondary">
                <h5 class="offcanvas-title text-white">Sistema de Gestão</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
            </div>
            <div class="offcanvas-body p-0">
                <ul class="nav nav-pills flex-column sidebar-nav">
                    <li class="nav-item">
                        <a href="<?= $rootPath ?>index.php" class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= $rootPath ?>pages/empresas.php" class="nav-link <?= $currentPage === 'empresas' ? 'active' : '' ?>">
                            <i class="bi bi-building"></i> Empresas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= $rootPath ?>pages/servicos.php" class="nav-link <?= $currentPage === 'servicos' ? 'active' : '' ?>">
                            <i class="bi bi-tools"></i> Serviços
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= $rootPath ?>pages/estoque.php" class="nav-link <?= $currentPage === 'estoque' ? 'active' : '' ?>">
                            <i class="bi bi-box-seam"></i> Estoque
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Conteúdo Principal -->
        <div class="col d-flex flex-column min-vh-100 p-0 main-column">
            <nav class="navbar navbar-light bg-white border-bottom px-3 px-lg-4 py-2">
                <div class="d-flex align-items-center flex-grow-1 min-w-0 me-2">
                    <button class="btn btn-outline-secondary d-lg-none me-2 flex-shrink-0" type="button"
                            data-bs-toggle="offcanvas" data-bs-target="#sidebarMobile">
                        <i class="bi bi-list"></i>
                    </button>
                    <span class="navbar-brand mb-0 fs-6 fw-semibold text-truncate">
                        Sistema de Gestão
                    </span>
                </div>
                <!-- Perfil do Usuário -->
                <div class="ms-auto dropdown">
                    <button class="btn btn-light dropdown-toggle d-flex align-items-center gap-2" type="button"
                            data-bs-toggle="dropdown">
                        <span class="avatar rounded-circle d-inline-flex align-items-center justify-content-center">
                            <i class="bi bi-person-fill"></i>
                        </span>
                        Administrador
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li><span class="dropdown-item-text text-muted small">Perfil em breve</span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= $rootPath ?>index.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                    </ul>
                </div>
            </nav>

            <main class="content flex-grow-1 p-3 p-lg-4">
