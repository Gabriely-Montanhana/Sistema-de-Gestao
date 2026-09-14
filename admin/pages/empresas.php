<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../config/pdo.php';

function redirectEmpresas(string $aba = 'cadastro', int $editId = 0): never
{
    $url = 'empresas.php?aba=' . urlencode($aba);

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

function empresaTemServico(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare('SELECT id_servico FROM servicos WHERE id_empresa = :id LIMIT 1');
    $stmt->execute([':id' => $id]);

    return (bool) $stmt->fetch();
}

function cnpjEmUso(PDO $pdo, string $cnpj, int $ignoreId = 0): bool
{
    $sql = 'SELECT id_empresa FROM empresas WHERE cnpj = :cnpj';
    $params = [':cnpj' => $cnpj];

    if ($ignoreId > 0) {
        $sql .= ' AND id_empresa != :id';
        $params[':id'] = $ignoreId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return (bool) $stmt->fetch();
}

function tipoDocumentoAtual(?array $empresa, array $old): string
{
    if (($old['tipo_documento'] ?? '') === 'cpf' || ($old['tipo_documento'] ?? '') === 'cnpj') {
        return (string) $old['tipo_documento'];
    }

    $digitos = preg_replace('/\D+/', '', (string) ($empresa['cnpj'] ?? '')) ?? '';

    return strlen($digitos) === 11 ? 'cpf' : 'cnpj';
}

function campoFormulario(string $campo, ?array $empresa, array $old): string
{
    if (array_key_exists($campo, $old)) {
        return htmlspecialchars(trim((string) $old[$campo]));
    }

    if ($empresa !== null && array_key_exists($campo, $empresa)) {
        return htmlspecialchars(trim((string) ($empresa[$campo] ?? '')));
    }

    return '';
}

$pdo = getConnection();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $acao = trim((string) ($_POST['acao'] ?? 'salvar'));
    $id = 0;

    try {
        if ($acao === 'salvar') {
            $id = (int) ($_POST['id_empresa'] ?? 0);
            $nome = trim((string) ($_POST['nome_empresa'] ?? ''));
            $cnpj = trim((string) ($_POST['cnpj'] ?? ''));
            $tipoDocumento = ($_POST['tipo_documento'] ?? 'cnpj') === 'cpf' ? 'cpf' : 'cnpj';
            $digitosDocumento = preg_replace('/\D+/', '', $cnpj) ?? '';
            $rotuloDocumento = $tipoDocumento === 'cpf' ? 'CPF' : 'CNPJ';

            if ($nome === '' || $cnpj === '') {
                throw new InvalidArgumentException('Nome e ' . $rotuloDocumento . ' são obrigatórios.');
            }

            if ($tipoDocumento === 'cpf' && strlen($digitosDocumento) !== 11) {
                throw new InvalidArgumentException('Informe um CPF válido.');
            }

            if ($tipoDocumento === 'cnpj' && strlen($digitosDocumento) !== 14) {
                throw new InvalidArgumentException('Informe um CNPJ válido.');
            }

            if (cnpjEmUso($pdo, $cnpj, $id)) {
                throw new InvalidArgumentException($rotuloDocumento . ' já cadastrado.');
            }

            $dados = [
                ':nome' => $nome,
                ':cnpj' => $cnpj,
                ':cidade' => trim((string) ($_POST['cidade'] ?? '')) ?: null,
                ':endereco' => trim((string) ($_POST['endereco'] ?? '')) ?: null,
                ':telefone' => trim((string) ($_POST['telefone'] ?? '')) ?: null,
                ':email' => trim((string) ($_POST['email'] ?? '')) ?: null,
            ];

            if ($id > 0) {
                $dados[':id'] = $id;
                $stmt = $pdo->prepare('
                    UPDATE empresas
                    SET nome_empresa = :nome,
                        cnpj = :cnpj,
                        cidade = :cidade,
                        endereco = :endereco,
                        telefone = :telefone,
                        email = :email
                    WHERE id_empresa = :id
                ');
                $stmt->execute($dados);

                if ($stmt->rowCount() === 0) {
                    throw new InvalidArgumentException('Empresa não encontrada.');
                }

                setFlash('success', 'Empresa atualizada com sucesso!');
            } else {
                $stmt = $pdo->prepare('
                    INSERT INTO empresas (nome_empresa, cnpj, cidade, endereco, telefone, email, status)
                    VALUES (:nome, :cnpj, :cidade, :endereco, :telefone, :email, :status)
                ');
                $stmt->execute($dados + [':status' => 'Ativo']);
                setFlash('success', 'Empresa cadastrada com sucesso!');
            }

            redirectEmpresas('cadastro');
        }

        if ($acao === 'excluir') {
            $id = (int) ($_POST['id_empresa'] ?? 0);

            if ($id <= 0) {
                throw new InvalidArgumentException('Empresa inválida.');
            }

            $stmt = $pdo->prepare('SELECT id_empresa, status FROM empresas WHERE id_empresa = :id');
            $stmt->execute([':id' => $id]);
            $empresa = $stmt->fetch();

            if (!$empresa) {
                throw new InvalidArgumentException('Empresa não encontrada.');
            }

            if (empresaTemServico($pdo, $id)) {
                if ($empresa['status'] !== 'Inativo') {
                    $stmt = $pdo->prepare("UPDATE empresas SET status = 'Inativo' WHERE id_empresa = :id");
                    $stmt->execute([':id' => $id]);
                }

                setFlash('success', 'Esta empresa possui serviços e não pode ser excluída. Ela foi desativada.');
                redirectEmpresas('visualizar');
            }

            $stmt = $pdo->prepare('DELETE FROM empresas WHERE id_empresa = :id');
            $stmt->execute([':id' => $id]);

            if ($stmt->rowCount() === 0) {
                throw new InvalidArgumentException('Empresa não encontrada.');
            }

            setFlash('success', 'Empresa excluída com sucesso!');
            redirectEmpresas('visualizar');
        }

        if ($acao === 'ativar' || $acao === 'inativar') {
            $id = (int) ($_POST['id_empresa'] ?? 0);
            $status = $acao === 'ativar' ? 'Ativo' : 'Inativo';

            if ($id <= 0) {
                throw new InvalidArgumentException('Empresa inválida.');
            }

            $stmt = $pdo->prepare('UPDATE empresas SET status = :status WHERE id_empresa = :id');
            $stmt->execute([':id' => $id, ':status' => $status]);

            if ($stmt->rowCount() === 0) {
                throw new InvalidArgumentException('Empresa não encontrada.');
            }

            setFlash('success', $acao === 'ativar' ? 'Empresa ativada com sucesso!' : 'Empresa inativada com sucesso!');
            redirectEmpresas('visualizar');
        }

        throw new InvalidArgumentException('Ação inválida.');
    } catch (Throwable $e) {
        setFlash('error', $e->getMessage());

        if ($acao === 'salvar') {
            $_SESSION['form_old'] = $_POST;
            redirectEmpresas('cadastro', $id);
        }

        redirectEmpresas('visualizar');
    }
}

$empresas = $pdo->query("
    SELECT e.*,
           EXISTS(SELECT 1 FROM servicos s WHERE s.id_empresa = e.id_empresa) AS tem_servico
    FROM empresas e
    ORDER BY FIELD(e.status, 'Inativo', 'Ativo'), e.nome_empresa
")->fetchAll();

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$empresaEdicao = null;

if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM empresas WHERE id_empresa = ?');
    $stmt->execute([$editId]);
    $empresaEdicao = $stmt->fetch() ?: null;
}

$aba = $_GET['aba'] ?? 'cadastro';
if ($empresaEdicao !== null) {
    $aba = 'cadastro';
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$formOld = $_SESSION['form_old'] ?? [];
unset($_SESSION['form_old']);

$editando = $empresaEdicao !== null || (int) ($formOld['id_empresa'] ?? 0) > 0;

if (!$empresaEdicao && $editando) {
    $editId = (int) ($formOld['id_empresa'] ?? 0);
    if ($editId > 0) {
        $stmt = $pdo->prepare('SELECT * FROM empresas WHERE id_empresa = ?');
        $stmt->execute([$editId]);
        $empresaEdicao = $stmt->fetch() ?: null;
    }
}

$tipoDocumento = tipoDocumentoAtual($empresaEdicao, $formOld);

$pageTitle = 'Empresas';
$currentPage = 'empresas';
$pageScript = 'pages/empresas.js';

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
            <h2 class="fw-bold mb-0">Empresas</h2>
            <p class="text-muted mb-0 mt-1">Cadastro de empresas e clientes</p>
        </div>
        <ul class="nav nav-pills mb-0 flex-shrink-0 flex-wrap justify-content-end" id="empresasTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $aba === 'cadastro' ? 'active' : '' ?>" id="tab-adicionar" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#painel-adicionar">
                    <i class="bi bi-plus"></i> Adicionar Empresa
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $aba === 'visualizar' ? 'active' : '' ?>" id="tab-visualizar" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#painel-visualizar">
                    <i class="bi bi-eye"></i> Visualizar Empresas
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
                    <i class="bi bi-<?= $editando ? 'pencil-square' : 'building-add' ?> me-2 text-secondary"></i>
                    <?= $editando ? 'Editar empresa' : 'Nova empresa' ?>
                </h5>
            </div>
            <div class="card-body">
                <form id="form-empresa" method="post" action="empresas.php" novalidate>
                    <input type="hidden" name="acao" value="salvar">
                    <input type="hidden" name="id_empresa" value="<?= $editando ? (int) ($empresaEdicao['id_empresa'] ?? $formOld['id_empresa'] ?? 0) : 0 ?>">

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="nome_empresa" class="form-label">Nome da empresa ou cliente <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nome_empresa" name="nome_empresa"
                                   value="<?= campoFormulario('nome_empresa', $empresaEdicao, $formOld) ?>"
                                   placeholder="Ex.: Metalúrgica Silva" required maxlength="255">
                            <div class="invalid-feedback">Informe o nome da empresa.</div>
                        </div>

                        <div class="col-md-6">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                <label for="cnpj" class="form-label mb-0" id="label-documento">
                                    <?= $tipoDocumento === 'cpf' ? 'CPF' : 'CNPJ' ?> <span class="text-danger">*</span>
                                </label>
                                <div class="btn-group btn-group-sm" role="group" aria-label="Tipo de documento">
                                    <input type="radio" class="btn-check" name="tipo_documento" id="tipo-cnpj" value="cnpj"
                                           <?= $tipoDocumento === 'cnpj' ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-secondary" for="tipo-cnpj">CNPJ</label>
                                    <input type="radio" class="btn-check" name="tipo_documento" id="tipo-cpf" value="cpf"
                                           <?= $tipoDocumento === 'cpf' ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-secondary" for="tipo-cpf">CPF</label>
                                </div>
                            </div>
                            <input type="text" class="form-control" id="cnpj" name="cnpj"
                                   value="<?= campoFormulario('cnpj', $empresaEdicao, $formOld) ?>"
                                   placeholder="<?= $tipoDocumento === 'cpf' ? '000.000.000-00' : '00.000.000/0000-00' ?>"
                                   required maxlength="20">
                            <div class="invalid-feedback" id="feedback-documento">
                                Informe o <?= $tipoDocumento === 'cpf' ? 'CPF' : 'CNPJ' ?>.
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="telefone" class="form-label">Telefone</label>
                            <input type="text" class="form-control" id="telefone" name="telefone"
                                   value="<?= campoFormulario('telefone', $empresaEdicao, $formOld) ?>"
                                   placeholder="(00) 00000-0000" maxlength="20">
                        </div>

                        <div class="col-md-4">
                            <label for="cidade" class="form-label">Cidade</label>
                            <input type="text" class="form-control" id="cidade" name="cidade"
                                   value="<?= campoFormulario('cidade', $empresaEdicao, $formOld) ?>"
                                   placeholder="Ex.: Curitiba" maxlength="100">
                        </div>

                        <div class="col-md-8">
                            <label for="endereco" class="form-label">Endereço</label>
                            <input type="text" class="form-control" id="endereco" name="endereco"
                                   value="<?= campoFormulario('endereco', $empresaEdicao, $formOld) ?>"
                                   placeholder="Rua, número, bairro" maxlength="255">
                        </div>

                        <div class="col-12">
                            <label for="email" class="form-label">E-mail</label>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="<?= campoFormulario('email', $empresaEdicao, $formOld) ?>"
                                   placeholder="contato@empresa.com.br" maxlength="100">
                            <div class="invalid-feedback">Informe um e-mail válido.</div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>
                            <?= $editando ? 'Salvar alterações' : 'Salvar empresa' ?>
                        </button>

                        <?php if ($editando): ?>
                            <a href="empresas.php" class="btn btn-outline-secondary">
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
                        <i class="bi bi-table me-2 text-secondary"></i>Empresas cadastradas
                    </h5>
                    <?php if (count($empresas) > 0): ?>
                        <div class="d-flex gap-2">
                            <input type="search" class="form-control form-control-sm" id="filtro-empresas-busca"
                                   placeholder="Buscar" autocomplete="off" style="width: 180px;">
                            <select class="form-select form-select-sm" id="filtro-empresas-status" style="width: 130px;">
                                <option value="">Status</option>
                                <option value="Ativo">Ativas</option>
                                <option value="Inativo">Inativas</option>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="table-responsive lista-visualizar">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nome</th>
                            <th>CNPJ/CPF</th>
                            <th>Cidade</th>
                            <th>Endereço</th>
                            <th>Telefone</th>
                            <th>E-mail</th>
                            <th>Status</th>
                            <th class="text-end" style="width: 60px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($empresas) === 0): ?>
                            <tr>
                                <td colspan="8" class="text-muted">Nenhuma empresa cadastrada.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($empresas as $empresa): ?>
                                <tr class="linha-empresa"
                                    data-busca="<?= htmlspecialchars(mb_strtolower(trim($empresa['nome_empresa'] . ' ' . $empresa['cnpj'] . ' ' . ($empresa['cidade'] ?? '')))) ?>"
                                    data-status="<?= htmlspecialchars((string) $empresa['status']) ?>">
                                    <td class="fw-semibold"><?= htmlspecialchars($empresa['nome_empresa']) ?></td>
                                    <td><?= htmlspecialchars($empresa['cnpj']) ?></td>
                                    <td><?= htmlspecialchars($empresa['cidade'] ?? '') ?: '—' ?></td>
                                    <td><?= htmlspecialchars($empresa['endereco'] ?? '') ?: '—' ?></td>
                                    <td><?= htmlspecialchars($empresa['telefone'] ?? '') ?: '—' ?></td>
                                    <td><?= htmlspecialchars($empresa['email'] ?? '') ?: '—' ?></td>
                                    <td>
                                        <?php if ($empresa['status'] === 'Ativo'): ?>
                                            <span class="badge rounded-pill text-bg-success">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge rounded-pill text-bg-secondary">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn-acoes" type="button" data-bs-toggle="dropdown"
                                                    data-bs-popper-config='{"strategy":"fixed"}' aria-label="Ações">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                <li>
                                                    <a class="dropdown-item" href="empresas.php?edit=<?= (int) $empresa['id_empresa'] ?>">
                                                        <i class="bi bi-pencil me-2"></i>Editar
                                                    </a>
                                                </li>
                                                <li>
                                                    <?php if ($empresa['status'] === 'Ativo'): ?>
                                                        <form method="post" action="empresas.php" class="form-acao-status m-0"
                                                              data-nome="<?= htmlspecialchars($empresa['nome_empresa']) ?>">
                                                            <input type="hidden" name="acao" value="inativar">
                                                            <input type="hidden" name="id_empresa" value="<?= (int) $empresa['id_empresa'] ?>">
                                                            <button type="submit" class="dropdown-item text-danger">
                                                                <i class="bi bi-slash-circle me-2"></i>Inativar
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <form method="post" action="empresas.php" class="form-acao-status m-0"
                                                              data-nome="<?= htmlspecialchars($empresa['nome_empresa']) ?>">
                                                            <input type="hidden" name="acao" value="ativar">
                                                            <input type="hidden" name="id_empresa" value="<?= (int) $empresa['id_empresa'] ?>">
                                                            <button type="submit" class="dropdown-item text-success">
                                                                <i class="bi bi-check-circle me-2"></i>Ativar
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </li>
                                                <li>
                                                    <form method="post" action="empresas.php" class="form-excluir-empresa m-0"
                                                          data-nome="<?= htmlspecialchars($empresa['nome_empresa']) ?>"
                                                          data-tem-servico="<?= (int) $empresa['tem_servico'] ?>">
                                                        <input type="hidden" name="acao" value="excluir">
                                                        <input type="hidden" name="id_empresa" value="<?= (int) $empresa['id_empresa'] ?>">
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
                            <tr id="filtro-empresas-vazio" class="d-none">
                                <td colspan="8" class="text-muted">Nenhuma empresa encontrada com esse filtro.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>
