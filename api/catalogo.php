<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/pdo.php';

try {
    $pdo = getConnection();

    $empresas = $pdo->query("
        SELECT nome_empresa, cidade, telefone, email
        FROM empresas
        WHERE status = 'Ativo'
        ORDER BY nome_empresa
    ")->fetchAll();

    $produtos = $pdo->query('
        SELECT nome_produto, preco, quantidade
        FROM estoque
        ORDER BY nome_produto
    ')->fetchAll();

    $servicos = $pdo->query("
        SELECT s.descricao, s.status, s.valor_total, e.nome_empresa
        FROM servicos s
        INNER JOIN empresas e ON e.id_empresa = s.id_empresa
        WHERE s.status IN ('Pendente', 'Em Andamento', 'Concluído')
        ORDER BY s.data_servico DESC
        LIMIT 12
    ")->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => [
            'empresas' => $empresas,
            'produtos' => $produtos,
            'servicos' => $servicos,
        ],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao carregar catálogo: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
