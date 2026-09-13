<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../config/pdo.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/rotinas.php';

function redirectEstoque(string $aba = 'cadastro', int $editId = 0): never
{
    $url = 'estoque.php?aba=' . urlencode($aba);

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

function campoFormulario(string $campo, ?array $produto, array $old): string
{
    if (array_key_exists($campo, $old)) {
        return htmlspecialchars(trim((string) $old[$campo]));
    }

    if ($produto !== null && array_key_exists($campo, $produto)) {
        return htmlspecialchars(trim((string) ($produto[$campo] ?? '')));
    }

    return '';
}

function produtoEmUso(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare('SELECT id FROM servico_produtos WHERE id_estoque = :id LIMIT 1');
    $stmt->execute([':id' => $id]);

    return (bool) $stmt->fetch();
}

function garantirTabelaMovimentos(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS estoque_movimentos (
          id_movimento INT AUTO_INCREMENT PRIMARY KEY,
          id_estoque INT NOT NULL,
          tipo ENUM('entrada', 'saida') NOT NULL,
          quantidade INT NOT NULL,
          data_movimento DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (id_estoque) REFERENCES estoque(id_estoque) ON DELETE CASCADE
        )
    ");
}

$pdo = getConnection();
garantirTabelaMovimentos($pdo);

try {
    garantir_rotinas_sql($pdo);
} catch (Throwable $e) {
    // Se o MySQL bloquear CREATE FUNCTION, a página ainda abre.
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $acao = trim((string) ($_POST['acao'] ?? 'salvar'));
    $id = 0;

    try {
        if ($acao === 'salvar') {
            $id = (int) ($_POST['id_estoque'] ?? 0);
            $nome = trim((string) ($_POST['nome_produto'] ?? ''));
            $preco = (float) str_replace(',', '.', (string) ($_POST['preco'] ?? '0'));
            $quantidade = (int) ($_POST['quantidade'] ?? 0);

            if ($nome === '') {
                throw new InvalidArgumentException('O nome do produto é obrigatório.');
            }

            if ($preco <= 0) {
                throw new InvalidArgumentException('O preço deve ser maior que zero.');
            }

            if ($quantidade < 0) {
                throw new InvalidArgumentException('A quantidade não pode ser negativa.');
            }

            if ($id > 0) {
                $stmt = $pdo->prepare('SELECT id_estoque FROM estoque WHERE id_estoque = :id');
                $stmt->execute([':id' => $id]);

                if (!$stmt->fetch()) {
                    throw new InvalidArgumentException('Produto não encontrado.');
                }

                $stmt = $pdo->prepare('
                    UPDATE estoque
                    SET nome_produto = :nome,
                        preco = :preco,
                        quantidade = :quantidade
                    WHERE id_estoque = :id
                ');
                $stmt->execute([
                    ':nome' => $nome,
                    ':preco' => $preco,
                    ':quantidade' => $quantidade,
                    ':id' => $id,
                ]);

                setFlash('success', 'Produto atualizado com sucesso!');
            } else {
                $stmt = $pdo->prepare('
                    INSERT INTO estoque (nome_produto, preco, quantidade)
                    VALUES (:nome, :preco, :quantidade)
                ');
                $stmt->execute([
                    ':nome' => $nome,
                    ':preco' => $preco,
                    ':quantidade' => $quantidade,
                ]);
                setFlash('success', 'Produto cadastrado com sucesso!');
            }

            redirectEstoque('cadastro');
        }

        if ($acao === 'excluir') {
            $id = (int) ($_POST['id_estoque'] ?? 0);

            if ($id <= 0) {
                throw new InvalidArgumentException('Produto inválido.');
            }

            if (produtoEmUso($pdo, $id)) {
                throw new InvalidArgumentException('Não é possível excluir: este produto está vinculado a um serviço.');
            }

            $pdo->prepare('DELETE FROM estoque_movimentos WHERE id_estoque = :id')->execute([':id' => $id]);

            $stmt = $pdo->prepare('DELETE FROM estoque WHERE id_estoque = :id');
            $stmt->execute([':id' => $id]);

            if ($stmt->rowCount() === 0) {
                throw new InvalidArgumentException('Produto não encontrado.');
            }

            setFlash('success', 'Produto excluído com sucesso!');
            redirectEstoque('visualizar');
        }

        if ($acao === 'movimentar') {
            $id = (int) ($_POST['id_estoque'] ?? 0);
            $tipo = ($_POST['tipo'] ?? '') === 'saida' ? 'saida' : 'entrada';
            $quantidade = (int) ($_POST['quantidade_movimento'] ?? 0);

            if ($id <= 0) {
                throw new InvalidArgumentException('Selecione o produto.');
            }

            if ($quantidade <= 0) {
                throw new InvalidArgumentException('Informe uma quantidade maior que zero.');
            }

            $stmt = $pdo->prepare('SELECT nome_produto FROM estoque WHERE id_estoque = :id');
            $stmt->execute([':id' => $id]);
            $produto = $stmt->fetch();

            if (!$produto) {
                throw new InvalidArgumentException('Produto não encontrado.');
            }

            $stmt = $pdo->prepare('CALL sp_registrar_movimento(?, ?, ?)');
            $stmt->execute([$id, $tipo, $quantidade]);
            $stmt->closeCursor();

            $rotulo = $tipo === 'entrada' ? 'Entrada' : 'Saída';
            setFlash('success', $rotulo . ' de ' . $quantidade . ' registrada para ' . $produto['nome_produto'] . '.');
            redirectEstoque('movimentar');
        }

        throw new InvalidArgumentException('Ação inválida.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        setFlash('error', $e->getMessage());

        if ($acao === 'salvar') {
            $_SESSION['form_old'] = $_POST;
            redirectEstoque('cadastro', $id);
        }

        if ($acao === 'movimentar') {
            $_SESSION['form_old'] = $_POST;
            redirectEstoque('movimentar');
        }

        redirectEstoque('visualizar');
    }
}

try {
    $produtos = $pdo->query('
        SELECT id_estoque, nome_produto, preco, quantidade, fn_nivel_estoque(quantidade) AS nivel
        FROM estoque
        ORDER BY nome_produto
    ')->fetchAll();
} catch (Throwable $e) {
    $produtos = $pdo->query('SELECT * FROM estoque ORDER BY nome_produto')->fetchAll();

    foreach ($produtos as &$produto) {
        $produto['nivel'] = (int) $produto['quantidade'] <= 5 ? 'baixo' : 'normal';
    }
    unset($produto);
}

$movimentos = $pdo->query('
    SELECT m.tipo, m.quantidade, m.data_movimento, e.nome_produto
    FROM estoque_movimentos m
    INNER JOIN estoque e ON e.id_estoque = m.id_estoque
    ORDER BY m.data_movimento DESC, m.id_movimento DESC
    LIMIT 15
')->fetchAll();

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$produtoEdicao = null;

if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM estoque WHERE id_estoque = ?');
    $stmt->execute([$editId]);
    $produtoEdicao = $stmt->fetch() ?: null;
}

$aba = $_GET['aba'] ?? 'cadastro';
if ($produtoEdicao !== null) {
    $aba = 'cadastro';
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$formOld = $_SESSION['form_old'] ?? [];
unset($_SESSION['form_old']);

$editando = $produtoEdicao !== null || (
    (int) ($formOld['id_estoque'] ?? 0) > 0 && ($formOld['acao'] ?? '') === 'salvar'
);

if (!$produtoEdicao && $editando) {
    $editId = (int) ($formOld['id_estoque'] ?? 0);
    if ($editId > 0) {
        $stmt = $pdo->prepare('SELECT * FROM estoque WHERE id_estoque = ?');
        $stmt->execute([$editId]);
        $produtoEdicao = $stmt->fetch() ?: null;
    }
}

$pageTitle = 'Estoque';
$currentPage = 'estoque';
$pageScript = 'pages/estoque.js';

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
            <h2 class="fw-bold mb-0">Estoque</h2>
            <p class="text-muted mb-0 mt-1">Controle de produtos fabricados</p>
        </div>
        <ul class="nav nav-pills mb-0 flex-shrink-0 flex-wrap justify-content-end" id="estoqueTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $aba === 'cadastro' ? 'active' : '' ?>" id="tab-adicionar" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#painel-adicionar">
                    <i class="bi bi-plus"></i> Adicionar produto
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $aba === 'visualizar' ? 'active' : '' ?>" id="tab-visualizar" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#painel-visualizar">
                    <i class="bi bi-eye"></i> Visualizar estoque
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $aba === 'movimentar' ? 'active' : '' ?>" id="tab-movimentar" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#painel-movimentar">
                    <i class="bi bi-arrow-left-right"></i> Entrada e saída
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
                    <i class="bi bi-<?= $editando ? 'pencil-square' : 'box-seam' ?> me-2 text-secondary"></i>
                    <?= $editando ? 'Editar produto' : 'Novo produto' ?>
                </h5>
            </div>
            <div class="card-body">
                <form id="form-estoque" method="post" action="estoque.php" novalidate>
                    <input type="hidden" name="acao" value="salvar">
                    <input type="hidden" name="id_estoque" value="<?= $editando ? (int) ($produtoEdicao['id_estoque'] ?? $formOld['id_estoque'] ?? 0) : 0 ?>">

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="nome_produto" class="form-label">Nome do produto <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nome_produto" name="nome_produto"
                                   value="<?= campoFormulario('nome_produto', $produtoEdicao, $formOld) ?>"
                                   placeholder="Ex.: Eixo usinado 50mm" required maxlength="255">
                            <div class="invalid-feedback">Informe o nome do produto.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="preco" class="form-label">Preço <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="preco" name="preco"
                                   value="<?= campoFormulario('preco', $produtoEdicao, $formOld) ?>"
                                   placeholder="0,00" required min="0.01" step="0.01">
                            <div class="invalid-feedback">Informe um preço maior que zero.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="quantidade" class="form-label">Quantidade <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="quantidade" name="quantidade"
                                   value="<?= campoFormulario('quantidade', $produtoEdicao, $formOld) ?>"
                                   placeholder="0" required min="0" step="1">
                            <div class="invalid-feedback">Informe a quantidade em estoque.</div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>
                            <?= $editando ? 'Salvar alterações' : 'Salvar produto' ?>
                        </button>

                        <?php if ($editando): ?>
                            <a href="estoque.php" class="btn btn-outline-secondary">
                                <i class="bi bi-x-lg me-1"></i> Cancelar edição
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="tab-pane fade <?= $aba === 'visualizar' ? 'show active' : '' ?>" id="painel-visualizar" role="tabpanel">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <h5 class="card-title mb-0 fw-semibold">
                        <i class="bi bi-table me-2 text-secondary"></i>Produtos em estoque
                    </h5>
                    <?php if (count($produtos) > 0): ?>
                        <div class="d-flex gap-2">
                            <input type="search" class="form-control form-control-sm" id="filtro-estoque-busca"
                                   placeholder="Buscar" autocomplete="off" style="width: 180px;">
                            <select class="form-select form-select-sm" id="filtro-estoque-nivel" style="width: 150px;">
                                <option value="">Estoque</option>
                                <option value="baixo">Baixo</option>
                                <option value="normal">Normal</option>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Produto</th>
                            <th>Preço</th>
                            <th>Quantidade</th>
                            <th class="text-end" style="width: 60px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($produtos) === 0): ?>
                            <tr>
                                <td colspan="4" class="text-muted">Nenhum produto cadastrado.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($produtos as $produto): ?>
                                <?php $nivel = (string) $produto['nivel']; ?>
                                <tr class="linha-produto"
                                    data-nome="<?= htmlspecialchars(mb_strtolower((string) $produto['nome_produto'])) ?>"
                                    data-nivel="<?= htmlspecialchars($nivel) ?>">
                                    <td class="fw-semibold"><?= htmlspecialchars($produto['nome_produto']) ?></td>
                                    <td><?= formatar_moeda($produto['preco']) ?></td>
                                    <td>
                                        <?php if ((int) $produto['quantidade'] <= 5): ?>
                                            <span class="badge rounded-pill text-bg-danger"><?= (int) $produto['quantidade'] ?></span>
                                        <?php else: ?>
                                            <?= (int) $produto['quantidade'] ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn-acoes" type="button" data-bs-toggle="dropdown" aria-label="Ações">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                <li>
                                                    <a class="dropdown-item" href="estoque.php?edit=<?= (int) $produto['id_estoque'] ?>">
                                                        <i class="bi bi-pencil me-2"></i>Editar
                                                    </a>
                                                </li>
                                                <li>
                                                    <form method="post" action="estoque.php" class="form-excluir-produto m-0"
                                                          data-nome="<?= htmlspecialchars($produto['nome_produto']) ?>">
                                                        <input type="hidden" name="acao" value="excluir">
                                                        <input type="hidden" name="id_estoque" value="<?= (int) $produto['id_estoque'] ?>">
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
                            <tr id="filtro-estoque-vazio" class="d-none">
                                <td colspan="4" class="text-muted">Nenhum produto encontrado com esse filtro.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="tab-pane fade <?= $aba === 'movimentar' ? 'show active' : '' ?>" id="painel-movimentar" role="tabpanel">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="card-title mb-0 fw-semibold">
                    <i class="bi bi-arrow-left-right me-2 text-secondary"></i>Registrar movimentação
                </h5>
            </div>
            <div class="card-body">
                <?php if (count($produtos) === 0): ?>
                    <div class="alert alert-warning mb-0">Cadastre um produto antes de registrar entrada ou saída.</div>
                <?php else: ?>
                    <?php
                    $idMovimento = (int) ($formOld['id_estoque'] ?? 0);
                    $tipoMovimento = ($formOld['tipo'] ?? 'entrada') === 'saida' ? 'saida' : 'entrada';
                    $qtdMovimento = htmlspecialchars(trim((string) ($formOld['quantidade_movimento'] ?? '')));
                    ?>
                    <form id="form-movimento" method="post" action="estoque.php" novalidate>
                        <input type="hidden" name="acao" value="movimentar">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="id_estoque_movimento" class="form-label">Produto <span class="text-danger">*</span></label>
                                <select class="form-select" id="id_estoque_movimento" name="id_estoque" required>
                                    <option value="">Selecione</option>
                                    <?php foreach ($produtos as $produto): ?>
                                        <option value="<?= (int) $produto['id_estoque'] ?>"
                                            <?= (int) $produto['id_estoque'] === $idMovimento ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($produto['nome_produto']) ?>
                                            (<?= (int) $produto['quantidade'] ?> un.)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Selecione o produto.</div>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label d-block">Tipo <span class="text-danger">*</span></label>
                                <div class="btn-group" role="group" aria-label="Tipo de movimentação">
                                    <input type="radio" class="btn-check" name="tipo" id="tipo-entrada" value="entrada"
                                           <?= $tipoMovimento === 'entrada' ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-success" for="tipo-entrada">Entrada</label>
                                    <input type="radio" class="btn-check" name="tipo" id="tipo-saida" value="saida"
                                           <?= $tipoMovimento === 'saida' ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-danger" for="tipo-saida">Saída</label>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <label for="quantidade_movimento" class="form-label">Quantidade <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="quantidade_movimento" name="quantidade_movimento"
                                       value="<?= $qtdMovimento ?>" placeholder="0" required min="1" step="1">
                                <div class="invalid-feedback">Informe a quantidade.</div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i> Registrar
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="card-title mb-0 fw-semibold">
                    <i class="bi bi-clock-history me-2 text-secondary"></i>Últimas movimentações
                </h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Data</th>
                            <th>Produto</th>
                            <th>Tipo</th>
                            <th>Quantidade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($movimentos) === 0): ?>
                            <tr>
                                <td colspan="4" class="text-muted">Nenhuma movimentação registrada.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($movimentos as $movimento): ?>
                                <?php
                                $dataMov = DateTime::createFromFormat('Y-m-d H:i:s', (string) $movimento['data_movimento']);
                                $dataExibicao = $dataMov instanceof DateTime ? $dataMov->format('d/m/Y H:i') : (string) $movimento['data_movimento'];
                                $entrada = $movimento['tipo'] === 'entrada';
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($dataExibicao) ?></td>
                                    <td class="fw-semibold"><?= htmlspecialchars((string) $movimento['nome_produto']) ?></td>
                                    <td>
                                        <span class="badge rounded-pill <?= $entrada ? 'text-bg-success' : 'text-bg-danger' ?>">
                                            <?= $entrada ? 'Entrada' : 'Saída' ?>
                                        </span>
                                    </td>
                                    <td><?= $entrada ? '+' : '-' ?><?= (int) $movimento['quantidade'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>
