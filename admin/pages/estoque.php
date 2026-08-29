<?php
declare(strict_types=1);

$pageTitle = 'Estoque';
$currentPage = 'estoque';

require __DIR__ . '/../templates/header.php';
?>

<div class="page-header mb-4">
    <h2 class="fw-bold mb-1">Estoque</h2>
    <p class="text-muted mb-0">Controle de produtos fabricados</p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5">
        <i class="bi bi-box-seam display-4 text-secondary mb-3"></i>
        <h3 class="h4">CRUD de Estoque</h3>
        <p class="text-muted mb-4">Em breve — produtos, preços e quantidades.</p>
        <div class="alert alert-success d-inline-block mb-0" role="alert">
            <i class="bi bi-box-seam me-2"></i>Página preparada com componentes Bootstrap
        </div>
    </div>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>
