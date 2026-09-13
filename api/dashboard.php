<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/pdo.php';

try {
    $pdo = getConnection();

    $indicadores = $pdo->query('SELECT * FROM vw_dashboard_indicadores')->fetch();

    $statusRows = $pdo->query('SELECT * FROM vw_servicos_por_status')->fetchAll();

    $rankingProdutos = $pdo->query('
        SELECT e.nome_produto AS nome, COALESCE(SUM(sp.quantidade), 0) AS total
        FROM estoque e
        LEFT JOIN servico_produtos sp ON sp.id_estoque = e.id_estoque
        GROUP BY e.id_estoque, e.nome_produto
    ')->fetchAll();

    $rankingEmpresas = $pdo->query('
        SELECT emp.nome_empresa AS nome, COUNT(s.id_servico) AS total
        FROM empresas emp
        LEFT JOIN servicos s ON s.id_empresa = emp.id_empresa
        GROUP BY emp.id_empresa, emp.nome_empresa
    ')->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => [
            'indicadores' => $indicadores ?: null,
            'servicos_por_status' => $statusRows,
            'ranking_produtos' => $rankingProdutos,
            'ranking_empresas' => $rankingEmpresas,
        ],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao carregar dashboard: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
