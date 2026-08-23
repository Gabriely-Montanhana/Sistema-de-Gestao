<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/pdo.php';

try {
    $pdo = getConnection();

    $indicadores = $pdo->query('SELECT * FROM vw_dashboard_indicadores')->fetch();

    $statusRows = $pdo->query('SELECT * FROM vw_servicos_por_status')->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => [
            'indicadores' => $indicadores ?: null,
            'servicos_por_status' => $statusRows,
        ],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao carregar dashboard: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
