<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/bootstrap.php';
require_once dirname(__DIR__, 2) . '/config/helpers.php';
require_once dirname(__DIR__) . '/includes/catalogo.php';

$empresas = [];
$erroCatalogo = null;

try {
    $empresas = catalogo_empresas(null, false);
} catch (Throwable $e) {
    $erroCatalogo = 'Catálogo indisponível no momento. Verifique a conexão com o banco.';
}

$pageTitle = 'Clientes';
$currentPage = 'clientes';
$pageScript = 'pages/catalogo.js';

require dirname(__DIR__) . '/templates/header.php';
?>

<section class="public-hero public-hero-sm">
    <div class="container">
        <p class="public-hero-kicker text-uppercase small fw-semibold mb-2">Catálogo</p>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-2">
            <h1 class="fw-bold mb-0">Clientes</h1>
            <?php if (!$erroCatalogo && count($empresas) > 0): ?>
                <?php catalogo_barra_filtro([
                    '' => 'Status',
                    'Ativo' => 'Ativas',
                    'Inativo' => 'Inativas',
                ], 'status'); ?>
            <?php endif; ?>
        </div>
        <p class="lead mb-0">Empresas cadastradas no sistema.</p>
    </div>
</section>

<section class="public-section bg-light">
    <div class="container">
        <?php if ($erroCatalogo): ?>
            <div class="alert alert-warning mb-0"><?= htmlspecialchars($erroCatalogo) ?></div>
        <?php else: ?>
            <div class="row g-4">
                <?php if (count($empresas) === 0): ?>
                    <?php lista_vazia('Nenhuma empresa para exibir.'); ?>
                <?php else: ?>
                    <?php foreach ($empresas as $empresa): ?>
                        <?php card_empresa($empresa, true); ?>
                    <?php endforeach; ?>
                    <?php catalogo_filtro_vazio(); ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require dirname(__DIR__) . '/templates/footer.php'; ?>
