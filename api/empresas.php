<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/pdo.php';

function readJsonBody(): array
{
    $body = json_decode(file_get_contents('php://input'), true);
    if (!is_array($body)) {
        throw new InvalidArgumentException('Dados inválidos.');
    }
    return $body;
}

function validateEmpresaFields(array $body, bool $requireId = false): array
{
    $id = (int) ($body['id_empresa'] ?? 0);

    if ($requireId && $id <= 0) {
        throw new InvalidArgumentException('Empresa inválida.');
    }

    $nome = trim((string) ($body['nome_empresa'] ?? ''));
    $cnpj = trim((string) ($body['cnpj'] ?? ''));

    if ($nome === '' || $cnpj === '') {
        throw new InvalidArgumentException('Nome e CNPJ são obrigatórios.');
    }

    $status = 'Ativo';
    if (!$requireId) {
        $status = (string) ($body['status'] ?? 'Ativo');
        if (!in_array($status, ['Ativo', 'Inativo'], true)) {
            $status = 'Ativo';
        }
    }

    return [
        'id' => $id,
        'nome' => $nome,
        'cnpj' => $cnpj,
        'cidade' => trim((string) ($body['cidade'] ?? '')) ?: null,
        'endereco' => trim((string) ($body['endereco'] ?? '')) ?: null,
        'telefone' => trim((string) ($body['telefone'] ?? '')) ?: null,
        'email' => trim((string) ($body['email'] ?? '')) ?: null,
        'status' => $status,
    ];
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

try {
    $pdo = getConnection();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        $stmt = $pdo->query('SELECT * FROM empresas ORDER BY nome_empresa');
        echo json_encode([
            'success' => true,
            'data' => $stmt->fetchAll(),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($method === 'POST') {
        $dados = validateEmpresaFields(readJsonBody());

        if (cnpjEmUso($pdo, $dados['cnpj'])) {
            throw new InvalidArgumentException('CNPJ já cadastrado.');
        }

        $stmt = $pdo->prepare('
            INSERT INTO empresas (nome_empresa, cnpj, cidade, endereco, telefone, email, status)
            VALUES (:nome, :cnpj, :cidade, :endereco, :telefone, :email, :status)
        ');

        $stmt->execute([
            ':nome' => $dados['nome'],
            ':cnpj' => $dados['cnpj'],
            ':cidade' => $dados['cidade'],
            ':endereco' => $dados['endereco'],
            ':telefone' => $dados['telefone'],
            ':email' => $dados['email'],
            ':status' => $dados['status'],
        ]);

        echo json_encode([
            'success' => true,
            'data' => ['id' => (int) $pdo->lastInsertId()],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($method === 'PUT') {
        $dados = validateEmpresaFields(readJsonBody(), true);

        if (cnpjEmUso($pdo, $dados['cnpj'], $dados['id'])) {
            throw new InvalidArgumentException('CNPJ já cadastrado em outra empresa.');
        }

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

        $stmt->execute([
            ':id' => $dados['id'],
            ':nome' => $dados['nome'],
            ':cnpj' => $dados['cnpj'],
            ':cidade' => $dados['cidade'],
            ':endereco' => $dados['endereco'],
            ':telefone' => $dados['telefone'],
            ':email' => $dados['email'],
        ]);

        if ($stmt->rowCount() === 0) {
            throw new InvalidArgumentException('Empresa não encontrada.');
        }

        echo json_encode(['success' => true, 'data' => ['id' => $dados['id']]], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($method === 'PATCH') {
        $body = readJsonBody();
        $id = (int) ($body['id_empresa'] ?? 0);

        if ($id <= 0) {
            throw new InvalidArgumentException('Empresa inválida.');
        }

        $status = (string) ($body['status'] ?? 'Inativo');
        if (!in_array($status, ['Ativo', 'Inativo'], true)) {
            throw new InvalidArgumentException('Status inválido.');
        }

        $stmt = $pdo->prepare('UPDATE empresas SET status = :status WHERE id_empresa = :id');
        $stmt->execute([':id' => $id, ':status' => $status]);

        if ($stmt->rowCount() === 0) {
            throw new InvalidArgumentException('Empresa não encontrada.');
        }

        echo json_encode(['success' => true, 'data' => ['id' => $id]], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método não permitido.',
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
