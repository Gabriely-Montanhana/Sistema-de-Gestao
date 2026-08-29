<?php
declare(strict_types=1);

$pageTitle = 'Dashboard';
$currentPage = 'dashboard';
$pageScript = 'pages/dashboard.js';

require __DIR__ . '/templates/header.php';
?>

<div class="page-header mb-4">
    <h2 class="fw-bold mb-1">Indicadores Analíticos</h2>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm kpi-card green h-100">
            <div class="card-body">
                <div class="kpi-icon rounded-3 mb-3"><i class="bi bi-currency-dollar"></i></div>
                <div class="kpi-label text-muted small">Receita Total</div>
                <div class="kpi-value fs-3 fw-bold" id="kpi-receita"><span class="loading">Carregando...</span></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm kpi-card blue h-100">
            <div class="card-body">
                <div class="kpi-icon rounded-3 mb-3"><i class="bi bi-clipboard-data"></i></div>
                <div class="kpi-label text-muted small">Total de Serviços</div>
                <div class="kpi-value fs-3 fw-bold" id="kpi-total-servicos"><span class="loading">...</span></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm kpi-card orange h-100">
            <div class="card-body">
                <div class="kpi-icon rounded-3 mb-3"><i class="bi bi-exclamation-triangle"></i></div>
                <div class="kpi-label text-muted small">Serviços Pendentes</div>
                <div class="kpi-value fs-3 fw-bold" id="kpi-pendentes"><span class="loading">...</span></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm kpi-card red h-100">
            <div class="card-body">
                <div class="kpi-icon rounded-3 mb-3"><i class="bi bi-box"></i></div>
                <div class="kpi-label text-muted small">Produtos Estoque Baixo</div>
                <div class="kpi-value fs-3 fw-bold" id="kpi-estoque-baixo"><span class="loading">...</span></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="card-title mb-0 fw-semibold">
                    <i class="bi bi-bar-chart me-2 text-secondary"></i>Serviços por Status
                </h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Status</th>
                            <th>Quantidade</th>
                            <th>Receita</th>
                        </tr>
                    </thead>
                    <tbody id="tabela-status">
                        <tr><td colspan="3" class="loading text-muted">Carregando...</td></tr>
                    </tbody>
                    <tfoot id="tabela-status-total"></tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="card-title mb-0 fw-semibold">
                    <i class="bi bi-star me-2 text-secondary"></i>Destaques
                </h5>
            </div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex align-items-start gap-3 py-4">
                    <div class="highlight-icon blue rounded-3 flex-shrink-0">
                        <i class="bi bi-trophy"></i>
                    </div>
                    <div>
                        <span class="text-muted small d-block">Produto mais usado:</span>
                        <strong class="text-primary fs-6" id="destaque-produto">...</strong>
                    </div>
                </li>
                <li class="list-group-item d-flex align-items-start gap-3 py-4">
                    <div class="highlight-icon green rounded-3 flex-shrink-0">
                        <i class="bi bi-building"></i>
                    </div>
                    <div>
                        <span class="text-muted small d-block">Empresa com mais serviços:</span>
                        <strong class="text-success fs-6" id="destaque-empresa">...</strong>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</div>

<?php require __DIR__ . '/templates/footer.php'; ?>
