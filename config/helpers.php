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

function texto_simples(?string $html, string $vazio = '—'): string
{
    $texto = trim(html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

    return $texto === '' ? $vazio : htmlspecialchars($texto);
}

function html_seguro(?string $html): string
{
    return trim(strip_tags((string) $html, '<p><br><b><strong><i><em><u><ul><ol><li><a><h3><h4><span>'));
}
