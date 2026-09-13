<?php

declare(strict_types=1);

function catalogo_pdo(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        require_once dirname(__DIR__, 2) . '/config/pdo.php';
        $pdo = getConnection();
    }

    return $pdo;
}

function catalogo_servicos(?int $limit = null): array
{
    $sql = "
        SELECT s.descricao, s.status, s.valor_total, s.data_servico, e.nome_empresa
        FROM servicos s
        INNER JOIN empresas e ON e.id_empresa = s.id_empresa
        WHERE s.status IN ('Pendente', 'Em Andamento', 'Concluído')
        ORDER BY s.data_servico DESC
    ";

    if ($limit !== null) {
        $sql .= ' LIMIT ' . $limit;
    }

    return catalogo_pdo()->query($sql)->fetchAll();
}

function catalogo_produtos(?int $limit = null): array
{
    $sql = '
        SELECT nome_produto, preco, quantidade
        FROM estoque
        ORDER BY nome_produto
    ';

    if ($limit !== null) {
        $sql .= ' LIMIT ' . $limit;
    }

    return catalogo_pdo()->query($sql)->fetchAll();
}

function catalogo_empresas(?int $limit = null, bool $somenteAtivas = true): array
{
    $sql = '
        SELECT nome_empresa, cidade, endereco, telefone, email, status
        FROM empresas
    ';

    if ($somenteAtivas) {
        $sql .= " WHERE status = 'Ativo'";
    }

    $sql .= ' ORDER BY nome_empresa';

    if ($limit !== null) {
        $sql .= ' LIMIT ' . $limit;
    }

    return catalogo_pdo()->query($sql)->fetchAll();
}

function badge_status_servico(string $status): string
{
    return match ($status) {
        'Pendente' => 'text-bg-warning',
        'Em Andamento' => 'text-bg-primary',
        'Concluído' => 'text-bg-success',
        default => 'text-bg-secondary',
    };
}

function formatar_data(?string $data): string
{
    if ($data === null || trim($data) === '') {
        return '—';
    }

    $dt = DateTime::createFromFormat('Y-m-d', $data);

    return $dt instanceof DateTime ? $dt->format('d/m/Y') : htmlspecialchars($data);
}

function catalogo_barra_filtro(array $opcoesSelect, string $campoSelect, string $placeholder = 'Buscar'): void
{
    ?>
    <div class="d-flex flex-nowrap gap-2" id="catalogo-filtro"
         data-campo-texto="busca" data-campo-select="<?= htmlspecialchars($campoSelect) ?>">
        <input type="search" class="form-control form-control-sm" id="filtro-catalogo-busca"
               placeholder="<?= htmlspecialchars($placeholder) ?>" autocomplete="off" style="width: 180px;">
        <select class="form-select form-select-sm" id="filtro-catalogo-select" style="width: 150px;">
            <?php foreach ($opcoesSelect as $valor => $label): ?>
                <option value="<?= htmlspecialchars((string) $valor) ?>"><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php
}

function catalogo_filtro_vazio(): void
{
    ?>
    <div id="filtro-catalogo-vazio" class="col-12 d-none">
        <div class="alert alert-light border mb-0">Nenhum resultado com esse filtro.</div>
    </div>
    <?php
}

function card_servico(array $servico, bool $completo = false): void
{
    $busca = mb_strtolower(trim((string) ($servico['descricao'] ?? '') . ' ' . (string) ($servico['nome_empresa'] ?? '')));
    ?>
    <div class="col-md-6 col-lg-4 item-catalogo"
         data-busca="<?= htmlspecialchars($busca) ?>"
         data-status="<?= htmlspecialchars((string) ($servico['status'] ?? '')) ?>">
        <div class="card catalog-card shadow-sm">
            <div class="card-body">
                <div class="catalog-icon mb-3"><i class="bi bi-tools"></i></div>
                <h3 class="h5 fw-semibold"><?= e($servico['descricao'] ?? null, 'Serviço') ?></h3>
                <p class="text-muted small mb-2"><?= e($servico['nome_empresa'] ?? null) ?></p>
                <?php if ($completo): ?>
                    <p class="text-muted small mb-3">Data: <?= formatar_data($servico['data_servico'] ?? null) ?></p>
                <?php endif; ?>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="badge <?= badge_status_servico((string) ($servico['status'] ?? '')) ?>">
                        <?= e($servico['status'] ?? null) ?>
                    </span>
                    <strong><?= formatar_moeda($servico['valor_total'] ?? 0) ?></strong>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function card_produto(array $produto): void
{
    $quantidade = (int) ($produto['quantidade'] ?? 0);
    $nivel = $quantidade <= 5 ? 'baixo' : 'normal';
    ?>
    <div class="col-md-6 col-lg-3 item-catalogo"
         data-busca="<?= htmlspecialchars(mb_strtolower((string) ($produto['nome_produto'] ?? ''))) ?>"
         data-nivel="<?= $nivel ?>">
        <div class="card catalog-card shadow-sm">
            <div class="card-body">
                <div class="catalog-icon mb-3"><i class="bi bi-box-seam"></i></div>
                <h3 class="h5 fw-semibold"><?= e($produto['nome_produto'] ?? null) ?></h3>
                <p class="fs-5 fw-bold text-primary mb-1"><?= formatar_moeda($produto['preco'] ?? 0) ?></p>
                <p class="text-muted small mb-0">
                    <?= (int) ($produto['quantidade'] ?? 0) ?> em estoque
                </p>
            </div>
        </div>
    </div>
    <?php
}

function card_empresa(array $empresa, bool $completo = false): void
{
    $busca = mb_strtolower(trim((string) ($empresa['nome_empresa'] ?? '') . ' ' . (string) ($empresa['cidade'] ?? '')));
    ?>
    <div class="col-md-6 item-catalogo"
         data-busca="<?= htmlspecialchars($busca) ?>"
         data-status="<?= htmlspecialchars((string) ($empresa['status'] ?? 'Ativo')) ?>">
        <div class="card catalog-card shadow-sm">
            <div class="card-body d-flex gap-3">
                <div class="catalog-icon flex-shrink-0"><i class="bi bi-building"></i></div>
                <div>
                    <h3 class="h5 fw-semibold mb-1">
                        <?= e($empresa['nome_empresa'] ?? null) ?>
                        <?php if (($empresa['status'] ?? 'Ativo') === 'Inativo'): ?>
                            <span class="badge rounded-pill text-bg-secondary fs-6 fw-normal">Inativo</span>
                        <?php endif; ?>
                    </h3>
                    <p class="text-muted small mb-1"><?= e($empresa['cidade'] ?? null) ?></p>
                    <?php if ($completo): ?>
                        <p class="text-muted small mb-1"><?= e($empresa['endereco'] ?? null) ?></p>
                    <?php endif; ?>
                    <p class="small mb-0"><?= e($empresa['telefone'] ?? null) ?> · <?= e($empresa['email'] ?? null) ?></p>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function lista_vazia(string $mensagem): void
{
    ?>
    <div class="col-12">
        <div class="alert alert-light border mb-0"><?= htmlspecialchars($mensagem) ?></div>
    </div>
    <?php
}
