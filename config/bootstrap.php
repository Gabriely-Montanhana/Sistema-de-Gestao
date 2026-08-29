<?php

declare(strict_types=1);

function app_paths(): array
{
    static $cached = null;

    if ($cached !== null) {
        return $cached;
    }

    $scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $projectDisk = str_replace('\\', '/', dirname(__DIR__));

    if (preg_match('#^((?:/[^/]+)*)/(?:admin|api|site)(?:/|$)#', $scriptPath, $matches) === 1) {
        $urlBase = $matches[1] === '' ? '/' : $matches[1] . '/';
    } else {
        $dir = str_replace('\\', '/', dirname($scriptPath));
        $urlBase = rtrim($dir, '/') . '/';

        if ($urlBase === '//') {
            $urlBase = '/';
        }
    }

    $cached = [
        'disk' => $projectDisk,
        'url' => $urlBase,
        'admin_url' => $urlBase . 'admin/',
        'api_url' => $urlBase . 'api/',
        'assets_url' => $urlBase . 'assets/',
    ];

    return $cached;
}
