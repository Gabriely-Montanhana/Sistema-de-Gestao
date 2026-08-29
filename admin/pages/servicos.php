<?php
declare(strict_types=1);

$pageTitle = 'Serviços';
$currentPage = 'servicos';

require __DIR__ . '/../templates/header.php';
?>

<div class="page-header mb-4">
    <h2 class="fw-bold mb-1">Serviços</h2>
    <p class="text-muted mb-0">Pedidos e orçamentos da tornearia</p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5">
        <i class="bi bi-tools display-4 text-secondary mb-3"></i>
        <h3 class="h4">CRUD de Serviços</h3>
        <p class="text-muted mb-4">Em breve — criação de pedidos com status e valor total.</p>
        <div class="alert alert-warning d-inline-block mb-0" role="alert">
            <i class="bi bi-tools me-2"></i>Página preparada com componentes Bootstrap
        </div>
    </div>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>
