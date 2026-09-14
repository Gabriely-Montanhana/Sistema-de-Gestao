<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../config/pdo.php';
require_once __DIR__ . '/../../config/helpers.php';

function redirectServicos(string $aba = 'cadastro', int $editId = 0): never
{
    $url = 'servicos.php?aba=' . urlencode($aba);

    if ($editId > 0) {
        $url .= '&edit=' . $editId;
    }

    header('Location: ' . $url);
    exit;
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function campoFormulario(string $campo, ?array $servico, array $old): string
{
    if (array_key_exists($campo, $old)) {
        return htmlspecialchars(trim((string) $old[$campo]));
    }

    if ($servico !== null && array_key_exists($campo, $servico)) {
        return htmlspecialchars(trim((string) ($servico[$campo] ?? '')));
    }

    return '';
}

function badge_status_admin(string $status): string
{
    return match ($status) {
        'Pendente' => 'text-bg-warning',
        'Em Andamento' => 'text-bg-primary',
        'Concluído' => 'text-bg-success',
        'Cancelado' => 'text-bg-danger',
        default => 'text-bg-secondary',
    };
}

function statusValido(string $status): bool
{
    return in_array($status, ['Pendente', 'Em Andamento', 'Concluído', 'Cancelado'], true);
}

function produtosDoPost(array $post): array
{
    $ids = array_map('intval', $post['produtos'] ?? []);
    $qtds = $post['qtd'] ?? [];
    $selecionados = [];

    foreach ($ids as $idEstoque) {
        if ($idEstoque <= 0) {
            continue;
        }

        $quantidade = (int) ($qtds[$idEstoque] ?? 1);
        $selecionados[$idEstoque] = $quantidade > 0 ? $quantidade : 1;
    }

    return $selecionados;
}

function sincronizarProdutos(PDO $pdo, int $idServico, array $produtos): void
{
    $stmt = $pdo->prepare('DELETE FROM servico_produtos WHERE id_servico = :id');
    $stmt->execute([':id' => $idServico]);

    if ($produtos === []) {
        return;
    }

    $check = $pdo->prepare('SELECT id_estoque FROM estoque WHERE id_estoque = :id');
    $insert = $pdo->prepare('
        INSERT INTO servico_produtos (id_servico, id_estoque, quantidade)
        VALUES (:servico, :estoque, :quantidade)
    ');

    foreach ($produtos as $idEstoque => $quantidade) {
        $check->execute([':id' => $idEstoque]);

        if (!$check->fetch()) {
            continue;
        }

        $insert->execute([
            ':servico' => $idServico,
            ':estoque' => $idEstoque,
            ':quantidade' => $quantidade,
        ]);
    }
}

$pdo = getConnection();
$statusOpcoes = ['Pendente', 'Em Andamento', 'Concluído', 'Cancelado'];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $acao = trim((string) ($_POST['acao'] ?? 'salvar'));
    $id = 0;

    try {
        if ($acao === 'salvar') {
            $id = (int) ($_POST['id_servico'] ?? 0);
            $idEmpresa = (int) ($_POST['id_empresa'] ?? 0);
            $dataServico = trim((string) ($_POST['data_servico'] ?? ''));
            $descricao = html_seguro(trim((string) ($_POST['descricao'] ?? '')));
            $status = trim((string) ($_POST['status'] ?? 'Pendente'));
            $valorTotal = (float) str_replace(',', '.', (string) ($_POST['valor_total'] ?? '0'));
            $produtos = produtosDoPost($_POST);

            if ($idEmpresa <= 0) {
                throw new InvalidArgumentException('Selecione a empresa.');
            }

            if ($dataServico === '' || DateTime::createFromFormat('Y-m-d', $dataServico) === false) {
                throw new InvalidArgumentException('Informe uma data válida.');
            }

            if (trim(strip_tags($descricao)) === '') {
                throw new InvalidArgumentException('A descrição do serviço é obrigatória.');
            }

            if (!statusValido($status)) {
                throw new InvalidArgumentException('Status inválido.');
            }

            if ($valorTotal < 0) {
                throw new InvalidArgumentException('O valor total não pode ser negativo.');
            }

            $stmt = $pdo->prepare('SELECT id_empresa FROM empresas WHERE id_empresa = :id');
            $stmt->execute([':id' => $idEmpresa]);

            if (!$stmt->fetch()) {
                throw new InvalidArgumentException('Empresa não encontrada.');
            }

            $pdo->beginTransaction();

            if ($id > 0) {
                $stmt = $pdo->prepare('SELECT id_servico FROM servicos WHERE id_servico = :id');
                $stmt->execute([':id' => $id]);

                if (!$stmt->fetch()) {
                    $pdo->rollBack();
                    throw new InvalidArgumentException('Serviço não encontrado.');
                }

                $stmt = $pdo->prepare('
                    UPDATE servicos
                    SET id_empresa = :empresa,
                        data_servico = :data,
                        descricao = :descricao,
                        status = :status,
                        valor_total = :valor
                    WHERE id_servico = :id
                ');
                $stmt->execute([
                    ':empresa' => $idEmpresa,
                    ':data' => $dataServico,
                    ':descricao' => $descricao,
                    ':status' => $status,
                    ':valor' => $valorTotal,
                    ':id' => $id,
                ]);

                sincronizarProdutos($pdo, $id, $produtos);
                $pdo->commit();
                setFlash('success', 'Serviço atualizado com sucesso!');
            } else {
                $stmt = $pdo->prepare('
                    INSERT INTO servicos (id_empresa, data_servico, descricao, status, valor_total)
                    VALUES (:empresa, :data, :descricao, :status, :valor)
                ');
                $stmt->execute([
                    ':empresa' => $idEmpresa,
                    ':data' => $dataServico,
                    ':descricao' => $descricao,
                    ':status' => $status,
                    ':valor' => $valorTotal,
                ]);

                $id = (int) $pdo->lastInsertId();
                sincronizarProdutos($pdo, $id, $produtos);
                $pdo->commit();
                setFlash('success', 'Serviço cadastrado com sucesso!');
            }

            redirectServicos('cadastro');
        }

        if ($acao === 'excluir') {
            $id = (int) ($_POST['id_servico'] ?? 0);

            if ($id <= 0) {
                throw new InvalidArgumentException('Serviço inválido.');
            }

            $stmt = $pdo->prepare('DELETE FROM servicos WHERE id_servico = :id');
            $stmt->execute([':id' => $id]);

            if ($stmt->rowCount() === 0) {
                throw new InvalidArgumentException('Serviço não encontrado.');
            }

            setFlash('success', 'Serviço excluído com sucesso!');
            redirectServicos('visualizar');
        }

        throw new InvalidArgumentException('Ação inválida.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        setFlash('error', $e->getMessage());

        if ($acao === 'salvar') {
            $_SESSION['form_old'] = $_POST;
            redirectServicos('cadastro', $id);
        }

        redirectServicos('visualizar');
    }
}

$servicos = $pdo->query("
    SELECT s.*, e.nome_empresa
    FROM servicos s
    INNER JOIN empresas e ON e.id_empresa = s.id_empresa
    ORDER BY FIELD(s.status, 'Em Andamento', 'Pendente', 'Concluído', 'Cancelado'), s.data_servico DESC, s.id_servico DESC
")->fetchAll();

$empresas = $pdo->query("
    SELECT id_empresa, nome_empresa, status
    FROM empresas
    ORDER BY nome_empresa
")->fetchAll();

$produtosEstoque = $pdo->query('
    SELECT id_estoque, nome_produto
    FROM estoque
    ORDER BY nome_produto
')->fetchAll();

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$servicoEdicao = null;

if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM servicos WHERE id_servico = ?');
    $stmt->execute([$editId]);
    $servicoEdicao = $stmt->fetch() ?: null;
}

$aba = $_GET['aba'] ?? 'cadastro';
if ($servicoEdicao !== null) {
    $aba = 'cadastro';
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$formOld = $_SESSION['form_old'] ?? [];
unset($_SESSION['form_old']);

$editando = $servicoEdicao !== null || (int) ($formOld['id_servico'] ?? 0) > 0;

if (!$servicoEdicao && $editando) {
    $editId = (int) ($formOld['id_servico'] ?? 0);
    if ($editId > 0) {
        $stmt = $pdo->prepare('SELECT * FROM servicos WHERE id_servico = ?');
        $stmt->execute([$editId]);
        $servicoEdicao = $stmt->fetch() ?: null;
    }
}

$produtosSelecionados = [];

if ($formOld !== []) {
    $produtosSelecionados = produtosDoPost($formOld);
} elseif ($servicoEdicao !== null) {
    $stmt = $pdo->prepare('SELECT id_estoque, quantidade FROM servico_produtos WHERE id_servico = :id');
    $stmt->execute([':id' => (int) $servicoEdicao['id_servico']]);

    foreach ($stmt->fetchAll() as $item) {
        $produtosSelecionados[(int) $item['id_estoque']] = (int) $item['quantidade'];
    }
}

$idEmpresaAtual = (int) ($formOld['id_empresa'] ?? $servicoEdicao['id_empresa'] ?? 0);
$dataPadrao = campoFormulario('data_servico', $servicoEdicao, $formOld);
if ($dataPadrao === '' && !$editando) {
    $dataPadrao = date('Y-m-d');
}

$statusAtual = campoFormulario('status', $servicoEdicao, $formOld);
if ($statusAtual === '') {
    $statusAtual = 'Pendente';
}

$pageTitle = 'Serviços';
$currentPage = 'servicos';
$pageScript = 'pages/servicos.js';
$pageExtraCss = [
    'https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs5.min.css',
];
$pageExtraJs = [
    'https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js',
    'https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs5.min.js',
    'https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/lang/summernote-pt-BR.min.js',
];

require __DIR__ . '/../templates/header.php';

?>

<?php if ($flash): ?>
    <div id="flash-message" class="d-none"
         data-type="<?= htmlspecialchars($flash['type']) ?>"
         data-message="<?= htmlspecialchars($flash['message']) ?>"></div>
<?php endif; ?>

<div class="page-header mb-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-2">
        <div>
            <h2 class="fw-bold mb-0">Serviços</h2>
            <p class="text-muted mb-0 mt-1">Pedidos e orçamentos da tornearia</p>
        </div>
        <ul class="nav nav-pills mb-0 flex-shrink-0 flex-wrap justify-content-end" id="servicosTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $aba === 'cadastro' ? 'active' : '' ?>" id="tab-adicionar" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#painel-adicionar">
                    <i class="bi bi-plus"></i> Adicionar serviço
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $aba === 'visualizar' ? 'active' : '' ?>" id="tab-visualizar" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#painel-visualizar">
                    <i class="bi bi-eye"></i> Visualizar serviços
                </button>
            </li>
        </ul>
    </div>
</div>

<div class="tab-content">
    <div class="tab-pane fade <?= $aba === 'cadastro' ? 'show active' : '' ?>" id="painel-adicionar" role="tabpanel">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="card-title mb-0 fw-semibold">
                    <i class="bi bi-<?= $editando ? 'pencil-square' : 'tools' ?> me-2 text-secondary"></i>
                    <?= $editando ? 'Editar serviço' : 'Novo serviço' ?>
                </h5>
            </div>
            <div class="card-body cadastro-scroll">
                <?php if (count($empresas) === 0): ?>
                    <div class="alert alert-warning mb-0">
                        Cadastre uma empresa antes de criar um serviço.
                    </div>
                <?php else: ?>
                    <form id="form-servico" method="post" action="servicos.php" novalidate>
                        <input type="hidden" name="acao" value="salvar">
                        <input type="hidden" name="id_servico" value="<?= $editando ? (int) ($servicoEdicao['id_servico'] ?? $formOld['id_servico'] ?? 0) : 0 ?>">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="id_empresa" class="form-label">Empresa <span class="text-danger">*</span></label>
                                <select class="form-select" id="id_empresa" name="id_empresa" required>
                                    <option value="">Selecione</option>
                                    <?php foreach ($empresas as $empresa): ?>
                                        <?php if ($empresa['status'] !== 'Ativo' && (int) $empresa['id_empresa'] !== $idEmpresaAtual): ?>
                                            <?php continue; ?>
                                        <?php endif; ?>
                                        <option value="<?= (int) $empresa['id_empresa'] ?>"
                                            <?= (int) $empresa['id_empresa'] === $idEmpresaAtual ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($empresa['nome_empresa']) ?>
                                            <?= $empresa['status'] !== 'Ativo' ? ' (inativa)' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Selecione a empresa.</div>
                            </div>

                            <div class="col-md-6">
                                <label for="data_servico" class="form-label">Data <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="data_servico" name="data_servico"
                                       value="<?= $dataPadrao ?>" required>
                                <div class="invalid-feedback">Informe a data do serviço.</div>
                            </div>

                            <div class="col-md-6">
                                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select" id="status" name="status" required>
                                    <?php foreach ($statusOpcoes as $opcao): ?>
                                        <option value="<?= htmlspecialchars($opcao) ?>" <?= $statusAtual === $opcao ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($opcao) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="valor_total" class="form-label">Valor total <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="valor_total" name="valor_total"
                                       value="<?= campoFormulario('valor_total', $servicoEdicao, $formOld) ?>"
                                       placeholder="0,00" required min="0" step="0.01">
                                <div class="invalid-feedback">Informe o valor total.</div>
                            </div>

                            <div class="col-12">
                                <label for="descricao" class="form-label">Descrição <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="descricao" name="descricao" rows="8"
                                          placeholder="Digite a descrição"><?= campoFormulario('descricao', $servicoEdicao, $formOld) ?></textarea>
                                <div class="invalid-feedback">Informe a descrição do serviço.</div>
                            </div>

                            <div class="col-12">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                    <label class="form-label mb-0">Produtos utilizados</label>
                                    <?php if (count($produtosEstoque) > 0): ?>
                                        <input type="search" class="form-control form-control-sm" id="filtro-produtos-servico"
                                               placeholder="Buscar produto" autocomplete="off" style="width: 180px;">
                                    <?php endif; ?>
                                </div>
                                <?php if (count($produtosEstoque) === 0): ?>
                                    <p class="text-muted small mb-0">Nenhum produto no estoque.</p>
                                <?php else: ?>
                                    <div class="table-responsive border rounded lista-produtos-servico">
                                        <table class="table table-sm align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Produto</th>
                                                    <th style="width: 120px;">Quantidade</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($produtosEstoque as $produto): ?>
                                                    <?php
                                                    $idProduto = (int) $produto['id_estoque'];
                                                    $marcado = array_key_exists($idProduto, $produtosSelecionados);
                                                    $qtdProduto = $marcado ? $produtosSelecionados[$idProduto] : 1;
                                                    ?>
                                                    <tr class="linha-produto-servico"
                                                        data-nome="<?= htmlspecialchars(mb_strtolower((string) $produto['nome_produto'])) ?>">
                                                        <td>
                                                            <div class="form-check mb-0">
                                                                <input class="form-check-input" type="checkbox"
                                                                       name="produtos[]" value="<?= $idProduto ?>"
                                                                       id="produto-<?= $idProduto ?>"
                                                                       <?= $marcado ? 'checked' : '' ?>>
                                                                <label class="form-check-label" for="produto-<?= $idProduto ?>">
                                                                    <?= htmlspecialchars($produto['nome_produto']) ?>
                                                                </label>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <input type="number" class="form-control form-control-sm"
                                                                   name="qtd[<?= $idProduto ?>]" min="1" step="1"
                                                                   value="<?= $qtdProduto ?>">
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                <tr id="filtro-produtos-servico-vazio" class="d-none">
                                                    <td colspan="2" class="text-muted">Nenhum produto encontrado.</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i>
                                <?= $editando ? 'Salvar alterações' : 'Salvar serviço' ?>
                            </button>

                            <?php if ($editando): ?>
                                <a href="servicos.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-lg me-1"></i> Cancelar edição
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="tab-pane fade <?= $aba === 'visualizar' ? 'show active' : '' ?>" id="painel-visualizar" role="tabpanel">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <h5 class="card-title mb-0 fw-semibold">
                        <i class="bi bi-table me-2 text-secondary"></i>Serviços cadastrados
                    </h5>
                    <?php if (count($servicos) > 0): ?>
                        <div class="d-flex gap-2">
                            <input type="search" class="form-control form-control-sm" id="filtro-servicos-busca"
                                   placeholder="Buscar" autocomplete="off" style="width: 180px;">
                            <select class="form-select form-select-sm" id="filtro-servicos-status" style="width: 160px;">
                                <option value="">Status</option>
                                <?php foreach ($statusOpcoes as $opcao): ?>
                                    <option value="<?= htmlspecialchars($opcao) ?>"><?= htmlspecialchars($opcao) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="table-responsive lista-visualizar">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 70px;">ID</th>
                            <th>Descrição</th>
                            <th>Empresa</th>
                            <th>Data</th>
                            <th>Status</th>
                            <th>Valor</th>
                            <th class="text-end" style="width: 60px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($servicos) === 0): ?>
                            <tr>
                                <td colspan="7" class="text-muted">Nenhum serviço cadastrado.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($servicos as $servico): ?>
                                <?php
                                $dataBr = DateTime::createFromFormat('Y-m-d', (string) $servico['data_servico']);
                                $dataExibicao = $dataBr instanceof DateTime ? $dataBr->format('d/m/Y') : (string) $servico['data_servico'];
                                $descricaoLista = trim(html_entity_decode(strip_tags((string) $servico['descricao']), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                                $idServico = (int) $servico['id_servico'];
                                $busca = mb_strtolower(trim($idServico . ' ' . $descricaoLista . ' ' . (string) $servico['nome_empresa']));
                                ?>
                                <tr class="linha-servico"
                                    data-busca="<?= htmlspecialchars($busca) ?>"
                                    data-status="<?= htmlspecialchars((string) $servico['status']) ?>">
                                    <td><?= $idServico ?></td>
                                    <td class="fw-semibold"><?= $descricaoLista !== '' ? htmlspecialchars($descricaoLista) : '—' ?></td>
                                    <td><?= htmlspecialchars((string) $servico['nome_empresa']) ?></td>
                                    <td><?= htmlspecialchars($dataExibicao) ?></td>
                                    <td>
                                        <span class="badge rounded-pill <?= badge_status_admin((string) $servico['status']) ?>">
                                            <?= htmlspecialchars((string) $servico['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= formatar_moeda($servico['valor_total']) ?></td>
                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn-acoes" type="button" data-bs-toggle="dropdown"
                                                    data-bs-popper-config='{"strategy":"fixed"}' aria-label="Ações">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                <li>
                                                    <a class="dropdown-item" href="servicos.php?edit=<?= (int) $servico['id_servico'] ?>">
                                                        <i class="bi bi-pencil me-2"></i>Editar
                                                    </a>
                                                </li>
                                                <li>
                                                    <form method="post" action="servicos.php" class="form-excluir-servico m-0"
                                                          data-nome="<?= htmlspecialchars($descricaoLista !== '' ? $descricaoLista : 'este serviço') ?>">
                                                        <input type="hidden" name="acao" value="excluir">
                                                        <input type="hidden" name="id_servico" value="<?= (int) $servico['id_servico'] ?>">
                                                        <button type="submit" class="dropdown-item text-danger">
                                                            <i class="bi bi-trash me-2"></i>Excluir
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr id="filtro-servicos-vazio" class="d-none">
                                <td colspan="7" class="text-muted">Nenhum serviço encontrado com esse filtro.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>
