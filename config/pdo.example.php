<?php

/**
 * Copie este arquivo para pdo.php e ajuste com seus dados do MySQL.
 *
 * Exemplo:
 *   copy config\pdo.example.php config\pdo.php
 */

function getConnection(): PDO
{
    $host = 'localhost';
    $db   = 'gestao';
    $user = 'root';
    $pass = '';

    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    return $pdo;
}
