<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/bootstrap.php';
require_once dirname(__DIR__, 2) . '/config/helpers.php';
require_once dirname(__DIR__) . '/includes/catalogo.php';

$produtos = [];
$erroCatalogo = null;

try {
    $produtos = catalogo_produtos();
} catch (Throwable $e) {
    $erroCatalogo = 'Catálogo indisponível no momento. Verifique a conexão com o banco.';
}

$pageTitle = 'Produtos';
$currentPage = 'produtos';

require dirname(__DIR__) . '/templates/header.php';
?>

<section class="public-hero public-hero-sm">
    <div class="container">
        <p class="public-hero-kicker text-uppercase small fw-semibold mb-2">Catálogo</p>
        <h1 class="fw-bold mb-2">Produtos</h1>
        <p class="lead mb-0">Itens disponíveis no estoque.</p>
    </div>
</section>

<section class="public-section">
    <div class="container">
        <?php if ($erroCatalogo): ?>
            <div class="alert alert-warning mb-0"><?= htmlspecialchars($erroCatalogo) ?></div>
        <?php else: ?>
            <div class="row g-4">
                <?php if (count($produtos) === 0): ?>
                    <?php lista_vazia('Nenhum produto cadastrado.'); ?>
                <?php else: ?>
                    <?php foreach ($produtos as $produto): ?>
                        <?php card_produto($produto); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require dirname(__DIR__) . '/templates/footer.php'; ?>
