<?php

declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/site/includes/catalogo.php';

$paths = app_paths();
$empresas = [];
$produtos = [];
$servicos = [];
$erroCatalogo = null;

try {
    $servicos = catalogo_servicos(3);
    $produtos = catalogo_produtos(4);
    $empresas = catalogo_empresas(2);
} catch (Throwable $e) {
    $erroCatalogo = 'Catálogo indisponível no momento. Verifique a conexão com o banco.';
}

$pageTitle = 'Início';
$currentPage = 'home';

require __DIR__ . '/site/templates/header.php';
?>

<section class="public-hero">
    <div class="container">
        <p class="public-hero-kicker text-uppercase small fw-semibold mb-3">Tornearia Sátelite</p>
        <h1 class="display-5 fw-bold mb-3">Peças, serviços e clientes em um só lugar</h1>
    </div>
</section>

<?php if ($erroCatalogo): ?>
    <div class="container py-5">
        <div class="alert alert-warning mb-0"><?= htmlspecialchars($erroCatalogo) ?></div>
    </div>
<?php endif; ?>

<section id="servicos" class="public-section bg-light">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end gap-3 mb-4">
            <div>
                <h2 class="fw-bold mb-1">Serviços recentes</h2>
                <p class="text-muted mb-0">Pedidos cadastrados</p>
            </div>
            <a class="link-ver-mais" href="<?= htmlspecialchars($paths['url']) ?>site/pages/servicos.php">
                Ver mais <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        <div class="row g-4">
            <?php if (count($servicos) === 0): ?>
                <?php lista_vazia('Nenhum serviço para exibir ainda.'); ?>
            <?php else: ?>
                <?php foreach ($servicos as $servico): ?>
                    <?php card_servico($servico); ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<section id="produtos" class="public-section">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end gap-3 mb-4">
            <div>
                <h2 class="fw-bold mb-1">Produtos</h2>
                <p class="text-muted mb-0">Itens do estoque</p>
            </div>
            <a class="link-ver-mais" href="<?= htmlspecialchars($paths['url']) ?>site/pages/produtos.php">
                Ver mais <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        <div class="row g-4">
            <?php if (count($produtos) === 0): ?>
                <?php lista_vazia('Nenhum produto cadastrado.'); ?>
            <?php else: ?>
                <?php foreach ($produtos as $produto): ?>
                    <?php card_produto($produto); ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<section id="clientes" class="public-section bg-light">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end gap-3 mb-4">
            <div>
                <h2 class="fw-bold mb-1">Clientes</h2>
                <p class="text-muted mb-0">Empresas ativas</p>
            </div>
            <a class="link-ver-mais" href="<?= htmlspecialchars($paths['url']) ?>site/pages/clientes.php">
                Ver mais <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        <div class="row g-4">
            <?php if (count($empresas) === 0): ?>
                <?php lista_vazia('Nenhuma empresa ativa para exibir.'); ?>
            <?php else: ?>
                <?php foreach ($empresas as $empresa): ?>
                    <?php card_empresa($empresa); ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/site/templates/footer.php'; ?>
