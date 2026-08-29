<?php

declare(strict_types=1);

function formatar_moeda(float|int|string $valor): string
{
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

function e(?string $valor, string $vazio = '—'): string
{
    $texto = trim((string) $valor);

    return $texto === '' ? $vazio : htmlspecialchars($texto);
}
